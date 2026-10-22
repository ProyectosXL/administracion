<?php
/**
 * Remitos pendientes de facturar: los rangos del período, los filtros, la
 * clasificación caso por caso, el resumen, la forma de la consulta y -si hay
 * base- la paridad con el SP RO_BUSCA_REMITOS_SIN_FACTURA.
 *
 * La paridad es de SÓLO LECTURA y se saltea sola sin conexión. El SP no se
 * ejecuta (manda un mail): se replica su SELECT. Las diferencias tienen que
 * explicarse todas por las mejoras del reporte, y se informan por motivo.
 */

require_once __DIR__ . '/../Class/RemitosPendientes.php';
require_once __DIR__ . '/../Class/Menu.php';
require_once __DIR__ . '/../Class/AuthReportes.php';

$base = __DIR__ . '/..';
$R = 'RemitosPendientes';

seccion('rangos del período');

$rango = function ($opcion, $hoy) use ($R) {
    $r = $R::rangoPeriodo($opcion, $hoy);

    return $r === null ? null : $r['desde'] . ' > ' . $r['hasta'];
};

chequear('el ejemplo: 22/10/2026, contable actual', '2026-08-01 > 2026-10-22', $rango('contable_actual', '2026-10-22'));
chequear('el ejemplo: 22/10/2026, contable anterior', '2025-08-01 > 2026-07-31', $rango('contable_anterior', '2026-10-22'));
chequear('31/07: el actual todavía empezó el año anterior', '2025-08-01 > 2026-07-31', $rango('contable_actual', '2026-07-31'));
chequear('31/07: anterior', '2024-08-01 > 2025-07-31', $rango('contable_anterior', '2026-07-31'));
chequear('01/08: el actual empieza hoy', '2026-08-01 > 2026-08-01', $rango('contable_actual', '2026-08-01'));
chequear('01/08: anterior es el que terminó ayer', '2025-08-01 > 2026-07-31', $rango('contable_anterior', '2026-08-01'));
chequear('01/01: el actual sigue siendo el que empezó en agosto', '2025-08-01 > 2026-01-01',
    $rango('contable_actual', '2026-01-01'));
chequear('01/01: anterior', '2024-08-01 > 2025-07-31', $rango('contable_anterior', '2026-01-01'));
chequear('año calendario', '2026-01-01 > 2026-10-22', $rango('calendario', '2026-10-22'));
chequear('año calendario el 01/01', '2026-01-01 > 2026-01-01', $rango('calendario', '2026-01-01'));
chequear('año calendario el 31/12', '2026-01-01 > 2026-12-31', $rango('calendario', '2026-12-31'));
chequear('personalizado no tiene rango', null, $rango('personalizado', '2026-10-22'));
chequear('las cuatro opciones, la contable actual primero', ['contable_actual', 'contable_anterior', 'calendario',
    'personalizado'], array_column($R::periodos('2026-10-22'), 'opcion'));

seccion('filtros');

$hoy = '2026-10-21';
$ok = ['desde' => '2026-08-01', 'hasta' => '2026-10-21', 'tipo' => '', 'clientes' => []];

chequear('lo mínimo se acepta', true, $R::validarPeriodo($ok, $hoy)['ok']);
$r = $R::validarPeriodo(['hasta' => '2026-10-22'] + $ok, $hoy);
chequear('un Hasta futuro se rechaza', false, $r['ok']);
chequear('y el mensaje dice hoy', true, strpos($r['message'], '21/10/2026') !== false);
chequear('Desde posterior a Hasta se rechaza', false, $R::validarPeriodo(['desde' => '2026-10-22', 'hasta' => '2026-10-21'] + $ok, $hoy)['ok']);
chequear('Desde igual a Hasta se acepta', true, $R::validarPeriodo(['desde' => '2026-10-21'] + $ok, $hoy)['ok']);
chequear('una fecha que no existe se rechaza', false, $R::validarPeriodo(['desde' => '2026-02-30'] + $ok, $hoy)['ok']);
chequear('una fecha vacía se rechaza', false, $R::validarPeriodo(['desde' => ''] + $ok, $hoy)['ok']);
chequear('el formato es AAAA-MM-DD', false, $R::validarPeriodo(['desde' => '01/08/2026'] + $ok, $hoy)['ok']);
chequear('tipo desconocido se rechaza', false, $R::validarPeriodo(['tipo' => 'X'] + $ok, $hoy)['ok']);
chequear('el tipo se normaliza a mayúsculas', 'M', $R::validarPeriodo(['tipo' => 'm'] + $ok, $hoy)['filtros']['tipo']);

