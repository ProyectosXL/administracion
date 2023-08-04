<?php
$accion = $_GET['accion'];


switch ($accion) {

    case 'confirmarVentaVsCobranza':

        confirmarVentaVsCobranza();

        break;
    
    default:
        # code...
        break;
}



function confirmarVentaVsCobranza () {

    require_once '../Class/ventas.php';

    $ventas = new Ventas();
    $nroSucursal = $_POST['nroSucursal'];
    $nroComprobante = $_POST['nroComprobante'];
    $result = $ventas->confirmarVentaVsCobranza($nroSucursal, $nroComprobante);

    return $result;
}

?>