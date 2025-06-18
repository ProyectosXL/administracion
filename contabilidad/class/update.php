<?php
require_once __DIR__.'/../../class/conexion.php';

$cid = new Conexion();
$cid_central = $cid->conectar('central');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
    $cid_central = $cid->conectar('uy');
}else{
    $cid_central = $cid->conectar('central');

}

if (isset($_POST['codRubro'])) {
    try {
        $codRubro = $_POST['codRubro'];
        $RubroDescripcion = $_POST['descRubro'];
        /*  $Cuenta = $_POST['cuenta']; */
        $ID= $_POST['ID'];
        $sql = "UPDATE RO_T_INTEGRAL_TANGO_2 SET COD_RUBRO='$codRubro',RUBRO_CONTABLE='$RubroDescripcion' WHERE ID='$ID'
    ";
        $stmt = sqlsrv_query($cid_central, $sql);

        sqlsrv_execute($stmt);
    } catch (Exception $e) {
        echo 'Excepción capturada: ',  $e->getMessage(), "\n";
    }
} else {
    if (isset($_POST['codProrrateo'])) {
        try {
            $codProrrateo = $_POST['codProrrateo'];
            $ProrrateoDescripcion = $_POST['descRubro'];
            /*  $Cuenta = $_POST['cuenta']; */
            $ID= $_POST['ID'];
            $sql = "UPDATE RO_T_INTEGRAL_TANGO_2 SET COD_PRORRATEO='$codProrrateo',DESC_PRORRATEO='$ProrrateoDescripcion' WHERE ID='$ID'
        ";
            $stmt = sqlsrv_query($cid_central, $sql);

            sqlsrv_execute($stmt);
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    } else {
        if (isset($_POST['checked'])) {
            try {
                $excluir = $_POST['checked'];
                $ID= $_POST['ID'];

                $sql = "UPDATE RO_T_INTEGRAL_TANGO_2 SET excluir = $excluir WHERE ID=$ID";

                $stmt = sqlsrv_query($cid_central, $sql);

                sqlsrv_execute($stmt);
            } catch (Exception $e) {
                echo 'Excepción capturada: ',  $e->getMessage(), "\n";
            }
        }else{
            if (isset($_POST['controlado'])) {
                try {
                    $controlado = $_POST['controlado'];
                    $ID= $_POST['ID'];
    
                    $sql = "UPDATE RO_T_INTEGRAL_TANGO_2 SET controlado=$controlado WHERE ID=$ID";
    
                    $stmt = sqlsrv_query($cid_central, $sql);
    
                    sqlsrv_execute($stmt);
                } catch (Exception $e) {
                    echo 'Excepción capturada: ',  $e->getMessage(), "\n";
                }
        }else{
            if (isset($_POST['amortizar'])) {
                try {
                    $amortizar = $_POST['amortizar'];
                    $ID= $_POST['ID'];
    
                    $sql = "UPDATE RO_T_INTEGRAL_TANGO_2 SET AMORTIZAR = $amortizar WHERE ID = $ID";
    
                    $stmt = sqlsrv_query($cid_central, $sql);
    
                    sqlsrv_execute($stmt);
                } catch (Exception $e) {
                    echo 'Excepción capturada: ',  $e->getMessage(), "\n";
                }
            }
        }
    }
}
}