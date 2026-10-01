<?php
/**
 * La cuenta de la estimación de PCI, del lado del servidor.
 *
 * QUÉ FIJA. CalculoEstimacion es la copia en PHP de calcularTodosLosConceptos()
 * de js/editar-estimacion.js, y las dos tienen que dar lo mismo. Como el JS no
 * se puede correr acá, los números esperados están ESCRITOS A MANO, con la
 * cuenta al lado, siguiendo el JS paso por paso: si esta prueba falla, una de
 * las dos implementaciones se movió y hay que mover la otra.
 *
 * Las rarezas del front están fijadas COMO RAREZAS: la prueba falla si alguien
 * las corrige de un solo lado. Corregirlas es una decisión aparte, en los dos
 * archivos a la vez.
 *
 * Sin base: todo se arma a mano.
 */

require_once __DIR__ . '/../class/CalculoEstimacion.php';
require_once __DIR__ . '/../class/AlicuotasVigencia.php';
require_once __DIR__ . '/../class/estimacionCostos.php';

/** El padrón de central tal como lo devuelve la base: decimales como string */
function padronCentral() {
    return [
        ['ID_CE' => 1,  'CONCEPTO' => 'Flete',            'TIPO_VALOR' => 'I', 'VALOR_DEFAULT_1' => '5000.0000', 'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 2,  'CONCEPTO' => 'Seguro',           'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => '.0005',     'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 3,  'CONCEPTO' => 'Derechos',         'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => '.2000',     'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 4,  'CONCEPTO' => 'Tasa estadistica', 'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => '.0300',     'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 5,  'CONCEPTO' => 'IVA General',      'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => '.2100',     'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 6,  'CONCEPTO' => 'IVA Adicional',    'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => '.2000',     'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 7,  'CONCEPTO' => 'IIGG',             'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => '.0600',     'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 8,  'CONCEPTO' => 'IIBB',             'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => '.0450',     'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 9,  'CONCEPTO' => 'SIM',              'TIPO_VALOR' => 'I', 'VALOR_DEFAULT_1' => '10.0000',   'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 10, 'CONCEPTO' => 'Antidumping',      'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => null,        'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 11, 'CONCEPTO' => 'Despachante',      'TIPO_VALOR' => 'I', 'VALOR_DEFAULT_1' => '450.0000',  'VALOR_DEFAULT_2' => '120.0000', 'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 12, 'CONCEPTO' => 'Terminal',         'TIPO_VALOR' => 'I', 'VALOR_DEFAULT_1' => '1800.0000', 'VALOR_DEFAULT_2' => null,       'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 13, 'CONCEPTO' => 'Suma asegurada',   'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => '.3500',     'VALOR_DEFAULT_2' => '.3000',    'ID_REF_CONCEPTO' => null],
    ];
}

/** Un subconjunto del padrón de uy, con los IDs reales de esa base */
function padronUruguay() {
    return [
        ['ID_CE' => 16, 'CONCEPTO' => 'Flete',              'TIPO_VALOR' => 'I', 'VALOR_DEFAULT_1' => '313.0000', 'VALOR_DEFAULT_2' => null, 'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 17, 'CONCEPTO' => 'Seguro',             'TIPO_VALOR' => 'I', 'VALOR_DEFAULT_1' => '40.0000',  'VALOR_DEFAULT_2' => null, 'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 19, 'CONCEPTO' => 'IMADUNI',            'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => '.1018',    'VALOR_DEFAULT_2' => null, 'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 22, 'CONCEPTO' => 'TIMBRE PROFESIONAL', 'TIPO_VALOR' => 'I', 'VALOR_DEFAULT_1' => '7.0010',   'VALOR_DEFAULT_2' => null, 'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 24, 'CONCEPTO' => 'IVA',                'TIPO_VALOR' => 'P', 'VALOR_DEFAULT_1' => '.2740',    'VALOR_DEFAULT_2' => null, 'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 31, 'CONCEPTO' => 'Despachante',        'TIPO_VALOR' => 'I', 'VALOR_DEFAULT_1' => '290.0000', 'VALOR_DEFAULT_2' => null, 'ID_REF_CONCEPTO' => null],
        ['ID_CE' => 32, 'CONCEPTO' => 'Terminal',           'TIPO_VALOR' => 'I', 'VALOR_DEFAULT_1' => '50.0000',  'VALOR_DEFAULT_2' => null, 'ID_REF_CONCEPTO' => null],
    ];
}

/** Las filas del resultado por ID_CE */
function porId($resultado) {
    $out = [];
    foreach ($resultado['filas'] as $f) {
        $out[$f['id_ce']] = $f;
    }

    return $out;
}

/* ======================================================================
   ARGENTINA, DESPACHANTE LAFFITTE
   ====================================================================== */
seccion('Argentina con Laffitte: FOB 10.000, la pantalla recién abierta');

$conceptos = EstimacionCostos::ajustarDespachante(padronCentral(), 'Laffitte');
$r = CalculoEstimacion::calcular(['entorno' => 'central', 'fob' => '10000.00', 'conceptos' => $conceptos]);
$f = porId($r);

chequear('es la rama argentina', 'AR', $r['rama']);
chequear('se graban los trece conceptos', 13, count($r['filas']));
chequear('en el orden de la pantalla', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13],
    array_column($r['filas'], 'id_ce'));

