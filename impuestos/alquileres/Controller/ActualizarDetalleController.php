<?php
require_once '../Class/Alquiler.php';

$alquiler = new Alquiler();

$periodo = $_POST['periodo'];
$sucursal = $_POST['sucursal'];
$concepto = $_POST['concepto'];
$importe = $_POST['importe'];


$result = $alquiler->actualizarDetalle($periodo, $sucursal, $concepto, $importe);

if($concepto == 8) {
 
    $importe9 = $_POST['importe9'];
    $importe13 = $_POST['importe13'];

    $alquiler->actualizarDetalle($periodo, $sucursal, 9, $importe9);
    $alquiler->actualizarDetalle($periodo, $sucursal, 13, $importe13);

}

return true;



