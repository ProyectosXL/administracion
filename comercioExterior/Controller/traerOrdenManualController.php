<?php
require_once '../Class/encabezado.php';

$encabezado = new Encabezado();


$nOrden =$encabezado->traerOrdenManual();


echo json_encode($nOrden);