// flete = el parámetro, porque sin estimación guardada el importe inicial es param1
chequear('flete: el importe inicial es el parámetro', 5000.0, $f[1]['importe']);
// seguro = (10000 + 5000) * 0,0005
chequear('seguro: (FOB + flete) * 0,05%', 7.5, $f[2]['importe']);
// CIF = 10000 + 5000 + 7,5
chequear('CIF', 15007.5, $r['totales']['CIF']);
chequear('derechos: CIF * 20%', 3001.5, $f[3]['importe']);
chequear('tasa estadística: CIF * 3%', 450.225, $f[4]['importe']);
// base = 15007,5 + 3001,5 + 450,225
chequear('base imponible: CIF + derechos + tasa', 18459.225, $r['totales']['BASE_IMPONIBLE']);
chequear('IVA general: base * 21%', 3876.43725, $f[5]['importe']);
chequear('IVA adicional: base * 20%', 3691.845, $f[6]['importe']);
chequear('IIGG: base * 6%', 1107.5535, $f[7]['importe']);
chequear('IIBB: base * 4,5%', 830.665125, $f[8]['importe']);
chequear('SIM: el importe fijo', 10.0, $f[9]['importe']);
chequear('antidumping: sin parámetro arranca en cero', 0.0, $f[10]['importe']);
// 3001,5 + 450,225 + 3876,43725 + 3691,845 + 1107,5535 + 830,665125 + 10 + 0
chequear('total nacionalización: conceptos 3 a 10, sin el seguro', 12968.225875,
    $r['totales']['TOTAL_NACIONALIZACION']);
// ((10000 * 0,01) + 450) * 1,21
chequear('despachante Laffitte: ((FOB * 1%) + 450) * 1,21', 665.5, $f[11]['importe']);
chequear('terminal: el importe fijo', 1800.0, $f[12]['importe']);
chequear('total cashflow: nacionalización + despachante + terminal', 15433.725875,
    $r['totales']['TOTAL_CASHFLOW']);
// ((10000 + 5000) * 1,35) * 1,30
chequear('suma asegurada: ((FOB + flete) * 1,35) * 1,30', 26325.0, $f[13]['importe']);

seccion('Argentina con Laffitte: lo que se graba');

$grabar = porIdFilas(EstimacionCostos::filasParaGrabar($r, $conceptos));

