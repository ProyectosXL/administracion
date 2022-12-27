
<?php

class RubroContable
{
    function __construct(){

        require_once './../../Class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('servidor');

    } 

    public function traerRubrosContables()
    {

        $sql = "SELECT * FROM RO_T_RUBROS_CONTABLES";

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

    function traerDescripcion($codigo)
    {

        $sql = "SELECT TOP 1 RUBRO_CONTABLE FROM RO_T_RUBROS_CONTABLES WHERE COD_RUBRO =   '$codigo'";
        $stmt = sqlsrv_query($this->cid_central, $sql);

        try{

            $dato = sqlsrv_fetch_array($stmt);
                
            echo $dato['RUBRO_CONTABLE'];

        } catch (\Throwable $th){
            print_r($th);
        } 

    }
}



if (isset($_GET['codigo'])) {
    $rubro = new RubroContable();
    $rubro->traerDescripcion($_GET['codigo']);
}
