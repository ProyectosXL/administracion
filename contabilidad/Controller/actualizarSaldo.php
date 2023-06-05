<?php

include '../Class/gasto.php';

$id= $_POST['id'];

$nuevoSaldo= $_POST['nuevoSaldo'];

$gastos = new Gasto();

$gastos->cambiarValorSaldo($id, $nuevoSaldo);


?>