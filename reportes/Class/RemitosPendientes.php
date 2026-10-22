<?php
/**
 * RemitosPendientes
 * Los remitos de LAKERS SA (base central) que seguían sin facturar a una fecha
 * de corte: la fecha Hasta del período. Un remito que se facturó después de
 * esa fecha cuenta como pendiente, y el reporte dice con qué factura y cuándo
 * se resolvió.
 *
 * La referencia es el SP RO_BUSCA_REMITOS_SIN_FACTURA, que manda por mail los
 * remitos de los últimos 60 días sin factura. El SP no se toca: este reporte
 * toma de él el universo y los importes, y lo mejora en cuatro cosas (ver el
 * README):
 *   - la fecha que filtra es la del remito (GVA106.FECHA_REM), no la del
 *     movimiento;
 *   - una factura que Tango ya vinculó a un remito (GVA54) no puede dar por
 *     facturado a otro: no entra al pozo del emparejamiento por importe;
 *   - el emparejamiento por importe es uno a uno: cada factura cubre un solo
 *     remito, y tiene que ser del mismo día o posterior al remito;
 *   - sólo cuentan las facturas (T_COMP = 'FAC'): una nota de crédito, de
 *     débito o un recibo por el mismo importe no factura un remito.
 *
 * LA BASE TRAE LOS DATOS; LA CLASIFICACIÓN ES PHP (clasificar()), porque el
 * emparejamiento uno a uno es secuencial: cada remito se lleva la primera
 * factura libre, y eso cambia lo que le queda al siguiente. Es una regla pura
 * y tests/test_remitos_pendientes.php la prueba caso por caso.
 */
require_once __DIR__ . '/../../class/conexion.php';

class RemitosPendientes {

    /** Los tipos de cliente: la primera letra del código */
    const TIPOS = [
        'F' => 'Franquicias',
        'L' => 'Gestión asistida',
        'M' => 'Mayoristas'
    ];

    /** Un cliente que el SP excluye y este reporte también */
    const CLIENTE_EXCLUIDO = 'FRUSH';

    /** El único comprobante que factura un remito: ni notas de crédito o débito ni recibos */
    const T_COMP_FACTURA = 'FAC';

    /** Las opciones del período */
    const PERIODO_CONTABLE_ACTUAL = 'contable_actual';
    const PERIODO_CONTABLE_ANTERIOR = 'contable_anterior';
    const PERIODO_CALENDARIO = 'calendario';
    const PERIODO_PERSONALIZADO = 'personalizado';

    /** El año contable empieza el 01/08 */
    const INICIO_CONTABLE = '08-01';

    /** Los valores de "Cómo se identificó" */
    const COMO_VINCULADO = 'Vinculado en Tango';
    const COMO_IMPORTE = 'Por importe';

    /** Lo que dice la razón social cuando el cliente no está en GVA14, como el SP */
    const SIN_RAZON_SOCIAL = 'Sin datos';

    /** El largo de COD_PRO_CL / COD_CLIENT */
    const LARGO_CLIENTE = 6;

    /** Las columnas del detalle, en el orden en que viaja cada fila al front */
    const COLUMNAS = ['fecha', 'tipo', 'cliente', 'razonSocial', 'remito', 'importe', 'diasAlCorte', 'facturaNro',
        'facturaFecha', 'como'];

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

    /**
     * Hoy en Argentina, Y-m-d. Con la zona escrita: el PHP de algunos
     * servidores no la tiene configurada (el de desarrollo está en
     * Europe/Berlin), y "hoy" es el tope del Hasta.
     */
    public static function hoy() {
        return (new DateTime('now', new DateTimeZone('America/Argentina/Buenos_Aires')))->format('Y-m-d');
    }

    /* =====================================================================
       REGLAS PURAS: PERÍODO Y FILTROS
       ===================================================================== */

