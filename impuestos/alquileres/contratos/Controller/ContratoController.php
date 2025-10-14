<?php 

$accion = isset($_GET['accion']) ? $_GET['accion'] : "";

switch ($accion) {
    case 'guardarContratoAlquiler':
        guardarContratoAlquiler(); 
        break;
    
    case 'verificarSolapamientoContrato':
        verificarSolapamientoContrato(); 
        break;

    default:
        // No hacer nada si no hay acción válida
        break;
}

function guardarContratoAlquiler() {
    // Desactivar la visualización de errores
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Establecer header para respuesta de texto plano
    header('Content-Type: text/plain');
    
    try {
        require_once "../Class/Contrato.php";

        $contrato = new Contrato();

        // Validar datos POST
        $campos_requeridos = ['desde', 'hasta', 'idSucursal', 'descSucursal', 'valorLlave', 'comisiones', 'lanzamiento'];
        
        foreach ($campos_requeridos as $campo) {
            if (!isset($_POST[$campo])) {
                throw new Exception("Campo faltante: $campo");
            }
        }

        $desde = $_POST['desde'];
        $hasta = $_POST['hasta'];
        $idSucursal = $_POST['idSucursal'];
        $descSucursal = $_POST['descSucursal'];
        $valorLlave = intval($_POST['valorLlave']);
        $comisiones = intval($_POST['comisiones']);
        $lanzamiento = intval($_POST['lanzamiento']);

        // Verificar solapamiento antes de guardar
        $verificacion = $contrato->verificarSolapamientoContrato($idSucursal, $desde, $hasta);
        
        if ($verificacion['solapamiento']) {
            // Hay solapamiento, devolver false
            echo 'false';
            exit;
        }

        // Si no hay solapamiento, proceder a guardar
        $result = $contrato->guardarContratoAlquiler($idSucursal, $descSucursal, $valorLlave, $comisiones, $lanzamiento, $desde, $hasta);
        
        if ($result) {
            echo 'true';
        } else {
            echo 'false';
        }
        
    } catch (\Throwable $th) {
        error_log("Error al guardar contrato: " . $th->getMessage());
        echo 'false';
    }
    
    exit;
}

function verificarSolapamientoContrato() {
    // Desactivar la visualización de errores para esta función
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Establecer header para JSON
    header('Content-Type: application/json');
    
    try {
        require_once "../Class/Contrato.php";
        
        $contrato = new Contrato();
        
        // Validar que los datos POST existen
        if (!isset($_POST['idSucursal']) || !isset($_POST['desde']) || !isset($_POST['hasta'])) {
            throw new Exception('Datos incompletos en la solicitud');
        }
        
        $idSucursal = $_POST['idSucursal'];
        $desde = $_POST['desde'];
        $hasta = $_POST['hasta'];
        
        // Validar que no estén vacíos
        if (empty($idSucursal) || empty($desde) || empty($hasta)) {
            throw new Exception('Todos los campos son obligatorios');
        }
        
        $resultado = $contrato->verificarSolapamientoContrato($idSucursal, $desde, $hasta);
        
        // Asegurar que siempre devolvemos un JSON válido
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        
    } catch (\Throwable $th) {
        // Log del error para debugging
        error_log("Error en verificarSolapamientoContrato: " . $th->getMessage());
        
        // Devolver error en formato JSON
        echo json_encode([
            'error' => true,
            'solapamiento' => false,
            'mensaje' => 'Error al verificar solapamiento: ' . $th->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    // Terminar la ejecución para evitar output adicional
    exit;
}

?>
