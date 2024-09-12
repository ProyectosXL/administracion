
<?php
require_once '../Class/Alquiler.php';

header('Content-Type: application/json');

$alquiler = new Alquiler();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contratoId = $_POST['contratoId'];
    $tipoArchivo = $_POST['tipoArchivo'];
    
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No se recibió el archivo correctamente']);
        exit;
    }

    $archivo = $_FILES['archivo'];

    try {
        $resultado = $alquiler->subirArchivoContrato($contratoId, $tipoArchivo, $archivo);
        echo json_encode(['success' => true, 'message' => 'Archivo subido exitosamente', 'fileName' => $nombreArchivo]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}