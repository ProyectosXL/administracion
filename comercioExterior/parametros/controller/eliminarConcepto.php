<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['id_ce'])) {
        throw new Exception('Datos incompletos');
    }

    require_once '../../../class/conexion.php';
    $cid = new Conexion();
    
    $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);

    $idCe = intval($data['id_ce']);

    $sql = "DELETE FROM RO_T_CONCEPTOS_ESTIMACION_COMEX WHERE ID_CE = ?";
    $stmt = sqlsrv_query($conn, $sql, array($idCe));

    if ($stmt === false) {
        throw new Exception('Error al eliminar concepto: ' . print_r(sqlsrv_errors(), true));
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
