
<?php
require_once "../Class/gastos.php";
$gastos = new Gastos();

$result = null;

$result = $gastos->traerCodRubro($_GET['codCuenta'],$_GET['sector']);
echo json_encode( $result);

