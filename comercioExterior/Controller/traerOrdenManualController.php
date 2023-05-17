<?php
require_once '../Class/encabezado.php';

$encabezado = new Encabezado();
// $datosDetalle = $_POST['array'];

$nOrden =$encabezado->traerOrdenManual();
// return ($nOrden);

echo json_encode($nOrden);


