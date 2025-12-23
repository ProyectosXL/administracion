<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../class/CronogramaDespachos.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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
