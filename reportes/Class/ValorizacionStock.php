<?php
/**
 * ValorizacionStock
 * Valorización de stock de LAKERS SA por fecha de cierre, sobre la tabla
 * histórica RO_T_VALORIZACION_STOCK_DETALLADO_LAKERS (base central).
 *
 * La tabla la llena RO_SP_VALORIZACION_STOCK_DETALLADO_LAKERS desde la vista
 * RO_V_VALORIZACION_STOCK_DETALLADO_LAKERS. Ninguna de las tres se toca: este
 * reporte sólo las lee.
 *
 * DOS MODOS
 * ---------
 *   histórico  STOCK y VALORIZACION tal como se grabaron. Si en ese momento la
 *              vista valorizó con la lista 20 dividida por 3, queda así: es lo
 *              que dijo el cierre.
 *   actual     el STOCK grabado en la fecha elegida, valorizado con el costo de
 *              HOY y la misma lógica de la vista. Un artículo sin costo de
 *              última compra vale 0 y se ve como "Sin costo", SALVO los OU:
 *              esos toman el precio de venta de la lista 20 dividido por 3,
 *              sin nacionalización. Ver origenCosto() y costoUnitarioActual().
 *
 * SE AGRUPA POR SECTOR + SUCURSAL + ARTÍCULO. En CENTRAL la vista une STA19
 * por depósito sin agrupar y la tabla no guarda el depósito, así que un mismo
 * artículo aparece hasta seis veces sin nada que distinga las filas. Sumarlas
 * no cambia ningún total y deja una fila por artículo.
 *
 * TODO EL CÁLCULO ES UNA SOLA CONSULTA PARAMETRIZADA (sqlConsulta()), con la
 * fecha, el modo, los filtros y el factor como parámetros. Las reglas puras de
 * abajo dicen lo mismo en PHP para poder probarlas; tests/test_valorizacion.php
 * compara las dos contra la base cuando hay conexión.
 */
require_once __DIR__ . '/../../class/conexion.php';
require_once __DIR__ . '/Parametros.php';

class ValorizacionStock {

    const TABLA = 'RO_T_VALORIZACION_STOCK_DETALLADO_LAKERS';

    const MODO_HISTORICO = 'historico';
    const MODO_ACTUAL = 'actual';

    const SECTOR_CENTRAL = 'CENTRAL';
    const SECTOR_LOCALES = 'LOCALES';

    /** Lo que la tabla tiene en COD_ARTICU y DESCRIPCIO: más largo no encuentra nada */
    const LARGO_ARTICULO = 50;

    /** Los valores fijos de la columna "Origen del costo" */
    const ORIGEN_HISTORICO = 'Histórico';
    const ORIGEN_ULTIMA_COMPRA = 'Última compra';
    const ORIGEN_NACIONALIZACION = 'Última compra + nacionalización';
    const ORIGEN_NAC_ESTIMADA = 'Última compra + nacionalización estimada';
    const ORIGEN_LISTA20 = 'Precio de venta lista 20 / 3';
    const ORIGEN_SIN_COSTO = 'Sin costo';

    /**
     * Los artículos sin costo de última compra que se valorizan con el precio
     * de venta: los que empiezan con OU, a la lista 20 de GVA17 dividida por 3.
     * Es un pedido de Administración. No llevan nacionalización: hoy ningún OU
     * usa partida, y la regla no se la suma aunque alguno llegara a usarla.
     */
    const PREFIJO_LISTA20 = 'OU';
    const LISTA_VENTA = 20;
    const DIVISOR_LISTA = 3;

    /** Las columnas del detalle, en el orden en que viaja cada fila al front */
    const COLUMNAS = ['sector', 'sucursal', 'articulo', 'descripcion', 'stock', 'costoUnitario',
        'valorizacion', 'origen'];

    private $conn;

