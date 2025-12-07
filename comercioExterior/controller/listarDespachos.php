<?php
header('Content-Type: application/json');

try {
    require_once '../class/estimacionCostos.php';
    
    $estimacion = new EstimacionCostos();
    $despachos = $estimacion->listarDespachosConEstado();
    
    echo json_encode([
        'success' => true,
        'data' => $despachos
    ]);
    
} catch (Exception $e) {
    error_log('Error en listarDespachos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
