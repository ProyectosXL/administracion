
<?php

class Prorrateo
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

    public function traerMetodosProrrateo(){

        $sql = "SELECT * FROM RO_T_METODOS_PRORRATEO WHERE ACTIVO = 1";

        $stmt = sqlsrv_query( $this->cid_central, $sql );

        if ($stmt === false) {
            return json_encode([]);
        }

        try{

            $rows = array();

            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }

            $myJSON = json_encode($rows);

            return $myJSON;

        } catch (\Throwable $th){
            return json_encode([]);
        }
    }

    public function traerMetodosProrrateoAdmin($estado = null) {
        if ($estado === 1) {
            $sql = "SELECT COD_PRORRATEO, DESC_PRORRATEO, ACTIVO FROM RO_T_METODOS_PRORRATEO WHERE ACTIVO = 1 ORDER BY COD_PRORRATEO";
        } elseif ($estado === 0) {
            $sql = "SELECT COD_PRORRATEO, DESC_PRORRATEO, ACTIVO FROM RO_T_METODOS_PRORRATEO WHERE ACTIVO = 0 ORDER BY COD_PRORRATEO";
        } else {
            $sql = "SELECT COD_PRORRATEO, DESC_PRORRATEO, ACTIVO FROM RO_T_METODOS_PRORRATEO ORDER BY COD_PRORRATEO";
        }

        $stmt = sqlsrv_query($this->cid_central, $sql);

        try {
            $rows = [];
            while ($v = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $v;
            }
            return json_encode($rows);
        } catch (\Throwable $th) {
            return json_encode([]);
        }
    }

    public function cambiarEstadoProrrateo($cod, $activo) {
        $sql  = "UPDATE RO_T_METODOS_PRORRATEO SET ACTIVO = ? WHERE COD_PRORRATEO = ?";
        $params = [(int)$activo, $cod];
        $stmt = sqlsrv_query($this->cid_central, $sql, $params);
        return $stmt !== false;
    }

    function traerDescripcion($codigo)
    {

        $sql = "SELECT TOP 1 DESC_PRORRATEO  FROM RO_T_METODOS_PRORRATEO WHERE COD_PRORRATEO = '$codigo'";
        $stmt = sqlsrv_query($this->cid_central, $sql);

        try{

            $dato = sqlsrv_fetch_array($stmt);          
            echo $dato['DESC_PRORRATEO'];

        } catch (\Throwable $th){
            print_r($th);
        }


    }
}



if (isset($_GET['codigo'])) {
    $rubro = new Prorrateo();
    $rubro->traerDescripcion($_GET['codigo']);
}

