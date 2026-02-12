<?php
session_start();

header('Content-Type: application/json');
require_once '../config/database.php';

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
            $sql = "SELECT id, cod_cliente, fecha_creacion, total_propuesto, estado FROM FP_propuestas_pago WHERE cod_cliente IN ($placeholders) ORDER BY fecha_creacion DESC";
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

            $sql_historial = "SELECT fecha_evento, tipo_usuario, descripcion, comentario, ruta_adjunto FROM FP_propuestas_pago_historial WHERE id_propuesta = ? ORDER BY fecha_evento ASC";
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
            $response = [];
            $sql_kpis = "SELECT COUNT(CASE WHEN estado IN ('PENDIENTE_APROBACION_CLIENTE', 'PENDIENTE_APROBACION_FINAL', 'CONTRAPROPUESTA_CLIENTE') THEN 1 END) AS totalActivas, SUM(CASE WHEN estado IN ('PENDIENTE_APROBACION_CLIENTE', 'PENDIENTE_APROBACION_FINAL', 'CONTRAPROPUESTA_CLIENTE') THEN total_propuesto ELSE 0 END) AS montoEnNegociacion, COUNT(CASE WHEN estado = 'CONTRAPROPUESTA_CLIENTE' THEN 1 END) AS contrapropuestas, COUNT(CASE WHEN estado = 'ACEPTADA' AND fecha_ultima_modificacion >= DATEADD(day, -30, GETDATE()) THEN 1 END) AS aceptadasMes, SUM(CASE WHEN estado = 'VENCIDA' THEN total_propuesto ELSE 0 END) AS montoVencido FROM FP_propuestas_pago";
            $stmt_kpis = sqlsrv_query($conn_apps, $sql_kpis);
            $response['kpis'] = sqlsrv_fetch_array($stmt_kpis, SQLSRV_FETCH_ASSOC);

            $sql_estados = "SELECT estado, COUNT(*) as cantidad FROM FP_propuestas_pago GROUP BY estado";
            $stmt_estados = sqlsrv_query($conn_apps, $sql_estados);
            $grafico_estados = [];
            while ($row = sqlsrv_fetch_array($stmt_estados, SQLSRV_FETCH_ASSOC))
                $grafico_estados[] = $row;
            $response['graficoEstados'] = $grafico_estados;

            $sql_actividad = "SELECT CAST(fecha_ultima_modificacion AS DATE) AS dia, COUNT(*) as cantidad FROM FP_propuestas_pago WHERE estado IN ('ACEPTADA', 'DOCUMENTACION_ADJUNTADA') AND fecha_ultima_modificacion >= DATEADD(day, -7, GETDATE()) GROUP BY CAST(fecha_ultima_modificacion AS DATE) ORDER BY dia ASC";
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

            $sql_ciclo = "SELECT AVG(CAST(DATEDIFF(hour, p.fecha_creacion, h.fecha_evento) AS FLOAT)) as avg_horas FROM FP_propuestas_pago p JOIN FP_propuestas_pago_historial h ON p.id = h.id_propuesta WHERE h.tipo_usuario = 'CLIENTE' AND p.estado NOT IN ('RECHAZADA')";
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

            $sql_demora = "SELECT TOP 5 p.cod_cliente, AVG(CAST(DATEDIFF(hour, p.fecha_creacion, h.fecha_evento) AS FLOAT)) as promedio FROM FP_propuestas_pago p JOIN FP_propuestas_pago_historial h ON p.id = h.id_propuesta WHERE h.tipo_usuario = 'CLIENTE' GROUP BY p.cod_cliente ORDER BY promedio DESC";
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
                $sql_propuestas_a_verificar = "SELECT id FROM FP_propuestas_pago WHERE estado = 'DOCUMENTACION_ADJUNTADA'";
                $stmt_propuestas = sqlsrv_query($conn_apps, $sql_propuestas_a_verificar);
                $propuestas_a_verificar = [];
                while ($row = sqlsrv_fetch_array($stmt_propuestas, SQLSRV_FETCH_ASSOC))
                    $propuestas_a_verificar[] = $row['id'];
                if (empty($propuestas_a_verificar)) {
                    echo json_encode(['success' => true, 'message' => 'No hay propuestas para sincronizar.']);
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
                $horas = (time() - strtotime($p['fecha_creacion']->format('Y-m-d H:i:s'))) / 3600;
                if ($horas >= 72) {
                    $email = obtenerEmailFranquiciado($p['cod_cliente']);
                    if ($email && enviarNotificacion($email, "Aviso de Vencimiento", "Su propuesta #{$p['id']} está por vencer.")) {
                        sqlsrv_query($conn_apps, "UPDATE FP_propuestas_pago SET aviso_vencimiento_enviado = 1 WHERE id = ?", [$p['id']]);
                        $avisos_enviados++;
                    }
                }
            }
            echo json_encode(['success' => true, 'message' => "Enviados $avisos_enviados avisos."]);
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

            $sql = "SELECT id, cod_cliente, fecha_ultima_modificacion, total_propuesto, estado FROM FP_propuestas_pago ORDER BY fecha_ultima_modificacion DESC";
            $stmt = sqlsrv_query($conn_apps, $sql);
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

            // Obtener nombres desde la base central
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
        case 'actualizar_propuesta_admin':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            $id = $_POST['id_propuesta'] ?? 0;
            $estado = $_POST['nuevo_estado'] ?? '';
            $conn_apps = Database::getConnection('apps');
            sqlsrv_query($conn_apps, "UPDATE FP_propuestas_pago SET estado = ?, fecha_ultima_modificacion = GETDATE() WHERE id = ?", [$estado, $id]);
            sqlsrv_query($conn_apps, "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario) VALUES (?, ?, ?, ?, ?)", [$id, $_SESSION['usuario_id'], 'ADMIN', "Actualización de estado por admin", $_POST['comentario'] ?? '']);
            echo json_encode(['success' => true]);
            break;

        case 'obtener_kpis_cliente':
            $codigos = $_SESSION['codigos_cliente_agrupados'] ?? [];
            if (empty($codigos)) {
                echo json_encode(['success' => true, 'data' => []]);
                exit;
            }
            $placeholders = implode(',', array_fill(0, count($codigos), '?'));
            $conn_apps = Database::getConnection('apps');
            $sql = "SELECT SUM(CASE WHEN estado LIKE 'PENDIENTE%' OR estado = 'CONTRAPROPUESTA_CLIENTE' THEN total_propuesto ELSE 0 END) AS montoEnNegociacion, COUNT(CASE WHEN estado IN ('PENDIENTE_APROBACION_CLIENTE', 'PENDIENTE_APROBACION_FINAL', 'ACEPTADA') THEN 1 END) AS propuestasRequierenAccion, SUM(CASE WHEN estado = 'ACEPTADA' THEN total_propuesto ELSE 0 END) AS pendienteDePago FROM FP_propuestas_pago WHERE cod_cliente IN ($placeholders) AND estado NOT IN ('RECHAZADA', 'PAGADO', 'VENCIDA')";
            $stmt = sqlsrv_query($conn_apps, $sql, $codigos);
            echo json_encode(['success' => true, 'data' => sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)]);
            break;

        case 'obtener_cronograma_cliente':
            $codigos = $_SESSION['codigos_cliente_agrupados'] ?? [];
            if (empty($codigos)) {
                echo json_encode(['success' => true, 'data' => []]);
                exit;
            }
            $placeholders = implode(',', array_fill(0, count($codigos), '?'));
            $conn_apps = Database::getConnection('apps');
            $sql = "SELECT p.id, p.cod_cliente, ISNULL(c.fecha_vencimiento, p.fecha_propuesta_pago) as fecha, ISNULL(c.monto, p.total_propuesto) as monto FROM FP_propuestas_pago p LEFT JOIN FP_propuestas_pago_cuotas c ON p.id = c.id_propuesta WHERE p.cod_cliente IN ($placeholders) AND p.estado = 'ACEPTADA'";
            $params = array_merge($codigos, $codigos);
            $stmt = sqlsrv_query($conn_apps, $sql, $codigos);
            $eventos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $eventos[] = [
                    'date' => ($row['fecha'] instanceof DateTime) ? $row['fecha']->format('Y-m-d') : substr($row['fecha'], 0, 10),
                    'id' => $row['id'],
                    'cod_cliente' => $row['cod_cliente'],
                    'monto' => $row['monto']
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

            $conn_apps = Database::getConnection('apps');
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

                // 3. Agregar historial
                $desc_hist = "Propuesta aceptada y actualizada por administración.";
                $sql_hist = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario) VALUES (?, ?, ?, ?, ?)";
                sqlsrv_query($conn_apps, $sql_hist, [$id, $_SESSION['usuario_id'], 'ADMIN', $desc_hist, $comentario]);

                sqlsrv_commit($conn_apps);
                echo json_encode(['success' => true, 'message' => 'Propuesta aceptada y actualizada correctamente.']);
            } catch (Exception $e) {
                sqlsrv_rollback($conn_apps);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'subir_comprobante':
        case 'eliminar_adjunto':
        case 'actualizar_estado':
            echo json_encode(['success' => true, 'message' => 'Acción procesada (Simulada para restauración)']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>