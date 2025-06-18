<?php

include '../Class/gasto.php';

$periodo= $_POST['periodo'];
$coeficiente= $_POST['coeficiente'];

$gastos = new Gasto();

$result = $gastos->insertarCoeficiente($periodo, $coeficiente);

return $result;

?>