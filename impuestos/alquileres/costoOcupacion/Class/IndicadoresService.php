<?php

/**
 * IndicadoresService.php
 * Servicio para la pestaña "Indicadores – Costos de Ocupación".
 * Reutiliza CostoOcupacionService para los cálculos base y
 * agrega la lógica de semáforo, ranking, break-even y KPIs ejecutivos.
 */
class IndicadoresService
{
    private $costoService;
    private $superficieService;

    // Umbrales del semáforo (igual que en el frontend JS)
    const UMBRAL_VERDE  = 15;   // <= 15% → verde (Eficiente)
    const UMBRAL_ROJO   = 18;   // >  18% → rojo  (Requiere acción)
    const TARGET_BREAKEVEN = 15; // 15% objetivo para calcular venta mínima (break-even)

    public function __construct()
    {
        require_once __DIR__ . '/../../costoOcupacion/Class/costoOcupacionService.php';
        require_once __DIR__ . '/../../costoOcupacion/Class/superficieService.php';
        $this->costoService      = new CostoOcupacionService();
        $this->superficieService = new SuperficieService();
    }

    // ----------------------------------------------------------------
    // SEMÁFORO
    // ----------------------------------------------------------------

    /**
     * Clasifica un porcentaje de costo de ocupación en verde/amarillo/rojo.
     */
    public function clasificarSemaforo(?float $pct): string
    {
        if ($pct === null) return 'sin_datos';
        if ($pct < self::UMBRAL_VERDE) return 'verde';
        if ($pct < self::UMBRAL_ROJO)  return 'amarillo';
        return 'rojo';
    }

    // ----------------------------------------------------------------
    // BREAKEVEN
    // ----------------------------------------------------------------

    /**
     * Calcula la venta neta mínima para que el % costo sea exactamente TARGET_BREAKEVEN.
     * Fórmula: CostoTotal / (TARGET_BREAKEVEN / 100)
     */
    public function calcularBreakeven(?float $costoTotal): ?float
    {
        if ($costoTotal === null || $costoTotal <= 0) return null;
        return $costoTotal / (self::TARGET_BREAKEVEN / 100);
    }

    /**
     * Calcula la brecha entre venta actual y break-even.
     * Positivo = ya superó el objetivo. Negativo = necesita crecer.
     */
    public function calcularBrechaBreakeven(?float $ventaActual, ?float $breakeven): ?float
    {
        if ($ventaActual === null || $breakeven === null) return null;
        return $ventaActual - $breakeven;
    }

    // ----------------------------------------------------------------
    // DATOS POR SUCURSAL (usado para ranking y semáforo)
    // ----------------------------------------------------------------

    /**
     * Construye los datos ejecutivos de una sucursal para el período dado.
     * Retorna array con:
     *   - id, nombre, numero
     *   - pct_actual, pct_anterior
     *   - variacion_pp, variacion_relativa
     *   - venta_neta, costo_total
     *   - breakeven, brecha_breakeven, brecha_pct
     *   - semaforo
     */
    public function buildDatosSucursal(
        array  $sucursal,
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $idSucursal = $sucursal['ID'];

        // Período actual
        $dataActual = $this->costoService->construirDatasetSimplificado(
            $idSucursal,
            $fechaDesde,
            $fechaHasta
        );

        // Período anterior (YoY: mismo rango -12 meses)
        $desdeAnterior = (new DateTime($fechaDesde))->modify('-12 months')->format('Y-m-d');
        $hastaAnterior = (new DateTime($fechaHasta))->modify('-12 months')->format('Y-m-d');

        $dataAnterior = $this->costoService->construirDatasetSimplificado(
            $idSucursal,
            $desdeAnterior,
            $hastaAnterior
        );

        $pctActual   = $dataActual['porcentaje_costo_ocupacion'];
        $pctAnterior = $dataAnterior['porcentaje_costo_ocupacion'];
        $costoTotal  = $dataActual['total_gastos'];
        $ventaNeta   = $dataActual['total_venta_neta'];

        // Variación
        $variacionPP       = null;
        $variacionRelativa = null;
        if ($pctActual !== null && $pctAnterior !== null) {
            $variacionPP = round($pctActual - $pctAnterior, 4);
            if ($pctAnterior != 0) {
                $variacionRelativa = round((($pctActual - $pctAnterior) / $pctAnterior) * 100, 2);
            }
        }

        // Break-even
        $breakeven = $this->calcularBreakeven($costoTotal);
        $brecha    = $this->calcularBrechaBreakeven($ventaNeta ?: null, $breakeven);
        $brechaPct = ($breakeven && $breakeven != 0)
            ? round(($brecha / $breakeven) * 100, 2)
            : null;

        return [
            'id'                  => $idSucursal,
            'nombre'              => $sucursal['DESC_SUCURSAL'] ?? ($sucursal['SUCURSAL'] ?? 'Sucursal'),
            'numero'              => $sucursal['NRO_SUCURSAL']  ?? 0,
            'pct_actual'          => $pctActual   !== null ? round($pctActual,   2) : null,
            'pct_anterior'        => $pctAnterior !== null ? round($pctAnterior, 2) : null,
            'variacion_pp'        => $variacionPP,
            'variacion_relativa'  => $variacionRelativa,
            'venta_neta'          => $ventaNeta  ? round($ventaNeta,  2) : 0,
            'costo_total'         => $costoTotal ? round($costoTotal, 2) : 0,
            'breakeven'           => $breakeven  ? round($breakeven,  2) : null,
            'brecha_breakeven'    => $brecha     !== null ? round($brecha,    2) : null,
            'brecha_pct'          => $brechaPct  !== null ? $brechaPct : null,
            'semaforo'            => $this->clasificarSemaforo($pctActual),
            'tiene_datos_ant'     => ($pctAnterior !== null),
            'superficie_m2'       => null, // se enriquece en buildIndicadores
            'ventas_m2'           => null, // se enriquece en buildIndicadores
        ];
    }

