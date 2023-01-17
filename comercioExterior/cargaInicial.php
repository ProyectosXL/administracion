<?php

include 'Class/proveedor.php';
include 'Class/ordenDeCompra.php';

$proveedor = new Proveedor();
$todosLosProveedores = $proveedor->traerProveedores();
$todosLosProveedores = json_decode($todosLosProveedores);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Title Page-->
    <title>Costos Importacion</title>

    <!-- Librerias Bootstrap -->
    <!-- CSS only -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
                        <!------------------------------------------------------------>

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
    
</head>

<body>
    <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
        <div class="wrapper wrapper--w680">
            <div class="card card-1">
                <div class="card-heading"></div>
                <div class="card-body">
                    <h2 class="title"><i class="bi bi-folder-check"></i> Datos de cabecera - Costos de Nacionalizacion</h2>
                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                      <!--   <div class="rs-select2 js-select-simple select--no-search"> -->
                                            <select id="proveedor" style="width: 283.16px;">
                                            <option selected disabled>PROVEEDOR</option>
                                            <?php
                                        
                                            foreach($todosLosProveedores as $valor => $value){
                                            /* $cuenta=$value-> */
                                            ?>
                                            <option id="proveedor-" value="<?= $value->COD_PROVEE; ?>"><?= $value->NOM_PROVEE; ?></option>
                                            <?php   
                                            }
                                            ?>
                                            </select>
                                            <div class="select-dropdown"></div>
                                        <!-- </div>      -->   
                                    </div>    
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 mayusc" type="text" placeholder="Nº ORDEN PROVEEDOR" id="contenedor">
                                    </div>    
                                </div>
                            </div>
                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 js-datepicker4" type="text" placeholder="FECHA DESP. ADUANA" id="fechaDespacho">
                                        <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar4"></i>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 mayusc" type="text" placeholder="DESPACHO N°" id="despacho" required>
                                    </div>    
                                </div>
                            </div>
                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 js-datepicker" type="text" placeholder="FECHA DE EMBARQUE" id="fechaEmbarque">
                                        <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar"></i>
                                    </div>                                
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 js-datepicker3" type="text" placeholder="FECHA ARRIBO" id="fechaArribo">
                                        <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar3"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 soloNum" type="text" placeholder="NUMERO BL" id="numeroBl">
                                    </div>    
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 mayusc" type="text" placeholder="MATERIAL" id="material" oninput="validarTextoEntrada(this, '[a-záéíóúñ ]')">
                                    </div>    
                                </div>
                            </div>
                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 js-datepicker2" type="text" placeholder="FECHA FACTURA" id="fechaFactura">
                                        <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar2"></i>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 mayusc" type="text" placeholder="FACTURA PROVEEDOR" id="facturaProveedor">
                                    </div>    
                                </div>
                            </div>
                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 mayusc" type="text" value="CHINA" placeholder="ORIGEN" id="origen">
                                    </div>    
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 decimales currencyInput" onkeyup="calcular()" type="text" placeholder="VALOR F.O.B. U$S" id="valorFobDolar">
                                    </div>    
                                </div>
                            </div>
                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 decimales currencyInput" onkeyup="calcular()" type="text" placeholder="TIPO DE CAMBIO DESPACHO" id="tipoCambio">
                                    </div>    
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 decimales" type="text" placeholder="VALOR F.O.B. $" id="valorFobPeso" readonly>
                                    </div>
                                </div>      
                            </div>
                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <!-- <input class="input--style-1 soloNum" type="text" placeholder="ORDEN DE COMPRA" id="ordenCompra"> -->
                                        <select id="ordenCompra">
                                            <option disabled="disabled" selected="selected">ORDEN DE COMPRA</option>
                                        </select>
                                    </div>    
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <div class="rs-select2 js-select-simple select--no-search ">
                                            <select id="formaPago" style="width: 283.16px;">
                                                <option disabled="disabled" selected="selected">FORMA DE PAGO</option>
                                                <option>PAGO ANTICIPADO</option>
                                                <option>PAGO VISTA</option>
                                                <option>PAGO DIFERIDO</option>
                                            </select>
                                            <div class="select-dropdown"></div>
                                        </div>        
                                    </div>    
                                </div>
                            </div>
                        <div class="p-t-20">
                            <button class="btn btn-primary" id="btnSave">Guardar <i class="bi bi-cloud-download"></i></button>
                        </div>
                </div>
            </div>
        </div>
    </div>

    
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Jquery JS-->
    <script src="assets/jquery/jquery.min.js"></script>
    <!-- Vendor JS-->
    <script src="assets/select2/select2.min.js"></script>
    <script src="assets/datepicker/moment.min.js"></script>
    <script src="assets/datepicker/daterangepicker.js"></script>

    <!-- Main JS-->
    <script src="js/global.js"></script>
    <script src="js/main.js"></script>

</body>

</html>


<script>

   

</script>
