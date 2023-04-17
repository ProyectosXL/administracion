
<?php

$datos =  file_get_contents('php://input');
$datos =  json_decode($datos, true);
$paso = $_GET['paso'];
$desde =  $datos['desde'];
$hasta =  $datos['hasta'];

require_once "../Class/paso.php";
$p = new Paso();

$result = null;

$ejecutarpaso = 'ejecutarPaso'.$paso;

$result = $p->$ejecutarpaso($desde, $hasta);

echo json_encode($result);

