<?php
require_once "../Class/sucursal.php";
$accion = $_GET['accion'];

switch ($accion) {
    
    case 'checkControl':
        marcarControlado();
        break;
    
    default:

        break;
}

function marcarControlado (){

    $nroSucursal = $_POST['nroSucursal'];
    $periodo = $_POST['periodo'];


    $sucursal = new Sucursal();

    $sucursal->controlarGastosTesoreria($periodo, $nroSucursal);


}
?>