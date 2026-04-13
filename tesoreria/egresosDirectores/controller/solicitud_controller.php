<?php
// Deshabilitar output de errores en HTML para no romper el JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../Class/SolicitudEgreso.php';
require_once __DIR__ . '/../Class/Director.php';
require_once __DIR__ . '/../Class/ArchivoSolicitud.php';
require_once __DIR__ . '/../Class/EmailNotificacion.php';

try {
    $solicitud = new SolicitudEgreso();
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    
    switch ($accion) {
        case 'crear':
            
            // Log de datos recibidos
            error_log("DEBUG - POST recibido: " . print_r($_POST, true));
            error_log("DEBUG - Motivo: " . ($_POST['motivo'] ?? 'no definido'));
            error_log("DEBUG - Tipo asignación: " . ($_POST['tipo_asignacion'] ?? 'no definido'));
            
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
                
                // Debug: log de lo que llega
                
                error_log("DEBUG - FILES structure: " . print_r($_FILES, true));
                error_log("DEBUG - POST data: " . print_r($_POST, true));
                
                // Verificar estructura de archivos
                if (!isset($_FILES['archivos']) || empty($_FILES['archivos']['name'])) {
                    error_log("ERROR - No se encontraron archivos en \$_FILES['archivos']");
                    throw new Exception('Para compras personales es obligatorio adjuntar la factura');
                }
                
                
                // Manejar tanto el formato array como el formato individual
                $archivos = $_FILES['archivos'];
                $cantidadArchivos = 0;
                
                // Si name es un array, hay múltiples archivos
                if (is_array($archivos['name'])) {
                    $cantidadArchivos = count(array_filter($archivos['name']));
                    
                    if ($cantidadArchivos === 0) {
                        throw new Exception('Para compras personales es obligatorio adjuntar la factura');
                    }
                } else {
                    // Un solo archivo
                    if (empty($archivos['name'])) {
                        throw new Exception('Para compras personales es obligatorio adjuntar la factura');
                    }
                    $cantidadArchivos = 1;
                }
                
                error_log("DEBUG - Cantidad de archivos detectados: " . $cantidadArchivos);
                
                // Validar tipos y tamaños de archivo
                $archivosPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                $tamaño_maximo = 15 * 1024 * 1024; // 15MB
                
                // Normalizar a array para procesamiento uniforme
                $archivosParaValidar = [];
                if (is_array($archivos['tmp_name'])) {
                    // Múltiples archivos
                    for ($i = 0; $i < count($archivos['tmp_name']); $i++) {
                        if (!empty($archivos['tmp_name'][$i])) {
                            $archivosParaValidar[] = [
                                'tmp_name' => $archivos['tmp_name'][$i],
                                'type' => $archivos['type'][$i],
                                'size' => $archivos['size'][$i],
                                'name' => $archivos['name'][$i]
                            ];
                        }
                    }
                } else {
                    // Un solo archivo
                    if (!empty($archivos['tmp_name'])) {
                        $archivosParaValidar[] = [
                            'tmp_name' => $archivos['tmp_name'],
                            'type' => $archivos['type'],
                            'size' => $archivos['size'],
                            'name' => $archivos['name']
                        ];
                    }
                }
                
                
                foreach ($archivosParaValidar as $idx => $archivo) {
                    
                    if (!in_array($archivo['type'], $archivosPermitidos)) {
                        throw new Exception('Formato de archivo no permitido. Use JPG, PNG, GIF o PDF');
                    }
                    
                    if ($archivo['size'] > $tamaño_maximo) {
                        throw new Exception('El archivo no puede superar los 15MB');
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
            
            // Preparar datos de la solicitud
            $datos = [
                'id_director' => $idDirector,
                'motivo' => $_POST['motivo'],
                'importe' => $importe,
                'observaciones' => $_POST['observaciones'] ?? ''
            ];
            
            // Agregar datos de proveedor si es compra personal
            if ($_POST['motivo'] === SolicitudEgreso::MOTIVO_COMPRA_PERSONAL) {
                // Validar CBU si se proporciona
                if (!empty($_POST['cbu'])) {
                    // Limpiar CBU (eliminar espacios)
                    $cbu = str_replace(' ', '', $_POST['cbu']);
                    
                    // Validar formato (22 dígitos)
                    if (!preg_match('/^\d{22}$/', $cbu)) {
                        throw new Exception('El CBU debe tener exactamente 22 dígitos');
                    }
                    
                    $datos['cbu'] = $cbu;
                }
                
                // Agregar nombre de proveedor si se seleccionó
                if (!empty($_POST['nom_provee'])) {
                    $datos['nom_provee'] = $_POST['nom_provee'];
                }
                
                // Agregar descripción CBU si se proporcionó
                if (!empty($_POST['descripcion_cbu'])) {
                    $datos['descripcion_cbu'] = $_POST['descripcion_cbu'];
                }
            }
            
            // Agregar indicador de archivos si es compra personal
            if ($_POST['motivo'] === SolicitudEgreso::MOTIVO_COMPRA_PERSONAL) {
                // Indicar que hay archivos (ya fueron validados arriba)
                $datos['archivos'] = true;
            }
            
            // Verificar si es retiro múltiple
            $esRetiroMultiple = false;
            if ($_POST['motivo'] === SolicitudEgreso::MOTIVO_RETIRO_DINERO && 
                isset($_POST['tipo_asignacion']) && 
                $_POST['tipo_asignacion'] === 'MULTIPLE' &&
                !empty($_POST['distribucion'])) {
                
                $esRetiroMultiple = true;
                
                // Decodificar distribución JSON
                $distribucion = json_decode($_POST['distribucion'], true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Error al decodificar la distribución: ' . json_last_error_msg());
                }
                
                if (empty($distribucion) || !is_array($distribucion)) {
                    throw new Exception('La distribución no es válida');
                }
                
                error_log("DEBUG - Creando retiro múltiple con " . count($distribucion) . " directores");
                
                // Usar el método crearMultiple
                $resultado = $solicitud->crearMultiple($datos, $distribucion);
            } else {
                // Crear solicitud individual normal
                $resultado = $solicitud->crear($datos);
            }
            
            error_log("DEBUG - Resultado de crear solicitud: " . print_r($resultado, true));
            
            // Si se creó exitosamente y hay archivos, procesarlos
            // NOTA: Los retiros múltiples NO tienen archivos (solo compras personales)
            if ($resultado['success'] && $_POST['motivo'] === SolicitudEgreso::MOTIVO_COMPRA_PERSONAL) {
                
                error_log("DEBUG - Intentando procesar archivos...");
                error_log("DEBUG - FILES: " . print_r($_FILES, true));
                
                // Verificar si realmente hay archivos para procesar
                if (isset($_FILES['archivos'])) {
                    $hayArchivos = false;
                    
                    if (is_array($_FILES['archivos']['name'])) {
                        $hayArchivos = !empty($_FILES['archivos']['name'][0]);
                    } else {
                        $hayArchivos = !empty($_FILES['archivos']['name']);
                    }
                    
                    error_log("DEBUG - Hay archivos para procesar: " . ($hayArchivos ? 'SI' : 'NO'));
                    
                    if ($hayArchivos) {
                        $idSolicitud = $resultado['id_solicitud'];
                        error_log("DEBUG - Procesando archivos para solicitud: " . $idSolicitud);
                        
                        $archivosProcesados = procesarArchivos($idSolicitud);
                        $resultado['archivos'] = $archivosProcesados;
                        
                        error_log("DEBUG - Archivos procesados: " . count($archivosProcesados));
                        error_log("DEBUG - Detalle archivos: " . print_r($archivosProcesados, true));
                    } else {
                    }
                } else {
                }
            }
            
            // Enviar notificación por email si la solicitud se creó exitosamente
            if ($resultado['success']) {
                try {
                    $emailNotificacion = new EmailNotificacion();
                    
                    // Verificar si el resultado proviene del flujo múltiple.
                    // Puede contener una sola solicitud si se cargó un único director con tipo_asignacion = MULTIPLE.
                    if (isset($resultado['solicitudes_creadas'])) {
                        error_log("DEBUG - Enviando email de solicitud múltiple con " . $resultado['solicitudes_creadas'] . " solicitudes");
                        
                        $emailEnviado = $emailNotificacion->notificarNuevaSolicitudMultiple([
                            'id_base' => $resultado['id_base'] ?? '',
                            'solicitudes_creadas' => $resultado['solicitudes_creadas'] ?? 0,
                            'detalles' => $resultado['detalles'] ?? [],
                            'observaciones' => $_POST['observaciones'] ?? ''
                        ]);
                        
                        if ($emailEnviado) {
                            error_log("DEBUG - Email de solicitud múltiple enviado correctamente");
                        } else {
                            error_log("DEBUG - No se pudo enviar el email de solicitud múltiple");
                        }
                        
                    } else {
                        // Es solicitud individual
                        error_log("DEBUG - Intentando enviar email para solicitud: " . $resultado['id_solicitud']);
                        $emailEnviado = $emailNotificacion->notificarNuevaSolicitud([
                            'id_solicitud' => $resultado['id_solicitud'],
                            'motivo' => $_POST['motivo']
                        ]);
                        
                        if ($emailEnviado) {
                            error_log("DEBUG - Notificación por email enviada correctamente");
                        } else {
                            error_log("DEBUG - No se pudo enviar la notificación por email");
                        }
                    }
                } catch (Throwable $emailException) {
                    // Capturar cualquier error de email pero no interrumpir el flujo
                    error_log("ERROR al enviar email: " . $emailException->getMessage());
                    error_log("TRACE: " . $emailException->getTraceAsString());
                    // No re-lanzar la excepción para que no afecte la respuesta JSON
                }
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
            
            $idSolicitud = $_POST['id_solicitud'];
            $nuevoEstado = $_POST['estado'];
            $usuario = $_POST['usuario'] ?? 'SISTEMA';
            $observaciones = $_POST['observaciones'] ?? '';
            
            // Actualizar el estado
            $resultado = $solicitud->actualizarEstado(
                $idSolicitud,
                $nuevoEstado,
                $usuario,
                $observaciones
            );
            
            if (!$resultado) {
                throw new Exception('Error al actualizar estado');
            }
            
            // Actualizar observaciones específicas según el usuario
            if (!empty($observaciones)) {
                if ($usuario === 'PROVEEDORES') {
                    $solicitud->actualizarObservacionesProveedores($idSolicitud, $observaciones);
                } elseif ($usuario === 'TESORERIA') {
                    $solicitud->actualizarObservacionesTesoreria($idSolicitud, $observaciones);
                }
            }
            
            // Enviar email si proveedores carga la orden de compra (cambia a CARGADO)
            if ($usuario === 'PROVEEDORES' && $nuevoEstado === SolicitudEgreso::ESTADO_CARGADO) {
                try {
                    error_log("DEBUG - Intentando enviar email de O.C. cargada para solicitud: " . $idSolicitud);
                    $emailNotificacion = new EmailNotificacion();
                    $emailEnviado = $emailNotificacion->notificarOrdenCompraCargada($idSolicitud);
                    
                    if ($emailEnviado) {
                        error_log("DEBUG - Notificación de O.C. cargada enviada correctamente");
                    } else {
                        error_log("DEBUG - No se pudo enviar la notificación de O.C. cargada");
                    }
                } catch (Throwable $emailException) {
                    // Capturar cualquier error de email pero no interrumpir el flujo
                    error_log("ERROR al enviar email O.C.: " . $emailException->getMessage());
                    error_log("TRACE: " . $emailException->getTraceAsString());
                    // No re-lanzar la excepción
                }
            }
            
            // Enviar email si tesorería paga la solicitud (cambia a PAGADO)
            if ($usuario === 'TESORERIA' && $nuevoEstado === SolicitudEgreso::ESTADO_PAGADO) {
                try {
                    error_log("DEBUG - Intentando enviar email de pago realizado para solicitud: " . $idSolicitud);
                    $emailNotificacion = new EmailNotificacion();
                    $emailEnviado = $emailNotificacion->notificarPagoRealizado($idSolicitud);
                    
                    if ($emailEnviado) {
                        error_log("DEBUG - Notificación de pago realizado enviada correctamente");
                    } else {
                        error_log("DEBUG - No se pudo enviar la notificación de pago realizado");
                    }
                } catch (Throwable $emailException) {
                    // Capturar cualquier error de email pero no interrumpir el flujo
                    error_log("ERROR al enviar email de pago: " . $emailException->getMessage());
                    error_log("TRACE: " . $emailException->getTraceAsString());
                    // No re-lanzar la excepción
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Estado actualizado correctamente'
            ]);
            break;
            
        case 'historial':
            // El historial se maneja con los campos de auditoría en la misma tabla
            // fecha_solicitud, fecha_modificacion, usuario_modificacion, observaciones_*
            if (empty($_GET['id_solicitud'])) {
                throw new Exception('ID de solicitud requerido');
            }
            
            // Devolver los datos de auditoría de la solicitud
            $solicitudData = $solicitud->obtenerPorId($_GET['id_solicitud']);
            
            if ($solicitudData) {
                $historial = [];
                
                // Evento 1: Creación de la solicitud
                $estadoInicial = ($solicitudData['motivo'] === 'RETIRO_DINERO') ? 'CARGADO' : 'SOLICITADO';
                
                $historial[] = [
                    'fecha_cambio' => $solicitudData['fecha_solicitud'],
                    'estado_anterior' => null,
                    'estado_nuevo' => $estadoInicial,
                    'usuario' => 'DIRECTORES',
                    'observaciones' => $solicitudData['observaciones']
                ];
                
                // Evento 2: Si hay modificación (cambio de estado)
                if ($solicitudData['fecha_modificacion'] && $solicitudData['usuario_modificacion']) {
                    // Determinar observaciones según el usuario que modificó
                    $observaciones = '';
                    if ($solicitudData['usuario_modificacion'] === 'PROVEEDORES' && !empty($solicitudData['observaciones_proveedores'])) {
                        $observaciones = $solicitudData['observaciones_proveedores'];
                    } elseif ($solicitudData['usuario_modificacion'] === 'TESORERIA' && !empty($solicitudData['observaciones_tesoreria'])) {
                        $observaciones = $solicitudData['observaciones_tesoreria'];
                    }
                    
                    $historial[] = [
                        'fecha_cambio' => $solicitudData['fecha_modificacion'],
                        'estado_anterior' => $estadoInicial,
                        'estado_nuevo' => $solicitudData['estado'],
                        'usuario' => $solicitudData['usuario_modificacion'],
                        'observaciones' => $observaciones
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $historial
                ]);
            } else {
                throw new Exception('Solicitud no encontrada');
            }
            break;
            
        case 'obtener_directores':
            $director = new Director();
            $directores = $director->obtenerDirectores();
            
            echo json_encode([
                'success' => true,
                'data' => $directores
            ]);
            break;
            
        case 'obtener_directores_distribucion':
            $director = new Director();
            $directores = $director->obtenerDirectoresDistribucion();
            
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
    error_log("ERROR en solicitud_controller: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    http_response_code(500);
    error_log("ERROR FATAL en solicitud_controller: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error fatal en el servidor: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

/**
 * Procesa los archivos adjuntos y los guarda en la base de datos
 */
function procesarArchivos($idSolicitud) {
    
    error_log("DEBUG procesarArchivos - Iniciando para solicitud: " . $idSolicitud);
    
    $archivo = new ArchivoSolicitud();
    $archivosProcesados = [];
    
    if (!isset($_FILES['archivos'])) {
        error_log("DEBUG procesarArchivos - No hay \$_FILES['archivos']");
        return $archivosProcesados;
    }
    
    $archivos = $_FILES['archivos'];
    $archivosParaProcesar = [];
    
    error_log("DEBUG procesarArchivos - Estructura de archivos: " . print_r($archivos, true));
    
    // Normalizar a array para procesamiento uniforme
    if (is_array($archivos['tmp_name'])) {
        error_log("DEBUG procesarArchivos - Detectado formato array");
        // Múltiples archivos
        for ($i = 0; $i < count($archivos['tmp_name']); $i++) {
            if (!empty($archivos['tmp_name'][$i])) {
                $archivosParaProcesar[] = [
                    'tmp_name' => $archivos['tmp_name'][$i],
                    'name' => $archivos['name'][$i],
                    'type' => $archivos['type'][$i],
                    'size' => $archivos['size'][$i]
                ];
                error_log("DEBUG procesarArchivos - Archivo {$i}: " . $archivos['name'][$i]);
            }
        }
    } else {
        error_log("DEBUG procesarArchivos - Detectado formato simple");
        // Un solo archivo
        if (!empty($archivos['tmp_name'])) {
            $archivosParaProcesar[] = [
                'tmp_name' => $archivos['tmp_name'],
                'name' => $archivos['name'],
                'type' => $archivos['type'],
                'size' => $archivos['size']
            ];
        }
    }
    
    error_log("DEBUG procesarArchivos - Total archivos a procesar: " . count($archivosParaProcesar));
    
    foreach ($archivosParaProcesar as $index => $archivoData) {
        error_log("DEBUG procesarArchivos - Procesando archivo {$index}: " . $archivoData['name']);
        
        if (is_uploaded_file($archivoData['tmp_name'])) {
            error_log("DEBUG procesarArchivos - Archivo validado como uploaded");
            
            // Leer archivo y convertir a base64
            $contenidoArchivo = file_get_contents($archivoData['tmp_name']);
            $archivoBase64 = base64_encode($contenidoArchivo);
            
            error_log("DEBUG procesarArchivos - Tamaño contenido: " . strlen($contenidoArchivo) . " bytes");
            error_log("DEBUG procesarArchivos - Tamaño base64: " . strlen($archivoBase64) . " caracteres");
            
            try {
                $resultado = $archivo->guardar(
                    $idSolicitud,
                    ArchivoSolicitud::TIPO_FACTURA,
                    $archivoData['name'],
                    $archivoBase64,
                    $archivoData['type']
                );
                
                error_log("DEBUG procesarArchivos - Resultado guardar: " . print_r($resultado, true));
                
                if ($resultado['success']) {
                    $archivosProcesados[] = [
                        'id' => $resultado['id'],
                        'nombre_original' => $archivoData['name'],
                        'tipo' => ArchivoSolicitud::TIPO_FACTURA,
                        'tamaño' => $archivoData['size'],
                        'mime_type' => $archivoData['type']
                    ];
                    error_log("DEBUG procesarArchivos - Archivo guardado exitosamente con ID: " . $resultado['id']);
                } else {
                    error_log("ERROR procesarArchivos - Fallo al guardar: " . ($resultado['message'] ?? 'Sin mensaje'));
                }
            } catch (Exception $e) {
                error_log("ERROR procesarArchivos - Exception al guardar archivo: " . $e->getMessage());
                // Continuamos con los otros archivos aunque uno falle
            }
        } else {
            error_log("ERROR procesarArchivos - Archivo NO es uploaded_file: " . $archivoData['tmp_name']);
        }
    }
    
    error_log("DEBUG procesarArchivos - Total archivos procesados: " . count($archivosProcesados));
    
    return $archivosProcesados;
}