$validos = ['F00001', 'M00002'];
$r = $R::validarFiltros(['clientes' => ['M00002', ' f00001 ', '', 'M00002']] + $ok, $hoy, $validos);
chequear('los clientes se limpian, sin repetidos y ordenados', ['F00001', 'M00002'], $r['filtros']['clientes']);
$r = $R::validarFiltros(['clientes' => ['L00009']] + $ok, $hoy, $validos);
chequear('un cliente sin remitos en el período se rechaza', false, $r['ok']);
chequear('y el mensaje lo nombra', true, strpos($r['message'], 'L00009') !== false);
chequear('sin clientes es todos', [], $R::validarFiltros($ok, $hoy, $validos)['filtros']['clientes']);
chequear('un período inválido se rechaza antes que los clientes', false,
    $R::validarFiltros(['hasta' => '2027-01-01'] + $ok, $hoy, $validos)['ok']);

chequear('tipo por la primera letra: F', 'Franquicias', $R::tipoCliente('F00001'));
chequear('L', 'Gestión asistida', $R::tipoCliente('L00001'));
chequear('M', 'Mayoristas', $R::tipoCliente('M00001'));
chequear('días al corte', 30, $R::diasAlCorte('2026-09-01', '2026-10-01'));
chequear('días al corte, mismo día', 0, $R::diasAlCorte('2026-10-01', '2026-10-01'));
chequear('centavos, sin error de punto flotante', 1157061115, $R::centavos('11570611.15'));

seccion('clasificación');

$corte = '2026-09-30';

$remito = function ($nro, $fecha, $importe, $facturas = [], $cliente = 'F00001') {
    return ['remito' => $nro, 'fecha' => $fecha, 'cliente' => $cliente, 'razonSocial' => 'CLIENTE ' . $cliente,
        'importe' => $importe, 'facturas' => $facturas];
};
$factura = function ($nro, $fecha, $importe = null, $cliente = 'F00001') {
    $f = ['factura' => $nro, 'fecha' => $fecha];

    return $importe === null ? $f : $f + ['cliente' => $cliente, 'importe' => $importe];
};
$nros = function ($filas) {
    return array_column($filas, 'remito');
};

// 1. Vinculado en Tango
chequear('vinculado antes del corte: facturado, no sale', [],
    $R::clasificar([$remito('R1', '2026-09-10', 100, [$factura('A1', '2026-09-20')])], [], $corte));
chequear('vinculado el mismo día del corte: facturado', [],
    $R::clasificar([$remito('R1', '2026-09-10', 100, [$factura('A1', $corte)])], [], $corte));

$p = $R::clasificar([$remito('R1', '2026-09-10', 100, [$factura('A1', '2026-10-05')])], [], $corte);
chequear('vinculado después del corte: pendiente', ['R1'], $nros($p));
chequear('con la factura y su fecha', ['A1', '2026-10-05', 'Vinculado en Tango'],
    [$p[0]['facturaNro'], $p[0]['facturaFecha'], $p[0]['como']]);
chequear('y los días al corte', 20, $p[0]['diasAlCorte']);

$p = $R::clasificar([$remito('R1', '2026-09-10', 100, [$factura('A2', '2026-10-15'), $factura('A1', '2026-09-20')])], [], $corte);
chequear('varias vinculadas, una después del corte: pendiente con la última', ['A2', '2026-10-15'],
    [$p[0]['facturaNro'] ?? null, $p[0]['facturaFecha'] ?? null]);
chequear('varias vinculadas, todas antes del corte: facturado', [],
    $R::clasificar([$remito('R1', '2026-09-10', 100, [$factura('A1', '2026-09-12'), $factura('A2', '2026-09-25')])], [], $corte));
chequear('vinculado le gana al pozo: no usa una factura por importe', [],
    $R::clasificar([$remito('R1', '2026-09-10', 100, [$factura('A1', '2026-09-20')])],
        [$factura('B1', '2026-10-05', 100)], $corte));
chequear('vinculado con la factura anterior al remito: facturado igual', [],
    $R::clasificar([$remito('R1', '2026-09-10', 100, [$factura('A1', '2026-09-01')])], [], $corte));

// 2. Por importe
chequear('por importe antes del corte: facturado', [],
    $R::clasificar([$remito('R1', '2026-09-10', 100)], [$factura('B1', '2026-09-20', 100)], $corte));
$p = $R::clasificar([$remito('R1', '2026-09-10', 100)], [$factura('B1', '2026-10-05', 100)], $corte);
chequear('por importe después del corte: pendiente', ['B1', '2026-10-05', 'Por importe'],
    [$p[0]['facturaNro'] ?? null, $p[0]['facturaFecha'] ?? null, $p[0]['como'] ?? null]);
chequear('la factura del mismo día del remito lo cubre', [],
    $R::clasificar([$remito('R1', '2026-09-10', 100)], [$factura('B1', '2026-09-10', 100)], $corte));

$p = $R::clasificar([$remito('R1', '2026-09-10', 100)], [$factura('B1', '2026-09-05', 100)], $corte);
chequear('factura anterior al remito: no lo cubre', ['R1', ''], [$p[0]['remito'] ?? null, $p[0]['como'] ?? null]);

