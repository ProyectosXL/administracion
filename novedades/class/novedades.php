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
                $datosAdaptados['fecha_vigencia'] = isset($datos['fecha_vigencia']) ? $datos['fecha_vigencia'] : null;
                $datosAdaptados['puesto'] = 'Cambio sucursal';
                if (isset($datos['nueva_sucursal'])) {
                    $datosAdaptados['observaciones'] .= " - Nueva sucursal: " . $datos['nueva_sucursal'];
                }
                break;

            case 2: // Nuevo puesto
                $datosAdaptados['fecha_vigencia'] = isset($datos['fecha_vigencia']) ? $datos['fecha_vigencia'] : null;
                $datosAdaptados['puesto'] = isset($datos['puesto']) ? substr($datos['puesto'], 0, 50) : '';
                break;

            case 3: // Nuevo salario neto
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = isset($datos['fecha_vigencia']) ? $datos['fecha_vigencia'] : null;
                $datosAdaptados['puesto'] = 'Ajuste Salario';
                break;

            case 4: // Ajuste de premios
                $datosAdaptados['valor_numerico'] = isset($datos['importe']) ? (float)$datos['importe'] : null;
                $datosAdaptados['fecha_vigencia'] = isset($datos['fecha_vigencia']) ? $datos['fecha_vigencia'] : null;
                $datosAdaptados['puesto'] = 'Premio';
                break;

            case 5: // Horas extras
                // Convertir horas a valor numérico (ejemplo: $1000 por hora extra)
                if (isset($datos['cantidad_horas'])) {
                    $tarifaHora = 1000;
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_horas'] * $tarifaHora;
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_horas']} horas extras";
                }
                $datosAdaptados['fecha_vigencia'] = isset($datos['fecha_vigencia']) ? $datos['fecha_vigencia'] : null;
                $datosAdaptados['puesto'] = 'Horas Extras';
                break;

            case 6: // Horas adicionales
                // Convertir horas a valor numérico (ejemplo: $800 por hora adicional)
                if (isset($datos['cantidad_horas'])) {
                    $tarifaHora = 800;
                    $datosAdaptados['valor_numerico'] = (float)$datos['cantidad_horas'] * $tarifaHora;
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_horas']} horas adicionales";
                }
                $datosAdaptados['puesto'] = 'Horas Adicionales';
                break;

            case 7: // Permisos
                $datosAdaptados['fecha_permiso'] = isset($datos['fecha_permiso']) ? $datos['fecha_permiso'] : null;
                $datosAdaptados['compensa'] = isset($datos['compensa']) ? (bool)$datos['compensa'] : null;
                $datosAdaptados['tipo_permiso'] = 'personal';
                $datosAdaptados['puesto'] = 'Permiso';
                break;

            case 8: // Cortes
                // Convertir cortes a descuento (ejemplo: -$500 por corte)
                if (isset($datos['cantidad_cortes'])) {
                    $descontoPorCorte = 500;
                    $datosAdaptados['valor_numerico'] = -(int)$datos['cantidad_cortes'] * $descontoPorCorte;
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_cortes']} cortes";
                }
                $datosAdaptados['puesto'] = 'Cortes';
                break;

            case 9: // Producción 25%
                // Convertir unidades a valor (ejemplo: $150 por unidad al 25%)
                if (isset($datos['cantidad_unidades'])) {
                    $valorUnidad = 150;
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_unidades'] * $valorUnidad;
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_unidades']} unidades (25%)";
                }
                $datosAdaptados['puesto'] = 'Producción 25%';
                break;

            case 10: // Producción 50%
                // Convertir unidades a valor (ejemplo: $300 por unidad al 50%)
                if (isset($datos['cantidad_unidades'])) {
                    $valorUnidad = 300;
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_unidades'] * $valorUnidad;
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_unidades']} unidades (50%)";
                }
                $datosAdaptados['puesto'] = 'Producción 50%';
                break;

            case 11: // Producción 100%
                // Convertir unidades a valor (ejemplo: $500 por unidad al 100%)
                if (isset($datos['cantidad_unidades'])) {
                    $valorUnidad = 500;
                    $datosAdaptados['valor_numerico'] = (int)$datos['cantidad_unidades'] * $valorUnidad;
                    $datosAdaptados['observaciones'] .= " - {$datos['cantidad_unidades']} unidades (100%)";
                }
                $datosAdaptados['puesto'] = 'Producción 100%';
                break;

            default:
                $datosAdaptados['puesto'] = 'Novedad General';
                break;
        }

        return $datosAdaptados;
    }

    /**
     * Obtener novedades del periodo actual - CON NOMBRES DE SUCURSAL REALES
     */
    public function getNovedadesPeriodoActual($filtros = []) {
        try {
            $periodo = $this->getPeriodoActual();
            
            // Consulta más simple para evitar errores de conversión
            $sql = "SELECT n.*, tn.descripcion as tipo_descripcion, 
                           rle.NOMBRE, rle.APELLIDO
                    FROM novedades n 
                    INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                    LEFT JOIN [TANGO-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL rle ON n.legajo = rle.NRO_LEGAJO
                    WHERE n.periodo_mes = ? AND n.periodo_anio = ?";
            
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
                
                // Agregar valor display basado en valor_numerico y tipo
                $tipoNovedad = (int)$novedad['tipo_novedad'];
                $valorNumerico = (float)$novedad['valor_numerico'];
                
                if ($valorNumerico != 0) {
                    if ($tipoNovedad >= 5 && $tipoNovedad <= 6) {
                        // Horas extras/adicionales - calcular horas desde valor
                        if ($tipoNovedad == 5) {
                            $horas = round($valorNumerico / 1000, 2); // $1000 por hora extra
                            $novedad['valor_display'] = $horas . ' horas extras';
                        } else {
                            $horas = round($valorNumerico / 800, 2); // $800 por hora adicional
                            $novedad['valor_display'] = $horas . ' horas adicionales';
                        }
                    } elseif ($tipoNovedad == 8) {
                        // Cortes - calcular cortes desde valor negativo
                        $cortes = abs(round($valorNumerico / 500)); // $500 por corte
                        $novedad['valor_display'] = $cortes . ' cortes';
                    } elseif ($tipoNovedad >= 9 && $tipoNovedad <= 11) {
                        // Producción - calcular unidades desde valor
                        if ($tipoNovedad == 9) {
                            $unidades = round($valorNumerico / 150); // $150 por unidad 25%
                            $novedad['valor_display'] = $unidades . ' unidades (25%)';
                        } elseif ($tipoNovedad == 10) {
                            $unidades = round($valorNumerico / 300); // $300 por unidad 50%
                            $novedad['valor_display'] = $unidades . ' unidades (50%)';
                        } else {
                            $unidades = round($valorNumerico / 500); // $500 por unidad 100%
                            $novedad['valor_display'] = $unidades . ' unidades (100%)';
                        }
                    } else {
                        // Valores monetarios (salarios, premios, etc.)
                        $novedad['valor_display'] = '$' . number_format($valorNumerico, 2);
                    }
                } else {
                    $novedad['valor_display'] = 'N/A';
                }
                
                // Agregar fecha display
                if (!empty($novedad['fecha_vigencia'])) {
                    if ($novedad['fecha_vigencia'] instanceof DateTime) {
                        $novedad['fecha_display'] = $novedad['fecha_vigencia']->format('d/m/Y');
                    } else {
                        $fecha = new DateTime($novedad['fecha_vigencia']);
                        $novedad['fecha_display'] = $fecha->format('d/m/Y');
                    }
                } elseif (!empty($novedad['fecha_permiso'])) {
                    if ($novedad['fecha_permiso'] instanceof DateTime) {
                        $novedad['fecha_display'] = $novedad['fecha_permiso']->format('d/m/Y');
                    } else {
                        $fecha = new DateTime($novedad['fecha_permiso']);
                        $novedad['fecha_display'] = $fecha->format('d/m/Y');
                    }
                } else {
                    if ($novedad['fecha_creacion'] instanceof DateTime) {
                        $novedad['fecha_display'] = $novedad['fecha_creacion']->format('d/m/Y');
                    } else {
                        $fecha = new DateTime($novedad['fecha_creacion']);
                        $novedad['fecha_display'] = $fecha->format('d/m/Y');
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
            
            // Guardar en cache
            $this->sucursalesCache = $mapa;
            return $mapa;
        } catch (Exception $e) {
            error_log("Error obteniendo mapa de sucursales: " . $e->getMessage());
            // Retornar un mapa básico en caso de error
            $this->sucursalesCache = [];
            return [];
        }
    }

    /**
     * Obtener novedad por ID
     */
    public function getNovedadById($id) {
        $sql = "SELECT n.*, tn.descripcion as tipo_descripcion 
                FROM novedades n 
                INNER JOIN tipos_novedad tn ON n.tipo_novedad = tn.id
                WHERE n.id = ?";
        
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->fetch();
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

        return $errores;
    }
}
?>