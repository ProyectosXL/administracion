<?php
require_once 'C:\xampp\htdocs\administracion\recursosHumanos\Class\Anticipo.php';
require_once 'C:\xampp\htdocs\administracion\recursosHumanos\Class\Email.php';

function iniciarEnvioAutomatico($periodoActual) {
    
    $anticipo = new Anticipo();
    $fechas = $anticipo->getFechasPorPeriodo($periodoActual);
    $emails = $anticipo->getEmails($periodoActual);

    $fechaHoy = date('Y-m-d');

    if ($fechaHoy == $fechas['fechaDesde']) {
        enviarEmailInicio($emails);
    }

    if ($fechaHoy == $fechas['fechaHasta']) {
        enviarEmailCierre($emails);
    }

}

function enviarEmailInicio($emails) {
    foreach ($emails as $email) {
       
        $emailObj = new Email(
            $email, 
            Email::getSubjectAnticipos(), 
            Email::getHtmlAnticipos()
        );


        $emailObj->sendEmail();
    }
}

function enviarEmailCierre($emails) {

    foreach ($emails as $email) {
        $emailObj = new Email(
            $email, 
            Email::getSubjectAnticiposCierre(), 
            Email::getHtmlAnticiposCierre()
        );

        $emailObj->sendEmail();
    }

}
?>
