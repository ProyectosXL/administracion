<?php
header('Content-Type: application/json');

require_once '../class/Orden.php';
$cid = new Orden();

$nroOrden    = $_POST['nroOrdenDeCompra'] ?? '';
$idEncabezado = intval($_POST['idEncabezado'] ?? 0);
$costoNac    = isset($_POST['costoNac']) ? floatval($_POST['costoNac']) : null;

if (empty(trim($nroOrden))) {
    echo json_encode(['success' => false, 'message' => 'Número de orden vacío']);
    exit;
}

if ($costoNac === null || $idEncabezado === 0) {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros: costoNac o idEncabezado']);
    exit;
}

$result = $cid->insertarCostoNacionalizacion($nroOrden, $idEncabezado, $costoNac);
echo json_encode($result);




