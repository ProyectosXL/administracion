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
    public function obtenerDirectores() {
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
     * COMPENSACION_IVA: se RESTA del total (signo negativo)
     * Para pagos de servicios (Pago de seguros, patentes, expensas): usa fecha_carga
     */
    public function obtenerEgresosEfectivo($fechaDesde, $fechaHasta) {
        try {
            $sql = "SELECT 
                        CASE 
                            WHEN e.motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas') 
                            THEN CAST(e.fecha_carga AS DATE)
                            ELSE CAST(e.fecha AS DATE)
                        END as fecha,
                        e.nombre_director,
                        SUM(CASE 
                            WHEN e.motivo = 'COMPENSACION_IVA' THEN -e.importe 
                            ELSE e.importe 
                        END) as total
                    FROM egresos e
                    WHERE e.nombre_director IS NOT NULL
                        AND (
                            (e.motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas') 
                             AND CAST(e.fecha_carga AS DATE) BETWEEN ? AND ?)
                            OR
                            (e.motivo NOT IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas')
                             AND e.fecha BETWEEN ? AND ?)
                        )
                    GROUP BY 
                        CASE 
                            WHEN e.motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas') 
                            THEN CAST(e.fecha_carga AS DATE)
                            ELSE CAST(e.fecha AS DATE)
                        END, 
                        e.nombre_director
                    ORDER BY 
                        CASE 
                            WHEN e.motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas') 
                            THEN CAST(e.fecha_carga AS DATE)
                            ELSE CAST(e.fecha AS DATE)
                        END ASC";
            
            // Pasar las fechas 4 veces (2 para cada condición del WHERE)
            $params = [$fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta];
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
    public function obtenerEgresosTransferencia($fechaDesde, $fechaHasta) {
        try {
            $sql = "SELECT 
                        CAST(s.fecha_modificacion AS DATE) as fecha,
                        d.NOMBRE as nombre_director,
                        SUM(s.importe) as total
                    FROM solicitudes_egresos s
                    INNER JOIN RO_T_DIRECTORES d ON s.id_director = d.ID_DIRECTOR
                    WHERE s.estado = 'PAGADO'
                        AND s.fecha_modificacion IS NOT NULL
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
    public function obtenerResumenPorDirector($fechaDesde, $fechaHasta) {
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
     * Columnas: FECHA | CODIGO | DIRECTOR | MOTIVO | ORIGEN | IMPORTE | PROVEEDOR | CBU
     * COMPENSACION_IVA: se muestra con signo negativo
     * Para pagos de servicios (Pago de seguros, patentes, expensas): usa fecha_carga
     */
    public function obtenerDetalleCompleto($fechaDesde, $fechaHasta) {
        try {
            // Query combinada con UNION ALL
            $sql = "SELECT 
                        fecha, codigo, director, motivo, origen, importe, 
                        proveedor, cbu, descripcion_cbu
                    FROM (
                        -- Fuente 1: egresos (EFECTIVO)
                        SELECT 
                            CASE 
                                WHEN e.motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas') 
                                THEN CAST(e.fecha_carga AS DATE)
                                ELSE CAST(e.fecha AS DATE)
                            END AS fecha,
                            ISNULL(e.COD_COMP, '') + ISNULL(e.N_COMP, '') AS codigo,
                            e.nombre_director AS director,
                            e.motivo,
                            'MANUAL' AS origen,
                            CASE 
                                WHEN e.motivo = 'COMPENSACION_IVA' THEN -e.importe 
                                ELSE e.importe 
                            END AS importe,
                            NULL as proveedor,
                            NULL as cbu,
                            NULL as descripcion_cbu
                        FROM egresos e
                        WHERE e.nombre_director IS NOT NULL
                          AND (
                            (e.motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas') 
                             AND CAST(e.fecha_carga AS DATE) BETWEEN ? AND ?)
                            OR
                            (e.motivo NOT IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas')
                             AND e.fecha BETWEEN ? AND ?)
                          )
                        
                        UNION ALL
                        
                        -- Fuente 2: solicitudes_egresos (TRANSFERENCIA)
                        SELECT 
                            CAST(s.fecha_modificacion AS DATE) AS fecha,
                            s.id_solicitud AS codigo,
                            d.NOMBRE AS director,
                            s.motivo,
                            'APP EGRESOS' AS origen,
                            s.importe,
                            ISNULL(s.NOM_PROVEE, '') as proveedor,
                            ISNULL(s.CBU, '') as cbu,
                            ISNULL(s.DESCRIPCION_CBU, '') as descripcion_cbu
                        FROM solicitudes_egresos s
                        INNER JOIN RO_T_DIRECTORES d ON s.id_director = d.ID_DIRECTOR
                        WHERE s.estado = 'PAGADO'
                          AND s.fecha_modificacion IS NOT NULL
                          AND s.fecha_modificacion BETWEEN ? AND ?
                    ) AS egresos_combinados
                    ORDER BY fecha DESC, origen, director";
            
            // Pasar las fechas 6 veces (4 para egresos MANUAL + 2 para solicitudes_egresos)
            $params = [$fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta];
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
     * COMPENSACION_IVA: se resta del total en lugar de sumar
     */
    public function obtenerTotalEgresos($fechaDesde, $fechaHasta): float {
        try {
            $sql = "SELECT 
                        COALESCE(SUM(importe_ajustado), 0) as total
                    FROM (
                        SELECT 
                            CASE 
                                WHEN motivo = 'COMPENSACION_IVA' THEN -importe 
                                ELSE importe 
                            END as importe_ajustado
                        FROM egresos 
                        WHERE nombre_director IS NOT NULL 
                          AND (
                              (motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas') 
                               AND CAST(fecha_carga AS DATE) BETWEEN ? AND ?)
                              OR
                              (motivo NOT IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas') 
                               AND fecha BETWEEN ? AND ?)
                          )
                        
                        UNION ALL
                        
                        SELECT importe as importe_ajustado
                        FROM solicitudes_egresos 
                        WHERE estado = 'PAGADO' 
                          AND fecha_modificacion IS NOT NULL
                          AND fecha_modificacion BETWEEN ? AND ?
                    ) AS egresos_combinados";
            
            $params = [$fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta, $fechaDesde, $fechaHasta];
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
    public function obtenerDetalleEgresoPorCodigo($origen, $codigo): ?array {
        try {
            if ($origen === 'MANUAL') {
                // Buscar en tabla egresos
                // El código viene como COD_COMP + N_COMP concatenado
                $sql = "SELECT 
                            CAST(e.fecha AS DATE) as fecha,
                            ISNULL(e.COD_COMP, '') + ISNULL(e.N_COMP, '') as codigo,
                            e.nombre_director as director,
                            e.motivo,
                            e.importe,
                            e.observaciones,
                            e.fecha_carga,
                            CASE WHEN e.foto IS NOT NULL THEN 1 ELSE 0 END as tiene_foto,
                            e.foto,
                            p.NOM_PROVEE as proveedor_nom,
                            p.CBU as proveedor_cbu,
                            p.DESCRIPCION_CBU as proveedor_descripcion_cbu
                        FROM egresos e
                        LEFT JOIN FT_T_PROVEEDORES p ON e.id = p.id_egresos
                        WHERE ISNULL(e.COD_COMP, '') + ISNULL(e.N_COMP, '') = ?
                          AND e.nombre_director IS NOT NULL";
                
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
                            ISNULL(s.NOM_PROVEE, '') as proveedor,
                            ISNULL(s.CBU, '') as cbu,
                            ISNULL(s.DESCRIPCION_CBU, '') as descripcion_cbu,
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
                
                // Detectar tipo de archivo si tiene foto
                if ($result['tiene_foto'] && !empty($result['foto'])) {
                    $fotoCompleta = $result['foto'];
                    $result['tipo_archivo'] = 'image/jpeg'; // Por defecto
                    
                    // Si el dato ya incluye el prefijo data:mime;base64, extraerlo
                    if (strpos($fotoCompleta, 'data:') === 0) {
                        // Formato: data:application/pdf;base64,JVBERi0...
                        preg_match('/^data:([^;]+);base64,(.+)$/', $fotoCompleta, $matches);
                        if ($matches) {
                            $result['tipo_archivo'] = $matches[1];
                            $result['foto'] = $matches[2]; // Actualizar con solo el base64
                        }
                    } else {
                        // Es base64 puro, detectar por contenido
                        $foto = $result['foto'];
                        
                        // Los PDFs en base64 empiezan con "JVBERi0"
                        if (strpos($foto, 'JVBERi0') === 0) {
                            $result['tipo_archivo'] = 'application/pdf';
                        }
                        // Las imágenes JPEG empiezan con "/9j/"
                        else if (strpos($foto, '/9j/') === 0) {
                            $result['tipo_archivo'] = 'image/jpeg';
                        }
                        // Las imágenes PNG empiezan con "iVBORw0KGgo"
                        else if (strpos($foto, 'iVBORw0KGgo') === 0) {
                            $result['tipo_archivo'] = 'image/png';
                        }
                    }
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error en obtenerDetalleEgresoPorCodigo: " . $e->getMessage());
            return null;
        }
    }
}
