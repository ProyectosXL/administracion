<?php
require_once '../Class/Alquiler.php';

$alquiler = new Alquiler();

$data = $_POST['values'];

$result = $alquiler->insertarDetalle($data);
return true;



