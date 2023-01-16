
<?php

$desde = $_GET['desde'];
$hasta = $_GET['hasta'];

$p = new prorratear();
$p->prorratearRegistros($desde, $hasta);
// $desde = '2022-01-01';
// $hasta = '2022-01-31';

class prorratear
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

    public function prorratearRegistros($desde, $hasta)
    {
        try {
            require_once '../Class/conexion.php';
            $cid = new Conexion();
            $cid_central = $cid->conectar();

            $sql = "DECLARE @ResultForPos int;
            EXEC @ResultForPos = RO_SP_PRORRATEAR_INTEGRAL ?, ?
            SELECT @ResultForPos as valor";

            $params = array($desde, $hasta);
            $stmt = sqlsrv_query($cid_central, $sql, $params);
            $next_result = sqlsrv_next_result($stmt);
            $next_result = sqlsrv_next_result($stmt);
            $next_result = sqlsrv_next_result($stmt);
            $salida['resultado']=sqlsrv_rows_affected($stmt);
            /* echo "Rows affected: " . sqlsrv_rows_affected($stmt) . "<br />"; */
            echo json_encode($salida);
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }
}