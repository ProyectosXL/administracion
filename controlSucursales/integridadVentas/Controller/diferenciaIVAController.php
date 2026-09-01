<?php
header('Content-Type: application/json');

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

            // Determinar el alias de base de datos según el entorno
            $db_alias = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
            $conexion_procesamiento = $conn->conectar($db_alias);
            if (!$conexion_procesamiento) throw new Exception('No se pudo conectar a la base de datos.');

            // Configurar para ignorar warnings del SP (solo errores reales)
            sqlsrv_configure('WarningsReturnAsErrors', 0);

            // Ejecutar el cursor que procesa todas las sucursales
            $sql_cursor = "EXEC dbo.RO_CURSOR_DIFERENCIA_IVA_VENTAS_PROPIOS @DESDE = ?, @HASTA = ?";
            $params = [$desde, $hasta];
            $stmt = sqlsrv_query($conexion_procesamiento, $sql_cursor, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS); // Solo errores, no warnings
                $error_msg = 'Error al ejecutar el proceso masivo de IVA.';
                if ($errors) {
                    $error_msg .= ' Detalles: ' . $errors[0]['message'];
                }
                sqlsrv_configure('WarningsReturnAsErrors', 1); // Restaurar
                throw new Exception($error_msg);
            }
            
            // Consumir todos los resultados del stored procedure
            do {
                while (sqlsrv_fetch($stmt)) {
                    // Consumir filas
                }
            } while (sqlsrv_next_result($stmt));
            
            sqlsrv_configure('WarningsReturnAsErrors', 1); // Restaurar configuración
            
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conexion_procesamiento);

            $response = ['success' => true, 'message' => 'Proceso masivo de IVA completado.'];
            break;

        case 'ejecutar_sucursal':
            $desde = $_POST['desde'] ?? '';
            $hasta = $_POST['hasta'] ?? '';
            $nro_sucurs = $_POST['nro_sucurs'] ?? '';
            if (empty($desde) || empty($hasta) || empty($nro_sucurs)) throw new Exception('Todos los parámetros son obligatorios.');

            $db_alias = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
            $conexion_procesamiento = $conn->conectar($db_alias);
            if (!$conexion_procesamiento) throw new Exception('No se pudo conectar a la base de datos.');

            // Configurar para ignorar warnings del SP (solo errores reales)
            sqlsrv_configure('WarningsReturnAsErrors', 0);

            $sql = "EXEC dbo.RO_SP_DIFERENCIA_IVA_VENTAS_PROPIOS @LOCAL = ?, @DESDE = ?, @HASTA = ?";
            $params = [$nro_sucurs, $desde, $hasta];
            $stmt = sqlsrv_query($conexion_procesamiento, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS); // Solo errores, no warnings
                $error_msg = 'Error al ejecutar el proceso para la sucursal.';
                if ($errors) {
                    $error_msg .= ' Detalles: ' . $errors[0]['message'];
                }
                sqlsrv_configure('WarningsReturnAsErrors', 1); // Restaurar
                throw new Exception($error_msg);
            }
            
            // Consumir todos los resultados del stored procedure
            do {
                while (sqlsrv_fetch($stmt)) {
                    // Consumir filas
                }
            } while (sqlsrv_next_result($stmt));
            
            sqlsrv_configure('WarningsReturnAsErrors', 1); // Restaurar configuración
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conexion_procesamiento);

            $response = ['success' => true, 'message' => 'Proceso IVA para sucursal ' . $nro_sucurs . ' ejecutado.'];
            break;
            
        case 'obtener_datos':
            $desde = $_POST['desde'] ?? '';
            $hasta = $_POST['hasta'] ?? '';
            // Las fechas son opcionales para obtener_datos, se cargan todos los registros

            // Obtener sucursales maestras de locales (siempre desde central)
            $conexion_maestra = $conn->conectar('locales');
            if (!$conexion_maestra) throw new Exception('No se pudo conectar a la base de datos maestra.');

            $condicion_canal = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy')
                ? "CANAL = 'EXTERIOR' AND HABILITADO = 1"
                : "CANAL = 'PROPIOS' AND HABILITADO = 1";
            
            $sql_maestra = "SELECT NRO_SUCURSAL, COD_CLIENT FROM dbo.SUCURSALES_LAKERS WHERE {$condicion_canal}";
            $stmt_maestra = sqlsrv_query($conexion_maestra, $sql_maestra);
            if ($stmt_maestra === false) {
                $errors = sqlsrv_errors();
                $error_msg = 'Error al obtener datos de la tabla maestra.';
                if ($errors) {
                    $error_msg .= ' Detalles: ' . $errors[0]['message'];
                }
                throw new Exception($error_msg);
            }

            $sucursales_maestra = [];
            while ($row = sqlsrv_fetch_array($stmt_maestra, SQLSRV_FETCH_ASSOC)) {
                $sucursales_maestra[$row['NRO_SUCURSAL']] = $row['COD_CLIENT'];
            }
            sqlsrv_free_stmt($stmt_maestra);
            sqlsrv_close($conexion_maestra);

            // Obtener resultados de IVA
            $db_alias = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
            $conexion_procesamiento = $conn->conectar($db_alias);
            if (!$conexion_procesamiento) throw new Exception('No se pudo conectar a la base de datos de procesamiento.');

            $sql_resultados = "
                SELECT 
                    nro_sucursal,
                    comprobantes_con_dif,
                    importe_local_total,
                    importe_central_total,
                    diferencia_neta,
                    estado_conexion,
                    ejecucion_at
                FROM dbo.RO_T_DIF_IVA_VENTAS_RESUMEN
                WHERE 1=1
            ";

            $stmt_resultados = sqlsrv_query($conexion_procesamiento, $sql_resultados);
            if ($stmt_resultados === false) {
                $errors = sqlsrv_errors();
                $error_msg = 'Error al obtener resultados de IVA.';
                if ($errors) {
                    $error_msg .= ' Detalles: ' . $errors[0]['message'];
                }
                throw new Exception($error_msg);
            }
            
            $resultados_iva = [];
            while ($row = sqlsrv_fetch_array($stmt_resultados, SQLSRV_FETCH_ASSOC)) {
                $resultados_iva[] = $row;
            }
            sqlsrv_free_stmt($stmt_resultados);
            sqlsrv_close($conexion_procesamiento);

            $data_final = [];
            foreach ($resultados_iva as $iva) {
                $nro_suc = $iva['nro_sucursal'];
                if (isset($sucursales_maestra[$nro_suc])) {
                    $data_final[] = [
                        'NUM_SUC' => $nro_suc,
                        'COD_SUCURSAL' => $sucursales_maestra[$nro_suc],
                        'COMPROBANTES_CON_DIF' => $iva['comprobantes_con_dif'],
                        'IMPORTE_LOCAL_TOTAL' => $iva['importe_local_total'],
                        'IMPORTE_CENTRAL_TOTAL' => $iva['importe_central_total'],
                        'DIFERENCIA_NETA' => $iva['diferencia_neta'],
                        'ESTADO_CONEXION' => $iva['estado_conexion'],
                        'EJECUCION_AT' => $iva['ejecucion_at']
                    ];
                }
            }

            $response = ['success' => true, 'data' => $data_final, 'debug_info' => $debug_info];
            break;

        case 'obtener_detalle':
            $nro_sucursal = $_POST['nro_sucursal'] ?? '';
            if (empty($nro_sucursal)) throw new Exception('El número de sucursal es obligatorio.');

            // Conectar a la base de datos de procesamiento
            $db_alias = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy') ? 'suc_uy' : 'locales';
            $conexion_procesamiento = $conn->conectar($db_alias);
            if (!$conexion_procesamiento) throw new Exception('No se pudo conectar a la base de datos.');

            // Consultar los comprobantes con diferencias para la sucursal
            $sql_detalle = "
                SELECT 
                    nro_sucursal,
                    t_comp,
                    n_comp,
                    fecha_emis,
                    importe_local,
                    importe_central,
                    diferencia,
                    tipo_diferencia
                FROM dbo.RO_T_DIF_IVA_VENTAS_DETALLE
                WHERE nro_sucursal = ?
                ORDER BY fecha_emis DESC, t_comp, n_comp
            ";
            
            $params_detalle = [$nro_sucursal];
            $stmt_detalle = sqlsrv_query($conexion_procesamiento, $sql_detalle, $params_detalle);
            
            if ($stmt_detalle === false) {
                $errors = sqlsrv_errors();
                $error_msg = 'Error al obtener el detalle de comprobantes.';
                if ($errors) {
                    $error_msg .= ' Detalles: ' . $errors[0]['message'];
                }
                throw new Exception($error_msg);
            }
            
            $detalle_comprobantes = [];
            while ($row = sqlsrv_fetch_array($stmt_detalle, SQLSRV_FETCH_ASSOC)) {
                $detalle_comprobantes[] = [
                    'NRO_SUCURSAL' => $row['nro_sucursal'],
                    'TIPO_COMPROBANTE' => $row['t_comp'],
                    'NRO_COMPROBANTE' => $row['n_comp'],
                    'FECHA_COMPROBANTE' => $row['fecha_emis'],
                    'IVA_LOCAL' => $row['importe_local'],
                    'IVA_CENTRAL' => $row['importe_central'],
                    'DIFERENCIA' => $row['diferencia'],
                    'TIPO_DIFERENCIA' => $row['tipo_diferencia']
                ];
            }
            
            sqlsrv_free_stmt($stmt_detalle);
            sqlsrv_close($conexion_procesamiento);

            $response = ['success' => true, 'data' => $detalle_comprobantes];
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