    /**
     * El rango de una opción de período. Pura.
     *
     * El año contable va del 01/08 al 31/07: el 31/07 todavía es el año que
     * empezó el 01/08 anterior, y el 01/08 ya es uno nuevo.
     *
     * @param string $opcion Una de las constantes PERIODO_*
     * @param string $hoy Y-m-d
     * @return array|null ['desde' => Y-m-d, 'hasta' => Y-m-d]; null si es personalizado o desconocida
     */
    public static function rangoPeriodo($opcion, $hoy) {
        $anio = (int) substr($hoy, 0, 4);
        $inicioActual = (substr($hoy, 5) >= self::INICIO_CONTABLE ? $anio : $anio - 1) . '-' . self::INICIO_CONTABLE;
        $anioInicio = (int) substr($inicioActual, 0, 4);

        switch ($opcion) {
            case self::PERIODO_CONTABLE_ACTUAL:
                return ['desde' => $inicioActual, 'hasta' => $hoy];
            case self::PERIODO_CONTABLE_ANTERIOR:
                return ['desde' => ($anioInicio - 1) . '-' . self::INICIO_CONTABLE, 'hasta' => $anioInicio . '-07-31'];
            case self::PERIODO_CALENDARIO:
                return ['desde' => $anio . '-01-01', 'hasta' => $hoy];
            default:
                return null;
        }
    }

    /**
     * Las opciones del selector, con su rango ya calculado. Pura.
     *
     * El front las pide al servidor y no las calcula: así la regla vive en un
     * solo lugar, con su prueba, y "hoy" es el de Argentina.
     *
     * @param string $hoy Y-m-d
     * @return array [['opcion', 'nombre', 'desde', 'hasta']]
     */
    public static function periodos($hoy) {
        $nombres = [
            self::PERIODO_CONTABLE_ACTUAL => 'Año contable actual',
            self::PERIODO_CONTABLE_ANTERIOR => 'Año contable anterior',
            self::PERIODO_CALENDARIO => 'Año calendario'
        ];
        $lista = [];

        foreach ($nombres as $opcion => $nombre) {
            $lista[] = ['opcion' => $opcion, 'nombre' => $nombre] + self::rangoPeriodo($opcion, $hoy);
        }

        $lista[] = ['opcion' => self::PERIODO_PERSONALIZADO, 'nombre' => 'Personalizado', 'desde' => null,
            'hasta' => null];

        return $lista;
    }

    /** Si un texto es una fecha Y-m-d válida. Pura. */
    public static function esFecha($s) {
        $d = DateTime::createFromFormat('!Y-m-d', (string) $s);

        return $d !== false && $d->format('Y-m-d') === $s;
    }

    /**
     * Valida el período y el tipo de cliente. Pura.
     *
     * Va por separado de validarFiltros() porque la lista de clientes válidos
     * sale de la base CON este período y este tipo: primero hay que saber que
     * son válidos.
     *
     * @param array $crudo desde, hasta, tipo
     * @param string $hoy Y-m-d
     * @return array ['ok' => bool, 'message' => string, 'filtros' => array|null]
     */
    public static function validarPeriodo(array $crudo, $hoy) {
        $desde = trim((string) ($crudo['desde'] ?? ''));
        $hasta = trim((string) ($crudo['hasta'] ?? ''));

        if (!self::esFecha($desde) || !self::esFecha($hasta)) {
            return self::filtroInvalido('Elegí las fechas Desde y Hasta del período.');
        }

        if ($hasta > $hoy) {
            return self::filtroInvalido('La fecha Hasta no puede ser posterior a hoy (' . self::fechaTexto($hoy)
                . '): es la fecha de corte, y lo que todavía no pasó no se puede saber.');
        }

        if ($desde > $hasta) {
            return self::filtroInvalido('La fecha Desde no puede ser posterior a la fecha Hasta.');
        }

        $tipo = strtoupper(trim((string) ($crudo['tipo'] ?? '')));

        if ($tipo !== '' && !isset(self::TIPOS[$tipo])) {
            return self::filtroInvalido('El tipo de cliente tiene que ser Todos, Franquicias, Gestión asistida o '
                . 'Mayoristas.');
        }

        return ['ok' => true, 'message' => '', 'filtros' => ['desde' => $desde, 'hasta' => $hasta, 'tipo' => $tipo]];
    }

