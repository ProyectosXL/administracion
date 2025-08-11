<?php
/**
 * Controlador para manejo de Novedades
 * /novedades/controller/novedades_controller.php
 */

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
    exit(0);
}

// Incluir clases necesarias
require_once '../class/Novedades.php';

// Función para enviar respuesta JSON
function sendResponse($success, $data = null, $message = '') {
    // Limpiar cualquier output previo que pueda interferir con JSON
    if (ob_get_level()) {
        ob_clean();
    }
    
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para manejar errores
function handleError($message, $error = null) {
    if ($error) {
        error_log("Error en controlador: $message - " . print_r($error, true));
    }
    sendResponse(false, null, $message);
}

try {
    // Instanciar clase de novedades
    $novedades = new Novedades();
    
    // Obtener acción
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Log para debugging
    error_log("Acción solicitada: $action");
    
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

        case 'test':
            // Endpoint de prueba
            sendResponse(true, ['message' => 'Controlador funcionando correctamente', 'timestamp' => date('Y-m-d H:i:s')]);
            break;

        default:
            handleError("Acción no válida: '$action'");
    }
    
} catch (Exception $e) {
    handleError('Error general del sistema', $e);
}
?>