$p = $R::clasificar([$remito('R2', '2026-09-15', 100), $remito('R1', '2026-09-10', 100)],
    [$factura('B1', '2026-09-20', 100)], $corte);
chequear('dos remitos, mismo importe, una factura: queda pendiente el más nuevo', ['R2'], $nros($p));
chequear('y sin factura', '', $p[0]['como'] ?? null);

$p = $R::clasificar([$remito('R1', '2026-09-10', 100), $remito('R2', '2026-09-15', 100)],
    [$factura('B2', '2026-10-10', 100), $factura('B1', '2026-09-20', 100)], $corte);
chequear('dos remitos y dos facturas: el viejo toma la primera, el nuevo la segunda', ['R2', 'B2', 'Por importe'],
    [$p[0]['remito'] ?? null, $p[0]['facturaNro'] ?? null, $p[0]['como'] ?? null]);
chequear('a igual fecha, los remitos por número', ['R2'],
    $nros($R::clasificar([$remito('R2', '2026-09-10', 100), $remito('R1', '2026-09-10', 100)],
        [$factura('B1', '2026-09-20', 100)], $corte)));

$p = $R::clasificar([$remito('R1', '2026-09-10', 100), $remito('R2', '2026-09-15', 100)],
    [$factura('B1', '2026-09-12', 100)], $corte);
chequear('la factura entre los dos remitos cubre al viejo', ['R2'], $nros($p));

$p = $R::clasificar([$remito('R1', '2026-09-20', 100), $remito('R2', '2026-09-05', 100)],
    [$factura('B1', '2026-09-10', 100)], $corte);
chequear('el remito viejo toma la factura aunque el nuevo no pudiera usarla', ['R1'], $nros($p));

chequear('otro cliente no lo cubre', ['R1'],
    $nros($R::clasificar([$remito('R1', '2026-09-10', 100)], [$factura('B1', '2026-09-20', 100, 'F00002')], $corte)));
chequear('un centavo de diferencia no lo cubre', ['R1'],
    $nros($R::clasificar([$remito('R1', '2026-09-10', 100.00)], [$factura('B1', '2026-09-20', 100.01)], $corte)));
chequear('el importe se compara exacto, sin error de punto flotante', [],
    $R::clasificar([$remito('R1', '2026-09-10', 0.1 + 0.2)], [$factura('B1', '2026-09-20', 0.3)], $corte));

$p = $R::clasificar([$remito('R1', '2026-09-10', 0)], [$factura('B1', '2026-09-20', 0)], $corte);
chequear('importe 0: no se empareja, sale sin factura', ['R1', '', 0.0],
    [$p[0]['remito'] ?? null, $p[0]['como'] ?? null, $p[0]['importe'] ?? null]);

// 3. Sin factura
$p = $R::clasificar([$remito('R1', '2026-09-10', 100)], [], $corte);
chequear('sin factura: pendiente, sin datos de factura', ['', '', ''],
    [$p[0]['facturaNro'], $p[0]['facturaFecha'], $p[0]['como']]);
chequear('la fila tiene las columnas del detalle', RemitosPendientes::COLUMNAS, array_keys($p[0]));

$p = $R::clasificar([$remito('R3', '2026-09-20', 100), $remito('R1', '2026-09-01', 50), $remito('R2', '2026-09-01', 70)], [], $corte);
chequear('salen por fecha y número de remito', ['R1', 'R2', 'R3'], $nros($p));

seccion('la factura vinculada en Tango no entra al pozo');

$pozo = RemitosPendientes::sqlPozo(0);
chequear('el pozo excluye las facturas de GVA54', true,
    (bool) preg_match('/NOT EXISTS \(\s*SELECT 1 FROM GVA54 x WITH \(NOLOCK\)\s*WHERE x\.T_COMP_V = A\.T_COMP AND x\.N_COMP = A\.N_COMP/', $pozo));
chequear('el pozo es sólo de FAC', true, strpos($pozo, "A.T_COMP = 'FAC'") !== false);
chequear('el pozo va hasta hoy, no hasta el corte', true,
    strpos($pozo, 'A.FECHA_EMIS < DATEADD(DAY, 1, @HOY)') !== false);
chequear('el importe del pozo es sin promociones', true, strpos($pozo, 'B.PROMOCION = 0') !== false);

seccion('resumen');

$filas = $R::clasificar([
    $remito('R1', '2026-09-01', 100, [], 'M00001'),
    $remito('R2', '2026-09-05', 300, [$factura('A2', '2026-10-01')], 'F00001'),
    $remito('R3', '2026-08-20', 50, [], 'F00001'),
    $remito('R4', '2026-09-10', 200, [], 'L00001')
], [$factura('B4', '2026-10-02', 200, 'L00001')], $corte);
$res = $R::resumir($filas);