function porIdFilas($filas) {
    $out = [];
    foreach ($filas as $x) {
        $out[$x['id_ce']] = $x;
    }

    return $out;
}

chequear('el parámetro del seguro', 0.0005, $grabar[2]['valor_default_1']);
chequear('un parámetro en null se graba null (parseFloat || null)', null, $grabar[10]['valor_default_1']);
chequear('el parámetro 2 en null también', null, $grabar[1]['valor_default_2']);
chequear('el despachante lleva el honorario de Laffitte', '450.0000', $grabar[11]['valor_default_1']);
chequear('y su parámetro 2 en null', null, $grabar[11]['valor_default_2']);
chequear('la suma asegurada guarda sus dos parámetros', [0.35, 0.3],
    [$grabar[13]['valor_default_1'], $grabar[13]['valor_default_2']]);

/* ======================================================================
   ARGENTINA, DESPACHANTE FARRE
   ====================================================================== */
seccion('Argentina con Farre: el honorario sale del parámetro 2');

$conceptosFarre = EstimacionCostos::ajustarDespachante(padronCentral(), 'Farre');
$r = CalculoEstimacion::calcular(['entorno' => 'central', 'fob' => '10000.00', 'conceptos' => $conceptosFarre]);
$f = porId($r);

// ((10000 * 0,01) + 120) * 1,21
chequear('despachante Farre: ((FOB * 1%) + 120) * 1,21', 266.2, $f[11]['importe']);
chequear('el resto no cambia: total nacionalización', 12968.225875, $r['totales']['TOTAL_NACIONALIZACION']);
chequear('total cashflow con Farre', 15034.425875, $r['totales']['TOTAL_CASHFLOW']);

$grabar = porIdFilas(EstimacionCostos::filasParaGrabar($r, $conceptosFarre));
chequear('se graba el honorario de Farre como parámetro 1', '120.0000', $grabar[11]['valor_default_1']);
chequear('y el 2 en null', null, $grabar[11]['valor_default_2']);

seccion('el ajuste de despachante es el de cargarEstimacion.php');

$sinDespachante = EstimacionCostos::ajustarDespachante(padronCentral(), null);
chequear('sin despachante -los contenedores viejos- usa el parámetro 1', '450.0000',
    $sinDespachante[10]['VALOR_DEFAULT_1']);
chequear('y deja el 2 en null', null, $sinDespachante[10]['VALOR_DEFAULT_2']);
chequear('Farre se compara exacto, como en el controller: "FARRE" no es Farre', '450.0000',
    EstimacionCostos::ajustarDespachante(padronCentral(), 'FARRE')[10]['VALOR_DEFAULT_1']);
chequear('los demás conceptos no se tocan', '.3000', $sinDespachante[12]['VALOR_DEFAULT_2']);

/* ======================================================================
   LOS IMPORTES EDITABLES
   ====================================================================== */
seccion('Argentina: el recálculo conserva flete, SIM, antidumping y terminal');

$r = CalculoEstimacion::calcular([
    'entorno' => 'central', 'fob' => '10000.00', 'conceptos' => $conceptos,
    'editables' => [1 => '6000.00', 9 => '25.00', 10 => '100.00', 12 => '2000.00',
                    3 => '99999.00'],   // derechos NO es editable: se ignora
]);
$f = porId($r);

chequear('flete guardado', 6000.0, $f[1]['importe']);
chequear('el seguro se recalcula sobre el flete guardado: 16000 * 0,05%', 8.0, $f[2]['importe']);
// CIF = 10000 + 6000 + 8 = 16008; derechos = 16008 * 20%
chequear('derechos se recalcula aunque venga un importe', 3201.6, $f[3]['importe']);
chequear('SIM guardado', 25.0, $f[9]['importe']);
chequear('antidumping guardado', 100.0, $f[10]['importe']);
chequear('terminal guardada', 2000.0, $f[12]['importe']);
chequear('los editables están marcados', [1, 9, 10, 12],
    array_values(array_map(function ($x) { return $x['id_ce']; },
        array_filter($r['filas'], function ($x) { return $x['editable']; }))));
