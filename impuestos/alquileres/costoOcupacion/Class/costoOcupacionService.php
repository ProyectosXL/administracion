<?php

/**
 * Servicio para Costo de Ocupación
 * Maneja la lógica de negocio y acceso a datos
 */
class CostoOcupacionService
{
    private $cid_central;
    private $alquiler;

    // Mapeo de agrupación de conceptos según tabla adjunta
    private $agrupacionConceptos = [
        'Alquiler' => [1, 2, 8], // Alquiler, Complementario
        'Alquiler porcentual' => [6, 7], // Porc. S/ventas brutas, Porc. S/ventas netas
        'Fondo de promoción' => [9, 16, 17], // Fondo de promoción (% VMM), Fondo promoción mensual (S/Vtas. Brutas), Fondo de promoción mensual (S/Vtas. Netas)
        'Baulera' => [3], // Baulera
        'Gastos varios' => [10, 13, 14], // Gastos publicidad, Gastos administrativos, Gastos administrativos (S/Vtas. netas)
        'Expensas' => [11], // Expensas + imp expensables
        'Diferencia' => [12], // Diferencia acuerdo
        'Llave' => [4, 5, 18] // Concepto 4 (25% de Alquiler+Complementario+VMM) + Comisiones (5) + FPC Lanzamiento (18). Solo si hay valor en RO_V_CONTRATOS_VALOR_LLAVE
    ];

    public function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/administracion/class/conexion.php';
        require_once __DIR__ . '/../../Class/Alquiler.php';

        // Iniciar sesión PRIMERO para saber a qué base de datos conectar
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $cid = new Conexion();

        // Determinar a qué base de datos conectar según el entorno
        $database = 'central';
        if (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') {
            $database = 'uy';
        }

        // Conectar a la base de datos correcta
        $this->cid_central = $cid->conectar($database);

        // Validar que la conexión sea exitosa
        if ($this->cid_central === false) {
            throw new Exception("Error al conectar con la base de datos {$database}");
        }