    /**
     * Valida todos los filtros. Pura.
     *
     * Los clientes se validan contra los que tienen remitos en el período
     * (con el tipo elegido): un código que no está ahí no se busca, se rechaza
     * con un mensaje. Ninguno elegido es todos.
     *
     * @param array $crudo desde, hasta, tipo, clientes[]
     * @param string $hoy Y-m-d
     * @param string[] $clientes Los códigos con remitos en ese período y tipo
     * @return array ['ok' => bool, 'message' => string, 'filtros' => array|null]
     */
    public static function validarFiltros(array $crudo, $hoy, array $clientes) {
        $val = self::validarPeriodo($crudo, $hoy);

        if (!$val['ok']) {
            return $val;
        }

        $pedidos = $crudo['clientes'] ?? [];
        $pedidos = is_array($pedidos) ? $pedidos : [$pedidos];
        $pedidos = array_values(array_unique(array_filter(array_map(function ($c) {
            return strtoupper(trim((string) $c));
        }, $pedidos), 'strlen')));

        $ajenos = array_values(array_diff($pedidos, $clientes));

        if ($ajenos) {
            return self::filtroInvalido('Estos clientes no tienen remitos del ' . self::fechaTexto($val['filtros']['desde'])
                . ' al ' . self::fechaTexto($val['filtros']['hasta'])
                . ($val['filtros']['tipo'] !== '' ? ' entre los ' . self::TIPOS[$val['filtros']['tipo']] : '') . ': '
                . implode(', ', $ajenos) . '.');
        }

        sort($pedidos);
        $val['filtros']['clientes'] = $pedidos;

        return $val;
    }

    private static function filtroInvalido($mensaje) {
        return ['ok' => false, 'message' => $mensaje, 'filtros' => null];
    }

    /** El nombre del tipo de un cliente, por la primera letra del código. Pura. */
    public static function tipoCliente($cliente) {
        return self::TIPOS[strtoupper(substr(trim((string) $cliente), 0, 1))] ?? '';
    }

    /** Y-m-d a DD/MM/AAAA. Pura. */
    public static function fechaTexto($ymd) {
        $p = explode('-', (string) $ymd);

        return count($p) === 3 ? $p[2] . '/' . $p[1] . '/' . $p[0] : (string) $ymd;
    }

    /** Los días entre el remito y el corte. Pura. */
    public static function diasAlCorte($fechaRemito, $hasta) {
        return (int) (new DateTime($fechaRemito))->diff(new DateTime($hasta))->format('%r%a');
    }

    /** Un importe DECIMAL(18,2) en centavos, para compararlo exacto. Pura. */
    public static function centavos($importe) {
        return (int) round((float) $importe * 100);
    }

    /* =====================================================================
       REGLA PURA: LA CLASIFICACIÓN
       ===================================================================== */

