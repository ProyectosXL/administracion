<?php
session_start();

require_once 'Class/sucursal.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $entrego = $_POST['entrego'];
    $recibio = $_POST['recibio'];
    $enviaValores = ($_POST['enviaValores'] == 'SI') ? 1 : 0;
    $observaciones = $_POST['observaciones'];
    $firma = $_POST['firma'];


    try {
        $guiaRetiro = new Sucursal();

        
        $guiaRetiro->actualizarGuiaRetiro($id, $entrego, $recibio, $enviaValores, $observaciones, $firma);

                
        header('Location: listarRetiros.php');
        exit();
    } catch (Exception $e) {
        
        echo "Error al actualizar la guía: " . $e->getMessage();
    }
}
?>
