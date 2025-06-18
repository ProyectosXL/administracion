
<?php
// Controller/getAnticipos.php
header('Content-Type: application/json');

try {
    require_once '../../Class/Anticipo.php';
    $anticipo = new Anticipo();
    
    // Parámetros de DataTables
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $periodo = isset($_POST['periodo']) ? $_POST['periodo'] : '';
    
    // Obtener datos usando los métodos de la clase
    $data = $anticipo->obtenerAnticipos($start, $length, $search, $periodo);
    $total = $anticipo->contarAnticipos('', $periodo);
    $filtered = $anticipo->contarAnticipos($search, $periodo);
    
    // Respuesta para DataTables
    $response = array(
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        "recordsTotal" => $total,
        "recordsFiltered" => $filtered,
        "data" => $data
    );
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error en getAnticipos.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'data' => array(),
        'recordsTotal' => 0,
        'recordsFiltered' => 0
    ]);
}
?>