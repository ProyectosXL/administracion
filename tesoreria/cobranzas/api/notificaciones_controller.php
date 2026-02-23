<?php
// api/notificaciones_controller.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Incluimos el autoloader de Composer (está en la raíz de administración)
require_once __DIR__ . '/../../../vendor/autoload.php';

// Cargamos PHPMailer manualmente desde la carpeta que existe en el servidor
require_once __DIR__ . '/../../egresosDirectores/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../egresosDirectores/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../egresosDirectores/PHPMailer/Exception.php';

// Cargamos las variables de entorno desde el .env de la raíz (W:\.env)
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../../');
    $dotenv->load();
} catch (\Exception $e) {
    error_log('Aviso: No se pudo cargar el archivo .env. ' . $e->getMessage());
}

if (!function_exists('enviarNotificacion')) {
    function enviarNotificacion($destinatarios, $asunto, $cuerpo_html)
    {
        if (empty($destinatarios))
            return false;

        $mail = new PHPMailer(true);
        try {
            // Usamos las variables del .env si existen, o fallbacks hardcodeados
            $mail->isSMTP();
            $mail->Host = $_ENV['HOST_EMAIL_EGRESOS'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['USER_EMAIL_EGRESOS'] ?? 'xl.notificaciones@xl.com.ar';
            $mail->Password = $_ENV['PASS_EMAIL_EGRESOS'] ?? 'oysuyuhsnyoaifin';
            $mail->SMTPSecure = 'tls';
            $mail->Port = $_ENV['PORT_EMAIL_EGRESOS'] ?? 587;

            $mail->setFrom($mail->Username, 'XL Extra Large');

            $destinatarios_array = is_array($destinatarios) ? $destinatarios : [$destinatarios];
            foreach ($destinatarios_array as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress(trim($email));
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
        $sql = "SELECT MAIL_NEXO, RAZON_SOCI FROM GVA14 WHERE COD_CLIENT = ?";
        $stmt = sqlsrv_query($conn, $sql, [$cod_cliente]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return [
                'email' => !empty($row['MAIL_NEXO']) ? trim($row['MAIL_NEXO']) : null,
                'razon_social' => trim($row['RAZON_SOCI'])
            ];
        }
        return null;
    }
}

if (!function_exists('obtenerEmailAdmin')) {
    function obtenerEmailAdmin($rol = 'TESORERIA')
    {
        // El usuario solicitó específicamente a Mariela para las notificaciones del portal
        return 'mariela.gueler@xl.com.ar';
    }
}

if (!function_exists('generarCuerpoEmail')) {
    function generarCuerpoEmail($titulo, $mensaje, $boton_texto = null, $boton_url = null)
    {
        $boton_html = '';
        if ($boton_texto && $boton_url) {
            $boton_html = "
<tr>
    <td align='center' style='padding: 20px 0;'>
        <a href='{$boton_url}'
            style='background-color: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>{$boton_texto}</a>
    </td>
</tr>
";
        }

        return "
<div
    style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 2px solid #007bff;'>
        <img src='https://app.xl.com.ar/assets/img/logo.png' alt='XL Extra Large' style='max-height: 50px;'>
    </div>
    <div style='padding: 30px;'>
        <h2 style='color: #333;'>{$titulo}</h2>
        <div style='color: #555; line-height: 1.6; font-size: 16px;'>
            {$mensaje}
        </div>
        <table width='100%' cellspacing='0' cellpadding='0'>
            {$boton_html}
        </table>
    </div>
    <div style='background-color: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #777;'>
        Este es un mensaje automático del Sistema de Gestión de Cobranzas - XL Extra Large.<br>
        Por favor no responda a este correo.
    </div>
</div>
";
    }
}
?>