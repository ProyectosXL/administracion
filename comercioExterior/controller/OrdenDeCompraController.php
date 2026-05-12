<?php
header('Content-Type: application/json');

require_once '../class/OrdenDeCompra.php';
require_once '../class/Orden.php';
require_once '../class/encabezado.php';

$ordenDeCompra   = new OrdenDeCompra();
$orden           = new Orden();
$encabezadoClass = new Encabezado();

$datosDetalle   = $_POST['array'];
$nOrden         = isset($_POST['idEncabezado']) ? $_POST['idEncabezado'] : null;
$nroOrdenCompra = isset($_POST['nroOrdenDeCompra']) ? trim($_POST['nroOrdenDeCompra']) : '';
$costoNac       = isset($_POST['costoNac']) ? floatval($_POST['costoNac']) : 0;

$cadena        = trim($nOrden, "( )");
$idEncabezados = explode(", ", $cadena);
$idEncabezado  = intval($idEncabezados[0]);

// Guardia: si el ID recibido es de una OC hija, rechazar con redirect al principal
$idPrincipal = $encabezadoClass->resolverIdPrincipal($idEncabezado);
if ($idPrincipal !== $idEncabezado) {
    echo json_encode([
        'success'    => false,
        'message'    => 'Esta OC está vinculada como hija de otra. Los costos deben cargarse desde la OC principal (ID ' . $idPrincipal . ').',
        'redirectTo' => $idPrincipal
    ]);
    exit;
}

$newArray = [];
foreach ($datosDetalle as $key => $value) {
    $newArray[$key]['gastos']           = ($value[0] == 'NaN' || $value[0] == '') ? '-- SIN DATOS --' : $value[0];
    $newArray[$key]['importeEnDolares'] = ($value[1] == 'NaN' || $value[1] == '') ? 0 : $value[1];
    $newArray[$key]['tipoCambio']       = ($value[2] == 'NaN' || $value[2] == '') ? 0 : $value[2];
    $newArray[$key]['importeEnPesos']   = ($value[3] == 'NaN' || $value[3] == '') ? 0 : $value[3];
    $newArray[$key]['sobreFob']         = ($value[4] == 'NaN' || $value[4] == '') ? 0 : $value[4];
    $newArray[$key]['observaciones']    = ($value[5] == 'NaN' || $value[5] == '') ? '' : $value[5];
}

// Obtener TODOS los IDs del grupo (principal + hijas)
$idsGrupo = $encabezadoClass->obtenerIdsDelGrupo($idEncabezado);

// 1. Borrar detalle previo en TODAS las OCs del grupo
$ordenDeCompra->deleteDetalleGrupo($idsGrupo);

// 2. Insertar el mismo detalle en TODAS las OCs del grupo
$ordenDeCompra->insertDetalleReplicado($newArray, $idsGrupo);

// 3. Insertar costo de nacionalización para TODAS las OCs del grupo
$resultNac = $orden->insertarCostoNacionalizacion($nroOrdenCompra, $idEncabezado, $costoNac);

echo json_encode($resultNac);
