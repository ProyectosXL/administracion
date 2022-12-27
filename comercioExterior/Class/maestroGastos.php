
<?php

class Gastos
{
    function __construct() {

        require_once './../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 


    public function traerGastos(){

       

        $sql = "SELECT * FROM RO_T_MAESTRO_GASTOS_NACIONALIZACION";

        try{
            $stmt = sqlsrv_query( $this->cid_central, $sql );
    
            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th) {

            print_r($th);

        }

    }

}  