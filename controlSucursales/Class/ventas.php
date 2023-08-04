<?php

class Ventas
{
    
    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $this->cid = new Conexion();

    } 

    
    public function ejercutarSP($desde, $hasta){   

        $cid_conexion = ($cid->env == 'DEV') ? $cid->conectar('central') : $cid->conectar('locales');

        $sql = "EXEC ".$cid->prefix."RO_RESUMEN_VENTA_SUCURSALES '$desde', '$hasta'";

        try {

            $stmt = sqlsrv_prepare($cid_conexion, $sql);
            $stmt = sqlsrv_execute($stmt);

        } catch (\Throwable $th) {

            print_r($th);

        }
    
    }

    public function traerVentas($desde, $hasta)
    {
        $cid_conexion = ($cid->env == 'DEV') ? $cid->conectar('central') : $cid->conectar('locales');   

        $this->ejercutarSP($desde,$hasta);

        $sql2 = "SELECT * FROM ".$cid->prefix."RO_T_RESUMEN_VENTA_SUCURSALES";

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

        $cid_conexion = ($cid->env == 'DEV') ? $cid->conectar('central') : $cid->conectar('locales');     

        $sql = "EXEC ".$cid->prefix."RO_SP_VENTAS_VS_COBRANZA_POR_COMPROBANTE '$desde', '$hasta'";

        try {

            $stmt = sqlsrv_prepare($cid_conexion, $sql);
            $stmt = sqlsrv_execute($stmt);

        } catch (\Throwable $th) {

            print_r($th);

        }
    
    }

    public function traerComprobantes($desde, $hasta)
    {
        $cid_conexion = ($cid->env == 'DEV') ? $cid->conectar('central') : $cid->conectar('locales');   

        $this->ejercutarSP2($desde,$hasta);

        $sql2 = "SELECT * FROM ".$cid->prefix."RO_T_VENTAS_VS_COBRANZA_POR_COMPROBANTE";

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
        $cid_conexion = ($cid->env == 'DEV') ? $cid->conectar('central') : $cid->conectar('locales');  

        $sql = "UPDATE ".$cid->prefix."CTA29 SET CONCILIADO = 1 WHERE NRO_SUCURS = '$nroSucursal' AND N_COMP = '$nroComprobante'";

        try{
            
            $stmt = sqlsrv_query($cid_conexion, $sql);
    
            return true;
        
        } catch (\Throwable $th){
            print_r($th);
        }

    }

}
