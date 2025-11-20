
<?php

class Proveedor
{

    private function retornarArray($sqlEnviado)
    {

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $cid_central = $cid->conectar($db);
        
        // Validar que la conexión sea exitosa
        if ($cid_central === false) {
            error_log("Error de conexión a la base de datos: " . print_r(sqlsrv_errors(), true));
            throw new Exception("No se pudo establecer conexión con la base de datos");
        }
        
        $sql = $sqlEnviado;
        $stmt = sqlsrv_query($cid_central, $sql);
        
        // Validar que la consulta sea exitosa
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


    public function traerProveedores()
    {
        
        $sql = "SELECT COD_PROVEE, NOM_PROVEE FROM CPA01 WHERE COD_PROVEE LIKE 'Z%' AND FECHA_INHA = '1800-01-01 00:00:00.000' ORDER BY 2 ASC";

        try{
            $rows = $this->retornarArray($sql);
            
            $myJSON = json_encode($rows);
            return $myJSON;

        } catch (\Throwable $th) {
            error_log("Error en traerProveedores: " . $th->getMessage());
            // Retornar array vacío en formato JSON en caso de error
            return json_encode([]);
        }

    }

}