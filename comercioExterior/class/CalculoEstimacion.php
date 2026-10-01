<?php
/**
 * La cuenta de la estimación de PCI, del lado del servidor.
 *
 * ES LA MISMA CUENTA QUE calcularTodosLosConceptos() DE js/editar-estimacion.js
 * ---------------------------------------------------------------------------
 * Son DOS IMPLEMENTACIONES DE LA MISMA CUENTA y tienen que moverse juntas: si
 * se cambia una fórmula, un total o el orden en uno de los dos archivos, hay
 * que cambiarlo en el otro. El encabezado de calcularTodosLosConceptos() dice
 * lo mismo, y tests/test_estimacion_calculo.php fija los números de los dos
 * caminos con casos escritos a mano.
 *
 * POR QUÉ HACE FALTA UNA SEGUNDA COPIA
 * El cashflow de Finanzas -pestaña Crono Nacionalización- no mira CONFIRMADO:
 * lo que necesita es que la estimación EXISTA. Hasta ahora sólo existía si
 * alguien entraba a PCI y guardaba, porque la cuenta vivía únicamente en el
 * navegador. Generarla en el alta, y recalcularla cuando cambian el FOB o la
 * fecha de nacionalización, pide poder hacer la cuenta sin pantalla.
 *
 * QUÉ SE COPIÓ, Y CON QUÉ CRITERIO
 * La consigna fue portar la cuenta TAL CUAL: mismas fórmulas, mismo orden de
 * las sumas (en punto flotante a+b+c no siempre es c+b+a, y el importe se
 * guarda), mismos conceptos dentro y fuera de cada total. Lo que el navegador
 * resuelve leyendo el DOM se simula acá con un mapa de "filas": una fila por
 * concepto que la pantalla dibujaría, con su parámetro escondido y su
 * data-valor. Los getters de abajo devuelven lo mismo que sus homónimos del JS
 * cuando la fila existe Y cuando no existe, porque los dos casos dan distinto
 * -ver getConceptoParam2()-.
 *
 * SIN OVERRIDES. El servidor calcula siempre como la pantalla recién abierta
 * sin estimación guardada: en el alta no hay nada guardado, y en el recálculo
 * la decisión es que los overrides manuales de los conceptos calculados se
 * pisan. Lo único que se conserva son los importes EDITABLES -los que el JS lee
 * con getConceptoValorEditable()-, y entran por 'editables'.
 *
 * RAREZAS DEL FRONT COPIADAS A PROPÓSITO, NO CORREGIDAS
 * Están marcadas con "RAREZA COPIADA" donde ocurren. Son posibles errores, se
 * revisan aparte, y corregirlas acá haría que el servidor y la pantalla den
 * números distintos para el mismo contenedor, que es peor:
 *
 *   1. esUruguay es verdadero en central si cualquier concepto tiene
 *      ID_CE > 13. Con eso la rama argentina de "conceptos nuevos" no se
 *      ejecuta nunca: un concepto nuevo manda TODO el cálculo a la rama de
 *      Uruguay, donde Derechos, IVA y el resto se calculan sobre el CIF y no
 *      sobre la base imponible.
 *   2. getConceptoParam2() devuelve 0 -no 1- cuando el parámetro 2 es null,
 *      porque el input escondido se dibuja con `param2 || 0` y el string "0"
 *      es verdadero. Sólo da 1 cuando la fila no existe.
 *   3. ID_REF_CONCEPTO lee el valor que la fila referenciada tiene EN ESE
 *      MOMENTO de la pasada: si todavía no se calculó, lee su valor inicial
 *      (el parámetro crudo). El resultado depende del orden de los conceptos.
 *
 * ES PURA: no lee la base ni la sesión. Lo que necesita -FOB, conceptos con la
 * vigencia y el ajuste de despachante ya aplicados, entorno- se lo pasa
 * EstimacionCostos, que es quien lee.
 */
