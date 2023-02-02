
<?php

class Articulo
{
    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

    public function traerArticulosSinCostoNac($desde, $hasta)
    {

        $sql = "EXEC RO_SP_ARTICULOS_SIN_COSTO_NAC '$desde', '$hasta'";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try{
            
            $rows = array();
    
            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }
    
            $myJSON = json_encode($rows);
    
            return $myJSON;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

}


