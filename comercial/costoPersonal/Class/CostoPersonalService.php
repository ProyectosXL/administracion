<?php

/**
 * CostoPersonalService.php
 * Lógica de negocio y acceso a datos para Costo de Personal.
 *
 * Tabla principal : RO_T_COSTO_PERSONAL_MENSUAL
 * Ventas          : RO_T_RENTABILIDAD_BRUTA
 * Parámetros      : RO_T_PARAMETROS_COSTO_PERSONAL
 *
 * IMPORTANTE: todos los cálculos usan IMPORTE_PRORRATEADO.
 *             IMPORTE_REAL es solo informativo.
 */
class CostoPersonalService
{
    private $conn;

    /** Categorías válidas y su orden de presentación */
    const CATEGORIAS = ['FIJO', 'VARIABLE', 'DIFERIDO', 'CONTINGENTE'];

    public function __construct()
    {
        require_once CP_CONEXION_PATH;

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $cid = new Conexion();
        // Solo Argentina: siempre conectar a 'central'
        $this->conn = $cid->conectar('central');

        if ($this->conn === false) {
            throw new Exception('Error al conectar con la base de datos central.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RANGO POR DEFECTO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Últimos 12 meses completos (excluyendo el mes actual).
     * @return array ['desde' => 'YYYY-MM-DD', 'hasta' => 'YYYY-MM-DD']
     */
    public function calcularRangoDefault(): array
    {
        $hoy = new DateTime();

        $ultimoDiaMesAnterior = new DateTime($hoy->format('Y-m-01'));
        $ultimoDiaMesAnterior->modify('-1 day');

        $desde = clone $ultimoDiaMesAnterior;
        $desde->modify('-11 months');
        $desde = new DateTime($desde->format('Y-m-01'));

        return [
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $ultimoDiaMesAnterior->format('Y-m-d'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PARÁMETROS CONFIGURABLES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lee los umbrales desde RO_T_PARAMETROS_COSTO_PERSONAL.
     * Devuelve valores hardcodeados como fallback si la tabla no existe.
     */
    public function obtenerParametros(): array
    {
        $defaults = [
            'UMBRAL_VERDE' => 10.00,
            'UMBRAL_ROJO'  => 15.00,
            'OBJETIVO_PCT' => 10.00,
        ];

        try {
            $sql  = "SELECT NOMBRE, CAST(VALOR AS FLOAT) AS VALOR FROM RO_T_PARAMETROS_COSTO_PERSONAL";
            $stmt = sqlsrv_query($this->conn, $sql);
            if (!$stmt) return $defaults;

            $params = $defaults;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                if (isset($defaults[$row['NOMBRE']])) {
                    $params[$row['NOMBRE']] = (float) $row['VALOR'];
                }
            }
            return $params;
        } catch (Exception $e) {
            error_log('CostoPersonalService::obtenerParametros — ' . $e->getMessage());
            return $defaults;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GENERADOR DE MESES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Genera array ['YYYY-MM', ...] entre dos fechas.
     */
    public function generarMeses(string $fechaDesde, string $fechaHasta): array
    {
        $meses  = [];
        $inicio = new DateTime($fechaDesde . '-01');   // forzar día 1 para evitar overflow
        $fin    = new DateTime($fechaHasta);
        $fin    = new DateTime($fin->format('Y-m-01'));

        while ($inicio <= $fin) {
            $meses[] = $inicio->format('Y-m');
            $inicio->modify('+1 month');
        }
        return $meses;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COSTOS POR MES (desglosados por CATEGORIA + CONCEPTO)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene todos los costos de una sucursal en el rango, agrupados por
     * mes → categoría → concepto.
     *
     * @return array [
     *   'por_mes'              => [ 'YYYY-MM' => [ 'FIJO' => ['SUELDOS' => n, ...], ... ] ],
     *   'total_por_categoria'  => [ 'FIJO' => n, ... ],
     *   'total_por_concepto'   => [ 'SUELDOS' => n, ... ],
     *   'total_real_por_concepto' => [ ... ],  // IMPORTE_REAL para mostrar en tooltip
     * ]
     */
    public function obtenerCostosDesglosados(int $idSucursal, string $fechaDesde, string $fechaHasta): array
    {
        $meses = $this->generarMeses($fechaDesde, $fechaHasta);

        // Estructura vacía base
        $porMes = [];
        foreach ($meses as $mes) {
            $porMes[$mes] = [];
            foreach (self::CATEGORIAS as $cat) {
                $porMes[$mes][$cat] = [];
            }
        }

        $sql = "
            SELECT
                FORMAT(FECHA_PERIODO, 'yyyy-MM')  AS PERIODO,
                CATEGORIA,
                CONCEPTO,
                SUM(IMPORTE_PRORRATEADO) AS IMPORTE_PRORR,
                SUM(IMPORTE_REAL)        AS IMPORTE_REAL
            FROM RO_T_COSTO_PERSONAL_MENSUAL
            WHERE NRO_SUCURSAL = ?
              AND FECHA_PERIODO >= DATEFROMPARTS(YEAR(CAST(? AS DATE)), MONTH(CAST(? AS DATE)), 1)
              AND FECHA_PERIODO <= EOMONTH(CAST(? AS DATE))
            GROUP BY
                FORMAT(FECHA_PERIODO, 'yyyy-MM'),
                CATEGORIA,
                CONCEPTO
            ORDER BY PERIODO, CATEGORIA, CONCEPTO
        ";

        try {
            $params = [$idSucursal, $fechaDesde, $fechaDesde, $fechaHasta];
            $stmt   = sqlsrv_prepare($this->conn, $sql, $params);

            if (!$stmt) {
                throw new Exception('Error preparando consulta de costos de personal.');
            }
            sqlsrv_execute($stmt);

            $totalPorCategoria = array_fill_keys(self::CATEGORIAS, 0.0);
            $totalPorConcepto  = [];
            $totalRealConcepto = [];

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $periodo  = $row['PERIODO'];
                $cat      = $row['CATEGORIA'];
                $concepto = $row['CONCEPTO'];
                $prorr    = (float) $row['IMPORTE_PRORR'];
                $real     = (float) $row['IMPORTE_REAL'];

                if (!isset($porMes[$periodo])) continue;  // mes fuera del rango
                if (!isset($porMes[$periodo][$cat])) $porMes[$periodo][$cat] = [];

                $porMes[$periodo][$cat][$concepto] = ($porMes[$periodo][$cat][$concepto] ?? 0) + $prorr;

                $totalPorCategoria[$cat]             = ($totalPorCategoria[$cat] ?? 0) + $prorr;
                $totalPorConcepto[$concepto]          = ($totalPorConcepto[$concepto] ?? 0) + $prorr;
                $totalRealConcepto[$concepto]         = ($totalRealConcepto[$concepto] ?? 0) + $real;
            }

            return [
                'meses'                   => $meses,
                'por_mes'                 => $porMes,
                'total_por_categoria'     => $totalPorCategoria,
                'total_por_concepto'      => $totalPorConcepto,
                'total_real_por_concepto' => $totalRealConcepto,
            ];

        } catch (Exception $e) {
            error_log('CostoPersonalService::obtenerCostosDesglosados — ' . $e->getMessage());
            return [
                'meses'                   => $meses,
                'por_mes'                 => $porMes,
                'total_por_categoria'     => array_fill_keys(self::CATEGORIAS, 0.0),
                'total_por_concepto'      => [],
                'total_real_por_concepto' => [],
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VENTA NETA POR MES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lee ventas de RO_T_RENTABILIDAD_BRUTA, mes por mes.
     * @return array ['YYYY-MM' => float, ...]
     */
    public function obtenerVentaNetaPorMes(int $idSucursal, string $fechaDesde, string $fechaHasta): array
    {
        $meses   = $this->generarMeses($fechaDesde, $fechaHasta);
        $ventas  = [];

        $sql = "
            SELECT NRO_SUCURS, SUM(CAST(VENTA AS FLOAT)) AS VENTA
            FROM RO_T_RENTABILIDAD_BRUTA
            WHERE NRO_SUCURS = ?
              AND FECHA LIKE ?
            GROUP BY NRO_SUCURS
        ";

        foreach ($meses as $mes) {
            try {
                $params = [$idSucursal, $mes . '%'];
                $stmt   = sqlsrv_prepare($this->conn, $sql, $params);

                if (!$stmt) {
                    $ventas[$mes] = 0;
                    continue;
                }
                sqlsrv_execute($stmt);

                if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $ventas[$mes] = $row['VENTA'] !== null ? floatval($row['VENTA']) : 0;
                } else {
                    $ventas[$mes] = 0;
                }
            } catch (Exception $e) {
                error_log("CostoPersonalService::obtenerVentaNetaPorMes — {$mes}: " . $e->getMessage());
                $ventas[$mes] = 0;
            }
        }

        return $ventas;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DATASET COMPLETO (para Reporte por Sucursal)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Construye el dataset completo para una sucursal.
     *
     * Estructura de filas devuelta:
     *   [ { concepto, categoria, meses:{YYYY-MM:v}, total, is_subtotal?, is_metric?, is_percentage? } ]
     *
     * Las filas están ordenadas: conceptos dentro de cada categoría →
     * subtotal por categoría → Total Costo Personal → Venta Neta → % Costo Personal.
     */
    public function construirDataset(int $idSucursal, string $fechaDesde, string $fechaHasta): array
    {
        if (empty($fechaDesde) || empty($fechaHasta)) {
            $rango     = $this->calcularRangoDefault();
            $fechaDesde = $rango['desde'];
            $fechaHasta = $rango['hasta'];
        }

        $costos   = $this->obtenerCostosDesglosados($idSucursal, $fechaDesde, $fechaHasta);
        $ventas   = $this->obtenerVentaNetaPorMes($idSucursal, $fechaDesde, $fechaHasta);
        $meses    = $costos['meses'];

        // ── Construir filas: una por concepto, agrupadas por categoría ────────
        $filas = [];

        foreach (self::CATEGORIAS as $cat) {
            // Recopilar todos los conceptos que aparecieron en esta categoría
            $conceptosDeCat = [];
            foreach ($meses as $mes) {
                foreach (($costos['por_mes'][$mes][$cat] ?? []) as $concepto => $importe) {
                    $conceptosDeCat[$concepto] = true;
                }
            }

            foreach (array_keys($conceptosDeCat) as $concepto) {
                $filaConcepto = [
                    'concepto'  => $concepto,
                    'categoria' => $cat,
                    'meses'     => [],
                    'total'     => 0,
                ];
                foreach ($meses as $mes) {
                    $v = $costos['por_mes'][$mes][$cat][$concepto] ?? 0;
                    $filaConcepto['meses'][$mes] = $v;
                    $filaConcepto['total'] += $v;
                }
                $filas[] = $filaConcepto;
            }

            // Subtotal por categoría
            $subtotalCat = ['concepto' => 'Subtotal ' . ucfirst(strtolower($cat)), 'categoria' => $cat, 'meses' => [], 'total' => 0, 'is_subtotal_cat' => true];
            foreach ($meses as $mes) {
                $suma = array_sum($costos['por_mes'][$mes][$cat] ?? []);
                $subtotalCat['meses'][$mes] = $suma;
                $subtotalCat['total'] += $suma;
            }
            $filas[] = $subtotalCat;
        }

        // ── Subtotal total de costo personal ─────────────────────────────────
        $subtotalTotal = ['concepto' => 'Subtotal Costo Personal Total', 'meses' => [], 'total' => 0, 'is_subtotal' => true];
        foreach ($meses as $mes) {
            $suma = 0;
            foreach (self::CATEGORIAS as $cat) {
                $suma += array_sum($costos['por_mes'][$mes][$cat] ?? []);
            }
            $subtotalTotal['meses'][$mes] = $suma;
            $subtotalTotal['total'] += $suma;
        }
        $filas[] = $subtotalTotal;

        // ── Venta Neta ────────────────────────────────────────────────────────
        $totalVenta = array_sum($ventas);
        $filas[] = [
            'concepto'  => 'Venta Neta',
            'meses'     => $ventas,
            'total'     => $totalVenta,
            'is_metric' => true,
        ];

        // ── % Costo Personal ──────────────────────────────────────────────────
        $pctPorMes = [];
        foreach ($meses as $mes) {
            $costo = $subtotalTotal['meses'][$mes];
            $venta = $ventas[$mes] ?? 0;
            $pctPorMes[$mes] = $venta > 0 ? ($costo / $venta) * 100 : null;
        }
        $pctTotal = $totalVenta > 0 ? ($subtotalTotal['total'] / $totalVenta) * 100 : null;

        $filas[] = [
            'concepto'       => '% Costo de Personal',
            'meses'          => $pctPorMes,
            'total'          => $pctTotal,
            'is_percentage'  => true,
        ];

        // ── KPIs ──────────────────────────────────────────────────────────────
        $kpis = $this->calcularKPIs($pctTotal, $idSucursal, $fechaDesde, $fechaHasta);

        return [
            'meses'       => $meses,
            'filas'       => $filas,
            'kpis'        => $kpis,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DATASET SIMPLIFICADO (para KPIs, Indicadores, comparaciones)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna los totales resumidos + costos desglosados por categoría para
     * que el frontend pueda recalcular al togglear categorías.
     *
     * @return array [
     *   'costos_por_categoria'     => ['FIJO' => n, ...],
     *   'conceptos_detalle'        => [['categoria'=>, 'concepto'=>, 'importe'=>], ...],
     *   'total_costo'              => float,  // suma de TODOS los prorrateados
     *   'total_venta_neta'         => float,
     *   'porcentaje_costo_personal' => float|null,
     * ]
     */
    public function construirDatasetSimplificado(int $idSucursal, string $fechaDesde, string $fechaHasta): array
    {
        $costos = $this->obtenerCostosDesglosados($idSucursal, $fechaDesde, $fechaHasta);
        $ventas = $this->obtenerVentaNetaPorMes($idSucursal, $fechaDesde, $fechaHasta);

        $totalVenta = array_sum($ventas);
        $totalCosto = array_sum($costos['total_por_categoria']);

        $pct = $totalVenta > 0 ? ($totalCosto / $totalVenta) * 100 : null;

        // Armar conceptos_detalle
        $detalle = [];
        foreach ($costos['total_por_concepto'] as $concepto => $importe) {
            // Determinar categoría del concepto
            $catConcepto = '';
            foreach ($costos['meses'] as $mes) {
                foreach (self::CATEGORIAS as $cat) {
                    if (isset($costos['por_mes'][$mes][$cat][$concepto])) {
                        $catConcepto = $cat;
                        break 2;
                    }
                }
            }
            $detalle[] = [
                'categoria' => $catConcepto,
                'concepto'  => $concepto,
                'importe'   => round($importe, 2),
            ];
        }

        return [
            'costos_por_categoria'      => array_map(fn($v) => round($v, 2), $costos['total_por_categoria']),
            'conceptos_detalle'         => $detalle,
            'total_costo'               => round($totalCosto, 2),
            'total_venta_neta'          => round($totalVenta, 2),
            'porcentaje_costo_personal' => $pct !== null ? round($pct, 4) : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KPIs
    // ─────────────────────────────────────────────────────────────────────────

    private function calcularKPIs(?float $pctTotal, int $idSucursal, string $fechaDesde, string $fechaHasta): array
    {
        // Variación anual (YoY)
        $desdeAnt = (new DateTime($fechaDesde))->modify('-1 year')->format('Y-m-d');
        $hastaAnt = (new DateTime($fechaHasta))->modify('-1 year')->format('Y-m-d');

        $antData    = $this->construirDatasetSimplificado($idSucursal, $desdeAnt, $hastaAnt);
        $pctAnterior = $antData['porcentaje_costo_personal'];

        $variacion = null;
        if ($pctTotal !== null && $pctAnterior !== null && $pctAnterior != 0) {
            $variacion = (($pctTotal - $pctAnterior) / $pctAnterior) * 100;
        }

        return [
            'acumulado_pct'     => $pctTotal   !== null ? round($pctTotal,   2) : null,
            'pct_anterior'      => $pctAnterior !== null ? round($pctAnterior, 2) : null,
            'variacion_pct'     => $variacion   !== null ? round($variacion,   2) : null,
            'variacion_pp'      => ($pctTotal !== null && $pctAnterior !== null) ? round($pctTotal - $pctAnterior, 4) : null,
            'periodo_anterior'  => "{$desdeAnt} a {$hastaAnt}",
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UTILIDAD: Datos de gráfico de evolución (últimos N meses vs YoY)
    // ─────────────────────────────────────────────────────────────────────────

    public function obtenerDatosEvolucion(int $idSucursal, array $meses): array
    {
        $ultimos6 = array_slice($meses, -6);
        $actuales  = [];
        $anteriores = [];

        foreach ($ultimos6 as $mes) {
            $fecha = new DateTime($mes . '-01');

            $desde = $fecha->format('Y-m-01');
            $hasta = $fecha->format('Y-m-t');
            $d = $this->construirDatasetSimplificado($idSucursal, $desde, $hasta);
            $actuales[] = ['mes' => $mes, 'costo' => $d['porcentaje_costo_personal']];

            $fechaAnt = clone $fecha;
            $fechaAnt->modify('-1 year');
            $desdeAnt = $fechaAnt->format('Y-m-01');
            $hastaAnt = $fechaAnt->format('Y-m-t');
            $a = $this->construirDatasetSimplificado($idSucursal, $desdeAnt, $hastaAnt);
            $anteriores[] = ['mes' => $fechaAnt->format('Y-m'), 'costo' => $a['porcentaje_costo_personal']];
        }

        return ['actuales' => $actuales, 'anteriores' => $anteriores];
    }
}
