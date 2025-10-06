<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../Class/SolicitudEgreso.php';
require_once __DIR__ . '/../Class/Director.php';

try {
    $solicitud = new SolicitudEgreso();
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
            // Validar datos requeridos
            if (empty($_POST['id_director']) || empty($_POST['motivo']) || empty($_POST['importe'])) {
                throw new Exception('Faltan datos obligatorios');
            }
            
            // Validar motivo
            $motivosValidos = [
                SolicitudEgreso::MOTIVO_COMPRA_PERSONAL,
                SolicitudEgreso::MOTIVO_RETIRO_DINERO
            ];
            
            if (!in_array($_POST['motivo'], $motivosValidos)) {
                throw new Exception('Motivo no válido');
            }
            
            // Limpiar y validar importe
            $importe = str_replace(['.', ','], ['', '.'], $_POST['importe']);
            $importe = (float)$importe;
            
            if ($importe <= 0) {
                throw new Exception('El importe debe ser mayor a cero');
            }
            
            // Validar que el ID director sea numérico
            $idDirector = (int)$_POST['id_director'];
            if ($idDirector <= 0) {
                throw new Exception('ID de director no válido');
            }
            
            $datos = [
                'id_director' => $idDirector,
                'motivo' => $_POST['motivo'],
                'importe' => $importe,
                'observaciones' => $_POST['observaciones'] ?? ''
            ];
            
            $resultado = $solicitud->crear($datos);
            
            echo json_encode($resultado);
            break;
            
        case 'listar':
            $filtros = [];
            
            if (!empty($_GET['id_director'])) {
                $filtros['id_director'] = (int)$_GET['id_director'];
            }
            
            if (!empty($_GET['nombre_director'])) {
                $filtros['nombre_director'] = $_GET['nombre_director'];
            }
            
            if (!empty($_GET['estado'])) {
                $filtros['estado'] = $_GET['estado'];
            }
            
            if (!empty($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            
            if (!empty($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            
            $solicitudes = $solicitud->obtenerTodas($filtros);
            
            echo json_encode([
                'success' => true,
                'data' => $solicitudes
            ]);
            break;
            
        case 'obtener':
            if (empty($_GET['id_solicitud'])) {
                throw new Exception('ID de solicitud requerido');
            }
            
            $datos = $solicitud->obtenerPorId($_GET['id_solicitud']);
            
            if ($datos) {
                echo json_encode([
                    'success' => true,
                    'data' => $datos
                ]);
            } else {
                throw new Exception('Solicitud no encontrada');
            }
            break;
            
        case 'actualizar_estado':
            if (empty($_POST['id_solicitud']) || empty($_POST['estado'])) {
                throw new Exception('Datos incompletos');
            }
            
            $resultado = $solicitud->actualizarEstado(
                $_POST['id_solicitud'],
                $_POST['estado'],
                $_POST['usuario'] ?? 'SISTEMA',
                $_POST['observaciones'] ?? ''
            );
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente'
                ]);
            } else {
                throw new Exception('Error al actualizar estado');
            }
            break;
            
        case 'historial':
            if (empty($_GET['id_solicitud'])) {
                throw new Exception('ID de solicitud requerido');
            }
            
            $historial = $solicitud->obtenerHistorial($_GET['id_solicitud']);
            
            echo json_encode([
                'success' => true,
                'data' => $historial
            ]);
            break;
            
        case 'obtener_directores':
            $director = new Director();
            $directores = $director->obtenerDirectores();
            
            echo json_encode([
                'success' => true,
                'data' => $directores
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}