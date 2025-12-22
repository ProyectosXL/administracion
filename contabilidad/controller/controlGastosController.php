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
    
    case 'existeResumen':
        existeResumen();
        break;
    
    
    case 'resumen':
        resumen();
        break;
    
    case 'cambiarEntorno':
        cambiarEntorno();
        break;
    
    case 'revertir':
        revertir();
        break;
    
    case 'validarModulos':
        validarModulos();
        break;

        case 'verificarAmortizado':
        verificarAmortizado();
        break;
    
    case 'existenGastosParaAmortizar':
        existenGastosParaAmortizar();
        break;
    
    case 'verificarProrrateado':
        verificarProrrateado();
        break;
    
    case 'revertirAmortizacion':
        revertirAmortizacion();
        break;
    
    case 'revertirProrrateo':
        revertirProrrateo();
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

function existeResumen() {

    $gasto = new Gasto();

    $periodo = $_POST['periodo'];


    $result = $gasto->existeResumen($periodo); 

    echo $result;

}

function resumen() {

    $gasto = new Gasto();

    $periodo = $_POST['periodo'];


    $result = $gasto->traerResumen($periodo); 

    echo json_encode($result);

}



function cambiarEntorno () {
    session_start();

    $entorno = $_POST['entorno'];

    $_SESSION['entorno'] = $entorno;

    echo 'ok';
}



function revertir() {

    $gasto = new Gasto();

    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];

    $result = $gasto->revertir($desde, $hasta);
    
    echo json_encode($result);
}

function validarModulos() {
    $gasto = new Gasto();
    $periodo = $_POST['periodo'];
    $result = $gasto->validarModulos($periodo);
    
    echo json_encode($result);
}

/**
 * Verifica si existen registros amortizados para el período
 */
function verificarAmortizado() {
    require_once '../Class/Gasto.php';
    $gasto = new Gasto();
    
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    
    $result = $gasto->verificarAmortizado($desde, $hasta);
    
    echo $result;
}

/**
 * Verifica si existen gastos con monto de amortización en el período
 */
function existenGastosParaAmortizar() {
    require_once '../Class/Gasto.php';
    $gasto = new Gasto();
    
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    
    $result = $gasto->existenGastosParaAmortizar($desde, $hasta);
    
    echo $result;
}

/**
 * Verifica si existen registros prorrateados para el período
 */
function verificarProrrateado() {
    require_once '../Class/Gasto.php';
    $gasto = new Gasto();
    
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    
    $result = $gasto->verificarProrrateado($desde, $hasta);
    
    echo $result;
}

/**
 * Revierte las amortizaciones del período
 */
function revertirAmortizacion() {
    require_once '../Class/Gasto.php';
    $gasto = new Gasto();
    
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    
    $result = $gasto->revertirAmortizacion($desde, $hasta);
    
    echo json_encode($result);
}

/**
 * Revierte los prorrateos del período
 */
function revertirProrrateo() {
    require_once '../Class/Gasto.php';
    $gasto = new Gasto();
    
    $desde = $_POST['desde'];
    $hasta = $_POST['hasta'];
    
    $result = $gasto->revertirProrrateo($desde, $hasta);
    
    echo json_encode($result);
}

 ?>