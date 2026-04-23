<?php
// PRIMERO: Verificamos si la acción es crear una propuesta.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'crear_propuesta') {
    session_start();
    header('Content-Type: application/json');
    require_once '../config/database.php';
    require_once __DIR__ . '/notificaciones_controller.php';

    $comprobantes = $_POST['comprobantes'] ?? [];
    $total_propuesto = $_POST['total_propuesto'] ?? 0;
    $fecha_propuesta_pago = $_POST['fecha_propuesta_pago'] ?? null;
    $medio_de_pago = $_POST['medio_de_pago'] ?? null;
    $id_usuario_admin = $_SESSION['usuario_id'] ?? null;
    $cod_cliente = $_POST['cod_cliente'] ?? null; // <-- AÑADE ESTA LÍNEA

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
    $vista = 'RO_V_COBRANZA_PEND_FRANQUICIAS'; // Las sugerencias por ahora son para franquicias
} else {
    echo json_encode(['error' => 'Tipo no válido']);
    exit;
}

try {
    $conn_central = Database::getConnection('central');
    $conn_apps = Database::getConnection('apps');

    $tableData = [];
    $summary = [
        'totalNeto' => 0,
        'totalComprobantes' => 0,
        'totalClientes' => 0
    ];

    // 1. Obtener todos los comprobantes que ya están en propuestas activas (para filtrar)
    $facturas_en_propuestas = [];
    $sql_prop = "SELECT items.n_comp_factura, propuestas.cod_cliente 
                 FROM FP_propuestas_pago_items items 
                 JOIN FP_propuestas_pago propuestas ON items.id_propuesta = propuestas.id 
                 WHERE propuestas.estado NOT IN ('RECHAZADA', 'CANCELADA', 'PAGADO', 'VENCIDA')";

    // Si estamos en detalle, filtramos solo para ese cliente para mayor eficiencia
    if ($cod_cliente) {
        $sql_prop .= " AND propuestas.cod_cliente = ?";
        $stmt_prop = sqlsrv_query($conn_apps, $sql_prop, [$cod_cliente]);
    } else {
        $stmt_prop = sqlsrv_query($conn_apps, $sql_prop);
    }

    if ($stmt_prop !== false) {
        while ($r = sqlsrv_fetch_array($stmt_prop, SQLSRV_FETCH_ASSOC)) {
            $facturas_en_propuestas[trim($r['n_comp_factura'])] = true;
        }
    }

    if ($cod_cliente) {
        // --- VISTA DE DETALLE (INDIVIDUAL) ---
        $sql_facturas = "
            SELECT 
                v.COD_CLIENT, v.RAZON_SOCI, v.FECHA_EMIS, v.T_COMP, v.N_COMP, 
                v.ESTADO, v.IMPORTE, v.FECHA_PROB_COBRO, v.PPP, v.IMPORTE_NETO,
                p.MEDIO_PAGO_DEFAULT, p.DIAS_PP_MAX, p.DESC_PP_MAX
            FROM $vista v
            LEFT JOIN RO_T_PARAMETROS_DESC_CLIENTES p ON v.COD_CLIENT = p.COD_CLIENT COLLATE Modern_Spanish_CI_AI
            WHERE v.COD_CLIENT = ? 
              AND v.ESTADO <> 'IMP'
            ORDER BY v.FECHA_EMIS DESC
        ";

        $stmt_facturas = sqlsrv_query($conn_central, $sql_facturas, [$cod_cliente]);
        if ($stmt_facturas === false) {
            throw new Exception("Error en la consulta de detalle de facturas: " . print_r(sqlsrv_errors(), true));
        }

        while ($row = sqlsrv_fetch_array($stmt_facturas, SQLSRV_FETCH_ASSOC)) {
            if (!isset($facturas_en_propuestas[trim($row['N_COMP'])])) {
                $tableData[] = $row;
            }
        }

    } else {
        // --- VISTA DE RESUMEN O SUGERENCIAS ---
        $sql = "SELECT v.COD_CLIENT, v.RAZON_SOCI, v.T_COMP, v.N_COMP, v.ESTADO, v.IMPORTE, v.IMPORTE_NETO, v.FECHA_PROB_COBRO,
                       ISNULL(p.DESC_PP_MAX, 0) as DESC_PP_MAX, p.MEDIO_PAGO_DEFAULT
                FROM $vista v
                LEFT JOIN RO_T_PARAMETROS_DESC_CLIENTES p ON v.COD_CLIENT = p.COD_CLIENT COLLATE Modern_Spanish_CI_AI
                WHERE v.ESTADO <> 'IMP' AND v.T_COMP <> 'REC'";

        $stmt = sqlsrv_query($conn_central, $sql);
        if ($stmt === false) {
            throw new Exception("Error en la consulta de resumen: " . print_r(sqlsrv_errors(), true));
        }

        $resumenClientes = [];
        while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $nComp = trim($v['N_COMP']);
            if (isset($facturas_en_propuestas[$nComp]))
                continue;

            $cod = $v['COD_CLIENT'];
            if (!isset($resumenClientes[$cod])) {
                $resumenClientes[$cod] = [
                    'COD_CLIENT' => $cod,
                    'RAZON_SOCI' => $v['RAZON_SOCI'],
                    'CANT_FACTURAS' => 0,
                    'TOTAL_BRUTO' => 0,
                    'TOTAL_NETO' => 0,
                    'COMPROBANTES' => [],
                    'FECHA_MIN' => null,
                    'FECHA_MAX' => null
                ];
            }

            $esNegativo = (strpos($v['T_COMP'], 'NC') === 0);
            $importe = (float) $v['IMPORTE'];
            $descPP = (float) $v['DESC_PP_MAX'];
            $porcDesc = 0;

            if (strpos($nComp, 'A00115') === 0) {
                if ($v['T_COMP'] === 'FAC')
                    $porcDesc = 0;
                else if ($v['T_COMP'] === 'NCP')
                    $porcDesc = $descPP;
            } else {
                $diff = $v['IMPORTE'] - $v['IMPORTE_NETO'];
                if ($v['IMPORTE'] > 0 && $diff > ($v['IMPORTE'] * $descPP))
                    $porcDesc = $diff / $v['IMPORTE'];
                else
                    $porcDesc = $descPP;
            }

            $netoItem = $importe * (1 - $porcDesc);
            $valNeto = ($esNegativo ? -$netoItem : $netoItem);
            
            $resumenClientes[$cod]['CANT_FACTURAS']++;
            $resumenClientes[$cod]['TOTAL_BRUTO'] += ($esNegativo ? -$importe : $importe);
            $resumenClientes[$cod]['TOTAL_NETO'] += $valNeto;
            
            // Para sugerencias, guardamos el detalle
            if ($tipo === 'sugerencias') {
                $fProbCobro = $v['FECHA_PROB_COBRO'] instanceof DateTime ? $v['FECHA_PROB_COBRO']->format('Y-m-d') : $v['FECHA_PROB_COBRO'];
                
                $resumenClientes[$cod]['COMPROBANTES_ALL'][] = [
                    't_comp' => trim($v['T_COMP']),
                    'n_comp' => trim($v['N_COMP']),
                    'importe_bruto' => ($esNegativo ? -$importe : $importe),
                    'importe_neto' => $valNeto,
                    'porcentaje_descuento' => $porcDesc * 100,
                    'fecha_prob_cobro' => $fProbCobro
                ];
                
                if ($fProbCobro) {
                    if (!$resumenClientes[$cod]['FECHA_MIN'] || $fProbCobro < $resumenClientes[$cod]['FECHA_MIN']) $resumenClientes[$cod]['FECHA_MIN'] = $fProbCobro;
                }
            }
        }

        foreach ($resumenClientes as $cod => $cliente) {
            if (round($cliente['TOTAL_BRUTO'], 2) != 0) {
                if ($tipo === 'sugerencias') {
                    // Ordenamos TODOS los comprobantes por fecha (vieja a nueva)
                    usort($resumenClientes[$cod]['COMPROBANTES_ALL'], function($a, $b) {
                        return strcmp($a['fecha_prob_cobro'] ?? '', $b['fecha_prob_cobro'] ?? '');
                    });

                    // SEGMENTACIÓN: Tomamos solo los primeros 10 comprobantes (más viejos)
                    $maxSugeridos = 10;
                    $sugeridos = array_slice($resumenClientes[$cod]['COMPROBANTES_ALL'], 0, $maxSugeridos);
                    
                    // Recalculamos totales de la SUGERENCIA específica
                    $totalBrutoSug = 0;
                    $totalNetoSug = 0;
                    foreach($sugeridos as $s) {
                        $totalBrutoSug += $s['importe_bruto'];
                        $totalNetoSug += $s['importe_neto'];
                    }

                    // Si la sugerencia da un saldo negativo o cero, no es una "propuesta de pago"
                    if ($totalNetoSug <= 0) {
                        unset($resumenClientes[$cod]);
                        continue;
                    }

                    $resumenClientes[$cod]['COMPROBANTES'] = $sugeridos;
                    $resumenClientes[$cod]['TOTAL_BRUTO_SUG'] = $totalBrutoSug;
                    $resumenClientes[$cod]['TOTAL_NETO_SUG'] = $totalNetoSug;
                    $resumenClientes[$cod]['TOTAL_PENDIENTE_CLIENTE'] = $cliente['TOTAL_BRUTO'];
                    $resumenClientes[$cod]['CANT_TOTAL_PENDIENTE'] = count($cliente['COMPROBANTES_ALL']);
                    
                    // --- CÁLCULO DE FECHA LÍMITE SUGERIDA: Factura más nueva + 15 días hábiles ---
                    $ultimaFechaStr = null;
                    foreach($sugeridos as $s) {
                        if (!$ultimaFechaStr || $s['fecha_prob_cobro'] > $ultimaFechaStr) $ultimaFechaStr = $s['fecha_prob_cobro'];
                    }
                    
                    if ($ultimaFechaStr) {
                         $fechaBase = new DateTime($ultimaFechaStr);
                         $fechaSugerida = sumarDiasHabilesCobranzas($fechaBase, 15);
                         $hoy = new DateTime();
                         $hoy->setTime(0, 0, 0); // Solo comparar fechas sin horas
                         
                         if ($fechaSugerida < $hoy) $fechaSugerida = $hoy;
                         
                         $resumenClientes[$cod]['FECHA_SUGERIDA'] = $fechaSugerida->format('Y-m-d');
                    } else {
                         $resumenClientes[$cod]['FECHA_SUGERIDA'] = (new DateTime())->format('Y-m-d');
                    }
                    
                    unset($resumenClientes[$cod]['COMPROBANTES_ALL']); // Limpiamos para no enviar peso extra innecesario
                    $tableData[] = $resumenClientes[$cod];
                } else {
                    unset($cliente['COMPROBANTES']);
                    unset($cliente['FECHA_MIN']);
                    unset($cliente['FECHA_MAX']);
                    $tableData[] = $cliente;
                }
                $summary['totalNeto'] += $cliente['TOTAL_NETO'];
                $summary['totalComprobantes'] += $cliente['CANT_FACTURAS'];
            }
        }
        $summary['totalClientes'] = count($tableData);

        if ($tipo === 'sugerencias') {
            // Ordenamos el listado general por la fecha mas vieja de cada cliente
            usort($tableData, function ($a, $b) {
                return strcmp($a['FECHA_MIN'] ?? '', $b['FECHA_MIN'] ?? '');
            });
        } else {
            usort($tableData, function ($a, $b) {
                return $b['TOTAL_NETO'] <=> $a['TOTAL_NETO'];
            });
        }
    }

    // Unificamos la respuesta final
    $response = ['summary' => $summary, 'data' => $tableData];
    echo json_encode($response);

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