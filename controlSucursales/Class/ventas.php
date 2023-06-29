
<?php

class Ventas
{
    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $this->cid = new Conexion();
     

    } 

    
    public function ejercutarSP($desde, $hasta){   
        $cid_central = $this->cid->conectar('central');     

        $sql = "EXEC [LAKERBIS].LOCALES_LAKERS.DBO.RO_RESUMEN_VENTA_SUCURSALES '$desde', '$hasta'";

        try {

            $stmt = sqlsrv_prepare($cid_central, $sql);
            $stmt = sqlsrv_execute($stmt);

            /* return true; */

        } catch (\Throwable $th) {

            print_r($th);

        }
    
    }

    public function traerVentas($desde,$hasta)
    {
        $cid_central = $this->cid->conectar('central');    
        $this->ejercutarSP($desde,$hasta);

        $sql2 = "SELECT * FROM [LAKERBIS].LOCALES_LAKERS.DBO.RO_T_RESUMEN_VENTA_SUCURSALES";

        $stmt = sqlsrv_query($cid_central, $sql2);

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
        $cid_central = $this->cid->conectar('central');     

        $sql = "EXEC [LAKERBIS].LOCALES_LAKERS.DBO.RO_SP_VENTAS_VS_COBRANZA_POR_COMPROBANTE '$desde', '$hasta'";

        try {

            $stmt = sqlsrv_prepare($cid_central, $sql);
            $stmt = sqlsrv_execute($stmt);

            /* return true; */

        } catch (\Throwable $th) {

            print_r($th);

        }
    
    }

    public function traerComprobantes($desde,$hasta)
    {
        $cid_central = $this->cid->conectar('central');    
        $this->ejercutarSP2($desde,$hasta);

        $sql2 = "SELECT * FROM [LAKERBIS].LOCALES_LAKERS.DBO.RO_T_VENTAS_VS_COBRANZA_POR_COMPROBANTE";

        $stmt = sqlsrv_query($cid_central, $sql2);

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
