<?php
require_once __DIR__ . '/Database.php';

/**
 * Clase EgresoSocios
 * Gestiona consultas de egresos de socios combinando:
 * - Tabla 'egresos' (efectivo)
 * - Tabla 'solicitudes_egresos' (transferencias)
 */
class EgresoSocios {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getAppsConnection();
    }
    
    /**
     * Obtiene lista de directores desde RO_T_DIRECTORES
     */
    public function obtenerDirectores(): array {
        try {
            $sql = "SELECT ID_DIRECTOR, NOMBRE FROM RO_T_DIRECTORES ORDER BY NOMBRE";
            $stmt = sqlsrv_query($this->db, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener directores: " . print_r(sqlsrv_errors(), true));
            }
            
            $directores = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $directores[] = [
                    'id' => $row['ID_DIRECTOR'],
                    'nombre' => $row['NOMBRE']
                ];
            }
            
            sqlsrv_free_stmt($stmt);
            return $directores;
        } catch (Exception $e) {
            error_log("Error en obtenerDirectores: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene egresos EFECTIVO por director y fecha
     * Fuente: tabla egresos WHERE nombre_director IS NOT NULL
     */
    public function obtenerEgresosEfectivo(string $fechaDesde, string $fechaHasta): array {
        try {
            $sql = "SELECT 
                        CAST(e.fecha AS DATE) as fecha,
                        e.nombre_director,
                        SUM(e.importe) as total
                    FROM egresos e
                    WHERE e.nombre_director IS NOT NULL
                        AND e.fecha BETWEEN ? AND ?
                    GROUP BY CAST(e.fecha AS DATE), e.nombre_director
                    ORDER BY CAST(e.fecha AS DATE) ASC";
            
            $params = [$fechaDesde, $fechaHasta];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener egresos efectivo: " . print_r(sqlsrv_errors(), true));
            }
            
            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir fecha DateTime a string
                if (is_object($row['fecha'])) {
                    $row['fecha'] = $row['fecha']->format('Y-m-d');
                }
                $resultados[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            return $resultados;
        } catch (Exception $e) {
            error_log("Error en obtenerEgresosEfectivo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene egresos TRANSFERENCIA por director y fecha
     * Fuente: tabla solicitudes_egresos WHERE estado = 'PAGADO'
     * Join con RO_T_DIRECTORES para obtener nombre
     */
    public function obtenerEgresosTransferencia(string $fechaDesde, string $fechaHasta): array {
        try {
            $sql = "SELECT 
                        CAST(s.fecha_modificacion AS DATE) as fecha,
                        d.NOMBRE as nombre_director,
                        SUM(s.importe) as total
                    FROM solicitudes_egresos s
                    INNER JOIN RO_T_DIRECTORES d ON s.id_director = d.ID_DIRECTOR
                    WHERE s.estado = 'PAGADO'
                        AND s.fecha_modificacion BETWEEN ? AND ?
                    GROUP BY CAST(s.fecha_modificacion AS DATE), d.NOMBRE
                    ORDER BY CAST(s.fecha_modificacion AS DATE) ASC";
            
            $params = [$fechaDesde, $fechaHasta];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener egresos transferencia: " . print_r(sqlsrv_errors(), true));
            }
            
            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir fecha DateTime a string
                if (is_object($row['fecha'])) {
                    $row['fecha'] = $row['fecha']->format('Y-m-d');
                }
                $resultados[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            return $resultados;
        } catch (Exception $e) {
            error_log("Error en obtenerEgresosTransferencia: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene resumen combinado de egresos efectivo + transferencia
     * Organizado por director con subtotales
     */
    public function obtenerResumenPorDirector(string $fechaDesde, string $fechaHasta): array {
        try {
            // Obtener directores
            $directores = $this->obtenerDirectores();
            
            // Obtener egresos efectivo
            $efectivo = $this->obtenerEgresosEfectivo($fechaDesde, $fechaHasta);
            
            // Obtener egresos transferencia
            $transferencia = $this->obtenerEgresosTransferencia($fechaDesde, $fechaHasta);
            
            // Organizar datos
            $resumen = [
                'directores' => [],
                'efectivo' => [],
                'transferencia' => [],
                'totales' => []
            ];
            
            // Crear índice de directores
            foreach ($directores as $director) {
                $resumen['directores'][$director['nombre']] = $director['id'];
                $resumen['totales'][$director['nombre']] = 0;
            }
            
            // Organizar efectivo por fecha y director
            foreach ($efectivo as $item) {
                $fecha = $item['fecha'];
                $director = $item['nombre_director'];
                $total = (float)$item['total'];
                
                if (!isset($resumen['efectivo'][$fecha])) {
                    $resumen['efectivo'][$fecha] = [];
                }
                
                $resumen['efectivo'][$fecha][$director] = $total;
                
                if (isset($resumen['totales'][$director])) {
                    $resumen['totales'][$director] += $total;
                }
            }
            
            // Organizar transferencia por fecha y director
            foreach ($transferencia as $item) {
                $fecha = $item['fecha'];
                $director = $item['nombre_director'];
                $total = (float)$item['total'];
                
                if (!isset($resumen['transferencia'][$fecha])) {
                    $resumen['transferencia'][$fecha] = [];
                }
                
                $resumen['transferencia'][$fecha][$director] = $total;
                
                if (isset($resumen['totales'][$director])) {
                    $resumen['totales'][$director] += $total;
                }
            }
            
            return $resumen;
        } catch (Exception $e) {
            error_log("Error en obtenerResumenPorDirector: " . $e->getMessage());
            return [
                'directores' => [],
                'efectivo' => [],
                'transferencia' => [],
                'totales' => []
            ];
        }
    }
    
    /**
     * Obtiene detalle completo de egresos combinando ambas fuentes
     * Columnas: FECHA | CODIGO | DIRECTOR | MOTIVO | ORIGEN | IMPORTE
     */
    public function obtenerDetalleCompleto(string $fechaDesde, string $fechaHasta): array {
        try {
            // Query combinada con UNION ALL
            $sql = "SELECT 
                        fecha, codigo, director, motivo, origen, importe
                    FROM (
                        -- Fuente 1: egresos (EFECTIVO)
                        SELECT 
                            CAST(e.fecha AS DATE) AS fecha,
                            CONCAT(e.COD_COMP, e.N_COMP) AS codigo,
                            e.nombre_director AS director,
                            e.motivo,
                            'MANUAL' AS origen,
                            e.importe
                        FROM egresos e
                        WHERE e.nombre_director IS NOT NULL
                          AND e.fecha BETWEEN ? AND ?
                        
                        UNION ALL
                        
                        -- Fuente 2: solicitudes_egresos (TRANSFERENCIA)
                        SELECT 
                            CAST(s.fecha_modificacion AS DATE) AS fecha,
                            s.id_solicitud AS codigo,
                            d.NOMBRE AS director,
                            s.motivo,
                            'APP EGRESOS' AS origen,
                            s.importe
                        FROM solicitudes_egresos s
                        INNER JOIN RO_T_DIRECTORES d ON s.id_director = d.ID_DIRECTOR
                        WHERE s.estado = 'PAGADO'
                          AND s.fecha_modificacion BETWEEN ? AND ?
                    ) AS egresos_combinados
                    ORDER BY fecha DESC, origen, director";
            
            $params = [$fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener detalle completo: " . print_r(sqlsrv_errors(), true));
            }
            
            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir fecha DateTime a string
                if (is_object($row['fecha'])) {
                    $row['fecha'] = $row['fecha']->format('Y-m-d');
                }
                
                // Formatear importe
                $row['importe'] = (float)$row['importe'];
                
                $resultados[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            return $resultados;
        } catch (Exception $e) {
            error_log("Error en obtenerDetalleCompleto: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene el total de egresos de socios en el rango de fechas
     */
    public function obtenerTotalEgresos(string $fechaDesde, string $fechaHasta): float {
        try {
            $sql = "SELECT 
                        COALESCE(SUM(importe), 0) as total
                    FROM (
                        SELECT importe FROM egresos 
                        WHERE nombre_director IS NOT NULL 
                          AND fecha BETWEEN ? AND ?
                        
                        UNION ALL
                        
                        SELECT importe FROM solicitudes_egresos 
                        WHERE estado = 'PAGADO' 
                          AND fecha_modificacion BETWEEN ? AND ?
                    ) AS egresos_combinados";
            
            $params = [$fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener total: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error en obtenerTotalEgresos: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Obtiene el detalle completo de un egreso específico por origen y código
     */
    public function obtenerDetalleEgresoPorCodigo(string $origen, string $codigo): ?array {
        try {
            if ($origen === 'MANUAL') {
                // Buscar en tabla egresos
                // El código viene como COD_COMP + N_COMP concatenado
                $sql = "SELECT 
                            CAST(fecha AS DATE) as fecha,
                            CONCAT(COD_COMP, N_COMP) as codigo,
                            nombre_director as director,
                            motivo,
                            importe,
                            observaciones,
                            fecha_carga,
                            CASE WHEN foto IS NOT NULL THEN 1 ELSE 0 END as tiene_foto,
                            foto
                        FROM egresos
                        WHERE CONCAT(COD_COMP, N_COMP) = ?
                          AND nombre_director IS NOT NULL";
                
                $stmt = sqlsrv_query($this->db, $sql, [$codigo]);
                
            } else if ($origen === 'APP EGRESOS') {
                // Buscar en tabla solicitudes_egresos
                $sql = "SELECT 
                            CAST(s.fecha_modificacion AS DATE) as fecha,
                            s.id_solicitud as codigo,
                            d.NOMBRE as director,
                            s.motivo,
                            s.importe,
                            s.observaciones,
                            s.observaciones_proveedores,
                            s.observaciones_tesoreria,
                            s.estado,
                            s.fecha_solicitud,
                            s.fecha_modificacion,
                            s.usuario_modificacion,
                            0 as tiene_foto,
                            NULL as foto
                        FROM solicitudes_egresos s
                        INNER JOIN RO_T_DIRECTORES d ON s.id_director = d.ID_DIRECTOR
                        WHERE s.id_solicitud = ?
                          AND s.estado = 'PAGADO'";
                
                $stmt = sqlsrv_query($this->db, $sql, [$codigo]);
                
            } else {
                throw new Exception("Origen no válido");
            }
            
            if ($stmt === false) {
                throw new Exception("Error al obtener detalle: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if ($result) {
                // Convertir fechas DateTime a string
                if (isset($result['fecha']) && is_object($result['fecha'])) {
                    $result['fecha'] = $result['fecha']->format('Y-m-d');
                }
                if (isset($result['fecha_carga']) && is_object($result['fecha_carga'])) {
                    $result['fecha_carga'] = $result['fecha_carga']->format('Y-m-d H:i:s');
                }
                if (isset($result['fecha_solicitud']) && is_object($result['fecha_solicitud'])) {
                    $result['fecha_solicitud'] = $result['fecha_solicitud']->format('Y-m-d H:i:s');
                }
                if (isset($result['fecha_modificacion']) && is_object($result['fecha_modificacion'])) {
                    $result['fecha_modificacion'] = $result['fecha_modificacion']->format('Y-m-d H:i:s');
                }
                
                // Convertir importe a float
                $result['importe'] = (float)$result['importe'];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error en obtenerDetalleEgresoPorCodigo: " . $e->getMessage());
            return null;
        }
    }
}
