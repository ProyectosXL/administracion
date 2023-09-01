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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

        <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css"> -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

                    <form class="form-inline"  action="#">
                        <label for="email">Desde :</label>
                        <input type="date" class="form-control form-control-sm ml-1" id="desde" name="desde" value="<?=  $desde ?>">
                        <label for="email" class="ml-2" >Hasta :</label>
                        <input type="date" class="form-control form-control-sm ml-1" id="hasta"  name="hasta" value="<?=  $hasta ?>">
                                
                        <button type="submit" name="filter" class="btn btn-primary">Filtrar <i class="bi bi-search"></i></button>
                               
                        <div class="mb-3">
                            <label id="textBusqueda" style="position:relative; margin-left: 119vh; width: 150px">Busqueda rapida:</label>
                            <input type="text" id="textBox"  placeholder="Sobre cualquier campo..." onkeyup="myFunction()" class="form-control form-control-sm" style="margin-left: 120vh; width: 300px"></input>  
                        </div>
                    </form>
        
        <table class="table table-striped table-bordered display" id="tableDinamic" style="width: 80%; font-size: 11.5px" data-page-length="100">
                
        <thead class="table-dark">
            <tr>
                <th style="position: sticky; top: 0; z-index: 10; width: 100px; background: #343a40">FECHA INGRESO</th>
                <th style="position: sticky; top: 0; z-index: 10; width: 100px; background: #343a40">FECHA DESP.</th>
                <th style="position: sticky; top: 0; z-index: 10; background: #343a40">CONTENEDOR</th>
                <th style="position: sticky; top: 0; z-index: 10; background: #343a40">DESPACHO N°</th>
                <th style="position: sticky; top: 0; z-index: 10; background: #343a40">PROVEEDOR</th>
                <th style="position: sticky; top: 0; z-index: 10; background: #343a40">N° ORDEN PROV.</th>
                <th style="position: sticky; top: 0; z-index: 10; background: #343a40">N° ORD.DE COMPRA</th>
                <th style="position: sticky; top: 0; z-index: 10; background: #343a40">COSTO NAC.</th>
                <th style="position: sticky; top: 0; z-index: 10; background: #343a40; width: 1.5rem;"></th>
                <th style="position: sticky; top: 0; z-index: 10; background: #343a40; width: 1.5rem;"></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach($listaDeOrdenes as $orden ){
                $newDate = $orden['FECHA_MOV']->format('d/m/Y'); // Format - Date
            ?>
                <td id="fecha2"><?= $newDate ?></td>
                <td><?= $orden['FECHA_DESP_ADU']->format('d/m/Y');?></td>
                <td><?= $orden['CONTENEDOR']?></td>
                <td><?= $orden['DESPACHO']?></td>
                <td ><?= $orden['PROVEEDOR']?></td>
                <td ><?= $orden['COD_PROVEE']?></td>
                <td><?= $orden['ORDEN_COMPRA']?></td>
                <td><?= number_format($orden['COSTO_NAC'], 2).' %'?></td>
                <td><button class="btn btn-sm btn-success" title="Editar" onclick="verDetalle('<?= $orden['ID']?>','<?= $orden['PROVEEDOR']?>','<?= $orden['ORDEN_COMPRA']?>','<?= $orden['COD_PROVEE']?>','<?= $orden['VALOR_FOB_PESO']?>')"><i class="fa fa-edit" style="font-size: 23px;"></td>
                <td><button class="btn btn-sm btn-warning" title="Descargar" onclick="imprimir('<?=$orden['ID']?>')"><i class="bi bi-download" style="font-size: 18px; color: white;" aria-hidden="true"></td>

            </tr>
            <?php
            }
            ?>
            </tbody>
    </table>
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
