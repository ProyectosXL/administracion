<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

try {
    require_once '../../../class/conexion.php';

    $cid = new Conexion();
    $db  = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);

    if (!$conn) {
        throw new Exception('No se pudo establecer conexión a la base de datos');
    }

    $sql = "SELECT CLAVE, VALOR, DESCRIPCION, USUARIO, FECHA_MOD
            FROM RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
            ORDER BY CLAVE";

    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        throw new Exception('Error al consultar parámetros: ' . print_r(sqlsrv_errors(), true));
    }

    $parametros = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (isset($row['FECHA_MOD']) && is_object($row['FECHA_MOD'])) {
            $row['FECHA_MOD'] = $row['FECHA_MOD']->format('Y-m-d H:i:s');
        }
        $row['VALOR'] = (int) $row['VALOR'];
        $parametros[] = $row;
    }

    echo json_encode([
        'success' => true,
        'entorno' => $db,
        'data'    => $parametros
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Error en listarParamCronograma.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
