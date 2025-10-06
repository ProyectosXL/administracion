<?php
require_once __DIR__ . '/Database.php';

/**
 * Clase ArchivoSolicitud
 * Gestiona archivos adjuntos de solicitudes
 */
class ArchivoSolicitud {
    private $db;
    
    // Constantes de tipos de archivo
    public const TIPO_FACTURA = 'FACTURA';
    public const TIPO_COMPROBANTE_TRANSFERENCIA = 'COMPROBANTE_TRANSFERENCIA';
    public const TIPO_ORDEN_PAGO = 'ORDEN_PAGO';
    public const TIPO_RETENCION = 'RETENCION';
    
    // Tamaño máximo: 15MB para móviles, 10MB para escritorio
    private const MAX_FILE_SIZE_MOBILE = 15 * 1024 * 1024;
    private const MAX_FILE_SIZE_DESKTOP = 10 * 1024 * 1024;
    
    public function __construct() {
        $this->db = Database::getInstance()->getAppsConnection();
    }
    
    /**
     * Guarda un archivo adjunto
     */
    public function guardar(string $idSolicitud, string $tipoArchivo, string $nombreArchivo, string $archivoBase64, string $mimeType): array {
        try {
            // Validar tipo de archivo
            $tiposValidos = [
                self::TIPO_FACTURA,
                self::TIPO_COMPROBANTE_TRANSFERENCIA,
                self::TIPO_ORDEN_PAGO,
                self::TIPO_RETENCION
            ];
            
            if (!in_array($tipoArchivo, $tiposValidos)) {
                throw new Exception("Tipo de archivo no válido");
            }
            
            // Decodificar base64
            $archivoData = base64_decode($archivoBase64);
            if ($archivoData === false) {
                throw new Exception("Error al decodificar archivo");
            }
            
            $tamañoBytes = strlen($archivoData);
            
            // Validar tamaño
            $maxSize = $this->esDispositoMovil() ? self::MAX_FILE_SIZE_MOBILE : self::MAX_FILE_SIZE_DESKTOP;
            if ($tamañoBytes > $maxSize) {
                $maxMB = round($maxSize / (1024 * 1024), 2);
                throw new Exception("Archivo demasiado grande. Máximo {$maxMB}MB");
            }
            
            // Comprimir si es imagen
            if (strpos($mimeType, 'image/') === 0) {
                $archivoData = $this->comprimirImagen($archivoBase64, $mimeType);
            }
            
            // Insertar en base de datos
            $sql = "INSERT INTO archivos_solicitud 
                        (id_solicitud, tipo_archivo, nombre_archivo, archivo, mime_type, tamanio_bytes)
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $params = [
                $idSolicitud,
                $tipoArchivo,
                $nombreArchivo,
                $archivoData,
                $mimeType,
                strlen($archivoData)
            ];
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al guardar archivo: " . print_r(sqlsrv_errors(), true));
            }
            
            $idArchivo = sqlsrv_rows_affected($stmt);
            sqlsrv_free_stmt($stmt);
            
            return [
                'success' => true,
                'id' => $idArchivo,
                'message' => 'Archivo guardado correctamente'
            ];
        } catch (Exception $e) {
            error_log("Error al guardar archivo: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene todos los archivos de una solicitud
     */
    public function obtenerPorSolicitud(string $idSolicitud): array {
        try {
            $sql = "SELECT id, tipo_archivo, nombre_archivo, mime_type, 
                           tamanio_bytes, fecha_carga
                    FROM archivos_solicitud 
                    WHERE id_solicitud = ? 
                    ORDER BY fecha_carga DESC";
            
            $stmt = sqlsrv_query($this->db, $sql, [$idSolicitud]);
            
            if ($stmt === false) {
                return [];
            }
            
            $archivos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                if (is_object($row['fecha_carga'])) {
                    $row['fecha_carga'] = $row['fecha_carga']->format('Y-m-d H:i:s');
                }
                $archivos[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            return $archivos;
        } catch (Exception $e) {
            error_log("Error al obtener archivos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene el contenido binario de un archivo
     */
    public function obtenerContenido(int $idArchivo): ?array {
        try {
            $sql = "SELECT archivo, nombre_archivo, mime_type 
                    FROM archivos_solicitud 
                    WHERE id = ?";
            
            $stmt = sqlsrv_query($this->db, $sql, [$idArchivo]);
            
            if ($stmt === false) {
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if ($row && $row['archivo']) {
                return [
                    'archivo' => base64_encode($row['archivo']),
                    'nombre_archivo' => $row['nombre_archivo'],
                    'mime_type' => $row['mime_type']
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error al obtener contenido: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Elimina un archivo
     */
    public function eliminar(int $idArchivo): bool {
        try {
            $sql = "DELETE FROM archivos_solicitud WHERE id = ?";
            $stmt = sqlsrv_query($this->db, $sql, [$idArchivo]);
            
            if ($stmt === false) {
                throw new Exception("Error al eliminar archivo: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Error al eliminar archivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Comprime una imagen para optimizar almacenamiento
     */
    private function comprimirImagen(string $imagenBase64, string $mimeType): string {
        try {
            // Verificar extensión GD
            if (!extension_loaded('gd')) {
                error_log("Extensión GD no disponible. Guardando sin compresión.");
                return base64_decode($imagenBase64);
            }
            
            // Remover prefijo data:image si existe
            if (strpos($imagenBase64, 'data:image') === 0) {
                $imagenBase64 = substr($imagenBase64, strpos($imagenBase64, ',') + 1);
            }
            
            $imagenData = base64_decode($imagenBase64);
            if ($imagenData === false) {
                throw new Exception("Error al decodificar imagen");
            }
            
            $imagen = imagecreatefromstring($imagenData);
            if ($imagen === false) {
                throw new Exception("Error al crear imagen desde datos");
            }
            
            // Obtener dimensiones
            $ancho = imagesx($imagen);
            $alto = imagesy($imagen);
            
            // Redimensionar si es necesario (máx 1200px)
            $maxDimension = 1200;
            if ($ancho > $alto) {
                $nuevoAncho = min($ancho, $maxDimension);
                $nuevoAlto = ($alto * $nuevoAncho) / $ancho;
            } else {
                $nuevoAlto = min($alto, $maxDimension);
                $nuevoAncho = ($ancho * $nuevoAlto) / $alto;
            }
            
            if ($nuevoAncho < $ancho || $nuevoAlto < $alto) {
                $imagenRedimensionada = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                
                // Preservar transparencia
                if (strpos($mimeType, 'png') !== false || strpos($mimeType, 'gif') !== false) {
                    imagecolortransparent($imagenRedimensionada, imagecolorallocate($imagenRedimensionada, 0, 0, 0));
                    imagealphablending($imagenRedimensionada, false);
                    imagesavealpha($imagenRedimensionada, true);
                }
                
                imagecopyresampled(
                    $imagenRedimensionada, $imagen,
                    0, 0, 0, 0,
                    $nuevoAncho, $nuevoAlto, $ancho, $alto
                );
                
                imagedestroy($imagen);
                $imagen = $imagenRedimensionada;
            }
            
            // Comprimir según tipo
            ob_start();
            if (strpos($mimeType, 'png') !== false) {
                imagepng($imagen, null, 6);
            } elseif (strpos($mimeType, 'gif') !== false) {
                imagegif($imagen, null);
            } elseif (strpos($mimeType, 'webp') !== false && function_exists('imagewebp')) {
                imagewebp($imagen, null, 80);
            } else {
                imagejpeg($imagen, null, 85);
            }
            $imagenComprimida = ob_get_contents();
            ob_end_clean();
            
            imagedestroy($imagen);
            
            // Log de compresión
            $tamañoOriginal = strlen($imagenData);
            $tamañoComprimido = strlen($imagenComprimida);
            $porcentaje = round((1 - $tamañoComprimido / $tamañoOriginal) * 100, 2);
            
            error_log("Imagen comprimida: {$ancho}x{$alto} -> {$nuevoAncho}x{$nuevoAlto}, " .
                     "Reducción: {$porcentaje}%");
            
            return $imagenComprimida;
        } catch (Exception $e) {
            error_log("Error al comprimir imagen: " . $e->getMessage());
            return base64_decode($imagenBase64);
        }
    }
    
    /**
     * Detecta si es dispositivo móvil
     */
    private function esDispositoMovil(): bool {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);
    }
    
    /**
     * Obtiene tipos de archivo válidos
     */
    public static function obtenerTiposValidos(): array {
        return [
            self::TIPO_FACTURA => 'Factura',
            self::TIPO_COMPROBANTE_TRANSFERENCIA => 'Comprobante de Transferencia',
            self::TIPO_ORDEN_PAGO => 'Orden de Pago',
            self::TIPO_RETENCION => 'Retención'
        ];
    }
}