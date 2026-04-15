
<?php
require_once __DIR__ . '/../Class/Alquiler.php';

header('Content-Type: application/json');

try {
    $nroSucursal = $_POST['franquicia'] ?? null;
    $vigDesde    = $_POST['fechaDesde'] ?? null;
    $vigHasta    = $_POST['fechaHasta'] ?? null;

    if (!$nroSucursal || !$vigDesde || !$vigHasta) {
        echo json_encode(["overlap" => false]);
        exit;
    }

    $alquiler = new Alquiler();
    $vigenciaOk = $alquiler->validarVigencia($nroSucursal, $vigDesde, $vigHasta);

    echo json_encode(["overlap" => !$vigenciaOk]);

} catch (Exception $e) {
    // En caso de error, no bloquear al usuario — dejar que el backend lo resuelva
    echo json_encode(["overlap" => false]);
}
