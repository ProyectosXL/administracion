<?php
require_once __DIR__ . '/Database.php';

/**
 * Clase ResumenServicios
 * Gestiona consultas de egresos con tipo_gasto = 'Servicios'
 * Agrupa por motivo con directores como columnas
 */
class ResumenServicios {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getAppsConnection();
    }
    
    /**
     * Obtiene resumen de servicios agrupados por motivo y director
     * Estructura retornada:
     * [
     *   'motivos' => ['SUELDOS', 'PROVEEDORES', ...],
     *   'directores' => ['Alberto', 'Jorge', ...],
     *   'datos' => [
     *     'SUELDOS' => ['Alberto' => 1000, 'Jorge' => 2000],
     *     'PROVEEDORES' => ['Alberto' => 500, 'Jorge' => 1500],
     *   ],
     *   'totales_por_director' => ['Alberto' => 1500, 'Jorge' => 3500],
     *   'totales_por_motivo' => ['SUELDOS' => 3000, 'PROVEEDORES' => 2000]
     * ]
     */
    public function obtenerResumenPorMotivo($fechaDesde, $fechaHasta) {
        try {
            $sql = "SELECT 
                        e.motivo,
                        e.nombre_director,
                        SUM(e.importe) as total
                    FROM egresos e
                    WHERE e.tipo_gasto = 'Servicios'
                        AND e.nombre_director IS NOT NULL
                        AND e.fecha_carga BETWEEN ? AND ?
                    GROUP BY e.motivo, e.nombre_director
                    ORDER BY e.motivo ASC, e.nombre_director ASC";
            
            $params = [$fechaDesde, $fechaHasta];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener resumen de servicios: " . print_r(sqlsrv_errors(), true));
            }
            
            // Inicializar estructura
            $motivos = [];
            $directores = [];
            $datos = [];
            $totalesPorDirector = [];
            $totalesPorMotivo = [];
            
            // Procesar resultados
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $motivo = $row['motivo'];
                $director = $row['nombre_director'];
                $total = (float)$row['total'];
                
                // Agregar motivo si no existe
                if (!in_array($motivo, $motivos)) {
                    $motivos[] = $motivo;
                    $datos[$motivo] = [];
                    $totalesPorMotivo[$motivo] = 0;
                }
                
                // Agregar director si no existe
                if (!in_array($director, $directores)) {
                    $directores[] = $director;
                    $totalesPorDirector[$director] = 0;
                }
                
                // Guardar dato
                $datos[$motivo][$director] = $total;
                
                // Actualizar totales
                $totalesPorDirector[$director] += $total;
                $totalesPorMotivo[$motivo] += $total;
            }
            
            sqlsrv_free_stmt($stmt);
            
            // Ordenar arrays
            sort($motivos);
            sort($directores);
            
            return [
                'motivos' => $motivos,
                'directores' => $directores,
                'datos' => $datos,
                'totales_por_director' => $totalesPorDirector,
                'totales_por_motivo' => $totalesPorMotivo
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerResumenPorMotivo: " . $e->getMessage());
            return [
                'motivos' => [],
                'directores' => [],
                'datos' => [],
                'totales_por_director' => [],
                'totales_por_motivo' => []
            ];
        }
    }
    
    /**
     * Obtiene el total de servicios en el rango de fechas
     */
    public function obtenerTotalServicios($fechaDesde, $fechaHasta): float {
        try {
            $sql = "SELECT COALESCE(SUM(importe), 0) as total
                    FROM egresos
                    WHERE tipo_gasto = 'Servicios'
                        AND nombre_director IS NOT NULL
                        AND fecha_carga BETWEEN ? AND ?";
            
            $params = [$fechaDesde, $fechaHasta];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener total de servicios: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error en obtenerTotalServicios: " . $e->getMessage());
            return 0.0;
        }
    }
}
