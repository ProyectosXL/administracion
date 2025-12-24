<?php
/**
 * Script que se ejecuta en segundo plano para realizar el prorrateo
 * Este archivo es llamado por ProrrateoController.php
 */

// Configurar para ejecución en background
set_time_limit(0); // Sin límite de tiempo
ini_set('memory_limit', '512M'); // Aumentar memoria disponible

// Obtener parámetros de línea de comandos
$idProceso = $argv[1] ?? null;
$desde = $argv[2] ?? null;
$hasta = $argv[3] ?? null;

if (!$idProceso || !$desde || !$hasta) {
    die("Parámetros insuficientes\n");
}

// Incluir conexión usando la misma ruta que prorratear.php
require_once __DIR__.'/../../class/conexion.php';

try {
    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Establecer entorno por defecto si no existe
    if (!isset($_SESSION['entorno'])) {
        $_SESSION['entorno'] = 'central';
    }
    
    $conexion = new Conexion();
    $entorno = $_SESSION['entorno'] ?? 'central';
    $conn = $conexion->conectar($entorno);
    
    // Actualizar estado a EN_PROCESO
    $sql = "EXEC RO_SP_ACTUALIZAR_PROGRESO_PROCESO @ID_PROCESO = ?, @ESTADO = ?, @PROGRESO = ?, @MENSAJE = ?";
    $params = array($idProceso, 'EN_PROCESO', 10, 'Iniciando proceso de prorrateo...');
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        throw new Exception('Error al actualizar progreso: ' . print_r(sqlsrv_errors(), true));
    }
    
    // Actualizar a 30%
    $params = array($idProceso, 'EN_PROCESO', 30, 'Ejecutando prorrateo integral...');
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    // Ejecutar el SP principal (intentar V2 primero, luego original)
    $sqlV2 = "EXEC RO_SP_PRORRATEAR_INTEGRAL_V2 @DESDE = ?, @HASTA = ?, @ID_PROCESO = ?";
    $paramsV2 = array($desde, $hasta, $idProceso);
    $stmtProrrateo = sqlsrv_query($conn, $sqlV2, $paramsV2);
    
    if ($stmtProrrateo === false) {
        // Si V2 no existe, intentar con el SP original
        $sqlOriginal = "EXEC RO_SP_PRORRATEAR_INTEGRAL @DESDE = ?, @HASTA = ?";
        $paramsOriginal = array($desde, $hasta);
        $stmtProrrateo = sqlsrv_query($conn, $sqlOriginal, $paramsOriginal);
        
        if ($stmtProrrateo === false) {
            throw new Exception('Error al ejecutar prorrateo: ' . print_r(sqlsrv_errors(), true));
        }
        
        // Si usamos el SP original, actualizar progreso manualmente a 100%
        $params = array($idProceso, 'EN_PROCESO', 100, 'Prorrateo completado (SP original)');
        sqlsrv_query($conn, $sql, $params);
    }
    
    // Marcar como completado
    $sql = "EXEC RO_SP_ACTUALIZAR_PROGRESO_PROCESO @ID_PROCESO = ?, @ESTADO = ?, @PROGRESO = 100, @MENSAJE = ?";
    $params = array($idProceso, 'COMPLETADO', 'Prorrateo completado exitosamente');
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        error_log("Error al marcar como completado: " . print_r(sqlsrv_errors(), true));
    }
    
} catch (Exception $e) {
    // Marcar como error
    $sql = "EXEC RO_SP_ACTUALIZAR_PROGRESO_PROCESO @ID_PROCESO = ?, @ESTADO = ?, @MENSAJE = ?, @DETALLES_ERROR = ?";
    $params = array($idProceso, 'ERROR', 'Error durante el prorrateo', $e->getMessage());
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        error_log("Error al registrar fallo del proceso: " . print_r(sqlsrv_errors(), true));
    }
    
    error_log("Error en prorrateo (ID: $idProceso): " . $e->getMessage());
}
