
<?php

// $periodo = str_replace("0","",substr($hasta, 5, 2)).'-'.substr($hasta, 0, 4);

class Gastos
{

    public function traerGastos($desde, $hasta){
        try {

            $servidor_central = 'servidor';
            $conexion_central = array( "Database"=>"LAKER_SA", "UID"=>"sa", "PWD"=>"Axoft1988", "CharacterSet" => "UTF-8");
            $cid_central = sqlsrv_connect($servidor_central, $conexion_central);
             
         } catch (PDOException $e){
                 echo $e->getMessage();
         }

        $sql = "SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO IS NULL AND FECHA BETWEEN '$desde' AND '$hasta' AND PRORRATEADO IS NULL    
                    UNION ALL
                SELECT * FROM RO_T_INTEGRAL_TANGO_2 WHERE AMORTIZADO = 1 AND AMORTIZAR IS NULL AND PERIODO = CAST(DATEPART(MONTH, '$hasta') AS VARCHAR)+'-'+CAST(DATEPART(YEAR, '$hasta') AS VARCHAR) AND PRORRATEADO IS NULL
        ";
        $stmt = sqlsrv_query( $cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        $myJSON = json_encode($rows);

        return $myJSON;
    }

    public function traerGastos2($desde, $hasta){
        try {

            $servidor_central = 'servidor';
            $conexion_central = array( "Database"=>"LAKER_SA", "UID"=>"sa", "PWD"=>"Axoft1988", "CharacterSet" => "UTF-8");
            $cid_central = sqlsrv_connect($servidor_central, $conexion_central);
             
         } catch (PDOException $e){
                 echo $e->getMessage();
         }

        $sql = "SELECT * FROM RO_T_INTEGRAL_CUENTAS_2 WHERE FECHA BETWEEN '$desde' AND '$hasta'
        ";
        $stmt = sqlsrv_query( $cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        $myJSON = json_encode($rows);

        return $myJSON;
    }

}  