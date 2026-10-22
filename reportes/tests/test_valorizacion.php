<?php
/**
 * Valorización de stock: las reglas puras del costo actual, los filtros, el
 * resumen, la forma de la consulta y -si hay base- la paridad entre el SQL y
 * las reglas de PHP.
 *
 * La paridad es de SÓLO LECTURA y se saltea sola sin conexión. Recalcula en
 * PHP cada fila del último cierre con los insumos que devolvió el SQL y exige
 * el mismo origen y la misma valorización: es la prueba de que el CASE de la
 * consulta y origenCosto() dicen lo mismo.
 */

require_once __DIR__ . '/../Class/ValorizacionStock.php';
require_once __DIR__ . '/../Class/Menu.php';

$base = __DIR__ . '/..';
$V = 'ValorizacionStock';

seccion('origen del costo (modo actual)');

// Un artículo cualquiera (X...) y uno OU, con y sin precio de lista 20
$X = 'XV5SST06Z100139';
$OU = 'OUA12345';

chequear('sin PRECIO_U_L: Sin costo', $V::ORIGEN_SIN_COSTO, $V::origenCosto(null, 0, null, $X, 300));
chequear('PRECIO_U_L en 0: Sin costo', $V::ORIGEN_SIN_COSTO, $V::origenCosto(0, 0, null, $X, 300));
chequear('sin costo le gana a la partida', $V::ORIGEN_SIN_COSTO, $V::origenCosto(null, 1, 0.35, $X, 300));
chequear('con partida y COSTO_NAC: nacionalización', $V::ORIGEN_NACIONALIZACION, $V::origenCosto(100, 1, 0.35, $X, null));
chequear('con partida y COSTO_NAC en 0: sigue siendo nacionalización cargada', $V::ORIGEN_NACIONALIZACION,
    $V::origenCosto(100, 1, 0.0, $X, null));
chequear('con partida sin COSTO_NAC: nacionalización estimada', $V::ORIGEN_NAC_ESTIMADA, $V::origenCosto(100, 1, null, $X, null));
chequear('sin partida: última compra', $V::ORIGEN_ULTIMA_COMPRA, $V::origenCosto(100, 0, null, $X, null));
chequear('sin partida, aunque tenga COSTO_NAC: última compra', $V::ORIGEN_ULTIMA_COMPRA, $V::origenCosto(100, 0, 0.35, $X, null));
chequear('sin fila en STA11 (USA_PARTID null): última compra', $V::ORIGEN_ULTIMA_COMPRA, $V::origenCosto(100, null, null, $X, null));
chequear('USA_PARTID como bit de la base (true)', $V::ORIGEN_NAC_ESTIMADA, $V::origenCosto(100, true, null, $X, null));

seccion('los OU sin costo: lista 20 / 3');

chequear('OU sin PRECIO_U_L y con lista 20: lista 20 / 3', $V::ORIGEN_LISTA20, $V::origenCosto(null, 0, null, $OU, 300));
chequear('OU con PRECIO_U_L en 0: lista 20 / 3', $V::ORIGEN_LISTA20, $V::origenCosto(0, 0, null, $OU, 300));
chequear('OU sin lista 20: Sin costo', $V::ORIGEN_SIN_COSTO, $V::origenCosto(null, 0, null, $OU, null));
chequear('OU con lista 20 en 0: Sin costo', $V::ORIGEN_SIN_COSTO, $V::origenCosto(null, 0, null, $OU, 0));
chequear('OU con costo de última compra: la regla de siempre', $V::ORIGEN_ULTIMA_COMPRA, $V::origenCosto(100, 0, null, $OU, 300));
chequear('OU sin costo con partida: lista 20 / 3 igual, sin nacionalización', $V::ORIGEN_LISTA20,
    $V::origenCosto(null, 1, 0.35, $OU, 300));
chequear('el prefijo es al principio: "XOU..." no es OU', $V::ORIGEN_SIN_COSTO, $V::origenCosto(null, 0, null, 'XOU1', 300));
chequear('y distingue mayúsculas, como los códigos de Tango', $V::ORIGEN_SIN_COSTO, $V::origenCosto(null, 0, null, 'ou1', 300));
chequear('usaLista20() recorta espacios', true, $V::usaLista20('  OUX '));

