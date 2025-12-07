<?php 

require_once '../class/Orden.php';
$cid = new Orden();
$nroOrden = $_POST['nroOrdenDeCompra'];
$result = $cid->updateCostoNacionalizacion($nroOrden);
echo ($result);




