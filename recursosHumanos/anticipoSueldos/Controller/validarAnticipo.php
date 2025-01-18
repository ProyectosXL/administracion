
<?php
// Controller/validarAnticipo.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/Argentina/Buenos_Aires');

header('Content-Type: application/json');

try {
    if (!isset($_POST['registros'])) {
        throw new Exception('No se recibieron datos para validar');
    }

    $registrosJson = $_POST['registros'];
    $registros = json_decode($registrosJson, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar los datos: ' . json_last_error_msg());
    }

    require_once '../../Class/Anticipo.php';
    $anticipo = new Anticipo();

    // Obtener período vigente usando el método de la clase
    $periodoData = $anticipo->obtenerPeriodoActual();
    
    if (!$periodoData) {
        throw new Exception('No hay un período vigente para cargar anticipos');
    }

    // Validar duplicados usando el método obtenerValor
    $sql = "SELECT a.*, CONVERT(VARCHAR, a.FECHA_CARGA, 120) as FECHA_FORMATEADA 
            FROM RO_T_DETALLE_ANTICIPOS a 
            WHERE a.NRO_LEGAJO = ? AND a.PERIODO = ?";
    
    $stmt = sqlsrv_query($anticipo->cid_central, $sql, array($registro['legajo'], $periodoVigente));
    
    if ($stmt === false) {
        throw new Exception('Error al verificar duplicados');
    }

    $duplicado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if ($duplicado) {
        $fechaCarga = new DateTime($duplicado['FECHA_FORMATEADA']);
        throw new Exception(
            "Ya existe un anticipo registrado para el empleado:\n" .
            "Legajo: " . $registro['legajo'] . "\n" .
            "Nombre: " . $registro['nombre'] . "\n" .
            "Período: " . $periodoVigente . "\n" .
            "Importe: $" . number_format($duplicado['IMPORTE'], 2, ',', '.') . "\n" .
            "Fecha de carga: " . $fechaCarga->format('d/m/Y H:i:s')
        );
    }

    $response = array(
        'success' => true,
        'message' => 'Validación exitosa',
        'periodo' => $periodoData['PERIODO'],
        'fechaAnticipo' => $periodoData['FECHA_ANTICIPO']->format('d/m/Y'),
        'vigDesde' => $periodoData['VIG_DESDE']->format('d/m/Y H:i'),
        'vigHasta' => $periodoData['VIG_HASTA']->format('d/m/Y H:i')
    );

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error en validación: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}