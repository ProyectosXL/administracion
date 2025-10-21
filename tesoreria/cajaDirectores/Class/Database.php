<?php
require_once __DIR__ . '/../../../class/classEnv.php';

/**
 * Clase Database
 * Gestiona múltiples conexiones a bases de datos mediante PDO
 * Soporta conexiones a BASE APPS y BASE CENTRAL usando variables del .env
 */
class Database {
    private static $instance = null;
    private $connections = [];
    private $envVars;
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        $vars = new DotEnv(__DIR__ . '/../../../.env');
        $this->envVars = $vars->listVars();
    }
    
    /**
     * Obtiene la instancia única de Database
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establece la conexión con una base de datos específica
     * @param string $dbType
     * @return resource
     */
    private function connect($dbType) {
        try {
            if ($dbType === 'apps') {
                $host = $this->envVars['HOST_APPS'];
                $dbname = $this->envVars['DATABASE_APPS'];
            } elseif ($dbType === 'central') {
                $host = $this->envVars['HOST_CENTRAL'];
                $dbname = $this->envVars['DATABASE_CENTRAL'];
            } else {
                throw new Exception("Tipo de base de datos no válido: {$dbType}");
            }
            
            $username = $this->envVars['USER'];
            $password = $this->envVars['PASS'];
            
            // Para SQL Server usamos sqlsrv
            $connectionInfo = [
                "Database" => $dbname,
                "UID" => $username,
                "PWD" => $password,
                "CharacterSet" => $this->envVars['CHARACTER']
            ];
            
            $connection = sqlsrv_connect($host, $connectionInfo);
            
            if ($connection === false) {
                throw new Exception("Error de conexión SQL Server: " . print_r(sqlsrv_errors(), true));
            }
            
            return $connection;
        } catch (Exception $e) {
            error_log("Error de conexión: " . $e->getMessage());
            throw new Exception("No se pudo conectar a la base de datos {$dbType}");
        }
    }
    
    /**
     * Obtiene la conexión para la base APPS (sistemas)
     */
    public function getAppsConnection() {
        if (!isset($this->connections['apps'])) {
            $this->connections['apps'] = $this->connect('apps');
        }
        return $this->connections['apps'];
    }
    
    /**
     * Obtiene la conexión para la base CENTRAL
     */
    public function getCentralConnection() {
        if (!isset($this->connections['central'])) {
            $this->connections['central'] = $this->connect('central');
        }
        return $this->connections['central'];
    }
    
    /**
     * Obtiene la conexión (mantiene compatibilidad)
     * Por defecto usa la conexión central
     * Nota: Retorna resource de sqlsrv, no PDO
     */
    public function getConnection() {
        return $this->getCentralConnection();
    }
    
    /**
     * Cierra todas las conexiones
     * @return void
     */
    public function closeConnections() {
        foreach ($this->connections as $connection) {
            if ($connection) {
                sqlsrv_close($connection);
            }
        }
        $this->connections = [];
    }
    
    /**
     * Prevenir clonación del objeto
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización del objeto
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar un Singleton");
    }
    
    /**
     * Destructor para cerrar conexiones
     */
    public function __destruct() {
        $this->closeConnections();
    }
}