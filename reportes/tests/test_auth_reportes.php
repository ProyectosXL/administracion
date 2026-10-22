<?php
/**
 * Los permisos del módulo: la regla, la falla cerrada, el mapa de acciones y
 * que el script de alta diga exactamente lo mismo que el código.
 *
 * El mapa y los controllers se prueban LEYENDO EL CÓDIGO, sin comentarios:
 * un case que nadie declaró no se puede ejercitar sin un usuario de verdad, y
 * lo que importa es que no exista.
 */

require_once __DIR__ . '/../Class/Menu.php';
require_once __DIR__ . '/../Class/AuthReportes.php';

$base = __DIR__ . '/..';

seccion('falla cerrada: por línea de comandos no hay usuario');

chequear('no hay usuario', null, AuthReportes::usuario());
chequear('username() es null', null, AuthReportes::username());
chequear('no ve valorización', false, AuthReportes::puede('valorizacion_stock'));
chequear('no ve parámetros', false, AuthReportes::puede('parametros'));
chequear('no edita parámetros', false, AuthReportes::puedeEditar('parametros'));
chequear('el atributo de edición dice 0', ' data-puede-editar="0"', AuthReportes::atributoEdicion('parametros'));
chequear('ninguna pestaña permitida', [], Menu::permitidas());

$tipo = null;
try {
    AuthReportes::exigirAccion('Parametros', 'listar');
} catch (Exception $e) {
    $tipo = get_class($e);
}
chequear('una lectura declarada sin usuario es 401', 'AuthReportesSinUsuario', $tipo);

$tipo = null;
try {
    AuthReportes::exigirAccion('Parametros', 'guardar');
} catch (Exception $e) {
    $tipo = get_class($e);
}
chequear('una escritura declarada sin usuario es 401', 'AuthReportesSinUsuario', $tipo);

$tipo = null;
try {
    AuthReportes::exigirAccion('Parametros', 'borrarTodo');
} catch (Exception $e) {
    $tipo = get_class($e);
}
chequear('una acción no declarada es 403, haya o no usuario', 'AuthReportesSinPermiso', $tipo);

$auth = codigoSinComentarios($base . '/Class/AuthReportes.php');
chequear('username() no vuelve a $_SESSION', false,
    strpos(cuerpoDe($auth, 'username'), '$_SESSION') !== false);
chequear('el módulo se resuelve por código, no por id', true,
    strpos($auth, 'm.codigo = ?') !== false && strpos($auth, 'modulo_id = 7') === false);
chequear('el require del padrón es condicional', true,
    (bool) preg_match('/if \(file_exists\(AUTH_REPORTES_PADRON\)\)\s*\{\s*require_once AUTH_REPORTES_PADRON;/', $auth));

seccion('la regla pura');

$soloVer = ['ra.tab.parametros' => true];
$verYEditar = ['ra.tab.parametros' => true, 'ra.editar.parametros' => true];
$editarSuelto = ['ra.editar.parametros' => true];

chequear('con ra.tab.parametros ve', true, AuthReportes::permiteVer($soloVer, false, 'parametros'));
chequear('con ra.tab.parametros no ve valorización', false, AuthReportes::permiteVer($soloVer, false, 'valorizacion_stock'));
chequear('sólo con ver no edita', false, AuthReportes::permiteEditar($soloVer, false, 'parametros'));
chequear('con ver y editar edita', true, AuthReportes::permiteEditar($verYEditar, false, 'parametros'));
chequear('escribir implica leer: la clave de edición suelta no alcanza', false,
    AuthReportes::permiteEditar($editarSuelto, false, 'parametros'));
chequear('el administrador ve sin permisos', true, AuthReportes::permiteVer([], true, 'valorizacion_stock'));
chequear('el administrador edita sin permisos', true, AuthReportes::permiteEditar([], true, 'parametros'));
chequear('sin permisos no ve nada', false, AuthReportes::permiteVer([], false, 'valorizacion_stock'));

seccion('el mapa de acciones');

$escrituras = AuthReportes::ESCRITURAS;
$lecturas = AuthReportes::LECTURAS;
$tabs = array_column(Menu::todas(), 'tab');

$dobles = [];
$tabsDesconocidas = [];

