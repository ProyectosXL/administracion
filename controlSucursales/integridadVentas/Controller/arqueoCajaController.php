<?php
header('Content-Type: application/json');

require_once '../../../class/conexion.php';
session_start();

$response = ['success' => false, 'message' => 'Acción no válida.'];
$action = $_POST['action'] ?? '';

if (empty($action)) {
    echo json_encode($response);
    exit;
}

$conn = new Conexion();

try {
    switch ($action) {
        case 'obtener_datos':
            $fecha = $_POST['fecha'] ?? '';
            if (empty($fecha)) throw new Exception('La fecha es obligatoria.');

            $db_alias = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
            $conexion = $conn->conectar($db_alias);
            if (!$conexion) throw new Exception('No se pudo conectar a la base de datos.');

            $sql = "
                SELECT
                    FECHA,
                    NRO_SUCURSAL,
                    DESC_SUCURSAL,
                    COD_CTA_CUENTA_TESORERIA,
                    SALDO_CAJA,
                    SALDO_CIERRE,
                    DIFERENCIA
                FROM dbo.RO_V_AUDITORIA_CAJA_SUCURSALES
                WHERE CAST(FECHA AS date) = ?
                ORDER BY NRO_SUCURSAL
            ";
            $params = [$fecha];
            $stmt = sqlsrv_query($conexion, $sql, $params);
            if ($stmt === false) throw new Exception('Error al consultar la vista de auditoría de caja.');

            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = [
                    'FECHA'                    => ($row['FECHA'] instanceof DateTime) ? $row['FECHA']->format('Y-m-d') : (is_string($row['FECHA']) ? substr($row['FECHA'], 0, 10) : null),
                    'NRO_SUCURSAL'             => $row['NRO_SUCURSAL'],
                    'DESC_SUCURSAL'            => $row['DESC_SUCURSAL'],
                    'COD_CTA_CUENTA_TESORERIA' => $row['COD_CTA_CUENTA_TESORERIA'],
                    'SALDO_CAJA'               => $row['SALDO_CAJA'] !== null ? floatval($row['SALDO_CAJA']) : null,
                    'SALDO_CIERRE'             => $row['SALDO_CIERRE'] !== null ? floatval($row['SALDO_CIERRE']) : null,
                    'DIFERENCIA'               => $row['DIFERENCIA'] !== null ? floatval($row['DIFERENCIA']) : null,
                ];
            }
            sqlsrv_close($conexion);

            $response = ['success' => true, 'data' => $data];
            break;

        default:
            $response['message'] = 'Acción no válida.';
            break;
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