        $this->alquiler = new Alquiler();
    }

    /**
     * Calcula el rango de fechas por defecto (últimos 12 meses completos previos)
     * @return array ['desde' => 'YYYY-MM-DD', 'hasta' => 'YYYY-MM-DD']
     */
    public function calcularRangoDefault()
    {
        $hoy = new DateTime();

        // Primer día del mes actual
        $primerDiaMesActual = new DateTime($hoy->format('Y-m-01'));

        // Restar 1 día para ir al mes anterior
        $ultimoDiaMesAnterior = clone $primerDiaMesActual;
        $ultimoDiaMesAnterior->modify('-1 day');

        // Desde: 12 meses atrás desde el mes anterior
        $desde = clone $ultimoDiaMesAnterior;
        $desde->modify('-11 months');
        $desde = new DateTime($desde->format('Y-m-01'));

        return [
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $ultimoDiaMesAnterior->format('Y-m-d')
        ];
    }

    /**
     * Genera array de meses en formato YYYY-MM entre dos fechas
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array
     */
    private function generarMeses($fechaDesde, $fechaHasta)
    {
        $meses = [];
        $inicio = new DateTime($fechaDesde);
        $fin = new DateTime($fechaHasta);

        // Ajustar al primer día del mes
        $inicio = new DateTime($inicio->format('Y-m-01'));
        $fin = new DateTime($fin->format('Y-m-01'));

        while ($inicio <= $fin) {
            $meses[] = $inicio->format('Y-m');
            $inicio->modify('+1 month');
        }

        return $meses;
    }

    /**
     * Convierte periodo M-YYYY o MM-YYYY a YYYY-MM
     * @param string $periodo Formato "10-2024" o "02-2024"
     * @return string Formato "2024-10" o "2024-02"
     */
    private function normalizarPeriodo($periodo)
    {
        $partes = explode('-', $periodo);
        if (count($partes) != 2)
            return $periodo;

        $mes = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
        $anio = $partes[1];

        return "{$anio}-{$mes}";
    }

    /**
     * Obtiene gastos por concepto y mes para una sucursal (con agrupación)
     * @param string $idSucursal
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array
     */
    public function obtenerGastosPorMes($idSucursal, $fechaDesde, $fechaHasta)
    {
        $meses = $this->generarMeses($fechaDesde, $fechaHasta);

        // Construir lista de periodos en formato M-YYYY y MM-YYYY (ambos formatos)
        $periodos = [];
        foreach ($meses as $mes) {
            // Usar explode en lugar de DateTime para evitar problemas con días del mes
            list($anio, $mesNum) = explode('-', $mes);

            // Agregar ambos formatos: con y sin cero a la izquierda
            $p1 = (int) $mesNum . '-' . $anio; // Ej: "2-2025"
            $p2 = $mesNum . '-' . $anio;      // Ej: "02-2025"

            $periodos[] = $p1;
            $periodos[] = $p2;

            // Log para debug específico de febrero
            if ($mes === '2025-02') {
                error_log("DEBUG FEBRERO - Mes: {$mes}, P1: [{$p1}], P2: [{$p2}]");
            }
        }

        // Eliminar duplicados y reindexar
        $periodos = array_values(array_unique($periodos));

        // Log para verificar que febrero está en la lista
        $tieneFebreroSinCero = in_array('2-2025', $periodos);
        $tieneFebreroConCero = in_array('02-2025', $periodos);
        error_log("DEBUG FEBRERO - ¿Tiene '2-2025'? " . ($tieneFebreroSinCero ? 'SI' : 'NO') . ", ¿Tiene '02-2025'? " . ($tieneFebreroConCero ? 'SI' : 'NO'));

        // Log para debug
        error_log("obtenerGastosPorMes - Sucursal: {$idSucursal}, Períodos buscados: " . implode(", ", $periodos));

        $periodosStr = "'" . implode("','", $periodos) . "'";

        // Usar LTRIM y RTRIM para eliminar espacios en blanco del campo PERIODO
        $sql = "
            SELECT 
                d.PERIODO,
                d.ID_CA,
                c.CONCEPTO,
                CAST(d.IMPORTE AS FLOAT) as IMPORTE
            FROM RO_T_DETALLE_ALQUILERES d
            INNER JOIN RO_T_CONCEPTOS_ALQUILERES c ON d.ID_CA = c.ID_CA
            WHERE d.NRO_SUCURS = ?
            AND LTRIM(RTRIM(d.PERIODO)) IN ({$periodosStr})
            ORDER BY c.CONCEPTO, d.PERIODO
        ";

        try {
            $params = [$idSucursal];
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);

            if (!$stmt) {
                throw new Exception("Error al preparar consulta de gastos");
            }

            sqlsrv_execute($stmt);

            // Almacenar datos por ID_CA
            $datosPorConcepto = [];
            $registrosEncontrados = 0;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $registrosEncontrados++;
                $periodoOriginal = $row['PERIODO'];
                $periodoNorm = $this->normalizarPeriodo($periodoOriginal);
                $idCa = $row['ID_CA'];

                // Log detallado para febrero
                if (strpos($periodoOriginal, '2-2025') !== false || strpos($periodoOriginal, '02-2025') !== false) {
                    error_log("FEBRERO ENCONTRADO - Original: [{$periodoOriginal}], Normalizado: [{$periodoNorm}], ID_CA: {$idCa}, Importe: {$row['IMPORTE']}");
                }

                if (!isset($datosPorConcepto[$idCa])) {
                    $datosPorConcepto[$idCa] = [];
                }

                $datosPorConcepto[$idCa][$periodoNorm] = $row['IMPORTE'];
            }

            // Log para debug
            error_log("obtenerGastosPorMes - Registros encontrados: {$registrosEncontrados}");
            if ($registrosEncontrados > 0 && count($datosPorConcepto) > 0) {
                $primerConcepto = array_key_first($datosPorConcepto);
                error_log("obtenerGastosPorMes - Primer concepto ({$primerConcepto}) tiene meses: " . implode(", ", array_keys($datosPorConcepto[$primerConcepto])));

                // Verificar específicamente febrero
                if (isset($datosPorConcepto[$primerConcepto]['2025-02'])) {
                    error_log("obtenerGastosPorMes - Concepto {$primerConcepto} TIENE febrero con valor: " . $datosPorConcepto[$primerConcepto]['2025-02']);
                } else {
                    error_log("obtenerGastosPorMes - Concepto {$primerConcepto} NO TIENE clave '2025-02'. Claves disponibles: " . implode(", ", array_keys($datosPorConcepto[$primerConcepto])));
                }
            }

            // Calcular Llave = Concepto 4 calculado (25% de conceptos 1, 2 y 8) + Comisiones (5) + FPC Lanzamiento (18) de BD
            // Solo se calcula si hay valor en RO_V_CONTRATOS_VALOR_LLAVE, de lo contrario es 0
            $llaveCalculada = [];
            foreach ($meses as $mes) {
                $suma = 0;

                // Verificar si existe valor en la vista RO_V_CONTRATOS_VALOR_LLAVE para esta sucursal y período
                $tieneValorLlave = $this->verificarValorLlaveNegocio($idSucursal, $mes);

                if ($tieneValorLlave) {
                    // Si hay valor mayor a 0, aplicar cálculo actual
                    foreach ([1, 2, 8] as $idCa) {
                        if (isset($datosPorConcepto[$idCa][$mes])) {
                            $suma += $datosPorConcepto[$idCa][$mes];
                        }
                    }
                    $llaveCalculada[$mes] = $suma * 0.25;
                } else {
                    // Si no hay valor o es 0, la llave debe ser 0
                    $llaveCalculada[$mes] = 0;
                }
            }

            // Agregar Llave calculada
            $datosPorConcepto[4] = $llaveCalculada;

            // Agrupar según mapeo
            $gastosAgrupados = [];

            foreach ($this->agrupacionConceptos as $nombreGrupo => $idsConceptos) {
                $gastosAgrupados[$nombreGrupo] = [
                    'concepto' => $nombreGrupo,
                    'meses' => []
                ];

                foreach ($meses as $mes) {
                    $sumaGrupo = 0;

                    foreach ($idsConceptos as $idCa) {
                        if (isset($datosPorConcepto[$idCa][$mes])) {
                            $sumaGrupo += $datosPorConcepto[$idCa][$mes];
                        }
                    }

                    $gastosAgrupados[$nombreGrupo]['meses'][$mes] = $sumaGrupo;
                }
            }

            // Completar meses faltantes con 0
            foreach ($gastosAgrupados as $grupo => &$data) {
                foreach ($meses as $mes) {
                    if (!isset($data['meses'][$mes])) {
                        $data['meses'][$mes] = 0;
                    }
                }
                ksort($data['meses']);
            }

            return array_values($gastosAgrupados);

        } catch (Exception $e) {
            error_log("Error en obtenerGastosPorMes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene venta bruta por mes
     * @param string $idSucursal
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array
     */
    public function obtenerVentaBrutaPorMes($idSucursal, $fechaDesde, $fechaHasta)
    {
        $meses = $this->generarMeses($fechaDesde, $fechaHasta);

        // Construir lista de periodos en ambos formatos
        $periodos = [];
        foreach ($meses as $mes) {
            // Usar explode en lugar de DateTime para evitar problemas con días del mes
            list($anio, $mesNum) = explode('-', $mes);
            $periodos[] = (int) $mesNum . '-' . $anio; // Ej: "2-2025"
            $periodos[] = $mesNum . '-' . $anio;      // Ej: "02-2025"
        }

        // Eliminar duplicados y reindexar
        $periodos = array_values(array_unique($periodos));

        error_log("obtenerVentaBrutaPorMes - Sucursal: {$idSucursal}, Períodos: " . implode(", ", $periodos));

        $periodosStr = "'" . implode("','", $periodos) . "'";

        // Usar LTRIM y RTRIM para eliminar espacios en blanco del campo PERIODO
        $sql = "
            SELECT 
                PERIODO,
                NRO_SUCURSAL,
                CAST(IMPORTE AS FLOAT) as IMPORTE
            FROM RO_V_VENTAS_BRUTAS_IE
            WHERE NRO_SUCURSAL = ?
            AND LTRIM(RTRIM(PERIODO)) IN ({$periodosStr})
        ";

        try {
            $params = [$idSucursal];
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);

            if (!$stmt) {
                throw new Exception("Error al preparar consulta de venta bruta");
            }

            sqlsrv_execute($stmt);

            $ventas = [];
            $registrosEncontrados = 0;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $registrosEncontrados++;
                $periodoNorm = $this->normalizarPeriodo($row['PERIODO']);
                $ventas[$periodoNorm] = $row['IMPORTE'];
            }

            error_log("obtenerVentaBrutaPorMes - Registros encontrados: {$registrosEncontrados}");

            // Completar meses faltantes con 0
            $resultado = [];
            foreach ($meses as $mes) {
                $resultado[$mes] = isset($ventas[$mes]) ? $ventas[$mes] : 0;
            }

            return $resultado;

        } catch (Exception $e) {
            error_log("Error en obtenerVentaBrutaPorMes: " . $e->getMessage());
            return array_fill_keys($meses, 0);
        }
    }

    /**
     * Obtiene venta neta por mes
     * @param string $idSucursal
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array
     */
    public function obtenerVentaNetaPorMes($idSucursal, $fechaDesde, $fechaHasta)
    {
        $meses = $this->generarMeses($fechaDesde, $fechaHasta);

        error_log("obtenerVentaNetaPorMes - Sucursal: {$idSucursal}, Meses: " . implode(", ", $meses));

        $ventas = [];

        foreach ($meses as $mes) {
            $fecha = DateTime::createFromFormat('Y-m', $mes);
            $periodo = $fecha->format('Y-m');

            $sql = "
                SELECT 
                    NRO_SUCURS,
                    SUM(CAST(VENTA AS FLOAT)) as VENTA
                FROM RO_T_RENTABILIDAD_BRUTA
                WHERE NRO_SUCURS = ?
                AND FECHA LIKE ?
                GROUP BY NRO_SUCURS
            ";

            try {
                $params = [$idSucursal, $periodo . '%'];
                $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);

                if (!$stmt) {
                    error_log("obtenerVentaNetaPorMes - Error preparando query para mes {$mes}");
                    $ventas[$mes] = 0;
                    continue;
                }

                sqlsrv_execute($stmt);

                if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $ventas[$mes] = $row['VENTA'] !== null ? floatval($row['VENTA']) : 0;
                    error_log("obtenerVentaNetaPorMes - Mes {$mes}: " . $ventas[$mes]);
                } else {
                    error_log("obtenerVentaNetaPorMes - No hay datos para mes {$mes}");
                    $ventas[$mes] = 0;
                }

            } catch (Exception $e) {
                error_log("obtenerVentaNetaPorMes - Exception para {$mes}: " . $e->getMessage());
                $ventas[$mes] = 0;
            }
        }

        return $ventas;
    }

    /**
     * Construye dataset completo para Costo de Ocupación
     * @param string $idSucursal
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array
     */
    public function construirDataset($idSucursal, $fechaDesde, $fechaHasta)
    {
        // Validar y setear rango por defecto si es necesario
        if (empty($fechaDesde) || empty($fechaHasta)) {
            $rango = $this->calcularRangoDefault();
            $fechaDesde = $rango['desde'];
            $fechaHasta = $rango['hasta'];
        }

        $meses = $this->generarMeses($fechaDesde, $fechaHasta);

        // Obtener datos
        $gastos = $this->obtenerGastosPorMes($idSucursal, $fechaDesde, $fechaHasta);
        $ventaBruta = $this->obtenerVentaBrutaPorMes($idSucursal, $fechaDesde, $fechaHasta);
        $ventaNeta = $this->obtenerVentaNetaPorMes($idSucursal, $fechaDesde, $fechaHasta);

        // Calcular subtotal de gastos por mes
        $subtotalGastos = [];
        foreach ($meses as $mes) {
            $subtotalGastos[$mes] = 0;
            foreach ($gastos as $gasto) {
                $subtotalGastos[$mes] += $gasto['meses'][$mes];
            }
        }

        // Calcular totales
        $totalSubtotal = array_sum($subtotalGastos);
        $totalVentaBruta = array_sum($ventaBruta);
        $totalVentaNeta = array_sum($ventaNeta);

        // Calcular % Costo de Ocupación por mes
        $porcentajeCosto = [];
        foreach ($meses as $mes) {
            if ($ventaNeta[$mes] > 0) {
                $porcentajeCosto[$mes] = ($subtotalGastos[$mes] / $ventaNeta[$mes]) * 100;
            } else {
                $porcentajeCosto[$mes] = null;
            }
        }

        // Calcular % total (acumulado 12 meses)
        $porcentajeCostoTotal = $totalVentaNeta > 0 ? ($totalSubtotal / $totalVentaNeta) * 100 : null;

        // Calcular totales por concepto
        foreach ($gastos as &$gasto) {
            $gasto['total'] = array_sum($gasto['meses']);
        }

        // Construir filas finales
        $filas = $gastos;

        // Agregar fila Subtotal
        $filaSubtotal = [
            'concepto' => 'Subtotal gastos de alquiler',
            'meses' => $subtotalGastos,
            'total' => $totalSubtotal,
            'is_subtotal' => true
        ];
        $filas[] = $filaSubtotal;

        // Venta Bruta comentada - no es necesaria para el cálculo de costo de ocupación
        // $filaVentaBruta = [
        //     'concepto' => 'Venta bruta',
        //     'meses' => $ventaBruta,
        //     'total' => $totalVentaBruta,
        //     'is_metric' => true
        // ];
        // $filas[] = $filaVentaBruta;

        // Agregar fila Venta Neta
        $filaVentaNeta = [
            'concepto' => 'Venta neta',
            'meses' => $ventaNeta,
            'total' => $totalVentaNeta,
            'is_metric' => true
        ];
        $filas[] = $filaVentaNeta;

        // Agregar fila % Costo de Ocupación
        $filaPorcentaje = [
            'concepto' => '% Costo de ocupación',
            'meses' => $porcentajeCosto,
            'total' => $porcentajeCostoTotal,
            'is_percentage' => true
        ];
        $filas[] = $filaPorcentaje;

        // Calcular KPIs (actualizado)
        $kpis = $this->calcularKPIs($porcentajeCosto, $porcentajeCostoTotal, $meses, $idSucursal, $fechaDesde, $fechaHasta);

        return [
            'meses' => $meses,
            'filas' => $filas,
            'kpis' => $kpis,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ];
    }

    /**
     * Calcula KPIs del dashboard (actualizado según requerimientos)
     * @param array $porcentajeCosto
     * @param float $porcentajeCostoTotal
     * @param array $meses
     * @param string $idSucursal
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array
     */
    private function calcularKPIs($porcentajeCosto, $porcentajeCostoTotal, $meses, $idSucursal, $fechaDesde, $fechaHasta)
    {
        // KPI 1: Acumulado últimos 12 meses con datos
        $acumulado12m = $porcentajeCostoTotal;

        // KPI 2: % Variación contra mismo período año anterior
        $variacionAnual = $this->calcularVariacionAnual($idSucursal, $fechaDesde, $fechaHasta);

        // Datos para gráfico (últimos 6 meses vs año anterior)
        $datosGrafico = $this->obtenerDatosGraficoEvolucion($idSucursal, $meses);

        return [
            'acumulado_12m' => $acumulado12m,
            'variacion_anual' => $variacionAnual['porcentaje'],
            'variacion_anual_estado' => $variacionAnual['estado'],
            'periodo_actual' => $variacionAnual['periodo_actual'],
            'periodo_anterior' => $variacionAnual['periodo_anterior'],
            'grafico_datos' => $datosGrafico
        ];
    }

    /**
     * Calcula variación anual comparando período actual vs año anterior
     * @param string $idSucursal
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array
     */
    private function calcularVariacionAnual($idSucursal, $fechaDesde, $fechaHasta)
    {
        // Calcular período anterior (mismo rango, un año atrás)
        $desde = new DateTime($fechaDesde);
        $hasta = new DateTime($fechaHasta);

        $desdeAnterior = clone $desde;
        $desdeAnterior->modify('-1 year');

        $hastaAnterior = clone $hasta;
        $hastaAnterior->modify('-1 year');

        // Obtener % costo de ocupación período actual
        $datasetActual = $this->construirDatasetSimplificado($idSucursal, $fechaDesde, $fechaHasta);
        $costoActual = $datasetActual['costo_ocupacion_total'];

        // Obtener % costo de ocupación período anterior
        $datasetAnterior = $this->construirDatasetSimplificado(
            $idSucursal,
            $desdeAnterior->format('Y-m-d'),
            $hastaAnterior->format('Y-m-d')
        );
        $costoAnterior = $datasetAnterior['costo_ocupacion_total'];

        // Calcular variación porcentual
        $variacionPorcentaje = null;
        $estado = 'info';

        if ($costoAnterior !== null && $costoAnterior > 0 && $costoActual !== null) {
            $variacionPorcentaje = (($costoActual - $costoAnterior) / $costoAnterior) * 100;

            // Determinar estado
            if ($variacionPorcentaje < 0) {
                $estado = 'success'; // Mejoró (bajó el costo)
            } elseif ($variacionPorcentaje > 0) {
                $estado = 'danger'; // Empeoró (subió el costo)
            } else {
                $estado = 'info'; // Sin cambio
            }
        }

        return [
            'porcentaje' => $variacionPorcentaje,
            'estado' => $estado,
            'costo_actual' => $costoActual,
            'costo_anterior' => $costoAnterior,
            'periodo_actual' => $fechaDesde . ' a ' . $fechaHasta,
            'periodo_anterior' => $desdeAnterior->format('Y-m-d') . ' a ' . $hastaAnterior->format('Y-m-d')
        ];
    }

    /**
     * Construye dataset simplificado solo para cálculo de KPIs
     * @param string $idSucursal
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array
     */
    /**
     * Construir dataset simplificado con % costo de ocupación
     * Método público para poder usarse desde controladores externos
     */
    public function construirDatasetSimplificado($idSucursal, $fechaDesde, $fechaHasta)
    {
        $meses = $this->generarMeses($fechaDesde, $fechaHasta);

        $gastos = $this->obtenerGastosPorMes($idSucursal, $fechaDesde, $fechaHasta);
        $ventaNeta = $this->obtenerVentaNetaPorMes($idSucursal, $fechaDesde, $fechaHasta);

        $totalGastos = 0;
        foreach ($gastos as $gasto) {
            $totalGastos += array_sum($gasto['meses']);
        }

        $totalVentaNeta = array_sum($ventaNeta);

        $costoOcupacion = null;
        if ($totalVentaNeta > 0) {
            $costoOcupacion = ($totalGastos / $totalVentaNeta) * 100;
        }

        return [
            'costo_ocupacion_total' => $costoOcupacion,
            'porcentaje_costo_ocupacion' => $costoOcupacion, // Alias para compatibilidad
            'total_gastos' => $totalGastos,
            'total_venta_neta' => $totalVentaNeta
        ];
    }

    /**
     * Obtiene datos para gráfico de evolución (últimos 6 meses vs año anterior)
     * @param string $idSucursal
     * @param array $meses Meses del período actual
     * @return array
     */
    private function obtenerDatosGraficoEvolucion($idSucursal, $meses)
    {
        // Tomar últimos 6 meses del período actual
        $ultimos6Meses = array_slice($meses, -6);

        $datosActuales = [];
        $datosAnteriores = [];

        foreach ($ultimos6Meses as $mes) {
            $fecha = DateTime::createFromFormat('Y-m', $mes);

            // Mes actual
            $desde = $fecha->format('Y-m-01');
            $hasta = $fecha->format('Y-m-t');

            $dataActual = $this->construirDatasetSimplificado($idSucursal, $desde, $hasta);
            $datosActuales[] = [
                'mes' => $mes,
                'costo' => $dataActual['costo_ocupacion_total']
            ];

            // Mes año anterior
            $fechaAnterior = clone $fecha;
            $fechaAnterior->modify('-1 year');

            $desdeAnterior = $fechaAnterior->format('Y-m-01');
            $hastaAnterior = $fechaAnterior->format('Y-m-t');

            $dataAnterior = $this->construirDatasetSimplificado($idSucursal, $desdeAnterior, $hastaAnterior);
            $datosAnteriores[] = [
                'mes' => $fechaAnterior->format('Y-m'),
                'costo' => $dataAnterior['costo_ocupacion_total']
            ];
        }

        return [
            'actuales' => $datosActuales,
            'anteriores' => $datosAnteriores
        ];
    }

    /**
     * Obtiene el valor agregado para un concepto, sucursal y rango de fechas
     * Si el rango abarca múltiples meses, suma los valores
     * 
     * @param int $conceptoId ID del concepto
     * @param int $sucursalId ID de la sucursal
     * @param string $fechaDesde Fecha desde (Y-m-d)
     * @param string $fechaHasta Fecha hasta (Y-m-d)
     * @return float Valor agregado
     */
    public function obtenerValorAgregado($conceptoId, $sucursalId, $fechaDesde, $fechaHasta)
    {
        try {
            $desde = new DateTime($fechaDesde);
            $hasta = new DateTime($fechaHasta);

            $totalAgregado = 0;

            // Iterar por cada mes en el rango
            $periodo = new DatePeriod(
                $desde,
                new DateInterval('P1M'),
                $hasta->modify('+1 day')
            );

            foreach ($periodo as $fecha) {
                $mesDesde = $fecha->format('Y-m-01');
                $mesHasta = $fecha->format('Y-m-t');

                // Obtener datos del mes
                $dataset = $this->construirDatasetSimplificado($sucursalId, $mesDesde, $mesHasta);

                // Obtener valor del concepto específico
                $valor = $this->obtenerValorConcepto($conceptoId, $dataset, $sucursalId, $mesDesde, $mesHasta);
                $totalAgregado += $valor;
            }

            return $totalAgregado;

        } catch (Exception $e) {
            error_log("Error en obtenerValorAgregado: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene el valor de un concepto específico del dataset
     * 
     * @param int $conceptoId ID del concepto
     * @param array $dataset Dataset con los datos del mes
     * @param int $sucursalId ID de la sucursal
     * @param string $desde Fecha desde
     * @param string $hasta Fecha hasta
     * @return float Valor del concepto
     */
    private function obtenerValorConcepto($conceptoId, $dataset, $sucursalId, $desde, $hasta)
    {
        // Mapeo de conceptos según IDs
        $mapeoConceptos = [
            1 => 'alquiler_total',
            2 => 'expensas_total',
            3 => 'impuestos_municipales',
            4 => 'luz',
            5 => 'gas',
            6 => 'agua',
            7 => 'abl_arba',
            8 => 'internet',
            9 => 'telefono',
            10 => 'seguridad',
            11 => 'limpieza',
            12 => 'mantenimiento',
            13 => 'otros_gastos',
            14 => 'amortizacion',
            15 => 'ventas',
            16 => 'rentabilidad_bruta',
            17 => 'porcentaje_costo_ventas',
            18 => 'porcentaje_rentabilidad_ventas'
        ];

        // Si el concepto está en el mapeo directo
        if (isset($mapeoConceptos[$conceptoId]) && isset($dataset[$mapeoConceptos[$conceptoId]])) {
            return $dataset[$mapeoConceptos[$conceptoId]];
        }

        // Casos especiales
        switch ($conceptoId) {
            case 15: // Ventas - obtener de otra fuente
                return $this->obtenerVentasSucursal($sucursalId, $desde, $hasta);

            case 16: // Rentabilidad bruta
                $ventas = $this->obtenerVentasSucursal($sucursalId, $desde, $hasta);
                $costoOcupacion = $dataset['costo_ocupacion_total'] ?? 0;
                return $ventas - $costoOcupacion;

            case 17: // % Costo ocupación s/ventas
                $ventas = $this->obtenerVentasSucursal($sucursalId, $desde, $hasta);
                $costoOcupacion = $dataset['costo_ocupacion_total'] ?? 0;
                return $ventas > 0 ? ($costoOcupacion / $ventas) * 100 : 0;

            case 18: // % Rentabilidad s/ventas
                $ventas = $this->obtenerVentasSucursal($sucursalId, $desde, $hasta);
                $costoOcupacion = $dataset['costo_ocupacion_total'] ?? 0;
                $rentabilidad = $ventas - $costoOcupacion;
                return $ventas > 0 ? ($rentabilidad / $ventas) * 100 : 0;

            default:
                return 0;
        }
    }

    /**
     * Obtiene las ventas de una sucursal en un período
     * 
     * @param int $sucursalId ID de la sucursal
     * @param string $desde Fecha desde
     * @param string $hasta Fecha hasta
     * @return float Total de ventas
     */
    private function obtenerVentasSucursal($sucursalId, $desde, $hasta)
    {
        try {
            // Aquí debes implementar la lógica para obtener las ventas
            // Esto es un ejemplo, ajusta según tu estructura de base de datos

            $query = "SELECT ISNULL(SUM(IMPORTE), 0) as total_ventas 
                     FROM VENTAS 
                     WHERE NRO_SUCURSAL = ? 
                     AND FECHA BETWEEN ? AND ?";

            $stmt = sqlsrv_prepare($this->cid_central, $query, [$sucursalId, $desde, $hasta]);

            if ($stmt && sqlsrv_execute($stmt)) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                return floatval($row['total_ventas'] ?? 0);
            }

            return 0;

        } catch (Exception $e) {
            error_log("Error en obtenerVentasSucursal: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verifica si existe un valor mayor a 0 en la vista RO_V_CONTRATOS_VALOR_LLAVE
     * para la sucursal y período especificados
     * 
     * @param int $sucursalId ID de la sucursal
     * @param string $periodo Período en formato Y-m (ej: "2025-02")
     * @return bool True si hay un valor mayor a 0, False en caso contrario
     */
    private function verificarValorLlaveNegocio($sucursalId, $periodo)
    {
        try {
            // Convertir período Y-m a formato que use la vista
            list($anio, $mes) = explode('-', $periodo);
            $periodoFormateado = (int) $mes . '-' . $anio; // Ej: "2-2025"

            $sql = "
                SELECT TOP 1 CAST(IMPORTE AS FLOAT) as IMPORTE
                FROM RO_V_CONTRATOS_VALOR_LLAVE
                WHERE NRO_SUCURS = ?
                AND LTRIM(RTRIM(PERIODO)) = ?
                AND CAST(IMPORTE AS FLOAT) > 0
            ";

            $params = [$sucursalId, $periodoFormateado];
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);

            if (!$stmt) {
                error_log("Error al preparar consulta de verificación de llave de negocio");
                return false;
            }

            sqlsrv_execute($stmt);

            // Si encuentra al menos un registro con importe > 0, devuelve true
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $tieneValor = ($row && isset($row['IMPORTE']) && $row['IMPORTE'] > 0);

            // Log para debug
            error_log("verificarValorLlaveNegocio - Sucursal: {$sucursalId}, Período: {$periodoFormateado}, Tiene valor: " . ($tieneValor ? 'SI' : 'NO'));

            return $tieneValor;

        } catch (Exception $e) {
            error_log("Error en verificarValorLlaveNegocio: " . $e->getMessage());
            return false; // En caso de error, asumir que no hay valor
        }
    }
}