class CalculoEstimacion
{
    /**
     * Los IDs con los que arranca CONCEPTOS_ID en el JS. En Uruguay se pisan
     * por nombre; los que no se encuentran quedan con estos valores, que es lo
     * que hace el JS (y por eso SUMA_ASEGURADA vale 13 en una base que no lo
     * tiene: la fila no existe y todo lo que se le pide da cero).
     */
    const IDS_DEFAULT = [
        'FLETE'            => 1,
        'SEGURO'           => 2,
        'DERECHOS'         => 3,
        'TASA_ESTADISTICA' => 4,
        'IVA_GENERAL'      => 5,
        'IVA_ADICIONAL'    => 6,
        'IIGG'             => 7,
        'IIBB'             => 8,
        'SIM'              => 9,
        'ANTIDUMPING'      => 10,
        'DESPACHANTE'      => 11,
        'TERMINAL'         => 12,
        'SUMA_ASEGURADA'   => 13,
    ];

    /**
     * Qué conceptos la pantalla trata como importe EDITABLE en cada rama, o
     * sea los que lee con getConceptoValorEditable() y nunca calcula.
     *
     * Son los únicos cuyo importe guardado sobrevive a un recálculo. En
     * Uruguay es sólo el Flete: Terminal y el resto de los de tipo I se
     * recalculan como el parámetro 1 de la vigencia, porque así los trata el
     * loop de la rama uruguaya.
     */
    const EDITABLES = [
        'AR' => ['FLETE', 'SIM', 'ANTIDUMPING', 'TERMINAL'],
        'UY' => ['FLETE'],
    ];

    /**
     * La cuenta entera.
     *
     * @param array $entrada [
     *   'entorno'    => 'central'|'uy',
     *   'fob'        => VALOR_FOB_DOLAR tal como viene de la base,
     *   'conceptos'  => filas de EstimacionCostos::obtenerConceptos(), con la
     *                   vigencia y el ajuste de despachante ya aplicados, en el
     *                   orden en que las devuelve (ORDER BY ID_CE),
     *   'editables'  => [ID_CE => importe] opcional: el importe guardado de los
     *                   conceptos editables. El que no viene arranca con el
     *                   parámetro 1, que es lo que hace la pantalla sin
     *                   estimación guardada.
     * ]
     * @return array [
     *   'rama'    => 'AR'|'UY',
     *   'filas'   => [['id_ce','concepto','valor_default_1','valor_default_2',
     *                  'importe','editable'], ...] en el orden de la pantalla,
     *   'totales' => ['FOB','CIF','BASE_IMPONIBLE','TOTAL_NACIONALIZACION',
     *                 'TOTAL_CASHFLOW'],
     * ]
     */
    public static function calcular(array $entrada)
    {
        $conceptos = array_values(isset($entrada['conceptos']) ? $entrada['conceptos'] : []);
        $entorno   = isset($entrada['entorno']) ? $entrada['entorno'] : 'central';
        $editables = isset($entrada['editables']) && is_array($entrada['editables'])
            ? $entrada['editables'] : [];

        $esUruguay = self::esUruguay($conceptos, $entorno);
        $ids       = self::idsDeConceptos($conceptos, $esUruguay);
        $orden     = self::ordenVisualizacion($conceptos, $ids, $esUruguay);

        $c = new self($conceptos, $ids, $orden, $editables, $esUruguay);

        return $esUruguay ? $c->ramaUruguay($entrada['fob']) : $c->ramaArgentina($entrada['fob']);
    }

    /**
     * La condición del JS, con su rareza.
     *
     * RAREZA COPIADA (1): en central, cualquier concepto con ID_CE > 13 manda
     * el cálculo a la rama de Uruguay. Es como el JS detecta el entorno además
     * del #entorno de la página.
     */
    public static function esUruguay(array $conceptos, $entorno)
    {
        if ($entorno === 'uy') {
            return true;
        }

        foreach ($conceptos as $c) {
            if (intval($c['ID_CE']) > 13) {
                return true;
            }
        }

        return false;
    }

    /**
     * IDs de los conceptos editables de la rama que corresponde, ya
     * resueltos contra los IDs de esta base.
     *
     * @return int[]
     */
    public static function idsEditables(array $conceptos, $entorno)
    {
        $esUruguay = self::esUruguay($conceptos, $entorno);
        $ids = self::idsDeConceptos($conceptos, $esUruguay);

        $out = [];
        foreach (self::EDITABLES[$esUruguay ? 'UY' : 'AR'] as $clave) {
            $out[] = $ids[$clave];
        }

        return $out;
    }

