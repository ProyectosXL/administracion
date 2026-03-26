<?php
require_once __DIR__ . '/../../../class/conexion.php';

/**
 * Clase Director
 * Gestiona los directores desde la tabla RO_T_DIRECTORES
 */
class Director {
    private $db;
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
        $this->db = $this->conexion->conectar('apps');
        
        if ($this->db === false) {
            throw new Exception("Error al conectar con la base de datos APPS en Director");
        }
    }
    
    /**
     * Obtiene todos los directores activos
     * @return array
     */
    public function obtenerDirectores() {
        try {
            // Debug: log de inicio
            error_log("Director::obtenerDirectores - Iniciando consulta");
            
            $sql = "SELECT ID_DIRECTOR, NOMBRE, EMAIL, ACTIVO 
                    FROM RO_T_DIRECTORES 
                    WHERE ACTIVO = 1 
                    ORDER BY NOMBRE";
            
            $stmt = sqlsrv_query($this->db, $sql);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Director::obtenerDirectores - Error SQL: " . print_r($errors, true));
                throw new Exception("Error en consulta: " . print_r($errors, true));
            }
            
            $directores = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $directores[] = [
                    'id_director' => $row['ID_DIRECTOR'],
                    'nombre_director' => $row['NOMBRE'],
                    'mail_director' => $row['EMAIL'] ?? '',
                    'activo' => $row['ACTIVO']
                ];
            }
            
            sqlsrv_free_stmt($stmt);
            
            // Debug: log del resultado
            error_log("Director::obtenerDirectores - Encontrados " . count($directores) . " directores");
            
            return $directores;
        } catch (Exception $e) {
            error_log("Error al obtener directores: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene un director por ID
     * @param int $idDirector
     * @return array|null
     */
    public function obtenerPorId(int $idDirector) {
        try {
            $sql = "SELECT ID_DIRECTOR, NOMBRE, EMAIL, ACTIVO 
                    FROM RO_T_DIRECTORES 
                    WHERE ID_DIRECTOR = ? AND ACTIVO = 1";
            
            $stmt = sqlsrv_query($this->db, $sql, [$idDirector]);
            
            if ($stmt === false) {
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if ($row) {
                return [
                    'id_director' => $row['ID_DIRECTOR'],
                    'nombre_director' => $row['NOMBRE'],
                    'mail_director' => $row['EMAIL'],
                    'activo' => $row['ACTIVO']
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error al obtener director por ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtiene un director por nombre
     * @param string $nombreDirector
     * @return array|null
     */
    public function obtenerPorNombre(string $nombreDirector) {
        try {
            $sql = "SELECT ID_DIRECTOR, NOMBRE, EMAIL, ACTIVO 
                    FROM RO_T_DIRECTORES 
                    WHERE NOMBRE = ? AND ACTIVO = 1";
            
            $stmt = sqlsrv_query($this->db, $sql, [$nombreDirector]);
            
            if ($stmt === false) {
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if ($row) {
                return [
                    'id_director' => $row['ID_DIRECTOR'],
                    'nombre_director' => $row['NOMBRE'],
                    'mail_director' => $row['EMAIL'],
                    'activo' => $row['ACTIVO']
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error al obtener director por nombre: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica si existe un director por ID
     * @param int $idDirector
     * @return bool
     */
    public function existeDirector(int $idDirector) {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM RO_T_DIRECTORES 
                    WHERE ID_DIRECTOR = ? AND ACTIVO = 1";
            
            $stmt = sqlsrv_query($this->db, $sql, [$idDirector]);
            
            if ($stmt === false) {
                return false;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return $row && $row['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar director: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el nombre de un director por ID
     * @param int $idDirector
     * @return string|null
     */
    public function obtenerNombrePorId(int $idDirector) {
        $director = $this->obtenerPorId($idDirector);
        return $director ? $director['nombre_director'] : null;
    }
    
    /**
     * Obtiene el ID de un director por nombre
     * @param string $nombreDirector
     * @return int|null
     */
    public function obtenerIdPorNombre(string $nombreDirector) {
        $director = $this->obtenerPorNombre($nombreDirector);
        return $director ? $director['id_director'] : null;
    }
    
    /**
     * Obtiene directores para distribución de retiros múltiples
     * Excluye el ID 1123 según requerimiento
     * @return array
     */
    public function obtenerDirectoresDistribucion() {
        try {
            error_log("Director::obtenerDirectoresDistribucion - Iniciando consulta");
            
            $sql = "SELECT ID_DIRECTOR, NOMBRE 
                    FROM RO_T_DIRECTORES 
                    ORDER BY NOMBRE";
            
            $stmt = sqlsrv_query($this->db, $sql);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Director::obtenerDirectoresDistribucion - Error SQL: " . print_r($errors, true));
                throw new Exception("Error en consulta: " . print_r($errors, true));
            }
            
            $directores = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $directores[] = [
                    'id_director' => $row['ID_DIRECTOR'],
                    'nombre_director' => $row['NOMBRE']
                ];
            }
            
            sqlsrv_free_stmt($stmt);
            
            error_log("Director::obtenerDirectoresDistribucion - Encontrados " . count($directores) . " directores");
            
            return $directores;
        } catch (Exception $e) {
            error_log("Error al obtener directores de distribución: " . $e->getMessage());
            return [];
        }
    }
}