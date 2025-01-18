
<?php
// Controller/getPeriodoActual.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/Argentina/Buenos_Aires');

header('Content-Type: application/json');

try {
    require_once '../../Class/Anticipo.php';
    $anticipo = new Anticipo();
    
    $sql = "SELECT TOP 1 PERIODO, 
            FORMAT(FECHA_ANTICIPO, 'dd/MM/yyyy') as FECHA_ANTICIPO,
            FORMAT(VIG_DESDE, 'dd/MM/yyyy HH:mm') as VIG_DESDE,
            FORMAT(VIG_HASTA, 'dd/MM/yyyy HH:mm') as VIG_HASTA
            FROM RO_T_FECHA_ANTICIPOS 
            WHERE MONTH(FECHA_ANTICIPO) = MONTH(GETDATE()) 
            AND YEAR(FECHA_ANTICIPO) = YEAR(GETDATE())";
    
    $stmt = sqlsrv_query($anticipo->cid_central, $sql);
    
    if ($stmt === false) {
        error_log("Error SQL: " . print_r(sqlsrv_errors(), true));
        throw new Exception("Error al ejecutar la consulta");
    }
    
    $periodoData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    error_log("Datos recuperados: " . print_r($periodoData, true));
    
    if ($periodoData) {
        $response = [
            'success' => true,
            'periodo' => $periodoData['PERIODO'],
            'fechaAnticipo' => $periodoData['FECHA_ANTICIPO'],
            'vigDesde' => $periodoData['VIG_DESDE'],
            'vigHasta' => $periodoData['VIG_HASTA']
        ];
        error_log("Respuesta a enviar: " . print_r($response, true));
    } else {
        $response = [
            'success' => true,
            'periodo' => null,
            'fechaAnticipo' => '',
            'vigDesde' => '',
            'vigHasta' => ''
        ];
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error en getPeriodoActual: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}