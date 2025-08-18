<?php
require_once __DIR__ . '/class/Novedades.php';

echo "=== PRUEBA DE EDICIÓN COMPLETA PRODUCCIÓN ===\n";

// Simular edición de una novedad de producción 25%
$datosEdicion = [
    'id' => 1,
    'legajo' => 1427,
    'nombre' => 'CAROLINA',
    'apellido' => 'LARDIT',
    'tipo_novedad' => 9, // Producción 25%
    'observaciones' => 'Observaciones originales - 12122 unidades (25%) - 100 unidades (25%)', // Observaciones con duplicación
    'fecha_vigencia' => '2025-01-01',
    'cantidad_unidades' => 150  // Nueva cantidad
];

echo "Datos de entrada:\n";
print_r($datosEdicion);

try {
    $novedades = new Novedades();
    
    // Usar reflection para acceder al método privado adaptarDatosParaInsercion
    $reflection = new ReflectionClass($novedades);
    $method = $reflection->getMethod('adaptarDatosParaInsercion');
    $method->setAccessible(true);
    
    echo "\n=== EJECUTANDO adaptarDatosParaInsercion ===\n";
    $datosAdaptados = $method->invoke($novedades, $datosEdicion);
    
    echo "Datos adaptados:\n";
    print_r($datosAdaptados);
    
    echo "\nObservaciones finales:\n";
    echo "'{$datosAdaptados['observaciones']}'\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
