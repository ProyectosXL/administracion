<?php 

require_once "../Class/gasto.php";

$gasto = new Gasto();
$id = $_POST['id'];

$result = $gasto -> eliminarGasto($id);


?>

