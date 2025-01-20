
<?php
// Controller/getAnticipos.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once '../Class/Anticipo.php';
    $anticipo = new Anticipo();

    // Obtener parámetros
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $periodo = isset($_POST['periodo']) ? $_POST['periodo'] : '';

    // Usar los métodos de la clase
    $data = $anticipo->obtenerAnticipos($start, $length, $search, $periodo);
    $recordsTotal = $anticipo->contarAnticipos();
    $recordsFiltered = $anticipo->contarAnticipos($search, $periodo);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en getAnticipos.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'draw' => $draw ?? 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
}