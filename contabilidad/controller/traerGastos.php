<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../Class/gasto.php';

$desde     = isset($_GET['desde'])        ? $_GET['desde']        : '';
$hasta     = isset($_GET['hasta'])        ? $_GET['hasta']        : '';
$estado    = isset($_GET['selectEstado']) ? $_GET['selectEstado'] : '0';
$codRubro  = isset($_GET['codRubro'])     ? $_GET['codRubro']     : '%';
$codCuenta = isset($_GET['codCuenta'])    ? $_GET['codCuenta']    : '%';

header('Content-Type: application/json');

if (!$desde || !$hasta) {
    echo json_encode([]);
    exit;
}

$gastos = new Gasto();
echo $gastos->traerGastos($desde, $hasta, $estado, $codRubro, $codCuenta);
