<?php
require_once "../Class/sucursal.php";
$accion = $_GET['accion'];

switch ($accion) {
    case 'checkFactura':
        marcarFacturado();
        break;
    
    case 'checkControl':
        marcarControlado();
        break;

    case 'marcarRecibido':
        marcarRecibido();
        break;
    
    default:
        # code...
        break;
}


function marcarFacturado (){

    $fecha = $_POST['fecha'];
    $nroSucursal = $_POST['nro_sucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $descripcionCuenta = $_POST['descripcionCuenta'];
    $monto = $_POST['monto'];
    $leyenda = $_POST['leyenda'];
    $factura = $_POST['factura'];
    $control = $_POST['control'];    

    $sucursal = new Sucursal();

    $sucursal->marcarFacturado($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control);


}
function marcarControlado (){

    $fecha = $_POST['fecha'];
    $nroSucursal = $_POST['nro_sucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $descripcionCuenta = $_POST['descripcionCuenta'];
    $monto = $_POST['monto'];
    $leyenda = $_POST['leyenda'];
    $factura = $_POST['factura'];
    $control = $_POST['control'];    

    $sucursal = new Sucursal();

    $sucursal->marcarControlado($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control);


}

function marcarRecibido (){

    $fecha = $_POST['fecha'];
    $nroSucursal = $_POST['nroSucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $descripcionCuenta = $_POST['descripcionCuenta'];
    $monto = $_POST['monto'];

    $sucursal = new Sucursal();

    $result = $sucursal->marcarRecibido($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto);
    
    echo $result;

}
?>