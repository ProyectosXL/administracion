<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Director.php';

/**
 * Clase Egreso
 * Gestiona las operaciones CRUD de egresos de caja
 */
class Egreso {
    private $db;
    private $director;
    
    // Constantes para motivos de egreso
    public const MOTIVO_SUELDOS = 'SUELDOS';
    public const MOTIVO_PROVEEDORES = 'PROVEEDORES';
    public const MOTIVO_RETIROS = 'RETIROS';
    
    public function __construct() {
        $this->db = Database::getInstance()->getAppsConnection();
        $this->director = new Director();
    }
    
    /**
     * Genera el próximo número de comprobante
     */
    private function generarNumeroComprobante() {
        $sql = "SELECT MAX(CAST(N_COMP AS BIGINT)) as max_comp 
                FROM egresos 
                WHERE COD_COMP = 'EGR'";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
        }
        
        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        
        $ultimoNumero = $result['max_comp'] ?? 10000000000;
        $nuevoNumero = $ultimoNumero + 1;
        
        return str_pad($nuevoNumero, 11, '0', STR_PAD_LEFT);
    }
    
    /**
     * Crea un nuevo egreso
     */
    public function crear($datos) {
        try {
            $sql = "INSERT INTO egresos (
                        ID_SBA05, COD_COMP, N_COMP, fecha, motivo, 
                        nombre_director, importe, observaciones, 
                        recibido, fecha_carga, foto
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, 
                        1, GETDATE(), ?
                    )";
            
            $nComp = $this->generarNumeroComprobante();
            
            // Validar director si es retiro de socio
            $nombreDirector = null;
            if ($datos['motivo'] === self::MOTIVO_RETIROS) {
                if (empty($datos['nombre_director']) || 
                    !$this->director->existeDirector($datos['nombre_director'])) {
                    throw new Exception("Director no válido");
                }
                $nombreDirector = $datos['nombre_director'];
            }
            
            // Procesar foto si se proporciona
            $fotoComprimida = null;
            if (!empty($datos['foto'])) {
                $fotoComprimida = $this->comprimirImagen($datos['foto']);
            }
            
            $params = [
                $datos['id_sba05'] ?? null,
                'EGR',
                $nComp,
                $datos['fecha'],
                $datos['motivo'],
                $nombreDirector,
                $datos['importe'],
                $datos['observaciones'] ?? '',
                $fotoComprimida
            ];
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Error al crear egreso: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene todos los egresos con filtros opcionales
     */
    public function obtenerTodos($filtros = []) {
        try {
            $sql = "SELECT *, CAST(fecha_carga AS DATE) as fecha_solo, 
                           CASE WHEN foto IS NOT NULL THEN 1 ELSE 0 END as tiene_foto 
                    FROM egresos WHERE 1=1";
            $params = [];
            
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND fecha >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND fecha <= ?";
                $params[] = $filtros['fecha_hasta'];
            }
            
            if (!empty($filtros['motivo'])) {
                $sql .= " AND motivo = ?";
                $params[] = $filtros['motivo'];
            }
            
            $sql .= " ORDER BY fecha DESC, id DESC";
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $resultados = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultados[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            return $resultados;
        } catch (Exception $e) {
            error_log("Error al obtener egresos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene el total de egresos
     */
    public function obtenerTotal(): float {
        try {
            $sql = "SELECT COALESCE(SUM(importe), 0) as total FROM egresos";
            $stmt = sqlsrv_query($this->db, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Comprime imagen base64 para almacenamiento optimizado
     * Soporta múltiples formatos: JPEG, PNG, GIF, BMP, WebP
     */
    private function comprimirImagen($imagenBase64) {
        try {
            // Verificar si la extensión GD está disponible
            if (!extension_loaded('gd')) {
                error_log("Extensión GD no disponible. Guardando imagen sin compresión.");
                
                // Remover el prefijo data:image si existe y devolver solo base64
                if (strpos($imagenBase64, 'data:image') === 0) {
                    return substr($imagenBase64, strpos($imagenBase64, ',') + 1);
                }
                return $imagenBase64;
            }
            
            // Extraer información del tipo de imagen
            $tipoImagen = '';
            if (strpos($imagenBase64, 'data:image') === 0) {
                preg_match('/data:image\/([^;]+)/', $imagenBase64, $matches);
                $tipoImagen = isset($matches[1]) ? $matches[1] : 'jpeg';
                $imagenBase64 = substr($imagenBase64, strpos($imagenBase64, ',') + 1);
            }
            
            // Decodificar base64
            $imagenData = base64_decode($imagenBase64);
            if ($imagenData === false) {
                throw new Exception("Error al decodificar imagen base64");
            }
            
            // Crear imagen desde string (soporta JPEG, PNG, GIF, BMP, WebP automáticamente)
            $imagen = imagecreatefromstring($imagenData);
            if ($imagen === false) {
                throw new Exception("Error al crear imagen desde datos. Formato no soportado: " . $tipoImagen);
            }
            
            // Obtener dimensiones originales
            $ancho = imagesx($imagen);
            $alto = imagesy($imagen);
            
            // Calcular nuevas dimensiones (máximo 1200px para fotos de móvil de alta calidad)
            $maxDimension = 1200;
            if ($ancho > $alto) {
                $nuevoAncho = min($ancho, $maxDimension);
                $nuevoAlto = ($alto * $nuevoAncho) / $ancho;
            } else {
                $nuevoAlto = min($alto, $maxDimension);
                $nuevoAncho = ($ancho * $nuevoAlto) / $alto;
            }
            
            // Solo redimensionar si es necesario
            if ($nuevoAncho < $ancho || $nuevoAlto < $alto) {
                // Crear imagen redimensionada
                $imagenRedimensionada = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                
                // Preservar transparencia para PNG y GIF
                if ($tipoImagen === 'png' || $tipoImagen === 'gif') {
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
            
            // Convertir según el tipo original, pero optimizar para web
            ob_start();
            
            switch($tipoImagen) {
                case 'png':
                    imagepng($imagen, null, 6); // Compresión PNG nivel 6
                    break;
                case 'gif':
                    imagegif($imagen, null);
                    break;
                case 'webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($imagen, null, 80); // 80% calidad WebP
                    } else {
                        imagejpeg($imagen, null, 85); // Fallback a JPEG
                    }
                    break;
                default:
                    imagejpeg($imagen, null, 85); // 85% calidad JPEG (mejor para fotos)
                    break;
            }
            
            $imagenComprimida = ob_get_contents();
            ob_end_clean();
            
            // Limpiar memoria
            imagedestroy($imagen);
            
            // Retornar base64 comprimido
            $resultado = base64_encode($imagenComprimida);
            
            // Log de información de compresión
            $tamañoOriginal = strlen($imagenData);
            $tamañoComprimido = strlen($imagenComprimida);
            $porcentajeReduccion = round((1 - $tamañoComprimido / $tamañoOriginal) * 100, 2);
            
            error_log("Imagen comprimida: {$ancho}x{$alto} -> {$nuevoAncho}x{$nuevoAlto}, " .
                     "Tamaño: " . number_format($tamañoOriginal/1024, 2) . "KB -> " . 
                     number_format($tamañoComprimido/1024, 2) . "KB ({$porcentajeReduccion}% reducción)");
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Error al comprimir imagen: " . $e->getMessage());
            
            // Si falla, remover prefijo y devolver imagen original
            if (strpos($imagenBase64, 'data:image') === 0) {
                return substr($imagenBase64, strpos($imagenBase64, ',') + 1);
            }
            return $imagenBase64;
        }
    }
    
    /**
     * Obtiene la foto de un egreso específico
     */
    public function obtenerFoto($id): ?string {
        try {
            $sql = "SELECT foto FROM egresos WHERE id = ?";
            $stmt = sqlsrv_query($this->db, $sql, [$id]);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return $result ? $result['foto'] : null;
        } catch (Exception $e) {
            error_log("Error al obtener foto: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtiene la lista de directores desde la base de datos
     */
    public function obtenerDirectores() {
        return $this->director->obtenerDirectores();
    }
}
