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
<style> body {  /* to centre page on screen*/ margin-left: auto; margin-right: auto;  padding-left: 30px; padding-right: 30px;} </style>

<body >
    <div class="row" >
        <div class ="col-2" style = "border:  solid black 1px;">
            <div class ="row">PROVEEDOR</div>
            <div class ="row">DESPACHO N°</div>
            <div class ="row">MATERIAL:</div>
            <div class ="row">ORIGEN</div>
            <div class ="row">FECHA DE EMBARQUE</div>
            <div class ="row">FECHA ARRIBO</div>
            <div class ="row">FACTURA PROVEEDOR</div>
            <div class ="row">FECHA FACTURA</div>
            <div class ="row">ORDEN DE COMPRA</div>
            <div class ="row">FORMA DE PAGO</div>
            <div class ="row"></div>
            <div class ="row mt-3 mb-2">TC DEL DESPACHO</div>
            <div class ="row"></div>
        </div>
        <div class ="col-6" style = "border:  solid black 1px">
            <div class ="row"><?=$dataEncabezado['PROVEEDOR']?></div>
            <div class ="row"><?=$dataEncabezado['DESPACHO']?></div>
            <div class ="row"><?=$dataEncabezado['MATERIAL']?></div>
            <div class ="row"><?=$dataEncabezado['ORIGEN']?></div>
            <div class ="row"><?=$dataEncabezado['FECHA_EMB']->format('d/m/Y');?></div>
            <div class ="row"><?=$dataEncabezado['FECHA_ARR']->format('d/m/Y');?></div>
            <div class ="row"><?=$dataEncabezado['FACTURA']?></div>
            <div class ="row"><?=$dataEncabezado['FECHA_FACT']->format('d/m/Y');?></div>
            <div class ="row"><?=$dataEncabezado['ORDEN_COMPRA']?>SDASDA</div>
            <div class ="row"><?=$dataEncabezado['FORMA_PAGO']?></div>
            <div class ="row"></div>
            <div class ="row mt-3 mb-2"><?=$dataEncabezado['TIPO_CAMBIO']?></div>
            <div class ="row"></div>
        </div>
        <div class ="col-4" style = "border:  solid black 1px">
            <div class ="row">
                <div class="col">Numero BL</div>                
                <div class="col"><?= $dataEncabezado['NUMERO_BL']?></div>                
            </div>
            <div class ="row mt-4">
                <div class="col">VALOR F.O.B  <strong>U$S</strong></div>                
                <div class="col"><?= $dataEncabezado['VALOR_FOB_DOLAR']?></div>    
            </div>
            <div class ="row">
                <div class="col">VALOR F.O.B  <strong>$</strong></div>                
                <div class="col"><?= $dataEncabezado['VALOR_FOB_PESO']?></div>    
            </div>
            <div class ="row">
                <div class="col">GASTOS <strong>$</strong></div>                
                <div class="col"><?= $dataEncabezado['VALOR_FOB_DOLAR']?></div>    
            </div>
            <div class ="row mt-5">
                <div class="col">costo nacionalizacion<strong>$</strong></div>                
                <div class="col"><?= $dataEncabezado['VALOR_FOB_DOLAR']?></div>   
            </div>
    
        </div>
    </div>

    <div class="row" >
        <div class ="col-2" style = "text-align: center;border:  solid black 1px">GASTOS</div>

        <div class ="col-1" style = "text-align: center; border:solid black 1px; width: 12.499999995%">IMPORTE<br>U$S</div>

        <div class ="col-1" style = "text-align: center; border:  solid black 1px;width: 12.499999995%">TC</div>

        <div class ="col-1" style = "text-align: center; border:  solid black 1px;width: 12.499999995%">IMPORTE<br>$</div>

        <div class ="col-1" style = "text-align: center; border:  solid black 1px;width: 12.499999995%">$SOBRE<br>F.O.B.$</div>

        <div class ="col-4" style = "text-align: center;border:  solid black 1px">OBSERVACIONES</div>
    </div>
  
    <div class="row">
        <div class ="col-2" style = "border:  solid black 1px">
            
            <?php foreach ($dataDetalle as $key => $value) { ?>
                <div class="row"> <?php echo $value['GASTOS']; ?></div>
            <?php } ?>

        </div>

        <div class ="col-1" style = "text-align: right;border:  solid black 1px ;width: 12.499999995%">

            <?php foreach ($dataDetalle as $key => $value) { ?>
              <?php echo $value['IMPORTE_U$S']; ?><br>
            <?php } ?>

        </div>
        <div class ="col-1" style = "text-align: right;border:  solid black 1px ;width: 12.499999995%">

            <?php foreach ($dataDetalle as $key => $value) { ?>
                <?php echo $value['TIPO_CAMBIO']; ?><br>
            <?php } ?>

        </div>
        <div class ="col-1" style = "text-align: right;border:  solid black 1px ;width: 12.499999995%">

            <?php foreach ($dataDetalle as $key => $value) { ?>
                <?php echo $value['IMPORTE_$']; ?><br>
            <?php } ?>

        </div>
        <div class ="col-1" style = "text-align: right;border:  solid black 1px ;width: 12.499999995%">

            <?php foreach ($dataDetalle as $key => $value) { ?>
                <?php echo $value['PORCENTAJE']; ?><br>
            <?php } ?>

        </div>
        <div class ="col-4" style = "text-align: right;border:  solid black 1px">

            <?php foreach ($dataDetalle as $key => $value) { ?>
                <?php echo $value['OBSERVACIONES']; ?><br>
            <?php } ?>

        </div>
    </div>
    <div class="row">
        <div class ="col-2" style = "border:  solid black 1px">
            <div class="row">TOTAL GASTOS</div>
            <div class="row">COSTEABLES</div>
        </div>
        <div class ="col-1" style = "text-align: right; border:  solid black 1px;width: 12.499999995%">
                <?= $totalDeGastos ?>
        </div>
        <div class ="col-1" style = "text-align: right; border:  solid black 1px;width: 12.499999995%">
          
        </div>
        <div class ="col-1" style = "text-align: right; border:  solid black 1px;width: 12.499999995%">
               <?= $importeEnPesos ?>
        </div>
        <div class ="col-1" style = "text-align: right; border:  solid black 1px;width: 12.499999995%">
                <?= $sobreFob ?>  
        </div>
        <div class ="col-4" style = "border:  solid black 1px;">
            
        </div>
    </div>
</body>
</html>
