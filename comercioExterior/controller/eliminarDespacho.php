<?php
require_once __DIR__ . '/../class/Orden.php';

header('Content-Type: application/json');

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
