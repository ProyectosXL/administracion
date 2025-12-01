<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    // Verificar que la clase existe
    if (!file_exists('../../../class/conexion.php')) {
        throw new Exception('Archivo de conexión no encontrado');
    }
    
    require_once '../../../class/conexion.php';
    
    if (!class_exists('Conexion')) {
        throw new Exception('Clase Conexion no encontrada');
    }
    
    $cid = new Conexion();
    
    $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);
    
    if (!$conn) {
        throw new Exception('No se pudo establecer conexión a la base de datos');
    }
    
    $sql = "SELECT 
                ID_CE,
                CONCEPTO,
                TIPO_VALOR,
                VALOR_DEFAULT_1,
                VALOR_DEFAULT_2,
                ULT_ACTUA
            FROM RO_T_CONCEPTOS_ESTIMACION_COMEX
            ORDER BY ID_CE";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        throw new Exception('Error al consultar parámetros: ' . print_r(sqlsrv_errors(), true));
    }
    
    $parametros = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Convertir DateTime a string
        if (isset($row['ULT_ACTUA']) && is_object($row['ULT_ACTUA'])) {
            $row['ULT_ACTUA'] = $row['ULT_ACTUA']->format('Y-m-d H:i:s');
        }
        $parametros[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $parametros
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Error en listarParametros.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    error_log('Error fatal en listarParametros.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del sistema: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