foreach ([$escrituras, $lecturas] as $mapa) {
    foreach ($mapa as $ctrl => $acciones) {
        foreach ($acciones as $accion => $tabsAccion) {
            if (isset($escrituras[$ctrl][$accion]) && isset($lecturas[$ctrl][$accion])) {
                $dobles[] = $ctrl . '.' . $accion;
            }

            foreach ($tabsAccion as $t) {
                if (!in_array($t, $tabs, true)) {
                    $tabsDesconocidas[] = $ctrl . '.' . $accion . ' -> ' . $t;
                }
            }
        }
    }
}

chequear('ninguna acción es lectura y escritura a la vez', [], array_values(array_unique($dobles)));
chequear('toda acción nombra pestañas que existen en el Menu', [], $tabsDesconocidas);
chequear('una escritura pide edición', ['escribe' => true, 'tabs' => ['parametros']],
    AuthReportes::requisitoDe('Parametros', 'guardar'));
chequear('una lectura pide ver', ['escribe' => false, 'tabs' => ['valorizacion_stock']],
    AuthReportes::requisitoDe('Valorizacion', 'consultar'));

seccion('cada case de cada controller está declarado, y viceversa');

$controllers = glob($base . '/Controller/*Controller.php');
$sinDeclarar = [];
$sinGuarda = [];
$vistos = [];

foreach ($controllers as $archivo) {
    $nombre = basename($archivo, 'Controller.php');

    if ($nombre === 'Tab') {
        continue; // Sirve HTML por pestaña con Menu; no tiene acciones
    }

    $codigo = codigoSinComentarios($archivo);
    preg_match_all("/case\s+'([A-Za-z_]+)'\s*:/", $codigo, $m);

    foreach ($m[1] as $accion) {
        $vistos[$nombre][$accion] = true;

        if (!isset($escrituras[$nombre][$accion]) && !isset($lecturas[$nombre][$accion])) {
            $sinDeclarar[] = $nombre . '.' . $accion;
        }
    }

    $posGuarda = strpos($codigo, "autorizar('" . $nombre . "', \$action)");
    $posSwitch = strpos($codigo, 'switch ($action)');

    if ($posGuarda === false || $posSwitch === false || $posGuarda > $posSwitch) {
        $sinGuarda[] = $nombre;
    }
}

chequear('ningún case sin declarar en AuthReportes', [], $sinDeclarar);
chequear('todo controller llama a autorizar() antes del switch', [], $sinGuarda);

$faltan = [];

foreach (array_merge_recursive($escrituras, $lecturas) as $ctrl => $acciones) {
    if (!file_exists($base . '/Controller/' . $ctrl . 'Controller.php')) {
        Pruebas::saltear('Controller/' . $ctrl . 'Controller.php todavía no existe');
        continue;
    }

    foreach (array_keys($acciones) as $accion) {
        if (!isset($vistos[$ctrl][$accion])) {
            $faltan[] = $ctrl . '.' . $accion;
        }
    }
}

chequear('toda acción declarada existe como case en su controller', [], $faltan);

seccion('el script de alta da exactamente las claves del código');

$sql = file_get_contents($base . '/sql/reportes_permisos.sql');

preg_match_all("/^\('((?:hub\.app|ra)\.[a-z_.]+)'/m", $sql, $m);
$enScript = $m[1];
sort($enScript);

chequear('las claves del script son las de AuthReportes::claves()', AuthReportes::claves(), $enScript);
chequear('el script da de alta el módulo por su código', true,
    strpos($sql, "codigo = '" . AuthReportes::MODULO_CODIGO . "'") !== false);
chequear('el script no asigna roles', false, stripos($sql, 'INSERT INTO dbo.FP_ROL_PERMISO') !== false);
// El HUB (sistemas/hub/index.php) muestra la tarjeta a quien tenga
// 'hub.app.' . strtolower(codigo): si no coinciden, la tarjeta no aparece
chequear('la clave del HUB es la que el HUB deriva del código del módulo',
    'hub.app.' . strtolower(AuthReportes::MODULO_CODIGO), AuthReportes::CLAVE_HUB);

seccion('cada pestaña del Menu tiene su archivo y su JS');

foreach (Menu::todas() as $p) {
    chequear('Tabs/' . $p['tab'] . '.php existe', true, file_exists($base . '/Tabs/' . $p['tab'] . '.php'));
    foreach ($p['js'] as $js) {
        chequear('Js/' . $js . ' existe', true, file_exists($base . '/Js/' . $js));
    }
    chequear('la pestaña ' . $p['tab'] . ' marca data-puede-editar', true,
        strpos(file_get_contents($base . '/Tabs/' . $p['tab'] . '.php'),
            "AuthReportes::atributoEdicion('" . $p['tab'] . "')") !== false);
}