seccion('costo unitario y valorización (modo actual)');

chequear('nacionalización: base × (1 + COSTO_NAC)', 135.0, $V::costoUnitarioActual(100, 1, 0.35, $X, null, 1.65));
chequear('estimada: base × factor', 165.0, $V::costoUnitarioActual(100, 1, null, $X, null, 1.65));
chequear('estimada con otro factor', 150.0, $V::costoUnitarioActual(100, 1, null, $X, null, 1.5));
chequear('sin partida: el costo base', 100.0, $V::costoUnitarioActual(100, 0, null, $X, null, 1.65));
chequear('sin PRECIO_U_L y no OU: 0, aunque tenga lista 20', 0.0, $V::costoUnitarioActual(null, 0, null, $X, 300, 1.65));
chequear('PRECIO_U_L en 0 con partida: 0, sin factor', 0.0, $V::costoUnitarioActual(0, 1, null, $X, null, 1.65));
chequear('OU sin costo: lista 20 / 3', 100.0, $V::costoUnitarioActual(null, 0, null, $OU, 300, 1.65));
chequear('OU sin costo con partida: sin factor ni COSTO_NAC', 100.0, $V::costoUnitarioActual(null, 1, 0.35, $OU, 300, 1.65));
chequear('OU sin costo ni lista 20: 0', 0.0, $V::costoUnitarioActual(null, 0, null, $OU, null, 1.65));
chequear('valorización a 2 decimales', 100.0, $V::valorizar(3, 33.3333));
chequear('valorización redondea medio centavo hacia arriba', 0.13, $V::valorizar(1, 0.125));
chequear('histórico: valorización / stock', 25.0, $V::costoUnitarioHistorico(4, 100));
chequear('histórico sin stock: 0', 0.0, $V::costoUnitarioHistorico(0, 100));

seccion('filtros');

$fechas = ['2026-10-18', '2026-10-11'];
$sucs = [
    ['sector' => 'CENTRAL', 'sucursal' => 'LAKERS SA'],
    ['sector' => 'LOCALES', 'sucursal' => 'ALTO ROSARIO'],
    ['sector' => 'LOCALES', 'sucursal' => 'UNICENTER']
];
$ok = ['fecha' => '2026-10-18', 'modo' => 'historico', 'sector' => '', 'sucursales' => [], 'articulo' => ''];

$r = $V::validarFiltros($ok, $fechas, $sucs);
chequear('lo mínimo se acepta', true, $r['ok']);
chequear('fecha que no existe se rechaza', false,
    $V::validarFiltros(['fecha' => '2026-10-17'] + $ok, $fechas, $sucs)['ok']);
chequear('fecha vacía se rechaza', false, $V::validarFiltros(['fecha' => ''] + $ok, $fechas, $sucs)['ok']);
chequear('modo desconocido se rechaza', false, $V::validarFiltros(['modo' => 'otro'] + $ok, $fechas, $sucs)['ok']);
chequear('sector desconocido se rechaza', false, $V::validarFiltros(['sector' => 'X'] + $ok, $fechas, $sucs)['ok']);
chequear('el sector se normaliza a mayúsculas', 'LOCALES',
    $V::validarFiltros(['sector' => 'locales'] + $ok, $fechas, $sucs)['filtros']['sector']);

$r = $V::validarFiltros(['sector' => 'LOCALES', 'sucursales' => ['LAKERS SA']] + $ok, $fechas, $sucs);
chequear('LAKERS SA no es de LOCALES', false, $r['ok']);
chequear('y el mensaje la nombra', true, strpos($r['message'], 'LAKERS SA') !== false);
chequear('una sucursal que no existe se rechaza', false,
    $V::validarFiltros(['sucursales' => ['NO EXISTE']] + $ok, $fechas, $sucs)['ok']);
chequear('las repetidas y vacías se limpian', ['ALTO ROSARIO', 'UNICENTER'],
    $V::validarFiltros(['sucursales' => ['ALTO ROSARIO', '', 'UNICENTER', 'ALTO ROSARIO']] + $ok, $fechas, $sucs)['filtros']['sucursales']);
