
<?php

class Politica
{
    private $cid;
    private $cid_central;
    private $upload_dir; // Directorio donde se guardarán los archivos

    function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/Class/Conexion.php';
        $this->cid = new Conexion();
        $this->cid_central = $this->cid->conectar('central');
        
        // Definir la carpeta de almacenamiento de documentos
        $this->upload_dir = $_SERVER['DOCUMENT_ROOT'].'/documentos/';
        
        // Crear el directorio si no existe
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    /**
     * Ejecuta una consulta y retorna un array con los resultados
     */
    private function retornarArray($sqlEnviado, $db = 'central'){
        $sql = $sqlEnviado;
        $cid_central = $this->cid->conectar($db);
        $stmt = sqlsrv_query($cid_central, $sql);
        
        if ($stmt === false) {
            return false;
        }
        
        $rows = array();
        while($v = sqlsrv_fetch_array($stmt)) {
            $rows[] = $v;
        }
        
        return $rows;
    }
    
    /**
     * Ejecuta una consulta que no devuelve resultados (INSERT, UPDATE, DELETE)
     */
    private function ejecutarSQL($sqlEnviado, $db = 'central') {
        $sql = $sqlEnviado;
        $cid_central = $this->cid->conectar($db);
        $stmt = sqlsrv_query($cid_central, $sql);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            return [
                'status' => 'error',
                'message' => $errors[0]['message'],
                'code' => $errors[0]['code']
            ];
        }
        
