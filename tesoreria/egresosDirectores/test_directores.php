<?php
// Script de prueba para verificar conexión a directores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Test de Conexión a Directores</h2>";

try {
    require_once __DIR__ . '/Class/Director.php';
    
    echo "<p>✅ Clase Director cargada correctamente</p>";
    
    $director = new Director();
    echo "<p>✅ Instancia de Director creada</p>";
    
    // Probar conexión directa a la base
    require_once __DIR__ . '/Class/Database.php';
    $db = Database::getInstance()->getAppsConnection();
    echo "<p>✅ Conexión a base de datos obtenida</p>";
    
    // Probar consulta SQL directa
    $sql = "SELECT ID_DIRECTOR, NOMBRE, EMAIL, ACTIVO FROM RO_T_DIRECTORES";
    echo "<p>🔍 Ejecutando consulta: <code>$sql</code></p>";
    
    $stmt = sqlsrv_query($db, $sql);
    if ($stmt === false) {
        echo "<p>❌ Error en consulta SQL:</p>";
        echo "<pre>" . print_r(sqlsrv_errors(), true) . "</pre>";
    } else {
        echo "<p>✅ Consulta SQL ejecutada correctamente</p>";
        
        $todos_directores = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $todos_directores[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        
        echo "<h3>📊 Todos los Directores en la tabla:</h3>";
        echo "<p><strong>Total:</strong> " . count($todos_directores) . "</p>";
        
        if (count($todos_directores) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Activo</th></tr>";
            foreach ($todos_directores as $dir) {
                echo "<tr>";
                echo "<td>" . $dir['ID_DIRECTOR'] . "</td>";
                echo "<td>" . $dir['NOMBRE'] . "</td>";
                echo "<td>" . ($dir['EMAIL'] ?? 'N/A') . "</td>";
                echo "<td>" . ($dir['ACTIVO'] ? 'SÍ' : 'NO') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Ahora probar solo los activos
    echo "<hr><h3>🔍 Probando método obtenerDirectores():</h3>";
    $directores = $director->obtenerDirectores();
    echo "<p>✅ Método obtenerDirectores() ejecutado</p>";
    
    echo "<h3>📊 Directores Activos:</h3>";
    echo "<p><strong>Total de directores activos encontrados:</strong> " . count($directores) . "</p>";
    
    if (count($directores) > 0) {
        echo "<h4>📋 Lista de Directores Activos:</h4>";
        echo "<ul>";
        foreach ($directores as $dir) {
            echo "<li>ID: {$dir['id_director']} - Nombre: {$dir['nombre_director']} - Email: {$dir['mail_director']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ <strong>No se encontraron directores activos. Verificar:</strong></p>";
        echo "<ul>";
        echo "<li>Campo ACTIVO debe ser = 1 (no 0)</li>";
        echo "<li>Tipo de dato del campo ACTIVO (BIT, TINYINT, INT)</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>🔧 Información de Debug:</h3>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>SQL Server Extension:</strong> " . (extension_loaded('sqlsrv') ? '✅ Cargada' : '❌ No cargada') . "</p>";
echo "<p><strong>Ruta actual:</strong> " . __DIR__ . "</p>";

// Verificar que el archivo .env existe
$envPath = __DIR__ . '/../../../.env';
echo "<p><strong>Archivo .env:</strong> " . (file_exists($envPath) ? '✅ Existe' : '❌ No encontrado') . " en: $envPath</p>";
?>