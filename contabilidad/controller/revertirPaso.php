<?php

$datos = file_get_contents('php://input');
$datos = json_decode($datos, true);
$paso  = (int) $_GET['paso'];
$desde = $datos['desde'];
$hasta = $datos['hasta'];

require_once "../Class/paso.php";
$p = new Paso();

$ok = $p->revertirPaso($paso, $desde, $hasta);

echo json_encode(['success' => $ok]);
