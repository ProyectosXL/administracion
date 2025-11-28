<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../Class/ResumenServicios.php';

/**
 * Controlador para gestionar resumen de servicios
 * Endpoints:
 * - obtener_resumen: Tabla resumen por motivo y director
 * - obtener_total: Total general de servicios
 */

try {
    // Validar acción
    if (!isset($_GET['action'])) {
        throw new Exception("Acción no especificada");
    }
    
    $action = $_GET['action'];
    $resumenServicios = new ResumenServicios();
    
    switch ($action) {
        case 'obtener_resumen':
            // Validar fechas
            if (empty($_GET['fecha_desde']) || empty($_GET['fecha_hasta'])) {
                throw new Exception("Fechas requeridas");
            }
            
            $fechaDesde = $_GET['fecha_desde'];
            $fechaHasta = $_GET['fecha_hasta'];
            
            // Validar formato de fechas
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || 
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
                throw new Exception("Formato de fecha inválido");
            }
            
            // Obtener resumen
            $resumen = $resumenServicios->obtenerResumenPorMotivo($fechaDesde, $fechaHasta);
            
            echo json_encode([
                'success' => true,
                'data' => $resumen
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'obtener_total':
            // Validar fechas
            if (empty($_GET['fecha_desde']) || empty($_GET['fecha_hasta'])) {
                throw new Exception("Fechas requeridas");
            }
            
            $fechaDesde = $_GET['fecha_desde'];
            $fechaHasta = $_GET['fecha_hasta'];
            
            // Validar formato de fechas
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || 
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
                throw new Exception("Formato de fecha inválido");
            }
            
            // Obtener total
            $total = $resumenServicios->obtenerTotalServicios($fechaDesde, $fechaHasta);
            
            echo json_encode([
                'success' => true,
                'total' => $total
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception("Acción no válida: {$action}");
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en resumen_servicios_controller: " . $e->getMessage());
    
    // Respuesta de error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