    /**
     * mapName() del JS: el nombre del concepto a la clave de CONCEPTOS_ID.
     */
    public static function claveDelNombre($nombre)
    {
        $n = mb_strtolower(trim((string) $nombre), 'UTF-8');

        $mapa = [
            'flete'             => 'FLETE',
            'seguro'            => 'SEGURO',
            'derechos'          => 'DERECHOS',
            'tasa estadistica'  => 'TASA_ESTADISTICA',
            'tasa estadística'  => 'TASA_ESTADISTICA',
            'iva general'       => 'IVA_GENERAL',
            'iva adicional'     => 'IVA_ADICIONAL',
            'iigg'              => 'IIGG',
            'iibb'              => 'IIBB',
            'sim'               => 'SIM',
            'antidumping'       => 'ANTIDUMPING',
            'despachante'       => 'DESPACHANTE',
            'terminal'          => 'TERMINAL',
            'suma asegurada'    => 'SUMA_ASEGURADA',
        ];

        return isset($mapa[$n]) ? $mapa[$n] : null;
    }

    /**
     * CONCEPTOS_ID: los defaults, pisados por nombre sólo en la rama de
     * Uruguay. Si dos conceptos tienen el mismo nombre gana el último, como en
     * el forEach del JS.
     */
    private static function idsDeConceptos(array $conceptos, $esUruguay)
    {
        $ids = self::IDS_DEFAULT;

        if ($esUruguay) {
            foreach ($conceptos as $c) {
                $clave = self::claveDelNombre($c['CONCEPTO']);
                if ($clave !== null) {
                    $ids[$clave] = intval($c['ID_CE']);
                }
            }
        }

        return $ids;
    }

    /**
     * Los IDs de concepto de ORDEN_VISUALIZACION, en orden. Es lo que decide
     * qué conceptos tienen FILA -y por lo tanto qué se guarda-, igual que
     * generarFormularioConceptos(), que saltea los que no están en la base.
     */
    private static function ordenVisualizacion(array $conceptos, array $ids, $esUruguay)
    {
        $existe = [];
        foreach ($conceptos as $c) {
            $existe[intval($c['ID_CE'])] = true;
        }

        if ($esUruguay) {
            $orden = [$ids['FLETE'], $ids['SEGURO']];

            foreach ($conceptos as $c) {
                $clave = self::claveDelNombre($c['CONCEPTO']);
                if (!in_array($clave, ['FLETE', 'SEGURO', 'DESPACHANTE', 'TERMINAL', 'SUMA_ASEGURADA'], true)) {
                    $orden[] = intval($c['ID_CE']);
                }
            }

            $orden[] = $ids['DESPACHANTE'];
            $orden[] = $ids['TERMINAL'];
            if ($ids['SUMA_ASEGURADA']) {
                $orden[] = $ids['SUMA_ASEGURADA'];
            }
        } else {
            /* Los trece fijos, y los conceptos que no están entre ellos antes
               de Total Cashflow. En la rama argentina esa segunda parte no
               agrega nada nunca: un concepto fuera de 1..13 manda el cálculo a
               la otra rama (RAREZA COPIADA 1). Se escribe igual para que la
               copia sea la del JS y no una versión deducida. */
            $nuevos = [];
            foreach ($conceptos as $c) {
                if (!in_array(intval($c['ID_CE']), range(1, 13), true)) {
                    $nuevos[] = intval($c['ID_CE']);
                }
            }

            // Total Cashflow va entre Terminal (12) y Suma asegurada (13).
            $orden = array_merge(range(1, 12), $nuevos, [13]);
        }

        /* Sin el concepto en la base, la fila no se dibuja. Y un mismo ID dos
           veces sería la misma fila: jQuery lee siempre la primera. */
        $out = [];
        foreach ($orden as $id) {
            if (isset($existe[$id]) && !in_array($id, $out, true)) {
                $out[] = $id;
            }
        }

        return $out;
    }

    /* ======================================================================
       LAS "FILAS" DE LA PANTALLA
       ====================================================================== */

    /** @var array ID_CE => concepto */
    private $porId = [];

    /** @var array ID_CE => ['param1' => string, 'param2' => string, 'valor' => float] */
    private $filas = [];

    /** @var array CONCEPTOS_ID ya resuelto */
    private $ids;

    /** @var int[] IDs con fila, en el orden de la pantalla */
    private $orden;

    /** @var array Los conceptos en el orden en que llegaron */
    private $conceptos;

    /** @var int[] IDs editables de la rama */
    private $idsEditables = [];

