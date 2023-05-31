
<?php

// $periodo = str_replace("0","",substr($hasta, 5, 2)).'-'.substr($hasta, 0, 4);

class Gasto
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

      

    public function traerGastos($desde, $hasta, $estado, $codRubro, $columna = null,$codCuenta = null){


        $queryColumna =" AND ( DESC_LEYENDA  LIKE '%$columna%' OR RAZON_SOCIAL LIKE '%$columna%' OR N_COMP LIKE '%$columna%')";
        $queryCodCuenta = "AND COD_CUENTA LIKE '$codCuenta'";

    if($estado == '0'){

            $sql = "SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE  AMORTIZADO IS NULL AND FECHA BETWEEN '$desde' AND '$hasta' AND PRORRATEADO IS NULL AND (CONTROLADO = 0 OR CONTROLADO IS NULL) AND EXCLUIR = 0  
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
            $sql = "SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE (AMORTIZADO IS NULL OR AMORTIZADO = 0) AND FECHA BETWEEN '$desde' AND '$hasta' AND PRORRATEADO IS NULL
                    AND COD_RUBRO LIKE '$codRubro' AND ( DESC_LEYENDA LIKE '%$columna%' OR RAZON_SOCIAL LIKE '%$columna%' OR N_COMP LIKE '%$columna%')AND COD_CUENTA LIKE '$codCuenta'
                    UNION ALL SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO = 1 AND AMORTIZAR IS NULL 
                    AND PERIODO BETWEEN CAST(DATEPART(MONTH, '$desde') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$desde') AS VARCHAR) AND CAST(DATEPART(MONTH, '$hasta') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$hasta') AS VARCHAR) 
                    AND PRORRATEADO IS NULL AND COD_RUBRO LIKE '$codRubro' 
                ";

    }
    if($columna != null){
        $sql = $sql.$queryColumna;
    }   
    if($codCuenta){
        $sql = $sql.$queryCodCuenta;
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

    public function traerGastosParaControl($desde, $hasta, $estado, $codRubro){

        $sql = "SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE (AMORTIZADO IS NULL OR AMORTIZADO = 0) AND FECHA BETWEEN '$desde' AND '$hasta' AND PRORRATEADO IS NULL
        AND COD_RUBRO LIKE '$codRubro'
        UNION ALL SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO = 1 AND AMORTIZAR IS NULL 
        AND PERIODO BETWEEN CAST(DATEPART(MONTH, '$desde') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$desde') AS VARCHAR) AND CAST(DATEPART(MONTH, '$hasta') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$hasta') AS VARCHAR) 
        AND PRORRATEADO IS NULL AND COD_RUBRO LIKE '$codRubro' ";

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

    public function updateGasto($id, $numSucursal, $codAuxiliar, $descAuxiliar, $sector){

        $sql = "UPDATE RO_T_INTEGRAL_TANGO_2 SET NUM_SUCURSAL = '$numSucursal', COD_AUXILIAR = '$codAuxiliar', DESC_AUXILIAR = '$descAuxiliar', SECTOR = '$sector' WHERE ID = '$id'";

        $stmt = sqlsrv_query( $this->cid_central, $sql );
  
        try {
            sqlsrv_execute($stmt);
      
        } catch (Exception $e) {
            print_r($e);
        }


    }

    public function cambiarValorSaldo($id, $saldo){

        $sql = "UPDATE RO_T_INTEGRAL_TANGO_2 SET saldo = '$saldo' WHERE ID = '$id'";

        $stmt = sqlsrv_prepare( $this->cid_central, $sql );
  
        try {
            sqlsrv_execute($stmt);
      
        } catch (Exception $e) {
            print_r($e);
        }
        

    }


    public function traerCodRubro ($codCuenta,$sector){

        $sql ="SELECT * from RO_T_RELACION_CUENTA_RUBRO_CONTABLE  where COD_CUENTA = '$codCuenta' AND SECTOR = '$sector'";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        try{

            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
    
            return $rows;

        } catch (\Throwable $th){
            print_r($th);
        }
    }

    public function eliminarGasto($id){
        
        $sql="DELETE FROM  RO_T_INTEGRAL_CUENTAS_2 WHERE ID_CTA_2 = '$id'";
        $sql2 = "DELETE FROM RO_T_INTEGRAL_TANGO_2 WHERE ID_CTA_2 = '$id'";

        try {

            $stmt = sqlsrv_query( $this->cid_central, $sql );
            $stmt2 = sqlsrv_query( $this->cid_central, $sql2 );
            return true;
            
        } catch (\Throwable $th) {
            throw $th;
        }
       
    }

}  