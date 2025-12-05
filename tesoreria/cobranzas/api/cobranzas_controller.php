<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$cod_cliente = isset($_GET['cod_client']) ? $_GET['cod_client'] : null;
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
    $db = Database::getInstance();
    $conn = $db->conn;
    $sql = "";
    $params = [];

    if ($cod_cliente) {
        // --- VISTA DE DETALLE: Traer todas las facturas de UN cliente ---
        $sql = "SELECT COD_CLIENT, RAZON_SOCI, FECHA_EMIS, T_COMP, N_COMP, IMPORTE, FECHA_PROB_COBRO, PPP, IMPORTE_NETO FROM $vista WHERE COD_CLIENT = ?";
        $params = [$cod_cliente];
        $stmt = sqlsrv_query($conn, $sql, $params);
    } else {
        // --- VISTA DE RESUMEN: Agrupar por cliente y sumar totales ---
        $sql = "SELECT 
                    COD_CLIENT, 
                    RAZON_SOCI, 
                    COUNT(*) AS CANT_FACTURAS,
                    SUM(IMPORTE) AS TOTAL_BRUTO,
                    SUM(IMPORTE_NETO) AS TOTAL_NETO,
                    MAX(FECHA_PROB_COBRO) AS ULTIMO_VENCIMIENTO
                FROM $vista 
                GROUP BY COD_CLIENT, RAZON_SOCI 
                ORDER BY TOTAL_NETO DESC";
        $stmt = sqlsrv_query($conn, $sql);
    }

    if ($stmt === false) {
        throw new Exception("Error en la consulta SQL: " . print_r(sqlsrv_errors(), true));
    }
    
    // Almacenamos los resultados de la tabla en una variable
    $tableData = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // El formateo de fechas se mantiene igual
        if (isset($row['FECHA_EMIS']) && $row['FECHA_EMIS'] instanceof DateTime) {
            $row['FECHA_EMIS'] = $row['FECHA_EMIS']->format('Y-m-d');
        }
        if (isset($row['FECHA_PROB_COBRO']) && $row['FECHA_PROB_COBRO'] instanceof DateTime) {
            $row['FECHA_PROB_COBRO'] = $row['FECHA_PROB_COBRO']->format('Y-m-d');
        }
        if (isset($row['ULTIMO_VENCIMIENTO']) && $row['ULTIMO_VENCIMIENTO'] instanceof DateTime) {
            $row['ULTIMO_VENCIMIENTO'] = $row['ULTIMO_VENCIMIENTO']->format('Y-m-d');
        }
        $tableData[] = $row;
    }
    sqlsrv_free_stmt($stmt);

    // ===================== NUEVA LÓGICA PARA CALCULAR LOS TOTALES =====================
    $summary = [
        'totalNeto' => 0,
        'totalComprobantes' => 0,
        'totalClientes' => 0
    ];

    // Solo calculamos los totales para la vista de resumen (no para el detalle de un cliente)
    if (!$cod_cliente) { 
        $summary['totalClientes'] = count($tableData);
        foreach ($tableData as $cliente) {
            $summary['totalNeto'] += $cliente['TOTAL_NETO'];
            $summary['totalComprobantes'] += $cliente['CANT_FACTURAS'];
        }
    }
    // ===================== FIN DE LA LÓGICA DE CÁLCULO =====================

    // Creamos la respuesta final que incluye tanto el resumen como los datos de la tabla
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