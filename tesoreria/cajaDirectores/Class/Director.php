<?php
require_once __DIR__ . '/../../../class/conexion.php';

/**
 * Clase Director
 * Gestiona la obtención de directores desde la base de datos APPS
 */
class Director
{
    private $db;
    private $conexion;

    public function __construct($db = null)
    {
        if ($db !== null) {
            $this->db = $db;
        } else {
            $this->conexion = new Conexion();
            $this->db = $this->conexion->conectar('apps');

            if ($this->db === false) {
                throw new Exception("Error al conectar con la base de datos APPS en Director");
            }
        }
    }

    /**
     * Obtiene la lista de directores desde la base de datos sistemas
     * Consulta: SELECT NOMBRE FROM RO_V_DIRECTORES
     * @return array
     */
    public function obtenerDirectores()
    {
        try {
            $sql = "SELECT NOMBRE FROM RO_T_DIRECTORES ORDER BY NOMBRE";
            $stmt = sqlsrv_query($this->db, $sql);

            if ($stmt === false) {
                throw new Exception("Error en la consulta: " . print_r(sqlsrv_errors(), true));
            }

            $directores = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir a formato capitalizado (primera letra mayúscula, resto minúscula)
                $directores[] = ucwords(strtolower($row['NOMBRE']));
            }

            sqlsrv_free_stmt($stmt);

            return $directores;
        } catch (Exception $e) {
            error_log("Error al obtener directores: " . $e->getMessage());
            return []; // Retornar array vacío en caso de error
        }
    }

    /**
     * Verifica si un director existe en la base de datos
     * @param string $nombreDirector
     * @return bool
     */
    public function existeDirector($nombreDirector)
    {
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