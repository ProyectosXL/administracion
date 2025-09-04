<?php
require_once "../Class/articulos.php";
require_once "../../class/conexion.php";

if (isset($_POST['codArticulo']) && isset($_POST['costoNac']) && isset($_POST['fecha'])) {
    try {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Crear conexión directamente
        $conexion = new Conexion();
        $base = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $cid = $conexion->conectar($base);

        if (!$cid) {
            throw new Exception("Error al conectar con la base de datos");
        }

        $codArticulo = trim($_POST['codArticulo']);
        $costoNac = floatval(str_replace(',', '.', $_POST['costoNac']));
        $fecha = $_POST['fecha'];

        // Validar que los valores no estén vacíos y que el costo sea válido
        if (empty($codArticulo) || empty($fecha) || !is_numeric($costoNac) || $costoNac <= 0) {
            throw new Exception('Los valores no son válidos');
        }

        // Log de los datos recibidos
        error_log("Datos recibidos:");
        error_log("Artículo: " . $codArticulo);
        error_log("Costo: " . $costoNac);
        error_log("Fecha: " . $fecha);

        $sql = "INSERT INTO RO_ORDEN_COMPRA_IMPORT_COSTO_NAC_MANUAL (FECHA, COD_ARTICU, COSTO_NAC) 
                VALUES (?, ?, ?)";
        
        $params = array(
            $fecha,
            $codArticulo,
            floatval($costoNac)
        );

        // Log de la consulta
        error_log("SQL a ejecutar: " . $sql);
        error_log("Parámetros: " . print_r($params, true));
        
        $stmt = sqlsrv_prepare($cid, $sql, $params);
        
        if (!$stmt) {
            throw new Exception("Error preparando la consulta: " . print_r(sqlsrv_errors(), true));
        }
        
        $result = sqlsrv_execute($stmt);
        if ($result === false) {
            throw new Exception("Error ejecutando la consulta: " . print_r(sqlsrv_errors(), true));
        }

        // Verificar que se insertó el registro
        $rowsAffected = sqlsrv_rows_affected($stmt);
        if ($rowsAffected === false || $rowsAffected === 0) {
            throw new Exception("No se insertó ningún registro");
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Registro insertado correctamente',
            'rowsAffected' => $rowsAffected
        ]);

    } catch (Exception $e) {
        error_log("Error en guardarCostoNac.php: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    error_log("Faltan parámetros requeridos en la solicitud");
    error_log("POST recibido: " . print_r($_POST, true));
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Faltan parámetros requeridos',
        'received' => $_POST
    ]);
}
?>
