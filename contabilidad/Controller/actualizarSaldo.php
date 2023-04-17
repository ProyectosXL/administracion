<?php

include '../Class/gastos.php';

$id= $_POST['id'];

$nuevoSaldo= $_POST['nuevoSaldo'];

$gastos = new Gastos();

$gastos->cambiarValorSaldo($id, $nuevoSaldo);


?>