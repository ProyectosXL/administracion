<?php

class Encabezado
{
    private $cid_central;

    /**
     * VALOR_FOB_PESO derivado del FOB en dólares y el tipo de cambio.
     *
     * Es la MISMA regla que recalcularFobPesos() aplica en el navegador, y
     * está acá porque el Valor F.O.B. U$S pasó a ser editable después del
     * alta: desde que ese número se puede corregir, el FOB en pesos no puede
     * seguir saliendo de lo que el cliente informa. Si el formulario mandaba
     * el campo vacío -por ejemplo porque el tipo de cambio no estaba cargado-
     * el UPDATE salteaba la columna y VALOR_FOB_PESO quedaba con el valor del
     * FOB viejo, que es el que después usan el % sobre FOB de los costos de
     * nacionalización y la impresión.
     *
     * URUGUAY: FOB_PESO = FOB_DOLAR, sin multiplicar. Es lo que hace la
     * pantalla y lo que hay en la base: en las 72 filas de uy las dos
     * columnas son iguales y el TIPO_CAMBIO -que ronda 40- no se aplica.
     *
     * @return float|null null cuando no hay con qué calcularlo; en ese caso
     *                    quien llama NO debe tocar la columna, porque escribir
     *                    un cero sería afirmar que el contenedor no vale nada.
     */
    public static function calcularFobPeso($fobDolar, $tipoCambio, $esUruguay = false)
    {
        $fob = floatval(str_replace(',', '.', (string) $fobDolar));

        if ($fob <= 0) {
            return null;
        }

        if ($esUruguay) {
            return round($fob, 2);
        }

        $tc = floatval(str_replace(',', '.', (string) $tipoCambio));

        if ($tc <= 0) {
            return null;
        }

        return round($fob * $tc, 2);
    }

    function __construct()
    {

        require_once __DIR__ . '/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);

    }

    /* ====================================================================
       LA FECHA ESTIMADA DE PAGO FIJADA A MANO

       FECHA_EST_PAGO la calcula el navegador como "fecha base + 5 dias". El
       BIT FECHA_PAGO_CONF -script 10- es lo que dice que ese calculo NO se
       aplica porque alguien puso la fecha a mano.

       Por que un BIT y no un flag deducido de RO_T_IMPORTACIONES_FECHAS_HIST:
       el recalculo automatico y la edicion manual llegan aca por el MISMO POST
       del formulario y dejan en el historial una fila indistinguible. Ver el
       encabezado de sql/10_fecha_pago_manual.sql.
       ==================================================================== */

    /**
     * Dias entre la fecha base de embarque y la estimada de pago.
     *
     * Es la MISMA regla que recalcularFechaEstimadaPago() aplica en el
     * navegador, y esta duplicada: el calculo sigue viviendo en el JS y este
     * valor existe solo para que revertirFechaPagoAuto() pueda devolver la
     * fecha ya resuelta. Si se cambia uno hay que cambiar el otro.
     */
    const DIAS_EMB_EST_PAGO = 5;

    /** @var bool|null Cache por request de si el script 10 ya corrio */
    private $fechaPagoConf = null;