    /**
     * Qué remitos seguían sin facturar al Hasta. Pura.
     *
     * EN ESTE ORDEN:
     *   1. Vinculado en Tango (GVA54). Si tiene varias facturas manda la
     *      ÚLTIMA: el remito queda facturado recién cuando se emitió la
     *      última. Del Hasta o antes: facturado, no sale. Después: pendiente,
     *      "Vinculado en Tango", con esa factura.
     *   2. Por importe, uno a uno, para los no vinculados con importe distinto
     *      de 0. Por cliente + importe exacto, los remitos por fecha y número;
     *      cada uno se lleva la primera factura del pozo todavía libre, del
     *      mismo cliente, el mismo importe y del mismo día o posterior al
     *      remito. Del Hasta o antes: facturado. Después: pendiente, "Por
     *      importe".
     *   3. Sin factura: pendiente, sin datos de factura.
     *
     * Un importe 0 (remito sólo de promociones) no se empareja: que dos cosas
     * valgan 0 no prueba que una facture a la otra.
     *
     * @param array $remitos [['remito', 'fecha', 'cliente', 'razonSocial', 'importe',
     *                        'facturas' => [['factura', 'fecha']]]], las vinculadas en Tango
     * @param array $pozo [['factura', 'fecha', 'cliente', 'importe']]: facturas NO vinculadas
     *                    a ningún remito, desde el Desde hasta hoy
     * @param string $hasta Y-m-d, la fecha de corte
     * @return array Las filas pendientes, con las claves de COLUMNAS, por fecha y remito
     */
    public static function clasificar(array $remitos, array $pozo, $hasta) {
        $pendientes = [];
        $porImporte = [];

        foreach ($remitos as $r) {
            if (!empty($r['facturas'])) {
                $ultima = self::ultimaFactura($r['facturas']);

                if ($ultima['fecha'] > $hasta) {
                    $pendientes[] = self::filaPendiente($r, $hasta, $ultima, self::COMO_VINCULADO);
                }
            } elseif (self::centavos($r['importe']) === 0) {
                $pendientes[] = self::filaPendiente($r, $hasta, null, '');
            } else {
                $porImporte[self::claveImporte($r['cliente'], $r['importe'])][] = $r;
            }
        }

        // El pozo, por cliente + importe y en orden de emisión: "la primera
        // todavía no usada" es la más vieja que cumpla.
        $libres = [];

        foreach ($pozo as $f) {
            $libres[self::claveImporte($f['cliente'], $f['importe'])][] = $f;
        }

        foreach ($libres as &$lista) {
            usort($lista, function ($a, $b) {
                return [$a['fecha'], $a['factura']] <=> [$b['fecha'], $b['factura']];
            });
        }
        unset($lista);

        foreach ($porImporte as $clave => $grupo) {
            usort($grupo, function ($a, $b) {
                return [$a['fecha'], $a['remito']] <=> [$b['fecha'], $b['remito']];
            });

            $facturas = $libres[$clave] ?? [];

            foreach ($grupo as $r) {
                $asignada = null;

                foreach ($facturas as $i => $f) {
                    // Primero se hace el remito y después la factura
                    if ($f['fecha'] >= $r['fecha']) {
                        $asignada = $f;
                        unset($facturas[$i]);
                        break;
                    }
                }

                if ($asignada === null) {
                    $pendientes[] = self::filaPendiente($r, $hasta, null, '');
                } elseif ($asignada['fecha'] > $hasta) {
                    $pendientes[] = self::filaPendiente($r, $hasta, $asignada, self::COMO_IMPORTE);
                }
            }
        }

        usort($pendientes, function ($a, $b) {
            return [$a['fecha'], $a['remito']] <=> [$b['fecha'], $b['remito']];
        });

        return $pendientes;
    }

    /** La última de las facturas vinculadas: la de fecha más nueva y, a igual fecha, la de número mayor */
    private static function ultimaFactura(array $facturas) {
        usort($facturas, function ($a, $b) {
            return [$b['fecha'], $b['factura']] <=> [$a['fecha'], $a['factura']];
        });

        return $facturas[0];
    }

    private static function claveImporte($cliente, $importe) {
        return trim((string) $cliente) . '|' . self::centavos($importe);
    }

    private static function filaPendiente(array $r, $hasta, $factura, $como) {
        return [
            'fecha' => $r['fecha'],
            'tipo' => self::tipoCliente($r['cliente']),
            'cliente' => $r['cliente'],
            'razonSocial' => $r['razonSocial'],
            'remito' => $r['remito'],
            'importe' => round((float) $r['importe'], 2),
            'diasAlCorte' => self::diasAlCorte($r['fecha'], $hasta),
            'facturaNro' => $factura ? $factura['factura'] : '',
            'facturaFecha' => $factura ? $factura['fecha'] : '',
            'como' => $como
        ];
    }

    /* =====================================================================
       REGLAS PURAS: RESUMEN Y TEXTOS
       ===================================================================== */

