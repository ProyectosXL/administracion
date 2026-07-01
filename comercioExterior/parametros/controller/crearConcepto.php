<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['concepto']) || !isset($data['tipo_valor'])) {
        throw new Exception('Datos incompletos');
    }

    require_once '../../../class/conexion.php';
    $cid = new Conexion();
    
    $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conn = $cid->conectar($db);

    $concepto = trim($data['concepto']);
    $tipoValor = trim($data['tipo_valor']);
    $v1 = isset($data['valor_default_1']) && $data['valor_default_1'] !== '' ? floatval($data['valor_default_1']) : null;
    $v2 = isset($data['valor_default_2']) && $data['valor_default_2'] !== '' ? floatval($data['valor_default_2']) : null;

    $idRefConcepto = isset($data['id_ref_concepto']) && $data['id_ref_concepto'] !== '' && $data['id_ref_concepto'] !== null ? intval($data['id_ref_concepto']) : null;

    if ($db === 'uy') {
        $moneda = isset($data['moneda']) ? trim($data['moneda']) : 'USD';
        $tc = isset($data['tipo_cambio']) ? floatval($data['tipo_cambio']) : 1.0;
        $v1Uyu = isset($data['valor_default_1_uyu']) && $data['valor_default_1_uyu'] !== null ? floatval($data['valor_default_1_uyu']) : null;
        $v2Uyu = isset($data['valor_default_2_uyu']) && $data['valor_default_2_uyu'] !== null ? floatval($data['valor_default_2_uyu']) : null;

        $sql = "INSERT INTO RO_T_CONCEPTOS_ESTIMACION_COMEX 
                (CONCEPTO, TIPO_VALOR, VALOR_DEFAULT_1, VALOR_DEFAULT_2, MONEDA, TIPO_CAMBIO, VALOR_DEFAULT_1_UYU, VALOR_DEFAULT_2_UYU, ID_REF_CONCEPTO, ULT_ACTUA) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";
        $params = array($concepto, $tipoValor, $v1, $v2, $moneda, $tc, $v1Uyu, $v2Uyu, $idRefConcepto);
    } else {
        $sql = "INSERT INTO RO_T_CONCEPTOS_ESTIMACION_COMEX 
                (CONCEPTO, TIPO_VALOR, VALOR_DEFAULT_1, VALOR_DEFAULT_2, ULT_ACTUA) 
                VALUES (?, ?, ?, ?, GETDATE())";
        $params = array($concepto, $tipoValor, $v1, $v2);
    }

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        throw new Exception('Error al insertar concepto: ' . print_r(sqlsrv_errors(), true));
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
