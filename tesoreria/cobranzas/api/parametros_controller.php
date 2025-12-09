<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// ======================= CORRECCIÓN PRINCIPAL =======================
// 1. Ya no se usa getInstance().
// 2. Pedimos la conexión a la base de datos 'central' (LAKER_SA).
$conn = Database::getConnection('central');
// ====================================================================

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'read':
            $sql = "SELECT ID, COD_CLIENT, DESC_COMPRA, DESC_FLETE, DIAS_PP, DESC_PP FROM RO_T_PARAMETROS_DESC_CLIENTES ORDER BY COD_CLIENT";
            $stmt = sqlsrv_query($conn, $sql);
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }
            // Pequeña mejora: Envolvemos la data en un objeto JSON estándar como lo espera DataTables
            echo json_encode(['data' => $data]);
            break;

        case 'create':
            $sql = "INSERT INTO RO_T_PARAMETROS_DESC_CLIENTES (COD_CLIENT, DESC_COMPRA, DESC_FLETE, DIAS_PP, DESC_PP, FECHA_MOD) VALUES (?, ?, ?, ?, ?, GETDATE())";
            // CORRECCIÓN: Los nombres de los parámetros en el JS son diferentes (param-cod-client)
            $params = [
                $_POST['param-cod-client'], $_POST['param-desc-compra'], $_POST['param-desc-flete'], 
                $_POST['param-dias-pp'], $_POST['param-desc-pp']
            ];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));
            echo json_encode(['success' => true, 'message' => 'Parámetro creado correctamente.']);
            break;
            
        case 'update':
            $sql = "UPDATE RO_T_PARAMETROS_DESC_CLIENTES SET COD_CLIENT = ?, DESC_COMPRA = ?, DESC_FLETE = ?, DIAS_PP = ?, DESC_PP = ?, FECHA_MOD = GETDATE() WHERE ID = ?";
            // CORRECCIÓN: Los nombres de los parámetros en el JS son diferentes (param-cod-client, param-id)
            $params = [
                $_POST['param-cod-client'], $_POST['param-desc-compra'], $_POST['param-desc-flete'], 
                $_POST['param-dias-pp'], $_POST['param-desc-pp'], $_POST['param-id']
            ];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));
            echo json_encode(['success' => true, 'message' => 'Parámetro actualizado correctamente.']);
            break;
            
        case 'delete':
            $sql = "DELETE FROM RO_T_PARAMETROS_DESC_CLIENTES WHERE ID = ?";
            $params = [$_POST['id']];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));
            echo json_encode(['success' => true, 'message' => 'Parámetro eliminado correctamente.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>