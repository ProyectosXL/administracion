<?php 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Headers para evitar caché
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(isset($_POST['entorno'])) {
    $entorno = $_POST['entorno'];
    
    if($entorno == 'central' || $entorno == 0) {
        $_SESSION['entorno'] = 'central';
    } else {
        $_SESSION['entorno'] = 'uy';
    }
    
    // Forzar escritura inmediata de la sesión
    session_write_close();
    
    // Reiniciar sesión para continuar usándola
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    echo json_encode([
        'success' => true,
        'entorno' => $_SESSION['entorno'],
        'timestamp' => time() // Agregar timestamp para debug
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió el parámetro entorno'
    ]);
}

?>