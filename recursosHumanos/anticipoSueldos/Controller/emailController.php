<?php
require_once '../Class/Anticipo.php';
require_once '../Class/Email.php';

function iniciarEnvioAutomatico($periodoActual) {
    $anticipo = new Anticipo();
    $fechas = $anticipo->getFechasPorPeriodo($periodoActual);
    $emails = $anticipo->getEmails($periodoActual);

    $fechaHoy = date('Y-m-d');

    if ($fechaHoy == $fechas['fechaDesde']) {
        enviarEmailInicio($emails);
    }
}

function enviarEmailInicio($emails) {
    $apiUrl = "http://app.xl.com.ar:6002/api/email/";

    foreach ($emails as $email) {
       
        $emailObj = new Email(
            $email, 
            Email::getSubjectAnticipos(), 
            Email::getHtmlAnticipos()
        );


        $emailObj->sendEmail($apiUrl);
    }
}

?>