    // ----------------------------------------------------------------
    // DATASET COMPLETO (llamado desde el controlador)
    // ----------------------------------------------------------------

    /**
     * Genera todos los datos necesarios para la pestaña Indicadores:
     * - kpis_cadena: KPIs agregados de toda la red
     * - ranking: array ordenado de mayor a menor % costo
     * - semaforo_counts: {verde, amarillo, rojo}
     * - sucursales: datos individuales para el semáforo y el detalle
     */
    public function buildIndicadores(
        array  $sucursales,
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $datos = [];

        foreach ($sucursales as $suc) {
            $datos[] = $this->buildDatosSucursal($suc, $fechaDesde, $fechaHasta);
        }

        // Filtrar solo los que tienen datos
        $conDatos = array_filter($datos, fn($d) => $d['pct_actual'] !== null);

        // Enriquecer con m² — una sola consulta para todas las sucursales
        $nrosSucursales = array_map(fn($d) => $d['numero'], $datos);
        $superficies    = $this->superficieService->obtenerSuperficies($nrosSucursales);

        foreach ($datos as &$d) {
            $nro = $d['numero'];
            $sup = isset($superficies[$nro]) && $superficies[$nro] > 0 ? $superficies[$nro] : null;
            $d['superficie_m2'] = $sup ? round($sup, 2) : null;
            $d['ventas_m2']     = ($sup && $d['venta_neta'] > 0)
                ? round($d['venta_neta'] / $sup, 2)
                : null;
        }
        unset($d);

        // Promedio ventas/m² de la cadena (solo sucursales con datos)
        $conM2 = array_filter($datos, fn($d) => $d['ventas_m2'] !== null);
        $promedioVentasM2 = count($conM2) > 0
            ? round(array_sum(array_column(array_values($conM2), 'ventas_m2')) / count($conM2), 2)
            : null;

        // KPIs de cadena (promedio ponderado por venta neta)
        $totalVenta      = array_sum(array_column(array_values($conDatos), 'venta_neta'));
        $totalCosto      = array_sum(array_column(array_values($conDatos), 'costo_total'));
        $pctCadena       = $totalVenta > 0 ? round(($totalCosto / $totalVenta) * 100, 2) : null;

        // YoY cadena
        $desdeAnterior = (new DateTime($fechaDesde))->modify('-12 months')->format('Y-m-d');
        $hastaAnterior = (new DateTime($fechaHasta))->modify('-12 months')->format('Y-m-d');

        $totalVentaAnt = 0;
        $totalCostoAnt = 0;
        foreach ($datos as $d) {
            $idSuc = $d['id'];
            $ant = $this->costoService->construirDatasetSimplificado($idSuc, $desdeAnterior, $hastaAnterior);
            $totalVentaAnt += $ant['total_venta_neta'] ?? 0;
            $totalCostoAnt += $ant['total_gastos']     ?? 0;
        }
        $pctCadenaAnt = $totalVentaAnt > 0 ? round(($totalCostoAnt / $totalVentaAnt) * 100, 2) : null;

        $variacionPPCadena       = ($pctCadena !== null && $pctCadenaAnt !== null) ? round($pctCadena - $pctCadenaAnt, 2) : null;
        $variacionRelativaCadena = ($pctCadenaAnt && $pctCadenaAnt != 0 && $pctCadena !== null)
            ? round((($pctCadena - $pctCadenaAnt) / $pctCadenaAnt) * 100, 2)
            : null;

        // Semáforo counts
        $sem = ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'sin_datos' => 0];
        foreach ($datos as $d) {
            $sem[$d['semaforo']] = ($sem[$d['semaforo']] ?? 0) + 1;
        }

        // Ranking (mayor a menor pct_actual)
        $ranking = array_values($conDatos);
        usort($ranking, fn($a, $b) => $b['pct_actual'] <=> $a['pct_actual']);

        return [
            'kpis_cadena' => [
                'pct_actual'          => $pctCadena,
                'pct_anterior'        => $pctCadenaAnt,
                'variacion_pp'        => $variacionPPCadena,
                'variacion_relativa'  => $variacionRelativaCadena,
                'semaforo'            => $this->clasificarSemaforo($pctCadena),
                'total_venta'         => round($totalVenta,  2),
                'total_costo'         => round($totalCosto,  2),
                'promedio_ventas_m2'  => $promedioVentasM2,
            ],
            'semaforo_counts' => $sem,
            'ranking'         => $ranking,
            'sucursales'      => $datos,
            'fecha_desde'     => $fechaDesde,
            'fecha_hasta'     => $fechaHasta,
            'fecha_desde_ant' => $desdeAnterior,
            'fecha_hasta_ant' => $hastaAnterior,
        ];
    }
}
