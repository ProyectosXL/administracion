<?php
require_once __DIR__ . '/../../../class/conexion.php';
require_once __DIR__ . '/Director.php';
require_once __DIR__ . '/Config.php';

/**
 * Clase Egreso
 * Gestiona las operaciones CRUD de egresos de caja
 */
class Egreso
{
    private $db;
    private $conexion;
    private $director;
    private static $sharedConexion = null;

    // Constantes para motivos de egreso
    public const MOTIVO_SUELDOS = 'SUELDOS';
    public const MOTIVO_PROVEEDORES = 'PROVEEDORES';
    public const MOTIVO_RETIROS = 'RETIROS';
    public const MOTIVO_COMPENSACION_IVA = 'COMPENSACION_IVA';
    public const MOTIVO_AJUSTE = 'AJUSTE';
    public const MOTIVO_GASTOS_DIRECTORES = 'GASTOS_DIRECTORES';

    public function __construct()
    {
        // Reutilizar instancia de conexión si existe
        if (self::$sharedConexion === null) {
            self::$sharedConexion = new Conexion();
        }
        $this->conexion = self::$sharedConexion;

        $this->db = $this->conexion->conectar('apps');

        if ($this->db === false) {
            throw new Exception("Error al conectar con la base de datos APPS en Egreso");
        }

        $this->director = new Director($this->db);
    }

    /**
     * Obtiene la instancia de conexión compartida
     */
    public function getConexion()
    {
        return $this->conexion;
    }

