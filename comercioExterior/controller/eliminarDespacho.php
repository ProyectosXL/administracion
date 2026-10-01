<?php
/**
 * Borra un despacho y todo lo que cuelga de él, en una transacción.
 *
 * Antes de llamar a esto la pantalla pide consultarEliminacionDespacho.php y,
 * si hay algo más que el despacho -costos, estimación, pagos o historial-, lo
 * muestra y pide confirmación. La regla y el orden del borrado están en
 * Orden::eliminarDespacho().
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../class/Orden.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$id = isset($_POST['id']) ? trim((string) $_POST['id']) : '';

if ($id === '' || !ctype_digit($id) || intval($id) <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID del despacho no proporcionado o inválido'
    ]);
    exit;
}

$orden = new Orden();
$resultado = $orden->eliminarDespacho(intval($id));

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
