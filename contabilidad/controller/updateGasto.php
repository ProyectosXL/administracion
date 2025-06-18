<?php
include '../Class/gasto.php';

$id= $_POST['id'];
$numSucursal= $_POST['numSucursal'];
$codAuxiliar= $_POST['codAuxiliar'];
$descAuxiliar= $_POST['descAuxiliar'];
$sector= $_POST['sector'];

$gastos = new Gasto();

$gastos->updateGasto($id, $numSucursal, $codAuxiliar, $descAuxiliar, $sector);


?>