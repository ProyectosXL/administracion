<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método no permitido');
    }
    
    if (!isset($_GET['id_mg'])) {
        throw new Exception('ID de despacho no proporcionado');
    }
    
    $idMg = intval($_GET['id_mg']);
    
    require_once '../class/estimacionCostos.php';
    
    $estimacion = new EstimacionCostos();
    
    // Obtener despacho con campo DESPACHANTE
    $despacho = $estimacion->obtenerDespacho($idMg);
    
    if (!$despacho) {
        throw new Exception('Despacho no encontrado');
    }
    
    // Obtener concepto DESPACHANTE de la BD
    require_once '../class/conexion.php';
    $cid = new Conexion();
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
    $conexion = $cid->conectar($db);
    
    $sql = "SELECT 
                ID_CE,
                VALOR_DEFAULT_1,
                VALOR_DEFAULT_2
            FROM RO_T_CONCEPTOS_ESTIMACION_COMEX
            WHERE CONCEPTO = 'DESPACHANTE'";
    
    $stmt = sqlsrv_query($conexion, $sql);
    
    if ($stmt === false) {
        throw new Exception('Error al obtener concepto DESPACHANTE');
    }
    
    $concepto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if (!$concepto) {
        throw new Exception('Concepto DESPACHANTE no encontrado en la base de datos');
    }
    
    // Determinar parámetros según despachante del despacho
    $despachante = $despacho['DESPACHANTE'];
    $parametro1 = null;
    $parametro2 = null;
    
    if ($despachante === 'Farre') {
        // Farre usa VALOR_DEFAULT_2
        $parametro1 = $concepto['VALOR_DEFAULT_2'];
        $parametro2 = null;
    } else {
        // Laffitte o cualquier otro caso usa VALOR_DEFAULT_1
        $parametro1 = $concepto['VALOR_DEFAULT_1'];
        $parametro2 = null;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'despachante' => $despachante,
            'id_ce' => $concepto['ID_CE'],
            'parametro1' => $parametro1,
            'parametro2' => $parametro2,
            'valor_default_1_laffitte' => $concepto['VALOR_DEFAULT_1'],
            'valor_default_2_farre' => $concepto['VALOR_DEFAULT_2']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error en obtenerParametrosDespachante.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
