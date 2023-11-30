<?php 
require_once '../Class/Gasto.php';

$accion = $_GET['accion'];

switch ($accion) {
    case 'validarPendienteDeAsignar':
        validarPendienteDeAsignar();
        break;
    
    case 'validarPendienteControl':
        validarPendienteControl();
        break;
    
    case 'validarPendienteAmortizar':
        validarPendienteAmortizar();
        break;
    
    default:
        # code...
        break;
}


function validarPendienteDeAsignar() {

    $gasto = new Gasto();

    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];

    $result = $gasto->validarPendienteDeAsignar($desde, $hasta); 

    echo $result;

}

function validarPendienteControl() {

    $gasto = new Gasto();

    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];

    $result = $gasto->validarPendienteControl($desde, $hasta); 

    echo $result;

}

function validarPendienteAmortizar() {

    $gasto = new Gasto();

    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];

    $result = $gasto->validarPendienteAmortizar($desde, $hasta); 

    echo $result;

}

 ?>