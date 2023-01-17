<?php 
require_once __DIR__.'./Controller/listarOrden.php';
$arrayEncabezado = listar($_GET['idEncabezado']);
// $arrayEncabezado = listar("112");
$dataEncabezado = $arrayEncabezado[0];
$dataDetalle = listarPorOrdenCompra($dataEncabezado['ID']);


$totalDeGastos = "";
$importeEnPesos = "";
$sobreFob = "";
$tc = "";
foreach ($dataDetalle as $value) {
    if($totalDeGastos == ""){
        $totalDeGastos = (float)$value['IMPORTE_U$S'];
    }else{
        $totalDeGastos = $totalDeGastos + (float)$value['IMPORTE_U$S'];
    }
    if($importeEnPesos == ""){
        $importeEnPesos = (float)$value['IMPORTE_$'];
    }else{
        $importeEnPesos = $importeEnPesos + (float)$value['IMPORTE_$'];
    }
    if($sobreFob == ""){
        $sobreFob = (float)$value['PORCENTAJE'];
    }else{
        $sobreFob = $sobreFob + (float)$value['PORCENTAJE'];
    }
}

$htmlContent =' 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- BOOTSTRAP -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
<!-- Icons font CSS-->
<link href="vendor/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">
<link href="vendor/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
<!-- Font special for pages-->
<link href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
<!-- Vendor CSS-->
<link href="vendor/select2/select2.min.css" rel="stylesheet" media="all">
<link href="vendor/datepicker/daterangepicker.css" rel="stylesheet" media="all">

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg"> -->
<!-- Main CSS-->
<!-- <link href="css/style.css" rel="stylesheet" media="all"> -->
</head>
<style> 

    body {  

        margin-left: auto; 
        margin-right: auto;  
        padding-left: 5px; 
        padding-right: 30px;
    }

    .headerLeft {
        border:  solid black 1px;
        width: 25%;
        float:left
    }

    .headerCenter {
        border:  solid black 1px;
        width: 293px;
        float:left;
    }

    .headerRight {
        border:  solid black 1px;
        float:left;
        width: 35%;
        height: 209px;
    }

    .separacion {
        clear:both;
        width: 20%;
    }

    .gastosIndice{
        border:solid black 1px;
        text-align: center;
        float:left;
        width: 25%;
        height: 3%;
    }
    .importeEnDolarIndice{
        border:solid black 1px;
        text-align: center;
        float:left;
        width: 71px;
        height: 3%;
        font-size: 12px;
    }
    .tipoCambioIndice {
        border:solid black 1px;
        text-align: center;
        float:left;
        width: 71px;
        height: 3%;
    }
    .importePesosIndice {
        border:solid black 1px;
        text-align: center;
        float:left;
        width: 71px;
        height: 3%;
        font-size: 12px;
    }

    .sobreFobIndice {
        border:solid black 1px;
        text-align: center;
        float:left;
        width: 74px;
        height: 3%;
        font-size: 12px;
    }
    .observacionesIndice {
        padding-left:10px;
        border:solid black 1px;
        text-align: center;
        float:left;
        width: 224px;
        height: 3%;
        font-size: 12px;
    }
    .bodyGastos{
        border:solid black 1px;
        text-align: center;
        float:left;
        width: 25%;
        height: 80%;
    }
    .bodyImporteDolar {
        border:solid black 1px;
        text-align: right;
        float:left;
        width: 10%;
        height: 80%;  
        padding-right: 4px; 
    }
    .bodyTc {
        border:solid black 1px;
        text-align: right;
        float:left;
        width: 10%;
        height: 80%;
        padding-right: 4px;
    }
    .bodyCenter {
        border:solid black 1px;
        text-align: right;
        float:left;
        width: 10%;
        height: 80%;
        padding-right: 4px;
    }
    .bodyCenterPorcentaje {
        padding-left: 4px !important;
        text-align: left !important;
    }
    .gastosCosteables {
        border:solid black 1px;
        text-align: center;
        float:left;
        width: 25%;
    }

</style>

