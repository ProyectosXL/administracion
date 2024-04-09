<?php

class Ventas
{
    
    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $this->cid = new Conexion();

    } 

    
    public function ejercutarSP($desde, $hasta){   

        $cid_conexion = ($this->cid->env == 'DEV') ? $this->cid->conectar('central') : $this->cid->conectar('locales');

        $sql = "EXEC ".$this->cid->prefix."RO_RESUMEN_VENTA_SUCURSALES '$desde', '$hasta'";

        try {

            $stmt = sqlsrv_prepare($cid_conexion, $sql);
            $stmt = sqlsrv_execute($stmt);

        } catch (\Throwable $th) {

            print_r($th);

        }
    
    }

    public function traerVentas($desde, $hasta)
    {
        $cid_conexion = ($this->cid->env == 'DEV') ? $this->cid->conectar('central') : $this->cid->conectar('locales');   

        $this->ejercutarSP($desde,$hasta);

        $sql2 = "SELECT * FROM ".$this->cid->prefix."RO_T_RESUMEN_VENTA_SUCURSALES";

        $stmt = sqlsrv_query($cid_conexion, $sql2);

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

    public function ejercutarSP2($desde, $hasta){   

        $cid_conexion = ($this->cid->env == 'DEV') ? $this->cid->conectar('central') : $this->cid->conectar('locales');     

        $sql = "EXEC ".$this->cid->prefix."RO_SP_VENTAS_VS_COBRANZA_POR_COMPROBANTE '$desde', '$hasta'";
     
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
            $cid_conexion = $this->cid->conectar('suc_uy');
        }

        try {

            $stmt = sqlsrv_prepare($cid_conexion, $sql);
            $stmt = sqlsrv_execute($stmt);

        } catch (\Throwable $th) {

            print_r($th);

        }
    
    }

    public function traerComprobantes($desde, $hasta)
    {
        $cid_conexion = ($this->cid->env == 'DEV') ? $this->cid->conectar('central') : $this->cid->conectar('locales');   

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
            $cid_conexion = $this->cid->conectar('suc_uy');
        }
      

        $this->ejercutarSP2($desde,$hasta);

        $sql2 = "SELECT * FROM ".$this->cid->prefix."RO_T_VENTAS_VS_COBRANZA_POR_COMPROBANTE";

        $stmt = sqlsrv_query($cid_conexion, $sql2);

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

    public function confirmarVentaVsCobranza($nroSucursal ,$nroComprobante)
    {
        $cid_conexion = ($this->cid->env == 'DEV') ? $this->cid->conectar('central') : $this->cid->conectar('locales');  
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
            $cid_conexion = $this->cid->conectar('suc_uy');
        }
      

        $sql = "UPDATE ".$this->cid->prefix."CTA29 SET CONCILIADO = 1 WHERE NRO_SUCURS = '$nroSucursal' AND N_COMP = '$nroComprobante'";

        try{
            
            $stmt = sqlsrv_query($cid_conexion, $sql);
    
            return true;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

}
