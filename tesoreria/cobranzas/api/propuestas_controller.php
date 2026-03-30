<?php
session_start();

header('Content-Type: application/json');
require_once '../config/database.php';
require_once __DIR__ . '/notificaciones_controller.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $es_admin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin');

    switch ($action) {
        case 'listar_cliente':
            $codigos_cliente = $_SESSION['codigos_cliente_agrupados'] ?? [];
            if (empty($codigos_cliente)) {
                echo json_encode(['data' => []]);
                exit;
            }

            $placeholders = implode(',', array_fill(0, count($codigos_cliente), '?'));
            $conn_apps = Database::getConnection('apps');
            $sql = "SELECT id, cod_cliente, fecha_creacion, fecha_propuesta_pago, total_propuesto, estado FROM FP_propuestas_pago WHERE cod_cliente IN ($placeholders) ORDER BY fecha_creacion DESC";
            $stmt = sqlsrv_query($conn_apps, $sql, $codigos_cliente);
            $propuestas = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $propuestas[] = $row;
            }
            echo json_encode(['data' => $propuestas]);
            break;

        case 'ver_detalle':
            $id_propuesta = $_GET['id'] ?? 0;
            $conn_apps = Database::getConnection('apps');

            $sql_propuesta = "SELECT * FROM FP_propuestas_pago WHERE id = ?";
            $params = [$id_propuesta];

            if (!$es_admin) {
                $codigos_cliente_permitidos = $_SESSION['codigos_cliente_agrupados'] ?? [];
                if (empty($codigos_cliente_permitidos)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
                    exit;
                }
                $placeholders = implode(',', array_fill(0, count($codigos_cliente_permitidos), '?'));
                $sql_propuesta .= " AND cod_cliente IN ($placeholders)";
                $params = array_merge($params, $codigos_cliente_permitidos);
            }

            $stmt_propuesta = sqlsrv_query($conn_apps, $sql_propuesta, $params);
            $propuesta = sqlsrv_fetch_array($stmt_propuesta, SQLSRV_FETCH_ASSOC);

            if (!$propuesta) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Propuesta no encontrada o sin permisos para verla.']);
                exit;
            }

            $sql_items = "SELECT t_comp_factura, n_comp_factura, importe_bruto, importe_neto, porcentaje_descuento FROM FP_propuestas_pago_items WHERE id_propuesta = ?";
            $stmt_items = sqlsrv_query($conn_apps, $sql_items, [$id_propuesta]);
            $items = [];
            while ($row = sqlsrv_fetch_array($stmt_items, SQLSRV_FETCH_ASSOC))
                $items[] = $row;

            $sql_historial = "SELECT fecha_evento, tipo_usuario, descripcion, comentario, ruta_adjunto, json_data FROM FP_propuestas_pago_historial WHERE id_propuesta = ? ORDER BY fecha_evento ASC";
            $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, [$id_propuesta]);
            $historial = [];
            while ($row = sqlsrv_fetch_array($stmt_historial, SQLSRV_FETCH_ASSOC))
                $historial[] = $row;

            $adjuntos = [];
            $sql_adjuntos = "SELECT id, nombre_archivo, ruta_archivo, fecha_subida, id_cuota FROM FP_propuestas_adjuntos WHERE id_propuesta = ? ORDER BY fecha_subida DESC";
            $stmt_adjuntos = sqlsrv_query($conn_apps, $sql_adjuntos, [$id_propuesta]);
            if ($stmt_adjuntos !== false) {
                while ($row_adjunto = sqlsrv_fetch_array($stmt_adjuntos, SQLSRV_FETCH_ASSOC))
                    $adjuntos[] = $row_adjunto;
            }

            $cuotas = [];
            $sql_cuotas = "SELECT id, num_cuota, monto, fecha_vencimiento, estado FROM FP_propuestas_pago_cuotas WHERE id_propuesta = ? ORDER BY num_cuota ASC";
            $stmt_cuotas = sqlsrv_query($conn_apps, $sql_cuotas, [$id_propuesta]);
            if ($stmt_cuotas !== false) {
                while ($row_cuota = sqlsrv_fetch_array($stmt_cuotas, SQLSRV_FETCH_ASSOC))
                    $cuotas[] = $row_cuota;
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'propuesta' => $propuesta,
                    'items' => $items,
                    'historial' => $historial,
                    'adjuntos' => $adjuntos,
                    'cuotas' => $cuotas
                ]
            ]);
            break;

        case 'obtener_dashboard_admin':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            $conn_apps = Database::getConnection('apps');

            // --- AUTO-VENCIMIENTO GLOBAL ---
            // Revisamos vencimientos de todos los clientes antes de mostrar los KPIs
            require_once __DIR__ . '/vencimientos_controller.php';
            verificarYActualizarVencimientosCliente($conn_apps, null);

            $response = [];
            $sql_kpis = "SELECT 
                COUNT(CASE WHEN p.estado IN ('PENDIENTE_APROBACION_CLIENTE', 'PENDIENTE_APROBACION_FINAL', 'CONTRAPROPUESTA_CLIENTE') THEN 1 END) AS totalActivas, 
                SUM(CASE WHEN p.estado IN ('PENDIENTE_APROBACION_CLIENTE', 'PENDIENTE_APROBACION_FINAL', 'CONTRAPROPUESTA_CLIENTE') THEN p.total_propuesto ELSE 0 END) AS montoEnNegociacion, 
                COUNT(CASE WHEN p.estado = 'CONTRAPROPUESTA_CLIENTE' THEN 1 END) AS contrapropuestas, 
                (SELECT COUNT(DISTINCT h.id_propuesta) 
                 FROM FP_propuestas_pago_historial h
                 JOIN FP_propuestas_pago p2 ON h.id_propuesta = p2.id
                 WHERE CAST(h.descripcion AS varchar(max)) LIKE 'Propuesta aceptada por el cliente%'
                   AND h.fecha_evento >= DATEADD(day, -30, GETDATE())
                   AND NOT EXISTS (
                       SELECT 1 FROM FP_propuestas_pago_historial h2 
                       WHERE h2.id_propuesta = p2.id 
                         AND (CAST(h2.descripcion AS varchar(max)) LIKE '%Contrapropuesta%' 
                              OR CAST(h2.descripcion AS varchar(max)) LIKE '%actualizada por administración%')
                   )
                ) AS aceptadasMes, 
                SUM(CASE WHEN p.estado = 'VENCIDA' THEN p.total_propuesto ELSE 0 END) AS montoVencido 
            FROM FP_propuestas_pago p";
            $stmt_kpis = sqlsrv_query($conn_apps, $sql_kpis);
            $response['kpis'] = sqlsrv_fetch_array($stmt_kpis, SQLSRV_FETCH_ASSOC);

            $sql_estados = "SELECT estado, COUNT(*) as cantidad FROM FP_propuestas_pago GROUP BY estado";
            $stmt_estados = sqlsrv_query($conn_apps, $sql_estados);
            $grafico_estados = [];
            while ($row = sqlsrv_fetch_array($stmt_estados, SQLSRV_FETCH_ASSOC))
                $grafico_estados[] = $row;
            $response['graficoEstados'] = $grafico_estados;

            $sql_actividad = "SELECT CAST(h.fecha_evento AS DATE) AS dia, COUNT(DISTINCT h.id_propuesta) as cantidad 
                             FROM FP_propuestas_pago_historial h
                             JOIN FP_propuestas_pago p ON h.id_propuesta = p.id
                             WHERE CAST(h.descripcion AS varchar(max)) LIKE 'Propuesta aceptada por el cliente%'
                               AND h.fecha_evento >= DATEADD(day, -7, GETDATE())
                               AND NOT EXISTS (
                                   SELECT 1 FROM FP_propuestas_pago_historial h2 
                                   WHERE h2.id_propuesta = p.id 
                                     AND (CAST(h2.descripcion AS varchar(max)) LIKE '%Contrapropuesta%' 
                                          OR CAST(h2.descripcion AS varchar(max)) LIKE '%actualizada por administración%')
                               )
                             GROUP BY CAST(h.fecha_evento AS DATE) 
                             ORDER BY dia ASC";
            $stmt_actividad = sqlsrv_query($conn_apps, $sql_actividad);
            $grafico_actividad = [];
            while ($row = sqlsrv_fetch_array($stmt_actividad, SQLSRV_FETCH_ASSOC))
                $grafico_actividad[] = $row;
            $response['graficoActividad'] = $grafico_actividad;

            echo json_encode(['success' => true, 'data' => $response]);
            break;

        case 'obtener_indicadores_pro':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            $conn_apps = Database::getConnection('apps');
            $conn_central = Database::getConnection('central');
            $indicadores = [];

            $sql_ciclo = "WITH PrimerasRespuestas AS (
                SELECT id_propuesta, MIN(fecha_evento) as fecha_respuesta
                FROM FP_propuestas_pago_historial
                WHERE tipo_usuario = 'CLIENTE'
                  AND (CAST(descripcion AS varchar(max)) LIKE '%aceptada por el cliente%' 
                       OR CAST(descripcion AS varchar(max)) LIKE '%Contrapropuesta enviada%')
                GROUP BY id_propuesta
            )
            SELECT AVG(CAST(DATEDIFF(hour, p.fecha_creacion, pr.fecha_respuesta) AS FLOAT)) as avg_horas 
            FROM FP_propuestas_pago p 
            JOIN PrimerasRespuestas pr ON p.id = pr.id_propuesta 
            WHERE p.estado NOT IN ('RECHAZADA')";
            $stmt_ciclo = sqlsrv_query($conn_apps, $sql_ciclo);
            $row_ciclo = sqlsrv_fetch_array($stmt_ciclo, SQLSRV_FETCH_ASSOC);
            $indicadores['tiempo_promedio_horas'] = round($row_ciclo['avg_horas'] ?? 0, 1);

            $sql_beneficio = "SELECT COUNT(DISTINCT i.n_comp_factura) as cant_comps, SUM(i.importe_bruto - i.importe_neto) as total_ahorro FROM FP_propuestas_pago_items i JOIN FP_propuestas_pago p ON i.id_propuesta = p.id WHERE p.estado IN ('ACEPTADA', 'DOCUMENTACION_ADJUNTADA', 'PAGADO') AND (i.importe_bruto - i.importe_neto) > 0";
            $stmt_ben = sqlsrv_query($conn_apps, $sql_beneficio);
            $row_ben = sqlsrv_fetch_array($stmt_ben, SQLSRV_FETCH_ASSOC);
            $total_ahorro = round($row_ben['total_ahorro'] ?? 0, 2);
            $cant_comps_ben = intval($row_ben['cant_comps'] ?? 0);

            $indicadores['total_beneficio_otorgado'] = $total_ahorro;
            $indicadores['conteo_comprobantes_beneficio'] = $cant_comps_ben;
            $indicadores['promedio_beneficio_pesos'] = $cant_comps_ben > 0 ? round($total_ahorro / $cant_comps_ben, 2) : 0;

            $sql_conversion = "SELECT COUNT(*) as total, SUM(CASE WHEN estado IN ('ACEPTADA', 'DOCUMENTACION_ADJUNTADA', 'PAGADO') THEN 1 ELSE 0 END) as concretadas FROM FP_propuestas_pago";
            $stmt_conv = sqlsrv_query($conn_apps, $sql_conversion);
            $row_conv = sqlsrv_fetch_array($stmt_conv, SQLSRV_FETCH_ASSOC);
            $tot_prop = intval($row_conv['total'] ?: 1);
            $cant_conc = intval($row_conv['concretadas'] ?? 0);

            $indicadores['tasa_conversion'] = round(($cant_conc / $tot_prop) * 100, 1);
            $indicadores['cantidad_concretadas'] = $cant_conc;
            $indicadores['total_propuestas'] = $tot_prop;

            $sql_demora = "WITH PrimerasRespuestas AS (
                SELECT id_propuesta, MIN(fecha_evento) as fecha_respuesta
                FROM FP_propuestas_pago_historial
                WHERE tipo_usuario = 'CLIENTE'
                  AND (CAST(descripcion AS varchar(max)) LIKE '%aceptada por el cliente%' 
                       OR CAST(descripcion AS varchar(max)) LIKE '%Contrapropuesta enviada%')
                GROUP BY id_propuesta
            )
            SELECT TOP 5 p.cod_cliente, AVG(CAST(DATEDIFF(hour, p.fecha_creacion, pr.fecha_respuesta) AS FLOAT)) as promedio 
            FROM FP_propuestas_pago p 
            JOIN PrimerasRespuestas pr ON p.id = pr.id_propuesta 
            GROUP BY p.cod_cliente 
            ORDER BY promedio DESC";
            $stmt_demora = sqlsrv_query($conn_apps, $sql_demora);
            $codigos_demora = [];
            $ranking_demora_tmp = [];
            if ($stmt_demora) {
                while ($rd = sqlsrv_fetch_array($stmt_demora, SQLSRV_FETCH_ASSOC)) {
                    $ranking_demora_tmp[] = $rd;
                    $codigos_demora[] = $rd['cod_cliente'];
                }
            }

            $sql_top = "SELECT TOP 5 cod_cliente, SUM(total_propuesto) as total FROM FP_propuestas_pago WHERE estado NOT IN ('RECHAZADA', 'PAGADO', 'VENCIDA') GROUP BY cod_cliente ORDER BY total DESC";
            $stmt_top = sqlsrv_query($conn_apps, $sql_top);
            $codigos_deuda = [];
            $top_deudores_tmp = [];
            if ($stmt_top) {
                while ($row = sqlsrv_fetch_array($stmt_top, SQLSRV_FETCH_ASSOC)) {
                    $top_deudores_tmp[] = $row;
                    $codigos_deuda[] = $row['cod_cliente'];
                }
            }

            $todos_codigos = array_unique(array_merge($codigos_demora, $codigos_deuda));
            $nombres_map = [];
            if (!empty($todos_codigos) && $conn_central) {
                $placeholders = implode(',', array_fill(0, count($todos_codigos), '?'));
                $sql_n = "SELECT COD_CLIENT, RAZON_SOCI FROM RO_V_COBRANZA_PEND_FRANQUICIAS WHERE COD_CLIENT IN ($placeholders) UNION SELECT COD_CLIENT, RAZON_SOCI FROM RO_V_COBRANZA_PEND_MAYORISTAS WHERE COD_CLIENT IN ($placeholders)";
                $stmt_n = sqlsrv_query($conn_central, $sql_n, array_merge($todos_codigos, $todos_codigos));
                if ($stmt_n) {
                    while ($rn = sqlsrv_fetch_array($stmt_n, SQLSRV_FETCH_ASSOC)) {
                        $nombres_map[$rn['COD_CLIENT']] = $rn['RAZON_SOCI'];
                    }
                }
            }

            $indicadores['ranking_deuda'] = array_map(function ($d) use ($nombres_map) {
                return ['cliente' => $nombres_map[$d['cod_cliente']] ?? "Cliente " . $d['cod_cliente'], 'monto' => $d['total'], 'codigo' => $d['cod_cliente']];
            }, $top_deudores_tmp);
            $indicadores['ranking_demora'] = array_map(function ($d) use ($nombres_map) {
                return ['cliente' => $nombres_map[$d['cod_cliente']] ?? "Cliente " . $d['cod_cliente'], 'horas' => round($d['promedio'], 1), 'codigo' => $d['cod_cliente']];
            }, $ranking_demora_tmp);

            $sql_medios = "SELECT medio_de_pago, COUNT(*) as cantidad FROM FP_propuestas_pago WHERE medio_de_pago IS NOT NULL GROUP BY medio_de_pago ORDER BY cantidad DESC";
            $stmt_medios = sqlsrv_query($conn_apps, $sql_medios);
            $medios = [];
            if ($stmt_medios) {
                while ($rm = sqlsrv_fetch_array($stmt_medios, SQLSRV_FETCH_ASSOC))
                    $medios[] = $rm;
            }
            $indicadores['distribucion_medios'] = $medios;
            $indicadores['metodo_preferido'] = !empty($medios) ? [
                'medio' => $medios[0]['medio_de_pago'],
                'cantidad' => $medios[0]['cantidad']
            ] : ['medio' => 'N/A', 'cantidad' => 0];

            // Métricas adicionales para visualizaciones avanzadas
            $sql_estados = "SELECT estado, COUNT(*) as cantidad, SUM(total_propuesto) as monto_total FROM FP_propuestas_pago GROUP BY estado";
            $stmt_estados = sqlsrv_query($conn_apps, $sql_estados);
            $estados_dist = [];
            if ($stmt_estados) {
                while ($re = sqlsrv_fetch_array($stmt_estados, SQLSRV_FETCH_ASSOC))
                    $estados_dist[] = $re;
            }
            $indicadores['distribucion_estados'] = $estados_dist;

            // Tendencia últimos 30 días
            $sql_tendencia = "SELECT CAST(fecha_creacion AS DATE) as fecha, COUNT(*) as cantidad, SUM(total_propuesto) as monto FROM FP_propuestas_pago WHERE fecha_creacion >= DATEADD(day, -30, GETDATE()) GROUP BY CAST(fecha_creacion AS DATE) ORDER BY fecha ASC";
            $stmt_tend = sqlsrv_query($conn_apps, $sql_tendencia);
            $tendencia = [];
            if ($stmt_tend) {
                while ($rt = sqlsrv_fetch_array($stmt_tend, SQLSRV_FETCH_ASSOC)) {
                    $tendencia[] = [
                        'fecha' => $rt['fecha'] instanceof DateTime ? $rt['fecha']->format('Y-m-d') : $rt['fecha'],
                        'cantidad' => $rt['cantidad'],
                        'monto' => $rt['monto']
                    ];
                }
            }
            $indicadores['tendencia_30dias'] = $tendencia;

            // Promedio de descuento otorgado
            $sql_desc_prom = "SELECT AVG(porcentaje_descuento) as promedio FROM FP_propuestas_pago_items WHERE porcentaje_descuento > 0";
            $stmt_desc = sqlsrv_query($conn_apps, $sql_desc_prom);
            $row_desc = sqlsrv_fetch_array($stmt_desc, SQLSRV_FETCH_ASSOC);
            $indicadores['descuento_promedio'] = round($row_desc['promedio'] ?? 0, 1);

            // Ranking de clientes con mayor deuda TOTAL (independiente de propuestas)
            $ranking_deuda_total = [];
            $deuda_total_sistema = 0; // Este será el total "Falta Cobrar" (Neto Central + En Negociación)
            $monto_neto_central = 0;

            if ($conn_central) {
                // 1. Obtener facturas en propuestas activas para excluirlas del cálculo de "Neto Central"
                $facturas_activas = [];
                $sql_f_activas = "SELECT items.n_comp_factura FROM FP_propuestas_pago_items items JOIN FP_propuestas_pago p ON items.id_propuesta = p.id WHERE p.estado NOT IN ('RECHAZADA', 'CANCELADA', 'PAGADO', 'VENCIDA')";
                $stmt_f_activas = sqlsrv_query($conn_apps, $sql_f_activas);
                if ($stmt_f_activas) {
                    while ($rfa = sqlsrv_fetch_array($stmt_f_activas, SQLSRV_FETCH_ASSOC)) {
                        $facturas_activas[trim($rfa['n_comp_factura'])] = true;
                    }
                }

                $deudas_acumuladas = [];
                $vistas = ['RO_V_COBRANZA_PEND_FRANQUICIAS', 'RO_V_COBRANZA_PEND_MAYORISTAS'];

                foreach ($vistas as $v) {
                    $sql_v = "SELECT v.COD_CLIENT, v.RAZON_SOCI, v.T_COMP, v.N_COMP, v.IMPORTE, v.IMPORTE_NETO, ISNULL(p.DESC_PP_MAX, 0) as DESC_PP_MAX
                              FROM $v v
                              LEFT JOIN RO_T_PARAMETROS_DESC_CLIENTES p ON v.COD_CLIENT = p.COD_CLIENT COLLATE Modern_Spanish_CI_AI
                              WHERE v.ESTADO <> 'IMP'";

                    $stmt_v = sqlsrv_query($conn_central, $sql_v);
                    if ($stmt_v) {
                        while ($row = sqlsrv_fetch_array($stmt_v, SQLSRV_FETCH_ASSOC)) {
                            $nComp = trim($row['N_COMP']);
                            $esNegociacion = isset($facturas_activas[$nComp]);

                            // Cálculo del NETO siguiendo la misma lógica del dashboard principal
                            $importe = (float) $row['IMPORTE'];
                            $descPP = (float) $row['DESC_PP_MAX'];
                            $porcDesc = 0;

                            if (strpos($nComp, 'A00115') === 0) {
                                $porcDesc = ($row['T_COMP'] === 'NCP') ? $descPP : 0;
                            } else {
                                $diff = $row['IMPORTE'] - $row['IMPORTE_NETO'];
                                $porcDesc = ($row['IMPORTE'] > 0 && $diff > ($row['IMPORTE'] * $descPP)) ? ($diff / $row['IMPORTE']) : $descPP;
                            }

                            $netoItem = $importe * (1 - $porcDesc);
                            $esNC = (strpos($row['T_COMP'], 'NC') === 0);
                            $valorFinal = $esNC ? -$netoItem : $netoItem;

                            // Acumular para el ranking (aquí sí incluimos todo para ver deuda total del cliente)
                            $codigo = trim($row['COD_CLIENT']);
                            if (!isset($deudas_acumuladas[$codigo])) {
                                $deudas_acumuladas[$codigo] = ['codigo' => $codigo, 'cliente' => trim($row['RAZON_SOCI']), 'deuda' => 0];
                            }
                            $deudas_acumuladas[$codigo]['deuda'] += $valorFinal;

                            // Si NO está en negociación, suma al "Neto a Cobrar"
                            if (!$esNegociacion) {
                                $monto_neto_central += $valorFinal;
                            }
                        }
                    }
                }

                // Ordenar y formatear ranking
                usort($deudas_acumuladas, function ($a, $b) {
                    return $b['deuda'] <=> $a['deuda'];
                });
                $ranking_deuda_total = array_slice(array_map(function ($item) {
                    return ['codigo' => $item['codigo'], 'cliente' => $item['cliente'], 'deuda' => round($item['deuda'], 2)];
                }, $deudas_acumuladas), 0, 10);
            }
            $indicadores['ranking_deuda_total'] = $ranking_deuda_total;

            // Obtener Monto en Negociación (Activas)
            $sql_neg = "SELECT SUM(total_propuesto) as total FROM FP_propuestas_pago WHERE estado IN ('PENDIENTE_APROBACION_CLIENTE', 'PENDIENTE_APROBACION_FINAL', 'CONTRAPROPUESTA_CLIENTE')";
            $stmt_neg = sqlsrv_query($conn_apps, $sql_neg);
            $row_neg = sqlsrv_fetch_array($stmt_neg, SQLSRV_FETCH_ASSOC);
            $monto_negociacion = floatval($row_neg['total'] ?? 0);

            // TOTAL FALTA COBRAR = Neto Central + Monto en Negociación
            $deuda_total_sistema = $monto_neto_central + $monto_negociacion;
            $indicadores['ranking_deuda_total'] = $ranking_deuda_total;

            // Calcular Eficiencia de Cobranza (Cobrado REAL vs Deuda Total)
            $sql_cobrado = "SELECT SUM(total_propuesto) as total_cobrado FROM FP_propuestas_pago WHERE estado = 'PAGADO'";
            $stmt_cobrado = sqlsrv_query($conn_apps, $sql_cobrado);
            $row_cobrado = sqlsrv_fetch_array($stmt_cobrado, SQLSRV_FETCH_ASSOC);
            $total_cobrado = floatval($row_cobrado['total_cobrado'] ?? 0);

            $indicadores['total_cobrado_historico'] = $total_cobrado;
            $denominator = $total_cobrado + $deuda_total_sistema;
            $indicadores['eficiencia_cobranza'] = $denominator > 0 ? round(($total_cobrado / $denominator) * 100, 1) : 0;
            $indicadores['deuda_total_sistema'] = $deuda_total_sistema;

            echo json_encode(['success' => true, 'data' => $indicadores]);
            break;

        case 'sincronizar_estados_pagados':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            $conn_apps = Database::getConnection('apps');
            $conn_central = Database::getConnection('central');
            $propuestas_actualizadas = 0;
            try {
                // Forzado especial puntual solicitado por administración para la propuesta #1252
                sqlsrv_query($conn_apps, "UPDATE FP_propuestas_pago SET estado = 'PAGADO', fecha_ultima_modificacion = GETDATE() WHERE id = 1252 AND estado <> 'PAGADO'");

                $sql_propuestas_a_verificar = "SELECT id FROM FP_propuestas_pago WHERE estado IN ('DOCUMENTACION_ADJUNTADA', 'ACEPTADA', 'PENDIENTE_APROBACION_FINAL', 'CONTRAPROPUESTA_CLIENTE')";
                $stmt_propuestas = sqlsrv_query($conn_apps, $sql_propuestas_a_verificar);
                $propuestas_a_verificar = [];
                while ($row = sqlsrv_fetch_array($stmt_propuestas, SQLSRV_FETCH_ASSOC))
                    $propuestas_a_verificar[] = $row['id'];
                
                if (empty($propuestas_a_verificar)) {
                    echo json_encode(['success' => true, 'message' => 'No hay propuestas pendientes de sincronización (se forzó el cierre de la #1252 si correspondía).']);
                    exit;
                }

                foreach ($propuestas_a_verificar as $id_propuesta) {
                    $sql_items = "SELECT t_comp_factura, n_comp_factura FROM FP_propuestas_pago_items WHERE id_propuesta = ?";
                    $stmt_items = sqlsrv_query($conn_apps, $sql_items, [$id_propuesta]);
                    $items_de_propuesta = [];
                    $solo_facturas = true;
                    while ($item = sqlsrv_fetch_array($stmt_items, SQLSRV_FETCH_ASSOC)) {
                        $items_de_propuesta[] = $item;
                        if (strpos(trim($item['t_comp_factura']), 'NC') !== false)
                            $solo_facturas = false;
                    }
                    if (
                        $solo_facturas === false && empty(array_filter($items_de_propuesta, function ($i) {
                            return strpos(trim($i['t_comp_factura']), 'FAC') !== false;
                        }))
                    ) {
                        sqlsrv_query($conn_apps, "UPDATE FP_propuestas_pago SET estado = 'PAGADO', fecha_ultima_modificacion = GETDATE() WHERE id = ?", [$id_propuesta]);
                        $propuestas_actualizadas++;
                        continue;
                    }
                    $todas_facturas_canceladas = true;
                    foreach ($items_de_propuesta as $item_a_verificar) {
                        if (strpos(trim($item_a_verificar['t_comp_factura']), 'FAC') === false)
                            continue;
                        $stmt_estado = sqlsrv_query($conn_central, "SELECT ESTADO FROM GVA12 WHERE T_COMP = ? AND N_COMP = ?", [trim($item_a_verificar['t_comp_factura']), trim($item_a_verificar['n_comp_factura'])]);
                        $estado_row = sqlsrv_fetch_array($stmt_estado, SQLSRV_FETCH_ASSOC);
                        if (!$estado_row || trim($estado_row['ESTADO']) !== 'CAN') {
                            $todas_facturas_canceladas = false;
                            break;
                        }
                    }
                    if ($todas_facturas_canceladas) {
                        sqlsrv_query($conn_apps, "UPDATE FP_propuestas_pago SET estado = 'PAGADO', fecha_ultima_modificacion = GETDATE() WHERE id = ?", [$id_propuesta]);
                        sqlsrv_query($conn_apps, "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion) VALUES (?, ?, ?, ?)", [$id_propuesta, $_SESSION['usuario_id'], 'SISTEMA', "El sistema verificó el pago."]);
                        $propuestas_actualizadas++;
                    }
                }
                echo json_encode(['success' => true, 'message' => "Se actualizaron $propuestas_actualizadas propuestas."]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'enviar_avisos_vencimiento':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            require_once __DIR__ . '/notificaciones_controller.php';
            $conn_apps = Database::getConnection('apps');
            $sql = "SELECT id, cod_cliente, fecha_creacion FROM FP_propuestas_pago WHERE estado = 'PENDIENTE_APROBACION_CLIENTE' AND aviso_vencimiento_enviado = 0";
            $stmt = sqlsrv_query($conn_apps, $sql);
            $avisos_enviados = 0;
            while ($p = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $fecha_creacion = $p['fecha_creacion'];
                if ($fecha_creacion instanceof DateTime) {
                    $ts = $fecha_creacion->getTimestamp();
                } else {
                    $ts = strtotime($fecha_creacion);
                }
                $horas = (time() - $ts) / 3600;

                if ($horas >= 72) {
                    $datos_cliente = obtenerEmailFranquiciado($p['cod_cliente']);
                    if ($datos_cliente && $datos_cliente['email']) {
                        $titulo = "Aviso de Vencimiento Próximo (ID: #{$p['id']})";
                        $mensaje = "Hola <strong>{$datos_cliente['razon_social']}</strong>,<br><br>Su propuesta de pago #{$p['id']} vencerá en las próximas 24 horas.<br><br>Por favor, ingrese al portal para revisarla y tomar una acción.";
                        $cuerpo = generarCuerpoEmail($titulo, $mensaje, "Ver Propuesta", "https://app.xl.com.ar/administracion/tesoreria/cobranzas/portal_cliente.php");

                        if (enviarNotificacion($datos_cliente['email'], $titulo, $cuerpo)) {
                            sqlsrv_query($conn_apps, "UPDATE FP_propuestas_pago SET aviso_vencimiento_enviado = 1 WHERE id = ?", [$p['id']]);
                            $avisos_enviados++;
                        }
                    }
                }
            }
            echo json_encode(['success' => true, 'message' => "Se procesaron $avisos_enviados avisos de vencimiento (72hs)."]);
            break;

        case 'ejecutar_recordatorios':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            require_once __DIR__ . '/cron_recordatorio_pagos.php';
            $conn_apps = Database::getConnection('apps');
            $enviados = ejecutarRecordatoriosPago($conn_apps);
            echo json_encode(['success' => true, 'message' => "Se procesaron $enviados recordatorios de pago."]);
            break;

        case 'obtener_cronograma_admin':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            $conn_apps = Database::getConnection('apps');
            $conn_central = Database::getConnection('central');
            $sql = "SELECT id, fecha_propuesta_pago, total_propuesto, cod_cliente, medio_de_pago FROM FP_propuestas_pago WHERE estado = 'ACEPTADA' AND fecha_propuesta_pago IS NOT NULL";
            $stmt = sqlsrv_query($conn_apps, $sql);
            $eventos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $eventos[] = [
                    'title' => '$' . number_format($row['total_propuesto'], 2) . ' - ' . $row['cod_cliente'],
                    'start' => ($row['fecha_propuesta_pago'] instanceof DateTime) ? $row['fecha_propuesta_pago']->format('Y-m-d') : $row['fecha_propuesta_pago'],
                    'extendedProps' => ['id' => $row['id'], 'monto' => $row['total_propuesto'], 'cliente' => $row['cod_cliente'], 'medio_de_pago' => $row['medio_de_pago']]
                ];
            }
            echo json_encode(['success' => true, 'data' => $eventos]);
            break;

        case 'listar_admin':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            $conn_apps = Database::getConnection('apps');
            $conn_central = Database::getConnection('central');

            // --- AUTO-VENCIMIENTO GLOBAL ---
            // Refrescamos estados antes de listar
            require_once __DIR__ . '/vencimientos_controller.php';
            verificarYActualizarVencimientosCliente($conn_apps, null);

            // --- RECOLECCIÓN DE FILTROS ---
            $f_desde = $_GET['f_desde'] ?? '';
            $f_hasta = $_GET['f_hasta'] ?? '';
            $codigo = $_GET['codigo'] ?? '';
            $razon = $_GET['razon'] ?? '';
            $estado = $_GET['estado'] ?? '';

            $where = " WHERE 1=1";
            $params = [];

            if (!empty($f_desde)) {
                $where .= " AND fecha_creacion >= ?";
                $params[] = $f_desde . ' 00:00:00';
            }
            if (!empty($f_hasta)) {
                $where .= " AND fecha_creacion <= ?";
                $params[] = $f_hasta . ' 23:59:59';
            }
            if (!empty($codigo)) {
                $where .= " AND cod_cliente LIKE ?";
                $params[] = "%$codigo%";
            }
            if (!empty($estado)) {
                $where .= " AND estado = ?";
                $params[] = $estado;
            }

            // --- FILTRO POR RAZÓN SOCIAL (Requiere búsqueda en base central) ---
            if (!empty($razon) && $conn_central) {
                $sql_razon = "SELECT COD_CLIENT FROM GVA14 WHERE RAZON_SOCI LIKE ?";
                $stmt_razon = sqlsrv_query($conn_central, $sql_razon, ["%$razon%"]);
                $cods_filtrados = [];
                if ($stmt_razon) {
                    while ($r = sqlsrv_fetch_array($stmt_razon, SQLSRV_FETCH_ASSOC)) {
                        $cods_filtrados[] = "'" . trim($r['COD_CLIENT']) . "'";
                    }
                }

                if (!empty($cods_filtrados)) {
                    $where .= " AND cod_cliente IN (" . implode(',', $cods_filtrados) . ")";
                } else {
                    // Si buscó y no hubo coincidencias en GVA14, forzamos resultado vacío
                    $where .= " AND 1=0";
                }
            }

            $sql = "SELECT id, cod_cliente, fecha_creacion, fecha_propuesta_pago, fecha_ultima_modificacion, total_propuesto, estado, 
                           DATEDIFF(day, fecha_creacion, fecha_propuesta_pago) as dias_plazo 
                    FROM FP_propuestas_pago" . $where . " ORDER BY id DESC";
            $stmt = sqlsrv_query($conn_apps, $sql, $params);

            $propuestas = [];
            $codigos = [];
            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $propuestas[] = $row;
                    if (!empty($row['cod_cliente'])) {
                        $codigos[] = "'" . trim($row['cod_cliente']) . "'";
                    }
                }
            }

            // Obtener nombres para los resultados (base central)
            $nombres = [];
            if (!empty($codigos)) {
                $codigos_str = implode(',', array_unique($codigos));
                $sql_nombres = "SELECT COD_CLIENT, RAZON_SOCI FROM GVA14 WHERE COD_CLIENT IN ($codigos_str)";
                $stmt_n = sqlsrv_query($conn_central, $sql_nombres);
                if ($stmt_n) {
                    while ($rn = sqlsrv_fetch_array($stmt_n, SQLSRV_FETCH_ASSOC)) {
                        $nombres[trim($rn['COD_CLIENT'])] = trim($rn['RAZON_SOCI']);
                    }
                }
            }

            foreach ($propuestas as &$p) {
                $p['razon_social'] = $nombres[trim($p['cod_cliente'])] ?? 'Desconocido';
            }

            echo json_encode(['data' => $propuestas]);
            break;
        case 'actualizar_estado_admin':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            $id = $_POST['id_propuesta'] ?? 0;
            $estado = $_POST['nuevo_estado'] ?? '';
            $conn_apps = Database::getConnection('apps');

            // --- PROTECCIÓN CONTRA DUPLICADOS ---
            $sql_check = "SELECT estado FROM FP_propuestas_pago WHERE id = ?";
            $stmt_check = sqlsrv_query($conn_apps, $sql_check, [$id]);
            if ($row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC)) {
                if (trim($row_check['estado']) === $estado) {
                    echo json_encode(['success' => true, 'message' => 'La propuesta ya se encuentra en ese estado.']);
                    exit;
                }
            }

            sqlsrv_query($conn_apps, "UPDATE FP_propuestas_pago SET estado = ?, fecha_ultima_modificacion = GETDATE() WHERE id = ?", [$estado, $id]);
            sqlsrv_query($conn_apps, "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario) VALUES (?, ?, ?, ?, ?)", [$id, $_SESSION['usuario_id'], 'ADMIN', "Actualización de estado por admin", $_POST['comentario'] ?? '']);

            // --- RESPUESTA INMEDIATA PARA EVITAR TIMEOUT ---
            $response = json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
            if (ob_get_level()) ob_end_clean();
            header('Connection: close');
            header('Content-Length: ' . strlen($response));
            header('Content-Type: application/json');
            echo $response;
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                flush();
                if (session_id()) session_write_close();
                ignore_user_abort(true);
            }

            // --- NOTIFICACIÓN AL CLIENTE (EN SEGUNDO PLANO) ---
            try {
                require_once __DIR__ . '/notificaciones_controller.php';
                $stmt_c = sqlsrv_query($conn_apps, "SELECT cod_cliente FROM FP_propuestas_pago WHERE id = ?", [$id]);
                $p_row = sqlsrv_fetch_array($stmt_c, SQLSRV_FETCH_ASSOC);
                if ($p_row && $estado === 'PENDIENTE_APROBACION_CLIENTE') {
                    $datos_cliente = obtenerEmailFranquiciado($p_row['cod_cliente']);
                    if ($datos_cliente && $datos_cliente['email']) {
                        $titulo = "Actualización de Propuesta (ID: #{$id})";
                        $mensaje = "Hola <strong>{$datos_cliente['razon_social']}</strong>,<br><br>La administración ha actualizado los términos de su propuesta de pago #{$id}.<br><br>Por favor, ingrese al portal de clientes para revisarla.";
                        $cuerpo = generarCuerpoEmail($titulo, $mensaje, "Ver Propuesta", "https://app.xl.com.ar/administracion/tesoreria/cobranzas/portal_cliente.php");
                        enviarNotificacion($datos_cliente['email'], $titulo, $cuerpo);
                    }
                }
            } catch (Exception $e_mail) {
                error_log("Error in notification: " . $e_mail->getMessage());
            }
            break;

        case 'obtener_kpis_cliente':
            $codigos = $_SESSION['codigos_cliente_agrupados'] ?? [];

            // Estructura por defecto en caso de que no haya clientes o falle algo
            $resultado = [
                'deudaTotalPendiente' => 0,
                'montoEnNegociacion' => 0,
                'pendienteDePago' => 0,
                'propuestasRequierenAccion' => 0
            ];

            if (!empty($codigos)) {
                // Limpiamos los códigos para evitar problemas de comparación
                $params = array_map('trim', array_values($codigos));
                $placeholders = implode(',', array_fill(0, count($params), '?'));

                $conn_apps = Database::getConnection('apps');
                $conn_central = Database::getConnection('central');

                // 1. Obtener todos los comprobantes que ya están en propuestas activas (para excluir en SQL)
                $facturas_en_propuestas = [];
                $sql_prop = "SELECT items.n_comp_factura 
                             FROM FP_propuestas_pago_items items 
                             JOIN FP_propuestas_pago propuestas ON items.id_propuesta = propuestas.id 
                             WHERE propuestas.cod_cliente IN ($placeholders) 
                             AND propuestas.estado NOT IN ('RECHAZADA', 'CANCELADA', 'PAGADO', 'VENCIDA')";

                $stmt_prop = sqlsrv_query($conn_apps, $sql_prop, $params);
                if ($stmt_prop !== false) {
                    while ($r = sqlsrv_fetch_array($stmt_prop, SQLSRV_FETCH_ASSOC)) {
                        $facturas_en_propuestas[] = trim($r['n_comp_factura']);
                    }
                }

                // 2. Obtener Deuda Total Fuera de Propuesta (Calculado en SQL para máxima velocidad)
                $deuda_fuera_propuesta = 0;
                if ($conn_central) {
                    $vistas = ['RO_V_COBRANZA_PEND_FRANQUICIAS', 'RO_V_COBRANZA_PEND_MAYORISTAS'];

                    // Preparamos el filtro de exclusión si hay facturas en propuestas
                    $where_exclude = "";
                    $sql_params = $params; // Empezamos con los códigos de cliente

                    if (!empty($facturas_en_propuestas)) {
                        $placeholders_excl = implode(',', array_fill(0, count($facturas_en_propuestas), '?'));
                        $where_exclude = " AND v.N_COMP NOT IN ($placeholders_excl)";
                        $sql_params = array_merge($sql_params, $facturas_en_propuestas);
                    }

                    foreach ($vistas as $vista) {
                        // Replicamos la lógica aritmética exacta de Mariela pero directamente en SQL
                        $sql_v = "SELECT SUM(
                                    CASE WHEN v.T_COMP LIKE 'NC%' THEN -1.0 ELSE 1.0 END * 
                                    (v.IMPORTE * (1.0 - 
                                        CASE 
                                            WHEN v.N_COMP LIKE 'A00115%' THEN 
                                                CASE 
                                                    WHEN v.T_COMP = 'FAC' THEN 0.0 
                                                    WHEN v.T_COMP = 'NCP' THEN ISNULL(p.DESC_PP_MAX, 0.0)
                                                    ELSE 0.0
                                                END
                                            ELSE 
                                                CASE 
                                                    WHEN v.IMPORTE > 0 AND (v.IMPORTE - ISNULL(v.IMPORTE_NETO, v.IMPORTE)) > (v.IMPORTE * ISNULL(p.DESC_PP_MAX, 0.0))
                                                    THEN (v.IMPORTE - ISNULL(v.IMPORTE_NETO, v.IMPORTE)) / v.IMPORTE
                                                    ELSE ISNULL(p.DESC_PP_MAX, 0.0)
                                                END
                                        END
                                    ))
                                ) as total_neto
                                FROM $vista v
                                LEFT JOIN RO_T_PARAMETROS_DESC_CLIENTES p ON v.COD_CLIENT = p.COD_CLIENT COLLATE Modern_Spanish_CI_AI
                                WHERE v.COD_CLIENT IN ($placeholders) AND v.ESTADO <> 'IMP' AND v.T_COMP <> 'REC' AND v.T_COMP NOT LIKE 'NCR%' AND v.T_COMP NOT LIKE 'NCP%' $where_exclude";

                        $stmt_v = sqlsrv_query($conn_central, $sql_v, $sql_params);
                        if ($stmt_v) {
                            $row_v = sqlsrv_fetch_array($stmt_v, SQLSRV_FETCH_ASSOC);
                            $deuda_fuera_propuesta += floatval($row_v['total_neto'] ?? 0);
                        }
                    }
                }

                $deuda_pendiente_real = $deuda_fuera_propuesta > 0 ? $deuda_fuera_propuesta : 0;

                // 3. Obtener el resto de KPIs desde la APP Local
                $sql = "SELECT 
                        SUM(CASE WHEN estado LIKE 'PENDIENTE%' OR estado = 'CONTRAPROPUESTA_CLIENTE' THEN total_propuesto ELSE 0 END) AS montoEnNegociacion, 
                        COUNT(CASE WHEN estado IN ('PENDIENTE_APROBACION_CLIENTE', 'ACEPTADA') THEN 1 END) AS propuestasRequierenAccion, 
                        SUM(CASE WHEN estado = 'ACEPTADA' THEN total_propuesto ELSE 0 END) AS pendienteDePago 
                        FROM FP_propuestas_pago 
                        WHERE cod_cliente IN ($placeholders) 
                        AND estado NOT IN ('RECHAZADA', 'PAGADO', 'VENCIDA', 'CANCELADA')";

                $stmt = sqlsrv_query($conn_apps, $sql, $params);

                if ($stmt) {
                    $datos = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

                    $resultado['deudaTotalPendiente'] = round($deuda_pendiente_real, 2);
                    $resultado['montoEnNegociacion'] = round(floatval($datos['montoEnNegociacion'] ?? 0), 2);
                    $resultado['pendienteDePago'] = round(floatval($datos['pendienteDePago'] ?? 0), 2);
                    $resultado['propuestasRequierenAccion'] = intval($datos['propuestasRequierenAccion'] ?? 0);
                }
            }

            echo json_encode(['success' => true, 'data' => $resultado]);
            break;

        case 'obtener_cronograma_cliente':
            $codigos = $_SESSION['codigos_cliente_agrupados'] ?? [];
            if (empty($codigos)) {
                echo json_encode(['success' => true, 'data' => []]);
                exit;
            }
            $placeholders = implode(',', array_fill(0, count($codigos), '?'));
            $conn_apps = Database::getConnection('apps');
            $sql = "SELECT p.id, p.cod_cliente, ISNULL(c.fecha_vencimiento, p.fecha_propuesta_pago) as fecha, ISNULL(c.monto, p.total_propuesto) as monto, c.num_cuota, (SELECT COUNT(*) FROM FP_propuestas_pago_cuotas WHERE id_propuesta = p.id) as total_cuotas FROM FP_propuestas_pago p LEFT JOIN FP_propuestas_pago_cuotas c ON p.id = c.id_propuesta WHERE p.cod_cliente IN ($placeholders) AND p.estado = 'ACEPTADA'";
            $stmt = sqlsrv_query($conn_apps, $sql, $codigos);
            $eventos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $eventos[] = [
                    'date' => ($row['fecha'] instanceof DateTime) ? $row['fecha']->format('Y-m-d') : substr($row['fecha'], 0, 10),
                    'id' => $row['id'],
                    'cod_cliente' => $row['cod_cliente'],
                    'monto' => $row['monto'],
                    'num_cuota' => $row['num_cuota'] ?? 1,
                    'total_cuotas' => $row['total_cuotas'] ?? 1
                ];
            }
            echo json_encode(['success' => true, 'data' => $eventos]);
            break;

        case 'eliminar_propuesta':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            $id = $_POST['id_propuesta'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de propuesta no proporcionado.']);
                exit;
            }
            $conn_apps = Database::getConnection('apps');

            // Eliminar registros relacionados
            sqlsrv_query($conn_apps, "DELETE FROM FP_propuestas_pago_items WHERE id_propuesta = ?", [$id]);
            sqlsrv_query($conn_apps, "DELETE FROM FP_propuestas_pago_historial WHERE id_propuesta = ?", [$id]);
            sqlsrv_query($conn_apps, "DELETE FROM FP_propuestas_pago_cuotas WHERE id_propuesta = ?", [$id]);
            sqlsrv_query($conn_apps, "DELETE FROM FP_propuestas_adjuntos WHERE id_propuesta = ?", [$id]);

            // Eliminar propuesta principal
            $sql = "DELETE FROM FP_propuestas_pago WHERE id = ?";
            $stmt = sqlsrv_query($conn_apps, $sql, [$id]);

            if ($stmt) {
                echo json_encode(['success' => true, 'message' => 'Propuesta eliminada correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar la propuesta.']);
            }
            break;

        case 'aceptar_contrapropuesta_admin':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            $id = $_POST['id_propuesta'] ?? 0;
            $comentario = $_POST['comentario'] ?? '';
            $total_propuesto = $_POST['total_propuesto'] ?? 0;
            $items = json_decode($_POST['comprobantes'] ?? '[]', true);
            $cuotas = json_decode($_POST['cuotas'] ?? '[]', true);

            $conn_apps = Database::getConnection('apps');

            // --- PROTECCIÓN CONTRA DUPLICADOS ---
            $sql_check = "SELECT estado FROM FP_propuestas_pago WHERE id = ?";
            $stmt_check = sqlsrv_query($conn_apps, $sql_check, [$id]);
            if ($row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC)) {
                if (trim($row_check['estado']) === 'ACEPTADA') {
                    echo json_encode(['success' => true, 'message' => 'Esta propuesta ya fue aceptada anteriormente.']);
                    exit;
                }
            }

            sqlsrv_begin_transaction($conn_apps);

            try {
                // 1. Actualizar cabecera
                $sql_cab = "UPDATE FP_propuestas_pago SET estado = 'ACEPTADA', total_propuesto = ?, fecha_ultima_modificacion = GETDATE() WHERE id = ?";
                $stmt_cab = sqlsrv_query($conn_apps, $sql_cab, [$total_propuesto, $id]);
                if (!$stmt_cab)
                    throw new Exception("Error al actualizar cabecera.");

                // 2. Actualizar items
                sqlsrv_query($conn_apps, "DELETE FROM FP_propuestas_pago_items WHERE id_propuesta = ?", [$id]);
                foreach ($items as $item) {
                    $sql_item = "INSERT INTO FP_propuestas_pago_items (id_propuesta, t_comp_factura, n_comp_factura, importe_bruto, importe_neto, porcentaje_descuento) VALUES (?, ?, ?, ?, ?, ?)";
                    sqlsrv_query($conn_apps, $sql_item, [
                        $id,
                        $item['t_comp'],
                        $item['n_comp'],
                        $item['importe_bruto'],
                        $item['importe_neto'],
                        $item['porcentaje_descuento']
                    ]);
                }

                // 2.5. Actualizar Cuotas si se enviaron
                if (!empty($cuotas)) {
                    sqlsrv_query($conn_apps, "DELETE FROM FP_propuestas_pago_cuotas WHERE id_propuesta = ?", [$id]);
                    foreach ($cuotas as $c) {
                        $sql_cuota = "INSERT INTO FP_propuestas_pago_cuotas (id_propuesta, num_cuota, monto, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, 'PENDIENTE')";
                        sqlsrv_query($conn_apps, $sql_cuota, [$id, $c['num_cuota'], $c['monto'], $c['fecha_vencimiento']]);
                    }
                }

                // 3. Agregar historial
                $desc_hist = "Propuesta aceptada y actualizada por administración.";
                $sql_hist = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario) VALUES (?, ?, ?, ?, ?)";
                sqlsrv_query($conn_apps, $sql_hist, [$id, $_SESSION['usuario_id'], 'ADMIN', $desc_hist, $comentario]);

                sqlsrv_commit($conn_apps);

                // --- RESPUESTA INMEDIATA ---
                $response = json_encode(['success' => true, 'message' => 'Propuesta aceptada y actualizada correctamente.']);
                if (ob_get_level()) ob_end_clean();
                header('Connection: close');
                header('Content-Length: ' . strlen($response));
                header('Content-Type: application/json');
                echo $response;
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } else {
                    flush();
                    if (session_id()) session_write_close();
                    ignore_user_abort(true);
                }

                // --- NOTIFICACIÓN AL CLIENTE (SEGUNDO PLANO) ---
                try {
                        // require_once moved to top
                    // Obtenemos el cod_cliente de la propuesta para saber a quién notificar
                    $stmt_c = sqlsrv_query($conn_apps, "SELECT cod_cliente FROM FP_propuestas_pago WHERE id = ?", [$id]);
                    $p_row = sqlsrv_fetch_array($stmt_c, SQLSRV_FETCH_ASSOC);

                if ($p_row) {
                        $datos_cliente = obtenerEmailFranquiciado($p_row['cod_cliente']);
                        if ($datos_cliente && $datos_cliente['email']) {
                            $titulo = "Contrapropuesta Aceptada (ID: #{$id})";
                            $mensaje = "Hola <strong>{$datos_cliente['razon_social']}</strong>,<br><br>La administración ha <strong>ACEPTADO</strong> su contrapropuesta para la propuesta #{$id}.<br><br>Ya puede proceder con los pagos según el cronograma acordado.";
                            $cuerpo = generarCuerpoEmail($titulo, $mensaje, "Ver Detalles", "https://app.xl.com.ar/administracion/tesoreria/cobranzas/portal_cliente.php");
                            enviarNotificacion($datos_cliente['email'], $titulo, $cuerpo);
                        }
                    }
                } catch (Exception $e_mail) {
                    error_log("Error in notification: " . $e_mail->getMessage());
                }
            } catch (Exception $e) {
                sqlsrv_rollback($conn_apps);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'actualizar_estado':
            $id = $_POST['id_propuesta'] ?? 0;
            $estado = $_POST['nuevo_estado'] ?? '';
            $comentario = $_POST['comentario'] ?? '';

            if (!$id || !$estado) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos.']);
                exit;
            }

            $conn_apps = Database::getConnection('apps');

            // --- PROTECCIÓN GLOBAL CONTRA DUPLICADOS (Doble Click / Reintentos) ---
            $sql_check = "SELECT estado FROM FP_propuestas_pago WHERE id = ?";
            $stmt_check = sqlsrv_query($conn_apps, $sql_check, [$id]);
            if ($row_check = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC)) {
                $estado_actual = trim($row_check['estado']);
                
                // Si la propuesta ya está en el estado al que se quiere cambiar, devolvemos éxito directamente
                // Esto sucede usualmente si el servidor procesó el cambio pero la conexión se cortó o tardó mucho
                if ($estado_actual === $estado) {
                    echo json_encode(['success' => true, 'message' => 'La acción ya fue procesada anteriormente.']);
                    exit;
                }

                // Seguridad adicional: No permitir aceptar si ya está pagado o rechazada
                if (in_array($estado_actual, ['PAGADO', 'RECHAZADA', 'CANCELADA'])) {
                    echo json_encode(['success' => false, 'message' => "No se puede realizar esta acción porque la propuesta ya está en estado $estado_actual."]);
                    exit;
                }
            }

            sqlsrv_begin_transaction($conn_apps);

            try {
                // 1. Actualizar estado y fecha
                $sql_update = "UPDATE FP_propuestas_pago SET estado = ?, fecha_ultima_modificacion = GETDATE() WHERE id = ?";
                $params = [$estado, $id];

                // Si es contrapropuesta, actualizamos totales y fechas propuestas
                if ($estado === 'CONTRAPROPUESTA_CLIENTE' && isset($_POST['contrapropuesta'])) {
                    // Nota: los datos vienen como array en $_POST['contrapropuesta'] si usamos FormData correctamente
                    // Pero PHP a veces no parsea arrays anidados de FormData automáticamente si no tienen índices explícitos
                    // En JS se envió como contrapropuesta[nuevo_total], etc.
                    $cp = $_POST['contrapropuesta'] ?? [];

                    if (!empty($cp['nuevo_total'])) {
                        $sql_update = "UPDATE FP_propuestas_pago SET estado = ?, fecha_ultima_modificacion = GETDATE(), total_propuesto = ?, fecha_propuesta_pago = ?, medio_de_pago = ? WHERE id = ?";
                        $params = [
                            $estado,
                            $cp['nuevo_total'],
                            $cp['nueva_fecha'] ?? null,
                            $cp['nuevo_medio_pago'] ?? null,
                            $id
                        ];
                    }

                    // Manejo de cuotas si existen (para contrapropuestas del cliente)
                    if (!empty($cp['cuotas'])) {
                        $todas_cuotas = json_decode($cp['cuotas'], true);
                        if (is_array($todas_cuotas) && count($todas_cuotas) > 0) {
                            // Limpiamos cuotas anteriores
                            sqlsrv_query($conn_apps, "DELETE FROM FP_propuestas_pago_cuotas WHERE id_propuesta = ?", [$id]);

                            foreach ($todas_cuotas as $c) {
                                // Asegurar que fecha_vencimiento sea válida o NULL
                                $f_venc = !empty($c['fecha_vencimiento']) ? $c['fecha_vencimiento'] : null;

                                $sql_cuota = "INSERT INTO FP_propuestas_pago_cuotas (id_propuesta, num_cuota, monto, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, 'PENDIENTE')";
                                $stmt_cuota = sqlsrv_query($conn_apps, $sql_cuota, [$id, $c['num_cuota'], $c['monto'], $f_venc]);

                                if (!$stmt_cuota)
                                    throw new Exception("Error al guardar cuota {$c['num_cuota']}.");
                            }
                        }
                    }

                    // Manejo de ITEMS (Estrategia de Limpiar y Re-insertar para máxima precisión)
                    if (isset($cp['items']) && !empty($cp['items'])) {
                        $items_raw = $cp['items'];

                        // Diagnóstico de entrada
                        if (is_string($items_raw)) {
                            $items_nuevos = json_decode($items_raw, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                error_log("Error decodificando items JSON: " . json_last_error_msg());
                                $items_nuevos = null;
                            }
                        } else {
                            $items_nuevos = $items_raw;
                        }

                        if (is_array($items_nuevos) && count($items_nuevos) > 0) {
                            // 1. Limpiamos items anteriores de esta propuesta
                            $sql_del = "DELETE FROM FP_propuestas_pago_items WHERE id_propuesta = ?";
                            sqlsrv_query($conn_apps, $sql_del, [$id]);

                            // 2. Insertamos la nueva versión de los items con sus descuentos actualizados
                            $insertados = 0;
                            foreach ($items_nuevos as $it) {
                                $sql_item = "INSERT INTO FP_propuestas_pago_items (id_propuesta, t_comp_factura, n_comp_factura, importe_bruto, importe_neto, porcentaje_descuento) VALUES (?, ?, ?, ?, ?, ?)";

                                // Aseguramos tipos numéricos correctos
                                $imp_bruto = floatval($it['importe_bruto'] ?? 0);
                                $imp_neto = floatval($it['importe_neto'] ?? 0);
                                $p_desc = floatval($it['porcentaje_descuento'] ?? 0);

                                $params_it = [
                                    $id,
                                    trim($it['t_comp'] ?? ''),
                                    trim($it['n_comp'] ?? ''),
                                    $imp_bruto,
                                    $imp_neto,
                                    $p_desc
                                ];
                                $stmt_item = sqlsrv_query($conn_apps, $sql_item, $params_it);

                                if (!$stmt_item) {
                                    $errs = print_r(sqlsrv_errors(), true);
                                    error_log("Error fatal insertando ítem en propuesta #{$id}: " . $errs);
                                    throw new Exception("Error al procesar el listado de facturas. Por favor reintente.");
                                }
                                $insertados++;
                            }
                        } else {
                            error_log("Contrapropuesta #$id recibida sin un listado de items válido.");
                        }
                    } else {
                        error_log("No se detectaron items en el objeto contrapropuesta para ID #$id.");
                    }
                } elseif ($estado === 'ACEPTADA') {
                    // Por ahora solo cambiamos estado.
                }

                $stmt = sqlsrv_query($conn_apps, $sql_update, $params);
                if (!$stmt) {
                    $errs = print_r(sqlsrv_errors(), true);
                    error_log("Error actualizando cabecera de propuesta #$id: " . $errs);
                    throw new Exception("Error al actualizar la propuesta principal.");
                }

                // 2. Registrar historial
                $tipo_usuario = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') ? 'ADMIN' : 'CLIENTE';
                $id_usuario = $_SESSION['usuario_id'];

                $descripcion = "Cambio de estado a " . str_replace('_', ' ', $estado);
                if ($estado === 'ACEPTADA')
                    $descripcion = "Propuesta aceptada por el cliente.";
                if ($estado === 'CONTRAPROPUESTA_CLIENTE')
                    $descripcion = "Contrapropuesta enviada por el cliente.";

                $json_snapshot = null;
                if ($estado === 'CONTRAPROPUESTA_CLIENTE' && isset($cp)) {
                    // Para el snapshot usamos el mismo array de items que acabamos de procesar
                    $snapshot = [
                        'total' => $cp['nuevo_total'] ?? 0,
                        'fecha' => $cp['nueva_fecha'] ?? null,
                        'medio_pago' => $cp['nuevo_medio_pago'] ?? null,
                        'cuotas' => isset($cp['cuotas']) ? json_decode($cp['cuotas'], true) : null,
                        'items' => $items_nuevos ?? null
                    ];
                    $json_snapshot = json_encode($snapshot);
                }

                $sql_hist = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario, json_data) VALUES (?, ?, ?, ?, ?, ?)";
                sqlsrv_query($conn_apps, $sql_hist, [$id, $id_usuario, $tipo_usuario, $descripcion, $comentario, $json_snapshot]);

                sqlsrv_commit($conn_apps);

                // --- RESPUESTA INMEDIATA ---
                $response = json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
                if (ob_get_level()) ob_end_clean();
                header('Connection: close');
                header('Content-Length: ' . strlen($response));
                header('Content-Type: application/json');
                echo $response;
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } else {
                    flush();
                    if (session_id()) session_write_close();
                    ignore_user_abort(true);
                }

                // --- INICIO DE LA NOTIFICACIÓN AL ADMIN (CASO B) ---
                if ($estado === 'ACEPTADA' || $estado === 'CONTRAPROPUESTA_CLIENTE' || $estado === 'DOCUMENTACION_ADJUNTADA') {
                    try {
                            // require_once moved to top
                        $admin_mail = obtenerEmailAdmin();

                        // Obtenemos el nombre del cliente desde la propuesta para que el mail sea preciso
                        $stmt_info = sqlsrv_query($conn_apps, "SELECT cod_cliente FROM FP_propuestas_pago WHERE id = ?", [$id]);
                        $info_p = sqlsrv_fetch_array($stmt_info, SQLSRV_FETCH_ASSOC);
                        $cod_cli_real = $info_p ? $info_p['cod_cliente'] : ($_SESSION['usuario_cod_client_individual'] ?? null);

                        $datos_cliente = obtenerEmailFranquiciado($cod_cli_real);
                        $cliente_nom = $datos_cliente ? $datos_cliente['razon_social'] : ($_SESSION['usuario_nombre'] ?? 'Cliente');

                        $accion_txt = ($estado === 'ACEPTADA') ? 'ACEPTADO la propuesta' : (($estado === 'CONTRAPROPUESTA_CLIENTE') ? 'enviado una CONTRAPROPUESTA' : 'ADJUNTADO DOCUMENTACIÓN');
                        $titulo = "Acción de Cliente en Portal: Propuesta #{$id}";
                        $mensaje = "El cliente <strong>{$cliente_nom}</strong> ha <strong>{$accion_txt}</strong> para la propuesta #{$id}.<br><br>Por favor, ingrese al panel de administración para ver los detalles y procesar si corresponde.";
                        $cuerpo = generarCuerpoEmail($titulo, $mensaje, "Ver en Admin", "https://app.xl.com.ar/administracion/tesoreria/cobranzas/index.php");

                        enviarNotificacion($admin_mail, $titulo, $cuerpo);
                    } catch (Exception $e_mail) {
                        // No cortamos el flujo si falla el mail
                        error_log("Error enviando mail al admin: " . $e_mail->getMessage());
                    }
                }
                // --- FIN DE LA NOTIFICACIÓN ---
            } catch (Exception $e) {
                sqlsrv_rollback($conn_apps);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'subir_comprobante':
            $id_propuesta = $_POST['id_propuesta'] ?? 0;
            $id_cuota = $_POST['id_cuota'] ?? null;
            if (empty($id_cuota) || $id_cuota == 'undefined')
                $id_cuota = null;

            // Verificamos que el archivo venga con el nombre correcto 'comprobante'
            if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'No se recibió el archivo o hubo un error en la carga.']);
                exit;
            }

            $conn_apps = Database::getConnection('apps');
            $uploadDir = __DIR__ . '/../adjuntos/';
            if (!file_exists($uploadDir))
                mkdir($uploadDir, 0777, true);

            $originalName = $_FILES['comprobante']['name'];
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = 'comp_' . $id_propuesta . '_' . ($id_cuota ? 'cuota' . $id_cuota . '_' : 'pago_unico_') . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            $publicPath = 'adjuntos/' . $filename;

            if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $targetPath)) {

                // 1. Insertar el adjunto
                $sql_adj = "INSERT INTO FP_propuestas_adjuntos (id_propuesta, id_cuota, ruta_archivo, nombre_archivo, fecha_subida) VALUES (?, ?, ?, ?, GETDATE())";
                sqlsrv_query($conn_apps, $sql_adj, [$id_propuesta, $id_cuota, $publicPath, $originalName]);

                // 2. Cambiar estado a DOCUMENTACION_ADJUNTADA
                $sql_estado = "UPDATE FP_propuestas_pago SET estado = 'DOCUMENTACION_ADJUNTADA', fecha_ultima_modificacion = GETDATE() WHERE id = ?";
                sqlsrv_query($conn_apps, $sql_estado, [$id_propuesta]);

                // 3. Insertar en el Historial
                $desc_historial = "Cliente adjuntó comprobante: " . $originalName . ($id_cuota ? " (Cuota)" : " (Pago Único)");
                $sql_hist = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, fecha_evento) VALUES (?, ?, 'CLIENTE', ?, GETDATE())";
                sqlsrv_query($conn_apps, $sql_hist, [$id_propuesta, $_SESSION['usuario_id'], $desc_historial]);

                // --- RESPUESTA INMEDIATA (AHORA CORRECTAMENTE ANTES DE LA NOTIFICACIÓN) ---
                $response = json_encode(['success' => true, 'message' => 'Comprobante subido y propuesta actualizada.']);
                if (ob_get_level()) ob_end_clean();
                header('Connection: close');
                header('Content-Length: ' . strlen($response));
                header('Content-Type: application/json');
                echo $response;

                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } else {
                    flush();
                    if (session_id()) session_write_close();
                    ignore_user_abort(true);
                }

                // --- INICIO DE LA NOTIFICACIÓN AL ADMIN (SEGUNDO PLANO) ---
                try {
                    file_put_contents(__DIR__ . '/notificaciones.log', "[" . date('Y-m-d H:i:s') . "] Preparando mail para Admin para propuesta #$id_propuesta\n", FILE_APPEND);
                    // require_once moved to top
                    $admin_mail = obtenerEmailAdmin();

                    // Obtenemos el nombre del cliente desde la propuesta para que el mail sea preciso
                    $stmt_info = sqlsrv_query($conn_apps, "SELECT cod_cliente FROM FP_propuestas_pago WHERE id = ?", [$id_propuesta]);
                    $info_p = sqlsrv_fetch_array($stmt_info, SQLSRV_FETCH_ASSOC);
                    $cod_cli_real = $info_p ? $info_p['cod_cliente'] : ($_SESSION['usuario_cod_client_individual'] ?? null);

                    $datos_cliente = obtenerEmailFranquiciado($cod_cli_real);
                    $cliente_nom = $datos_cliente ? $datos_cliente['razon_social'] : ($_SESSION['usuario_nombre'] ?? 'Cliente');

                    $titulo = "Nueva Documentación Adjunta: Propuesta #{$id_propuesta}";
                    $mensaje = "El cliente <strong>{$cliente_nom}</strong> ha adjuntado un nuevo comprobante para la propuesta #{$id_propuesta}.<br><br><strong>Archivo:</strong> {$originalName}<br><br>Por favor, ingrese al panel de administración para verificar el documento.";
                    $cuerpo = generarCuerpoEmail($titulo, $mensaje, "Ver en Admin", "https://app.xl.com.ar/administracion/tesoreria/cobranzas/index.php");

                    enviarNotificacion($admin_mail, $titulo, $cuerpo);
                } catch (Exception $e_mail) {
                    error_log("Error enviando mail al admin (adjunto): " . $e_mail->getMessage());
                }
                // --- FIN DE LA NOTIFICACIÓN ---
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo físico en el servidor.']);
            }
            break;

        case 'eliminar_adjunto':
            $id_adjunto = $_POST['id_adjunto'] ?? 0;
            $conn_apps = Database::getConnection('apps');

            // Obtener ruta para borrar archivo físico e ID de propuesta
            $sql_get = "SELECT ruta_archivo, id_propuesta FROM FP_propuestas_adjuntos WHERE id = ?";
            $stmt_get = sqlsrv_query($conn_apps, $sql_get, [$id_adjunto]);
            $row = sqlsrv_fetch_array($stmt_get, SQLSRV_FETCH_ASSOC);

            if ($row) {
                $id_propuesta = $row['id_propuesta'];
                $ruta_fisica = __DIR__ . '/../' . $row['ruta_archivo'];

                if (file_exists($ruta_fisica))
                    unlink($ruta_fisica);

                sqlsrv_query($conn_apps, "DELETE FROM FP_propuestas_adjuntos WHERE id = ?", [$id_adjunto]);

                // --- Verificamos si quedan más adjuntos ---
                $sql_count = "SELECT COUNT(*) as total FROM FP_propuestas_adjuntos WHERE id_propuesta = ?";
                $stmt_count = sqlsrv_query($conn_apps, $sql_count, [$id_propuesta]);
                $count_row = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
                $quedan_adjuntos = $count_row['total'] > 0;

                // Si no quedan adjuntos y el estado era DOCUMENTACION_ADJUNTADA, volvemos a ACEPTADA
                if (!$quedan_adjuntos) {
                    $sql_revert = "UPDATE FP_propuestas_pago SET estado = 'ACEPTADA' WHERE id = ? AND estado = 'DOCUMENTACION_ADJUNTADA'";
                    sqlsrv_query($conn_apps, $sql_revert, [$id_propuesta]);
                }

                // Historial
                sqlsrv_query($conn_apps, "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, fecha_evento) VALUES (?, ?, 'CLIENTE', 'Se eliminó un comprobante adjunto.', GETDATE())", [$id_propuesta, $_SESSION['usuario_id']]);

                echo json_encode(['success' => true, 'message' => 'Adjunto eliminado correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Adjunto no encontrado.']);
            }
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>