    /**
     * Las tarjetas, el resumen por tipo de cliente y el resumen por cliente,
     * sobre las filas pendientes. Pura.
     *
     * @param array $filas Las de clasificar()
     * @return array ['kpis' => [...], 'tipos' => [...], 'total' => [...], 'clientes' => [...]]
     */
    public static function resumir(array $filas) {
        $kpis = [
            'pendientes' => 0, 'importePendiente' => 0.0,
            'facturadosDespues' => 0, 'importeFacturadoDespues' => 0.0,
            'sinFactura' => 0, 'importeSinFactura' => 0.0,
            'clientes' => 0
        ];
        $tipos = [];
        $clientes = [];

        foreach (self::TIPOS as $nombre) {
            $tipos[$nombre] = ['tipo' => $nombre, 'cantidad' => 0, 'importe' => 0.0];
        }

        foreach ($filas as $f) {
            $despues = $f['como'] !== '';

            $kpis['pendientes']++;
            $kpis['importePendiente'] += $f['importe'];

            if ($despues) {
                $kpis['facturadosDespues']++;
                $kpis['importeFacturadoDespues'] += $f['importe'];
            } else {
                $kpis['sinFactura']++;
                $kpis['importeSinFactura'] += $f['importe'];
            }

            $tipos[$f['tipo']]['cantidad']++;
            $tipos[$f['tipo']]['importe'] += $f['importe'];

            $c = $f['cliente'];

            if (!isset($clientes[$c])) {
                $clientes[$c] = ['cliente' => $c, 'razonSocial' => $f['razonSocial'], 'tipo' => $f['tipo'],
                    'cantidad' => 0, 'importe' => 0.0, 'facturadosDespues' => 0, 'masViejo' => $f['fecha']];
            }

            $clientes[$c]['cantidad']++;
            $clientes[$c]['importe'] += $f['importe'];
            $clientes[$c]['facturadosDespues'] += $despues ? 1 : 0;
            $clientes[$c]['masViejo'] = min($clientes[$c]['masViejo'], $f['fecha']);
        }

        $kpis['clientes'] = count($clientes);

        foreach (['importePendiente', 'importeFacturadoDespues', 'importeSinFactura'] as $k) {
            $kpis[$k] = round($kpis[$k], 2);
        }

        // Sólo los tipos con remitos, en el orden de TIPOS
        $tipos = array_values(array_filter(array_map(function ($t) {
            $t['importe'] = round($t['importe'], 2);

            return $t;
        }, $tipos), function ($t) {
            return $t['cantidad'] > 0;
        }));

        $clientes = array_map(function ($c) {
            $c['importe'] = round($c['importe'], 2);

            return $c;
        }, array_values($clientes));

        // De mayor a menor importe; a igual importe, por código
        usort($clientes, function ($a, $b) {
            return [$b['importe'], $a['cliente']] <=> [$a['importe'], $b['cliente']];
        });

        return [
            'kpis' => $kpis,
            'tipos' => $tipos,
            'total' => ['cantidad' => $kpis['pendientes'], 'importe' => $kpis['importePendiente']],
            'clientes' => $clientes
        ];
    }

    /**
     * La leyenda de lo que se está viendo. Pura.
     *
     * @param array $filtros Ya validados
     * @param string $hoy Y-m-d
     * @return string
     */
    public static function leyenda(array $filtros, $hoy) {
        return 'Remitos del ' . self::fechaTexto($filtros['desde']) . ' al ' . self::fechaTexto($filtros['hasta'])
            . ', pendientes de facturar al ' . self::fechaTexto($filtros['hasta'])
            . ($filtros['hasta'] === $hoy ? ' (hoy: el resultado es al día de hoy)' : '');
    }

    /**
     * Los filtros aplicados, en palabras, para la pantalla y el Excel. Pura.
     *
     * @param array $filtros Ya validados
     * @param array $nombres código => razón social de los clientes elegidos
     * @return string[] 'Etiqueta: valor'
     */
    public static function filtrosEnTexto(array $filtros, array $nombres = []) {
        $clientes = array_map(function ($c) use ($nombres) {
            return isset($nombres[$c]) ? $c . ' ' . $nombres[$c] : $c;
        }, $filtros['clientes']);

        return [
            'Período (fecha de remito): ' . self::fechaTexto($filtros['desde']) . ' al '
                . self::fechaTexto($filtros['hasta']),
            'Fecha de corte: ' . self::fechaTexto($filtros['hasta']),
            'Tipo de cliente: ' . ($filtros['tipo'] === '' ? 'Todos' : self::TIPOS[$filtros['tipo']]),
            'Cliente: ' . ($clientes ? implode(', ', $clientes) : 'Todos')
        ];
    }

    /* =====================================================================
       CONSULTAS
       ===================================================================== */

