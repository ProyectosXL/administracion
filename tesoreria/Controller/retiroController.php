<?php
session_start();
header('Content-Type: application/json');
require_once '../Class/sucursal.php';

$accion = $_GET['accion'] ?? '';

$sucursal = new Sucursal(); 

switch ($accion) {
    case 'registrar':
        registrarRetiro();
        break;

    case 'traerDatos': 
        $numeroRegistro = $_GET['numeroRegistro'] ?? '';
        if (empty($numeroRegistro)) {
            echo json_encode(['success' => false, 'message' => 'Número de registro no proporcionado.']);
            exit;
        }

        $datos = $sucursal->traerDatosGuiaRetiro($numeroRegistro);

        if (!empty($datos)) {
            echo json_encode(['success' => true, 'data' => $datos[0]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontraron datos para el número de registro proporcionado.']);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
        break;
}

function registrarRetiro() {
    $datos = $_POST['datos'] ?? null;
    $firma = $_POST['firma'] ?? null;
    $remitos = $_POST['remitos'] ?? null;
    $nroSucursal = $_POST['nroSucursal'] ?? null;
    $estado = $_POST['estado'] ?? null;
    
    if($estado != 1 ){

        if (empty($datos) || empty($firma)) {
            echo json_encode(['success' => false, 'message' => 'Datos no proporcionados.']);
            exit;
        }

    }
    $sucursal = new Sucursal();
    
   
    $resultado = $sucursal->insertarEncabezadoGuiaRetiro($datos, $nroSucursal, $firma, $estado);
    
    if ($resultado['success']) {

        if(empty($remitos)){
            echo true;
            die();
        }

        foreach ($remitos as $remito) {    
            $sucursal->insertarEgresos($datos['numeroRegistro'], $remito['fecha'], $remito['t_comp'], $remito['remito']);
            $sucursal->insertarRemitos($datos['numeroRegistro'], $remito['fecha'], $remito['remito'], $remito['destino'], $remito['bultos']);
            
        }

        echo true;
    }else{
        echo false;
    }

}

function actualizarRetiro() {
    $datos = $_POST['datos'] ?? null;
    $firma = $_POST['firma'] ?? null;
    $remitos = $_POST['remitos'] ?? null;
    $nroSucursal = $_POST['nroSucursal'] ?? null;
    $estado = $_POST['estado'] ?? null;
    
    if($estado != 1 ){

        if (empty($datos) || empty($firma)) {
            echo json_encode(['success' => false, 'message' => 'Datos no proporcionados.']);
            exit;
        }

    }
    $sucursal = new Sucursal();
    
   
    $resultado = $sucursal->actualizarEncabezadoGuiaRetiro($datos, $nroSucursal, $firma, $estado);
    
    if ($resultado['success']) {

        if(empty($remitos)){
            echo true;
            die();
        }

        foreach ($remitos as $remito) {    
            $sucursal->insertarEgresos($datos['numeroRegistro'], $remito['fecha'], $remito['t_comp'], $remito['remito']);
            $sucursal->insertarRemitos($datos['numeroRegistro'], $remito['fecha'], $remito['remito'], $remito['destino'], $remito['bultos']);
            
        }

        echo true;
    }else{
        echo false;
    }


}