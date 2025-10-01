<?php
/**
 * Test directo con captura de errores
 */

require_once __DIR__ . '/../../class/classEnv.php';
require_once __DIR__ . '/Class/Database.php';

try {
    echo "=== TEST DIRECTO SQL ===\n\n";
    
    $database = Database::getInstance();
    $conn = $database->getAppsConnection();
    
    $sql = "INSERT INTO ingresos (
                ID_TESORERIA, fecha, importe, observaciones, 
                recibido, fecha_carga, origen
            ) VALUES (
                ?, ?, ?, ?, 
                1, GETDATE(), 'TESORERIA'
            )";
    
    $params = [
        9999,           // ID_TESORERIA
        '2025-10-01',   // fecha
        1000.00,        // importe
        'Test directo'  // observaciones
    ];
    
    echo "SQL: " . $sql . "\n";
    echo "Params: " . print_r($params, true) . "\n";
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        echo "ERROR SQL:\n";
        print_r($errors);
    } else {
        echo "✓ INSERT exitoso\n";
        sqlsrv_free_stmt($stmt);
        
        // Limpiar
        $deleteStmt = sqlsrv_query($conn, "DELETE FROM ingresos WHERE ID_TESORERIA = 9999");
        if ($deleteStmt) {
            echo "✓ Registro limpiado\n";
            sqlsrv_free_stmt($deleteStmt);
        }
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
?>