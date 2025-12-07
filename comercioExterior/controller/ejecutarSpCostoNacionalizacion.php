<?php 

require_once '../class/Orden.php';
$cid = new Orden();
$nroOrden = json_decode($_POST['nroOrdenDeCompra']);

foreach ($nroOrden as  $value) {

    $result = $cid->EjecutarSp($value);
    
}

echo ($result);




