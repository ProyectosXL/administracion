<?php
/**
 * =========================================================================
 *  SOLUCIÓN DEFINITIVA - Usando la clase personalizada del proyecto
 * =========================================================================
 *  Este código utiliza la clase 'classEnv.php' que ya existe en tu
 *  proyecto, asegurando compatibilidad total.
 */

// 1. Incluimos TU PROPIA clase para manejar el .env. Esta es la clave.
require_once __DIR__ . '/../../../class/classEnv.php';

class Database {
    // Mantenemos el patrón Singleton y la conexión pública para que los otros archivos no fallen
    private static $instance = null;
    public $conn;

    /**
     * El constructor ahora usa la clase DotEnv de tu proyecto
     */
    private function __construct() {
        try {
            // 2. Creamos una instancia de TU clase DotEnv, tal como en el ejemplo funcional
            $vars = new DotEnv(__DIR__ . '/../../../.env');
            $envVars = $vars->listVars();

            // 3. Obtenemos las variables para la conexión CENTRAL del array que nos devuelve tu clase
            $serverName = $envVars['HOST_CENTRAL'];
            $dbName     = $envVars['DATABASE_CENTRAL'];
            $uid        = $envVars['USER'];
            $pwd        = $envVars['PASS']; // Usamos PASS_LOCALES de tu .env original
            $charset    = $envVars['CHARACTER'];    // Usamos CHARACTER de tu .env

            $connectionInfo = [
                "Database" => $dbName,
                "UID" => $uid,
                "PWD" => $pwd,
                "CharacterSet" => $charset
            ];

            // 4. Conectamos usando sqlsrv, como siempre
            $this->conn = sqlsrv_connect($serverName, $connectionInfo);

            if ($this->conn === false) {
                // Si la conexión falla, lanzamos un error claro
                throw new Exception("Error al conectar con SQL Server: " . print_r(sqlsrv_errors(), true));
            }

        } catch (Exception $e) {
            // Capturamos cualquier error (clase no encontrada, .env no encontrado, conexión fallida)
            die("Error Crítico al inicializar la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Método para obtener la instancia única de la clase (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Prevenimos que se pueda clonar o deserializar la instancia para mantener el Singleton
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("No se puede deserializar un Singleton");
    }
}