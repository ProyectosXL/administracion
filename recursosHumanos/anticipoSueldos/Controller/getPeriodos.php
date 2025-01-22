
<?php

try {
    require_once '../../Class/Anticipo.php';
    $anticipo = new Anticipo();
    
    // Usar el método de la clase
    $periodos = $anticipo->obtenerPeriodos();
    echo json_encode($periodos);

} catch (Exception $e) {
    error_log("Error en getPeriodos.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}