    /**
     * El universo de remitos con su fecha, como CTE "U". Lo comparten la lista
     * de clientes y los remitos, para que el filtro de clientes ofrezca
     * exactamente los que la consulta puede devolver.
     *
     * Es el del SP, con la fecha del remito en lugar de la del movimiento:
     *   - GVA106 tiene un renglón por renglón del remito, pero una sola
     *     FECHA_REM por remito (verificado en toda la base): el MAX no elige
     *     entre fechas distintas, sólo colapsa los renglones;
     *   - un remito sin GVA106 (casi todos de 2010-2014) toma FECHA_MOV, que
     *     en los que tienen las dos coincide siempre. El SP los dejaba afuera;
     *   - los anulados se excluyen siempre: Tango no guarda cuándo se anuló.
     *
     * Parámetros, en orden: @DESDE, @HASTA, @TIPO y después un "?" por cliente.
     * WITH (NOLOCK), como el SP: son tablas vivas del ERP y un reporte no
     * tiene que frenar la facturación.
     *
     * @param int $cantClientes
     * @return string
     */
    private static function sqlUniverso($cantClientes) {
        $enClientes = $cantClientes > 0
            ? 'AND s.COD_PRO_CL IN (' . implode(', ', array_fill(0, $cantClientes, '?')) . ')'
            : '';

        return "
SET NOCOUNT ON;

DECLARE @DESDE DATE = ?, @HASTA DATE = ?, @TIPO VARCHAR(1) = ?;

WITH R AS (
    SELECT s.ID_STA14, s.TCOMP_IN_S, s.NCOMP_IN_S, s.N_COMP, s.COD_PRO_CL,
           COALESCE(g.FECHA_REM, CAST(s.FECHA_MOV AS DATE)) AS FECHA_REM
    FROM STA14 s WITH (NOLOCK)
    -- Agrupado de una vez, como el SP: un OUTER APPLY por remito tardaba
    -- más de 30 s, esto unos 2 s
    LEFT JOIN (
        SELECT x.TCOMP_IN_S, x.NCOMP_IN_S, MAX(CAST(x.FECHA_REM AS DATE)) AS FECHA_REM
        FROM GVA106 x WITH (NOLOCK)
        WHERE x.TCOMP_IN_S = 'RE'
        GROUP BY x.TCOMP_IN_S, x.NCOMP_IN_S
    ) g ON g.TCOMP_IN_S = s.TCOMP_IN_S AND g.NCOMP_IN_S = s.NCOMP_IN_S
    WHERE s.TCOMP_IN_S = 'RE'
      AND s.N_COMP LIKE 'R%'
      AND s.COD_PRO_CL LIKE '[FML]%'
      AND s.COD_PRO_CL <> '" . self::CLIENTE_EXCLUIDO . "'
      AND s.ESTADO_MOV <> 'A'
      AND (@TIPO = '' OR s.COD_PRO_CL LIKE @TIPO + '%')
      $enClientes
),
U AS (
    SELECT * FROM R WHERE R.FECHA_REM BETWEEN @DESDE AND @HASTA
)";
    }

    /**
     * La consulta de los remitos del período: una fila por remito y factura
     * vinculada (o una sola, sin factura, si no tiene).
     *
     * Las vinculadas se buscan por T_COMP_V + N_COMP. El SP une GVA54 con
     * GVA12 sólo por N_COMP, y un mismo número puede ser de dos tipos de
     * comprobante. Sólo cuentan las FAC: hoy GVA54 no vincula estos remitos
     * con otra cosa, y si algún día lo hiciera una nota de crédito no los
     * daría por facturados.
     *
     * @param int $cantClientes
     * @return string
     */
    public static function sqlRemitos($cantClientes) {
        return self::sqlUniverso($cantClientes) . "
SELECT U.NCOMP_IN_S, U.N_COMP, U.FECHA_REM, U.COD_PRO_CL,
       ISNULL(c.RAZON_SOCI, '" . self::SIN_RAZON_SOCIAL . "') AS RAZON_SOCI,
       CAST(ISNULL(d.IMPORTE, 0) AS DECIMAL(18,2)) AS IMPORTE,
       f.FACTURA, f.FECHA_FACTURA
FROM U
LEFT JOIN GVA14 c WITH (NOLOCK) ON c.COD_CLIENT = U.COD_PRO_CL
LEFT JOIN (
    -- Neto y sin promociones, como el SP. Agrupado de una vez sobre los
    -- remitos del período: un OUTER APPLY por remito tardaba 55 s en un año
    -- contable, esto unos 6 s
    SELECT b.ID_STA14, SUM(b.CANTIDAD * b.PRECIO_REM) AS IMPORTE
    FROM STA20 b WITH (NOLOCK)
    JOIN U u2 ON u2.ID_STA14 = b.ID_STA14
    WHERE b.PROMOCION = 0
    GROUP BY b.ID_STA14
) d ON d.ID_STA14 = U.ID_STA14
OUTER APPLY (
    -- GVA54 es por renglón: DISTINCT deja una fila por factura
    SELECT DISTINCT a.N_COMP AS FACTURA, CAST(a.FECHA_EMIS AS DATE) AS FECHA_FACTURA
    FROM GVA54 x WITH (NOLOCK)
    JOIN GVA12 a WITH (NOLOCK) ON a.T_COMP = x.T_COMP_V AND a.N_COMP = x.N_COMP
    WHERE x.TCOMP_IN_S = U.TCOMP_IN_S AND x.NCOMP_IN_S = U.NCOMP_IN_S
      AND x.T_COMP_V = '" . self::T_COMP_FACTURA . "'
) f
ORDER BY U.FECHA_REM, U.N_COMP;";
    }

