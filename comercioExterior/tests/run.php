<?php
/**
 * Corredor de pruebas de Comercio Exterior.
 *
 *   php comercioExterior/tests/run.php              corre todo
 *   php comercioExterior/tests/run.php estimacion   corre solo los archivos que contengan "estimacion"
 *
 * Las pruebas que necesitan la base se saltean solas si no hay conexión, así
 * que el corredor sirve igual en una máquina sin acceso a SQL Server. Las que
 * sí la usan son de SÓLO LECTURA: ninguna escribe.
 *
 * Es el mismo corredor que cashflow/tests/run.php de ProyectosXL/finanzas.
 */

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

require_once __DIR__ . '/lib.php';

$filtro = isset($argv[1]) ? $argv[1] : null;

$archivos = glob(__DIR__ . '/test_*.php');
sort($archivos);

echo 'Pruebas de Comercio Exterior';
echo ($filtro ? " (filtro: $filtro)" : '') . PHP_EOL;

$corridos = 0;

foreach ($archivos as $archivo) {
    $nombre = basename($archivo, '.php');

    if ($filtro !== null && strpos($nombre, $filtro) === false) {
        continue;
    }

    $corridos++;
    Pruebas::$archivo = $nombre;

    echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
    echo $nombre . PHP_EOL;
    echo str_repeat('=', 70) . PHP_EOL;

    require $archivo;
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;

if ($corridos === 0) {
    echo 'Ningun archivo de prueba coincide con el filtro.' . PHP_EOL;
    exit(1);
}

if (Pruebas::$fallas > 0) {
    echo 'FALLAS:' . PHP_EOL;

    foreach (Pruebas::$detalle as $d) {
        echo '  - ' . $d . PHP_EOL;
    }

    echo PHP_EOL;
}

echo 'OK: ' . Pruebas::$ok . '   FALLAS: ' . Pruebas::$fallas
    . '   (' . $corridos . ' archivos)' . PHP_EOL;

exit(Pruebas::$fallas > 0 ? 1 : 0);