    /**
     * @param resource|null $conn Una conexión a central ya abierta (pruebas)
     */
    public function __construct($conn = null) {
        if ($conn === null) {
            $conn = (new Conexion())->conectar('central');
        }

        if (!$conn) {
            throw new Exception('No se pudo conectar con la base central.');
        }

        $this->conn = $conn;
    }

    /* =====================================================================
       REGLAS PURAS
       ===================================================================== */

    /**
     * El origen del costo en modo actual. Pura.
     *
     * El orden importa: sin costo de última compra no hay nada que
     * nacionalizar, así que la falta de costo se resuelve antes que la
     * partida. Ahí un OU con precio de lista 20 toma ese precio / 3, y
     * cualquier otro queda "Sin costo".
     *
     * @param float|null $precioUltimaCompra STA12.PRECIO_U_L
     * @param bool|int|null $usaPartida SJ_VIEW_STA11.USA_PARTID
     * @param float|null $costoNac RO_ORDEN_COMPRA_IMPORT_COSTO_NAC.COSTO_NAC
     * @param string $articulo COD_ARTICU
     * @param float|null $precioLista20 GVA17.PRECIO de la lista 20
     * @return string Una de las constantes ORIGEN_*
     */
    public static function origenCosto($precioUltimaCompra, $usaPartida, $costoNac, $articulo, $precioLista20) {
        if ($precioUltimaCompra === null || (float) $precioUltimaCompra == 0) {
            if (self::usaLista20($articulo) && $precioLista20 !== null && (float) $precioLista20 > 0) {
                return self::ORIGEN_LISTA20;
            }

            return self::ORIGEN_SIN_COSTO;
        }

        if ((int) $usaPartida === 1) {
            return $costoNac !== null ? self::ORIGEN_NACIONALIZACION : self::ORIGEN_NAC_ESTIMADA;
        }

        return self::ORIGEN_ULTIMA_COMPRA;
    }

    /** Si el artículo, sin costo de última compra, se valoriza con la lista 20. Pura. */
    public static function usaLista20($articulo) {
        return strncmp(trim((string) $articulo), self::PREFIJO_LISTA20, strlen(self::PREFIJO_LISTA20)) === 0;
    }

    /**
     * El costo unitario de hoy. Pura.
     *
     * Es la cuenta de la vista, con una diferencia: la lista 20 / 3 sólo la
     * toman los OU sin costo. Cualquier otro artículo sin costo base vale 0.
     *
     * @param float|null $precioUltimaCompra
     * @param bool|int|null $usaPartida
     * @param float|null $costoNac
     * @param string $articulo
     * @param float|null $precioLista20
     * @param float $factor El factor de nacionalización estimado de Parámetros
     * @return float
     */
    public static function costoUnitarioActual($precioUltimaCompra, $usaPartida, $costoNac, $articulo, $precioLista20,
                                               $factor) {
        switch (self::origenCosto($precioUltimaCompra, $usaPartida, $costoNac, $articulo, $precioLista20)) {
            case self::ORIGEN_SIN_COSTO:
                return 0.0;
            case self::ORIGEN_LISTA20:
                return (float) $precioLista20 / self::DIVISOR_LISTA;
            case self::ORIGEN_NACIONALIZACION:
                return (float) $precioUltimaCompra * (1 + (float) $costoNac);
            case self::ORIGEN_NAC_ESTIMADA:
                return (float) $precioUltimaCompra * (float) $factor;
            default:
                return (float) $precioUltimaCompra;
        }
    }

    /** STOCK × costo unitario, a 2 decimales. Pura. */
    public static function valorizar($stock, $costoUnitario) {
        return round((float) $stock * (float) $costoUnitario, 2);
    }

    /** El costo unitario histórico: valorización / stock, o 0 sin stock. Pura. */
    public static function costoUnitarioHistorico($stock, $valorizacion) {
        return ((float) $stock == 0) ? 0.0 : (float) $valorizacion / (float) $stock;
    }

