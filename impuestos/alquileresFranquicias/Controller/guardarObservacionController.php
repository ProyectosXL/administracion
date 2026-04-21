<?php
require_once '../Class/Alquiler.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
    exit;
}

$id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$observacion = isset($_POST['observacion']) ? trim($_POST['observacion']) : '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de contrato inválido.']);
    exit;
}

try {
    $alquiler = new Alquiler();
    $alquiler->actualizarObservacion($id, $observacion);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
