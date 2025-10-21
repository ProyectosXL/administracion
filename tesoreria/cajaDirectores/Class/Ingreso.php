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
    private function generarNumeroComprobante() {
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
    public function crear($datos) {
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
    public function obtenerTodos($filtros = []) {
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
     * Obtiene ingresos desde TESORERÍA (Base Central - SBA05)
     */
    public function obtenerIngresosTesoreria($desde, $hasta) {
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
                // Verificar si ya fue marcado como recibido en tabla ingresos
                $recibido = $this->verificarRecibidoTesoreria($row['ID_SBA05']);
                
                // Solo incluir si NO está marcado como recibido (evitar duplicados)
                if (!$recibido) {
                    $resultados[] = [
                        'id' => 'EXT_TES_' . $row['ID_SBA05'], // ID único para identificar
                        'ID_SBA05' => $row['ID_SBA05'],
                        'fecha' => $row['FECHA'],
                        'COD_COMP' => $row['COD_COMP'], // Ya viene correctamente separado de SBA05
                        'N_COMP' => $row['N_COMP'],     // Ya viene correctamente separado de SBA05
                        'observaciones' => $row['OBSERVACIONES'],
                        'importe' => $row['MONTO'],
                        'recibido' => 0, // Siempre pendiente hasta marcar
                        'origen' => 'TESORERIA'
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
     * Obtiene ingresos desde 599 (Base Central - sj_administracion_cobros)
     */
    public function obtenerIngresos599($desde, $hasta) {
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
                $resultados[] = [
                    'id' => 'EXT_599_' . $row['ID'], // ID único para identificar
                    'ID_SBA05' => null, // No aplica para 599
                    'fecha' => $row['FECHA'],
                    'COD_COMP' => '', // Vacío para 599
                    'N_COMP' => '', // Vacío para 599
                    'observaciones' => $row['OBSERVACIONES'],
                    'importe' => $row['MONTO'],
                    'recibido' => 1, // Siempre recibido para 599
                    'origen' => '599'
                ];
            }
            
            sqlsrv_free_stmt($stmt);
            return $resultados;
        } catch (Exception $e) {
            error_log("Error en obtenerIngresosTesoreria: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un ingreso de TESORERÍA ya fue marcado como recibido
     */
    private function verificarRecibidoTesoreria($idSba05) {
        try {
            // Convertir a INT para la comparación
            $idSba05Int = (int)$idSba05;
            
            $sql = "SELECT COUNT(*) as count FROM ingresos WHERE ID_SBA05 = ? AND origen = 'TESORERIA'";
            $stmt = sqlsrv_query($this->db, $sql, [$idSba05Int]);
            
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
     * Obtiene ingresos combinados de todas las fuentes
     */
    public function obtenerIngresosCombinados($desde, $hasta) {
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
        
        // 2. Ingresos desde TESORERÍA (SBA05)
        $ingresosTesoreria = $this->obtenerIngresosTesoreria($desde, $hasta);
        $resultados = array_merge($resultados, $ingresosTesoreria);
        
        // 3. Ingresos desde 599 (sj_administracion_cobros)
        $ingresos599 = $this->obtenerIngresos599($desde, $hasta);
        $resultados = array_merge($resultados, $ingresos599);
        
        // Ordenar por fecha descendente
        usort($resultados, function($a, $b) {
            $fechaA = is_object($a['fecha']) ? $a['fecha']->format('Y-m-d') : $a['fecha'];
            $fechaB = is_object($b['fecha']) ? $b['fecha']->format('Y-m-d') : $b['fecha'];
            return strtotime($fechaB) - strtotime($fechaA);
        });
        
        return $resultados;
    }
    
    /**
     * Marca un ingreso TESORERÍA como recibido (lo inserta en tabla ingresos)
     */
    public function marcarRecibidoTesoreria($idSba05, $fecha, $codComp, $nComp, $observaciones, $importe) {
        try {
            // CRÍTICO: Convertir ID_SBA05 a entero (la columna en la BD es INT)
            // Para ingresos de tesorería, ID_SBA05 debe ser un número válido
            $idSba05Int = (int)$idSba05;
            
            // Validar que la conversión fue exitosa (debe ser un número positivo)
            if ($idSba05Int <= 0) {
                error_log("Error: ID_SBA05 inválido: '$idSba05' convertido a: $idSba05Int");
                throw new Exception("ID_SBA05 debe ser un número entero positivo para ingresos de TESORERÍA");
            }
            
            // Verificar si ya existe
            if ($this->verificarRecibidoTesoreria($idSba05Int)) {
                error_log("[INFO] Ingreso ID_SBA05 $idSba05Int ya está marcado como recibido");
                return true; // Ya existe, retornar éxito
            }
            
            // Los campos COD_COMP y N_COMP ya vienen separados correctamente desde obtenerIngresosTesoreria()
            // Ajustar longitudes según límites de la tabla
            $codCompAjustado = substr($codComp, 0, 10); // Máximo 10 caracteres para COD_COMP
            $nCompAjustado = substr($nComp, 0, 20);     // Máximo 20 caracteres para N_COMP
            
            error_log("[DEBUG marcarRecibidoTesoreria] ID_SBA05 original: '$idSba05', convertido a INT: $idSba05Int");
            error_log("[DEBUG marcarRecibidoTesoreria] COD_COMP: '$codCompAjustado', N_COMP: '$nCompAjustado'");
            
            $sql = "INSERT INTO ingresos (
                        ID_SBA05, COD_COMP, N_COMP, fecha, importe, 
                        observaciones, recibido, fecha_carga, origen
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, 1, GETDATE(), 'TESORERIA'
                    )";
            
            $params = [
                $idSba05Int, // Usar el valor convertido a INT
                $codCompAjustado,
                $nCompAjustado,
                $fecha,
                (float)$importe, // Asegurar que importe es float
                $observaciones
            ];
            
            error_log("[DEBUG marcarRecibidoTesoreria] SQL: " . $sql);
            error_log("[DEBUG marcarRecibidoTesoreria] Parámetros: " . print_r($params, true));
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("[ERROR marcarRecibidoTesoreria] SQL Error: " . print_r($errors, true));
                throw new Exception("Error al insertar ingreso TESORERÍA: " . print_r($errors, true));
            }
            
            sqlsrv_free_stmt($stmt);
            error_log("[DEBUG marcarRecibidoTesoreria] ✅ Inserción exitosa para ID_SBA05: $idSba05Int");
            return true;
        } catch (Exception $e) {
            error_log("Error en marcarRecibidoTesoreria: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca un ingreso como recibido
     */
    public function marcarRecibido($id) {
        try {
            error_log("[DEBUG marcarRecibido] Intentando marcar ingreso ID: $id como recibido");
            
            $sql = "UPDATE ingresos SET recibido = 1 WHERE id = ?";
            $params = [$id];
            
            error_log("[DEBUG marcarRecibido] SQL: $sql");
            error_log("[DEBUG marcarRecibido] Parámetros: " . print_r($params, true));
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("[ERROR marcarRecibido] Error SQL: " . print_r($errors, true));
                throw new Exception("Error en la consulta: " . print_r($errors, true));
            }
            
            // Verificar cuántas filas fueron afectadas
            $rowsAffected = sqlsrv_rows_affected($stmt);
            error_log("[DEBUG marcarRecibido] Filas afectadas: $rowsAffected");
            
            if ($rowsAffected === 0) {
                error_log("[WARNING marcarRecibido] No se actualizó ninguna fila. ¿El ID existe?");
            } else {
                error_log("[DEBUG marcarRecibido] ✅ Ingreso ID $id marcado como recibido exitosamente");
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
