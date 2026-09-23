<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../Class/gasto.php';

$filtros = !empty($_POST) ? $_POST : $_GET;
$desde         = isset($filtros['desde'])        ? $filtros['desde']        : '';
$hasta         = isset($filtros['hasta'])        ? $filtros['hasta']        : '';
$estado        = isset($filtros['selectEstado']) ? $filtros['selectEstado'] : '0';
$codRubro      = isset($filtros['codRubro'])     ? $filtros['codRubro']     : '%';
$codCuenta     = isset($filtros['codCuenta'])    ? $filtros['codCuenta']    : '%';
$codAuxiliar   = isset($filtros['codAuxiliar'])  ? $filtros['codAuxiliar']  : '%';
$sector        = isset($filtros['sector'])       ? $filtros['sector']       : '%';
$codProrrateo  = isset($filtros['codProrrateo']) ? $filtros['codProrrateo'] : '%';

header('Content-Type: application/json');

if (!$desde || !$hasta) {
    echo json_encode([]);
    exit;
}

$gastos = new Gasto();
echo $gastos->traerGastos($desde, $hasta, $estado, $codRubro, $codCuenta, $codAuxiliar, $sector, $codProrrateo);
