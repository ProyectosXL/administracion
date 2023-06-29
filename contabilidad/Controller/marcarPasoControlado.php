<?php

include '../Class/paso.php';

$periodo= $_POST['periodo'];
$paso = $_POST['paso'];

$pasos = new Paso();

$result = $pasos->marcarPasoEjecutado($paso, $periodo);

?>