<?php
/**
 * Controlador de exportación de ventas para locatarios IRSA
 * Genera archivo TXT con datos de ventas filtrados por fecha y sucursal
 */

// Incluir clase de conexión
require_once(__DIR__ . '/../../../../class/conexion.php');

// Validar parámetros GET
if (!isset($_GET['desde']) || !isset($_GET['hasta']) || !isset($_GET['suc'])) {
    http_response_code(400);
    die('Error: Faltan parámetros requeridos (desde, hasta, suc)');
}

$desde = $_GET['desde'];
$hasta = $_GET['hasta'];
$suc = $_GET['suc'];

// Validar formato de fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    http_response_code(400);
    die('Error: Formato de fecha inválido. Use YYYY-MM-DD');
}

// Validar sucursal (solo números)
if (!is_numeric($suc)) {
    http_response_code(400);
    die('Error: ID de sucursal inválido');
}

// Generar nombre del archivo
$fechaHora = date("Ymd_His");
$nombre = "locatarios_irsa_suc{$suc}_{$fechaHora}.txt";

// Configurar headers para descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $nombre);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Abrir output stream
$output = fopen('php://output', 'w');

try {
    // Crear instancia de conexión
    $conexion = new Conexion();
    $cid = $conexion->conectar('locales');
    
    if (!$cid) {
        throw new Exception('Error al conectar con la base de datos locales');
    }
    
    // Preparar la consulta SQL
    $sql = "
        SET DATEFORMAT YMD
        EXEC SJ_LOCATARIOS_IRSA ?, ?, ?
    ";
    
    // Preparar parámetros
    $params = array($desde, $hasta, $suc);
    
    // Ejecutar consulta
    $query = sqlsrv_query($cid, $sql, $params);
    
    if ($query === false) {
        throw new Exception('Error al ejecutar la consulta: ' . print_r(sqlsrv_errors(), true));
    }
    
    // Escribir datos al archivo
    $rowCount = 0;
    $first = true;
    while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
        if (!$first) {
            echo "\r\n";
        }
        // Limpiar valores nulos y asegurarse de que el array esté completo
        $line = array_map(function($value) {
            return $value === null ? '' : $value;
        }, $row);
        echo implode(';', $line);
        $rowCount++;
        $first = false;
    }
    
    // Liberar recursos
    sqlsrv_free_stmt($query);
    sqlsrv_close($cid);
    
    // Log de éxito (opcional)
    error_log("Exportación exitosa: {$rowCount} registros para sucursal {$suc} ({$desde} - {$hasta})");
    
} catch (Exception $e) {
    // Manejar errores
    http_response_code(500);
    error_log("Error en exportación de locatarios: " . $e->getMessage());
    die('Error al generar el archivo de exportación');
}

exit;