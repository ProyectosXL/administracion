<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// PRIMERO: Verificamos si la acción es crear una propuesta.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'crear_propuesta') {
    header('Content-Type: application/json');
    require_once '../config/database.php';
    require_once __DIR__ . '/notificaciones_controller.php';

    $comprobantes = $_POST['comprobantes'] ?? [];
    $total_propuesto = $_POST['total_propuesto'] ?? 0;
    $fecha_propuesta_pago = $_POST['fecha_propuesta_pago'] ?? null;
    $medio_de_pago = $_POST['medio_de_pago'] ?? null;
    $id_usuario_admin = $_SESSION['usuario_id'] ?? null;
    $cod_cliente = $_POST['cod_cliente'] ?? null;

    // La validación correcta
    if (empty($cod_cliente) || empty($comprobantes) || !isset($total_propuesto) || empty($fecha_propuesta_pago) || empty($medio_de_pago) || empty($id_usuario_admin)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faltan datos para crear la propuesta.']);
        exit;
    }

    $conn_apps = Database::getConnection('apps');
    if (sqlsrv_begin_transaction($conn_apps) === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    try {
        $sql_propuesta = "INSERT INTO FP_propuestas_pago (cod_cliente, id_usuario_admin, estado, total_propuesto, fecha_propuesta_pago, medio_de_pago) OUTPUT INSERTED.id VALUES (?, ?, ?, ?, ?, ?)";
        $params_propuesta = [$cod_cliente, $id_usuario_admin, 'PENDIENTE_APROBACION_CLIENTE', $total_propuesto, $fecha_propuesta_pago, $medio_de_pago];
        $stmt_propuesta = sqlsrv_query($conn_apps, $sql_propuesta, $params_propuesta);

        $row_id = sqlsrv_fetch_array($stmt_propuesta, SQLSRV_FETCH_ASSOC);
        $id_propuesta = $row_id['id'];

        if (!$id_propuesta)
            throw new Exception("No se pudo crear la cabecera de la propuesta.");

        $sql_item = "INSERT INTO FP_propuestas_pago_items (id_propuesta, t_comp_factura, n_comp_factura, importe_bruto, importe_neto, porcentaje_descuento) VALUES (?, ?, ?, ?, ?, ?)";
        foreach ($comprobantes as $comp) {
            $params_item = [$id_propuesta, $comp['t_comp'], $comp['n_comp'], $comp['importe_bruto'], $comp['importe_neto'], $comp['porcentaje_descuento']];
            $stmt_item = sqlsrv_query($conn_apps, $sql_item, $params_item);
            if ($stmt_item === false)
                throw new Exception("Error al insertar item: " . $comp['n_comp']);
        }

        // --- NUEVO: Guardar Cuotas (Facilidades de Pago) ---
        $cuotas = $_POST['cuotas'] ?? [];
        if (!empty($cuotas)) {
            $sql_cuota = "INSERT INTO FP_propuestas_pago_cuotas (id_propuesta, num_cuota, monto, fecha_vencimiento) VALUES (?, ?, ?, ?)";
            foreach ($cuotas as $cuota) {
                $params_cuota = [$id_propuesta, $cuota['num_cuota'], $cuota['monto'], $cuota['fecha_vencimiento']];
                $stmt_cuota = sqlsrv_query($conn_apps, $sql_cuota, $params_cuota);
                if ($stmt_cuota === false)
                    throw new Exception("Error al insertar la cuota " . $cuota['num_cuota']);
            }
        }

        // --- GUARDAR SNAPSHOT INICIAL (AHORA CON CUOTAS YA CARGADAS) ---
        $snapshot = [
            'total' => $total_propuesto,
            'fecha' => $fecha_propuesta_pago,
            'medio_pago' => $medio_de_pago,
            'comprobantes' => $comprobantes,
            'cuotas' => $cuotas
        ];
        $json_snapshot = json_encode($snapshot);

        $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, json_data) VALUES (?, ?, ?, ?, ?)";
        $desc_historial = "Propuesta de pago creada por el administrador.";
        $params_historial = [$id_propuesta, $id_usuario_admin, 'ADMIN', $desc_historial, $json_snapshot];
        $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, $params_historial);
        if ($stmt_historial === false)
            throw new Exception("Error al registrar historial.");
        
        // --- NUEVO: Eliminar de la tabla de caché si existe ---
        $idx_sugerencia = $_POST['idx_sugerencia'] ?? null;
        if ($idx_sugerencia) {
            $sql_del_cache = "DELETE FROM FP_SUGERENCIAS_COBRANZAS WHERE COD_CLIENT = ? AND IDX = ? AND USUARIO = CAST(? AS VARCHAR(50))";
            sqlsrv_query($conn_apps, $sql_del_cache, [$cod_cliente, $idx_sugerencia, $id_usuario_admin]);
        }

        sqlsrv_commit($conn_apps);

        // --- RESPUESTA INMEDIATA Y CIERRE DE CONEXIÓN ---
        $response = json_encode(['success' => true, 'message' => 'Propuesta de pago enviada correctamente.']);
        
        // Limpiamos cualquier salida previa accidental
        if (ob_get_level()) ob_end_clean();
        
        // Configuramos cabeceras para forzar al navegador a cerrar la conexión tras recibir el JSON
        header('Connection: close');
        header('Content-Length: ' . strlen($response));
        header('Content-Type: application/json');
        
        echo $response;
        
        // Forzamos el envío de los buffers al navegador
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
            if (session_id()) session_write_close();
            ignore_user_abort(true);
        }

        // --- PROCESO EN SEGUNDO PLANO: NOTIFICACIÓN ---
        try {
            $datos_cliente = obtenerEmailFranquiciado($cod_cliente);
            if ($datos_cliente && $datos_cliente['email']) {
                $titulo = "Nueva Propuesta de Pago Recibida (ID: #{$id_propuesta})";
                $mensaje = "Hola <strong>{$datos_cliente['razon_social']}</strong>,<br><br>Se ha generado una nueva propuesta de pago para regularizar su cuenta corriente. Por favor, ingrese al portal de clientes para revisar el detalle y responder (Aceptar o Contraproponer) dentro de las próximas 96 horas hábiles.";
                $cuerpo = generarCuerpoEmail($titulo, $mensaje, "Ver Propuesta", "https://app.xl.com.ar/administracion/tesoreria/cobranzas/portal_cliente.php");
                enviarNotificacion($datos_cliente['email'], $titulo, $cuerpo);
            }
        } catch (Exception $e_mail) {
            error_log("Error enviando mail al cliente: " . $e_mail->getMessage());
        }
        // --- FIN DE LA NOTIFICACIÓN ---

    } catch (Exception $e) {
        sqlsrv_rollback($conn_apps);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// LÓGICA PARA LISTAR COBRANZAS
header('Content-Type: application/json');
require_once '../config/database.php';

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$cod_cliente = isset($_GET['cod_client']) ? trim($_GET['cod_client']) : null;
$vista = '';

if ($tipo === 'franquicias') {
    $vista = 'RO_V_COBRANZA_PEND_FRANQUICIAS';
} elseif ($tipo === 'mayoristas') {
    $vista = 'RO_V_COBRANZA_PEND_MAYORISTAS';
} elseif ($tipo === 'sugerencias') {
    $vista = 'RO_V_COBRANZA_PEND_FRANQUICIAS'; 
} else {
    echo json_encode(['error' => 'Tipo no válido']);
    exit;
}

$id_usuario = $_SESSION['usuario_id'] ?? 'SISTEMA';
$usuario_nombre = $_SESSION['usuario_nombre'] ?? '';

if (!empty($usuario_nombre) && trim(strtolower($usuario_nombre)) === 'vvillarreal') {
    if ($tipo !== 'mayoristas') {
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }
}

// Liberar lock de sesión inmediatamente para permitir solicitudes concurrentes entre solapas
session_write_close();

$recalcular = ($_GET['recalcular'] ?? 'false') === 'true';

try {
    $conn_central = Database::getConnection('central');
    $conn_apps = Database::getConnection('apps');

    // --- ACCESO ULTRARRÁPIDO A CACHÉ DE SUGERENCIAS ---
    // Si se piden sugerencias y no se fuerza recálculo, leemos directamente la caché y calculamos saldo en vivo consolidado
    if ($tipo === 'sugerencias' && !$recalcular && !$cod_cliente) {
        $sql_cache = "SELECT COD_CLIENT, RAZON_SOCI, IDX, TOTAL_BRUTO_SUG, TOTAL_NETO_SUG, FECHA_SUGERIDA, COMPROBANTES_JSON, TOTAL_PENDIENTE_CLIENTE, CANT_TOTAL_PENDIENTE 
                      FROM FP_SUGERENCIAS_COBRANZAS WHERE USUARIO = CAST(? AS VARCHAR(50)) ORDER BY COD_CLIENT, IDX";
        $stmt_cache = sqlsrv_query($conn_apps, $sql_cache, [$id_usuario]);
        if ($stmt_cache !== false) {
            $raw_cache = [];
            while ($row = sqlsrv_fetch_array($stmt_cache, SQLSRV_FETCH_ASSOC)) {
                $raw_cache[] = $row;
            }

            if (!empty($raw_cache)) {
                // Comprobantes Packaging de LALOMA (muy rápido, ~0.05s)
                $sql_laloma_pack = "SELECT DISTINCT g.T_COMP, g.N_COMP 
                                    FROM GVA53 g WITH (NOLOCK)
                                    JOIN SOF_MAESTRO_ARTICULOS_RUBRO_CATEGORIA m WITH (NOLOCK) ON g.COD_ARTICU = m.COD_ARTICU
                                    WHERE m.RUBRO = 'PACKAGING'
                                      AND EXISTS (SELECT 1 FROM RO_V_COBRANZA_PEND_FRANQUICIAS v WHERE v.COD_CLIENT = 'LALOMA' AND v.T_COMP = g.T_COMP AND v.N_COMP = g.N_COMP)";
                $stmt_l = sqlsrv_query($conn_central, $sql_laloma_pack);
                $lalomaPackaging = [];
                if ($stmt_l !== false) {
                    while($r = sqlsrv_fetch_array($stmt_l, SQLSRV_FETCH_ASSOC)) {
                        $lalomaPackaging[trim($r['T_COMP']) . '|' . trim($r['N_COMP'])] = true;
                    }
                }

                // Consulta viva consolidada para TODOS los clientes (1 sola pasada, sin N+1, ~0.35s)
                $sql_all_live = "SELECT v.COD_CLIENT, v.T_COMP, v.N_COMP,
                                        CAST(ISNULL(s.SALDO_REAL, v.IMPORTE) AS FLOAT) as IMPORTE
                                 FROM RO_V_COBRANZA_PEND_FRANQUICIAS v
                                 OUTER APPLY (
                                     SELECT SUM(s.IMPORTE_VT - s.IMPORT_CAN) as SALDO_REAL 
                                     FROM SJ_SALDOS_CC_DETALLE s
                                     WHERE s.T_COMP = v.T_COMP AND s.N_COMP = v.N_COMP
                                 ) s
                                 WHERE v.ESTADO <> 'IMP'
                                   AND (v.T_COMP IN ('FAC','NCR','NCP','NDP') OR (v.T_COMP = 'REC' AND v.IMPORTE < 0))";
                $stmt_all = sqlsrv_query($conn_central, $sql_all_live);
                $live_by_client = [];
                if ($stmt_all !== false) {
                    while ($row = sqlsrv_fetch_array($stmt_all, SQLSRV_FETCH_ASSOC)) {
                        $cod = strtoupper(trim($row['COD_CLIENT']));
                        $tComp = strtoupper(trim($row['T_COMP']));
                        $nComp = strtoupper(trim($row['N_COMP']));
                        
                        if ($cod === 'LALOMA') {
                            if (strpos($nComp, 'A00115') === false && !isset($lalomaPackaging[$tComp . '|' . $nComp])) {
                                continue;
                            }
                        }
                        
                        if (!isset($live_by_client[$cod])) {
                            $live_by_client[$cod] = ['TOTAL_VIVO' => 0.0, 'CANT_VIVO' => 0];
                        }
                        $live_by_client[$cod]['TOTAL_VIVO'] += (float)$row['IMPORTE'];
                        $live_by_client[$cod]['CANT_VIVO']++;
                    }
                }

                $cache_rows = [];
                foreach ($raw_cache as $row) {
                    $cod_c = strtoupper(trim($row['COD_CLIENT']));
                    $live_data = $live_by_client[$cod_c] ?? ['TOTAL_VIVO' => 0.0, 'CANT_VIVO' => 0];

                    $cache_rows[] = [
                        'COD_CLIENT' => $row['COD_CLIENT'],
                        'RAZON_SOCI' => $row['RAZON_SOCI'],
                        'IDX' => $row['IDX'],
                        'TOTAL_BRUTO_SUG' => (float)$row['TOTAL_BRUTO_SUG'],
                        'TOTAL_NETO_SUG' => (float)$row['TOTAL_NETO_SUG'],
                        'FECHA_SUGERIDA' => $row['FECHA_SUGERIDA'] instanceof DateTime ? $row['FECHA_SUGERIDA']->format('Y-m-d') : $row['FECHA_SUGERIDA'],
                        'COMPROBANTES' => json_decode($row['COMPROBANTES_JSON'], true),
                        'TOTAL_PENDIENTE_CLIENTE' => (float)($live_data['TOTAL_VIVO'] ?? 0),
                        'CANT_TOTAL_PENDIENTE' => (int)($live_data['CANT_VIVO'] ?? 0)
                    ];
                }

                echo json_encode(['success' => true, 'data' => $cache_rows]);
                exit;
            }
        }
    }

    $tableData = [];
    $resumenClientes = []; // Limpiar explícitamente
    $summary = [
        'totalNeto' => 0,
        'totalComprobantes' => 0,
        'totalClientes' => 0
    ];

    // 1. Obtener todos los comprobantes que ya están en propuestas activas (para filtrar)
    $facturas_en_propuestas = [];
    $sql_prop = "SELECT items.t_comp_factura, items.n_comp_factura, propuestas.cod_cliente 
                 FROM FP_propuestas_pago_items items 
                 JOIN FP_propuestas_pago propuestas ON items.id_propuesta = propuestas.id 
                 WHERE propuestas.estado NOT IN ('RECHAZADA', 'CANCELADA', 'PAGADO', 'VENCIDA')";

    $stmt_prop = sqlsrv_query($conn_apps, $sql_prop);

    if ($stmt_prop !== false) {
        while ($r = sqlsrv_fetch_array($stmt_prop, SQLSRV_FETCH_ASSOC)) {
            $key = strtoupper(trim($r['t_comp_factura'])) . '|' . strtoupper(trim($r['n_comp_factura']));
            $facturas_en_propuestas[$key] = true;
        }
    }

    if ($cod_cliente) {
        // --- VISTA DE DETALLE (INDIVIDUAL) ---
        $estado_col = ($tipo === 'mayoristas') ? "'PEN' as ESTADO" : "v.ESTADO";
        $estado_cond = ($tipo === 'mayoristas') ? "" : "AND v.ESTADO <> 'IMP'";
        
        // Precargar packaging de LALOMA solo si el cliente es LALOMA
        $lalomaPackaging = [];
        if ($cod_cliente === 'LALOMA') {
            $sql_laloma_pack = "SELECT DISTINCT g.T_COMP, g.N_COMP 
                                FROM GVA53 g WITH (NOLOCK)
                                JOIN SOF_MAESTRO_ARTICULOS_RUBRO_CATEGORIA m WITH (NOLOCK) ON g.COD_ARTICU = m.COD_ARTICU
                                WHERE m.RUBRO = 'PACKAGING'
                                  AND EXISTS (SELECT 1 FROM RO_V_COBRANZA_PEND_FRANQUICIAS v WHERE v.COD_CLIENT = 'LALOMA' AND v.T_COMP = g.T_COMP AND v.N_COMP = g.N_COMP)";
            $stmt_l = sqlsrv_query($conn_central, $sql_laloma_pack);
            if ($stmt_l !== false) {
                while($r = sqlsrv_fetch_array($stmt_l, SQLSRV_FETCH_ASSOC)) {
                    $lalomaPackaging[trim($r['T_COMP']) . '|' . trim($r['N_COMP'])] = true;
                }
            }
        }

        $sql_facturas = "
            SELECT 
                v.COD_CLIENT, v.RAZON_SOCI, v.FECHA_EMIS, v.T_COMP, v.N_COMP, 
                $estado_col, 
                CAST(ISNULL(s.SALDO_REAL, v.IMPORTE) AS FLOAT) as IMPORTE,
                v.FECHA_PROB_COBRO, v.PPP, 
                CAST(ISNULL(s.SALDO_REAL * (v.IMPORTE_NETO / NULLIF(v.IMPORTE, 0)), v.IMPORTE_NETO) AS FLOAT) as IMPORTE_NETO,
                p.MEDIO_PAGO_DEFAULT, p.DIAS_PP_MAX, p.DESC_PP_MAX
            FROM $vista v
            OUTER APPLY (
                SELECT SUM(s.IMPORTE_VT - s.IMPORT_CAN) as SALDO_REAL
                FROM SJ_SALDOS_CC_DETALLE s
                WHERE s.T_COMP = v.T_COMP AND s.N_COMP = v.N_COMP
            ) s
            LEFT JOIN RO_T_PARAMETROS_DESC_CLIENTES p ON v.COD_CLIENT = p.COD_CLIENT COLLATE Modern_Spanish_CI_AI
            WHERE v.COD_CLIENT = ? 
              $estado_cond
            ORDER BY v.FECHA_EMIS DESC
        ";

        $stmt_facturas = sqlsrv_query($conn_central, $sql_facturas, [$cod_cliente]);
        if ($stmt_facturas === false) {
            throw new Exception("Error en la consulta de detalle de facturas: " . print_r(sqlsrv_errors(), true));
        }

        $procesados_detalle = [];
        while ($row = sqlsrv_fetch_array($stmt_facturas, SQLSRV_FETCH_ASSOC)) {
            if ($cod_cliente === 'LALOMA') {
                $nComp = strtoupper(trim($row['N_COMP']));
                $tComp = strtoupper(trim($row['T_COMP']));
                if (strpos($nComp, 'A00115') === false && !isset($lalomaPackaging[$tComp . '|' . $nComp])) {
                    continue;
                }
            }
            $key = strtoupper(trim($row['T_COMP'])) . '|' . strtoupper(trim($row['N_COMP']));
            if (isset($procesados_detalle[$key])) continue;
            $procesados_detalle[$key] = true;
            $tableData[] = $row;
        }

    } else {
        // --- VISTA DE RESUMEN ---
        $estado_col = ($tipo === 'mayoristas') ? "'PEN' as ESTADO" : "v.ESTADO";
        $estado_cond = ($tipo === 'mayoristas') ? "" : "WHERE v.ESTADO <> 'IMP'";

        // 1. Precargar Packaging de LALOMA (muy rápido, ~0.05s)
        $sql_laloma_pack = "SELECT DISTINCT g.T_COMP, g.N_COMP 
                            FROM GVA53 g WITH (NOLOCK)
                            JOIN SOF_MAESTRO_ARTICULOS_RUBRO_CATEGORIA m WITH (NOLOCK) ON g.COD_ARTICU = m.COD_ARTICU
                            WHERE m.RUBRO = 'PACKAGING'
                              AND EXISTS (SELECT 1 FROM RO_V_COBRANZA_PEND_FRANQUICIAS v WHERE v.COD_CLIENT = 'LALOMA' AND v.T_COMP = g.T_COMP AND v.N_COMP = g.N_COMP)";
        $stmt_l = sqlsrv_query($conn_central, $sql_laloma_pack);
        $lalomaPackaging = [];
        if ($stmt_l !== false) {
            while($r = sqlsrv_fetch_array($stmt_l, SQLSRV_FETCH_ASSOC)) {
                $lalomaPackaging[trim($r['T_COMP']) . '|' . trim($r['N_COMP'])] = true;
            }
        }

        // 2. Precargar Parámetros de Descuentos (sólo ~100 filas, ~0.003s)
        $sql_params = "SELECT COD_CLIENT, 
                              MAX(DESC_PP_MAX) as DESC_PP_MAX, 
                              MAX(DIAS_PP_MAX) as DIAS_PP_MAX, 
                              MAX(MEDIO_PAGO_DEFAULT) as MEDIO_PAGO_DEFAULT,
                              MAX(CANT_COMPROBANTES_SUG) as CANT_COMPROBANTES_SUG, 
                              MAX(PORC_MONTO_SUG) as PORC_MONTO_SUG
                       FROM RO_T_PARAMETROS_DESC_CLIENTES
                       GROUP BY COD_CLIENT";
        $stmt_p = sqlsrv_query($conn_central, $sql_params);
        $parametros = [];
        if ($stmt_p !== false) {
            while($r = sqlsrv_fetch_array($stmt_p, SQLSRV_FETCH_ASSOC)) {
                $parametros[strtoupper(trim($r['COD_CLIENT']))] = $r;
            }
        }

        // 3. Si es sugerencias, precargamos los cheques futuros de todos los clientes (en una sola pasada, ~0.06s)
        $cheques_por_cliente = [];
        if ($tipo === 'sugerencias') {
            $sql_all_cheques = "SELECT CLIENTE, CAST(FECHA_CHEQ AS DATE) AS FECHA_CHEQ 
                                FROM LAKER_SA.dbo.SBA14 
                                WHERE FECHA_CHEQ >= GETDATE() 
                                  AND ESTADO NOT IN ('X', 'R') 
                                ORDER BY CLIENTE, FECHA_CHEQ";
            $stmt_ch = sqlsrv_query($conn_central, $sql_all_cheques);
            if ($stmt_ch !== false) {
                while ($rc = sqlsrv_fetch_array($stmt_ch, SQLSRV_FETCH_ASSOC)) {
                    $cli = strtoupper(trim($rc['CLIENTE']));
                    $fCheq = $rc['FECHA_CHEQ'] instanceof DateTime ? $rc['FECHA_CHEQ']->format('Y-m-d') : $rc['FECHA_CHEQ'];
                    $cheques_por_cliente[$cli][] = $fCheq;
                }
            }
        }

        // 4. Consulta principal optimizada con OUTER APPLY (0.8s en lugar de 45.5s)
        $sql = "SELECT v.COD_CLIENT, v.RAZON_SOCI, v.T_COMP, v.N_COMP, $estado_col, 
                       CAST(ISNULL(s.SALDO_REAL, v.IMPORTE) AS FLOAT) as IMPORTE, 
                       CAST(ISNULL(s.SALDO_REAL * (v.IMPORTE_NETO / NULLIF(v.IMPORTE, 0)), v.IMPORTE_NETO) AS FLOAT) as IMPORTE_NETO, 
                       v.FECHA_EMIS, v.FECHA_PROB_COBRO
                FROM $vista v
                OUTER APPLY (
                    SELECT SUM(s.IMPORTE_VT - s.IMPORT_CAN) as SALDO_REAL
                    FROM SJ_SALDOS_CC_DETALLE s
                    WHERE s.T_COMP = v.T_COMP AND s.N_COMP = v.N_COMP
                ) s
                $estado_cond";

        $stmt = sqlsrv_query($conn_central, $sql);
        if ($stmt === false) {
            throw new Exception("Error en la consulta de resumen: " . print_r(sqlsrv_errors(), true));
        }

        $resumenClientes = [];
        $procesados_lote = []; // Para evitar duplicados dentro del mismo resultado de consulta
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $nComp = strtoupper(trim($v['N_COMP']));
            $tComp = strtoupper(trim($v['T_COMP']));
            $cod = strtoupper(trim($v['COD_CLIENT']));
            $llaveComp = $cod . '|' . $tComp . '|' . $nComp;
            $filterKey = $tComp . '|' . $nComp;

            if (isset($facturas_en_propuestas[$filterKey]) || isset($procesados_lote[$llaveComp]))
                continue;

            // Filtro LALOMA en PHP
            if ($cod === 'LALOMA') {
                if (strpos($nComp, 'A00115') === false && !isset($lalomaPackaging[$tComp . '|' . $nComp])) {
                    continue;
                }
            }

            $procesados_lote[$llaveComp] = true;

            // Asociar parámetros del cliente
            $param = $parametros[$cod] ?? null;
            $v['DESC_PP_MAX'] = $param['DESC_PP_MAX'] ?? 0;
            $v['DIAS_PP_MAX'] = $param['DIAS_PP_MAX'] ?? 0;
            $v['MEDIO_PAGO_DEFAULT'] = $param['MEDIO_PAGO_DEFAULT'] ?? null;
            $v['CANT_COMPROBANTES_SUG'] = $param['CANT_COMPROBANTES_SUG'] ?? null;
            $v['PORC_MONTO_SUG'] = $param['PORC_MONTO_SUG'] ?? null;

            if (!isset($resumenClientes[$cod])) {
                $resumenClientes[$cod] = [
                    'COD_CLIENT' => $cod,
                    'RAZON_SOCI' => $v['RAZON_SOCI'],
                    'CANT_FACTURAS' => 0,
                    'TOTAL_BRUTO' => 0,
                    'TOTAL_NETO' => 0,
                    'CANT_COMPROBANTES_SUG' => $v['CANT_COMPROBANTES_SUG'],
                    'PORC_MONTO_SUG' => $v['PORC_MONTO_SUG'],
                    'FECHA_MIN' => null,
                    'FECHA_MAX' => null
                ];
            }

            $esNegativo = (strpos(trim($v['T_COMP']), 'NC') !== false || trim($v['T_COMP']) === 'REC');
            $importe = (float) $v['IMPORTE'];
            $descPP = (float) $v['DESC_PP_MAX'];
            $porcDesc = 0;
            $tComp = trim($v['T_COMP']);

            // --- REGLA GLOBAL: NCR, NCP, NDP SIEMPRE 0% DESCUENTO ---
            if (in_array($tComp, ['NCR', 'NCP', 'NDP'])) {
                $porcDesc = 0;
            } else if (strpos($nComp, 'A00115') === 0) {
                if ($tComp === 'FAC')
                    $porcDesc = 0;
                else
                    $porcDesc = $descPP;
            } else {
                $porcDesc = $descPP;
                
                // 1. Validación de antigüedad
                if ($v['FECHA_EMIS'] && $v['DIAS_PP_MAX'] > 0) {
                    $f_emis = $v['FECHA_EMIS'];
                    $fecha_base = ($f_emis instanceof DateTime) ? $f_emis : new DateTime($f_emis);
                    $hoy = new DateTime();
                    $intervalo = $hoy->diff($fecha_base);
                    
                    if ($intervalo->days > $v['DIAS_PP_MAX']) {
                        $porcDesc = 0;
                    }
                }
                
                // 2. Medio de pago
                $medioDef = strtoupper(trim($v['MEDIO_PAGO_DEFAULT'] ?? ''));
                if (($medioDef === 'TRANSFERENCIA' || $medioDef === 'TRANSFERERENCIA') && abs($porcDesc - 0.08) < 0.0005) {
                    $porcDesc = 0.06;
                }
                
                // 3. Descuento previo en ERP
                $bruto = $importe;
                $netoOriginal = (float)$v['IMPORTE_NETO'];
                if ($bruto > 0 && $bruto > $netoOriginal) {
                    $calcu = ($bruto - $netoOriginal) / $bruto;
                    if ($calcu > $porcDesc) {
                        $porcDesc = $calcu;
                    }
                }
            }

            $netoItem = $importe * (1 - $porcDesc);
            $valNeto = ($esNegativo ? -abs($netoItem) : abs($netoItem));
            
            $resumenClientes[$cod]['CANT_FACTURAS']++;
            $resumenClientes[$cod]['TOTAL_BRUTO'] += ($esNegativo ? -abs($importe) : abs($importe));
            $resumenClientes[$cod]['TOTAL_NETO'] += $valNeto;
            
            // Para sugerencias, guardamos el detalle
            if ($tipo === 'sugerencias') {
                $fProbCobro = $v['FECHA_PROB_COBRO'] instanceof DateTime ? $v['FECHA_PROB_COBRO']->format('Y-m-d') : $v['FECHA_PROB_COBRO'];
                $fEmis = $v['FECHA_EMIS'] instanceof DateTime ? $v['FECHA_EMIS']->format('Y-m-d') : $v['FECHA_EMIS'];
                
                $resumenClientes[$cod]['COMPROBANTES_ALL'][] = [
                    't_comp' => trim($v['T_COMP']),
                    'n_comp' => trim($v['N_COMP']),
                    'importe_bruto' => ($esNegativo ? -abs($importe) : abs($importe)),
                    'importe_neto' => $valNeto,
                    'porcentaje_descuento' => $porcDesc * 100,
                    'fecha_prob_cobro' => $fProbCobro,
                    'fecha_emision' => $fEmis
                ];
                
                if ($fEmis) {
                    if (!$resumenClientes[$cod]['FECHA_MIN'] || $fEmis < $resumenClientes[$cod]['FECHA_MIN']) $resumenClientes[$cod]['FECHA_MIN'] = $fEmis;
                }
            }
        }

        foreach ($resumenClientes as $cod => $cliente) {
            if (round($cliente['TOTAL_BRUTO'], 2) != 0) {
                if ($tipo === 'sugerencias') {
                    $allComps = $resumenClientes[$cod]['COMPROBANTES_ALL'];
                    
                    // Separamos por tipo para la regla de los negativos
                    $negativos = [];
                    $positivos = [];
                    foreach($allComps as $c) {
                        if ($c['importe_bruto'] < 0) $negativos[] = $c;
                        else $positivos[] = $c;
                    }

                    // Ordenamos los positivos por fecha (vieja a nueva)
                    usort($positivos, function($a, $b) {
                        $res = strcmp($a['fecha_emision'] ?? '', $b['fecha_emision'] ?? '');
                        if ($res === 0) return strcmp($a['n_comp'] ?? '', $b['n_comp'] ?? '');
                        return $res;
                    });

                    // Parámetros de segmentación
                    $maxCantBase = intval($cliente['CANT_COMPROBANTES_SUG'] ?? 0);
                    $porcMontoBase = floatval($cliente['PORC_MONTO_SUG'] ?? 0);
                    $balanceOriginal = $cliente['TOTAL_BRUTO'];
                    $targetMonto = ($porcMontoBase > 0) ? ($balanceOriginal * ($porcMontoBase / 100)) : 0;
                    
                    if ($maxCantBase <= 0 && ($porcMontoBase <= 0 || $porcMontoBase >= 100)) {
                        $maxCantBase = 10;
                    }

                    $numSugerencia = 1;

                    // Bucle para generar TODAS las sugerencias posibles
                    while (!empty($positivos)) {
                        $seleccionados = [];
                        $montoBrutoAcumulado = 0;

                        // 1. En la PRIMERA sugerencia, metemos TODOS los negativos
                        if ($numSugerencia === 1 && !empty($negativos)) {
                            foreach($negativos as $neg) {
                                $seleccionados[] = $neg;
                                $montoBrutoAcumulado += $neg['importe_bruto'];
                            }
                            $negativos = []; // Vaciamos para que no entren en la segunda
                        }

                        // 2. Llenamos con positivos respetando el límite inicial (Smart Selection)
                        $batchPositivos = [];
                        foreach($positivos as $idx => $pos) {
                            if ($maxCantBase > 0 && count($seleccionados) >= $maxCantBase) break;
                            if ($targetMonto > 0 && $montoBrutoAcumulado >= $targetMonto) break;
                            
                            $batchPositivos[] = $pos;
                            $montoBrutoAcumulado += $pos['importe_bruto'];
                            unset($positivos[$idx]); // Quitamos del pool global
                        }
                        $seleccionados = array_merge($seleccionados, $batchPositivos);
                        $positivos = array_values($positivos); // Reindexar

                        // 3. REGLA DE ORO: Si el total es <= 0, DEBEMOS seguir agregando positivos 
                        // ignorando los límites hasta que sea positivo.
                        while ($montoBrutoAcumulado <= 0 && !empty($positivos)) {
                            $extra = array_shift($positivos);
                            $seleccionados[] = $extra;
                            $montoBrutoAcumulado += $extra['importe_bruto'];
                        }

                        // 4. OPTIMIZACIÓN (Swap) para este batch:
                        if ($targetMonto > 0 && $maxCantBase > 0 && count($seleccionados) == $maxCantBase && $montoBrutoAcumulado < $targetMonto) {
                            $indexSiguiente = 0;
                            $maxIntentos = 30;
                            while ($indexSiguiente < count($positivos) && $indexSiguiente < $maxIntentos && $montoBrutoAcumulado < $targetMonto) {
                                $nuevo = $positivos[$indexSiguiente];
                                $mejorSwapIdx = -1;
                                $mejorMejora = 0;
                                foreach($seleccionados as $idx => $actual) {
                                    if ($actual['importe_bruto'] < 0) continue; // No swapeamos negativos
                                    $mejoraPotencial = $nuevo['importe_bruto'] - $actual['importe_bruto'];
                                    if ($mejoraPotencial > 0) {
                                        $nuevoTotal = $montoBrutoAcumulado + $mejoraPotencial;
                                        if (abs($targetMonto - $nuevoTotal) < abs($targetMonto - $montoBrutoAcumulado)) {
                                            if ($mejoraPotencial > $mejorMejora) {
                                                $mejorMejora = $mejoraPotencial;
                                                $mejorSwapIdx = $idx;
                                            }
                                        }
                                    }
                                }
                                if ($mejorSwapIdx !== -1) {
                                    $montoBrutoAcumulado += $mejorMejora;
                                    $old = $seleccionados[$mejorSwapIdx];
                                    $seleccionados[$mejorSwapIdx] = $nuevo;
                                    $positivos[$indexSiguiente] = $old; // Devolvemos el chico al pool
                                    usort($positivos, function($a, $b) { 
                                        $res = strcmp($a['fecha_emision'] ?? '', $b['fecha_emision'] ?? '');
                                        if ($res === 0) return strcmp($a['n_comp'] ?? '', $b['n_comp'] ?? '');
                                        return $res;
                                    });
                                }
                                $indexSiguiente++;
                            }
                        }

                        // --- VINCULACIÓN DE CHEQUES (PRECARGADOS EN MEMORIA) ---
                        $fechasCheques = $cheques_por_cliente[$cod] ?? [];

                        // 5. Cálculos de la sugerencia (Fecha promedio + 15 días hábiles)
                        $sumTimestamps = 0;
                        $countDocs = 0;
                        foreach($seleccionados as $s) {
                            if (!empty($s['fecha_emision'])) {
                                $sumTimestamps += strtotime($s['fecha_emision']);
                                $countDocs++;
                            }
                        }
                        
                        $hoy = new DateTime();
                        $hoy->setTime(0, 0, 0);
                        
                        if ($countDocs > 0) {
                             $avgTimestamp = $sumTimestamps / $countDocs;
                             $fechaBase = new DateTime();
                             $fechaBase->setTimestamp($avgTimestamp);
                             
                             $fechaSugerida = sumarDiasHabilesCobranzas($fechaBase, 15);
                             
                             // Si la fecha sugerida (promedio + 15) es menor a hoy, usamos hoy
                             if ($fechaSugerida < $hoy) $fechaSugerida = $hoy;

                             // --- APLICACIÓN DE REGLA DE CHEQUES ---
                             // Si la fecha coincide con un cheque, la movemos al siguiente día hábil disponible
                             $intentosEvitarCheque = 0;
                             while (in_array($fechaSugerida->format('Y-m-d'), $fechasCheques) && $intentosEvitarCheque < 20) {
                                 $fechaSugerida = sumarDiasHabilesCobranzas($fechaSugerida, 1);
                                 $intentosEvitarCheque++;
                             }

                             $fechaSugStr = $fechaSugerida->format('Y-m-d');
                        } else {
                             $fechaSugStr = $hoy->format('Y-m-d');
                             $fechaSugerida = clone $hoy;
                        }

                        $totalBrutoSug = 0; $totalNetoSug = 0;
                        foreach($seleccionados as $s_item) {
                            $totalBrutoSug += $s_item['importe_bruto'];
                            $totalNetoSug += $s_item['importe_neto'];
                        }

                        // Guardamos esta sugerencia
                        $copy = $cliente;
                        $copy['COMPROBANTES'] = $seleccionados;
                        $copy['TOTAL_BRUTO_SUG'] = $totalBrutoSug;
                        $copy['TOTAL_NETO_SUG'] = $totalNetoSug;
                        $copy['FECHA_SUGERIDA'] = $fechaSugStr;
                        $copy['IDX'] = $numSugerencia;
                        $copy['TOTAL_PENDIENTE_CLIENTE'] = (float)$balanceOriginal;
                        $copy['CANT_TOTAL_PENDIENTE'] = count($allComps);
                        
                        // Determinamos la FECHA_MIN de este lote para el ordenamiento
                        $fMinLote = null;
                        foreach($seleccionados as $s) {
                            if (!$fMinLote || ($s['fecha_emision'] ?? '') < $fMinLote) $fMinLote = $s['fecha_emision'] ?? null;
                        }
                        $copy['FECHA_MIN'] = $fMinLote;

                        // Si es la segunda sugerencia en adelante, agregamos un sufijo a la razón social para distinguirlas
                        if ($numSugerencia > 1) {
                            $copy['RAZON_SOCI'] .= " (Parte $numSugerencia)";
                        }

                        if ($totalNetoSug > 0) {
                            $tableData[] = $copy;
                            $numSugerencia++;
                        }
                        
                        if ($numSugerencia > 50) break;
                    }
                } else {
                    unset($cliente['COMPROBANTES']);
                    unset($cliente['FECHA_MIN']);
                    unset($cliente['FECHA_MAX']);
                    $tableData[] = $cliente;
                    $summary['totalNeto'] += $cliente['TOTAL_NETO'];
                    $summary['totalComprobantes'] += $cliente['CANT_FACTURAS'];
                }
            }
        }
        $summary['totalClientes'] = count($tableData);

        if ($tipo === 'sugerencias') {
            // Ordenamos el listado general por la fecha mas vieja de cada cliente
            usort($tableData, function ($a, $b) {
                $res = strcmp($a['FECHA_MIN'] ?? '', $b['FECHA_MIN'] ?? '');
                if ($res === 0) return strcmp($a['COD_CLIENT'] ?? '', $b['COD_CLIENT'] ?? '');
                return $res;
            });
        } else {
            usort($tableData, function ($a, $b) {
                return $b['TOTAL_NETO'] <=> $a['TOTAL_NETO'];
            });
        }
    }

    // --- FILTRADO FINAL DE DUPLICADOS (Seguridad Extra solo para Sugerencias) ---
    if ($tipo === 'sugerencias') {
        $finalData = [];
        $vistos = [];
        foreach ($tableData as $item) {
            // Generamos una huella digital de la sugerencia/cliente
            $fingerprint = ($item['COD_CLIENT'] ?? '') . '|' . ($item['RAZON_SOCI'] ?? '') . '|' . ($item['TOTAL_NETO_SUG'] ?? $item['TOTAL_NETO'] ?? 0) . '|' . count($item['COMPROBANTES'] ?? []);
            if (!isset($vistos[$fingerprint])) {
                $vistos[$fingerprint] = true;
                $finalData[] = $item;
            }
        }
        $tableData = $finalData;
    }

    // Unificamos la respuesta final
    // --- NUEVO: Si generamos nuevas sugerencias (o recalcular), las guardamos en el caché ---
    if ($tipo === 'sugerencias' && !empty($tableData)) {
        $conn_apps = Database::getConnection('apps');
        
        // Si estamos recalculando o si no había nada, guardamos la "foto" nueva
        $res_check = sqlsrv_query($conn_apps, "SELECT COUNT(*) as cuenta FROM FP_SUGERENCIAS_COBRANZAS WHERE USUARIO = CAST(? AS VARCHAR(50))", [$id_usuario]);
        if ($res_check === false) {
            throw new Exception("Error al verificar existencia en cache de sugerencias: " . print_r(sqlsrv_errors(), true));
        }
        $row_check = sqlsrv_fetch_array($res_check, SQLSRV_FETCH_ASSOC);
        
        if ($recalcular || $row_check['cuenta'] == 0) {
            sqlsrv_query($conn_apps, "DELETE FROM FP_SUGERENCIAS_COBRANZAS WHERE USUARIO = CAST(? AS VARCHAR(50))", [$id_usuario]);
            $sql_ins = "INSERT INTO FP_SUGERENCIAS_COBRANZAS (COD_CLIENT, RAZON_SOCI, IDX, TOTAL_BRUTO_SUG, TOTAL_NETO_SUG, FECHA_SUGERIDA, COMPROBANTES_JSON, USUARIO, TOTAL_PENDIENTE_CLIENTE, CANT_TOTAL_PENDIENTE) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            sqlsrv_begin_transaction($conn_apps);
            foreach ($tableData as $row) {
                $params = [
                    $row['COD_CLIENT'],
                    $row['RAZON_SOCI'],
                    $row['IDX'],
                    $row['TOTAL_BRUTO_SUG'],
                    $row['TOTAL_NETO_SUG'],
                    $row['FECHA_SUGERIDA'],
                    json_encode($row['COMPROBANTES']),
                    $id_usuario,
                    $row['TOTAL_PENDIENTE_CLIENTE'],
                    $row['CANT_TOTAL_PENDIENTE']
                ];
                sqlsrv_query($conn_apps, $sql_ins, $params);
            }
            sqlsrv_commit($conn_apps);
        }
    }

    $response = ['summary' => $summary, 'data' => $tableData];
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}

function sumarDiasHabilesCobranzas($fechaInicio, $diasASumar) {
    $feriados = ['2026-01-01','2026-03-02','2026-03-03','2026-03-24','2026-04-02','2026-04-03','2026-05-01','2026-05-25','2026-06-15','2026-06-20','2026-07-09','2026-08-17','2026-10-12','2026-11-23','2026-12-08','2026-12-25'];
    $fecha = clone $fechaInicio;
    $diasContados = 0;
    while ($diasContados < $diasASumar) {
        $fecha->modify('+1 day');
        $diaSemana = (int) $fecha->format('N');
        $fechaSoloDia = $fecha->format('Y-m-d');
        if ($diaSemana >= 1 && $diaSemana <= 5 && !in_array($fechaSoloDia, $feriados)) {
            $diasContados++;
        }
    }
    return $fecha;
}