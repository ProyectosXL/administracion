<?php
// config/database.php

class Database {
    private static $instances = [];
    private static $envLoaded = false; // Bandera para cargar el .env una sola vez

    private function __construct() {}

    /**
     * Carga las variables de entorno manualmente desde el archivo .env.
     * Esto evita depender de la clase externa classEnv.php.
     */
    private static function loadEnvironment() {
        if (self::$envLoaded) {
            return;
        }

        try {
            // Subimos tres niveles para encontrar el .env en la raíz de 'administracion/'
            $path = __DIR__ . '/../../../../.env';

            if (!file_exists($path) || !is_readable($path)) {
                throw new \RuntimeException(sprintf('%s file is not found or not readable', $path));
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Cargamos las variables en el entorno
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                }
            }
            
            self::$envLoaded = true;

        } catch (Exception $e) {
            die("Error Crítico al cargar .env: " . $e->getMessage());
        }
    }

    public static function getConnection(string $connectionName = 'central') {
        // Nos aseguramos de que las variables de entorno estén cargadas
        self::loadEnvironment();

        if (!isset(self::$instances[$connectionName])) {
            try {
                $serverName = '';
                $dbName = '';

                // Usamos $_ENV que es más fiable que getenv() en algunos entornos
                if ($connectionName === 'central') {
                    $serverName = $_ENV['HOST_CENTRAL'];
                    $dbName     = $_ENV['DATABASE_CENTRAL'];
                } elseif ($connectionName === 'apps') {
                    $dbName     = $_ENV['DATABASE_APPS'];
                } elseif ($connectionName === 'lakers') {
                    $serverName = 'XL-LAKERBIS';
                    $dbName     = 'LOCALES_LAKERS';
                } else {
                    throw new Exception("Nombre de conexión no válido: $connectionName");
                }
                
                $uid     = $_ENV['USER'];
                $pwd     = $_ENV['PASS'];
                $charset = $_ENV['CHARACTER'];

                if (empty($dbName) || empty($uid)) { // PWD puede estar vacío
                    throw new Exception("Una o más variables de entorno de la base de datos no se encontraron. Verifica tu archivo .env.");
                }

                $connectionInfo = [
                    "Database" => $dbName,
                    "UID" => $uid,
                    "PWD" => $pwd,
                    "CharacterSet" => $charset,
                    "ReturnDatesAsStrings" => true
                ];

                $conn = sqlsrv_connect($serverName, $connectionInfo);

                if ($conn === false) {
                    throw new Exception("Error al conectar con SQL Server ($connectionName): " . print_r(sqlsrv_errors(), true));
                }
                
                self::$instances[$connectionName] = $conn;

            } catch (Exception $e) {
                die("Error Crítico al inicializar la base de datos: " . $e->getMessage());
            }
        }
        
        return self::$instances[$connectionName];
    }
    
    private function __clone() {}
    public function __wakeup() {}
}