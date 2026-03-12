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

    /**
     * Convierte un período 'M-AAAA' a rango de fechas [desde, hasta]
     */
    private function periodoToFechas(string $periodo): array
    {
        [$mes, $anio] = explode('-', trim($periodo));
        $mes  = (int)$mes;
        $anio = (int)$anio;
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));
        return [$desde, $hasta];
    }

    /**
     * Genera lista de períodos 'M-AAAA' entre dos períodos inclusive
     */
    public function generarPeriodos(string $desde, string $hasta): array
    {
        [$mesD, $anioD] = array_map('intval', explode('-', trim($desde)));
        [$mesH, $anioH] = array_map('intval', explode('-', trim($hasta)));

        $periodos = [];
        $mesActual  = $mesD;
        $anioActual = $anioD;

        while (
            $anioActual < $anioH ||
            ($anioActual === $anioH && $mesActual <= $mesH)
        ) {
            $periodos[] = "$mesActual-$anioActual";
            $mesActual++;
            if ($mesActual > 12) {
                $mesActual = 1;
                $anioActual++;
            }
        }

        return $periodos;
    }

    /**
     * Convierte lista de períodos a rango de fechas SQL [fecha_desde, fecha_hasta]
     */
    private function periodosToRangoFechas(array $periodos): array
    {
        [$fdMin] = $this->periodoToFechas($periodos[0]);
        [, $fdMax] = $this->periodoToFechas($periodos[count($periodos) - 1]);
        return [$fdMin, $fdMax];
    }

    /**
     * Genera el fragmento SQL de filtro por canal sobre NRO_SUCURSAL.
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
     * Obtiene los canales disponibles desde RO_T_RESUMEN_FINAL_IE
     * usando el CASE WHEN sobre NRO_SUCURSAL.
     */
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
        if ($stmt === false) {
            return [];
        }
        $canales = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['CANAL'] !== null && $row['CANAL'] !== '') {
                $canales[] = $row['CANAL'];
            }
        }
        sqlsrv_free_stmt($stmt);
        return array_unique($canales);
    }

    /**
     * Obtiene el Total de Ventas sin IVA (COD_RUBRO 1.5 + 1.8) para el período y canal.
     * Es la base del coeficiente de prorrateo de gastos.
     */
    public function getVentasSinIVA(array $periodos, string $canal = ''): float
    {
        if (empty($periodos)) return 0.0;

        $placeholders = implode(', ', array_fill(0, count($periodos), '?'));
        $params = [];
        foreach ($periodos as $p) {
            $params[] = [$p, SQLSRV_PARAM_IN];
        }

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
            $errors = sqlsrv_errors();
            throw new RuntimeException(
                'Error en getVentasSinIVA: ' . ($errors[0]['message'] ?? 'Error desconocido')
            );
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return (float)($row['VENTA_SIN_IVA'] ?? 0);
    }

    /**
     * Obtiene Venta y Costo por Rubro en el rango de fechas
     */
    public function getVentaCostoPorRubro(string $fechaDesde, string $fechaHasta, string $canal = ''): array
    {
        $params = [
            [$fechaDesde, SQLSRV_PARAM_IN],
            [$fechaHasta,  SQLSRV_PARAM_IN],
        ];

        // Filtro de canal usando CASE WHEN sobre NRO_SUCURS
        if ($canal !== '') {
            $params[] = [$canal, SQLSRV_PARAM_IN];
            $filtroCanal = "
                AND CASE
                        WHEN NRO_SUCURS = 102 THEN 'ECOMMERCE'
                        WHEN NRO_SUCURS = 100 THEN 'FRANQUICIAS'
                        WHEN NRO_SUCURS = 101 THEN 'MAYORISTAS'
                        WHEN NRO_SUCURS = 103 THEN 'OTROS'
                        ELSE 'LOCALES PROPIOS'
                    END = ?
            ";
        } else {
            $filtroCanal = '';
        }

        $sql = "
            SELECT
                RUBRO,
                SUM(VENTA) AS VENTA,
                SUM(COSTO) AS COSTO
            FROM RO_T_RENT_BRUTA_RUBRO
            WHERE FECHA BETWEEN ? AND ?
              $filtroCanal
            GROUP BY RUBRO
            ORDER BY RUBRO
        ";

        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new RuntimeException(
                'Error en getVentaCostoPorRubro: ' . ($errors[0]['message'] ?? 'Error desconocido')
            );
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[$row['RUBRO']] = [
                'venta' => (float)($row['VENTA'] ?? 0),
                'costo' => (float)($row['COSTO'] ?? 0),
            ];
        }
        sqlsrv_free_stmt($stmt);
        return $result;
    }

    /**
     * Obtiene Gastos por CAT_RUBRO_CONTABLE para la lista de períodos y canal.
     * Excluye COD_RUBRO 1.5 y 1.8 (que son ventas, no gastos).
     * Filtra por canal via CASE WHEN sobre NRO_SUCURSAL.
     */
    public function getGastosPorCategoria(array $periodos, string $canal = ''): array
    {
        if (empty($periodos)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($periodos), '?'));
        $params = [];
        foreach ($periodos as $p) {
            $params[] = [$p, SQLSRV_PARAM_IN];
        }

        $filtroCanal = $this->canalSqlFilter($canal);

        $sql = "
            SELECT
                B.CAT_RUBRO_CONTABLE,
                SUM(A.IMPORTE) AS IMPORTE
            FROM RO_T_RESUMEN_FINAL_IE A
            INNER JOIN RO_T_RUBROS_CONTABLES B ON A.COD_RUBRO = B.COD_RUBRO
            WHERE A.PERIODO IN ($placeholders)
              AND A.COD_RUBRO NOT IN ('1.5.', '1.6.', '1.7.', '1.8.')
              AND $filtroCanal
            GROUP BY B.CAT_RUBRO_CONTABLE
        ";

        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new RuntimeException(
                'Error en getGastosPorCategoria: ' . ($errors[0]['message'] ?? 'Error desconocido')
            );
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $cat = $row['CAT_RUBRO_CONTABLE'] ?? 'SIN_CATEGORIA';
            $result[$cat] = (float)($row['IMPORTE'] ?? 0);
        }
        sqlsrv_free_stmt($stmt);
        return $result;
    }

    /**
     * Mapeo de CAT_RUBRO_CONTABLE a clave interna.
     * Los valores deben coincidir exactamente con lo que devuelve la BD.
     */
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

    /**
     * Calcula los indicadores de un rubro dado los valores base y los gastos totales
     */
    private function calcularIndicadores(
        float $venta,
        float $costo,
        float $gastosComercializacion,
        float $gastosPersonal,
        float $gastosOcupacion,
        float $otrosGastos,
        float $bienesDeUso,
        float $gastosEstructura
    ): array {
        $safe = fn($num, $den) => ($den != 0) ? $num / $den : null;

        $resultadoBruto        = $venta - $costo;
        $relCostoVenta         = $safe($costo, $venta);
        $markup                = $safe($venta, $costo);

        $resultadoComercial    = $resultadoBruto - $gastosComercializacion;
        $relResultadoComercial = $safe($resultadoComercial, $venta);

        $totalGastosOperativos  = $gastosPersonal + $gastosOcupacion + $otrosGastos;
        $resultadoOperativo    = $resultadoComercial - $totalGastosOperativos;
        $relResultadoOperativo = $safe($resultadoOperativo, $venta);

        $contribucionMarginal  = $resultadoOperativo - $bienesDeUso;

        $costoTotal            = $costo + $gastosComercializacion + $gastosPersonal
                                 + $gastosOcupacion + $otrosGastos + $gastosEstructura;
        $relCostoTotal         = $safe($costoTotal, $venta);

        $resultadoExplotacion  = $contribucionMarginal - $gastosEstructura;
        $relResultadoExplot    = $safe($resultadoExplotacion, $venta);

        return [
            'venta'                       => $venta,
            'costo'                       => $costo,
            'markup'                      => $markup,
            'resultado_bruto'             => $resultadoBruto,
            'rel_costo_venta'             => $relCostoVenta,
            'gastos_comercializacion'     => $gastosComercializacion,
            'resultado_comercial'         => $resultadoComercial,
            'rel_resultado_comercial'     => $relResultadoComercial,
            'gastos_personal'             => $gastosPersonal,
            'gastos_ocupacion'            => $gastosOcupacion,
            'otros_gastos_operativos'     => $otrosGastos,
            'total_gastos_operativos'     => $totalGastosOperativos,
            'resultado_operativo'         => $resultadoOperativo,
            'rel_resultado_operativo'     => $relResultadoOperativo,
            'bienes_de_uso'               => $bienesDeUso,
            'contribucion_marginal_neta'  => $contribucionMarginal,
            'gastos_estructura'           => $gastosEstructura,
            'rel_costo_total'             => $relCostoTotal,
            'resultado_explotacion'       => $resultadoExplotacion,
            'rel_resultado_explotacion'   => $relResultadoExplot,
        ];
    }

    /**
     * Obtiene el TCC promedio del último día hábil de cada mes del período,
     * consultando la vista RO_V_DOLAR_OFICIAL_BCRA.
     * Retorna null si no hay cotización disponible para algún período.
     */
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

        $where = implode(' OR ', $conditions);

        $sql = "
            SELECT AVG(TCC) AS TCC_PROMEDIO, COUNT(*) AS MESES_ENCONTRADOS
            FROM RO_V_DOLAR_OFICIAL_BCRA
            WHERE $where
        ";

        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new RuntimeException(
                'Error en getCotizacionPromedio: ' . ($errors[0]['message'] ?? 'Error desconocido')
            );
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if (!$row || (int)$row['MESES_ENCONTRADOS'] === 0 || $row['TCC_PROMEDIO'] === null) {
            return null;
        }

        return (float)$row['TCC_PROMEDIO'];
    }

    /**
     * Divide por el TCC únicamente los campos monetarios (no ratios ni porcentajes).
     */
    private function convertirADolar(array $indicadores, float $tcc): array
    {
        $camposMonetarios = [
            'venta',
            'costo',
            'resultado_bruto',
            'gastos_comercializacion',
            'resultado_comercial',
            'gastos_personal',
            'gastos_ocupacion',
            'otros_gastos_operativos',
            'total_gastos_operativos',
            'resultado_operativo',
            'bienes_de_uso',
            'contribucion_marginal_neta',
            'gastos_estructura',
            'resultado_explotacion',
        ];

        foreach ($camposMonetarios as $campo) {
            if (array_key_exists($campo, $indicadores) && $indicadores[$campo] !== null) {
                $indicadores[$campo] = $indicadores[$campo] / $tcc;
            }
        }

        return $indicadores;
    }

    /**
     * Retorna los períodos del rango que NO tienen datos en RO_T_RENT_BRUTA_RUBRO.
     * Compara los períodos solicitados contra los meses-año distintos que efectivamente
     * existen en la tabla para el canal dado.
     */
    private function getPeriodosFaltantes(array $periodos, string $fechaDesde, string $fechaHasta, string $canal): array
    {
        // Filtro de canal igual que en getVentaCostoPorRubro
        $params = [
            [$fechaDesde, SQLSRV_PARAM_IN],
            [$fechaHasta,  SQLSRV_PARAM_IN],
        ];

        if ($canal !== '') {
            $params[] = [$canal, SQLSRV_PARAM_IN];
            $filtroCanal = "
                AND CASE
                        WHEN NRO_SUCURS = 102 THEN 'ECOMMERCE'
                        WHEN NRO_SUCURS = 100 THEN 'FRANQUICIAS'
                        WHEN NRO_SUCURS = 101 THEN 'MAYORISTAS'
                        WHEN NRO_SUCURS = 103 THEN 'OTROS'
                        ELSE 'LOCALES PROPIOS'
                    END = ?
            ";
        } else {
            $filtroCanal = '';
        }

        $sql = "
            SELECT DISTINCT MONTH(FECHA) AS MES, YEAR(FECHA) AS ANIO
            FROM RO_T_RENT_BRUTA_RUBRO
            WHERE FECHA BETWEEN ? AND ?
              $filtroCanal
        ";

        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new RuntimeException(
                'Error en getPeriodosFaltantes: ' . ($errors[0]['message'] ?? 'Error desconocido')
            );
        }

        $conDatos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $conDatos[] = (int)$row['MES'] . '-' . (int)$row['ANIO'];
        }
        sqlsrv_free_stmt($stmt);

        // Devuelve los solicitados que no aparecen en la tabla
        return array_values(array_diff($periodos, $conDatos));
    }

    /**
     * Ejecuta el SP que procesa la rentabilidad bruta por rubro para un período.
     * $desde y $hasta deben ser fechas SQL 'YYYY-MM-DD'.
     */
    public function procesarPeriodo(string $desde, string $hasta): void
    {
        $params = [
            [$desde, SQLSRV_PARAM_IN],
            [$hasta,  SQLSRV_PARAM_IN],
        ];
        $stmt = sqlsrv_query($this->conn, "EXEC RO_SP_RENTABILIDAD_BRUTA_RUBRO ?, ?", $params);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new RuntimeException(
                'Error al procesar el período: ' . ($errors[0]['message'] ?? 'Error desconocido')
            );
        }
        sqlsrv_free_stmt($stmt);
    }

    /**
     * Convierte 'M-AAAA' a nombre legible en español, p.ej. 'Enero 2025'.
     */
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

    /**
     * Construye el reporte completo
     */
    public function buildReporte(string $periodoDesde, string $periodoHasta, string $canal = '', string $moneda = 'ARS'): array
    {
        $periodos = $this->generarPeriodos($periodoDesde, $periodoHasta);
        [$fechaDesde, $fechaHasta] = $this->periodosToRangoFechas($periodos);

        // 0. Validar que existan datos en RO_T_RENT_BRUTA_RUBRO para TODOS los períodos solicitados
        $periodosFaltantes = $this->getPeriodosFaltantes($periodos, $fechaDesde, $fechaHasta, $canal);
        if (!empty($periodosFaltantes)) {
            $periodosOk       = array_values(array_diff($periodos, $periodosFaltantes));

            // Construir lista enriquecida con nombre legible y fechas SQL para procesar
            $detalleFaltantes = [];
            foreach ($periodosFaltantes as $p) {
                [$fdDesde, $fdHasta] = $this->periodoToFechas($p);
                $detalleFaltantes[] = [
                    'periodo'     => $p,
                    'nombre'      => $this->periodoANombre($p),
                    'fecha_desde' => $fdDesde,
                    'fecha_hasta' => $fdHasta,
                ];
            }
            $detalleOk = array_map(fn($p) => [
                'periodo' => $p,
                'nombre'  => $this->periodoANombre($p),
            ], $periodosOk);

            return [
                'success'           => false,
                'code'              => 'PERIODOS_FALTANTES',
                'message'           => 'Algunos meses del rango seleccionado no tienen datos calculados.',
                'periodos_faltantes'=> $detalleFaltantes,
                'periodos_ok'       => $detalleOk,
            ];
        }
        $ventaCosto = $this->getVentaCostoPorRubro($fechaDesde, $fechaHasta, $canal);
        if (empty($ventaCosto)) {
            return [
                'success' => false,
                'message' => 'No se encontraron datos para el período seleccionado.',
                'rubros'  => [],
                'data'    => [],
                'kpis'    => [],
            ];
        }

        $rubros     = array_keys($ventaCosto);
        $ventaTotal = array_sum(array_column($ventaCosto, 'venta'));
        $costoTotal = array_sum(array_column($ventaCosto, 'costo'));

        // 4. Base del coeficiente: Ventas sin IVA desde RO_T_RESUMEN_FINAL_IE.
        //    Si la consulta devuelve 0 (COD_RUBRO con formato distinto),
        //    se usa ventaTotal de RO_T_RENT_BRUTA_RUBRO como denominador equivalente.
        $ventasSinIVA = $this->getVentasSinIVA($periodos, $canal);
        $baseCoef = ($ventasSinIVA != 0) ? $ventasSinIVA : $ventaTotal;

        // 3. Gastos por categoría desde RO_T_RESUMEN_FINAL_IE
        $gastosBD = $this->getGastosPorCategoria($periodos, $canal);

        // Normalizar categorías a claves internas
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
            if (isset($gastosCat[$clave])) {
                $gastosCat[$clave] += $importe;
            }
        }

        // 5. Coeficientes: total_gasto_categoria / base
        $coeficientes = [];
        foreach ($gastosCat as $clave => $totalGasto) {
            $coeficientes[$clave] = ($baseCoef != 0) ? $totalGasto / $baseCoef : 0.0;
        }

        // 6. Para cada rubro: importe_gasto = venta_rubro * coeficiente
        $data = [];

        foreach ($rubros as $rubro) {
            $venta = $ventaCosto[$rubro]['venta'];
            $costo = $ventaCosto[$rubro]['costo'];

            $gastosRubro = [];
            foreach ($coeficientes as $clave => $coef) {
                $gastosRubro[$clave] = $venta * $coef;
            }

            $data[$rubro] = $this->calcularIndicadores(
                $venta,
                $costo,
                $gastosRubro['gastos_comercializacion'],
                $gastosRubro['gastos_personal'],
                $gastosRubro['gastos_ocupacion'],
                $gastosRubro['otros_gastos_operativos'],
                $gastosRubro['bienes_de_uso'],
                $gastosRubro['gastos_estructura']
            );
        }

        // 7. Columna TOTAL
        $data['TOTAL'] = $this->calcularIndicadores(
            $ventaTotal,
            $costoTotal,
            $gastosCat['gastos_comercializacion'],
            $gastosCat['gastos_personal'],
            $gastosCat['gastos_ocupacion'],
            $gastosCat['otros_gastos_operativos'],
            $gastosCat['bienes_de_uso'],
            $gastosCat['gastos_estructura']
        );

        // 8. Conversión a USD si se solicita
        $tccPromedio = null;
        if ($moneda === 'USD') {
            $tccPromedio = $this->getCotizacionPromedio($periodos);
            if ($tccPromedio === null) {
                return [
                    'success' => false,
                    'message' => 'No se encontró cotización del dólar para el período seleccionado en RO_V_DOLAR_OFICIAL_BCRA.',
                ];
            }
            foreach ($data as $rubro => $indicadores) {
                $data[$rubro] = $this->convertirADolar($indicadores, $tccPromedio);
            }
        }

        // 9. KPIs (calculados sobre los valores ya convertidos si corresponde)
        $total = $data['TOTAL'];
        $safe  = fn($num, $den) => ($den != 0) ? $num / $den : null;

        $kpis = [
            'venta_total'              => $total['venta'],
            'resultado_bruto'          => $total['resultado_bruto'],
            'rel_resultado_bruto'      => $safe($total['resultado_bruto'], $total['venta']),
            'resultado_operativo'      => $total['resultado_operativo'],
            'rel_resultado_operativo'  => $safe($total['resultado_operativo'], $total['venta']),
            'resultado_explotacion'    => $total['resultado_explotacion'],
            'rel_resultado_explotacion'=> $safe($total['resultado_explotacion'], $total['venta']),
        ];

        return [
            'success'       => true,
            'moneda'        => $moneda,
            'tcc_promedio'  => $tccPromedio,
            'rubros'        => $rubros,
            'data'          => $data,
            'kpis'          => $kpis,
            'base_calculo'  => [
                'monto'  => $baseCoef,
                'fuente' => ($ventasSinIVA != 0) ? 'sinIVA' : 'total',
            ],
        ];
    }
}
