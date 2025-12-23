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
    // Validar datos
    if (!isset($_POST['id_pago']) || !isset($_POST['fecha_pago'])) {
        throw new Exception('Datos incompletos');
    }

    $idPago = intval($_POST['id_pago']);
    $fechaPago = $_POST['fecha_pago'];
    
    // Conectar a BD
    $cid = new Conexion();
    $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conexion = $cid->conectar($db);
    
    // Convertir fecha DD/MM/YYYY a YYYY-MM-DD HH:MM:SS
    $fechaParts = explode('/', $fechaPago);
    if (count($fechaParts) !== 3) {
        throw new Exception('Formato de fecha inválido');
    }
    $fechaSQL = $fechaParts[2] . '-' . $fechaParts[1] . '-' . $fechaParts[0] . ' 00:00:00';
    
    // Actualizar fecha
    $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO_PAGOS 
            SET FECHA_PAGO = '" . $fechaSQL . "'
            WHERE ID = " . $idPago;
    
    error_log("SQL: " . $sql);
    
    $stmt = sqlsrv_query($conexion, $sql);
    
    if ($stmt === false) {
        throw new Exception("Error en la actualización: " . print_r(sqlsrv_errors(), true));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Fecha actualizada correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en actualizarFechaPagoController: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
