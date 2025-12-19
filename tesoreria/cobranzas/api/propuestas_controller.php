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
            $conn_apps = Database::getConnection('apps');
            $cod_cliente = $_SESSION['usuario_cod_client'];
            // CORRECCIÓN: Añadir prefijo FP_
            $sql = "SELECT id, fecha_creacion, total_propuesto, estado FROM FP_propuestas_pago WHERE cod_cliente = ? ORDER BY fecha_creacion DESC";
            $stmt = sqlsrv_query($conn_apps, $sql, [$cod_cliente]);
            $propuestas = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $propuestas[] = $row;
            }
            echo json_encode(['data' => $propuestas]);
            break;

        case 'ver_detalle':
            $id_propuesta = $_GET['id'] ?? 0;
            $cod_cliente_sesion = $_SESSION['usuario_cod_client'] ?? null;
            $conn_apps = Database::getConnection('apps');

            // CORRECCIÓN: Añadir prefijo FP_
            $sql_propuesta = "SELECT * FROM FP_propuestas_pago WHERE id = ?";
            $params = [$id_propuesta];
            
            if (!$es_admin) {
                $sql_propuesta .= " AND cod_cliente = ?";
                $params[] = $cod_cliente_sesion;
            }

            $stmt_propuesta = sqlsrv_query($conn_apps, $sql_propuesta, $params);
            $propuesta = sqlsrv_fetch_array($stmt_propuesta, SQLSRV_FETCH_ASSOC);

            if (!$propuesta) {
                 http_response_code(404);
                 echo json_encode(['success' => false, 'message' => 'Propuesta no encontrada.']);
                 exit;
            }

            // CORRECCIÓN: Añadir prefijo FP_
            // CORRECCIÓN: Pedir las nuevas columnas
            $sql_items = "SELECT t_comp_factura, n_comp_factura, importe_bruto, importe_neto, porcentaje_descuento FROM FP_propuestas_pago_items WHERE id_propuesta = ?";
            $stmt_items = sqlsrv_query($conn_apps, $sql_items, [$id_propuesta]);
            $items = [];
            while ($row = sqlsrv_fetch_array($stmt_items, SQLSRV_FETCH_ASSOC)) $items[] = $row;

            // CORRECCIÓN: Añadir prefijo FP_
            $sql_historial = "SELECT fecha_evento, tipo_usuario, descripcion, comentario, ruta_adjunto FROM FP_propuestas_pago_historial WHERE id_propuesta = ? ORDER BY fecha_evento ASC";
            $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, [$id_propuesta]);
            $historial = [];
            while ($row = sqlsrv_fetch_array($stmt_historial, SQLSRV_FETCH_ASSOC)) {
                $historial[] = $row;
            }

                // ======================= INICIO DE LA NUEVA LÓGICA =======================
    // Buscamos si existen archivos adjuntos para esta propuesta
    $adjuntos = [];
    $sql_adjuntos = "SELECT id, nombre_archivo, ruta_archivo, fecha_subida FROM FP_propuestas_adjuntos WHERE id_propuesta = ? ORDER BY fecha_subida DESC";
    $stmt_adjuntos = sqlsrv_query($conn_apps, $sql_adjuntos, [$id_propuesta]);
    
    if ($stmt_adjuntos !== false) {
        while ($row_adjunto = sqlsrv_fetch_array($stmt_adjuntos, SQLSRV_FETCH_ASSOC)) {
            $adjuntos[] = $row_adjunto;
        }
    }
    // ======================== FIN DE LA NUEVA LÓGICA =========================

            
    // Añadimos los adjuntos a la respuesta JSON
    echo json_encode(['success' => true, 'data' => [
        'propuesta' => $propuesta, 
        'items' => $items, 
        'historial' => $historial,
        'adjuntos' => $adjuntos // <-- Nuevo dato en la respuesta
    ]]);
    break;

        
        // ======================= NUEVO ENDPOINT PARA DASHBOARD ADMIN =======================
        case 'obtener_dashboard_admin':
            if (!$es_admin) {
                http_response_code(403);
                exit;
            }

            $conn_apps = Database::getConnection('apps');
            $response = [];

            // 1. KPIs Principales
$sql_kpis = "
    SELECT
        -- 'Activas' son solo las que están en proceso de negociación real
        COUNT(CASE WHEN estado IN ('PENDIENTE_APROBACION_CLIENTE', 'PENDIENTE_APROBACION_FINAL', 'CONTRAPROPUESTA_CLIENTE') THEN 1 END) AS totalActivas,
        
        -- 'Monto en Negociación' es la suma de las 'Activas'
        SUM(CASE WHEN estado IN ('PENDIENTE_APROBACION_CLIENTE', 'PENDIENTE_APROBACION_FINAL', 'CONTRAPROPUESTA_CLIENTE') THEN total_propuesto ELSE 0 END) AS montoEnNegociacion,
        
        -- 'Requieren Acción' del admin son las contrapropuestas del cliente
        COUNT(CASE WHEN estado = 'CONTRAPROPUESTA_CLIENTE' THEN 1 END) AS contrapropuestas,

        -- 'Aceptadas Mes' se mantiene igual
        COUNT(CASE WHEN estado = 'ACEPTADA' AND fecha_ultima_modificacion >= DATEADD(day, -30, GETDATE()) THEN 1 END) AS aceptadasMes,

        -- ===== NUEVO KPI =====
        -- Suma total de las propuestas que han vencido
        SUM(CASE WHEN estado = 'VENCIDA' THEN total_propuesto ELSE 0 END) AS montoVencido

    FROM FP_propuestas_pago
