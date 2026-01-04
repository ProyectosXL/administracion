<?php 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if($_POST['entorno'] == 0){
    $_SESSION['entorno'] = 'central';
}else{
    $_SESSION['entorno'] = 'suc_uy';
}

echo json_encode(['success' => true, 'message' => 'Entorno cambiado correctamente']);

?>
