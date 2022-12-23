
<?php

class CentroCosto
{

    public function traerCentroCostos(){
        try {

            $servidor_central = 'servidor';
            $conexion_central = array( "Database"=>"LAKER_SA", "UID"=>"sa", "PWD"=>"Axoft1988", "CharacterSet" => "UTF-8");
            $cid_central = sqlsrv_connect($servidor_central, $conexion_central);
             
         } catch (PDOException $e){
                 echo $e->getMessage();
         }

        $sql = "SELECT * FROM RO_T_CENTRO_DE_COSTOS
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

        $sql = "SELECT TOP 1 DESC_AUXILIAR, SECTOR, NUM_SUCURSAL FROM RO_T_CENTRO_DE_COSTOS WHERE COD_AUXILIAR = '$codigo'";
        $stmt = sqlsrv_query($cid_central, $sql);


        $dato = sqlsrv_fetch_array($stmt);
            
        // echo $dato['DESC_AUXILIAR'];
       echo json_encode($dato);


    }
}

if (isset($_GET['codigo'])) {
    $centroCosto = new CentroCosto();
    $centroCosto->traerDescripcion($_GET['codigo']);
}