";
$stmt_kpis = sqlsrv_query($conn_apps, $sql_kpis);
$response['kpis'] = sqlsrv_fetch_array($stmt_kpis, SQLSRV_FETCH_ASSOC);

            // 2. Datos para Gráfico de Estados (Dona)
            $sql_estados = "SELECT estado, COUNT(*) as cantidad FROM FP_propuestas_pago GROUP BY estado";
            $stmt_estados = sqlsrv_query($conn_apps, $sql_estados);
            $grafico_estados = [];
            while ($row = sqlsrv_fetch_array($stmt_estados, SQLSRV_FETCH_ASSOC)) {
                $grafico_estados[] = $row;
            }
            $response['graficoEstados'] = $grafico_estados;
    // ======================= INICIO DE LA MODIFICACIÓN =======================
    // 3. Datos para Gráfico de Actividad Reciente (Barras)
    // Contará las propuestas ACEPTADAS o con DOCUMENTACIÓN en los últimos 7 días.
    $sql_actividad = "
        SELECT 
            CAST(fecha_ultima_modificacion AS DATE) AS dia,
            COUNT(*) as cantidad
        FROM FP_propuestas_pago
        WHERE 
            -- Aquí está el cambio: usamos IN para incluir ambos estados
            estado IN ('ACEPTADA', 'DOCUMENTACION_ADJUNTADA') AND
            fecha_ultima_modificacion >= DATEADD(day, -7, GETDATE())
        GROUP BY CAST(fecha_ultima_modificacion AS DATE)
        ORDER BY dia ASC
    ";
    // ======================== FIN DE LA MODIFICACIÓN =========================

            $stmt_actividad = sqlsrv_query($conn_apps, $sql_actividad);
            $grafico_actividad = [];
            while ($row = sqlsrv_fetch_array($stmt_actividad, SQLSRV_FETCH_ASSOC)) {
                $grafico_actividad[] = $row;
            }
            $response['graficoActividad'] = $grafico_actividad;


            echo json_encode(['success' => true, 'data' => $response]);
            break;

                // ======================= NUEVO ENDPOINT PARA SINCRONIZACIÓN DE PAGOS =======================
case 'sincronizar_estados_pagados':
    if (!$es_admin) {
        http_response_code(403);
        exit;
    }

    $conn_apps = Database::getConnection('apps');
    $conn_central = Database::getConnection('central');
    $propuestas_actualizadas = 0;
    
    try {
        // 1. Buscamos todas las propuestas que tienen documentación adjunta y están esperando verificación
        $sql_propuestas_a_verificar = "SELECT id FROM FP_propuestas_pago WHERE estado = 'DOCUMENTACION_ADJUNTADA'";
        $stmt_propuestas = sqlsrv_query($conn_apps, $sql_propuestas_a_verificar);
        if ($stmt_propuestas === false) throw new Exception("Error al buscar propuestas para sincronizar.");
        
        $propuestas_a_verificar = [];
        while ($row = sqlsrv_fetch_array($stmt_propuestas, SQLSRV_FETCH_ASSOC)) {
            $propuestas_a_verificar[] = $row['id'];
        }

        // Si no hay nada que hacer, terminamos
        if (empty($propuestas_a_verificar)) {
            echo json_encode(['success' => true, 'message' => 'No hay propuestas con documentación para sincronizar.']);
            exit;
        }

        // 2. Iteramos sobre cada propuesta para verificar sus comprobantes
        foreach ($propuestas_a_verificar as $id_propuesta) {
            
            // Obtenemos todos los items (facturas y NCs) de la propuesta actual
            $sql_items = "SELECT t_comp_factura, n_comp_factura FROM FP_propuestas_pago_items WHERE id_propuesta = ?";
            $stmt_items = sqlsrv_query($conn_apps, $sql_items, [$id_propuesta]);
            if ($stmt_items === false) continue; // Si falla, pasamos a la siguiente propuesta

            $items_de_propuesta = [];
            $solo_facturas = true;
            while ($item = sqlsrv_fetch_array($stmt_items, SQLSRV_FETCH_ASSOC)) {
                $items_de_propuesta[] = $item;
                if (strpos(trim($item['t_comp_factura']), 'NC') !== false) {
                    $solo_facturas = false;
                }
            }
            
            // Si la propuesta no contiene ninguna factura (solo NCs, por ejemplo), no necesita verificación de pago.
            // La marcamos como pagada directamente.
            if ($solo_facturas === false && empty(array_filter($items_de_propuesta, function($i) { return strpos(trim($i['t_comp_factura']), 'FAC') !== false; }))) {
                $sql_update_pagado = "UPDATE FP_propuestas_pago SET estado = 'PAGADO', fecha_ultima_modificacion = GETDATE() WHERE id = ?";
                sqlsrv_query($conn_apps, $sql_update_pagado, [$id_propuesta]);
                $propuestas_actualizadas++;
                continue; // Pasamos a la siguiente propuesta
            }

            // 3. Verificamos el estado de CADA factura de la propuesta
            $todas_facturas_canceladas = true;
            foreach ($items_de_propuesta as $item_a_verificar) {
                // Solo nos interesan las facturas (FAC), ignoramos las NCs en esta verificación
                if (strpos(trim($item_a_verificar['t_comp_factura']), 'FAC') === false) {
                    continue;
                }

                $t_comp = trim($item_a_verificar['t_comp_factura']);
                $n_comp = trim($item_a_verificar['n_comp_factura']);
                
                // ======================= INICIO DE LA CORRECCIÓN LÓGICA =======================
                // Buscamos directamente en la tabla madre GVA12, que contiene todos los estados.
                $sql_estado = "SELECT ESTADO FROM GVA12 WHERE T_COMP = ? AND N_COMP = ?";
                // ======================== FIN DE LA CORRECCIÓN LÓGICA =========================

                $params_estado = [$t_comp, $n_comp];
                $stmt_estado = sqlsrv_query($conn_central, $sql_estado, $params_estado);
                
                if($stmt_estado === false) {
                    $todas_facturas_canceladas = false;
                    break; // Si hay un error en la consulta, rompemos el bucle
                }
                
                $estado_row = sqlsrv_fetch_array($stmt_estado, SQLSRV_FETCH_ASSOC);

                // Si no encontramos la factura o su estado NO es 'CAN' (Cancelada), rompemos el bucle.
                if (!$estado_row || trim($estado_row['ESTADO']) !== 'CAN') {
                    $todas_facturas_canceladas = false;
                    break;
                }
            }

            // 4. Si todas las facturas de la propuesta están canceladas, actualizamos la propuesta a PAGADO
            if ($todas_facturas_canceladas) {
                $sql_update_pagado = "UPDATE FP_propuestas_pago SET estado = 'PAGADO', fecha_ultima_modificacion = GETDATE() WHERE id = ?";
                $stmt_update = sqlsrv_query($conn_apps, $sql_update_pagado, [$id_propuesta]);

                if ($stmt_update !== false && sqlsrv_rows_affected($stmt_update) > 0) {
                    $id_usuario_admin = $_SESSION['usuario_id'];
                    $descripcion_historial = "El sistema ha verificado el pago y la propuesta se marcó como PAGADA.";
                    $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion) VALUES (?, ?, ?, ?)";
                    $params_historial = [$id_propuesta, $id_usuario_admin, 'SISTEMA', $descripcion_historial];
                    sqlsrv_query($conn_apps, $sql_historial, $params_historial);
                    
                    $propuestas_actualizadas++;
                }
            }
        }

        echo json_encode(['success' => true, 'message' => "Sincronización finalizada. Se actualizaron $propuestas_actualizadas propuestas al estado PAGADO."]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error en el servidor durante la sincronización: ' . $e->getMessage()]);
    }
    break;
