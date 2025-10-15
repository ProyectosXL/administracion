<?php
header('Content-Type: application/json');

require_once '../../class/conexion.php';
session_start();

$response = ['success' => false, 'message' => 'Acción no válida.'];
$action = $_POST['action'] ?? '';

if (empty($action)) {
    echo json_encode($response);
    exit;
}

$conn = new Conexion();
$debug_info = [];

try {
    switch ($action) {
        case 'ejecutar_masivo':
            set_time_limit(300);
            $desde = $_POST['desde'] ?? '';
            $hasta = $_POST['hasta'] ?? '';
            if (empty($desde) || empty($hasta)) throw new Exception('Las fechas son obligatorias.');

            $conexion_maestra = $conn->conectar('locales');
            if (!$conexion_maestra) throw new Exception('No se pudo conectar a la base de datos maestra.');

            $condicion_canal = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy')
                ? "CANAL = 'EXTERIOR' AND HABILITADO = 1"
                : "CANAL = 'PROPIOS' AND HABILITADO = 1";

            $sql_sucursales = "SELECT NRO_SUCURSAL FROM dbo.SUCURSALES_LAKERS WHERE {$condicion_canal}";
            $stmt_sucursales = sqlsrv_query($conexion_maestra, $sql_sucursales);
            if ($stmt_sucursales === false) throw new Exception('Error al obtener la lista de sucursales maestra.');
            
            $lista_sucursales = [];
            while ($row = sqlsrv_fetch_array($stmt_sucursales, SQLSRV_FETCH_ASSOC)) {
                $lista_sucursales[] = $row['NRO_SUCURSAL'];
            }
            sqlsrv_close($conexion_maestra);

            $db_alias_procesamiento = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
            $conexion_procesamiento = $conn->conectar($db_alias_procesamiento);
            if (!$conexion_procesamiento) throw new Exception('No se pudo conectar a la base de datos de procesamiento.');

            foreach ($lista_sucursales as $nroSucursal) {
                $sql_individual = "EXEC dbo.RO_SP_COMPARAR_VENTAS_SUCURSAL @NRO_SUCURSAL = ?, @DESDE = ?, @HASTA = ?";
                $params_individual = [$nroSucursal, $desde, $hasta];
                $stmt_individual = sqlsrv_query($conexion_procesamiento, $sql_individual, $params_individual);
                if ($stmt_individual === false) error_log("Error al procesar sucursal $nroSucursal: " . print_r(sqlsrv_errors(), true));
            }
            sqlsrv_close($conexion_procesamiento);

            $response = ['success' => true, 'message' => 'Proceso masivo ejecutado correctamente.'];
            break;

        case 'ejecutar_sucursal':
            $desde = $_POST['desde'] ?? '';
            $hasta = $_POST['hasta'] ?? '';
            $nro_sucurs = $_POST['nro_sucurs'] ?? '';
            if (empty($desde) || empty($hasta) || empty($nro_sucurs)) throw new Exception('Todos los parámetros son obligatorios.');

            $db_alias_procesamiento = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
            $conexion_procesamiento = $conn->conectar($db_alias_procesamiento);
            if (!$conexion_procesamiento) throw new Exception('No se pudo conectar a la base de datos de procesamiento.');

            $sql = "EXEC dbo.RO_SP_COMPARAR_VENTAS_SUCURSAL @NRO_SUCURSAL = ?, @DESDE = ?, @HASTA = ?";
            $params = [$nro_sucurs, $desde, $hasta];
            $stmt = sqlsrv_query($conexion_procesamiento, $sql, $params);
            if ($stmt === false) throw new Exception('Error al ejecutar el proceso para la sucursal.');
            sqlsrv_close($conexion_procesamiento);

            $response = ['success' => true, 'message' => 'Proceso para sucursal ' . $nro_sucurs . ' ejecutado.'];
            break;

        case 'obtener_datos':
            $desde = $_POST['desde'] ?? '';
            $hasta = $_POST['hasta'] ?? '';
            if (empty($desde) || empty($hasta)) throw new Exception('Las fechas son obligatorias.');

            $conexion_maestra = $conn->conectar('locales');
            if (!$conexion_maestra) throw new Exception('No se pudo conectar a la base de datos maestra.');

            $condicion_canal = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy')
                ? "CANAL = 'EXTERIOR' AND HABILITADO = 1"
                : "CANAL = 'PROPIOS' AND HABILITADO = 1";
            
            $sql_maestra = "SELECT NRO_SUCURSAL, COD_CLIENT FROM dbo.SUCURSALES_LAKERS WHERE {$condicion_canal}";
            $stmt_maestra = sqlsrv_query($conexion_maestra, $sql_maestra);
            if ($stmt_maestra === false) throw new Exception('Error al obtener datos de la tabla maestra.');

            $sucursales_maestra = [];
            while ($row = sqlsrv_fetch_array($stmt_maestra, SQLSRV_FETCH_ASSOC)) {
                $sucursales_maestra[$row['NRO_SUCURSAL']] = $row['COD_CLIENT'];
            }
            sqlsrv_close($conexion_maestra);

            $db_alias_procesamiento = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
            $conexion_procesamiento = $conn->conectar($db_alias_procesamiento);
            if (!$conexion_procesamiento) throw new Exception('No se pudo conectar a la base de datos de procesamiento.');

            // --- INICIO DE LA CORRECCIÓN DE LA DIFERENCIA ---
            $sql_resultados = "
                SELECT 
                    NRO_SUCURS, 
                    IMPORTE_CENTRAL, 
                    IMPORTE_LOCAL, 
                    -- Si la diferencia es NULL, la calculamos aquí mismo
                    ISNULL(DIFERENCIA, ISNULL(IMPORTE_CENTRAL, 0) - ISNULL(IMPORTE_LOCAL, 0)) AS DIFERENCIA,
                    ESTADO, 
                    REFRESHED_AT 
                FROM dbo.RO_T_COMPARA_VENTAS 
                WHERE DESDE = ? AND HASTA = ?
            ";
            // --- FIN DE LA CORRECCIÓN DE LA DIFERENCIA ---

            $params_resultados = [$desde, $hasta];
            $stmt_resultados = sqlsrv_query($conexion_procesamiento, $sql_resultados, $params_resultados);
            if ($stmt_resultados === false) throw new Exception('Error al obtener resultados de ventas.');
            
            $resultados_ventas = [];
            while ($row = sqlsrv_fetch_array($stmt_resultados, SQLSRV_FETCH_ASSOC)) {
                $resultados_ventas[] = $row;
            }
            sqlsrv_close($conexion_procesamiento);

            $data_final = [];
            foreach ($resultados_ventas as $venta) {
                $nro_suc = $venta['NRO_SUCURS'];
                if (isset($sucursales_maestra[$nro_suc])) {
                    $data_final[] = [
                        'NUM_SUC' => $nro_suc,
                        'COD_SUCURSAL' => $sucursales_maestra[$nro_suc],
                        'IMPORTE_CENTRAL' => $venta['IMPORTE_CENTRAL'],
                        'IMPORTE_LOCAL' => $venta['IMPORTE_LOCAL'],
                        'DIFERENCIA' => $venta['DIFERENCIA'],
                        'ESTADO' => $venta['ESTADO'],
                        'REFRESHED_AT' => $venta['REFRESHED_AT']
                    ];
                }
            }

            $response = ['success' => true, 'data' => $data_final, 'debug_info' => $debug_info];
            break;

        default:
            $response['message'] = 'Acción no válida.';
            break;
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['debug_info'] = $debug_info;
}

echo json_encode($response);
?>