<?php
/**
 * Controlador para actualizar contratos de alquiler
 * Maneja las solicitudes AJAX para editar contratos existentes
 */

header('Content-Type: application/json; charset=utf-8');

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    // Incluir la clase Alquiler
    require_once "../Class/Alquiler.php";
    $alquiler = new Alquiler();
    
    // Validar que todos los campos requeridos estén presentes
    $camposRequeridos = [
        'contratoId', 'nroSucursal', 'descSucursal', 
        'vigDesde', 'vigHasta'
    ];
    
    foreach ($camposRequeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            echo json_encode([
                'success' => false,
                'message' => "El campo '$campo' es requerido"
            ]);
            exit;
        }
    }
    
    // Obtener y sanitizar los datos
    $contratoId = (int) $_POST['contratoId'];
    $nroSucursal = trim($_POST['nroSucursal']);
    $descSucursal = trim($_POST['descSucursal']);
    $vigDesde = $_POST['vigDesde'];
    $vigHasta = $_POST['vigHasta'];
    
    // Los importes son opcionales, si están vacíos se asigna 0
    $valorLlave = isset($_POST['valorLlave']) && $_POST['valorLlave'] !== '' ? (float) $_POST['valorLlave'] : 0;
    $comisiones = isset($_POST['comisiones']) && $_POST['comisiones'] !== '' ? (float) $_POST['comisiones'] : 0;
    $lanzamiento = isset($_POST['lanzamiento']) && $_POST['lanzamiento'] !== '' ? (float) $_POST['lanzamiento'] : 0;
    
    // Validaciones adicionales
    if ($contratoId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de contrato inválido'
        ]);
        exit;
    }
    
    // Validar fechas
    $fechaDesde = DateTime::createFromFormat('Y-m-d', $vigDesde);
    $fechaHasta = DateTime::createFromFormat('Y-m-d', $vigHasta);
    
    if (!$fechaDesde || !$fechaHasta) {
        echo json_encode([
            'success' => false,
            'message' => 'Formato de fecha inválido'
        ]);
        exit;
    }
    
    if ($fechaDesde >= $fechaHasta) {
        echo json_encode([
            'success' => false,
            'message' => 'La fecha "Hasta" debe ser posterior a la fecha "Desde"'
        ]);
        exit;
    }
    
    // Validar importes
    if ($valorLlave < 0 || $comisiones < 0 || $lanzamiento < 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Los importes no pueden ser negativos'
        ]);
        exit;
    }
    
    // Verificar que el contrato existe antes de actualizar
    $contratoExistente = $alquiler->obtenerContratoPorId($contratoId);
    if (!$contratoExistente) {
        echo json_encode([
            'success' => false,
            'message' => 'El contrato no existe o no se puede modificar'
        ]);
        exit;
    }
    
    // Verificar solapamientos solo si las fechas cambiaron
    if ($contratoExistente['VIG_DESDE'] !== $vigDesde || $contratoExistente['VIG_HASTA'] !== $vigHasta) {
        $verificacionSolapamiento = $alquiler->verificarSolapamientoContratoEdicion($nroSucursal, $vigDesde, $vigHasta, $contratoId);
        
        if ($verificacionSolapamiento['solapamiento']) {
            echo json_encode([
                'success' => false,
                'message' => 'Las nuevas fechas se solapan con otro contrato existente: ' . $verificacionSolapamiento['mensaje']
            ]);
            exit;
        }
    }
    
    // Actualizar el contrato
    $resultado = $alquiler->actualizarContrato(
        $contratoId,
        $vigDesde,
        $vigHasta,
        $valorLlave,
        $comisiones,
        $lanzamiento
    );
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Contrato actualizado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo actualizar el contrato. Intente nuevamente.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en actualizarContrato.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
} catch (Throwable $e) {
    error_log("Error crítico en actualizarContrato.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error crítico del servidor. Contacte al administrador.'
    ]);
}
?>