case 'obtener_cronograma_admin':
    if (!$es_admin) {
        http_response_code(403);
        exit;
    }

    $conn_apps = Database::getConnection('apps');
    $conn_central = Database::getConnection('central');

    // ======================= INICIO DE LA MODIFICACIÓN =======================
    // 1. Añadimos 'medio_de_pago' a la consulta SQL
    $sql_propuestas = "SELECT id, fecha_propuesta_pago, total_propuesto, cod_cliente, medio_de_pago FROM FP_propuestas_pago WHERE estado = 'ACEPTADA' AND fecha_propuesta_pago IS NOT NULL";
    // ======================== FIN DE LA MODIFICACIÓN =========================
    
    $stmt_propuestas = sqlsrv_query($conn_apps, $sql_propuestas);
    if ($stmt_propuestas === false) {
        throw new Exception("Error al consultar propuestas aceptadas para el cronograma.");
    }

    $propuestas = [];
    $codigos_cliente = [];
    while ($row = sqlsrv_fetch_array($stmt_propuestas, SQLSRV_FETCH_ASSOC)) {
        $propuestas[] = $row;
        if (!in_array($row['cod_cliente'], $codigos_cliente)) {
            $codigos_cliente[] = $row['cod_cliente'];
        }
    }

    $mapa_nombres = [];
    if (!empty($codigos_cliente)) {
        $placeholders = implode(',', array_fill(0, count($codigos_cliente), '?'));
        $sql_nombres = "
            SELECT COD_CLIENT, RAZON_SOCI FROM RO_V_COBRANZA_PEND_FRANQUICIAS WHERE COD_CLIENT IN ($placeholders)
            UNION
            SELECT COD_CLIENT, RAZON_SOCI FROM RO_V_COBRANZA_PEND_MAYORISTAS WHERE COD_CLIENT IN ($placeholders)
        ";
        $params_nombres = array_merge($codigos_cliente, $codigos_cliente);
        $stmt_nombres = sqlsrv_query($conn_central, $sql_nombres, $params_nombres);
        if ($stmt_nombres !== false) {
            while ($row_nombre = sqlsrv_fetch_array($stmt_nombres, SQLSRV_FETCH_ASSOC)) {
                $mapa_nombres[$row_nombre['COD_CLIENT']] = $row_nombre['RAZON_SOCI'];
            }
        }
    }

    $eventos_fc = [];
    foreach ($propuestas as $propuesta) {
        $nombre_cliente = $mapa_nombres[$propuesta['cod_cliente']] ?? $propuesta['cod_cliente'];
        $fecha_pago = new DateTime($propuesta['fecha_propuesta_pago']);

        $eventos_fc[] = [
            'title' => '$' . number_format($propuesta['total_propuesto'], 2, ',', '.') . ' - ' . $nombre_cliente,
            'start' => $fecha_pago->format('Y-m-d'),
            'extendedProps' => [
                'id' => $propuesta['id'],
                'monto' => $propuesta['total_propuesto'],
                'cliente' => $nombre_cliente,
                // ======================= DATO NUEVO AÑADIDO =======================
                'medio_de_pago' => $propuesta['medio_de_pago']
            ]
        ];
    }

    echo json_encode(['success' => true, 'data' => array_values($eventos_fc)]);
    break;

case 'listar_admin':
    if (!$es_admin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acción no permitida.']);
        exit;
    }
    
    $conn_apps = Database::getConnection('apps');
    $sql_propuestas = "SELECT id, cod_cliente, fecha_ultima_modificacion, total_propuesto, estado FROM FP_propuestas_pago ORDER BY fecha_ultima_modificacion DESC";
    $stmt_propuestas = sqlsrv_query($conn_apps, $sql_propuestas);

    if ($stmt_propuestas === false) {
        throw new Exception("Error al consultar propuestas: " . print_r(sqlsrv_errors(), true));
    }

    $propuestas = [];
    $codigos_cliente = [];
    while ($row = sqlsrv_fetch_array($stmt_propuestas, SQLSRV_FETCH_ASSOC)) {
        $propuestas[$row['id']] = $row;
        if (!empty($row['cod_cliente'])) {
            $codigos_cliente[] = $row['cod_cliente'];
        }
    }

    $nombres_clientes = [];
    if (!empty($codigos_cliente)) {
        $conn_central = Database::getConnection('central');
        $placeholders = implode(',', array_fill(0, count($codigos_cliente), '?'));
        
        // ======================= INICIO DE LA MODIFICACIÓN =======================
        // Hemos comentado la parte de la consulta que une con la vista de Mayoristas.
        // Ahora solo buscará nombres en la tabla de Franquicias.
        $sql_nombres = "
            SELECT COD_CLIENT, RAZON_SOCI FROM RO_V_COBRANZA_PEND_FRANQUICIAS WHERE COD_CLIENT IN ($placeholders)
            -- UNION
            -- SELECT COD_CLIENT, RAZON_SOCI FROM RO_V_COBRANZA_PEND_MAYORISTAS WHERE COD_CLIENT IN ($placeholders)
        ";
        
        // Como ya no hay UNION, no necesitamos duplicar los parámetros
        $params_nombres = $codigos_cliente;
        // ======================== FIN DE LA MODIFICACIÓN =========================

        $stmt_nombres = sqlsrv_query($conn_central, $sql_nombres, $params_nombres);

        if ($stmt_nombres === false) {
            // El error que te daba antes ocurría aquí. Ahora no debería pasar.
            throw new Exception("Error al consultar nombres: " . print_r(sqlsrv_errors(), true));
        }
        while ($row_nombre = sqlsrv_fetch_array($stmt_nombres, SQLSRV_FETCH_ASSOC)) {
            $nombres_clientes[$row_nombre['COD_CLIENT']] = $row_nombre['RAZON_SOCI'];
        }
    }

    $resultado_final = [];
    foreach ($propuestas as $id => $propuesta) {
        $propuesta['razon_social'] = $nombres_clientes[$propuesta['cod_cliente']] ?? 'N/A (Revisar Cliente)';
        $resultado_final[] = $propuesta;
    }

    echo json_encode(['data' => $resultado_final]);
    break;

        case 'actualizar_estado_admin':
             if (!$es_admin) {
                http_response_code(403);
                exit;
            }
            $id_propuesta = $_POST['id_propuesta'] ?? 0;
            $nuevo_estado = $_POST['nuevo_estado'] ?? '';
            $comentario = $_POST['comentario'] ?? null;
            $id_usuario_admin = $_SESSION['usuario_id'];

            $conn_apps = Database::getConnection('apps');
            sqlsrv_begin_transaction($conn_apps);

            // CORRECCIÓN: Añadir prefijo FP_
            $sql_update = "UPDATE FP_propuestas_pago SET estado = ?, fecha_ultima_modificacion = GETDATE() WHERE id = ?";
            $stmt_update = sqlsrv_query($conn_apps, $sql_update, [$nuevo_estado, $id_propuesta]);

            if ($stmt_update === false || sqlsrv_rows_affected($stmt_update) === 0) {
                 sqlsrv_rollback($conn_apps);
                 throw new Exception("No se pudo actualizar la propuesta.");
            }

            $desc_historial = "El administrador ha enviado la propuesta final para su aceptación.";
            // CORRECCIÓN: Añadir prefijo FP_
            $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario) VALUES (?, ?, ?, ?, ?)";
            $params_historial = [$id_propuesta, $id_usuario_admin, 'ADMIN', $desc_historial, $comentario];
            $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, $params_historial);

            if ($stmt_historial === false) {
                 sqlsrv_rollback($conn_apps);
                 throw new Exception("Error al registrar el historial.");
            }

            sqlsrv_commit($conn_apps);
            echo json_encode(['success' => true, 'message' => 'Propuesta final enviada al cliente.']);
            break;

