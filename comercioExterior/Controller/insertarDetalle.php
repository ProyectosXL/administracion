<?php
require_once '../Class/detalle.php';
$cid = new Detalle();
$datosDetalle = $_POST['array'];

$newArray = [];

foreach($datosDetalle as $key => $value){

    $newArray[$key]['gastos']= $value[0];
    $newArray[$key]['importeEnDolares']= $value[1];
    $newArray[$key]['tipoCambio']= $value[2];
    $newArray[$key]['importeEnPesos']= $value[3];
    $newArray[$key]['sobreFob']= $value[4];
    $newArray[$key]['observaciones']= $value[5];
    
}

$result = $cid->insertarDetalle($newArray,$datosDetalle[16]);
echo ($result);