    private function __construct(array $conceptos, array $ids, array $orden, array $editables, $esUruguay)
    {
        $this->conceptos = $conceptos;
        $this->ids = $ids;
        $this->orden = $orden;

        foreach (self::EDITABLES[$esUruguay ? 'UY' : 'AR'] as $clave) {
            $this->idsEditables[] = $ids[$clave];
        }

        foreach ($conceptos as $c) {
            $id = intval($c['ID_CE']);
            if (!isset($this->porId[$id])) {
                $this->porId[$id] = $c;
            }
        }

        foreach ($orden as $id) {
            $c = $this->porId[$id];
            $p1 = $c['VALOR_DEFAULT_1'];
            $p2 = $c['VALOR_DEFAULT_2'];

            /* El importe inicial de generarFormularioConceptos() sin estimación
               guardada: el parámetro 1, o cero. Para un editable con importe
               guardado, ese importe. */
            if (in_array($id, $this->idsEditables, true) && array_key_exists($id, $editables)
                && $editables[$id] !== null) {
                $valor = self::parseFloatJs($editables[$id]);
                $valor = is_nan($valor) ? 0.0 : $valor;
            } elseif ($p1 !== null) {
                $valor = self::parseFloatJs($p1);
                $valor = is_nan($valor) ? 0.0 : $valor;
            } else {
                $valor = 0.0;
            }

            $this->filas[$id] = [
                // El input escondido se dibuja con `${param || 0}`.
                'param1' => self::jsTruthy($p1) ? (string) $p1 : '0',
                'param2' => self::jsTruthy($p2) ? (string) $p2 : '0',
                'valor'  => $valor,
            ];
        }
    }

    /** getConceptoValorEditable(): parseFloat(data-valor) || 0 */
    private function valorEditable($id)
    {
        if (!isset($this->filas[$id])) {
            return 0.0;
        }

        return self::oCero($this->filas[$id]['valor']);
    }

    /** getConceptoParam1(): parseFloat(.param1) || 0 */
    private function param1($id)
    {
        if (!isset($this->filas[$id])) {
            return 0.0;
        }

        return self::oCero(self::parseFloatJs($this->filas[$id]['param1']));
    }

    /**
     * getConceptoParam2(): `param2 ? parseFloat(param2) : 1`.
     *
     * RAREZA COPIADA (2): con la fila dibujada, un parámetro 2 en null llega
     * como el string "0" -el input escondido se arma con `param2 || 0`- y "0"
     * es verdadero en JS, así que devuelve 0 y no el 1 que dice su comentario.
     * El 1 sólo sale cuando la fila no existe y .val() da undefined.
     */
    private function param2($id)
    {
        if (!isset($this->filas[$id])) {
            return 1.0;
        }

        $p2 = $this->filas[$id]['param2'];

        return ($p2 !== '') ? self::parseFloatJs($p2) : 1.0;
    }

    /**
     * getConceptoValorActual(): lo que la fila tiene en este momento.
     *
     * RAREZA COPIADA (3): si la fila referenciada todavía no se calculó en
     * esta pasada, lo que se lee es su valor inicial -el parámetro 1 crudo-,
     * así que el resultado depende del orden de los conceptos.
     */
    private function valorActual($id)
    {
        if (isset($this->filas[$id])) {
            return self::oCero($this->filas[$id]['valor']);
        }

        if ((string) $id === 'FOB') {
            return 0.0;   // el JS lee #valorFOB; ID_REF_CONCEPTO es INT y nunca llega acá
        }
        if ((string) $id === 'CIF') {
            return 0.0;   // ídem
        }

        return 0.0;
    }

    /** setConceptoValorCalculado(): sin overrides ni foco, escribe si hay fila */
    private function setCalculado($id, $valor)
    {
        if (isset($this->filas[$id])) {
            $this->filas[$id]['valor'] = $valor;
        }
    }

    private function concepto($id)
    {
        return isset($this->porId[$id]) ? $this->porId[$id] : null;
    }

    /* ======================================================================
       LAS DOS RAMAS, EN EL ORDEN DEL JS
       ====================================================================== */

