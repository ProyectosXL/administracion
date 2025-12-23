<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../class/Orden.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID del despacho no proporcionado'
        ]);
        exit;
    }
    
    $id = $_POST['id'];
    $orden = new Orden();
    
    $resultado = $orden->eliminarDespacho($id);
    
    if ($resultado === true) {
        echo json_encode([
            'success' => true,
            'message' => 'Despacho eliminado exitosamente'
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
