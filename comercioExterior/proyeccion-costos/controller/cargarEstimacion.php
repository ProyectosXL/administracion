<?php
header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ID del despacho no proporcionado');
    }
    
    $idMg = intval($_GET['id']);
    
    require_once '../../class/estimacionCostos.php';
    
    $estimacion = new EstimacionCostos();
    
    // Obtener datos del despacho
    $despacho = $estimacion->obtenerDespacho($idMg);
    
    if (!$despacho) {
        throw new Exception('Despacho no encontrado');
    }
    
    // Obtener conceptos configurados
    $conceptos = $estimacion->obtenerConceptos();
    
    // Obtener estimación existente (si existe)
    $estimacionExistente = $estimacion->obtenerEstimacion($idMg);
    
    // Verificar si está confirmada
    $confirmada = $estimacion->estaConfirmada($idMg);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'despacho' => $despacho,
            'conceptos' => $conceptos,
            'estimacion' => $estimacionExistente,
            'confirmada' => $confirmada
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error en cargarEstimacion.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
