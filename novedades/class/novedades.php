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
     * Obtener información completa de un empleado (deduplicado por ingreso más reciente)
     */
    public function getEmpleadoInfo($legajo) {
        try {
            $sql = "SELECT TOP 1
                        e.NRO_LEGAJO as legajo,
                        LTRIM(RTRIM(e.NOMBRE)) as nombre,
                        LTRIM(RTRIM(e.APELLIDO)) as apellido,
                        e.COD_DEPARTAMENTO as codigo_centro_costos,
                        e.DESC_DEPARTAMENTO as descripcion_centro_costos,
                        LTRIM(RTRIM(e.TAREA_HABITUAL)) as puesto_actual,
                        e.FECHA_INGRESO as fecha_ingreso
                    FROM [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL e
                    WHERE e.NRO_LEGAJO = ?
                    ORDER BY CASE WHEN e.HABILITADO = 'S' THEN 0 ELSE 1 END, e.FECHA_INGRESO DESC";
            
            $result = $this->db->query($sql, [$legajo]);
            $empleado = $result->fetch();
            
            if (!$empleado) {
                throw new Exception("No se encontró empleado con legajo: $legajo");
            }
            
            // Obtener información de la sucursal
            try {
                $sucursalDesc = $this->getSucursalPorCentroCostos($empleado['codigo_centro_costos']);
                $numeroSucursal = null;
                if (!empty($empleado['codigo_centro_costos']) && preg_match('/^LOC(\d+)$/i', trim($empleado['codigo_centro_costos']), $m)) {
                    $numeroSucursal = (int)$m[1];
                }
                $empleado['sucursal_numero'] = $numeroSucursal;
                $empleado['sucursal'] = $sucursalDesc ?: ($empleado['descripcion_centro_costos'] ?? 'No especificado');
            } catch (Exception $e) {
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
     * Obtener sucursal asociada a un centro de costos (sin depender del linked server TANGO-SUELDOS)
     */
    public function getSucursalPorCentroCostos($codigoCentroCostos) {
        try {
            if (empty($codigoCentroCostos)) {
                return null;
            }
            $codigoCentroCostos = trim($codigoCentroCostos);

            if (preg_match('/^LOC(\d+)$/i', $codigoCentroCostos, $m)) {
                $nroSucursal = (int)$m[1];
                $sql = "SELECT TOP 1 DESC_SUCURSAL as descripcion
                        FROM [LAKERBIS].locales_lakers.dbo.SUCURSALES_LAKERS
                        WHERE NRO_SUCURSAL = ? AND CANAL = 'PROPIOS'";
                $row = $this->db->query($sql, [$nroSucursal])->fetch();
                if ($row && !empty($row['descripcion'])) {
                    return $row['descripcion'];
                }
            }

            $sqlEmp = "SELECT TOP 1 DESC_DEPARTAMENTO as descripcion
                       FROM [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL
                       WHERE COD_DEPARTAMENTO = ? AND DESC_DEPARTAMENTO IS NOT NULL";
            $rowEmp = $this->db->query($sqlEmp, [$codigoCentroCostos])->fetch();
            if ($rowEmp && !empty($rowEmp['descripcion'])) {
                return $rowEmp['descripcion'];
            }

            return null;
        } catch (Exception $e) {
            error_log("Error obteniendo sucursal para centro de costos $codigoCentroCostos: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los centros de costos únicos de empleados activos
     */
    public function getCentrosCostos() {
        $sql = "SELECT DISTINCT COD_DEPARTAMENTO as codigo, DESC_DEPARTAMENTO as descripcion 
                FROM [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL 
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
     * Buscar empleados para Select2 (deduplicado por legajo, tomando el ingreso más reciente)
     */
    public function buscarEmpleadosSelect2($termino = '', $limit = 10) {
        $limit = max(1, (int)$limit);
        $sql = "WITH EmpleadosDeduplicados AS (
                    SELECT
                        NRO_LEGAJO as legajo,
                        LTRIM(RTRIM(NOMBRE)) as nombre,
                        LTRIM(RTRIM(APELLIDO)) as apellido,
                        COD_DEPARTAMENTO as cod_centro_costos,
                        DESC_DEPARTAMENTO as desc_centro_costos,
                        LTRIM(RTRIM(TAREA_HABITUAL)) as puesto_actual,
                        FECHA_INGRESO as fecha_ingreso,
                        ROW_NUMBER() OVER (PARTITION BY NRO_LEGAJO ORDER BY FECHA_INGRESO DESC) as rn
                    FROM [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL
                    WHERE HABILITADO = 'S'
                )
                SELECT TOP {$limit}
                    legajo,
                    nombre,
                    apellido,
                    cod_centro_costos,
                    desc_centro_costos,
                    puesto_actual,
                    fecha_ingreso,
                    CONCAT(nombre, ' ', apellido, ' (', legajo, ')') as texto_completo
                FROM EmpleadosDeduplicados
                WHERE rn = 1";
        
        $params = [];
        
        if (!empty($termino)) {
            $sql .= " AND (
                        CONCAT(nombre, ' ', apellido) LIKE ? 
                        OR CONCAT(apellido, ' ', nombre) LIKE ?
                        OR CAST(legajo AS VARCHAR) LIKE ?
                        OR nombre LIKE ?
                        OR apellido LIKE ?
                      )";
            $terminoBusqueda = "%{$termino}%";
            $params = [$terminoBusqueda, $terminoBusqueda, $terminoBusqueda, $terminoBusqueda, $terminoBusqueda];
        }
        
        $sql .= " ORDER BY nombre, apellido";
        
        $stmt = $this->db->query($sql, $params);
        $empleados = $stmt->fetchAll();
        
        // Formatear para Select2
        $resultado = [];
        foreach ($empleados as $empleado) {
            $sucursalNumero = null;
            if (!empty($empleado['cod_centro_costos']) && preg_match('/^LOC(\d+)$/i', trim($empleado['cod_centro_costos']), $m)) {
                $sucursalNumero = (int)$m[1];
            }
            $resultado[] = [
                'id' => $empleado['legajo'],
                'text' => $empleado['texto_completo'],
                'legajo' => $empleado['legajo'],
                'nombre' => $empleado['nombre'],
                'apellido' => $empleado['apellido'],
                'cod_centro_costos' => $empleado['cod_centro_costos'],
                'desc_centro_costos' => $empleado['desc_centro_costos'],
                'puesto_actual' => $empleado['puesto_actual'] ?? '',
                'sucursal_numero' => $sucursalNumero,
                'fecha_ingreso' => $this->formatearFecha($empleado['fecha_ingreso'] ?? null)
            ];
        }
        
        return $resultado;
    }

    public function buscarEmpleado($legajo) {
        $sql = "SELECT TOP 1
                       NRO_LEGAJO as legajo,
                       LTRIM(RTRIM(NOMBRE)) as nombre,
                       LTRIM(RTRIM(APELLIDO)) as apellido,
                       LTRIM(RTRIM(TAREA_HABITUAL)) as puesto_actual,
                       COD_DEPARTAMENTO as cod_centro_costos,
                       DESC_DEPARTAMENTO as desc_centro_costos,
                       FECHA_INGRESO as fecha_ingreso
                FROM [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL 
                WHERE NRO_LEGAJO = ? AND HABILITADO = 'S'
                ORDER BY FECHA_INGRESO DESC";
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
     * Obtener puestos disponibles directamente desde TAREA_HABITUAL en Tango Sueldos
     * Unifica la fuente entre modo único, modo múltiple y edición (ej. "SUB ENCARGADA")
     */
    public function getPuestosDisponibles() {
        $sql = "SELECT DISTINCT LTRIM(RTRIM(TAREA_HABITUAL)) as nombre_puesto
                FROM [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL
                WHERE HABILITADO = 'S'
                  AND TAREA_HABITUAL IS NOT NULL
                  AND LTRIM(RTRIM(TAREA_HABITUAL)) != ''
                ORDER BY nombre_puesto";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Buscar puestos desde TAREA_HABITUAL para Select2
     */
    public function buscarPuestosSelect2($termino = '', $limit = 50) {
        $limit = max(1, (int)$limit);
        $sql = "SELECT DISTINCT TOP {$limit} 
                    LTRIM(RTRIM(TAREA_HABITUAL)) as puesto
                FROM [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL 
                WHERE HABILITADO = 'S' 
                AND TAREA_HABITUAL IS NOT NULL 
                AND LTRIM(RTRIM(TAREA_HABITUAL)) != ''";
        
        $params = [];
        
        if (!empty($termino)) {
            $sql .= " AND LTRIM(RTRIM(TAREA_HABITUAL)) LIKE ?";
            $params = ["%{$termino}%"];
        }
        
        $sql .= " ORDER BY LTRIM(RTRIM(TAREA_HABITUAL))";
        
        $stmt = $this->db->query($sql, $params);
        $puestos = $stmt->fetchAll();
        
        // Formatear para Select2
        $resultado = [];
        foreach ($puestos as $puesto) {
            $nombre = trim($puesto['puesto']);
            if (!empty($nombre)) {
                $resultado[] = [
                    'id' => $nombre,
                    'text' => $nombre
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
        // Los tipos activos se administran desde Gestión de Tipos / migraciones SQL.
        return;
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
                        LEFT JOIN [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL e 
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
            $empSubquery = $this->getSubqueryEmpleadosUnicos('rle');
            
            // Si es usuario RRHH, puede ver todas las novedades
            if ($tipoUsuario == Usuario::TIPO_RRHH) {
                $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                               n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                               n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                               n.periodo_mes, n.periodo_anio, n.estado, n.es_global,
                               tn.descripcion as tipo_descripcion, 
                               CASE WHEN n.es_global = 1 THEN 'TODOS LOS LOCALES' ELSE COALESCE(rle.NOMBRE, n.nombre) END as NOMBRE,
                               CASE WHEN n.es_global = 1 THEN '' ELSE COALESCE(rle.APELLIDO, n.apellido) END as APELLIDO,
                               CASE WHEN n.es_global = 1 THEN 'TODOS' ELSE COALESCE(rle.COD_DEPARTAMENTO, n.centro_costos) END as codigo_centro_costos,
                               CASE WHEN n.es_global = 1 THEN 'Todos los locales' ELSE rle.DESC_DEPARTAMENTO END as descripcion_centro_costos
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN {$empSubquery} ON n.legajo = rle.NRO_LEGAJO AND rle.rn = 1 AND ISNULL(n.es_global, 0) = 0
                        WHERE n.periodo_mes = ? AND n.periodo_anio = ?";
            } else {
                // Para otros tipos de usuario, aplicar filtro específico dinámicamente
                $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
                
                $sql = "SELECT n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                               n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                               n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                               n.periodo_mes, n.periodo_anio, n.estado, n.es_global,
                               tn.descripcion as tipo_descripcion, 
                               CASE WHEN n.es_global = 1 THEN 'TODOS LOS LOCALES' ELSE COALESCE(rle.NOMBRE, n.nombre) END as NOMBRE,
                               CASE WHEN n.es_global = 1 THEN '' ELSE COALESCE(rle.APELLIDO, n.apellido) END as APELLIDO,
                               CASE WHEN n.es_global = 1 THEN 'TODOS' ELSE COALESCE(rle.COD_DEPARTAMENTO, n.centro_costos) END as codigo_centro_costos,
                               CASE WHEN n.es_global = 1 THEN 'Todos los locales' ELSE rle.DESC_DEPARTAMENTO END as descripcion_centro_costos
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN {$empSubquery} ON n.legajo = rle.NRO_LEGAJO AND rle.rn = 1 AND ISNULL(n.es_global, 0) = 0
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
     * Subconsulta SQL deduplicada de empleados por NRO_LEGAJO (prioriza activo y último ingreso)
     */
    private function getSubqueryEmpleadosUnicos($alias = 'rle') {
        return "(
            SELECT NRO_LEGAJO,
                   LTRIM(RTRIM(NOMBRE)) COLLATE DATABASE_DEFAULT AS NOMBRE,
                   LTRIM(RTRIM(APELLIDO)) COLLATE DATABASE_DEFAULT AS APELLIDO,
                   COD_DEPARTAMENTO COLLATE DATABASE_DEFAULT AS COD_DEPARTAMENTO,
                   DESC_DEPARTAMENTO COLLATE DATABASE_DEFAULT AS DESC_DEPARTAMENTO,
                   LTRIM(RTRIM(TAREA_HABITUAL)) COLLATE DATABASE_DEFAULT AS TAREA_HABITUAL,
                   FECHA_INGRESO, HABILITADO,
                   ROW_NUMBER() OVER (
                       PARTITION BY NRO_LEGAJO
                       ORDER BY CASE WHEN HABILITADO = 'S' THEN 0 ELSE 1 END, FECHA_INGRESO DESC
                   ) AS rn
            FROM [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL
        ) {$alias}";
    }

    /**
     * Obtener sucursales de comisión individual agrupadas por novedad_id
     */
    private function obtenerMapaComisionSucursales($novedadIds) {
        if (empty($novedadIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($novedadIds), '?'));
        $sql = "SELECT id, novedad_id, nro_sucursal, nombre_sucursal, porcentaje_1, tiene_tope, porcentaje_2
                FROM FP_NOVEDADES_COMISION_SUCURSALES
                WHERE novedad_id IN ($placeholders)
                ORDER BY nombre_sucursal";
        $rows = $this->db->query($sql, array_values($novedadIds))->fetchAll();
        $mapa = [];
        foreach ($rows as $row) {
            $nid = (int)$row['novedad_id'];
            $row['porcentaje_1_pct'] = $row['porcentaje_1'] !== null ? round((float)$row['porcentaje_1'] * 100, 2) : null;
            $row['porcentaje_2_pct'] = $row['porcentaje_2'] !== null ? round((float)$row['porcentaje_2'] * 100, 2) : null;
            $row['tiene_tope'] = !empty($row['tiene_tope']) ? 1 : 0;
            $mapa[$nid][] = $row;
        }
        return $mapa;
    }

    /**
     * Obtener escalas de Premio Local agrupadas por novedad_id
     */
    private function obtenerMapaPremioLocalEscala($novedadIds) {
        if (empty($novedadIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($novedadIds), '?'));
        $sql = "SELECT id, novedad_id, nivel_codigo, nivel_orden, nivel_descripcion, importe_enc_sub, importe_vend
                FROM FP_NOVEDADES_PREMIO_LOCAL_ESCALA
                WHERE novedad_id IN ($placeholders)
                ORDER BY nivel_orden ASC";
        $rows = $this->db->query($sql, array_values($novedadIds))->fetchAll();
        $mapa = [];
        foreach ($rows as $row) {
            $nid = (int)$row['novedad_id'];
            $mapa[$nid][] = $row;
        }
        return $mapa;
    }

    /**
     * Obtener TODAS las novedades sin filtro de período (para consulta general)
     */
    public function getAllNovedades($filtros = []) {
        try {
            $tipoUsuario = Usuario::getTipoUsuario();
            $empSubquery = $this->getSubqueryEmpleadosUnicos('rle');
            
            $selectCols = "n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                           n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                           n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                           n.periodo_mes, n.periodo_anio, n.estado,
                           n.tiene_tope, n.porcentaje_1, n.porcentaje_2, n.tipo_comision,
                           n.aplica_vendedora, n.aplica_sub_encargada, n.aplica_encargada,
                           n.es_global, n.lote_id, n.etapa_multiple, n.convenio, n.escala_sueldo_basico, n.aplica_plus,
                           tn.descripcion as tipo_descripcion,
                           CASE WHEN n.es_global = 1 THEN 'TODOS LOS LOCALES' ELSE COALESCE(rle.NOMBRE, n.nombre) END as nombre,
                           CASE WHEN n.es_global = 1 THEN '' ELSE COALESCE(rle.APELLIDO, n.apellido) END as apellido,
                           CASE WHEN n.es_global = 1 THEN 'TODOS' ELSE COALESCE(rle.COD_DEPARTAMENTO, n.centro_costos) END as codigo_centro_costos,
                           CASE WHEN n.es_global = 1 THEN 'Todos los locales' ELSE rle.DESC_DEPARTAMENTO END as descripcion_centro_costos";

            if ($tipoUsuario == Usuario::TIPO_RRHH) {
                $sql = "SELECT {$selectCols}
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN {$empSubquery} ON n.legajo = rle.NRO_LEGAJO AND rle.rn = 1 AND ISNULL(n.es_global, 0) = 0
                        WHERE 1=1";
            } else {
                $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
                
                $sql = "SELECT {$selectCols}
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN {$empSubquery} ON n.legajo = rle.NRO_LEGAJO AND rle.rn = 1 AND ISNULL(n.es_global, 0) = 0
                        WHERE tn.$campoPermiso = 1";
            }
            
            $params = [];

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

            // Adjuntar detalles relacionados en batch (evita N+1)
            $idsComision = [];
            $idsPremio = [];
            foreach ($novedades as $nov) {
                if ((int)$nov['tipo_novedad'] === 36 || (int)$nov['tipo_novedad'] === 16) {
                    $idsComision[] = (int)$nov['id'];
                }
                if (((int)$nov['tipo_novedad'] === 35 || (int)$nov['tipo_novedad'] === 15) && !empty($nov['es_global'])) {
                    $idsPremio[] = (int)$nov['id'];
                }
            }
            $mapaComisiones = $this->obtenerMapaComisionSucursales($idsComision);
            $mapaPremios = $this->obtenerMapaPremioLocalEscala($idsPremio);
            
            foreach ($novedades as &$novedad) {
                $nid = (int)$novedad['id'];
                $novedad['comision_sucursales'] = $mapaComisiones[$nid] ?? [];
                $novedad['premio_local_escala'] = $mapaPremios[$nid] ?? [];

                // Agregar display de centro de costos (abreviado)
                if (!empty($novedad['es_global'])) {
                    $novedad['centro_costos_display'] = 'Todos los locales';
                } else {
                    $novedad['centro_costos_display'] = '';
                    if (!empty($novedad['descripcion_centro_costos'])) {
                        $novedad['centro_costos_display'] = $this->abreviarCentroCostos(
                            $novedad['descripcion_centro_costos'], 
                            $novedad['codigo_centro_costos'] ?? ''
                        );
                    } elseif (!empty($novedad['codigo_centro_costos'])) {
                        $novedad['centro_costos_display'] = $novedad['codigo_centro_costos'];
                    }
                }
                
                $novedad['nombre_sucursal'] = $novedad['centro_costos_display'];
                $novedad['valor_display'] = $this->calcularValorDisplayNovedad($novedad);
                
                // Procesar fechas
                $fechaRegistro = null;
                $fechaVigencia = null;
                $fechaVigenciaHasta = null;
                
                if (!empty($novedad['fecha_creacion'])) {
                    try {
                        $fechaRegistro = ($novedad['fecha_creacion'] instanceof DateTime)
                            ? $novedad['fecha_creacion']->format('d/m/Y')
                            : date('d/m/Y', strtotime($novedad['fecha_creacion']));
                    } catch (Exception $e) {
                        $fechaRegistro = 'Fecha inválida';
                    }
                }
                
                $fechaClave = null;
                if (!empty($novedad['fecha_vigencia'])) {
                    try {
                        $fechaVigencia = ($novedad['fecha_vigencia'] instanceof DateTime)
                            ? $novedad['fecha_vigencia']->format('d/m/Y')
                            : date('d/m/Y', strtotime($novedad['fecha_vigencia']));
                        $novedad['fecha_vigencia'] = $fechaVigencia;
                        $fechaClave = $fechaVigencia;
                    } catch (Exception $e) {
                        $fechaVigencia = null;
                    }
                } elseif (!empty($novedad['fecha_permiso'])) {
                    try {
                        $fechaClave = ($novedad['fecha_permiso'] instanceof DateTime)
                            ? $novedad['fecha_permiso']->format('d/m/Y')
                            : date('d/m/Y', strtotime($novedad['fecha_permiso']));
                        $novedad['fecha_permiso'] = $fechaClave;
                    } catch (Exception $e) {
                        $fechaClave = null;
                    }
                }
                
                if (!empty($novedad['fecha_vigencia_hasta'])) {
                    try {
                        $fechaVigenciaHasta = ($novedad['fecha_vigencia_hasta'] instanceof DateTime)
                            ? $novedad['fecha_vigencia_hasta']->format('d/m/Y')
                            : date('d/m/Y', strtotime($novedad['fecha_vigencia_hasta']));
                        $novedad['fecha_vigencia_hasta'] = $fechaVigenciaHasta;
                    } catch (Exception $e) {
                        $fechaVigenciaHasta = null;
                    }
                }
                
                if ($fechaRegistro) {
                    $novedad['fecha_creacion'] = $fechaRegistro;
                }
                
                $novedad['fecha_registro'] = $fechaRegistro ?: 'N/A';
                $novedad['fecha_display'] = $fechaClave ?: $fechaRegistro ?: 'N/A';
                $novedad['estado_numero'] = $this->mapearEstadoANumero($novedad['estado']);
            }
            
            return $novedades;
            
        } catch (Exception $e) {
            error_log("Error en getAllNovedades: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calcular el valor_display unificado para cualquier tipo de novedad
     */
    private function calcularValorDisplayNovedad($novedad) {
        $tipoNovedad = (int)$novedad['tipo_novedad'];
        $valorNumerico = $novedad['valor_numerico'] !== null ? (float)$novedad['valor_numerico'] : 0;

        switch ($tipoNovedad) {
            case 1:
                return 'Cambio de sucursal';
            case 2:
                $puesto = !empty($novedad['puesto']) ? $novedad['puesto'] : 'Nueva Posición';
                return !empty($novedad['convenio']) ? "{$puesto} ({$novedad['convenio']})" : $puesto;
            case 3:
                return $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2, ',', '.') : 'Ajuste salarial';
            case 4:
                return $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2, ',', '.') : 'A definir';
            case 5:
                return $valorNumerico != 0 ? $valorNumerico . ' horas extras' : 'Horas extras';
            case 6:
                return $valorNumerico != 0 ? $valorNumerico . ' horas adicionales' : 'Horas adicionales';
            case 7:
                return 'Permiso' . ($novedad['compensa'] ? ' (compensa)' : '');
            case 12:
            case 32:
                if (preg_match('/Tipo:\s*([^-\n]+)/i', (string)$novedad['observaciones'], $m)) {
                    return 'Tipo: ' . ucfirst(trim($m[1]));
                }
                return $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2, ',', '.') : 'Plus de caja';
            case 13:
            case 33:
                if ($novedad['aplica_plus'] !== null) {
                    return $novedad['aplica_plus'] ? 'SI' : 'NO';
                }
                return $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2, ',', '.') : 'SI';
            case 14:
            case 34:
                if ($novedad['aplica_plus'] !== null) {
                    return $novedad['aplica_plus'] ? 'SI' : 'NO';
                }
                return $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2, ',', '.') : 'SI';
            case 15:
            case 35:
                if (!empty($novedad['es_global']) && !empty($novedad['premio_local_escala'])) {
                    return 'Escala global (' . count($novedad['premio_local_escala']) . ' tramos)';
                }
                $aplica = [];
                if (!empty($novedad['aplica_vendedora'])) $aplica[] = 'Vendedora';
                if (!empty($novedad['aplica_encargada'])) $aplica[] = 'Encargada';
                if (!empty($novedad['aplica_sub_encargada'])) $aplica[] = 'Sub-Encargada';
                if (!empty($aplica)) {
                    return 'Aplica: ' . implode(', ', $aplica);
                }
                return $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2, ',', '.') : 'Premio Local';
            case 16:
            case 36:
                if (!empty($novedad['comision_sucursales'])) {
                    $cnt = count($novedad['comision_sucursales']);
                    return $cnt . ($cnt === 1 ? ' sucursal' : ' sucursales');
                }
                $p = $novedad['porcentaje_1'] ? number_format((float)$novedad['porcentaje_1'] * 100, 2) : '0';
                return "Individual: {$p}%";
            case 17:
            case 37:
                if (!empty($novedad['tiene_tope'])) {
                    $p1 = $novedad['porcentaje_1'] ? number_format((float)$novedad['porcentaje_1'] * 100, 2) : '0';
                    $p2 = $novedad['porcentaje_2'] ? number_format((float)$novedad['porcentaje_2'] * 100, 2) : '0';
                    return "Con tope: {$p1}% / {$p2}%";
                }
                $p = $novedad['porcentaje_1'] ? number_format((float)$novedad['porcentaje_1'] * 100, 2) : '0';
                return "Sin tope: {$p}%";
            case 18:
            case 38:
                if ($novedad['aplica_plus'] !== null && $valorNumerico == 0) {
                    return $novedad['aplica_plus'] ? 'SI' : 'NO';
                }
                return $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2, ',', '.') : 'A definir';
            case 53:
            case 55:
                return !empty($novedad['puesto']) ? $novedad['puesto'] : 'N/A';
            case 54:
                if (!empty($novedad['escala_sueldo_basico'])) {
                    return 'Sueldo básico: ' . $novedad['escala_sueldo_basico'];
                }
                if (!empty($novedad['porcentaje_1']) && (float)$novedad['porcentaje_1'] > 0) {
                    return number_format((float)$novedad['porcentaje_1'] * 100, 2) . '%';
                }
                return $valorNumerico != 0 ? '$' . number_format($valorNumerico, 2, ',', '.') : 'Aumento Salarial';
            default:
                return 'N/A';
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
        $empSubquery = $this->getSubqueryEmpleadosUnicos('emp');
        
        $selectCols = "n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                       n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                       n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                       n.periodo_mes, n.periodo_anio, n.estado,
                       n.tiene_tope, n.porcentaje_1, n.porcentaje_2, n.tipo_comision,
                       n.aplica_vendedora, n.aplica_sub_encargada, n.aplica_encargada,
                       n.es_global, n.lote_id, n.etapa_multiple, n.convenio, n.escala_sueldo_basico, n.aplica_plus,
                       tn.descripcion as tipo_descripcion,
                       CASE WHEN n.es_global = 1 THEN 'TODOS LOS LOCALES' ELSE COALESCE(emp.NOMBRE, n.nombre) END as nombre,
                       CASE WHEN n.es_global = 1 THEN '' ELSE COALESCE(emp.APELLIDO, n.apellido) END as apellido,
                       CASE WHEN n.es_global = 1 THEN 'TODOS' ELSE COALESCE(emp.COD_DEPARTAMENTO, n.centro_costos) END as codigo_centro_costos,
                       CASE WHEN n.es_global = 1 THEN 'Todos los locales' ELSE emp.DESC_DEPARTAMENTO END as descripcion_centro_costos";

        if ($tipoUsuario == Usuario::TIPO_RRHH) {
            $sql = "SELECT {$selectCols}
                    FROM novedades n 
                    INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                    LEFT JOIN {$empSubquery} ON n.legajo = emp.NRO_LEGAJO AND emp.rn = 1 AND ISNULL(n.es_global, 0) = 0
                    WHERE n.id = ?";
        } else {
            $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
            
            $sql = "SELECT {$selectCols}
                    FROM novedades n 
                    INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                    LEFT JOIN {$empSubquery} ON n.legajo = emp.NRO_LEGAJO AND emp.rn = 1 AND ISNULL(n.es_global, 0) = 0
                    WHERE n.id = ? AND tn.$campoPermiso = 1";
        }

        $stmt = $this->db->query($sql, [$id]);
        $novedad = $stmt->fetch();

        if ($novedad) {
            $nid = (int)$novedad['id'];
            $mapaCom = $this->obtenerMapaComisionSucursales([$nid]);
            $mapaPrem = $this->obtenerMapaPremioLocalEscala([$nid]);
            $novedad['comision_sucursales'] = $mapaCom[$nid] ?? [];
            $novedad['premio_local_escala'] = $mapaPrem[$nid] ?? [];

            if (!empty($novedad['es_global'])) {
                $novedad['centro_costos_display'] = 'Todos los locales';
            } else {
                $novedad['centro_costos_display'] = '';
                if (!empty($novedad['descripcion_centro_costos'])) {
                    $novedad['centro_costos_display'] = $this->abreviarCentroCostos(
                        $novedad['descripcion_centro_costos'], 
                        $novedad['codigo_centro_costos'] ?? ''
                    );
                } elseif (!empty($novedad['codigo_centro_costos'])) {
                    $novedad['centro_costos_display'] = $novedad['codigo_centro_costos'];
                } elseif (!empty($novedad['sucursal'])) {
                    $novedad['centro_costos_display'] = 'Centro ' . $novedad['sucursal'];
                }
            }

            $novedad['nombre_sucursal'] = $novedad['centro_costos_display'];

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

            if (empty($novedad['periodo_mes']) || empty($novedad['periodo_anio'])) {
                $periodo = $this->getPeriodoActual();
                $novedad['periodo_mes'] = $novedad['periodo_mes'] ?: $periodo['periodo_mes'];
                $novedad['periodo_anio'] = $novedad['periodo_anio'] ?: $periodo['periodo_anio'];
            }

            $novedad['valor_display'] = $this->calcularValorDisplayNovedad($novedad);
            $novedad['detalle_especifico'] = $this->generarDetalleEspecifico($novedad);
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

        $tipoNovedad = isset($datos['tipo_novedad']) ? (int)$datos['tipo_novedad'] : 0;
        $esGlobal = !empty($datos['es_global']) || ($tipoNovedad === 35 && isset($datos['escala_premios']));

        if (!$tipoNovedad) {
            $errores[] = 'El tipo de novedad es obligatorio';
        }

        if (!$esGlobal) {
            if (empty($datos['legajo'])) {
                $errores[] = 'El legajo es obligatorio';
            }
            if (empty($datos['nombre'])) {
                $errores[] = 'El nombre es obligatorio';
            }
            if (empty($datos['apellido'])) {
                $errores[] = 'El apellido es obligatorio';
            }
        }

        // Validación opcional de fecha_vigencia_hasta >= fecha_vigencia
        if (!empty($datos['fecha_vigencia']) && !empty($datos['fecha_vigencia_hasta'])) {
            $f1 = $this->formatearFechaParaSQL($datos['fecha_vigencia']);
            $f2 = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta']);
            if ($f1 && $f2 && $f2 < $f1) {
                $errores[] = 'La fecha de vencimiento/fin no puede ser anterior a la fecha de inicio';
            }
        }

        switch ($tipoNovedad) {
            case 1: // Cambio de sucursal
                if (empty($datos['nueva_sucursal']) && empty($datos['sucursal_nueva_id'])) {
                    $errores[] = 'La nueva sucursal es obligatoria';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                break;
                
            case 2: // Nueva Posición
                if (empty($datos['puesto'])) {
                    $errores[] = 'El puesto es obligatorio';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                break;
                
            case 4: // Ajuste de premios (importe opcional)
                if (isset($datos['importe']) && $datos['importe'] !== '' && (!is_numeric($datos['importe']) || (float)$datos['importe'] < 0)) {
                    $errores[] = 'El importe debe ser un valor numérico válido';
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
                if (empty($datos['fecha_permiso'])) {
                    $errores[] = 'La fecha del permiso es obligatoria';
                } else {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_permiso'])) {
                        $errores[] = 'La fecha del permiso debe tener formato válido (YYYY-MM-DD)';
                    } else {
                        $fechaPartes = explode('-', $datos['fecha_permiso']);
                        if (count($fechaPartes) === 3 && !checkdate((int)$fechaPartes[1], (int)$fechaPartes[2], (int)$fechaPartes[0])) {
                            $errores[] = 'La fecha del permiso no es válida';
                        }
                    }
                }
                break;
            
            case 12:
            case 32: // Plus de caja (no lleva importe obligatorio)
                if (empty($datos['tipo_plus_caja'])) {
                    $errores[] = 'El tipo de plus de caja es obligatorio';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                break;

            case 13:
            case 33: // Plus de Sub-Encargada (solo SI/NO, sin importe)
            case 14:
            case 34: // Plus de Encargada (solo SI/NO, sin importe)
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                if (!isset($datos['aplica_plus']) && !isset($datos['tiene_importe'])) {
                    $errores[] = 'Debe indicar SI o NO para el plus';
                }
                break;

            case 15:
            case 35: // Premio Local
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                if ($esGlobal) {
                    if (empty($datos['escala_premios']) || !is_array($datos['escala_premios'])) {
                        $errores[] = 'Debe completar la escala de premios (3 categorías)';
                    } else {
                        $codigosRequeridos = ['PRIMER_RANKING', 'CUMPLE_OBJETIVO', 'PRIMER_NO_LLEGA'];
                        $encontrados = [];
                        foreach ($datos['escala_premios'] as $tramo) {
                            $cod = $tramo['nivel_codigo'] ?? '';
                            $encontrados[] = $cod;
                            if (!isset($tramo['importe_enc_sub']) || !is_numeric($tramo['importe_enc_sub']) || (float)$tramo['importe_enc_sub'] <= 0) {
                                $errores[] = "El importe de Encargada/Sub para '{$cod}' es obligatorio y mayor a 0";
                            }
                            if (!isset($tramo['importe_vend']) || !is_numeric($tramo['importe_vend']) || (float)$tramo['importe_vend'] <= 0) {
                                $errores[] = "El importe de Vendedora para '{$cod}' es obligatorio y mayor a 0";
                            }
                        }
                        foreach ($codigosRequeridos as $req) {
                            if (!in_array($req, $encontrados, true)) {
                                $errores[] = "Falta la categoría de premio: {$req}";
                            }
                        }
                    }
                } else {
                    $tieneSeleccion = !empty($datos['aplica_vendedora']) || !empty($datos['aplica_encargada']) || !empty($datos['aplica_sub_encargada']);
                    if (!$tieneSeleccion) {
                        $errores[] = 'Debe seleccionar a quién aplica el Premio Local (Vendedora o Encargada)';
                    }
                }
                break;

            case 16:
            case 36: // Comisión Individual (Supervisoras: múltiples sucursales PROPIOS con tope opcional; o simple)
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                if (!empty($datos['comision_sucursales']) && is_array($datos['comision_sucursales'])) {
                    foreach ($datos['comision_sucursales'] as $idx => $suc) {
                        if (empty($suc['nro_sucursal'])) {
                            $errores[] = 'Debe seleccionar la sucursal en todas las filas de comisión';
                        }
                        if (!isset($suc['porcentaje_1']) || !is_numeric($suc['porcentaje_1']) || (float)$suc['porcentaje_1'] <= 0) {
                            $errores[] = 'El porcentaje base debe ser mayor a 0 en todas las sucursales';
                        }
                        if (!empty($suc['tiene_tope'])) {
                            if (!isset($suc['porcentaje_2']) || !is_numeric($suc['porcentaje_2']) || (float)$suc['porcentaje_2'] <= 0) {
                                $errores[] = 'El porcentaje post-tope es obligatorio y mayor a 0 cuando tiene tope';
                            }
                        }
                    }
                } else {
                    if (empty($datos['porcentaje_individual']) || !is_numeric($datos['porcentaje_individual']) || (float)$datos['porcentaje_individual'] <= 0) {
                        $errores[] = 'Debe agregar al menos una sucursal o indicar el porcentaje individual mayor a 0';
                    }
                }
                break;

            case 17:
            case 37: // Comisión sobre Local
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                if (!isset($datos['tiene_tope'])) {
                    $errores[] = 'Debe indicar si la comisión tiene tope';
                } else {
                    if ($datos['tiene_tope']) {
                        if (empty($datos['porcentaje_1']) || !is_numeric($datos['porcentaje_1']) || (float)$datos['porcentaje_1'] <= 0) {
                            $errores[] = 'El primer porcentaje es obligatorio y debe ser mayor a 0';
                        }
                        if (empty($datos['porcentaje_2']) || !is_numeric($datos['porcentaje_2']) || (float)$datos['porcentaje_2'] <= 0) {
                            $errores[] = 'El segundo porcentaje es obligatorio y debe ser mayor a 0';
                        }
                    } else {
                        if (empty($datos['porcentaje_unico']) || !is_numeric($datos['porcentaje_unico']) || (float)$datos['porcentaje_unico'] <= 0) {
                            $errores[] = 'El porcentaje único es obligatorio y debe ser mayor a 0';
                        }
                    }
                }
                break;

            case 18:
            case 38: // Premios - Ajuste General (importe no obligatorio)
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                if (isset($datos['importe']) && $datos['importe'] !== '' && (!is_numeric($datos['importe']) || (float)$datos['importe'] < 0)) {
                    $errores[] = 'El importe del ajuste debe ser numérico válido';
                }
                break;

            case 53: // Reemplazo
                if (empty($datos['puesto'])) {
                    $errores[] = 'El puesto de reemplazo es obligatorio';
                }
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de inicio es obligatoria';
                }
                if (empty($datos['fecha_vigencia_hasta'])) {
                    $errores[] = 'La fecha de fin es obligatoria para reemplazos';
                }
                break;

            case 54: // Aumento Salarial / Sueldo Básico
                if (empty($datos['fecha_vigencia'])) {
                    $errores[] = 'La fecha de vigencia es obligatoria';
                }
                if (isset($datos['tipo_aumento']) && $datos['tipo_aumento'] === 'basico') {
                    if (empty($datos['escala_sueldo_basico'])) {
                        $errores[] = 'Debe seleccionar el sueldo básico (Vendedora o Encargada)';
                    }
                } elseif (isset($datos['tipo_aumento']) && $datos['tipo_aumento'] === 'monto') {
                    $monto = $datos['valor_numerico'] ?? ($datos['importe'] ?? null);
                    if (empty($monto) || !is_numeric($monto) || (float)$monto <= 0) {
                        $errores[] = 'El monto del aumento debe ser mayor a 0';
                    }
                } else {
                    if (empty($datos['porcentaje_1']) || !is_numeric($datos['porcentaje_1']) || (float)$datos['porcentaje_1'] <= 0) {
                        $errores[] = 'El porcentaje de aumento debe ser mayor a 0';
                    }
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
                
                if ($mes < 1 || $mes > 12) {
                    $errores[] = 'El mes del período debe estar entre 1 y 12';
                }
                if ($año < 2020 || $año > 2030) {
                    $errores[] = 'El año del período debe estar entre 2020 y 2030';
                }
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

            if (isset($datos['tipo_novedad']) && $datos['tipo_novedad'] != $novedadActual['tipo_novedad']) {
                $this->validarPermisosTipoNovedad($datos['tipo_novedad']);
            } else {
                $this->validarPermisosTipoNovedad($novedadActual['tipo_novedad']);
            }

            // Conservar flag global si la novedad original es global
            if (!isset($datos['es_global']) && !empty($novedadActual['es_global'])) {
                $datos['es_global'] = 1;
            }

            // Validar datos
            $errores = $this->validarDatos($datos);
            if (!empty($errores)) {
                throw new Exception('Errores de validación: ' . implode(', ', $errores));
            }

            // Adaptar datos para la actualización
            $datosAdaptados = $this->adaptarDatosParaEdicion($datos);

            $sql = "UPDATE novedades SET 
                        legajo = ?, nombre = ?, apellido = ?, centro_costos = ?, 
                        fecha_vigencia = ?, fecha_vigencia_hasta = ?, puesto = ?, valor_numerico = ?, 
                        fecha_permiso = ?, compensa = ?, tipo_permiso = ?, tipo_nuevo_puesto = ?,
                        observaciones = ?, tipo_novedad = ?, fecha_modificacion = GETDATE(),
                        tiene_tope = ?, porcentaje_1 = ?, porcentaje_2 = ?, tipo_comision = ?,
                        aplica_vendedora = ?, aplica_sub_encargada = ?, aplica_encargada = ?,
                        es_global = ?, convenio = ?, escala_sueldo_basico = ?, aplica_plus = ?
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
                $datosAdaptados['tiene_tope'],
                $datosAdaptados['porcentaje_1'],
                $datosAdaptados['porcentaje_2'],
                $datosAdaptados['tipo_comision'],
                $datosAdaptados['aplica_vendedora'],
                $datosAdaptados['aplica_sub_encargada'],
                $datosAdaptados['aplica_encargada'],
                $datosAdaptados['es_global'],
                $datosAdaptados['convenio'],
                $datosAdaptados['escala_sueldo_basico'],
                $datosAdaptados['aplica_plus'],
                $id
            ];

            $this->db->query($sql, $params);

            // Actualizar tablas detalle FP_ si corresponde
            $tipo = (int)$datosAdaptados['tipo_novedad'];
            if ($tipo === 36 || $tipo === 16) {
                $this->db->query("DELETE FROM FP_NOVEDADES_COMISION_SUCURSALES WHERE novedad_id = ?", [$id]);
                if (!empty($datos['comision_sucursales']) && is_array($datos['comision_sucursales'])) {
                    $this->guardarComisionSucursales($id, $datos['comision_sucursales']);
                }
            }
            if (($tipo === 35 || $tipo === 15) && !empty($datosAdaptados['es_global']) && !empty($datos['escala_premios'])) {
                $this->db->query("DELETE FROM FP_NOVEDADES_PREMIO_LOCAL_ESCALA WHERE novedad_id = ?", [$id]);
                $this->guardarPremioLocalEscala($id, $datos['escala_premios']);
            }

            $this->db->commit();
            
            return ['success' => true, 'id' => $id];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error editando novedad ID $id: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Eliminar una novedad y sus detalles relacionados
     */
    public function eliminarNovedad($id) {
        try {
            $this->db->beginTransaction();

            $novedad = $this->getNovedadById($id);
            if (!$novedad) {
                throw new Exception('Novedad no encontrada');
            }

            $this->validarPermisosTipoNovedad($novedad['tipo_novedad']);

            $this->db->query("DELETE FROM FP_NOVEDADES_COMISION_SUCURSALES WHERE novedad_id = ?", [$id]);
            $this->db->query("DELETE FROM FP_NOVEDADES_PREMIO_LOCAL_ESCALA WHERE novedad_id = ?", [$id]);
            $this->db->query("DELETE FROM novedades WHERE id = ?", [$id]);

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
     * Obtener centro de costos de un empleado específico (deduplicado por ingreso más reciente)
     */
    private function obtenerCentroCostosEmpleado($legajo) {
        try {
            $sql = "SELECT TOP 1 COD_DEPARTAMENTO
                    FROM [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL
                    WHERE NRO_LEGAJO = ?
                    ORDER BY CASE WHEN HABILITADO = 'S' THEN 0 ELSE 1 END, FECHA_INGRESO DESC";
            $result = $this->db->query($sql, [$legajo]);
            $resultado = $result->fetch();
            
            if ($resultado && !empty($resultado['COD_DEPARTAMENTO'])) {
                return $resultado['COD_DEPARTAMENTO'];
            }
            
            throw new Exception("No se encontró centro de costos para el empleado con legajo: $legajo");
        } catch (Exception $e) {
            error_log("Error obteniendo centro de costos para empleado {$legajo}: " . $e->getMessage());
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
     * Adaptar datos para inserción - ACTUALIZADO CON NUEVOS TIPOS Y REGLAS 2026.11
     */
    private function adaptarDatosParaInsercionActualizado($datos) {
        $tipoNovedad = (int)$datos['tipo_novedad'];
        $esGlobal = !empty($datos['es_global']) || ($tipoNovedad === 35 && isset($datos['escala_premios']));
        
        $observacionesBase = isset($datos['observaciones']) ? $datos['observaciones'] : '';
        $observacionesLimpias = $this->limpiarObservacionesEspecificas($observacionesBase, $tipoNovedad);
        
        if ($esGlobal) {
            $centroCostos = 'TODOS';
            $legajo = 0;
            $nombre = 'TODOS LOS LOCALES';
            $apellido = '';
        } elseif ($tipoNovedad == 1) {
            $centroCostos = isset($datos['sucursal_nueva_id']) ? $datos['sucursal_nueva_id'] : 
                        (isset($datos['nueva_sucursal']) ? $datos['nueva_sucursal'] : null);
            if (!$centroCostos) {
                throw new Exception("Para cambio de sucursal debe especificar la nueva sucursal");
            }
            $legajo = $datos['legajo'];
            $nombre = $datos['nombre'];
            $apellido = $datos['apellido'];
        } else {
            $centroCostos = $this->obtenerCentroCostosEmpleado($datos['legajo']);
            if (!$centroCostos) {
                throw new Exception("No se pudo obtener el centro de costos del empleado con legajo: " . $datos['legajo']);
            }
            $legajo = $datos['legajo'];
            $nombre = $datos['nombre'];
            $apellido = $datos['apellido'];
        }
        
        $datosAdaptados = [
            'legajo' => $legajo,
            'nombre' => $nombre,
            'apellido' => $apellido,
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
            'tiene_tope' => null,
            'porcentaje_1' => null,
            'porcentaje_2' => null,
            'tipo_comision' => null,
            'aplica_vendedora' => null,
            'aplica_sub_encargada' => null,
            'aplica_encargada' => null,
            'es_global' => $esGlobal ? 1 : 0,
            'lote_id' => $datos['lote_id'] ?? null,
            'etapa_multiple' => $datos['etapa_multiple'] ?? null,
            'convenio' => !empty($datos['convenio']) ? $datos['convenio'] : null,
            'escala_sueldo_basico' => !empty($datos['escala_sueldo_basico']) ? $datos['escala_sueldo_basico'] : null,
            'aplica_plus' => null,
        ];

        switch ($tipoNovedad) {
            case 1: // Cambio de sucursal (permite fecha_vigencia_hasta opcional)
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                $datosAdaptados['puesto'] = 'Cambio de Sucursal';
                
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
                if (!empty($datos['convenio'])) {
                    $datosAdaptados['observaciones'] .= " - Convenio: " . $datos['convenio'];
                }
                break;

            case 4: // Ajuste de premios (importe opcional)
                $datosAdaptados['valor_numerico'] = (isset($datos['importe']) && $datos['importe'] !== '') ? (float)$datos['importe'] : null;
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
            
            case 12:
            case 32: // Plus de caja (no lleva importe obligatorio; permite fecha_vigencia_hasta opcional)
                $datosAdaptados['valor_numerico'] = null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de caja';
                $tipoPlus = isset($datos['tipo_plus_caja']) ? $datos['tipo_plus_caja'] : 'recibo';
                $datosAdaptados['observaciones'] .= " - Tipo: " . ucfirst($tipoPlus);
                break;

            case 13:
            case 33: // Plus de Sub-Encargada (solo SI/NO, sin importe; fecha_vigencia_hasta opcional)
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de Sub-Encargada';
                $datosAdaptados['valor_numerico'] = null;
                $aplicaPlus = isset($datos['aplica_plus'])
                    ? (bool)(int)$datos['aplica_plus']
                    : (!(isset($datos['tiene_importe']) && $datos['tiene_importe'] === '0'));
                $datosAdaptados['aplica_plus'] = $aplicaPlus ? 1 : 0;
                $datosAdaptados['observaciones'] .= " - Aplica: " . ($aplicaPlus ? 'SI' : 'NO');
                break;

            case 14:
            case 34: // Plus de Encargada (solo SI/NO, sin importe; fecha_vigencia_hasta opcional)
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de Encargada';
                $datosAdaptados['valor_numerico'] = null;
                $aplicaPlusEnc = isset($datos['aplica_plus'])
                    ? (bool)(int)$datos['aplica_plus']
                    : (!(isset($datos['tiene_importe']) && $datos['tiene_importe'] === '0'));
                $datosAdaptados['aplica_plus'] = $aplicaPlusEnc ? 1 : 0;
                $datosAdaptados['observaciones'] .= " - Aplica: " . ($aplicaPlusEnc ? 'SI' : 'NO');
                break;

            case 15:
            case 35: // Premio Local
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Premio Local';
                if ($esGlobal) {
                    $datosAdaptados['valor_numerico'] = null;
                    $datosAdaptados['observaciones'] .= " - Escala global para todos los locales";
                } else {
                    $datosAdaptados['valor_numerico'] = null;
                    $datosAdaptados['aplica_vendedora'] = !empty($datos['aplica_vendedora']) ? 1 : 0;
                    $datosAdaptados['aplica_encargada'] = !empty($datos['aplica_encargada']) ? 1 : 0;
                    $datosAdaptados['aplica_sub_encargada'] = !empty($datos['aplica_sub_encargada']) ? 1 : 0;
                    $aplicacionTexto = [];
                    if ($datosAdaptados['aplica_vendedora']) $aplicacionTexto[] = 'Vendedora';
                    if ($datosAdaptados['aplica_encargada']) $aplicacionTexto[] = 'Encargada';
                    if ($datosAdaptados['aplica_sub_encargada']) $aplicacionTexto[] = 'Sub-Encargada';
                    if (!empty($aplicacionTexto)) {
                        $datosAdaptados['observaciones'] .= " - Aplica a: " . implode(', ', $aplicacionTexto);
                    }
                }
                break;

            case 16:
            case 36: // Comisión Individual (Supervisoras multi-sucursal o simple)
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Comisión Individual';
                $datosAdaptados['tipo_comision'] = 'individual';
                
                if (!empty($datos['comision_sucursales']) && is_array($datos['comision_sucursales'])) {
                    $algunoConTope = false;
                    $resumen = [];
                    foreach ($datos['comision_sucursales'] as $suc) {
                        $nom = $suc['nombre_sucursal'] ?? $this->obtenerNombreSucursal($suc['nro_sucursal']);
                        $p1 = number_format((float)$suc['porcentaje_1'], 2);
                        if (!empty($suc['tiene_tope'])) {
                            $algunoConTope = true;
                            $p2 = number_format((float)$suc['porcentaje_2'], 2);
                            $resumen[] = "{$nom}: {$p1}% (tope -> {$p2}%)";
                        } else {
                            $resumen[] = "{$nom}: {$p1}% (sin tope)";
                        }
                    }
                    $datosAdaptados['tiene_tope'] = $algunoConTope ? 1 : 0;
                    $datosAdaptados['porcentaje_1'] = null;
                    $datosAdaptados['porcentaje_2'] = null;
                    $datosAdaptados['observaciones'] .= " - Sucursales: " . implode(' | ', $resumen);
                } else {
                    $datosAdaptados['tiene_tope'] = 0;
                    $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_individual']) ? round((float)$datos['porcentaje_individual'] / 100, 4) : null;
                    $datosAdaptados['porcentaje_2'] = null;
                    $pDisplay = isset($datos['porcentaje_individual']) ? number_format((float)$datos['porcentaje_individual'], 2) : 0;
                    $datosAdaptados['observaciones'] .= " - Porcentaje: {$pDisplay}%";
                }
                break;

            case 17:
            case 37: // Comisión sobre Local
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Comisión sobre Local';
                $datosAdaptados['tipo_comision'] = 'local';
                $datosAdaptados['tiene_tope'] = !empty($datos['tiene_tope']) ? 1 : 0;
                
                if ($datosAdaptados['tiene_tope']) {
                    $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_1']) ? round((float)$datos['porcentaje_1'] / 100, 4) : null;
                    $datosAdaptados['porcentaje_2'] = isset($datos['porcentaje_2']) ? round((float)$datos['porcentaje_2'] / 100, 4) : null;
                    $p1Display = isset($datos['porcentaje_1']) ? number_format((float)$datos['porcentaje_1'], 2) : 0;
                    $p2Display = isset($datos['porcentaje_2']) ? number_format((float)$datos['porcentaje_2'], 2) : 0;
                    $datosAdaptados['observaciones'] .= " - Con tope: {$p1Display}% y {$p2Display}%";
                } else {
                    $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_unico']) ? round((float)$datos['porcentaje_unico'] / 100, 4) : null;
                    $datosAdaptados['porcentaje_2'] = null;
                    $pDisplay = isset($datos['porcentaje_unico']) ? number_format((float)$datos['porcentaje_unico'], 2) : 0;
                    $datosAdaptados['observaciones'] .= " - Sin tope: {$pDisplay}%";
                }
                break;

            case 18:
            case 38: // Premios - Ajuste General (importe opcional o SI/NO)
                $datosAdaptados['valor_numerico'] = (isset($datos['importe']) && $datos['importe'] !== '') ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Premios - Ajuste General';
                if (isset($datos['aplica_plus'])) {
                    $aplica = (bool)(int)$datos['aplica_plus'];
                    $datosAdaptados['aplica_plus'] = $aplica ? 1 : 0;
                    $datosAdaptados['observaciones'] .= " - Ajuste General: " . ($aplica ? 'SI' : 'NO');
                }
                break;

            case 53: // Reemplazo - siempre temporario
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : 'Reemplazo';
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['tipo_nuevo_puesto'] = 'temporario';
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                break;

            case 55: // A prueba (solo compatibilidad histórica)
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : 'A prueba';
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['tipo_nuevo_puesto'] = 'temporario';
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                break;

            case 54: // Aumento Salarial / Sueldo Básico
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Aumento Salarial';
                if (isset($datos['tipo_aumento'])) {
                    if ($datos['tipo_aumento'] === 'basico') {
                        $datosAdaptados['escala_sueldo_basico'] = $datos['escala_sueldo_basico'] ?? null;
                        $datosAdaptados['observaciones'] .= " - Sueldo básico: " . ($datosAdaptados['escala_sueldo_basico'] ?? '');
                    } elseif ($datos['tipo_aumento'] === 'porcentaje' && !empty($datos['porcentaje_1'])) {
                        $datosAdaptados['porcentaje_1'] = round((float)$datos['porcentaje_1'] / 100, 4);
                        $datosAdaptados['valor_numerico'] = null;
                    } elseif ($datos['tipo_aumento'] === 'monto') {
                        $montoVal = $datos['valor_numerico'] ?? ($datos['importe'] ?? null);
                        if (!empty($montoVal)) {
                            $datosAdaptados['valor_numerico'] = round((float)$montoVal, 2);
                        }
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
     * Adaptar datos específicamente para edición - NO concatena duplicados en observaciones
     */
    private function adaptarDatosParaEdicion($datos) {
        $tipoNovedad = (int)$datos['tipo_novedad'];
        $esGlobal = !empty($datos['es_global']);
        $observacionesBase = isset($datos['observaciones']) ? $datos['observaciones'] : '';
        
        if ($esGlobal) {
            $centroCostos = 'TODOS';
            $legajo = 0;
            $nombre = 'TODOS LOS LOCALES';
            $apellido = '';
        } elseif ($tipoNovedad == 1) {
            $centroCostos = isset($datos['sucursal_nueva_id']) ? $datos['sucursal_nueva_id'] : 
                        (isset($datos['nueva_sucursal']) ? $datos['nueva_sucursal'] : null);
            if (!$centroCostos) {
                throw new Exception("Para cambio de sucursal debe especificar la nueva sucursal");
            }
            $legajo = $datos['legajo'];
            $nombre = $datos['nombre'];
            $apellido = $datos['apellido'];
        } else {
            $centroCostos = isset($datos['centro_costos']) ? $datos['centro_costos'] : 
                        $this->obtenerCentroCostosEmpleado($datos['legajo']);
            $legajo = $datos['legajo'];
            $nombre = $datos['nombre'];
            $apellido = $datos['apellido'];
        }
        
        $datosAdaptados = [
            'legajo' => $legajo,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'centro_costos' => $centroCostos,
            'tipo_novedad' => $tipoNovedad,
            'observaciones' => $observacionesBase,
            'fecha_vigencia' => null,
            'fecha_vigencia_hasta' => null,
            'puesto' => '',
            'valor_numerico' => null,
            'fecha_permiso' => null,
            'compensa' => null,
            'tipo_permiso' => null,
            'tipo_nuevo_puesto' => null,
            'tiene_tope' => null,
            'porcentaje_1' => null,
            'porcentaje_2' => null,
            'tipo_comision' => null,
            'aplica_vendedora' => null,
            'aplica_sub_encargada' => null,
            'aplica_encargada' => null,
            'es_global' => $esGlobal ? 1 : 0,
            'convenio' => !empty($datos['convenio']) ? $datos['convenio'] : null,
            'escala_sueldo_basico' => !empty($datos['escala_sueldo_basico']) ? $datos['escala_sueldo_basico'] : null,
            'aplica_plus' => null,
        ];

        switch ($tipoNovedad) {
            case 1: // Cambio de sucursal
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                $datosAdaptados['puesto'] = 'Cambio de Sucursal';
                break;

            case 2: // Nueva Posición
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : 'Cambio de Puesto';
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['tipo_nuevo_puesto'] = 'permanente';
                $datosAdaptados['fecha_vigencia_hasta'] = null;
                break;

            case 4: // Ajuste de premios
                $datosAdaptados['valor_numerico'] = (isset($datos['importe']) && $datos['importe'] !== '') ? (float)$datos['importe'] : null;
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
            
            case 12:
            case 32: // Plus de caja
                $datosAdaptados['valor_numerico'] = null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de caja';
                if (isset($datos['tipo_plus_caja'])) {
                    $tipoPlus = ucfirst($datos['tipo_plus_caja']);
                    $datosAdaptados['observaciones'] = trim($observacionesBase . ($observacionesBase ? ' - ' : '') . "Tipo: {$tipoPlus}");
                }
                break;

            case 13:
            case 33: // Plus de Sub-Encargada
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de Sub-Encargada';
                $datosAdaptados['valor_numerico'] = null;
                $aplicaPlus = isset($datos['aplica_plus']) ? (bool)(int)$datos['aplica_plus'] : true;
                $datosAdaptados['aplica_plus'] = $aplicaPlus ? 1 : 0;
                break;

            case 14:
            case 34: // Plus de Encargada
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                $datosAdaptados['puesto'] = 'Plus de Encargada';
                $datosAdaptados['valor_numerico'] = null;
                $aplicaPlusEnc = isset($datos['aplica_plus']) ? (bool)(int)$datos['aplica_plus'] : true;
                $datosAdaptados['aplica_plus'] = $aplicaPlusEnc ? 1 : 0;
                break;

            case 15:
            case 35: // Premio Local
                $datosAdaptados['valor_numerico'] = null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Premio Local';
                if (!$esGlobal) {
                    $datosAdaptados['aplica_vendedora'] = !empty($datos['aplica_vendedora']) ? 1 : 0;
                    $datosAdaptados['aplica_encargada'] = !empty($datos['aplica_encargada']) ? 1 : 0;
                    $datosAdaptados['aplica_sub_encargada'] = !empty($datos['aplica_sub_encargada']) ? 1 : 0;
                }
                break;

            case 16:
            case 36: // Comisión Individual
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Comisión Individual';
                $datosAdaptados['tipo_comision'] = 'individual';
                if (!empty($datos['comision_sucursales']) && is_array($datos['comision_sucursales'])) {
                    $algunoConTope = false;
                    foreach ($datos['comision_sucursales'] as $suc) {
                        if (!empty($suc['tiene_tope'])) $algunoConTope = true;
                    }
                    $datosAdaptados['tiene_tope'] = $algunoConTope ? 1 : 0;
                    $datosAdaptados['porcentaje_1'] = null;
                    $datosAdaptados['porcentaje_2'] = null;
                } else {
                    $datosAdaptados['tiene_tope'] = 0;
                    $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_individual']) ? round((float)$datos['porcentaje_individual'] / 100, 4) : null;
                    $datosAdaptados['porcentaje_2'] = null;
                    $p_display = isset($datos['porcentaje_individual']) ? $datos['porcentaje_individual'] : 0;
                    $datosAdaptados['observaciones'] = "Comisión individual: {$p_display}%";
                }
                break;

            case 17:
            case 37: // Comisión sobre Local
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Comisión sobre Local';
                $datosAdaptados['tipo_comision'] = isset($datos['tipo_comision']) ? $datos['tipo_comision'] : 'local';
                $datosAdaptados['tiene_tope'] = !empty($datos['tiene_tope']) ? 1 : 0;
                
                if ($datosAdaptados['tiene_tope']) {
                    $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_1']) ? round((float)$datos['porcentaje_1'] / 100, 4) : null;
                    $datosAdaptados['porcentaje_2'] = isset($datos['porcentaje_2']) ? round((float)$datos['porcentaje_2'] / 100, 4) : null;
                    $p1_display = isset($datos['porcentaje_1']) ? $datos['porcentaje_1'] : 0;
                    $p2_display = isset($datos['porcentaje_2']) ? $datos['porcentaje_2'] : 0;
                    $datosAdaptados['observaciones'] = "Comisión sobre local con tope: {$p1_display}% / {$p2_display}%";
                } else {    
                    $datosAdaptados['porcentaje_1'] = isset($datos['porcentaje_unico']) ? round((float)$datos['porcentaje_unico'] / 100, 4) : null;
                    $datosAdaptados['porcentaje_2'] = null;
                    $p_display = isset($datos['porcentaje_unico']) ? $datos['porcentaje_unico'] : 0;
                    $datosAdaptados['observaciones'] = "Comisión sobre local sin tope: {$p_display}%";
                }
                break;

            case 18:
            case 38: // Premios - Ajuste General
                $datosAdaptados['valor_numerico'] = (isset($datos['importe']) && $datos['importe'] !== '') ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Premios - Ajuste General';
                if (isset($datos['aplica_plus'])) {
                    $datosAdaptados['aplica_plus'] = (int)$datos['aplica_plus'] ? 1 : 0;
                }
                break;

            case 53: // Reemplazo - siempre temporario
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : 'Reemplazo';
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['tipo_nuevo_puesto'] = 'temporario';
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                break;

            case 55: // A prueba - siempre temporario
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? $datos['puesto'] : 'A prueba';
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['tipo_nuevo_puesto'] = 'temporario';
                $datosAdaptados['fecha_vigencia_hasta'] = $this->formatearFechaParaSQL($datos['fecha_vigencia_hasta'] ?? '');
                break;

            case 54: // Aumento Salarial / Sueldo Básico
                $datosAdaptados['fecha_vigencia'] = $this->formatearFechaParaSQL($datos['fecha_vigencia'] ?? '');
                $datosAdaptados['puesto'] = 'Aumento Salarial';
                if (isset($datos['tipo_aumento'])) {
                    if ($datos['tipo_aumento'] === 'basico') {
                        $datosAdaptados['escala_sueldo_basico'] = $datos['escala_sueldo_basico'] ?? null;
                        $datosAdaptados['porcentaje_1'] = null;
                        $datosAdaptados['valor_numerico'] = null;
                    } elseif ($datos['tipo_aumento'] === 'porcentaje') {
                        if (!empty($datos['porcentaje_1'])) {
                            $datosAdaptados['porcentaje_1'] = round((float)$datos['porcentaje_1'] / 100, 4);
                        }
                        $datosAdaptados['valor_numerico'] = null;
                    } elseif ($datos['tipo_aumento'] === 'monto') {
                        if (!empty($datos['valor_numerico'])) {
                            $datosAdaptados['valor_numerico'] = round((float)$datos['valor_numerico'], 2);
                        }
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
     * Guardar detalle de sucursales para Comisión Individual (Supervisoras)
     */
    private function guardarComisionSucursales($novedadId, $sucursales) {
        $sql = "INSERT INTO FP_NOVEDADES_COMISION_SUCURSALES
                    (novedad_id, nro_sucursal, nombre_sucursal, porcentaje_1, tiene_tope, porcentaje_2)
                VALUES (?, ?, ?, ?, ?, ?)";
        foreach ($sucursales as $suc) {
            $nro = (int)$suc['nro_sucursal'];
            $nombre = !empty($suc['nombre_sucursal']) ? trim($suc['nombre_sucursal']) : $this->obtenerNombreSucursal($nro);
            $p1 = round((float)$suc['porcentaje_1'] / 100, 4);
            $tieneTope = !empty($suc['tiene_tope']) ? 1 : 0;
            $p2 = ($tieneTope && isset($suc['porcentaje_2']) && $suc['porcentaje_2'] !== '')
                ? round((float)$suc['porcentaje_2'] / 100, 4)
                : null;
            $this->db->query($sql, [$novedadId, $nro, $nombre, $p1, $tieneTope, $p2]);
        }
    }

    /**
     * Guardar escala global de 3 niveles para Premio Local
     */
    private function guardarPremioLocalEscala($novedadId, $escala) {
        $descripciones = [
            'PRIMER_RANKING' => '1ro en el ranking al cierre del mes',
            'CUMPLE_OBJETIVO' => 'Cumple con el objetivo',
            'PRIMER_NO_LLEGA' => '1ro en el ranking que NO llegó al objetivo (más cercano a 0%)'
        ];
        $ordenes = [
            'PRIMER_RANKING' => 1,
            'CUMPLE_OBJETIVO' => 2,
            'PRIMER_NO_LLEGA' => 3
        ];

        $sql = "INSERT INTO FP_NOVEDADES_PREMIO_LOCAL_ESCALA
                    (novedad_id, nivel_codigo, nivel_orden, nivel_descripcion, importe_enc_sub, importe_vend)
                VALUES (?, ?, ?, ?, ?, ?)";

        foreach ($escala as $tramo) {
            $cod = $tramo['nivel_codigo'];
            $orden = $ordenes[$cod] ?? ($tramo['nivel_orden'] ?? 1);
            $desc = $descripciones[$cod] ?? ($tramo['nivel_descripcion'] ?? $cod);
            $impEncSub = round((float)$tramo['importe_enc_sub'], 2);
            $impVend = round((float)$tramo['importe_vend'], 2);
            $this->db->query($sql, [$novedadId, $cod, $orden, $desc, $impEncSub, $impVend]);
        }
    }

    /**
     * Inserción interna dentro de una transacción ya iniciada
     */
    private function insertarNovedadEnTransaccion($datos) {
        $tipoNovedad = (int)$datos['tipo_novedad'];
        $this->validarPermisosTipoNovedad($tipoNovedad);

        $tipoNovedadInfo = $this->getTipoNovedadById($tipoNovedad);
        if (!$tipoNovedadInfo) {
            throw new Exception("Tipo de novedad no encontrado");
        }

        if (!class_exists('PeriodoUtils')) {
            require_once __DIR__ . '/PeriodoUtils.php';
        }

        if (!empty($datos['periodo_mes']) && !empty($datos['periodo_anio'])) {
            $periodo = [
                'month' => (int)$datos['periodo_mes'],
                'year' => (int)$datos['periodo_anio']
            ];
        } else {
            $periodo = PeriodoUtils::calcularPeriodoSegunTipoCompleto($tipoNovedadInfo);
        }

        $datosAdaptados = $this->adaptarDatosParaInsercionActualizado($datos);

        $sql = "INSERT INTO novedades (
                    legajo, nombre, apellido, fecha_vigencia, puesto, 
                    valor_numerico, fecha_permiso, compensa, tipo_permiso,
                    observaciones, periodo_mes, periodo_anio, fecha_creacion, tipo_novedad, 
                    tipo_nuevo_puesto, estado, fecha_vigencia_hasta, centro_costos,
                    tiene_tope, porcentaje_1, porcentaje_2, tipo_comision, 
                    aplica_vendedora, aplica_sub_encargada, aplica_encargada,
                    es_global, lote_id, etapa_multiple, convenio, escala_sueldo_basico, aplica_plus
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
            $datosAdaptados['tipo_novedad'],
            $datosAdaptados['tipo_nuevo_puesto'],
            self::ESTADO_ENVIADA,
            $datosAdaptados['fecha_vigencia_hasta'],
            $datosAdaptados['centro_costos'],
            $datosAdaptados['tiene_tope'],
            $datosAdaptados['porcentaje_1'],
            $datosAdaptados['porcentaje_2'],
            $datosAdaptados['tipo_comision'],
            $datosAdaptados['aplica_vendedora'],
            $datosAdaptados['aplica_sub_encargada'],
            $datosAdaptados['aplica_encargada'],
            $datosAdaptados['es_global'],
            $datosAdaptados['lote_id'],
            $datosAdaptados['etapa_multiple'],
            $datosAdaptados['convenio'],
            $datosAdaptados['escala_sueldo_basico'],
            $datosAdaptados['aplica_plus']
        ];

        $novedadId = $this->db->insert($sql, $params);

        if (($tipoNovedad === 36 || $tipoNovedad === 16) && !empty($datos['comision_sucursales']) && is_array($datos['comision_sucursales'])) {
            $this->guardarComisionSucursales($novedadId, $datos['comision_sucursales']);
        }

        if (($tipoNovedad === 35 || $tipoNovedad === 15) && !empty($datosAdaptados['es_global']) && !empty($datos['escala_premios'])) {
            $this->guardarPremioLocalEscala($novedadId, $datos['escala_premios']);
        }

        return $novedadId;
    }

    /**
     * Crear nueva novedad - ACTUALIZADO PARA NUEVOS CAMPOS Y TABLAS FP_
     */
    public function crearNovedadActualizada($datos) {
        $errores = $this->validarDatos($datos);
        if (!empty($errores)) {
            return ['success' => false, 'error' => implode(', ', $errores)];
        }
        try {
            $this->db->beginTransaction();
            $novedadId = $this->insertarNovedadEnTransaccion($datos);
            $this->db->commit();
            return ['success' => true, 'id' => $novedadId];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crear lote atómico de novedades (para Carga Múltiple Checklist)
     */
    public function crearNovedadesLote($items) {
        if (empty($items) || !is_array($items)) {
            return ['success' => false, 'error' => 'Debe incluir al menos una novedad en el lote'];
        }

        $erroresTotales = [];
        foreach ($items as $idx => $item) {
            $errores = $this->validarDatos($item);
            if (!empty($errores)) {
                $erroresTotales[] = 'Ítem #' . ($idx + 1) . ': ' . implode(', ', $errores);
            }
        }
        if (!empty($erroresTotales)) {
            return ['success' => false, 'error' => implode(' | ', $erroresTotales)];
        }

        $loteId = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        try {
            $this->db->beginTransaction();
            $ids = [];
            foreach ($items as $item) {
                $item['lote_id'] = $loteId;
                $ids[] = $this->insertarNovedadEnTransaccion($item);
            }
            $this->db->commit();
            return ['success' => true, 'lote_id' => $loteId, 'ids' => $ids, 'count' => count($ids)];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener novedades del período actual - DEDUPLICADO Y ACTUALIZADO
     */
    public function getNovedadesPeriodoActualActualizado($filtros = []) {
        try {
            $periodo = $this->getPeriodoActual();
            $tipoUsuario = Usuario::getTipoUsuario();
            $empSubquery = $this->getSubqueryEmpleadosUnicos('rle');
            
            $selectCols = "n.id, n.legajo, n.tipo_novedad, n.valor_numerico, 
                           n.puesto, n.fecha_vigencia, n.fecha_vigencia_hasta, n.fecha_permiso, n.compensa, 
                           n.observaciones, n.fecha_creacion, n.fecha_modificacion, n.tipo_nuevo_puesto,
                           n.periodo_mes, n.periodo_anio, n.estado,
                           n.tiene_tope, n.porcentaje_1, n.porcentaje_2, n.tipo_comision,
                           n.aplica_vendedora, n.aplica_sub_encargada, n.aplica_encargada,
                           n.es_global, n.lote_id, n.etapa_multiple, n.convenio, n.escala_sueldo_basico, n.aplica_plus,
                           tn.descripcion as tipo_descripcion,
                           CASE WHEN n.es_global = 1 THEN 'TODOS LOS LOCALES' ELSE COALESCE(rle.NOMBRE, n.nombre) END as nombre,
                           CASE WHEN n.es_global = 1 THEN '' ELSE COALESCE(rle.APELLIDO, n.apellido) END as apellido,
                           CASE WHEN n.es_global = 1 THEN 'TODOS' ELSE COALESCE(rle.COD_DEPARTAMENTO, n.centro_costos) END as codigo_centro_costos,
                           CASE WHEN n.es_global = 1 THEN 'Todos los locales' ELSE rle.DESC_DEPARTAMENTO END as descripcion_centro_costos";

            if ($tipoUsuario == Usuario::TIPO_RRHH) {
                $sql = "SELECT {$selectCols}
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN {$empSubquery} ON n.legajo = rle.NRO_LEGAJO AND rle.rn = 1 AND ISNULL(n.es_global, 0) = 0
                        WHERE n.periodo_mes = ? AND n.periodo_anio = ?";
            } else {
                $campoPermiso = $this->getCampoPermisoUsuario($tipoUsuario);
                
                $sql = "SELECT {$selectCols}
                        FROM novedades n 
                        INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                        LEFT JOIN {$empSubquery} ON n.legajo = rle.NRO_LEGAJO AND rle.rn = 1 AND ISNULL(n.es_global, 0) = 0
                        WHERE n.periodo_mes = ? AND n.periodo_anio = ? AND tn.$campoPermiso = 1";
            }
            
            $params = [$periodo['periodo_mes'], $periodo['periodo_anio']];

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

            $idsComision = [];
            $idsPremio = [];
            foreach ($novedades as $nov) {
                if ((int)$nov['tipo_novedad'] === 36 || (int)$nov['tipo_novedad'] === 16) {
                    $idsComision[] = (int)$nov['id'];
                }
                if (((int)$nov['tipo_novedad'] === 35 || (int)$nov['tipo_novedad'] === 15) && !empty($nov['es_global'])) {
                    $idsPremio[] = (int)$nov['id'];
                }
            }
            $mapaComisiones = $this->obtenerMapaComisionSucursales($idsComision);
            $mapaPremios = $this->obtenerMapaPremioLocalEscala($idsPremio);
            
            foreach ($novedades as &$novedad) {
                $nid = (int)$novedad['id'];
                $novedad['comision_sucursales'] = $mapaComisiones[$nid] ?? [];
                $novedad['premio_local_escala'] = $mapaPremios[$nid] ?? [];
                $novedad['valor_display'] = $this->calcularValorDisplayNovedad($novedad);
                $novedad['estado_numero'] = $this->mapearEstadoANumero($novedad['estado']);
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
            case 12:
            case 32: // Plus de caja
                $detalle['tipo'] = 'Plus de caja';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                $detalle['fecha_vigencia_hasta'] = !empty($novedad['fecha_vigencia_hasta'])
                    ? $this->formatearFecha($novedad['fecha_vigencia_hasta'])
                    : 'Sin vencimiento';
                if (preg_match('/Tipo:\s*([^-\n]+)/i', (string)$novedad['observaciones'], $matches)) {
                    $detalle['tipo_plus'] = ucfirst(trim($matches[1]));
                }
                break;

            case 13:
            case 33: // Plus de Sub-Encargada
                $detalle['tipo'] = 'Plus de Sub-Encargada';
                $detalle['aplica'] = ($novedad['aplica_plus'] !== null) ? ($novedad['aplica_plus'] ? 'SI' : 'NO') : 'SI';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                $detalle['fecha_vigencia_hasta'] = !empty($novedad['fecha_vigencia_hasta'])
                    ? $this->formatearFecha($novedad['fecha_vigencia_hasta'])
                    : 'Sin vencimiento';
                break;

            case 14:
            case 34: // Plus de Encargada
                $detalle['tipo'] = 'Plus de Encargada';
                $detalle['aplica'] = ($novedad['aplica_plus'] !== null) ? ($novedad['aplica_plus'] ? 'SI' : 'NO') : 'SI';
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                $detalle['fecha_vigencia_hasta'] = !empty($novedad['fecha_vigencia_hasta'])
                    ? $this->formatearFecha($novedad['fecha_vigencia_hasta'])
                    : 'Sin vencimiento';
                break;

            case 15:
            case 35: // Premio Local
                $detalle['tipo'] = 'Premio Local';
                $detalle['es_global'] = !empty($novedad['es_global']);
                if (!empty($novedad['es_global'])) {
                    $detalle['escala'] = $novedad['premio_local_escala'] ?? [];
                } else {
                    $aplicaciones = [];
                    if (!empty($novedad['aplica_vendedora'])) $aplicaciones[] = 'Vendedora';
                    if (!empty($novedad['aplica_encargada'])) $aplicaciones[] = 'Encargada';
                    if (!empty($novedad['aplica_sub_encargada'])) $aplicaciones[] = 'Sub-Encargada';
                    $detalle['aplica_a'] = !empty($aplicaciones) ? implode(', ', $aplicaciones) : 'No especificado';
                }
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 16:
            case 36: // Comisión Individual
                $detalle['tipo'] = 'Comisión Individual';
                $detalle['tipo_comision'] = 'Individual';
                if (!empty($novedad['comision_sucursales'])) {
                    $detalle['sucursales'] = $novedad['comision_sucursales'];
                } else {
                    $detalle['porcentaje_individual'] = !empty($novedad['porcentaje_1'])
                        ? number_format((float)$novedad['porcentaje_1'] * 100, 2) . '%'
                        : '0%';
                }
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 17:
            case 37: // Comisión sobre Local
                $detalle['tipo'] = 'Comisión sobre el Local';
                $detalle['tipo_comision'] = 'Sobre el Local';
                if (!empty($novedad['tiene_tope'])) {
                    $detalle['estructura'] = 'Con tope';
                    $detalle['porcentaje_1'] = !empty($novedad['porcentaje_1']) ? number_format((float)$novedad['porcentaje_1'] * 100, 2) . '%' : '0%';
                    $detalle['porcentaje_2'] = !empty($novedad['porcentaje_2']) ? number_format((float)$novedad['porcentaje_2'] * 100, 2) . '%' : '0%';
                } else {
                    $detalle['estructura'] = 'Sin tope';
                    $detalle['porcentaje_unico'] = !empty($novedad['porcentaje_1']) ? number_format((float)$novedad['porcentaje_1'] * 100, 2) . '%' : '0%';
                }
                if (!empty($novedad['fecha_vigencia'])) {
                    $detalle['fecha_vigencia'] = $this->formatearFecha($novedad['fecha_vigencia']);
                }
                break;

            case 18:
            case 38: // Premios - Ajuste General
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
