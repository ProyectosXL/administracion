<?php

class Email {
    private $to;
    private $subject;
    private $htmlContent;

    public function __construct($to, $subject, $htmlContent) {
        $this->to = $to;
        $this->subject = $subject;
        $this->htmlContent = $htmlContent;
    }

 
    public function getEmailData() {
        return [
            "to" => $this->to,
            "subject" => $this->subject,
            "html" => $this->htmlContent
        ];
    }


    public function sendEmail($apiUrl) {
        $data = json_encode($this->getEmailData());

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Content-Length: " . strlen($data)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            echo "Email enviado con éxito a: " . $this->to . "\n";
        } else {
            echo "Error al enviar email a: " . $this->to . " | Código HTTP: $httpCode\n";
            echo "Respuesta de la API: $response\n";
        }
    }

    // Métodos estáticos para obtener templates de email
    public static function getSubjectAnticipos() {
        return "Apertura del Período de Anticipos";
    }

    public static function getHtmlAnticipos() {
        return "<!DOCTYPE html> <html lang='es'> 
        <head>
            <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>
                        Apertura del Período de Anticipos
                    </title>
                    <style> 
                        body 
                        { 
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            line-height: 1.6;
                             color: #333;
                            background-color: #f9f9f9;
                            margin: 0; padding: 0; 
                        } 
                        .email-container {
                            max-width: 600px;
                            margin: 0 auto;
                            background-color: #ffffff;
                            border-radius: 8px;
                            overflow: hidden;
                            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
                        } 
                        .email-header {
                            background-color: #3498db;
                            color: white;
                            padding: 20px;
                            text-align: center;
                        } 
                        .email-body { 
                            padding: 30px;
                        } 
                        .email-footer { 
                            background-color: #f5f5f5;
                            padding: 15px;
                            text-align: center;
                            font-size: 14px;
                            color: #777;
                        } 
                        .btn-primary { 
                            background-color: #3498db;
                            border-color: #3498db;
                            padding: 10px 20px;
                            font-weight: 500;
                            border-radius: 4px;
                            text-decoration: none;
                            display: inline-block;
                            margin-top: 15px;
                            color: white;
                        } 
                        .btn-primary:hover { 
                            background-color: #2980b9;
                            border-color: #2980b9;
                        } 
                        .highlight {
                            background-color: #f8f4e5; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0; border-radius: 4px; } .icon { font-size: 48px; margin-bottom: 15px; text-align: center; } @media only screen and (max-width: 600px) { .email-body { padding: 20px; } } </style> </head> <body> <div class='email-container'> <div class='email-header'> <h1>Solicitud de Anticipos</h1> </div> <div class='email-body'> <div class='text-center mb-4'> <div class='icon'>📅</div> <h2>¡Período de Solicitud Abierto!</h2> </div> <p>Buen día,</p> <div class='highlight'> <p>Les comunicamos que ya se encuentra <strong>habilitado el período para solicitar anticipos de sueldo</strong>.</p> </div> <div class='mt-4'> <p>Recuerde que:</p> <ul> <li>Debe completar todos los campos requeridos</li> <li>La aprobación está sujeta a revisión</li> <li>Consulte las políticas de anticipos vigentes</li> </ul> </div> <p>Si tiene alguna consulta, no dude en contactar al departamento de Recursos Humanos.</p> <p>Gracias!<br>Saludos.-</p> </div> <div class='email-footer'> <p>Este es un correo automático. Por favor, no responda a este mensaje.</p> <p>&copy; 2025 Portal de Anticipos</p> </div> </div> </body> </html>";
    }
}
?>
