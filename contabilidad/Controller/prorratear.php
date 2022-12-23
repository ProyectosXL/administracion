
<?php

$desde = $_GET['desde'];
$hasta = $_GET['hasta'];

// $desde = '2022-01-01';
// $hasta = '2022-01-31';

class prorratear
{

    private function ejecutarQuery($sqlEnviado)
    {
        try {
            require_once '../Class/conexion.php';

            $cid = new Conexion();
            $cid_central = $cid->conectar();
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

    public function prorratearRegistros($desde, $hasta)
    {

        $sql = "EXEC RO_SP_PRORRATEAR_INTEGRAL '$desde', '$hasta'";

        $stmt = sqlsrv_query($cid_central, $sql);

        print(sqlsrv_next_result($stmt));

    }
}
