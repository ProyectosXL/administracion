
<?php

class Proveedor
{

    private function retornarArray($sqlEnviado)
    {

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $cid_central = $cid->conectar('central');
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
        
        $sql = "SELECT COD_PROVEE, NOM_PROVEE FROM CPA01 WHERE COD_PROVEE LIKE 'Z%' ORDER BY 2 ASC";

        try{
            $rows = $this->retornarArray($sql);
            
            $myJSON = json_encode($rows);
            return $myJSON;

        } catch (\Throwable $th) {

            print_r($th);

        }

    }

}