<?php
header('Content-Type: application/json');

try {
    require_once '../Class/encabezado.php';
    $cid = new Encabezado();
    
    // Obtener todos los despachos ordenados por fecha descendente
    $despachos = $cid->listarTodosLosDespachos();
    
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
