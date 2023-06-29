<?php

$desde = $_GET['desde'];
$hasta = $_GET['hasta'];

$p = new Procesar();
$p->procesarr($desde, $hasta);

class Procesar
{

    public function procesarr($desde, $hasta)
    {
        try {
            require_once __DIR__ . '/../../class/conexion.php';
            $cid = new Conexion();

            $cid_central = $cid->conectar('central');

          /*   $result=[]; */
            $sql = "EXEC RO_SP_PROCESAR_DATAWAREHOUSE_IE ?,?";

            $params = array($desde, $hasta);
            $stmt = sqlsrv_query($cid_central, $sql, $params);
           /*  $next_result = sqlsrv_next_result($stmt); */
            /*  $next_result = sqlsrv_next_result($stmt);
            $next_result = sqlsrv_next_result($stmt); */
            do{
                while($row=sqlsrv_fetch_array($stmt))
                {
                    $result[]=$row;
                }
            }while(sqlsrv_next_result($stmt));

            /* $salida['resultado'] = sqlsrv_rows_affected($stmt); */
            /* echo "Rows affected: " . sqlsrv_rows_affected($stmt) . "<br />"; */
            echo json_encode($result);
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }
}
