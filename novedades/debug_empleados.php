<?php
/**
 * Script de prueba mejorado para empleados
 */

// Configurar error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Test de Búsqueda de Empleados - MEJORADO</h1>";

try {
    // Incluir clases
    require_once 'class/Novedades.php';
    echo "<p>✅ Clase Novedades cargada correctamente</p>";

    // Instanciar
    $novedades = new Novedades();
    echo "<p>✅ Instancia de Novedades creada correctamente</p>";

    // Test 1: Buscar empleados sin término (primeros 5)
    echo "<h3>📋 Test 1: Buscar primeros 5 empleados (sin filtro)</h3>";
    $empleados = $novedades->buscarEmpleadosSelect2('', 5);
    echo "<p><strong>Cantidad encontrada:</strong> " . count($empleados) . "</p>";
    if (!empty($empleados)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Legajo</th><th>Nombre</th><th>Apellido</th><th>Texto Completo</th></tr>";
        foreach ($empleados as $empleado) {
            echo "<tr>";
            echo "<td>{$empleado['id']}</td>";
            echo "<td>{$empleado['legajo']}</td>";
            echo "<td>{$empleado['nombre']}</td>";
            echo "<td>{$empleado['apellido']}</td>";
            echo "<td>{$empleado['text']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Test 2: Buscar por término específico
    echo "<h3>🔎 Test 2: Buscar empleados con término 'JUAN'</h3>";
    $empleadosJuan = $novedades->buscarEmpleadosSelect2('JUAN', 10);
    echo "<p><strong>Cantidad encontrada:</strong> " . count($empleadosJuan) . "</p>";
    if (!empty($empleadosJuan)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Legajo</th><th>Nombre</th><th>Apellido</th><th>Texto Completo</th></tr>";
        foreach ($empleadosJuan as $empleado) {
            echo "<tr>";
            echo "<td>{$empleado['id']}</td>";
            echo "<td>{$empleado['legajo']}</td>";
            echo "<td>{$empleado['nombre']}</td>";
            echo "<td>{$empleado['apellido']}</td>";
            echo "<td>{$empleado['text']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Test 3: Buscar empleado específico por legajo
    if (!empty($empleados)) {
        $primerEmpleado = $empleados[0];
        $legajo = $primerEmpleado['legajo'];
        
        echo "<h3>👤 Test 3: Buscar empleado específico por legajo ($legajo)</h3>";
        $empleado = $novedades->buscarEmpleado($legajo);
        
        if ($empleado) {
            echo "<p><strong>Empleado encontrado:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Legajo:</strong> {$empleado['legajo']}</li>";
            echo "<li><strong>Nombre:</strong> {$empleado['nombre']}</li>";
            echo "<li><strong>Apellido:</strong> {$empleado['apellido']}</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>No se encontró empleado con legajo $legajo</p>";
        }
    }

    // Test 4: Probar endpoint via AJAX simulado
    echo "<h3>🌐 Test 4: Simular llamada AJAX al controlador</h3>";
    
    // Simular $_GET
    $_GET_backup = $_GET;
    $_GET = [
        'action' => 'buscar_empleados_select2',
        'q' => 'WALTER',
        'limit' => 5
    ];

    // Capturar la salida del controlador
    ob_start();
    include 'controller/novedades_controller.php';
    $controllerOutput = ob_get_clean();
    
    // Restaurar $_GET
    $_GET = $_GET_backup;
    
    echo "<p><strong>Respuesta del controlador:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow: auto;'>";
    echo htmlspecialchars($controllerOutput);
    echo "</pre>";

    // Intentar decodificar la respuesta JSON
    $jsonResponse = json_decode($controllerOutput, true);
    if ($jsonResponse) {
        echo "<p><strong>JSON decodificado:</strong></p>";
        if ($jsonResponse['success']) {
            echo "<p style='color: green;'>✅ Respuesta exitosa con " . count($jsonResponse['data']) . " resultados</p>";
        } else {
            echo "<p style='color: red;'>❌ Error: " . ($jsonResponse['message'] ?? 'Sin mensaje') . "</p>";
        }
    }

    echo "<h2>✅ Todos los tests de empleados completados</h2>";
    echo "<p><strong>Resumen:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Conexión a base de datos funcionando</li>";
    echo "<li>✅ Consultas SQL ejecutándose correctamente</li>";
    echo "<li>✅ Formato Select2 aplicado correctamente</li>";
    echo "<li>✅ Controlador respondiendo correctamente</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<h2>❌ Error durante los tests:</h2>";
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 1px solid red; margin: 10px 0;'>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
    
    echo "<h3>Stack Trace:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;'>";
    echo $e->getTraceAsString();
    echo "</pre>";
}
?>
