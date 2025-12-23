<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['id_mg']) || !isset($data['conceptos'])) {
        throw new Exception('Datos incompletos');
    }
    
    $idMg = intval($data['id_mg']);
    $conceptos = $data['conceptos'];
    
    require_once '../class/estimacionCostos.php';
    
    $estimacion = new EstimacionCostos();
    
    // Verificar si está confirmada
    if ($estimacion->estaConfirmada($idMg)) {
        throw new Exception('No se puede modificar una estimación confirmada');
    }
    
    $resultado = $estimacion->guardarEstimacion($idMg, $conceptos);
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Estimación guardada correctamente'
        ]);
    } else {
        throw new Exception('Error al guardar la estimación');
    }
    
} catch (Exception $e) {
    error_log('Error en guardarEstimacion.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
