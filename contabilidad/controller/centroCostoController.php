<?php

require_once '../Class/centroCosto.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$accion = $_GET['accion'] ?? '';

$centroCosto = new CentroCosto();

switch ($accion) {

    case 'auxiliaresDisponibles':
        echo $centroCosto->traerAuxiliaresDisponibles();
        break;

    case 'listarCentros':
        $estadoParam = $_POST['estado'] ?? 'activos';
        if ($estadoParam === 'activos') {
            $estado = 1;
        } elseif ($estadoParam === 'inactivos') {
            $estado = 0;
        } else {
            $estado = null;
        }
        echo json_encode($centroCosto->traerCentrosCostoAdmin($estado));
        break;

    case 'agregarCentro':
        $codAuxiliar    = $_POST['codAuxiliar']  ?? '';
        $descAuxiliar   = $_POST['descAuxiliar'] ?? '';
        $centroCostoVal = $_POST['centroCosto']  ?? '';
        $sector         = $_POST['sector']       ?? '';
        $numSucursalRaw = $_POST['numSucursal']  ?? '';

        if (empty($codAuxiliar) || empty($descAuxiliar) || empty($centroCostoVal) || empty($sector) || $numSucursalRaw === '') {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
            break;
        }

        if (!is_numeric($numSucursalRaw) || floor((float)$numSucursalRaw) != (float)$numSucursalRaw) {
            echo json_encode(['success' => false, 'message' => 'El número de sucursal debe ser un entero válido']);
            break;
        }

        $numSucursal = (int)$numSucursalRaw;

        $rows = $centroCosto->insertarCentroCosto($codAuxiliar, $descAuxiliar, $centroCostoVal, $sector, $numSucursal);

        if ($rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un centro de costo con ese auxiliar']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Centro de costo agregado correctamente']);
        }
        break;

    case 'cambiarEstado':
        $codAuxiliar = $_POST['codAuxiliar'] ?? '';
        $activoRaw   = $_POST['activo']      ?? '';

        $activo = (int)$activoRaw;
        if (!in_array($activo, [0, 1])) {
            echo json_encode(['success' => false, 'message' => 'Estado no válido']);
            break;
        }

        $centroCosto->cambiarEstadoCentroCosto($codAuxiliar, $activo);
        $mensaje = $activo === 1 ? 'Centro de costo habilitado' : 'Centro de costo inhabilitado';
        echo json_encode(['success' => true, 'message' => $mensaje]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}
?>
