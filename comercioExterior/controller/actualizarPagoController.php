<?php
/**
 * Edita un pago ya cargado: fecha, forma, medio e IMPORTE EN U$S.
 *
 * Reemplaza a actualizarFechaPagoController.php, que sólo movía la fecha. Con
 * el saldo calculado en dólares el importe pasó a ser lo que más se corrige
 * -un pago cargado de más o de menos deja el saldo mintiendo- y la única
 * forma de arreglarlo era borrar el pago y volver a cargarlo entero.
 *
 * Los campos que no se mandan quedan como están: se leen del pago y se
 * reescriben. Así el mismo endpoint sirve para mover sólo la fecha sin que el
 * cliente tenga que mandar el resto.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../class/Pagos.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido');
    }

    if (!isset($_POST['id_pago']) || $_POST['id_pago'] === '') {
        throw new Exception('ID de pago requerido');
    }

    $idPago = intval($_POST['id_pago']);
    if ($idPago <= 0) {
        throw new Exception('ID de pago inválido');
    }

    $pagosClass = new Pagos();

    $pago = $pagosClass->obtenerPago($idPago);
    if (!$pago) {
        throw new Exception('El pago no existe');
    }

    // --- Fecha ---
    $fechaSQL = $pago['FECHA_PAGO'];
    if (isset($_POST['fecha_pago']) && trim($_POST['fecha_pago']) !== '') {
        $partes = explode('/', trim($_POST['fecha_pago']));
        if (count($partes) !== 3 || !checkdate(intval($partes[1]), intval($partes[0]), intval($partes[2]))) {
            throw new Exception('Formato de fecha inválido');
        }
        $fechaSQL = $partes[2] . '-' . $partes[1] . '-' . $partes[0] . ' 00:00:00';
    }

    // --- Forma y medio ---
    $formaPago = (isset($_POST['forma_pago']) && trim($_POST['forma_pago']) !== '')
        ? trim($_POST['forma_pago']) : $pago['FORMA_PAGO'];
    $medioPago = (isset($_POST['medio_pago']) && trim($_POST['medio_pago']) !== '')
        ? trim($_POST['medio_pago']) : $pago['MEDIO_PAGO'];

    // --- Importe en U$S ---
    $monto = floatval($pago['MONTO']);
    if (isset($_POST['monto']) && trim((string) $_POST['monto']) !== '') {
        $montoCrudo = str_replace(',', '.', trim((string) $_POST['monto']));

        if (!is_numeric($montoCrudo)) {
            throw new Exception('El importe en U$S no es un número válido');
        }

        $monto = round(floatval($montoCrudo), 2);

        if ($monto <= 0) {
            throw new Exception('El importe en U$S tiene que ser mayor a cero');
        }
    }

    $resultado = $pagosClass->actualizarPago($idPago, $fechaSQL, $formaPago, $medioPago, $monto);

    if ($resultado !== true) {
        throw new Exception(is_string($resultado) ? $resultado : 'No se pudo actualizar el pago');
    }

    // El saldo lo recalcula el servidor, no la pantalla. Ver insertarPago.php.
    $resumen = $pagosClass->obtenerResumen($pago['ID_ENCABEZADO']);

    echo json_encode([
        'success'        => true,
        'message'        => 'Pago actualizado correctamente',
        'fobUsd'         => $resumen['fobUsd'],
        'totalPagado'    => $resumen['totalPagado'],
        'saldoPendiente' => $resumen['saldoPendiente'],
        'estado'         => $resumen['estado']
    ]);

} catch (Exception $e) {
    error_log('Error en actualizarPagoController: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'message' => $e->getMessage()
    ]);
}
