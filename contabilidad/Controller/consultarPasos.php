
<?php

$periodo = $_GET['periodo'];

require_once "../Class/paso.php";
$p = new Paso();

$result = null;

$result = $p->consultarPasos($periodo);;
echo json_encode( $result);

