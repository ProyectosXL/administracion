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
            $sql_items = "SELECT n_comp_factura, importe_bruto, importe_neto, porcentaje_descuento FROM FP_propuestas_pago_items WHERE id_propuesta = ?";
            $stmt_items = sqlsrv_query($conn_apps, $sql_items, [$id_propuesta]);
            $items = [];
            while ($row = sqlsrv_fetch_array($stmt_items, SQLSRV_FETCH_ASSOC)) $items[] = $row;

            // CORRECCIÓN: Añadir prefijo FP_
            $sql_historial = "SELECT fecha_evento, tipo_usuario, descripcion, comentario FROM FP_propuestas_pago_historial WHERE id_propuesta = ? ORDER BY fecha_evento ASC";
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
                    COUNT(CASE WHEN estado <> 'ACEPTADA' THEN 1 END) AS totalActivas,
                    SUM(CASE WHEN estado <> 'ACEPTADA' THEN total_propuesto ELSE 0 END) AS montoEnNegociacion,
                    COUNT(CASE WHEN estado = 'CONTRAPROPUESTA_CLIENTE' THEN 1 END) AS contrapropuestas,
                    COUNT(CASE WHEN estado = 'ACEPTADA' AND fecha_ultima_modificacion >= DATEADD(day, -30, GETDATE()) THEN 1 END) AS aceptadasMes
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
        // ... (La búsqueda de propuestas a verificar se mantiene igual) ...
        $sql_propuestas_a_verificar = "SELECT id FROM FP_propuestas_pago WHERE estado = 'DOCUMENTACION_ADJUNTADA'";
        $stmt_propuestas = sqlsrv_query($conn_apps, $sql_propuestas_a_verificar);
        if ($stmt_propuestas === false) throw new Exception("Error al buscar propuestas.");
        
        $propuestas_a_verificar = [];
        while ($row = sqlsrv_fetch_array($stmt_propuestas, SQLSRV_FETCH_ASSOC)) {
            $propuestas_a_verificar[] = $row['id'];
        }

        if (empty($propuestas_a_verificar)) {
            echo json_encode(['success' => true, 'message' => 'No hay propuestas con documentación para sincronizar.']);
            exit;
        }

        // ... (La iteración sobre las propuestas se mantiene igual) ...
        foreach ($propuestas_a_verificar as $id_propuesta) {
            
            // ... (La obtención de items de la propuesta se mantiene igual) ...
            $sql_items = "SELECT t_comp_factura, n_comp_factura FROM FP_propuestas_pago_items WHERE id_propuesta = ?";
            $stmt_items = sqlsrv_query($conn_apps, $sql_items, [$id_propuesta]);
            if ($stmt_items === false) continue;

            $items_de_propuesta = [];
            $solo_notas_de_credito = true;
            while ($item = sqlsrv_fetch_array($stmt_items, SQLSRV_FETCH_ASSOC)) {
                $items_de_propuesta[] = $item;
                if (trim($item['t_comp_factura']) === 'FAC') {
                    $solo_notas_de_credito = false;
                }
            }
            
            if ($solo_notas_de_credito && !empty($items_de_propuesta)) {
                $sql_update_pagado = "UPDATE FP_propuestas_pago SET estado = 'PAGADO', fecha_ultima_modificacion = GETDATE() WHERE id = ?";
                sqlsrv_query($conn_apps, $sql_update_pagado, [$id_propuesta]);
                $propuestas_actualizadas++;
                continue;
            }

            $todas_facturas_canceladas = true;
            foreach ($items_de_propuesta as $item_a_verificar) {
                if (trim($item_a_verificar['t_comp_factura']) !== 'FAC') {
                    continue;
                }

                $t_comp = trim($item_a_verificar['t_comp_factura']);
                $n_comp = trim($item_a_verificar['n_comp_factura']);
                
                // ======================= CONSULTA CORREGIDA Y SIMPLIFICADA =======================
                // Asumimos que si un cliente es 'FR...' sus facturas estarán en la vista de franquicias.
                // Si pudiera tener en ambas, necesitaríamos saber el nombre de la columna de estado en la vista de mayoristas.
                $sql_estado = "SELECT ESTADO FROM RO_V_COBRANZA_PEND_FRANQUICIAS WHERE T_COMP = ? AND N_COMP = ?";
                // ===============================================================================

                $params_estado = [$t_comp, $n_comp];
                $stmt_estado = sqlsrv_query($conn_central, $sql_estado, $params_estado);
                
                if($stmt_estado === false) {
                    $todas_facturas_canceladas = false;
                    break;
                }
                
                $estado_row = sqlsrv_fetch_array($stmt_estado, SQLSRV_FETCH_ASSOC);

                if (!$estado_row || trim($estado_row['ESTADO']) !== 'CAN') {
                    $todas_facturas_canceladas = false;
                    break;
                }
            }

            if ($todas_facturas_canceladas) {
            $sql_update_pagado = "UPDATE FP_propuestas_pago SET estado = 'PAGADO', fecha_ultima_modificacion = GETDATE() WHERE id = ?";
            $stmt_update = sqlsrv_query($conn_apps, $sql_update_pagado, [$id_propuesta]);

            // ======================= INICIO DE LA NUEVA LÓGICA =======================
            // 5.2. Registrar el evento en el historial (si la actualización fue exitosa)
            if ($stmt_update !== false && sqlsrv_rows_affected($stmt_update) > 0) {
                $id_usuario_admin = $_SESSION['usuario_id']; // Obtenemos el ID del admin logueado
                $descripcion_historial = "El sistema ha verificado el pago y la propuesta se marcó como PAGADA.";
                $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion) VALUES (?, ?, ?, ?)";
                $params_historial = [$id_propuesta, $id_usuario_admin, 'SISTEMA', $descripcion_historial]; // Usamos 'SISTEMA' para indicar que fue automático
                sqlsrv_query($conn_apps, $sql_historial, $params_historial);
            }
            // ======================== FIN DE LA NUEVA LÓGICA =========================

            $propuestas_actualizadas++;
            }
        }

        echo json_encode(['success' => true, 'message' => "Sincronización finalizada. Se actualizaron $propuestas_actualizadas propuestas al estado PAGADO."]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    }
    exit;
    break;

        case 'listar_admin':
            if (!$es_admin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acción no permitida.']);
                exit;
            }
            
            $conn_apps = Database::getConnection('apps');
            // CORRECCIÓN: Añadir prefijo FP_
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
                $sql_nombres = "
                    SELECT COD_CLIENT, RAZON_SOCI FROM RO_V_COBRANZA_PEND_FRANQUICIAS WHERE COD_CLIENT IN ($placeholders)
                    UNION
                    SELECT COD_CLIENT, RAZON_SOCI FROM RO_V_COBRANZA_PEND_MAYORISTAS WHERE COD_CLIENT IN ($placeholders)
                ";
                $params_nombres = array_merge($codigos_cliente, $codigos_cliente);
                $stmt_nombres = sqlsrv_query($conn_central, $sql_nombres, $params_nombres);

                if ($stmt_nombres === false) {
                    throw new Exception("Error al consultar nombres: " . print_r(sqlsrv_errors(), true));
                }
                while ($row_nombre = sqlsrv_fetch_array($stmt_nombres, SQLSRV_FETCH_ASSOC)) {
                    $nombres_clientes[$row_nombre['COD_CLIENT']] = $row_nombre['RAZON_SOCI'];
                }
            }

            $resultado_final = [];
            foreach ($propuestas as $id => $propuesta) {
                $propuesta['razon_social'] = $nombres_clientes[$propuesta['cod_cliente']] ?? 'N/A';
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
            if ($es_admin) { // Esta acción es solo para clientes
                http_response_code(403);
                exit;
            }

            $cod_cliente = $_SESSION['usuario_cod_client'];
            $conn_apps = Database::getConnection('apps');
            $conn_central = Database::getConnection('central');
            $response = [];

            // 1. Calcular Monto en Negociación y Propuestas que requieren acción
            $sql_negociacion = "
                SELECT
                    SUM(CASE WHEN estado LIKE 'PENDIENTE%' OR estado = 'CONTRAPROPUESTA_CLIENTE' THEN total_propuesto ELSE 0 END) AS montoEnNegociacion,
                    COUNT(CASE WHEN estado = 'PENDIENTE_APROBACION_CLIENTE' OR estado = 'PENDIENTE_APROBACION_FINAL' THEN 1 END) AS propuestasRequierenAccion
                FROM FP_propuestas_pago
                WHERE cod_cliente = ? AND estado NOT IN ('ACEPTADA', 'RECHAZADA')
            ";
            $stmt_negociacion = sqlsrv_query($conn_apps, $sql_negociacion, [$cod_cliente]);
            $kpis_negociacion = sqlsrv_fetch_array($stmt_negociacion, SQLSRV_FETCH_ASSOC);
            $response['montoEnNegociacion'] = $kpis_negociacion['montoEnNegociacion'] ?? 0;
            $response['propuestasRequierenAccion'] = $kpis_negociacion['propuestasRequierenAccion'] ?? 0;


            // 2. Calcular Deuda Total Pendiente (Facturas que NO están en propuestas activas)
            // Primero, obtenemos la lista de facturas en propuestas activas
            $facturas_en_propuesta_activa = [];
            $sql_propuestas_activas = "
                SELECT items.n_comp_factura FROM FP_propuestas_pago_items items
                JOIN FP_propuestas_pago propuestas ON items.id_propuesta = propuestas.id
                WHERE propuestas.cod_cliente = ? AND propuestas.estado NOT IN ('ACEPTADA', 'RECHAZADA')";
            $stmt_prop_activas = sqlsrv_query($conn_apps, $sql_propuestas_activas, [$cod_cliente]);
            while ($row = sqlsrv_fetch_array($stmt_prop_activas, SQLSRV_FETCH_ASSOC)) {
                $facturas_en_propuesta_activa[] = $row['n_comp_factura'];
            }

            // Segundo, consultamos las vistas de cobranzas en 'central'
            // La consulta es la unión de franquicias y mayoristas para este cliente.
            $sql_deuda_total = "
                SELECT SUM(IMPORTE_NETO) as totalDeuda FROM RO_V_COBRANZA_PEND_FRANQUICIAS WHERE COD_CLIENT = ?
                UNION ALL
                SELECT SUM(IMPORTE_NETO) as totalDeuda FROM RO_V_COBRANZA_PEND_MAYORISTAS WHERE COD_CLIENT = ?
            ";

            // Si hay facturas en propuestas, las excluimos
            if (!empty($facturas_en_propuesta_activa)) {
                $placeholders = implode(',', array_fill(0, count($facturas_en_propuesta_activa), '?'));
                $sql_deuda_total = "
                    SELECT SUM(IMPORTE_NETO) as totalDeuda FROM (
                        SELECT IMPORTE_NETO, N_COMP FROM RO_V_COBRANZA_PEND_FRANQUICIAS WHERE COD_CLIENT = ?
                        UNION ALL
                        SELECT IMPORTE_NETO, N_COMP FROM RO_V_COBRANZA_PEND_MAYORISTAS WHERE COD_CLIENT = ?
                    ) as t
                    WHERE t.N_COMP NOT IN ($placeholders)
                ";
                $params_deuda = array_merge([$cod_cliente, $cod_cliente], $facturas_en_propuesta_activa);
            } else {
                 $params_deuda = [$cod_cliente, $cod_cliente];
            }
            
            // Sumamos los resultados de la unión
            $stmt_deuda = sqlsrv_query($conn_central, $sql_deuda_total, $params_deuda);
            $totalDeuda = 0;
            while($row_deuda = sqlsrv_fetch_array($stmt_deuda, SQLSRV_FETCH_ASSOC)){
                $totalDeuda += (float)$row_deuda['totalDeuda'];
            }
            $response['deudaTotalPendiente'] = $totalDeuda;

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

case 'actualizar_estado':
    $id_propuesta = $_POST['id_propuesta'] ?? 0;
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    $comentario = $_POST['comentario'] ?? null;
    $cod_cliente = $_SESSION['usuario_cod_client'];
    $id_usuario = $_SESSION['usuario_id'];

    $conn_apps = Database::getConnection('apps');
    sqlsrv_begin_transaction($conn_apps);

    try {
        // 1. Actualizamos el estado de la propuesta
        $sql_update = "UPDATE FP_propuestas_pago SET estado = ?, fecha_ultima_modificacion = GETDATE() WHERE id = ? AND cod_cliente = ?";
        $stmt_update = sqlsrv_query($conn_apps, $sql_update, [$nuevo_estado, $id_propuesta, $cod_cliente]);

        if ($stmt_update === false || sqlsrv_rows_affected($stmt_update) === 0) {
             sqlsrv_rollback($conn_apps);
             throw new Exception("No se pudo actualizar la propuesta o no tienes permiso.");
        }

        // 2. Insertamos en el historial
        $descripcion_historial = ($nuevo_estado === 'ACEPTADA') ? "El cliente aceptó la propuesta." : "El cliente ha generado una contrapropuesta.";
        $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario) VALUES (?, ?, ?, ?, ?)";
        $params_historial = [$id_propuesta, $id_usuario, 'CLIENTE', $descripcion_historial, $comentario];
        $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, $params_historial);
        
        if ($stmt_historial === false) {
             sqlsrv_rollback($conn_apps);
             throw new Exception("Error al registrar el historial.");
        }
        
        // NO SE NECESITA NADA MÁS AQUÍ.
        // La propuesta ya tiene su fecha y monto, que es lo que leerá el calendario.
        
        sqlsrv_commit($conn_apps);
        echo json_encode(['success' => true, 'message' => 'Propuesta actualizada correctamente.']);

    } catch (Exception $e) {
        sqlsrv_rollback($conn_apps);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }

    exit;
    break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>