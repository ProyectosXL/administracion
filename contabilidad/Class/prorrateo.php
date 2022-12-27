
<?php

class Prorrateo
{
    function __construct(){

        require_once './../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('servidor');

    } 

    public function traerMetodosProrrateo(){

        $sql = "SELECT * FROM RO_T_METODOS_PRORRATEO";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        try{

            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
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

        $sql = "SELECT TOP 1 DESC_PRORRATEO  FROM RO_T_METODOS_PRORRATEO WHERE COD_PRORRATEO = '$codigo'";
        $stmt = sqlsrv_query($this->cid_central, $sql);

        try{

            $dato = sqlsrv_fetch_array($stmt);          
            echo $dato['DESC_PRORRATEO'];

        } catch (\Throwable $th){
            print_r($th);
        }


    }
}



if (isset($_GET['codigo'])) {
    $rubro = new Prorrateo();
    $rubro->traerDescripcion($_GET['codigo']);
}