    private function ramaUruguay($fobCrudo)
    {
        $ids = $this->ids;

        $fob = self::oCero(self::parseFloatJs($fobCrudo));

        $flete = $this->valorEditable($ids['FLETE']);

        $seguroParam = $this->param1($ids['SEGURO']);
        $conceptoSeguro = $this->concepto($ids['SEGURO']);
        if ($conceptoSeguro && $conceptoSeguro['TIPO_VALOR'] === 'P') {
            $seguro = ($fob + $flete) * $seguroParam;
        } else {
            $seguro = $seguroParam;   // importe fijo (ej: 40.00 en UY)
        }
        $this->setCalculado($ids['SEGURO'], $seguro);

        $cif = $fob + $flete + $seguro;

        $totalNac = 0.0;
        $despachante = 0.0;
        $terminal = 0.0;

        foreach ($this->conceptos as $c) {
            $idCe = intval($c['ID_CE']);

            if ($idCe === $ids['FLETE'] || $idCe === $ids['SEGURO']) {
                continue;
            }

            $param1 = $this->param1($idCe);

            if ($c['TIPO_VALOR'] === 'P') {
                $base = $cif;
                $idRef = isset($c['ID_REF_CONCEPTO']) ? $c['ID_REF_CONCEPTO'] : null;
                if (self::jsTruthy($idRef)) {
                    $base = $this->valorActual(intval($idRef));
                }
                $valor = $base * $param1;
            } else {
                $valor = $param1;
            }
            $this->setCalculado($idCe, $valor);

            if ($idCe === $ids['DESPACHANTE']) {
                $despachante = $valor;
            } elseif ($idCe === $ids['TERMINAL']) {
                $terminal = $valor;
            } elseif ($idCe === $ids['SUMA_ASEGURADA']) {
                // fuera de los totales
            } else {
                $totalNac += $valor;
            }
        }

        $totalCashflow = $totalNac + $despachante + $terminal;

        if ($ids['SUMA_ASEGURADA']) {
            $p1 = $this->param1($ids['SUMA_ASEGURADA']);
            $p2 = $this->param2($ids['SUMA_ASEGURADA']);
            $this->setCalculado($ids['SUMA_ASEGURADA'], (($fob + $flete) * (1 + $p1)) * (1 + $p2));
        }

        return $this->resultado('UY', [
            'FOB'                   => $fob,
            'CIF'                   => $cif,
            'BASE_IMPONIBLE'        => $cif,
            'TOTAL_NACIONALIZACION' => $totalNac,
            'TOTAL_CASHFLOW'        => $totalCashflow,
        ]);
    }

    private function ramaArgentina($fobCrudo)
    {
        $ids = $this->ids;

        $fob = self::oCero(self::parseFloatJs($fobCrudo));

        $flete = $this->valorEditable($ids['FLETE']);

        $seguro = ($fob + $flete) * $this->param1($ids['SEGURO']);
        $this->setCalculado($ids['SEGURO'], $seguro);

        $cif = $fob + $flete + $seguro;

        $derechos = $cif * $this->param1($ids['DERECHOS']);
        $this->setCalculado($ids['DERECHOS'], $derechos);

        $tasa = $cif * $this->param1($ids['TASA_ESTADISTICA']);
        $this->setCalculado($ids['TASA_ESTADISTICA'], $tasa);

        $base = $cif + $derechos + $tasa;

        $ivaGeneral = $base * $this->param1($ids['IVA_GENERAL']);
        $this->setCalculado($ids['IVA_GENERAL'], $ivaGeneral);

        $ivaAdicional = $base * $this->param1($ids['IVA_ADICIONAL']);
        $this->setCalculado($ids['IVA_ADICIONAL'], $ivaAdicional);

        $iigg = $base * $this->param1($ids['IIGG']);
        $this->setCalculado($ids['IIGG'], $iigg);

        $iibb = $base * $this->param1($ids['IIBB']);
        $this->setCalculado($ids['IIBB'], $iibb);

        $sim = $this->valorEditable($ids['SIM']);
        $antidumping = $this->valorEditable($ids['ANTIDUMPING']);

        // ID_CE 3 a 10, en el orden de suma del JS. El Seguro no entra.
        $totalNac = $derechos + $tasa + $ivaGeneral + $ivaAdicional + $iigg + $iibb + $sim + $antidumping;

        $despachante = (($fob * 0.01) + $this->param1($ids['DESPACHANTE'])) * 1.21;
        $this->setCalculado($ids['DESPACHANTE'], $despachante);

        $terminal = $this->valorEditable($ids['TERMINAL']);

        /* Conceptos nuevos. RAREZA COPIADA (1): en la rama argentina este loop
           no encuentra nada nunca, porque un concepto fuera de CONCEPTOS_ID
           tiene ID_CE > 13 y eso manda el cálculo a la rama de Uruguay. */
        $acumulado = 0.0;
        $idsFijos = array_values($ids);

        foreach ($this->conceptos as $c) {
            $idCe = intval($c['ID_CE']);
            if (in_array($idCe, $idsFijos, true)) {
                continue;
            }

            $param1 = $this->param1($idCe);

            if ($c['TIPO_VALOR'] === 'P') {
                $baseCalculo = $cif;
                $idRef = isset($c['ID_REF_CONCEPTO']) ? $c['ID_REF_CONCEPTO'] : null;
                if (self::jsTruthy($idRef)) {
                    $baseCalculo = $this->valorActual(intval($idRef));
                }
                $valor = $baseCalculo * $param1;
            } else {
                $valor = $param1;
            }
            $this->setCalculado($idCe, $valor);

            $acumulado += $valor;
        }

        $totalCashflow = $totalNac + $despachante + $terminal + $acumulado;

        $p1 = $this->param1($ids['SUMA_ASEGURADA']);
        $p2 = $this->param2($ids['SUMA_ASEGURADA']);
        $this->setCalculado($ids['SUMA_ASEGURADA'], (($fob + $flete) * (1 + $p1)) * (1 + $p2));

        return $this->resultado('AR', [
            'FOB'                   => $fob,
            'CIF'                   => $cif,
            'BASE_IMPONIBLE'        => $base,
            'TOTAL_NACIONALIZACION' => $totalNac,
            'TOTAL_CASHFLOW'        => $totalCashflow,
        ]);
    }

