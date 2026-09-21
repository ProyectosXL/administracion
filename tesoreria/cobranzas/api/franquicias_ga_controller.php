<?php
/**
 * LIQUIDACION SEMANAL FRANQUICIAS GA  --  ENDPOINT AJAX
 *
 * Toda la logica de calculo vive en los SP de [XL-LAKERBIS].[FRANQUICIAS_LAKERS]
 * (ver sql/02_RO_SP_FRANQ_GA.sql). Este archivo solo valida la entrada, invoca
 * el SP que corresponde y normaliza la salida a JSON.
 *
 * Conexion: perfil 'franquicias' (XL-LAKERBIS / FRANQUICIAS_LAKERS).
 *
 * Acciones:
 *   GET   init                 franquicias del canal + periodo por defecto
 *   GET   resumen              RO_SP_FRANQ_GA_RESUMEN
 *   GET   detalle              RO_SP_FRANQ_GA_DETALLE
 *   GET   recibos              RO_SP_FRANQ_GA_RECIBOS_CANDIDATOS + ya vinculados
 *   POST  vincular_recibo      RO_SP_FRANQ_GA_VINCULAR_RECIBO
 *   POST  desvincular_recibo   RO_SP_FRANQ_GA_DESVINCULAR_RECIBO
 *   POST  generar_lote         RO_SP_FRANQ_GA_GENERAR_LOTE
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesion expirada o no iniciada.']);
    exit;
}

/*  La pestaña vive dentro de index.php, que ya es solo para rol admin. Se
    repite el chequeo aca porque el controller es alcanzable por URL directa y
    genera/modifica registros de liquidacion.                                */
if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para operar la liquidacion de Franquicias GA.']);
    exit;
}

$conn = Database::getConnection('franquicias');
$action = $_GET['action'] ?? '';

/* -------------------------------------------------------------------------
   Helpers
------------------------------------------------------------------------- */

/** Fecha a 'Y-m-d'. La conexion trae fechas como string, pero se cubren los dos casos. */
function fechaISO($v)
{
    if ($v === null || $v === '') {
        return null;
    }
    if ($v instanceof DateTime) {
        return $v->format('Y-m-d');
    }
    return substr((string) $v, 0, 10);
}

/** Fecha+hora a 'Y-m-d H:i:s'. */
function fechaHoraISO($v)
{
    if ($v === null || $v === '') {
        return null;
    }
    if ($v instanceof DateTime) {
        return $v->format('Y-m-d H:i:s');
    }
    return substr((string) $v, 0, 19);
}

/**
 * Lunes a domingo inmediatos anteriores.
 *
 * Replica EXACTAMENTE el criterio de RO_SP_FRANQ_GA_GENERAR_LOTE: se ancla en
 * 1900-01-01 (que fue lunes) en vez de usar 'last monday', para no depender de
 * la configuracion regional de PHP ni de SET DATEFIRST en SQL Server. Si esta
 * cuenta y la del SP se separan, la pantalla mostraria un periodo distinto al
 * que genera el job.
 */
function periodoLunesDomingoAnterior()
{
    $hoy   = new DateTime('today');
    $ancla = new DateTime('1900-01-01');

    $dias              = (int) $ancla->diff($hoy)->days;
    $lunesDeEstaSemana = (clone $ancla)->modify('+' . (intdiv($dias, 7) * 7) . ' days');

    $desde = (clone $lunesDeEstaSemana)->modify('-7 days');
    $hasta = (clone $desde)->modify('+6 days');

    return [$desde->format('Y-m-d'), $hasta->format('Y-m-d')];
}

/** Parametro de fecha opcional: '' o ausente => null. */
function paramFecha($origen, $clave)
{
    $v = trim($origen[$clave] ?? '');
    return $v === '' ? null : $v;
}

/** Parametro entero opcional: '' o ausente => null. */
function paramEntero($origen, $clave)
{
    $v = trim((string) ($origen[$clave] ?? ''));
    return $v === '' ? null : (int) $v;
}

/**
 * Convierte el arreglo de sqlsrv_errors() en un mensaje presentable.
 *
 * Los SP levantan sus validaciones de negocio con THROW 5xxxx ("el recibo ya
 * esta vinculado", "el lote esta ANULADO", etc.). Esos mensajes estan escritos
 * para que los lea el usuario, asi que se devuelven tal cual, sacandoles el
 * prefijo del driver y el nombre del SP.
 *
 * Cualquier otro error (timeout, permisos, deadlock) se registra completo en el
 * log y al usuario le llega un texto generico: el volcado de sqlsrv_errors()
 * expone el driver, la estructura de la base y los nombres de los objetos.
 */
function mensajeErrorSql($errores)
{
    error_log('[franquicias_ga_controller] SQL: ' . print_r($errores, true));

    foreach ((array) $errores as $e) {
        if (isset($e['code']) && (int) $e['code'] >= 50000) {
            $msg = (string) ($e['message'] ?? '');
            $msg = preg_replace('/^\[Microsoft\].*?\[SQL Server\]\s*/', '', $msg);
            $msg = preg_replace('/^RO_SP_[A-Z_]+:\s*/', '', $msg);
            // Al sacar el prefijo el mensaje arranca en minuscula.
            return ucfirst(trim($msg));
        }
    }

    return 'Error de base de datos. El detalle quedó registrado en el log del servidor.';
}

