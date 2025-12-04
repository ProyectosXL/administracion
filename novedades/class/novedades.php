<?php
/**
 * Clase para manejo de Novedades
 * /novedades/class/Novedades.php
 * Adaptada para SQL Server y estructura existente
 */

require_once 'Database.php';
require_once 'Usuario.php';

class Novedades {
    private $db;
    private $sucursalesCache = null;

    // Constantes para estados de novedades - STRINGS para compatibilidad con DB
    const ESTADO_ENVIADA = 'Enviada';
    const ESTADO_EN_REVISION = 'En Revisión';
    const ESTADO_APROBADA = 'Aprobada';
    const ESTADO_RECHAZADA = 'A Revisar';
    const ESTADO_PROCESADA = 'Procesada';

    public function __construct() {
        $this->db = Database::getInstance();
        
        // Inicializar configuración de usuario
        $this->inicializarConfiguracionUsuario();
        
        // Inicializar tablas necesarias
        $this->inicializarTablas();
    }
    
    /**
     * Inicializar configuración de usuario
     */
    private function inicializarConfiguracionUsuario() {
        // Incluir configuración temporal de usuario
        require_once __DIR__ . '/../config/usuario_config.php';
    }
    
    /**
     * Convertir fecha a formato d/m/Y manejando diferentes tipos de entrada
     */
    private function formatearFecha($fecha) {
        if (empty($fecha)) {
            return '';
        }
        
        // Si es un objeto DateTime
        if ($fecha instanceof DateTime) {
            return $fecha->format('d/m/Y');
        }
        
        // Si es un array con propiedad 'date' (formato SQL Server)
        if (is_array($fecha) && isset($fecha['date'])) {
            $fechaStr = $fecha['date'];
            if ($fechaStr instanceof DateTime) {
                return $fechaStr->format('d/m/Y');
            }
            return date('d/m/Y', strtotime($fechaStr));
        }
        
        // Si es un string
        if (is_string($fecha)) {
            return date('d/m/Y', strtotime($fecha));
        }
        
        return '';
    }
    
    /**
     * Abreviar nombre de centro de costos
     * Elimina el contenido entre paréntesis excepto en casos especiales
     * Ejemplos:
     * - "PASEO ALDREY (LOC033)" -> "PASEO ALDREY"
     * - "ADMINISTRACION (ADM)" -> "ADM"
     */
    private function abreviarCentroCostos($descripcion, $codigo) {
        // Si la descripción está vacía, retornar el código
        if (empty($descripcion)) {
            return !empty($codigo) ? $codigo : '';
        }
        
        // Detectar caso especial: ADMINISTRACION (ADM) -> solo ADM
        if (preg_match('/^ADMINISTRACION\s*\(([^)]+)\)$/i', $descripcion, $matches)) {
            return trim($matches[1]);
        }
        
        // Caso general: eliminar todo lo que está entre paréntesis
        $nombreAbreviado = preg_replace('/\s*\([^)]*\)\s*/', '', $descripcion);
        
        // Limpiar espacios adicionales
        $nombreAbreviado = trim($nombreAbreviado);
        
        return $nombreAbreviado;
    }
    
    /**
     * Inicializar todas las tablas necesarias
     */
    private function inicializarTablas() {
        try {
            $this->crearTablaTiposNovedad();
            $this->crearTablaPuestos();
            $this->crearTablaNovedades();
        } catch (Exception $e) {
            error_log("Error inicializando tablas: " . $e->getMessage());
        }
    }

