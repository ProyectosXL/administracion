<?php
require_once __DIR__ . '/../class/Pagos.php';

header('Content-Type: application/json');

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
