<?php
require_once __DIR__ . '/Database.php';

/**
 * Clase Director
 * Gestiona la obtención de directores desde la base de datos APPS
 */
class Director {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getAppsConnection();
    }
    
    /**
     * Obtiene la lista de directores desde la base de datos sistemas
     * Consulta: SELECT NOMBRE FROM RO_T_DIRECTORES
     */
    public function obtenerDirectores(): array {
        try {
            $sql = "SELECT NOMBRE FROM RO_T_DIRECTORES";
            $stmt = sqlsrv_query($this->db, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $directores = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $directores[] = $row['NOMBRE'];
            }
            
            sqlsrv_free_stmt($stmt);
            
            return $directores;
        } catch (Exception $e) {
            error_log("Error al obtener directores: " . $e->getMessage());

        }
    }
    
    /**
     * Verifica si un director existe en la base de datos
     */
    public function existeDirector(string $nombreDirector): bool {
        try {
            $sql = "SELECT COUNT(*) as total FROM RO_T_DIRECTORES WHERE NOMBRE = ?";
            $params = [$nombreDirector];
            $stmt = sqlsrv_query($this->db, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return $row['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar director: " . $e->getMessage());
            return false;
        }
    }
}