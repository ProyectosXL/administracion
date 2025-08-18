<?php
require_once __DIR__ . '/class/Novedades.php';

echo "=== PRUEBA DE LIMPIEZA DE OBSERVACIONES PRODUCCIÓN ===\n";

// Simular observaciones con producción duplicada como el problema descrito
$observacionesTest = "Observaciones originales - 12122 unidades (25%) - 100 unidades (25%)";

echo "Observaciones originales:\n";
echo "$observacionesTest\n\n";

$novedades = new Novedades();

// Usar reflection para acceder al método privado
$reflection = new ReflectionClass($novedades);
$method = $reflection->getMethod('limpiarObservacionesEspecificas');
$method->setAccessible(true);

// Probar limpieza para tipo 9 (Producción 25%)
$observacionesLimpias = $method->invoke($novedades, $observacionesTest, 9);

echo "Después de limpiar para tipo 9 (Producción 25%):\n";
echo "'$observacionesLimpias'\n\n";

// Probar con diferentes variaciones
$variaciones = [
    "Test - 100 unidades (25%)",
    "Test - 12122 unidades (25%) - 100 unidades (25%)",
    "Otras obs - 50 unidades (25%) - algo más",
    "Solo - 999 unidades (25%)"
];

foreach ($variaciones as $i => $obs) {
    $limpio = $method->invoke($novedades, $obs, 9);
    echo "Variación " . ($i+1) . ":\n";
    echo "Original: '$obs'\n";
    echo "Limpio:   '$limpio'\n\n";
}
?>
