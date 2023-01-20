<?php
require_once '../Class/OrdenDeCompra.php';

$ordenDeCompra = new OrdenDeCompra();
$datosDetalle = $_POST['array'];

$nOrden = isset($_POST['idEncabezado']) ? $_POST['idEncabezado'] : null;

$newArray = [];

foreach($datosDetalle as $key => $value){

    $newArray[$key]['gastos'] = ($value[0] == 'NaN' || $value[0] == '') ? '-- SIN DATOS --' : $value[0] ;
    $newArray[$key]['importeEnDolares'] = ($value[1] == 'NaN' || $value[1] == '') ? 0 : $value[1] ;
    $newArray[$key]['tipoCambio'] = ($value[2] == 'NaN' || $value[2] == '') ? 0 : $value[2] ;
    $newArray[$key]['importeEnPesos'] = ($value[3] == 'NaN' || $value[3] == '') ? 0 : $value[3] ;
    $newArray[$key]['sobreFob'] = ($value[4] == 'NaN' || $value[4] == '') ? 0 : $value[4] ;
    $newArray[$key]['observaciones'] = ($value[5] == 'NaN' || $value[5] == '') ? '' : $value[5] ;
    
}

$ordenDeCompra->deleteDetalle($nOrden);

$ordenDeCompra->insertDetalle($newArray, $nOrden);

echo true;


