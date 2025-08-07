<?php
require_once 'class/Database.php';

try {
    $db = Database::getInstance();
    
    echo "=== VERIFICANDO ESTRUCTURA DE TABLA NOVEDADES ===\n\n";
    
    // Verificar si la tabla existe
    $checkTable = "SELECT COUNT(*) as existe FROM sysobjects WHERE name='novedades' AND xtype='U'";
    $result = $db->query($checkTable);
    $row = $result->fetch();
    
    if ($row['existe'] > 0) {
        echo "✅ La tabla 'novedades' existe.\n\n";
        
        // Obtener estructura de columnas
        $sql = "SELECT 
                    COLUMN_NAME, 
                    DATA_TYPE, 
                    IS_NULLABLE, 
                    COLUMN_DEFAULT,
                    CHARACTER_MAXIMUM_LENGTH
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = 'novedades' 
                ORDER BY ORDINAL_POSITION";
        
        $stmt = $db->query($sql);
        $columns = $stmt->fetchAll();
        
        echo "COLUMNAS DE LA TABLA 'novedades':\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-20s %-15s %-10s %-15s %s\n", "COLUMNA", "TIPO", "NULLABLE", "DEFAULT", "LENGTH");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($columns as $col) {
            printf("%-20s %-15s %-10s %-15s %s\n", 
                $col['COLUMN_NAME'], 
                $col['DATA_TYPE'],
                $col['IS_NULLABLE'],
                $col['COLUMN_DEFAULT'] ?? 'NULL',
                $col['CHARACTER_MAXIMUM_LENGTH'] ?? 'N/A'
            );
        }
        
        // Verificar también datos existentes
        echo "\n\n=== DATOS EXISTENTES ===\n";
        $countSql = "SELECT COUNT(*) as total FROM novedades";
        $countResult = $db->query($countSql);
        $countRow = $countResult->fetch();
        echo "Total de registros: " . $countRow['total'] . "\n";
        
        if ($countRow['total'] > 0) {
            echo "\nPrimeros 3 registros:\n";
            $sampleSql = "SELECT TOP 3 * FROM novedades ORDER BY id DESC";
            $sampleResult = $db->query($sampleSql);
            $samples = $sampleResult->fetchAll();
            
            foreach ($samples as $i => $sample) {
                echo "\nRegistro " . ($i + 1) . ":\n";
                foreach ($sample as $key => $value) {
                    if (!is_numeric($key)) {
                        echo "  $key: " . ($value ?? 'NULL') . "\n";
                    }
                }
            }
        }
        
    } else {
        echo "❌ La tabla 'novedades' NO existe.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
