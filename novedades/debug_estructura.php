<?php
/**
 * Script para ver la estructura de la tabla de empleados
 */

// Configurar error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Estructura de Tabla de Empleados</h1>";

try {
    // Incluir clases
    require_once 'class/Database.php';
    echo "<p>✅ Clase Database cargada correctamente</p>";

    // Instanciar
    $db = Database::getInstance();
    echo "<p>✅ Conexión a base de datos establecida</p>";

    // Consultar la estructura de la tabla
    echo "<h3>📋 Columnas de la tabla RO_LEGAJOS_PERSONAL_ALL</h3>";
    
    $sql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'RO_LEGAJOS_PERSONAL_ALL' 
            AND TABLE_SCHEMA = 'DBO'
            ORDER BY ORDINAL_POSITION";
    
    $stmt = $db->query($sql);
    $columnas = $stmt->fetchAll();
    
    if (!empty($columnas)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Nombre Columna</th><th>Tipo</th><th>Longitud</th><th>Nullable</th>";
        echo "</tr>";
        
        foreach ($columnas as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['COLUMN_NAME']}</strong></td>";
            echo "<td>{$col['DATA_TYPE']}</td>";
            echo "<td>" . ($col['CHARACTER_MAXIMUM_LENGTH'] ?? '-') . "</td>";
            echo "<td>{$col['IS_NULLABLE']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No se encontraron columnas para esta tabla.</p>";
    }

    // Intentar una consulta simple para obtener algunos registros
    echo "<h3>📊 Primeros 5 registros (todas las columnas)</h3>";
    try {
        $sql2 = "SELECT TOP 5 * FROM [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL";
        $stmt2 = $db->query($sql2);
        $registros = $stmt2->fetchAll();
        
        if (!empty($registros)) {
            echo "<p>Encontrados " . count($registros) . " registros</p>";
            echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;'>";
            foreach ($registros as $i => $registro) {
                echo "<strong>Registro " . ($i + 1) . ":</strong>\n";
                foreach ($registro as $campo => $valor) {
                    if (!is_numeric($campo)) { // Solo mostrar campos con nombre, no índices numéricos
                        echo "  {$campo}: " . var_export($valor, true) . "\n";
                    }
                }
                echo "\n";
                if ($i >= 2) break; // Solo mostrar 3 registros completos
            }
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>Error obteniendo registros: " . $e->getMessage() . "</p>";
    }

    echo "<h2>✅ Análisis de estructura completado</h2>";

} catch (Exception $e) {
    echo "<h2>❌ Error durante el análisis:</h2>";
    echo "<p style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid red;'>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "</p>";
}
?>
