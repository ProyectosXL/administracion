
<?php

class Prorrateo
{

    public function traerMetodosProrrateo(){
        try {

            $servidor_central = 'servidor';
            $conexion_central = array( "Database"=>"LAKER_SA", "UID"=>"sa", "PWD"=>"Axoft1988", "CharacterSet" => "UTF-8");
            $cid_central = sqlsrv_connect($servidor_central, $conexion_central);
             
         } catch (PDOException $e){
                 echo $e->getMessage();
         }

        $sql = "SELECT * FROM RO_T_METODOS_PRORRATEO
        ";
        $stmt = sqlsrv_query( $cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }

        $myJSON = json_encode($rows);

        return $myJSON;
    }

    function traerDescripcion($codigo)
    {
        try {

            $servidor_central = 'servidor';
            $conexion_central = array("Database" => "LAKER_SA", "UID" => "sa", "PWD" => "Axoft1988", "CharacterSet" => "UTF-8");
            $cid_central = sqlsrv_connect($servidor_central, $conexion_central);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        $sql = "SELECT TOP 1 DESC_PRORRATEO  FROM RO_T_METODOS_PRORRATEO WHERE COD_PRORRATEO = '$codigo'";
        $stmt = sqlsrv_query($cid_central, $sql);


        $dato = sqlsrv_fetch_array($stmt);
            
        echo $dato['DESC_PRORRATEO'];

    }
}



if (isset($_GET['codigo'])) {
    $rubro = new Prorrateo();
    $rubro->traerDescripcion($_GET['codigo']);
}

