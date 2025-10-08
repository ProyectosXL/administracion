<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../Class/SolicitudEgreso.php';
require_once __DIR__ . '/../Class/Director.php';
require_once __DIR__ . '/../Class/ArchivoSolicitud.php';

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
            
            // Validar archivos para compra personal
            if ($_POST['motivo'] === SolicitudEgreso::MOTIVO_COMPRA_PERSONAL) {
                if (empty($_FILES['archivos']['name'][0])) {
                    throw new Exception('Para compras personales es obligatorio adjuntar la factura');
                }
                
                // Validar tipos y tamaños de archivo
                $archivosPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                $tamaño_maximo = 15 * 1024 * 1024; // 15MB
                
                foreach ($_FILES['archivos']['tmp_name'] as $index => $tmp_name) {
                    if (!empty($tmp_name)) {
                        $tipo = $_FILES['archivos']['type'][$index];
                        $tamaño = $_FILES['archivos']['size'][$index];
                        
                        if (!in_array($tipo, $archivosPermitidos)) {
                            throw new Exception('Formato de archivo no permitido. Use JPG, PNG, GIF o PDF');
                        }
                        
                        if ($tamaño > $tamaño_maximo) {
                            throw new Exception('El archivo no puede superar los 15MB');
                        }
                    }
                }
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
            
            // Crear la solicitud
            $resultado = $solicitud->crear($datos);
            
            // Si se creó exitosamente y hay archivos, procesarlos
            if ($resultado['success'] && $_POST['motivo'] === SolicitudEgreso::MOTIVO_COMPRA_PERSONAL && !empty($_FILES['archivos']['name'][0])) {
                $idSolicitud = $resultado['id_solicitud'];
                $archivosProcesados = procesarArchivos($idSolicitud);
                $resultado['archivos'] = $archivosProcesados;
            }
            
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

/**
 * Procesa los archivos adjuntos y los guarda en la base de datos
 */
function procesarArchivos($idSolicitud) {
    $archivo = new ArchivoSolicitud();
    $archivosProcesados = [];
    
    foreach ($_FILES['archivos']['tmp_name'] as $index => $tmp_name) {
        if (!empty($tmp_name) && is_uploaded_file($tmp_name)) {
            $nombreOriginal = $_FILES['archivos']['name'][$index];
            $mimeType = $_FILES['archivos']['type'][$index];
            $tamaño = $_FILES['archivos']['size'][$index];
            
            // Leer archivo y convertir a base64
            $contenidoArchivo = file_get_contents($tmp_name);
            $archivoBase64 = base64_encode($contenidoArchivo);
            
            try {
                $resultado = $archivo->guardar(
                    $idSolicitud,
                    ArchivoSolicitud::TIPO_FACTURA,
                    $nombreOriginal,
                    $archivoBase64,
                    $mimeType
                );
                
                if ($resultado['success']) {
                    $archivosProcesados[] = [
                        'id' => $resultado['id'],
                        'nombre_original' => $nombreOriginal,
                        'tipo' => ArchivoSolicitud::TIPO_FACTURA,
                        'tamaño' => $tamaño,
                        'mime_type' => $mimeType
                    ];
                }
            } catch (Exception $e) {
                error_log("Error al guardar archivo: " . $e->getMessage());
                // Continuamos con los otros archivos aunque uno falle
            }
        }
    }
    
    return $archivosProcesados;
}