    /**
     * Genera el próximo número de comprobante
     */
    private function generarNumeroComprobante()
    {
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
    public function crear($datos)
    {
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
                if (
                    empty($datos['nombre_director']) ||
                    !$this->director->existeDirector($datos['nombre_director'])
                ) {
                    throw new Exception("Director no válido");
                }
                $nombreDirector = $datos['nombre_director'];
            }

            // Validar director si es compensación IVA
            if ($datos['motivo'] === self::MOTIVO_COMPENSACION_IVA) {
                if (
                    empty($datos['nombre_director']) ||
                    !$this->director->existeDirector($datos['nombre_director'])
                ) {
                    throw new Exception("Director no válido");
                }
                $nombreDirector = $datos['nombre_director'];
            }

            // Validar director si es gastos de directores
            if ($datos['motivo'] === self::MOTIVO_GASTOS_DIRECTORES) {
                if (
                    empty($datos['nombre_director']) ||
                    !$this->director->existeDirector($datos['nombre_director'])
                ) {
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

            // Combinar el INSERT con el SELECT SCOPE_IDENTITY() en el mismo lote (batch)
            // para asegurar que SCOPE_IDENTITY() devuelva el ID correcto en el mismo ámbito.
            $sql .= "; SELECT SCOPE_IDENTITY() as id";
            $stmt = sqlsrv_query($this->db, $sql, $params);

            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            // Moverse al siguiente resultado para obtener el ID generado
            sqlsrv_next_result($stmt);
            $rowId = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $idEgreso = $rowId['id'] ?? null;

            if ($idEgreso === null) {
                error_log("Advertencia: No se pudo obtener el ID del egreso recién creado, pero la consulta no dio error.");
            }

            // Guardar archivos adicionales si existen
            if (!empty($datos['fotos']) && is_array($datos['fotos'])) {
                $this->guardarArchivos($idEgreso, $datos['fotos']);
            }

            sqlsrv_free_stmt($stmt);
            return $idEgreso; // Retornar ID en lugar de true para ser más útil
        } catch (Exception $e) {
            error_log("Error al crear egreso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los egresos con filtros opcionales
     */
    public function obtenerTodos($filtros = [])
    {
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
                // Por defecto, excluir GASTOS_DIRECTORES (no impacta saldo de caja)
                if (!isset($filtros['incluir_gastos_directores']) || $filtros['incluir_gastos_directores'] !== true) {
                    $sql .= " AND e.motivo != 'GASTOS_DIRECTORES'";
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
            $dbCentral = $this->conexion->conectar('central');

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
    public function obtenerTotal(): float
    {
        try {
            // Aplicar filtro de fecha de inicio de la app
            $fechaInicioApp = Config::getFechaInicioApp();

            $sql = "SELECT COALESCE(SUM(importe), 0) as total 
                    FROM egresos 
                    WHERE COD_COMP != 'GAS'
                      AND motivo != 'COMPENSACION_IVA'
                      AND motivo != 'GASTOS_DIRECTORES'
                      AND (tipo_gasto IS NULL OR tipo_gasto != 'Servicios')
                      AND fecha >= ?";
            $stmt = sqlsrv_query($this->db, $sql, [$fechaInicioApp]);

            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);

            return (float) $result['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtiene el total de egresos de un proveedor específico (para Reporte Alberto)
     */
    public function obtenerTotalProveedor($codProvee): float
    {
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

            return (float) $result['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total proveedor: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Comprime imagen base64 para almacenamiento optimizado
     * Soporta múltiples formatos: JPEG, PNG, GIF, BMP, WebP
     */
    public function comprimirImagen($archivoBase64Completo)
    {
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
                    $imagenRedimensionada,
                    $imagen,
                    0,
                    0,
                    0,
                    0,
                    $nuevoAncho,
                    $nuevoAlto,
                    $ancho,
                    $alto
                );

                imagedestroy($imagen);
                $imagen = $imagenRedimensionada;
            }

            // Convertir a formato optimizado (JPEG o WebP)
            ob_start();

            switch ($tipoImagen) {
                case 'png':
                    imagepng($imagen, null, 6);
                    break;
                case 'gif':
                    imagegif($imagen, null);
                    break;
                case 'webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($imagen, null, 80);
                    } else {
                        imagejpeg($imagen, null, 85);
                    }
                    break;
                default:
                    imagejpeg($imagen, null, 85);
                    break;
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
                    "Tamaño: " . number_format($tamañoOriginal / 1024, 2) . "KB -> " .
                    number_format($tamañoComprimido / 1024, 2) . "KB ({$porcentajeReduccion}% reducción)");
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
    public function obtenerFoto($id): ?array
    {
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
     * Obtiene todos los archivos adjuntos de un egreso
     */
    public function obtenerArchivos($idEgreso)
    {
        try {
            // Intentar obtener de la tabla egresos_archivos
            $sql = "SELECT id, archivo, tipo FROM egresos_archivos WHERE id_egreso = ?";
            $stmt = sqlsrv_query($this->db, $sql, [$idEgreso]);

            if ($stmt === false) {
                // Si falla (ej. tabla no existe aún), retornar vacío
                return [];
            }

            $archivos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $fotoCompleta = $row['archivo'];
                $tipo = $row['tipo'];

                // Procesar base64 si es necesario (similar a obtenerFoto)
                if (strpos($fotoCompleta, 'data:') === 0) {
                    preg_match('/^data:([^;]+);base64,(.+)$/', $fotoCompleta, $matches);
                    if ($matches) {
                        $tipo = $matches[1];
                        $foto = $matches[2];
                    } else {
                        $foto = $fotoCompleta;
                    }
                } else {
                    $foto = $fotoCompleta;
                }

                $archivos[] = [
                    'id' => $row['id'],
                    'foto' => $foto,
                    'tipo' => $tipo
                ];
            }
            sqlsrv_free_stmt($stmt);

            // Si no hay archivos en la tabla nueva, buscar en la tabla principal (retrocompatibilidad)
            if (empty($archivos)) {
                $fotoPrincipal = $this->obtenerFoto($idEgreso);
                if ($fotoPrincipal) {
                    $archivos[] = array_merge(['id' => 'main'], $fotoPrincipal);
                }
            } else {
                // Si hay archivos en la tabla nueva, TAMBIÉN revisar la tabla principal si no está duplicada
                // (Esto depende de cómo decidamos migrar. Por ahora asumimos que si hay en la nueva, usamos la nueva, 
                // o mezclamos. Mezclamos para seguridad.)
                $fotoPrincipal = $this->obtenerFoto($idEgreso);
                if ($fotoPrincipal) {
                    // Verificar si ya está (comparando contenido podría ser costoso, mejor asumimos que si usas el sistema nuevo
                    // guardas en ambas o solo en archivos. Vamos a incluirlas todas y el frontend decide).
                    // Para evitar duplicados visuales si el mismo archivo está en ambos lados, podríamos comparar lengths o hashes,
                    // pero por ahora simplifiquemos.
                    $archivos[] = array_merge(['id' => 'main'], $fotoPrincipal);
                }
            }

            return $archivos;
        } catch (Exception $e) {
            error_log("Error al obtener archivos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Guarda múltiples archivos para un egreso
     */
    public function guardarArchivos($idEgreso, $archivos)
    {
        try {
            // Verificar si la tabla existe antes de intentar insertar
            $checkTable = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'egresos_archivos'";
            $stmtCheck = sqlsrv_query($this->db, $checkTable);

            if ($stmtCheck === false || sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC) === null) {
                // La tabla no existe, solo registrar en log y retornar
                error_log("Tabla egresos_archivos no existe. Los archivos adicionales no se guardarán.");
                return true;
            }
            sqlsrv_free_stmt($stmtCheck);

            $sql = "INSERT INTO egresos_archivos (id_egreso, archivo, tipo, fecha_carga) VALUES (?, ?, ?, GETDATE())";

            foreach ($archivos as $archivoBase64) {
                // Comprimir/Procesar
                $archivoProcesado = $this->comprimirImagen($archivoBase64);

                // Detectar tipo
                $tipo = 'image/jpeg';
                if (strpos($archivoBase64, 'data:') === 0) {
                    preg_match('/^data:([^;]+);/', $archivoBase64, $matches);
                    if (isset($matches[1]))
                        $tipo = $matches[1];
                } else if (strpos($archivoProcesado, 'JVBERi0') === 0) {
                    $tipo = 'application/pdf';
                }

                $params = [$idEgreso, $archivoProcesado, $tipo];
                $stmt = sqlsrv_query($this->db, $sql, $params);

                if ($stmt === false) {
                    error_log("Error al guardar archivo adicional para egreso $idEgreso: " . print_r(sqlsrv_errors(), true));
                    // No lanzar excepción, solo continuar
                } else {
                    sqlsrv_free_stmt($stmt);
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Error al guardar archivos: " . $e->getMessage());
            // No lanzar excepción para no romper el flujo principal
            return false;
        }
    }

    /**
     * Verifica y crea la tabla egresos_archivos si no existe
     */
    private function ensureAttachmentsTableExists()
    {
        try {
            $sqlCheck = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'egresos_archivos'";
            $stmtCheck = sqlsrv_query($this->db, $sqlCheck);
            $exists = sqlsrv_has_rows($stmtCheck);
            sqlsrv_free_stmt($stmtCheck); // Change: added sqlsrv_free_stmt to properly release resource.

            if (!$exists) {
                $sqlCreate = "CREATE TABLE egresos_archivos (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    id_egreso INT NOT NULL,
                    archivo VARCHAR(MAX),
                    tipo VARCHAR(50),
                    nombre_original VARCHAR(255),
                    fecha_carga DATETIME DEFAULT GETDATE(),
                    FOREIGN KEY (id_egreso) REFERENCES egresos(id) ON DELETE CASCADE
                )";
                sqlsrv_query($this->db, $sqlCreate);
            }
        } catch (Exception $e) {
            // Silenciar error para no romper flujo principal, pero loguear
            error_log("Error checking/creating attachments table: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la lista de directores desde la base de datos
     */
    public function obtenerDirectores()
    {
        return $this->director->obtenerDirectores();
    }

    /**
     * Obtiene la lista de centros de costo desde la base CENTRAL
     * Retorna array con COD_AUXILIAR y CENTRO_COSTO
     */
    public function obtenerCentrosCosto()
    {
        try {
            $dbCentral = $this->conexion->conectar('central');
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
    public function obtenerProveedores()
    {
        try {
            $dbCentral = $this->conexion->conectar('central');
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
    /**
     * Crea un gasto con distribución múltiple de centros de costo
     * Inserta múltiples filas con el mismo N_COMP pero diferentes centros e importes
     */
    public function crearGastoConDistribucion($datosComunes, $distribucion, $importeTotal)
    {
        try {
            // Generar UN SOLO número de comprobante para todos los registros
            $nComp = $this->generarNumeroComprobanteGAS();

            // Validar que la suma de importes coincida con el total (con tolerancia de centavos)
            $sumaImportes = array_sum(array_column($distribucion, 'importe'));
            if (abs($sumaImportes - $importeTotal) > 0.02) {
                throw new Exception("La suma de importes no coincide con el total");
            }

            // Procesar foto/PDF si se proporciona (UNA SOLA VEZ, se replica en todas las filas)
            $archivoComprimido = null;
            if (!empty($datosComunes['foto'])) {
                $tipoArchivo = $datosComunes['tipo_archivo'] ?? 'image/jpeg';
                if ($tipoArchivo === 'application/pdf') {
                    // Para PDF, guardar directamente sin comprimir
                    $archivoComprimido = $datosComunes['foto'];
                } else {
                    // Para imágenes, comprimir
                    $archivoComprimido = $this->comprimirImagen($datosComunes['foto']);
                }
            }

            // SQL para insertar cada línea de distribución (sin tipo_archivo)
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

            // Insertar una fila por cada centro de costo en la distribución
            foreach ($distribucion as $item) {
                $params = [
                    $datosComunes['es_factura'] ?? 0,
                    $nComp,  // MISMO N_COMP para todas las filas
                    $datosComunes['fecha'],
                    $datosComunes['tipo_gasto'],
                    $item['importe'],  // Importe proporcional
                    $datosComunes['observaciones'] ?? '',
                    $archivoComprimido,  // MISMA foto/PDF para todas las filas
                    $item['centro_costo']  // Centro de costo específico
                ];

                $stmt = sqlsrv_query($this->db, $sql, $params);

                if ($stmt === false) {
                    throw new Exception("Error al insertar distribución: " . print_r(sqlsrv_errors(), true));
                }

                sqlsrv_free_stmt($stmt);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error al crear gasto con distribución: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un gasto simple (mantener para compatibilidad)
     */
    public function crearGasto($datos)
    {
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
    private function generarNumeroComprobanteGAS()
    {
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
    public function obtenerGastos($filtros = [])
    {
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
     * Obtiene gastos agrupados por N_COMP (para mostrar gastos distribuidos)
     */
    public function obtenerGastosAgrupados($filtros = [])
    {
        try {
            $sql = "SELECT 
                        N_COMP,
                        fecha,
                        tipo_gasto,
                        SUM(importe) as importe_total,
                        COUNT(*) as cantidad_centros,
                        MAX(observaciones) as observaciones,
                        MAX(es_factura) as es_factura,
                        MAX(CASE WHEN foto IS NOT NULL THEN 1 ELSE 0 END) as tiene_foto,
                        MAX(id) as id_representativo
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

            $sql .= " GROUP BY N_COMP, fecha, tipo_gasto
                      ORDER BY fecha DESC, N_COMP DESC";

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
            error_log("Error al obtener gastos agrupados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el detalle de distribución de un gasto específico por N_COMP
     */
    public function obtenerDetalleDistribucion($nComp)
    {
        try {
            // Primero obtener los centros de costo de la tabla egresos (base APPS)
            $sql = "SELECT 
                        centro_costo as cod_centro,
                        importe
                    FROM egresos
                    WHERE COD_COMP = 'GAS' AND N_COMP = ?
                    ORDER BY importe DESC";

            $stmt = sqlsrv_query($this->db, $sql, [$nComp]);

            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $distribucion = [];
            $importeTotal = 0;

            // Obtener conexión CENTRAL para buscar nombres
            $dbCentral = $this->conexion->conectar('central');

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $importe = floatval($row['importe']);
                $importeTotal += $importe;
                $codCentro = trim($row['cod_centro']);

                // Buscar nombre del centro de costo en la base CENTRAL
                $nombreCentro = $codCentro; // Por defecto usar el código
                if (!empty($codCentro)) {
                    $sqlNombre = "SELECT CENTRO_COSTO FROM RO_T_CENTRO_DE_COSTOS WHERE COD_AUXILIAR = ?";
                    $stmtNombre = sqlsrv_query($dbCentral, $sqlNombre, [$codCentro]);
                    if ($stmtNombre && $rowNombre = sqlsrv_fetch_array($stmtNombre, SQLSRV_FETCH_ASSOC)) {
                        $nombreCentro = trim($rowNombre['CENTRO_COSTO']);
                    }
                    if ($stmtNombre)
                        sqlsrv_free_stmt($stmtNombre);
                }

                $distribucion[] = [
                    'cod_centro' => $codCentro,
                    'nombre_centro' => $nombreCentro,
                    'importe' => $importe
                ];
            }

            // Calcular porcentajes
            foreach ($distribucion as &$item) {
                $item['porcentaje'] = $importeTotal > 0 ? ($item['importe'] / $importeTotal * 100) : 0;
            }

            sqlsrv_free_stmt($stmt);
            return $distribucion;
        } catch (Exception $e) {
            error_log("Error al obtener detalle de distribución: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el total de gastos desde la fecha de inicio de la app (COD_COMP = 'GAS')
     */
    public function obtenerTotalGastos()
    {
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
    public function obtenerTotalProveedorSinGastos($proveedor)
    {
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
    public function eliminar($id): bool
    {
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
    public function obtenerPorId($id)
    {
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
    public function actualizar($id, $datos)
    {
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
                $datos['foto'],
                $datos['foto'], // Se usa dos veces para la condición IIF
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
                if ($stmtUpdProv === false)
                    throw new Exception("Error al actualizar proveedor.");

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
                if ($stmtDelProv === false)
                    throw new Exception("Error al limpiar datos de proveedor.");
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

    /**
     * Obtiene el total histórico real de todos los egresos (sin filtros)
     */
    public function obtenerTotalReal(): float
    {
        try {
            $fechaInicioApp = Config::getFechaInicioApp();
            $sql = "SELECT COALESCE(SUM(importe), 0) as total FROM egresos WHERE fecha >= ?";
            $stmt = sqlsrv_query($this->db, $sql, [$fechaInicioApp]);

            if ($stmt === false) {
                throw new Exception("Error en la consulta real: " . print_r(sqlsrv_errors(), true));
            }

            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);

            return (float) $result['total'];
        } catch (Exception $e) {
            error_log("Error al obtener total real: " . $e->getMessage());
            return 0.0;
        }
    }
}
