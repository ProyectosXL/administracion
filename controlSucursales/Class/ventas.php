


<?php

class Ventas
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


    public function traerVentas()
    {
        
        $sql = "SELECT * FROM [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS";

        try{
            $rows = $this->retornarArray($sql);
            
            $myJSON = json_encode($rows);
            return $myJSON;

        } catch (\Throwable $th) {

            print_r($th);

        }

    }

}
