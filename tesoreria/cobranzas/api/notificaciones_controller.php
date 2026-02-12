<?php
// api/notificaciones_controller.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Incluimos el autoloader de Composer
require_once __DIR__ . '/../../../vendor/autoload.php';

// Cargamos las variables de entorno desde el .env de la raíz del proyecto
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../../');
    $dotenv->load();
} catch (Exception $e) {
    error_log('Error crítico: No se pudo cargar el archivo .env. ' . $e->getMessage());
    return;
}

if (!function_exists('enviarNotificacion')) {
    function enviarNotificacion($destinatarios, $asunto, $cuerpo_html)
    {
        if (empty($destinatarios))
            return false;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $mail->Port = $_ENV['MAIL_PORT'];

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);

            $destinatarios_array = is_array($destinatarios) ? $destinatarios : [$destinatarios];
            foreach ($destinatarios_array as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($email);
                }
            }

            if (empty($mail->getAllRecipientAddresses()))
                return false;

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto;
            $mail->Body = $cuerpo_html;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}

if (!function_exists('obtenerEmailFranquiciado')) {
    function obtenerEmailFranquiciado($cod_cliente)
    {
        $conn = Database::getConnection('central');
        $sql = "SELECT MAIL_NEXO FROM GVA14 WHERE COD_CLIENT = ?";
        $stmt = sqlsrv_query($conn, $sql, [$cod_cliente]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return !empty($row['MAIL_NEXO']) ? trim($row['MAIL_NEXO']) : null;
        }
        return null;
    }
}

if (!function_exists('obtenerEmailAdmin')) {
    function obtenerEmailAdmin($rol)
    {
        if ($rol === 'SILVIA')
            return 'silvia.freire@xl.com.ar';
        if ($rol === 'MARIELA')
            return 'mariela.gueler@xl.com.ar';
        return null;
    }
}
?>