<?php
require_once '../Class/Alquiler.php';

$alquiler = new Alquiler();

$idConcepto = $_POST['idConcepto'];
$idLocal = $_POST['idLocal'];
$descLocal = $_POST['descLocal'];


$result = $alquiler->insertarPorcentaje($idConcepto, $idLocal, $descLocal);
echo ($result);


