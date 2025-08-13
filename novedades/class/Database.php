<?php
/**
 * Clase para manejo de conexión a base de datos
 * /novedades/class/Database.php
 * Adaptada para usar la clase Conexion existente
 */

require_once(__DIR__ . '/../../class/conexion.php');

class Database {
    private $conexion;
    private $connection;
    private static $instance = null;

    private function __construct() {
        $this->conexion = new Conexion();
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            // Usar la conexión central por defecto para el sistema de novedades
            $this->connection = $this->conexion->conectar('apps');
            
            if (!$this->connection) {
                $errors = sqlsrv_errors();
                $errorMsg = "Error de conexión a base de datos 'apps': " . print_r($errors, true);
                error_log($errorMsg);
                throw new Exception($errorMsg);
            }
            
            // Log conexión exitosa
            error_log("Conexión a base de datos 'apps' establecida correctamente");
        } catch (Exception $e) {
            $errorMsg = "Error de conexión: " . $e->getMessage();
            error_log($errorMsg);
            throw new Exception($errorMsg);
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    /**
     * Ejecutar consulta con parámetros (SQL Server)
     */
    public function query($sql, $params = []) {
        try {
            $stmt = sqlsrv_query($this->connection, $sql, $params);
            
            if (!$stmt) {
                throw new Exception("Error en consulta: " . print_r(sqlsrv_errors(), true));
            }
            
            return new DatabaseResult($stmt);
        } catch (Exception $e) {
            throw new Exception("Error en consulta: " . $e->getMessage());
        }
    }

    /**
     * Ejecutar consulta de inserción y obtener ID
     */
    public function insert($sql, $params = []) {
        try {
            // Agregar SELECT SCOPE_IDENTITY() para obtener el ID insertado
            $sqlWithIdentity = $sql . "; SELECT SCOPE_IDENTITY() AS id;";
            
            $stmt = sqlsrv_query($this->connection, $sqlWithIdentity, $params);
            
            if (!$stmt) {
                throw new Exception("Error en inserción: " . print_r(sqlsrv_errors(), true));
            }
            
            // Obtener el ID insertado
            if (sqlsrv_next_result($stmt) && sqlsrv_fetch($stmt)) {
                return sqlsrv_get_field($stmt, 0);
            }
            
            return null;
        } catch (Exception $e) {
            throw new Exception("Error en inserción: " . $e->getMessage());
        }
    }

    public function beginTransaction() {
        return sqlsrv_begin_transaction($this->connection);
    }

    public function commit() {
        return sqlsrv_commit($this->connection);
    }

    public function rollback() {
        return sqlsrv_rollback($this->connection);
    }
}

/**
 * Clase auxiliar para manejar resultados de SQL Server
 */
class DatabaseResult {
    private $stmt;

    public function __construct($stmt) {
        $this->stmt = $stmt;
    }

    public function fetch() {
        return sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC);
    }

    public function fetchAll() {
        $results = [];
        while ($row = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    /**
     * Obtener número de filas afectadas (para UPDATE, DELETE, INSERT)
     */
    public function rowCount() {
        return sqlsrv_rows_affected($this->stmt);
    }

    /**
     * Verificar si hay resultados
     */
    public function hasRows() {
        return sqlsrv_has_rows($this->stmt);
    }
}
?>