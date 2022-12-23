
<?php

class CuentaContable
{

    public function traerCuentasContables(){
        try {

            $servidor_central = 'servidor';
            $conexion_central = array( "Database"=>"LAKER_SA", "UID"=>"sa", "PWD"=>"Axoft1988", "CharacterSet" => "UTF-8");
            $cid_central = sqlsrv_connect($servidor_central, $conexion_central);
             
         } catch (PDOException $e){
                 echo $e->getMessage();
         }

        $sql = "SELECT COD_CUENTA, DESC_CUENTA FROM CUENTA WHERE COD_CUENTA BETWEEN '510100' AND '570200' ORDER BY DESC_CUENTA
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

        $sql = "SELECT TOP 1 DESC_CUENTA FROM CUENTA WHERE COD_CUENTA = '$codigo'";
        $stmt = sqlsrv_query($cid_central, $sql);

        $dato = sqlsrv_fetch_array($stmt);
            
        // echo $dato['DESC_AUXILIAR'];
       echo $dato['DESC_CUENTA'];


    }
}

if (isset($_GET['codigo'])) {
    $cuentaContable = new CuentaContable();
    $cuentaContable->traerDescripcion($_GET['codigo']);
}
