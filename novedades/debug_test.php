<?php
/**
 * Script de depuración para probar la funcionalidad
 */

// Configurar error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Debug Test - Sistema de Novedades</h1>";

try {
    // Incluir clases
    require_once 'class/Novedades.php';
    echo "<p>✅ Clase Novedades cargada correctamente</p>";

    // Instanciar
    $novedades = new Novedades();
    echo "<p>✅ Instancia de Novedades creada correctamente</p>";

    // Test 1: Buscar empleados
    echo "<h3>📋 Test 1: Buscar empleados para Select2</h3>";
    $empleados = $novedades->buscarEmpleadosSelect2('', 5);
    echo "<p>Cantidad de empleados encontrados: " . count($empleados) . "</p>";
    if (!empty($empleados)) {
        echo "<ul>";
        foreach ($empleados as $empleado) {
            echo "<li>ID: {$empleado['id']}, Texto: {$empleado['text']}</li>";
        }
        echo "</ul>";
    }

    // Test 2: Obtener sucursales
    echo "<h3>🏢 Test 2: Obtener sucursales</h3>";
    $sucursales = $novedades->getSucursales();
    echo "<p>Cantidad de sucursales: " . count($sucursales) . "</p>";
    if (!empty($sucursales)) {
        echo "<ul>";
        foreach (array_slice($sucursales, 0, 5) as $sucursal) {
            echo "<li>Número: {$sucursal['numero']}, Descripción: {$sucursal['descripcion']}</li>";
        }
        echo "</ul>";
    }

    // Test 3: Tipos de novedad
    echo "<h3>📝 Test 3: Tipos de novedad</h3>";
    $tipos = $novedades->getTiposNovedad();
    echo "<p>Cantidad de tipos: " . count($tipos) . "</p>";
    if (!empty($tipos)) {
        echo "<ul>";
        foreach ($tipos as $tipo) {
            echo "<li>ID: {$tipo['id']}, Descripción: {$tipo['descripcion']}</li>";
        }
        echo "</ul>";
    }

    // Test 4: Novedades actuales
    echo "<h3>📊 Test 4: Novedades del período actual</h3>";
    $novedadesData = $novedades->getNovedadesPeriodoActual();
    echo "<p>Cantidad de novedades: " . count($novedadesData) . "</p>";

    echo "<h2>✅ Todos los tests completados exitosamente</h2>";

} catch (Exception $e) {
    echo "<h2>❌ Error durante los tests:</h2>";
    echo "<p style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid red;'>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "</p>";
    
    echo "<h3>Stack Trace:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto;'>";
    echo $e->getTraceAsString();
    echo "</pre>";
}
?>