<body>
    <div>
        <div>
            <div class ="headerLeft" >
                <div style="padding-left:2px">
                    <div>PROVEEDOR </div>
                    <div>DESPACHO N°</div>
                    <div>MATERIAL</div>
                    <div>ORIGEN</div>
                    <div>FECHA EMBARQUE</div>
                    <div>FECHA ARRIBO</div>
                    <div>FACTURA P</div>
                    <div>FECHA FACTURA</div>
                    <div>ORDEN DE COMPRA</div>
                    <div>FORMA DE PAGO</div>
                    <div></div>
                    <div >TC DEL DESPACHO</div>
                    <div></div>
                </div>
            </div>

            <div class ="headerCenter">
                <div style="padding-left:2px">
                    <div>'.$dataEncabezado['PROVEEDOR'].'</div>
                    <div>'.$dataEncabezado['DESPACHO'].'</div>
                    <div>'.$dataEncabezado['MATERIAL'].'</div>
                    <div>'.$dataEncabezado['ORIGEN'].'</div>
                    <div>'.$dataEncabezado['FECHA_EMB']->format('d/m/Y').'</div>
                    <div>'.$dataEncabezado['FECHA_ARR']->format('d/m/Y').'</div>
                    <div>'.$dataEncabezado['FACTURA'].'</div>
                    <div>'.$dataEncabezado['FECHA_FACT']->format('d/m/Y').'</div>
                    <div>'.$dataEncabezado['ORDEN_COMPRA'].'SDASDA</div>
                    <div>'.$dataEncabezado['FORMA_PAGO'].'</div>
                    <div></div>
                    <div>'.$dataEncabezado['TIPO_CAMBIO'].'</div>
                    <div></div>
                </div>
            </div>

            <div class ="headerRight">
                <div style="padding-left:2px">
                    <div>
                    <div>Numero BL &nbsp;&nbsp;&nbsp;<span>'. $dataEncabezado['NUMERO_BL'].'</span></div>                
                </div>
                <div>
                    <div><br></div>                
                </div>
                <div>
                    <div><br></div>                
                </div>
                <div>
                    <div>VALOR F.O.B  &nbsp;<strong>U$S</strong>'. $dataEncabezado['VALOR_FOB_DOLAR'].'</div>    
                </div>
                <div>
                    <div>VALOR F.O.B &nbsp; <strong>$</strong>'. $dataEncabezado['VALOR_FOB_PESO'].'</div>    
                </div>
                <div>
                    <div>GASTOS &nbsp; <strong>$</strong>'. $dataEncabezado['VALOR_FOB_DOLAR'].'</div>    
                </div>
                <div>
                    <div><br></div>                
                </div>
                <div>
                    <div><br></div>                
                </div>
                <div>
                    <div><br></div>                
                </div>
                <div>
                    <div><br></div>                
                </div>
                <div>
                    <div>costo nacionalizacion &nbsp;&nbsp;<strong>$</strong>'. $dataEncabezado['VALOR_FOB_DOLAR'].'</div>   
                </div>
            </div>
        </div>
    </div>   

    <div>
    <div class="separacion" >
        <div></div>
    </div>
    <div class="gastosIndice">
        <div style="padding-top:10px">GASTOS</div>
    </div>
    <div class="importeEnDolarIndice"> IMPORTE <div> U$S </div> </div>
    <div class="tipoCambioIndice">
    TC
    </div>
    <div class="importePesosIndice" >
    IMPORTE
    <div>$</div>
    </div>
    <div class="sobreFobIndice">
    $SOBRE<div>F.O.B</div>
    </div>
    <div class="observacionesIndice" >
    <div style="padding-top:10px">OBSERVACIONES</div>
    </div>

    </div>    
    <div>
    <div class="separacion">
    <div></div>
    </div>

    <div class="bodyGastos"><div>';

    foreach ($dataDetalle as $key => $value) { 
        if($value['GASTOS'] == "Gastos multa dest. Fuera termino"){
            $value['GASTOS'] = "Gastos multa dest.fuer.ter";
        }
        if($value['GASTOS'] == "Tasa estadistica monto maximo"){
            $value['GASTOS'] = "Tasa est monto max";
        }
        $htmlContent = $htmlContent .  '<div style="padding-bottom:10px"> ' .$value['GASTOS'].'</div>';
    } ;

    $htmlContent = $htmlContent .'

    </div></div>
    <div class="bodyImporteDolar">
    ';

    foreach ($dataDetalle as $key => $value) { 
        $htmlContent = $htmlContent .  '<div style="padding-bottom:10px;">' .$value['IMPORTE_U$S'].'</div>';
    } ;


    $htmlContent= $htmlContent .'</div>

    <div class="bodyTc">';

    foreach ($dataDetalle as $key => $value) { 
        $htmlContent = $htmlContent .  '<div style="padding-bottom:10px">' .$value['TIPO_CAMBIO'].'</div>';
    };

    $htmlContent= $htmlContent .'

    </div> <div class="bodyCenter">';
    foreach ($dataDetalle as $key => $value) { 
        $htmlContent = $htmlContent .  '<div style="padding-bottom:10px">' .$value['IMPORTE_$'].'</div>';
    };

    $htmlContent = $htmlContent.
    ' </div><div class="bodyCenter bodyCenterPorcentaje">';

    foreach ($dataDetalle as $key => $value) { 
        $htmlContent = $htmlContent .  '<div style="padding-bottom:10px"> %  ' .$value['PORCENTAJE'].'</div>';
    } ;

    $htmlContent=$htmlContent.

    ' </div>

    <div style="border:  solid black 1px;text-align: center;float:left;width: 234px;height: 80%" >';

    foreach ($dataDetalle as $key => $value) { 
        $htmlContent = $htmlContent .  '<div style="padding-bottom:10px">' .$value['OBSERVACIONES'].'</div>';
    } ;

    $htmlContent=$htmlContent.'
    </div> 

    <div class="" style="clear:both;" >
    <div><div class="gastosCosteables" >Total Gastos Costeables</div>

    <div class="" style="border:  solid black 1px;text-align: center;float:left;width: 72px" >'

    .$totalDeGastos.'

    </div><div class="" style="text-align: center;float:left;width: 71px" >'

    .$tc.'

    </div><div class="" style="border:  solid black 1px;text-align: center;float:left;width: 71px" >'

    .$importeEnPesos.'

    </div><div class="" style="border:  solid black 1px;text-align: center;float:left;width: 76px" > %  '

    .$sobreFob.'

    </div></div>
    </div> ';

    $htmlContent= $htmlContent .'</div></div>    </div>

</body>';




?>


<?php 
// var_dump($htmlContent);
// die();

require '../vendor/autoload.php';

// reference the Dompdf namespace
use Dompdf\Dompdf;

// instantiate and use the dompdf class

// print_r($htmlContent);
// die();

$dompdf = new Dompdf();
$dompdf->loadHtml($htmlContent);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream();
?>