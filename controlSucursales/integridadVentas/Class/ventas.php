<?php

class Ventas
{
    private $cid;
    private $cid_conn;
    
    function __construct(){

        // Ajustar la ruta para que apunte a la clase conexion desde integridadVentas/Class/
        require_once __DIR__.'/../../../class/conexion.php';
        $this->cid = new Conexion();

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
            $this->cid_conn = $this->cid->conectar('suc_uy');
        }else{
            $this->cid_conn = $this->cid->conectar('locales');
        }

    } 

    public function ejercutarSP2($desde, $hasta){   
 
        $sql = "EXEC ".$this->cid->prefix."RO_SP_VENTAS_VS_COBRANZA_POR_COMPROBANTE '$desde', '$hasta'";
     
        try {
            $stmt = sqlsrv_prepare($this->cid_conn, $sql);
            $stmt = sqlsrv_execute($stmt);
        } catch (\Throwable $th) {
            print_r($th);
        }
    
    }

    public function traerComprobantes($desde, $hasta)
    {

        $this->ejercutarSP2($desde,$hasta);

        $sql2 = "SELECT * FROM ".$this->cid->prefix."RO_T_VENTAS_VS_COBRANZA_POR_COMPROBANTE";

        $stmt = sqlsrv_query($this->cid_conn, $sql2);

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

    public function confirmarVentaVsCobranza($nroSucursal, $nroComprobante)
    {

        $sql = "UPDATE ".$this->cid->prefix."CTA29 SET CONCILIADO = 1 WHERE NRO_SUCURS = '$nroSucursal' AND N_COMP = '$nroComprobante'";

        try{
            
            $stmt = sqlsrv_query($this->cid_conn, $sql);
    
            return true;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

}
