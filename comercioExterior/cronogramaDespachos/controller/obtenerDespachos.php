<?php
require_once __DIR__ . '/../class/CronogramaDespachos.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $cronograma = new CronogramaDespachos();
    $resultado = $cronograma->obtenerDespachos();
    
    if (is_array($resultado)) {
        // Agregar estado a cada despacho
        foreach ($resultado as &$despacho) {
            $despacho['ESTADO'] = CronogramaDespachos::determinarEstado($despacho);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $resultado
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultado
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>
