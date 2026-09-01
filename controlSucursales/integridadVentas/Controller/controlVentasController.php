<?php
header('Content-Type: application/json');

// Ajustar ruta para apuntar a la clase conexion desde integridadVentas/Controller/
require_once __DIR__ . '/../../../class/conexion.php';
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

            // --- INICIO DE LA OPTIMIZACIÓN DE CACHÉ ---
            $cache_lifetime_minutes = 30; // Tiempo de vida del caché en minutos. Puedes ajustarlo.
            $procesar_datos = true; // Asumimos que debemos procesar por defecto.

            $db_alias_procesamiento = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
            $conexion_cache_check = $conn->conectar($db_alias_procesamiento);
            if (!$conexion_cache_check) throw new Exception('No se pudo conectar a la base de datos para verificar el caché.');

            $sql_cache_check = "SELECT TOP 1 REFRESHED_AT FROM dbo.RO_T_COMPARA_VENTAS WHERE DESDE = ? AND HASTA = ? ORDER BY REFRESHED_AT DESC";
            $params_cache_check = [$desde, $hasta];
            $stmt_cache_check = sqlsrv_query($conexion_cache_check, $sql_cache_check, $params_cache_check);
            
            if ($stmt_cache_check && $row_cache = sqlsrv_fetch_array($stmt_cache_check, SQLSRV_FETCH_ASSOC)) {
                if ($row_cache['REFRESHED_AT']) {
                    $last_refresh = $row_cache['REFRESHED_AT'];
                    $cache_expiry_time = new DateTime("-{$cache_lifetime_minutes} minutes");
                    if ($last_refresh > $cache_expiry_time) {
                        // Los datos son recientes, no es necesario volver a procesar.
                        $procesar_datos = false;
                    }
                }
            }
            sqlsrv_close($conexion_cache_check);

            if ($procesar_datos) {
                // Si la caché expiró o no existe, ejecutamos el proceso pesado.
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

                $conexion_procesamiento = $conn->conectar($db_alias_procesamiento);
                if (!$conexion_procesamiento) throw new Exception('No se pudo conectar a la base de datos de procesamiento.');

                foreach ($lista_sucursales as $nroSucursal) {
                    $sql_individual = "EXEC dbo.RO_SP_COMPARAR_VENTAS_SUCURSAL @NRO_SUCURSAL = ?, @DESDE = ?, @HASTA = ?";
                    $params_individual = [$nroSucursal, $desde, $hasta];
                    $stmt_individual = sqlsrv_query($conexion_procesamiento, $sql_individual, $params_individual);
                    if ($stmt_individual === false) error_log("Error al procesar sucursal $nroSucursal: " . print_r(sqlsrv_errors(), true));
                }
                sqlsrv_close($conexion_procesamiento);
            }
            // --- FIN DE LA OPTIMIZACIÓN DE CACHÉ ---

            $response = ['success' => true, 'message' => 'Proceso masivo verificado y listo.'];
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

            $sql_resultados = "
                SELECT 
                    NRO_SUCURS, 
                    IMPORTE_CENTRAL, 
                    IMPORTE_LOCAL, 
                    ISNULL(DIFERENCIA, ISNULL(IMPORTE_CENTRAL, 0) - ISNULL(IMPORTE_LOCAL, 0)) AS DIFERENCIA,
                    ESTADO, 
                    REFRESHED_AT 
                FROM dbo.RO_T_COMPARA_VENTAS 
                WHERE DESDE = ? AND HASTA = ?
                ORDER BY NRO_SUCURS
            ";

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

        case 'obtener_detalle_ventas':
            $desde      = $_POST['desde']      ?? '';
            $hasta      = $_POST['hasta']      ?? '';
            $nro_sucurs = $_POST['nro_sucurs'] ?? '';
            if (empty($desde) || empty($hasta) || empty($nro_sucurs))
                throw new Exception('Todos los parámetros son obligatorios.');

            $db_alias = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
            $conexion = $conn->conectar($db_alias);
            if (!$conexion) throw new Exception('No se pudo conectar a la base de datos.');

            $sql_detalle   = "EXEC dbo.RO_SP_DETALLE_VENTAS_SUCURSAL @NRO_SUCURSAL = ?, @DESDE = ?, @HASTA = ?";
            $params_detalle = [$nro_sucurs, $desde, $hasta];
            $stmt_detalle   = sqlsrv_query($conexion, $sql_detalle, $params_detalle);
            if ($stmt_detalle === false) {
                $err = sqlsrv_errors();
                throw new Exception('Error al ejecutar el SP de detalle: ' . ($err[0]['message'] ?? 'desconocido'));
            }

            $detalle = [];
            while ($row = sqlsrv_fetch_array($stmt_detalle, SQLSRV_FETCH_ASSOC)) {
                $fecha = $row['FECHA_EMIS'];
                if ($fecha instanceof DateTime) {
                    $fecha = $fecha->format('Y-m-d');
                } elseif (is_array($fecha) && isset($fecha['date'])) {
                    $fecha = (new DateTime($fecha['date']))->format('Y-m-d');
                }
                $detalle[] = [
                    'T_COMP'          => $row['T_COMP'],
                    'FECHA_EMIS'      => $fecha,
                    'CANT_CENTRAL'    => (int)$row['CANT_CENTRAL'],
                    'IMPORTE_CENTRAL' => (float)$row['IMPORTE_CENTRAL'],
                    'CANT_LOCAL'      => (int)$row['CANT_LOCAL'],
                    'IMPORTE_LOCAL'   => (float)$row['IMPORTE_LOCAL'],
                    'DIFERENCIA'      => (float)$row['DIFERENCIA'],
                    'ESTADO'          => $row['ESTADO'],
                ];
            }
            sqlsrv_close($conexion);

            $response = ['success' => true, 'data' => $detalle];
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
