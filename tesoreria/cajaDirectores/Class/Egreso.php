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
                        es_factura, COD_COMP, N_COMP, fecha, motivo, 
                        nombre_director, importe, observaciones, 
                        recibido, fecha_carga, foto, centro_costo,
                        proveedor, tipo_gasto
                    ) VALUES (
                        NULL, ?, ?, ?, ?,
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
        
        // --- Lógica de filtrado mejorada ---

        // Condición especial para buscar Pagos de Servicios
        if (!empty($filtros['tipo_gasto_especifico']) && $filtros['tipo_gasto_especifico'] === 'Servicios') {
            
            $sql .= " AND e.tipo_gasto = 'Servicios'";
            
            // Para servicios, el filtro de fecha se aplica sobre `fecha_carga`
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND CAST(e.fecha_carga AS DATE) >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND CAST(e.fecha_carga AS DATE) <= ?";
                $params[] = $filtros['fecha_hasta'];
            }

        } else {
            // Lógica original para otros tipos de egresos (que no son servicios)
            
            // Por defecto, excluir tipo_gasto = 'Servicios'
            $sql .= " AND (e.tipo_gasto IS NULL OR e.tipo_gasto != 'Servicios')";
            
            // Por defecto, excluir gastos (COD_COMP='GAS') a menos que se especifique incluirlos
            if (!isset($filtros['incluir_gastos']) || $filtros['incluir_gastos'] !== true) {
                $sql .= " AND e.COD_COMP != 'GAS'";
            }
            // Por defecto, excluir COMPENSACION_IVA a menos que se especifique incluirlos
            if (!isset($filtros['incluir_compensacion_iva']) || $filtros['incluir_compensacion_iva'] !== true) {
                $sql .= " AND e.motivo != 'COMPENSACION_IVA'";
            }
            
            // Para otros egresos, el filtro de fecha se aplica sobre la `fecha` del egreso
            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND e.fecha >= ?";
                $params[] = $filtros['fecha_desde'];
            }
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND e.fecha <= ?";
                $params[] = $filtros['fecha_hasta'];
            }
        }
        
        // --- Filtros comunes que aplican a todos los casos ---
        
        if (!empty($filtros['nombre_director'])) {
            $sql .= " AND e.nombre_director = ?";
            $params[] = $filtros['nombre_director'];
        }
        
        if (!empty($filtros['motivo'])) {
            $sql .= " AND e.motivo = ?";
            $params[] = $filtros['motivo'];
        }
        
        if (!empty($filtros['proveedor'])) {
            $sql .= " AND e.proveedor = ?";
            $params[] = $filtros['proveedor'];
        }
        
        // Ordenamiento consistente
        $sql .= " ORDER BY e.fecha_carga DESC, e.id DESC";
        
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
                      AND (tipo_gasto IS NULL OR tipo_gasto != 'Servicios')
                      AND fecha >= ?";
            $stmt = sqlsrv_query($this->db, $sql, [$fechaInicioApp]);
            
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
    public function comprimirImagen($archivoBase64Completo) {
        try {
            // --- INICIO DE LA CORRECCIÓN ---
            // Primero, verificamos si el archivo es un PDF por su prefijo.
            // Si es así, no intentamos procesarlo como imagen.
            if (strpos($archivoBase64Completo, 'data:application/pdf') === 0) {
                // Es un PDF, simplemente extraemos el contenido base64 y lo devolvemos.
                error_log("Archivo detectado como PDF. Se omitirá la compresión de imagen.");
                return substr($archivoBase64Completo, strpos($archivoBase64Completo, ',') + 1);
            }
            // --- FIN DE LA CORRECCIÓN ---

            // Verificar si la extensión GD está disponible
            if (!extension_loaded('gd')) {
                error_log("Extensión GD no disponible. Guardando imagen sin compresión.");
                if (strpos($archivoBase64Completo, 'data:image') === 0) {
                    return substr($archivoBase64Completo, strpos($archivoBase64Completo, ',') + 1);
                }
                return $archivoBase64Completo; // Devuelve la data base64 si no tiene prefijo
            }
            
            // Extraer información del tipo de imagen y los datos
            $tipoImagen = 'jpeg'; // Valor por defecto
            $imagenBase64 = $archivoBase64Completo;
            if (strpos($archivoBase64Completo, 'data:image') === 0) {
                preg_match('/data:image\/([^;]+)/', $archivoBase64Completo, $matches);
                $tipoImagen = isset($matches[1]) ? $matches[1] : 'jpeg';
                $imagenBase64 = substr($archivoBase64Completo, strpos($archivoBase64Completo, ',') + 1);
            }
            
            // Decodificar base64
            $imagenData = base64_decode($imagenBase64);
            if ($imagenData === false) {
                throw new Exception("Error al decodificar imagen base64");
            }
            
            // Crear imagen desde string
            $imagen = imagecreatefromstring($imagenData);
            if ($imagen === false) {
                // Si llegamos aquí, es un formato de imagen no soportado por GD
                throw new Exception("Error al crear imagen desde datos. Formato de imagen no soportado: " . $tipoImagen);
            }
            
            // Obtener dimensiones originales
            $ancho = imagesx($imagen);
            $alto = imagesy($imagen);
            
            // Calcular nuevas dimensiones (máximo 1200px)
            $maxDimension = 1200;
            $nuevoAncho = $ancho;
            $nuevoAlto = $alto;

            if ($ancho > $maxDimension || $alto > $maxDimension) {
                if ($ancho > $alto) {
                    $nuevoAncho = $maxDimension;
                    $nuevoAlto = floor($alto * ($maxDimension / $ancho));
                } else {
                    $nuevoAlto = $maxDimension;
                    $nuevoAncho = floor($ancho * ($maxDimension / $alto));
                }
            }
            
            // Solo redimensionar si es necesario
            if ($nuevoAncho < $ancho || $nuevoAlto < $alto) {
                $imagenRedimensionada = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                
                // Preservar transparencia para PNG y GIF
                if ($tipoImagen === 'png' || $tipoImagen === 'gif') {
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
            
            // Convertir a formato optimizado (JPEG o WebP)
            ob_start();
            
            switch($tipoImagen) {
                case 'png': imagepng($imagen, null, 6); break;
                case 'gif': imagegif($imagen, null); break;
                case 'webp': 
                    if (function_exists('imagewebp')) { imagewebp($imagen, null, 80); } 
                    else { imagejpeg($imagen, null, 85); } 
                    break;
                default: imagejpeg($imagen, null, 85); break;
            }
            
            $imagenComprimida = ob_get_contents();
            ob_end_clean();
            
            imagedestroy($imagen);
            
            $resultado = base64_encode($imagenComprimida);
            
            $tamañoOriginal = strlen($imagenData);
            $tamañoComprimido = strlen($imagenComprimida);
            if ($tamañoOriginal > 0) {
                 $porcentajeReduccion = round((1 - $tamañoComprimido / $tamañoOriginal) * 100, 2);
                 error_log("Imagen comprimida: {$ancho}x{$alto} -> {$nuevoAncho}x{$nuevoAlto}, " .
                     "Tamaño: " . number_format($tamañoOriginal/1024, 2) . "KB -> " . 
                     number_format($tamañoComprimido/1024, 2) . "KB ({$porcentajeReduccion}% reducción)");
            }

            return $resultado;
            
        } catch (Exception $e) {
            error_log("Error al comprimir imagen: " . $e->getMessage());
            
            // Si falla, devolver la imagen original sin el prefijo data:
            if (preg_match('/^data:[^;]+;base64,/', $archivoBase64Completo, $matches)) {
                return substr($archivoBase64Completo, strlen($matches[0]));
            }
            return $archivoBase64Completo;
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
                        es_factura, COD_COMP, N_COMP, fecha, motivo, 
                        nombre_director, proveedor, tipo_gasto, importe, 
                        observaciones, foto, recibido, fecha_carga,
                        centro_costo
                    ) VALUES (
                        ?, 'GAS', ?, ?, 'PROVEEDORES',
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
                $datos['es_factura'] ?? 0,
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
public function eliminar($id): bool {
    // Iniciar la transacción
    if (sqlsrv_begin_transaction($this->db) === false) {
        error_log("Error al iniciar la transacción: " . print_r(sqlsrv_errors(), true));
        return false;
    }

    try {
        // 1. Borrar de la tabla de proveedores si existe
        $sqlProveedor = "DELETE FROM FT_T_PROVEEDORES WHERE id_egresos = ?";
        $stmtProveedor = sqlsrv_query($this->db, $sqlProveedor, [$id]);
        
        if ($stmtProveedor === false) {
            sqlsrv_rollback($this->db);
            throw new Exception("Error al eliminar datos del proveedor: " . print_r(sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt($stmtProveedor);

        // 2. Borrar el registro principal de egresos
        $sqlEgreso = "DELETE FROM egresos WHERE id = ?";
        $stmtEgreso = sqlsrv_query($this->db, $sqlEgreso, [$id]);

        if ($stmtEgreso === false) {
            sqlsrv_rollback($this->db);
            throw new Exception("Error al eliminar el egreso principal: " . print_r(sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt($stmtEgreso);

        // --- CORRECCIÓN CLAVE ---
        // Si llegamos hasta aquí sin errores, la operación fue exitosa.
        // No confiamos en sqlsrv_rows_affected().
        sqlsrv_commit($this->db);
        return true;

    } catch (Exception $e) {
        // En caso de cualquier excepción, asegurarse de deshacer la transacción
        sqlsrv_rollback($this->db);
        error_log("Error en transacción de eliminación para ID {$id}: " . $e->getMessage());
        return false;
    }
}
public function obtenerPorId($id) {
    try {
        $sql = "SELECT e.*,
                       p.NOM_PROVEE as proveedor_nom,
                       p.CBU as proveedor_cbu,
                       p.DESCRIPCION_CBU as proveedor_descripcion_cbu
                FROM egresos e
                LEFT JOIN FT_T_PROVEEDORES p ON e.id = p.id_egresos
                WHERE e.id = ?";
        
        $stmt = sqlsrv_query($this->db, $sql, [$id]);
        if ($stmt === false) {
            throw new Exception("Error al obtener el pago: " . print_r(sqlsrv_errors(), true));
        }
        
        $pago = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        
        // Formatear la fecha para que sea compatible con input type="date"
        if ($pago && $pago['fecha'] instanceof DateTime) {
            $pago['fecha'] = $pago['fecha']->format('Y-m-d');
        }

        return $pago;

    } catch (Exception $e) {
        error_log("Error al obtener egreso por ID {$id}: " . $e->getMessage());
        return null;
    }
}

/**
 * Actualiza un egreso existente y sus datos de proveedor asociados.
 */
public function actualizar($id, $datos) {
    if (sqlsrv_begin_transaction($this->db) === false) {
        throw new Exception("Error al iniciar la transacción de actualización.");
    }

    try {
        // 1. Actualizar la tabla principal de egresos
        $sqlEgreso = "UPDATE egresos SET 
                        nombre_director = ?,
                        motivo = ?,
                        fecha = ?,
                        importe = ?,
                        observaciones = ?,
                        proveedor = ?,
                        foto = IIF(? IS NOT NULL, ?, foto) -- Solo actualiza la foto si se envía una nueva
                      WHERE id = ?";
        
        $paramsEgreso = [
            $datos['nombre_director'],
            $datos['motivo'],
            $datos['fecha_vencimiento'],
            $datos['importe'],
            $datos['observaciones'],
            $datos['proveedor'],
            $datos['foto'], $datos['foto'], // Se usa dos veces para la condición IIF
            $id
        ];
        
        $stmtEgreso = sqlsrv_query($this->db, $sqlEgreso, $paramsEgreso);
        if ($stmtEgreso === false) {
            throw new Exception("Error al actualizar el egreso: " . print_r(sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt($stmtEgreso);

        // 2. Manejar datos del proveedor si el motivo es "Pago de seguros"
        if ($datos['motivo'] === 'Pago de seguros') {
            // Intentar actualizar primero
            $sqlUpdProv = "UPDATE FT_T_PROVEEDORES SET NOM_PROVEE = ?, CBU = ?, DESCRIPCION_CBU = ? WHERE id_egresos = ?";
            $paramsProv = [
                $datos['nom_provee'],
                $datos['cbu'] ?? '',
                $datos['descripcion_cbu'] ?? '',
                $id
            ];
            $stmtUpdProv = sqlsrv_query($this->db, $sqlUpdProv, $paramsProv);
            if ($stmtUpdProv === false) throw new Exception("Error al actualizar proveedor.");

            // Si no se afectó ninguna fila, significa que no existía, entonces lo insertamos
if (sqlsrv_rows_affected($stmtUpdProv) === 0) {
    $sqlInsProv = "INSERT INTO FT_T_PROVEEDORES (id_egresos, NOM_PROVEE, CBU, DESCRIPCION_CBU) VALUES (?, ?, ?, ?)";
    
    // --- ESTA ES LA CORRECCIÓN ---
    // Construimos el array de parámetros de forma manual para máxima compatibilidad
    $paramsInsertProv = [
        $id,
        $datos['nom_provee'],
        $datos['cbu'] ?? '',
        $datos['descripcion_cbu'] ?? ''
    ];
    // --- FIN DE LA CORRECCIÓN ---

    $stmtInsProv = sqlsrv_query($this->db, $sqlInsProv, $paramsInsertProv); // Usamos $this->db
    if ($stmtInsProv === false) {
        throw new Exception("Error al insertar proveedor: " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmtInsProv);
}
            sqlsrv_free_stmt($stmtUpdProv);
        } else {
            // Si el motivo ya no es "Pago de seguros", borrar el registro de proveedor asociado
            $sqlDelProv = "DELETE FROM FT_T_PROVEEDORES WHERE id_egresos = ?";
            $stmtDelProv = sqlsrv_query($this->db, $sqlDelProv, [$id]);
            if ($stmtDelProv === false) throw new Exception("Error al limpiar datos de proveedor.");
            sqlsrv_free_stmt($stmtDelProv);
        }

        sqlsrv_commit($this->db);
        return true;

    } catch (Exception $e) {
        sqlsrv_rollback($this->db);
        error_log("Error al actualizar pago ID {$id}: " . $e->getMessage());
        return false;
    }
}
}
