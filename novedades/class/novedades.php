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
                WHERE 1=1";
        
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
        $sql = "SELECT NRO_LEGAJO as legajo, NOMBRE as nombre, APELLIDO as apellido 
                FROM [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL 
                WHERE NRO_LEGAJO = ?";
        $stmt = $this->db->query($sql, [$legajo]);
        return $stmt->fetch();
    }

    /**
     * Obtener tipos de novedad activos - FILTRADOS DIRECTO DESDE BD
     */
    public function getTiposNovedad() {
        $tipoUsuario = Usuario::getTipoUsuario();
        
        // Construir la condición WHERE según el tipo de usuario
        $campoPermiso = '';
        switch($tipoUsuario) {
            case 1: $campoPermiso = 'user_adm'; break;
            case 2: $campoPermiso = 'user_com'; break; 
            case 3: $campoPermiso = 'user_prod'; break;
            default: $campoPermiso = 'user_adm'; break;
        }
        
        $sql = "SELECT id, codigo, descripcion 
                FROM tipos_novedad 
                WHERE activo = 1 AND $campoPermiso = 1 
                ORDER BY id";
        
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
                    user_prod BIT DEFAULT 0
                )";
        
        $this->db->query($sql);
        
        // Si la tabla está vacía, insertar tipos básicos
        $checkSql = "SELECT COUNT(*) as count FROM tipos_novedad";
        $result = $this->db->query($checkSql);
        $row = $result->fetch();
        
        if ($row['count'] == 0) {
            $this->insertarTiposNovedad();
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
                        fecha_creacion DATETIME DEFAULT GETDATE(),
                        fecha_modificacion DATETIME NULL
                    )";
            $this->db->query($sql);
        }
    }

    /**
     * Insertar tipos de novedad con permisos - SEGÚN TU SQL
     */
    private function insertarTiposNovedad() {
        // Tipos con permisos exactos según tu SQL ejecutado
        $tipos = [
            ['CAMBIO_SUCURSAL', 'Cambio de sucursal', 1, 1, 0],
            ['NUEVO_PUESTO', 'Nuevo puesto', 1, 1, 0],
            ['NUEVO_SALARIO', 'Nuevo salario neto', 1, 0, 0],
            ['AJUSTE_PREMIOS', 'Ajuste de premios', 1, 1, 0],
            ['HORAS_EXTRAS', 'Horas extras', 1, 1, 1],
            ['HORAS_ADICIONALES', 'Horas adicionales', 1, 1, 1],
            ['PERMISOS', 'Permisos', 1, 1, 1],
            ['CORTES', 'Cortes', 1, 1, 1],
            ['PRODUCCION_25', 'Producción 25%', 0, 0, 1],
            ['PRODUCCION_50', 'Producción 50%', 0, 0, 1],
            ['PRODUCCION_100', 'Producción 100%', 0, 0, 1]
        ];

        foreach ($tipos as $tipo) {
            $sql = "INSERT INTO tipos_novedad (codigo, descripcion, user_adm, user_com, user_prod) VALUES (?, ?, ?, ?, ?)";
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

            $periodo = $this->getPeriodoActual();
            
            // Adaptar datos según el tipo de novedad y estructura real de tabla
            $datosAdaptados = $this->adaptarDatosParaInsercion($datos);
            
            // Log para debugging - mostrar fechas procesadas
            error_log("Fechas procesadas: fecha_vigencia=" . var_export($datosAdaptados['fecha_vigencia'], true) . 
                     ", fecha_permiso=" . var_export($datosAdaptados['fecha_permiso'], true));
            
            $sql = "INSERT INTO novedades (
                        legajo, nombre, apellido, sucursal, fecha_vigencia, puesto, 
                        valor_numerico, fecha_permiso, compensa, tipo_permiso,
                        observaciones, periodo_mes, periodo_anio, tipo_novedad
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $datosAdaptados['legajo'],
                $datosAdaptados['nombre'],
                $datosAdaptados['apellido'],
                $datosAdaptados['sucursal'],
                $datosAdaptados['fecha_vigencia'],
                $datosAdaptados['puesto'],
                $datosAdaptados['valor_numerico'],
                $datosAdaptados['fecha_permiso'],
                $datosAdaptados['compensa'],
                $datosAdaptados['tipo_permiso'],
                $datosAdaptados['observaciones'],
                $periodo['periodo_mes'],
                $periodo['periodo_anio'],
                $datosAdaptados['tipo_novedad']
            ];

            // Log para debugging - mostrar parámetros SQL
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
        
        // Construir el nombre del campo según el tipo de usuario
        $campoPermiso = '';
        switch($tipoUsuario) {
            case 1: $campoPermiso = 'user_adm'; break;
            case 2: $campoPermiso = 'user_com'; break;
            case 3: $campoPermiso = 'user_prod'; break;
            default: throw new Exception("Tipo de usuario no válido");
        }
        
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
     * Adaptar datos según tipo de novedad a estructura real de tabla
     */
    private function adaptarDatosParaInsercion($datos) {
        $tipoNovedad = (int)$datos['tipo_novedad'];
        
        $datosAdaptados = [
            'legajo' => $datos['legajo'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'sucursal' => $datos['sucursal'],
            'tipo_novedad' => $tipoNovedad,
            'observaciones' => isset($datos['observaciones']) ? $datos['observaciones'] : '',
            'fecha_vigencia' => null,
            'puesto' => '',
            'valor_numerico' => null,
            'fecha_permiso' => null,
            'compensa' => null,
            'tipo_permiso' => null
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
                // Guardar el nuevo puesto en el campo puesto
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : '';
                if (isset($datos['puesto'])) {
                    $datosAdaptados['observaciones'] .= " - Nuevo puesto: " . $datos['puesto'];
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
     * Obtener novedades del periodo actual - CON NOMBRES DE SUCURSAL REALES Y FILTRO POR TIPO DE USUARIO
     */
    public function getNovedadesPeriodoActual($filtros = []) {
        try {
            $periodo = $this->getPeriodoActual();
            $tipoUsuario = Usuario::getTipoUsuario();
            
            // Determinar campo de permiso según tipo de usuario
            $campoPermiso = '';
            switch($tipoUsuario) {
                case 1: $campoPermiso = 'user_adm'; break;
                case 2: $campoPermiso = 'user_com'; break; 
                case 3: $campoPermiso = 'user_prod'; break;
                default: $campoPermiso = 'user_adm'; break;
            }
            
            // Consulta con filtro por tipo de usuario
            $sql = "SELECT n.*, tn.descripcion as tipo_descripcion, 
                           rle.NOMBRE, rle.APELLIDO,
                           n.periodo_mes, n.periodo_anio
                    FROM novedades n 
                    INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                    LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                    WHERE n.periodo_mes = ? AND n.periodo_anio = ? AND tn.$campoPermiso = 1";
            
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
            }
            
            return $novedades;
            
        } catch (Exception $e) {
            error_log("Error en getNovedadesPeriodoActual: " . $e->getMessage());
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
        
        // Determinar campo de permiso según tipo de usuario
        $campoPermiso = '';
        switch($tipoUsuario) {
            case 1: $campoPermiso = 'user_adm'; break;
            case 2: $campoPermiso = 'user_com'; break; 
            case 3: $campoPermiso = 'user_prod'; break;
            default: $campoPermiso = 'user_adm'; break;
        }
        
        $sql = "SELECT n.*, tn.descripcion as tipo_descripcion,
                       emp.NOMBRE as nombre, emp.APELLIDO as apellido
                FROM novedades n 
                INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL emp ON n.legajo = emp.NRO_LEGAJO
                WHERE n.id = ? AND tn.$campoPermiso = 1";

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
}
?>