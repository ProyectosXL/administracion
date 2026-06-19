
<?php

class RubroContable
{
    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    

        if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
            $this->cid_central  = $cid->conectar('uy');
        }else{
            $this->cid_central = $cid->conectar('central');

        }

    } 

    public function traerRubrosContables()
    {

        $sql = "SELECT * FROM RO_T_RUBROS_CONTABLES";

        $stmt = sqlsrv_query($this->cid_central, $sql);

        if ($stmt === false) {
            return json_encode([]);
        }

        try{

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
                $rows[] = $v;
            }

            $myJSON = json_encode($rows);

            return $myJSON;

        } catch (\Throwable $th){
            return json_encode([]);
        }

    }

    function traerDescripcion($codigo)
    {

        $sql = "SELECT TOP 1 RUBRO_CONTABLE FROM RO_T_RUBROS_CONTABLES WHERE COD_RUBRO =   '$codigo'";
        $stmt = sqlsrv_query($this->cid_central, $sql);

        if ($stmt === false) {
            return;
        }

        try{

            $dato = sqlsrv_fetch_array($stmt);

            echo $dato['RUBRO_CONTABLE'];

        } catch (\Throwable $th){
            // tabla no disponible en este entorno
        }

    }
}



if (isset($_GET['codigo'])) {
    $rubro = new RubroContable();
    $rubro->traerDescripcion($_GET['codigo']);
}
