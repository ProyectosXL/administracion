
<?php

$desde = $_GET['desde'];
$hasta = $_GET['hasta'];

class amortizar
{

    private function ejecutarQuery($sqlEnviado)
    {

     
        require_once './../../Class/conexion.php';   

        $cid = new Conexion();
        $cid_central = $cid->conectar('servidor');
        $sql = $sqlEnviado;

        $stmt = sqlsrv_query($cid_central, $sql);
        sqlsrv_execute($stmt);
    }

    public function insertRegistros($desde, $hasta)
    {

        try {
            //code...
            $sql = "EXEC RO_SP_AMORTIZAR_GASTOS '$desde', '$hasta'
            ";

            return $this->ejecutarQuery($sql);
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }
}

if($_GET['estado']==1)
{
$a=new amortizar();

$a->insertRegistros($desde, $hasta);
}