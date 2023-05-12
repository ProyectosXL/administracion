<?php 

require_once '../Class/Orden.php';
$cid = new Orden();
$nroOrden = $_POST['nroOrdenDeCompra'];
$result = $cid->updateCostoNacionalizacion($nroOrden);
echo ($result);