    /**
     * Valida los filtros que manda el front contra lo que existe. Pura.
     *
     * La fecha y las sucursales se validan contra las listas que salen de la
     * tabla: un valor que no está ahí no se busca, se rechaza con un mensaje.
     *
     * @param array $crudo fecha, modo, sector, sucursales[], articulo
     * @param string[] $fechas Las fechas de cierre que existen (Y-m-d)
     * @param array $sucursales [['sector' => ..., 'sucursal' => ...]] de esa fecha
     * @return array ['ok' => bool, 'message' => string, 'filtros' => array|null]
     */
    public static function validarFiltros(array $crudo, array $fechas, array $sucursales) {
        $fecha = trim((string) ($crudo['fecha'] ?? ''));

        if ($fecha === '' || !in_array($fecha, $fechas, true)) {
            return self::filtroInvalido('Elegí una fecha de cierre de la lista.');
        }

        $modo = trim((string) ($crudo['modo'] ?? ''));

        if (!in_array($modo, [self::MODO_HISTORICO, self::MODO_ACTUAL], true)) {
            return self::filtroInvalido('Elegí el costo: histórico o actual.');
        }

        $sector = strtoupper(trim((string) ($crudo['sector'] ?? '')));

        if (!in_array($sector, ['', self::SECTOR_CENTRAL, self::SECTOR_LOCALES], true)) {
            return self::filtroInvalido('El sector tiene que ser Todos, Central o Locales.');
        }

        $pedidas = $crudo['sucursales'] ?? [];
        $pedidas = is_array($pedidas) ? $pedidas : [$pedidas];
        $pedidas = array_values(array_unique(array_filter(array_map(function ($s) {
            return trim((string) $s);
        }, $pedidas), 'strlen')));

        $validas = [];

        foreach ($sucursales as $s) {
            if ($sector === '' || $s['sector'] === $sector) {
                $validas[] = $s['sucursal'];
            }
        }

        $ajenas = array_values(array_diff($pedidas, $validas));

        if ($ajenas) {
            return self::filtroInvalido('Estas sucursales no tienen stock en el cierre del ' . $fecha
                . ($sector !== '' ? ' para el sector ' . ucfirst(strtolower($sector)) : '') . ': '
                . implode(', ', $ajenas) . '.');
        }

        $articulo = trim((string) ($crudo['articulo'] ?? ''));

        if (mb_strlen($articulo) > self::LARGO_ARTICULO) {
            return self::filtroInvalido('El artículo a buscar puede tener hasta ' . self::LARGO_ARTICULO
                . ' caracteres.');
        }

        return ['ok' => true, 'message' => '', 'filtros' => [
            'fecha' => $fecha,
            'modo' => $modo,
            'sector' => $sector,
            'sucursales' => $pedidas,
            'articulo' => $articulo
        ]];
    }

    private static function filtroInvalido($mensaje) {
        return ['ok' => false, 'message' => $mensaje, 'filtros' => null];
    }

    /**
     * El texto del LIKE, con los comodines del usuario escapados. Pura.
     *
     * Quien busca "50%" quiere el texto "50%", no "50 y cualquier cosa".
     *
     * @param string $texto
     * @return string '%texto%'
     */
    public static function patronLike($texto) {
        return '%' . strtr($texto, ['[' => '[[]', '%' => '[%]', '_' => '[_]']) . '%';
    }

    /**
     * La leyenda de lo que se está viendo. Pura.
     *
     * @param array $filtros Ya validados
     * @return string
     */
    public static function leyenda(array $filtros) {
        if ($filtros['modo'] === self::MODO_ACTUAL) {
            return 'Stock al ' . $filtros['fecha'] . ' valorizado al costo de hoy';
        }

        return 'Stock y valorización al ' . $filtros['fecha'] . ', tal como se grabaron en el cierre';
    }