chequear('CalculoEstimacion::idsEditables() en central', [1, 9, 10, 12],
    CalculoEstimacion::idsEditables($conceptos, 'central'));

/* ======================================================================
   URUGUAY
   ====================================================================== */
seccion('Uruguay: todo sobre el CIF, despachante y terminal aparte');

$conceptosUy = EstimacionCostos::ajustarDespachante(padronUruguay(), 'Laffitte');
$r = CalculoEstimacion::calcular(['entorno' => 'uy', 'fob' => '10000.00', 'conceptos' => $conceptosUy]);
$f = porId($r);

chequear('es la rama uruguaya', 'UY', $r['rama']);
chequear('en el orden de la pantalla: flete, seguro, el resto, despachante, terminal',
    [16, 17, 19, 22, 24, 31, 32], array_column($r['filas'], 'id_ce'));
chequear('flete: el importe inicial', 313.0, $f[16]['importe']);
chequear('seguro de tipo I: el importe fijo, no un porcentaje', 40.0, $f[17]['importe']);
// CIF = 10000 + 313 + 40
chequear('CIF', 10353.0, $r['totales']['CIF']);
chequear('la base imponible es el CIF', 10353.0, $r['totales']['BASE_IMPONIBLE']);
chequear('IMADUNI: CIF * 10,18%', 1053.9354, $f[19]['importe']);
chequear('timbre: tipo I, el parámetro', 7.001, $f[22]['importe']);
chequear('IVA: CIF * 27,4%', 2836.722, $f[24]['importe']);
chequear('despachante: el parámetro, sin la fórmula argentina', 290.0, $f[31]['importe']);
chequear('terminal: el parámetro', 50.0, $f[32]['importe']);
// 1053,9354 + 7,001 + 2836,722
chequear('total nacionalización: todo menos flete, seguro, despachante y terminal', 3897.6584,
    $r['totales']['TOTAL_NACIONALIZACION']);
chequear('total cashflow', 4237.6584, $r['totales']['TOTAL_CASHFLOW']);

seccion('Uruguay: el recálculo conserva sólo el flete');

$r = CalculoEstimacion::calcular(['entorno' => 'uy', 'fob' => '10000.00', 'conceptos' => $conceptosUy,
    'editables' => [16 => '400.00', 32 => '999.00', 17 => '999.00']]);
$f = porId($r);
chequear('flete guardado', 400.0, $f[16]['importe']);
chequear('terminal vuelve al parámetro de la vigencia', 50.0, $f[32]['importe']);
chequear('seguro también', 40.0, $f[17]['importe']);
chequear('idsEditables() en uy es sólo el flete', [16], CalculoEstimacion::idsEditables($conceptosUy, 'uy'));

/* ======================================================================
   ID_REF_CONCEPTO
   ====================================================================== */
seccion('ID_REF_CONCEPTO: un porcentaje sobre otro concepto');

$conRef = padronUruguay();
// Un recargo del 10% sobre el IVA, que en el orden viene DESPUÉS del IVA.
$conRef[] = ['ID_CE' => 40, 'CONCEPTO' => 'RECARGO SOBRE IVA', 'TIPO_VALOR' => 'P',
             'VALOR_DEFAULT_1' => '.1000', 'VALOR_DEFAULT_2' => null, 'ID_REF_CONCEPTO' => 24];
$r = CalculoEstimacion::calcular(['entorno' => 'uy', 'fob' => '10000.00', 'conceptos' => $conRef]);
$f = porId($r);

chequear('va sobre el IVA ya calculado: 2836,722 * 10%', 283.6722, $f[40]['importe']);
chequear('y suma al total de nacionalización', 4181.3306, $r['totales']['TOTAL_NACIONALIZACION']);