case 'actualizar_propuesta_admin':
    if (!$es_admin) {
        http_response_code(403);
        exit;
    }

    // Recibimos los datos del AJAX.
    $id_propuesta = $_POST['id_propuesta'] ?? 0;
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    $comentario = $_POST['comentario'] ?? null;
    $total_propuesto = $_POST['total_propuesto'] ?? 0;
    $fecha_propuesta_pago = $_POST['fecha_propuesta_pago'] ?? null;
    $comprobantes = $_POST['comprobantes'] ?? [];
    $id_usuario_admin = $_SESSION['usuario_id'];

    if ($id_propuesta == 0 || empty($nuevo_estado)) { // Ya no requerimos comprobantes, se podrían eliminar todos
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faltan datos para actualizar la propuesta.']);
        exit;
    }
    
    $conn_apps = Database::getConnection('apps');
    if (sqlsrv_begin_transaction($conn_apps) === false) {
        throw new Exception("Error al iniciar la transacción.");
    }

    try {
        // 1. Actualizar la cabecera de la propuesta
        $sql_update_header = "UPDATE FP_propuestas_pago SET estado = ?, total_propuesto = ?, fecha_ultima_modificacion = GETDATE(), fecha_propuesta_pago = ? WHERE id = ?";
        $params_header = [$nuevo_estado, $total_propuesto, $fecha_propuesta_pago, $id_propuesta]; 
        $stmt_header = sqlsrv_query($conn_apps, $sql_update_header, $params_header);
        if ($stmt_header === false) throw new Exception("Error al actualizar la cabecera de la propuesta.");

        // 2. Borramos TODOS los items anteriores de esta propuesta.
        $sql_delete_items = "DELETE FROM FP_propuestas_pago_items WHERE id_propuesta = ?";
        $stmt_delete = sqlsrv_query($conn_apps, $sql_delete_items, [$id_propuesta]);
        if ($stmt_delete === false) throw new Exception("Error al limpiar items antiguos.");

        // 3. Re-insertamos los items que quedaron (si los hay).
        if (!empty($comprobantes)) {
            $sql_insert_item = "INSERT INTO FP_propuestas_pago_items (id_propuesta, n_comp_factura, importe_bruto, importe_neto, porcentaje_descuento) VALUES (?, ?, ?, ?, ?)";
            foreach ($comprobantes as $comp) {
                $params_item = [
                    $id_propuesta,
                    $comp['n_comp'],
                    $comp['importe_bruto'],
                    $comp['importe_neto'],
                    $comp['porcentaje_descuento']
                ];
                $stmt_insert = sqlsrv_query($conn_apps, $sql_insert_item, $params_item);
                if ($stmt_insert === false) throw new Exception("Error al re-insertar el item " . $comp['n_comp']);
            }
        }
        
        // ======================= BLOQUE FALTANTE AÑADIDO =======================
        // 4. Registrar el evento en el historial
        $desc_historial = "El administrador ha enviado la propuesta final para su aceptación.";
        $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario) VALUES (?, ?, ?, ?, ?)";
        $params_historial = [$id_propuesta, $id_usuario_admin, 'ADMIN', $desc_historial, $comentario];
        $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, $params_historial);
        if ($stmt_historial === false) throw new Exception("Error al registrar el historial.");
        // ======================= FIN DEL BLOQUE AÑADIDO =======================

        // Si todo fue bien, confirmamos los cambios
        sqlsrv_commit($conn_apps);
        echo json_encode(['success' => true, 'message' => 'Propuesta final enviada al cliente.']);

    } catch (Exception $e) {
        // Si algo falló, revertimos todo
        sqlsrv_rollback($conn_apps);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la propuesta: ' . $e->getMessage()]);
    }
    exit; // Importante salir para no ejecutar más código
    break;

            // ======================= NUEVO ENDPOINT PARA KPIs DEL CLIENTE =======================
case 'obtener_kpis_cliente':
    if ($es_admin) { 
        http_response_code(403);
        exit;
    }

    // ======================= INICIO DE LA CORRECCIÓN =======================
    // Verificamos varios nombres comunes para la variable de sesión del código de cliente
    if (isset($_SESSION['cod_client'])) {
        $cod_cliente = $_SESSION['cod_client'];
    } elseif (isset($_SESSION['usuario_cod_client'])) {
        $cod_cliente = $_SESSION['usuario_cod_client'];
    } else {
        // Si no encontramos ninguna, devolvemos un error claro.
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se pudo identificar el código del cliente en la sesión.']);
        exit;
    }
    // ======================== FIN DE LA CORRECCIÓN =========================
    $conn_apps = Database::getConnection('apps');
    $conn_central = Database::getConnection('central');
    $response = [];

    // 1. Calcular Monto en Negociación y Propuestas que requieren acción (ESTO ESTÁ BIEN)
