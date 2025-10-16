<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Director.php';

/**
 * Clase SolicitudEgreso
 * Gestiona las solicitudes de egresos de directores
 */
class SolicitudEgreso {
    private $db;
    private Director $director;
    
    // Constantes de motivos
    public const MOTIVO_COMPRA_PERSONAL = 'COMPRA_PERSONAL';
    public const MOTIVO_RETIRO_DINERO = 'RETIRO_DINERO';
    
    // Constantes de estados
    public const ESTADO_SOLICITADO = 'SOLICITADO';
    public const ESTADO_CARGADO = 'CARGADO';
    public const ESTADO_PAGADO = 'PAGADO';
    
    public function __construct() {
        $this->db = Database::getInstance()->getAppsConnection();
        $this->director = new Director();
    }
    
    /**
     * Genera ID único de solicitud usando procedimiento almacenado
     */
    private function generarIdSolicitud(): string {
        try {
            $sql = "{CALL sp_generar_id_solicitud(?)}";
            $params = [
                ['', SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(50)]
            ];
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al generar ID: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return $params[0][0];
        } catch (Exception $e) {
            error_log("Error en generarIdSolicitud: " . $e->getMessage());
            // Fallback manual si falla el procedimiento
            $fecha = date('Ymd');
            $contador = rand(1, 999999);
            return 'SOL-' . $fecha . '-' . str_pad($contador, 6, '0', STR_PAD_LEFT);
        }
    }
    