chequear('un artículo de más de 50 caracteres se rechaza', false,
    $V::validarFiltros(['articulo' => str_repeat('x', 51)] + $ok, $fechas, $sucs)['ok']);
chequear('el artículo se recorta', 'xv5', $V::validarFiltros(['articulo' => '  xv5 '] + $ok, $fechas, $sucs)['filtros']['articulo']);

chequear('el LIKE escapa los comodines del usuario', '%50[%] [_]x[[]1]%', $V::patronLike('50% _x[1]'));

seccion('leyenda y filtros en palabras');

chequear('modo actual', 'Stock al 2026-10-18 valorizado al costo de hoy',
    $V::leyenda(['modo' => 'actual'] + $ok));
chequear('modo histórico', 'Stock y valorización al 2026-10-18, tal como se grabaron en el cierre',
    $V::leyenda($ok));
chequear('los filtros en palabras', [
    'Fecha de cierre: 2026-10-18',
    'Costo: Actual (costo de hoy)',
    'Sector: Locales',
    'Sucursal: ALTO ROSARIO',
    'Artículo: contiene "xv5"'
], $V::filtrosEnTexto(['modo' => 'actual', 'sector' => 'LOCALES', 'sucursales' => ['ALTO ROSARIO'], 'articulo' => 'xv5'] + $ok));

seccion('resumen por sector y sucursal');

$fila = function ($sec, $suc, $art, $stock, $val, $origen) {
    return ['sector' => $sec, 'sucursal' => $suc, 'articulo' => $art, 'descripcion' => '', 'stock' => $stock,
        'costoUnitario' => 0, 'valorizacion' => $val, 'origen' => $origen];
};
$res = $V::resumir([
    $fila('LOCALES', 'UNICENTER', 'A', 2, 10.10, $V::ORIGEN_ULTIMA_COMPRA),
    $fila('CENTRAL', 'LAKERS SA', 'A', 5, 50.05, $V::ORIGEN_NACIONALIZACION),
    $fila('LOCALES', 'ALTO ROSARIO', 'B', 1, 0.0, $V::ORIGEN_SIN_COSTO),
    $fila('LOCALES', 'UNICENTER', 'C', 3, 30.20, $V::ORIGEN_NAC_ESTIMADA)
]);

chequear('CENTRAL antes que LOCALES', ['CENTRAL', 'LOCALES'], array_column($res['sectores'], 'sector'));
chequear('las sucursales por nombre', ['ALTO ROSARIO', 'UNICENTER'],
    array_column($res['sectores'][1]['sucursales'], 'sucursal'));
chequear('subtotal de UNICENTER', 40.30, $res['sectores'][1]['sucursales'][1]['valorizacion']);
chequear('subtotal de LOCALES', 40.30, $res['sectores'][1]['valorizacion']);
chequear('stock de LOCALES', 6.0, $res['sectores'][1]['stock']);
chequear('total general', 90.35, $res['total']['valorizacion']);
chequear('el total es la suma de los subtotales', $res['total']['valorizacion'],
    round(array_sum(array_column($res['sectores'], 'valorizacion')), 2));
chequear('artículos distintos (A está en dos sucursales)', 3, $res['kpis']['articulos']);
chequear('filas sin costo', 1, $res['kpis']['sinCosto']);
chequear('filas con nacionalización estimada', 1, $res['kpis']['nacEstimada']);
chequear('filas OU por lista 20 / 3', 1, $V::resumir([
    $fila('LOCALES', 'UNICENTER', 'OU1', 1, 100.0, $V::ORIGEN_LISTA20)
])['kpis']['lista20']);
chequear('sin filas, todo en cero', ['stock' => 0.0, 'valorizacion' => 0.0, 'articulos' => 0, 'filas' => 0,
    'sinCosto' => 0, 'nacEstimada' => 0, 'lista20' => 0], $V::resumir([])['kpis']);

seccion('la consulta');

$sql0 = $V::sqlConsulta(0);
$sql3 = $V::sqlConsulta(3);
$f3 = ['sucursales' => ['A', 'B', 'C'], 'articulo' => 'x'] + $ok;