chequear('cuatro pendientes', 4, $res['kpis']['pendientes']);
chequear('importe pendiente', 650.0, $res['kpis']['importePendiente']);
chequear('facturados después: el vinculado y el de por importe', [2, 500.0],
    [$res['kpis']['facturadosDespues'], $res['kpis']['importeFacturadoDespues']]);
chequear('sin factura', [2, 150.0], [$res['kpis']['sinFactura'], $res['kpis']['importeSinFactura']]);
chequear('clientes con pendientes', 3, $res['kpis']['clientes']);
chequear('por tipo, en el orden F, L, M', ['Franquicias', 'Gestión asistida', 'Mayoristas'], array_column($res['tipos'], 'tipo'));
chequear('importe por tipo', [350.0, 200.0, 100.0], array_column($res['tipos'], 'importe'));
chequear('el total es la suma de los tipos', $res['total']['importe'], round(array_sum(array_column($res['tipos'], 'importe')), 2));
chequear('por cliente, de mayor a menor importe', ['F00001', 'L00001', 'M00001'], array_column($res['clientes'], 'cliente'));
chequear('el remito más viejo del cliente', '2026-08-20', $res['clientes'][0]['masViejo']);
chequear('cuántos ya se facturaron después, por cliente', [1, 1, 0], array_column($res['clientes'], 'facturadosDespues'));
chequear('a igual importe, por código', ['F00002', 'F00003'], array_column($R::resumir($R::clasificar([
    $remito('R1', '2026-09-01', 100, [], 'F00003'), $remito('R2', '2026-09-01', 100, [], 'F00002')
], [], $corte))['clientes'], 'cliente'));
chequear('sin pendientes: todo en cero y sin tipos', [0, [], []],
    [$R::resumir([])['kpis']['pendientes'], $R::resumir([])['tipos'], $R::resumir([])['clientes']]);

seccion('leyenda y filtros en palabras');

$f = ['desde' => '2026-08-01', 'hasta' => '2026-09-30', 'tipo' => 'M', 'clientes' => ['M00001']];
chequear('la leyenda', 'Remitos del 01/08/2026 al 30/09/2026, pendientes de facturar al 30/09/2026',
    $R::leyenda($f, '2026-10-21'));
chequear('si el Hasta es hoy, lo aclara', true,
    strpos($R::leyenda(['hasta' => '2026-10-21'] + $f, '2026-10-21'), 'al día de hoy') !== false);
chequear('los filtros en palabras', [
    'Período (fecha de remito): 01/08/2026 al 30/09/2026',
    'Fecha de corte: 30/09/2026',
    'Tipo de cliente: Mayoristas',
    'Cliente: M00001 MAYORISTA UNO'
], $R::filtrosEnTexto($f, ['M00001' => 'MAYORISTA UNO']));
chequear('sin tipo ni clientes: todos', ['Tipo de cliente: Todos', 'Cliente: Todos'],
    array_slice($R::filtrosEnTexto(['tipo' => '', 'clientes' => []] + $f), 2));

seccion('la consulta');

$sql0 = RemitosPendientes::sqlRemitos(0);
$sql2 = RemitosPendientes::sqlRemitos(2);
$par = function ($n) use ($R) {
    return count($R::parametros('2026-08-01', '2026-09-30', ['tipo' => '', 'clientes' => array_fill(0, $n, 'F00001')]));
};

chequear('un "?" por parámetro, sin clientes', $par(0), substr_count($sql0, '?'));
chequear('un "?" por parámetro, con dos clientes', $par(2), substr_count($sql2, '?'));
chequear('el pozo también', $par(2), substr_count(RemitosPendientes::sqlPozo(2), '?'));
chequear('la lista de clientes también', $par(0), substr_count(RemitosPendientes::sqlClientes(), '?'));
chequear('el universo del SP', true, strpos($sql0, "s.TCOMP_IN_S = 'RE'") !== false
    && strpos($sql0, "s.N_COMP LIKE 'R%'") !== false && strpos($sql0, "s.COD_PRO_CL LIKE '[FML]%'") !== false
    && strpos($sql0, "s.COD_PRO_CL <> 'FRUSH'") !== false);
chequear('los anulados se excluyen siempre', true, strpos($sql0, "s.ESTADO_MOV <> 'A'") !== false);
chequear('filtra por la fecha del remito, y sin GVA106 por FECHA_MOV', true,
    strpos($sql0, 'COALESCE(g.FECHA_REM, CAST(s.FECHA_MOV AS DATE)) AS FECHA_REM') !== false
    && strpos($sql0, 'R.FECHA_REM BETWEEN @DESDE AND @HASTA') !== false);
chequear('el importe del remito es sin promociones', true,
    strpos($sql0, 'SUM(b.CANTIDAD * b.PRECIO_REM) AS IMPORTE') !== false && strpos($sql0, 'b.PROMOCION = 0') !== false);