        return [
            'status' => 'success'
        ];
    }
    
    /**
     * Obtiene todos los documentos (políticas y procedimientos)
     */
    public function obtenerTodos($filtros = []) {
        $sql = "SELECT p.*, s.nombre as sector_nombre, s.icono 
                FROM Politicas_Procedimientos p 
                JOIN Sectores s ON p.sector_id = s.id 
                WHERE p.estado = 'activo'";
        
        // Aplicar filtros si existen
        if (isset($filtros['tipo']) && !empty($filtros['tipo'])) {
            $sql .= " AND p.tipo = '".$filtros['tipo']."'";
        }
        
        if (isset($filtros['sector_id']) && !empty($filtros['sector_id'])) {
            $sql .= " AND p.sector_id = ".$filtros['sector_id'];
        }
        
        if (isset($filtros['busqueda']) && !empty($filtros['busqueda'])) {
            $busqueda = $filtros['busqueda'];
            $sql .= " AND (p.titulo LIKE '%$busqueda%' OR p.descripcion LIKE '%$busqueda%' OR p.tags LIKE '%$busqueda%')";
        }
        
        $sql .= " ORDER BY p.fecha_actualizacion DESC";
        
        return $this->retornarArray($sql);
    }
    
    /**
     * Obtiene un documento específico por su ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT p.*, s.nombre as sector_nombre, s.icono 
                FROM Politicas_Procedimientos p 
                JOIN Sectores s ON p.sector_id = s.id 
                WHERE p.id = $id";
        
        $resultado = $this->retornarArray($sql);
        
        if (count($resultado) > 0) {
            // Incrementar contador de vistas
            $this->incrementarContador($id, 'vistas');
            return $resultado[0];
        }
        
        return false;
    }
    
    /**
     * Guarda un nuevo documento en la base de datos y en el sistema de archivos
     */
    public function guardarDocumento($datos, $archivo) {
        // Validar datos mínimos
        if (empty($datos['titulo']) || empty($datos['sector_id']) || empty($archivo['name'])) {
            return [
                'status' => 'error',
                'message' => 'Faltan datos obligatorios'
            ];
        }
        
        // Procesar el archivo
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nuevo_nombre = $this->generarNombreArchivo($datos['titulo'], $extension);
        $ruta_completa = $this->upload_dir . $nuevo_nombre;
        
        // Comprobar que es un PDF
        if (strtolower($extension) !== 'pdf') {
            return [
                'status' => 'error',
                'message' => 'Solo se permiten archivos PDF'
            ];
        }
        
        // Mover el archivo al directorio de almacenamiento
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
            return [
                'status' => 'error',
                'message' => 'Error al subir el archivo'
            ];
        }
        
        // Preparar campos para la base de datos
        $tipo = isset($datos['tipo']) ? $datos['tipo'] : 'politica';
        $descripcion = isset($datos['descripcion']) ? $datos['descripcion'] : '';
        $tags = isset($datos['tags']) ? $datos['tags'] : '';
        $creado_por = isset($datos['creado_por']) ? $datos['creado_por'] : 'Sistema';
        
        // Insertar en la base de datos
        $sql = "INSERT INTO Politicas_Procedimientos (
                    tipo, titulo, descripcion, sector_id, 
                    archivo_nombre, ruta_archivo, tags, creado_por
                ) VALUES (
                    '$tipo', '".$this->escaparTexto($datos['titulo'])."', 
                    '".$this->escaparTexto($descripcion)."', ".$datos['sector_id'].", 
                    '$nuevo_nombre', '$ruta_completa', 
                    '".$this->escaparTexto($tags)."', '$creado_por'
                )";
        
        $resultado = $this->ejecutarSQL($sql);
        
        if ($resultado['status'] === 'success') {
            // Obtener el ID del documento recién insertado
            $sql_id = "SELECT MAX(id) as id FROM Politicas_Procedimientos";
            $id_resultado = $this->retornarArray($sql_id);
            
            if ($id_resultado && count($id_resultado) > 0) {
                $resultado['id'] = $id_resultado[0]['id'];
            }
        }
        
        return $resultado;
    }
    
    /**
     * Actualiza un documento existente
     */
    public function actualizarDocumento($id, $datos, $archivo = null) {
        // Validar existencia del documento
        $documento = $this->obtenerPorId($id);
        if (!$documento) {
            return [
                'status' => 'error',
                'message' => 'El documento no existe'
            ];
        }
        
        // Iniciar la consulta SQL para actualización
        $sql = "UPDATE Politicas_Procedimientos SET 
                fecha_actualizacion = GETDATE()";
        
        // Actualizar campos si están presentes
        if (isset($datos['titulo']) && !empty($datos['titulo'])) {
            $sql .= ", titulo = '".$this->escaparTexto($datos['titulo'])."'";
        }
        
        if (isset($datos['descripcion'])) {
            $sql .= ", descripcion = '".$this->escaparTexto($datos['descripcion'])."'";
        }
        
        if (isset($datos['sector_id']) && !empty($datos['sector_id'])) {
            $sql .= ", sector_id = ".$datos['sector_id'];
        }
        
        if (isset($datos['tags'])) {
            $sql .= ", tags = '".$this->escaparTexto($datos['tags'])."'";
        }
        
        if (isset($datos['estado']) && in_array($datos['estado'], ['activo', 'inactivo', 'obsoleto'])) {
            $sql .= ", estado = '".$datos['estado']."'";
        }
        
        if (isset($datos['version'])) {
            $sql .= ", version = '".$datos['version']."'";
        }
        
        // Si hay un archivo nuevo, procesarlo
        if ($archivo && !empty($archivo['name'])) {
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            
            // Comprobar que es un PDF
            if (strtolower($extension) !== 'pdf') {
                return [
                    'status' => 'error',
                    'message' => 'Solo se permiten archivos PDF'
                ];
            }
            
            // Generar nuevo nombre y ruta
            $titulo = isset($datos['titulo']) ? $datos['titulo'] : $documento['titulo'];
            $nuevo_nombre = $this->generarNombreArchivo($titulo, $extension);
            $ruta_completa = $this->upload_dir . $nuevo_nombre;
            
            // Mover el archivo nuevo
            if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                // Eliminar archivo anterior si existe
                if (file_exists($documento['ruta_archivo'])) {
                    unlink($documento['ruta_archivo']);
                }
                
                // Actualizar campos en la base de datos
                $sql .= ", archivo_nombre = '$nuevo_nombre', ruta_archivo = '$ruta_completa'";
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Error al subir el archivo'
                ];
            }
        }
        
        // Completar la consulta
        $sql .= " WHERE id = $id";
        
        return $this->ejecutarSQL($sql);
    }
    
    /**
     * Elimina un documento (marcándolo como inactivo)
     */
    public function eliminarDocumento($id) {
        $sql = "UPDATE Politicas_Procedimientos SET 
                estado = 'inactivo', 
                fecha_actualizacion = GETDATE() 
                WHERE id = $id";
        
        return $this->ejecutarSQL($sql);
    }
    
    /**
     * Elimina físicamente un documento (tanto registro como archivo)
     */
    public function eliminarDefinitivamente($id) {
        // Primero obtener la información del documento
        $documento = $this->obtenerPorId($id);
        if (!$documento) {
            return [
                'status' => 'error',
                'message' => 'El documento no existe'
            ];
        }
        
        // Eliminar archivo físico si existe
        if (file_exists($documento['ruta_archivo'])) {
            unlink($documento['ruta_archivo']);
        }
        
        // Eliminar registro de la base de datos
        $sql = "DELETE FROM Politicas_Procedimientos WHERE id = $id";
        
        return $this->ejecutarSQL($sql);
    }
    
    /**
     * Obtiene todos los sectores
     */
    public function obtenerSectores() {
        $sql = "SELECT * FROM Sectores ORDER BY nombre";
        return $this->retornarArray($sql);
    }
    
    /**
     * Obtiene un sector por su ID
     */
    public function obtenerSectorPorId($id) {
        $sql = "SELECT * FROM Sectores WHERE id = $id";
        $resultado = $this->retornarArray($sql);
        
        if (count($resultado) > 0) {
            return $resultado[0];
        }
        
        return false;
    }
    
    /**
     * Guarda un nuevo sector
     */
    public function guardarSector($datos) {
        if (empty($datos['nombre'])) {
            return [
                'status' => 'error',
                'message' => 'El nombre del sector es obligatorio'
            ];
        }
        
        $descripcion = isset($datos['descripcion']) ? $datos['descripcion'] : '';
        $icono = isset($datos['icono']) ? $datos['icono'] : 'fa-folder';
        
        $sql = "INSERT INTO Sectores (nombre, descripcion, icono) 
                VALUES ('".$this->escaparTexto($datos['nombre'])."', 
                        '".$this->escaparTexto($descripcion)."', 
                        '$icono')";
        
        return $this->ejecutarSQL($sql);
    }
    
    /**
     * Obtiene términos del glosario
     */
    public function obtenerGlosario($filtro = '') {
        $sql = "SELECT g.*, s.nombre as sector_nombre 
                FROM Glosario g 
                LEFT JOIN Sectores s ON g.sector_id = s.id";
        
        if (!empty($filtro)) {
            $sql .= " WHERE g.termino LIKE '%$filtro%' OR g.definicion LIKE '%$filtro%'";
        }
        
        $sql .= " ORDER BY g.termino";
        
        return $this->retornarArray($sql);
    }
    
    /**
     * Guarda un nuevo término en el glosario
     */
    public function guardarTerminoGlosario($datos) {
        if (empty($datos['termino']) || empty($datos['definicion'])) {
            return [
                'status' => 'error',
                'message' => 'El término y la definición son obligatorios'
            ];
        }
        
        $sector_id = isset($datos['sector_id']) && !empty($datos['sector_id']) ? $datos['sector_id'] : "NULL";
        
        $sql = "INSERT INTO Glosario (termino, definicion, sector_id) 
                VALUES ('".$this->escaparTexto($datos['termino'])."', 
                        '".$this->escaparTexto($datos['definicion'])."', 
                        $sector_id)";
        
        return $this->ejecutarSQL($sql);
    }
    
    /**
     * Registra acceso a un documento
     */
    public function registrarAcceso($documento_id, $tipo_acceso, $usuario_id = null) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $usuario_id = $usuario_id ? $usuario_id : "NULL";
        
        $sql = "INSERT INTO Accesos_Documentos (documento_id, usuario_id, tipo_acceso, ip_acceso) 
                VALUES ($documento_id, $usuario_id, '$tipo_acceso', '$ip')";
        
        $this->ejecutarSQL($sql);
        
        // Incrementar contador específico
        $this->incrementarContador($documento_id, $tipo_acceso);
    }
    
    /**
     * Incrementa el contador de vistas o descargas
     */
    private function incrementarContador($documento_id, $tipo) {
        $campo = $tipo === 'descarga' ? 'descargas' : 'vistas';
        
        $sql = "UPDATE Politicas_Procedimientos 
                SET $campo = $campo + 1 
                WHERE id = $documento_id";
        
        $this->ejecutarSQL($sql);
    }
    
    /**
     * Obtiene estadísticas de uso
     */
    public function obtenerEstadisticas() {
        $estadisticas = [];
        
        // Total de documentos
        $sql = "SELECT COUNT(*) as total FROM Politicas_Procedimientos WHERE estado = 'activo'";
        $resultado = $this->retornarArray($sql);
        $estadisticas['total_documentos'] = $resultado[0]['total'];
        
        // Documentos por tipo
        $sql = "SELECT tipo, COUNT(*) as cantidad 
                FROM Politicas_Procedimientos 
                WHERE estado = 'activo' 
                GROUP BY tipo";
        $estadisticas['por_tipo'] = $this->retornarArray($sql);
        
        // Documentos por sector
        $sql = "SELECT s.nombre, COUNT(*) as cantidad 
                FROM Politicas_Procedimientos p
                JOIN Sectores s ON p.sector_id = s.id
                WHERE p.estado = 'activo' 
                GROUP BY s.nombre";
        $estadisticas['por_sector'] = $this->retornarArray($sql);
        
        // Documentos más vistos
        $sql = "SELECT TOP 5 id, titulo, vistas 
                FROM Politicas_Procedimientos 
                WHERE estado = 'activo' 
                ORDER BY vistas DESC";
        $estadisticas['mas_vistos'] = $this->retornarArray($sql);
        
        // Documentos más descargados
        $sql = "SELECT TOP 5 id, titulo, descargas 
                FROM Politicas_Procedimientos 
                WHERE estado = 'activo' 
                ORDER BY descargas DESC";
        $estadisticas['mas_descargados'] = $this->retornarArray($sql);
        
        return $estadisticas;
    }
    
    /**
     * Busca documentos por contenido
     * Nota: Esta es una implementación básica. Para búsqueda avanzada en PDFs,
     * se necesitaría una herramienta de indexación de contenido como Elasticsearch
     */
    public function buscarPorContenido($texto) {
        // En una implementación real, aquí se utilizaría un índice de búsqueda
        // Para este ejemplo, sólo buscamos en metadatos
        $sql = "SELECT p.*, s.nombre as sector_nombre 
                FROM Politicas_Procedimientos p 
                JOIN Sectores s ON p.sector_id = s.id 
                WHERE p.estado = 'activo' 
                AND (p.titulo LIKE '%$texto%' 
                    OR p.descripcion LIKE '%$texto%' 
                    OR p.tags LIKE '%$texto%')";
        
        return $this->retornarArray($sql);
    }
    
    /**
     * Método auxiliar para generar un nombre de archivo único
     */
    private function generarNombreArchivo($titulo, $extension) {
        $base = preg_replace('/[^a-z0-9]+/', '-', strtolower($titulo));
        $timestamp = time();
        return $base . '-' . $timestamp . '.' . $extension;
    }
    
    /**
     * Método auxiliar para escapar texto para SQL
     */
    private function escaparTexto($texto) {
        return str_replace("'", "''", $texto);
    }
}