<?php
/**
 * Archivo de prueba para verificar el controlador
 * /novedades/test_controller.php
 */

echo "<h2>Prueba del Sistema de Novedades</h2>";

// Probar la conexión
echo "<h3>1. Probando conexión a base de datos...</h3>";
try {
    require_once 'class/Database.php';
    $db = Database::getInstance();
    echo "✅ Conexión exitosa<br>";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
}

// Probar la clase Novedades
echo "<h3>2. Probando clase Novedades...</h3>";
try {
    require_once 'class/Novedades.php';
    $novedades = new Novedades();
    echo "✅ Clase Novedades instanciada<br>";
    
    // Probar método de período
    $periodo = $novedades->getPeriodoActual();
    echo "✅ Período actual: " . $periodo['descripcion'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error en clase Novedades: " . $e->getMessage() . "<br>";
}

// Probar obtención de tipos de novedad
echo "<h3>3. Probando tipos de novedad...</h3>";
try {
    $tipos = $novedades->getTiposNovedad();
    echo "✅ Tipos de novedad obtenidos: " . count($tipos) . " tipos<br>";
    
    foreach ($tipos as $tipo) {
        echo "- ID: {$tipo['id']}, Código: {$tipo['codigo']}, Descripción: {$tipo['descripcion']}<br>";
    }
} catch (Exception $e) {
    echo "❌ Error obteniendo tipos: " . $e->getMessage() . "<br>";
}

// Probar obtención de sucursales
echo "<h3>4. Probando sucursales...</h3>";
try {
    $sucursales = $novedades->getSucursales();
    echo "✅ Sucursales obtenidas: " . count($sucursales) . " sucursales<br>";
    
    foreach (array_slice($sucursales, 0, 5) as $sucursal) {
        echo "- Número: {$sucursal['numero']}, Descripción: {$sucursal['descripcion']}<br>";
    }
    if (count($sucursales) > 5) {
        echo "... y " . (count($sucursales) - 5) . " más<br>";
    }
} catch (Exception $e) {
    echo "❌ Error obteniendo sucursales: " . $e->getMessage() . "<br>";
}

// Probar controlador
echo "<h3>5. Probando controlador...</h3>";
echo '<a href="controller/novedades_controller.php?action=test" target="_blank">🔗 Probar endpoint de test</a><br>';
echo '<a href="controller/novedades_controller.php?action=get_tipos_novedad" target="_blank">🔗 Probar get_tipos_novedad</a><br>';
echo '<a href="controller/novedades_controller.php?action=get_sucursales" target="_blank">🔗 Probar get_sucursales</a><br>';

echo "<h3>6. Estructura de archivos:</h3>";
echo "Verificar que existen estos archivos:<br>";
$archivos = [
    'class/Database.php',
    'class/Novedades.php', 
    'controller/novedades_controller.php',
    'js/novedades_main.js',
    'js/nueva_novedad_form.js',
    'css/novedades_estilos.css'
];

foreach ($archivos as $archivo) {
    if (file_exists($archivo)) {
        echo "✅ $archivo<br>";
    } else {
        echo "❌ $archivo (NO EXISTE)<br>";
    }
}
?>