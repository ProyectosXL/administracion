
<?php
try {
    require_once __DIR__.'/../../class/conexion.php';
    $cid = new Conexion();

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }


    if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
        $cid_central  = $cid->conectar('uy');
    }else{
        $cid_central = $cid->conectar('central');

    }
    
    $fecha = $_POST['fecha'];
    $codCentro = $_POST['codCentro'];
    $auxiliar = $_POST['auxiliar'];
    $sector = $_POST['sector'];
    $codCuenta = $_POST['codCuenta'];
    $cuenta = $_POST['cuenta'];
    $importe = $_POST['importe'];
    $leyenda = $_POST['leyenda'];
    $codRubro = $_POST['codRubro'];
    $rubro = $_POST['rubro'];
    $codProrrateo = $_POST['codProrrateo'];
    $descProrrateo = $_POST['descProrrateo'];
    $suc = $_POST['suc'];
    $amortizar = $_POST['amortizar'];
    $fechaAlta = Date('Y-m-d');
    $periodo = str_replace("0","",substr($fecha, 5, 2)).'-'.substr($fecha, 0, 4);


    $sql1 = "INSERT INTO RO_T_INTEGRAL_CUENTAS_2 ([MODULO],[COD_AUXILIAR],[FECHA],[DESC_AUXILIAR],[SECTOR],[COD_CUENTA],[DESC_CUENTA],[SALDO],[DESC_LEYENDA],
            [COD_RUBRO],[RUBRO_CONTABLE],[COD_PRORRATEO],[DESC_PRORRATEO],[NUM_SUCURSAL],[CONTROLADO],[AMORTIZAR],[FECHA_ALTA])
            VALUES ('CUENTAS2','$codCentro','$fecha','$auxiliar','$sector','$codCuenta','$cuenta',$importe,'$leyenda','$codRubro',
            '$rubro','$codProrrateo','$descProrrateo','$suc',1,'$amortizar','$fechaAlta')";
    
    $stmt1 = sqlsrv_query($cid_central, $sql1);
    sqlsrv_execute($stmt1);


    $queryIdentity = "select @@identity";
    $stmtIdentity = sqlsrv_query($cid_central, $queryIdentity);

    $id = null;
    
    while ($v = sqlsrv_fetch_array($stmtIdentity)) {
        $id = $v;
    }

    $idInsertado = $id[0];

    $sql2 = "INSERT INTO RO_T_INTEGRAL_TANGO_2 ([MODULO],[COD_AUXILIAR],[FECHA],[DESC_AUXILIAR],[SECTOR],[COD_CUENTA],[DESC_CUENTA],[SALDO],[DESC_LEYENDA],
            [T_COMP],[RAZON_SOCIAL],[PROVEEDOR],[N_COMP],[IMPUTACIONES],[COD_RUBRO],[RUBRO_CONTABLE],[COD_PRORRATEO],[DESC_PRORRATEO],[NUM_SUCURSAL],[EXCLUIR],
            [CONTROLADO],[AMORTIZAR],[AMORTIZADO],[PERIODO],[ID_AA],[PRORRATEADO], [ID_CTA_2])
            VALUES ('CUENTAS2','$codCentro','$fecha','$auxiliar','$sector','$codCuenta','$cuenta',$importe,'$leyenda',NULL,NULL,NULL,NULL,NULL,'$codRubro',
            '$rubro','$codProrrateo','$descProrrateo','$suc',0,1,'$amortizar',NULL,NULL,NULL,NULL, $idInsertado)";
    $stmt2 = sqlsrv_query($cid_central, $sql2);
    sqlsrv_execute($stmt2);

} catch (Exception $e) {
    echo 'Excepción capturada: ',  $e->getMessage(), "\n";
}
