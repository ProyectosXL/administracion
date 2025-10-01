<?php
require_once __DIR__ . '/Database.php';

/**
 * Clase Ingreso
 * Gestiona las operaciones CRUD de ingresos de caja
 */
class Ingreso {
    private $db;
    private $dbCentral;
    
    public function __construct() {
        $this->db = Database::getInstance()->getAppsConnection();
        $this->dbCentral = Database::getInstance()->getCentralConnection();
    }
    
    /**
     * Genera el próximo número de comprobante
     */
    private function generarNumeroComprobante(): string {
        $sql = "SELECT MAX(CAST(N_COMP AS BIGINT)) as max_comp 
                FROM ingresos 
                WHERE COD_COMP = 'ING'";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
        }
        
        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        
        $ultimoNumero = $result['max_comp'] ?? 10000000000;
        $nuevoNumero = $ultimoNumero + 1;
        
        return str_pad($nuevoNumero, 11, '0', STR_PAD_LEFT);
    }
    
    /**
     * Crea un nuevo ingreso
     */
    public function crear(array $datos): bool {
        try {
            $sql = "INSERT INTO ingresos (
                        ID_SBA05, COD_COMP, N_COMP, fecha, importe, 
                        observaciones, recibido, fecha_carga, origen
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, GETDATE(), ?
                    )";
            
            $nComp = $this->generarNumeroComprobante();
            
            $params = [
                $datos['id_sba05'] ?? null,
                'ING',
                $nComp,
                $datos['fecha'],
                $datos['importe'],
                $datos['observaciones'] ?? '',
                $datos['recibido'] ?? 0,
                $datos['origen'] ?? 'MANUAL'
            ];
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Error al crear ingreso: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene todos los ingresos con filtros opcionales
     */
    public function obtenerTodos(array $filtros = []): array {
        try {
            $sql = "SELECT *, CAST(fecha_carga AS DATE) as fecha_solo FROM ingresos WHERE 1=1";
            $params = [];
            
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND fecha >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND fecha <= ?";
                $params[] = $filtros['fecha_hasta'];
            }
            
            if (isset($filtros['recibido'])) {
                $sql .= " AND recibido = ?";
                $params[] = $filtros['recibido'];
            }
            
            $sql .= " ORDER BY fecha DESC, id DESC";
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultados[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            return $resultados;
        } catch (Exception $e) {
            error_log("Error al obtener ingresos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene ingresos desde la fuente 599 (Base Central)
     */
    public function obtenerIngresos599($desde, $hasta): array {
        try {
            $sql = "SELECT    
                        ID_SBA05,    
                        CAST(FECHA AS DATE) FECHA,    
                        COD_COMP,    
                        N_COMP,      
                        LEYENDA OBSERVACIONES,    
                        CAST(MONTO AS FLOAT) MONTO
                    FROM SBA05    
                    WHERE COD_CTA = '100130'    
                      AND FECHA BETWEEN ? AND ?
                      AND D_H = 'D'
                    ORDER BY FECHA DESC";
            
            $params = [$desde, $hasta];
            $stmt = sqlsrv_query($this->dbCentral, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en consulta 599: " . print_r(sqlsrv_errors(), true));
            }
            
            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Verificar si ya fue insertado en tabla ingresos (para evitar duplicados)
                $yaInsertado = $this->verificarRecibido599($row['ID_SBA05']);
                
                // Solo mostrar si NO está ya insertado en tabla ingresos (evitar duplicados)
                if (!$yaInsertado) {
                    $resultados[] = [
                        'id' => 'EXT_599_' . $row['ID_SBA05'], // ID único para identificar
                        'ID_SBA05' => $row['ID_SBA05'],
                        'fecha' => $row['FECHA'],
                        'COD_COMP' => $row['COD_COMP'],
                        'N_COMP' => $row['N_COMP'],
                        'observaciones' => $row['OBSERVACIONES'],
                        'importe' => $row['MONTO'],
                        'recibido' => 1, // Fuente 599 viene RECIBIDA por defecto
                        'origen' => '599'
                    ];
                }
            }
            
            sqlsrv_free_stmt($stmt);
            return $resultados;
        } catch (Exception $e) {
            error_log("Error en obtenerIngresos599: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene ingresos desde TESORERIA (Base Central)
     */
    public function obtenerIngresosTesoreria($desde, $hasta): array {
        try {
            $sql = "SELECT 
                        CAST(fecha_cobro AS DATE) FECHA, 
                        ID, 
                        (UPPER(user_rinde) + ' - ' + nombre_cliente) OBSERVACIONES, 
                        CAST(importe_efectivo AS FLOAT) MONTO 
                    FROM sj_administracion_cobros 
                    WHERE importe_efectivo > 0
                      AND fecha_cobro BETWEEN ? AND ?
                    ORDER BY fecha_cobro DESC";
            
            $params = [$desde, $hasta];
            $stmt = sqlsrv_query($this->dbCentral, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en consulta TESORERIA: " . print_r(sqlsrv_errors(), true));
            }
            
            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Verificar si ya fue insertado en tabla ingresos (para evitar duplicados)
                $yaInsertado = $this->verificarRecibidoTesoreria($row['ID']);
                
                // Solo mostrar si NO está ya insertado en tabla ingresos (evitar duplicados)
                if (!$yaInsertado) {
                    $resultados[] = [
                        'id' => 'EXT_TES_' . $row['ID'], // ID único para identificar
                        'ID_TESORERIA' => $row['ID'], // Nuevo campo para identificar origen tesorería
                        'ID_SBA05' => null, // No aplica para tesorería
                        'fecha' => $row['FECHA'],
                        'COD_COMP' => '', // Vacío para tesorería
                        'N_COMP' => '', // Vacío para tesorería
                        'observaciones' => $row['OBSERVACIONES'],
                        'importe' => $row['MONTO'],
                        'recibido' => 0, // TESORERÍA viene PENDIENTE, requiere checkbox
                        'origen' => 'TESORERIA'
                    ];
                }
            }
            
            sqlsrv_free_stmt($stmt);
            return $resultados;
        } catch (Exception $e) {
            error_log("Error en obtenerIngresosTesoreria: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un ingreso de la fuente 599 ya fue marcado como recibido
     */
    private function verificarRecibido599($idSba05): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM ingresos WHERE ID_SBA05 = ? AND origen = '599'";
            $stmt = sqlsrv_query($this->db, $sql, [$idSba05]);
            
            if ($stmt === false) {
                return 0;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return $row['count'] > 0 ? 1 : 0;
        } catch (Exception $e) {
            error_log("Error en verificarRecibido599: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verifica si un ingreso de TESORERÍA ya fue marcado como recibido
     */
    private function verificarRecibidoTesoreria($idTesoreria): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM ingresos WHERE ID_TESORERIA = ? AND origen = 'TESORERIA'";
            $stmt = sqlsrv_query($this->db, $sql, [$idTesoreria]);
            
            if ($stmt === false) {
                return 0;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return $row['count'] > 0 ? 1 : 0;
        } catch (Exception $e) {
            error_log("Error en verificarRecibidoTesoreria: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtiene ingresos combinados de todas las fuentes
     */
    public function obtenerIngresosCombinados($desde, $hasta): array {
        $resultados = [];
        
        // 1. Ingresos MANUALES (desde tabla ingresos local)
        $filtros = [
            'fecha_desde' => $desde,
            'fecha_hasta' => $hasta
        ];
        $manuales = $this->obtenerTodos($filtros);
        
        foreach ($manuales as $manual) {
            $resultados[] = [
                'id' => 'MAN_' . $manual['id'],
                'ID_SBA05' => $manual['ID_SBA05'],
                'fecha' => $manual['fecha'],
                'COD_COMP' => $manual['COD_COMP'],
                'N_COMP' => $manual['N_COMP'],
                'observaciones' => $manual['observaciones'],
                'importe' => $manual['importe'],
                'recibido' => $manual['recibido'],
                'origen' => $manual['origen'] ?? 'MANUAL',
                'fecha_carga' => $manual['fecha_carga'] ?? null
            ];
        }
        
        // 2. Ingresos desde fuente 599
        $ingresos599 = $this->obtenerIngresos599($desde, $hasta);
        $resultados = array_merge($resultados, $ingresos599);
        
        // 3. Ingresos desde TESORERIA
        $ingresosTesoreria = $this->obtenerIngresosTesoreria($desde, $hasta);
        $resultados = array_merge($resultados, $ingresosTesoreria);
        
        // Ordenar por fecha descendente
        usort($resultados, function($a, $b) {
            $fechaA = is_object($a['fecha']) ? $a['fecha']->format('Y-m-d') : $a['fecha'];
            $fechaB = is_object($b['fecha']) ? $b['fecha']->format('Y-m-d') : $b['fecha'];
            return strtotime($fechaB) - strtotime($fechaA);
        });
        
        return $resultados;
    }
    
    /**
     * Marca un ingreso 599 como recibido (lo inserta en tabla ingresos)
     */
    public function marcarRecibido599($idSba05, $fecha, $codComp, $nComp, $observaciones, $importe): bool {
        try {
            // Verificar si ya existe
            if ($this->verificarRecibido599($idSba05)) {
                return true; // Ya existe
            }
            
            $sql = "INSERT INTO ingresos (
                        ID_SBA05, COD_COMP, N_COMP, fecha, importe, 
                        observaciones, recibido, fecha_carga, origen
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, 1, GETDATE(), '599'
                    )";
            
            $params = [
                $idSba05,
                $codComp,
                $nComp,
                $fecha,
                $importe,
                $observaciones
            ];
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al insertar ingreso 599: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Error en marcarRecibido599: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca un ingreso de TESORERÍA como recibido (lo inserta en tabla ingresos)
     */
    public function marcarRecibidoTesoreria($idTesoreria, $fecha, $observaciones, $importe): bool {
        try {
            // Verificar si ya existe
            if ($this->verificarRecibidoTesoreria($idTesoreria)) {
                return true; // Ya existe
            }
            
            // Generar número de comprobante para TESORERÍA
            $numeroComprobante = $this->generarNumeroComprobante();
            
            $sql = "INSERT INTO ingresos (
                        ID_TESORERIA, COD_COMP, N_COMP, fecha, importe, observaciones, 
                        recibido, fecha_carga, origen
                    ) VALUES (
                        ?, 'TES', ?, ?, ?, ?, 
                        1, GETDATE(), 'TESORERIA'
                    )";
            
            $params = [
                $idTesoreria,
                $numeroComprobante,
                $fecha,
                $importe,
                $observaciones
            ];
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al insertar ingreso TESORERÍA: " . print_r($errors, true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Error en marcarRecibidoTesoreria: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca un ingreso como recibido
     */
    public function marcarRecibido(int $id): bool {
        try {
            $sql = "UPDATE ingresos SET recibido = 1 WHERE id = ?";
            $params = [$id];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Error al marcar ingreso recibido: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el total de ingresos recibidos
     */
    public function obtenerTotalRecibido(): float {
        try {
            $sql = "SELECT COALESCE(SUM(importe), 0) as total 
                    FROM ingresos 
                    WHERE recibido = 1";
            $stmt = sqlsrv_query($this->db, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total: " . $e->getMessage());
            return 0.0;
        }
    }
}