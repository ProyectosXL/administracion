
<?php
// Iniciar sesión para gestión de usuarios (si se implementa)
session_start();

// Incluir la clase Politica
require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/Class/Politica.php';

// Configurar encabezados para respuesta JSON
header('Content-Type: application/json');

// Crear instancia de la clase
$politicaObj = new Politica();

// Verificar que se ha proporcionado un ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    
    $documento_id = (int)$_GET['id'];
    
    // Obtener información del documento
    $documento = $politicaObj->obtenerPorId($documento_id);
    
    if ($documento) {
        // Registrar la visualización
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
        $politicaObj->registrarAcceso($documento_id, 'vista', $usuario_id);
        
        // Convertir fechas a formato legible
        $documento['fecha_creacion'] = date('d/m/Y', strtotime($documento['fecha_creacion']));
        $documento['fecha_actualizacion'] = date('d/m/Y', strtotime($documento['fecha_actualizacion']));
        
        // Devolver respuesta exitosa
        echo json_encode([
            'status' => 'success',
            'documento' => $documento
        ]);
        
    } else {
        // Documento no encontrado
        echo json_encode([
            'status' => 'error',
            'message' => 'Documento no encontrado'
        ]);
    }
    
} else {
    // ID no proporcionado
    echo json_encode([
        'status' => 'error',
        'message' => 'ID de documento no especificado'
    ]);
}
exit;