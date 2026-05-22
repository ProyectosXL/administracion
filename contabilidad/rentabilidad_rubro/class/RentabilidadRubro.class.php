<?php

/**
 * RentabilidadRubro.class.php
 * Clase para obtener y calcular el reporte de rentabilidad por rubro
 */
class RentabilidadRubro
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // ── Helpers de período ────────────────────────────────────────────────────

    private function periodoToFechas(string $periodo): array
    {
        [$mes, $anio] = explode('-', trim($periodo));
        $desde = sprintf('%04d-%02d-01', (int)$anio, (int)$mes);
        $hasta = date('Y-m-t', strtotime($desde));
        return [$desde, $hasta];
    }

    public function generarPeriodos(string $desde, string $hasta): array
    {
        [$mesD, $anioD] = array_map('intval', explode('-', trim($desde)));
        [$mesH, $anioH] = array_map('intval', explode('-', trim($hasta)));

        $periodos = [];
        $m = $mesD; $a = $anioD;
        while ($a < $anioH || ($a === $anioH && $m <= $mesH)) {
            $periodos[] = "$m-$a";
            if (++$m > 12) { $m = 1; $a++; }
        }
        return $periodos;
    }

    private function periodosToRangoFechas(array $periodos): array
    {
        [$fdMin] = $this->periodoToFechas($periodos[0]);
        [, $fdMax] = $this->periodoToFechas($periodos[count($periodos) - 1]);
        return [$fdMin, $fdMax];
    }

    private function periodoANombre(string $periodo): string
    {
        static $meses = [
            1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',
            5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',
            9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre',
        ];
        [$mes, $anio] = array_map('intval', explode('-', trim($periodo)));
        return ($meses[$mes] ?? $mes) . ' ' . $anio;
    }

    // ── Helpers de filtro canal ───────────────────────────────────────────────

    /**
     * Filtro para RO_T_RESUMEN_FINAL_IE (columna NRO_SUCURSAL)
     */
    private function canalSqlFilter(string $canal): string
    {
        switch ($canal) {
            case 'ECOMMERCE':       return 'NRO_SUCURSAL = 102';
            case 'FRANQUICIAS':     return 'NRO_SUCURSAL = 100';
            case 'MAYORISTAS':      return 'NRO_SUCURSAL = 101';
            case 'OTROS':           return 'NRO_SUCURSAL = 103';
            case 'LOCALES PROPIOS': return 'NRO_SUCURSAL NOT IN (100, 101, 102, 103)';
            default:                return '1=1';
        }
    }

    /**
     * Filtro para RO_T_RENT_BRUTA_RUBRO (columna NRO_SUCURS).
     * Retorna [cláusula SQL, params a agregar].
     */
    private function buildCanalFilterRubro(string $canal): array
    {
        if ($canal === '') return ['', []];
        $clause = "AND CASE
                WHEN NRO_SUCURS = 102 THEN 'ECOMMERCE'
                WHEN NRO_SUCURS = 100 THEN 'FRANQUICIAS'
                WHEN NRO_SUCURS = 101 THEN 'MAYORISTAS'
                WHEN NRO_SUCURS = 103 THEN 'OTROS'
                ELSE 'LOCALES PROPIOS'
            END = ?";
        return [$clause, [[$canal, SQLSRV_PARAM_IN]]];
    }

    // ── Datos maestros ────────────────────────────────────────────────────────

    public function getCanales(): array
    {
        $sql = "
            SELECT DISTINCT
                CASE
                    WHEN NRO_SUCURSAL = 102 THEN 'ECOMMERCE'
                    WHEN NRO_SUCURSAL = 100 THEN 'FRANQUICIAS'
                    WHEN NRO_SUCURSAL = 101 THEN 'MAYORISTAS'
                    WHEN NRO_SUCURSAL = 103 THEN 'OTROS'
                    ELSE 'LOCALES PROPIOS'
                END AS CANAL
            FROM RO_T_RESUMEN_FINAL_IE
            ORDER BY CANAL
        ";
        $stmt = sqlsrv_query($this->conn, $sql);
        if ($stmt === false) return [];
        $canales = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['CANAL'] !== null && $row['CANAL'] !== '') $canales[] = $row['CANAL'];
        }
        sqlsrv_free_stmt($stmt);
        return array_unique($canales);
    }

    public function getRubros(): array
    {
        $sql = "
            SELECT DISTINCT RUBRO
            FROM RO_T_RENT_BRUTA_RUBRO
            WHERE RUBRO NOT IN ('RECUPEROS','PRORRATEABLES','SIN RUBRO')
            ORDER BY RUBRO
        ";
        $stmt = sqlsrv_query($this->conn, $sql);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['RUBRO'] !== null) $result[] = $row['RUBRO'];
        }
        sqlsrv_free_stmt($stmt);
        return $result;
    }

    public function getColoresPorRubro(string $rubro): array
    {
        $sql = "
            SELECT DISTINCT COLOR
            FROM RO_T_RENT_BRUTA_RUBRO
            WHERE RUBRO = ?
              AND COLOR IS NOT NULL
              AND COLOR <> ''
            ORDER BY COLOR
        ";
        $stmt = sqlsrv_query($this->conn, $sql, [[$rubro, SQLSRV_PARAM_IN]]);
        if ($stmt === false) return [];
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['COLOR'] !== null) $result[] = $row['COLOR'];
        }
        sqlsrv_free_stmt($stmt);
        return $result;
    }

    // ── Ventas sin IVA (base prorrateo gastos) ────────────────────────────────

    public function getVentasSinIVA(array $periodos, string $canal = ''): float
    {
        if (empty($periodos)) return 0.0;

        $placeholders = implode(', ', array_fill(0, count($periodos), '?'));
        $params = [];
        foreach ($periodos as $p) $params[] = [$p, SQLSRV_PARAM_IN];

        $filtroCanal = $this->canalSqlFilter($canal);

        $sql = "
            SELECT SUM(IMPORTE) AS VENTA_SIN_IVA
            FROM RO_T_RESUMEN_FINAL_IE
            WHERE PERIODO IN ($placeholders)
              AND COD_RUBRO IN ('1.5.', '1.6.', '1.7.', '1.8.')
              AND $filtroCanal
        ";
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            throw new RuntimeException('Error en getVentasSinIVA: ' . (sqlsrv_errors()[0]['message'] ?? 'Error desconocido'));
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return (float)($row['VENTA_SIN_IVA'] ?? 0);
    }

    // ── Gastos por categoría ──────────────────────────────────────────────────

    public function getGastosPorCategoria(array $periodos, string $canal = ''): array
    {
        if (empty($periodos)) return [];

        $placeholders = implode(', ', array_fill(0, count($periodos), '?'));
        $params = [];
        foreach ($periodos as $p) $params[] = [$p, SQLSRV_PARAM_IN];

        $filtroCanal = $this->canalSqlFilter($canal);

        $sql = "
            SELECT B.CAT_RUBRO_CONTABLE, SUM(A.IMPORTE) AS IMPORTE
            FROM RO_T_RESUMEN_FINAL_IE A
            INNER JOIN RO_T_RUBROS_CONTABLES B ON A.COD_RUBRO = B.COD_RUBRO
            WHERE A.PERIODO IN ($placeholders)
              AND A.COD_RUBRO NOT IN ('1.5.', '1.6.', '1.7.', '1.8.')
              AND $filtroCanal
            GROUP BY B.CAT_RUBRO_CONTABLE
        ";
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            throw new RuntimeException('Error en getGastosPorCategoria: ' . (sqlsrv_errors()[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[$row['CAT_RUBRO_CONTABLE'] ?? 'SIN_CATEGORIA'] = (float)($row['IMPORTE'] ?? 0);
        }
        sqlsrv_free_stmt($stmt);
        return $result;
    }

    private function mapearCategoria(string $cat): string
    {
        $map = [
            'Gastos de Comercialización' => 'gastos_comercializacion',
            'Gastos de Personal'          => 'gastos_personal',
            'Gastos de Ocupación'         => 'gastos_ocupacion',
            'Otros Gastos Operativos'     => 'otros_gastos_operativos',
            'Gastos de Estructura'        => 'gastos_estructura',
            'Bienes de Uso'               => 'bienes_de_uso',
        ];
        return $map[$cat] ?? 'otros';
    }

    // ── Venta y costo por dimensión con prorrateo de especiales ──────────────

    /**
     * Obtiene venta y costo agrupados por la dimensión indicada, distribuyendo
     * RECUPEROS y PRORRATEABLES proporcionalmente por participación de venta
     * de cada sucursal/mes entre los rubros normales.
     *
     * @param string $dimensionCol  Columna de agrupación: 'RUBRO', 'ORIGEN_PROD' o 'CATEGORIA_PADRE'
     * @param array  $filtrosExtra  Pares columna => valor para filtros adicionales (ej. RUBRO, COLOR)
     */
    private function getVentaCostoPorDimension(
        string $fechaDesde,
        string $fechaHasta,
        string $canal,
        string $dimensionCol,
        array  $filtrosExtra = []
    ): array {
        // Whitelist de columnas permitidas para evitar inyección
        $colsPermitidas = ['RUBRO', 'ORIGEN_PROD', 'CATEGORIA_PADRE'];
        if (!in_array($dimensionCol, $colsPermitidas, true)) {
            throw new InvalidArgumentException("Columna de dimensión no permitida: $dimensionCol");
        }

        [$canalClause, $canalParams] = $this->buildCanalFilterRubro($canal);

        // Filtros extra (RUBRO=?, COLOR=?) — solo columnas de la whitelist
        $colsExtra = ['RUBRO', 'COLOR', 'CATEGORIA_PADRE', 'ORIGEN_PROD'];
        $extraClause = '';
        $extraParams = [];
        foreach ($filtrosExtra as $col => $val) {
            if (!in_array($col, $colsExtra, true)) continue;
            if ($val !== '' && $val !== null) {
                $extraClause .= " AND $col = ?";
                $extraParams[] = [$val, SQLSRV_PARAM_IN];
            }
        }

        // ── Paso A: RECUPEROS y PRORRATEABLES por FECHA + NRO_SUCURS ─────────
        $paramsA = array_merge(
            [[$fechaDesde, SQLSRV_PARAM_IN], [$fechaHasta, SQLSRV_PARAM_IN]],
            $canalParams
        );
        $sqlA = "
            SELECT FECHA, NRO_SUCURS, RUBRO,
                   SUM(VENTA) AS VENTA, SUM(COSTO) AS COSTO
            FROM RO_T_RENT_BRUTA_RUBRO
            WHERE FECHA BETWEEN ? AND ?
              AND RUBRO IN ('RECUPEROS','PRORRATEABLES')
              $canalClause
            GROUP BY FECHA, NRO_SUCURS, RUBRO
        ";
        $stmtA = sqlsrv_query($this->conn, $sqlA, $paramsA);
        if ($stmtA === false) {
            throw new RuntimeException('Error en getVentaCostoPorDimension (paso A): ' . (sqlsrv_errors()[0]['message'] ?? 'Error desconocido'));
        }
        $especiales = [];
        while ($row = sqlsrv_fetch_array($stmtA, SQLSRV_FETCH_ASSOC)) {
            $fk  = ($row['FECHA'] instanceof DateTime) ? $row['FECHA']->format('Y-m-d') : (string)$row['FECHA'];
            $suc = (string)$row['NRO_SUCURS'];
            $especiales[$fk][$suc][$row['RUBRO']] = [
                'venta' => (float)($row['VENTA'] ?? 0),
                'costo' => (float)($row['COSTO'] ?? 0),
            ];
        }
        sqlsrv_free_stmt($stmtA);

        // ── Paso B: rubros normales por FECHA + NRO_SUCURS + dimensión ────────
        // REVISAR: filtrosExtra no se aplican en paso A; si RECUPEROS corresponden
        // solo al rubro filtrado, este prorrateo puede sobre-asignar.
        $paramsB = array_merge(
            [[$fechaDesde, SQLSRV_PARAM_IN], [$fechaHasta, SQLSRV_PARAM_IN]],
            $canalParams,
            $extraParams
        );
        $sqlB = "
            SELECT FECHA, NRO_SUCURS,
                   $dimensionCol AS DIM_VALUE,
                   SUM(VENTA) AS VENTA, SUM(COSTO) AS COSTO
            FROM RO_T_RENT_BRUTA_RUBRO
            WHERE FECHA BETWEEN ? AND ?
              AND RUBRO NOT IN ('RECUPEROS','PRORRATEABLES','SIN RUBRO')
              $canalClause
              $extraClause
            GROUP BY FECHA, NRO_SUCURS, $dimensionCol
        ";
        $stmtB = sqlsrv_query($this->conn, $sqlB, $paramsB);
        if ($stmtB === false) {
            throw new RuntimeException('Error en getVentaCostoPorDimension (paso B): ' . (sqlsrv_errors()[0]['message'] ?? 'Error desconocido'));
        }
        $normales = [];
        while ($row = sqlsrv_fetch_array($stmtB, SQLSRV_FETCH_ASSOC)) {
            $fk  = ($row['FECHA'] instanceof DateTime) ? $row['FECHA']->format('Y-m-d') : (string)$row['FECHA'];
            $suc = (string)$row['NRO_SUCURS'];
            $dim = (string)($row['DIM_VALUE'] ?? 'N/A');
            if (!isset($normales[$fk][$suc][$dim])) {
                $normales[$fk][$suc][$dim] = ['venta' => 0.0, 'costo' => 0.0];
            }
            $normales[$fk][$suc][$dim]['venta'] += (float)($row['VENTA'] ?? 0);
            $normales[$fk][$suc][$dim]['costo'] += (float)($row['COSTO'] ?? 0);
        }
        sqlsrv_free_stmt($stmtB);

        // ── Paso C: distribuir especiales por participación de venta ──────────
        foreach ($especiales as $fk => $sucursales) {
            foreach ($sucursales as $suc => $rubroEsp) {
                $ventaTotalSucMes = 0.0;
                if (isset($normales[$fk][$suc])) {
                    foreach ($normales[$fk][$suc] as $dd) $ventaTotalSucMes += $dd['venta'];
                }

                foreach ($rubroEsp as $nombreEsp => $importes) {
                    if ($ventaTotalSucMes == 0) {
                        error_log("RentabilidadRubro: $nombreEsp sin distribuir en $fk suc=$suc (venta_total=0)");
                        if (!isset($normales[$fk][$suc]['SIN ASIGNAR'])) {
                            $normales[$fk][$suc]['SIN ASIGNAR'] = ['venta' => 0.0, 'costo' => 0.0];
                        }
                        $normales[$fk][$suc]['SIN ASIGNAR']['venta'] += $importes['venta'];
                        $normales[$fk][$suc]['SIN ASIGNAR']['costo'] += $importes['costo'];
                        continue;
                    }
                    foreach ($normales[$fk][$suc] as &$dimData) {
                        $coef = $dimData['venta'] / $ventaTotalSucMes;
                        $dimData['venta'] += $importes['venta'] * $coef;
                        $dimData['costo'] += $importes['costo'] * $coef;
                    }
                    unset($dimData);
                }
            }
        }

        // ── Agregar por dimensión (sumar todas las fechas/sucursales) ──────────
        $result = [];
        foreach ($normales as $sucursales) {
            foreach ($sucursales as $dims) {
                foreach ($dims as $dimValue => $data) {
                    if (!isset($result[$dimValue])) {
                        $result[$dimValue] = ['venta' => 0.0, 'costo' => 0.0];
                    }
                    $result[$dimValue]['venta'] += $data['venta'];
                    $result[$dimValue]['costo'] += $data['costo'];
                }
            }
        }
        return $result;
    }

    /**
     * Wrapper público para compatibilidad — retorna venta/costo por RUBRO.
     */
    public function getVentaCostoPorRubro(string $fechaDesde, string $fechaHasta, string $canal = ''): array
    {
        return $this->getVentaCostoPorDimension($fechaDesde, $fechaHasta, $canal, 'RUBRO');
    }

    // ── Cotización USD ────────────────────────────────────────────────────────

    public function getCotizacionPromedio(array $periodos): ?float
    {
        if (empty($periodos)) return null;

        $conditions = [];
        $params     = [];
        foreach ($periodos as $p) {
            [$mes, $anio] = array_map('intval', explode('-', trim($p)));
            $conditions[] = '(Año = ? AND Mes = ?)';
            $params[]     = [$anio, SQLSRV_PARAM_IN];
            $params[]     = [$mes,  SQLSRV_PARAM_IN];
        }

        $sql = "
            SELECT AVG(TCC) AS TCC_PROMEDIO, COUNT(*) AS MESES_ENCONTRADOS
            FROM RO_V_DOLAR_OFICIAL_BCRA
            WHERE " . implode(' OR ', $conditions);

        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            throw new RuntimeException('Error en getCotizacionPromedio: ' . (sqlsrv_errors()[0]['message'] ?? 'Error desconocido'));
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if (!$row || (int)$row['MESES_ENCONTRADOS'] === 0 || $row['TCC_PROMEDIO'] === null) {
            return null;
        }
        return (float)$row['TCC_PROMEDIO'];
    }

    // ── Indicadores ──────────────────────────────────────────────────────────

    /**
     * Calcula los 5 indicadores que se muestran en la tabla.
     * Los gastos no se incluyen aquí; se calculan aparte en el TOTAL para los KPIs.
     */
    private function calcularIndicadores(float $venta, float $costo): array
    {
        $safe = fn($n, $d) => ($d != 0) ? $n / $d : null;
        $rb   = $venta - $costo;
        return [
            'venta'           => $venta,
            'costo'           => $costo,
            'resultado_bruto' => $rb,
            'margen_bruto'    => $safe($rb, $venta),
            'markup'          => $safe($venta, $costo),
        ];
    }

    /**
     * Divide los campos monetarios por TCC para conversión a USD.
     * Los ratios (margen_bruto, markup, participacion_venta) no se convierten.
     */
    private function convertirADolar(array $ind, float $tcc): array
    {
        $monetarios = [
            'venta', 'costo', 'resultado_bruto',
            'gastos_comercializacion', 'gastos_personal', 'gastos_ocupacion',
            'otros_gastos_operativos', 'total_gastos_operativos',
            'gastos_estructura', 'bienes_de_uso',
            'resultado_operativo', 'resultado_explotacion',
        ];
        foreach ($monetarios as $campo) {
            if (array_key_exists($campo, $ind) && $ind[$campo] !== null) {
                $ind[$campo] = $ind[$campo] / $tcc;
            }
        }
        return $ind;
    }

    // ── Períodos faltantes ────────────────────────────────────────────────────

    private function getPeriodosFaltantes(array $periodos, string $fechaDesde, string $fechaHasta, string $canal): array
    {
        [$canalClause, $canalParams] = $this->buildCanalFilterRubro($canal);

        $params = array_merge(
            [[$fechaDesde, SQLSRV_PARAM_IN], [$fechaHasta, SQLSRV_PARAM_IN]],
            $canalParams
        );

        $sql = "
            SELECT DISTINCT MONTH(FECHA) AS MES, YEAR(FECHA) AS ANIO
            FROM RO_T_RENT_BRUTA_RUBRO
            WHERE FECHA BETWEEN ? AND ?
              $canalClause
        ";
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            throw new RuntimeException('Error en getPeriodosFaltantes: ' . (sqlsrv_errors()[0]['message'] ?? 'Error desconocido'));
        }
        $conDatos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $conDatos[] = (int)$row['MES'] . '-' . (int)$row['ANIO'];
        }
        sqlsrv_free_stmt($stmt);
        return array_values(array_diff($periodos, $conDatos));
    }

    // ── Procesamiento de período ──────────────────────────────────────────────

    public function procesarPeriodo(string $desde, string $hasta): void
    {
        $stmt = sqlsrv_query($this->conn, "EXEC RO_SP_RENTABILIDAD_BRUTA_RUBRO_CATEGORIA ?, ?", [
            [$desde, SQLSRV_PARAM_IN],
            [$hasta,  SQLSRV_PARAM_IN],
        ]);
        if ($stmt === false) {
            throw new RuntimeException('Error al procesar el período: ' . (sqlsrv_errors()[0]['message'] ?? 'Error desconocido'));
        }
        sqlsrv_free_stmt($stmt);
    }

    // ── Constructor del reporte genérico ──────────────────────────────────────

    /**
     * Construye la estructura de reporte para cualquier dimensión.
     * Llamado por buildReporte(), buildReportePorOrigen(), buildReportePorCategoria().
     */
    private function buildReporteConDimensiones(
        string $periodoDesde,
        string $periodoHasta,
        string $canal,
        string $moneda,
        string $dimensionCol,
        array  $filtrosExtra = []
    ): array {
        $periodos = $this->generarPeriodos($periodoDesde, $periodoHasta);
        [$fechaDesde, $fechaHasta] = $this->periodosToRangoFechas($periodos);

        // Verificar períodos faltantes
        $periodosFaltantes = $this->getPeriodosFaltantes($periodos, $fechaDesde, $fechaHasta, $canal);
        if (!empty($periodosFaltantes)) {
            $detalleFaltantes = [];
            foreach ($periodosFaltantes as $p) {
                [$fdD, $fdH] = $this->periodoToFechas($p);
                $detalleFaltantes[] = [
                    'periodo'     => $p,
                    'nombre'      => $this->periodoANombre($p),
                    'fecha_desde' => $fdD,
                    'fecha_hasta' => $fdH,
                ];
            }
            $periodosOk = array_values(array_diff($periodos, $periodosFaltantes));
            return [
                'success'            => false,
                'code'               => 'PERIODOS_FALTANTES',
                'message'            => 'Algunos meses del rango seleccionado no tienen datos calculados.',
                'periodos_faltantes' => $detalleFaltantes,
                'periodos_ok'        => array_map(fn($p) => ['periodo' => $p, 'nombre' => $this->periodoANombre($p)], $periodosOk),
            ];
        }

        // Datos de venta/costo por dimensión (con prorrateo de RECUPEROS)
        $ventaCosto = $this->getVentaCostoPorDimension($fechaDesde, $fechaHasta, $canal, $dimensionCol, $filtrosExtra);
        if (empty($ventaCosto)) {
            return [
                'success'    => false,
                'message'    => 'No se encontraron datos para el período y filtros seleccionados.',
                'dimensiones'=> [],
                'data'       => [],
                'kpis'       => [],
            ];
        }

        // Base de prorrateo de gastos
        $ventasSinIVA = $this->getVentasSinIVA($periodos, $canal);
        $ventaTotalBruta = array_sum(array_column($ventaCosto, 'venta'));
        $baseCoef = ($ventasSinIVA != 0) ? $ventasSinIVA : $ventaTotalBruta;

        // Gastos por categoría
        $gastosBD  = $this->getGastosPorCategoria($periodos, $canal);
        $gastosCat = [
            'gastos_comercializacion' => 0.0,
            'gastos_personal'         => 0.0,
            'gastos_ocupacion'        => 0.0,
            'otros_gastos_operativos' => 0.0,
            'gastos_estructura'       => 0.0,
            'bienes_de_uso'           => 0.0,
        ];
        foreach ($gastosBD as $cat => $importe) {
            $clave = $this->mapearCategoria($cat);
            if (isset($gastosCat[$clave])) $gastosCat[$clave] += $importe;
        }

        // Ordenar dimensiones por venta descendente
        uasort($ventaCosto, fn($a, $b) => $b['venta'] <=> $a['venta']);
        $dimensiones = array_keys($ventaCosto);

        $ventaTotal = array_sum(array_column($ventaCosto, 'venta'));
        $costoTotal = array_sum(array_column($ventaCosto, 'costo'));
        $safe = fn($n, $d) => ($d != 0) ? $n / $d : null;

        // Indicadores por dimensión
        $data = [];
        foreach ($dimensiones as $dim) {
            $data[$dim] = $this->calcularIndicadores(
                $ventaCosto[$dim]['venta'],
                $ventaCosto[$dim]['costo']
            );
            $data[$dim]['participacion_venta'] = $safe($ventaCosto[$dim]['venta'], $ventaTotal);
        }

        // TOTAL — incluye campos de gastos para los KPI cards
        $data['TOTAL'] = $this->calcularIndicadores($ventaTotal, $costoTotal);
        $gc = $gastosCat;
        $data['TOTAL']['gastos_comercializacion']  = $gc['gastos_comercializacion'];
        $data['TOTAL']['gastos_personal']          = $gc['gastos_personal'];
        $data['TOTAL']['gastos_ocupacion']         = $gc['gastos_ocupacion'];
        $data['TOTAL']['otros_gastos_operativos']  = $gc['otros_gastos_operativos'];
        $totalGastosOp = $gc['gastos_personal'] + $gc['gastos_ocupacion'] + $gc['otros_gastos_operativos'];
        $data['TOTAL']['total_gastos_operativos']  = $totalGastosOp;
        $data['TOTAL']['gastos_estructura']        = $gc['gastos_estructura'];
        $data['TOTAL']['bienes_de_uso']            = $gc['bienes_de_uso'];

        $rb = $data['TOTAL']['resultado_bruto'];
        $rc = $rb - $gc['gastos_comercializacion'];
        $ro = $rc - $totalGastosOp;
        $cm = $ro - $gc['bienes_de_uso'];
        $re = $cm - $gc['gastos_estructura'];
        $data['TOTAL']['resultado_operativo']   = $ro;
        $data['TOTAL']['resultado_explotacion'] = $re;
        $data['TOTAL']['participacion_venta']   = null;

        // Conversión a USD
        $tccPromedio = null;
        if ($moneda === 'USD') {
            $tccPromedio = $this->getCotizacionPromedio($periodos);
            if ($tccPromedio === null) {
                return [
                    'success' => false,
                    'message' => 'No se encontró cotización del dólar para el período seleccionado en RO_V_DOLAR_OFICIAL_BCRA.',
                ];
            }
            foreach ($data as $dim => $ind) {
                $data[$dim] = $this->convertirADolar($ind, $tccPromedio);
            }
        }

        // KPIs (sobre valores ya convertidos si corresponde)
        $total = $data['TOTAL'];
        $kpis = [
            'venta_total'                  => $total['venta'],
            'resultado_bruto'              => $total['resultado_bruto'],
            'rel_resultado_bruto'          => $safe($total['resultado_bruto'],   $total['venta']),
            'resultado_operativo'          => $total['resultado_operativo'],
            'rel_resultado_operativo'      => $safe($total['resultado_operativo'], $total['venta']),
            'resultado_explotacion'        => $total['resultado_explotacion'],
            'rel_resultado_explotacion'    => $safe($total['resultado_explotacion'], $total['venta']),
            // Cards de estructura de gastos (siempre en %, no se convierten)
            'pct_gastos_comercializacion'  => $safe($total['gastos_comercializacion'],  $total['venta']),
            'pct_gastos_operativos'        => $safe($total['total_gastos_operativos'],  $total['venta']),
            'pct_gastos_personal'          => $safe($total['gastos_personal'],          $total['venta']),
            'pct_gastos_ocupacion'         => $safe($total['gastos_ocupacion'],         $total['venta']),
            'pct_otros_gastos_operativos'  => $safe($total['otros_gastos_operativos'],  $total['venta']),
            'pct_gastos_estructura'        => $safe($total['gastos_estructura'],        $total['venta']),
        ];

        return [
            'success'      => true,
            'moneda'       => $moneda,
            'tcc_promedio' => $tccPromedio,
            'dimensiones'  => $dimensiones,
            'data'         => $data,
            'kpis'         => $kpis,
            'base_calculo' => [
                'monto'  => $baseCoef,
                'fuente' => ($ventasSinIVA != 0) ? 'sinIVA' : 'total',
            ],
        ];
    }

    // ── Métodos públicos de reporte ───────────────────────────────────────────

    public function buildReporte(string $periodoDesde, string $periodoHasta, string $canal = '', string $moneda = 'ARS'): array
    {
        return $this->buildReporteConDimensiones($periodoDesde, $periodoHasta, $canal, $moneda, 'RUBRO');
    }

    public function buildReportePorOrigen(
        string $periodoDesde,
        string $periodoHasta,
        string $canal,
        string $rubro,
        string $moneda
    ): array {
        $filtros = $rubro !== '' ? ['RUBRO' => $rubro] : [];
        return $this->buildReporteConDimensiones($periodoDesde, $periodoHasta, $canal, $moneda, 'ORIGEN_PROD', $filtros);
    }

    public function buildReportePorCategoria(
        string $periodoDesde,
        string $periodoHasta,
        string $canal,
        string $rubro,
        string $color,
        string $moneda
    ): array {
        $filtros = [];
        if ($rubro !== '') $filtros['RUBRO']  = $rubro;
        if ($color !== '') $filtros['COLOR']  = $color;
        return $this->buildReporteConDimensiones($periodoDesde, $periodoHasta, $canal, $moneda, 'CATEGORIA_PADRE', $filtros);
    }
}
