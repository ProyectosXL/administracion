<?php
/**
 * Test específico para marcar_recibido_tesoreria
 */

// Simular datos POST
$_POST['accion'] = 'marcar_recibido_tesoreria';
$_POST['id_tesoreria'] = '123';
$_POST['fecha'] = '2025-10-01';
$_POST['observaciones'] = 'Test de tesorería';
$_POST['importe'] = '1000';

echo "=== TEST MARCAR_RECIBIDO_TESORERIA ===\n";
echo "Datos enviados:\n";
print_r($_POST);
echo "\n";

// Capturar el output del controller
ob_start();

try {
    include 'controller/caja_ingresos_controller.php';
    $output = ob_get_clean();
    
    echo "Output: " . $output . "\n";
    
    // Verificar si es JSON válido
    $json = json_decode($output, true);
    if ($json !== null) {
        echo "✓ JSON válido\n";
        echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        if (isset($json['message'])) {
            echo "Message: " . $json['message'] . "\n";
        }
    } else {
        echo "✗ JSON inválido\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>