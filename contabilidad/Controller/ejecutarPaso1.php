
<?php

$desde = $_GET['desde'];
$hasta = $_GET['hasta'];
$periodo = str_replace("0","",substr($desde, 5, 2)).'-'.substr($desde, 0, 4);

$p = new ejecutarPasos();
$p->ejecutarPaso1($desde, $hasta);
// $desde = '2022-01-01';
// $hasta = '2022-01-31';

class ejecutarPasos
{

    private function ejecutarQuery($sqlEnviado)
    {
        try {
            require_once __DIR__.'/../../class/conexion.php';

            $cid = new Conexion();
            $cid_central = $cid->conectar('central');
            $sql = $sqlEnviado;

            $stmt = sqlsrv_query($cid_central, $sql);
            sqlsrv_execute($stmt);
        }

        /*  print_r($stmt); */
        /* sqlsrv_execute($stmt); */
        /*  $dato = sqlsrv_fetch_array($stmt);
            var_dump($dato); */ catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function ejecutarPaso1($desde, $hasta)
    {
       
        try {
            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            $cid_central = $cid->conectar('central');

            $sql = "DECLARE @ResultForPos int;
            EXEC @ResultForPos = RO_SP_ARTICULOS_SIN_COSTO_NAC ?, ?
            SELECT @ResultForPos as valor";

            $params = array($desde, $hasta);
            $stmt = sqlsrv_query($cid_central, $sql, $params);
            $salida=array();
            /* $next_result = sqlsrv_next_result($stmt);
            $next_result = sqlsrv_next_result($stmt);
            $next_result = sqlsrv_next_result($stmt); */
            /* $salida['resultado']=sqlsrv_rows_affected($stmt); */
            do {
                while ($row = sqlsrv_fetch_array($stmt)) {
                   $salida[] = $row;
                }
             } while (sqlsrv_next_result($stmt)); 
          
             echo json_encode($salida);
<<<<<<< HEAD
            /* echo "Rows affected: " . sqlsrv_rows_affected($stmt) . "<br />"; */
            /* echo json_encode($salida); */
=======
          
>>>>>>> 5c00953e05fc439f1c2393d3bdf3ce23e186e5ff
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

}