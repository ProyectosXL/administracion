<?php
/**
 * Que el README diga lo mismo que el código en lo que se consulta de él: las
 * claves de permiso, los valores de "Origen del costo", los scripts y las
 * pestañas. Un README que quedó atrás es peor que uno que no existe, porque se
 * le cree.
 */

require_once __DIR__ . '/../Class/AuthReportes.php';
require_once __DIR__ . '/../Class/Menu.php';
require_once __DIR__ . '/../Class/ValorizacionStock.php';
require_once __DIR__ . '/../Class/Parametros.php';

$base = __DIR__ . '/..';
$readme = file_get_contents($base . '/README-reportes.md');

seccion('el README nombra lo que existe');

$faltan = array_values(array_filter(AuthReportes::claves(), function ($c) use ($readme) {
    return strpos($readme, '`' . $c . '`') === false;
}));
chequear('todas las claves de permiso', [], $faltan);

$origenes = [ValorizacionStock::ORIGEN_ULTIMA_COMPRA, ValorizacionStock::ORIGEN_NACIONALIZACION,
    ValorizacionStock::ORIGEN_NAC_ESTIMADA, ValorizacionStock::ORIGEN_SIN_COSTO];
chequear('los cuatro orígenes del modo actual, en la tabla', [],
    array_values(array_filter($origenes, function ($o) use ($readme) {
        return strpos($readme, '| ' . $o . ' |') === false;
    })));

$scripts = array_map('basename', glob($base . '/sql/*.sql'));
chequear('todos los scripts SQL', [], array_values(array_filter($scripts, function ($s) use ($readme) {
    return strpos($readme, '`sql/' . $s . '`') === false;
})));
chequear('los scripts en orden: parámetros (central) antes que permisos (apps)', true,
    strpos($readme, '| 1 | `sql/reportes_parametros.sql`') !== false
    && strpos($readme, '| 2 | `sql/reportes_permisos.sql`') !== false);

chequear('todos los parámetros con regla', [], array_values(array_filter(array_keys(Parametros::REGLAS),
    function ($k) use ($readme) {
        return strpos($readme, '`' . $k . '`') === false;
    })));

chequear('explica por qué se agrupa (la tabla no guarda el depósito)', true,
    strpos($readme, '### Se agrupa por sector + sucursal + artículo') !== false
    && strpos($readme, 'guarda el depósito') !== false);
chequear('la versión de SheetJS es la del Menu', true,
    strpos($readme, 'xlsx@0.18.5') !== false
    && strpos(Menu::buscar('valorizacion_stock')['libs'][0], 'xlsx@0.18.5') !== false);
chequear('el código del módulo', true, strpos($readme, '`' . AuthReportes::MODULO_CODIGO . '`') !== false);