chequear('las vinculadas por T_COMP_V + N_COMP, sólo FAC', true,
    strpos($sql0, 'a.T_COMP = x.T_COMP_V AND a.N_COMP = x.N_COMP') !== false
    && strpos($sql0, "x.T_COMP_V = 'FAC'") !== false);
chequear('los importes como DECIMAL(18,2), como el SP', 2, substr_count($sql0 . RemitosPendientes::sqlPozo(0), 'AS DECIMAL(18,2))'));

$clase = codigoSinComentarios($base . '/Class/RemitosPendientes.php');
chequear('las consultas no reciben filtros: no pueden concatenar valores', true,
    strpos($clase, 'public static function sqlRemitos($cantClientes)') !== false
    && strpos($clase, 'public static function sqlPozo($cantClientes)') !== false
    && strpos($clase, 'public static function sqlClientes()') !== false);

$ctrl = codigoSinComentarios($base . '/Controller/RemitosController.php');
chequear('hoy no viene del front', false, (bool) preg_match('/\$_(POST|GET|REQUEST)\[\s*\'hoy/', $ctrl));
chequear('consultar y detalle validan con la misma función', 2, substr_count($ctrl, 'filtrosValidados($r, $hoy)'));
chequear('el usuario del Excel es el del padrón', true, strpos($ctrl, "'usuario' => \$usuario") !== false);

seccion('permisos');

chequear('la pestaña está en el Menu, después de valorización y antes de parámetros',
    ['valorizacion_stock', 'remitos_pendientes', 'parametros'], array_column(Menu::todas(), 'tab'));
chequear('sus cuatro acciones son lecturas de la pestaña', ['periodos', 'clientes', 'consultar', 'detalle'],
    array_keys(array_filter(AuthReportes::LECTURAS['Remitos'], function ($tabs) {
        return $tabs === ['remitos_pendientes'];
    })));
chequear('no tiene escrituras', false, isset(AuthReportes::ESCRITURAS['Remitos']));
chequear('la clave está en el script de permisos', true,
    strpos(file_get_contents($base . '/sql/reportes_permisos.sql'), "('ra.tab.remitos_pendientes',") !== false);
chequear('y no hay clave de edición', false, in_array('ra.editar.remitos_pendientes', AuthReportes::claves(), true));

seccion('contra la base (sólo lectura)');

