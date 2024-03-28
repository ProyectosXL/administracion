
<?php

class Gastos
{
    function __construct() {

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = 'central';
        $this->cid_central = $cid->conectar($db);


    } 


    public function traerGastos(){

        $table = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'RO_T_MAESTRO_GASTOS_NACIONALIZACION_UY' : 'RO_T_MAESTRO_GASTOS_NACIONALIZACION';
        
        $sql = " SELECT * FROM $table ORDER BY ORDEN ";

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