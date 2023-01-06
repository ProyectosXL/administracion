<?php
require_once '../Class/detalle.php';
$cid = new Detalle();
$datosDetalle = $_POST['array'];

$newArray = [];

foreach($datosDetalle as $key => $value){

    $newArray[$key]['Gastos']= $value[0];
    $newArray[$key]['importeEnDolares']= $value[1];
    $newArray[$key]['tipoCambio']= $value[2];
    $newArray[$key]['importeEnPesos']= $value[3];
    $newArray[$key]['sobreFob']= $value[4];
    $newArray[$key]['observaciones']= $value[5];
    $newArray[$key]['idDetalle']= $value[6];
    $newArray[$key]['idEncabezado'] = $value[7];
}
$result = $cid->editarDetalle($newArray);
echo ($result);