$sql_kpis_propuestas = "
    SELECT
        SUM(CASE WHEN estado LIKE 'PENDIENTE%' OR estado = 'CONTRAPROPUESTA_CLIENTE' THEN total_propuesto ELSE 0 END) AS montoEnNegociacion,
        
        -- ======================= INICIO DE LA CORRECCIÓN =======================
        -- Ahora contamos los 3 estados que requieren una acción del cliente
        COUNT(CASE WHEN estado IN ('PENDIENTE_APROBACION_CLIENTE', 'PENDIENTE_APROBACION_FINAL', 'ACEPTADA') THEN 1 END) AS propuestasRequierenAccion,
        -- ======================== FIN DE LA CORRECCIÓN =========================

        SUM(CASE WHEN estado = 'ACEPTADA' THEN total_propuesto ELSE 0 END) AS pendienteDePago
    FROM FP_propuestas_pago
    WHERE cod_cliente = ? AND estado NOT IN ('RECHAZADA', 'PAGADO')
";
    $stmt_kpis = sqlsrv_query($conn_apps, $sql_kpis_propuestas, [$cod_cliente]);
    $kpis_propuestas = sqlsrv_fetch_array($stmt_kpis, SQLSRV_FETCH_ASSOC);
    
    // Asignamos todos los valores a la respuesta
    $response['montoEnNegociacion'] = $kpis_propuestas['montoEnNegociacion'] ?? 0;
    $response['propuestasRequierenAccion'] = $kpis_propuestas['propuestasRequierenAccion'] ?? 0;
    // ===== NUEVA LÍNEA =====
    $response['pendienteDePago'] = $kpis_propuestas['pendienteDePago'] ?? 0;


// ======================= INICIO DE LA CORRECCIÓN LÓGICA =======================
// 2. Calcular Deuda Total Pendiente (Facturas que NO están en propuestas)

// Primero, obtenemos la lista de TODAS las facturas que están en CUALQUIER propuesta para este cliente
$facturas_en_propuesta = [];
$sql_propuestas_items = "
    SELECT items.n_comp_factura FROM FP_propuestas_pago_items items
    JOIN FP_propuestas_pago propuestas ON items.id_propuesta = propuestas.id
    WHERE propuestas.cod_cliente = ?";
$stmt_prop_items = sqlsrv_query($conn_apps, $sql_propuestas_items, [$cod_cliente]);
// Añadimos una verificación de error aquí también
if ($stmt_prop_items === false) {
    throw new Exception("Error al obtener facturas en propuestas: " . print_r(sqlsrv_errors(), true));
}
while ($row = sqlsrv_fetch_array($stmt_prop_items, SQLSRV_FETCH_ASSOC)) {
    // Usamos trim() para evitar problemas con espacios en blanco
    $facturas_en_propuesta[] = trim($row['n_comp_factura']);
}

// Segundo, consultamos la VISTA de cobranzas para obtener el total de deuda PENDIENTE
// que NO está en ninguna propuesta.
// Asumimos que la vista correcta depende del tipo de cliente.
// Si no tienes esta info, prueba con una de las vistas directamente.
$tipo_cliente = $_SESSION['tipo_cliente'] ?? 'mayoristas'; // Asume 'mayoristas' por defecto o el que sea más común
$vista_cobranzas = 'RO_V_COBRANZA_PEND_FRANQUICIAS';

$sql_deuda_total = "SELECT ISNULL(SUM(IMPORTE_NETO), 0) as totalDeuda FROM $vista_cobranzas WHERE COD_CLIENT = ?";
$params_deuda = [$cod_cliente];

// Si hay facturas en propuestas, las excluimos de la suma
if (!empty($facturas_en_propuesta)) {
    $placeholders = implode(',', array_fill(0, count($facturas_en_propuesta), '?'));
    $sql_deuda_total .= " AND N_COMP NOT IN ($placeholders)";
    // Unimos los parámetros del COD_CLIENT con la lista de facturas
    $params_deuda = array_merge($params_deuda, $facturas_en_propuesta);
}

$stmt_deuda = sqlsrv_query($conn_central, $sql_deuda_total, $params_deuda);

// === Verificación de error para evitar el Fatal Error ===
if ($stmt_deuda === false) {
    // En lugar de dejar que el programa se rompa, lanzamos una excepción controlada
    throw new Exception("Error en la consulta de deuda total pendiente: " . print_r(sqlsrv_errors(), true));
}
// ==========================================================

$row_deuda = sqlsrv_fetch_array($stmt_deuda, SQLSRV_FETCH_ASSOC);
// Aquí ya es seguro usar $row_deuda
$response['deudaTotalPendiente'] = $row_deuda['totalDeuda'] ?? 0;

