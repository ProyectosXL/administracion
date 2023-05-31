
<?php
require_once "../Class/gasto.php";
$gastos = new Gasto();

$result = null;

$result = $gastos->traerCodRubro($_GET['codCuenta'],$_GET['sector']);
echo json_encode( $result);

