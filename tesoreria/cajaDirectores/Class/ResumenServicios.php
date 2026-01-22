<?php
require_once __DIR__ . '/../../../class/conexion.php';

/**
 * Clase ResumenServicios
 * Gestiona consultas de egresos con tipo_gasto = 'Servicios'
 * Agrupa por motivo con directores como columnas
 */
class ResumenServicios {
    private $db;
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
        $this->db = $this->conexion->conectar('apps');
        
        if ($this->db === false) {
            throw new Exception("Error al conectar con la base de datos APPS en ResumenServicios");
        }
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
            // Primero obtener TODOS los motivos activos de la tabla maestra
            $sqlMotivos = "SELECT NOMBRE 
                          FROM RO_T_MOTIVOS_PAGO_SERVICIOS 
                          WHERE ACTIVO = 1 
                          ORDER BY ORDEN, NOMBRE";
            
            $stmtMotivos = sqlsrv_query($this->db, $sqlMotivos);
            
            if ($stmtMotivos === false) {
                throw new Exception("Error al obtener motivos: " . print_r(sqlsrv_errors(), true));
            }
            
            $todosMotivos = [];
            while ($row = sqlsrv_fetch_array($stmtMotivos, SQLSRV_FETCH_ASSOC)) {
                $todosMotivos[] = $row['NOMBRE'];
            }
            sqlsrv_free_stmt($stmtMotivos);
            
            // Obtener todos los directores
            $sqlDirectores = "SELECT DISTINCT nombre_director 
                             FROM egresos 
                             WHERE nombre_director IS NOT NULL 
                             ORDER BY nombre_director";
            
            $stmtDirectores = sqlsrv_query($this->db, $sqlDirectores);
            
            if ($stmtDirectores === false) {
                throw new Exception("Error al obtener directores: " . print_r(sqlsrv_errors(), true));
            }
            
            $todosDirectores = [];
            while ($row = sqlsrv_fetch_array($stmtDirectores, SQLSRV_FETCH_ASSOC)) {
                $todosDirectores[] = $row['nombre_director'];
            }
            sqlsrv_free_stmt($stmtDirectores);
            
            // Ahora obtener los gastos reales del período
            $sql = "SELECT 
                        e.motivo,
                        e.nombre_director,
                        SUM(e.importe) as total
                    FROM egresos e WITH (NOLOCK)
                    WHERE e.tipo_gasto = 'Servicios'
                        AND e.nombre_director IS NOT NULL
                        AND CAST(e.fecha_carga AS DATE) BETWEEN ? AND ?
                    GROUP BY e.motivo, e.nombre_director";
            
            $params = [$fechaDesde, $fechaHasta];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener resumen de servicios: " . print_r(sqlsrv_errors(), true));
            }
            
            // Inicializar estructura con TODOS los motivos y directores
            $datos = [];
            $totalesPorDirector = [];
            $totalesPorMotivo = [];
            
            // Inicializar todos los motivos con ceros
            foreach ($todosMotivos as $motivo) {
                $datos[$motivo] = [];
                $totalesPorMotivo[$motivo] = 0;
                foreach ($todosDirectores as $director) {
                    $datos[$motivo][$director] = 0;
                }
            }
            
            // Inicializar todos los directores con ceros
            foreach ($todosDirectores as $director) {
                $totalesPorDirector[$director] = 0;
            }
            
            // Procesar resultados reales y actualizar valores
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $motivo = $row['motivo'];
                $director = $row['nombre_director'];
                $total = (float)$row['total'];
                
                // Solo actualizar si el motivo está en la lista de motivos activos
                if (in_array($motivo, $todosMotivos)) {
                    // Guardar dato
                    $datos[$motivo][$director] = $total;
                    
                    // Actualizar totales
                    $totalesPorDirector[$director] += $total;
                    $totalesPorMotivo[$motivo] += $total;
                }
            }
            
            sqlsrv_free_stmt($stmt);
            
            return [
                'motivos' => $todosMotivos,
                'directores' => $todosDirectores,
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
                    FROM egresos WITH (NOLOCK)
                    WHERE tipo_gasto = 'Servicios'
                        AND nombre_director IS NOT NULL
                        AND CAST(fecha_carga AS DATE) BETWEEN ? AND ?";
            
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
