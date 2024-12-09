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


    var_dump($datos, $firma);
    // try {
    //     $datos = [
    //         'numeroRegistro' => $_POST['numeroRegistro'] ?? '',
    //         'nroSucursal'   => $_SESSION['numsuc'] ?? '2',
    //         'entrego'       => $_POST['entrego'] ?? '',
    //         'recibio'       => $_POST['recibio'] ?? '',
    //         'enviaValores'  => $_POST['enviaValores'] ?? '',
    //         'precinto'      => $_POST['numeroPrecinto'] ?? null,
    //         'observaciones' => $_POST['observaciones'] ?? '',
    //         'firma'         => $_POST['firma'] ?? '',
    //         'egresos'       => $_POST['egresos'] ?? [],
    //         'remitos'       => $_POST['remitos'] ?? []
    //     ];

        
    //     if (empty($datos['numeroRegistro']) || empty($datos['entrego']) || empty($datos['recibio'])) {
    //         throw new Exception("Datos faltantes o incorrectos.");
    //     }

        
    //     if (!$sucursal->insertarEncabezadoGuiaRetiro($datos)) {
    //         throw new Exception("Error al insertar el encabezado.");
    //     }

        
    //     if ($datos['enviaValores'] === 'SI' && !empty($datos['egresos'])) {
    //         if (!$sucursal->insertarEgresos($datos['numeroRegistro'], $datos['egresos'])) {
    //             throw new Exception("Error al insertar egresos.");
    //         }
    //     }

        
    //     if (!empty($datos['remitos'])) {
    //         if (!$sucursal->insertarRemitos($datos['numeroRegistro'], $datos['remitos'])) {
    //             throw new Exception("Error al insertar remitos.");
    //         }
    //     }

    //     echo json_encode([
    //         'success' => true,
    //         'message' => 'Registro exitoso'
    //     ]);

    // } catch (Exception $e) {
    //     error_log("Error al registrar el retiro: " . $e->getMessage());
    //     echo json_encode([
    //         'success' => false,
    //         'message' => "Error: " . $e->getMessage()
    //     ]);
    // }
}
