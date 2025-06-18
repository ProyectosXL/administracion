
<?php

$datos =  file_get_contents('php://input');
$datos =  json_decode($datos, true);
$paso = $_GET['paso'];
$desde =  $datos['desde'];
$hasta =  $datos['hasta'];
$periodo =  $datos['periodo'];

require_once "../Class/paso.php";
$p = new Paso();

$result = null;



if($paso == 8){

    require_once "../Class/gasto.php";
    $gasto = new Gasto();
  
    $result = $gasto->validarCoeficiente($periodo);

    if(count($result) == 0){

        echo json_encode(false);
        
        die();
    }
}

$ejecutarpaso = 'ejecutarPaso'.$paso;


$result = $p->$ejecutarpaso($desde, $hasta);

echo json_encode($result);

