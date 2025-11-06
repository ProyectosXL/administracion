<?php
require_once __DIR__ . '/../../../class/classEnv.php';

/**
 * Clase Database
 * Gestiona conexiones a bases de datos mediante sqlsrv
 * Implementa patrón Singleton para reutilización de conexiones
 */
class Database {
    private static $instance = null;
    private $connections = [];
    public $envVars; // Hacer pública para acceso a HOST_CENTRAL
    
    /**
     * Constructor privado - Singleton
     */
    private function __construct() {
        $this->connections = [];
        $vars = new DotEnv(__DIR__ . '/../../../.env');
        $this->envVars = $vars->listVars();
    }
    
    /**
     * Obtiene instancia única
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Conecta a una base de datos específica
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
            
            $connectionInfo = [
                "Database" => $dbname,
                "UID" => $this->envVars['USER'],
                "PWD" => $this->envVars['PASS'],
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
     * Obtiene conexión para base APPS
     */
    public function getAppsConnection() {
        if (!isset($this->connections['apps'])) {
            $this->connections['apps'] = $this->connect('apps');
        }
        return $this->connections['apps'];
    }
    
    /**
     * Obtiene conexión para base CENTRAL
     */
    public function getCentralConnection() {
        if (!isset($this->connections['central'])) {
            $this->connections['central'] = $this->connect('central');
        }
        return $this->connections['central'];
    }
    
    /**
     * Obtiene conexión por defecto (APPS)
     */
    public function getConnection() {
        return $this->getAppsConnection();
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
     * Prevenir clonación
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar un Singleton");
    }
    
    /**
     * Destructor - cierra conexiones
     */
    public function __destruct() {
        $this->closeConnections();
    }
}