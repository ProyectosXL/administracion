<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../../../class/classEnv.php';

// Cargar PHPMailer desde carpeta local
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Clase EmailNotificacion
 * Gestiona el envío de correos electrónicos para notificaciones del sistema
 */
class EmailNotificacion {
    
    // Direcciones de correo
    private const EMAIL_TESORERIA = 'federico.trejo@xl.com.ar';
    private const EMAIL_PROVEEDORES = 'cfedetrejo@gmail.com';
    
    // Nombre del remitente
    private const EMAIL_FROM_NAME = 'Sistema Egresos Directores';
    
    private $db;
    private $envVars;
    
    public function __construct() {
        $this->db = Database::getInstance()->getAppsConnection();
        
        // Cargar variables de entorno
        $vars = new DotEnv(__DIR__ . '/../../../.env');
        $this->envVars = $vars->listVars();
    }
    
    /**
     * Envía notificación cuando se crea una nueva solicitud
     * @param array $solicitud
     * @return bool
     */
    public function notificarNuevaSolicitud(array $solicitud) {
        try {
            $motivo = $solicitud['motivo'];
            $idSolicitud = $solicitud['id_solicitud'];
            
            // Obtener datos completos de la solicitud
            $datosCompletos = $this->obtenerDatosSolicitud($idSolicitud);
            
            if (!$datosCompletos) {
                throw new Exception("No se pudo obtener datos de la solicitud");
            }
            
            if ($motivo === 'COMPRA_PERSONAL') {
                // Enviar a Tesorería y Proveedores
                $resultadoTesoreria = $this->enviarEmailNuevaCompraPersonal($datosCompletos, self::EMAIL_TESORERIA);
                $resultadoProveedores = $this->enviarEmailNuevaCompraPersonal($datosCompletos, self::EMAIL_PROVEEDORES);
                
                return $resultadoTesoreria && $resultadoProveedores;
            } else {
                // RETIRO_DINERO - Solo enviar a Tesorería
                return $this->enviarEmailNuevoRetiroDinero($datosCompletos, self::EMAIL_TESORERIA);
            }
        } catch (Exception $e) {
            error_log("Error al enviar notificación de nueva solicitud: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía notificación cuando proveedores carga una orden de compra
     * @param string $idSolicitud
     * @return bool
     */
    public function notificarOrdenCompraCargada(string $idSolicitud) {
        try {
            $datosCompletos = $this->obtenerDatosSolicitud($idSolicitud);
            
            if (!$datosCompletos) {
                throw new Exception("No se pudo obtener datos de la solicitud");
            }
            
            return $this->enviarEmailOrdenCompraCargada($datosCompletos, self::EMAIL_TESORERIA);
        } catch (Exception $e) {
            error_log("Error al enviar notificación de O.C. cargada: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene los datos completos de una solicitud
     * @param string $idSolicitud
     * @return array|null
     */
    private function obtenerDatosSolicitud(string $idSolicitud) {
        try {
            $sql = "SELECT 
                        s.id_solicitud,
                        s.id_director,
                        d.NOMBRE as nombre_director,
                        ISNULL(d.EMAIL, '') as email_director,
                        s.motivo,
                        s.importe,
                        s.estado,
                        s.observaciones,
                        ISNULL(s.observaciones_proveedores, '') as observaciones_proveedores,
                        ISNULL(s.observaciones_tesoreria, '') as observaciones_tesoreria,
                        s.fecha_solicitud,
                        (SELECT COUNT(*) FROM archivos_solicitud WHERE id_solicitud = s.id_solicitud) as cantidad_archivos
                    FROM solicitudes_egresos s
                    INNER JOIN RO_T_DIRECTORES d ON s.id_director = d.ID_DIRECTOR
                    WHERE s.id_solicitud = ?";
            
            $stmt = sqlsrv_query($this->db, $sql, [$idSolicitud]);
            
            if ($stmt === false) {
                error_log("Error en consulta SQL: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if ($row) {
                // Convertir fechas
                if (is_object($row['fecha_solicitud'])) {
                    $row['fecha_solicitud'] = $row['fecha_solicitud']->format('Y-m-d H:i:s');
                }
            }
            
            return $row;
        } catch (Exception $e) {
            error_log("Error al obtener datos de solicitud: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Envía email para nueva compra personal
     * @param array $datos
     * @param string $destinatario
     * @return bool
     */
    private function enviarEmailNuevaCompraPersonal(array $datos, string $destinatario) {
        $fecha = date('d/m/Y H:i', strtotime($datos['fecha_solicitud']));
        $importe = number_format($datos['importe'], 2, ',', '.');
        
        $esTesoreria = ($destinatario === self::EMAIL_TESORERIA);
        $rol = $esTesoreria ? 'Tesorería' : 'Proveedores';
        
        $asunto = "Nueva Compra Personal - {$datos['id_solicitud']} - {$datos['nombre_director']}";
        
        $mensaje = $this->generarHtmlEmail([
            'titulo' => 'Nueva Solicitud de Compra Personal',
            'contenido' => "
                <p>Se ha registrado una nueva solicitud de compra personal que requiere su atención.</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #007bff; margin-top: 0;'>Datos de la Solicitud</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 180px;'>ID Solicitud:</td>
                            <td style='padding: 8px 0;'>{$datos['id_solicitud']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Director:</td>
                            <td style='padding: 8px 0;'>{$datos['nombre_director']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Fecha:</td>
                            <td style='padding: 8px 0;'>{$fecha}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Importe:</td>
                            <td style='padding: 8px 0; color: #28a745; font-size: 18px; font-weight: bold;'>$ {$importe}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Archivos Adjuntos:</td>
                            <td style='padding: 8px 0;'>{$datos['cantidad_archivos']} archivo(s)</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Estado:</td>
                            <td style='padding: 8px 0;'><span style='background-color: #ffc107; color: #856404; padding: 5px 10px; border-radius: 3px;'>SOLICITADO</span></td>
                        </tr>
                    </table>
                </div>
                
                " . ($datos['observaciones'] ? "
                <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h4 style='margin-top: 0; color: #856404;'>Observaciones del Director:</h4>
                    <p style='margin: 0;'>{$datos['observaciones']}</p>
                </div>
                " : "") . "
                
                " . ($esTesoreria ? "
                <p><strong>Próximos pasos:</strong></p>
                <ul>
                    <li>El área de Proveedores cargará la orden de compra</li>
                    <li>Recibirá una notificación cuando la factura esté lista para el pago</li>
                </ul>
                " : "
                <p><strong>Acción requerida:</strong></p>
                <ul>
                    <li>Revisar la factura adjunta en el sistema</li>
                    <li>Cargar la orden de compra correspondiente</li>
                </ul>
                ") . "
                
                <p style='margin-top: 30px;'>
                    <a href='http://xl.com.ar/administracion/tesoreria/egresosDirectores/" . ($esTesoreria ? "tesoreria.php" : "proveedores.php") . "' 
                       style='background-color: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Ir al Portal de {$rol}
                    </a>
                </p>
            "
        ]);
        
        return $this->enviarEmail($destinatario, $asunto, $mensaje);
    }
    
    /**
     * Envía email para nuevo retiro de dinero
     * @param array $datos
     * @param string $destinatario
     * @return bool
     */
    private function enviarEmailNuevoRetiroDinero(array $datos, string $destinatario) {
        $fecha = date('d/m/Y H:i', strtotime($datos['fecha_solicitud']));
        $importe = number_format($datos['importe'], 2, ',', '.');
        
        $asunto = "Nuevo Retiro de Dinero - {$datos['id_solicitud']} - {$datos['nombre_director']}";
        
        $mensaje = $this->generarHtmlEmail([
            'titulo' => 'Nueva Solicitud de Retiro de Dinero',
            'contenido' => "
                <p>Se ha registrado una nueva solicitud de retiro de dinero que requiere pago inmediato.</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #28a745; margin-top: 0;'>Datos de la Solicitud</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 180px;'>ID Solicitud:</td>
                            <td style='padding: 8px 0;'>{$datos['id_solicitud']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Director:</td>
                            <td style='padding: 8px 0;'>{$datos['nombre_director']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Fecha:</td>
                            <td style='padding: 8px 0;'>{$fecha}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Importe a Transferir:</td>
                            <td style='padding: 8px 0; color: #28a745; font-size: 18px; font-weight: bold;'>$ {$importe}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Estado:</td>
                            <td style='padding: 8px 0;'><span style='background-color: #17a2b8; color: #fff; padding: 5px 10px; border-radius: 3px;'>CARGADO - Listo para Pago</span></td>
                        </tr>
                    </table>
                </div>
                
                " . ($datos['observaciones'] ? "
                <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h4 style='margin-top: 0; color: #856404;'>Observaciones del Director:</h4>
                    <p style='margin: 0;'>{$datos['observaciones']}</p>
                </div>
                " : "") . "
                
                <div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #17a2b8;'>
                    <h4 style='margin-top: 0; color: #0c5460;'>Acción Requerida:</h4>
                    <p style='margin: 0;'>Esta solicitud está lista para realizar la transferencia. Una vez efectuado el pago, deberá adjuntar el comprobante de transferencia en el sistema.</p>
                </div>
                
                <p style='margin-top: 30px;'>
                    <a href='http://xl.com.ar/administracion/tesoreria/egresosDirectores/tesoreria.php' 
                       style='background-color: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Ir al Portal de Tesorería
                    </a>
                </p>
            "
        ]);
        
        return $this->enviarEmail($destinatario, $asunto, $mensaje);
    }
    
    /**
     * Envía email cuando se carga una orden de compra
     * @param array $datos
     * @param string $destinatario
     * @return bool
     */
    private function enviarEmailOrdenCompraCargada(array $datos, string $destinatario) {
        $fecha = date('d/m/Y H:i', strtotime($datos['fecha_solicitud']));
        $importe = number_format($datos['importe'], 2, ',', '.');
        
        $asunto = "Factura Lista para Pago - {$datos['id_solicitud']} - {$datos['nombre_director']}";
        
        $mensaje = $this->generarHtmlEmail([
            'titulo' => 'Factura Lista para Pago',
            'contenido' => "
                <p>El área de Proveedores ha cargado la orden de compra. La factura está lista para proceder con el pago.</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #28a745; margin-top: 0;'>Datos de la Solicitud</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 180px;'>ID Solicitud:</td>
                            <td style='padding: 8px 0;'>{$datos['id_solicitud']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Director:</td>
                            <td style='padding: 8px 0;'>{$datos['nombre_director']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Fecha Solicitud:</td>
                            <td style='padding: 8px 0;'>{$fecha}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Importe:</td>
                            <td style='padding: 8px 0; color: #28a745; font-size: 18px; font-weight: bold;'>$ {$importe}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Estado:</td>
                            <td style='padding: 8px 0;'><span style='background-color: #17a2b8; color: #fff; padding: 5px 10px; border-radius: 3px;'>CARGADO - Listo para Pago</span></td>
                        </tr>
                    </table>
                </div>
                
                " . ($datos['observaciones_proveedores'] ? "
                <div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #17a2b8;'>
                    <h4 style='margin-top: 0; color: #0c5460;'>Observaciones de Proveedores:</h4>
                    <p style='margin: 0;'>{$datos['observaciones_proveedores']}</p>
                </div>
                " : "") . "
                
                <div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;'>
                    <h4 style='margin-top: 0; color: #155724;'>Acción Requerida:</h4>
                    <p style='margin: 0;'>Proceder con el pago de la factura. Recuerde adjuntar en el sistema:</p>
                    <ul style='margin-bottom: 0;'>
                        <li>Comprobante de transferencia</li>
                        <li>Orden de pago</li>
                        <li>Retenciones</li>
                    </ul>
                </div>
                
                <p style='margin-top: 30px;'>
                    <a href='http://xl.com.ar/administracion/tesoreria/egresosDirectores/tesoreria.php' 
                       style='background-color: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Ir al Portal de Tesorería
                    </a>
                </p>
            "
        ]);
        
        return $this->enviarEmail($destinatario, $asunto, $mensaje);
    }
    
    /**
     * Genera HTML del email con plantilla
     * @param array $params
     * @return string
     */
    private function generarHtmlEmail(array $params) {
        $titulo = $params['titulo'] ?? 'Notificación del Sistema';
        $contenido = $params['contenido'] ?? '';
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$titulo}</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background-color: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;'>
                <h1 style='margin: 0; font-size: 24px;'>{$titulo}</h1>
            </div>
            
            <div style='background-color: white; padding: 30px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 5px 5px;'>
                {$contenido}
            </div>
            
            <div style='text-align: center; margin-top: 20px; padding: 20px; color: #6c757d; font-size: 12px;'>
                <p>Este es un mensaje automático del Sistema de Egresos Directores.</p>
                <p>Por favor no responda a este correo.</p>
                <hr style='border: none; border-top: 1px solid #dee2e6; margin: 15px 0;'>
                <p>&copy; " . date('Y') . " XL Administración - Todos los derechos reservados</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Envía el email usando PHPMailer con SMTP
     * @param string $destinatario
     * @param string $asunto
     * @param string $mensaje
     * @return bool
     */
    private function enviarEmail(string $destinatario, string $asunto, string $mensaje) {
        $mail = new PHPMailer(true);
        
        try {
            // Configuración del servidor SMTP (credenciales que funcionan)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'xl.notificaciones@xl.com.ar';
            $mail->Password = 'oysuyuhsnyoaifin';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            // Configuración adicional
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // Debug solo en desarrollo
            $mail->SMTPDebug = 0; // 0 = sin debug, 2 = debug completo
            
            // Remitente
            $mail->setFrom('xl.notificaciones@xl.com.ar', 'XL Extralarge');
            
            // Destinatario
            $mail->addAddress($destinatario);
            
            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje;
            
            // Versión texto plano (fallback)
            $mail->AltBody = strip_tags($mensaje);
            
            // Enviar
            $resultado = $mail->send();
            
            if ($resultado) {
                error_log("✅ Email enviado exitosamente a: {$destinatario} - Asunto: {$asunto}");
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("❌ Error al enviar email a {$destinatario}: {$mail->ErrorInfo}");
            error_log("Detalles del error: " . $e->getMessage());
            return false;
        }
    }
}
