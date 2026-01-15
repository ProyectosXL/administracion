<?php

class Puerto
{
    private function retornarArray($sqlEnviado)
    {
        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        // Siempre usar entorno Argentina para tablas de parámetros
        $db = 'central';
        $cid_central = $cid->conectar($db);
        
        if ($cid_central === false) {
            error_log("Error de conexión a la base de datos: " . print_r(sqlsrv_errors(), true));
            throw new Exception("No se pudo establecer conexión con la base de datos");
        }
        
        $stmt = sqlsrv_query($cid_central, $sqlEnviado);
        
        if ($stmt === false) {
            error_log("Error en la consulta SQL: " . print_r(sqlsrv_errors(), true));
            throw new Exception("Error al ejecutar la consulta");
        }
        
        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt)) {
            $rows[] = $v;
        }
        
        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    /**
     * Obtiene todos los puertos activos
     * @return array Lista de puertos
     */
    public function traerPuertos()
    {
        $sql = "SELECT ID, NOMBRE, PAIS, ACTIVO, FECHA_CREACION, FECHA_MODIFICACION 
                FROM RO_T_CE_PUERTOS 
                WHERE ACTIVO = 1 
                ORDER BY NOMBRE ASC";
        
        try {
            return $this->retornarArray($sql);
        } catch (Exception $e) {
            error_log("Error al traer puertos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los puertos (para administración)
     * @return array Lista completa de puertos
     */
    public function traerTodosLosPuertos()
    {
        $sql = "SELECT ID, NOMBRE, PAIS, ACTIVO, FECHA_CREACION, FECHA_MODIFICACION 
                FROM RO_T_CE_PUERTOS 
                ORDER BY NOMBRE ASC";
        
        try {
            return $this->retornarArray($sql);
        } catch (Exception $e) {
            error_log("Error al traer todos los puertos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Inserta un nuevo puerto
     */
    public function insertarPuerto($nombre, $pais = null, $activo = 1)
    {
        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        // Siempre usar entorno Argentina para tablas de parámetros
        $db = 'central';
        $cid_central = $cid->conectar($db);
        
        $sql = "INSERT INTO RO_T_CE_PUERTOS (NOMBRE, PAIS, ACTIVO, FECHA_CREACION, FECHA_MODIFICACION) 
                VALUES (?, ?, ?, GETDATE(), GETDATE())";
        
        $params = array($nombre, $pais, $activo);
        $stmt = sqlsrv_query($cid_central, $sql, $params);
        
        if ($stmt === false) {
            error_log("Error al insertar puerto: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        
        sqlsrv_free_stmt($stmt);
        return true;
    }

    /**
     * Actualiza un puerto existente
     */
    public function actualizarPuerto($id, $nombre, $pais, $activo)
    {
        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $cid_central = $cid->conectar($db);
        
        $sql = "UPDATE RO_T_CE_PUERTOS 
                SET NOMBRE = ?, PAIS = ?, ACTIVO = ?, FECHA_MODIFICACION = GETDATE() 
                WHERE ID = ?";
        
        $params = array($nombre, $pais, $activo, $id);
        $stmt = sqlsrv_query($cid_central, $sql, $params);
        
        if ($stmt === false) {
            error_log("Error al actualizar puerto: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        
        sqlsrv_free_stmt($stmt);
        return true;
    }

    /**
     * Elimina (desactiva) un puerto
     */
    public function eliminarPuerto($id)
    {
        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        // Siempre usar entorno Argentina para tablas de parámetros
        $db = 'central';
        $cid_central = $cid->conectar($db);
        
        $sql = "UPDATE RO_T_CE_PUERTOS SET ACTIVO = 0, FECHA_MODIFICACION = GETDATE() WHERE ID = ?";
        
        $params = array($id);
        $stmt = sqlsrv_query($cid_central, $sql, $params);
        
        if ($stmt === false) {
            error_log("Error al eliminar puerto: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        
        sqlsrv_free_stmt($stmt);
        return true;
    }
}
