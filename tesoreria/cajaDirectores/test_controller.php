<?php
/**
 * Test simple para verificar el controller de ingresos
 */

// Simular datos POST para el test
$_POST['accion'] = 'total_recibido';

// Capturar el output del controller
ob_start();

try {
    include 'controller/caja_ingresos_controller.php';
    $output = ob_get_clean();
    
    echo "=== TEST CONTROLLER INGRESOS ===\n";
    echo "Output: " . $output . "\n";
    
    // Verificar si es JSON válido
    $json = json_decode($output, true);
    if ($json !== null) {
        echo "✓ JSON válido\n";
        echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        if (isset($json['total'])) {
            echo "Total: " . $json['total'] . "\n";
        }
    } else {
        echo "✗ JSON inválido\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>