    /**
     * Los clientes con remitos en el período, para el filtro.
     *
     * @return string
     */
    public static function sqlClientes() {
        return self::sqlUniverso(0) . "
SELECT U.COD_PRO_CL, ISNULL(MAX(c.RAZON_SOCI), '" . self::SIN_RAZON_SOCIAL . "') AS RAZON_SOCI
FROM U
LEFT JOIN GVA14 c WITH (NOLOCK) ON c.COD_CLIENT = U.COD_PRO_CL
GROUP BY U.COD_PRO_CL
ORDER BY U.COD_PRO_CL;";
    }

    /**
     * El pozo del emparejamiento por importe: las facturas de esos clientes
     * emitidas desde el Desde hasta HOY que no estén vinculadas en GVA54 a
     * ningún remito. Una factura que Tango ya asignó no puede facturar otro
     * remito.
     *
     * Importe: SUM(IMPORTE_GRAVADO) de los renglones sin promoción, neto,
     * como el SP. Parámetros: @DESDE, @HOY, @TIPO y un "?" por cliente.
     *
     * @param int $cantClientes
     * @return string
     */
    public static function sqlPozo($cantClientes) {
        $enClientes = $cantClientes > 0
            ? 'AND A.COD_CLIENT IN (' . implode(', ', array_fill(0, $cantClientes, '?')) . ')'
            : '';

        return "
SET NOCOUNT ON;

DECLARE @DESDE DATE = ?, @HOY DATE = ?, @TIPO VARCHAR(1) = ?;

SELECT A.N_COMP, A.COD_CLIENT, CAST(A.FECHA_EMIS AS DATE) AS FECHA_EMIS,
       CAST(SUM(B.IMPORTE_GRAVADO) AS DECIMAL(18,2)) AS IMPORTE
FROM GVA12 A WITH (NOLOCK)
JOIN GVA53 B WITH (NOLOCK) ON B.T_COMP = A.T_COMP AND B.N_COMP = A.N_COMP
WHERE A.T_COMP = '" . self::T_COMP_FACTURA . "'
  AND A.COD_CLIENT LIKE '[FML]%'
  AND (@TIPO = '' OR A.COD_CLIENT LIKE @TIPO + '%')
  $enClientes
  AND A.FECHA_EMIS >= @DESDE AND A.FECHA_EMIS < DATEADD(DAY, 1, @HOY)
  AND B.PROMOCION = 0
  AND NOT EXISTS (
      SELECT 1 FROM GVA54 x WITH (NOLOCK)
      WHERE x.T_COMP_V = A.T_COMP AND x.N_COMP = A.N_COMP
  )
GROUP BY A.N_COMP, A.COD_CLIENT, CAST(A.FECHA_EMIS AS DATE);";
    }

    /**
     * Los parámetros del universo (y del pozo, con el Desde y hoy), en el
     * orden de sus "?".
     */
    public static function parametros($fecha1, $fecha2, array $filtros) {
        return array_merge([$fecha1, $fecha2, $filtros['tipo']], $filtros['clientes']);
    }

