<?php
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('ID del despacho no proporcionado');
    }
    
    $idMg = intval($_POST['id']);
    
    require_once '../class/estimacionCostos.php';
    
    $estimacion = new EstimacionCostos();
    
    // Verificar si ya está confirmada
    if ($estimacion->estaConfirmada($idMg)) {
        throw new Exception('La estimación ya está confirmada');
    }
    
    // Verificar que exista la estimación
    $estimacionExistente = $estimacion->obtenerEstimacion($idMg);
    
    if (!$estimacionExistente) {
        throw new Exception('Debe guardar la estimación antes de confirmarla');
    }
    
    $resultado = $estimacion->confirmarEstimacion($idMg);
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Estimación confirmada correctamente'
        ]);
    } else {
        throw new Exception('Error al confirmar la estimación');
    }
    
} catch (Exception $e) {
    error_log('Error en confirmarEstimacion.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
