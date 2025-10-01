<?php
/**
 * Test detallado para el método marcarRecibidoTesoreria
 */

require_once __DIR__ . '/../../class/classEnv.php';
require_once __DIR__ . '/Class/Database.php';
require_once __DIR__ . '/Class/Ingreso.php';

try {
    echo "=== TEST DETALLADO MARCAR_RECIBIDO_TESORERIA ===\n\n";
    
    $ingreso = new Ingreso();
    
    // Test 1: Verificar estructura de tabla
    echo "1. Verificando estructura de tabla ingresos...\n";
    $database = Database::getInstance();
    $conn = $database->getAppsConnection();
    
    $sql = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ingresos' AND COLUMN_NAME = 'ID_TESORERIA'";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt && sqlsrv_has_rows($stmt)) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        echo "   ✓ Campo ID_TESORERIA existe: " . $row['DATA_TYPE'] . "\n";
        sqlsrv_free_stmt($stmt);
    } else {
        echo "   ✗ Campo ID_TESORERIA NO existe en la tabla\n";
        echo "   Ejecuta el script sql/agregar_campo_tesoreria.sql\n";
        exit;
    }
    
    // Test 2: Verificar método verificarRecibidoTesoreria
    echo "\n2. Testing verificarRecibidoTesoreria...\n";
    $reflection = new ReflectionClass($ingreso);
    $method = $reflection->getMethod('verificarRecibidoTesoreria');
    $method->setAccessible(true);
    
    $existe = $method->invoke($ingreso, 9999); // ID que no debería existir
    echo "   Verificación ID 9999 (no debería existir): " . ($existe ? 'true' : 'false') . "\n";
    
    // Test 3: Intentar insertar
    echo "\n3. Testing marcarRecibidoTesoreria...\n";
    $resultado = $ingreso->marcarRecibidoTesoreria(9999, '2025-10-01', 'Test de tesorería', 1000.00);
    echo "   Resultado: " . ($resultado ? 'ÉXITO' : 'ERROR') . "\n";
    
    if ($resultado) {
        echo "   ✓ Registro insertado correctamente\n";
        
        // Verificar que se insertó
        $existe_ahora = $method->invoke($ingreso, 9999);
        echo "   Verificación después del insert: " . ($existe_ahora ? 'true' : 'false') . "\n";
        
        // Limpiar - eliminar el registro de prueba
        $sqlDelete = "DELETE FROM ingresos WHERE ID_TESORERIA = ? AND origen = 'TESORERIA'";
        $stmt = sqlsrv_query($conn, $sqlDelete, [9999]);
        if ($stmt) {
            echo "   ✓ Registro de prueba limpiado\n";
            sqlsrv_free_stmt($stmt);
        }
    } else {
        echo "   ✗ Error al insertar registro\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>