<?php
require_once __DIR__ . '/../../../class/conexion.php';
require_once __DIR__ . '/Config.php';

/**
 * Clase Ingreso
 * Gestiona las operaciones CRUD de ingresos de caja
 */
class Ingreso {
    private $db;
    private $dbCentral;
    private $conexion;
    private static $sharedConexion = null;
    
    public function __construct() {
        // Reutilizar instancia de conexión si existe
        if (self::$sharedConexion === null) {
            self::$sharedConexion = new Conexion();
        }
        $this->conexion = self::$sharedConexion;
        
        $this->db = $this->conexion->conectar('apps');
        $this->dbCentral = $this->conexion->conectar('central');
        
        if ($this->db === false) {
            throw new Exception("Error al conectar con la base de datos APPS");
        }
        
        if ($this->dbCentral === false) {
            throw new Exception("Error al conectar con la base de datos CENTRAL");
        }
    }
    
    /**
     * Obtiene la instancia de conexión compartida
     */
    public function getConexion() {
        return $this->conexion;
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
            $moneda = $datos['moneda'] ?? 'ARS';
            $importe = $moneda === 'USD' ? 0 : (float)($datos['importe'] ?? 0);
            $importeDolares = $moneda === 'USD' ? (float)($datos['importe'] ?? 0) : 0;

            $sql = "INSERT INTO ingresos (
                        ID_SBA05, COD_COMP, N_COMP, fecha, importe, importe_dolares,
                        observaciones, recibido, fecha_carga, origen, moneda
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, GETDATE(), ?, ?
                    )";

            $nComp = $this->generarNumeroComprobante();

