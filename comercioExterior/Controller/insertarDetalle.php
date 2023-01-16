<?php

require_once '../Class/detalle.php';
$cid = new Detalle();
$datosDetalle = [];

$datosDetalle['importeEnDolares'] = $_POST['importeEnDolares'];
$datosDetalle['tipoCambio'] = $_POST['tipoCambio'];
$datosDetalle['importeEnPesos'] = $_POST['importeEnPesos'];
$datosDetalle['porcentaje'] = $_POST['porcentaje'];
$datosDetalle['idEncabezado'] = $_POST['idEncabezado'];

$result = $cid->insertarDetalle($datosDetalle);
echo ($result);