// ======================== FIN DE LA CORRECCIÓN LÓGICA =========================

    echo json_encode(['success' => true, 'data' => $response]);
    exit;
    break;

                // ======================= NUEVO ENDPOINT PARA CRONOGRAMA DE PAGOS =======================
        case 'obtener_cronograma_cliente':
            if ($es_admin) {
                http_response_code(403);
                exit;
            }

            $cod_cliente = $_SESSION['usuario_cod_client'];
            $conn_apps = Database::getConnection('apps');

            $sql = "
                SELECT 
                    id,
                    fecha_propuesta_pago,
                    total_propuesto
                FROM 
                    FP_propuestas_pago
                WHERE 
                    cod_cliente = ? 
                    AND estado = 'ACEPTADA'
                    AND fecha_propuesta_pago IS NOT NULL
                ORDER BY
                    fecha_propuesta_pago ASC
            ";
            
            $stmt = sqlsrv_query($conn_apps, $sql, [$cod_cliente]);
            if ($stmt === false) {
                throw new Exception("Error al consultar el cronograma.");
            }

            $eventos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $eventos[] = [
                    'date' => $row['fecha_propuesta_pago'],
                    'id' => $row['id'],
                    'monto' => $row['total_propuesto']
                ];
            }

            echo json_encode(['success' => true, 'data' => $eventos]);
            exit;
            break;
        // ======================= NUEVO ENDPOINT PARA SUBIR COMPROBANTE =======================
        case 'subir_comprobante':
            if ($es_admin) {
                http_response_code(403);
                exit;
            }

            $id_propuesta = $_POST['id_propuesta'] ?? 0;
            $cod_cliente = $_SESSION['usuario_cod_client'];

                // ====================== INICIO DE LA VERIFICACIÓN DE SEGURIDAD ======================
    $conn_apps_check = Database::getConnection('apps');
    $sql_check = "SELECT estado FROM FP_propuestas_pago WHERE id = ? AND cod_cliente = ?";
    $stmt_check = sqlsrv_query($conn_apps_check, $sql_check, [$id_propuesta, $cod_cliente]);
    $propuesta = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

    // Si la propuesta no existe, no es del cliente o NO está en estado ACEPTADA, denegar.
    if (!$propuesta || $propuesta['estado'] !== 'ACEPTADA') {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Acción no permitida. La propuesta no está en estado ACEPTADA o ya ha vencido.']);
        exit;
    }
    // ======================= FIN DE LA VERIFICACIÓN DE SEGURIDAD ========================

            if ($id_propuesta == 0 || !isset($_FILES['comprobanteFile'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Faltan datos o el archivo no fue enviado.']);
                exit;
            }

            $file = $_FILES['comprobanteFile'];

            // Validaciones básicas del archivo
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Error al subir el archivo. Código: ' . $file['error']]);
                exit;
            }
            $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!in_array($file['type'], $allowed_types)) {
                 echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido.']);
                 exit;
            }

            // Creamos un nombre de archivo único
            $path_parts = pathinfo($file['name']);
            $extension = $path_parts['extension'];
            $new_filename = "propuesta_" . $id_propuesta . "_" . time() . "." . $extension;
    // ======================= INICIO DE LA CORRECCIÓN DE RUTA =======================
    // Antes, subía dos niveles (../../), ahora solo sube uno (../).
    // Desde 'cobranzas/api/' sube a 'cobranzas/' y luego entra a 'uploads/comprobantes/'.
    $upload_dir = __DIR__ . '/../uploads/comprobantes/';
    
    // La ruta guardada en la BD también debe ser relativa a la raíz del proyecto de cobranzas.
    $relative_path = 'uploads/comprobantes/' . $new_filename;
    // ======================== FIN DE LA CORRECCIÓN DE RUTA =========================
            $upload_path = $upload_dir . $new_filename;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                echo json_encode(['success' => false, 'message' => 'No se pudo mover el archivo subido.']);
                exit;
            }

            // Si todo fue bien, guardamos en la BD y actualizamos el estado
            $conn_apps = Database::getConnection('apps');
            if (sqlsrv_begin_transaction($conn_apps) === false) {
                 throw new Exception("Error al iniciar la transacción.");
            }

    try {
        // 1. Insertar en la tabla de adjuntos
        $sql_adjunto = "INSERT INTO FP_propuestas_adjuntos (id_propuesta, nombre_archivo, ruta_archivo) VALUES (?, ?, ?)";
        $params_adjunto = [$id_propuesta, $file['name'], $relative_path]; // Usamos la nueva ruta relativa
        $stmt_adjunto = sqlsrv_query($conn_apps, $sql_adjunto, $params_adjunto);
        if ($stmt_adjunto === false) throw new Exception("Error al guardar el adjunto en la BD.");

        // 2. Actualizar estado de la propuesta
        $sql_update = "UPDATE FP_propuestas_pago SET estado = 'DOCUMENTACION_ADJUNTADA', fecha_ultima_modificacion = GETDATE() WHERE id = ? AND cod_cliente = ?"; // Añadimos fecha_ultima_modificacion
        $stmt_update = sqlsrv_query($conn_apps, $sql_update, [$id_propuesta, $cod_cliente]);
        if ($stmt_update === false) throw new Exception("Error al actualizar el estado de la propuesta.");

        // ======================= INICIO DE LA NUEVA LÓGICA =======================
        // 3. Registrar el evento en el historial
        $id_usuario_cliente = $_SESSION['usuario_id']; // Obtenemos el ID del cliente logueado
        $descripcion_historial = "El cliente ha adjuntado el comprobante de pago.";
        $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion) VALUES (?, ?, ?, ?)";
        $params_historial = [$id_propuesta, $id_usuario_cliente, 'CLIENTE', $descripcion_historial];
        $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, $params_historial);
        if ($stmt_historial === false) throw new Exception("Error al registrar el historial de la subida.");
        // ======================== FIN DE LA NUEVA LÓGICA =========================

        sqlsrv_commit($conn_apps);
        echo json_encode(['success' => true, 'message' => 'Comprobante subido y propuesta actualizada.']);

            } catch (Exception $e) {
                sqlsrv_rollback($conn_apps);
                // Si falla la BD, borramos el archivo que ya subimos
                if (file_exists($upload_path)) {
                    unlink($upload_path);
                }
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
            }
            exit;
            break;


        // ======================= NUEVO ENDPOINT PARA ELIMINAR PROPUESTA =======================
        case 'eliminar_propuesta':
            if (!$es_admin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acción no permitida.']);
                exit;
            }

            $id_propuesta = $_POST['id_propuesta'] ?? 0;

            if ($id_propuesta == 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de propuesta no válido.']);
                exit;
            }

            $conn_apps = Database::getConnection('apps');

            try {
                // Gracias a ON DELETE CASCADE, al borrar la propuesta principal,
                // se borrarán automáticamente los registros en:
                // - FP_propuestas_pago_items
                // - FP_propuestas_pago_historial
                // - FP_propuestas_adjuntos
                // - FP_cuotas_propuesta (si la hubiéramos usado)
                
                $sql_delete = "DELETE FROM FP_propuestas_pago WHERE id = ?";
                $stmt_delete = sqlsrv_query($conn_apps, $sql_delete, [$id_propuesta]);

                if ($stmt_delete === false) {
                    throw new Exception("Error en la consulta de eliminación.");
                }

                if (sqlsrv_rows_affected($stmt_delete) > 0) {
                    echo json_encode(['success' => true, 'message' => 'Propuesta #' . $id_propuesta . ' eliminada correctamente.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se encontró la propuesta a eliminar o ya fue borrada.']);
                }

            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
            }
            exit;
            break;


        // ======================= NUEVO ENDPOINT: ADMIN ACEPTA CONTRAPROPUESTA =======================
