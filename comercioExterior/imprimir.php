
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
<style>
    body{font-size:12px }
</style>
<body>

    <div class="container border border-secondary">
        <div class="row">
            <div class="col-2 border border-secondary" >
                <div class="row" style="">PROVEEDOR</div>
                <div class="row">DESPACHO N°</div>
                <div class="row">MATERIAL</div>
                <div class="row">ORIGEN</div>
                <div class="row">FECHA EMBARQUE</div>
                <div class="row">FECHA ARRIBO</div>
                <div class="row">FACTURA PROVEEDOR</div>
                <div class="row">FECHA FACTURA</div>
                <div class="row">ORDEN DE COMPRA</div>
                <div class="row">FORMA DE PAGO</div>
                <div class="row"><br></div>
                <div class="row">TC DEL DESPACHO</div>
                <div class="row"></div> 
           </div>
           <div class="col-6 border border-secondary" >
               <div class="row " style=" white-space: pre;"><?=$dataEncabezado['PROVEEDOR'] ?></div>
                 <div class="row"><?=$dataEncabezado['DESPACHO'] ?></div>
                <div class="row"><?=$dataEncabezado['MATERIAL'] ?></div>
                <div class="row"><?=$dataEncabezado['ORIGEN'] ?></div>
                <div class="row"><?=$dataEncabezado['FECHA_EMB']->format('d/m/Y') ?></div>
                <div class="row"><?=$dataEncabezado['FECHA_ARR']->format('d/m/Y') ?></div>
                <div class="row"><?=$dataEncabezado['FACTURA'] ?></div>
                <div class="row"><?=$dataEncabezado['FECHA_FACT']->format('d/m/Y') ?></div>
                <div class="row"><?=$dataEncabezado['ORDEN_COMPRA'] ?></div>
                <div class="row"><?=$dataEncabezado['FORMA_PAGO'] ?></div>
                <div class="row"><br></div>
                <div class="row"><br></div>
                <div class="row"><?=$dataEncabezado['TIPO_CAMBIO'] ?></div>
                <div class="row"><br></div>
           </div>
           <div class="col-4 border border-secondary" >

                <div class="row">
                    <div class="col">Número BL</div>
                    <div class="col"><?= $dataEncabezado['NUMERO_BL']?></div>
                </div>

                <div class="row"><br></div>
                <div class="row"><br></div>

                <div class="row">
                    <div class="col" style =" white-space: pre;">VALOR F.O.B</div>
                    <div class="col">U$S</div>
                    <div class="col"><?= $dataEncabezado['VALOR_FOB_DOLAR']?></div>
                </div>

                <div class="row">
                    <div class="col" style =" white-space: pre;">VALOR F.O.B </div>
                    <div class="col">$</div>
                    <div class="col"><?= $dataEncabezado['VALOR_FOB_PESO']?></div>
                </div>
                <div class="row">
                    <div class="col" style =" white-space: pre;">GASTOS </div>
                    <div class="col">  $</div>
                    <div class="col"><?= $dataEncabezado['VALOR_FOB_PESO']?></div>
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
        <div class="row">
           <div class="col-2 border border-secondary text-center">
                <div class="row" style="display: block;">GASTOS</div>
           </div>
           <div class="col-6 border border-secondary">
                <div class="row">
                    <div class="col border-end border-secondary text-center">IMPORTE U$S</div>
                    <div class="col border-end border-secondary text-center">TC</div>
                    <div class="col border-end border-secondary text-center">IMPORTE $</div>
                    <div class="col text-center">% SOBRE F.O.B</div>
                </div>
    
           </div>
           <div class="col-4 border border-secondary text-center">
                <div class="col">OBSERVACIONES</div>
           </div>
        </div>

        <?php 
            foreach ($dataDetalle as $key => $value) {
            
        ?>
                <div class="row text-center" style="padding-bottom:10px">
                    <div class="col-2 text-center">
                        <?= $value['GASTOS']; ?>
                    </div>
                    <div class="col-6 col-md-offset-3 text-center">
                        <div class="row ">
                            <div class="col"  ><?= $value['IMPORTE_U$S']; ?></div>
                            <div class="col"  ><?= $value['TIPO_CAMBIO']; ?></div>
                            <div class="col"  ><?= $value['IMPORTE_$']; ?></div>
                            <div class="col" ><?= $value['PORCENTAJE']; ?>%</div>
                        </div>
                    </div>
                    <div class="col-4" >
                        <?= $value['OBSERVACIONES']; ?>
                    </div>
                </div>
        <?php
            }
        ?>
        <div class="row">
            <div class="col" style="border:solid black 1px;">
                <div class="row">
                    <div class="col-2">total gastos costeables</div>
                    <div class="col-6" style="text-align:center">
                        <div class="row">
                            <div class="col border border-secondary" style="height:42px" >U$S <?= $totalDeGastos;?></div>
                            <div class="col border border-secondary"></div>
                            <div class="col border border-secondary"><?= $importeEnPesos;?></div>
                            <div class="col border border-secondary"><?= $sobreFob;?></div>
                        </div>
                    </div>
                    <div class="col-4" style="text-align:center"></div>
                </div>
        
            </div>

        </div>
    </div>
</body>
</html>
<script>
    //    window.print();
</script>