
<?php
// Iniciar sesión para gestión de usuarios (si se implementa)
session_start();

// Incluir la clase Politica
require_once '../Class/Politica.php';

// Crear instancia de la clase
$politicaObj = new Politica();

// Verificar que se ha proporcionado un ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    
    $documento_id = (int)$_GET['id'];
    
    // Obtener información del documento
    $documento = $politicaObj->obtenerPorId($documento_id);
    
    if ($documento) {
        // Registrar la descarga
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
        $politicaObj->registrarAcceso($documento_id, 'descarga', $usuario_id);
        
        // Verificar que el archivo existe
        if (file_exists($documento['ruta_archivo'])) {
            
            // Configurar cabeceras para la descarga
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $documento['archivo_nombre'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($documento['ruta_archivo']));
            
            // Limpiar el buffer de salida
            ob_clean();
            flush();
            
            // Leer el archivo y enviarlo al navegador
            readfile($documento['ruta_archivo']);
            exit;
            
        } else {
            // El archivo no existe
            header('Location: index.php?mensaje=error&texto=El archivo no existe en el servidor');
        }
        
    } else {
        // Documento no encontrado
        header('Location: index.php?mensaje=error&texto=Documento no encontrado');
    }
    
} else {
    // ID no proporcionado
    header('Location: index.php?mensaje=error&texto=ID de documento no especificado');
}
exit;