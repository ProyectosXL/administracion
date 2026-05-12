<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    require_once '../class/estimacionCostos.php';

    $estimacion = new EstimacionCostos();

    // tipo=pci  → solo OCs principales, con ESTADO para PCI (default)
    // tipo=gestion → todas las OCs, con ID_PADRE / OCS_VINCULADAS / ORDEN_COMPRA_PADRE
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'pci';

    if ($tipo === 'gestion') {
        $despachos = $estimacion->listarDespachosTodosConPadre();
    } else {
        $despachos = $estimacion->listarDespachosConEstado();
    }

    echo json_encode([
        'success' => true,
        'data'    => $despachos
    ]);

} catch (Exception $e) {
    error_log('Error en listarDespachos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