chequear('un "?" por parámetro, sin sucursales', count($V::parametrosConsulta($ok, null)), substr_count($sql0, '?'));
chequear('un "?" por parámetro, con tres sucursales', count($V::parametrosConsulta($f3, 1.65)), substr_count($sql3, '?'));
chequear('compara la fecha contra CAST(FECHA AS DATE)', true, strpos($sql0, 'CAST(T.FECHA AS DATE) = @FECHA') !== false);
chequear('agrupa por sector + sucursal + artículo', true,
    strpos($sql0, 'GROUP BY T.SECTOR, T.SUCURSAL, T.COD_ARTICU') !== false);
// Sin las etiquetas N'...' ni los comentarios, que dicen "lista 20 / 3" en prosa
$sqlCuentas = preg_replace(["#N'[^']*'#", '#--[^\n]*#'], '', $sql0);
chequear('divide por 3 en un solo lugar: la lista 20 de los OU', 1, preg_match_all('#/\s*3\b#', $sqlCuentas));
chequear('y ese lugar es el costo del origen lista 20', true,
    (bool) preg_match("#WHEN O\.ORIGEN = N'" . preg_quote($V::ORIGEN_LISTA20, '#') . "' THEN O\.PRECIO_LISTA20 / 3#", $sql0));
chequear('la lista 20 se busca sólo para los OU, en modo actual', true,
    (bool) preg_match("#LEFT JOIN GVA17 L ON @MODO = 'actual' AND B\.COD_ARTICU LIKE 'OU%'\s+AND L\.NRO_DE_LIS = 20#", $sql0));
chequear('el origen lista 20 exige no tener costo, ser OU y tener precio de lista', true,
    (bool) preg_match("#WHEN \(C\.PRECIO_U_L IS NULL OR C\.PRECIO_U_L = 0\)\s+AND C\.COD_ARTICU LIKE 'OU%' AND C\.PRECIO_LISTA20 > 0#", $sql0));
chequear('y va antes que "Sin costo" y que la partida', true,
    strpos($sql0, 'C.PRECIO_LISTA20 > 0 THEN') < strpos($sql0, "THEN N'" . $V::ORIGEN_SIN_COSTO . "'")
    && strpos($sql0, 'C.PRECIO_LISTA20 > 0 THEN') < strpos($sql0, 'WHEN C.USA_PARTID = 1'));
chequear('el factor es un parámetro, no un número escrito', true,
    strpos($sql0, '* @FACTOR') !== false && strpos($sql0, '1.65') === false);
chequear('el código se busca sin distinguir mayúsculas', true,
    strpos($sql0, 'T.COD_ARTICU COLLATE Modern_Spanish_CI_AI LIKE @ARTICULO') !== false);
chequear('los seis orígenes salen de las constantes', 6, count(array_filter([
    $V::ORIGEN_HISTORICO, $V::ORIGEN_ULTIMA_COMPRA, $V::ORIGEN_NACIONALIZACION, $V::ORIGEN_NAC_ESTIMADA,
    $V::ORIGEN_LISTA20, $V::ORIGEN_SIN_COSTO
], function ($o) use ($sql0) {
    return strpos($sql0, "N'" . $o . "'") !== false;
})));
chequear('el modo es el 2º parámetro y el factor el 3º', ['actual', 1.65],
    array_slice($V::parametrosConsulta(['modo' => 'actual'] + $ok, 1.65), 1, 2));

$clase = codigoSinComentarios($base . '/Class/ValorizacionStock.php');
chequear('sqlConsulta() no recibe filtros: no puede concatenar valores', true,
    strpos($clase, 'public static function sqlConsulta($cantSucursales)') !== false);

$ctrl = codigoSinComentarios($base . '/Controller/ValorizacionController.php');
chequear('el factor no viene del front', false, (bool) preg_match('/\$_(POST|GET|REQUEST)\[\s*\'factor/', $ctrl));
chequear('el factor lo lee el servidor de Parámetros', true,
    strpos($ctrl, '->valor(Parametros::FACTOR_NAC_ESTIMADO)') !== false);
