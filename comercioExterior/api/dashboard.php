

<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../class/dashboardController.php';

try {
    $dashboard = new DashboardController();
    $action = $_GET['action'] ?? '';
    
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
            throw new Exception('Acción no válida');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>