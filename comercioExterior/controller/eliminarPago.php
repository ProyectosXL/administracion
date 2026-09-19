<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    // Validar que la solicitud sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido');
    }
    
    // Validar que se proporcione el ID del pago
    if (!isset($_POST['id_pago']) || empty($_POST['id_pago'])) {
        throw new Exception('ID de pago requerido');
    }
    
    $idPago = intval($_POST['id_pago']);

    // Incluir clases necesarias
    require_once('../class/Pagos.php');

    $pagosClass = new Pagos();

    /* El contenedor se lee ANTES de borrar: después el pago ya no está y no
       hay de dónde sacar sobre cuál recalcular el saldo. */
    $pago = $pagosClass->obtenerPago($idPago);
    if (!$pago) {
        throw new Exception('El pago no existe');
    }

    // Eliminar el pago
    $resultado = $pagosClass->eliminarPago($idPago);

    if ($resultado === true) {
        // Igual que al insertar y al editar: el saldo lo devuelve el servidor.
        $resumen = $pagosClass->obtenerResumen($pago['ID_ENCABEZADO']);

        echo json_encode([
            'success'        => true,
            'message'        => 'Pago eliminado correctamente',
            'fobUsd'         => $resumen['fobUsd'],
            'totalPagado'    => $resumen['totalPagado'],
            'saldoPendiente' => $resumen['saldoPendiente'],
            'estado'         => $resumen['estado']
        ]);
    } else {
        throw new Exception(is_string($resultado) ? $resultado : 'No se pudo eliminar el pago');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
