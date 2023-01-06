<?php

require_once __DIR__ ."./Controller/listarOrden.php";
$listaDeOrdenes = listar();

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
    <link href="vendor/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <!-- Font special for pages-->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <!-- Vendor CSS-->
    <link href="vendor/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="vendor/datepicker/daterangepicker.css" rel="stylesheet" media="all">

    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">
    <!-- Main CSS-->
    <link href="./../contabilidad/css/style.css" rel="stylesheet" media="all">
    
</head>

<body>
    <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
        <div class="wrapper wrapper--w680">
            <div class="card card-1">
                <div class="card-heading"></div>
                <div class="card-body">
                    <h2 class="title"><i class="bi bi-folder-check"></i> Seleccion de despachos de importacion</h2>

                    <div >

                <div class="row" style="margin-top: -1rem; margin-left: 0.5rem">

                    <div class="form-row">
                        
                        <form action="">
                            <div class="desde">
                                
                                <label class="">Desde:</label>
                                <label class="">Hasta:</label>
                                <div>
                                    <input type="date" class="" name="desde" value="2023-01-03">
                                </div>
                                    <input type="date" class="" name="hasta" value="2023-01-03">
                            </div>
                            <!-- <div class="form-row mt-auto"> -->
                                <button type="submit" name="filter" class="btn btn-primary" id="search">Filtrar <i class="bi bi-search"></i></button>
                            <!-- </div> -->
                        </form>
                    </div>
                
               
            </div>     
        </div>
        
        <table class="table table-striped table-bordered display" id="tableDinamic" style="width: 99%;" data-page-length="100">
                
        <thead class="table-dark">
            <tr>
                <th style="position: sticky; top: 0; z-index: 10; width: 100px;"class="col-1">FECHA DESP.</th>
                <th style="position: sticky; top: 0; z-index: 10;">DESPACHO N°</th>
                <th style="position: sticky; top: 0; z-index: 10;">PROVEEDOR</th>
                <th style="position: sticky; top: 0; z-index: 10;">N° ORDEN PROV.</th>
                <th style="position: sticky; top: 0; z-index: 10;">N° ORD.DE COMPRA</th>
                <th style="position: sticky; top: 0; z-index: 10;"></th>
                <th style="position: sticky; top: 0; z-index: 10;"></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach($listaDeOrdenes as $orden ){
                $newDate = $orden['FECHA_MOV']->format('d/m/Y'); // Format - Date
            ?>
                <td id="fecha2"><?= $newDate ?></td>
                <td><?= $orden['DESPACHO']?></td>
                <td ><?= $orden['PROVEEDOR']?></td>
                <td ><?= $orden['COD_PROVEE']?></td>
                <td><?= $orden['ORDEN_COMPRA']?></td>
                <td><button style="" onclick="verDetalle('<?= $orden['ID']?>','<?= $orden['PROVEEDOR']?>','<?= $orden['ORDEN_COMPRA']?>','<?= $orden['COD_PROVEE']?>','<?= $orden['VALOR_FOB_PESO']?>')"><i class="fa fa-pencil-square-o fa-3x" aria-hidden="true"></i></button></td>
                <td><button ><i class="fa fa-download fa-2x" aria-hidden="true" ></i></button></td>

            </tr>
            <?php
            }
            ?>
            </tbody>
    </table>
    <input type="button" id="btnaddrow" class="btn-primary" value="+" onclick="guardarGasto()">
                </div>
            </div>
        </div>
    </div>

    
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Jquery JS-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <!-- Vendor JS-->
    <script src="vendor/select2/select2.min.js"></script>
    <script src="vendor/datepicker/moment.min.js"></script>
    <script src="vendor/datepicker/daterangepicker.js"></script>
    <!-- <script src="js/index.js"></script> -->

</body>

</html>


<script>

const verDetalle = (id,prov,orden,codProv,valorFobPeso)=>{
    window.location = "editarOrden.php?idEncabezado="+id+"&proveedor="+prov+"&ordenDeCompra="+orden+"&codProveedor="+codProv+"&valorFobPeso="+valorFobPeso;
}


</script>
