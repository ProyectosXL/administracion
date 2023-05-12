
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
    
    $porcentaje = ($value['IMPORTE_$'] / $dataEncabezado['VALOR_FOB_PESO']) * 100;
    $porcentajeParseado = (number_format((float)$porcentaje, 2, '.', '')); 

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
        
        $sobreFob = $porcentajeParseado;
    }else{
        $sobreFob = $sobreFob + $porcentajeParseado;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <title>Document</title>
</head>

    
<style type="text/css" media="print">

    body{font-size:12px }
    
    @page {
        size: portrait;   /* auto is the initial value */
        margin: 15px;  /* this affects the margin in the printer settings */
    }

</style>
<body class="d-flex flex-column min-vh-100 border border-secondary">

    <div class="">
        <div class="row">
            <div class="col-3 border border-secondary" >
                <div class="row ps-3" >PROVEEDOR</div>
                <div class="row ps-3">DESPACHO N°</div>
                <div class="row ps-3">MATERIAL</div>
                <div class="row ps-3">ORIGEN</div>
                <div class="row ps-3">FECHA EMBARQUE</div>
                <div class="row ps-3">FECHA ARRIBO</div>
                <div class="row ps-3">FACTURA PROVEEDOR</div>
                <div class="row ps-3">FECHA FACTURA</div>
                <div class="row ps-3">ORDEN DE COMPRA</div>
                <div class="row ps-3">FORMA DE PAGO</div>
                <div class="row ps-3"><br></div>
                <div class="row ps-3"><br></div> 
                <div class="row ps-3">TC DEL DESPACHO</div>
           </div>
           <div class="col-5 border border-secondary" >
               <div class="row ps-2 " style=" white-space: pre;"><?=$dataEncabezado['PROVEEDOR'] ?></div>
                <div class="row ps-2"><?=$dataEncabezado['DESPACHO'] ?></div>
                <div class="row ps-2"><?=$dataEncabezado['MATERIAL'] ?></div>
                <div class="row ps-2"><?=$dataEncabezado['ORIGEN'] ?></div>
                <div class="row ps-2"><?=$dataEncabezado['FECHA_EMB']->format('d/m/Y') ?></div>
                <div class="row ps-2"><?=$dataEncabezado['FECHA_ARR']->format('d/m/Y') ?></div>
                <div class="row ps-2"><?=$dataEncabezado['FACTURA'] ?></div>
                <div class="row ps-2"><?=$dataEncabezado['FECHA_FACT']->format('d/m/Y') ?></div>
                <div class="row ps-2"><?=$dataEncabezado['ORDEN_COMPRA'] ?></div>
                <div class="row ps-2"><?=$dataEncabezado['FORMA_PAGO'] ?></div>
                <div class="row ps-2"><br></div>
                <div class="row ps-2"><br></div>
                <div class="row ps-2"><?=$dataEncabezado['TIPO_CAMBIO'] ?></div>
                <div class="row ps-2"><br></div>
           </div>
           <div class="col-4 border-bottom border-secondary" >

                <div class="row">
                    <div class="col">Número BL</div>
                    <div class="col"><?= $dataEncabezado['NUMERO_BL']?></div>
                </div>

                <div class="row"><br></div>
                <div class="row"><br></div>

                <div class="row">
                    <div class="col" style =" white-space: pre;">VALOR F.O.B</div>
                    <div class="col">U$S</div>
                    <div class="col pe-3"><?= $totalDeGastos?></div>
                </div>

                <div class="row">
                    <div class="col" style =" white-space: pre;">VALOR F.O.B </div>
                    <div class="col">$</div>
                    <div class="col pe-3"><?= $dataEncabezado['VALOR_FOB_PESO']?></div>
                </div>
                <div class="row">
                    <div class="col" style =" white-space: pre;">GASTOS </div>
                    <div class="col">  $</div>
                    <div class="col pe-3"><?=$importeEnPesos?></div>
                </div>  
                <div class="row"><br></div>
                <div class="row"><br></div>
                <div class="row"><br></div>
                <div class="row"><br></div>
                <div class="row"><br></div>
                <div class="row"><br></div>

                <div class="row" style =" white-space: pre;">
                    <div class="col" style =" white-space: pre;">Costo Nacionalizacion $</div>
                    <div class="col"><?= $sobreFob?>% </div>
                </div>  

           </div>
        </div>
        <!-- TABLE -->
        <div class="row border-bottom border-dark">
            <div class="col-3 mr-2 border-end border-dark text-center">GASTOS</div>
            <div class="col-1 mr-2 border-end border-dark text-center" style="font-size:10px">IMPORTE U$S</div>
            <div class="col-1 mr-2 border-end border-dark text-center">TC</div>
            <div class="col-2 mr-2 border-end border-dark text-center" style="font-size:10px">IMPORTE $</div>
            <div class="col-1 mr-2 border-end border-dark text-center"  style="font-size:10px">% SOBRE F.O.B</div>
            <div class="col-4 mr-2 border-end border-dark text-center">OBSERVACIONES</div>
        </div>

        <?php 
            $countRow = 0;
            foreach ($dataDetalle as $key => $value) {

                $porcentaje = ($value['IMPORTE_$'] / $dataEncabezado['VALOR_FOB_PESO']) * 100;
                                    $porcentajeParseado = (number_format((float)$porcentaje, 2, ',', '')); 
        ?>
            <div class="row">
                <div class="col-3 mr-2 border-end border-dark text-left ps-3" style="font-size:12px"> <?= $value['GASTOS']; ?> </div>
                <div class="col-1 mr-2 border-end border-dark" style="font-size:11px;text-align:right" ><?php   echo number_format($value['IMPORTE_U$S'], 2, ',', '.'); ?></div>
                <div class="col-1 mr-2 border-end border-dark " style="font-size:12px;text-align:right" ><?php echo number_format($value['TIPO_CAMBIO'], 2, ',', '');  ?></div>
                <div class="col-2 mr-2 border-end border-dark " style="font-size:12px;text-align:right"><?php echo number_format($value['IMPORTE_$'], 2, ',', '.'); ?></div>
                <div class="col-1 mr-2 border-end border-dark" style="font-size:12px;text-align:right" ><?= $porcentajeParseado ?>%</div>
                <div class="col-4 mr-2 border-end border-dark text-center" style="font-size:12px" ><?= $value['OBSERVACIONES']; ?></div>
            </div>
        <?php
                $countRow++;
            }
    
            
            $height = 730 - ($countRow * 20);


            if($countRow > 5){
                $height += 10; 
            }
            if($countRow > 10){
                $height += 15; 
            }


        ?>

        <div class="row ">
            <div class="col-3 border-end border-dark text-center d-flex flex-column" style="height:<?= $height ?>px"></div>
            <div class="col-1 border-end border-dark text-center"></div>
            <div class="col-1 border-end border-dark text-center"></div>
            <div class="col-2 border-end border-dark text-center"></div>
            <div class="col-1 border-end border-dark text-center"></div>
            <div class="col-4 border-end border-dark text-center"></div>
        </div>

    </div>
</body>

<footer class="mt-auto mb-6">

    <div class="row border border-dark">
        <div class="col-3 mr-2 border-end border-dark text-center" style="font-size:10px">Total gastos costeables</div>
        <div class="col-1 mr-2 border-end border-dark text-center" style="font-size:10px"><?= $totalDeGastos;?></div>
        <div class="col-1 mr-2 border-end border-dark text-center" style="font-size:10px"></div>
        <div class="col-2 mr-2 border-end border-dark text-center" style="font-size:10px"><?= $importeEnPesos;?></div>
        <div class="col-1 mr-2 border-end border-dark text-center" style="font-size:10px"><?= $sobreFob;?></div>
        <div class="col-4 mr-2 border-end border-dark text-center" style="font-size:10px"></div>
    </div>
    
</footer>

</html>
<script>
       window.print();
</script>