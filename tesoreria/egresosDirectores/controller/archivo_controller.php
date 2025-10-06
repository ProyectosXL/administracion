<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../class/ArchivoSolicitud.php';

try {
    $archivo = new ArchivoSolicitud();
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'subir':
            if (empty($_POST['id_solicitud']) || empty($_POST['tipo_archivo']) || 
                empty($_POST['nombre_archivo']) || empty($_POST['archivo_base64'])) {
                throw new Exception('Datos incompletos');
            }
            
            $resultado = $archivo->guardar(
                $_POST['id_solicitud'],
                $_POST['tipo_archivo'],
                $_POST['nombre_archivo'],
                $_POST['archivo_base64'],
                $_POST['mime_type'] ?? 'application/octet-stream'
            );
            
            echo json_encode($resultado);
            break;
            
        case 'listar':
            if (empty($_GET['id_solicitud'])) {
                throw new Exception('ID de solicitud requerido');
            }
            
            $archivos = $archivo->obtenerPorSolicitud($_GET['id_solicitud']);
            
            echo json_encode([
                'success' => true,
                'data' => $archivos
            ]);
            break;
            
        case 'descargar':
            if (empty($_GET['id'])) {
                throw new Exception('ID de archivo requerido');
            }
            
            $contenido = $archivo->obtenerContenido((int)$_GET['id']);
            
            if ($contenido) {
                echo json_encode([
                    'success' => true,
                    'data' => $contenido
                ]);
            } else {
                throw new Exception('Archivo no encontrado');
            }
            break;
            
        case 'eliminar':
            if (empty($_POST['id'])) {
                throw new Exception('ID de archivo requerido');
            }
            
            $resultado = $archivo->eliminar((int)$_POST['id']);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Archivo eliminado correctamente'
                ]);
            } else {
                throw new Exception('Error al eliminar archivo');
            }
            break;
            
        case 'tipos_validos':
            $tipos = ArchivoSolicitud::obtenerTiposValidos();
            
            echo json_encode([
                'success' => true,
                'data' => $tipos
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