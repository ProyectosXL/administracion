
<?php
session_start();
header('Content-Type: application/json');

try {
    require_once '../../Class/Anticipo.php';
    $anticipo = new Anticipo();
    
    $sector = (isset($_GET['sector']) && $_GET['sector'] != '') ? $_GET['sector'] : '%';
    $nroSucursal = ($_GET['sucursal'] == 'Casa Central') ? 1 : $_GET['sucursal'];

    $empleados = $anticipo->traerEmpleadosGrupo($sector, $nroSucursal);
    
    echo json_encode([
        'data' => $empleados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'data' => [],
        'error' => $e->getMessage()
    ]);
}