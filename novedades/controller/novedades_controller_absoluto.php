<?php
/**
 * Controlador con PATHS ABSOLUTOS para debug
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

error_log("🔍 CONTROLADOR PATHS ABSOLUTOS - Inicio");

// Obtener path base absoluto
$baseDir = dirname(__DIR__);
error_log("🔍 Base directory: $baseDir");

function enviarRespuesta($exito, $mensaje = '', $datos = null) {
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

function manejarError($mensaje, $error = null) {
    $mensajeCompleto = $mensaje;
    if ($error) {
        error_log("ERROR: $mensaje - " . $error->getMessage());
        $mensajeCompleto .= " - " . $error->getMessage();
    }
    enviarRespuesta(false, $mensajeCompleto);
}

try {
    // Verificar que es POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        manejarError('Método no permitido');
    }
    
    // Verificar action
    $action = $_GET['action'] ?? '';
    if ($action !== 'actualizar_tipo_novedad') {
        manejarError("Acción no válida: $action");
    }
    
    // Leer datos
    $inputData = file_get_contents('php://input');
    if (empty($inputData)) {
        manejarError('No se recibieron datos');
    }
    
    $datos = json_decode($inputData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        manejarError('JSON inválido: ' . json_last_error_msg());
    }
    
    if (!isset($datos['id'])) {
        manejarError('ID faltante');
    }
    
    // Incluir archivos con paths absolutos
    $baseDir = dirname(__DIR__);
    $archivos = [
        'Database' => $baseDir . '/class/Database.php',
        'Usuario' => $baseDir . '/class/Usuario.php',
        'Config' => $baseDir . '/config/usuario_config.php',
        'Novedades' => $baseDir . '/class/Novedades.php'
    ];
    
    foreach ($archivos as $nombre => $archivo) {
        error_log("🔍 Incluyendo $nombre: $archivo");
        
        if (!file_exists($archivo)) {
            manejarError("Archivo $nombre no encontrado: $archivo");
        }
        
        require_once $archivo;
        error_log("✅ $nombre incluido correctamente");
    }
    
    // Instanciar clases
    error_log("🔍 Instanciando Database");
    $db = Database::getInstance();
    
    error_log("🔍 Instanciando Novedades");
    $novedades = new Novedades($db);
    
    // Verificar usuario
    error_log("🔍 Verificando tipo de usuario: " . Usuario::getTipoUsuario());
    if (Usuario::getTipoUsuario() !== Usuario::TIPO_RRHH) {
        manejarError('Acceso denegado - solo RRHH');
    }
    
    // Ejecutar actualización
    error_log("🔍 Ejecutando actualización ID: " . $datos['id']);
    $resultado = $novedades->actualizarTipoNovedad($datos['id'], $datos);
    error_log("🔍 Resultado: " . ($resultado ? 'SUCCESS' : 'FAILURE'));
    
    if ($resultado) {
        enviarRespuesta(true, 'Actualizado correctamente');
    } else {
        enviarRespuesta(false, 'No se pudo actualizar');
    }
    
} catch (Exception $e) {
    error_log("❌ EXCEPCIÓN: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    manejarError('Excepción capturada', $e);
} catch (Error $e) {
    error_log("❌ ERROR FATAL: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    manejarError('Error fatal', $e);
}

error_log("❌ FINAL INESPERADO");
enviarRespuesta(false, 'Final inesperado');
?>
