
<?php

class CuentaContable
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('servidor');

    } 

    public function traerCuentasContables(){
 

        $sql = "SELECT COD_CUENTA, DESC_CUENTA FROM CUENTA WHERE COD_CUENTA BETWEEN '510100' AND '570200' ORDER BY DESC_CUENTA";
        
        $stmt = sqlsrv_query( $this->cid_central, $sql );

        try {

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

        $sql = "SELECT TOP 1 DESC_CUENTA FROM CUENTA WHERE COD_CUENTA = '$codigo'";
        $stmt = sqlsrv_query($this->cid_central, $sql);
        try{

            $dato = sqlsrv_fetch_array($stmt);
                
            // echo $dato['DESC_AUXILIAR'];
           echo $dato['DESC_CUENTA'];

        } catch (\Throwable $th){
            print_r($th);
        }


    }
}

if (isset($_GET['codigo'])) {
    $cuentaContable = new CuentaContable();
    $cuentaContable->traerDescripcion($_GET['codigo']);
}
