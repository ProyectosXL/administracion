
<?php

class DashboardController {
    
    private $conexion;
    
    function __construct() {
        try {
            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
            $this->conexion = $cid->conectar($db);
            
            if (!$this->conexion) {
                throw new Exception('No se pudo establecer conexión a la base de datos');
            }
        } catch (Exception $e) {
            throw new Exception('Error inicializando DashboardController: ' . $e->getMessage());
        }
    }

    /**
     * Ejecuta una consulta SQL y retorna los resultados
     */
    private function executeQuery($sql, $returnType = 'array') {
        try {
            $stmt = sqlsrv_query($this->conexion, $sql);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                $errorMessage = 'Error en consulta SQL: ';
                if ($errors) {
                    foreach ($errors as $error) {
                        $errorMessage .= $error['message'] . ' ';
                    }
                } else {
                    $errorMessage .= 'Error desconocido';
                }
                throw new Exception($errorMessage);
            }
            
            if ($returnType === 'single') {
                $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                return $result ?: [];
            } else {
                $rows = array();
                while($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $rows[] = $v;
                }
                return $rows;
            }
            
        } catch (Exception $e) {
            throw new Exception('Error ejecutando consulta: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene la evolución mensual del % de costo de nacionalización de los últimos 3 años calendario
     * Este método NO usa filtros (siempre últimos 3 años calendario)
     */
    public function getEvolucionMensualCostos() {
        try {
            $anioActual = date('Y');
            $anioInicio = $anioActual - 2; // Últimos 3 años calendario
            
            $sql = "SELECT 
                        YEAR(A.FECHA_DESP_ADU) as anio,
                        MONTH(A.FECHA_DESP_ADU) as mes,
                        AVG(CAST(B.COSTO_NAC AS FLOAT) * 100) as promedio_costo_nac,
                        COUNT(*) as total_despachos
                    FROM RO_T_IMPORTACIONES_ENCABEZADO A
                    LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                    WHERE YEAR(A.FECHA_DESP_ADU) >= $anioInicio
                        AND YEAR(A.FECHA_DESP_ADU) <= $anioActual
                        AND B.COSTO_NAC IS NOT NULL
                        AND A.FECHA_DESP_ADU IS NOT NULL
                    GROUP BY YEAR(A.FECHA_DESP_ADU), MONTH(A.FECHA_DESP_ADU)
                    ORDER BY anio DESC, mes DESC";
            
            $rows = $this->executeQuery($sql);
            
            $result = array();
            foreach ($rows as $v) {
                $result[] = array(
                    'anio' => intval($v['anio'] ?? 0),
                    'mes' => intval($v['mes'] ?? 0),
                    'promedio_costo_nac' => round(floatval($v['promedio_costo_nac'] ?? 0), 2),
                    'total_despachos' => intval($v['total_despachos'] ?? 0),
                    'fecha_texto' => $this->obtenerNombreMes(intval($v['mes'] ?? 0)) . ' ' . intval($v['anio'] ?? 0)
                );
            }
            
            return $result;
            
        } catch (Exception $e) {
            return ['error' => 'Error obteniendo evolución mensual: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene promedio por proveedor para gráfico de barras (con filtros de fecha)
     */
    public function getPromedioBarrasPorProveedor($fechaDesde = null, $fechaHasta = null, $proveedor = null) {
        try {
            $whereClause = "WHERE B.COSTO_NAC IS NOT NULL AND A.FECHA_DESP_ADU IS NOT NULL";
            
            if ($fechaDesde && $fechaHasta) {
                $whereClause .= " AND A.FECHA_DESP_ADU BETWEEN '$fechaDesde' AND '$fechaHasta'";
            }
            
            if ($proveedor && $proveedor !== '') {
                $whereClause .= " AND A.COD_PROVEE = '$proveedor'";
            }
            
            $sql = "SELECT 
                        A.COD_PROVEE,
                        A.PROVEEDOR,
                        AVG(CAST(B.COSTO_NAC AS FLOAT) * 100) as promedio_costo_nac,
                        COUNT(*) as total_despachos,
                        SUM(CAST(A.VALOR_FOB_PESO AS FLOAT)) as valor_total_fob
                    FROM RO_T_IMPORTACIONES_ENCABEZADO A
                    LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                    $whereClause
                    GROUP BY A.COD_PROVEE, A.PROVEEDOR
                    HAVING COUNT(*) >= 1
                    ORDER BY promedio_costo_nac DESC";
            
            $rows = $this->executeQuery($sql);
            
            $result = array();
            foreach ($rows as $v) {
                $result[] = array(
                    'cod_proveedor' => $v['COD_PROVEE'] ?? '',
                    'proveedor' => $v['PROVEEDOR'] ?? '',
                    'promedio_costo_nac' => round(floatval($v['promedio_costo_nac'] ?? 0), 2),
                    'total_despachos' => intval($v['total_despachos'] ?? 0),
                    'valor_total_fob' => round(floatval($v['valor_total_fob'] ?? 0), 2)
                );
            }
            
            return $result;
            
        } catch (Exception $e) {
            return ['error' => 'Error obteniendo datos por proveedor: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene KPIs generales
     */
    public function getKPIsGenerales($fechaDesde = null, $fechaHasta = null) {
        try {
            $whereClause = "WHERE B.COSTO_NAC IS NOT NULL AND A.FECHA_DESP_ADU IS NOT NULL";
            
            if ($fechaDesde && $fechaHasta) {
                $whereClause .= " AND A.FECHA_DESP_ADU BETWEEN '$fechaDesde' AND '$fechaHasta'";
            }
            
            $sql = "SELECT 
                        COUNT(*) as total_despachos,
                        AVG(CAST(B.COSTO_NAC AS FLOAT) * 100) as promedio_costo_general,
                        MIN(CAST(B.COSTO_NAC AS FLOAT) * 100) as minimo_costo,
                        MAX(CAST(B.COSTO_NAC AS FLOAT) * 100) as maximo_costo,
                        SUM(CAST(A.VALOR_FOB_PESO AS FLOAT)) as valor_total_importado,
                        COUNT(DISTINCT A.COD_PROVEE) as total_proveedores,
                        AVG(CAST(A.VALOR_FOB_PESO AS FLOAT)) as promedio_valor_fob
                    FROM RO_T_IMPORTACIONES_ENCABEZADO A
                    LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                    $whereClause";
            
            $result = $this->executeQuery($sql, 'single');
            
            return array(
                'total_despachos' => intval($result['total_despachos'] ?? 0),
                'promedio_costo_general' => round(floatval($result['promedio_costo_general'] ?? 0), 2),
                'minimo_costo' => round(floatval($result['minimo_costo'] ?? 0), 2),
                'maximo_costo' => round(floatval($result['maximo_costo'] ?? 0), 2),
                'valor_total_importado' => round(floatval($result['valor_total_importado'] ?? 0), 2),
                'total_proveedores' => intval($result['total_proveedores'] ?? 0),
                'promedio_valor_fob' => round(floatval($result['promedio_valor_fob'] ?? 0), 2)
            );
            
        } catch (Exception $e) {
            return ['error' => 'Error obteniendo KPIs generales: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene top 10 proveedores por costo de nacionalización
     */
    public function getTopProveedoresPorCosto($fechaDesde = null, $fechaHasta = null) {
        try {
            $whereClause = "WHERE B.COSTO_NAC IS NOT NULL AND A.FECHA_DESP_ADU IS NOT NULL";
            
            if ($fechaDesde && $fechaHasta) {
                $whereClause .= " AND A.FECHA_DESP_ADU BETWEEN '$fechaDesde' AND '$fechaHasta'";
            }
            
            $sql = "SELECT TOP 10
                        A.COD_PROVEE,
                        A.PROVEEDOR,
                        AVG(CAST(B.COSTO_NAC AS FLOAT) * 100) as promedio_costo_nac,
                        COUNT(*) as total_despachos,
                        SUM(CAST(A.VALOR_FOB_PESO AS FLOAT)) as valor_total_fob
                    FROM RO_T_IMPORTACIONES_ENCABEZADO A
                    LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                    $whereClause
                    GROUP BY A.COD_PROVEE, A.PROVEEDOR
                    ORDER BY promedio_costo_nac DESC";
            
            $rows = $this->executeQuery($sql);
            
            $result = array();
            foreach ($rows as $v) {
                $result[] = array(
                    'cod_proveedor' => $v['COD_PROVEE'] ?? '',
                    'proveedor' => $v['PROVEEDOR'] ?? '',
                    'promedio_costo_nac' => round(floatval($v['promedio_costo_nac'] ?? 0), 2),
                    'total_despachos' => intval($v['total_despachos'] ?? 0),
                    'valor_total_fob' => round(floatval($v['valor_total_fob'] ?? 0), 2)
                );
            }
            
            return $result;
            
        } catch (Exception $e) {
            return ['error' => 'Error obteniendo top proveedores: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene lista de proveedores para filtros
     */
    public function getProveedores() {
        try {
            $sql = "SELECT DISTINCT A.COD_PROVEE, A.PROVEEDOR 
                    FROM RO_T_IMPORTACIONES_ENCABEZADO A
                    LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                    WHERE B.COSTO_NAC IS NOT NULL
                        AND A.PROVEEDOR IS NOT NULL
                        AND A.PROVEEDOR != ''
                    ORDER BY A.PROVEEDOR";
            
            $rows = $this->executeQuery($sql);
            
            $result = array();
            foreach ($rows as $v) {
                $result[] = array(
                    'cod_proveedor' => $v['COD_PROVEE'] ?? '',
                    'proveedor' => $v['PROVEEDOR'] ?? ''
                );
            }
            
            return $result;
            
        } catch (Exception $e) {
            return ['error' => 'Error obteniendo lista de proveedores: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene distribución de costos por rangos nuevos: <40%, 40%-80%, >80%
     * Versión alternativa sin CTE
     */
    public function getDistribucionCostosPorRangos($fechaDesde = null, $fechaHasta = null) {
        try {
            $whereClause = "WHERE B.COSTO_NAC IS NOT NULL AND A.FECHA_DESP_ADU IS NOT NULL";
            
            if ($fechaDesde && $fechaHasta) {
                $whereClause .= " AND A.FECHA_DESP_ADU BETWEEN '$fechaDesde' AND '$fechaHasta'";
            }
            
            // Obtener todos los datos primero
            $sql = "SELECT 
                        CAST(B.COSTO_NAC AS FLOAT) * 100 as costo_porcentaje
                    FROM RO_T_IMPORTACIONES_ENCABEZADO A
                    LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                    $whereClause";
            
            $rows = $this->executeQuery($sql);
            
            // Procesar los datos en PHP
            $rangos = [
                '< 40%' => ['count' => 0, 'sum' => 0],
                'Entre 40% y 80%' => ['count' => 0, 'sum' => 0],
                '> 80%' => ['count' => 0, 'sum' => 0]
            ];
            
            foreach ($rows as $row) {
                $costo = floatval($row['costo_porcentaje'] ?? 0);
                
                if ($costo < 40) {
                    $rangos['< 40%']['count']++;
                    $rangos['< 40%']['sum'] += $costo;
                } elseif ($costo >= 40 && $costo <= 80) {
                    $rangos['Entre 40% y 80%']['count']++;
                    $rangos['Entre 40% y 80%']['sum'] += $costo;
                } else {
                    $rangos['> 80%']['count']++;
                    $rangos['> 80%']['sum'] += $costo;
                }
            }
            
            $result = array();
            foreach ($rangos as $rango => $data) {
                if ($data['count'] > 0) {
                    $result[] = array(
                        'rango_costo' => $rango,
                        'cantidad_despachos' => $data['count'],
                        'promedio_rango' => round($data['sum'] / $data['count'], 2)
                    );
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            return ['error' => 'Error obteniendo distribución de costos: ' . $e->getMessage()];
        }
    }

    /**
     * Convierte número de mes a nombre
     */
    private function obtenerNombreMes($numeroMes) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $meses[$numeroMes] ?? 'Mes ' . $numeroMes;
}

}

?>