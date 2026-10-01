<?php
/**
 * Recalcula la estimación de PCI de un contenedor.
 *
 * LO LLAMA EL CASHFLOW DE FINANZAS después de mover una fecha de
 * nacionalización desde Crono Nacionalización -Js/Comex-fechas.js-, porque esa
 * escritura no pasa por Comercio Exterior. Los disparadores de esta aplicación
 * llaman directo a EstimacionCostos::recalcularEstimacion(), sin HTTP.
 *
 * EL ENTORNO VIAJA EXPLÍCITO Y NO SE LEE DE LA SESIÓN. Las dos aplicaciones
 * están en el mismo XAMPP y en el mismo origen y comparten la cookie: el
 * $_SESSION['entorno'] de este pedido es el de la última pestaña de Comex que
 * alguien abrió, que no tiene nada que ver con a qué base pertenece el
 * contenedor. Un recálculo contra la base equivocada grabaría la estimación de
 * otro contenedor con el mismo ID.
 *
 * NO HACE NADA MÁS QUE RECALCULAR. Comercio Exterior no tiene autenticación, así
 * que este endpoint valida lo que recibe -ID numérico que exista, entorno
 * 'central' o 'uy'- y no expone ninguna otra operación. Lo peor que puede
 * hacer un pedido armado a mano es dejar la estimación de un contenedor igual
 * a lo que daría abrirla en PCI y guardarla.
 *
 * Recibe JSON {id, entorno} o un POST de formulario con los mismos campos.
 * Devuelve si recalculó, qué cambió, o por qué no lo hizo.
 */

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Método no permitido: el recálculo es POST.');
    }

    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) {
        $datos = $_POST;
    }

    $id = isset($datos['id']) ? trim((string) $datos['id']) : '';
    $entorno = isset($datos['entorno']) ? trim((string) $datos['entorno']) : '';

    if (!in_array($entorno, ['central', 'uy'], true)) {
        http_response_code(400);
        throw new Exception('Falta el entorno, o no es válido: tiene que ser "central" o "uy".');
    }

    if ($id === '' || !ctype_digit($id) || intval($id) <= 0) {
        http_response_code(400);
        throw new Exception('El ID del despacho tiene que ser un número entero positivo.');
    }

    require_once __DIR__ . '/../class/estimacionCostos.php';

    $estimacion = new EstimacionCostos($entorno);

    if (!$estimacion->obtenerDespacho(intval($id))) {
        http_response_code(404);
        throw new Exception('No existe el despacho ' . intval($id) . ' en ' . $entorno . '.');
    }

    $r = $estimacion->recalcularEstimacion(intval($id));

    echo json_encode([
        'success'     => true,
        'entorno'     => $entorno,
        'id'          => intval($id),
        'idPrincipal' => $r['idPrincipal'],
        'recalculo'   => $r['recalculo'],
        'motivo'      => $r['motivo'],
        'cambios'     => $r['cambios'],
        'message'     => $r['mensaje'],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error en recalcularEstimacion.php: ' . $e->getMessage());
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
