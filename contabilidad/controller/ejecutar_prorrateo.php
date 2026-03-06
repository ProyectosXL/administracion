<?php
/**
 * Script que se ejecuta en segundo plano para realizar el prorrateo
 * Este archivo es llamado por ProrrateoController.php
 */

// Configurar para ejecución en background
set_time_limit(0); // Sin límite de tiempo
ini_set('memory_limit', '512M'); // Aumentar memoria disponible
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/prorrateo_debug.log');

// Función de logging
function logDebug($msg) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/prorrateo_debug.log';
    file_put_contents($logFile, "[$timestamp] $msg\n", FILE_APPEND);
}

logDebug("=== INICIO PROCESO DE PRORRATEO ===");
logDebug("Argumentos recibidos: " . print_r($argv, true));

// Obtener parámetros de línea de comandos
$idProceso = $argv[1] ?? null;
$desde = $argv[2] ?? null;
$hasta = $argv[3] ?? null;
$entorno = $argv[4] ?? 'central'; // Nuevo parámetro

logDebug("ID Proceso: $idProceso");
logDebug("Desde: $desde");
logDebug("Hasta: $hasta");
logDebug("Entorno: $entorno");

if (!$idProceso || !$desde || !$hasta) {
    logDebug("ERROR: Parámetros insuficientes");
    die("Parámetros insuficientes\n");
}

// Incluir conexión usando la misma ruta que prorratear.php
logDebug("Incluyendo archivo de conexión...");
require_once __DIR__.'/../../class/conexion.php';

try {
    logDebug("Creando conexión a base de datos (entorno: $entorno)...");
    
    $conexion = new Conexion();
    $conn = $conexion->conectar($entorno);
    
    if (!$conn) {
        throw new Exception('No se pudo establecer conexión con la base de datos');
    }
    
    logDebug("Conexión establecida exitosamente");
    
    // Actualizar estado a EN_PROCESO
    logDebug("Actualizando estado a EN_PROCESO (10%)...");
    $sql = "EXEC RO_SP_ACTUALIZAR_PROGRESO_PROCESO @ID_PROCESO = ?, @ESTADO = ?, @PROGRESO = ?, @MENSAJE = ?";
    $params = array($idProceso, 'EN_PROCESO', 10, 'Iniciando proceso de prorrateo...');
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $error = print_r(sqlsrv_errors(), true);
        logDebug("ERROR al actualizar progreso inicial: $error");
        throw new Exception('Error al actualizar progreso: ' . $error);
    }
    logDebug("Estado actualizado a EN_PROCESO");
    
    // Actualizar a 30%
    logDebug("Actualizando progreso a 30%...");
    $params = array($idProceso, 'EN_PROCESO', 30, 'Ejecutando prorrateo integral...');
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    // Ejecutar el SP principal (intentar V2 primero, luego original)
    logDebug("Ejecutando RO_SP_PRORRATEAR_INTEGRAL_V2...");
    $sqlV2 = "EXEC RO_SP_PRORRATEAR_INTEGRAL_V2 @DESDE = ?, @HASTA = ?, @ID_PROCESO = ?";
    $paramsV2 = array($desde, $hasta, $idProceso);
    $stmtProrrateo = sqlsrv_query($conn, $sqlV2, $paramsV2);
    
    logDebug("Resultado de ejecución SP V2: " . ($stmtProrrateo === false ? 'ERROR' : 'OK'));
    
    if ($stmtProrrateo === false) {
        $errorV2 = print_r(sqlsrv_errors(), true);
        logDebug("SP V2 falló: $errorV2");
        logDebug("Intentando con SP original...");
        
        // Si V2 no existe, intentar con el SP original
        $sqlOriginal = "EXEC RO_SP_PRORRATEAR_INTEGRAL @DESDE = ?, @HASTA = ?";
        $paramsOriginal = array($desde, $hasta);
        $stmtProrrateo = sqlsrv_query($conn, $sqlOriginal, $paramsOriginal);
        
        if ($stmtProrrateo === false) {
            $errorOriginal = print_r(sqlsrv_errors(), true);
            logDebug("SP original también falló: $errorOriginal");
            throw new Exception('Error al ejecutar prorrateo: ' . $errorOriginal);
        }
        
        logDebug("SP original ejecutado exitosamente");
        // Si usamos el SP original, actualizar progreso manualmente a 100%
        $params = array($idProceso, 'EN_PROCESO', 100, 'Prorrateo completado (SP original)');
        sqlsrv_query($conn, $sql, $params);
    } else {
        logDebug("SP V2 ejecutado exitosamente");
    }
    
    // Marcar como completado
    logDebug("Marcando proceso como COMPLETADO...");
    $sql = "EXEC RO_SP_ACTUALIZAR_PROGRESO_PROCESO @ID_PROCESO = ?, @ESTADO = ?, @PROGRESO = 100, @MENSAJE = ?";
    $params = array($idProceso, 'COMPLETADO', 'Prorrateo completado exitosamente');
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $error = print_r(sqlsrv_errors(), true);
        logDebug("ERROR al marcar como completado: $error");
        error_log("Error al marcar como completado: $error");
    } else {
        logDebug("Proceso marcado como COMPLETADO exitosamente");
    }
    
    logDebug("=== FIN EXITOSO DEL PROCESO ===");
    
} catch (Exception $e) {
    logDebug("=== EXCEPCIÓN CAPTURADA ===");
    logDebug("Mensaje: " . $e->getMessage());
    logDebug("Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
    logDebug("Trace: " . $e->getTraceAsString());
    
    // Marcar como error
    $sql = "EXEC RO_SP_ACTUALIZAR_PROGRESO_PROCESO @ID_PROCESO = ?, @ESTADO = ?, @MENSAJE = ?, @DETALLES_ERROR = ?";
    $params = array($idProceso, 'ERROR', 'Error durante el prorrateo', $e->getMessage());
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $error = print_r(sqlsrv_errors(), true);
        logDebug("ERROR al registrar fallo: $error");
        error_log("Error al registrar fallo del proceso: $error");
    } else {
        logDebug("Error registrado en base de datos");
    }
    
    error_log("Error en prorrateo (ID: $idProceso): " . $e->getMessage());
    logDebug("=== FIN CON ERROR ===");
}
