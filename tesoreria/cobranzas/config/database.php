<?php
require_once __DIR__ . '/../../../class/classEnv.php';

class Database {
    // Array para almacenar las instancias de conexión
    private static $instances = [];

    // Hacemos el constructor privado para forzar el uso de los métodos estáticos
    private function __construct() {}

    /**
     * Método estático para obtener una conexión por su nombre.
     * Los nombres válidos son 'central' y 'apps'.
     *
     * @param string $connectionName El nombre de la conexión ('central' o 'apps')
     * @return PDO|null La conexión PDO o null si falla
     */
    public static function getConnection(string $connectionName = 'central') {
        // Si la instancia para esta conexión aún no existe, la creamos
        if (!isset(self::$instances[$connectionName])) {
            try {
                $vars = new DotEnv(__DIR__ . '/../../../.env');
                $envVars = $vars->listVars();
                
                $serverName = '';
                $dbName = '';

                // Seleccionamos las credenciales según el nombre de la conexión
                if ($connectionName === 'central') {
                    $serverName = $envVars['HOST_CENTRAL'];
                    $dbName     = $envVars['DATABASE_CENTRAL']; // LAKER_SA
                } elseif ($connectionName === 'apps') {
                    $serverName = $envVars['HOST_APPS'];     // 192.168.0.143
                    $dbName     = $envVars['DATABASE_APPS'];  // sistemas
                } else {
                    throw new Exception("Nombre de conexión no válido: $connectionName");
                }
                
                // Credenciales comunes
                $uid        = $envVars['USER'];
                $pwd        = $envVars['PASS'];
                $charset    = $envVars['CHARACTER'];

                $connectionInfo = [
                    "Database" => $dbName,
                    "UID" => $uid,
                    "PWD" => $pwd,
                    "CharacterSet" => $charset,
                    "ReturnDatesAsStrings" => true // Facilita el manejo de fechas
                ];

                $conn = sqlsrv_connect($serverName, $connectionInfo);

                if ($conn === false) {
                    throw new Exception("Error al conectar con SQL Server ($connectionName): " . print_r(sqlsrv_errors(), true));
                }
                
                // Guardamos la conexión exitosa en nuestro array de instancias
                self::$instances[$connectionName] = $conn;

            } catch (Exception $e) {
                die("Error Crítico al inicializar la base de datos: " . $e->getMessage());
            }
        }
        
        // Devolvemos la instancia (nueva o existente)
        return self::$instances[$connectionName];
    }
    
    // Prevenimos clonación y deserialización
    private function __clone() {}
    public function __wakeup() {}
}