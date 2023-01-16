
<?php

// $periodo = str_replace("0","",substr($hasta, 5, 2)).'-'.substr($hasta, 0, 4);

class Gastos
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

    public function traerGastos($desde, $hasta, $estado){

    if($estado == '0'){

            $sql = "SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO IS NULL AND FECHA BETWEEN '$desde' AND '$hasta' AND PRORRATEADO IS NULL AND CONTROLADO = 0 AND EXCLUIR = 0  
                        UNION ALL
                    SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO = 1 AND AMORTIZAR IS NULL AND PERIODO = CAST(DATEPART(MONTH, '$hasta') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$hasta') AS VARCHAR) AND PRORRATEADO IS NULL AND CONTROLADO = 0 AND EXCLUIR = 0";
    
    }elseif($estado == '1'){

            $sql="SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO IS NULL AND FECHA BETWEEN '$desde' AND '$hasta' 
                  AND PRORRATEADO IS NULL AND CONTROLADO IS NOT NULL AND EXCLUIR = 0 AND AMORTIZAR > 0 AND AMORTIZADO IS NULL
            ";
    }elseif($estado == '2'){

            $sql ="SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE FECHA BETWEEN '$desde' AND '$hasta' AND EXCLUIR = 1";

    }elseif($estado == '3'){

            $sql ="SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE FECHA BETWEEN '$desde' AND '$hasta' AND EXCLUIR = 0 AND (COD_RUBRO IS NULL OR COD_PRORRATEO IS NULL)";

     }else{
            $sql = "SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO IS NULL AND FECHA BETWEEN '$desde' AND '$hasta' AND PRORRATEADO IS NULL 
                        UNION ALL
                    SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO = 1 AND AMORTIZAR IS NULL AND PERIODO = CAST(DATEPART(MONTH, '$hasta') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$hasta') AS VARCHAR) AND PRORRATEADO IS NULL 
            ";
        }
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

    public function traerGastos2($desde, $hasta){

        $sql = "SELECT * FROM RO_T_INTEGRAL_CUENTAS_2 WHERE FECHA BETWEEN '$desde' AND '$hasta'
        ";
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

}  