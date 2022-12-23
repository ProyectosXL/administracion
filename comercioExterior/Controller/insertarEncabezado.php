<?php

require_once '../Class/conexion.php';
$cid = new Conexion();
$cid_central = $cid->conectar();

$cod_proveedor = $_POST['cod_proveedor'];
$proveedor = $_POST['proveedor'];
$contenedor = $_POST['contenedor'];
$despacho = $_POST['despacho'];
$material = $_POST['material'];
$origen = $_POST['origen'];
$fechaEmbarque = $_POST['fechaEmbarque'];
$facturaProveedor = $_POST['facturaProveedor'];
$fechaFactura = $_POST['fechaFactura'];
$ordenCompra = $_POST['ordenCompra'];
$formaPago = $_POST['formaPago'];
$numeroBl = $_POST['numeroBl'];
$tipoCambio = $_POST['tipoCambio'];
$valorFobDolar = $_POST['valorFobDolar'];
$valorFobPeso = $_POST['valorFobPeso'];
$fechaArribo = $_POST['fechaArribo'];
$fechaDespacho = $_POST['fechaDespacho'];

print_r($_POST);

try {


    $sql = "INSERT INTO RO_T_IMPORTACIONES_ENCABEZADO(FECHA_MOV, COD_PROVEE, PROVEEDOR, CONTENEDOR, DESPACHO, MATERIAL, ORIGEN, FECHA_EMB, FACTURA, FECHA_FACT, 
        ORDEN_COMPRA, FORMA_PAGO, NUMERO_BL, TIPO_CAMBIO, VALOR_FOB_DOLAR, VALOR_FOB_PESO, FECHA_ARR, FECHA_DESP_ADU)

        VALUES (GETDATE(),'$cod_proveedor','$proveedor','$contenedor','$despacho','$material','$origen','$fechaEmbarque','$facturaProveedor','$fechaFactura',
        '$ordenCompra','$formaPago','$numeroBl',$tipoCambio,$valorFobDolar,$valorFobPeso,'$fechaArribo','$fechaDespacho')

    ";
    $stmt = sqlsrv_query($cid_central, $sql);
} catch (Exception $e) {
    echo 'Excepción capturada: ',  $e->getMessage(), "\n";
}
