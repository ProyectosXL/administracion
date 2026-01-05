<?php
$accion = $_GET['accion'] ?? '';

switch ($accion) {

    case 'confirmarVentaVsCobranza':
        confirmarVentaVsCobranza();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function confirmarVentaVsCobranza() {
    // Ajustar la ruta para apuntar a la clase Ventas desde integridadVentas/Controller/
    require_once '../Class/ventas.php';

    $ventas = new Ventas();
    $nroSucursal = $_POST['nroSucursal'] ?? '';
    $nroComprobante = $_POST['nroComprobante'] ?? '';
    
    if (empty($nroSucursal) || empty($nroComprobante)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
        return;
    }
    
    $result = $ventas->confirmarVentaVsCobranza($nroSucursal, $nroComprobante);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Confirmado exitosamente']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al confirmar']);
    }
}
?>
