<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Director.php';
require_once __DIR__ . '/Config.php';

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
    public const MOTIVO_COMPENSACION_IVA = 'COMPENSACION_IVA';
    public const MOTIVO_AJUSTE = 'AJUSTE';
    
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
                        recibido, fecha_carga, foto, centro_costo,
                        proveedor, tipo_gasto
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, 
                        1, GETDATE(), ?, ?,
                        ?, ?
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
            
            // Validar director si es compensación IVA
            if ($datos['motivo'] === self::MOTIVO_COMPENSACION_IVA) {
                if (empty($datos['nombre_director']) || 
                    !$this->director->existeDirector($datos['nombre_director'])) {
                    throw new Exception("Director no válido");
                }
                $nombreDirector = $datos['nombre_director'];
            }
            
            // Validar centro de costo si es para sueldos
            $centroCosto = null;
            if ($datos['motivo'] === self::MOTIVO_SUELDOS) {
                if (!empty($datos['centro_costo'])) {
                    $centroCosto = $datos['centro_costo'];
                }
            }
            
            // Validar proveedor y tipo de gasto si es pago a proveedores
            $proveedor = null;
            $tipoGasto = null;
            if ($datos['motivo'] === self::MOTIVO_PROVEEDORES) {
                if (!empty($datos['proveedor'])) {
                    $proveedor = $datos['proveedor'];
                }
                if (!empty($datos['tipo_gasto'])) {
                    $tipoGasto = $datos['tipo_gasto'];
                }
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
                $fotoComprimida,
                $centroCosto,
                $proveedor,
                $tipoGasto
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
            $sql = "SELECT e.*, 
                           CAST(e.fecha_carga AS DATE) as fecha_solo, 
                           CASE WHEN e.foto IS NOT NULL THEN 1 ELSE 0 END as tiene_foto,
                           p.NOM_PROVEE as proveedor_nom,
                           p.CBU as proveedor_cbu,
                           p.DESCRIPCION_CBU as proveedor_descripcion_cbu
                    FROM egresos e
                    LEFT JOIN FT_T_PROVEEDORES p ON e.id = p.id_egresos
                    WHERE 1=1";
            $params = [];
            
            // Por defecto, excluir gastos (COD_COMP='GAS') a menos que se especifique incluirlos
            if (!isset($filtros['incluir_gastos']) || $filtros['incluir_gastos'] !== true) {
                $sql .= " AND e.COD_COMP != 'GAS'";
            }
            
            // Por defecto, excluir COMPENSACION_IVA a menos que se especifique incluirlos
            if (!isset($filtros['incluir_compensacion_iva']) || $filtros['incluir_compensacion_iva'] !== true) {
                $sql .= " AND e.motivo != 'COMPENSACION_IVA'";
            }
            
            if (!empty($filtros['fecha_desde'])) {
                // Para pagos de servicios, filtrar por fecha_carga; para otros, por fecha
                $sql .= " AND (
                    (e.motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas', 'Pago de tarjetas', 'Transf. Haberes', 'Otros') AND CAST(e.fecha_carga AS DATE) >= ?)
                    OR
                    (e.motivo NOT IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas', 'Pago de tarjetas', 'Transf. Haberes', 'Otros') AND e.fecha >= ?)
                )";
                $params[] = $filtros['fecha_desde'];
                $params[] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                // Para pagos de servicios, filtrar por fecha_carga; para otros, por fecha
                $sql .= " AND (
                    (e.motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas', 'Pago de tarjetas', 'Transf. Haberes', 'Otros') AND CAST(e.fecha_carga AS DATE) <= ?)
                    OR
                    (e.motivo NOT IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas', 'Pago de tarjetas', 'Transf. Haberes', 'Otros') AND e.fecha <= ?)
                )";
                $params[] = $filtros['fecha_hasta'];
                $params[] = $filtros['fecha_hasta'];
            }
            
            if (!empty($filtros['motivo'])) {
                $sql .= " AND e.motivo = ?";
                $params[] = $filtros['motivo'];
            }
            
            if (!empty($filtros['proveedor'])) {
                $sql .= " AND e.proveedor = ?";
                $params[] = $filtros['proveedor'];
            }
            
            $sql .= " ORDER BY e.fecha DESC, e.id DESC";
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $resultados = [];
            $dbCentral = Database::getInstance()->getCentralConnection();
            
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Agregar nombres de centro de costo y proveedor
                if (!empty($row['centro_costo'])) {
                    $sqlCentro = "SELECT CENTRO_COSTO FROM RO_T_CENTRO_DE_COSTOS WHERE COD_AUXILIAR = ?";
                    $stmtCentro = sqlsrv_query($dbCentral, $sqlCentro, [$row['centro_costo']]);
                    if ($stmtCentro && $rowCentro = sqlsrv_fetch_array($stmtCentro, SQLSRV_FETCH_ASSOC)) {
                        $row['centro_costo_nombre'] = $rowCentro['CENTRO_COSTO'];
                    }
                }
                
                if (!empty($row['proveedor'])) {
                    $sqlProv = "SELECT NOM_PROVEE FROM RO_V_PROVEEDORES_EGRE_DIRECTORES WHERE COD_PROVEE = ?";
                    $stmtProv = sqlsrv_query($dbCentral, $sqlProv, [$row['proveedor']]);
                    if ($stmtProv && $rowProv = sqlsrv_fetch_array($stmtProv, SQLSRV_FETCH_ASSOC)) {
                        $row['proveedor_nombre'] = $rowProv['NOM_PROVEE'];
                    }
                }
                
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
     * Obtiene el total de egresos desde la fecha de inicio de la app (excluyendo gastos COD_COMP='GAS' y COMPENSACION_IVA)
     */
    public function obtenerTotal(): float {
        try {
            // Aplicar filtro de fecha de inicio de la app
            $fechaInicioApp = Config::getFechaInicioApp();
            
            $sql = "SELECT COALESCE(SUM(importe), 0) as total 
                    FROM egresos 
                    WHERE COD_COMP != 'GAS'
                      AND motivo != 'COMPENSACION_IVA'
                      AND (
                          (motivo IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas', 'Pago de tarjetas', 'Transf. Haberes', 'Otros') 
                           AND CAST(fecha_carga AS DATE) >= ?)
                          OR
                          (motivo NOT IN ('Pago de seguros', 'Pago de patentes', 'Pago de expensas', 'Pago de tarjetas', 'Transf. Haberes', 'Otros') 
                           AND fecha >= ?)
                      )";
            $stmt = sqlsrv_query($this->db, $sql, [$fechaInicioApp, $fechaInicioApp]);
            
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
     * Obtiene el total de egresos de un proveedor específico (para Reporte Alberto)
     */
    public function obtenerTotalProveedor($codProvee): float {
        try {
            $sql = "SELECT COALESCE(SUM(importe), 0) as total 
                    FROM egresos 
                    WHERE proveedor = ?";
            $stmt = sqlsrv_query($this->db, $sql, [$codProvee]);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total proveedor: " . $e->getMessage());
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
    public function obtenerFoto($id): ?array {
        try {
            $sql = "SELECT foto FROM egresos WHERE id = ?";
            $stmt = sqlsrv_query($this->db, $sql, [$id]);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if (!$result || !$result['foto']) {
                return null;
            }
            
            // Detectar tipo de archivo desde el contenido
            $fotoCompleta = $result['foto'];
            $tipo = 'image/jpeg'; // Por defecto
            $foto = $fotoCompleta;
            
            // Si el dato ya incluye el prefijo data:mime;base64, extraerlo
            if (strpos($fotoCompleta, 'data:') === 0) {
                // Formato: data:application/pdf;base64,JVBERi0...
                preg_match('/^data:([^;]+);base64,(.+)$/', $fotoCompleta, $matches);
                if ($matches) {
                    $tipo = $matches[1];
                    $foto = $matches[2];
                }
            } else {
                // Es base64 puro, detectar por contenido
                // Los PDFs en base64 empiezan con "JVBERi0" (que es "%PDF-" en base64)
                if (strpos($foto, 'JVBERi0') === 0) {
                    $tipo = 'application/pdf';
                }
                // Las imágenes JPEG empiezan con "/9j/"
                else if (strpos($foto, '/9j/') === 0) {
                    $tipo = 'image/jpeg';
                }
                // Las imágenes PNG empiezan con "iVBORw0KGgo"
                else if (strpos($foto, 'iVBORw0KGgo') === 0) {
                    $tipo = 'image/png';
                }
            }
            
            return [
                'foto' => $foto,
                'tipo' => $tipo
            ];
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
    
    /**
     * Obtiene la lista de centros de costo desde la base CENTRAL
     * Retorna array con COD_AUXILIAR y CENTRO_COSTO
     */
    public function obtenerCentrosCosto() {
        try {
            $dbCentral = Database::getInstance()->getCentralConnection();
            $sql = "SELECT COD_AUXILIAR, CENTRO_COSTO 
                    FROM RO_T_CENTRO_DE_COSTOS 
                    ORDER BY CENTRO_COSTO";
            $stmt = sqlsrv_query($dbCentral, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $centros = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $centros[] = [
                    'cod_auxiliar' => trim($row['COD_AUXILIAR']),
                    'centro_costo' => trim($row['CENTRO_COSTO'])
                ];
            }
            
            sqlsrv_free_stmt($stmt);
            return $centros;
        } catch (Exception $e) {
            error_log("Error al obtener centros de costo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene la lista de proveedores desde la base CENTRAL
     * Retorna array con COD_PROVEE y NOM_PROVEE
     */
    public function obtenerProveedores() {
        try {
            $dbCentral = Database::getInstance()->getCentralConnection();
            $sql = "SELECT COD_PROVEE, NOM_PROVEE 
                    FROM RO_V_PROVEEDORES_EGRE_DIRECTORES 
                    ORDER BY NOM_PROVEE";
            $stmt = sqlsrv_query($dbCentral, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $proveedores = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $proveedores[] = [
                    'cod_provee' => trim($row['COD_PROVEE']),
                    'nom_provee' => trim($row['NOM_PROVEE'])
                ];
            }
            
            sqlsrv_free_stmt($stmt);
            return $proveedores;
        } catch (Exception $e) {
            error_log("Error al obtener proveedores: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Crea un nuevo gasto (COD_COMP = 'GAS') con numeración independiente
     */
    public function crearGasto($datos) {
        try {
            // Generar número de comprobante para GAS
            $nComp = $this->generarNumeroComprobanteGAS();
            
            $sql = "INSERT INTO egresos (
                        ID_SBA05, COD_COMP, N_COMP, fecha, motivo, 
                        nombre_director, proveedor, tipo_gasto, importe, 
                        observaciones, foto, recibido, fecha_carga,
                        centro_costo
                    ) VALUES (
                        NULL, 'GAS', ?, ?, 'PROVEEDORES',
                        NULL, 'OGROLL', ?, ?,
                        ?, ?, 1, GETDATE(),
                        ?
                    )";
            
            // Procesar foto si se proporciona
            $fotoComprimida = null;
            if (!empty($datos['foto'])) {
                $fotoComprimida = $this->comprimirImagen($datos['foto']);
            }
            
            $params = [
                $nComp,
                $datos['fecha'],
                $datos['tipo_gasto'],
                $datos['importe'],
                $datos['observaciones'] ?? '',
                $fotoComprimida,
                $datos['centro_costo'] ?? null
            ];
            
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmt);
            return true;
        } catch (Exception $e) {
            error_log("Error al crear gasto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Genera el próximo número de comprobante para GAS (numeración independiente)
     */
    private function generarNumeroComprobanteGAS() {
        $sql = "SELECT MAX(CAST(N_COMP AS BIGINT)) as max_comp 
                FROM egresos 
                WHERE COD_COMP = 'GAS'";
        $stmt = sqlsrv_query($this->db, $sql);
        
        if ($stmt === false) {
            throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
        }
        
        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        
        $ultimoNumero = $result['max_comp'] ?? 0;
        $nuevoNumero = $ultimoNumero + 1;
        
        return str_pad($nuevoNumero, 11, '0', STR_PAD_LEFT);
    }
    
    /**
     * Obtiene todos los gastos (COD_COMP = 'GAS')
     */
    public function obtenerGastos($filtros = []) {
        try {
            $sql = "SELECT *, CAST(fecha_carga AS DATE) as fecha_solo,
                           CASE WHEN foto IS NOT NULL THEN 1 ELSE 0 END as tiene_foto 
                    FROM egresos 
                    WHERE COD_COMP = 'GAS'";
            $params = [];
            
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND fecha >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND fecha <= ?";
                $params[] = $filtros['fecha_hasta'];
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
            error_log("Error al obtener gastos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene el total de gastos desde la fecha de inicio de la app (COD_COMP = 'GAS')
     */
    public function obtenerTotalGastos() {
        try {
            // Aplicar filtro de fecha de inicio de la app
            $fechaInicioApp = Config::getFechaInicioApp();
            
            $sql = "SELECT ISNULL(SUM(importe), 0) as total 
                    FROM egresos 
                    WHERE COD_COMP = 'GAS'
                      AND fecha >= ?";
            
            $stmt = sqlsrv_query($this->db, $sql, [$fechaInicioApp]);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error al obtener total de gastos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtiene el total de egresos de un proveedor excluyendo gastos (COD_COMP != 'GAS')
     */
    public function obtenerTotalProveedorSinGastos($proveedor) {
        try {
            $sql = "SELECT ISNULL(SUM(importe), 0) as total 
                    FROM egresos 
                    WHERE proveedor = ? AND COD_COMP != 'GAS'";
            
            $stmt = sqlsrv_query($this->db, $sql, [$proveedor]);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error al obtener total de proveedor sin gastos: " . $e->getMessage());
            return 0;
        }
    }
}
