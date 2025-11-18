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
    private const EMAIL_TESORERIA = 'tesoreria@xl.com.ar';
    private const EMAIL_PROVEEDORES = [
        'rodrigo.alganaraz@xl.com.ar',
        'julieta.bianculli@xl.com.ar'  // Agrega aquí el segundo email
    ];
    
    // Nombre del remitente
    private const EMAIL_FROM_NAME = 'Sistema Egresos Directores';
    
    // Modo desarrollo: enviar todos los emails a federico.trejo@xl.com.ar
    private const DEVELOP = true;
    private const EMAIL_DEVELOP = 'federico.trejo@xl.com.ar';
    
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
                // Enviar a Tesorería
                $resultadoTesoreria = $this->enviarEmailNuevaCompraPersonal($datosCompletos, self::EMAIL_TESORERIA);
                
                // Enviar a Proveedores (puede ser uno o múltiples)
                $resultadoProveedores = true;
                $emailsProveedores = is_array(self::EMAIL_PROVEEDORES) ? self::EMAIL_PROVEEDORES : [self::EMAIL_PROVEEDORES];
                
                foreach ($emailsProveedores as $emailProveedor) {
                    $resultado = $this->enviarEmailNuevaCompraPersonal($datosCompletos, $emailProveedor);
                    $resultadoProveedores = $resultadoProveedores && $resultado;
                }
                
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
     * Envía notificación cuando se crea una solicitud múltiple de retiro
     * @param array $datosMultiple Información de la solicitud múltiple (id_base, detalles, etc)
     * @return bool
     */
    public function notificarNuevaSolicitudMultiple(array $datosMultiple) {
        try {
            // Enviar solo a Tesorería
            return $this->enviarEmailNuevoRetiroMultiple($datosMultiple, self::EMAIL_TESORERIA);
        } catch (Exception $e) {
            error_log("Error al enviar notificación de solicitud múltiple: " . $e->getMessage());
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
     * Envía notificación cuando tesorería paga una solicitud
     * @param string $idSolicitud
     * @return bool
     */
    public function notificarPagoRealizado(string $idSolicitud) {
        try {
            $datosCompletos = $this->obtenerDatosSolicitud($idSolicitud);
            
            if (!$datosCompletos) {
                throw new Exception("No se pudo obtener datos de la solicitud");
            }
            
            // Determinar destinatario: email del director (o email de desarrollo)
            $destinatario = $this->obtenerEmailDestinatario($datosCompletos['email_director']);
            
            // Enviar email según el tipo de solicitud
            if ($datosCompletos['motivo'] === 'COMPRA_PERSONAL') {
                return $this->enviarEmailCompraPagada($datosCompletos, $destinatario);
            } else {
                // RETIRO_DINERO
                return $this->enviarEmailRetiroPagado($datosCompletos, $destinatario);
            }
        } catch (Exception $e) {
            error_log("Error al enviar notificación de pago realizado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el email destinatario según el modo (desarrollo o producción)
     * @param string $emailDirector
     * @return string
     */
    private function obtenerEmailDestinatario(string $emailDirector) {
        if (self::DEVELOP) {
            error_log("MODO DESARROLLO: Email redirigido a " . self::EMAIL_DEVELOP);
            return self::EMAIL_DEVELOP;
        }
        
        // En producción, usar el email del director
        if (empty($emailDirector)) {
            error_log("ADVERTENCIA: Director sin email, usando email de desarrollo");
            return self::EMAIL_DEVELOP;
        }
        
        return $emailDirector;
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
                
            "
        ]);
        
        return $this->enviarEmail($destinatario, $asunto, $mensaje);
    }
    
    /**
     * Envía email de nueva solicitud múltiple de retiro de dinero
     * @param array $datosMultiple
     * @param string $destinatario
     * @return bool
     */
    private function enviarEmailNuevoRetiroMultiple(array $datosMultiple, string $destinatario) {
        $fecha = date('d/m/Y H:i');
        $idBase = $datosMultiple['id_base'];
        $cantidadSolicitudes = $datosMultiple['solicitudes_creadas'];
        $importeTotal = 0;
        $observaciones = $datosMultiple['observaciones'] ?? '';
        
        // Calcular importe total y generar tabla de detalle
        $filasTabla = '';
        foreach ($datosMultiple['detalles'] as $detalle) {
            $importeTotal += $detalle['importe'];
            $importeFormateado = number_format($detalle['importe'], 2, ',', '.');
            $filasTabla .= "
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$detalle['id_solicitud']}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$detalle['nombre_director']}</td>
                    <td style='padding: 8px; border-bottom: 1px solid #dee2e6; text-align: right; font-weight: bold;'>$ {$importeFormateado}</td>
                </tr>
            ";
        }
        
        $importeTotalFormateado = number_format($importeTotal, 2, ',', '.');
        
        $asunto = "Retiro Múltiple - Grupo {$idBase} - {$cantidadSolicitudes} Solicitudes";
        
        $mensaje = $this->generarHtmlEmail([
            'titulo' => 'Nueva Solicitud de Retiro Múltiple',
            'contenido' => "
                <p>Se ha registrado una solicitud de retiro múltiple que incluye transferencias para <strong>{$cantidadSolicitudes} directores</strong>.</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #28a745; margin-top: 0;'>Resumen del Grupo</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 180px;'>ID Grupo:</td>
                            <td style='padding: 8px 0;'>{$idBase}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Fecha:</td>
                            <td style='padding: 8px 0;'>{$fecha}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Cantidad de Solicitudes:</td>
                            <td style='padding: 8px 0;'>{$cantidadSolicitudes}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Importe Total:</td>
                            <td style='padding: 8px 0; color: #28a745; font-size: 18px; font-weight: bold;'>$ {$importeTotalFormateado}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Estado:</td>
                            <td style='padding: 8px 0;'><span style='background-color: #17a2b8; color: #fff; padding: 5px 10px; border-radius: 3px;'>CARGADO - Listo para Pago</span></td>
                        </tr>
                    </table>
                </div>
                
                <div style='background-color: #fff; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #dee2e6;'>
                    <h3 style='color: #333; margin-top: 0;'>Detalle de Distribución</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <thead>
                            <tr style='background-color: #f8f9fa;'>
                                <th style='padding: 10px; border-bottom: 2px solid #dee2e6; text-align: left;'>ID Solicitud</th>
                                <th style='padding: 10px; border-bottom: 2px solid #dee2e6; text-align: left;'>Director</th>
                                <th style='padding: 10px; border-bottom: 2px solid #dee2e6; text-align: right;'>Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$filasTabla}
                        </tbody>
                        <tfoot>
                            <tr style='background-color: #f8f9fa; font-weight: bold;'>
                                <td colspan='2' style='padding: 10px; border-top: 2px solid #dee2e6; text-align: right;'>TOTAL:</td>
                                <td style='padding: 10px; border-top: 2px solid #dee2e6; text-align: right; color: #28a745; font-size: 16px;'>$ {$importeTotalFormateado}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                " . ($observaciones ? "
                <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h4 style='margin-top: 0; color: #856404;'>Observaciones:</h4>
                    <p style='margin: 0;'>{$observaciones}</p>
                </div>
                " : "") . "
                
                <div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #17a2b8;'>
                    <h4 style='margin-top: 0; color: #0c5460;'>Acción Requerida:</h4>
                    <p style='margin: 0;'>Estas solicitudes están listas para realizar las transferencias. Cada solicitud requiere su propio comprobante de pago una vez efectuada la transferencia.</p>
                </div>
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
                
            "
        ]);
        
        return $this->enviarEmail($destinatario, $asunto, $mensaje);
    }
    
    /**
     * Envía email cuando se paga una compra personal
     * @param array $datos
     * @param string $destinatario
     * @return bool
     */
    private function enviarEmailCompraPagada(array $datos, string $destinatario) {
        $fecha = date('d/m/Y H:i', strtotime($datos['fecha_solicitud']));
        $importe = number_format($datos['importe'], 2, ',', '.');
        
        $asunto = "Compra Personal Pagada - {$datos['id_solicitud']}";
        
        $mensaje = $this->generarHtmlEmail([
            'titulo' => 'Compra Personal Pagada',
            'contenido' => "
                <p><strong>{$datos['nombre_director']}</strong>,</p>
                
                <p>Le informamos que su solicitud de compra personal ha sido <strong>pagada</strong> por el área de Tesorería.</p>
                
                <div style='background-color: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;'>
                    <h3 style='color: #155724; margin-top: 0;'>
                        <i style='font-size: 24px;'>✅</i> Pago Completado
                    </h3>
                    <p style='margin: 0; font-size: 16px; color: #155724;'>El importe fue transferido exitosamente</p>
                </div>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #007bff; margin-top: 0;'>Datos de la Solicitud</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 180px;'>ID Solicitud:</td>
                            <td style='padding: 8px 0;'><code style='background-color: #e9ecef; padding: 3px 8px; border-radius: 3px;'>{$datos['id_solicitud']}</code></td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Tipo:</td>
                            <td style='padding: 8px 0;'>Compra Personal</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Fecha Solicitud:</td>
                            <td style='padding: 8px 0;'>{$fecha}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Importe Pagado:</td>
                            <td style='padding: 8px 0; color: #28a745; font-size: 18px; font-weight: bold;'>$ {$importe}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Estado:</td>
                            <td style='padding: 8px 0;'><span style='background-color: #28a745; color: #fff; padding: 5px 10px; border-radius: 3px;'>PAGADO</span></td>
                        </tr>
                    </table>
                </div>
                
                " . ($datos['observaciones_tesoreria'] ? "
                <div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #17a2b8;'>
                    <h4 style='margin-top: 0; color: #0c5460;'>Observaciones de Tesorería:</h4>
                    <p style='margin: 0;'>{$datos['observaciones_tesoreria']}</p>
                </div>
                " : "") . "
                
                <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h4 style='margin-top: 0; color: #856404;'>Importante:</h4>
                    <p style='margin: 0;'>Los comprobantes de pago (transferencia, orden de pago y retenciones) están disponibles en el sistema. Puede consultarlos en cualquier momento.</p>
                </div>
            "
        ]);
        
        return $this->enviarEmail($destinatario, $asunto, $mensaje);
    }
    
    /**
     * Envía email cuando se paga un retiro de dinero
     * @param array $datos
     * @param string $destinatario
     * @return bool
     */
    private function enviarEmailRetiroPagado(array $datos, string $destinatario) {
        $fecha = date('d/m/Y H:i', strtotime($datos['fecha_solicitud']));
        $importe = number_format($datos['importe'], 2, ',', '.');
        
        $asunto = "Retiro de Dinero Pagado - {$datos['id_solicitud']}";
        
        $mensaje = $this->generarHtmlEmail([
            'titulo' => 'Retiro de Dinero Pagado',
            'contenido' => "
                <p><strong>{$datos['nombre_director']}</strong>,</p>
                
                <p>Le informamos que su solicitud de retiro de dinero ha sido <strong>pagada</strong> por el área de Tesorería.</p>
                
                <div style='background-color: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;'>
                    <h3 style='color: #155724; margin-top: 0;'>
                        <i style='font-size: 24px;'>✅</i> Pago Completado
                    </h3>
                    <p style='margin: 0; font-size: 16px; color: #155724;'>El importe fue transferido exitosamente</p>
                </div>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #17a2b8; margin-top: 0;'>Datos de la Solicitud</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 180px;'>ID Solicitud:</td>
                            <td style='padding: 8px 0;'><code style='background-color: #e9ecef; padding: 3px 8px; border-radius: 3px;'>{$datos['id_solicitud']}</code></td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Tipo:</td>
                            <td style='padding: 8px 0;'>Retiro de Dinero</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Fecha Solicitud:</td>
                            <td style='padding: 8px 0;'>{$fecha}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Importe Transferido:</td>
                            <td style='padding: 8px 0; color: #28a745; font-size: 18px; font-weight: bold;'>$ {$importe}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Estado:</td>
                            <td style='padding: 8px 0;'><span style='background-color: #28a745; color: #fff; padding: 5px 10px; border-radius: 3px;'>PAGADO</span></td>
                        </tr>
                    </table>
                </div>
                
                " . ($datos['observaciones'] ? "
                <div style='background-color: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #007bff;'>
                    <h4 style='margin-top: 0; color: #004085;'>Sus Observaciones:</h4>
                    <p style='margin: 0;'>{$datos['observaciones']}</p>
                </div>
                " : "") . "
                
                " . ($datos['observaciones_tesoreria'] ? "
                <div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #17a2b8;'>
                    <h4 style='margin-top: 0; color: #0c5460;'>Observaciones de Tesorería:</h4>
                    <p style='margin: 0;'>{$datos['observaciones_tesoreria']}</p>
                </div>
                " : "") . "
                
                <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h4 style='margin-top: 0; color: #856404;'>Importante:</h4>
                    <p style='margin: 0;'>El comprobante de transferencia está disponible en el sistema. Puede consultarlo en cualquier momento.</p>
                </div>
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
