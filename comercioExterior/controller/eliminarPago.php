<?php
header('Content-Type: application/json');

session_start();

try {
    // Validar que la solicitud sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido');
    }
    
    // Validar que se proporcione el ID del pago
    if (!isset($_POST['id_pago']) || empty($_POST['id_pago'])) {
        throw new Exception('ID de pago requerido');
    }
    
    $idPago = $_POST['id_pago'];
    
    // Incluir clases necesarias
    require_once('../class/Pagos.php');
    
    $pagosClass = new Pagos();
    
    // Eliminar el pago
    $resultado = $pagosClass->eliminarPago($idPago);
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Pago eliminado correctamente'
        ]);
    } else {
        throw new Exception('No se pudo eliminar el pago');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
