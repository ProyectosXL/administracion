<?php 
    $accion = $_GET['accion'];
        
    include_once '../Class/Horario.php';
    $horario = new Horario();

    switch ($accion) {
        case 'traerVendedores':
            traerVendedores($horario);
            break;


        }

        
        function traerVendedores ($horario) {

            $data = $horario->traerVendedores();

            echo json_encode($data);


        }