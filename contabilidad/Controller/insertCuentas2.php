
<?php
try {
    require_once __DIR__.'/../../class/conexion.php';
    $cid = new Conexion();
    $cid_central = $cid->conectar('servidor');

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


    $sql = "INSERT INTO RO_T_INTEGRAL_CUENTAS_2 ([MODULO],[COD_AUXILIAR],[FECHA],[DESC_AUXILIAR],[SECTOR],[COD_CUENTA],[DESC_CUENTA],[SALDO],[DESC_LEYENDA],
            [COD_RUBRO],[RUBRO_CONTABLE],[COD_PRORRATEO],[DESC_PRORRATEO],[NUM_SUCURSAL],[CONTROLADO],[AMORTIZAR],[FECHA_ALTA])
            VALUES ('CUENTAS2','$codCentro','$fecha','$auxiliar','$sector','$codCuenta','$cuenta',$importe,'$leyenda','$codRubro',
            '$rubro','$codProrrateo','$descProrrateo','$suc',1,'$amortizar','$fechaAlta')

            INSERT INTO RO_T_INTEGRAL_TANGO_2 ([MODULO],[COD_AUXILIAR],[FECHA],[DESC_AUXILIAR],[SECTOR],[COD_CUENTA],[DESC_CUENTA],[SALDO],[DESC_LEYENDA],
            [T_COMP],[RAZON_SOCIAL],[PROVEEDOR],[N_COMP],[IMPUTACIONES],[COD_RUBRO],[RUBRO_CONTABLE],[COD_PRORRATEO],[DESC_PRORRATEO],[NUM_SUCURSAL],[EXCLUIR],
            [CONTROLADO],[AMORTIZAR],[AMORTIZADO],[PERIODO],[ID_AA],[PRORRATEADO])
            VALUES ('CUENTAS2','$codCentro','$fecha','$auxiliar','$sector','$codCuenta','$cuenta',$importe,'$leyenda',NULL,NULL,NULL,NULL,NULL,'$codRubro',
            '$rubro','$codProrrateo','$descProrrateo','$suc',NULL,1,'$amortizar',NULL,'$periodo',NULL,NULL)
    ";
    $stmt = sqlsrv_query($cid_central, $sql);
    sqlsrv_execute($stmt);
} catch (Exception $e) {
    echo 'Excepción capturada: ',  $e->getMessage(), "\n";
}
