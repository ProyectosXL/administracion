
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once '../../Class/Anticipo.php';
    $anticipo = new Anticipo();
    
    $nroSucursal = isset($_SESSION['numsuc']) ? $_SESSION['numsuc'] : '1';
    $empleados = $anticipo->traerEmpleadosGrupo($nroSucursal);
    
    echo json_encode($empleados);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}