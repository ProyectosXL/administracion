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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['clave']) || !isset($data['valor'])) {
        throw new Exception('Datos incompletos');
    }

    require_once '../../../class/conexion.php';

    $cid  = new Conexion();
    $db   = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);

    if (!$conn) {
        throw new Exception('No se pudo establecer conexión a la base de datos');
    }

    // Whitelist: la clave nunca se interpola ni se acepta libre. Ademas evita
    // que se den de alta claves sueltas que el cronograma no sabe leer.
    $clavesValidas = ['DIAS_EMB_ARR', 'DIAS_ARR_DESP', 'DIAS_DESP_REC', 'DIAS_ARR_DIST'];
    $clave = trim($data['clave']);

    if (!in_array($clave, $clavesValidas, true)) {
        throw new Exception('Parámetro no reconocido: ' . $clave);
    }

    // Son dias corridos de offset: enteros positivos y con un techo sensato,
    // para que un tipeo no proyecte fechas a años de distancia.
    if (!is_numeric($data['valor'])) {
        throw new Exception('El valor debe ser numérico');
    }

    $valor = (int) $data['valor'];
    if ($valor < 0 || $valor > 365) {
        throw new Exception('El valor debe estar entre 0 y 365 días');
    }

    $usuario = isset($_SESSION['usuario_dns']) ? $_SESSION['usuario_dns'] : null;

    $sql = "UPDATE RO_T_IMPORTACIONES_PARAM_CRONOGRAMA
            SET VALOR = ?, USUARIO = ?, FECHA_MOD = GETDATE()
            WHERE CLAVE = ?";

    $stmt = sqlsrv_query($conn, $sql, [$valor, $usuario, $clave]);

    if ($stmt === false) {
        throw new Exception('Error al actualizar: ' . print_r(sqlsrv_errors(), true));
    }

    if (sqlsrv_rows_affected($stmt) === 0) {
        throw new Exception('El parámetro no existe en esta base. ¿Se corrió el script 04?');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Parámetro actualizado correctamente'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Error en actualizarParamCronograma.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
