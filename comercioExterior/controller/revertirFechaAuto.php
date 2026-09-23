<?php
/**
 * Vuelve al cálculo automático una de las tres fechas que el sistema calcula:
 * la estimada de pago, el arribo o la nacionalización.
 *
 * GENERALIZA A controller/revertirFechaPagoAuto.php, que hacía esto mismo solo
 * para el pago. Las tres fechas tienen ahora una marca de "fijada a mano" que
 * sobrevive al cierre de la pantalla -scripts 10 y 11-, así que las tres
 * necesitan la puerta de salida: sin ella, una fecha marcada por error queda
 * congelada para siempre.
 *
 * Y HAY FECHAS MARCADAS POR ERROR A PROPÓSITO: el backfill del script 11 marca
 * como manual toda FECHA_DESP_ADU que no se explique con la regla vieja,
 * porque equivocarse para ese lado se arregla con un clic y equivocarse para
 * el otro deja que el recálculo pise una corrección hecha a mano. Este
 * endpoint es ese clic.
 *
 * DESCARTA UN DATO CARGADO A MANO, así que es POST y la pantalla pide
 * confirmación antes de llamarlo. Un GET lo haría alcanzable desde una
 * precarga del navegador.
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

    /* El tipo se valida contra FECHAS_FIJABLES y no se pasa crudo: es lo que
       elige qué columnas toca el UPDATE de revertirFechaAuto(). */
    $tipo = isset($_POST['tipo']) ? strtoupper(trim($_POST['tipo'])) : 'PAGO';

    if (!isset(Encabezado::FECHAS_FIJABLES[$tipo])) {
        throw new Exception('Tipo de fecha no reconocido: ' . $tipo);
    }

    $encabezado = new Encabezado();
    $resultado  = $encabezado->revertirFechaAuto($id, $tipo);

    /* La fecha vuelve en los dos formatos: 'Y-m-d' es lo que guardó la base y
       'd/m/Y' es lo que el input de la pantalla muestra. Convertirla en el JS
       obligaría a repetir ahí una regla de formato que ya está resuelta acá. */
    $fechaISO      = $resultado['fecha'];
    $fechaPantalla = null;

    if ($fechaISO !== null) {
        $p = explode('-', $fechaISO);
        $fechaPantalla = (count($p) === 3) ? ($p[2] . '/' . $p[1] . '/' . $p[0]) : $fechaISO;
    }

    $etiqueta = Encabezado::FECHAS_FIJABLES[$tipo]['etiqueta'];

    echo json_encode([
        'success'       => true,
        'tipo'          => $tipo,
        'fecha'         => $fechaISO,
        'fechaPantalla' => $fechaPantalla,
        'ocs'           => $resultado['ocs'],
        'aviso'         => $resultado['aviso'],
        'message'       => $resultado['aviso'] !== null
            ? $resultado['aviso']
            : 'La ' . $etiqueta . ' volvió al cálculo automático',
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error en revertirFechaAuto.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