chequear('consultar y detalle validan con la misma función', 2, substr_count($ctrl, 'filtrosYFactor($v)'));
chequear('el usuario del Excel es el del padrón', true, strpos($ctrl, "'usuario' => \$usuario") !== false);

seccion('la pantalla y el Excel');

$js = codigoSinComentarios($base . '/Js/valorizacion.js');
$tab = file_get_contents($base . '/Tabs/valorizacion_stock.php');
$menu = Menu::buscar('valorizacion_stock');

chequear('SheetJS es la versión de novedades, xlsx@0.18.5',
    ['https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js'], $menu['libs']);
chequear('cambiar un filtro no consulta: sólo el botón y Enter llaman a consultar()', 2,
    substr_count($js, 'consultar)') + substr_count($js, 'consultar();'));
chequear('la descarga usa los filtros de la última consulta, no los de la pantalla', true,
    strpos($js, "Reportes.post('Valorizacion', 'detalle', ultima.filtros)") !== false);
chequear('el front no manda el factor', false, (bool) preg_match('/factor\s*:/', $js));
chequear('el nombre del archivo', true,
    strpos($js, "'Valorizacion_Stock_' + r.filtros.fecha.replace(/-/g, '') + '_'") !== false
    && strpos($js, "'Actual' : 'Historico') + '.xlsx'") !== false);
chequear('las dos hojas', true,
    strpos($js, "hojaDetalle(r), 'Detalle')") !== false && strpos($js, "hojaResumen(r), 'Resumen')") !== false);
chequear('las columnas del detalle', true, strpos($js, "['Fecha de cierre', 'Sector', 'Sucursal', 'Artículo', "
    . "'Descripción', 'Stock', 'Costo unitario',\n            'Valorización', 'Origen del costo']") !== false);
chequear('autofiltro en la fila de títulos', true, strpos($js, "ws['!autofilter']") !== false);
chequear('stock e importes con miles y sin decimales', true, strpos($js, "var FORMATO_NUMERO = '#,##0';") !== false);
chequear('en las cinco columnas numéricas de las dos hojas', 5, substr_count($js, ', FORMATO_NUMERO);'));
chequear('ninguna con decimales', false, strpos($js, '#,##0.00') !== false);
chequear('en pantalla también sin decimales', true,
    strpos($js, "return Reportes.numero(v, 0);") !== false && strpos($js, 'Reportes.numero(r.kpis.valorizacion)') === false);
chequear('el encabezado lleva factor, usuario y hora', true,
    strpos($js, "'Factor de nacionalización estimado: '") !== false
    && strpos($js, "'Generado por ' + r.usuario + ' el ' + fechaHora(r.generado)") !== false);
chequear('la fecha va como número de serie, no como texto', true, strpos($js, 'var serie = serieExcel(r.filtros.fecha)') !== false);
chequear('la pantalla tiene los cinco filtros y los dos botones', 7,
    preg_match_all('/id="(raValFecha|raValModoHist|raValSector|raValSucursalBtn|raValArticulo|raValConsultar|raValExcel)"/', $tab));
chequear('el Excel arranca deshabilitado', true, (bool) preg_match('/id="raValExcel" disabled/', $tab));
chequear('el histórico es el modo por defecto', true, (bool) preg_match('/id="raValModoHist" value="historico" checked/', $tab));

seccion('contra la base (sólo lectura)');

