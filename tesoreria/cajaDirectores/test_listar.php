<?php
/**
 * Test específico para la acción listar
 */

// Simular datos GET para el test
$_GET['accion'] = 'listar';

// Capturar el output del controller
ob_start();

try {
    include 'controller/caja_ingresos_controller.php';
    $output = ob_get_clean();
    
    echo "=== TEST ACCIÓN LISTAR ===\n";
    echo "Output: " . $output . "\n";
    
    // Verificar si es JSON válido
    $json = json_decode($output, true);
    if ($json !== null) {
        echo "✓ JSON válido\n";
        echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        if (isset($json['data'])) {
            echo "Cantidad de registros: " . count($json['data']) . "\n";
        }
    } else {
        echo "✗ JSON inválido\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>