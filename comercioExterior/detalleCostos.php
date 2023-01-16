<?php

include 'Class/maestroGastos.php';

$gastos = new Gastos();
$todosLosGastos = $gastos->traerGastos();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <!-- Icons font CSS-->
    <link href="assets/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">
    <link href="assets/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <!-- Font special for pages-->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <!-- Vendor CSS-->
    <link href="assets/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="assets/datepicker/daterangepicker.css" rel="stylesheet" media="all">

    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">
    <!-- Main CSS-->
    <link href="css/style.css" rel="stylesheet" media="all">
    <title>Carga Costos</title>
</head>
<body>
    <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo" >
        <div class="wrapper wrapper--w680" style="margin-left: 12rem;">
            <div class="card card-1" style="width: 1200px; justify-content:center; text-align:center">
                <div class="card-heading"></div>
                <div class="card-body">
                    <div class="alert alert-primary">
                        <div class="row justify-content-md-center mb-2">
                            <div class="col-md-auto"><h3 class="mb-1" style="font-weight: bold;"><i class="bi bi-box-seam-fill"></i> <?= $_GET['proveedor'].'-'.$_GET['ordenCompra']?></h3></div>
                        </div>
                        <div class="row justify-content-md-center">
                            <div class="col-md-auto"><i class="bi bi-airplane-fill icon"></i><h5 class="mb-1"><label style="font-weight: bold;">Nº Orden Proveedor</label><?= ' '.$_GET['contenedor']?></h5></div>
                            <div class="col-md-auto"><i class="bi bi-cash icon"></i><h5 class="mb-1" id ="valorPesosFob" attr-value = "<?=  $_GET['valorFobPeso'] ?>"><label style="font-weight: bold;" >Valor F.O.B. $: </label><?= ' '.$_GET['valorFobPeso']?></h5></div>
                            <div id="idEncabezado" attr-value="<?= $_GET['idEncabezado'] ?>" hidden></div>
                            <div class="col-md-auto"><i class="bi bi-cash-coin icon"></i><h5 class="mb-1"><label  id="totalGastosDetalle" style="font-weight: bold;">Gastos $:</label></h5></div>
                            <div class="col-md-auto"><i class="bi bi-percent icon"></i><h5 class="mb-1"><label style="font-weight: bold;">Costos nac.: </label> <span id="porcentaje"></span></h5></div>
                        </div>
                    </div>
                    <h2 class="title"><i class="bi bi-folder-check"></i> Detalle Costos de Nacionalizacion</h2>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="col-">ID</th>
                                    <th class="col-3">GASTOS</th>
                                    <th class="col-">IMPORTE U$S</th>
                                    <th class="col-">TIPO CAMBIO</th>
                                    <th class="col-">IMPORTE $</th>
                                    <th class="col-">% SOBRE F.O.B. $</th>
                                    <th class="col-">OBSERVACIONES</th>
                                </tr>
                            </thead>
                            <tbody id="table">

                                <?php
                                foreach($todosLosGastos as $valor => $key){
                                ?>
                                <tr>
                                    <td id="id"><?=  $key['ID_MG']?></td>
                                    <td><?=  $key['GASTOS']?></td>
                                    <td><input class="decimales currencyInput" style="text-align:center" type="text" id="valorFobDolar" onkeyup="iniciarCalculo(this)"></input></td>
                                    <td><input class="decimales currencyInput tipoCambio" style="text-align:center" type="text"  onkeyup="iniciarCalculo(this)" id="tipoCambio" value="<?= ($valor <= 5) ? $_GET['tipoCambio'] : "0" ?>"></input></td>
                                    <td><input class="decimales currencyInput importe" style="text-align:center" type="number" id="valorFobPeso" name="inputNum[]" readonly></input></td>
                                    <td><input style="text-align:center"></input></td>
                                    <td><input></input></td>
                                </tr>
                            <?php
                            }   
                            ?>
                                <tr class="alert alert-primary" style="font-weight: bold;">
                                    <td>TOTALES</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td id="totalGastosDetalleR" value="0"></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <div><button class="btn btn-primary" id="btnSaveDetalle">Guardar <i class="bi bi-cloud-download"></i></button></div>
                </div>
            </div>
        </div>
    </div>

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Jquery JS-->
    <script src="assets/jquery/jquery.min.js"></script>

    <!-- <script src="js/main.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.min.js" integrity="sha384-IDwe1+LCz02ROU9k972gdyvl+AESN10+x7tBKgc9I5HFtuNz0wWnPclzo6p9vxnk" crossorigin="anonymous"></script>
    <script src="js/costos.js"></script>

</body>
</html>