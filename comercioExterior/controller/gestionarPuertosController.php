<?php
/**
 * Controlador para gestionar puertos (CRUD)
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../class/puerto.php';
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $puertoClass = new Puerto();
    $accion = isset($_POST['accion']) ? $_POST['accion'] : (isset($_GET['accion']) ? $_GET['accion'] : null);
    
    switch ($accion) {
        case 'listar':
            $puertos = $puertoClass->traerTodosLosPuertos();
            echo json_encode([
                'success' => true,
                'puertos' => $puertos
            ]);
            break;
            
        case 'insertar':
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $pais = isset($_POST['pais']) ? trim($_POST['pais']) : null;
            $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;
            
            if (empty($nombre)) {
                throw new Exception('El nombre del puerto es obligatorio');
            }
            
            $resultado = $puertoClass->insertarPuerto($nombre, $pais, $activo);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Puerto creado correctamente'
                ]);
            } else {
                throw new Exception('Error al insertar puerto');
            }
            break;
            
        case 'actualizar':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $pais = isset($_POST['pais']) ? trim($_POST['pais']) : null;
            $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;
            
            if ($id <= 0 || empty($nombre)) {
                throw new Exception('Datos incompletos');
            }
            
            $resultado = $puertoClass->actualizarPuerto($id, $nombre, $pais, $activo);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Puerto actualizado correctamente'
                ]);
            } else {
                throw new Exception('Error al actualizar puerto');
            }
            break;
            
        case 'eliminar':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }
            
            $resultado = $puertoClass->eliminarPuerto($id);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Puerto desactivado correctamente'
                ]);
            } else {
                throw new Exception('Error al eliminar puerto');
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
