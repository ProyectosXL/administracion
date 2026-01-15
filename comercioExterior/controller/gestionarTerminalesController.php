<?php
/**
 * Controlador para gestionar terminales (CRUD)
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../class/terminal.php';
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $terminalClass = new Terminal();
    $accion = isset($_POST['accion']) ? $_POST['accion'] : (isset($_GET['accion']) ? $_GET['accion'] : null);
    
    switch ($accion) {
        case 'listar':
            $terminales = $terminalClass->traerTodasLasTerminales();
            echo json_encode([
                'success' => true,
                'terminales' => $terminales
            ]);
            break;
            
        case 'insertar':
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $entorno = isset($_POST['entorno']) ? trim($_POST['entorno']) : '';
            $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;
            
            if (empty($nombre) || empty($entorno)) {
                throw new Exception('Nombre y entorno son obligatorios');
            }
            
            if (!in_array($entorno, ['ARG', 'UY', 'AMBOS'])) {
                throw new Exception('Entorno inválido');
            }
            
            $resultado = $terminalClass->insertarTerminal($nombre, $entorno, $activo);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Terminal creada correctamente'
                ]);
            } else {
                throw new Exception('Error al insertar terminal');
            }
            break;
            
        case 'actualizar':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $entorno = isset($_POST['entorno']) ? trim($_POST['entorno']) : '';
            $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;
            
            if ($id <= 0 || empty($nombre) || empty($entorno)) {
                throw new Exception('Datos incompletos');
            }
            
            if (!in_array($entorno, ['ARG', 'UY', 'AMBOS'])) {
                throw new Exception('Entorno inválido');
            }
            
            $resultado = $terminalClass->actualizarTerminal($id, $nombre, $entorno, $activo);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Terminal actualizada correctamente'
                ]);
            } else {
                throw new Exception('Error al actualizar terminal');
            }
            break;
            
        case 'eliminar':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }
            
            $resultado = $terminalClass->eliminarTerminal($id);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Terminal desactivada correctamente'
                ]);
            } else {
                throw new Exception('Error al eliminar terminal');
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