/** Ejecuta una consulta y devuelve todas las filas, o lanza con el error de SQL Server. */
function traerFilas($conn, $sql, $params = [])
{
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        throw new Exception(mensajeErrorSql(sqlsrv_errors()));
    }
    $filas = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $filas[] = $row;
    }
    sqlsrv_free_stmt($stmt);
    return $filas;
}

/* -------------------------------------------------------------------------
   Mail de liquidacion
------------------------------------------------------------------------- */

/** 'Y-m-d' -> 'd/m/Y' para mostrar. */
function fechaAR($iso)
{
    if (!$iso) {
        return '';
    }
    $p = explode('-', substr($iso, 0, 10));
    return count($p) === 3 ? $p[2] . '/' . $p[1] . '/' . $p[0] : $iso;
}

/** Importe en pesos con formato argentino. */
function monedaAR($v)
{
    return '$ ' . number_format((float) $v, 2, ',', '.');
}

/**
 * Junta todo lo que hace falta para el mail de una liquidacion: la fila del
 * resumen (via RO_SP_FRANQ_GA_RESUMEN, que ya calcula importes, comprobantes
 * y ultimo envio), el mail de la franquicia y el cuerpo HTML armado.
 *
 * Es la UNICA funcion que arma el cuerpo. La usan tanto preview_mail como
 * enviar_mail, asi la vista previa es identica a lo que se manda y el
 * servidor nunca confia en HTML que venga del navegador.
 *
 * Devuelve ['resumen' => fila, 'destinatario' => string|null,
 *           'asunto' => string, 'cuerpo_html' => string]
 * o lanza si el lote/sucursal no existe.
 */
