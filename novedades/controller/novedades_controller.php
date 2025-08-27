<?php
/**
 * Controlador para manejo de Novedades
 * /novedades/controller/novedades_controller.php
 */

// Iniciar buffer de salida y limpiar cualquier salida previa - MEJORADO
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

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
require_once __DIR__ . '/../class/Database.php';
require_once __DIR__ . '/../class/Novedades.php';
require_once __DIR__ . '/../class/Usuario.php';

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

// Función para obtener datos POST - compatible con FormData y JSON
function getPostData() {
    // Si hay datos en $_POST, usarlos (FormData)
    if (!empty($_POST)) {
        return $_POST;
    }
    
    // Si no hay datos en $_POST, intentar leer JSON desde php://input
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $jsonData;
        }
    }
    
    // Si no se pudo obtener datos de ninguna forma
    return [];
}

try {
    // Inicializar configuración de usuario ANTES de instanciar otras clases
    require_once __DIR__ . '/../config/usuario_config.php';
    
    // Instanciar base de datos y clase de novedades
    $db = Database::getInstance();
    $novedades = new Novedades();
    
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

        case 'get_empleado_info':
            try {
                $legajo = $_GET['legajo'] ?? null;
                if (!$legajo) {
                    throw new Exception('Legajo es requerido');
                }
                $empleadoInfo = $novedades->getEmpleadoInfo($legajo);
                sendResponse(true, $empleadoInfo);
            } catch (Exception $e) {
                handleError('Error obteniendo información del empleado', $e);
            }
            break;

        case 'get_sucursal_por_centro_costos':
            try {
                $codigoCentroCostos = $_GET['codigo'] ?? null;
                if (!$codigoCentroCostos) {
                    throw new Exception('Código de centro de costos es requerido');
                }
                $sucursal = $novedades->getSucursalPorCentroCostos($codigoCentroCostos);
                sendResponse(true, $sucursal);
            } catch (Exception $e) {
                handleError('Error obteniendo sucursal por centro de costos', $e);
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

        case 'get_centros_costos':
            try {
                $centrosCostos = $novedades->getCentrosCostos();
                sendResponse(true, $centrosCostos);
            } catch (Exception $e) {
                handleError('Error obteniendo centros de costos', $e);
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

        case 'buscar_puestos_select2':
            try {
                $termino = isset($_GET['q']) ? $_GET['q'] : '';
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
                $puestos = $novedades->buscarPuestosSelect2($termino, $limit);
                sendResponse(true, $puestos);
            } catch (Exception $e) {
                handleError('Error buscando puestos para select2', $e);
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
                error_log("📥 Raw input data: " . $inputData);
                
                $datos = json_decode($inputData, true);
                
                if (!$datos) {
                    error_log("❌ Error: Datos JSON inválidos - raw input: " . $inputData);
                    handleError('Datos JSON inválidos');
                }
                
                // Log para debugging detallado
                error_log("📋 Datos recibidos completos: " . print_r($datos, true));
                
                // Validar datos
                $errores = $novedades->validarDatos($datos);
                if (!empty($errores)) {
                    error_log("❌ Errores de validación: " . implode(', ', $errores));
                    handleError('Errores de validación: ' . implode(', ', $errores));
                }

                error_log("✅ Iniciando creación de novedad...");
                
                // USAR EL MÉTODO ACTUALIZADO
                $resultado = $novedades->crearNovedadActualizada($datos);
                
                if ($resultado['success']) {
                    error_log("✅ Novedad creada exitosamente con ID: " . $resultado['id']);
                    sendResponse(true, ['id' => $resultado['id']], 'Novedad creada exitosamente');
                } else {
                    error_log("❌ Error en resultado: " . ($resultado['error'] ?? 'Error desconocido'));
                    handleError($resultado['error']);
                }
            } catch (Exception $e) {
                error_log("❌ Excepción en crear_novedad: " . $e->getMessage());
                error_log("❌ Stack trace: " . $e->getTraceAsString());
                handleError('Error creando novedad', $e);
            }
            break;

        case 'get_novedades':
            try {
                $filtros = [
                    'legajo' => $_GET['legajo'] ?? '',
                    'centro_costos' => $_GET['centro_costos'] ?? '',
                    'tipo_novedad' => $_GET['tipo_novedad'] ?? ''
                ];
                
                // USAR EL MÉTODO ACTUALIZADO
                $novedadesList = $novedades->getNovedadesPeriodoActualActualizado($filtros);
                sendResponse(true, $novedadesList);
            } catch (Exception $e) {
                handleError('Error obteniendo novedades', $e);
            }
            break;

        case 'get_all_novedades':
            try {
                $filtros = [
                    'legajo' => $_GET['legajo'] ?? '',
                    'centro_costos' => $_GET['centro_costos'] ?? '',
                    'tipo_novedad' => $_GET['tipo_novedad'] ?? ''
                ];
                
                $novedadesList = $novedades->getAllNovedades($filtros);
                sendResponse(true, $novedadesList);
            } catch (Exception $e) {
                handleError('Error obteniendo todas las novedades', $e);
            }
            break;

        case 'get_novedad':
            // Extraer el ID numérico del REQUEST_URI si hay múltiples parámetros id
            $id = null;
            if (isset($_GET['id'])) {
                if (is_numeric($_GET['id'])) {
                    $id = (int)$_GET['id'];
                } else {
                    // Si hay múltiples IDs, buscar el numérico en la URI
                    if (preg_match('/[?&]id=(\d+)/', $_SERVER['REQUEST_URI'], $matches)) {
                        $id = (int)$matches[1];
                    }
                }
            }
            
            if (!$id || !is_numeric($id)) {
                handleError('ID de novedad requerido y debe ser numérico');
            }
            
            try {
                // El $id ya está definido arriba como entero
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

        case 'cambiar_estado_novedad':
            try {
                // Verificar que solo usuarios RRHH puedan cambiar estados
                if (Usuario::getTipoUsuario() !== Usuario::TIPO_RRHH) {
                    handleError('Solo el personal de RRHH puede cambiar el estado de las novedades');
                }
                
                // Obtener datos POST compatibles con FormData y JSON
                $postData = getPostData();
                
                if (empty($postData['novedad_id']) || !is_numeric($postData['novedad_id'])) {
                    handleError('ID de novedad requerido y debe ser numérico');
                }
                
                if (empty($postData['nuevo_estado'])) {
                    handleError('Nuevo estado requerido');
                }
                
                $novedadId = (int)$postData['novedad_id'];
                $nuevoEstado = $postData['nuevo_estado']; // Mantener como string para BD
                
                $resultado = $novedades->cambiarEstadoNovedad($novedadId, $nuevoEstado);
                
                if ($resultado) {
                    sendResponse(true, null, 'Estado cambiado exitosamente');
                } else {
                    handleError('No se pudo cambiar el estado de la novedad');
                }
            } catch (Exception $e) {
                handleError('Error cambiando estado de novedad', $e);
            } catch (Error $e) {
                handleError('Error fatal cambiando estado de novedad', $e);
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
            
        case 'get_tipo_usuario':
            try {
                $tipoUsuario = Usuario::getTipoUsuario();
                $esRRHH = Usuario::esUsuarioRRHH();
                $descripcion = Usuario::getTipoUsuarioDescripcion();
                
                sendResponse(true, [
                    'tipo' => $tipoUsuario,
                    'es_rrhh' => $esRRHH,
                    'descripcion' => $descripcion
                ]);
            } catch (Exception $e) {
                handleError('Error obteniendo tipo de usuario', $e);
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