/* ======================================================================
   LAS RAREZAS DEL FRONT, FIJADAS COMO RAREZAS
   ====================================================================== */
seccion('RAREZA 1: en central, un concepto con ID_CE > 13 manda todo a la rama de Uruguay');

$conNuevo = padronCentral();
$conNuevo[] = ['ID_CE' => 14, 'CONCEPTO' => 'Concepto nuevo', 'TIPO_VALOR' => 'P',
               'VALOR_DEFAULT_1' => '.0100', 'VALOR_DEFAULT_2' => null, 'ID_REF_CONCEPTO' => null];
$conNuevo = EstimacionCostos::ajustarDespachante($conNuevo, 'Laffitte');
$r = CalculoEstimacion::calcular(['entorno' => 'central', 'fob' => '10000.00', 'conceptos' => $conNuevo]);
$f = porId($r);

chequear('central con el concepto 14 es esUruguay', true, CalculoEstimacion::esUruguay($conNuevo, 'central'));
chequear('y sin él no', false, CalculoEstimacion::esUruguay(padronCentral(), 'central'));
chequear('la rama es la uruguaya', 'UY', $r['rama']);
// En la rama uruguaya el IVA general va sobre el CIF (15007,5), no sobre la base imponible.
chequear('el IVA general se calcula sobre el CIF: 15007,5 * 21%', 3151.575, $f[5]['importe']);
chequear('el despachante es el parámetro a secas, sin la fórmula argentina', 450.0, $f[11]['importe']);
chequear('el concepto nuevo, sobre el CIF', 150.075, $f[14]['importe']);
chequear('la suma asegurada sigue con su fórmula', 26325.0, $f[13]['importe']);

seccion('RAREZA 2: getConceptoParam2() da 0, no 1, con el parámetro 2 en null');

$sinParam2 = padronCentral();
$sinParam2[12]['VALOR_DEFAULT_2'] = null;
$r = CalculoEstimacion::calcular(['entorno' => 'central', 'fob' => '10000.00',
    'conceptos' => EstimacionCostos::ajustarDespachante($sinParam2, 'Laffitte')]);
// ((10000 + 5000) * 1,35) * (1 + 0) -- con el 1 que dice el comentario del JS daría 40500
chequear('suma asegurada sin parámetro 2: * (1 + 0)', 20250.0, porId($r)[13]['importe']);

seccion('RAREZA 3: ID_REF_CONCEPTO lee el valor del momento, y depende del orden');

$refAdelante = padronUruguay();
// Concepto 18: viene ANTES que el IVA (24) en el orden, así que lo encuentra
// sin calcular y lee su valor inicial: el parámetro crudo, 0,274.
$refAdelante[] = ['ID_CE' => 18, 'CONCEPTO' => 'SOBRE IVA ADELANTADO', 'TIPO_VALOR' => 'P',
                  'VALOR_DEFAULT_1' => '.5000', 'VALOR_DEFAULT_2' => null, 'ID_REF_CONCEPTO' => 24];
usort($refAdelante, function ($a, $b) { return $a['ID_CE'] - $b['ID_CE']; });
$r = CalculoEstimacion::calcular(['entorno' => 'uy', 'fob' => '10000.00', 'conceptos' => $refAdelante]);
chequear('lee el 0,274 del IVA todavía sin calcular: 0,274 * 50%', 0.137, porId($r)[18]['importe']);

seccion('la semántica de JS que la cuenta necesita');

chequear('parseFloat(".2000")', 0.2, CalculoEstimacion::parseFloatJs('.2000'));
chequear('parseFloat de un texto sin número es NaN', true, is_nan(CalculoEstimacion::parseFloatJs('abc')));
chequear('parseFloat(null) es NaN, no cero', true, is_nan(CalculoEstimacion::parseFloatJs(null)));
chequear('parseFloat("12abc")', 12.0, CalculoEstimacion::parseFloatJs('12abc'));
chequear('un FOB vacío da cero', 0.0,
    CalculoEstimacion::calcular(['entorno' => 'central', 'fob' => null, 'conceptos' => padronCentral()])['totales']['FOB']);

