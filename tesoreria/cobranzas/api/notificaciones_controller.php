<?php
// api/notificaciones_controller.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// NO se carga vendor/autoload.php. Nada en cobranzas usa clases de vendor
// (ni Dompdf ni Dotenv): PHPMailer se carga a mano abajo y las variables de
// entorno las resuelve config/database.php. Ademas el vendor/ commiteado esta
// incompleto (faltan symfony/polyfill-*, phpoption, graham-campbell y vlucas
// que composer.lock declara), asi que el autoloader fatalea en cualquier
// checkout limpio y se llevaba puesto todo endpoint que incluyera este archivo.

// Cargamos PHPMailer manualmente desde la carpeta que existe en el servidor
require_once __DIR__ . '/../../egresosDirectores/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../egresosDirectores/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../egresosDirectores/PHPMailer/Exception.php';

// Las variables de entorno ya son cargadas por database.php mediante putenv() y $_ENV
// No es necesario volver a cargarlas aquí con Dotenv.

if (!function_exists('enviarNotificacion')) {
    /**
     * @param string|array $destinatarios  Uno o varios mails (array o separados por ';').
     * @param string       $asunto
     * @param string       $cuerpo_html
     * @param array        $adjuntos       Opcional. Lista de ['nombre' => 'archivo.xlsx',
     *                                     'contenido' => <binario>, 'tipo' => 'mime/type'].
     *                                     Se adjuntan desde memoria con addStringAttachment,
     *                                     sin pasar por disco.
     * @param string|null  $error_detalle  Opcional, por referencia. Si el envío falla, acá
     *                                     queda el ErrorInfo de PHPMailer para que el llamador
     *                                     pueda mostrárselo al usuario o registrarlo.
     * @return bool
     *
     * Los dos últimos parámetros se agregaron para la liquidación de Franquicias GA;
     * los llamadores anteriores pasan tres argumentos y siguen funcionando igual.
     */
    function enviarNotificacion($destinatarios, $asunto, $cuerpo_html, $adjuntos = [], &$error_detalle = null)
    {
        $error_detalle = null;
        // ========================================================================
        // INTERRUPTOR GLOBAL DE NOTIFICACIONES
        // Cambiar a 'true' para habilitar los envíos reales de correo.
        // ========================================================================
        $notificaciones_habilitadas = true; 

        if (empty($destinatarios)) {
            $error_detalle = 'No se indicó ningún destinatario.';
            return false;
        }

        // Normalizamos los destinatarios a un array, soportando separación por punto y coma (;)
        $destinatarios_array = is_array($destinatarios) ? $destinatarios : explode(';', (string)$destinatarios);

        $destinatarios_filtrados = [];
        $excluidos = [];
        foreach ($destinatarios_array as $email) {
            $email = trim($email);
            if (empty($email)) {
                continue;
            }
            if (strcasecmp($email, 'yamilaruiz.extralarge@gmail.com') === 0) {
                $excluidos[] = $email;
                continue;
            }
            $destinatarios_filtrados[] = $email;
        }

        // Si se excluyeron destinatarios, registrarlo en el log
        if (!empty($excluidos)) {
            file_put_contents(__DIR__ . '/notificaciones.log', "[" . date('Y-m-d H:i:s') . "] EXCLUSIÓN: Se omitió el envío a los siguientes destinatarios: " . implode(',', $excluidos) . "\n", FILE_APPEND);
        }

        if (empty($destinatarios_filtrados)) {
            file_put_contents(__DIR__ . '/notificaciones.log', "[" . date('Y-m-d H:i:s') . "] ENVÍO ANULADO: No quedan destinatarios válidos tras aplicar la exclusión.\n", FILE_APPEND);
            return true; // Retornamos true para evitar reintentos infinitos en crons de recordatorios/avisos
        }

        if (!$notificaciones_habilitadas) {
            file_put_contents(__DIR__ . '/notificaciones.log', "[" . date('Y-m-d H:i:s') . "] ENVÍO SUPRIMIDO (MODO MANTENIMIENTO) | A: " . implode(',', $destinatarios_filtrados) . " | Asunto: $asunto\n", FILE_APPEND);
            return true; // Retornamos true para que el flujo de la app continúe sin errores
        }

        file_put_contents(__DIR__ . '/notificaciones.log', "[" . date('Y-m-d H:i:s') . "] Enviando mail a: " . implode(',', $destinatarios_filtrados) . " | Asunto: $asunto\n", FILE_APPEND);
        
        require_once __DIR__ . '/../config/database.php';
        Database::getConnection('apps'); 

        $mail = new PHPMailer(true);
        try {
            // Usamos las variables del .env si existen, o fallbacks hardcodeados actualizados
            $mail->isSMTP();
            $mail->Host = $_ENV['HOST_EMAIL_EGRESOS'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['USER_EMAIL_EGRESOS'];
            $mail->Password = $_ENV['PASS_EMAIL_EGRESOS'];
            $mail->SMTPSecure = 'tls';
            $mail->Port = $_ENV['PORT_EMAIL_EGRESOS'] ?? 587;

            $mail->setFrom($mail->Username, 'XL Extra Large');

            foreach ($destinatarios_filtrados as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($email);
                }
            }

            if (empty($mail->getAllRecipientAddresses())) {
                $error_detalle = 'Ningún destinatario tiene formato de mail válido.';
                return false;
            }

            // Adjuntos en memoria (ver docblock). Se omiten silenciosamente los mal formados.
            foreach ((array) $adjuntos as $adj) {
                if (empty($adj['nombre']) || !isset($adj['contenido'])) {
                    continue;
                }
                $mail->addStringAttachment(
                    $adj['contenido'],
                    $adj['nombre'],
                    PHPMailer::ENCODING_BASE64,
                    $adj['tipo'] ?? 'application/octet-stream'
                );
            }

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto;
            $mail->Body = $cuerpo_html;
            $mail->send();
            file_put_contents(__DIR__ . '/notificaciones.log', "[" . date('Y-m-d H:i:s') . "] Mail enviado con éxito!" . (empty($adjuntos) ? '' : ' (con ' . count($adjuntos) . ' adjunto/s)') . "\n", FILE_APPEND);
            return true;
        } catch (Exception $e) {
            $error_detalle = $mail->ErrorInfo ?: $e->getMessage();
            error_log("PHPMailer Error: {$error_detalle}");
            file_put_contents(__DIR__ . '/notificaciones.log', "[" . date('Y-m-d H:i:s') . "] ERROR PHPMailer: " . $error_detalle . "\n", FILE_APPEND);
            return false;
        }
    }
}

if (!function_exists('obtenerEmailFranquiciado')) {
    function obtenerEmailFranquiciado($cod_cliente)
    {
        try {
            // Usamos un try-catch local para evitar que el 'die()' central de Database.php detenga el script
            $conn = Database::getConnection('lakers');
            if (!$conn) return null;

            $sql = "SELECT MAIL_GRUP_EMP, DESC_SUCURSAL FROM DIRECCIONARIO WHERE COD_CLIENT = ? AND CANAL = 'FRANQUICIAS' AND NRO_SUC_MADRE IS NULL";
            $stmt = sqlsrv_query($conn, $sql, [$cod_cliente]);
            if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                return [
                    'email' => !empty($row['MAIL_GRUP_EMP']) ? trim($row['MAIL_GRUP_EMP']) : null,
                    'razon_social' => trim($row['DESC_SUCURSAL'])
                ];
            }
        } catch (Throwable $e_db) {
            error_log("Error buscando datos en Lakers para $cod_cliente: " . $e_db->getMessage());
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
        <img src='https://app.xl.com.ar/administracion/assets/images/logo.jpg' alt='XL Extra Large' width='60' height='60' style='max-height: 60px; display: inline-block;'>
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