<?php 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if(isset($_POST['entorno'])) {
    $entorno = $_POST['entorno'];
    
    if($entorno == 'central' || $entorno == 0) {
        $_SESSION['entorno'] = 'central';
    } else {
        $_SESSION['entorno'] = 'uy';
    }
    
    echo json_encode([
        'success' => true,
        'entorno' => $_SESSION['entorno']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió el parámetro entorno'
    ]);
}

?>