<?php
/**
 * Controlador SIMPLIFICADO para debug del error 500
 */

// Configuración básica de errores
ini_set('display_errors', 0); // Ocultar errores para JSON limpio
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Log de inicio
error_log("🔍 CONTROLADOR SIMPLIFICADO - Inicio");

// Función simple para respuesta
function enviarRespuesta($exito, $mensaje = '', $datos = null) {
    // Limpiar cualquier output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $response = [
        'success' => $exito,
        'message' => $mensaje,
        'data' => $datos
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($exito ? 200 : 500);
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Manejo de errores simple
function manejarError($mensaje, $error = null) {
    $mensajeCompleto = $mensaje;
    if ($error) {
        error_log("ERROR: $mensaje - " . $error->getMessage());
        $mensajeCompleto .= " - " . $error->getMessage();
    }
    enviarRespuesta(false, $mensajeCompleto);
}

try {
    error_log("🔍 Verificando método de petición");
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido - se esperaba POST, se recibió: ' . $_SERVER['REQUEST_METHOD']);
    }
    
    error_log("🔍 Obteniendo action");
    $action = $_GET['action'] ?? '';
    
    if ($action !== 'actualizar_tipo_novedad') {
        manejarError("Acción no válida: $action");
    }
    
    error_log("🔍 Leyendo datos POST");
    $inputData = file_get_contents('php://input');
    
    if (empty($inputData)) {
        manejarError('No se recibieron datos en php://input');
    }
    
    error_log("🔍 Datos recibidos: $inputData");
    
    $datos = json_decode($inputData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        manejarError('JSON inválido: ' . json_last_error_msg());
    }
    
    if (!isset($datos['id'])) {
        manejarError('ID faltante en los datos');
    }
    
    error_log("🔍 Incluyendo clases necesarias");
    
    // Incluir archivos paso a paso
    require_once '../class/Database.php';
    require_once '../class/Usuario.php';
    require_once '../config/usuario_config.php';
    require_once '../class/Novedades.php';
    
    error_log("🔍 Instanciando clases");
    
    $db = Database::getInstance();
    $novedades = new Novedades($db);
    
    error_log("🔍 Verificando permisos de usuario");
    
    if (Usuario::getTipoUsuario() !== Usuario::TIPO_RRHH) {
        manejarError('Acceso denegado - solo RRHH puede actualizar tipos de novedad');
    }
    
    error_log("🔍 Ejecutando actualización - ID: " . $datos['id']);
    
    $resultado = $novedades->actualizarTipoNovedad($datos['id'], $datos);
    
    error_log("🔍 Resultado de actualización: " . ($resultado ? 'TRUE' : 'FALSE'));
    
    if ($resultado) {
        enviarRespuesta(true, 'Tipo de novedad actualizado correctamente');
    } else {
        enviarRespuesta(false, 'No se pudo actualizar - sin cambios realizados');
    }
    
} catch (Exception $e) {
    error_log("❌ EXCEPCIÓN CAPTURADA: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    manejarError('Error en la ejecución', $e);
} catch (Error $e) {
    error_log("❌ ERROR FATAL CAPTURADO: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    manejarError('Error fatal del sistema', $e);
} catch (Throwable $e) {
    error_log("❌ THROWABLE CAPTURADO: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    manejarError('Error crítico del sistema', $e);
}

// Si llegamos aquí algo salió muy mal
error_log("❌ LLEGÓ AL FINAL SIN RESPUESTA - esto no debería pasar");
enviarRespuesta(false, 'Error inesperado - llegó al final sin respuesta');
?>
