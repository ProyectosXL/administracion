<?php
session_start();
header('Content-Type: application/json');
require_once '../Class/sucursal.php';
require_once '../Class/gasto.php';

$accion = $_GET['accion'] ?? '';

$sucursal = new Sucursal();
$gasto = new Gasto();

switch ($accion) {
    case 'registrar':
        registrarRetiro();
        break;

    case 'actualizar':
        actualizarRetiro();
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
    try {
        $datos = $_POST['datos'] ?? null;
        $firma = $_POST['firma'] ?? null;
        $remitos = $_POST['remitos'] ?? [];
        $nroSucursal = $_POST['nroSucursal'] ?? null;
        $estado = $_POST['estado'] ?? null;
        
        if($estado != 1 ){

            if (empty($datos) || empty($firma)) {
                echo json_encode(['success' => false, 'message' => 'Datos no proporcionados.']);
                exit;
            }

        }
        $sucursal = new Sucursal();
        $gasto = new Gasto();

    // --- VALIDATION START ---
    // Check for existing record with the same NRO_REGISTRO and NRO_SUCURS
    $existingRecord = $sucursal->checkExistingRetiro($datos['numeroRegistro'], $nroSucursal);

    if ($existingRecord) {
        // If a record exists, return an error
        echo json_encode(['success' => false, 'message' => 'Ya existe un registro con este número para esta sucursal.']);
        exit; // Stop execution
    }
    // --- VALIDATION END ---
    
    $resultado = $sucursal->insertarEncabezadoGuiaRetiro($datos, $nroSucursal, $firma, $estado);
    
    if ($resultado['success']) {

        if((count($remitos) > 0) ){

            $sucursal->limpiarRemitos($datos['numeroRegistro'], $nroSucursal);

            foreach ($remitos as $remito) {    
                $sucursal->insertarRemitos($datos['numeroRegistro'], $remito['fecha'], $remito['remito'], $remito['destino'], $remito['bultos'], $nroSucursal);
                
            }

        }


        if(isset($datos['egresos']) && count($datos['egresos']) > 0){

            $gasto->limpiarEgresos($datos['numeroRegistro'], $nroSucursal);

            foreach ($datos['egresos'] as $egreso) {
                $gasto->insertarEgresos($datos['numeroRegistro'], $egreso['fecha'], $egreso['tipo'], $egreso['comprobante'], $nroSucursal);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Registro guardado correctamente']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el registro']);
    }

    } catch (Exception $e) {
        error_log("Error en registrarRetiro: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
    }
}

function actualizarRetiro() {
    try {
        $datos = $_POST['datos'] ?? null;
        $firma = $_POST['firma'] ?? null;
        $remitos = $_POST['remitos'] ?? null;
        $nroSucursal = $_POST['nroSucursal'] ?? null;
        $estado = $_POST['estado'] ?? null;
        $egresos = $_POST['egresos'] ?? null;
        
        
        if($estado != 1 ){

            if (empty($datos) || empty($firma)) {
            echo json_encode(['success' => false, 'message' => 'Datos no proporcionados.']);
            exit;
        }

    }
    $sucursal = new Sucursal();
 
   
    $resultado = $sucursal->actualizarEncabezadoGuiaRetiro($datos, $nroSucursal, $firma, $estado);


    if((count($remitos) > 0) ){

        $sucursal->limpiarRemitos($datos['numeroRegistro'], $nroSucursal);

        foreach ($remitos as $remito) {    
            $sucursal->insertarRemitos($datos['numeroRegistro'], $remito['fecha'], $remito['remito'], $remito['destino'], $remito['bultos'], $nroSucursal);
            
        }

    }


    if(isset($datos['egresos']) && count($datos['egresos']) > 0){

        $gasto->limpiarEgresos($datos['numeroRegistro'], $nroSucursal);

        foreach ($datos['egresos'] as $egreso) {
            $gasto->insertarEgresos($datos['numeroRegistro'], $egreso['fecha'], $egreso['tipo'], $egreso['comprobante'], $nroSucursal);
        }
    }
    echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);

    } catch (Exception $e) {
        error_log("Error en actualizarRetiro: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
    }
}