/* ======================================================================
   QUÉ CUENTA COMO DIFERENCIA AL RECALCULAR
   ====================================================================== */
seccion('diferencias(): qué se graba en un recálculo');

$calc = [
    ['id_ce' => 2, 'concepto' => 'Seguro',   'valor_default_1' => 0.0005, 'valor_default_2' => null, 'importe' => 26.754999999999],
    ['id_ce' => 3, 'concepto' => 'Derechos', 'valor_default_1' => 0.2,    'valor_default_2' => null, 'importe' => 3001.5],
];
$guard = [
    ['ID_CE' => 2, 'VALOR_DEFAULT_1' => '.000500', 'VALOR_DEFAULT_2' => null, 'IMPORTE' => '26.75'],
    ['ID_CE' => 3, 'VALOR_DEFAULT_1' => '.200000', 'VALOR_DEFAULT_2' => null, 'IMPORTE' => '3001.50'],
];
chequear('lo mismo con otra escala y redondeado por la base no es diferencia', [],
    EstimacionCostos::diferencias($calc, $guard));

$guard[1]['VALOR_DEFAULT_1'] = '.220000';
$d = EstimacionCostos::diferencias($calc, $guard);
chequear('un cambio de alícuota sola es diferencia', 1, count($d));
chequear('y dice qué columna', ['VALOR_DEFAULT_1'], array_keys($d[0]['campos']));
chequear('es un UPDATE', 'UPDATE', $d[0]['accion']);

$guard[1]['VALOR_DEFAULT_1'] = null;
chequear('NULL contra un valor también', 1, count(EstimacionCostos::diferencias($calc, $guard)));

$guard[1]['VALOR_DEFAULT_1'] = '.200000';
$guard[1]['IMPORTE'] = '3001.52';
$d = EstimacionCostos::diferencias($calc, $guard);
chequear('dos centavos de importe sí', ['IMPORTE'], array_keys($d[0]['campos']));

$d = EstimacionCostos::diferencias($calc, [$guard[0]]);
chequear('un concepto sin fila guardada se inserta', 'INSERT', $d[0]['accion']);
chequear('y es el que falta', 3, $d[0]['id_ce']);

seccion('cambioDisparaRecalculo(): sólo el FOB y la fecha de nacionalización');

$antes = ['VALOR_FOB_DOLAR' => '10000.00', 'FECHA_DESP_ADU' => '2026-11-10', 'DESPACHANTE' => 'Farre'];
chequear('nada cambió', false, EstimacionCostos::cambioDisparaRecalculo($antes, $antes));
chequear('el mismo FOB escrito distinto no dispara', false,
    EstimacionCostos::cambioDisparaRecalculo($antes, ['VALOR_FOB_DOLAR' => '10000'] + $antes));
chequear('cambiar el FOB dispara', true,
    EstimacionCostos::cambioDisparaRecalculo($antes, ['VALOR_FOB_DOLAR' => '10500.00'] + $antes));
chequear('cambiar la fecha de nacionalización dispara', true,
    EstimacionCostos::cambioDisparaRecalculo($antes, ['FECHA_DESP_ADU' => '2026-11-12'] + $antes));
chequear('la misma fecha en otro formato no dispara', false,
    EstimacionCostos::cambioDisparaRecalculo($antes, ['FECHA_DESP_ADU' => '10/11/2026'] + $antes));
chequear('cambiar sólo el despachante NO dispara', false,
    EstimacionCostos::cambioDisparaRecalculo($antes, ['DESPACHANTE' => 'Laffitte'] + $antes));
chequear('sin lectura previa no dispara', false,
    EstimacionCostos::cambioDisparaRecalculo(null, $antes));
