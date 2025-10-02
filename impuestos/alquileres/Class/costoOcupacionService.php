
<?php

/**
 * Servicio para Costo de Ocupación
 * Maneja la lógica de negocio y acceso a datos
 */
class CostoOcupacionService
{
    private $cid_central;
    private $alquiler;

    public function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/class/conexion.php';
        require_once __DIR__ . '/Alquiler.php';
        
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');
        $this->alquiler = new Alquiler();

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') {
            $this->cid_central = $cid->conectar('uy');
        }
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
     * Convierte periodo M-YYYY a YYYY-MM
     * @param string $periodo Formato "10-2024"
     * @return string Formato "2024-10"
     */
    private function normalizarPeriodo($periodo)
    {
        $partes = explode('-', $periodo);
        if (count($partes) != 2) return $periodo;
        
        $mes = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
        $anio = $partes[1];
        
        return "{$anio}-{$mes}";
    }

    /**
     * Obtiene gastos por concepto y mes para una sucursal
     * @param string $idSucursal
     * @param string $fechaDesde
     * @param string $fechaHasta
     * @return array
     */
    public function obtenerGastosPorMes($idSucursal, $fechaDesde, $fechaHasta)
    {
        $meses = $this->generarMeses($fechaDesde, $fechaHasta);
        
        // Construir lista de periodos en formato M-YYYY
        $periodos = [];
        foreach ($meses as $mes) {
            $fecha = DateTime::createFromFormat('Y-m', $mes);
            $periodos[] = (int)$fecha->format('n') . '-' . $fecha->format('Y');
        }
        
        $periodosStr = "'" . implode("','", $periodos) . "'";
        
        $sql = "
            SELECT 
                d.PERIODO,
                d.ID_CA,
                c.CONCEPTO,
                CAST(d.IMPORTE AS FLOAT) as IMPORTE
            FROM RO_T_DETALLE_ALQUILERES d
            INNER JOIN RO_T_CONCEPTOS_ALQUILERES c ON d.ID_CA = c.ID_CA
            WHERE d.NRO_SUCURS = ?
            AND d.PERIODO IN ({$periodosStr})
            ORDER BY c.CONCEPTO, d.PERIODO
        ";
        
        try {
            $params = [$idSucursal];
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                throw new Exception("Error al preparar consulta de gastos");
            }
            
            sqlsrv_execute($stmt);
            
            $gastos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $periodoNorm = $this->normalizarPeriodo($row['PERIODO']);
                
                if (!isset($gastos[$row['CONCEPTO']])) {
                    $gastos[$row['CONCEPTO']] = [
                        'id_ca' => $row['ID_CA'],
                        'concepto' => $row['CONCEPTO'],
                        'meses' => []
                    ];
                }
                
                $gastos[$row['CONCEPTO']]['meses'][$periodoNorm] = $row['IMPORTE'];
            }
            
            // Completar meses faltantes con 0
            foreach ($gastos as $concepto => &$data) {
                foreach ($meses as $mes) {
                    if (!isset($data['meses'][$mes])) {
                        $data['meses'][$mes] = 0;
                    }
                }
                ksort($data['meses']);
            }
            
            return array_values($gastos);
            
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
        
        // Construir lista de periodos
        $periodos = [];
        foreach ($meses as $mes) {
            $fecha = DateTime::createFromFormat('Y-m', $mes);
            $periodos[] = (int)$fecha->format('n') . '-' . $fecha->format('Y');
        }
        
        $periodosStr = "'" . implode("','", $periodos) . "'";
        
        $sql = "
            SELECT 
                PERIODO,
                NRO_SUCURSAL,
                CAST(IMPORTE AS FLOAT) as IMPORTE
            FROM RO_V_VENTAS_BRUTAS_IE
            WHERE NRO_SUCURSAL = ?
            AND PERIODO IN ({$periodosStr})
        ";
        
        try {
            $params = [$idSucursal];
            $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
            
            if (!$stmt) {
                throw new Exception("Error al preparar consulta de venta bruta");
            }
            
            sqlsrv_execute($stmt);
            
            $ventas = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $periodoNorm = $this->normalizarPeriodo($row['PERIODO']);
                $ventas[$periodoNorm] = $row['IMPORTE'];
            }
            
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
        
        $ventas = [];
        
        foreach ($meses as $mes) {
            $fecha = DateTime::createFromFormat('Y-m', $mes);
            $periodo = $fecha->format('Y-m');
            
            $sql = "
                SELECT 
                    NRO_SUCURS,
                    CAST(VENTA AS FLOAT) as VENTA
                FROM RO_T_RENTABILIDAD_BRUTA
                WHERE NRO_SUCURS = ?
                AND FECHA LIKE ?
            ";
            
            try {
                $params = [$idSucursal, $periodo . '%'];
                $stmt = sqlsrv_prepare($this->cid_central, $sql, $params);
                
                if (!$stmt) {
                    $ventas[$mes] = 0;
                    continue;
                }
                
                sqlsrv_execute($stmt);
                
                if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $ventas[$mes] = $row['VENTA'];
                } else {
                    $ventas[$mes] = 0;
                }
                
            } catch (Exception $e) {
                error_log("Error en obtenerVentaNetaPorMes para {$mes}: " . $e->getMessage());
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
        
        // Calcular % total
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
        
        // Agregar fila Venta Bruta
        $filaVentaBruta = [
            'concepto' => 'Venta bruta',
            'meses' => $ventaBruta,
            'total' => $totalVentaBruta,
            'is_metric' => true
        ];
        $filas[] = $filaVentaBruta;
        
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
        
        // Calcular KPIs
        $kpis = $this->calcularKPIs($porcentajeCosto, $meses);
        
        return [
            'meses' => $meses,
            'filas' => $filas,
            'kpis' => $kpis,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ];
    }

    /**
     * Calcula KPIs del dashboard
     * @param array $porcentajeCosto
     * @param array $meses
     * @return array
     */
    private function calcularKPIs($porcentajeCosto, $meses)
    {
        // Promedio solo de meses con venta neta (excluyendo null)
        $valoresValidos = array_filter($porcentajeCosto, function($v) {
            return $v !== null;
        });
        
        $promedio12m = count($valoresValidos) > 0 ? 
            array_sum($valoresValidos) / count($valoresValidos) : null;
        
        // Último mes con datos disponibles (no necesariamente el último del rango)
        $costoUltimoMes = null;
        $ultimoMesConDatos = null;
        $costoPenultimoMes = null;
        $penultimoMesConDatos = null;
        
        // Recorrer desde el final hacia atrás para encontrar los últimos dos meses con datos
        $mesesInvertidos = array_reverse($meses, true);
        $contadorMesesConDatos = 0;
        
        foreach ($mesesInvertidos as $mes) {
            if ($porcentajeCosto[$mes] !== null) {
                $contadorMesesConDatos++;
                
                if ($contadorMesesConDatos === 1) {
                    // Primer mes con datos (más reciente)
                    $costoUltimoMes = $porcentajeCosto[$mes];
                    $ultimoMesConDatos = $mes;
                } elseif ($contadorMesesConDatos === 2) {
                    // Segundo mes con datos (penúltimo)
                    $costoPenultimoMes = $porcentajeCosto[$mes];
                    $penultimoMesConDatos = $mes;
                    break; // Ya tenemos los dos meses que necesitamos
                }
            }
        }
        
        // Estado del último mes (para badge)
        $estadoUltimoMes = 'success';
        if ($costoUltimoMes !== null) {
            if ($costoUltimoMes > 20) {
                $estadoUltimoMes = 'danger';
            } elseif ($costoUltimoMes >= 15) {
                $estadoUltimoMes = 'warning';
            }
        }
        
        // Calcular variación mensual (fórmula: ((actual - anterior) / anterior) * 100)
        $variacionMensual = null;
        $estadoVariacion = 'info';
        
        if ($costoUltimoMes !== null && $costoPenultimoMes !== null && $costoPenultimoMes > 0) {
            $variacionMensual = (($costoUltimoMes - $costoPenultimoMes) / $costoPenultimoMes) * 100;
            
            // Estado de la variación (invertido: mejora = negativo, empeora = positivo)
            if ($variacionMensual > 0) {
                $estadoVariacion = 'danger'; // Empeoró
            } elseif ($variacionMensual < 0) {
                $estadoVariacion = 'success'; // Mejoró
            } else {
                $estadoVariacion = 'info'; // Sin cambio
            }
        }
        
        // Calcular variación vs promedio (fórmula: ((actual - promedio) / promedio) * 100)
        $variacionVsPromedio = null;
        $estadoVsPromedio = 'info';
        
        if ($costoUltimoMes !== null && $promedio12m !== null && $promedio12m > 0) {
            $variacionVsPromedio = (($costoUltimoMes - $promedio12m) / $promedio12m) * 100;
            
            // Estado de la variación vs promedio (usando umbrales relativos)
            if ($variacionVsPromedio > 5) {
                $estadoVsPromedio = 'danger'; // Más de 5% por encima del promedio
            } elseif ($variacionVsPromedio < -5) {
                $estadoVsPromedio = 'success'; // Más de 5% por debajo del promedio
            } else {
                $estadoVsPromedio = 'warning'; // Dentro del rango ±5% del promedio
            }
        }
        
        // Últimos 6 meses con datos válidos para tendencia (excluyendo null)
        $tendencia6m = [];
        $contadorValidos = 0;
        
        // Recorrer desde el final hacia atrás para obtener los últimos 6 valores válidos
        $mesesInvertidos = array_reverse($meses, true);
        foreach ($mesesInvertidos as $mes) {
            if ($porcentajeCosto[$mes] !== null) {
                $tendencia6m[] = $porcentajeCosto[$mes];
                $contadorValidos++;
                
                // Detener cuando tengamos 6 valores válidos
                if ($contadorValidos >= 6) {
                    break;
                }
            }
        }
        
        // Revertir el array para que esté en orden cronológico
        $tendencia6m = array_reverse($tendencia6m);
        
        return [
            'promedio_12m' => $promedio12m,
            'costo_ultimo_mes' => $costoUltimoMes,
            'ultimo_mes_con_datos' => $ultimoMesConDatos,
            'estado_ultimo_mes' => $estadoUltimoMes,
            'variacion_mensual' => $variacionMensual,
            'estado_variacion' => $estadoVariacion,
            'penultimo_mes_con_datos' => $penultimoMesConDatos,
            'variacion_vs_promedio' => $variacionVsPromedio,
            'estado_vs_promedio' => $estadoVsPromedio,
            'tendencia_6m' => $tendencia6m
        ];
    }
}