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
    const ESTADO_RECHAZADA = 'Rechazada';
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
     * Obtener periodo actual dinámico: 28/MM/AAAA - 27/MM+1/AAAA
     */
    public function getPeriodoActual() {
        $fechaActual = new DateTime();
        $diaActual = (int)$fechaActual->format('d');
        $mesActual = (int)$fechaActual->format('m');
        $anioActual = (int)$fechaActual->format('Y');
        
        // Si estamos antes del día 28, el período va del mes anterior al actual
        // Si estamos en el 28 o después, el período va del actual al siguiente
        if ($diaActual < 28) {
            // Período: 28 del mes anterior al 27 del mes actual
            $fechaInicio = new DateTime();
            $fechaInicio->setDate($anioActual, $mesActual - 1, 28);
            
            $fechaFin = new DateTime();
            $fechaFin->setDate($anioActual, $mesActual, 27);
            
            $periodoMes = $mesActual;
            $periodoAnio = $anioActual;
        } else {
            // Período: 28 del mes actual al 27 del mes siguiente
            $fechaInicio = new DateTime();
            $fechaInicio->setDate($anioActual, $mesActual, 28);
            
            $fechaFin = new DateTime();
            $fechaFin->setDate($anioActual, $mesActual + 1, 27);
            
            $periodoMes = $mesActual + 1;
            $periodoAnio = $anioActual;
            
            // Manejar diciembre (mes 12 + 1 = enero del año siguiente)
            if ($periodoMes > 12) {
                $periodoMes = 1;
                $periodoAnio = $anioActual + 1;
                $fechaFin->setDate($periodoAnio, $periodoMes, 27);
            }
        }
        
        return [
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_fin' => $fechaFin->format('Y-m-d'),
            'periodo_mes' => $periodoMes,
            'periodo_anio' => $periodoAnio,
            'descripcion' => $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y')
        ];
    }

    /**
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
     * Buscar empleados para Select2 (autocompletado)
     */
    public function buscarEmpleadosSelect2($termino = '', $limit = 10) {
        $sql = "SELECT TOP {$limit} 
                    NRO_LEGAJO as legajo, 
                    NOMBRE as nombre, 
                    APELLIDO as apellido,
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
                'apellido' => $empleado['apellido']
            ];
        }
        
        return $resultado;
    }
    public function buscarEmpleado($legajo) {
        $sql = "SELECT NRO_LEGAJO as legajo, NOMBRE as nombre, APELLIDO as apellido, TAREA_HABITUAL as puesto_actual 
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
                'nombre' => 'Rechazada',
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
            $sql = "SELECT id, codigo, descripcion, cierre, corte
                    FROM tipos_novedad 
                    WHERE activo = 1 
                    ORDER BY id";
        } else {
            // Para otros tipos de usuario, aplicar filtros dinámicamente
            $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
            
            $sql = "SELECT id, codigo, descripcion, cierre, corte
                    FROM tipos_novedad 
                    WHERE activo = 1 AND $campoPermiso = 1 
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
        }
    }
    
    /**
     * Agregar columna user_rrhh si no existe
     */
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
                        sucursal INT NOT NULL,
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
     * Insertar tipos de novedad con permisos - SEGÚN TU SQL
     */
    private function insertarTiposNovedad() {
        // Tipos con permisos exactos según tu SQL ejecutado
        // Formato: [codigo, descripcion, user_adm, user_com, user_prod, user_rrhh]
        // user_rrhh siempre será 1 (puede ver todos los tipos)
        $tipos = [
            ['CAMBIO_SUCURSAL', 'Cambio de sucursal', 1, 1, 0, 1],
            ['NUEVO_PUESTO', 'Nuevo puesto', 1, 1, 0, 1],
            ['NUEVO_SALARIO', 'Nuevo salario neto', 1, 0, 0, 1],
            ['AJUSTE_PREMIOS', 'Ajuste de premios', 1, 1, 0, 1],
            ['HORAS_EXTRAS', 'Horas extras', 1, 1, 1, 1],
            ['HORAS_ADICIONALES', 'Horas adicionales', 1, 1, 1, 1],
            ['PERMISOS', 'Permisos', 1, 1, 1, 1],
            ['CORTES', 'Cortes', 1, 1, 1, 1],
            ['PRODUCCION_25', 'Producción 25%', 0, 0, 1, 1],
            ['PRODUCCION_50', 'Producción 50%', 0, 0, 1, 1],
            ['PRODUCCION_100', 'Producción 100%', 0, 0, 1, 1]
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

            // Incluir PeriodoUtils si no está ya incluido
            if (!class_exists('PeriodoUtils')) {
                require_once __DIR__ . '/PeriodoUtils.php';
            }

            // Calcular período según el tipo de novedad (considerando "Período siguiente")
            $periodo = PeriodoUtils::calcularPeriodoSegunTipoCompleto($tipoNovedadInfo);
            
            // Log para debugging del período calculado
            error_log("Período calculado para tipo {$tipoNovedad}: " . print_r($periodo, true));
            error_log("Tipo novedad info: " . print_r($tipoNovedadInfo, true));
            
            // Adaptar datos según el tipo de novedad y estructura real de tabla
            $datosAdaptados = $this->adaptarDatosParaInsercion($datos);
            
            // Log para debugging - mostrar fechas procesadas
            error_log("Fechas procesadas: fecha_vigencia=" . var_export($datosAdaptados['fecha_vigencia'], true) . 
                     ", fecha_permiso=" . var_export($datosAdaptados['fecha_permiso'], true));
            
            $sql = "INSERT INTO novedades (
                        legajo, nombre, apellido, sucursal, fecha_vigencia, fecha_vigencia_hasta, puesto, 
                        valor_numerico, fecha_permiso, compensa, tipo_permiso, tipo_nuevo_puesto,
                        observaciones, periodo_mes, periodo_anio, tipo_novedad, estado
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $datosAdaptados['legajo'],
                $datosAdaptados['nombre'],
                $datosAdaptados['apellido'],
                $datosAdaptados['sucursal'],
                $datosAdaptados['fecha_vigencia'],
                $datosAdaptados['fecha_vigencia_hasta'],
                $datosAdaptados['puesto'],
                $datosAdaptados['valor_numerico'],
                $datosAdaptados['fecha_permiso'],
                $datosAdaptados['compensa'],
                $datosAdaptados['tipo_permiso'],
                $datosAdaptados['tipo_nuevo_puesto'],
                $datosAdaptados['observaciones'],
                $periodo['month'], // PeriodoUtils devuelve 'month', no 'periodo_mes'
                $periodo['year'],  // PeriodoUtils devuelve 'year', no 'periodo_anio'
                $datosAdaptados['tipo_novedad'],
                self::ESTADO_ENVIADA // Estado inicial por defecto: Enviada
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
        
        $datosAdaptados = [
            'legajo' => $datos['legajo'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'sucursal' => $datos['sucursal'],
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
                $datosAdaptados['fecha_vigencia'] = $this->formatearFecha($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Cambio sucursal';
                if (isset($datos['nueva_sucursal'])) {
                    // Obtener el nombre de la sucursal en lugar del número
                    $mapaSucursales = $this->obtenerMapaSucursales();
                    $nombreSucursal = $mapaSucursales[$datos['nueva_sucursal']] ?? 'Sucursal ' . $datos['nueva_sucursal'];
                    $datosAdaptados['observaciones'] .= " - Nueva sucursal: " . $nombreSucursal;
                }
                break;

            case 2: // Nuevo puesto
                $datosAdaptados['fecha_vigencia'] = $this->formatearFecha($datos['fecha_vigencia'] ?? '');
                
                // Manejar tipo de puesto (permanente/temporario)
                $tipoPuesto = isset($datos['tipo_nuevo_puesto']) ? $datos['tipo_nuevo_puesto'] : 'permanente';
                $datosAdaptados['tipo_nuevo_puesto'] = $tipoPuesto;
                
                // Si es temporario, agregar fecha de fin
                if ($tipoPuesto === 'temporario' && isset($datos['fecha_vigencia_hasta'])) {
                    $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFecha($datos['fecha_vigencia_hasta']);
                }
                
                // Guardar el nuevo puesto en el campo puesto
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : '';
                
                // Agregar detalles en observaciones
                if (isset($datos['puesto'])) {
                    $detalleObservaciones = " - Nuevo puesto: " . $datos['puesto'];
                    $detalleObservaciones .= " (" . ucfirst($tipoPuesto) . ")";
                    
                    if ($tipoPuesto === 'temporario' && isset($datos['fecha_vigencia_hasta'])) {
                        $fechaFinFormateada = date('d/m/Y', strtotime($datos['fecha_vigencia_hasta']));
                        $detalleObservaciones .= " hasta " . $fechaFinFormateada;
                    }
                    
                    $datosAdaptados['observaciones'] .= $detalleObservaciones;
                }
                break;

            case 3: // Nuevo salario neto
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFecha($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Ajuste Salario';
                break;

            case 4: // Ajuste de premios
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFecha($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Premio';
                break;

            case 5: // Horas extras
                // NO calcular valores monetarios - solo guardar cantidad de horas
                if (isset($datos['cantidad_horas'])) {
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_horas']; // Solo la cantidad de horas
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_horas']} horas extras";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFecha($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Horas Extras';
                break;

            case 6: // Horas adicionales
                // NO calcular valores monetarios - solo guardar cantidad de horas
                if (isset($datos['cantidad_horas'])) {
                    $datosAdaptados['valor_numerico'] = (float)$datos['cantidad_horas']; // Solo la cantidad de horas
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_horas']} horas adicionales";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFecha($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Horas Adicionales';
                break;

            case 7: // Permisos
                $datosAdaptados['fecha_permiso'] = $this->formatearFecha($datos['fecha_permiso'] ?? '');
                $datosAdaptados['compensa'] = isset($datos['compensa']) ? (bool)$datos['compensa'] : null;
                $datosAdaptados['puesto'] = 'Permiso';
                break;

            case 8: // Cortes
                // NO calcular valores monetarios - solo guardar cantidad de cortes
                if (isset($datos['cantidad_cortes'])) {
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_cortes']; // Solo la cantidad de cortes
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_cortes']} cortes";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFecha($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Cortes';
                break;

            case 9: // Producción 25%
                // NO calcular valores monetarios - solo guardar cantidad de unidades
                if (isset($datos['cantidad_unidades'])) {
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_unidades']; // Solo la cantidad de unidades
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_unidades']} unidades (25%)";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFecha($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 25%';
                break;

            case 10: // Producción 50%
                // NO calcular valores monetarios - solo guardar cantidad de unidades
                if (isset($datos['cantidad_unidades'])) {
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_unidades']; // Solo la cantidad de unidades
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_unidades']} unidades (50%)";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFecha($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Producción 50%';
                break;

            case 11: // Producción 100%
                // NO calcular valores monetarios - solo guardar cantidad de unidades
                if (isset($datos['cantidad_unidades'])) {
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_unidades']; // Solo la cantidad de unidades
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_unidades']} unidades (100%)";
                }
                $datosAdaptados['fecha_vigencia'] = $this->formatearFecha($datos['fecha_vigencia'] ?? '');
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

            case 2: // Nuevo puesto
                // Remover cualquier mención de "Nuevo puesto:"
                $observacionesLimpias = preg_replace('/ - Nuevo puesto: [^-]*/', '', $observacionesLimpias);
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
                $sql = "SELECT n.*, tn.descripcion as tipo_descripcion, 
                               rle.NOMBRE, rle.APELLIDO,
                               n.periodo_mes, n.periodo_anio, n.estado
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                        WHERE n.periodo_mes = ? AND n.periodo_anio = ?";
            } else {
                // Para otros tipos de usuario, aplicar filtro específico dinámicamente
                $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
                
                $sql = "SELECT n.*, tn.descripcion as tipo_descripcion, 
                               rle.NOMBRE, rle.APELLIDO,
                               n.periodo_mes, n.periodo_anio, n.estado
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                        WHERE n.periodo_mes = ? AND n.periodo_anio = ? AND tn.$campoPermiso = 1";
            }
            
            $params = [$periodo['periodo_mes'], $periodo['periodo_anio']];

            // Filtros adicionales
            if (!empty($filtros['legajo'])) {
                $sql .= " AND n.legajo = ?";
                $params[] = $filtros['legajo'];
            }

            if (!empty($filtros['sucursal'])) {
                $sql .= " AND n.sucursal = ?";
                $params[] = $filtros['sucursal'];
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
                // Agregar nombre de sucursal
                $sucursalesMap = $this->obtenerMapaSucursales();
                $novedad['nombre_sucursal'] = $sucursalesMap[$novedad['sucursal']] ?? 'Sucursal ' . $novedad['sucursal'];
                
                // Agregar valor display basado en tipo de novedad - SIN valores monetarios ficticios
                $tipoNovedad = (int)$novedad['tipo_novedad'];
                $valorNumerico = (float)$novedad['valor_numerico'];
                
                switch ($tipoNovedad) {
                    case 1: // Cambio de sucursal
                        $novedad['valor_display'] = 'Cambio de sucursal';
                        break;
                    case 2: // Nuevo puesto
                        $novedad['valor_display'] = !empty($novedad['puesto']) ? $novedad['puesto'] : 'Nuevo puesto';
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
                        $fechaClave = $fechaVigencia;
                    } catch (Exception $e) {
                        $fechaVigencia = null;
                    }
                } elseif (!empty($novedad['fecha_permiso'])) {
                    try {
                        if ($novedad['fecha_permiso'] instanceof DateTime) {
                            $fechaClave = $novedad['fecha_permiso']->format('d/m/Y');
                        } else {
                            $fechaClave = date('d/m/Y', strtotime($novedad['fecha_permiso']));
                        }
                    } catch (Exception $e) {
                        $fechaClave = null;
                    }
                }
                
                // Asignar fechas procesadas
                $novedad['fecha_registro'] = $fechaRegistro ?: 'N/A';
                $novedad['fecha_display'] = $fechaClave ?: $fechaRegistro ?: 'N/A';

                // Normalizar campos de fecha crudos a string ISO para JSON limpio
                foreach (['fecha_creacion','fecha_vigencia','fecha_permiso'] as $campoF) {
                    if (!empty($novedad[$campoF])) {
                        if ($novedad[$campoF] instanceof DateTime) {
                            $novedad[$campoF] = $novedad[$campoF]->format('Y-m-d H:i:s');
                        }
                    }
                }
                
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
            'Rechazada' => 4,
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
                $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.sucursal, n.valor_numerico, 
                               n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                               n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                               n.periodo_mes, n.periodo_anio, n.estado,
                               tn.descripcion as tipo_descripcion, 
                               rle.NOMBRE as nombre, rle.APELLIDO as apellido
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                        WHERE 1=1";
            } else {
                // Para otros tipos de usuario, aplicar filtro específico dinámicamente
                $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
                
                $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.sucursal, n.valor_numerico, 
                               n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                               n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                               n.periodo_mes, n.periodo_anio, n.estado,
                               tn.descripcion as tipo_descripcion, 
                               rle.NOMBRE as nombre, rle.APELLIDO as apellido
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                        WHERE tn.$campoPermiso = 1";
            }
            
            $params = [];

            // Filtros adicionales
            if (!empty($filtros['legajo'])) {
                $sql .= " AND n.legajo = ?";
                $params[] = $filtros['legajo'];
            }

            if (!empty($filtros['sucursal'])) {
                $sql .= " AND n.sucursal = ?";
                $params[] = $filtros['sucursal'];
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
                // Agregar nombre de sucursal
                $sucursalesMap = $this->obtenerMapaSucursales();
                $novedad['nombre_sucursal'] = $sucursalesMap[$novedad['sucursal']] ?? 'Sucursal ' . $novedad['sucursal'];
                
                // Agregar valor display basado en tipo de novedad
                $tipoNovedad = (int)$novedad['tipo_novedad'];
                $valorNumerico = (float)$novedad['valor_numerico'];
                
                switch ($tipoNovedad) {
                    case 1: // Cambio de sucursal
                        $novedad['valor_display'] = 'Cambio de sucursal';
                        break;
                    case 2: // Nuevo puesto
                        $novedad['valor_display'] = !empty($novedad['puesto']) ? $novedad['puesto'] : 'Nuevo puesto';
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
                        $fechaClave = $fechaVigencia;
                    } catch (Exception $e) {
                        $fechaVigencia = null;
                    }
                } elseif (!empty($novedad['fecha_permiso'])) {
                    try {
                        if ($novedad['fecha_permiso'] instanceof DateTime) {
                            $fechaClave = $novedad['fecha_permiso']->format('d/m/Y');
                        } else {
                            $fechaClave = date('d/m/Y', strtotime($novedad['fecha_permiso']));
                        }
                    } catch (Exception $e) {
                        $fechaClave = null;
                    }
                }
                
                // Asignar fechas procesadas
                $novedad['fecha_registro'] = $fechaRegistro ?: 'N/A';
                $novedad['fecha_display'] = $fechaClave ?: $fechaRegistro ?: 'N/A';

                // Normalizar campos de fecha crudos a string ISO para JSON limpio
                foreach (['fecha_creacion','fecha_vigencia','fecha_permiso'] as $campoF) {
                    if (!empty($novedad[$campoF])) {
                        if ($novedad[$campoF] instanceof DateTime) {
                            $novedad[$campoF] = $novedad[$campoF]->format('Y-m-d H:i:s');
                        }
                    }
                }
                
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
     * Obtener novedad por ID con detalle completo - RESPETA PERMISOS DE USUARIO
     */
    public function getNovedadById($id) {
        $tipoUsuario = Usuario::getTipoUsuario();
        
        // Si es usuario RRHH, puede ver cualquier novedad
        if ($tipoUsuario == Usuario::TIPO_RRHH) {
            $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.sucursal, n.valor_numerico, 
                           n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                           n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                           n.periodo_mes, n.periodo_anio, n.estado,
                           tn.descripcion as tipo_descripcion,
                           emp.NOMBRE as nombre, emp.APELLIDO as apellido
                    FROM novedades n 
                    INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                    LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL emp ON n.legajo = emp.NRO_LEGAJO
                    WHERE n.id = ?";
        } else {
            // Para otros tipos de usuario, validar permisos específicos dinámicamente
            $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
            
            $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.sucursal, n.valor_numerico, 
                           n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                           n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                           n.periodo_mes, n.periodo_anio, n.estado,
                           tn.descripcion as tipo_descripcion,
                           emp.NOMBRE as nombre, emp.APELLIDO as apellido
                    FROM novedades n 
                    INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                    LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL emp ON n.legajo = emp.NRO_LEGAJO
                    WHERE n.id = ? AND tn.$campoPermiso = 1";
        }

        $stmt = $this->db->query($sql, [$id]);
        $novedad = $stmt->fetch();

        if ($novedad) {
            // Nombre sucursal seguro
            try {
                $mapa = $this->obtenerMapaSucursales();
                $novedad['nombre_sucursal'] = $mapa[$novedad['sucursal']] ?? ('Sucursal ' . $novedad['sucursal']);
            } catch (Exception $e) {
                $novedad['nombre_sucursal'] = 'Sucursal ' . $novedad['sucursal'];
            }

            // Fechas seguras - Convertir DateTime a string antes de JSON
            foreach (['fecha_vigencia','fecha_permiso','fecha_creacion'] as $campoFecha) {
                if (!empty($novedad[$campoFecha])) {
                    if ($novedad[$campoFecha] instanceof DateTime) {
                        $novedad[$campoFecha] = $novedad[$campoFecha]->format('Y-m-d H:i:s');
                    }
                    // Formatear para mostrar
                    $raw = $novedad[$campoFecha];
                    $str = (string)$raw;
                    $ts = strtotime($str);
                    if ($ts) {
                        $novedad[$campoFecha . '_formateada'] = ($campoFecha === 'fecha_creacion') ? date('d/m/Y H:i', $ts) : date('d/m/Y', $ts);
                    }
                }
            }

            // Agregar información de período
            $periodo = $this->getPeriodoActual();
            $novedad['periodo_mes'] = $periodo['mes'];
            $novedad['periodo_anio'] = $periodo['anio'];

            // Valor display
            $novedad['valor_display'] = $this->construirValorDisplay((int)$novedad['tipo_novedad'], $novedad['valor_numerico']);

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
                $detalle['sucursal_actual'] = $novedad['sucursal'];
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                }
                // Extraer nueva sucursal de observaciones si existe
                if (preg_match('/Nueva sucursal:\s*(.+)/', $novedad['observaciones'], $matches)) {
                    $detalle['nueva_sucursal'] = trim($matches[1]);
                }
                break;

            case 2: // Nuevo puesto
                $detalle['tipo'] = 'Nuevo puesto';
                $detalle['nuevo_puesto'] = $novedad['puesto'];
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                }
                break;

            case 3: // Nuevo salario neto
                $detalle['tipo'] = 'Nuevo salario neto';
                $detalle['nuevo_salario'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                }
                break;

            case 4: // Ajuste de premios
                $detalle['tipo'] = 'Ajuste de premios';
                $detalle['monto_premio'] = $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2) : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                }
                break;

            case 5: // Horas extras
                $detalle['tipo'] = 'Horas extras';
                $detalle['cantidad_horas'] = $valorNumerico != 0 ? $valorNumerico . ' horas' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                }
                break;

            case 6: // Horas adicionales
                $detalle['tipo'] = 'Horas adicionales';
                $detalle['cantidad_horas'] = $valorNumerico != 0 ? $valorNumerico . ' horas' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                }
                break;

            case 7: // Permisos
                $detalle['tipo'] = 'Permiso';
                $detalle['compensa'] = $novedad['compensa'] ? 'Sí' : 'No';
                if (!empty($novedad['fecha_permiso'])) {
                    $detalle['fecha_permiso'] = date('d/m/Y', strtotime($novedad['fecha_permiso']));
                }
                break;

            case 8: // Cortes
                $detalle['tipo'] = 'Cortes';
                $detalle['cantidad_cortes'] = $valorNumerico != 0 ? $valorNumerico . ' cortes' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                }
                break;

            case 9: // Producción 25%
                $detalle['tipo'] = 'Producción 25%';
                $detalle['cantidad_unidades'] = $valorNumerico != 0 ? $valorNumerico . ' unidades' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                }
                break;

            case 10: // Producción 50%
                $detalle['tipo'] = 'Producción 50%';
                $detalle['cantidad_unidades'] = $valorNumerico != 0 ? $valorNumerico . ' unidades' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                }
                break;

            case 11: // Producción 100%
                $detalle['tipo'] = 'Producción 100%';
                $detalle['cantidad_unidades'] = $valorNumerico != 0 ? $valorNumerico . ' unidades' : 'No especificado';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = date('d/m/Y', strtotime($novedad['fecha_vigencia']));
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

        if (empty($datos['sucursal'])) {
            $errores[] = 'La sucursal es obligatoria';
        }

        // Validaciones específicas por tipo de novedad
        $tipoNovedad = (int)$datos['tipo_novedad'];
        
        switch($tipoNovedad) {
            case 1: // Cambio de sucursal
                if (empty($datos['nueva_sucursal'])) {
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
        }

        return $errores;
    }

    /**
     * Formatear fecha para SQL Server
     * Convierte fecha HTML (YYYY-MM-DD) a formato compatible con SQL Server
     * Retorna NULL si la fecha está vacía o es inválida
     */
    private function formatearFecha($fecha) {
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
            $datosAdaptados = $this->adaptarDatosParaInsercion($datos);
            $periodo = $this->getPeriodoActual();

            // Preparar SQL de actualización
            $sql = "UPDATE novedades SET 
                        legajo = ?, nombre = ?, apellido = ?, sucursal = ?, 
                        fecha_vigencia = ?, fecha_vigencia_hasta = ?, puesto = ?, valor_numerico = ?, 
                        fecha_permiso = ?, compensa = ?, tipo_permiso = ?, tipo_nuevo_puesto = ?,
                        observaciones = ?, tipo_novedad = ?, fecha_modificacion = GETDATE()
                    WHERE id = ?";

            $params = [
                $datosAdaptados['legajo'],
                $datosAdaptados['nombre'],
                $datosAdaptados['apellido'],
                $datosAdaptados['sucursal'],
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
}
?>