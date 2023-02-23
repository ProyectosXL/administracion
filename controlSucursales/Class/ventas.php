


<?php

class Ventas
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $this->conn = new Conexion;

    } 


    // public function traerVentas($desde, $hasta)
    // {
    //     $cid = $this->conn->conectar('locales');

    //     $sql = "SET DATEFORMAT YMD EXEC RO_RESUMEN_VENTA_SUCURSALES '2022-01-01', '2022-01-31'";

    //     $stmt = sqlsrv_query($cid, $sql);


    //     $v = [];

    //     try {

    //         $next_result = sqlsrv_next_result($stmt);

    //         // var_dump($next_result);
    //         return $next_result;

    //         // $num = 0;

    //         while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {
    //             // var_dump($num++); 
    //             // var_dump($row); 


    //             $v[] = $row;

    //         }

    //         // var_dump($v);
    
    //         return $v;

    //     } catch (\Throwable $th) {

    //         print_r($th);

    //     }

    // }


    public function traerVentas($desde, $hasta){

        $cid = $this->conn->conectar('central');
        

        $sql = " SET DATEFORMAT YMD EXEC [LAKERBIS].LOCALES_LAKERS.DBO.RO_RESUMEN_VENTA_SUCURSALES '2022-01-01', '2022-01-31' ";

        $stmt = sqlsrv_query($cid, $sql);

        $v = [];

        try {

            $next_result = sqlsrv_next_result($stmt);

            print_r($next_result);

            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {

                $v[] = $row;
    
            }
    
            var_dump($v);

        } catch (\Throwable $th) {

            print_r($th);

        }


    }


}
