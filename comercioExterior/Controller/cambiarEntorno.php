<?php 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if($_POST['entorno'] == 0){

    $_SESSION['entorno'] = 'central';

}else{

    $_SESSION['entorno'] = 'uy';

}
echo true;

?>