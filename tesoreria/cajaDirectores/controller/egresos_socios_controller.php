<?php
// Aumentar tiempo de ejecución para consultas pesadas
set_time_limit(180); // 3 minutos
ini_set('max_execution_time', '180');

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../Class/EgresoSocios.php';

/**
 * Controlador para gestionar egresos de socios
 * Endpoints:
 * - obtener_resumen: Tabla resumen por director (efectivo + transferencia + total)
 * - obtener_detalle: Tabla detalle completa combinando ambas fuentes
 */

try {
    // Validar acción
    if (!isset($_GET['action'])) {
        throw new Exception("Acción no especificada");
    }
    
    $action = $_GET['action'];
    $egresoSocios = new EgresoSocios();
    
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
            $resumen = $egresoSocios->obtenerResumenPorDirector($fechaDesde, $fechaHasta);
            
            echo json_encode([
                'success' => true,
                'data' => $resumen
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'obtener_detalle':
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
            
            // Obtener detalle
            $detalle = $egresoSocios->obtenerDetalleCompleto($fechaDesde, $fechaHasta);
            
            echo json_encode([
                'success' => true,
                'data' => $detalle,
                'total_registros' => count($detalle)
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
            $total = $egresoSocios->obtenerTotalEgresos($fechaDesde, $fechaHasta);
            
            echo json_encode([
                'success' => true,
                'total' => $total
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'obtener_detalle_egreso':
            // Validar parámetros
            if (empty($_GET['origen']) || empty($_GET['codigo'])) {
                throw new Exception("Origen y código requeridos");
            }
            
            $origen = $_GET['origen'];
            $codigo = $_GET['codigo'];
            
            // Obtener detalle según origen
            $detalle = $egresoSocios->obtenerDetalleEgresoPorCodigo($origen, $codigo);
            
            if (!$detalle) {
                throw new Exception("No se encontró el egreso");
            }
            
            echo json_encode([
                'success' => true,
                'data' => $detalle
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception("Acción no válida: {$action}");
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en egresos_socios_controller: " . $e->getMessage());
    
    // Respuesta de error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
