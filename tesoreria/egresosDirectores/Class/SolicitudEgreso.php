<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Director.php';

/**
 * Clase SolicitudEgreso
 * Gestiona las solicitudes de egresos de directores
 */
class SolicitudEgreso {
    private $db;
    private $director;
    
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
     * @return string
     */
    private function generarIdSolicitud() {
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
     * @param array $datos
     * @return array
     */
    public function crear(array $datos) {
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
            
            // Preparar datos de proveedor (solo para compras personales)
            $nomProvee = null;
            $cbu = null;
            $descripcionCbu = null;
            
            if ($datos['motivo'] === self::MOTIVO_COMPRA_PERSONAL) {
                // Si se seleccionó "MANUAL", guardar NULL en nom_provee
                $nomProvee = isset($datos['nom_provee']) && $datos['nom_provee'] !== 'MANUAL' 
                    ? $datos['nom_provee'] 
                    : null;
                
                $cbu = isset($datos['cbu']) && !empty($datos['cbu']) 
                    ? $datos['cbu'] 
                    : null;
                
                $descripcionCbu = isset($datos['descripcion_cbu']) && !empty($datos['descripcion_cbu']) 
                    ? $datos['descripcion_cbu'] 
                    : null;
            }
            
            // Insertar solicitud
            $sql = "INSERT INTO solicitudes_egresos (
                        id_solicitud, id_director, motivo, importe, 
                        estado, observaciones, fecha_solicitud,
                        NOM_PROVEE, CBU, DESCRIPCION_CBU
                    ) VALUES (?, ?, ?, ?, ?, ?, GETDATE(), ?, ?, ?)";
            
            $params = [
                $idSolicitud,
                (int)$datos['id_director'],
                $datos['motivo'],
                $importe,
                $estadoInicial,
                $datos['observaciones'] ?? '',
                $nomProvee,
                $cbu,
                $descripcionCbu
            ];
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al crear solicitud: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            
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
     * @param array $filtros
     * @return array
     */
    public function obtenerTodas(array $filtros = []) {
        try {
            // Consultar directamente las tablas
            // Los campos NOM_PROVEE, CBU, DESCRIPCION_CBU ya están en solicitudes_egresos
            // No necesitamos JOIN con CPA01 para consultas normales
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
                        ISNULL(s.NOM_PROVEE, '') as NOM_PROVEE,
                        ISNULL(s.CBU, '') as CBU,
                        ISNULL(s.DESCRIPCION_CBU, '') as DESCRIPCION_CBU,
                        (SELECT COUNT(*) FROM archivos_solicitud WHERE id_solicitud = s.id_solicitud) as cantidad_archivos
                    FROM solicitudes_egresos s
                    INNER JOIN RO_T_DIRECTORES d ON s.id_director = d.ID_DIRECTOR
                    WHERE 1=1";
            
            $params = [];
            
            error_log("DEBUG obtenerTodas - Filtros recibidos: " . print_r($filtros, true));
            
            if (isset($filtros['id_director']) && $filtros['id_director'] > 0) {
                $sql .= " AND s.id_director = ?";
                $params[] = (int)$filtros['id_director'];
                error_log("DEBUG obtenerTodas - Agregando filtro id_director: " . $filtros['id_director']);
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
     * @param string $idSolicitud
     * @return array|null
     */
    public function obtenerPorId(string $idSolicitud) {
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
                        ISNULL(s.NOM_PROVEE, '') as NOM_PROVEE,
                        ISNULL(s.CBU, '') as CBU,
                        ISNULL(s.DESCRIPCION_CBU, '') as DESCRIPCION_CBU,
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
     * @param string $idSolicitud
     * @param string $nuevoEstado
     * @param string $usuario
     * @param string $observaciones
     * @return bool
     */
    public function actualizarEstado(string $idSolicitud, string $nuevoEstado, string $usuario = 'SISTEMA', string $observaciones = '') {
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
            
            return true;
        } catch (Exception $e) {
            error_log("Error al actualizar estado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza las observaciones de proveedores
     * @param string $idSolicitud
     * @param string $observaciones
     * @return bool
     */
    public function actualizarObservacionesProveedores(string $idSolicitud, string $observaciones) {
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
     * @param string $idSolicitud
     * @param string $observaciones
     * @return bool
     */
    public function actualizarObservacionesTesoreria(string $idSolicitud, string $observaciones) {
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
     * Crea múltiples solicitudes de egreso (una por cada director en la distribución)
     * Utilizado para retiros de dinero con tipo de asignación múltiple
     * @param array $datos Datos base de la solicitud
     * @param array $distribucion Array con [id_director => importe, ...]
     * @return array
     */
    public function crearMultiple(array $datos, array $distribucion) {
        try {
            // Validar que sea retiro de dinero
            if ($datos['motivo'] !== self::MOTIVO_RETIRO_DINERO) {
                throw new Exception("La creación múltiple solo aplica para retiros de dinero");
            }
            
            // Validar que haya distribución
            if (empty($distribucion) || !is_array($distribucion)) {
                throw new Exception("Debe proporcionar una distribución válida");
            }
            
            // Validar que la suma de importes coincida con el importe total
            $importeTotal = (float)$datos['importe'];
            $sumaDistribucion = 0;
            
            foreach ($distribucion as $item) {
                if (!isset($item['id_director']) || !isset($item['importe'])) {
                    throw new Exception("Formato de distribución inválido");
                }
                $sumaDistribucion += (float)$item['importe'];
            }
            
            if (abs($importeTotal - $sumaDistribucion) > 0.01) {
                throw new Exception("La suma de la distribución ($sumaDistribucion) no coincide con el importe total ($importeTotal)");
            }
            
            // Generar un ID base para agrupar las solicitudes
            $idBase = $this->generarIdSolicitud();
            $observacionesBase = $datos['observaciones'] ?? '';
            $idsCreados = [];
            $errores = [];
            
            // Crear una solicitud por cada director
            foreach ($distribucion as $index => $item) {
                try {
                    $idDirector = (int)$item['id_director'];
                    $importe = (float)$item['importe'];
                    
                    // Saltar si el importe es 0
                    if ($importe <= 0) {
                        continue;
                    }
                    
                    // Validar que el director exista
                    if (!$this->director->existeDirector($idDirector)) {
                        throw new Exception("Director con ID $idDirector no es válido");
                    }
                    
                    // Generar ID único para esta solicitud (basado en el ID base + índice)
                    $idSolicitud = $idBase . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                    
                    // Las solicitudes de retiro múltiple inician en CARGADO
                    $estadoInicial = self::ESTADO_CARGADO;
                    
                    // Agregar referencia al grupo en observaciones
                    $observaciones = $observacionesBase;
                    if (!empty($observaciones)) {
                        $observaciones .= ' | ';
                    }
                    $observaciones .= "Retiro múltiple - Grupo: $idBase";
                    
                    // Insertar solicitud
                    $sql = "INSERT INTO solicitudes_egresos (
                                id_solicitud, id_director, motivo, importe, 
                                estado, observaciones, fecha_solicitud,
                                NOM_PROVEE, CBU, DESCRIPCION_CBU
                            ) VALUES (?, ?, ?, ?, ?, ?, GETDATE(), NULL, NULL, NULL)";
                    
                    $params = [
                        $idSolicitud,
                        $idDirector,
                        self::MOTIVO_RETIRO_DINERO,
                        $importe,
                        $estadoInicial,
                        $observaciones
                    ];
                    
                    $stmt = sqlsrv_query($this->db, $sql, $params);
                    
                    if ($stmt === false) {
                        throw new Exception("Error al crear solicitud: " . print_r(sqlsrv_errors(), true));
                    }
                    
                    sqlsrv_free_stmt($stmt);
                    
                    // Obtener nombre del director para el log
                    $nombreDirector = $this->director->obtenerNombrePorId($idDirector);
                    
                    $idsCreados[] = [
                        'id_solicitud' => $idSolicitud,
                        'id_director' => $idDirector,
                        'nombre_director' => $nombreDirector,
                        'importe' => $importe
                    ];
                    
                    error_log("Solicitud múltiple creada: $idSolicitud - Director: $nombreDirector - Importe: $importe");
                    
                } catch (Exception $e) {
                    $errores[] = "Director ID $idDirector: " . $e->getMessage();
                    error_log("Error al crear solicitud para director $idDirector: " . $e->getMessage());
                }
            }
            
            // Verificar si se crearon solicitudes
            if (empty($idsCreados)) {
                throw new Exception("No se pudo crear ninguna solicitud. Errores: " . implode('; ', $errores));
            }
            
            $resultado = [
                'success' => true,
                'id_base' => $idBase,
                'solicitudes_creadas' => count($idsCreados),
                'detalles' => $idsCreados,
                'message' => count($idsCreados) . ' solicitudes creadas correctamente'
            ];
            
            // Agregar advertencias si hubo errores parciales
            if (!empty($errores)) {
                $resultado['advertencias'] = $errores;
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Error en crearMultiple: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}