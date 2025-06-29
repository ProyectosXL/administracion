

<?php
// Configurar manejo de errores para evitar HTML en respuesta JSON
error_reporting(0); // Deshabilitar mostrar errores en producción
ini_set('display_errors', 0);

// Headers obligatorios al inicio
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Función para respuesta de error consistente
function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'data' => null
    ]);
    exit;
}

// Función para respuesta exitosa
function sendSuccessResponse($data) {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'error' => null
    ]);
    exit;
}

try {
    // Verificar que el archivo de controlador existe
    $controllerPath = __DIR__ . '/../class/dashboardController.php';
    if (!file_exists($controllerPath)) {
        sendErrorResponse('Controller file not found: ' . $controllerPath);
    }

    require_once $controllerPath;
    
    // Verificar que la clase existe
    if (!class_exists('DashboardController')) {
        sendErrorResponse('DashboardController class not found');
    }

    $dashboard = new DashboardController();
    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        sendErrorResponse('Action parameter is required');
    }

    $data = null;
    
    switch ($action) {
        case 'evolucion_mensual':
            // Este método NO usa filtros (siempre últimos 3 años calendario)
            $data = $dashboard->getEvolucionMensualCostos();
            break;
            
        case 'promedio_barras_proveedor':
            $fechaDesde = $_GET['fecha_desde'] ?? null;
            $fechaHasta = $_GET['fecha_hasta'] ?? null;
            $proveedor = $_GET['proveedor'] ?? null;
            $data = $dashboard->getPromedioBarrasPorProveedor($fechaDesde, $fechaHasta, $proveedor);
            break;
            
        case 'kpis_generales':
            $fechaDesde = $_GET['fecha_desde'] ?? null;
            $fechaHasta = $_GET['fecha_hasta'] ?? null;
            $data = $dashboard->getKPIsGenerales($fechaDesde, $fechaHasta);
            break;
            
        case 'top_proveedores':
            $fechaDesde = $_GET['fecha_desde'] ?? null;
            $fechaHasta = $_GET['fecha_hasta'] ?? null;
            $data = $dashboard->getTopProveedoresPorCosto($fechaDesde, $fechaHasta);
            break;
            
        case 'lista_proveedores':
            $data = $dashboard->getProveedores();
            break;
            
        case 'distribucion_costos':
            $fechaDesde = $_GET['fecha_desde'] ?? null;
            $fechaHasta = $_GET['fecha_hasta'] ?? null;
            $data = $dashboard->getDistribucionCostosPorRangos($fechaDesde, $fechaHasta);
            break;
            
        default:
            sendErrorResponse('Invalid action: ' . $action);
    }
    
    // Verificar si hay error en los datos
    if (is_array($data) && isset($data['error'])) {
        sendErrorResponse($data['error']);
    }
    
    // Respuesta exitosa
    sendSuccessResponse($data);
    
} catch (Exception $e) {
    // Log del error para debugging (opcional)
    error_log('Dashboard API Error: ' . $e->getMessage());
    
    sendErrorResponse('Internal server error: ' . $e->getMessage(), 500);
} catch (Error $e) {
    // Para errores fatales de PHP
    error_log('Dashboard API Fatal Error: ' . $e->getMessage());
    
    sendErrorResponse('Fatal error occurred', 500);
}
?>