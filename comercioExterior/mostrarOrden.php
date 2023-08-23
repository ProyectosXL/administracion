<?php

require_once __DIR__ ."./Controller/listarOrden.php";

$fecha_actual = date("Y-m-d");
$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d",strtotime($fecha_actual."- 1 month"));
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : $fecha_actual;

$listaDeOrdenes = listarPorFecha($desde, $hasta);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">

    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">
    <!-- Main CSS-->
    <link href="./../contabilidad/css/style.css" rel="stylesheet" media="all">
    
</head>

<body>
    <div class="page-wrapper bg-secondary p-b-100 pt-2">
        <div class="wrapper wrapper--w680">
            <div class="card card-1">
                <div class="card-heading"></div>
                <div class="card-body">
                    <h2 class="title"><i class="bi bi-folder-check"></i> Seleccion de despachos de importacion</h2>

                    <div >

                <div class="row" >

                    <div class="form-row mb-3">
                        
                        <form action="">
                            <div class="contenedor">
                                <div class="col-">
                                    <label>Desde:</label>
                                    <input type="date" class="form-control form-control-sm" name="desde" value="<?= $desde ?>">
                                </div>

                                <div class="ml-2">
                                    <label>Hasta:</label>
                                    <input type="date" class="form-control form-control-sm" name="hasta" value="<?= $hasta ?>">
                                </div>
                                <div class="form-row mt-auto">
                                    <button type="submit" name="filter" class="btn btn-primary" style="width: 6rem;">Filtrar <i class="bi bi-search"></i></button>
                                </div>
                                <div id="contBusqRapida">
                                    <label id="textBusqueda" style="position:relative; margin-left: 65vh; width: 150px">Busqueda rapida:</label>
                                    <input type="text" id="textBox"  placeholder="Sobre cualquier campo..." onkeyup="myFunction()" class="form-control form-control-sm" style="margin-left: 65vh; width: 300px"></input>  
                                </div>
                            </div>
                        </form>
                    </div>
                
               
            </div>     
        </div>
        
        <table class="table table-striped table-bordered display" id="tableDinamic" style="width: 80%; font-size: 11.5px" data-page-length="100">
                
        <thead class="table-dark">
            <tr>
                <th style="position: sticky; top: 0; z-index: 10; width: 100px;"class="col-1">FECHA DESP.</th>
                <th style="position: sticky; top: 0; z-index: 10;">DESPACHO N°</th>
                <th style="position: sticky; top: 0; z-index: 10;">PROVEEDOR</th>
                <th style="position: sticky; top: 0; z-index: 10;">N° ORDEN PROV.</th>
                <th style="position: sticky; top: 0; z-index: 10;">N° ORD.DE COMPRA</th>
                <th style="position: sticky; top: 0; z-index: 10; width: 1.5rem;"></th>
                <th style="position: sticky; top: 0; z-index: 10; width: 1.5rem;"></th>
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
                <td><button class="btn btn-sm btn-success" title="Editar" onclick="verDetalle('<?= $orden['ID']?>','<?= $orden['PROVEEDOR']?>','<?= $orden['ORDEN_COMPRA']?>','<?= $orden['COD_PROVEE']?>','<?= $orden['VALOR_FOB_PESO']?>')"><i class="fa fa-edit" style="font-size: 20px;"></td>
                <td><button class="btn btn-sm btn-warning" title="Descargar" onclick="imprimir('<?=$orden['ID']?>')"><i class="bi bi-download" style="font-size: 18px; color: white;" aria-hidden="true"></td>

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
    <script src="assets/jquery/jquery.min.js"></script>
    <!-- Vendor JS-->
    <script src="assets/select2/select2.min.js"></script>
    <script src="assets/datepicker/moment.min.js"></script>
    <script src="assets/datepicker/daterangepicker.js"></script>
    <!-- <script src="js/index.js"></script> -->

</body>

</html>


<script>

const verDetalle = (id,prov,orden,codProv,valorFobPeso)=>{
    window.location = "editarOrden.php?idEncabezado="+id+"&proveedor="+prov+"&ordenDeCompra="+orden+"&codProveedor="+codProv+"&valorFobPeso="+valorFobPeso;
}
const imprimir=(id)=>{
    window.location = "imprimir.php?idEncabezado="+id
}

//Búsqueda rápida table//

function myFunction() {
  var input, filter, table, tr, td, td2, i, txtValue;

  
  input = document.getElementById("textBox");
  filter = input.value.toUpperCase();
  table = document.querySelector("#tableDinamic")
  tr = table.querySelectorAll("tbody tr");


  //tr = document.getElementById('tr');

  for (i = 0; i < tr.length; i++) {
    visible = false;
    /* Obtenemos todas las celdas de la fila, no sólo la primera */
    td = tr[i].getElementsByTagName("td");

    for (j = 0; j < td.length; j++) {
      if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
        visible = true;
      } 
    }
    if (visible === true) {
      tr[i].style.display = "";
    } else {
      tr[i].style.display = "none";
    }
  }
}


</script>