function armarMailLiquidacion($conn, $idLote, $nroSucursal)
{
    require_once __DIR__ . '/notificaciones_controller.php';

    /*  Fila del resumen para ESTE lote y sucursal. El SP devuelve todos los
        lotes de la sucursal; se filtra por ID_LOTE en PHP.                   */
    $filas   = traerFilas($conn, "EXEC RO_SP_FRANQ_GA_RESUMEN @NroSucursal = ?", [$nroSucursal]);
    $resumen = null;
    foreach ($filas as $f) {
        if ((int) $f['ID_LOTE'] === (int) $idLote) {
            $resumen = $f;
            break;
        }
    }
    if ($resumen === null) {
        throw new Exception("El lote #$idLote no tiene comprobantes de la sucursal $nroSucursal.");
    }

    if (trim((string) $resumen['ESTADO_LOTE']) === 'ANULADO') {
        throw new Exception('El lote está ANULADO: no se envía la liquidación de un lote anulado.');
    }

    /*  Mail de la franquicia. Pedido explicito: SUCURSALES_LAKERS.MAIL.       */
    $suc = traerFilas($conn, "
        SELECT  MAIL, DESC_SUCURSAL
        FROM    [LOCALES_LAKERS].DBO.SUCURSALES_LAKERS WITH (NOLOCK)
        WHERE   NRO_SUCURSAL = ?", [$nroSucursal]);

    $destinatario = null;
    if (!empty($suc)) {
        $m = trim((string) ($suc[0]['MAIL'] ?? ''));
        $destinatario = ($m !== '' && filter_var($m, FILTER_VALIDATE_EMAIL)) ? $m : null;
    }

    /*  Datos ya formateados y ESCAPADOS. generarCuerpoEmail() inyecta el
        mensaje crudo, sin htmlspecialchars, asi que se escapa aca.          */
    $franquicia   = htmlspecialchars(trim((string) $resumen['DESC_SUCURSAL']), ENT_QUOTES, 'UTF-8');
    $nroSuc       = (int) $resumen['NRO_SUCURS'];
    $desde        = fechaAR($resumen['PERIODO_DESDE']);
    $hasta        = fechaAR($resumen['PERIODO_HASTA']);
    $importe      = (float) $resumen['IMPORTE_TOTAL'];
    $cobrado      = (float) $resumen['IMPORTE_COBRADO'];
    $saldo        = (float) $resumen['SALDO'];
    $cantComp     = (int) $resumen['CANT_COMPROBANTES'];
    $cantRezag    = (int) $resumen['CANT_REZAGADOS'];
    $cantSinPrec  = (int) $resumen['CANT_SIN_PRECIO'];
    $nroLista     = (int) $resumen['NRO_LISTA'];

    $asunto = "Liquidación semanal Franquicias GA - {$resumen['DESC_SUCURSAL']} - {$desde} al {$hasta}";
    $asunto = trim(preg_replace('/\s+/', ' ', $asunto));

    $titulo = "Liquidación semanal - {$franquicia}";

    $filaTabla = function ($etiqueta, $valor, $destacar = false) {
        $estiloVal = $destacar
            ? "font-weight: bold; font-size: 18px; color: #0d6efd;"
            : "font-weight: bold;";
        return "<tr>
            <td style='padding: 8px 12px; border-bottom: 1px solid #eee; color: #666;'>{$etiqueta}</td>
            <td style='padding: 8px 12px; border-bottom: 1px solid #eee; text-align: right; {$estiloVal}'>{$valor}</td>
        </tr>";
    };

    $tabla = "<table width='100%' cellspacing='0' cellpadding='0' style='border: 1px solid #eee; border-radius: 6px; margin: 20px 0; font-size: 15px;'>"
        . $filaTabla('Sucursal', "{$nroSuc} - {$franquicia}")
        . $filaTabla('Período liquidado', "{$desde} al {$hasta}")
        . $filaTabla('Comprobantes incluidos', number_format($cantComp, 0, ',', '.'))
        . ($cantRezag > 0
            ? $filaTabla('De los cuales, de períodos anteriores', number_format($cantRezag, 0, ',', '.'))
            : '')
        . $filaTabla('Lista de precios aplicada', "Lista {$nroLista}")
        . $filaTabla('<strong>Total de la liquidación</strong>', monedaAR($importe), true)
        . ($cobrado > 0
            ? $filaTabla('Ya cobrado', monedaAR($cobrado)) . $filaTabla('Saldo pendiente', monedaAR($saldo), true)
            : '')
        . "</table>";

    $notaSinPrecio = $cantSinPrec > 0
        ? "<p style='color: #b45309; font-size: 14px;'><strong>Atención:</strong> {$cantSinPrec} renglón/es corresponden a artículos sin precio en la Lista {$nroLista} al momento de la liquidación y se incluyeron en $ 0,00. Están marcados en el detalle adjunto.</p>"
        : '';

    $mensaje = "
        <p>Estimados <strong>{$franquicia}</strong>,</p>
        <p>Les acercamos la liquidación semanal correspondiente al período
           <strong>{$desde} al {$hasta}</strong>, valuada a precios de la Lista {$nroLista}
           vigentes al momento de generarla.</p>
        {$tabla}
        {$notaSinPrecio}
        <p>Adjuntamos el <strong>detalle completo de los comprobantes</strong> en formato Excel,
           con fecha, tipo, número, artículo, cantidad, precio unitario e importe de cada renglón.
           Las notas de crédito figuran con importe negativo y ya están descontadas del total.</p>
        <p style='font-size: 14px; color: #666;'>Ante cualquier diferencia o consulta sobre esta liquidación,
           comunicarse con Tesorería de XL Extra Large.</p>
    ";

    $cuerpo = generarCuerpoEmail($titulo, $mensaje);

    return [
        'resumen'      => $resumen,
        'destinatario' => $destinatario,
        'asunto'       => $asunto,
        'cuerpo_html'  => $cuerpo,
    ];
}

/** Deja constancia del intento de envio en RO_T_FRANQ_GA_LOTE_ENVIO. */
function registrarEnvio($conn, $idLote, $nroSucursal, $destinatario, $asunto, $conAdjunto, $ok, $errorDetalle, $usuario)
{
    $sql = "INSERT INTO RO_T_FRANQ_GA_LOTE_ENVIO
                (ID_LOTE, NRO_SUCURS, DESTINATARIO, ASUNTO, CON_ADJUNTO, RESULTADO, ERROR_DETALLE, USUARIO_ENVIA)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = sqlsrv_query($conn, $sql, [
        $idLote,
        $nroSucursal,
        mb_substr($destinatario, 0, 200),
        mb_substr($asunto, 0, 300),
        $conAdjunto ? 1 : 0,
        $ok ? 'OK' : 'ERROR',
        $errorDetalle === null ? null : mb_substr($errorDetalle, 0, 500),
        $usuario,
    ]);

    /*  Si falla el registro no se aborta: el mail ya salio (o ya fallo) y
        eso es lo que hay que informar. Queda en el log del servidor.        */
    if ($stmt === false) {
        error_log('[franquicias_ga_controller] No se pudo registrar el envio: ' . print_r(sqlsrv_errors(), true));
    }
}

/* -------------------------------------------------------------------------
   Router
------------------------------------------------------------------------- */
try {
    switch ($action) {

        // =====================================================================
        // INIT -- franquicias del canal y periodo por defecto para los filtros
        // =====================================================================
        case 'init':
            /*  Se trae tambien la fecha de alta de cada franquicia. Una sin
                fila en RO_T_FRANQ_GA_SUCURSAL_INICIO NO se liquida (ver la
                cabecera de RO_SP_FRANQ_GA_GENERAR_LOTE), asi que la pantalla
                tiene que poder avisarlo en vez de mostrar una grilla corta sin
                explicacion.                                                  */
            $sql_suc = "
                SELECT      S.NRO_SUCURSAL, S.DESC_SUCURSAL, I.FECHA_INICIO
                FROM        [LOCALES_LAKERS].DBO.SUCURSALES_LAKERS S WITH (NOLOCK)
                LEFT JOIN   RO_T_FRANQ_GA_SUCURSAL_INICIO I
                        ON  I.NRO_SUCURS = S.NRO_SUCURSAL
                WHERE       S.CANAL COLLATE Modern_Spanish_CI_AI = 'FRANQUICIAS GA'
                ORDER BY    S.DESC_SUCURSAL
            ";

            $sucursales = [];
            $sinAlta    = [];
            foreach (traerFilas($conn, $sql_suc) as $row) {
                $fila = [
                    'NRO_SUCURSAL'  => (int) $row['NRO_SUCURSAL'],
                    'DESC_SUCURSAL' => trim((string) $row['DESC_SUCURSAL']),
                    'FECHA_INICIO'  => fechaISO($row['FECHA_INICIO']),
                ];
                $sucursales[] = $fila;
                if ($fila['FECHA_INICIO'] === null) {
                    $sinAlta[] = $fila;
                }
            }

            list($desde, $hasta) = periodoLunesDomingoAnterior();

            echo json_encode([
                'success'            => true,
                'sucursales'         => $sucursales,
                'sucursales_sin_alta' => $sinAlta,
                'periodo_desde'      => $desde,
                'periodo_hasta'      => $hasta,
                'estados_lote'       => ['GENERADO', 'COBRADO', 'ANULADO'],
            ]);
            break;

        // =====================================================================
        // RESUMEN -- grilla principal: una fila por lote y franquicia
        // =====================================================================
        case 'resumen':
            $sql = "EXEC RO_SP_FRANQ_GA_RESUMEN
                        @Desde       = ?,
                        @Hasta       = ?,
                        @NroSucursal = ?,
                        @Estado      = ?";

            $estado = trim($_GET['estado'] ?? '');

            $params = [
                paramFecha($_GET, 'desde'),
                paramFecha($_GET, 'hasta'),
                paramEntero($_GET, 'nro_sucursal'),
                $estado === '' ? null : $estado,
            ];

            $data = [];
            foreach (traerFilas($conn, $sql, $params) as $row) {
                $data[] = [
                    'ID_LOTE'           => (int) $row['ID_LOTE'],
                    'PERIODO_DESDE'     => fechaISO($row['PERIODO_DESDE']),
                    'PERIODO_HASTA'     => fechaISO($row['PERIODO_HASTA']),
                    'FECHA_EJECUCION'   => fechaHoraISO($row['FECHA_EJECUCION']),
                    'NRO_LISTA'         => (int) $row['NRO_LISTA'],
                    'ESTADO_LOTE'       => trim((string) $row['ESTADO_LOTE']),
                    'USUARIO_GENERA'    => trim((string) $row['USUARIO_GENERA']),
                    'NRO_SUCURS'        => (int) $row['NRO_SUCURS'],
                    'DESC_SUCURSAL'     => trim((string) $row['DESC_SUCURSAL']),
                    'CANT_RENGLONES'    => (int) $row['CANT_RENGLONES'],
                    'CANT_COMPROBANTES' => (int) $row['CANT_COMPROBANTES'],
                    'CANT_REZAGADOS'    => (int) $row['CANT_REZAGADOS'],
                    'CANT_SIN_PRECIO'   => (int) $row['CANT_SIN_PRECIO'],
                    'IMPORTE_TOTAL'     => (float) $row['IMPORTE_TOTAL'],
                    'CANT_RECIBOS'      => (int) $row['CANT_RECIBOS'],
                    'IMPORTE_COBRADO'   => (float) $row['IMPORTE_COBRADO'],
                    'SALDO'             => (float) $row['SALDO'],
                    'ESTADO_SUCURSAL'   => trim((string) $row['ESTADO_SUCURSAL']),
                    /*  Ultimo envio por mail OK (NULL = nunca se envio). */
                    'ULTIMO_ENVIO_FECHA'   => fechaHoraISO($row['ULTIMO_ENVIO_FECHA'] ?? null),
                    'ULTIMO_ENVIO_DEST'    => isset($row['ULTIMO_ENVIO_DEST']) ? trim((string) $row['ULTIMO_ENVIO_DEST']) : null,
                    'ULTIMO_ENVIO_USUARIO' => isset($row['ULTIMO_ENVIO_USUARIO']) ? trim((string) $row['ULTIMO_ENVIO_USUARIO']) : null,
                    'CANT_ENVIOS'          => (int) ($row['CANT_ENVIOS'] ?? 0),
                ];
            }

            /*  Totales del conjunto filtrado, para la fila de pie de la grilla.
                Se calculan aca y no en JS para que el Excel exportado y la
                pantalla muestren el mismo numero.                            */
            $tot = ['importe' => 0.0, 'cobrado' => 0.0, 'saldo' => 0.0, 'comprobantes' => 0, 'rezagados' => 0];
            foreach ($data as $d) {
                $tot['importe']      += $d['IMPORTE_TOTAL'];
                $tot['cobrado']      += $d['IMPORTE_COBRADO'];
                $tot['saldo']        += $d['SALDO'];
                $tot['comprobantes'] += $d['CANT_COMPROBANTES'];
                $tot['rezagados']    += $d['CANT_REZAGADOS'];
            }

            echo json_encode(['success' => true, 'data' => $data, 'totales' => $tot]);
            break;

        // =====================================================================
        // DETALLE -- comprobantes de un lote
        // =====================================================================
        case 'detalle':
            $idLote = paramEntero($_GET, 'id_lote');
            if ($idLote === null || $idLote <= 0) {
                throw new Exception('Falta el ID del lote.');
            }

            $sql = "EXEC RO_SP_FRANQ_GA_DETALLE @IdLote = ?, @NroSucursal = ?";
            $params = [$idLote, paramEntero($_GET, 'nro_sucursal')];

            $data     = [];
            $cabecera = null;
            $tot      = ['importe' => 0.0, 'renglones' => 0, 'rezagados' => 0, 'sin_precio' => 0];

            foreach (traerFilas($conn, $sql, $params) as $row) {
                if ($cabecera === null) {
                    $cabecera = [
                        'ID_LOTE'       => (int) $row['ID_LOTE'],
                        'PERIODO_DESDE' => fechaISO($row['PERIODO_DESDE']),
                        'PERIODO_HASTA' => fechaISO($row['PERIODO_HASTA']),
                        'ESTADO_LOTE'   => trim((string) $row['ESTADO_LOTE']),
                    ];
                }

                $importe = (float) $row['IMPORTE'];

                $data[] = [
                    'ID_DETALLE'       => (int) $row['ID_DETALLE'],
                    'NRO_SUCURS'       => (int) $row['NRO_SUCURS'],
                    'DESC_SUCURSAL'    => trim((string) $row['DESC_SUCURSAL']),
                    'FECHA_EMIS'       => fechaISO($row['FECHA_EMIS']),
                    'T_COMP'           => trim((string) $row['T_COMP']),
                    'N_COMP'           => trim((string) $row['N_COMP']),
                    'COD_ARTICU'       => trim((string) $row['COD_ARTICU']),
                    'CANTIDAD'         => (float) $row['CANTIDAD'],
                    'PRECIO_UNITARIO'  => (float) $row['PRECIO_UNITARIO'],
                    'IMPORTE'          => $importe,
                    'ES_REZAGADO'      => (int) $row['ES_REZAGADO'],
                    'SIN_PRECIO_LISTA' => (int) $row['SIN_PRECIO_LISTA'],
                    'CLASE_COMP'       => trim((string) $row['CLASE_COMP']),
                ];

                $tot['importe']   += $importe;
                $tot['renglones'] += 1;
                if ((int) $row['ES_REZAGADO'] === 1) {
                    $tot['rezagados'] += 1;
                }
                if ((int) $row['SIN_PRECIO_LISTA'] === 1) {
                    $tot['sin_precio'] += 1;
                }
            }

            echo json_encode([
                'success'  => true,
                'cabecera' => $cabecera,
                'data'     => $data,
                'totales'  => $tot,
            ]);
            break;

        // =====================================================================
        // RECIBOS -- candidatos a vincular + los ya vinculados al lote
        // =====================================================================
        case 'recibos':
            $idLote = paramEntero($_GET, 'id_lote');
            if ($idLote === null || $idLote <= 0) {
                throw new Exception('Falta el ID del lote.');
            }
            $nroSucursal = paramEntero($_GET, 'nro_sucursal');

            $sql_cand = "EXEC RO_SP_FRANQ_GA_RECIBOS_CANDIDATOS @IdLote = ?, @NroSucursal = ?";

            $candidatos = [];
            $saldo      = 0.0;
            $importe    = 0.0;
            $cobrado    = 0.0;

            foreach (traerFilas($conn, $sql_cand, [$idLote, $nroSucursal]) as $row) {
                $saldo   = (float) $row['SALDO_PENDIENTE'];
                $importe = (float) $row['IMPORTE_LOTE'];
                $cobrado = (float) $row['IMPORTE_COBRADO'];

                $candidatos[] = [
                    'NRO_SUCURS'        => (int) $row['NRO_SUCURS'],
                    'DESC_SUCURSAL'     => trim((string) $row['DESC_SUCURSAL']),
                    'N_COMP_RECIBO'     => trim((string) $row['N_COMP_RECIBO']),
                    'FECHA_EMIS_RECIBO' => fechaISO($row['FECHA_EMIS_RECIBO']),
                    'COD_CLIENT'        => trim((string) $row['COD_CLIENT']),
                    'IMPORTE_RECIBO'    => (float) $row['IMPORTE_RECIBO'],
                    'LEYENDA'           => trim((string) $row['LEYENDA']),
                    'MATCH_EXACTO'      => (int) $row['MATCH_EXACTO'],
                    'DIFERENCIA'        => (float) $row['DIFERENCIA'],
                ];
            }

            /*  Si no hay ningun candidato, el SP no devuelve filas y con ellas
                se pierden los importes del lote. Se recalculan aca para que el
                panel siempre pueda mostrar el saldo.                          */
            if (empty($candidatos)) {
                $sql_saldo = "
                    SELECT  ISNULL((SELECT SUM(D.IMPORTE)
                                      FROM RO_T_FRANQ_GA_LOTE_DETALLE D
                                     WHERE D.ID_LOTE = ?
                                       AND (? IS NULL OR D.NRO_SUCURS = ?)), 0) AS IMPORTE_LOTE,
                            ISNULL((SELECT SUM(R.IMPORTE_RECIBO)
                                      FROM RO_T_FRANQ_GA_LOTE_RECIBO R
                                     WHERE R.ID_LOTE = ?
                                       AND (? IS NULL OR R.NRO_SUCURS = ?)), 0) AS IMPORTE_COBRADO
                ";
                $fila    = traerFilas($conn, $sql_saldo, [
                    $idLote, $nroSucursal, $nroSucursal,
                    $idLote, $nroSucursal, $nroSucursal,
                ]);
                $importe = isset($fila[0]) ? (float) $fila[0]['IMPORTE_LOTE'] : 0.0;
                $cobrado = isset($fila[0]) ? (float) $fila[0]['IMPORTE_COBRADO'] : 0.0;
                $saldo   = $importe - $cobrado;
            }

            /*  Los ya vinculados no salen del SP de candidatos (por definicion
                los excluye). Es un SELECT directo sobre la tabla de vinculos. */
            $sql_vinc = "
                SELECT  R.ID_VINCULO, R.NRO_SUCURS, R.N_COMP_RECIBO,
                        R.FECHA_EMIS_RECIBO, R.IMPORTE_RECIBO, R.TIPO_MATCH,
                        R.USUARIO_VINCULA, R.FECHA_VINCULO
                FROM    RO_T_FRANQ_GA_LOTE_RECIBO R WITH (NOLOCK)
                WHERE   R.ID_LOTE = ?
                  AND   (? IS NULL OR R.NRO_SUCURS = ?)
                ORDER BY R.FECHA_VINCULO DESC
            ";

            $vinculados = [];
            foreach (traerFilas($conn, $sql_vinc, [$idLote, $nroSucursal, $nroSucursal]) as $row) {
                $vinculados[] = [
                    'ID_VINCULO'        => (int) $row['ID_VINCULO'],
                    'NRO_SUCURS'        => (int) $row['NRO_SUCURS'],
                    'N_COMP_RECIBO'     => trim((string) $row['N_COMP_RECIBO']),
                    'FECHA_EMIS_RECIBO' => fechaISO($row['FECHA_EMIS_RECIBO']),
                    'IMPORTE_RECIBO'    => (float) $row['IMPORTE_RECIBO'],
                    'TIPO_MATCH'        => trim((string) $row['TIPO_MATCH']),
                    'USUARIO_VINCULA'   => trim((string) $row['USUARIO_VINCULA']),
                    'FECHA_VINCULO'     => fechaHoraISO($row['FECHA_VINCULO']),
                ];
            }

            echo json_encode([
                'success'         => true,
                'id_lote'         => $idLote,
                'nro_sucursal'    => $nroSucursal,
                'importe_lote'    => $importe,
                'importe_cobrado' => $cobrado,
                'saldo_pendiente' => $saldo,
                'candidatos'      => $candidatos,
                'vinculados'      => $vinculados,
            ]);
            break;

        // =====================================================================
        // VINCULAR -- imputa un recibo al lote
        // =====================================================================
        case 'vincular_recibo':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Esta accion requiere POST.');
            }

            $idLote      = paramEntero($_POST, 'id_lote');
            $nroSucursal = paramEntero($_POST, 'nro_sucursal');
            $nComp       = trim($_POST['n_comp_recibo'] ?? '');

            if ($idLote === null || $nroSucursal === null || $nComp === '') {
                throw new Exception('Faltan datos: lote, sucursal y numero de recibo son obligatorios.');
            }

            /*  El importe NO se manda desde el navegador: el SP lo lee de GVA12.
                Ver la cabecera de RO_SP_FRANQ_GA_VINCULAR_RECIBO.             */
            $sql = "EXEC RO_SP_FRANQ_GA_VINCULAR_RECIBO
                        @IdLote      = ?,
                        @NroSucursal = ?,
                        @NCompRecibo = ?,
                        @TipoMatch   = ?,
                        @Usuario     = ?";

            $params = [
                $idLote,
                $nroSucursal,
                $nComp,
                (trim($_POST['tipo_match'] ?? 'MANUAL') === 'AUTO') ? 'AUTO' : 'MANUAL',
                $_SESSION['usuario_nombre'] ?? null,
            ];

            $filas = traerFilas($conn, $sql, $params);
            $r     = $filas[0] ?? null;

            echo json_encode([
                'success' => true,
                'message' => 'Recibo ' . $nComp . ' vinculado al lote #' . $idLote . '.',
                'data'    => $r === null ? null : [
                    'ID_LOTE'          => (int) $r['ID_LOTE'],
                    'ESTADO_LOTE'      => trim((string) $r['ESTADO_LOTE']),
                    'NRO_SUCURS'       => (int) $r['NRO_SUCURS'],
                    'N_COMP_RECIBO'    => trim((string) $r['N_COMP_RECIBO']),
                    'IMPORTE_RECIBO'   => (float) $r['IMPORTE_RECIBO'],
                    'IMPORTE_SUCURSAL' => (float) $r['IMPORTE_SUCURSAL'],
                    'COBRADO_SUCURSAL' => (float) $r['COBRADO_SUCURSAL'],
                    'SALDO_SUCURSAL'   => (float) $r['SALDO_SUCURSAL'],
                ],
            ]);
            break;

        // =====================================================================
        // DESVINCULAR -- saca la imputacion
        // =====================================================================
        case 'desvincular_recibo':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Esta accion requiere POST.');
            }

            $idVinculo = paramEntero($_POST, 'id_vinculo');
            if ($idVinculo === null || $idVinculo <= 0) {
                throw new Exception('Falta el ID del vinculo a eliminar.');
            }

            $sql = "EXEC RO_SP_FRANQ_GA_DESVINCULAR_RECIBO @IdVinculo = ?, @Usuario = ?";

            $filas = traerFilas($conn, $sql, [$idVinculo, $_SESSION['usuario_nombre'] ?? null]);
            $r     = $filas[0] ?? null;

            echo json_encode([
                'success' => true,
                'message' => 'Recibo desvinculado. El saldo del lote se recalculo.',
                'data'    => $r === null ? null : [
                    'ID_LOTE'          => (int) $r['ID_LOTE'],
                    'ESTADO_LOTE'      => trim((string) $r['ESTADO_LOTE']),
                    'NRO_SUCURS'       => (int) $r['NRO_SUCURS'],
                    'N_COMP_RECIBO'    => trim((string) $r['N_COMP_RECIBO']),
                    'IMPORTE_SUCURSAL' => (float) $r['IMPORTE_SUCURSAL'],
                    'COBRADO_SUCURSAL' => (float) $r['COBRADO_SUCURSAL'],
                    'SALDO_SUCURSAL'   => (float) $r['SALDO_SUCURSAL'],
                ],
            ]);
            break;

        // =====================================================================
        // GENERAR LOTE -- reproceso manual si el job fallo
        // =====================================================================
        case 'generar_lote':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Esta accion requiere POST.');
            }

            /*  Sin fechas, el SP calcula el lunes-domingo anterior por su
                cuenta -- igual que cuando lo dispara el job.                  */
            $desde = paramFecha($_POST, 'desde');
            $hasta = paramFecha($_POST, 'hasta');

            if (($desde === null) !== ($hasta === null)) {
                throw new Exception('Indique las dos fechas del periodo, o ninguna para tomar la semana cerrada anterior.');
            }

            $sql = "EXEC RO_SP_FRANQ_GA_GENERAR_LOTE
                        @Desde   = ?,
                        @Hasta   = ?,
                        @Usuario = ?,
                        @NroLista = ?";

            $params = [$desde, $hasta, $_SESSION['usuario_nombre'] ?? 'WEB', 30];

            $filas = traerFilas($conn, $sql, $params);
            $r     = $filas[0] ?? null;

            if ($r === null) {
                throw new Exception('El SP no devolvio informacion del lote.');
            }

            $yaExistia = (int) $r['YA_EXISTIA'] === 1;

            echo json_encode([
                'success'    => true,
                'ya_existia' => $yaExistia,
                'message'    => $yaExistia
                    ? 'Ya existia un lote para ese periodo (#' . (int) $r['ID_LOTE'] . '). No se genero nada nuevo.'
                    : 'Lote #' . (int) $r['ID_LOTE'] . ' generado correctamente.',
                'data'       => [
                    'ID_LOTE'           => (int) $r['ID_LOTE'],
                    'PERIODO_DESDE'     => fechaISO($r['PERIODO_DESDE']),
                    'PERIODO_HASTA'     => fechaISO($r['PERIODO_HASTA']),
                    'FECHA_EJECUCION'   => fechaHoraISO($r['FECHA_EJECUCION']),
                    'NRO_LISTA'         => (int) $r['NRO_LISTA'],
                    'ESTADO'            => trim((string) $r['ESTADO']),
                    'IMPORTE_TOTAL'     => (float) $r['IMPORTE_TOTAL'],
                    'CANT_RENGLONES'    => (int) $r['CANT_RENGLONES'],
                    'CANT_COMPROBANTES' => (int) $r['CANT_COMPROBANTES'],
                    'CANT_SUCURSALES'   => (int) $r['CANT_SUCURSALES'],
                    'CANT_REZAGADOS'    => (int) $r['CANT_REZAGADOS'],
                    'CANT_SIN_PRECIO'   => (int) $r['CANT_SIN_PRECIO'],
                    'CANT_SUCURSALES_SIN_ALTA' => (int) $r['CANT_SUCURSALES_SIN_ALTA'],
                ],
            ]);
            break;

        // =====================================================================
        // PREVIEW MAIL -- todo lo que el modal necesita mostrar antes de enviar
        // =====================================================================
        case 'preview_mail':
            $idLote      = paramEntero($_GET, 'id_lote');
            $nroSucursal = paramEntero($_GET, 'nro_sucursal');
            if ($idLote === null || $idLote <= 0 || $nroSucursal === null) {
                throw new Exception('Faltan el lote y la sucursal.');
            }

            $m = armarMailLiquidacion($conn, $idLote, $nroSucursal);
            $r = $m['resumen'];

            echo json_encode([
                'success'      => true,
                'destinatario' => $m['destinatario'],
                'asunto'       => $m['asunto'],
                'cuerpo_html'  => $m['cuerpo_html'],
                'lote'         => [
                    'ID_LOTE'           => (int) $r['ID_LOTE'],
                    'PERIODO_DESDE'     => fechaISO($r['PERIODO_DESDE']),
                    'PERIODO_HASTA'     => fechaISO($r['PERIODO_HASTA']),
                    'NRO_SUCURS'        => (int) $r['NRO_SUCURS'],
                    'DESC_SUCURSAL'     => trim((string) $r['DESC_SUCURSAL']),
                    'CANT_COMPROBANTES' => (int) $r['CANT_COMPROBANTES'],
                    'CANT_RENGLONES'    => (int) $r['CANT_RENGLONES'],
                    'IMPORTE_TOTAL'     => (float) $r['IMPORTE_TOTAL'],
                    'SALDO'             => (float) $r['SALDO'],
                ],
                'ultimo_envio' => empty($r['ULTIMO_ENVIO_FECHA']) ? null : [
                    'FECHA'   => fechaHoraISO($r['ULTIMO_ENVIO_FECHA']),
                    'DEST'    => trim((string) $r['ULTIMO_ENVIO_DEST']),
                    'USUARIO' => trim((string) $r['ULTIMO_ENVIO_USUARIO']),
                ],
                'cant_envios'  => (int) ($r['CANT_ENVIOS'] ?? 0),
            ]);
            break;

        // =====================================================================
        // ENVIAR MAIL -- sincrono: la respuesta es el resultado REAL del envio
        // =====================================================================
        case 'enviar_mail':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Esta accion requiere POST.');
            }

            $idLote       = paramEntero($_POST, 'id_lote');
            $nroSucursal  = paramEntero($_POST, 'nro_sucursal');
            $destinatario = trim($_POST['destinatario'] ?? '');
            $asuntoPost   = trim($_POST['asunto'] ?? '');

            if ($idLote === null || $idLote <= 0 || $nroSucursal === null) {
                throw new Exception('Faltan el lote y la sucursal.');
            }
            if ($destinatario === '' || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El destinatario no es una dirección de mail válida: "' . $destinatario . '".');
            }

            /*  El cuerpo se rearma desde la base: el navegador solo manda a
                quien y con que asunto. Ver armarMailLiquidacion().          */
            $m      = armarMailLiquidacion($conn, $idLote, $nroSucursal);
            $asunto = $asuntoPost !== '' ? mb_substr($asuntoPost, 0, 300) : $m['asunto'];

            /*  Adjunto: el xlsx lo genera el navegador con el mismo exportador
                que usa el boton Excel, asi es identico a lo que se descarga.
                Se valida tamaño, nombre y que sea un ZIP (todo xlsx lo es). */
            $adjuntos    = [];
            $adjB64      = $_POST['adjunto_base64'] ?? '';
            $adjNombre   = trim($_POST['adjunto_nombre'] ?? '');

            if ($adjB64 !== '') {
                $bin = base64_decode($adjB64, true);
                if ($bin === false || strlen($bin) < 100) {
                    throw new Exception('El adjunto llegó corrupto o vacío.');
                }
                if (strlen($bin) > 10 * 1024 * 1024) {
                    throw new Exception('El adjunto supera los 10 MB.');
                }
                if (substr($bin, 0, 2) !== 'PK') {
                    throw new Exception('El adjunto no es un archivo Excel válido.');
                }
                if ($adjNombre === '' || !preg_match('/^[A-Za-z0-9_\-\.]+\.xlsx$/', $adjNombre)) {
                    $adjNombre = 'Liquidacion_' . $nroSucursal . '_' . fechaISO($m['resumen']['PERIODO_DESDE']) . '.xlsx';
                }
                $adjuntos[] = [
                    'nombre'    => $adjNombre,
                    'contenido' => $bin,
                    'tipo'      => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ];
            }

            $errorDetalle = null;
            $ok = enviarNotificacion($destinatario, $asunto, $m['cuerpo_html'], $adjuntos, $errorDetalle);

            registrarEnvio(
                $conn, $idLote, $nroSucursal, $destinatario, $asunto,
                !empty($adjuntos), $ok, $errorDetalle,
                $_SESSION['usuario_nombre'] ?? null
            );

            if (!$ok) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El mail NO se pudo enviar. ' . ($errorDetalle ?: 'Sin detalle del servidor de correo.'),
                ]);
                break;
            }

            echo json_encode([
                'success'      => true,
                'message'      => 'Liquidación enviada a ' . $destinatario . (empty($adjuntos) ? '.' : ' con el detalle adjunto.'),
                'destinatario' => $destinatario,
                'con_adjunto'  => !empty($adjuntos),
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Accion no valida: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log('[franquicias_ga_controller] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