if (!Pruebas::hayBase()) {
    Pruebas::saltear('sin conexión a la base');
} else {
    $conn = (new Conexion())->conectar('central');
    $rp = new RemitosPendientes($conn);
    $hoy = RemitosPendientes::hoy();

    // Un período cerrado: el contable anterior
    $f = RemitosPendientes::rangoPeriodo(RemitosPendientes::PERIODO_CONTABLE_ANTERIOR, $hoy) + ['tipo' => '', 'clientes' => []];
    $rem = $rp->remitos($f);
    $pozo = $rp->pozo($f, $hoy);
    $pend = RemitosPendientes::clasificar($rem, $pozo, $f['hasta']);

    chequear('hay remitos en el contable anterior', true, count($rem) > 0);
    chequear('todos con la fecha dentro del período', [], array_values(array_filter(array_column($rem, 'fecha'),
        function ($d) use ($f) {
            return $d < $f['desde'] || $d > $f['hasta'];
        })));
    chequear('un remito por fila', count($rem), count(array_unique(array_column($rem, 'remito'))));
    chequear('ninguna factura del pozo está vinculada a un remito del período', [],
        array_values(array_intersect(array_column($pozo, 'factura'), array_merge([], ...array_map(function ($r) {
            return array_column($r['facturas'], 'factura');
        }, $rem)))));
    chequear('los "facturado después" son posteriores al corte', [], array_values(array_filter($pend, function ($p) use ($f) {
        return $p['como'] !== '' && $p['facturaFecha'] <= $f['hasta'];
    })));
    chequear('el filtro de tipo deja sólo esos clientes', [], array_values(array_filter(array_column(
        $rp->clientes(['tipo' => 'M'] + $f), 'cliente'), function ($c) {
            return $c[0] !== 'M';
        })));

    $como = array_count_values(array_map(function ($p) {
        return $p['como'] === '' ? 'sin factura' : $p['como'];
    }, $pend));
    Pruebas::informar('contable anterior ' . $f['desde'] . ' > ' . $f['hasta'] . ': ' . count($rem) . ' remitos, '
        . count($pozo) . ' facturas en el pozo, ' . count($pend) . ' pendientes ' . json_encode($como, JSON_UNESCAPED_UNICODE));

    /* ---- Paridad con el SP: últimos 60 días, Hasta = hoy ----
       El SP filtra FECHA_MOV >= GETDATE() - 60 con hora, así que el primer
       día que entra es hoy - 59. Su SELECT se replica tal cual, sin el mail. */
    $desde = (new DateTime($hoy))->modify('-59 days')->format('Y-m-d');
    $nuestros = $rp->detalle(['desde' => $desde, 'hasta' => $hoy, 'tipo' => '', 'clientes' => []], $hoy);
    $sinFactura = [];

    foreach ($nuestros as $p) {
        if ($p['como'] === '') {
            $sinFactura[$p['remito']] = $p;
        }
    }

    $spSql = "
SET NOCOUNT ON;
WITH R AS (
    SELECT CAST(g106.FECHA_REM AS DATE) AS FECHA_REM, s.COD_PRO_CL, s.N_COMP,
           CAST(ISNULL(det.IMPORTE, 0) AS DECIMAL(18,2)) AS IMPORTE
    FROM STA14 s WITH (NOLOCK)
    INNER JOIN (SELECT FECHA_REM, TCOMP_IN_S, NCOMP_IN_S FROM GVA106 WITH (NOLOCK)
                GROUP BY TCOMP_IN_S, NCOMP_IN_S, FECHA_REM) g106
        ON s.TCOMP_IN_S = g106.TCOMP_IN_S AND s.NCOMP_IN_S = g106.NCOMP_IN_S
    LEFT JOIN GVA54 g54 WITH (NOLOCK) ON s.TCOMP_IN_S = g54.TCOMP_IN_S AND s.NCOMP_IN_S = g54.NCOMP_IN_S
    LEFT JOIN GVA12 g12 WITH (NOLOCK) ON g54.N_COMP = g12.N_COMP AND g12.COD_CLIENT LIKE '[FML]%'
    OUTER APPLY (SELECT SUM(b.CANTIDAD * b.PRECIO_REM) AS IMPORTE FROM STA20 b WITH (NOLOCK)
                 WHERE b.ID_STA14 = s.ID_STA14 AND b.PROMOCION = 0) det
    WHERE s.COD_PRO_CL LIKE '[FML]%' AND s.TCOMP_IN_S = 'RE' AND s.N_COMP LIKE 'R%'
      AND s.FECHA_MOV >= DATEADD(DAY, -60, GETDATE()) AND s.COD_PRO_CL <> 'FRUSH'
      AND g12.N_COMP IS NULL AND s.ESTADO_MOV <> 'A'
),
F AS (
    SELECT A.N_COMP, A.COD_CLIENT AS COD_PRO_CL, CAST(SUM(B.IMPORTE_GRAVADO) AS DECIMAL(18,2)) AS IMPORTE
    FROM GVA12 A WITH (NOLOCK) INNER JOIN GVA53 B WITH (NOLOCK) ON A.T_COMP = B.T_COMP AND A.N_COMP = B.N_COMP
    WHERE A.COD_CLIENT LIKE '[FML]%' AND A.FECHA_EMIS >= DATEADD(DAY, -60, CAST(GETDATE() AS DATE)) AND B.PROMOCION = 0
    GROUP BY A.N_COMP, A.COD_CLIENT
)
SELECT DISTINCT r.N_COMP, r.FECHA_REM, r.COD_PRO_CL, r.IMPORTE,
       CASE WHEN EXISTS (SELECT 1 FROM F WHERE F.COD_PRO_CL = r.COD_PRO_CL COLLATE Modern_Spanish_CI_AI
                                        AND F.IMPORTE = r.IMPORTE) THEN 0 ELSE 1 END AS PENDIENTE
FROM R r;";

    // Todo lo que el SP podría haber usado como factura, con lo que hace
    // falta para explicar cada diferencia
    $candSql = "
SET NOCOUNT ON;
SELECT A.T_COMP, A.N_COMP, A.COD_CLIENT, CAST(A.FECHA_EMIS AS DATE) AS FECHA_EMIS,
       CAST(SUM(B.IMPORTE_GRAVADO) AS DECIMAL(18,2)) AS IMPORTE,
       CASE WHEN EXISTS (SELECT 1 FROM GVA54 x WITH (NOLOCK) WHERE x.T_COMP_V = A.T_COMP AND x.N_COMP = A.N_COMP)
            THEN 1 ELSE 0 END AS VINCULADA
FROM GVA12 A WITH (NOLOCK) INNER JOIN GVA53 B WITH (NOLOCK) ON A.T_COMP = B.T_COMP AND A.N_COMP = B.N_COMP
WHERE A.COD_CLIENT LIKE '[FML]%' AND A.FECHA_EMIS >= DATEADD(DAY, -60, CAST(GETDATE() AS DATE)) AND B.PROMOCION = 0
GROUP BY A.T_COMP, A.N_COMP, A.COD_CLIENT, CAST(A.FECHA_EMIS AS DATE);";

    $leer = function ($sql, $params = []) use ($conn) {
        $stmt = sqlsrv_query($conn, $sql, $params, ['QueryTimeout' => 300]);
        $filas = [];

        while ($stmt && ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            foreach ($r as $k => $v) {
                $r[$k] = $v instanceof DateTime ? $v->format('Y-m-d') : (is_string($v) ? trim($v) : $v);
            }
            $filas[] = $r;
        }

        return $filas;
    };

    $sp = [];

    foreach ($leer($spSql) as $r) {
        $sp[$r['N_COMP']] = $r;
    }

    $spPend = array_filter($sp, function ($r) {
        return (int) $r['PENDIENTE'] === 1;
    });
    $candidatas = $leer($candSql);

    $motivos = [];
    $sinExplicar = [];
    $anotar = function ($motivo, $remito) use (&$motivos) {
        $motivos[$motivo][] = $remito;
    };

    // Pendientes para el reporte y no para el SP
    foreach ($sinFactura as $nro => $p) {
        if (isset($spPend[$nro])) {
            continue;
        }

        if (!isset($sp[$nro])) {
            $tiene106 = $leer("SELECT COUNT(*) AS N FROM GVA106 g WITH (NOLOCK) JOIN STA14 s WITH (NOLOCK)
                ON s.TCOMP_IN_S = g.TCOMP_IN_S AND s.NCOMP_IN_S = g.NCOMP_IN_S
                WHERE s.TCOMP_IN_S = 'RE' AND s.N_COMP = ?", [$nro])[0]['N'] > 0;

            if (!$tiene106) {
                $anotar('sin GVA106: el reporte usa FECHA_MOV, el SP lo deja afuera', $nro);
            } else {
                $sinExplicar[] = $nro . ' (fuera del universo del SP)';
            }
            continue;
        }

        $cents = RemitosPendientes::centavos($p['importe']);
        $mismas = array_filter($candidatas, function ($c) use ($p, $cents) {
            return $c['COD_CLIENT'] === $p['cliente'] && RemitosPendientes::centavos($c['IMPORTE']) === $cents;
        });
        $fac = array_filter($mismas, function ($c) {
            return $c['T_COMP'] === RemitosPendientes::T_COMP_FACTURA;
        });
        $libres = array_filter($fac, function ($c) {
            return (int) $c['VINCULADA'] === 0;
        });
        $posteriores = array_filter($libres, function ($c) use ($p) {
            return $c['FECHA_EMIS'] >= $p['fecha'];
        });

        if ($cents === 0) {
            $anotar('importe 0: no se empareja', $nro);
        } elseif (!$mismas) {
            $sinExplicar[] = $nro . ' (el SP no tiene con qué emparejarlo)';
        } elseif (!$fac) {
            $anotar('sólo FAC: el SP lo dio por facturado con ' . implode('/', array_unique(array_column($mismas, 'T_COMP'))), $nro);
        } elseif (!$libres) {
            $anotar('la factura del mismo importe ya está vinculada a otro remito', $nro);
        } elseif (!$posteriores) {
            $anotar('la factura del mismo importe es anterior al remito', $nro);
        } else {
            $anotar('uno a uno: la factura cubrió otro remito', $nro);
        }
    }

    // Pendientes para el SP y no para el reporte
    foreach ($spPend as $nro => $r) {
        if (isset($sinFactura[$nro])) {
            continue;
        }

        $nuestro = array_values(array_filter($rem = $rp->remitos(['desde' => $desde, 'hasta' => $hoy, 'tipo' => '',
            'clientes' => [$r['COD_PRO_CL']]]), function ($x) use ($nro) {
                return $x['remito'] === $nro;
            }));

        if (!$nuestro) {
            $sinExplicar[] = $nro . ' (el reporte no lo tiene)';
        } elseif ($nuestro[0]['facturas']) {
            $anotar('vinculado por T_COMP + N_COMP (el SP une sólo por N_COMP)', $nro);
        } else {
            $sinExplicar[] = $nro . ' (el reporte lo dio por facturado por importe y el SP no)';
        }
    }

    chequear('paridad con el SP: toda diferencia tiene un motivo', [], $sinExplicar);
    Pruebas::informar('paridad ' . $desde . ' > ' . $hoy . ': SP ' . count($spPend) . ' pendientes, reporte '
        . count($sinFactura) . ' sin factura, en los dos ' . count(array_intersect_key($spPend, $sinFactura)));

    foreach ($motivos as $motivo => $remitos) {
        Pruebas::informar('  ' . count($remitos) . ' ' . $motivo . ': ' . implode(', ', array_slice($remitos, 0, 5))
            . (count($remitos) > 5 ? '…' : ''));
    }
}

seccion('la pantalla');

$js = codigoSinComentarios($base . '/Js/remitos.js');
$tab = file_get_contents($base . '/Tabs/remitos_pendientes.php');

chequear('los filtros y el botón', 6,
    preg_match_all('/id="(raRemPeriodo|raRemDesde|raRemHasta|raRemTipo|raRemClienteBtn|raRemConsultar)"/', $tab));
chequear('el Hasta es la fecha de corte', true, strpos($tab, 'Hasta (fecha de corte)') !== false);
chequear('los tipos de cliente', 3, preg_match_all('/<option value="[FLM]">/', $tab));
chequear('cambiar un filtro no consulta: sólo el botón llama a consultar()', 1, substr_count($js, 'consultar)'));
chequear('los rangos los da el servidor: el JS no calcula fechas', false,
    (bool) preg_match('/getMonth|getFullYear|new Date\(/', $js));
chequear('las opciones salen de la acción periodos', true, strpos($js, "Reportes.post('Remitos', 'periodos')") !== false);
chequear('tocar una fecha a mano pasa a Personalizado', true,
    (bool) preg_match("/'change', '#raRemDesde, #raRemHasta', function \(\) \{\s*\\$\('#raRemPeriodo'\)\.val\(PERSONALIZADO\)/", $js));
chequear('Personalizado es el mismo código que en PHP', true,
    strpos($js, "var PERSONALIZADO = '" . RemitosPendientes::PERIODO_PERSONALIZADO . "';") !== false);
chequear('el Hasta no puede pasar de hoy', true, strpos($js, "attr('max', hoy)") !== false);
chequear('los clientes salen del período y el tipo', true,
    strpos($js, "Reportes.post('Remitos', 'clientes', { desde: f.desde, hasta: f.hasta, tipo: f.tipo })") !== false);
chequear('las tarjetas: pendientes, facturados después, sin factura y clientes', 4,
    preg_match_all("/kpi\('(Pendientes al |Ya facturados después|Siguen sin factura hoy|Clientes con pendientes)/", $js));
chequear('las dos tablas del resumen', true,
    strpos($tab, 'id="raRemTipos"') !== false && strpos($tab, 'id="raRemClientes"') !== false);
chequear('un mensaje si no hay pendientes', true, strpos($tab, 'No hay remitos pendientes') !== false);

seccion('el Excel');

$menu = Menu::buscar('remitos_pendientes');
chequear('SheetJS, la misma versión que la valorización', Menu::buscar('valorizacion_stock')['libs'], $menu['libs']);
chequear('usa las funciones compartidas de excel.js, cargado antes', ['excel.js', 'remitos.js'], $menu['js']);
chequear('el botón arranca deshabilitado', true, (bool) preg_match('/id="raRemExcel" disabled/', $tab));
chequear('se habilita al consultar, si hay pendientes', true, strpos($js, "\$('#raRemExcel').prop('disabled', !hay)") !== false);
chequear('la descarga usa los filtros de la última consulta', true,
    strpos($js, "Reportes.post('Remitos', 'detalle', ultima.filtros)") !== false);
chequear('el nombre: Remitos_Pendientes_AAAAMMDD_AAAAMMDD.xlsx', true,
    strpos($js, "'Remitos_Pendientes_' + r.filtros.desde.replace(/-/g, '') + '_'\n            + r.filtros.hasta.replace(/-/g, '') + '.xlsx'") !== false);
chequear('las dos hojas', true,
    strpos($js, "hojaDetalle(r), 'Detalle')") !== false && strpos($js, "hojaResumen(r), 'Resumen')") !== false);
chequear('las columnas del detalle', true, strpos($js, "['Fecha remito', 'Tipo de cliente', 'Código cliente', "
    . "'Razón social', 'Remito', 'Importe neto',\n            'Días al corte', 'Facturado después: Nº factura', "
    . "'Facturado después: fecha', 'Cómo se identificó']") !== false);
chequear('el detalle sigue el orden de COLUMNAS', true, strpos($js, 'aoa.push([serie(f[c.fecha]), f[c.tipo], f[c.cliente], '
    . 'f[c.razonSocial], f[c.remito], f[c.importe],' . "\n" . '                f[c.diasAlCorte], f[c.facturaNro], '
    . 'serie(f[c.facturaFecha]), f[c.como]]);') !== false);
chequear('importes con miles y 2 decimales', true, strpos($js, "var FORMATO_IMPORTE = '#,##0.00';") !== false);
chequear('fechas como fechas: remito, factura y remito más viejo', 3, substr_count($js, ', FORMATO_FECHA);'));
chequear('autofiltro en la fila de títulos, las diez columnas', true, strpos($js, 'Excel.autofiltro(ws, fila0, ultimaFila, 9)') !== false);
chequear('el encabezado: período, corte, filtros, usuario y hora', true,
    strpos($js, "Excel.encabezado('Remitos pendientes de facturar — LAKERS SA', [r.leyenda].concat(r.filtrosTexto),\n            r.usuario, r.generado)") !== false);
chequear('la hoja Resumen tiene las dos tablas', true,
    strpos($js, "aoa.push(['Resumen por tipo de cliente'])") !== false && strpos($js, "aoa.push(['Resumen por cliente'])") !== false);