            $params = [
                $datos['id_sba05'] ?? null,
                'ING',
                $nComp,
                $datos['fecha'],
                $importe,
                $importeDolares,
                $datos['observaciones'] ?? '',
                $datos['recibido'] ?? 0,
                $datos['origen'] ?? 'MANUAL',
                $moneda
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

            if (!empty($filtros['moneda'])) {
                $sql .= " AND moneda = ?";
                $params[] = $filtros['moneda'];
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
    public function obtenerIngresosTesoreria($desde, $hasta, $moneda = 'ARS') {
        try {
            // Aplicar filtro de fecha de inicio de la app
            $fechaInicioApp = Config::getFechaInicioApp();
            if (strtotime($desde) < strtotime($fechaInicioApp)) {
                $desde = $fechaInicioApp;
            }
            
            if ($moneda === 'USD') {
                $sql = "SELECT    
                            ID_SBA05,    
                            CAST(FECHA AS DATE) FECHA,    
                            COD_COMP,    
                            N_COMP,      
                            LEYENDA OBSERVACIONES,    
                            CAST(CANT_MONE AS FLOAT) MONTO
                        FROM SBA05    
                        WHERE COD_CTA = '100901'    
                          AND FECHA BETWEEN ? AND ?
                          AND D_H = 'D'
                        ORDER BY FECHA DESC";
            } else {
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
            }
            
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
                        'fecha_carga' => $row['FECHA'], // Usar la fecha como fecha_carga para ordenamiento
                        'COD_COMP' => $row['COD_COMP'], // Ya viene correctamente separado de SBA05
                        'N_COMP' => $row['N_COMP'],     // Ya viene correctamente separado de SBA05
                        'observaciones' => $row['OBSERVACIONES'],
                        'importe' => $row['MONTO'],
                        'recibido' => 0, // Siempre pendiente hasta marcar
                        'origen' => 'TESORERIA',
                        'moneda' => $moneda
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
            // Aplicar filtro de fecha de inicio de la app
            $fechaInicioApp = Config::getFechaInicioApp();
            if (strtotime($desde) < strtotime($fechaInicioApp)) {
                $desde = $fechaInicioApp;
            }

            $sql = "SELECT
                        CAST(fecha_cobro AS DATE) FECHA,
                        ID,
                        (UPPER(user_rinde) + ' - ' + nombre_cliente) OBSERVACIONES,
                        CAST(importe_efectivo AS FLOAT) MONTO,
                        CAST(importe_dolares AS FLOAT) IMPORTE_DOLARES,
                        CAST(cotizacion_dolar AS FLOAT) COTIZACION_DOLAR
                    FROM sj_administracion_cobros
                    WHERE importe_efectivo > 0
                      AND CAST(fecha_cobro AS DATE) BETWEEN ? AND ?
                      AND rendido = 1
                    ORDER BY fecha_cobro DESC";

            $params = [$desde, $hasta];
            $stmt = sqlsrv_query($this->dbCentral, $sql, $params);

            if ($stmt === false) {
                throw new Exception("Error en consulta TESORERIA: " . print_r(sqlsrv_errors(), true));
            }

            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultados[] = [
                    'id' => 'EXT_599_' . $row['ID'],
                    'ID_SBA05' => null,
                    'fecha' => $row['FECHA'],
                    'fecha_carga' => $row['FECHA'],
                    'COD_COMP' => '',
                    'N_COMP' => '',
                    'observaciones' => $row['OBSERVACIONES'],
                    'importe' => $row['MONTO'],
                    'importe_dolares' => $row['IMPORTE_DOLARES'] ?? 0,
                    'cotizacion_dolar' => $row['COTIZACION_DOLAR'] ?? 0,
                    'recibido' => 1,
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
     * Obtiene ingresos desde 599 con componente en dólares (importe_dolares > 0)
     */
    public function obtenerIngresos599Dolares($desde, $hasta) {
        try {
            $fechaInicioApp = Config::getFechaInicioApp();
            if (strtotime($desde) < strtotime($fechaInicioApp)) {
                $desde = $fechaInicioApp;
            }

            $sql = "SELECT
                        CAST(fecha_cobro AS DATE) FECHA,
                        ID,
                        (UPPER(user_rinde) + ' - ' + nombre_cliente) OBSERVACIONES,
                        CAST(importe_dolares AS FLOAT) MONTO_USD,
                        CAST(cotizacion_dolar AS FLOAT) COTIZACION
                    FROM sj_administracion_cobros
                    WHERE importe_dolares > 0
                      AND CAST(fecha_cobro AS DATE) BETWEEN ? AND ?
                      AND rendido = 1
                    ORDER BY fecha_cobro DESC";

            $params = [$desde, $hasta];
            $stmt = sqlsrv_query($this->dbCentral, $sql, $params);

            if ($stmt === false) {
                throw new Exception("Error en consulta 599 dólares: " . print_r(sqlsrv_errors(), true));
            }

            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultados[] = [
                    'id' => 'EXT_599_' . $row['ID'],
                    'ID_SBA05' => null,
                    'fecha' => $row['FECHA'],
                    'fecha_carga' => $row['FECHA'],
                    'COD_COMP' => '',
                    'N_COMP' => '',
                    'observaciones' => $row['OBSERVACIONES'],
                    'importe' => $row['MONTO_USD'],
                    'cotizacion_dolar' => $row['COTIZACION'] ?? 0,
                    'recibido' => 1,
                    'origen' => '599',
                    'moneda' => 'USD'
                ];
            }

            sqlsrv_free_stmt($stmt);
            return $resultados;
        } catch (Exception $e) {
            error_log("Error en obtenerIngresos599Dolares: " . $e->getMessage());
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
            
            // Verificar si ya existe el ID_SBA05 sin importar el origen
            $sql = "SELECT COUNT(*) as count FROM ingresos WHERE ID_SBA05 = ?";
            $stmt = sqlsrv_query($this->db, $sql, [$idSba05Int]);
            
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
    public function obtenerIngresosCombinados($desde, $hasta, $moneda = 'ARS') {
        // Aplicar filtro de fecha de inicio de la app
        $fechaInicioApp = Config::getFechaInicioApp();
        if (strtotime($desde) < strtotime($fechaInicioApp)) {
            $desde = $fechaInicioApp;
        }

        $resultados = [];

        if ($moneda === 'USD') {
            // Ingresos manuales en dólares
            $filtros = [
                'fecha_desde' => $desde,
                'fecha_hasta' => $hasta,
                'moneda' => 'USD'
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
                    'importe' => $manual['importe_dolares'], // exponer dólares como importe
                    'recibido' => $manual['recibido'],
                    'origen' => $manual['origen'] ?? 'MANUAL',
                    'fecha_carga' => $manual['fecha_carga'] ?? null,
                    'moneda' => 'USD'
                ];
            }

            // Cobros 599 en dólares
            $ingresos599 = $this->obtenerIngresos599Dolares($desde, $hasta);
            $resultados = array_merge($resultados, $ingresos599);

            // Ingresos de Tesorería en dólares (SBA05)
            $ingresosTesoreriaUSD = $this->obtenerIngresosTesoreria($desde, $hasta, 'USD');
            $resultados = array_merge($resultados, $ingresosTesoreriaUSD);
        } else {
            // 1. Ingresos MANUALES (desde tabla ingresos local) - solo ARS
            $filtros = [
                'fecha_desde' => $desde,
                'fecha_hasta' => $hasta,
                'moneda' => 'ARS'
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
        }

        // Ordenar por fecha_carga descendente (más reciente primero)
        usort($resultados, function($a, $b) {
            $fechaA = !empty($a['fecha_carga']) ? $a['fecha_carga'] : $a['fecha'];
            $fechaB = !empty($b['fecha_carga']) ? $b['fecha_carga'] : $b['fecha'];

            $timestampA = is_object($fechaA) ? strtotime($fechaA->format('Y-m-d H:i:s')) : strtotime($fechaA);
            $timestampB = is_object($fechaB) ? strtotime($fechaB->format('Y-m-d H:i:s')) : strtotime($fechaB);

            return $timestampB - $timestampA;
        });

        return $resultados;
    }
    
    /**
     * Marca un ingreso TESORERÍA como recibido (lo inserta en tabla ingresos)
     */
    public function marcarRecibidoTesoreria($idSba05, $fecha, $codComp, $nComp, $observaciones, $importe, $moneda = 'ARS') {
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
            
            $importeARS = $moneda === 'USD' ? 0 : (float)$importe;
            $importeUSD = $moneda === 'USD' ? (float)$importe : 0;
            
            $sql = "INSERT INTO ingresos (
                        ID_SBA05, COD_COMP, N_COMP, fecha, importe, importe_dolares,
                        observaciones, recibido, fecha_carga, origen, moneda
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, 1, GETDATE(), 'TESORERIA', ?
                    )";
            
            $params = [
                $idSba05Int, // Usar el valor convertido a INT
                $codCompAjustado,
                $nCompAjustado,
                $fecha,
                $importeARS,
                $importeUSD,
                $observaciones,
                $moneda
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
     * Obtiene el total de ingresos recibidos desde la fecha de inicio de la app
     */
    public function obtenerTotalRecibido(): float {
        try {
            // Aplicar filtro de fecha de inicio de la app
            $fechaInicioApp = Config::getFechaInicioApp();
            
            $sql = "SELECT COALESCE(SUM(importe), 0) as total 
                    FROM ingresos 
                    WHERE recibido = 1
                      AND fecha >= ?";
            $stmt = sqlsrv_query($this->db, $sql, [$fechaInicioApp]);
            
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
    
    /**
     * Obtiene el total de ingresos manuales recibidos en dólares desde la fecha de inicio de la app
     */
    public function obtenerTotalRecibidoDolares(): float {
        try {
            $fechaInicioApp = Config::getFechaInicioApp();

            $sql = "SELECT COALESCE(SUM(importe_dolares), 0) as total
                    FROM ingresos
                    WHERE recibido = 1
                      AND moneda = 'USD'
                      AND importe_dolares > 0
                      AND fecha >= ?";
            $stmt = sqlsrv_query($this->db, $sql, [$fechaInicioApp]);

            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);

            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total ingresos dólares: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtiene gastos (ingresos con COD_COMP='GAS') para Reporte Alberto
     */
    public function obtenerGastos($desde, $hasta) {
        try {
            $sql = "SELECT * FROM ingresos 
                    WHERE COD_COMP = 'GAS'
                      AND fecha BETWEEN ? AND ?
                    ORDER BY fecha DESC";
            
            $params = [$desde, $hasta];
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
            error_log("Error al obtener gastos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene el total de gastos (ingresos con COD_COMP='GAS') para Reporte Alberto
     */
    public function obtenerTotalGastos(): float {
        try {
            $sql = "SELECT COALESCE(SUM(importe), 0) as total 
                    FROM ingresos 
                    WHERE COD_COMP = 'GAS'";
            $stmt = sqlsrv_query($this->db, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total gastos: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtiene el total histórico de ingresos desde 599
     */
    public function obtenerTotal599(): float {
        try {
            $fechaInicioApp = Config::getFechaInicioApp();
            $sql = "SELECT COALESCE(SUM(CAST(importe_efectivo AS FLOAT)), 0) as total
                    FROM sj_administracion_cobros
                    WHERE importe_efectivo > 0
                      AND rendido = 1
                      AND CAST(fecha_cobro AS DATE) >= ?";

            $stmt = sqlsrv_query($this->dbCentral, $sql, [$fechaInicioApp]);

            if ($stmt === false) {
                throw new Exception("Error en consulta 599 total: " . print_r(sqlsrv_errors(), true));
            }

            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);

            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total 599: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtiene el total histórico en dólares desde 599 (importe_dolares)
     */
    public function obtenerTotal599Dolares(): float {
        try {
            $fechaInicioApp = Config::getFechaInicioApp();
            $sql = "SELECT COALESCE(SUM(CAST(importe_dolares AS FLOAT)), 0) as total
                    FROM sj_administracion_cobros
                    WHERE importe_dolares > 0
                      AND rendido = 1
                      AND CAST(fecha_cobro AS DATE) >= ?";

            $stmt = sqlsrv_query($this->dbCentral, $sql, [$fechaInicioApp]);

            if ($stmt === false) {
                throw new Exception("Error en consulta 599 dólares total: " . print_r(sqlsrv_errors(), true));
            }

            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);

            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total 599 dólares: " . $e->getMessage());
            return 0.0;
        }
    }
}
