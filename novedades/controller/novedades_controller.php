<?php
/**
 * Controlador para manejo de Novedades
 * /novedades/controller/novedades_controller.php
 */

// Iniciar buffer de salida y limpiar cualquier salida previa
ob_start();
ob_clean();

// Suprimir warnings que puedan interferir con JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Configurar headers para CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Incluir clases necesarias
require_once '../class/Database.php';
require_once '../class/Novedades.php';
require_once '../class/Usuario.php';

// Función para enviar respuesta JSON
function sendResponse($success, $data = null, $message = '', $httpCode = null) {
    // Limpiar cualquier output previo que pueda interferir con JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Configurar código de respuesta HTTP - usar el proporcionado o determinar por success
    if ($httpCode !== null) {
        http_response_code($httpCode);
    } else {
        http_response_code($success ? 200 : 400);
    }
    
    // Enviar JSON
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Función para manejar errores
function handleError($message, $error = null) {
    if ($error) {
        error_log("Error en controlador: $message - " . $error->getMessage());
        // En modo debug, incluir más detalles
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            $message .= " | Detalles: " . $error->getMessage() . " en " . $error->getFile() . ":" . $error->getLine();
        }
    }
    
    // Usar sendResponse con código de error 500 específico
    sendResponse(false, null, $message, 500);
}

