<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$conn = Database::getConnection('central');
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'read':
            $sql = "SELECT ID, COD_CLIENT, MEDIO_PAGO_DEFAULT, DIAS_PP_MAX, DESC_PP_MAX FROM RO_T_PARAMETROS_DESC_CLIENTES ORDER BY COD_CLIENT";
            $stmt = sqlsrv_query($conn, $sql);
            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }
            echo json_encode(['data' => $data]);
            break;

        case 'create':
            // El descuento se guarda como decimal (8% = 0.08)
            $desc_pp = floatval($_POST['desc_pp_max']) / 100;
            $sql = "INSERT INTO RO_T_PARAMETROS_DESC_CLIENTES (COD_CLIENT, MEDIO_PAGO_DEFAULT, DIAS_PP_MAX, DESC_PP_MAX, FECHA_MOD) VALUES (?, ?, ?, ?, GETDATE())";
            $params = [$_POST['cod_client'], $_POST['medio_pago'], $_POST['dias_pp_max'], $desc_pp];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));
            echo json_encode(['success' => true, 'message' => 'Parámetro creado.']);
            break;
            
        case 'update':
            $desc_pp = floatval($_POST['desc_pp_max']) / 100;
            $sql = "UPDATE RO_T_PARAMETROS_DESC_CLIENTES SET COD_CLIENT = ?, MEDIO_PAGO_DEFAULT = ?, DIAS_PP_MAX = ?, DESC_PP_MAX = ?, FECHA_MOD = GETDATE() WHERE ID = ?";
            $params = [$_POST['cod_client'], $_POST['medio_pago'], $_POST['dias_pp_max'], $desc_pp, $_POST['id']];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));
            echo json_encode(['success' => true, 'message' => 'Parámetro actualizado.']);
            break;
            
        case 'delete':
            $sql = "DELETE FROM RO_T_PARAMETROS_DESC_CLIENTES WHERE ID = ?";
            $params = [$_POST['id']];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) throw new Exception(print_r(sqlsrv_errors(), true));
            echo json_encode(['success' => true, 'message' => 'Parámetro eliminado.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>