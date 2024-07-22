<?php

require_once '../Class/encabezado.php';
$cid = new Encabezado();

$datosDeCabezera = [];
$datosDeCabezera['cod_proveedor'] = $_POST['cod_proveedor'];
$datosDeCabezera['proveedor'] = $_POST['proveedor'];
$datosDeCabezera['contenedor']= $_POST['contenedor'];
$datosDeCabezera['despacho']= $_POST['despacho'];
$datosDeCabezera['material']= $_POST['material'];
$datosDeCabezera['origen']= $_POST['origen'];
$datosDeCabezera['fechaEmbarque']= $_POST['fechaEmbarque'];
$datosDeCabezera['facturaProveedor'] = $_POST['facturaProveedor'];
$datosDeCabezera['fechaFactura']= $_POST['fechaFactura'];
$datosDeCabezera['formaPago']= $_POST['formaPago'];
$datosDeCabezera['numeroBl']= $_POST['numeroBl'];
$datosDeCabezera['tipoCambio']= $_POST['tipoCambio'];
$datosDeCabezera['valorFobDolar']= $_POST['valorFobDolar'];
$datosDeCabezera['valorFobPeso']= $_POST['valorFobPeso'];
$datosDeCabezera['fechaArribo']= $_POST['fechaArribo'];
$datosDeCabezera['fechaDespacho']= $_POST['fechaDespacho'];
$datosDeCabezera['ocm']= $_POST['ocm'];

$ordenes = json_decode($_POST['ordenCompra'], true);

$arrayResult = [];

foreach ($ordenes as  $orden) {

    if(strlen(trim($orden)) == 13){
        $orden = ' '.trim($orden);
    }

    $datosDeCabezera['ordenCompra'] = $orden ;

    

    $result = $cid->insertarEncabezado($datosDeCabezera);

    if ($result) {
        $arrayResult[] = $result;
    }

}

echo (json_encode($arrayResult));




