<?php
require_once '../Class/Alquiler.php';

$alquiler = new Alquiler();

$id = $_POST['id'];
$porcentaje = $_POST['porcentaje'];

$result = $alquiler->actualizarPorcentaje($id, $porcentaje);
echo ($result);


