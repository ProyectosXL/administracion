<?php
/**
 * Vuelve la Fecha Est. Pago de un contenedor al cálculo automático.
 *
 * Apaga FECHA_PAGO_CONF, limpia quién y cuándo la fijó, recalcula la fecha con
 * la regla de +5 días sobre la fecha base de embarque y deja el cambio en
 * RO_T_IMPORTACIONES_FECHAS_HIST con ORIGEN = 'GESTION_DESPACHOS'.
 *
 * DESCARTA UN DATO CARGADO A MANO, así que es POST y la pantalla pide
 * confirmación antes de llamarlo. Un GET lo haría alcanzable desde una
 * precarga del navegador.
 *
 * TODO PARAMETRIZADO. No sigue el armado de SQL por concatenación de
 * actualizarFechaPagoController.php: ese es código existente que esta rama no
 * refactoriza, pero tampoco replica.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido');
    }

    if (!isset($_POST['id']) || $_POST['id'] === '') {
        throw new Exception('Falta el despacho al que corresponde la fecha');
    }

    $id = intval($_POST['id']);

    if ($id <= 0) {
        throw new Exception('ID de despacho inválido');
    }

    require_once '../class/encabezado.php';

    $encabezado = new Encabezado();
    $resultado  = $encabezado->revertirFechaPagoAuto($id);

    /* La fecha vuelve en los dos formatos: 'Y-m-d' es lo que guardó la base y
       'd/m/Y' es lo que el input de la pantalla muestra. Convertirla en el JS
       obligaría a repetir ahí una regla de formato que ya está resuelta acá. */
    $fechaISO      = $resultado['fecha'];
    $fechaPantalla = null;

    if ($fechaISO !== null) {
        $p = explode('-', $fechaISO);
        $fechaPantalla = (count($p) === 3) ? ($p[2] . '/' . $p[1] . '/' . $p[0]) : $fechaISO;
    }

    echo json_encode([
        'success'       => true,
        'fecha'         => $fechaISO,
        'fechaPantalla' => $fechaPantalla,
        'ocs'           => $resultado['ocs'],
        'aviso'         => $resultado['aviso'],
        'message'       => $resultado['aviso'] !== null
            ? $resultado['aviso']
            : 'La fecha estimada de pago volvió al cálculo automático',
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error en revertirFechaPagoAuto.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