if (!Pruebas::hayBase()) {
    Pruebas::saltear('sin conexión a la base');
} else {
    $conn = (new Conexion())->conectar('central');
    $v = new ValorizacionStock($conn);
    $fechas = $v->fechas();

    chequear('hay fechas de cierre', true, count($fechas) > 0);
    $ordenadas = $fechas;
    rsort($ordenadas);
    chequear('de la más reciente a la más vieja, sin repetidas', $ordenadas, array_values(array_unique($fechas)));

    $ultima = $fechas[0];
    Pruebas::informar('último cierre: ' . $ultima . ' (' . count($fechas) . ' fechas)');

    $f = ['fecha' => $ultima, 'modo' => 'historico', 'sector' => '', 'sucursales' => [], 'articulo' => ''];
    $hist = $v->detalle($f, null);
    $stmt = sqlsrv_query($conn, 'SELECT SUM(VALORIZACION) AS V, SUM(STOCK) AS S FROM ' . ValorizacionStock::TABLA
        . ' WHERE CAST(FECHA AS DATE) = ?', [$ultima]);
    $suma = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $res = ValorizacionStock::resumir($hist);

    chequear('histórico: el total coincide con SUM(VALORIZACION) de la tabla', round((float) $suma['V'], 2),
        $res['total']['valorizacion']);
    chequear('histórico: el stock coincide con SUM(STOCK)', round((float) $suma['S'], 4), $res['total']['stock']);

    $claves = array_map(function ($x) {
        return $x['sector'] . '|' . $x['sucursal'] . '|' . $x['articulo'];
    }, $hist);
    chequear('una fila por sector + sucursal + artículo', count($claves), count(array_unique($claves)));

    $f['modo'] = 'actual';
    $actual = $v->detalle($f, 1.65, true);
    chequear('actual: las mismas filas que el histórico', count($hist), count($actual));

    $distintas = [];

    foreach ($actual as $x) {
        $origen = ValorizacionStock::origenCosto($x['precioUltimaCompra'], $x['usaPartida'], $x['costoNac'],
            $x['articulo'], $x['precioLista20']);
        $val = ValorizacionStock::valorizar($x['stock'],
            ValorizacionStock::costoUnitarioActual($x['precioUltimaCompra'], $x['usaPartida'], $x['costoNac'],
                $x['articulo'], $x['precioLista20'], 1.65));

        if ($origen !== $x['origen'] || abs($val - $x['valorizacion']) > 0.011) {
            $distintas[] = $x['articulo'] . ' ' . $x['sucursal'] . ': SQL ' . $x['origen'] . ' ' . $x['valorizacion']
                . ' / PHP ' . $origen . ' ' . $val;
        }
    }

    chequear('actual: el SQL y las reglas de PHP dan lo mismo en todas las filas', [], array_slice($distintas, 0, 5));

    $sinCosto = array_filter($actual, function ($x) {
        return $x['origen'] === ValorizacionStock::ORIGEN_SIN_COSTO;
    });
    chequear('actual: "Sin costo" vale 0', 0.0, (float) array_sum(array_column($sinCosto, 'valorizacion')));

    $l20 = array_filter($actual, function ($x) {
        return $x['origen'] === ValorizacionStock::ORIGEN_LISTA20;
    });
    chequear('actual: sólo los OU van a lista 20 / 3', [], array_values(array_filter(array_column($l20, 'articulo'),
        function ($a) {
            return !ValorizacionStock::usaLista20($a);
        })));
    chequear('actual: ningún OU con precio de lista 20 queda sin costo', [], array_values(array_map(function ($x) {
        return $x['articulo'];
    }, array_filter($sinCosto, function ($x) {
        return ValorizacionStock::usaLista20($x['articulo']) && $x['precioLista20'] > 0;
    }))));

    $resA = ValorizacionStock::resumir($actual);
    Pruebas::informar('histórico ' . number_format($res['total']['valorizacion'], 2, ',', '.')
        . ' / actual ' . number_format($resA['total']['valorizacion'], 2, ',', '.')
        . ' / sin costo ' . $resA['kpis']['sinCosto'] . ' filas / lista 20 ' . $resA['kpis']['lista20']
        . ' filas (' . number_format(array_sum(array_column($l20, 'valorizacion')), 2, ',', '.') . ')'
        . ' / estimada ' . $resA['kpis']['nacEstimada'] . ' filas');

    $loc = $v->sucursales($ultima);
    $unaLocal = null;

    foreach ($loc as $s) {
        if ($s['sector'] === 'LOCALES') {
            $unaLocal = $s['sucursal'];
            break;
        }
    }

    if ($unaLocal !== null) {
        $filt = $v->detalle(['fecha' => $ultima, 'modo' => 'historico', 'sector' => 'LOCALES',
            'sucursales' => [$unaLocal], 'articulo' => ''], null);
        chequear('el filtro de sucursal deja sólo esa sucursal', [$unaLocal],
            array_values(array_unique(array_column($filt, 'sucursal'))));
    }
}
