<?php

include '../Class/paso.php';

$periodo= $_POST['periodo'];
$paso = $_POST['paso'];
$desde = $_POST['desde'];
$hasta = $_POST['hasta'];

$pasos = new Paso();

$result = $pasos->aceptarConDiferencias($paso, $periodo, $desde, $hasta);

?>