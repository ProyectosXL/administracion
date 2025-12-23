<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../class/conexion.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    // Verificar que viene ID del despacho
    if (!isset($_POST['id_despacho']) || empty($_POST['id_despacho'])) {
        throw new Exception('ID de despacho no proporcionado');
    }

    $idDespacho = intval($_POST['id_despacho']);
    error_log("Buscando pagos para despacho: " . $idDespacho);
    
    // Conectar a BD
    $cid = new Conexion();
    $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conexion = $cid->conectar($db);
    
    // Obtener pagos del despacho
    $sql = "SELECT 
        ID,
        ID_ENCABEZADO,
        FECHA_PAGO,
        FORMA_PAGO,
        MEDIO_PAGO,
        MONTO
    FROM RO_T_IMPORTACIONES_ENCABEZADO_PAGOS
    WHERE ID_ENCABEZADO = " . $idDespacho . "
    ORDER BY FECHA_PAGO DESC";
    
    error_log("SQL: " . $sql);
    
    $stmt = sqlsrv_query($conexion, $sql);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        error_log("Error SQL: " . print_r($errors, true));
        throw new Exception("Error en la consulta: " . print_r($errors, true));
    }
    
    $pagos = [];
    $totalMonto = 0;
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Convertir objeto DateTime a string formato DD/MM/YYYY
        if (isset($row['FECHA_PAGO'])) {
            if (is_object($row['FECHA_PAGO'])) {
                // Si es objeto DateTime
                $row['FECHA_PAGO'] = $row['FECHA_PAGO']->format('d/m/Y');
            } else if (is_string($row['FECHA_PAGO'])) {
                // Si ya es string, intentar convertir
                $fechaObj = new DateTime($row['FECHA_PAGO']);
                $row['FECHA_PAGO'] = $fechaObj->format('d/m/Y');
            }
        }
        $pagos[] = $row;
        $totalMonto += floatval($row['MONTO']);
    }
    
    error_log("Pagos encontrados: " . count($pagos));
    
    // Obtener FOB para calcular saldo pendiente
    $sqlFob = "SELECT VALOR_FOB_PESO FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = " . $idDespacho;
    $stmtFob = sqlsrv_query($conexion, $sqlFob);
    
    $valorFob = 0;
    if ($stmtFob && $row = sqlsrv_fetch_array($stmtFob, SQLSRV_FETCH_ASSOC)) {
        $valorFob = floatval($row['VALOR_FOB_PESO']);
    }
    
    $saldoPendiente = $valorFob - $totalMonto;
    
    echo json_encode([
        'success' => true,
        'pagos' => $pagos,
        'totalPagado' => $totalMonto,
        'saldoPendiente' => $saldoPendiente
    ]);
    
} catch (Exception $e) {
    error_log("Error en traerPagosController: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
