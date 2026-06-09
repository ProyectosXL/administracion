<?php

require_once '../Class/prorrateo.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$accion = $_GET['accion'] ?? '';

$prorrateo = new Prorrateo();

switch ($accion) {

    case 'listarMetodos':
        $estadoParam = $_POST['estado'] ?? 'todos';
        if ($estadoParam === 'activos') {
            $estado = 1;
        } elseif ($estadoParam === 'inactivos') {
            $estado = 0;
        } else {
            $estado = null;
        }
        echo $prorrateo->traerMetodosProrrateoAdmin($estado);
        break;

    case 'cambiarEstado':
        $cod      = $_POST['cod']    ?? '';
        $activoRaw = $_POST['activo'] ?? '';

        $activo = (int)$activoRaw;
        if (!in_array($activo, [0, 1]) || empty($cod)) {
            echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
            break;
        }

        $ok = $prorrateo->cambiarEstadoProrrateo($cod, $activo);
        $mensaje = $activo === 1 ? 'Método habilitado' : 'Método inhabilitado';
        echo json_encode(['success' => $ok, 'message' => $ok ? $mensaje : 'Error al actualizar']);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}
?>
