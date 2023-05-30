<?php 

require_once "../Class/sucursal.php";

$sucursal = new Sucursal();

$data = $_POST['data'];

foreach ($data as $key => $value) {

   $sucursal->actualizarValor($value['id'], $value['importeControl'],$_GET['verificado']);

}

?>