    /**
     * Los clientes con remitos en el período y el tipo.
     *
     * @param array $periodo desde, hasta, tipo (validados)
     * @return array [['cliente' => ..., 'razonSocial' => ...]]
     */
    public function clientes(array $periodo) {
        $stmt = $this->consultar(self::sqlClientes(),
            self::parametros($periodo['desde'], $periodo['hasta'], ['clientes' => []] + $periodo));
        $lista = [];

        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $lista[] = ['cliente' => trim($r['COD_PRO_CL']), 'razonSocial' => trim((string) $r['RAZON_SOCI'])];
        }

        return $lista;
    }

    /**
     * Valida los filtros contra la base: el período, y los clientes contra
     * los que tienen remitos en él.
     *
     * @param array $crudo
     * @param string $hoy
     * @return array Lo mismo que validarFiltros(), con 'nombres' (código => razón social)
     */
    public function validar(array $crudo, $hoy) {
        $per = self::validarPeriodo($crudo, $hoy);

        if (!$per['ok']) {
            return $per;
        }

        $nombres = array_column($this->clientes($per['filtros']), 'razonSocial', 'cliente');
        $val = self::validarFiltros($crudo, $hoy, array_map('strval', array_keys($nombres)));

        if ($val['ok']) {
            $val['nombres'] = array_intersect_key($nombres, array_flip($val['filtros']['clientes']));
        }

        return $val;
    }

    /**
     * Los remitos del período, con sus facturas vinculadas.
     *
     * @param array $filtros Ya validados
     * @return array Lo que espera clasificar()
     */
    public function remitos(array $filtros) {
        $stmt = $this->consultar(self::sqlRemitos(count($filtros['clientes'])),
            self::parametros($filtros['desde'], $filtros['hasta'], $filtros));
        $remitos = [];

        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $id = trim($r['NCOMP_IN_S']);

            if (!isset($remitos[$id])) {
                $remitos[$id] = [
                    'remito' => trim($r['N_COMP']),
                    'fecha' => self::ymd($r['FECHA_REM']),
                    'cliente' => trim($r['COD_PRO_CL']),
                    'razonSocial' => trim((string) $r['RAZON_SOCI']),
                    'importe' => (float) $r['IMPORTE'],
                    'facturas' => []
                ];
            }

            if ($r['FACTURA'] !== null) {
                $remitos[$id]['facturas'][] = ['factura' => trim($r['FACTURA']), 'fecha' => self::ymd($r['FECHA_FACTURA'])];
            }
        }

        return array_values($remitos);
    }

    /**
     * El pozo de facturas sin vincular, desde el Desde hasta hoy.
     *
     * @param array $filtros Ya validados
     * @param string $hoy
     * @return array Lo que espera clasificar()
     */
    public function pozo(array $filtros, $hoy) {
        $stmt = $this->consultar(self::sqlPozo(count($filtros['clientes'])),
            self::parametros($filtros['desde'], $hoy, $filtros));
        $pozo = [];

        while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $pozo[] = [
                'factura' => trim($r['N_COMP']),
                'fecha' => self::ymd($r['FECHA_EMIS']),
                'cliente' => trim($r['COD_CLIENT']),
                'importe' => (float) $r['IMPORTE']
            ];
        }

        return $pozo;
    }

    /**
     * Los pendientes al Hasta: las dos consultas y la clasificación.
     *
     * @param array $filtros Ya validados
     * @param string $hoy
     * @return array Filas con las claves de COLUMNAS
     */
    public function detalle(array $filtros, $hoy) {
        return self::clasificar($this->remitos($filtros), $this->pozo($filtros, $hoy), $filtros['hasta']);
    }

    private static function ymd($v) {
        return $v instanceof DateTime ? $v->format('Y-m-d') : substr((string) $v, 0, 10);
    }

    /** Ejecuta una consulta parametrizada o lanza una excepción con el error */
    private function consultar($sql, array $params = []) {
        $stmt = sqlsrv_query($this->conn, $sql, $params, ['QueryTimeout' => 120]);

        if ($stmt === false) {
            error_log('Reportes/RemitosPendientes: ' . print_r(sqlsrv_errors(), true));

            throw new Exception('Error al consultar los remitos pendientes.');
        }

        return $stmt;
    }
}