    /**
     * Los filtros aplicados, en palabras, para la pantalla y el Excel. Pura.
     *
     * @param array $filtros Ya validados
     * @return string[] 'Etiqueta: valor'
     */
    public static function filtrosEnTexto(array $filtros) {
        return [
            'Fecha de cierre: ' . $filtros['fecha'],
            'Costo: ' . ($filtros['modo'] === self::MODO_ACTUAL ? 'Actual (costo de hoy)' : 'Histórico (tal como se grabó)'),
            'Sector: ' . ($filtros['sector'] === '' ? 'Todos' : ucfirst(strtolower($filtros['sector']))),
            'Sucursal: ' . ($filtros['sucursales'] ? implode(', ', $filtros['sucursales']) : 'Todas'),
            'Artículo: ' . ($filtros['articulo'] === '' ? 'Todos' : 'contiene "' . $filtros['articulo'] . '"')
        ];
    }

    /**
     * El resumen por sector y sucursal y las tarjetas, sobre las filas del
     * detalle. Pura.
     *
     * Suma lo que el detalle ya trae redondeado: así el total de la pantalla
     * es exactamente la suma de la columna del Excel.
     *
     * @param array $filas Filas con las claves de COLUMNAS
     * @return array ['sectores' => [...], 'total' => [...], 'kpis' => [...]]
     */
    public static function resumir(array $filas) {
        $sectores = [];
        $articulos = [];
        $sinCosto = 0;
        $estimada = 0;
        $lista20 = 0;
        $total = ['stock' => 0.0, 'valorizacion' => 0.0];

        foreach ($filas as $f) {
            $sec = $f['sector'];
            $suc = $f['sucursal'];

            if (!isset($sectores[$sec])) {
                $sectores[$sec] = ['sector' => $sec, 'sucursales' => [], 'stock' => 0.0, 'valorizacion' => 0.0];
            }

            if (!isset($sectores[$sec]['sucursales'][$suc])) {
                $sectores[$sec]['sucursales'][$suc] = ['sucursal' => $suc, 'stock' => 0.0, 'valorizacion' => 0.0];
            }

            $sectores[$sec]['sucursales'][$suc]['stock'] += $f['stock'];
            $sectores[$sec]['sucursales'][$suc]['valorizacion'] += $f['valorizacion'];
            $sectores[$sec]['stock'] += $f['stock'];
            $sectores[$sec]['valorizacion'] += $f['valorizacion'];
            $total['stock'] += $f['stock'];
            $total['valorizacion'] += $f['valorizacion'];

            $articulos[$f['articulo']] = true;

            if ($f['origen'] === self::ORIGEN_SIN_COSTO) {
                $sinCosto++;
            } elseif ($f['origen'] === self::ORIGEN_NAC_ESTIMADA) {
                $estimada++;
            } elseif ($f['origen'] === self::ORIGEN_LISTA20) {
                $lista20++;
            }
        }

        // CENTRAL primero, después LOCALES; las sucursales por nombre
        ksort($sectores);
        $salida = [];

        foreach ($sectores as $s) {
            ksort($s['sucursales']);
            $s['sucursales'] = array_map([self::class, 'redondearTotales'], array_values($s['sucursales']));
            $salida[] = self::redondearTotales($s);
        }

        return [
            'sectores' => $salida,
            'total' => self::redondearTotales($total),
            'kpis' => [
                'stock' => round($total['stock'], 4),
                'valorizacion' => round($total['valorizacion'], 2),
                'articulos' => count($articulos),
                'filas' => count($filas),
                'sinCosto' => $sinCosto,
                'nacEstimada' => $estimada,
                'lista20' => $lista20
            ]
        ];
    }

    /** Saca el ruido de punto flotante de una suma de importes */
    private static function redondearTotales(array $x) {
        $x['stock'] = round($x['stock'], 4);
        $x['valorizacion'] = round($x['valorizacion'], 2);

        return $x;
    }

    /* =====================================================================
       CONSULTAS
       ===================================================================== */

