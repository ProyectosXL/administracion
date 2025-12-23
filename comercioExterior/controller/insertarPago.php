<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

try {
    // Validar que la solicitud sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido');
    }
    
    // Validar parámetros requeridos
    $required = ['id_despacho', 'fecha_pago', 'forma_pago', 'medio_pago', 'monto'];
    foreach ($required as $param) {
        if (!isset($_POST[$param]) || empty($_POST[$param])) {
            throw new Exception("Parámetro requerido: $param");
        }
    }
    
    $idDespacho = $_POST['id_despacho'];
    $fechaPago = $_POST['fecha_pago'];
    $formaPago = $_POST['forma_pago'];
    $medioPago = $_POST['medio_pago'];
    $monto = $_POST['monto'];
    
    // Convertir fecha de DD/MM/YYYY a YYYY-MM-DD HH:MM:SS
    $partes = explode('/', $fechaPago);
    if (count($partes) !== 3) {
        throw new Exception('Formato de fecha inválido');
    }
    $fechaFormato = $partes[2] . '-' . $partes[1] . '-' . $partes[0] . ' 00:00:00';
    
    // Incluir clases necesarias
    require_once('../class/Pagos.php');
    
    $pagosClass = new Pagos();
    
    // Insertar el pago
    $resultado = $pagosClass->insertarPago($idDespacho, $fechaFormato, $formaPago, $medioPago, $monto);
    
    if ($resultado === true) {
        echo json_encode([
            'success' => true,
            'message' => 'Pago insertado correctamente'
        ]);
    } else {
        throw new Exception($resultado);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
