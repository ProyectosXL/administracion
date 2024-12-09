<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer el contenido JSON de la solicitud
    $inputData = json_decode(file_get_contents('php://input'), true);

    if (isset($inputData['firma'])) {
        // Directorio donde se guardará la firma
        $root = $_SERVER["DOCUMENT_ROOT"];
        $targetDir = '../assets/uploads/';

        // Crear el directorio si no existe
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Obtener el contenido Base64 y decodificarlo
        $base64Data = $inputData['firma'];
        $nroRegistro = $inputData['nro_registro'];
        $sucursal = $inputData['sucursal'];

        $decodedData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Data));

        // Crear un nombre único para el archivo
        $timestamp = strval(round(microtime(true) * 1000));
        $fileName = "firma_".$nroRegistro."_".$sucursal."_". $timestamp .".jpg";
        $targetFile = $targetDir . $fileName;

        // Guardar el archivo en el servidor
        if (file_put_contents($targetFile, $decodedData)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Firma guardada correctamente.',
                'filePath' => $targetFile,
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al guardar la firma.',
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se recibió una firma válida.',
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido.',
    ]);
}
?>