    /**
     * Las fechas de cierre que existen, de la más reciente a la más vieja.
     *
     * @return string[] Y-m-d
     */
    public function fechas() {
        $stmt = $this->consultar("SELECT DISTINCT CAST(FECHA AS DATE) AS FECHA
                                  FROM " . self::TABLA . "
                                  ORDER BY FECHA DESC");
        $fechas = [];

        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $fechas[] = $r['FECHA'] instanceof DateTime ? $r['FECHA']->format('Y-m-d') : (string) $r['FECHA'];
        }

        return $fechas;
    }

    /**
     * Las sucursales con stock en un cierre, con su sector.
     *
     * @param string $fecha Y-m-d, ya validada contra fechas()
     * @return array [['sector' => ..., 'sucursal' => ...]]
     */
    public function sucursales($fecha) {
        $stmt = $this->consultar("SELECT DISTINCT SECTOR, SUCURSAL
                                  FROM " . self::TABLA . "
                                  WHERE CAST(FECHA AS DATE) = ?
                                  ORDER BY SECTOR, SUCURSAL", [$fecha]);
        $lista = [];

        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $lista[] = ['sector' => trim($r['SECTOR']), 'sucursal' => trim($r['SUCURSAL'])];
        }

        return $lista;
    }

    /**
     * Valida los filtros contra la base: las fechas que existen y las
     * sucursales de la fecha pedida.
     *
     * @param array $crudo
     * @return array Lo mismo que validarFiltros()
     */
    public function validar(array $crudo) {
        $fechas = $this->fechas();
        $fecha = trim((string) ($crudo['fecha'] ?? ''));
        $sucursales = in_array($fecha, $fechas, true) ? $this->sucursales($fecha) : [];

        return self::validarFiltros($crudo, $fechas, $sucursales);
    }