case 'aceptar_contrapropuesta_admin':
    // 1. Verificación de seguridad: solo los administradores pueden ejecutar esta acción.
    if (!$es_admin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acción no permitida.']);
        exit;
    }

    // 2. Recolección de datos del formulario POST
    $id_propuesta = $_POST['id_propuesta'] ?? 0;
    $comentario = $_POST['comentario'] ?? null;
    $id_usuario_admin = $_SESSION['usuario_id'];
    $ruta_adjunto_db = null;

    // --- Nuevos datos recibidos desde el modal del admin ---
    $nuevo_total_propuesto = $_POST['total_propuesto'] ?? 0;
    $comprobantes_json = $_POST['comprobantes'] ?? '[]';
    $comprobantes = json_decode($comprobantes_json, true);

    // Verificación de datos mínimos requeridos
    if ($id_propuesta == 0 || (json_last_error() !== JSON_ERROR_NONE)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faltan datos o el formato de los comprobantes es incorrecto.']);
        exit;
    }
    
    // 3. Procesamiento del archivo subido (si existe)
    if (isset($_FILES['historial_adjunto']) && $_FILES['historial_adjunto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['historial_adjunto'];
        
        // --- Validaciones de seguridad para el archivo ---
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // Límite de 5 MB

        if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Archivo no válido o demasiado grande (máx. 5MB, solo JPG, PNG, GIF).']);
            exit;
        }

        // --- Mover archivo a su carpeta de destino ---
        $upload_dir = __DIR__ . '/../uploads/historial/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = "historial_{$id_propuesta}_admin_" . time() . "." . $extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $ruta_adjunto_db = 'uploads/historial/' . $new_filename;
        } else {
            throw new Exception("Error crítico: No se pudo mover el archivo adjunto al servidor.");
        }
    }

    // 4. Operaciones de Base de Datos dentro de una transacción
    $conn_apps = Database::getConnection('apps');
    if (sqlsrv_begin_transaction($conn_apps) === false) {
        throw new Exception("Error al iniciar la transacción de base de datos.");
    }

    try {
        // 4.1. Actualizamos la cabecera de la propuesta con el nuevo estado y el nuevo total
        $sql_update_header = "UPDATE FP_propuestas_pago SET estado = 'ACEPTADA', total_propuesto = ?, fecha_ultima_modificacion = GETDATE() WHERE id = ?";
        $params_header = [$nuevo_total_propuesto, $id_propuesta];
        $stmt_update_header = sqlsrv_query($conn_apps, $sql_update_header, $params_header);
        
        if ($stmt_update_header === false || sqlsrv_rows_affected($stmt_update_header) === 0) {
            throw new Exception("No se pudo actualizar la cabecera de la propuesta.");
        }

        // 4.2. Borramos TODOS los items antiguos de la propuesta para reemplazarlos
        $sql_delete_items = "DELETE FROM FP_propuestas_pago_items WHERE id_propuesta = ?";
        $stmt_delete = sqlsrv_query($conn_apps, $sql_delete_items, [$id_propuesta]);
        
        if ($stmt_delete === false) {
            throw new Exception("Error al limpiar los items antiguos de la propuesta.");
        }

        // 4.3. Re-insertamos los items actualizados que quedaron en el modal
        // Si no quedan comprobantes, este bucle no se ejecuta y la propuesta queda vacía, lo cual es correcto.
        if (!empty($comprobantes)) {
            $sql_insert_item = "INSERT INTO FP_propuestas_pago_items (id_propuesta, t_comp_factura, n_comp_factura, importe_bruto, importe_neto, porcentaje_descuento) VALUES (?, ?, ?, ?, ?, ?)";
            foreach ($comprobantes as $comp) {
                $params_item = [
                    $id_propuesta, 
                    $comp['t_comp'], 
                    $comp['n_comp'], 
                    $comp['importe_bruto'], 
                    $comp['importe_neto'], 
                    $comp['porcentaje_descuento']
                ];
                $stmt_item = sqlsrv_query($conn_apps, $sql_insert_item, $params_item);
                if ($stmt_item === false) {
                    // Si falla la inserción de un item, lanzamos un error con detalles
                    throw new Exception("Error al re-insertar el item: " . $comp['n_comp']);
                }
            }
        }

        // 4.4. Registramos el evento en el historial
        $descripcion_historial = "El administrador aceptó la contrapropuesta y guardó los cambios finales.";
        $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario, ruta_adjunto) VALUES (?, ?, ?, ?, ?, ?)";
        $params_historial = [$id_propuesta, $id_usuario_admin, 'ADMIN', $descripcion_historial, $comentario, $ruta_adjunto_db];
        $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, $params_historial);

        if ($stmt_historial === false) {
            throw new Exception("Error al registrar el evento en el historial.");
        }

        // 5. Si todas las operaciones fueron exitosas, confirmamos la transacción
        sqlsrv_commit($conn_apps);
        echo json_encode(['success' => true, 'message' => 'Contrapropuesta aceptada y cambios guardados correctamente.']);

    } catch (Exception $e) {
        // 6. Si algo falló en cualquier punto, revertimos todos los cambios
        sqlsrv_rollback($conn_apps);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
    break;

case 'actualizar_estado':
    $id_propuesta = $_POST['id_propuesta'] ?? 0;
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    $comentario = $_POST['comentario'] ?? null;
    $cod_cliente = $_SESSION['usuario_cod_client'];
    $id_usuario = $_SESSION['usuario_id'];
    $ruta_adjunto_db = null; // Inicializamos la ruta del archivo como null

    // ================== INICIO DEL MANEJO DE ARCHIVO ADJUNTO ==================
    if (isset($_FILES['historial_adjunto']) && $_FILES['historial_adjunto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['historial_adjunto'];
        
        // --- Validaciones de seguridad ---
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file['type'], $allowed_types)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se aceptan JPG, PNG o GIF.']);
            exit;
        }

        if ($file['size'] > $max_size) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. El tamaño máximo es de 5 MB.']);
            exit;
        }
        // --- Fin de validaciones ---

        // Creamos un nombre de archivo único para evitar colisiones
        $upload_dir = __DIR__ . '/../uploads/historial/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = "historial_{$id_propuesta}_" . time() . "." . $extension;
        $upload_path = $upload_dir . $new_filename;

        // Movemos el archivo a su destino final
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Guardamos la ruta relativa para la base de datos
            $ruta_adjunto_db = 'uploads/historial/' . $new_filename;
        } else {
            // Si falla el movimiento del archivo, detenemos el proceso
            throw new Exception("Error crítico: No se pudo mover el archivo adjunto.");
        }
    }
    // =================== FIN DEL MANEJO DE ARCHIVO ADJUNTO ====================

    $conn_apps = Database::getConnection('apps');
    sqlsrv_begin_transaction($conn_apps);

    try {
        $sql_get_propuesta = "SELECT total_propuesto, fecha_propuesta_pago, medio_de_pago FROM FP_propuestas_pago WHERE id = ?";
        $stmt_get = sqlsrv_query($conn_apps, $sql_get_propuesta, [$id_propuesta]);
        $propuesta_actual = sqlsrv_fetch_array($stmt_get, SQLSRV_FETCH_ASSOC);

        $descripcion_historial = "";
        $update_sql = "UPDATE FP_propuestas_pago SET estado = ?, fecha_ultima_modificacion = GETDATE() WHERE id = ? AND cod_cliente = ?";
        $update_params = [$nuevo_estado, $id_propuesta, $cod_cliente];

        if ($nuevo_estado === 'CONTRAPROPUESTA_CLIENTE' && isset($_POST['contrapropuesta'])) {
            $contra = $_POST['contrapropuesta'];
            $nuevo_total = $contra['nuevo_total'] ?? $propuesta_actual['total_propuesto'];
            $nueva_fecha = $contra['nueva_fecha'] ?? $propuesta_actual['fecha_propuesta_pago'];
            $nuevo_medio_pago = $contra['nuevo_medio_pago'] ?? $propuesta_actual['medio_de_pago'];
            
            // Recalculamos el descuento basado en los nuevos datos
            $diff_dias = (strtotime($nueva_fecha) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
            $nuevo_porcentaje = 0;
            $reglas_descuento = [
                'ECHECK' => [15 => 8, 22 => 6, 29 => 4],
                'TRANSFERENCIA' => [15 => 6, 22 => 4, 29 => 2]
            ];

            if (isset($reglas_descuento[$nuevo_medio_pago])) {
                if ($diff_dias <= 15) $nuevo_porcentaje = $reglas_descuento[$nuevo_medio_pago][15];
                elseif ($diff_dias <= 22) $nuevo_porcentaje = $reglas_descuento[$nuevo_medio_pago][22];
                elseif ($diff_dias <= 29) $nuevo_porcentaje = $reglas_descuento[$nuevo_medio_pago][29];
            }

            // Actualizamos cada item de la propuesta
            $sql_items_con_descuento = "SELECT n_comp_factura, importe_bruto FROM FP_propuestas_pago_items WHERE id_propuesta = ? AND porcentaje_descuento > 0";
            $stmt_items_desc = sqlsrv_query($conn_apps, $sql_items_con_descuento, [$id_propuesta]);
            if ($stmt_items_desc === false) throw new Exception("Error al obtener items con descuento.");

            $sql_update_item = "UPDATE FP_propuestas_pago_items SET importe_neto = ?, porcentaje_descuento = ? WHERE id_propuesta = ? AND n_comp_factura = ?";
            
            while ($item = sqlsrv_fetch_array($stmt_items_desc, SQLSRV_FETCH_ASSOC)) {
                $nuevo_neto = $item['importe_bruto'] * (1 - ($nuevo_porcentaje / 100));
                $params_update = [$nuevo_neto, $nuevo_porcentaje, $id_propuesta, $item['n_comp_factura']];
                $stmt_update_item = sqlsrv_query($conn_apps, $sql_update_item, $params_update);
                if ($stmt_update_item === false) throw new Exception("Error al actualizar el item: " . $item['n_comp_factura']);
            }
            
            // Construimos la descripción para el historial
            $descripcion_historial = "El cliente ha generado una contrapropuesta.";
            $cambios = [];
            if (number_format($nuevo_total, 2) != number_format($propuesta_actual['total_propuesto'], 2)) $cambios[] = "nuevo monto de $" . number_format($nuevo_total, 2);
            if ($nueva_fecha != $propuesta_actual['fecha_propuesta_pago']) $cambios[] = "nueva fecha para el " . date("d/m/Y", strtotime($nueva_fecha));
            if ($nuevo_medio_pago != $propuesta_actual['medio_de_pago']) $cambios[] = "cambio de medio de pago a " . $nuevo_medio_pago;
            if (isset($nuevo_porcentaje)) $cambios[] = "nuevo descuento del " . $nuevo_porcentaje . "%";

            if(!empty($cambios)) {
                $descripcion_historial .= " Cambios solicitados: " . implode(', ', $cambios) . ".";
            }

            // Actualizamos la cabecera de la propuesta
            $update_sql = "UPDATE FP_propuestas_pago SET estado = ?, total_propuesto = ?, fecha_propuesta_pago = ?, medio_de_pago = ?, fecha_ultima_modificacion = GETDATE() WHERE id = ? AND cod_cliente = ?";
            $update_params = [$nuevo_estado, $nuevo_total, $nueva_fecha, $nuevo_medio_pago, $id_propuesta, $cod_cliente];
        
        } else if ($nuevo_estado === 'ACEPTADA') {
            $descripcion_historial = "El cliente aceptó la propuesta.";
        }
        
        // 1. Ejecutamos la actualización de la propuesta
        $stmt_update = sqlsrv_query($conn_apps, $update_sql, $update_params);
        if ($stmt_update === false || sqlsrv_rows_affected($stmt_update) === 0) {
             throw new Exception("No se pudo actualizar la propuesta o no tienes permiso.");
        }

        // 2. Insertamos el evento en el historial (con la columna de adjunto)
        $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario, ruta_adjunto) VALUES (?, ?, ?, ?, ?, ?)";
        $params_historial = [$id_propuesta, $id_usuario, 'CLIENTE', $descripcion_historial, $comentario, $ruta_adjunto_db];
        $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, $params_historial);
        if ($stmt_historial === false) {
             throw new Exception("Error al registrar el historial.");
        }
        
        sqlsrv_commit($conn_apps);
        echo json_encode(['success' => true, 'message' => 'Propuesta actualizada correctamente.']);

    } catch (Exception $e) {
        sqlsrv_rollback($conn_apps);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
    break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>