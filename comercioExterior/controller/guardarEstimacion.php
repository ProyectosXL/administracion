<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['id_mg']) || !isset($data['conceptos'])) {
        throw new Exception('Datos incompletos');
    }
    
    $idMg = intval($data['id_mg']);
    $conceptos = $data['conceptos'];
    
    require_once '../class/estimacionCostos.php';
    
    $estimacion = new EstimacionCostos();

    /* UNA ESTIMACIÓN CONFIRMADA SE PUEDE SEGUIR EDITANDO.
       Antes acá había un throw: confirmar congelaba los importes y corregir un
       número equivocado obligaba a borrar el contenedor y rehacerlo entero.
       Los costos de nacionalización se conocen DESPUÉS de confirmar -llega el
       despacho, llega la factura del despachante- así que el estado de "ya no
       se toca" llegaba siempre demasiado temprano.

       Lo que NO cambia: guardar no devuelve el contenedor a borrador -el flag
       CONFIRMADO queda como está- y sigue sin generar filas nuevas, porque el
       guardado va por (ID_MG, ID_CE). Ver EstimacionCostos::actualizarEstimacion. */
    $estabaConfirmada = $estimacion->estaConfirmada($idMg);

    $resultado = $estimacion->guardarEstimacion($idMg, $conceptos);

    if ($resultado) {
        echo json_encode([
            'success'    => true,
            'confirmada' => $estabaConfirmada,
            'message'    => $estabaConfirmada
                ? 'Costos actualizados. El contenedor sigue confirmado.'
                : 'Estimación guardada correctamente'
        ]);
    } else {
        throw new Exception('Error al guardar la estimación');
    }
    
} catch (Exception $e) {
    error_log('Error en guardarEstimacion.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