    /**
     * El detalle: una fila por sector + sucursal + artículo, con el costo y
     * la valorización del modo pedido.
     *
     * @param array $filtros Ya validados
     * @param float|null $factor El factor de Parámetros; obligatorio en modo actual
     * @param bool $conInsumos Agrega lo que usó el costo actual (precioUltimaCompra,
     *                         usaPartida, costoNac, precioLista20): lo usa la
     *                         prueba de paridad para recalcular cada fila con
     *                         las reglas puras
     * @return array Filas con las claves de COLUMNAS
     */
    public function detalle(array $filtros, $factor, $conInsumos = false) {
        if ($filtros['modo'] === self::MODO_ACTUAL && $factor === null) {
            throw new Exception('Falta el factor de nacionalización estimado en Parámetros, así que no se '
                . 'puede valorizar al costo de hoy. Verificá que se haya corrido sql/reportes_parametros.sql.');
        }

        $stmt = $this->consultar(self::sqlConsulta(count($filtros['sucursales'])), self::parametrosConsulta($filtros, $factor));
        $filas = [];

        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $fila = [
                'sector' => trim($r['SECTOR']),
                'sucursal' => trim($r['SUCURSAL']),
                'articulo' => trim($r['COD_ARTICU']),
                'descripcion' => trim((string) $r['DESCRIPCIO']),
                'stock' => (float) $r['STOCK'],
                'costoUnitario' => round((float) $r['COSTO_UNITARIO'], 4),
                'valorizacion' => round((float) $r['VALORIZACION'], 2),
                'origen' => $r['ORIGEN']
            ];

            if ($conInsumos) {
                $fila['precioUltimaCompra'] = $r['PRECIO_U_L'] === null ? null : (float) $r['PRECIO_U_L'];
                $fila['usaPartida'] = $r['USA_PARTID'] === null ? null : (int) $r['USA_PARTID'];
                $fila['costoNac'] = $r['COSTO_NAC'] === null ? null : (float) $r['COSTO_NAC'];
                $fila['precioLista20'] = $r['PRECIO_LISTA20'] === null ? null : (float) $r['PRECIO_LISTA20'];
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    /**
     * Los parámetros de sqlConsulta(), en el orden de sus "?".
     *
     * @param array $filtros
     * @param float|null $factor
     * @return array
     */
    public static function parametrosConsulta(array $filtros, $factor) {
        $articulo = $filtros['articulo'];

        return array_merge(
            [
                $filtros['fecha'],
                $filtros['modo'],
                $factor === null ? null : (float) $factor,
                $filtros['sector'],
                $articulo === '' ? '' : self::patronLike($articulo)
            ],
            $filtros['sucursales']
        );
    }

    /**
     * La consulta del detalle, en los dos modos.
     *
     * Lo único que se arma es la cantidad de "?" de la lista de sucursales;
     * todos los VALORES van como parámetros. Las etiquetas del origen salen de
     * las constantes ORIGEN_*, que son código y no entrada del usuario.
     *
     * OPTION (RECOMPILE): con el modo como parámetro, un plan único tendría
     * que contemplar las uniones del costo actual también en modo histórico.
     * Recompilando, SQL Server ve el valor de @MODO y en histórico ni toca
     * STA12, GVA17 ni la vista de nacionalización, que es lo caro.
     *
     * El prefijo OU, la lista 20 y el divisor 3 también se escriben desde
     * constantes: son la regla, no un filtro del usuario.
     *
     * @param int $cantSucursales
     * @return string
     */
    public static function sqlConsulta($cantSucursales) {
        $enSucursales = $cantSucursales > 0
            ? 'AND T.SUCURSAL IN (' . implode(', ', array_fill(0, $cantSucursales, '?')) . ')'
            : '';

        $hist = "N'" . self::ORIGEN_HISTORICO . "'";
        $ult = "N'" . self::ORIGEN_ULTIMA_COMPRA . "'";
        $nac = "N'" . self::ORIGEN_NACIONALIZACION . "'";
        $est = "N'" . self::ORIGEN_NAC_ESTIMADA . "'";
        $l20 = "N'" . self::ORIGEN_LISTA20 . "'";
        $sin = "N'" . self::ORIGEN_SIN_COSTO . "'";
        $prefijo = "'" . self::PREFIJO_LISTA20 . "%'";
        $lista = (int) self::LISTA_VENTA;
        $divisor = (int) self::DIVISOR_LISTA;

        return "
SET NOCOUNT ON;

DECLARE @FECHA DATE = ?, @MODO VARCHAR(10) = ?, @FACTOR FLOAT = ?,
        @SECTOR VARCHAR(10) = ?, @ARTICULO NVARCHAR(160) = ?;

WITH B AS (
    -- Una fila por sector + sucursal + artículo: en CENTRAL la tabla repite
    -- el artículo por depósito sin guardar el depósito.
    SELECT T.SECTOR, T.SUCURSAL, T.COD_ARTICU,
           MAX(T.DESCRIPCIO) AS DESCRIPCIO,
           SUM(T.STOCK) AS STOCK,
           SUM(T.VALORIZACION) AS VALORIZACION
    FROM " . self::TABLA . " T
    WHERE CAST(T.FECHA AS DATE) = @FECHA
      AND (@SECTOR = '' OR T.SECTOR = @SECTOR)
      AND (@ARTICULO = ''
           OR T.COD_ARTICU COLLATE Modern_Spanish_CI_AI LIKE @ARTICULO
           OR T.DESCRIPCIO LIKE @ARTICULO)
      $enSucursales
    GROUP BY T.SECTOR, T.SUCURSAL, T.COD_ARTICU
),
C AS (
    -- El costo de hoy, sólo en modo actual. Las cuatro fuentes tienen una
    -- fila por COD_ARTICU (índice único en STA12, STA11 y GVA17 por artículo
    -- y lista; ROW_NUMBER = 1 en la vista de nacionalización), así que las
    -- uniones no duplican. La lista 20 sólo se busca para los OU.
    SELECT B.*,
           CAST(P.PRECIO_U_L AS FLOAT) AS PRECIO_U_L,
           CAST(A.USA_PARTID AS INT) AS USA_PARTID,
           CAST(N.COSTO_NAC AS FLOAT) AS COSTO_NAC,
           CAST(L.PRECIO AS FLOAT) AS PRECIO_LISTA20
    FROM B
    LEFT JOIN STA12 P ON @MODO = '" . self::MODO_ACTUAL . "' AND P.COD_ARTICU = B.COD_ARTICU
    LEFT JOIN SJ_VIEW_STA11 A ON @MODO = '" . self::MODO_ACTUAL . "' AND A.COD_ARTICU = B.COD_ARTICU
    LEFT JOIN RO_ORDEN_COMPRA_IMPORT_COSTO_NAC N ON @MODO = '" . self::MODO_ACTUAL . "' AND N.COD_ARTICU = B.COD_ARTICU
    LEFT JOIN GVA17 L ON @MODO = '" . self::MODO_ACTUAL . "' AND B.COD_ARTICU LIKE $prefijo
                     AND L.NRO_DE_LIS = $lista AND L.COD_ARTICU = B.COD_ARTICU
),
O AS (
    SELECT C.*,
           CASE
               WHEN @MODO = '" . self::MODO_HISTORICO . "' THEN $hist
               -- Sin costo de última compra: los OU con precio de lista 20
               -- van a lista 20 / 3, sin nacionalización; el resto, a 0
               WHEN (C.PRECIO_U_L IS NULL OR C.PRECIO_U_L = 0)
                    AND C.COD_ARTICU LIKE $prefijo AND C.PRECIO_LISTA20 > 0 THEN $l20
               WHEN C.PRECIO_U_L IS NULL OR C.PRECIO_U_L = 0 THEN $sin
               WHEN C.USA_PARTID = 1 AND C.COSTO_NAC IS NOT NULL THEN $nac
               WHEN C.USA_PARTID = 1 THEN $est
               ELSE $ult
           END AS ORIGEN
    FROM C
),
U AS (
    SELECT O.*,
           CASE
               WHEN @MODO = '" . self::MODO_HISTORICO . "'
                   THEN CASE WHEN O.STOCK = 0 THEN 0 ELSE O.VALORIZACION / O.STOCK END
               WHEN O.ORIGEN = $sin THEN 0
               WHEN O.ORIGEN = $l20 THEN O.PRECIO_LISTA20 / $divisor
               WHEN O.ORIGEN = $nac THEN O.PRECIO_U_L * (1 + O.COSTO_NAC)
               WHEN O.ORIGEN = $est THEN O.PRECIO_U_L * @FACTOR
               ELSE O.PRECIO_U_L
           END AS COSTO_UNITARIO
    FROM O
)
SELECT U.SECTOR, U.SUCURSAL, U.COD_ARTICU, U.DESCRIPCIO, U.STOCK, U.COSTO_UNITARIO,
       CASE WHEN @MODO = '" . self::MODO_HISTORICO . "'
            THEN ROUND(U.VALORIZACION, 2)
            ELSE ROUND(U.STOCK * U.COSTO_UNITARIO, 2)
       END AS VALORIZACION,
       U.ORIGEN,
       U.PRECIO_U_L, U.USA_PARTID, U.COSTO_NAC, U.PRECIO_LISTA20
FROM U
ORDER BY U.SECTOR, U.SUCURSAL, U.COD_ARTICU
OPTION (RECOMPILE);";
    }

    /** Ejecuta una consulta parametrizada o lanza una excepción con el error */
    private function consultar($sql, array $params = []) {
        $stmt = sqlsrv_query($this->conn, $sql, $params, ['QueryTimeout' => 120]);

        if ($stmt === false) {
            error_log('Reportes/ValorizacionStock: ' . print_r(sqlsrv_errors(), true));

            throw new Exception('Error al consultar la valorización de stock.');
        }

        return $stmt;
    }
}
