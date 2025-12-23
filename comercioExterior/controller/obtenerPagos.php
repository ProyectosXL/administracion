<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../class/Pagos.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (!isset($_GET['idEncabezado']) || empty($_GET['idEncabezado'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID del encabezado no proporcionado'
        ]);
        exit;
    }
    
    $idEncabezado = $_GET['idEncabezado'];
    $pagos = new Pagos();
    
    $resultado = $pagos->obtenerPagosPorEncabezado($idEncabezado);
    
    if (is_array($resultado)) {
        echo json_encode([
            'success' => true,
            'data' => $resultado
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultado
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>
