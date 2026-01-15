<?php

class Terminal
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
     * Obtiene todas las terminales activas según el entorno
     * @param string $entorno 'central' para Argentina, 'uy' para Uruguay, 'ambos' para todos
     * @return array Lista de terminales
     */
    public function traerTerminales($entorno = null)
    {
        if ($entorno === null) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $entorno = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        }
        
        // Normalizar entorno
        $entornoFiltro = ($entorno === 'uy') ? 'UY' : 'ARG';
        
        $sql = "SELECT ID, NOMBRE, ENTORNO, ACTIVO, FECHA_CREACION, FECHA_MODIFICACION 
                FROM RO_T_CE_TERMINALES 
                WHERE ACTIVO = 1 
                AND (ENTORNO = '$entornoFiltro' OR ENTORNO = 'AMBOS')
                ORDER BY NOMBRE ASC";
        
        try {
            return $this->retornarArray($sql);
        } catch (Exception $e) {
            error_log("Error al traer terminales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todas las terminales (para administración)
     * @return array Lista completa de terminales
     */
    public function traerTodasLasTerminales()
    {
        $sql = "SELECT ID, NOMBRE, ENTORNO, ACTIVO, FECHA_CREACION, FECHA_MODIFICACION 
                FROM RO_T_CE_TERMINALES 
                ORDER BY ENTORNO ASC, NOMBRE ASC";
        
        try {
            return $this->retornarArray($sql);
        } catch (Exception $e) {
            error_log("Error al traer todas las terminales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Inserta una nueva terminal
     */
    public function insertarTerminal($nombre, $entorno, $activo = 1)
    {
        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        // Siempre usar entorno Argentina para tablas de parámetros
        $db = 'central';
        $cid_central = $cid->conectar($db);
        
        $sql = "INSERT INTO RO_T_CE_TERMINALES (NOMBRE, ENTORNO, ACTIVO, FECHA_CREACION, FECHA_MODIFICACION) 
                VALUES (?, ?, ?, GETDATE(), GETDATE())";
        
        $params = array($nombre, $entorno, $activo);
        $stmt = sqlsrv_query($cid_central, $sql, $params);
        
        if ($stmt === false) {
            error_log("Error al insertar terminal: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        
        sqlsrv_free_stmt($stmt);
        return true;
    }

    /**
     * Actualiza una terminal existente
     */
    public function actualizarTerminal($id, $nombre, $entorno, $activo)
    {
        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $cid_central = $cid->conectar($db);
        
        $sql = "UPDATE RO_T_CE_TERMINALES 
                SET NOMBRE = ?, ENTORNO = ?, ACTIVO = ?, FECHA_MODIFICACION = GETDATE() 
                WHERE ID = ?";
        
        $params = array($nombre, $entorno, $activo, $id);
        $stmt = sqlsrv_query($cid_central, $sql, $params);
        
        if ($stmt === false) {
            error_log("Error al actualizar terminal: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        
        sqlsrv_free_stmt($stmt);
        return true;
    }

    /**
     * Elimina (desactiva) una terminal
     */
    public function eliminarTerminal($id)
    {
        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        // Siempre usar entorno Argentina para tablas de parámetros
        $db = 'central';
        $cid_central = $cid->conectar($db);
        
        $sql = "UPDATE RO_T_CE_TERMINALES SET ACTIVO = 0, FECHA_MODIFICACION = GETDATE() WHERE ID = ?";
        
        $params = array($id);
        $stmt = sqlsrv_query($cid_central, $sql, $params);
        
        if ($stmt === false) {
            error_log("Error al eliminar terminal: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        
        sqlsrv_free_stmt($stmt);
        return true;
    }
}
