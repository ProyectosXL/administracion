<?php
// PRIMERO: Verificamos si la acción es crear una propuesta.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'crear_propuesta') {
    session_start(); 
    header('Content-Type: application/json');
    require_once '../config/database.php';

    $comprobantes = $_POST['comprobantes'] ?? [];
    $total_propuesto = $_POST['total_propuesto'] ?? 0;
    $fecha_propuesta_pago = $_POST['fecha_propuesta_pago'] ?? null;
    $medio_de_pago = $_POST['medio_de_pago'] ?? null;
    $id_usuario_admin = $_SESSION['usuario_id'] ?? null;
    $cod_cliente = $_POST['cod_cliente'] ?? null; // <-- AÑADE ESTA LÍNEA

    // La validación correcta
if (empty($cod_cliente) || empty($comprobantes) || !isset($total_propuesto) || empty($fecha_propuesta_pago) || empty($medio_de_pago) || empty($id_usuario_admin)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos para crear la propuesta.']);
    exit;
}
    
    $conn_apps = Database::getConnection('apps');
    if (sqlsrv_begin_transaction($conn_apps) === false) { /* ... */ }

    try {
        $sql_propuesta = "INSERT INTO FP_propuestas_pago (cod_cliente, id_usuario_admin, estado, total_propuesto, fecha_propuesta_pago, medio_de_pago) OUTPUT INSERTED.id VALUES (?, ?, ?, ?, ?, ?)";
        $params_propuesta = [$cod_cliente, $id_usuario_admin, 'PENDIENTE_APROBACION_CLIENTE', $total_propuesto, $fecha_propuesta_pago, $medio_de_pago];
        $stmt_propuesta = sqlsrv_query($conn_apps, $sql_propuesta, $params_propuesta);
        
        $row_id = sqlsrv_fetch_array($stmt_propuesta, SQLSRV_FETCH_ASSOC);
        $id_propuesta = $row_id['id'];

        if (!$id_propuesta) throw new Exception("No se pudo crear la cabecera de la propuesta.");

        $sql_item = "INSERT INTO FP_propuestas_pago_items (id_propuesta, t_comp_factura, n_comp_factura, importe_bruto, importe_neto, porcentaje_descuento) VALUES (?, ?, ?, ?, ?, ?)";
        foreach ($comprobantes as $comp) {
            $params_item = [$id_propuesta, $comp['t_comp'], $comp['n_comp'], $comp['importe_bruto'], $comp['importe_neto'], $comp['porcentaje_descuento']];
            $stmt_item = sqlsrv_query($conn_apps, $sql_item, $params_item);
            if ($stmt_item === false) throw new Exception("Error al insertar item: " . $comp['n_comp']);
        }

        $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, id_usuario_evento, tipo_usuario, descripcion) VALUES (?, ?, ?, ?)";
        $desc_historial = "Propuesta de pago creada por el administrador.";
        $params_historial = [$id_propuesta, $id_usuario_admin, 'ADMIN', $desc_historial];
        $stmt_historial = sqlsrv_query($conn_apps, $sql_historial, $params_historial);
        if ($stmt_historial === false) throw new Exception("Error al registrar historial.");

        sqlsrv_commit($conn_apps);
        echo json_encode(['success' => true, 'message' => 'Propuesta de pago enviada correctamente.']);

    } catch (Exception $e) {
        sqlsrv_rollback($conn_apps);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// LÓGICA PARA LISTAR COBRANZAS (Esta parte no necesita cambios)
// LÓGICA PARA LISTAR COBRANZAS (CORREGIDA)
header('Content-Type: application/json');
require_once '../config/database.php';

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$cod_cliente = isset($_GET['cod_client']) ? trim($_GET['cod_client']) : null; // Añadimos trim() por seguridad
$vista = '';

if ($tipo === 'franquicias') {
    $vista = 'RO_V_COBRANZA_PEND_FRANQUICIAS';
} elseif ($tipo === 'mayoristas') {
    $vista = 'RO_V_COBRANZA_PEND_MAYORISTAS';
} else {
    echo json_encode(['error' => 'Tipo no válido']);
    exit;
}

try {
    $conn_central = Database::getConnection('central');
    $conn_apps = Database::getConnection('apps');

    $tableData = [];
    $summary = [
        'totalNeto' => 0,
        'totalComprobantes' => 0,
        'totalClientes' => 0
    ];

if ($cod_cliente) {
    // --- VISTA DE DETALLE ---

    // 1. Obtener la lista de N_COMP de las propuestas
    $facturas_en_propuesta = [];
    $sql_propuestas = "
        SELECT 
            items.n_comp_factura
        FROM 
            FP_propuestas_pago_items items
        JOIN 
            FP_propuestas_pago propuestas ON items.id_propuesta = propuestas.id
        WHERE 
            propuestas.cod_cliente = ?
    ";
    $params_propuestas = [$cod_cliente];
    $stmt_propuestas = sqlsrv_query($conn_apps, $sql_propuestas, $params_propuestas);
    if ($stmt_propuestas === false) {
        throw new Exception("Error al consultar propuestas activas: ".print_r(sqlsrv_errors(), true));
    }
    while ($row = sqlsrv_fetch_array($stmt_propuestas, SQLSRV_FETCH_ASSOC)) {
        $facturas_en_propuesta[trim($row['n_comp_factura'])] = true;
    }

    // ======================= INICIO DE LA CORRECCIÓN DE COLLATION EN EL JOIN =======================
    // 2. Obtener TODAS las facturas del cliente, forzando la intercalación en el JOIN
    $sql_facturas = "
        SELECT 
            v.COD_CLIENT, v.RAZON_SOCI, v.FECHA_EMIS, v.T_COMP, v.N_COMP, 
            v.ESTADO, v.IMPORTE, v.FECHA_PROB_COBRO, v.PPP, v.IMPORTE_NETO,
            p.MEDIO_PAGO_DEFAULT, p.DIAS_PP_MAX, p.DESC_PP_MAX
        FROM $vista v
        LEFT JOIN RO_T_PARAMETROS_DESC_CLIENTES p ON v.COD_CLIENT = p.COD_CLIENT COLLATE Modern_Spanish_CI_AI
        WHERE v.COD_CLIENT = ?
        ORDER BY v.FECHA_EMIS DESC
    ";
    // ======================== FIN DE LA CORRECCIÓN DE COLLATION EN EL JOIN =========================
    
    $params_facturas = [$cod_cliente];
    $stmt_facturas = sqlsrv_query($conn_central, $sql_facturas, $params_facturas);
    if ($stmt_facturas === false) {
        throw new Exception("Error en la consulta de detalle de facturas: ".print_r(sqlsrv_errors(), true));
    }

    // 3. Filtrar los resultados en PHP
    while ($row_factura = sqlsrv_fetch_array($stmt_facturas, SQLSRV_FETCH_ASSOC)) {
        if (!isset($facturas_en_propuesta[trim($row_factura['N_COMP'])])) {
            $tableData[] = $row_factura;
        }
    }
    
    } else {
        // --- VISTA DE RESUMEN ---
        $sql = "SELECT 
                    COD_CLIENT, RAZON_SOCI, COUNT(*) AS CANT_FACTURAS,
                    SUM(IMPORTE) AS TOTAL_BRUTO, SUM(IMPORTE_NETO) AS TOTAL_NETO
                FROM $vista 
                GROUP BY COD_CLIENT, RAZON_SOCI 
                ORDER BY TOTAL_NETO DESC";
        $stmt = sqlsrv_query($conn_central, $sql);

        if ($stmt === false) {
            throw new Exception("Error en la consulta de resumen: " . print_r(sqlsrv_errors(), true));
        }

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $tableData[] = $row;
        }

        // Calculamos los totales solo para la vista de resumen
        $summary['totalClientes'] = count($tableData);
        foreach ($tableData as $cliente) {
            $summary['totalNeto'] += $cliente['TOTAL_NETO'];
            $summary['totalComprobantes'] += $cliente['CANT_FACTURAS'];
        }
    }

    // Unificamos la respuesta final
    $response = [
        'summary' => $summary,
        'data' => $tableData
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>