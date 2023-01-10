
<?php

class CentroCosto
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

    public function traerCentroCostos(){

        $sql = "SELECT * FROM RO_T_CENTRO_DE_COSTOS";

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
        $sql = "SELECT TOP 1 DESC_AUXILIAR, SECTOR, NUM_SUCURSAL FROM RO_T_CENTRO_DE_COSTOS WHERE COD_AUXILIAR = '$codigo'";
        try {
           
           $stmt = sqlsrv_query($this->cid_central, $sql);
   
   
           $dato = sqlsrv_fetch_array($stmt);
               
           // echo $dato['DESC_AUXILIAR'];
          echo json_encode($dato);
       
        } catch (\Throwable $th){
            print_r($th);
        }


    }
}

if (isset($_GET['codigo'])) {
    $centroCosto = new CentroCosto();
    $centroCosto->traerDescripcion($_GET['codigo']);
}
