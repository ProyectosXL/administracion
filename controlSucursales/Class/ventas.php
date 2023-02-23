


<?php

class Ventas
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $this->conn = new Conexion;

    } 

    private function retornarArray($sqlEnviado)
    {

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $cid_central = $cid->conectar('central');
        $sql = $sqlEnviado;
        
        $stmt = sqlsrv_query($cid_central, $sql);
        
        $rows = array();
        
        while ($v = sqlsrv_fetch_array($stmt)) {
            $rows[] = $v;
        }
        

        return $rows;
    }


    public function traerVentas($desde, $hasta)
    {
        $cid = $this->conn->conectar('locales');

        $sql = "SET DATEFORMAT YMD EXEC RO_RESUMEN_VENTA_SUCURSALES '2022-01-01', '2022-01-31'";

        $stmt = sqlsrv_query($cid, $sql);


        $v = [];

        try {

            $next_result = sqlsrv_next_result($stmt);

            var_dump($next_result);

            $num = 0;

            while ($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)) {
                var_dump($num++); 
                var_dump($row); 


                $v[] = $row;

            }

            var_dump($v);
    
            return $v;

        } catch (\Throwable $th) {

            print_r($th);

        }

    }

}
