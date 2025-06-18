
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
        $sql = $sqlEnviado;
        
        $stmt = sqlsrv_query($cid_central, $sql);
        
        $rows = array();
        
        while ($v = sqlsrv_fetch_array($stmt)) {
            $rows[] = $v;
        }
        

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

            print_r($th);

        }

    }

}