    /**
     * Lo que enviarEstimacion() mandaría al guardar: una entrada por fila, en
     * el orden de la pantalla.
     *
     * `parseFloat(param) || null`: un parámetro en cero se guarda como NULL,
     * igual que desde el navegador. El del DESPACHANTE lo vuelve a resolver
     * EstimacionCostos al grabar -como obtenerParametrosDespachante() en el
     * guardado de siempre-, así que lo que diga acá no es lo que queda.
     */
    private function resultado($rama, array $totales)
    {
        $filas = [];

        foreach ($this->orden as $id) {
            $f = $this->filas[$id];
            $p1 = self::oCero(self::parseFloatJs($f['param1']));
            $p2 = self::oCero(self::parseFloatJs($f['param2']));

            $filas[] = [
                'id_ce'           => $id,
                'concepto'        => $this->porId[$id]['CONCEPTO'],
                'valor_default_1' => ($p1 != 0) ? $p1 : null,
                'valor_default_2' => ($p2 != 0) ? $p2 : null,
                'importe'         => self::oCero($f['valor']),
                'editable'        => in_array($id, $this->idsEditables, true),
            ];
        }

        return [
            'rama'    => $rama,
            'filas'   => $filas,
            'totales' => $totales,
        ];
    }

    /* ======================================================================
       SEMÁNTICA DE JS
       ====================================================================== */

    /**
     * parseFloat() de JS: lee el número del principio del texto y devuelve
     * NaN si no hay ninguno. floatval() de PHP devolvería 0, que en esta
     * cuenta no es lo mismo: `parseFloat(x) || 0` y `param ? parseFloat(param)
     * : 1` distinguen los dos casos.
     */
    public static function parseFloatJs($v)
    {
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }
        if ($v === null || is_bool($v)) {
            return NAN;
        }

        if (preg_match('/^\s*([+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?)/', (string) $v, $m)) {
            return (float) $m[1];
        }

        return NAN;
    }

    /** `x || 0` sobre un número: NaN y 0 dan 0 */
    private static function oCero($n)
    {
        return (is_nan($n) || $n == 0) ? 0.0 : (float) $n;
    }

    /** Verdad de JS para lo que puede llegar de la base: null, '', 0, '0'… */
    private static function jsTruthy($v)
    {
        if ($v === null || $v === false || $v === '') {
            return false;
        }
        if (is_int($v) || is_float($v)) {
            return $v != 0 && !is_nan($v);
        }

        return true;   // cualquier string no vacío, incluido "0" y ".0000"
    }
}