    /**
     * Si el maestro ya tiene la columna FECHA_PAGO_CONF.
     *
     * SE PREGUNTA en vez de darla por hecha, mismo criterio que
     * AlicuotasVigencia::disponible() y que Comex::tieneCotizEdit() en la
     * aplicacion de Finanzas: sin el script 10 la pantalla sigue andando
     * exactamente como hoy -la fecha se recalcula siempre- y lo unico que no se
     * puede es fijarla. Los dos repos se despliegan juntos, pero no se puede
     * asumir que el DDL corrio antes que el codigo.
     *
     * @return bool
     */
    public function tieneFechaPagoConf()
    {
        if ($this->fechaPagoConf !== null) {
            return $this->fechaPagoConf;
        }

        $this->fechaPagoConf = false;

        $stmt = sqlsrv_query(
            $this->cid_central,
            "SELECT COL_LENGTH('dbo.RO_T_IMPORTACIONES_ENCABEZADO', 'FECHA_PAGO_CONF') AS C"
        );

        if ($stmt === false) {
            error_log('tieneFechaPagoConf: ' . print_r(sqlsrv_errors(), true));
            return false;
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        $this->fechaPagoConf = ($row && $row['C'] !== null);

        return $this->fechaPagoConf;
    }

    /**
     * Marca como fijada a mano la fecha estimada de pago de las OCs indicadas.
     *
     * HACEN FALTA LAS DOS COSAS, y por motivos distintos:
     *
     *   1. Que el FRONT diga que lo toco el usuario. Sin esto, cualquier
     *      recalculo automatico -mover el ETD recalcula la fecha y la manda
     *      distinta- quedaria marcado como manual y el contenedor no volveria a
     *      recalcular nunca mas. El marcador es lo unico que separa los dos
     *      casos, porque los dos llegan por el mismo POST.
     *
     *   2. Que el valor REALMENTE difiera del maestro. Es la comparacion que
     *      manda, igual que en Comex::guardarFecha(): el cliente no decide
     *      solo. Tipear la misma fecha que ya estaba no es una edicion, y
     *      marcarla dejaria una fila de auditoria que afirma algo que no paso.
     *
     * NO APAGA EL BIT. Volver a automatico es una decision explicita y tiene su
     * propio endpoint -controller/revertirFechaPagoAuto.php-. Que un guardado
     * cualquiera pudiera apagarlo devolveria el problema original: la fecha
     * fijada se perderia sin que nadie lo pida.
     *
     * @param array  $ids       OCs a marcar
     * @param array  $antes     Estado previo, de leerFechasParaHistorial()
     * @param bool   $marcador  Lo que el front afirma sobre esta edicion
     * @return int Cuantas OCs se marcaron
     */
    private function marcarFechaPagoFijada($ids, $antes, $marcador)
    {
        if (!$marcador || empty($ids) || !$this->tieneFechaPagoConf()) {
            return 0;
        }

        $this->cargarCronogramaFechas();
        $despues = CronogramaFechas::leerFechas($this->cid_central, $ids);

        // Solo las que efectivamente cambiaron de valor.
        $aMarcar = [];
        foreach ($despues as $id => $fila) {
            if (!isset($antes[$id])) continue;

            if ((string) $antes[$id]['FECHA_EST_PAGO'] !== (string) $fila['FECHA_EST_PAGO']) {
                $aMarcar[] = (int) $id;
            }
        }

        if (empty($aMarcar)) {
            return 0;
        }

        $usuario      = isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null;
        $placeholders = implode(',', array_fill(0, count($aMarcar), '?'));

        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                   SET FECHA_PAGO_CONF         = 1,
                       FECHA_PAGO_CONF_USUARIO = ?,
                       FECHA_PAGO_CONF_FECHA   = GETDATE()
                 WHERE ID IN ($placeholders)";

        $stmt = sqlsrv_query($this->cid_central, $sql, array_merge([$usuario], $aMarcar));

        if ($stmt === false) {
            error_log('marcarFechaPagoFijada: ' . print_r(sqlsrv_errors(), true));
            return 0;
        }

        error_log('marcarFechaPagoFijada: ' . count($aMarcar) .
                  ' OCs con FECHA_EST_PAGO fijada a mano');

        return count($aMarcar);
    }

    /**
     * Vuelve la fecha estimada de pago al calculo automatico.
     *
     * LA REGLA DE +5 DIAS SE DUPLICA ACA, y es deuda conocida: la formula
     * canonica vive en recalcularFechaEstimadaPago() de js/cargaInicial.js y
     * este metodo la repite porque el endpoint tiene que devolver la fecha ya
     * resuelta. Moverla al backend -y que el JS deje de calcularla- es lo que
     * cierra esta duplicacion y ademas haria deducible el BIT desde el
     * historial; queda fuera de esta entrega.
     *
     * LA BASE ES LA MISMA QUE EN EL JS: FECHA_EMB -el ETD real- con fallback a
     * FECHA_EST_EMB. Si no hay ninguna de las dos no hay de donde calcular: se
     * apaga el BIT igual -que es lo que se pidio- y la fecha queda como estaba,
     * porque escribir NULL le sacaria el dato a la pestana del cashflow.
     *
     * @param int $id OC cuyo grupo se revierte
     * @return array ['fecha' => 'Y-m-d'|null, 'ocs' => int, 'aviso' => string|null]
     */
    public function revertirFechaPagoAuto($id)
    {
        if (!$this->tieneFechaPagoConf()) {
            throw new Exception(
                'Todavía no se puede volver a automático: falta correr el script '
                . 'comercioExterior/sql/10_fecha_pago_manual.sql en esta base.'
            );
        }

        $idPrincipal = $this->resolverIdPrincipal($id);
        if (!$idPrincipal) {
            throw new Exception('No se encontró el despacho ' . intval($id));
        }

        /* Se revierte TODO EL GRUPO, no la OC sola: el guardado replica
           FECHA_EST_PAGO a todas las OCs del contenedor -ver
           actualizarEncabezadoGrupo()- y dejar media docena de hermanas fijadas
           haria que la proxima edicion volviera a pisar la que se acaba de
           liberar. */
        $idsGrupo = $this->obtenerIdsDelGrupo($idPrincipal);
        if (empty($idsGrupo)) {
            throw new Exception('No se encontró el despacho ' . intval($id));
        }

        $antes = $this->leerFechasParaHistorial($idsGrupo);
        if (empty($antes) || !isset($antes[$idPrincipal])) {
            throw new Exception('No se encontró el despacho ' . intval($id));
        }

        /* Cinco dias, en duro, igual que en el JS. NO sale de
           RO_T_IMPORTACIONES_PARAM_CRONOGRAMA a proposito: el JS no lee esa
           tabla, asi que un parametro configurable haria que el navegador y
           este metodo calcularan fechas distintas sobre el mismo contenedor.
           Cuando la formula se mude al backend -la deuda anotada arriba- ese es
           el momento de hacerla configurable, en un solo lugar. */
        $diasPago = self::DIAS_EMB_EST_PAGO;

        $base = !empty($antes[$idPrincipal]['FECHA_EMB'])
            ? $antes[$idPrincipal]['FECHA_EMB']
            : $antes[$idPrincipal]['FECHA_EST_EMB'];

        $fechaNueva = null;
        $aviso      = null;

        if (!empty($base)) {
            $d = DateTime::createFromFormat('Y-m-d', substr((string) $base, 0, 10));
            if ($d) {
                $d->modify('+' . $diasPago . ' days');
                $fechaNueva = $d->format('Y-m-d');
            }
        }

        if ($fechaNueva === null) {
            $aviso = 'La fecha quedó en automático, pero no se pudo recalcular porque '
                   . 'el contenedor no tiene fecha de embarque ni estimada de embarque. '
                   . 'Se mantiene la fecha que tenía.';
        }

        $placeholders = implode(',', array_fill(0, count($idsGrupo), '?'));

        if (sqlsrv_begin_transaction($this->cid_central) === false) {
            throw new Exception('No se pudo abrir la transacción para revertir la fecha');
        }

        try {
            /* El BIT y la fecha se escriben JUNTOS: si se apagara el BIT y
               fallara el recalculo, el contenedor quedaria diciendo "esta fecha
               es automatica" al lado de una fecha que nadie calculo. */
            $sqlConf = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                           SET FECHA_PAGO_CONF         = 0,
                               FECHA_PAGO_CONF_USUARIO = NULL,
                               FECHA_PAGO_CONF_FECHA   = NULL"
                     . ($fechaNueva !== null ? ", FECHA_EST_PAGO = ?" : "")
                     . " WHERE ID IN ($placeholders)";

            $params = ($fechaNueva !== null)
                ? array_merge([$fechaNueva], $idsGrupo)
                : $idsGrupo;

            $stmt = sqlsrv_query($this->cid_central, $sqlConf, $params);
            if ($stmt === false) {
                throw new Exception('Error al revertir: ' . print_r(sqlsrv_errors(), true));
            }

            sqlsrv_commit($this->cid_central);
        } catch (Throwable $e) {
            sqlsrv_rollback($this->cid_central);
            throw $e;
        }

        /* El historial va DESPUES del commit y con su propio motivo: es el
           mismo criterio que recalcularDistribucion(), donde la cascada
           automatica se registra aparte de lo que edito el usuario. */
        if ($fechaNueva !== null) {
            $usuario = isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null;

            foreach ($idsGrupo as $idOc) {
                if (!isset($antes[$idOc])) continue;
                if ((string) $antes[$idOc]['FECHA_EST_PAGO'] === (string) $fechaNueva) continue;

                CronogramaFechas::registrarHistorial($this->cid_central, [
                    'idEncabezado'  => $idOc,
                    'ordenCompra'   => $antes[$idOc]['ORDEN_COMPRA'],
                    'contenedor'    => $antes[$idOc]['CONTENEDOR'],
                    'campo'         => 'FECHA_EST_PAGO',
                    'valorAnterior' => $antes[$idOc]['FECHA_EST_PAGO'],
                    'valorNuevo'    => $fechaNueva,
                    'motivo'        => MotivosFecha::RECALCULO_AUTOMATICO,
                    'observacion'   => 'Se volvió al cálculo automático (+' . $diasPago . ' días)',
                    'usuario'       => $usuario,
                    'origen'        => 'GESTION_DESPACHOS',
                ]);
            }
        }

        return [
            'fecha' => $fechaNueva,
            'ocs'   => count($idsGrupo),
            'aviso' => $aviso,
        ];
    }

    public function insertarEncabezado($datosDeCabezera)
    {

        $codProv = substr($datosDeCabezera['cod_proveedor'], 0, 6);

        // Sección 1 - Datos Iniciales (campos obligatorios)
        $sql = "INSERT INTO RO_T_IMPORTACIONES_ENCABEZADO(
            FECHA_MOV, COD_PROVEE, PROVEEDOR, CONTENEDOR, MATERIAL, ORIGEN, 
            VALOR_FOB_DOLAR, FECHA_EST_EMB, ORDEN_COMPRA, OCM, DESPACHANTE";

        $values = "VALUES (
            GETDATE(),
            '" . $codProv . "',
            '" . $datosDeCabezera['proveedor'] . "',
            '" . $datosDeCabezera['contenedor'] . "',
            '" . $datosDeCabezera['material'] . "',
            '" . $datosDeCabezera['origen'] . "',
            '" . $datosDeCabezera['valorFobDolar'] . "',
            '" . $datosDeCabezera['fechaEstEmb'] . "',
            '" . $datosDeCabezera['ordenCompra'] . "',
            '" . $datosDeCabezera['ocm'] . "',
            '" . (isset($datosDeCabezera['despachante']) ? $datosDeCabezera['despachante'] : 'Laffitte') . "'";

        // Campos calculados automáticamente que se guardan desde la Sección 1
        if (isset($datosDeCabezera['fechaArr']) && !empty($datosDeCabezera['fechaArr'])) {
            $sql .= ", FECHA_ARR";
            $values .= ", '" . $datosDeCabezera['fechaArr'] . "'";
        }

        if (isset($datosDeCabezera['fechaPago']) && !empty($datosDeCabezera['fechaPago'])) {
            $sql .= ", FECHA_PAGO";
            $values .= ", '" . $datosDeCabezera['fechaPago'] . "'";
        }

        if (isset($datosDeCabezera['fechaDespAdu']) && !empty($datosDeCabezera['fechaDespAdu'])) {
            $sql .= ", FECHA_DESP_ADU";
            $values .= ", '" . $datosDeCabezera['fechaDespAdu'] . "'";
        }

        // Campos opcionales de secciones 2 y 3 (solo si vienen en el array)
        // FECHA_EMB es el ETD (Estimated Time of Departure - fecha de salida real)
        if (isset($datosDeCabezera['fechaEmb']) && !empty($datosDeCabezera['fechaEmb'])) {
            $sql .= ", FECHA_EMB";
            $values .= ", '" . $datosDeCabezera['fechaEmb'] . "'";
        }

        if (isset($datosDeCabezera['numeroBl']) && !empty($datosDeCabezera['numeroBl'])) {
            $sql .= ", NUMERO_BL";
            $values .= ", '" . $datosDeCabezera['numeroBl'] . "'";
        }

        if (isset($datosDeCabezera['facturaProveedor']) && !empty($datosDeCabezera['facturaProveedor'])) {
            $sql .= ", FACTURA";
            $values .= ", '" . $datosDeCabezera['facturaProveedor'] . "'";
        }

        if (isset($datosDeCabezera['tipoCambio']) && !empty($datosDeCabezera['tipoCambio'])) {
            $sql .= ", TIPO_CAMBIO";
            $values .= ", '" . $datosDeCabezera['tipoCambio'] . "'";
        }

        if (isset($datosDeCabezera['valorFobPeso']) && !empty($datosDeCabezera['valorFobPeso'])) {
            $sql .= ", VALOR_FOB_PESO";
            $values .= ", '" . (float) ($datosDeCabezera['valorFobPeso'] + 0.15) . "'";
        }

        if (isset($datosDeCabezera['formaPago']) && !empty($datosDeCabezera['formaPago'])) {
            $sql .= ", FORMA_PAGO";
            $values .= ", '" . $datosDeCabezera['formaPago'] . "'";
        }

        if (isset($datosDeCabezera['despacho']) && !empty($datosDeCabezera['despacho'])) {
            $sql .= ", DESPACHO";
            $values .= ", '" . $datosDeCabezera['despacho'] . "'";
        }

        if (isset($datosDeCabezera['fechaEstPago']) && !empty($datosDeCabezera['fechaEstPago'])) {
            $sql .= ", FECHA_EST_PAGO";
            $values .= ", '" . $datosDeCabezera['fechaEstPago'] . "'";
        }

        if (isset($datosDeCabezera['puertoOrigen']) && !empty($datosDeCabezera['puertoOrigen'])) {
            $sql .= ", PUERTO_ORIGEN";
            $values .= ", '" . $datosDeCabezera['puertoOrigen'] . "'";
        }

        if (isset($datosDeCabezera['terminal']) && !empty($datosDeCabezera['terminal'])) {
            $sql .= ", TERMINAL";
            $values .= ", '" . $datosDeCabezera['terminal'] . "'";
        }

        // ETA Confirmada (bit - 0 o 1)
        if (isset($datosDeCabezera['etaConfirmada'])) {
            $sql .= ", ETA_CONFIRMADA";
            $values .= ", " . $datosDeCabezera['etaConfirmada'];
        }

        $sql .= ") " . $values . ");";

        // Log del SQL para debugging
        error_log("SQL a ejecutar: " . $sql);

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en INSERT: " . print_r($errors, true));
                throw new Exception("Error al insertar: " . $errors[0]['message']);
            }

            $queryIdentity = "SELECT @@IDENTITY as ID";
            $stmtIdentity = sqlsrv_query($this->cid_central, $queryIdentity);

            if ($stmtIdentity === false) {
                error_log("Error al obtener ID: " . print_r(sqlsrv_errors(), true));
                return null;
            }

            $row = sqlsrv_fetch_array($stmtIdentity, SQLSRV_FETCH_ASSOC);
            $id = $row ? $row['ID'] : null;

            error_log("ID insertado: " . $id);
            return $id;

        } catch (Exception $e) {
            error_log('Excepción en insertarEncabezado: ' . $e->getMessage());
            throw $e;
        }

    }

    public function traerOrdenManual()
    {
        $sql = "SELECT MAX(cast (RIGHT(ORDEN_COMPRA,'8')as INT)+1 ) AS nroOrden FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE OCM = 1;";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

            $rows = $v;
        }
        return ($rows);

    }

    public function listarTodosLosDespachos()
    {
        $sql = "SELECT TOP 100
                A.ID,
                FECHA_MOV,
                COD_PROVEE,
                PROVEEDOR,
                CONTENEDOR,
                MATERIAL,
                ORDEN_COMPRA,
                FECHA_EST_EMB,
                FECHA_EMB,
                NUMERO_BL,
                FACTURA,
                A.TIPO_CAMBIO,
                VALOR_FOB_DOLAR
            FROM RO_T_IMPORTACIONES_ENCABEZADO A
            LEFT JOIN RO_T_IMPORTACIONES_DETALLE B ON A.ID = B.ID_MG
            WHERE DESPACHO IS NULL OR B.ID_MG IS NULL
            AND A.FECHA_MOV >= GETDATE()-90
            ORDER BY ID DESC";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                error_log("Error en listarTodosLosDespachos: " . print_r(sqlsrv_errors(), true));
                return [];
            }

            $despachos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir objetos DateTime a strings
                if (isset($row['FECHA_MOV']) && is_object($row['FECHA_MOV'])) {
                    $row['FECHA_MOV'] = $row['FECHA_MOV']->format('Y-m-d');
                }
                if (isset($row['FECHA_EST_EMB']) && is_object($row['FECHA_EST_EMB'])) {
                    $row['FECHA_EST_EMB'] = $row['FECHA_EST_EMB']->format('Y-m-d');
                }
                if (isset($row['FECHA_EMB']) && is_object($row['FECHA_EMB'])) {
                    $row['FECHA_EMB'] = $row['FECHA_EMB']->format('Y-m-d');
                }

                $despachos[] = $row;
            }

            return $despachos;

        } catch (Exception $e) {
            error_log('Error en listarTodosLosDespachos: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerDespachoPorId($id)
    {
        $sql = "SELECT * FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = ?";

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql, array($id));

            if ($stmt === false) {
                error_log("Error en obtenerDespachoPorId: " . print_r(sqlsrv_errors(), true));
                return null;
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($row) {
                // Convertir objetos DateTime a strings en formato DD/MM/YYYY
                $dateFields = [
                    'FECHA_MOV',
                    'FECHA_EST_EMB',
                    'FECHA_EMB',
                    'FECHA_ARR',
                    'FECHA_PAGO',
                    'FECHA_DESP_ADU',
                    'FECHA_EST_PAGO'
                ];

                foreach ($dateFields as $field) {
                    if (isset($row[$field]) && is_object($row[$field])) {
                        $row[$field] = $row[$field]->format('d/m/Y');
                    }
                }

                /* FECHA_PAGO_CONF llega por el SELECT * en cuanto el script 10
                   corrio, y no llega si no corrio. Se normaliza a 0/1 acá, una
                   sola vez: un BIT de SQL Server puede venir como '0', que en
                   el JSON seria un string verdadero y dejaria toda la base
                   marcada como manual. La clave se define SIEMPRE, asi que el
                   front no tiene que preguntar si el DDL corrio: sin la columna
                   vale 0, que es exactamente lo que la aplicacion hace hoy. */
                $row['FECHA_PAGO_CONF'] =
                    (isset($row['FECHA_PAGO_CONF']) && (string) $row['FECHA_PAGO_CONF'] === '1')
                        ? 1 : 0;

                if (isset($row['FECHA_PAGO_CONF_FECHA']) && is_object($row['FECHA_PAGO_CONF_FECHA'])) {
                    $row['FECHA_PAGO_CONF_FECHA'] = $row['FECHA_PAGO_CONF_FECHA']->format('d/m/Y H:i');
                }

                return $row;
            }

            return null;

        } catch (Exception $e) {
            error_log('Error en obtenerDespachoPorId: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Marca una OC como hija de otra (vinculación por contenedor compartido).
     * Solo actualiza si la fila todavía no tiene padre (idempotente).
     */
    public function vincularComoPadre($idHijo, $idPadre) {
        $idHijo  = intval($idHijo);
        $idPadre = intval($idPadre);

        if ($idHijo <= 0 || $idPadre <= 0 || $idHijo === $idPadre) {
            return false;
        }

        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                SET ID_PADRE = ?
                WHERE ID = ? AND ID_PADRE IS NULL";

        $stmt = sqlsrv_query($this->cid_central, $sql, array($idPadre, $idHijo));

        if ($stmt === false) {
            error_log('[vincularComoPadre] Error: ' . print_r(sqlsrv_errors(), true));
            return false;
        }

        return true;
    }

    /**
     * Devuelve el ID de la OC principal del grupo.
     * Si el ID dado ya es principal (ID_PADRE IS NULL), retorna el mismo.
     * Si es hijo, retorna el padre.
     */
    public function resolverIdPrincipal($idMg) {
        $idMg = intval($idMg);
        $sql = "SELECT COALESCE(ID_PADRE, ID) AS ID_PRINCIPAL
                FROM RO_T_IMPORTACIONES_ENCABEZADO
                WHERE ID = ?";

        $stmt = sqlsrv_query($this->cid_central, $sql, array($idMg));
        if ($stmt === false) return $idMg;

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? intval($row['ID_PRINCIPAL']) : $idMg;
    }

    /**
     * Devuelve array con todos los IDs del grupo (principal + hijas).
     * Si el ID dado es hijo, primero resuelve al padre.
     */
    public function obtenerIdsDelGrupo($idMg) {
        $idPrincipal = $this->resolverIdPrincipal($idMg);

        $ids = [$idPrincipal];

        $sql = "SELECT ID FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID_PADRE = ?";
        $stmt = sqlsrv_query($this->cid_central, $sql, array($idPrincipal));

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $ids[] = intval($row['ID']);
            }
        }

        return $ids;
    }

    /**
     * Devuelve array con los datos de todas las OCs del grupo.
     * Cada elemento: ['ID' => int, 'ORDEN_COMPRA' => string, 'ID_PADRE' => int|null]
     * El principal aparece primero.
     */
    public function obtenerOrdenesDelGrupo($idMg) {
        $idPrincipal = $this->resolverIdPrincipal($idMg);

        $sql = "SELECT ID, ORDEN_COMPRA, ID_PADRE
                FROM RO_T_IMPORTACIONES_ENCABEZADO
                WHERE ID = ? OR ID_PADRE = ?
                ORDER BY (CASE WHEN ID_PADRE IS NULL THEN 0 ELSE 1 END), ID";

        $stmt = sqlsrv_query($this->cid_central, $sql, array($idPrincipal, $idPrincipal));
        if ($stmt === false) return [];

        $resultado = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultado[] = $row;
        }
        return $resultado;
    }

    /**
     * Recalcula FECHA_DISTRI de las OCs indicadas cuando se mueve FECHA_ARR.
     *
     * Delega en CronogramaFechas, que es la misma regla que usa el drag & drop
     * del cronograma: solo se tocan las filas con DIST_ORIGEN = 'A'; las
     * movidas a mano ('M') o confirmadas ('C') quedan intactas.
     */
    private function recalcularDistribucion($ids, $fechaArribo, $antes = null) {
        $this->cargarCronogramaFechas();

        $tocadas = CronogramaFechas::recalcularDistribucion(
            $this->cid_central, $ids, $fechaArribo
        );

        if (empty($tocadas)) {
            return [];
        }

        error_log('recalcularDistribucion: ' . count($tocadas) .
                  ' OCs con FECHA_DISTRI recalculada a ' . reset($tocadas));

        // La cascada se registra aparte, con su propio motivo.
        if ($antes !== null) {
            $parametros = CronogramaFechas::obtenerParametros($this->cid_central);
            foreach ($tocadas as $id => $nueva) {
                if (!isset($antes[$id])) continue;
                CronogramaFechas::registrarHistorial($this->cid_central, [
                    'idEncabezado'  => $id,
                    'ordenCompra'   => $antes[$id]['ORDEN_COMPRA'],
                    'contenedor'    => $antes[$id]['CONTENEDOR'],
                    'campo'         => 'FECHA_DISTRI',
                    'valorAnterior' => CronogramaFechas::derivarDistribucion($antes[$id], $parametros),
                    'valorNuevo'    => $nueva,
                    'motivo'        => MotivosFecha::RECALCULO_AUTOMATICO,
                    'observacion'   => null,
                    'usuario'       => isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null,
                    'origen'        => 'GESTION_DESPACHOS',
                ]);
            }
        }

        return $tocadas;
    }

    private function cargarCronogramaFechas() {
        require_once __DIR__ . '/../cronogramaDespachos/class/CronogramaFechas.php';
        require_once __DIR__ . '/../cronogramaDespachos/class/MotivosFecha.php';
    }

    /**
     * Fechas de las OCs indicadas, para comparar antes y despues de un UPDATE.
     */
    private function leerFechasParaHistorial($ids) {
        $this->cargarCronogramaFechas();
        return CronogramaFechas::leerFechas($this->cid_central, $ids);
    }

    /**
     * Compara el estado previo contra el actual y registra una fila de
     * historial por cada campo de fecha que cambio.
     *
     * Es lo que evita que el historial quede con agujeros: sin esto, toda
     * edicion hecha desde gestion de despachos no dejaria rastro y el
     * historial solo mostraria lo movido desde el cronograma.
     *
     * MOTIVO y OBSERVACION quedan en NULL a proposito: el formulario de
     * gestion de despachos no los pide, y sumarle campos obligatorios no
     * entra en esta tanda.
     */
    private function registrarCambiosDeFecha($ids, $antes) {
        if (empty($antes)) return;

        $this->cargarCronogramaFechas();
        $despues = CronogramaFechas::leerFechas($this->cid_central, $ids);
        $usuario = isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null;

        foreach ($despues as $id => $filaDespues) {
            if (!isset($antes[$id])) continue;

            /* CAMPOS_HISTORIAL y no CAMPOS_EDITABLES: la segunda es lo que el
               cronograma puede arrastrar, y FECHA_EST_PAGO no es eso -no entra
               en ORDEN_FLUJO ni se dibuja en el calendario- pero si queremos
               rastro de sus cambios. Ver CronogramaFechas. */
            foreach (CronogramaFechas::CAMPOS_HISTORIAL as $campo) {
                $previo = $antes[$id][$campo];
                $actual = $filaDespues[$campo];

                if ((string) $previo === (string) $actual) {
                    continue;
                }

                CronogramaFechas::registrarHistorial($this->cid_central, [
                    'idEncabezado'  => $id,
                    'ordenCompra'   => $antes[$id]['ORDEN_COMPRA'],
                    'contenedor'    => $antes[$id]['CONTENEDOR'],
                    'campo'         => $campo,
                    'valorAnterior' => $previo,
                    'valorNuevo'    => $actual,
                    'motivo'        => null,
                    'observacion'   => null,
                    'usuario'       => $usuario,
                    'origen'        => 'GESTION_DESPACHOS',
                ]);
            }
        }
    }

    /**
     * Actualiza campos comunes de embarque/despacho en TODAS las OCs del grupo.
     * No toca ORDEN_COMPRA, OCM ni ID.
     *
     * @param int   $idEncabezado  ID de cualquier OC del grupo
     * @param array $datos         ['COLUMNA' => valor, ...]
     * @return bool
     */
    public function actualizarEncabezadoGrupo($idEncabezado, $datos, $fechaPagoManual = false) {
        $idPrincipal = $this->resolverIdPrincipal($idEncabezado);
        if (!$idPrincipal) return false;

        $idsGrupo = $this->obtenerIdsDelGrupo($idPrincipal);
        if (empty($idsGrupo)) return false;

        $columnasPermitidas = [
            'FECHA_MOV', 'FECHA_EMB', 'FECHA_OC',
            'FACTURA', 'NUMERO_BL',
            'TIPO_CAMBIO', 'VALOR_FOB_DOLAR', 'VALOR_FOB_PESO',
            'FECHA_ARR', 'FECHA_DESP_ADU',
            'FECHA_EST_PAGO', 'FECHA_EST_EMB',
            'DESPACHANTE', 'PUERTO_ORIGEN', 'TERMINAL', 'ETA_CONFIRMADA',
            'CONTENEDOR', 'DESPACHO', 'MATERIAL', 'ORIGEN', 'FORMA_PAGO',
            'COD_PROVEE', 'PROVEEDOR',
        ];

        $campos     = [];
        $parametros = [];

        foreach ($datos as $col => $valor) {
            if (in_array($col, $columnasPermitidas, true)) {
                $campos[]     = "$col = ?";
                $parametros[] = ($valor === '' ? null : $valor);
            }
        }

        if (empty($campos)) return false;

        $placeholders = implode(',', array_fill(0, count($idsGrupo), '?'));
        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO
                SET "    . implode(', ', $campos) . "
                WHERE ID IN ($placeholders)";

        $parametros = array_merge($parametros, $idsGrupo);

        // Estado previo, para poder registrar en el historial que cambio.
        $antes = $this->leerFechasParaHistorial($idsGrupo);

        $stmt = sqlsrv_query($this->cid_central, $sql, $parametros);
        if ($stmt === false) {
            error_log('actualizarEncabezadoGrupo: ' . print_r(sqlsrv_errors(), true));
            return false;
        }

        $this->registrarCambiosDeFecha($idsGrupo, $antes);

        /* La marca de "fijada a mano" se propaga al grupo entero porque la
           fecha tambien se propaga: arriba, FECHA_EST_PAGO esta en
           columnasPermitidas. Si el BIT quedara solo en la OC principal, la
           proxima edicion recalcularia el de las hermanas y las pisaria. */
        $this->marcarFechaPagoFijada($idsGrupo, $antes, $fechaPagoManual);

        // Si se movio el arribo, la distribucion automatica lo sigue.
        if (array_key_exists('FECHA_ARR', $datos) && !empty($datos['FECHA_ARR'])) {
            $this->recalcularDistribucion($idsGrupo, $datos['FECHA_ARR'], $antes);
        }

        $afectados = sqlsrv_rows_affected($stmt);
        error_log("actualizarEncabezadoGrupo: idRecibido=$idEncabezado, " .
                  "idPrincipal=$idPrincipal, ocsGrupo=" . count($idsGrupo) .
                  ", filasAfectadas=$afectados");

        return true;
    }

    public function actualizarEncabezado($id, $datosDeCabezera)
    {
        $id = intval($id);
        $codProv = substr(str_replace("'", "''", $datosDeCabezera['cod_proveedor']), 0, 6);

        // Construir UPDATE dinámico
        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO SET ";
        $updates = [];

        // Función auxiliar para formatear números de forma segura para SQL
        $fmtNum = function ($val) {
            return str_replace(',', '.', (string) $val);
        };

        // Función auxiliar para escapar strings
        $esc = function ($val) {
            return str_replace("'", "''", (string) $val);
        };

        // Campos que siempre se actualizan
        $updates[] = "COD_PROVEE = '" . $esc($codProv) . "'";
        $updates[] = "PROVEEDOR = '" . $esc($datosDeCabezera['proveedor']) . "'";
        $updates[] = "CONTENEDOR = '" . $esc($datosDeCabezera['contenedor']) . "'";
        $updates[] = "MATERIAL = '" . $esc($datosDeCabezera['material']) . "'";
        $updates[] = "ORIGEN = '" . $esc($datosDeCabezera['origen']) . "'";
        $updates[] = "VALOR_FOB_DOLAR = '" . $fmtNum($datosDeCabezera['valorFobDolar']) . "'";
        $updates[] = "FECHA_EST_EMB = '" . $esc($datosDeCabezera['fechaEstEmb']) . "'";
        $updates[] = "ORDEN_COMPRA = '" . $esc($datosDeCabezera['ordenCompra']) . "'";
        $updates[] = "OCM = '" . $esc($datosDeCabezera['ocm']) . "'";

        // Despachante (con valor por defecto si no viene)
        if (isset($datosDeCabezera['despachante']) && !empty($datosDeCabezera['despachante'])) {
            $updates[] = "DESPACHANTE = '" . $esc($datosDeCabezera['despachante']) . "'";
        }

        // Campos opcionales - actualizar siempre si están en el array (incluso si vacíos)
        if (isset($datosDeCabezera['fechaArr'])) {
            if (!empty($datosDeCabezera['fechaArr'])) {
                $updates[] = "FECHA_ARR = '" . $esc($datosDeCabezera['fechaArr']) . "'";
            } else {
                $updates[] = "FECHA_ARR = NULL";
            }
        }

        if (isset($datosDeCabezera['fechaPago']) && !empty($datosDeCabezera['fechaPago'])) {
            $updates[] = "FECHA_PAGO = '" . $esc($datosDeCabezera['fechaPago']) . "'";
        }

        if (isset($datosDeCabezera['fechaDespAdu']) && !empty($datosDeCabezera['fechaDespAdu'])) {
            $updates[] = "FECHA_DESP_ADU = '" . $esc($datosDeCabezera['fechaDespAdu']) . "'";
        }

        if (isset($datosDeCabezera['fechaEmb']) && !empty($datosDeCabezera['fechaEmb'])) {
            $updates[] = "FECHA_EMB = '" . $esc($datosDeCabezera['fechaEmb']) . "'";
        }

        if (isset($datosDeCabezera['numeroBl']) && !empty($datosDeCabezera['numeroBl'])) {
            $updates[] = "NUMERO_BL = '" . $esc($datosDeCabezera['numeroBl']) . "'";
        }

        if (isset($datosDeCabezera['facturaProveedor']) && !empty($datosDeCabezera['facturaProveedor'])) {
            $updates[] = "FACTURA = '" . $esc($datosDeCabezera['facturaProveedor']) . "'";
        }

        if (isset($datosDeCabezera['tipoCambio']) && !empty($datosDeCabezera['tipoCambio'])) {
            $updates[] = "TIPO_CAMBIO = '" . $fmtNum($datosDeCabezera['tipoCambio']) . "'";
        }

        if (isset($datosDeCabezera['valorFobPeso']) && !empty($datosDeCabezera['valorFobPeso'])) {
            $updates[] = "VALOR_FOB_PESO = '" . $fmtNum($datosDeCabezera['valorFobPeso']) . "'";
        }

        if (isset($datosDeCabezera['formaPago']) && !empty($datosDeCabezera['formaPago'])) {
            $updates[] = "FORMA_PAGO = '" . $esc($datosDeCabezera['formaPago']) . "'";
        }

        if (isset($datosDeCabezera['despacho']) && !empty($datosDeCabezera['despacho'])) {
            $updates[] = "DESPACHO = '" . $esc($datosDeCabezera['despacho']) . "'";
        }

        if (isset($datosDeCabezera['fechaEstPago']) && !empty($datosDeCabezera['fechaEstPago'])) {
            $updates[] = "FECHA_EST_PAGO = '" . $esc($datosDeCabezera['fechaEstPago']) . "'";
        }

        if (isset($datosDeCabezera['puertoOrigen']) && !empty($datosDeCabezera['puertoOrigen'])) {
            $updates[] = "PUERTO_ORIGEN = '" . $esc($datosDeCabezera['puertoOrigen']) . "'";
        }

        if (isset($datosDeCabezera['terminal']) && !empty($datosDeCabezera['terminal'])) {
            $updates[] = "TERMINAL = '" . $esc($datosDeCabezera['terminal']) . "'";
        }

        // ETA Confirmada (bit - 0 o 1)
        if (isset($datosDeCabezera['etaConfirmada'])) {
            $updates[] = "ETA_CONFIRMADA = " . intval($datosDeCabezera['etaConfirmada']);
        }

        if (empty($updates))
            return false;

        $sql .= implode(", ", $updates);
        $sql .= " WHERE ID = " . $id;

        error_log("SQL UPDATE: " . $sql);

        // Estado previo, para el historial.
        $antes = $this->leerFechasParaHistorial([$id]);

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en actualizarEncabezado: " . print_r($errors, true));
                return false;
            }

            $this->registrarCambiosDeFecha([$id], $antes);

            /* Si el usuario movio la fecha estimada de pago, queda fijada y el
               recalculo de +5 dias deja de pisarla. Hacen falta las dos cosas
               -el marcador del front Y que el valor haya cambiado de verdad-;
               ver marcarFechaPagoFijada(). */
            $this->marcarFechaPagoFijada(
                [$id],
                $antes,
                !empty($datosDeCabezera['fechaEstPagoManual'])
            );

            // Igual que en actualizarEncabezadoGrupo: mover el arribo arrastra
            // la distribucion automatica. Aca alcanza con esta OC, porque este
            // metodo actualiza una sola fila.
            if (isset($datosDeCabezera['fechaArr']) && !empty($datosDeCabezera['fechaArr'])) {
                $this->recalcularDistribucion([$id], $datosDeCabezera['fechaArr'], $antes);
            }

            return $id;

        } catch (Exception $e) {
            error_log('Error en actualizarEncabezado: ' . $e->getMessage());
            return false;
        }
    }

}