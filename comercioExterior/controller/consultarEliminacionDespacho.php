<?php
/**
 * Qué se borraría junto con un despacho. SÓLO LECTURA.
 *
 * Es lo que muestra el aviso previo de Gestión de Despachos: si tiene costos
 * de nacionalización cargados, si tiene estimación, cuántos pagos y por cuánto,
 * si tiene historial de fechas, y si el borrado se rechaza por ser una
 * principal con hijas. Lo calcula el servidor porque la pantalla no tiene
 * esos datos. Ver Orden::infoEliminacion().
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../class/Orden.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

try {
    $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';

    if ($id === '' || !ctype_digit($id) || intval($id) <= 0) {
        throw new Exception('ID del despacho no proporcionado o inválido');
    }

    $orden = new Orden();
    $info = $orden->infoEliminacion(intval($id));

    if (!$info['existe']) {
        throw new Exception('El despacho #' . intval($id) . ' ya no existe: puede que lo haya borrado otra persona.');
    }

    echo json_encode(['success' => true, 'data' => $info], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error en consultarEliminacionDespacho.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
