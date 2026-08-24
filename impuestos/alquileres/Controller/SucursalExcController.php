<?php
/**
 * Endpoints de la pestaña "Sucursales" del modal de Parámetros.
 * Administra RO_T_SUCURSALES_ALQUILERES_EXC: las excepciones a la regla automática
 * de visibilidad y carga de sucursales.
 */

header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../Class/Sucursal.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

try {

    switch ($accion) {

        case 'traerSucursales':
            echo json_encode([
                'success' => true,
                'data'    => getSucursalesParametros(),
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'guardarExcepcion':
            $nroSucursal = isset($_POST['nroSucursal']) ? trim($_POST['nroSucursal']) : '';

            if ($nroSucursal === '') {
                throw new Exception('Falta el número de sucursal');
            }

            // '' llega desde el <select> cuando la opción es "Automático".
            $visible = (isset($_POST['visible']) && $_POST['visible'] !== '') ? (int) $_POST['visible'] : null;
            $carga   = (isset($_POST['carga'])   && $_POST['carga']   !== '') ? (int) $_POST['carga']   : null;

            if ($visible !== null && $visible !== 0 && $visible !== 1) {
                throw new Exception('Valor inválido para visibilidad');
            }

            if ($carga !== null && $carga !== 0 && $carga !== 1) {
                throw new Exception('Valor inválido para carga');
            }

            $observacion = isset($_POST['observacion']) ? substr(trim($_POST['observacion']), 0, 200) : null;
            $usuario     = isset($_SESSION['username']) ? substr($_SESSION['username'], 0, 50) : null;

            guardarSucursalExcepcion($nroSucursal, $visible, $carga, $observacion, $usuario);

            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        case 'eliminarExcepcion':
            $nroSucursal = isset($_POST['nroSucursal']) ? trim($_POST['nroSucursal']) : '';

            if ($nroSucursal === '') {
                throw new Exception('Falta el número de sucursal');
            }

            eliminarSucursalExcepcion($nroSucursal);

            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    error_log('SucursalExcController: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