    /**
     * Obtener periodo actual: MES actual del año actual
     * PERIODO = MES (1-12 del año actual)
     */
    public function getPeriodoActual() {
        $fechaActual = new DateTime();
        $mesActual = (int)$fechaActual->format('m');
        $anioActual = (int)$fechaActual->format('Y');

        // Fecha de inicio: primer día del mes actual
        $fechaInicio = new DateTime();
        $fechaInicio->setDate($anioActual, $mesActual, 1);

        // Fecha de fin: último día del mes actual
        $fechaFin = new DateTime();
        $fechaFin->setDate($anioActual, $mesActual + 1, 1);
        $fechaFin->modify('-1 day'); // Último día del mes actual

        return [
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_fin' => $fechaFin->format('Y-m-d'),
            'periodo_mes' => $mesActual,
            'periodo_anio' => $anioActual,
            'descripcion' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y')
        ];
    }    /**
     * Obtener todas las sucursales activas
     */
    public function getSucursales() {
        $sql = "SELECT NRO_SUCURSAL as numero, DESC_SUCURSAL as descripcion 
                FROM [LAKERBIS].locales_lakers.dbo.SUCURSALES_LAKERS 
                WHERE CANAL = 'PROPIOS' AND HABILITADO = 1
                ORDER BY DESC_SUCURSAL";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtener todas las sucursales incluyendo CASA CENTRAL
     */
    public function getSucursalesConCasaCentral() {
        // Obtener sucursales de la base de datos
        $sucursales = $this->getSucursales();
        
        // Agregar CASA CENTRAL al principio de la lista
        // Usar 1 como número para compatibilidad con columna INT
        $casaCentral = [
            'numero' => 1,
            'descripcion' => 'CASA CENTRAL'
        ];
        
        // Insertar al principio del array
        array_unshift($sucursales, $casaCentral);
        
        return $sucursales;
    }

    /**
     * Obtener información completa de un empleado
     */
    public function getEmpleadoInfo($legajo) {
        try {
            $sql = "SELECT 
                        e.NRO_LEGAJO as legajo,
                        e.NOMBRE as nombre,
                        e.APELLIDO as apellido,
                        e.COD_DEPARTAMENTO as codigo_centro_costos,
                        e.DESC_DEPARTAMENTO as descripcion_centro_costos
                    FROM [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL e
                    WHERE e.NRO_LEGAJO = ?";
            
            $result = $this->db->query($sql, [$legajo]);
            $empleado = $result->fetch();
            
            if (!$empleado) {
                throw new Exception("No se encontró empleado con legajo: $legajo");
            }
            
            // Obtener información de la sucursal
            try {
                $sucursalInfo = $this->getSucursalPorCentroCostos($empleado['codigo_centro_costos']);
                $empleado['sucursal_numero'] = $sucursalInfo['numero'] ?? null;
                $empleado['sucursal'] = $sucursalInfo['descripcion'] ?? 'No especificado';
            } catch (Exception $e) {
                // Si no se puede obtener la sucursal, continuar sin ella
                $empleado['sucursal_numero'] = null;
                $empleado['sucursal'] = 'No especificado';
            }
            
            return $empleado;
        } catch (Exception $e) {
            error_log("Error obteniendo información del empleado $legajo: " . $e->getMessage());
            throw new Exception("Error obteniendo información del empleado");
        }
    }

    /**
     * Obtener sucursal asociada a un centro de costos
     */
    public function getSucursalPorCentroCostos($codigoCentroCostos) {
        try {
            $sql = "SELECT DESC_SUCURSAL as descripcion 
                    FROM RO_V_SUCURSALES_CON_CC 
                    WHERE COD_DEPARTAMENTO = ?";
            
            $result = $this->db->query($sql, [$codigoCentroCostos]);
            $sucursal = $result->fetch();
            
            if ($sucursal) {
                return $sucursal['descripcion'];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("❌ Error obteniendo sucursal para centro de costos $codigoCentroCostos: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los centros de costos únicos de empleados activos
     */
    public function getCentrosCostos() {
        $sql = "SELECT DISTINCT COD_DEPARTAMENTO as codigo, DESC_DEPARTAMENTO as descripcion 
                FROM [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL 
                WHERE HABILITADO = 'S' 
                  AND COD_DEPARTAMENTO IS NOT NULL 
                  AND DESC_DEPARTAMENTO IS NOT NULL
                  AND COD_DEPARTAMENTO != ''
                  AND DESC_DEPARTAMENTO != ''
                ORDER BY DESC_DEPARTAMENTO";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Buscar empleados para Select2 (autocompletado)
     */
    public function buscarEmpleadosSelect2($termino = '', $limit = 10) {
        $sql = "SELECT TOP {$limit} 
                    NRO_LEGAJO as legajo, 
                    NOMBRE as nombre, 
                    APELLIDO as apellido,
                    COD_DEPARTAMENTO as cod_centro_costos,
                    DESC_DEPARTAMENTO as desc_centro_costos,
                    CONCAT(NOMBRE, ' ', APELLIDO, ' (', NRO_LEGAJO, ')') as texto_completo
                FROM [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL 
                WHERE 1=1 AND HABILITADO = 'S'";
        
        $params = [];
        
        if (!empty($termino)) {
            $sql .= " AND (
                        CONCAT(NOMBRE, ' ', APELLIDO) LIKE ? 
                        OR CAST(NRO_LEGAJO AS VARCHAR) LIKE ?
                        OR NOMBRE LIKE ?
                        OR APELLIDO LIKE ?
                      )";
            $terminoBusqueda = "%{$termino}%";
            $params = [$terminoBusqueda, $terminoBusqueda, $terminoBusqueda, $terminoBusqueda];
        }
        
        $sql .= " ORDER BY NOMBRE, APELLIDO";
        
        $stmt = $this->db->query($sql, $params);
        $empleados = $stmt->fetchAll();
        
        // Formatear para Select2
        $resultado = [];
        foreach ($empleados as $empleado) {
            $resultado[] = [
                'id' => $empleado['legajo'],
                'text' => $empleado['texto_completo'],
                'legajo' => $empleado['legajo'],
                'nombre' => $empleado['nombre'],
                'apellido' => $empleado['apellido'],
                'cod_centro_costos' => $empleado['cod_centro_costos'],
                'desc_centro_costos' => $empleado['desc_centro_costos']
            ];
        }
        
        return $resultado;
    }
    public function buscarEmpleado($legajo) {
        $sql = "SELECT NRO_LEGAJO as legajo, NOMBRE as nombre, APELLIDO as apellido, TAREA_HABITUAL as puesto_actual,
                       COD_DEPARTAMENTO as cod_centro_costos, DESC_DEPARTAMENTO as desc_centro_costos
                FROM [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL 
                WHERE NRO_LEGAJO = ? AND HABILITADO = 'S'";
        $stmt = $this->db->query($sql, [$legajo]);
        return $stmt->fetch();
    }

    /**
     * Obtener información de estado basado en el número de estado
     */
    public static function getEstadoInfo($estadoNumero) {
        $estados = [
            self::ESTADO_ENVIADA => [
                'id' => self::ESTADO_ENVIADA,
                'nombre' => 'Enviada',
                'clase' => 'bg-info',
                'texto_clase' => 'text-white'
            ],
            self::ESTADO_EN_REVISION => [
                'id' => self::ESTADO_EN_REVISION,
                'nombre' => 'En Revisión',
                'clase' => 'bg-warning',
                'texto_clase' => 'text-dark'
            ],
            self::ESTADO_APROBADA => [
                'id' => self::ESTADO_APROBADA,
                'nombre' => 'Aprobada',
                'clase' => 'bg-success',
                'texto_clase' => 'text-white'
            ],
            self::ESTADO_RECHAZADA => [
                'id' => self::ESTADO_RECHAZADA,
                'nombre' => 'A Revisar',
                'clase' => 'bg-danger',
                'texto_clase' => 'text-white'
            ],
            self::ESTADO_PROCESADA => [
                'id' => self::ESTADO_PROCESADA,
                'nombre' => 'Procesada',
                'clase' => 'bg-secondary',
                'texto_clase' => 'text-white'
            ]
        ];

        return $estados[$estadoNumero] ?? [
            'id' => $estadoNumero,
            'nombre' => 'Estado Desconocido',
            'clase' => 'bg-light',
            'texto_clase' => 'text-dark'
        ];
    }

    /**
     * Obtener todos los estados disponibles
     */
    public static function getAllEstados() {
        return [
            self::ESTADO_ENVIADA => self::getEstadoInfo(self::ESTADO_ENVIADA),
            self::ESTADO_EN_REVISION => self::getEstadoInfo(self::ESTADO_EN_REVISION),
            self::ESTADO_APROBADA => self::getEstadoInfo(self::ESTADO_APROBADA),
            self::ESTADO_RECHAZADA => self::getEstadoInfo(self::ESTADO_RECHAZADA),
            self::ESTADO_PROCESADA => self::getEstadoInfo(self::ESTADO_PROCESADA)
        ];
    }

    /**
     * Generar badge HTML para un estado específico
     */
    public static function getBadgeEstado($estadoNumero) {
        $estado = self::getEstadoInfo($estadoNumero);
        return "<span class=\"badge {$estado['clase']} {$estado['texto_clase']}\">{$estado['nombre']}</span>";
    }

    /**
     * Cambiar estado de una novedad
     */
    public function cambiarEstadoNovedad($novedadId, $nuevoEstado) {
        try {
            // Validar que el nuevo estado sea válido
            if (!in_array($nuevoEstado, [self::ESTADO_ENVIADA, self::ESTADO_EN_REVISION, self::ESTADO_APROBADA, self::ESTADO_RECHAZADA, self::ESTADO_PROCESADA])) {
                throw new Exception("Estado no válido: $nuevoEstado");
            }

            $sql = "UPDATE novedades SET estado = ? WHERE id = ?";
            $stmt = $this->db->query($sql, [$nuevoEstado, $novedadId]);
            
            // Retornar true siempre si no hay excepción
            return true;
        } catch (Exception $e) {
            error_log("Error cambiando estado de novedad: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener tipos de novedad activos - FILTRADOS DIRECTO DESDE BD
     */
    public function getTiposNovedad() {
        $tipoUsuario = Usuario::getTipoUsuario();
        
        // Si es usuario RRHH, obtiene TODOS los tipos activos
        if ($tipoUsuario == Usuario::TIPO_RRHH) {
            $sql = "SELECT DISTINCT id, codigo, descripcion, cierre, corte
                    FROM tipos_novedad 
                    WHERE activo = 1 
                    GROUP BY id, codigo, descripcion, cierre, corte
                    ORDER BY id";
        } else {
            // Para otros tipos de usuario, aplicar filtros dinámicamente
            $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
            
            $sql = "SELECT DISTINCT id, codigo, descripcion, cierre, corte
                    FROM tipos_novedad 
                    WHERE activo = 1 AND $campoPermiso = 1 
                    GROUP BY id, codigo, descripcion, cierre, corte
                    ORDER BY id";
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtener puestos disponibles
     */
    public function getPuestosDisponibles() {
        $sql = "SELECT * FROM puestos_disponibles WHERE activo = 1 ORDER BY nombre_puesto";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Buscar puestos desde TAREA_HABITUAL para Select2
     */
    public function buscarPuestosSelect2($termino = '', $limit = 20) {
        $sql = "SELECT DISTINCT TOP {$limit} 
                    TAREA_HABITUAL as puesto
                FROM [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL 
                WHERE HABILITADO = 'S' 
                AND TAREA_HABITUAL IS NOT NULL 
                AND LTRIM(RTRIM(TAREA_HABITUAL)) != ''";
        
        $params = [];
        
        if (!empty($termino)) {
            $sql .= " AND TAREA_HABITUAL LIKE ?";
            $params = ["%{$termino}%"];
        }
        
        $sql .= " ORDER BY TAREA_HABITUAL";
        
        $stmt = $this->db->query($sql, $params);
        $puestos = $stmt->fetchAll();
        
        // Formatear para Select2
        $resultado = [];
        foreach ($puestos as $puesto) {
            if (!empty(trim($puesto['puesto']))) {
                $resultado[] = [
                    'id' => trim($puesto['puesto']),
                    'text' => trim($puesto['puesto'])
                ];
            }
        }
        
        return $resultado;
    }

    /**
     * Crear tabla tipos_novedad si no existe - SIMPLIFICADA
     */
    private function crearTablaTiposNovedad() {
        $sql = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='tipos_novedad' AND xtype='U')
                CREATE TABLE tipos_novedad (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    codigo VARCHAR(20) UNIQUE NOT NULL,
                    descripcion VARCHAR(100) NOT NULL,
                    activo BIT DEFAULT 1,
                    fecha_creacion DATETIME DEFAULT GETDATE(),
                    user_adm BIT DEFAULT 0,
                    user_com BIT DEFAULT 0,
                    user_prod BIT DEFAULT 0,
                    user_rrhh BIT DEFAULT 1
                )";
        
        $this->db->query($sql);
        
        // Verificar si existe la columna user_rrhh, si no existe, agregarla
        $this->agregarColumnaUserRRHH();
        
        // Si la tabla está vacía, insertar tipos básicos
        $checkSql = "SELECT COUNT(*) as count FROM tipos_novedad";
        $result = $this->db->query($checkSql);
        $row = $result->fetch();
        
        if ($row['count'] == 0) {
            $this->insertarTiposNovedad();
        } else {
            // Verificar si faltan tipos nuevos y agregarlos
            $this->verificarYAgregarTiposFaltantes();
        }
    }
    
    /**
     * Verificar y agregar tipos de novedad faltantes
     */
    private function verificarYAgregarTiposFaltantes() {
        // Tipos que podrían faltar (IDs 12-18)
        $tiposFaltantes = [
            ['PLUS_CAJA', 'Plus de caja', 1, 1, 0, 1],
            ['PREMIO_LOCAL', 'Premio Local', 1, 1, 0, 1],
            ['COMISION_INDIVIDUAL', 'Comisión Individual', 1, 1, 0, 1],
            ['COMISION_LOCAL', 'Comisión sobre Local', 1, 1, 0, 1]
        ];

        foreach ($tiposFaltantes as $tipo) {
            // Verificar si el tipo ya existe
            $checkSql = "SELECT COUNT(*) as count FROM tipos_novedad WHERE codigo = ?";
            $result = $this->db->query($checkSql, [$tipo[0]]);
            $row = $result->fetch();

            if ($row['count'] == 0) {
                // Insertar el tipo faltante
                $sql = "INSERT INTO tipos_novedad (codigo, descripcion, user_adm, user_com, user_prod, user_rrhh) VALUES (?, ?, ?, ?, ?, ?)";
                $this->db->query($sql, $tipo);
                error_log("Tipo de novedad agregado: " . $tipo[1]);
            }
        }
    }
    private function agregarColumnaUserRRHH() {
        try {
            // Verificar si la columna ya existe
            $sql = "SELECT COUNT(*) as existe 
                    FROM sys.columns 
                    WHERE object_id = OBJECT_ID('tipos_novedad') 
                    AND name = 'user_rrhh'";
            
            $result = $this->db->query($sql);
            $row = $result->fetch();
            
            // Si no existe la columna, agregarla con valor por defecto 1 (puede ver todos)
            if ($row['existe'] == 0) {
                $sql = "ALTER TABLE tipos_novedad ADD user_rrhh BIT DEFAULT 1";
                $this->db->query($sql);
                
                // Actualizar todos los registros existentes para que RRHH pueda verlos
                $sql = "UPDATE tipos_novedad SET user_rrhh = 1 WHERE user_rrhh IS NULL";
                $this->db->query($sql);
                
                error_log("Columna user_rrhh agregada exitosamente a tipos_novedad");
            }
        } catch (Exception $e) {
            error_log("Error agregando columna user_rrhh: " . $e->getMessage());
        }
    }
    
    /**
     * Método público para actualizar permisos RRHH en todos los tipos existentes
     * Útil para ejecutar manualmente si es necesario
     */
    public function actualizarPermisosRRHH() {
        try {
            // Primero verificar/agregar la columna
            $this->agregarColumnaUserRRHH();
            
            // Luego asegurar que todos los tipos tengan permiso para RRHH
            $sql = "UPDATE tipos_novedad SET user_rrhh = 1 WHERE user_rrhh IS NULL OR user_rrhh = 0";
            $result = $this->db->query($sql);
            
            // Verificar cuántos registros se actualizaron
            $sql = "SELECT COUNT(*) as total FROM tipos_novedad WHERE user_rrhh = 1";
            $result = $this->db->query($sql);
            $row = $result->fetch();
            
            return [
                'success' => true,
                'message' => 'Permisos RRHH actualizados correctamente',
                'tipos_con_permiso' => $row['total']
            ];
        } catch (Exception $e) {
            error_log("Error actualizando permisos RRHH: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error actualizando permisos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear tabla puestos_disponibles si no existe
     */
    private function crearTablaPuestos() {
        $sql = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='puestos_disponibles' AND xtype='U')
                CREATE TABLE puestos_disponibles (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    nombre_puesto VARCHAR(100) NOT NULL,
                    activo BIT DEFAULT 1,
                    fecha_creacion DATETIME DEFAULT GETDATE()
                )";
        
        $this->db->query($sql);
        
        // Insertar puestos si la tabla está vacía
        $checkSql = "SELECT COUNT(*) as count FROM puestos_disponibles";
        $result = $this->db->query($checkSql);
        $row = $result->fetch();
        
        if ($row['count'] == 0) {
            $this->insertarPuestos();
        }
    }

    /**
     * Crear tabla novedades si no existe - RESPETA ESTRUCTURA EXISTENTE
     */
    private function crearTablaNovedades() {
        // Solo verificar que existe, no crear porque ya existe con la estructura correcta
        $sql = "SELECT COUNT(*) as existe FROM sysobjects WHERE name='novedades' AND xtype='U'";
        $result = $this->db->query($sql);
        $row = $result->fetch();
        
        if ($row['existe'] == 0) {
            // Si no existe, crear con la estructura que esperamos
            $sql = "CREATE TABLE novedades (
                        id INT IDENTITY(1,1) PRIMARY KEY,
                        legajo INT NOT NULL,
                        nombre VARCHAR(50) NOT NULL,
                        apellido VARCHAR(50) NOT NULL,
                        centro_costos VARCHAR(50) NOT NULL,
                        fecha_vigencia DATE NULL,
                        puesto VARCHAR(50) NOT NULL,
                        valor_numerico DECIMAL(10,2) NULL,
                        fecha_permiso DATE NULL,
                        compensa BIT NULL,
                        tipo_permiso VARCHAR(20) NULL,
                        observaciones TEXT,
                        periodo_mes TINYINT NOT NULL,
                        periodo_anio SMALLINT NOT NULL,
                        tipo_novedad INT NULL,
                        estado TINYINT DEFAULT 1 NOT NULL,
                        fecha_creacion DATETIME DEFAULT GETDATE(),
                        fecha_modificacion DATETIME NULL
                    )";
            $this->db->query($sql);
        } else {
            // Si existe, verificar y agregar columna estado si no existe
            $this->agregarColumnaEstado();
            // Agregar columna centro_costos si no existe y migrar datos
            $this->migrarSucursalACentroCostos();
        }
    }

    /**
     * Agregar columna estado si no existe
     */
    private function agregarColumnaEstado() {
        try {
            // Verificar si la columna ya existe
            $sql = "SELECT COUNT(*) as existe 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_NAME = 'novedades' AND COLUMN_NAME = 'estado'";
            
            $result = $this->db->query($sql);
            $row = $result->fetch();
            
            // Si no existe la columna, agregarla con valor por defecto 1 (Enviada)
            if ($row['existe'] == 0) {
                $sql = "ALTER TABLE novedades ADD estado TINYINT DEFAULT 1 NOT NULL";
                $this->db->query($sql);
                
                // Actualizar registros existentes para que tengan estado 1 (Enviada)
                $sql = "UPDATE novedades SET estado = 1 WHERE estado IS NULL";
                $this->db->query($sql);
                
                error_log("Columna 'estado' agregada exitosamente a la tabla novedades");
            }
        } catch (Exception $e) {
            error_log("Error agregando columna estado: " . $e->getMessage());
        }
    }

    /**
     * Migrar de sucursal a centro de costos
     */
    private function migrarSucursalACentroCostos() {
        try {
            // Verificar si ya existe la columna centro_costos
            $sql = "SELECT COUNT(*) as existe 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_NAME = 'novedades' AND COLUMN_NAME = 'centro_costos'";
            
            $result = $this->db->query($sql);
            $row = $result->fetch();
            
            // Si no existe la columna centro_costos, agregarla
            if ($row['existe'] == 0) {
                $sql = "ALTER TABLE novedades ADD centro_costos VARCHAR(10) NULL";
                $this->db->query($sql);
                
                // Migrar datos existentes de sucursal a centro_costos
                // Obtener el centro de costos de cada empleado por su legajo
                $sql = "UPDATE n 
                        SET n.centro_costos = ISNULL(e.COD_DEPARTAMENTO, '0')
                        FROM novedades n
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL e 
                            ON n.legajo = e.NRO_LEGAJO
                        WHERE n.centro_costos IS NULL";
                $this->db->query($sql);
                
                // Hacer que la columna centro_costos sea NOT NULL después de la migración
                $sql = "ALTER TABLE novedades ALTER COLUMN centro_costos VARCHAR(10) NOT NULL";
                $this->db->query($sql);
                
                error_log("Migración de sucursal a centro_costos completada exitosamente");
            }
        } catch (Exception $e) {
            error_log("Error en migración de sucursal a centro de costos: " . $e->getMessage());
        }
    }

    /**
     * Insertar tipos de novedad con permisos - SEGÚN TU SQL
     */
    private function insertarTiposNovedad() {
        // Tipos con permisos exactos según tu SQL ejecutado
        // Formato: [codigo, descripcion, user_adm, user_com, user_prod, user_rrhh]
        // user_rrhh siempre será 1 (puede ver todos los tipos)
        $tipos = [
            ['CAMBIO_SUCURSAL', 'Cambio de sucursal', 1, 1, 0, 1],
            ['NUEVO_PUESTO', 'Nueva Posición', 1, 1, 0, 1],
            ['NUEVO_SALARIO', 'Nuevo salario neto', 1, 0, 0, 1],
            ['AJUSTE_PREMIOS', 'Ajuste de premios', 1, 1, 0, 1],
            ['HORAS_EXTRAS', 'Horas extras', 1, 1, 1, 1],
            ['HORAS_ADICIONALES', 'Horas adicionales', 1, 1, 1, 1],
            ['PERMISOS', 'Permisos', 1, 1, 1, 1],
            ['CORTES', 'Cortes', 1, 1, 1, 1],
            ['PRODUCCION_25', 'Producción 25%', 0, 0, 1, 1],
            ['PRODUCCION_50', 'Producción 50%', 0, 0, 1, 1],
            ['PRODUCCION_100', 'Producción 100%', 0, 0, 1, 1],
            ['PLUS_CAJA', 'Plus de caja', 1, 1, 0, 1],
            ['PLUS_SUB_ENCARGADA', 'Plus de Sub-Encargada', 1, 1, 0, 1],
            ['PLUS_ENCARGADA', 'Plus de Encargada', 1, 1, 0, 1],
            ['PREMIO_LOCAL', 'Premio Local', 1, 1, 0, 1],
            ['COMISION_INDIVIDUAL', 'Comisión Individual', 1, 1, 0, 1],
            ['COMISION_LOCAL', 'Comisión sobre Local', 1, 1, 0, 1],
            ['PREMIOS_AJUSTE_GENERAL', 'Premios - Ajuste General', 1, 1, 0, 1]
        ];

        foreach ($tipos as $tipo) {
            $sql = "INSERT INTO tipos_novedad (codigo, descripcion, user_adm, user_com, user_prod, user_rrhh) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, $tipo);
        }
    }

    /**
     * Insertar puestos disponibles
     */
    private function insertarPuestos() {
        $puestos = [
            'Vendedor', 'Vendedor Senior', 'Supervisor de Ventas', 'Cajero', 'Supervisor de Caja',
            'Gerente de Sucursal', 'Gerente Regional', 'Encargado de Depósito', 'Jefe de Depósito',
            'Asistente Administrativo', 'Analista de Ventas', 'Analista de RRHH', 'Gerente de RRHH',
            'Encargado de Local', 'Promotor', 'Coordinador de Promotores', 'Auxiliar de Limpieza',
            'Seguridad', 'Recepcionista', 'Contador', 'Asistente Contable', 'Jefe de Sistemas',
            'Analista de Sistemas', 'Chofer', 'Repositor'
        ];

        foreach ($puestos as $puesto) {
            $sql = "INSERT INTO puestos_disponibles (nombre_puesto) VALUES (?)";
            $this->db->query($sql, [$puesto]);
        }
    }

    /**
     * Crear nueva novedad - CORREGIDO PARA ESTRUCTURA REAL DE TABLA + VALIDACIÓN PERMISOS
     */
    public function crearNovedad($datos) {
        try {
            $this->db->beginTransaction();

            // Validar que el usuario puede crear este tipo de novedad
            $tipoNovedad = (int)$datos['tipo_novedad'];
            $this->validarPermisosTipoNovedad($tipoNovedad);

            // Obtener información del tipo de novedad para cálculo correcto de período
            $tipoNovedadInfo = $this->getTipoNovedadById($tipoNovedad);
            if (!$tipoNovedadInfo) {
                throw new Exception("Tipo de novedad no encontrado");
            }

            // Determinar el período a usar: personalizado o calculado automáticamente
            $periodo = null;
            
            // Si se proporciona período personalizado (desde el frontend), usarlo
            if (isset($datos['periodo_mes']) && isset($datos['periodo_anio'])) {
                $periodoMes = (int)$datos['periodo_mes'];
                $periodoAnio = (int)$datos['periodo_anio'];
                
                error_log("🔍 DEBUG PERÍODO: Datos recibidos - mes={$periodoMes}, año={$periodoAnio}");
                error_log("🔍 DEBUG PERÍODO: Datos originales - periodo_mes='{$datos['periodo_mes']}', periodo_anio='{$datos['periodo_anio']}'");
                
                // Validar que el período esté en un rango razonable
                if ($periodoMes >= 1 && $periodoMes <= 12 && $periodoAnio >= 2020 && $periodoAnio <= 2030) {
                    $periodo = [
                        'month' => $periodoMes,
                        'year' => $periodoAnio
                    ];
                    error_log("✅ Usando período personalizado CONFIRMADO: mes={$periodoMes}, año={$periodoAnio}");
                } else {
                    error_log("❌ Período personalizado inválido: mes={$periodoMes}, año={$periodoAnio} (fuera de rango válido)");
                }
            } else {
                error_log("⚠️ No se detectaron datos de período personalizado en el backend");
            }
            
            // Si no hay período personalizado válido, calcular según el tipo de novedad
            if (!$periodo) {
                // Incluir PeriodoUtils si no está ya incluido
                if (!class_exists('PeriodoUtils')) {
                    require_once __DIR__ . '/PeriodoUtils.php';
                }

                // Calcular período según el tipo de novedad (considerando "Período siguiente")
                $periodo = PeriodoUtils::calcularPeriodoSegunTipoCompleto($tipoNovedadInfo);
                error_log("Usando período calculado automáticamente según tipo de novedad");
            }
            
            // Log para debugging del período final
            error_log("Período final para tipo {$tipoNovedad}: " . print_r($periodo, true));
            error_log("Tipo novedad info: " . print_r($tipoNovedadInfo, true));
            
            // Adaptar datos según el tipo de novedad y estructura real de tabla
            $datosAdaptados = $this->adaptarDatosParaInsercion($datos);
            
            // Log para debugging - mostrar fechas procesadas
            error_log("Fechas procesadas: fecha_vigencia=" . var_export($datosAdaptados['fecha_vigencia'], true) . 
                     ", fecha_permiso=" . var_export($datosAdaptados['fecha_permiso'], true));
            
            $sql = "INSERT INTO novedades (
                        legajo, nombre, apellido, fecha_vigencia, puesto, 
                        valor_numerico, fecha_permiso, compensa, tipo_permiso,
                        observaciones, periodo_mes, periodo_anio, fecha_creacion, tipo_novedad, 
                        tipo_nuevo_puesto, estado, fecha_vigencia_hasta, centro_costos
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?, ?, ?, ?)";

            $params = [
                $datosAdaptados['legajo'],
                $datosAdaptados['nombre'],
                $datosAdaptados['apellido'],
                $datosAdaptados['fecha_vigencia'],
                $datosAdaptados['puesto'],
                $datosAdaptados['valor_numerico'],
                $datosAdaptados['fecha_permiso'],
                $datosAdaptados['compensa'],
                $datosAdaptados['tipo_permiso'],
                $datosAdaptados['observaciones'],
                $periodo['month'], // PeriodoUtils devuelve 'month', no 'periodo_mes'
                $periodo['year'],  // PeriodoUtils devuelve 'year', no 'periodo_anio'
                // fecha_creacion se inserta con GETDATE() directamente en SQL
                $datosAdaptados['tipo_novedad'],
                $datosAdaptados['tipo_nuevo_puesto'],
                self::ESTADO_ENVIADA, // Estado inicial por defecto: Enviada
                $datosAdaptados['fecha_vigencia_hasta'],
                $datosAdaptados['centro_costos']
            ];

            // Log para debugging - mostrar período usado
            error_log("Período utilizado: mes={$periodo['month']}, año={$periodo['year']}");
            error_log("Parámetros SQL: " . print_r($params, true));

            $novedadId = $this->db->insert($sql, $params);

            $this->db->commit();
            
            return ['success' => true, 'id' => $novedadId];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validar permisos DIRECTO desde BD
     */
    private function validarPermisosTipoNovedad($tipoNovedadId) {
        $tipoUsuario = Usuario::getTipoUsuario();
        
        // Si es usuario RRHH, tiene permisos para TODOS los tipos
        if ($tipoUsuario == Usuario::TIPO_RRHH) {
            $sql = "SELECT 1 as tiene_permiso, descripcion 
                    FROM tipos_novedad 
                    WHERE id = ? AND activo = 1";
            
            $stmt = $this->db->query($sql, [$tipoNovedadId]);
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception("Tipo de novedad no encontrado");
            }
            
            return true;
        }
        
        // Para otros tipos de usuario, validar permisos específicos
        $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
        
        $sql = "SELECT $campoPermiso as tiene_permiso, descripcion 
                FROM tipos_novedad 
                WHERE id = ? AND activo = 1";
        
        $stmt = $this->db->query($sql, [$tipoNovedadId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            throw new Exception("Tipo de novedad no encontrado");
        }
        
        if (!$result['tiene_permiso']) {
            throw new Exception("No tienes permisos para crear el tipo: " . $result['descripcion']);
        }
        
        return true;
    }

    /**
     * Obtener campo de permiso dinámicamente según tipo de usuario
     */
    private function getCampoPermisoUsuario($tipoUsuario) {
        $mapeoPermisos = [
            Usuario::TIPO_ADMIN => 'user_adm',
            Usuario::TIPO_COMERCIAL => 'user_com', 
            Usuario::TIPO_PRODUCCION => 'user_prod',
            Usuario::TIPO_RRHH => 'user_rrhh'
        ];
        
        if (!isset($mapeoPermisos[$tipoUsuario])) {
            throw new Exception("Tipo de usuario no válido: $tipoUsuario");
        }
        
        return $mapeoPermisos[$tipoUsuario];
    }

    /**
     * Adaptar datos según tipo de novedad a estructura real de tabla
     */
    private function adaptarDatosParaInsercion($datos) {
        $tipoNovedad = (int)$datos['tipo_novedad'];
        
        // Limpiar observaciones existentes de datos específicos para evitar duplicación
        $observacionesBase = isset($datos['observaciones']) ? $datos['observaciones'] : '';
        $observacionesLimpias = $this->limpiarObservacionesEspecificas($observacionesBase, $tipoNovedad);
        
        // Determinar centro_costos según el tipo de novedad
        $centroCostos = null;
        if ($tipoNovedad == 1) {
            // Para cambio de sucursal, usar sucursal_nueva_id del formulario (o nueva_sucursal como fallback)
            $centroCostos = isset($datos['sucursal_nueva_id']) ? $datos['sucursal_nueva_id'] : 
                           (isset($datos['nueva_sucursal']) ? $datos['nueva_sucursal'] : null);
            if (!$centroCostos) {
                throw new Exception("Para cambio de sucursal debe especificar la nueva sucursal");
            }
        } else {
            // Para otros tipos, obtener centro de costos del empleado
            $centroCostos = $this->obtenerCentroCostosEmpleado($datos['legajo']);
            if (!$centroCostos) {
                throw new Exception("No se pudo obtener el centro de costos del empleado con legajo: " . $datos['legajo']);
            }
        }
        
        $datosAdaptados = [
            'legajo' => $datos['legajo'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'centro_costos' => $centroCostos,
            'tipo_novedad' => $tipoNovedad,
            'observaciones' => $observacionesLimpias,
            'fecha_vigencia' => null,
            'fecha_vigencia_hasta' => null,
            'puesto' => '',
            'valor_numerico' => null,
            'fecha_permiso' => null,
            'compensa' => null,
            'tipo_permiso' => null,
            'tipo_nuevo_puesto' => null
        ];

        // Adaptar según el tipo específico
        switch ($tipoNovedad) {
            case 1: // Cambio de sucursal
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Cambio sucursal';
                
                // Usar sucursal_nueva_id o nueva_sucursal como fallback
                $nuevaSucursalId = isset($datos['sucursal_nueva_id']) ? $datos['sucursal_nueva_id'] : 
                                  (isset($datos['nueva_sucursal']) ? $datos['nueva_sucursal'] : null);
                
                if ($nuevaSucursalId) {
                    // Obtener el nombre de la sucursal en lugar del número
                    $mapaSucursales = $this->obtenerMapaSucursales();
                    $nombreSucursal = $mapaSucursales[$nuevaSucursalId] ?? 'Sucursal ' . $nuevaSucursalId;
                    $datosAdaptados['observaciones'] .= " - Nueva sucursal: " . $nombreSucursal;
                }
                break;

            case 2: // Nueva Posición
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                
                // Siempre permanente - no se permite temporario
                $datosAdaptados['tipo_nuevo_puesto'] = 'permanente';
                $datosAdaptados['fecha_vigencia_hasta'] = null;
                
                // Guardar la nueva posición en el campo puesto
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : '';
                
                // Agregar detalles en observaciones (sin tipo, siempre permanente)
                if (isset($datos['puesto'])) {
                    $detalleObservaciones = " - Nueva Posición: " . $datos['puesto'];
                    $datosAdaptados['observaciones'] .= $detalleObservaciones;
                }
                break;

            case 3: // Nuevo salario neto
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Ajuste Salario';
                break;

            case 4: // Ajuste de premios
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Premio';
                break;

            case 5: // Horas extras
                // NO calcular valores monetarios - solo guardar cantidad de horas
                if (isset($datos['cantidad_horas'])) {
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_horas']; // Solo la cantidad de horas
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_horas']} horas extras";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Horas Extras';
                break;

            case 6: // Horas adicionales
                // NO calcular valores monetarios - solo guardar cantidad de horas
                if (isset($datos['cantidad_horas'])) {
                    $datosAdaptados['valor_numerico'] = (float)$datos['cantidad_horas']; // Solo la cantidad de horas
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_horas']} horas adicionales";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Horas Adicionales';
                break;

            case 7: // Permisos
                $datosAdaptados['fecha_permiso'] = $this->formatearFechaParaSQL($datos['fecha_permiso'] ?? '');
                $datosAdaptados['compensa'] = isset($datos['compensa']) ? (bool)$datos['compensa'] : null;
                $datosAdaptados['puesto'] = 'Permiso';
                break;

            case 8: // Cortes
                // NO calcular valores monetarios - solo guardar cantidad de cortes
                if (isset($datos['cantidad_cortes'])) {
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_cortes']; // Solo la cantidad de cortes
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_cortes']} cortes";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Cortes';
                break;

            case 9: // Producción 25%
                // NO calcular valores monetarios - solo guardar cantidad de unidades
                if (isset($datos['cantidad_unidades'])) {
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_unidades']; // Solo la cantidad de unidades
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_unidades']} unidades (25%)";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 25%';
                break;

            case 10: // Producción 50%
                // NO calcular valores monetarios - solo guardar cantidad de unidades
                if (isset($datos['cantidad_unidades'])) {
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_unidades']; // Solo la cantidad de unidades
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_unidades']} unidades (50%)";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 50%';
                break;

            case 11: // Producción 100%
                // NO calcular valores monetarios - solo guardar cantidad de unidades
                if (isset($datos['cantidad_unidades'])) {
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_unidades']; // Solo la cantidad de unidades
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_unidades']} unidades (100%)";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 100%';
                break;

            default:
                $datosAdaptados['puesto'] = 'Novedad General';
                break;
        }

        return $datosAdaptados;
    }

    /**
     * Limpiar observaciones de datos específicos para evitar duplicación al editar
     */
    private function limpiarObservacionesEspecificas($observaciones, $tipoNovedad) {
        if (empty($observaciones)) {
            return '';
        }

        // Limpiar etiquetas HTML si las hay
        $observacionesLimpias = strip_tags($observaciones);

        switch ($tipoNovedad) {
            case 1: // Cambio de sucursal
                // Remover cualquier mención de "Nueva sucursal:"
                $observacionesLimpias = preg_replace('/ - Nueva sucursal: [^-]*/', '', $observacionesLimpias);
                break;

            case 2: // Nueva Posición
                // Remover cualquier mención de "Nueva Posición:" con todo su contenido
                // Patrón mejorado para capturar: 
                // - Nueva Posición: NOMBRE_PUESTO (Tipo) hasta DD/MM/YYYY
                // - Nueva Posición: NOMBRE_PUESTO (Tipo)
                // - Nueva Posición: NOMBRE_PUESTO
                $patterns = [
                    '/ - Nueva Posición: [^-]*?\([^)]*?\)\s*hasta\s*\d{2}\/\d{2}\/\d{4}/',  // Con tipo y fecha
                    '/ - Nueva Posición: [^-]*?\([^)]*?\)/',                                   // Con tipo sin fecha
                    '/ - Nueva Posición: [^-]*?(?=\s*-|\s*$)/',                               // Sin tipo, hasta siguiente - o final
                    // Mantener compatibilidad con formato anterior
                    '/ - Nuevo puesto: [^-]*?\([^)]*?\)\s*hasta\s*\d{2}\/\d{2}\/\d{4}/',
                    '/ - Nuevo puesto: [^-]*?\([^)]*?\)/',
                    '/ - Nuevo puesto: [^-]*?(?=\s*-|\s*$)/'
                ];
                
                foreach ($patterns as $pattern) {
                    $observacionesLimpias = preg_replace($pattern, '', $observacionesLimpias);
                }
                break;

            case 5: // Horas extras
                // Remover cualquier mención de horas extras
                $observacionesLimpias = preg_replace('/ - \d+ horas extras/', '', $observacionesLimpias);
                break;

            case 6: // Horas adicionales
                // Remover cualquier mención de horas adicionales
                $observacionesLimpias = preg_replace('/ - \d+ horas adicionales/', '', $observacionesLimpias);
                break;

            case 8: // Cortes
                // Remover cualquier mención de cortes
                $observacionesLimpias = preg_replace('/ - \d+ cortes/', '', $observacionesLimpias);
                break;

            case 9: // Producción 25%
                // Remover cualquier mención de unidades 25%
                $observacionesLimpias = preg_replace('/ - \d+ unidades \(25%\)/', '', $observacionesLimpias);
                break;

            case 10: // Producción 50%
                // Remover cualquier mención de unidades 50%
                $observacionesLimpias = preg_replace('/ - \d+ unidades \(50%\)/', '', $observacionesLimpias);
                break;

            case 11: // Producción 100%
                // Remover cualquier mención de unidades 100%
                $observacionesLimpias = preg_replace('/ - \d+ unidades \(100%\)/', '', $observacionesLimpias);
                break;
        }

        // Limpiar espacios múltiples y al final
        $observacionesLimpias = trim(preg_replace('/\s+/', ' ', $observacionesLimpias));

        return $observacionesLimpias;
    }

    /**
     * Obtener novedades del periodo actual - CON NOMBRES DE SUCURSAL REALES Y FILTRO POR TIPO DE USUARIO
     */
    public function getNovedadesPeriodoActual($filtros = []) {
        try {
            $periodo = $this->getPeriodoActual();
            $tipoUsuario = Usuario::getTipoUsuario();
            
            // Si es usuario RRHH, puede ver todas las novedades
            if ($tipoUsuario == Usuario::TIPO_RRHH) {
                $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                               n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                               n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                               n.periodo_mes, n.periodo_anio, n.estado,
                               tn.descripcion as tipo_descripcion, 
                               rle.NOMBRE, rle.APELLIDO,
                               rle.COD_DEPARTAMENTO as codigo_centro_costos, rle.DESC_DEPARTAMENTO as descripcion_centro_costos
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                        WHERE n.periodo_mes = ? AND n.periodo_anio = ?";
            } else {
                // Para otros tipos de usuario, aplicar filtro específico dinámicamente
                $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
                
                $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                               n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                               n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                               n.periodo_mes, n.periodo_anio, n.estado,
                               tn.descripcion as tipo_descripcion, 
                               rle.NOMBRE, rle.APELLIDO,
                               rle.COD_DEPARTAMENTO as codigo_centro_costos, rle.DESC_DEPARTAMENTO as descripcion_centro_costos
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                        WHERE n.periodo_mes = ? AND n.periodo_anio = ? AND tn.$campoPermiso = 1";
            }
            
            $params = [$periodo['periodo_mes'], $periodo['periodo_anio']];

            // Filtros adicionales para getNovedadesPeriodoActual
            if (!empty($filtros['legajo'])) {
                $sql .= " AND n.legajo = ?";
                $params[] = $filtros['legajo'];
            }

            if (!empty($filtros['centro_costos'])) {
                $sql .= " AND rle.COD_DEPARTAMENTO = ?";
                $params[] = $filtros['centro_costos'];
            }

            if (!empty($filtros['tipo_novedad'])) {
                $sql .= " AND n.tipo_novedad = ?";
                $params[] = $filtros['tipo_novedad'];
            }

            $sql .= " ORDER BY n.fecha_creacion DESC";

            $stmt = $this->db->query($sql, $params);
            $novedades = $stmt->fetchAll();
            
            // Procesar los resultados para agregar campos calculados
            foreach ($novedades as &$novedad) {
                // Agregar display de centro de costos (abreviado)
                $novedad['centro_costos_display'] = '';
                if (!empty($novedad['descripcion_centro_costos'])) {
                    // Usar función de abreviación
                    $novedad['centro_costos_display'] = $this->abreviarCentroCostos(
                        $novedad['descripcion_centro_costos'], 
                        $novedad['codigo_centro_costos'] ?? ''
                    );
                } elseif (!empty($novedad['codigo_centro_costos'])) {
                    $novedad['centro_costos_display'] = $novedad['codigo_centro_costos'];
                }
                
                // Mantener compatibilidad con nombre_sucursal para el frontend
                $novedad['nombre_sucursal'] = $novedad['centro_costos_display'];
                
                // Agregar valor display basado en tipo de novedad - SIN valores monetarios ficticios
                $tipoNovedad = (int)$novedad['tipo_novedad'];
                $valorNumerico = (float)$novedad['valor_numerico'];
                
                switch ($tipoNovedad) {
                    case 1: // Cambio de centro de costos
                        $novedad['valor_display'] = 'Cambio de centro de costos';
                        break;
                    case 2: // Nueva Posición
                        $novedad['valor_display'] = !empty($novedad['puesto']) ? $novedad['puesto'] : 'Nueva Posición';
                        break;
                    case 3: // Nuevo salario neto
                        $novedad['valor_display'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Ajuste salarial';
                        break;
                    case 4: // Ajuste de premios
                        $novedad['valor_display'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Premio';
                        break;
                    case 5: // Horas extras
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' horas extras' : 'Horas extras';
                        break;
                    case 6: // Horas adicionales
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' horas adicionales' : 'Horas adicionales';
                        break;
                    case 7: // Permisos
                        $novedad['valor_display'] = 'Permiso' . ($novedad['compensa'] ? ' (compensa)' : '');
                        break;
                    case 8: // Cortes
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' cortes' : 'Cortes';
                        break;
                    case 9: // Producción 25%
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' unidades (25%)' : 'Producción 25%';
                        break;
                    case 10: // Producción 50%
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' unidades (50%)' : 'Producción 50%';
                        break;
                    case 11: // Producción 100%
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' unidades (100%)' : 'Producción 100%';
                        break;
                    default:
                        $novedad['valor_display'] = 'N/A';
                        break;
                }
                
                // Arreglar fecha display - usar fecha_creacion correctamente formateada
                $fechaRegistro = null;
                $fechaVigencia = null;
                $fechaVigenciaHasta = null;
                
                // Procesar fecha_creacion (registro)
                if (!empty($novedad['fecha_creacion'])) {
                    try {
                        if ($novedad['fecha_creacion'] instanceof DateTime) {
                            $fechaRegistro = $novedad['fecha_creacion']->format('d/m/Y');
                        } else {
                            // Manejar string de fecha de SQL Server
                            $fechaRegistro = date('d/m/Y', strtotime($novedad['fecha_creacion']));
                        }
                    } catch (Exception $e) {
                        $fechaRegistro = 'Fecha inválida';
                    }
                }
                
                // Procesar fecha_vigencia o fecha_permiso según corresponda
                $fechaClave = null;
                if (!empty($novedad['fecha_vigencia'])) {
                    try {
                        if ($novedad['fecha_vigencia'] instanceof DateTime) {
                            $fechaVigencia = $novedad['fecha_vigencia']->format('d/m/Y');
                        } else {
                            $fechaVigencia = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                        }
                        $novedad['fecha_vigencia'] = $fechaVigencia; // Sobrescribir con formato correcto
                        $fechaClave = $fechaVigencia;
                    } catch (Exception $e) {
                        $fechaVigencia = null;
                    }
                } elseif (!empty($novedad['fecha_permiso'])) {
                    try {
                        if ($novedad['fecha_permiso'] instanceof DateTime) {
                            $fechaClave = $novedad['fecha_permiso']->format('d/m/Y');
                            $novedad['fecha_permiso'] = $fechaClave;
                        } else {
                            $fechaClave = date('d/m/Y', strtotime($novedad['fecha_permiso']));
                            $novedad['fecha_permiso'] = $fechaClave;
                        }
                    } catch (Exception $e) {
                        $fechaClave = null;
                    }
                }
                
                // Procesar fecha_vigencia_hasta
                if (!empty($novedad['fecha_vigencia_hasta'])) {
                    try {
                        if ($novedad['fecha_vigencia_hasta'] instanceof DateTime) {
                            $fechaVigenciaHasta = $novedad['fecha_vigencia_hasta']->format('d/m/Y');
                        } else {
                            $fechaVigenciaHasta = date('d/m/Y', strtotime($novedad['fecha_vigencia_hasta']));
                        }
                        $novedad['fecha_vigencia_hasta'] = $fechaVigenciaHasta; // Sobrescribir con formato correcto
                    } catch (Exception $e) {
                        $fechaVigenciaHasta = null;
                    }
                }
                
                // Procesar fecha_creacion y sobrescribir
                if ($fechaRegistro) {
                    $novedad['fecha_creacion'] = $fechaRegistro;
                }
                
                // Asignar fechas procesadas
                $novedad['fecha_registro'] = $fechaRegistro ?: 'N/A';
                $novedad['fecha_display'] = $fechaClave ?: $fechaRegistro ?: 'N/A';
                
                // Agregar estado_numero para compatibilidad con frontend
                $novedad['estado_numero'] = $this->mapearEstadoANumero($novedad['estado']);
            }
            
            return $novedades;
            
        } catch (Exception $e) {
            error_log("Error en getNovedadesPeriodoActual: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mapear estado string de BD a número para frontend
     */
    private function mapearEstadoANumero($estadoString) {
        $mapa = [
            'Enviada' => 1,
            'En Revisión' => 2,
            'Aprobada' => 3,
            'A Revisar' => 4,
            'Procesada' => 5
        ];
        
        return $mapa[$estadoString] ?? 1; // Default a "Enviada"
    }

    /**
     * Obtener TODAS las novedades sin filtro de período (para consulta general)
     */
    public function getAllNovedades($filtros = []) {
        try {
            $tipoUsuario = Usuario::getTipoUsuario();
            
            // Si es usuario RRHH, puede ver todas las novedades
            if ($tipoUsuario == Usuario::TIPO_RRHH) {
                $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                               n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                               n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                               n.periodo_mes, n.periodo_anio, n.estado,
                               tn.descripcion as tipo_descripcion, 
                               rle.NOMBRE as nombre, rle.APELLIDO as apellido,
                               rle.COD_DEPARTAMENTO as codigo_centro_costos, rle.DESC_DEPARTAMENTO as descripcion_centro_costos
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                        WHERE 1=1";
            } else {
                // Para otros tipos de usuario, aplicar filtro específico dinámicamente
                $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
                
                $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                               n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                               n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                               n.periodo_mes, n.periodo_anio, n.estado,
                               tn.descripcion as tipo_descripcion, 
                               rle.NOMBRE as nombre, rle.APELLIDO as apellido,
                               rle.COD_DEPARTAMENTO as codigo_centro_costos, rle.DESC_DEPARTAMENTO as descripcion_centro_costos
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                        WHERE tn.$campoPermiso = 1";
            }
            
            $params = [];

            // Filtros adicionales para getAllNovedades
            if (!empty($filtros['legajo'])) {
                $sql .= " AND n.legajo = ?";
                $params[] = $filtros['legajo'];
            }

            if (!empty($filtros['centro_costos'])) {
                $sql .= " AND rle.COD_DEPARTAMENTO = ?";
                $params[] = $filtros['centro_costos'];
            }

            if (!empty($filtros['tipo_novedad'])) {
                $sql .= " AND n.tipo_novedad = ?";
                $params[] = $filtros['tipo_novedad'];
            }

            $sql .= " ORDER BY n.fecha_creacion DESC";

            $stmt = $this->db->query($sql, $params);
            $novedades = $stmt->fetchAll();
            
            // Procesar los resultados igual que en getNovedadesPeriodoActual
            foreach ($novedades as &$novedad) {
                // Agregar display de centro de costos (abreviado)
                $novedad['centro_costos_display'] = '';
                if (!empty($novedad['descripcion_centro_costos'])) {
                    // Usar función de abreviación
                    $novedad['centro_costos_display'] = $this->abreviarCentroCostos(
                        $novedad['descripcion_centro_costos'], 
                        $novedad['codigo_centro_costos'] ?? ''
                    );
                } elseif (!empty($novedad['codigo_centro_costos'])) {
                    $novedad['centro_costos_display'] = $novedad['codigo_centro_costos'];
                }
                
                // Mantener compatibilidad con nombre_sucursal para el frontend
                $novedad['nombre_sucursal'] = $novedad['centro_costos_display'];
                
                // Agregar valor display basado en tipo de novedad
                $tipoNovedad = (int)$novedad['tipo_novedad'];
                $valorNumerico = (float)$novedad['valor_numerico'];
                
                switch ($tipoNovedad) {
                    case 1: // Cambio de centro de costos
                        $novedad['valor_display'] = 'Cambio de centro de costos';
                        break;
                    case 2: // Nueva Posición
                        $novedad['valor_display'] = !empty($novedad['puesto']) ? $novedad['puesto'] : 'Nueva Posición';
                        break;
                    case 3: // Nuevo salario neto
                        $novedad['valor_display'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Ajuste salarial';
                        break;
                    case 4: // Ajuste de premios
                        $novedad['valor_display'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Premio';
                        break;
                    case 5: // Horas extras
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' horas extras' : 'Horas extras';
                        break;
                    case 6: // Horas adicionales
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' horas adicionales' : 'Horas adicionales';
                        break;
                    case 7: // Permisos
                        $novedad['valor_display'] = 'Permiso' . ($novedad['compensa'] ? ' (compensa)' : '');
                        break;
                    case 8: // Cortes
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' cortes' : 'Cortes';
                        break;
                    case 9: // Producción 25%
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' unidades (25%)' : 'Producción 25%';
                        break;
                    case 10: // Producción 50%
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' unidades (50%)' : 'Producción 50%';
                        break;
                    case 11: // Producción 100%
                        $novedad['valor_display'] = $valorNumerico != 0 ? $valorNumerico . ' unidades (100%)' : 'Producción 100%';
                        break;
                    default:
                        $novedad['valor_display'] = 'N/A';
                        break;
                }
                
                // Procesar fechas igual que en el método original
                $fechaRegistro = null;
                $fechaVigencia = null;
                $fechaVigenciaHasta = null;
                
                // Procesar fecha_creacion (registro)
                if (!empty($novedad['fecha_creacion'])) {
                    try {
                        if ($novedad['fecha_creacion'] instanceof DateTime) {
                            $fechaRegistro = $novedad['fecha_creacion']->format('d/m/Y');
                        } else {
                            $fechaRegistro = date('d/m/Y', strtotime($novedad['fecha_creacion']));
                        }
                    } catch (Exception $e) {
                        $fechaRegistro = 'Fecha inválida';
                    }
                }
                
                // Procesar fecha_vigencia o fecha_permiso según corresponda
                $fechaClave = null;
                if (!empty($novedad['fecha_vigencia'])) {
                    try {
                        if ($novedad['fecha_vigencia'] instanceof DateTime) {
                            $fechaVigencia = $novedad['fecha_vigencia']->format('d/m/Y');
                        } else {
                            $fechaVigencia = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                        }
                        $novedad['fecha_vigencia'] = $fechaVigencia; // Sobrescribir con formato correcto
                        $fechaClave = $fechaVigencia;
                    } catch (Exception $e) {
                        $fechaVigencia = null;
                    }
                } elseif (!empty($novedad['fecha_permiso'])) {
                    try {
                        if ($novedad['fecha_permiso'] instanceof DateTime) {
                            $fechaClave = $novedad['fecha_permiso']->format('d/m/Y');
                            $novedad['fecha_permiso'] = $fechaClave;
                        } else {
                            $fechaClave = date('d/m/Y', strtotime($novedad['fecha_permiso']));
                            $novedad['fecha_permiso'] = $fechaClave;
                        }
                    } catch (Exception $e) {
                        $fechaClave = null;
                    }
                }
                
                // Procesar fecha_vigencia_hasta
                if (!empty($novedad['fecha_vigencia_hasta'])) {
                    try {
                        if ($novedad['fecha_vigencia_hasta'] instanceof DateTime) {
                            $fechaVigenciaHasta = $novedad['fecha_vigencia_hasta']->format('d/m/Y');
                        } else {
                            $fechaVigenciaHasta = date('d/m/Y', strtotime($novedad['fecha_vigencia_hasta']));
                        }
                        $novedad['fecha_vigencia_hasta'] = $fechaVigenciaHasta; // Sobrescribir con formato correcto
                    } catch (Exception $e) {
                        $fechaVigenciaHasta = null;
                    }
                }
                
                // Procesar fecha_creacion y sobrescribir
                if ($fechaRegistro) {
                    $novedad['fecha_creacion'] = $fechaRegistro;
                }
                
                // Asignar fechas procesadas
                $novedad['fecha_registro'] = $fechaRegistro ?: 'N/A';
                $novedad['fecha_display'] = $fechaClave ?: $fechaRegistro ?: 'N/A';
                
                // Mapear estado string de BD a número para frontend
                $novedad['estado_numero'] = $this->mapearEstadoANumero($novedad['estado']);
            }
            
            return $novedades;
            
        } catch (Exception $e) {
            error_log("Error en getAllNovedades: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener mapa de sucursales para nombres - con cache y manejo de errores
     */
    private function obtenerMapaSucursales() {
        // Usar cache si ya está disponible
        if ($this->sucursalesCache !== null) {
            return $this->sucursalesCache;
        }
        
        try {
            $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL 
                    FROM [LAKERBIS].locales_lakers.dbo.SUCURSALES_LAKERS 
                    WHERE CANAL = 'PROPIOS' AND HABILITADO = 1";
            
            $stmt = $this->db->query($sql);
            $sucursales = $stmt->fetchAll();
            
            $mapa = [];
            foreach ($sucursales as $sucursal) {
                $mapa[$sucursal['NRO_SUCURSAL']] = $sucursal['DESC_SUCURSAL'];
            }
            
            // Agregar Casa Central al mapa (usar 1 como identificador numérico)
            $mapa[1] = 'CASA CENTRAL';
            
            // Guardar en cache
            $this->sucursalesCache = $mapa;
            return $mapa;
        } catch (Exception $e) {
            error_log("Error obteniendo mapa de sucursales: " . $e->getMessage());
            // Retornar un mapa básico en caso de error (incluye Casa Central con ID 1)
            $this->sucursalesCache = [1 => 'CASA CENTRAL'];
            return $this->sucursalesCache;
        }
    }       
    
    /**
     * Obtener nombre de sucursal por número
     */
    private function obtenerNombreSucursal($numeroSucursal) {
        if (empty($numeroSucursal)) {
            return 'No especificado';
        }
        
        $mapa = $this->obtenerMapaSucursales();
        return isset($mapa[$numeroSucursal]) ? $mapa[$numeroSucursal] : "Sucursal $numeroSucursal";
    }

    /**
     * Obtener novedad por ID con detalle completo - RESPETA PERMISOS DE USUARIO
     */
    public function getNovedadById($id) {
        $tipoUsuario = Usuario::getTipoUsuario();
        
        // Si es usuario RRHH, puede ver cualquier novedad
        if ($tipoUsuario == Usuario::TIPO_RRHH) {
            $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                           n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                           n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                           n.periodo_mes, n.periodo_anio, n.estado,
                           n.tiene_tope, n.porcentaje_1, n.porcentaje_2, n.tipo_comision,
                           n.aplica_vendedora, n.aplica_sub_encargada, 
                           tn.descripcion as tipo_descripcion,
                           emp.NOMBRE as nombre, emp.APELLIDO as apellido,
                           emp.COD_DEPARTAMENTO as codigo_centro_costos,
                           emp.DESC_DEPARTAMENTO as descripcion_centro_costos
                    FROM novedades n 
                    INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                    LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL emp ON n.legajo = emp.NRO_LEGAJO
                    WHERE n.id = ?";
        } else {
            // Para otros tipos de usuario, validar permisos específicos dinámicamente
            $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
            
            $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                           n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                           n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                           n.periodo_mes, n.periodo_anio, n.estado,
                           n.tiene_tope, n.porcentaje_1, n.porcentaje_2, n.tipo_comision,
                           n.aplica_vendedora, n.aplica_sub_encargada,
                           tn.descripcion as tipo_descripcion,
                           emp.NOMBRE as nombre, emp.APELLIDO as apellido,
                           emp.COD_DEPARTAMENTO as codigo_centro_costos,
                           emp.DESC_DEPARTAMENTO as descripcion_centro_costos
                    FROM novedades n 
                    INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                    LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL emp ON n.legajo = emp.NRO_LEGAJO
                    WHERE n.id = ? AND tn.$campoPermiso = 1";
        }

        $stmt = $this->db->query($sql, [$id]);
        $novedad = $stmt->fetch();

        if ($novedad) {
            // Centro de costos en lugar de sucursal (abreviado)
            $novedad['centro_costos_display'] = '';
            if (!empty($novedad['descripcion_centro_costos'])) {
                // Usar función de abreviación
                $novedad['centro_costos_display'] = $this->abreviarCentroCostos(
                    $novedad['descripcion_centro_costos'], 
                    $novedad['codigo_centro_costos'] ?? ''
                );
            } elseif (!empty($novedad['codigo_centro_costos'])) {
                $novedad['centro_costos_display'] = $novedad['codigo_centro_costos'];
            } elseif (!empty($novedad['sucursal'])) {
                $novedad['centro_costos_display'] = 'Centro ' . $novedad['sucursal'];
            }

            // Mantener compatibilidad con campos de sucursal por ahora
            $novedad['nombre_sucursal'] = $novedad['centro_costos_display'];

            // Formatear fechas a dd/mm/aaaa
            foreach (['fecha_vigencia','fecha_vigencia_hasta','fecha_permiso','fecha_creacion'] as $campoFecha) {
                if (!empty($novedad[$campoFecha])) {
                    try {
                        if ($novedad[$campoFecha] instanceof DateTime) {
                            $novedad[$campoFecha] = $novedad[$campoFecha]->format('d/m/Y');
                        } else {
                            $novedad[$campoFecha] = date('d/m/Y', strtotime($novedad[$campoFecha]));
                        }
                    } catch (Exception $e) {
                        // Mantener valor original si hay error
                    }
                }
            }

            // Agregar información de período SOLO SI NO ESTÁ EN LA BASE DE DATOS
            if (empty($novedad['periodo_mes']) || empty($novedad['periodo_anio'])) {
                $periodo = $this->getPeriodoActual();
                $novedad['periodo_mes'] = $novedad['periodo_mes'] ?: $periodo['periodo_mes'];
                $novedad['periodo_anio'] = $novedad['periodo_anio'] ?: $periodo['periodo_anio'];
            }

            // Valor display - MEJORADO para tipo 2
            if ((int)$novedad['tipo_novedad'] === 2) {
                // Para nueva posición, construir display desde campos directos
                $puestoDisplay = !empty($novedad['puesto']) ? $novedad['puesto'] : 'Nueva Posición';
                $tipoPuesto = isset($novedad['tipo_nuevo_puesto']) ? ucfirst($novedad['tipo_nuevo_puesto']) : 'Permanente';
                $novedad['valor_display'] = $puestoDisplay . ' (' . $tipoPuesto . ')';
            } else {
                $novedad['valor_display'] = $this->construirValorDisplay((int)$novedad['tipo_novedad'], $novedad['valor_numerico']);
            }

            // Detalle específico
            $novedad['detalle_especifico'] = $this->generarDetalleEspecifico($novedad);
            
            // Agregar estado_numero para compatibilidad con frontend
            $novedad['estado_numero'] = $this->mapearEstadoANumero($novedad['estado']);
        }

        return $novedad;
    }

    /**
     * Generar detalle específico según tipo de novedad
     */
    private function generarDetalleEspecifico($novedad) {
        $tipoNovedad = (int)$novedad['tipo_novedad'];
        $valorNumerico = (float)$novedad['valor_numerico'];
        $detalle = [];

        switch ($tipoNovedad) {
            case 1: // Cambio de sucursal
                $detalle['tipo'] = 'Cambio de sucursal';
                // Ya no usamos el campo sucursal, se maneja desde centro_costos
                $detalle['sucursal_actual'] = $novedad['centro_costos'] ?? 'N/A';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                // Extraer nueva sucursal de observaciones si existe (múltiples patrones)
                if (preg_match('/- De:\s*.+?\s*a:\s*(.+?)(?:\s|$)/', $novedad['observaciones'], $matches)) {
                    // Patrón: "- De: X a: Y"
                    $detalle['nueva_sucursal'] = trim($matches[1]);
                } elseif (preg_match('/- Nueva sucursal:\s*(.+?)(?:\s|$)/', $novedad['observaciones'], $matches)) {
                    // Patrón: "- Nueva sucursal: X"
                    $detalle['nueva_sucursal'] = trim($matches[1]);
                } elseif (preg_match('/Nueva sucursal:\s*(.+?)(?:\s|$)/', $novedad['observaciones'], $matches)) {
                    // Patrón legacy: "Nueva sucursal: X"
                    $detalle['nueva_sucursal'] = trim($matches[1]);
                } else {
                    $detalle['nueva_sucursal'] = 'No especificado';
                }
                break;

            case 2: // Nueva Posición
                $detalle['tipo'] = 'Nueva Posición';
                $detalle['nuevo_puesto'] = $novedad['puesto'];
                
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 3: // Nuevo salario neto
                $detalle['tipo'] = 'Nuevo salario neto';
                $detalle['nuevo_salario'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 4: // Ajuste de premios
                $detalle['tipo'] = 'Ajuste de premios';
                $detalle['monto_premio'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 5: // Horas extras
                $detalle['tipo'] = 'Horas extras';
                $detalle['cantidad_horas'] = $valorNumerico != 0 ? $valorNumerico . ' horas' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 6: // Horas adicionales
                $detalle['tipo'] = 'Horas adicionales';
                $detalle['cantidad_horas'] = $valorNumerico != 0 ? $valorNumerico . ' horas' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 7: // Permisos
                $detalle['tipo'] = 'Permiso';
                $detalle['compensa'] = $novedad['compensa'] ? 'Sí' : 'No';
                if (!empty($novedad['fecha_permiso'])) {
                    $detalle['fecha_permiso'] = $this->formatearFecha($novedad['fecha_permiso']);
                }
                break;

            case 8: // Cortes
                $detalle['tipo'] = 'Cortes';
                $detalle['cantidad_cortes'] = $valorNumerico != 0 ? $valorNumerico . ' cortes' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 9: // Producción 25%
                $detalle['tipo'] = 'Producción 25%';
                $detalle['cantidad_unidades'] = $valorNumerico != 0 ? $valorNumerico . ' unidades' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 10: // Producción 50%
                $detalle['tipo'] = 'Producción 50%';
                $detalle['cantidad_unidades'] = $valorNumerico != 0 ? $valorNumerico . ' unidades' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 11: // Producción 100%
                $detalle['tipo'] = 'Producción 100%';
                $detalle['cantidad_unidades'] = $valorNumerico != 0 ? $valorNumerico . ' unidades' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            default:
                $detalle['tipo'] = 'Novedad general';
                break;
        }

        return $detalle;
    }

    /**
     * Construir valor display según tipo sin inventar montos
     */
    private function construirValorDisplay(int $tipo, $valor) {
        if ($valor === null || $valor === '' || $valor == 0) {
            // Para salarios/premios mostrar vacío si 0; para otros también
            return '';
        }
        switch ($tipo) {
            case 3: // salario
            case 4: // premio
                return '$' . number_format((float)$valor, 2, ',', '.');
            case 5:
                return $valor . ' horas extras';
            case 6:
                return $valor . ' horas adicionales';
            case 8:
                return $valor . ' cortes';
            case 9:
                return $valor . ' unidades (25%)';
            case 10:
                return $valor . ' unidades (50%)';
            case 11:
                return $valor . ' unidades (100%)';
            default:
                return (string)$valor;
        }
    }

    /**
     * Validar datos de novedad
     */
    public function validarDatos($datos) {
        $errores = [];

        if (empty($datos['legajo'])) {
            $errores[] = 'El legajo es obligatorio';
        }

        if (empty($datos['tipo_novedad'])) {
            $errores[] = 'El tipo de novedad es obligatorio';
        }

        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es obligatorio';
        }

        if (empty($datos['apellido'])) {
            $errores[] = 'El apellido es obligatorio';
        }

        // Validar sucursal solo para cambio de sucursal (tipo 1)
        $tipoNovedad = (int)$datos['tipo_novedad'];
        if ($tipoNovedad == 1 && empty($datos['nueva_sucursal']) && empty($datos['sucursal_nueva_id'])) {
            $errores[] = 'La nueva sucursal es obligatoria para cambio de sucursal';
        }

        // Validaciones específicas por tipo de novedad
        $tipoNovedad = (int)$datos['tipo_novedad'];
        
        switch($tipoNovedad) {
            case 1: // Cambio de sucursal
                if (empty($datos['nueva_sucursal']) && empty($datos['sucursal_nueva_id'])) {
                    $errores[] = 'La nueva sucursal es obligatoria';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                break;
                
            case 2: // Cambio de puesto
                if (empty($datos['puesto'])) {
                    $errores[] = 'El puesto es obligatorio';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                break;
                
            case 3: // Nuevo salario neto
            case 4: // Ajuste de premios
                if (empty($datos['importe']) || !is_numeric($datos['importe'])) {
                    $errores[] = 'El importe es obligatorio y debe ser numérico';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                break;
                
            case 5: // Horas extras
            case 6: // Horas adicionales
                if (empty($datos['cantidad_horas']) || !is_numeric($datos['cantidad_horas'])) {
                    $errores[] = 'La cantidad de horas es obligatoria y debe ser numérica';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                break;
                
            case 7: // Permisos
                // Log para debugging del caso de permisos
                error_log("🔍 VALIDACIÓN PERMISOS - Datos recibidos: fecha_permiso=" . 
                         (isset($datos['fecha_permiso']) ? var_export($datos['fecha_permiso'], true) : 'NO EXISTE') . 
                         ", empty()=" . (empty($datos['fecha_permiso']) ? 'TRUE' : 'FALSE'));
                
                if (empty($datos['fecha_permiso'])) {
                    $errores[] = 'La fecha del permiso es obligatoria';
                } else {
                    // Validar formato de fecha
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_permiso'])) {
                        $errores[] = 'La fecha del permiso debe tener formato válido (YYYY-MM-DD)';
                    } else {
                        // Validar que sea una fecha válida
                        $fechaPartes = explode('-', $datos['fecha_permiso']);
                        if (count($fechaPartes) === 3) {
                            $year = (int)$fechaPartes[0];
                            $month = (int)$fechaPartes[1];
                            $day = (int)$fechaPartes[2];
                            if (!checkdate($month, $day, $year)) {
                                $errores[] = 'La fecha del permiso no es válida';
                            }
                        }
                    }
                }
                break;
                
            case 8: // Cortes
                if (empty($datos['cantidad_cortes']) || !is_numeric($datos['cantidad_cortes'])) {
                    $errores[] = 'La cantidad de cortes es obligatoria y debe ser numérica';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                break;
            
            case 32: // Plus de caja
                if (empty($datos['tipo_plus_caja'])) {
                    $errores[] = 'El tipo de plus de caja es obligatorio';
                }
                // Para premios, el importe es obligatorio
                if (isset($datos['tipo_plus_caja']) && $datos['tipo_plus_caja'] === 'premio') {
                    if (empty($datos['importe']) || !is_numeric($datos['importe']) || $datos['importe'] <= 0) {
                        $errores[] = 'El importe es obligatorio para premios de caja';
                    }
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                break;

            case 33: // Plus de Sub-Encargada
            case 34: // Plus de Encargada
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                // Si tiene importe, validar que sea numérico y positivo
                if (isset($datos['tiene_importe']) && $datos['tiene_importe'] === '1') {
                    if (empty($datos['importe']) || !is_numeric($datos['importe']) || $datos['importe'] <= 0) {
                        $errores[] = 'El importe debe ser un número positivo cuando se especifica';
                    }
                }
                break;

            case 35: // Premio Local
                if (empty($datos['importe']) || !is_numeric($datos['importe']) || $datos['importe'] <= 0) {
                    $errores[] = 'El importe del premio es obligatorio';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                // Validar selección (manejar tanto formato nuevo como antiguo)
                $tieneSeleccion = false;
                if (isset($datos['aplica_a']) && in_array($datos['aplica_a'], ['vendedora', 'sub_encargada'])) {
                    $tieneSeleccion = true;
                } elseif (isset($datos['aplica_vendedora']) || isset($datos['aplica_sub_encargada'])) {
                    $tieneSeleccion = (!empty($datos['aplica_vendedora']) || !empty($datos['aplica_sub_encargada']));
                }
                
                if (!$tieneSeleccion) {
                    $errores[] = 'Debe seleccionar a quién aplica el premio';
                }
                break;

            case 36: // Comisión Individual
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                
                // COMISIÓN INDIVIDUAL: Solo validar porcentaje simple
                if (empty($datos['porcentaje_individual']) || !is_numeric($datos['porcentaje_individual']) || 
                    $datos['porcentaje_individual'] <= 0) {
                    $errores[] = 'El porcentaje individual es obligatorio y debe ser mayor a 0';
                }
                break;

            case 37: // Comisión sobre Local
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                
                // COMISIÓN SOBRE LOCAL: Validar estructura de tope
                if (!isset($datos['tiene_tope'])) {
                    $errores[] = 'Debe indicar si la comisión tiene tope';
                } else {
                    if ($datos['tiene_tope']) {
                        // Con tope: validar dos porcentajes
                        if (empty($datos['porcentaje_1']) || !is_numeric($datos['porcentaje_1']) || 
                            $datos['porcentaje_1'] <= 0) {
                            $errores[] = 'El primer porcentaje es obligatorio y debe ser mayor a 0';
                        }
                        if (empty($datos['porcentaje_2']) || !is_numeric($datos['porcentaje_2']) || 
                            $datos['porcentaje_2'] <= 0) {
                            $errores[] = 'El segundo porcentaje es obligatorio y debe ser mayor a 0';
                        }
                    } else {
                        // Sin tope: validar un porcentaje
                        if (empty($datos['porcentaje_unico']) || !is_numeric($datos['porcentaje_unico']) || 
                            $datos['porcentaje_unico'] <= 0) {
                            $errores[] = 'El porcentaje único es obligatorio y debe ser mayor a 0';
                        }
                    }
                }
                break;

            case 38: // Premios - Ajuste General
                if (empty($datos['importe']) || !is_numeric($datos['importe']) || $datos['importe'] <= 0) {
                    $errores[] = 'El importe del ajuste es obligatorio';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                break;
        }

        // Validar período personalizado si se proporciona
        if (isset($datos['periodo_mes']) || isset($datos['periodo_anio'])) {
            if (!isset($datos['periodo_mes']) || !isset($datos['periodo_anio'])) {
                $errores[] = 'Si se especifica un período personalizado, tanto el mes como el año son obligatorios';
            } else {
                $mes = (int)$datos['periodo_mes'];
                $año = (int)$datos['periodo_anio'];
                
                // Validar rango del mes
                if ($mes < 1 || $mes > 12) {
                    $errores[] = 'El mes del período debe estar entre 1 y 12';
                }
                
                // Validar rango del año
                if ($año < 2020 || $año > 2030) {
                    $errores[] = 'El año del período debe estar entre 2020 y 2030';
                }
                
                // Validar que el período esté dentro del rango permitido
                if ($mes >= 1 && $mes <= 12 && $año >= 2020 && $año <= 2030) {
                    $fechaActual = new DateTime();
                    $fechaPeriodo = new DateTime("{$año}-{$mes}-01");
                    
                    $fechaMinima = clone $fechaActual;
                    $fechaMinima->modify('-1 month');
                    
                    $fechaMaxima = clone $fechaActual;
                    $fechaMaxima->modify('+11 months');
                    
                    if ($fechaPeriodo < $fechaMinima || $fechaPeriodo > $fechaMaxima) {
                        $errores[] = 'El período seleccionado está fuera del rango permitido (desde el mes anterior hasta 11 meses en adelante)';
                    }
                }
            }
        }

        return $errores;
    }

    /**
     * Formatear fecha para SQL Server
     * Convierte fecha HTML (YYYY-MM-DD) a formato compatible con SQL Server
     * Retorna NULL si la fecha está vacía o es inválida
     */
    private function formatearFechaParaSQL($fecha) {
        // Si la fecha está vacía o es null, retornar null
        if (empty($fecha) || $fecha === null) {
            return null;
        }

        // Si ya es null, retornarlo
        if ($fecha === null) {
            return null;
        }

        // Validar formato YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return null;
        }

        // Validar que la fecha sea válida
        $fechaPartes = explode('-', $fecha);
        if (count($fechaPartes) !== 3) {
            return null;
        }

        $year = (int)$fechaPartes[0];
        $month = (int)$fechaPartes[1];
        $day = (int)$fechaPartes[2];

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        // Retornar en formato ISO que SQL Server acepta
        return $fecha . ' 00:00:00';
    }

    /**
     * Editar una novedad existente
     */
    public function editarNovedad($id, $datos) {
        try {
            $this->db->beginTransaction();

            // Verificar que la novedad existe y obtener datos actuales
            $novedadActual = $this->getNovedadById($id);
            if (!$novedadActual) {
                throw new Exception('Novedad no encontrada');
            }

            // Validar permisos si se cambia el tipo de novedad
            if (isset($datos['tipo_novedad']) && $datos['tipo_novedad'] != $novedadActual['tipo_novedad']) {
                $this->validarPermisosTipoNovedad($datos['tipo_novedad']);
            } else {
                // Validar permisos para el tipo actual
                $this->validarPermisosTipoNovedad($novedadActual['tipo_novedad']);
            }

            // Validar datos
            $errores = $this->validarDatos($datos);
            if (!empty($errores)) {
                throw new Exception('Errores de validación: ' . implode(', ', $errores));
            }

            // Adaptar datos para la actualización
            $datosAdaptados = $this->adaptarDatosParaEdicion($datos);
            $periodo = $this->getPeriodoActual();

            // Preparar SQL de actualización - ACTUALIZADO CON NUEVOS CAMPOS
            $sql = "UPDATE novedades SET 
                        legajo = ?, nombre = ?, apellido = ?, centro_costos = ?, 
                        fecha_vigencia = ?, fecha_vigencia_hasta = ?, puesto = ?, valor_numerico = ?, 
                        fecha_permiso = ?, compensa = ?, tipo_permiso = ?, tipo_nuevo_puesto = ?,
                        observaciones = ?, tipo_novedad = ?, fecha_modificacion = GETDATE(),
                        tiene_tope = ?, porcentaje_1 = ?, porcentaje_2 = ?, tipo_comision = ?,
                        aplica_vendedora = ?, aplica_sub_encargada = ?
                    WHERE id = ?";

            $params = [
                $datosAdaptados['legajo'],
                $datosAdaptados['nombre'],
                $datosAdaptados['apellido'],
                $datosAdaptados['centro_costos'],
                $datosAdaptados['fecha_vigencia'],
                $datosAdaptados['fecha_vigencia_hasta'],
                $datosAdaptados['puesto'],
                $datosAdaptados['valor_numerico'],
                $datosAdaptados['fecha_permiso'],
                $datosAdaptados['compensa'],
                $datosAdaptados['tipo_permiso'],
                $datosAdaptados['tipo_nuevo_puesto'],
                $datosAdaptados['observaciones'],
                $datosAdaptados['tipo_novedad'],
                // NUEVOS PARÁMETROS
                $datosAdaptados['tiene_tope'],
                $datosAdaptados['porcentaje_1'],
                $datosAdaptados['porcentaje_2'],
                $datosAdaptados['tipo_comision'],
                $datosAdaptados['aplica_vendedora'],
                $datosAdaptados['aplica_sub_encargada'],
                $id
            ];

            $this->db->query($sql, $params);
            $this->db->commit();
            
            return ['success' => true, 'id' => $id];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error editando novedad ID $id: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Eliminar una novedad
     */
    public function eliminarNovedad($id) {
        try {
            $this->db->beginTransaction();

            // Verificar que la novedad existe
            $novedad = $this->getNovedadById($id);
            if (!$novedad) {
                throw new Exception('Novedad no encontrada');
            }

            // Validar permisos para eliminar este tipo de novedad
            $this->validarPermisosTipoNovedad($novedad['tipo_novedad']);

            // Eliminar la novedad
            $sql = "DELETE FROM novedades WHERE id = ?";
            $this->db->query($sql, [$id]);

            $this->db->commit();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error eliminando novedad ID $id: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verificar si el usuario puede editar/eliminar una novedad específica
     */
    public function puedeEditarNovedad($novedadId) {
        try {
            $novedad = $this->getNovedadById($novedadId);
            if (!$novedad) {
                return false;
            }

            // Validar permisos según el tipo de novedad
            $this->validarPermisosTipoNovedad($novedad['tipo_novedad']);
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * GESTIÓN DE TIPOS DE NOVEDAD - SOLO PARA RRHH
     */

    /**
     * Obtener todos los tipos de novedad para gestión (solo RRHH)
     */
    public function getAllTiposNovedadParaGestion() {
        // Verificar que sea usuario RRHH
        if (Usuario::getTipoUsuario() !== Usuario::TIPO_RRHH) {
            throw new Exception("Acceso denegado. Solo usuarios RRHH pueden gestionar tipos de novedad.");
        }

        $sql = "SELECT id, codigo, descripcion, activo, fecha_creacion,
                       user_adm, user_com, user_prod, user_rrhh,
                       cierre, corte
                FROM tipos_novedad 
                ORDER BY id";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtener información completa de un tipo de novedad por ID
     */
    public function getTipoNovedadById($tipoId) {
        $sql = "SELECT id, codigo, descripcion, activo, fecha_creacion,
                       user_adm, user_com, user_prod, user_rrhh,
                       cierre, corte
                FROM tipos_novedad 
                WHERE id = ?";
        
        $stmt = $this->db->query($sql, [$tipoId]);
        return $stmt->fetch();
    }

    /**
     * Actualizar permisos de un tipo de novedad (solo RRHH)
     */
    public function actualizarTipoNovedad($id, $datos) {
        // Verificar que sea usuario RRHH
        if (Usuario::getTipoUsuario() !== Usuario::TIPO_RRHH) {
            throw new Exception("Acceso denegado. Solo usuarios RRHH pueden gestionar tipos de novedad.");
        }

        try {
            $sql = "UPDATE tipos_novedad SET 
                    codigo = ?,
                    descripcion = ?,
                    activo = ?,
                    user_adm = ?,
                    user_com = ?,
                    user_prod = ?,
                    user_rrhh = ?,
                    cierre = ?,
                    corte = ?
                    WHERE id = ?";
            
            $params = [
                $datos['codigo'],
                $datos['descripcion'],
                $datos['activo'] ? 1 : 0,
                $datos['user_adm'] ? 1 : 0,
                $datos['user_com'] ? 1 : 0,
                $datos['user_prod'] ? 1 : 0,
                $datos['user_rrhh'] ? 1 : 0,
                $datos['cierre'] ?? null,
                $datos['corte'] ?? null,
                $id
            ];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Error actualizando tipo de novedad: " . $e->getMessage());
            throw new Exception("Error al actualizar el tipo de novedad: " . $e->getMessage());
        }
    }

    /**
     * Crear nuevo tipo de novedad (solo RRHH)
     */
    public function crearTipoNovedad($datos) {
        // Verificar que sea usuario RRHH
        if (Usuario::getTipoUsuario() !== Usuario::TIPO_RRHH) {
            throw new Exception("Acceso denegado. Solo usuarios RRHH pueden gestionar tipos de novedad.");
        }

        try {
            // Verificar que no exista el código
            $sqlCheck = "SELECT COUNT(*) as count FROM tipos_novedad WHERE codigo = ?";
            $stmtCheck = $this->db->query($sqlCheck, [$datos['codigo']]);
            $result = $stmtCheck->fetch();
            
            if ($result['count'] > 0) {
                throw new Exception("Ya existe un tipo de novedad con el código: " . $datos['codigo']);
            }

            $sql = "INSERT INTO tipos_novedad (codigo, descripcion, activo, user_adm, user_com, user_prod, user_rrhh, cierre, corte) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $datos['codigo'],
                $datos['descripcion'],
                $datos['activo'] ? 1 : 0,
                $datos['user_adm'] ? 1 : 0,
                $datos['user_com'] ? 1 : 0,
                $datos['user_prod'] ? 1 : 0,
                $datos['user_rrhh'] ? 1 : 0,
                $datos['cierre'] ?? null,
                $datos['corte'] ?? null
            ];

            // Usar el método insert que maneja SCOPE_IDENTITY() correctamente
            $nuevoId = $this->db->insert($sql, $params);
            return $nuevoId;

        } catch (Exception $e) {
            error_log("Error creando tipo de novedad: " . $e->getMessage());
            throw new Exception("Error al crear el tipo de novedad: " . $e->getMessage());
        }
    }

    /**
     * Eliminar tipo de novedad (solo RRHH) - Solo si no hay novedades asociadas
     */
    public function eliminarTipoNovedad($id) {
        // Verificar que sea usuario RRHH
        if (Usuario::getTipoUsuario() !== Usuario::TIPO_RRHH) {
            throw new Exception("Acceso denegado. Solo usuarios RRHH pueden gestionar tipos de novedad.");
        }

        try {
            // Verificar que no tenga novedades asociadas
            $sqlCheck = "SELECT COUNT(*) as count FROM novedades WHERE tipo_novedad = ?";
            $stmtCheck = $this->db->query($sqlCheck, [$id]);
            $result = $stmtCheck->fetch();
            
            if ($result['count'] > 0) {
                throw new Exception("No se puede eliminar el tipo de novedad porque tiene novedades asociadas.");
            }

            $sql = "DELETE FROM tipos_novedad WHERE id = ?";
            $stmt = $this->db->query($sql, [$id]);
            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Error eliminando tipo de novedad: " . $e->getMessage());
            throw new Exception("Error al eliminar el tipo de novedad: " . $e->getMessage());
        }
    }

    /**
     * Obtener centro de costos de un empleado específico
     */
    private function obtenerCentroCostosEmpleado($legajo) {
        try {
            $sql = "SELECT COD_DEPARTAMENTO FROM [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL WHERE NRO_LEGAJO = ?";
            $result = $this->db->query($sql, [$legajo]);
            $resultado = $result->fetch();
            
            if ($resultado && !empty($resultado['COD_DEPARTAMENTO'])) {
                error_log("✅ Centro de costos encontrado para legajo {$legajo}: " . $resultado['COD_DEPARTAMENTO']);
                return $resultado['COD_DEPARTAMENTO'];
            }
            
            error_log("❌ No se encontró centro de costos para empleado con legajo: " . $legajo);
            throw new Exception("No se encontró centro de costos para el empleado con legajo: $legajo");
        } catch (Exception $e) {
            error_log("❌ Error obteniendo centro de costos para empleado {$legajo}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener tipos de usuario disponibles
     */
    public function getTiposUsuarioDisponibles() {
        return [
            'user_adm' => 'Administrador',
            'user_com' => 'Comercial', 
            'user_prod' => 'Producción',
            'user_rrhh' => 'RRHH'
        ];
    }

    /**
     * Adaptar datos para inserción - ACTUALIZADO CON NUEVOS TIPOS
     */
    private function adaptarDatosParaInsercionActualizado($datos) {
        $tipoNovedad = (int)$datos['tipo_novedad'];
        
        // Limpiar observaciones existentes de datos específicos para evitar duplicación
        $observacionesBase = isset($datos['observaciones']) ? $datos['observaciones'] : '';
        $observacionesLimpias = $this->limpiarObservacionesEspecificas($observacionesBase, $tipoNovedad);
        
        // Determinar centro_costos según el tipo de novedad
        $centroCostos = null;
        if ($tipoNovedad == 1) {
            // Para cambio de sucursal, usar sucursal_nueva_id del formulario
            $centroCostos = isset($datos['sucursal_nueva_id']) ? $datos['sucursal_nueva_id'] : 
                        (isset($datos['nueva_sucursal']) ? $datos['nueva_sucursal'] : null);
            if (!$centroCostos) {
                throw new Exception("Para cambio de sucursal debe especificar la nueva sucursal");
            }
        } else {
            // Para otros tipos, obtener centro de costos del empleado
            $centroCostos = $this->obtenerCentroCostosEmpleado($datos['legajo']);
            if (!$centroCostos) {
                throw new Exception("No se pudo obtener el centro de costos del empleado con legajo: " . $datos['legajo']);
            }
        }
        
        $datosAdaptados = [
            'legajo' => $datos['legajo'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'centro_costos' => $centroCostos,
            'tipo_novedad' => $tipoNovedad,
            'observaciones' => $observacionesLimpias,
            'fecha_vigencia' => null,
            'fecha_vigencia_hasta' => null,
            'puesto' => '',
            'valor_numerico' => null,
            'fecha_permiso' => null,
            'compensa' => null,
            'tipo_permiso' => null,
            'tipo_nuevo_puesto' => null,
            // NUEVOS CAMPOS PARA LOS NUEVOS TIPOS
            'tiene_tope' => null,
            'porcentaje_1' => null,
            'porcentaje_2' => null,
            'tipo_comision' => null,
            'aplica_vendedora' => null,
            'aplica_sub_encargada' => null,
        ];

        // Adaptar según el tipo específico - INCLUYENDO TODOS LOS TIPOS
        switch ($tipoNovedad) {
            case 1: // Cambio de centro de costos
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Cambio de Sucursal';
                
                // Obtener la sucursal actual y nueva por número y convertir a nombres
                $sucursalActualNumero = isset($datos['sucursal_actual']) ? $datos['sucursal_actual'] : '';
                $nuevaSucursalNumero = isset($datos['nueva_sucursal']) ? $datos['nueva_sucursal'] : '';
                
                if (!empty($nuevaSucursalNumero)) {
                    $nombreNuevaSucursal = $this->obtenerNombreSucursal($nuevaSucursalNumero);
                    
                    if (!empty($sucursalActualNumero)) {
                        $nombreSucursalActual = $this->obtenerNombreSucursal($sucursalActualNumero);
                        $datosAdaptados['observaciones'] .= " - De: $nombreSucursalActual a: $nombreNuevaSucursal";
                    } else {
                        $datosAdaptados['observaciones'] .= " - Nueva sucursal: $nombreNuevaSucursal";
                    }
                }
                break;

            case 2: // Nueva Posición
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : 'Cambio de Puesto';
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['tipo_nuevo_puesto'] = 'permanente';
                $datosAdaptados['fecha_vigencia_hasta'] = null;
                break;

            case 3: // Nuevo salario neto
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Nuevo Salario Neto';
                break;

            case 4: // Ajuste de premios
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Ajuste de Premios';
                break;

            case 5: // Horas extras
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_horas']) ? (int)$datos['cantidad_horas'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Horas Extras';
                break;

            case 6: // Horas adicionales
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_horas']) ? (int)$datos['cantidad_horas'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Horas Adicionales';
                break;

            case 7: // Permisos
                $datosAdaptados['fecha_permiso'] = $this->formatearFechaParaSQL($datos['fecha_permiso'] ?? '');
                $datosAdaptados['compensa'] = isset($datos['compensa']) ? (bool)$datos['compensa'] : false;
                $datosAdaptados['puesto'] = 'Permisos';
                break;

            case 8: // Cortes
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_cortes']) ? (int)$datos['cantidad_cortes'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Cortes';
                break;

            case 9: // Producción 25%
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_unidades']) ? (int)$datos['cantidad_unidades'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 25%';
                break;

            case 10: // Producción 50%
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_unidades']) ? (int)$datos['cantidad_unidades'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 50%';
                break;

            case 11: // Producción 100%
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_unidades']) ? (int)$datos['cantidad_unidades'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 100%';
                break;
            
            case 12: // Plus de caja (compatibilidad con ID anterior)
            case 32: // Plus de caja
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de caja';
                // Determinar si es recibo o premio
                $tipoPlus = isset($datos['tipo_plus_caja']) ? $datos['tipo_plus_caja'] : 'recibo';
                $datosAdaptados['observaciones'] .= " - Tipo: " . ucfirst($tipoPlus);
                break;

            case 13: // Plus de Sub-Encargada (compatibilidad con ID anterior)
            case 33: // Plus de Sub-Encargada
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de Sub-Encargada';
                
                // Verificar si tiene importe
                if (isset($datos['tiene_importe']) && $datos['tiene_importe'] === '1') {
                    $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                    $datosAdaptados['observaciones'] .= " - Con importe: $" . number_format($datosAdaptados['valor_numerico'], 2);
                } else {
                    $datosAdaptados['observaciones'] .= " - Sin importe específico";
                }
                break;

            case 14: // Plus de Encargada (compatibilidad con ID anterior)
            case 34: // Plus de Encargada
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de Encargada';
                
                // Verificar si tiene importe
                if (isset($datos['tiene_importe']) && $datos['tiene_importe'] === '1') {
                    $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                    $datosAdaptados['observaciones'] .= " - Con importe: $" . number_format($datosAdaptados['valor_numerico'], 2);
                } else {
                    $datosAdaptados['observaciones'] .= " - Sin importe específico";
                }
                break;

            case 15: // Premio Local (compatibilidad con ID anterior)
            case 35: // Premio Local
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Premio Local';
                
                // Configurar a quién aplica usando los datos booleanos directamente
                $datosAdaptados['aplica_vendedora'] = isset($datos['aplica_vendedora']) && $datos['aplica_vendedora'];
                $datosAdaptados['aplica_sub_encargada'] = isset($datos['aplica_sub_encargada']) && $datos['aplica_sub_encargada'];
                
                // Determinar el texto para las observaciones
                $aplicacionTexto = '';
                if ($datosAdaptados['aplica_vendedora']) {
                    $aplicacionTexto = 'Vendedora';
                } elseif ($datosAdaptados['aplica_sub_encargada']) {
                    $aplicacionTexto = 'Sub-Encargada';
                }
                
                if (!empty($aplicacionTexto)) {
                    $datosAdaptados['observaciones'] .= " - Aplica a: " . $aplicacionTexto;
                }
                break;

            case 16: // Comisión Individual (compatibilidad con ID anterior)
            case 36: // Comisión Individual
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Comisión Individual';
                $datosAdaptados['tipo_comision'] = 'individual';
                
                // COMISIÓN INDIVIDUAL: Solo un porcentaje simple, sin estructura de tope
                // Convertir de porcentaje (25) a decimal (0.25) dividiendo entre 100
                $datosAdaptados['tiene_tope'] = false;
                $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_individual']) ? ((float)$datos['porcentaje_individual'] / 100) : null;
                $datosAdaptados['porcentaje_2'] = null;
                
                // Observaciones simples para comisión individual (mostrar como porcentaje)
                $pDisplay = isset($datos['porcentaje_individual']) ? number_format($datos['porcentaje_individual'], 2) : 0;
                $datosAdaptados['observaciones'] .= " - Porcentaje: {$pDisplay}%";
                break;

            case 17: // Comisión sobre Local (compatibilidad con ID anterior)
            case 37: // Comisión sobre Local
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Comisión sobre Local';
                $datosAdaptados['tipo_comision'] = 'local';
                
                // COMISIÓN SOBRE LOCAL: Con estructura de tope/sin tope
                // Convertir porcentajes de formato humano (25) a decimal (0.25)
                $datosAdaptados['tiene_tope'] = isset($datos['tiene_tope']) ? (bool)$datos['tiene_tope'] : false;
                
                if ($datosAdaptados['tiene_tope']) {
                    // Con tope: dos porcentajes
                    $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_1']) ? ((float)$datos['porcentaje_1'] / 100) : null;
                    $datosAdaptados['porcentaje_2'] = isset($datos['porcentaje_2']) ? ((float)$datos['porcentaje_2'] / 100) : null;
                    $p1Display = isset($datos['porcentaje_1']) ? number_format($datos['porcentaje_1'], 2) : 0;
                    $p2Display = isset($datos['porcentaje_2']) ? number_format($datos['porcentaje_2'], 2) : 0;
                    $datosAdaptados['observaciones'] .= " - Con tope: {$p1Display}% y {$p2Display}%";
                } else {
                    // Sin tope: un porcentaje
                    $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_unico']) ? ((float)$datos['porcentaje_unico'] / 100) : null;
                    $datosAdaptados['porcentaje_2'] = null;
                    $pDisplay = isset($datos['porcentaje_unico']) ? number_format($datos['porcentaje_unico'], 2) : 0;
                    $datosAdaptados['observaciones'] .= " - Sin tope: {$pDisplay}%";
                }
                break;

            case 18: // Premios - Ajuste General (compatibilidad con ID anterior)
            case 38: // Premios - Ajuste General
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Premios - Ajuste General';
                break;

            case 53: // Reemplazo - siempre temporario
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : 'Reemplazo';
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['tipo_nuevo_puesto'] = 'temporario';
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                break;

            case 54: // Aumento Salarial
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Aumento Salarial';
                
                // JavaScript envía directamente porcentaje_1 y valor_numerico
                // Determinar si es por porcentaje o monto
                if (isset($datos['tipo_aumento'])) {
                    if ($datos['tipo_aumento'] === 'porcentaje' && !empty($datos['porcentaje_1'])) {
                        // IMPORTANTE: Convertir porcentaje de 21 a 0.21 (formato decimal) para evitar overflow
                        // La columna porcentaje_1 probablemente tiene NUMERIC(3,2) o NUMERIC(4,2)
                        $porcentajeValor = (float)$datos['porcentaje_1'];
                        $datosAdaptados['porcentaje_1'] = round($porcentajeValor / 100, 4); // Dividir entre 100: 21 -> 0.21
                    } elseif ($datos['tipo_aumento'] === 'monto' && !empty($datos['valor_numerico'])) {
                        // Convertir y redondear a 2 decimales para evitar overflow
                        $datosAdaptados['valor_numerico'] = round((float)$datos['valor_numerico'], 2);
                    }
                }
                break;

            default:
                $datosAdaptados['puesto'] = 'Novedad General';
                break;
        }

        return $datosAdaptados;
    }

    /**
     * Adaptar datos específicamente para edición - NO concatena en observaciones
     */
    private function adaptarDatosParaEdicion($datos) {
        $tipoNovedad = (int)$datos['tipo_novedad'];
        
        // Conservar observaciones originales sin agregar datos automáticos
        $observacionesBase = isset($datos['observaciones']) ? $datos['observaciones'] : '';
        
        // Determinar centro_costos según el tipo de novedad
        $centroCostos = null;
        if ($tipoNovedad == 1) {
            // Para cambio de sucursal, usar sucursal_nueva_id del formulario
            $centroCostos = isset($datos['sucursal_nueva_id']) ? $datos['sucursal_nueva_id'] : 
                        (isset($datos['nueva_sucursal']) ? $datos['nueva_sucursal'] : null);
            if (!$centroCostos) {
                throw new Exception("Para cambio de sucursal debe especificar la nueva sucursal");
            }
        } else {
            // Para otros tipos, mantener el centro de costos actual si no se especifica
            $centroCostos = isset($datos['centro_costos']) ? $datos['centro_costos'] : 
                        $this->obtenerCentroCostosEmpleado($datos['legajo']);
        }
        
        $datosAdaptados = [
            'legajo' => $datos['legajo'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'centro_costos' => $centroCostos,
            'tipo_novedad' => $tipoNovedad,
            'observaciones' => $observacionesBase, // NO agregar datos automáticos
            'fecha_vigencia' => null,
            'fecha_vigencia_hasta' => null,
            'puesto' => '',
            'valor_numerico' => null,
            'fecha_permiso' => null,
            'compensa' => null,
            'tipo_permiso' => null,
            'tipo_nuevo_puesto' => null,
            // CAMPOS ESPECÍFICOS PARA NUEVOS TIPOS
            'tiene_tope' => null,
            'porcentaje_1' => null,
            'porcentaje_2' => null,
            'tipo_comision' => null,
            'aplica_vendedora' => null,
            'aplica_sub_encargada' => null
        ];

        // Adaptar según el tipo específico - SIN MODIFICAR OBSERVACIONES
        switch ($tipoNovedad) {
            case 1: // Cambio de centro de costos
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Cambio de Sucursal';
                break;

            case 2: // Nueva Posición
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : 'Cambio de Puesto';
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['tipo_nuevo_puesto'] = 'permanente';
                $datosAdaptados['fecha_vigencia_hasta'] = null;
                break;

            case 3: // Nuevo salario neto
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Nuevo Salario Neto';
                break;

            case 4: // Ajuste de premios
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Ajuste de Premios';
                break;

            case 5: // Horas extras
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_horas']) ? (int)$datos['cantidad_horas'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Horas Extras';
                break;

            case 6: // Horas adicionales
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_horas']) ? (int)$datos['cantidad_horas'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Horas Adicionales';
                break;

            case 7: // Permisos
                $datosAdaptados['fecha_permiso'] = $this->formatearFechaParaSQL($datos['fecha_permiso'] ?? '');
                $datosAdaptados['compensa'] = isset($datos['compensa']) ? (bool)$datos['compensa'] : false;
                $datosAdaptados['puesto'] = 'Permisos';
                break;

            case 8: // Cortes
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_cortes']) ? (int)$datos['cantidad_cortes'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Cortes';
                break;

            case 9: // Producción 25%
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_unidades']) ? (int)$datos['cantidad_unidades'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 25%';
                break;

            case 10: // Producción 50%
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_unidades']) ? (int)$datos['cantidad_unidades'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 50%';
                break;

            case 11: // Producción 100%
                $datosAdaptados['valor_numerico'] = isset($datos['cantidad_unidades']) ? (int)$datos['cantidad_unidades'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 100%';
                break;
            
            case 12: // Plus de caja (compatibilidad)
            case 32: // Plus de caja
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de caja';
                
                // Guardar tipo de plus en observaciones (actualizar, no concatenar)
                if (isset($datos['tipo_plus_caja'])) {
                    $tipoPlus = ucfirst($datos['tipo_plus_caja']);
                    $datosAdaptados['observaciones'] = "Tipo: {$tipoPlus}";
                }
                break;

            case 13: // Plus de Sub-Encargada (compatibilidad)
            case 33: // Plus de Sub-Encargada
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de Sub-Encargada';
                
                // Solo asignar valor si tiene importe
                if (isset($datos['tiene_importe']) && $datos['tiene_importe'] === '1') {
                    $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                } else {
                    $datosAdaptados['valor_numerico'] = null;
                }
                break;

            case 14: // Plus de Encargada (compatibilidad)
            case 34: // Plus de Encargada
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de Encargada';
                
                // Solo asignar valor si tiene importe
                if (isset($datos['tiene_importe']) && $datos['tiene_importe'] === '1') {
                    $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                } else {
                    $datosAdaptados['valor_numerico'] = null;
                }
                break;

            case 15: // Premio Local (compatibilidad)
            case 35: // Premio Local
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Premio Local';
                
                // Configurar a quién aplica usando los datos booleanos directamente
                $datosAdaptados['aplica_vendedora'] = isset($datos['aplica_vendedora']) && $datos['aplica_vendedora'];
                $datosAdaptados['aplica_sub_encargada'] = isset($datos['aplica_sub_encargada']) && $datos['aplica_sub_encargada'];
                 
                // Actualizar observaciones con aplicabilidad para evitar contradicciones
                $aplicabilidades = [];
                if ($datosAdaptados['aplica_vendedora']) $aplicabilidades[] = 'Vendedora';
                if ($datosAdaptados['aplica_sub_encargada']) $aplicabilidades[] = 'Sub-Encargada';
                
                if (!empty($aplicabilidades)) {
                    $datosAdaptados['observaciones'] = 'Aplica a: ' . implode(', ', $aplicabilidades);
                } else {
                    $datosAdaptados['observaciones'] = 'Aplica a: No especificado';
                }
                break;

            case 16: // Comisión Individual (compatibilidad)
            case 36: // Comisión Individual
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Comisión Individual';
                $datosAdaptados['tipo_comision'] = 'individual';
                
                // COMISIÓN INDIVIDUAL: Solo un porcentaje simple, sin estructura de tope
                // Convertir de porcentaje (25) a decimal (0.25)
                $datosAdaptados['tiene_tope'] = false;
                $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_individual']) ? ((float)$datos['porcentaje_individual'] / 100) : null;
                $datosAdaptados['porcentaje_2'] = null;
                
                // Observaciones usando el valor original (no dividido)
                $p_display = isset($datos['porcentaje_individual']) ? $datos['porcentaje_individual'] : 0;
                $datosAdaptados['observaciones'] = "Comisión individual: {$p_display}%";
                break;

            case 17: // Comisión sobre Local (compatibilidad)
            case 37: // Comisión sobre Local
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Comisión sobre Local';
                $datosAdaptados['tipo_comision'] = isset($datos['tipo_comision']) ? $datos['tipo_comision'] : 'local';
                
                // Configurar tope y porcentajes directamente en campos específicos
                // Convertir de porcentaje (25) a decimal (0.25)
                $datosAdaptados['tiene_tope'] = isset($datos['tiene_tope']) ? (bool)$datos['tiene_tope'] : false;
                
                if ($datosAdaptados['tiene_tope']) {
                    $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_1']) ? ((float)$datos['porcentaje_1'] / 100) : null;
                    $datosAdaptados['porcentaje_2'] = isset($datos['porcentaje_2']) ? ((float)$datos['porcentaje_2'] / 100) : null;
                    
                    // Observaciones con porcentajes en formato original
                    $p1_display = isset($datos['porcentaje_1']) ? $datos['porcentaje_1'] : 0;
                    $p2_display = isset($datos['porcentaje_2']) ? $datos['porcentaje_2'] : 0;
                    $datosAdaptados['observaciones'] = "Comisión sobre local con tope: {$p1_display}% / {$p2_display}%";
                } else {    
                    $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_unico']) ? ((float)$datos['porcentaje_unico'] / 100) : null;
                    $datosAdaptados['porcentaje_2'] = null;
                    
                    // Observaciones con porcentaje en formato original
                    $p_display = isset($datos['porcentaje_unico']) ? $datos['porcentaje_unico'] : 0;
                    $datosAdaptados['observaciones'] = "Comisión sobre local sin tope: {$p_display}%";
                }
                break;

            case 18: // Premios - Ajuste General (compatibilidad)
            case 38: // Premios - Ajuste General
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Premios - Ajuste General';
                break;

            case 53: // Reemplazo - siempre temporario
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : 'Reemplazo';
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['tipo_nuevo_puesto'] = 'temporario';
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                break;

            case 54: // Aumento Salarial
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Aumento Salarial';
                
                // JavaScript envía directamente porcentaje_1 y valor_numerico
                // Determinar si es por porcentaje o monto
                if (isset($datos['tipo_aumento'])) {
                    if ($datos['tipo_aumento'] === 'porcentaje') {
                        // IMPORTANTE: Convertir porcentaje de 21 a 0.21 (formato decimal) para evitar overflow
                        if (!empty($datos['porcentaje_1'])) {
                            $porcentajeValor = (float)$datos['porcentaje_1'];
                            $datosAdaptados['porcentaje_1'] = round($porcentajeValor / 100, 4); // Dividir entre 100: 21 -> 0.21
                        }
                        // Limpiar campo de monto
                        $datosAdaptados['valor_numerico'] = null;
                    } elseif ($datos['tipo_aumento'] === 'monto') {
                        // Convertir y redondear a 2 decimales para evitar overflow
                        if (!empty($datos['valor_numerico'])) {
                            $datosAdaptados['valor_numerico'] = round((float)$datos['valor_numerico'], 2);
                        }
                        // Limpiar campo de porcentaje
                        $datosAdaptados['porcentaje_1'] = null;
                    }
                }
                break;

            default:
                $datosAdaptados['puesto'] = 'Novedad General';
                break;
        }

        return $datosAdaptados;
    }

    /**
     * Crear nueva novedad - ACTUALIZADO PARA NUEVOS CAMPOS
     */
    public function crearNovedadActualizada($datos) {
        try {
            $this->db->beginTransaction();

            // Validar que el usuario puede crear este tipo de novedad
            $tipoNovedad = (int)$datos['tipo_novedad'];
            $this->validarPermisosTipoNovedad($tipoNovedad);

            // Obtener información del tipo de novedad para cálculo correcto de período
            $tipoNovedadInfo = $this->getTipoNovedadById($tipoNovedad);
            if (!$tipoNovedadInfo) {
                throw new Exception("Tipo de novedad no encontrado");
            }

            // Incluir PeriodoUtils si no está ya incluido
            if (!class_exists('PeriodoUtils')) {
                require_once __DIR__ . '/PeriodoUtils.php';
            }

            // Calcular período según el tipo de novedad O usar los proporcionados
            // Si se proporcionan periodo_mes y periodo_anio explícitamente (modo múltiple), usarlos
            if (!empty($datos['periodo_mes']) && !empty($datos['periodo_anio'])) {
                $periodo = [
                    'month' => (int)$datos['periodo_mes'],
                    'year' => (int)$datos['periodo_anio']
                ];
                error_log("🗓️ Valores recibidos del frontend - mes: " . $datos['periodo_mes'] . ", año: " . $datos['periodo_anio']);
                error_log("🗓️ Período convertido - month: " . $periodo['month'] . ", year: " . $periodo['year']);
            } else {
                // Calcular período automáticamente según el tipo de novedad (modo simple)
                $periodo = PeriodoUtils::calcularPeriodoSegunTipoCompleto($tipoNovedadInfo);
                error_log("🗓️ Período calculado automáticamente: {$periodo['month']}/{$periodo['year']}");
            }
            
            // Adaptar datos según el tipo de novedad y estructura real de tabla
            $datosAdaptados = $this->adaptarDatosParaInsercionActualizado($datos);
            
            // DEBUG: Verificar valores de porcentaje_1 y valor_numerico antes del INSERT
            error_log("🔍 DEBUG INSERT - tipo_novedad: " . $datosAdaptados['tipo_novedad']);
            error_log("🔍 DEBUG INSERT - porcentaje_1: " . var_export($datosAdaptados['porcentaje_1'], true) . " (tipo: " . gettype($datosAdaptados['porcentaje_1']) . ")");
            error_log("🔍 DEBUG INSERT - valor_numerico: " . var_export($datosAdaptados['valor_numerico'], true) . " (tipo: " . gettype($datosAdaptados['valor_numerico']) . ")");
            
            // SQL ACTUALIZADO CON NUEVOS CAMPOS
            $sql = "INSERT INTO novedades (
                        legajo, nombre, apellido, fecha_vigencia, puesto, 
                        valor_numerico, fecha_permiso, compensa, tipo_permiso,
                        observaciones, periodo_mes, periodo_anio, fecha_creacion, tipo_novedad, 
                        tipo_nuevo_puesto, estado, fecha_vigencia_hasta, centro_costos,
                        tiene_tope, porcentaje_1, porcentaje_2, tipo_comision, 
                        aplica_vendedora, aplica_sub_encargada
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $datosAdaptados['legajo'],
                $datosAdaptados['nombre'],
                $datosAdaptados['apellido'],
                $datosAdaptados['fecha_vigencia'],
                $datosAdaptados['puesto'],
                $datosAdaptados['valor_numerico'],
                $datosAdaptados['fecha_permiso'],
                $datosAdaptados['compensa'],
                $datosAdaptados['tipo_permiso'],
                $datosAdaptados['observaciones'],
                $periodo['month'],
                $periodo['year'],
                // fecha_creacion se inserta con GETDATE() directamente en SQL
                $datosAdaptados['tipo_novedad'],
                $datosAdaptados['tipo_nuevo_puesto'],
                self::ESTADO_ENVIADA,
                $datosAdaptados['fecha_vigencia_hasta'],
                $datosAdaptados['centro_costos'],
                // NUEVOS PARÁMETROS
                $datosAdaptados['tiene_tope'],
                $datosAdaptados['porcentaje_1'],
                $datosAdaptados['porcentaje_2'],
                $datosAdaptados['tipo_comision'],
                $datosAdaptados['aplica_vendedora'],
                $datosAdaptados['aplica_sub_encargada']
            ];

            $novedadId = $this->db->insert($sql, $params);

            $this->db->commit();
            
            return ['success' => true, 'id' => $novedadId];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener novedades - ACTUALIZADO PARA INCLUIR NUEVOS CAMPOS
     */
    public function getNovedadesPeriodoActualActualizado($filtros = []) {
        try {
            $periodo = $this->getPeriodoActual();
            $tipoUsuario = Usuario::getTipoUsuario();
            
            // SQL actualizado con nuevos campos
            if ($tipoUsuario == Usuario::TIPO_RRHH) {
                $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                            n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                            n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                            n.periodo_mes, n.periodo_anio, n.estado,
                            n.tiene_tope, n.porcentaje_1, n.porcentaje_2, n.tipo_comision,
                            n.aplica_vendedora, n.aplica_sub_encargada, 
                            tn.descripcion as tipo_descripcion, 
                            rle.NOMBRE, rle.APELLIDO,
                            rle.COD_DEPARTAMENTO as codigo_centro_costos, 
                            rle.DESC_DEPARTAMENTO as descripcion_centro_costos
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                        WHERE n.periodo_mes = ? AND n.periodo_anio = ?";
            } else {
                $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
                
                $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                            n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                            n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                            n.periodo_mes, n.periodo_anio, n.estado,
                            n.tiene_tope, n.porcentaje_1, n.porcentaje_2, n.tipo_comision,
                            n.aplica_vendedora, n.aplica_sub_encargada,
                            tn.descripcion as tipo_descripcion, 
                            rle.NOMBRE, rle.APELLIDO,
                            rle.COD_DEPARTAMENTO as codigo_centro_costos, rle.DESC_DEPARTAMENTO as descripcion_centro_costos
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                        WHERE n.periodo_mes = ? AND n.periodo_anio = ? AND tn.$campoPermiso = 1";
            }
            
            $params = [$periodo['periodo_mes'], $periodo['periodo_anio']];

            // Resto de filtros se mantiene igual...
            if (!empty($filtros['legajo'])) {
                $sql .= " AND n.legajo = ?";
                $params[] = $filtros['legajo'];
            }

            if (!empty($filtros['centro_costos'])) {
                $sql .= " AND rle.COD_DEPARTAMENTO = ?";
                $params[] = $filtros['centro_costos'];
            }

            if (!empty($filtros['tipo_novedad'])) {
                $sql .= " AND n.tipo_novedad = ?";
                $params[] = $filtros['tipo_novedad'];
            }

            $sql .= " ORDER BY n.fecha_creacion DESC";

            $stmt = $this->db->query($sql, $params);
            $novedades = $stmt->fetchAll();
            
            // Procesar los resultados para agregar campos calculados - ACTUALIZADO
            foreach ($novedades as &$novedad) {
                // ... código existente se mantiene ...
                
                // Agregar valor display actualizado para nuevos tipos
                $tipoNovedad = (int)$novedad['tipo_novedad'];
                $valorNumerico = (float)$novedad['valor_numerico'];
                
                switch ($tipoNovedad) {
                    // ... casos existentes se mantienen ...
                    
                    case 32: // Plus de caja
                        $novedad['valor_display'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Plus de caja';
                        break;
                    case 33: // Plus de Sub-Encargada
                        $novedad['valor_display'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Plus de Sub-Encargada';
                        break;
                    case 34: // Plus de Encargada
                        $novedad['valor_display'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Plus de Encargada';
                        break;
                    case 15: // Premio Local
                        $aplicaciones = [];
                        if ($novedad['aplica_vendedora']) $aplicaciones[] = 'V';
                        if ($novedad['aplica_sub_encargada']) $aplicaciones[] = 'SE';
                        $aplicaTexto = !empty($aplicaciones) ? ' (' . implode(', ', $aplicaciones) . ')' : '';
                        $novedad['valor_display'] = ($valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Premio Local') . $aplicaTexto;
                        break;
                    case 36: // Comisión Individual
                        // COMISIÓN INDIVIDUAL: Solo mostrar el porcentaje simple
                        $p = $novedad['porcentaje_1'] ? number_format($novedad['porcentaje_1'], 2) : '0';
                        $novedad['valor_display'] = "Individual: {$p}%";
                        break;
                    case 37: // Comisión sobre Local
                        // COMISIÓN SOBRE LOCAL: Con estructura de tope
                        if ($novedad['tiene_tope']) {
                            $p1 = $novedad['porcentaje_1'] ? number_format($novedad['porcentaje_1'], 2) : '0';
                            $p2 = $novedad['porcentaje_2'] ? number_format($novedad['porcentaje_2'], 2) : '0';
                            $novedad['valor_display'] = "Local con tope: {$p1}% y {$p2}%";
                        } else {
                            $p = $novedad['porcentaje_1'] ? number_format($novedad['porcentaje_1'], 2) : '0';
                            $novedad['valor_display'] = "Local sin tope: {$p}%";
                        }
                        break;
                    case 38: // Premios - Ajuste General
                        $novedad['valor_display'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Ajuste General';
                        break;
                    default:
                        // Casos existentes se mantienen
                        break;
                }
                
                // ... resto del código existente se mantiene ...
            }
            
            return $novedades;
            
        } catch (Exception $e) {
            error_log("Error en getNovedadesPeriodoActualActualizado: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar detalle específico según tipo de novedad - ACTUALIZADO
     */
    private function generarDetalleEspecificoActualizado($novedad) {
        $tipoNovedad = (int)$novedad['tipo_novedad'];
        $valorNumerico = (float)$novedad['valor_numerico'];
        $detalle = [];

        switch ($tipoNovedad) {
            // ... casos existentes 1-11 se mantienen ...
            
            case 32: // Plus de caja
                $detalle['tipo'] = 'Plus de caja';
                $detalle['importe'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                // Extraer tipo de plus de observaciones
                if (preg_match('/Tipo:\s*(.+)/', $novedad['observaciones'], $matches)) {
                    $detalle['tipo_plus'] = ucfirst(trim($matches[1]));
                }
                break;

            case 13: // Plus de Sub-Encargada
                $detalle['tipo'] = 'Plus de Sub-Encargada';
                $detalle['importe'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Sin importe específico';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 14: // Plus de Encargada
                $detalle['tipo'] = 'Plus de Encargada';
                $detalle['importe'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'Sin importe específico';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;
            case 15: // Premio Local
                $detalle['tipo'] = 'Premio Local';
                $detalle['importe'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                // Mostrar aplicaciones
                $aplicaciones = [];
                if (!empty($novedad['aplica_vendedora'])) $aplicaciones[] = 'Vendedora';
                if (!empty($novedad['aplica_sub_encargada'])) $aplicaciones[] = 'Sub-Encargada';
                $detalle['aplica_a'] = !empty($aplicaciones) ? implode(', ', $aplicaciones) : 'No especificado';
                break;

            case 16: // Comisión Individual
                $detalle['tipo'] = 'Comisión Individual';
                $detalle['tipo_comision'] = 'Individual';
                // COMISIÓN INDIVIDUAL: Solo un porcentaje simple
                $detalle['porcentaje_individual'] = !empty($novedad['porcentaje_1']) ? number_format($novedad['porcentaje_1'], 2) . '%' : '0%';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 17: // Comisión sobre Local
                $detalle['tipo'] = 'Comisión sobre el Local';
                $detalle['tipo_comision'] = 'Sobre el Local';
                if (!empty($novedad['tiene_tope'])) {
                    $detalle['estructura'] = 'Con tope';
                    $detalle['porcentaje_1'] = !empty($novedad['porcentaje_1']) ? number_format($novedad['porcentaje_1'], 2) . '%' : '0%';
                    $detalle['porcentaje_2'] = !empty($novedad['porcentaje_2']) ? number_format($novedad['porcentaje_2'], 2) . '%' : '0%';
                } else {
                    $detalle['estructura'] = 'Sin tope';
                    $detalle['porcentaje_unico'] = !empty($novedad['porcentaje_1']) ? number_format($novedad['porcentaje_1'], 2) . '%' : '0%';
                }
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 18: // Premios - Ajuste General
                $detalle['tipo'] = 'Premios - Ajuste General';
                $detalle['importe'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            default:
                $detalle['tipo'] = 'Novedad general';
                break;
    }

        return $detalle;
}
}
?>
