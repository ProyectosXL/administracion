<?php
require_once '../Class/Alquiler.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';

if (!$id || !$tipo) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos', 'debug' => ['id' => $id, 'tipo' => $tipo]]);
    exit;
}

try {
    $alquiler = new Alquiler();
    $resultado = $alquiler->eliminarArchivoContrato($id, $tipo);

    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Archivo eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el archivo']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