try {
    // Inicializar configuración de usuario ANTES de instanciar otras clases
    require_once '../config/usuario_config.php';
    
    // Instanciar base de datos y clase de novedades
    $db = Database::getInstance();
    $novedades = new Novedades($db);
    
    // Obtener acción
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Log para debugging
    error_log("Acción solicitada: $action");
    
    if (empty($action)) {
        handleError('No se especificó una acción válida');
    }
    
    switch ($action) {
        case 'get_sucursales':
            try {
                $sucursales = $novedades->getSucursales();
                sendResponse(true, $sucursales);
            } catch (Exception $e) {
                handleError('Error obteniendo sucursales', $e);
            }
            break;

        case 'get_sucursales_con_casa_central':
            try {
                $sucursales = $novedades->getSucursalesConCasaCentral();
                sendResponse(true, $sucursales);
            } catch (Exception $e) {
                handleError('Error obteniendo sucursales con casa central', $e);
            }
            break;

        case 'get_tipos_novedad':
            try {
                $tipos = $novedades->getTiposNovedad();
                sendResponse(true, $tipos);
            } catch (Exception $e) {
                handleError('Error obteniendo tipos de novedad', $e);
            }
            break;

        case 'get_puestos':
            try {
                $puestos = $novedades->getPuestosDisponibles();
                sendResponse(true, $puestos);
            } catch (Exception $e) {
                handleError('Error obteniendo puestos', $e);
            }
            break;

        case 'buscar_empleados_select2':
            try {
                $termino = $_GET['q'] ?? $_GET['term'] ?? '';
                $limit = (int)($_GET['limit'] ?? 10);
                
                $empleados = $novedades->buscarEmpleadosSelect2($termino, $limit);
                sendResponse(true, $empleados);
            } catch (Exception $e) {
                handleError('Error buscando empleados para select2', $e);
            }
            break;

        case 'buscar_empleado':
            if (empty($_GET['legajo'])) {
                handleError('Legajo requerido');
            }
            
            try {
                $empleado = $novedades->buscarEmpleado($_GET['legajo']);
                if (!$empleado) {
                    handleError('Empleado no encontrado');
                }
                sendResponse(true, $empleado);
            } catch (Exception $e) {
                handleError('Error buscando empleado', $e);
            }
            break;

        case 'crear_novedad':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                handleError('Método no permitido');
            }

            try {
                // Obtener datos del POST
                $inputData = file_get_contents('php://input');
                $datos = json_decode($inputData, true);
                
                if (!$datos) {
                    handleError('Datos JSON inválidos');
                }
                
                // Log para debugging
                error_log("Datos recibidos: " . print_r($datos, true));
                
                // Log específico para permisos
                if (isset($datos['tipo_novedad']) && $datos['tipo_novedad'] == 7) {
                    error_log("🔍 CONTROLADOR PERMISOS - fecha_permiso: " . 
                             (isset($datos['fecha_permiso']) ? var_export($datos['fecha_permiso'], true) : 'NO EXISTE'));
                }
                
                // Validar datos
                $errores = $novedades->validarDatos($datos);
                if (!empty($errores)) {
                    handleError('Errores de validación: ' . implode(', ', $errores));
                }

                $resultado = $novedades->crearNovedad($datos);
                
                if ($resultado['success']) {
                    sendResponse(true, ['id' => $resultado['id']], 'Novedad creada exitosamente');
                } else {
                    handleError($resultado['error']);
                }
            } catch (Exception $e) {
                handleError('Error creando novedad', $e);
            }
            break;

        case 'get_novedades':
            try {
                $filtros = [
                    'legajo' => $_GET['legajo'] ?? '',
                    'sucursal' => $_GET['sucursal'] ?? '',
                    'tipo_novedad' => $_GET['tipo_novedad'] ?? ''
                ];
                
                $novedadesList = $novedades->getNovedadesPeriodoActual($filtros);
                sendResponse(true, $novedadesList);
            } catch (Exception $e) {
                handleError('Error obteniendo novedades', $e);
            }
            break;

        case 'get_novedad':
            if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
                handleError('ID de novedad requerido y debe ser numérico');
            }
            
            try {
                $id = (int)$_GET['id'];
                $novedad = $novedades->getNovedadById($id);
                if (!$novedad) {
                    handleError('Novedad no encontrada');
                }
                sendResponse(true, $novedad);
            } catch (Exception $e) {
                handleError('Error obteniendo novedad', $e);
            }
            break;

        case 'test':
            sendResponse(true, [
                'message' => 'Endpoint funcionando correctamente',
                'timestamp' => date('Y-m-d H:i:s'),
                'php_version' => phpversion(),
                'correcciones' => 'aplicadas'
            ]);
            break;

        case 'editar_novedad':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                handleError('Método no permitido');
            }

            try {
                // Obtener datos del POST
                $inputData = file_get_contents('php://input');
                $datos = json_decode($inputData, true);
                
                if (!$datos || !isset($datos['id']) || !is_numeric($datos['id'])) {
                    handleError('ID de novedad requerido y debe ser numérico');
                }

                $resultado = $novedades->editarNovedad($datos['id'], $datos);
                
                if ($resultado['success']) {
                    sendResponse(true, ['id' => $datos['id']], 'Novedad actualizada exitosamente');
                } else {
                    handleError($resultado['error']);
                }
            } catch (Exception $e) {
                handleError('Error editando novedad', $e);
            }
            break;

        case 'eliminar_novedad':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                handleError('Método no permitido');
            }

            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                handleError('ID de novedad requerido y debe ser numérico');
            }
            
            try {
                $id = (int)$_POST['id'];
                $resultado = $novedades->eliminarNovedad($id);
                
                if ($resultado['success']) {
                    sendResponse(true, null, 'Novedad eliminada exitosamente');
                } else {
                    handleError($resultado['error']);
                }
            } catch (Exception $e) {
                handleError('Error eliminando novedad', $e);
            }
            break;

        case 'get_periodo_actual':
            try {
                $periodo = $novedades->getPeriodoActual();
                sendResponse(true, $periodo);
            } catch (Exception $e) {
                handleError('Error obteniendo período actual', $e);
            }
            break;

        case 'test_empleados':
            try {
                // Probar búsqueda de empleados
                $empleados = $novedades->buscarEmpleadosSelect2('', 5);
                sendResponse(true, [
                    'message' => 'Test de empleados exitoso',
                    'count' => count($empleados),
                    'data' => $empleados
                ]);
            } catch (Exception $e) {
                handleError('Error en test de empleados', $e);
            }
            break;

        case 'actualizar_permisos_rrhh':
            try {
                $resultado = $novedades->actualizarPermisosRRHH();
                sendResponse($resultado['success'], $resultado, $resultado['message']);
            } catch (Exception $e) {
                handleError('Error actualizando permisos RRHH', $e);
            }
            break;

        // GESTIÓN DE TIPOS DE NOVEDAD (Solo RRHH)
        case 'get_tipos_novedad_gestion':
            try {
                $tipos = $novedades->getAllTiposNovedadParaGestion();
                sendResponse(true, $tipos);
            } catch (Exception $e) {
                handleError('Error obteniendo tipos de novedad para gestión', $e);
            }
            break;

        case 'actualizar_tipo_novedad':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                handleError('Método no permitido');
            }

            try {
                // Limpiar cualquier output previo
                while (ob_get_level()) {
                    ob_end_clean();
                }
                ob_start();
                
                $inputData = file_get_contents('php://input');
                
                if (empty($inputData)) {
                    ob_end_clean();
                    handleError('No se recibieron datos');
                }
                
                $datos = json_decode($inputData, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    ob_end_clean();
                    handleError('JSON inválido: ' . json_last_error_msg());
                }
                
                if (!$datos || !isset($datos['id'])) {
                    ob_end_clean();
                    handleError('Datos inválidos o ID faltante. Recibido: ' . print_r($datos, true));
                }
                
                // Validar campos requeridos
                $camposRequeridos = ['codigo', 'descripcion', 'activo', 'user_adm', 'user_com', 'user_prod', 'user_rrhh'];
                foreach ($camposRequeridos as $campo) {
                    if (!array_key_exists($campo, $datos)) {
                        ob_end_clean();
                        handleError("Campo requerido faltante: $campo");
                    }
                }
                
                // Log para debug
                error_log("Actualizando tipo novedad ID: " . $datos['id'] . " con datos: " . json_encode($datos));
                
                // Capturar cualquier output no deseado de la actualización
                $extraOutput = ob_get_contents();
                ob_clean();
                
                // Log cualquier output extra que podría estar causando problemas
                if (!empty($extraOutput)) {
                    error_log("⚠️ Output extra capturado en actualizar_tipo_novedad: " . var_export($extraOutput, true));
                }
                
                $resultado = $novedades->actualizarTipoNovedad($datos['id'], $datos);
                
                // Capturar output de la función
                $outputDespuesActualizar = ob_get_contents();
                ob_end_clean();
                
                if (!empty($outputDespuesActualizar)) {
                    error_log("⚠️ Output después de actualización: " . var_export($outputDespuesActualizar, true));
                }
                
                if ($resultado) {
                    sendResponse(true, ['message' => 'Tipo de novedad actualizado correctamente']);
                } else {
                    handleError('No se pudo actualizar el tipo de novedad - Sin cambios realizados');
                }
            } catch (Exception $e) {
                // Limpiar buffer en caso de error
                while (ob_get_level()) {
                    ob_end_clean();
                }
                handleError('Error actualizando tipo de novedad', $e);
            }
            break;

        case 'crear_tipo_novedad':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                handleError('Método no permitido');
            }

            try {
                $inputData = file_get_contents('php://input');
                $datos = json_decode($inputData, true);
                
                if (!$datos) {
                    handleError('Datos JSON inválidos');
                }
                
                $nuevoId = $novedades->crearTipoNovedad($datos);
                if ($nuevoId) {
                    sendResponse(true, ['id' => $nuevoId, 'message' => 'Tipo de novedad creado correctamente']);
                } else {
                    handleError('No se pudo crear el tipo de novedad');
                }
            } catch (Exception $e) {
                handleError('Error creando tipo de novedad', $e);
            }
            break;

        case 'eliminar_tipo_novedad':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                handleError('Método no permitido');
            }

            try {
                $inputData = file_get_contents('php://input');
                $datos = json_decode($inputData, true);
                
                if (!$datos || !isset($datos['id'])) {
                    handleError('ID faltante');
                }
                
                $resultado = $novedades->eliminarTipoNovedad($datos['id']);
                if ($resultado) {
                    sendResponse(true, ['message' => 'Tipo de novedad eliminado correctamente']);
                } else {
                    handleError('No se pudo eliminar el tipo de novedad');
                }
            } catch (Exception $e) {
                handleError('Error eliminando tipo de novedad', $e);
            }
            break;

        case 'get_tipos_usuario':
            try {
                $tipos = $novedades->getTiposUsuarioDisponibles();
                sendResponse(true, $tipos);
            } catch (Exception $e) {
                handleError('Error obteniendo tipos de usuario', $e);
            }
            break;

        case 'test':
            // Endpoint de prueba
            sendResponse(true, ['message' => 'Controlador funcionando correctamente', 'timestamp' => date('Y-m-d H:i:s')]);
            break;

        default:
            handleError("Acción no válida: '$action'");
    }
    
} catch (Exception $e) {
    // Log detallado del error
    $errorDetails = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    error_log("Error general del sistema: " . print_r($errorDetails, true));
    
    // Enviar error con más detalles en modo desarrollo
    $isDev = isset($_GET['debug']) || (defined('DEBUG') && DEBUG);
    $message = $isDev ? 
        'Error del sistema: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine() :
        'Error general del sistema';
        
    handleError($message, $e);
}
?>