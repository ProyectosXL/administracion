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
            
            echo json_encode(['success' => true, 'data' => ['propuesta' => $propuesta, 'items' => $items, 'historial' => $historial]]);
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

                        // 3. Datos para Gráfico de Actividad Reciente (Barras)
            // Contará las propuestas aceptadas en cada uno de los últimos 7 días.
            $sql_actividad = "
                SELECT 
                    CAST(fecha_ultima_modificacion AS DATE) AS dia,
                    COUNT(*) as cantidad
                FROM FP_propuestas_pago
                WHERE 
                    estado = 'ACEPTADA' AND
                    fecha_ultima_modificacion >= DATEADD(day, -7, GETDATE())
                GROUP BY CAST(fecha_ultima_modificacion AS DATE)
                ORDER BY dia ASC
            ";
            $stmt_actividad = sqlsrv_query($conn_apps, $sql_actividad);
            $grafico_actividad = [];
            while ($row = sqlsrv_fetch_array($stmt_actividad, SQLSRV_FETCH_ASSOC)) {
                $grafico_actividad[] = $row;
            }
            $response['graficoActividad'] = $grafico_actividad;


            echo json_encode(['success' => true, 'data' => $response]);
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

        case 'actualizar_estado':
            $id_propuesta = $_POST['id_propuesta'] ?? 0;
            $nuevo_estado = $_POST['nuevo_estado'] ?? '';
            $comentario = $_POST['comentario'] ?? null;
            $cod_cliente = $_SESSION['usuario_cod_client'];
            $id_usuario = $_SESSION['usuario_id'];

            $conn_apps = Database::getConnection('apps');
            sqlsrv_begin_transaction($conn_apps);

            // CORRECCIÓN: Añadir prefijo FP_
            $sql_update = "UPDATE FP_propuestas_pago SET estado = ?, fecha_ultima_modificacion = GETDATE() WHERE id = ? AND cod_cliente = ?";
            $stmt_update = sqlsrv_query($conn_apps, $sql_update, [$nuevo_estado, $id_propuesta, $cod_cliente]);

            if ($stmt_update === false || sqlsrv_rows_affected($stmt_update) === 0) {
                 sqlsrv_rollback($conn_apps);
                 throw new Exception("No se pudo actualizar la propuesta o no tienes permiso.");
            }

            $descripcion_historial = ($nuevo_estado === 'ACEPTADA') ? "El cliente aceptó la propuesta." : "El cliente ha generado una contrapropuesta.";
            // CORRECCIÓN: Añadir prefijo FP_
            $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion, comentario) VALUES (?, ?, ?, ?, ?)";
            $params_historial = [$id_propuesta, $id_usuario, 'CLIENTE', $descripcion_historial, $comentario];
            $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, $params_historial);
            
            if ($stmt_historial === false) {
                 sqlsrv_rollback($conn_apps);
                 throw new Exception("Error al registrar el historial.");
            }
            
            sqlsrv_commit($conn_apps);
            echo json_encode(['success' => true, 'message' => 'Propuesta actualizada correctamente.']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>