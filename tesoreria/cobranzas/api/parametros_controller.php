<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$db = Database::getInstance();
$conn = $db->conn;

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
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'create':
            $sql = "INSERT INTO RO_T_PARAMETROS_DESC_CLIENTES (COD_CLIENT, DESC_COMPRA, DESC_FLETE, DIAS_PP, DESC_PP, FECHA_MOD) VALUES (?, ?, ?, ?, ?, GETDATE())";
            $params = [$_POST['cod_client'], $_POST['desc_compra'], $_POST['desc_flete'], $_POST['dias_pp'], $_POST['desc_pp']];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));
            echo json_encode(['success' => true, 'message' => 'Parámetro creado correctamente.']);
            break;
            
        case 'update':
            $sql = "UPDATE RO_T_PARAMETROS_DESC_CLIENTES SET COD_CLIENT = ?, DESC_COMPRA = ?, DESC_FLETE = ?, DIAS_PP = ?, DESC_PP = ?, FECHA_MOD = GETDATE() WHERE ID = ?";
            $params = [$_POST['cod_client'], $_POST['desc_compra'], $_POST['desc_flete'], $_POST['dias_pp'], $_POST['desc_pp'], $_POST['id']];
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