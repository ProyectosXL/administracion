
<?php
session_start();
header('Content-Type: application/json');

try {
    require_once '../../Class/Anticipo.php';
    $anticipo = new Anticipo();
    
    $sector = isset($_GET['sector']) ? $_GET['sector'] : null;
    
    if (!$sector) {
        echo json_encode([
            'data' => []
        ]);
        exit;
    }

    $empleados = $anticipo->traerEmpleadosGrupo($sector);
    
    echo json_encode([
        'data' => $empleados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'data' => [],
        'error' => $e->getMessage()
    ]);
}