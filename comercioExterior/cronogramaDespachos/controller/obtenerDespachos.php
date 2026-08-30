<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../class/CronogramaDespachos.php';
require_once __DIR__ . '/../class/MotivosFecha.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$cronograma = new CronogramaDespachos();
$resultado  = $cronograma->obtenerDespachos();

if (!is_array($resultado)) {
    echo json_encode([
        'success' => false,
        'message' => $resultado
    ]);
    exit;
}

// Agregar estado a cada despacho
foreach ($resultado as &$despacho) {
    $despacho['ESTADO'] = CronogramaDespachos::determinarEstado($despacho);
}
unset($despacho);

// Todo lo que el front necesita para pintar el calendario viaja en esta
// misma respuesta: sin round-trips adicionales para alias, iconos ni
// parametros, que si no habria que resolver antes de cada render.
$rubros = $cronograma->obtenerRubrosPorOC(array_column($resultado, 'ORDEN_COMPRA'));

echo json_encode([
    'success'     => true,
    'data'        => $resultado,
    'rubros'      => $rubros,
    'alias'       => $cronograma->obtenerAliasProveedores(),
    'iconosRubro' => $cronograma->obtenerIconosRubro(),
    'parametros'  => $cronograma->obtenerParametros(),
    'motivos'     => MotivosFecha::listar(),
], JSON_UNESCAPED_UNICODE);
