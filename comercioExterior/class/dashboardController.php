
<?php

class DashboardController {
    
    private $conexion;
    
    function __construct() {
        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->conexion = $cid->conectar($db);
    }

    /**
     * Obtiene la evolución mensual del % de costo de nacionalización de los últimos 3 años calendario
     * Este método NO usa filtros de fecha (siempre últimos 3 años calendario)
     */
    public function getEvolucionMensualCostos() {
        $anioActual = date('Y');
        $anioInicio = $anioActual - 2; // Últimos 3 años calendario
        
        $sql = "SELECT 
                    YEAR(A.FECHA_DESP_ADU) as anio,
                    MONTH(A.FECHA_DESP_ADU) as mes,
                    AVG(B.COSTO_NAC * 100) as promedio_costo_nac,
                    COUNT(*) as total_despachos
                FROM RO_T_IMPORTACIONES_ENCABEZADO A
                LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                WHERE YEAR(A.FECHA_DESP_ADU) >= $anioInicio
                    AND YEAR(A.FECHA_DESP_ADU) <= $anioActual
                    AND B.COSTO_NAC IS NOT NULL
                GROUP BY YEAR(A.FECHA_DESP_ADU), MONTH(A.FECHA_DESP_ADU)
                ORDER BY anio DESC, mes DESC";
        
        try {
            $stmt = sqlsrv_query($this->conexion, $sql);
            $rows = array();
            
            while($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = array(
                    'anio' => $v['anio'],
                    'mes' => $v['mes'],
                    'promedio_costo_nac' => round($v['promedio_costo_nac'], 2),
                    'total_despachos' => $v['total_despachos'],
                    'fecha_texto' => $this->obtenerNombreMes($v['mes']) . ' ' . $v['anio']
                );
            }
            
            return $rows;
            
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    /**
     * Obtiene promedio por proveedor para gráfico de barras (con filtros de fecha)
     */
    public function getPromedioBarrasPorProveedor($fechaDesde = null, $fechaHasta = null, $proveedor = null) {
        $whereClause = "WHERE B.COSTO_NAC IS NOT NULL";
        
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
        
        try {
            $stmt = sqlsrv_query($this->conexion, $sql);
            $rows = array();
            
            while($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = array(
                    'cod_proveedor' => $v['COD_PROVEE'],
                    'proveedor' => $v['PROVEEDOR'],
                    'promedio_costo_nac' => round(floatval($v['promedio_costo_nac'] ?? 0), 2),
                    'total_despachos' => intval($v['total_despachos'] ?? 0),
                    'valor_total_fob' => round(floatval($v['valor_total_fob'] ?? 0), 2)
                );
            }
            
            return $rows;
            
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    /**
     * Obtiene KPIs generales
     */
    public function getKPIsGenerales($fechaDesde = null, $fechaHasta = null) {
        $whereClause = "WHERE B.COSTO_NAC IS NOT NULL";
        
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
        
        try {
            $stmt = sqlsrv_query($this->conexion, $sql);
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            return array(
                'total_despachos' => intval($result['total_despachos'] ?? 0),
                'promedio_costo_general' => round(floatval($result['promedio_costo_general'] ?? 0), 2),
                'minimo_costo' => round(floatval($result['minimo_costo'] ?? 0), 2),
                'maximo_costo' => round(floatval($result['maximo_costo'] ?? 0), 2),
                'valor_total_importado' => round(floatval($result['valor_total_importado'] ?? 0), 2),
                'total_proveedores' => intval($result['total_proveedores'] ?? 0),
                'promedio_valor_fob' => round(floatval($result['promedio_valor_fob'] ?? 0), 2)
            );
            
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    /**
     * Obtiene top 10 proveedores por costo de nacionalización
     */
    public function getTopProveedoresPorCosto($fechaDesde = null, $fechaHasta = null) {
        $whereClause = "WHERE B.COSTO_NAC IS NOT NULL";
        
        if ($fechaDesde && $fechaHasta) {
            $whereClause .= " AND A.FECHA_DESP_ADU BETWEEN '$fechaDesde' AND '$fechaHasta'";
        }
        
        $sql = "SELECT TOP 10
                    A.COD_PROVEE,
                    A.PROVEEDOR,
                    AVG(B.COSTO_NAC * 100) as promedio_costo_nac,
                    COUNT(*) as total_despachos,
                    SUM(A.VALOR_FOB_PESO) as valor_total_fob
                FROM RO_T_IMPORTACIONES_ENCABEZADO A
                LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                $whereClause
                GROUP BY A.COD_PROVEE, A.PROVEEDOR
                ORDER BY promedio_costo_nac DESC";
        
        try {
            $stmt = sqlsrv_query($this->conexion, $sql);
            $rows = array();
            
            while($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = array(
                    'cod_proveedor' => $v['COD_PROVEE'],
                    'proveedor' => $v['PROVEEDOR'],
                    'promedio_costo_nac' => round($v['promedio_costo_nac'], 2),
                    'total_despachos' => $v['total_despachos'],
                    'valor_total_fob' => round($v['valor_total_fob'], 2)
                );
            }
            
            return $rows;
            
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    /**
     * Obtiene lista de proveedores para filtros
     */
    public function getProveedores() {
        $sql = "SELECT DISTINCT A.COD_PROVEE, A.PROVEEDOR 
                FROM RO_T_IMPORTACIONES_ENCABEZADO A
                LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                WHERE B.COSTO_NAC IS NOT NULL
                ORDER BY A.PROVEEDOR";
        
        try {
            $stmt = sqlsrv_query($this->conexion, $sql);
            $rows = array();
            
            while($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = array(
                    'cod_proveedor' => $v['COD_PROVEE'],
                    'proveedor' => $v['PROVEEDOR']
                );
            }
            
            return $rows;
            
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    /**
     * Obtiene distribución de costos por rangos nuevos: <40%, 40%-80%, >80%
     */
    public function getDistribucionCostosPorRangos($fechaDesde = null, $fechaHasta = null) {
        $whereClause = "WHERE B.COSTO_NAC IS NOT NULL";
        
        if ($fechaDesde && $fechaHasta) {
            $whereClause .= " AND A.FECHA_DESP_ADU BETWEEN '$fechaDesde' AND '$fechaHasta'";
        }
        
        $sql = "SELECT 
                    CASE 
                        WHEN CAST(B.COSTO_NAC AS FLOAT) * 100 < 40 THEN '< 40%'
                        WHEN CAST(B.COSTO_NAC AS FLOAT) * 100 >= 40 AND CAST(B.COSTO_NAC AS FLOAT) * 100 <= 80 THEN 'Entre 40% y 80%'
                        ELSE '> 80%'
                    END as rango_costo,
                    COUNT(*) as cantidad_despachos,
                    AVG(CAST(B.COSTO_NAC AS FLOAT) * 100) as promedio_rango
                FROM RO_T_IMPORTACIONES_ENCABEZADO A
                LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                $whereClause
                GROUP BY 
                    CASE 
                        WHEN CAST(B.COSTO_NAC AS FLOAT) * 100 < 40 THEN '< 40%'
                        WHEN CAST(B.COSTO_NAC AS FLOAT) * 100 >= 40 AND CAST(B.COSTO_NAC AS FLOAT) * 100 <= 80 THEN 'Entre 40% y 80%'
                        ELSE '> 80%'
                    END
                ORDER BY 
                    CASE 
                        WHEN CAST(B.COSTO_NAC AS FLOAT) * 100 < 40 THEN 1
                        WHEN CAST(B.COSTO_NAC AS FLOAT) * 100 >= 40 AND CAST(B.COSTO_NAC AS FLOAT) * 100 <= 80 THEN 2
                        ELSE 3
                    END";
        
        try {
            $stmt = sqlsrv_query($this->conexion, $sql);
            $rows = array();
            
            while($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = array(
                    'rango_costo' => $v['rango_costo'],
                    'cantidad_despachos' => intval($v['cantidad_despachos'] ?? 0),
                    'promedio_rango' => round(floatval($v['promedio_rango'] ?? 0), 2)
                );
            }
            
            return $rows;
            
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
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
        
        return $meses[$numeroMes] ?? '';
    }
}

?>