    /**
     * Crea una nueva solicitud de egreso
     */
    public function crear(array $datos): array {
        try {
            // Validar director
            if (empty($datos['id_director'])) {
                throw new Exception("ID de director requerido");
            }
            
            if (!$this->director->existeDirector((int)$datos['id_director'])) {
                throw new Exception("Director no válido");
            }
            
            // Validar motivo
            $motivosValidos = [self::MOTIVO_COMPRA_PERSONAL, self::MOTIVO_RETIRO_DINERO];
            if (!in_array($datos['motivo'], $motivosValidos)) {
                throw new Exception("Motivo no válido");
            }
            
            // Validar importe
            $importe = (float)$datos['importe'];
            if ($importe <= 0) {
                throw new Exception("El importe debe ser mayor a cero");
            }
            
            // Si es compra personal, validar que haya factura
            if ($datos['motivo'] === self::MOTIVO_COMPRA_PERSONAL && empty($datos['archivos'])) {
                throw new Exception("Las compras personales requieren adjuntar la factura");
            }
            
            // Generar ID único
            $idSolicitud = $this->generarIdSolicitud();
            
            // Determinar estado inicial según el motivo
            // - COMPRA_PERSONAL: inicia en SOLICITADO (necesita orden de compra)
            // - RETIRO_DINERO: inicia en CARGADO (va directo a tesorería)
            $estadoInicial = ($datos['motivo'] === self::MOTIVO_RETIRO_DINERO) 
                ? self::ESTADO_CARGADO 
                : self::ESTADO_SOLICITADO;
            
            // Insertar solicitud
            $sql = "INSERT INTO solicitudes_egresos (
                        id_solicitud, id_director, motivo, importe, 
                        estado, observaciones, fecha_solicitud
                    ) VALUES (?, ?, ?, ?, ?, ?, GETDATE())";
            
            $params = [
                $idSolicitud,
                (int)$datos['id_director'],
                $datos['motivo'],
                $importe,
                $estadoInicial,
                $datos['observaciones'] ?? ''
            ];
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al crear solicitud: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            
            // Registrar en historial con el usuario apropiado
            $usuarioCreacion = 'DIRECTORES';
            $observacionCreacion = ($datos['motivo'] === self::MOTIVO_RETIRO_DINERO) 
                ? 'Solicitud de retiro de dinero creada y lista para pago'
                : 'Solicitud de compra personal creada';
            
            $this->registrarHistorial($idSolicitud, null, $estadoInicial, $observacionCreacion, $usuarioCreacion);
            
            return [
                'success' => true,
                'id_solicitud' => $idSolicitud,
                'message' => 'Solicitud creada correctamente'
            ];
        } catch (Exception $e) {
            error_log("Error al crear solicitud: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene todas las solicitudes con filtros opcionales
     */
    public function obtenerTodas(array $filtros = []): array {
        try {
            // Consultar directamente las tablas en lugar de la vista
            $sql = "SELECT 
                        s.id_solicitud,
                        s.id_director,
                        d.NOMBRE as nombre_director,
                        s.motivo,
                        s.importe,
                        s.estado,
                        s.observaciones,
                        ISNULL(s.observaciones_proveedores, '') as observaciones_proveedores,
                        ISNULL(s.observaciones_tesoreria, '') as observaciones_tesoreria,
                        s.fecha_solicitud,
                        s.fecha_modificacion,
                        s.usuario_modificacion,
                        (SELECT COUNT(*) FROM archivos_solicitud WHERE id_solicitud = s.id_solicitud) as cantidad_archivos
                    FROM solicitudes_egresos s
                    INNER JOIN RO_T_DIRECTORES d ON s.id_director = d.ID_DIRECTOR
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($filtros['id_director'])) {
                $sql .= " AND s.id_director = ?";
                $params[] = $filtros['id_director'];
            }
            
            if (!empty($filtros['nombre_director'])) {
                $sql .= " AND d.NOMBRE LIKE ?";
                $params[] = '%' . $filtros['nombre_director'] . '%';
            }
            
            if (!empty($filtros['estado'])) {
                $sql .= " AND s.estado = ?";
                $params[] = $filtros['estado'];
            }
            
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND CAST(s.fecha_solicitud AS DATE) >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND CAST(s.fecha_solicitud AS DATE) <= ?";
                $params[] = $filtros['fecha_hasta'];
            }
            
            $sql .= " ORDER BY s.fecha_solicitud DESC";
            
            error_log("SQL Query: " . $sql);
            error_log("SQL Params: " . print_r($params, true));
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en obtenerTodas: " . print_r($errors, true));
                throw new Exception("Error en consulta: " . print_r($errors, true));
            }
            
            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir DateTime a string para JSON
                if (is_object($row['fecha_solicitud'])) {
                    $row['fecha_solicitud'] = $row['fecha_solicitud']->format('Y-m-d H:i:s');
                }
                if (isset($row['fecha_modificacion']) && is_object($row['fecha_modificacion'])) {
                    $row['fecha_modificacion'] = $row['fecha_modificacion']->format('Y-m-d H:i:s');
                }
                $resultados[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            error_log("Solicitudes encontradas: " . count($resultados));
            
            return $resultados;
        } catch (Exception $e) {
            error_log("Error al obtener solicitudes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene una solicitud específica por ID
     */
    public function obtenerPorId(string $idSolicitud): ?array {
        try {
            $sql = "SELECT 
                        s.id_solicitud,
                        s.id_director,
                        d.NOMBRE as nombre_director,
                        s.motivo,
                        s.importe,
                        s.estado,
                        s.observaciones,
                        ISNULL(s.observaciones_proveedores, '') as observaciones_proveedores,
                        ISNULL(s.observaciones_tesoreria, '') as observaciones_tesoreria,
                        s.fecha_solicitud,
                        s.fecha_modificacion,
                        s.usuario_modificacion,
                        (SELECT COUNT(*) FROM archivos_solicitud WHERE id_solicitud = s.id_solicitud) as cantidad_archivos
                    FROM solicitudes_egresos s
                    INNER JOIN RO_T_DIRECTORES d ON s.id_director = d.ID_DIRECTOR
                    WHERE s.id_solicitud = ?";
            
            $stmt = sqlsrv_query($this->db, $sql, [$idSolicitud]);
            
            if ($stmt === false) {
                error_log("Error en obtenerPorId: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if ($row) {
                // Convertir fechas
                if (is_object($row['fecha_solicitud'])) {
                    $row['fecha_solicitud'] = $row['fecha_solicitud']->format('Y-m-d H:i:s');
                }
                if (isset($row['fecha_modificacion']) && is_object($row['fecha_modificacion'])) {
                    $row['fecha_modificacion'] = $row['fecha_modificacion']->format('Y-m-d H:i:s');
                }
            }
            
            return $row;
        } catch (Exception $e) {
            error_log("Error al obtener solicitud: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Actualiza el estado de una solicitud
     */
    public function actualizarEstado(string $idSolicitud, string $nuevoEstado, string $usuario = 'SISTEMA', string $observaciones = ''): bool {
        try {
            // Validar estado
            $estadosValidos = [self::ESTADO_SOLICITADO, self::ESTADO_CARGADO, self::ESTADO_PAGADO];
            if (!in_array($nuevoEstado, $estadosValidos)) {
                throw new Exception("Estado no válido");
            }
            
            // Obtener estado actual
            $solicitudActual = $this->obtenerPorId($idSolicitud);
            if (!$solicitudActual) {
                throw new Exception("Solicitud no encontrada");
            }
            
            $estadoAnterior = $solicitudActual['estado'];
            
            // Actualizar estado
            $sql = "UPDATE solicitudes_egresos 
                    SET estado = ?, 
                        fecha_modificacion = GETDATE(),
                        usuario_modificacion = ?
                    WHERE id_solicitud = ?";
            
            $stmt = sqlsrv_query($this->db, $sql, [$nuevoEstado, $usuario, $idSolicitud]);
            
            if ($stmt === false) {
                throw new Exception("Error al actualizar estado: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            
            // Registrar en historial (el trigger también lo hace, pero esto es explícito)
            $this->registrarHistorial($idSolicitud, $estadoAnterior, $nuevoEstado, $observaciones, $usuario);
            
            return true;
        } catch (Exception $e) {
            error_log("Error al actualizar estado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza las observaciones de proveedores
     */
    public function actualizarObservacionesProveedores(string $idSolicitud, string $observaciones): bool {
        try {
            $sql = "UPDATE solicitudes_egresos 
                    SET observaciones_proveedores = ?, 
                        fecha_modificacion = GETDATE(),
                        usuario_modificacion = 'PROVEEDORES'
                    WHERE id_solicitud = ?";
            
            $stmt = sqlsrv_query($this->db, $sql, [$observaciones, $idSolicitud]);
            
            if ($stmt === false) {
                throw new Exception("Error al actualizar observaciones de proveedores: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Error al actualizar observaciones de proveedores: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza las observaciones de tesorería
     */
    public function actualizarObservacionesTesoreria(string $idSolicitud, string $observaciones): bool {
        try {
            $sql = "UPDATE solicitudes_egresos 
                    SET observaciones_tesoreria = ?, 
                        fecha_modificacion = GETDATE(),
                        usuario_modificacion = 'TESORERIA'
                    WHERE id_solicitud = ?";
            
            $stmt = sqlsrv_query($this->db, $sql, [$observaciones, $idSolicitud]);
            
            if ($stmt === false) {
                throw new Exception("Error al actualizar observaciones de tesorería: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Error al actualizar observaciones de tesorería: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra un cambio en el historial
     */
    private function registrarHistorial(string $idSolicitud, ?string $estadoAnterior, string $estadoNuevo, string $observaciones = '', string $usuario = 'SISTEMA'): bool {
        try {
            $sql = "INSERT INTO historial_estados 
                        (id_solicitud, estado_anterior, estado_nuevo, usuario, observaciones)
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = sqlsrv_query($this->db, $sql, [
                $idSolicitud,
                $estadoAnterior,
                $estadoNuevo,
                $usuario,
                $observaciones
            ]);
            
            if ($stmt === false) {
                throw new Exception("Error al registrar historial: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Error en registrarHistorial: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el historial de cambios de una solicitud
     */
    public function obtenerHistorial(string $idSolicitud): array {
        try {
            $sql = "SELECT * FROM historial_estados 
                    WHERE id_solicitud = ? 
                    ORDER BY fecha_cambio DESC";
            
            $stmt = sqlsrv_query($this->db, $sql, [$idSolicitud]);
            
            if ($stmt === false) {
                return [];
            }
            
            $historial = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                if (is_object($row['fecha_cambio'])) {
                    $row['fecha_cambio'] = $row['fecha_cambio']->format('Y-m-d H:i:s');
                }
                $historial[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            return $historial;
        } catch (Exception $e) {
            error_log("Error al obtener historial: " . $e->getMessage());
            return [];
        }
    }
}