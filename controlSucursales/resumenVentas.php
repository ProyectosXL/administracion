<?php

include 'Class/ventas.php';

$ventas= new Ventas();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");


if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
    $checked = 'checked';
}else{
    $checked = '';
}
    
$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$dataOnValue = ($checkedValue === 'suc_uy') ? 'UY' : 'ARG';
$dataOffValue = ($checkedValue === 'suc_uy') ? 'ARG' : 'UY';
$imageOn = ($checkedValue === 'central') ? '../assets/images/bandera_con_sol__55757_std.jpg' : '../assets/images/UY.png';
$imageOff = ($checkedValue === 'central') ? '../assets/images/UY.png' : '../assets/images/bandera_con_sol__55757_std.jpg';


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de ventas </title>

    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
<style>
        .toggle-on {
        background-image: url('<?= $imageOn ?>');
        background-size: contain;
        background-repeat: no-repeat;
        height: 60px;
        width: 60px;
        }

        .toggle-off {
            background-image: url('<?= $imageOff ?>');
            background-size: contain;
            background-repeat: no-repeat;
            height: 60px;
            width: 60px;
        }
</style>

</head>

<body>

    <div class="alert alert-secondary">
       
    <form class="form-inline mt-3 mb-3">
            <a href="http://192.168.0.13:8000/" style="display:inline-block;">
                <img src="../image/home-button.png" style="width:50px;height:45px;margin-right:1rem;transition: transform 0.3s;" title="Menú" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            </a>
            <h4 class="ml-3"><i class="bi bi-credit-card"></i>  Ventas por medio de pago <a style="color: #6c757d;"><?php if (isset($_GET['desde'])){ echo $desde ?> a <?php echo $hasta ;}?></a></h4>
                <label class="ml-4">Desde:</label>
                <input type="date" class="form-control form-control-sm ml-1" name="desde" value="<?= $desde ?>">
                <label class="ml-2">Hasta:</label>
                <input type="date" class="form-control form-control-sm ml-1" name="hasta" value="<?= $hasta ?>">
                        
                <button type="submit" name="submit" class="btn btn-primary ml-2" id="search">Buscar <i class="bi bi-search"></i></button>
                <button type="submit" name="submit" class="btn btn-success ml-3" id="btnExport">Exportar <i class="bi bi-file-earmark-excel"></i></button>
                
                <label id="textBusqueda" class="ml-4">Busqueda rapida:</label>
                <input type="text" id="textBox" placeholder="Sobre cualquier campo..." onkeyup="myFunction()" class="form-control form-control-sm ml-1 mr-4"></input>
                <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;" onchange="cambiarEntorno(this)" id="checkEntorno" >

                
                
                <!-- spinner -->
                <div id="boxLoading"></div>     
    </form>
    </div>
           
    <?php

    if (isset($_GET['desde'])) {
        $todasLasVentas = json_decode($ventas->traerVentas($desde,$hasta));

    ?>

        <table class="table table-striped table-bordered display mt-2" data-page-length="100" id="tableVentas">
            <thead class="thead-dark">
                    <th class="col-">NRO. SUC</th>
                    <th style="width: 230px;">SUCURSAL</th>
                    <th class="col-">TARJETA</th>
                    <th class="col-">CUENTA DNI</th>
                    <th class="col-" style="color: #28a745;">TOTAL TARJETAS</th>
                    <th class="col-">MERCADO PAGO QR</th>
                    <th class="col-">MERCADO PAGO</th>
                    <th class="col-">MODO QR</th>
                    <th class="col-">PROMO BANCO</th>
                    <th class="col-">EFECTIVO</th>
                    <th class="col-">BONUS SHOPPING</th>
                    <th class="col-">DOLARES</th>
                    <th class="col-">EUROS</th>
                    <th class="col-" style="color: #28a745;">TOTAL VENTAS</th>
                    <!-- <th class="col-">TOTAL CONTADO</th> -->
            </thead>

            <tbody id="table">
                <?php
                foreach ($todasLasVentas as $valor => $key) {
                ?>
                    <tr>                        
                        <td><?= $key->NRO_SUCURSAL ?></td>
                        <td><?= $key->DESC_SUCURSAL ?></td>
                        <td id = "tdTarjeta">$<?= number_format($key->TARJETA, 2, '.', ',') ?></td>
                        <td id = "tdCuentaDni">$<?= number_format($key->CUENTA_DNI, 2, '.', ',') ?></td>
                        <td id = "tdTotalTarjetas">$<?= number_format($key->TOTAL_TARJETAS, 2, '.', ',') ?></td>
                        <td id = "tdMercadoPagoQr">$<?= number_format($key->MERCADO_PAGO_QR, 2, '.', ',') ?></td>
                        <td id = "tdMercadoPago">$<?= number_format($key->MERCADO_PAGO, 2, '.', ',') ?></td>
                        <td id = "tdModoQr">$<?= number_format($key->MODO_QR, 2, '.', ',') ?></td>
                        <td id = "tdPromoBanco">$<?= number_format($key->PROMO_BANCO, 2, '.', ',') ?></td>
                        <td id = "tdEfectivo">$<?= number_format($key->EFECTIVO, 2, '.', ',') ?></td>
                        <td id = "tdBonusShopping">$<?= number_format($key->BONUS_SHOPPING, 2, '.', ',') ?></td>
                        <td id = "tdDolares">$<?= number_format($key->DOLARES, 2, '.', ',') ?></td>
                        <td id = "tdEuros">$<?= number_format($key->EUROS, 2, '.', ',') ?></td>
                        <td id = "tdTotalVentas">$<?= number_format($key->VENTAS, 2, '.', ',') ?></td>
                        <!-- <td><?= number_format($key->TOTAL_CONTADO, 2, '.', ',') ?></td> -->
                    </tr>
                <?php
                }
                ?>
                <tr>
                    <td>TOTAL FILAS</td>
                    <td></td>
                    <td id = "totalTarjeta"></td>
                    <td id = "totalCuentaDni"></td>
                    <td id = "totalTarjetas"></td>
                    <td id = "totalMercadoPagoQr"></td>
                    <td id = "totalMercadoPago"></td>
                    <td id = "totalModoQr"></td>
                    <td id = "totalPromoBanco"></td>
                    <td id = "totalEfectivo"></td>
                    <td id = "totalBonusShopping"></td>
                    <td id = "totalDolares"></td>
                    <td id = "totalEuros"></td>
                    <td id = "totalVentas"></td>
                </tr>
                
            </tbody>
        </table>
    <?php
    }
    ?>
    
    <script src="js/main.js" charset="utf-8"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <!-- Plugin to export Excel -->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>

</body>

<script>

    //Spinner listOrdenesActivas.php//
    var btn = document.querySelectorAll('.btn-primary');
    btn.forEach(el => {
        el.addEventListener("click", ()=>{$("#boxLoading").addClass("loading")});
    })

    $(document).ready(() => {
        calcularTotales();
        $("#btnExport").click(function() {
            $("#tableVentas").table2excel({
                // exclude CSS class
                exclude: ".noE  xl",
                name: "Ventas por medio de pago",
                filename: "Ventas por medio de pago", //do not include extension
                fileext: ".xlsx" // file extension
            });
        });
        
            
    document.querySelector(".toggle").style.width="40px"
    document.querySelector(".toggle-on").style.fontSize="0"
    document.querySelector(".toggle-off").style.fontSize="0"
    document.querySelector('.toggle.btn.btn-primary').style.height = '38px'



    });


  
</script>

</html>