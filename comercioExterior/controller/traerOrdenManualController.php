<?php
require_once '../class/encabezado.php';

$encabezado = new Encabezado();


$nOrden =$encabezado->traerOrdenManual();


echo json_encode($nOrden);


