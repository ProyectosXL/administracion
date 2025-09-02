<?php
header('Content-Type: application/json');

require_once '../../class/conexion.php';

$response = ['success' => false, 'message' => 'Acción no válida.'];
$action = $_POST['action'] ?? '';

if (empty($action)) {
    echo json_encode($response);
    exit;
}

$conn = new Conexion();
$conexion = $conn->conectar('locales');

$debug_info = [];
if ($conexion) {
    $test_stmt = sqlsrv_query($conexion, "SELECT DB_NAME() AS db_name");
    if ($test_stmt && $row = sqlsrv_fetch_array($test_stmt, SQLSRV_FETCH_ASSOC)) {
        $debug_info['database'] = $row['db_name'];
    }
}

if (!$conexion) {
    $response['message'] = 'Error de conexión a la base de datos.';
    $response['debug_info'] = $debug_info;
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'ejecutar_masivo':
            set_time_limit(300);
            $desde = $_POST['desde'] ?? '';
            $hasta = $_POST['hasta'] ?? '';

            if (empty($desde) || empty($hasta)) throw new Exception('Las fechas son obligatorias.');

            sqlsrv_configure("WarningsReturnAsErrors", 0);

            $sql = "EXEC RO_SP_COMPARAR_VENTAS_MASIVO @desde = ?, @hasta = ?";
            $debug_info['sql'] = "EXEC RO_SP_COMPARAR_VENTAS_MASIVO @desde = '" . $desde . "', @hasta = '" . $hasta . "'";
            $params = [$desde, $hasta];
            $stmt = sqlsrv_query($conexion, $sql, $params);

            if ($stmt === false) throw new Exception('Error al ejecutar el proceso masivo: ' . print_r(sqlsrv_errors(), true));
            
            // Liberar resultados del SP, si los hubiera
            do {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    // No hacer nada, solo consumir el result set
                }
            } while (sqlsrv_next_result($stmt));

            $response = ['success' => true, 'message' => 'Proceso masivo ejecutado correctamente.', 'debug_info' => $debug_info];
            break;

        case 'ejecutar_sucursal':
            $desde = $_POST['desde'] ?? '';
            $hasta = $_POST['hasta'] ?? '';
            $nro_sucurs = $_POST['nro_sucurs'] ?? '';

            if (empty($desde) || empty($hasta) || empty($nro_sucurs)) throw new Exception('Todos los parámetros son obligatorios.');

            $sql = "EXEC RO_SP_COMPARAR_VENTAS_SUCURSAL @desde = ?, @hasta = ?, @NRO_SUCURSAL = ?";
            $debug_info['sql'] = "EXEC RO_SP_COMPARAR_VENTAS_SUCURSAL @desde = '" . $desde . "', @hasta = '" . $hasta . "', @NRO_SUCURSAL = " . $nro_sucurs;
            $params = [$desde, $hasta, $nro_sucurs];
            $stmt = sqlsrv_query($conexion, $sql, $params);

            if ($stmt === false) throw new Exception('Error al ejecutar el proceso para la sucursal: ' . print_r(sqlsrv_errors(), true));

            // Leemos el resultado que devuelve el SP para asegurar su correcta ejecución y para depurar.
            $sp_return_data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $sp_return_data[] = $row;
            }
            $debug_info['sp_return'] = $sp_return_data;

            $response = ['success' => true, 'message' => 'Proceso para sucursal ' . $nro_sucurs . ' ejecutado.', 'debug_info' => $debug_info];
            break;

        case 'obtener_datos':
            $desde = $_POST['desde'] ?? '';
            $hasta = $_POST['hasta'] ?? '';

            if (empty($desde) || empty($hasta)) throw new Exception('Las fechas son obligatorias.');

            $query = "
                SELECT 
                    NRO_SUCURS AS NUM_SUC, SUC.COD_CLIENT AS COD_SUCURSAL, IMPORTE_CENTRAL, 
                    IMPORTE_LOCAL, DIFERENCIA, ESTADO,
                    DATEADD(HOUR, -3, REFRESHED_AT) AS REFRESHED_AT
                FROM dbo.RO_T_COMPARA_VENTAS
                LEFT JOIN SUCURSALES_LAKERS AS SUC ON SUC.NRO_SUCURSAL = RO_T_COMPARA_VENTAS.NRO_SUCURS
                WHERE DESDE = ? AND HASTA = ?
                AND NRO_SUCURS IN (SELECT NRO_SUCURSAL FROM dbo.SUCURSALES_LAKERS WHERE CANAL = 'PROPIOS' AND HABILITADO = 1)
                ORDER BY NRO_SUCURS;
            ";
            $debug_info['sql'] = preg_replace('/\s+/', ' ', $query);
            $params = [$desde, $hasta];
            $stmt = sqlsrv_query($conexion, $query, $params);

            if ($stmt === false) throw new Exception('Error al obtener los datos: ' . print_r(sqlsrv_errors(), true));

            $data = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = $row;
            }

            $response = ['success' => true, 'data' => $data, 'debug_info' => $debug_info];
            break;

        default:
            $response['message'] = 'Acción desconocida.';
            break;
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['debug_info'] = $debug_info;
} finally {
    if ($conexion) {
        sqlsrv_close($conexion);
    }
}

echo json_encode($response);

?>