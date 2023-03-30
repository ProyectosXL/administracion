
<?php

$datos =  file_get_contents('php://input');
$datos =  json_decode($datos, true);
$paso = $_GET['paso'];
$desde =  $datos['desde'];
$hasta =  $datos['hasta'];

require_once "../Class/paso.php";
$p = new Paso();

$result = null;

switch ($paso) {
    case "1":
        $result = $p->ejecutarPaso1($desde, $hasta);
        break;
    case "2":
        $result = $p->ejecutarPaso2($desde, $hasta);
        break;
    case "3":
        $result = $p->ejecutarPaso3($desde, $hasta);
        break;
    case "4":
        $result = $p->ejecutarPaso4($desde, $hasta);
        break;
    case "5":
        $result = $p->ejecutarPaso5($desde, $hasta);
        break;
    case "6":
        $result = $p->ejecutarPaso6($desde, $hasta);
        break;
    case "7":
        $result = $p->ejecutarPaso7($desde, $hasta);
        break;
    default:
        break;
}

return $result;

