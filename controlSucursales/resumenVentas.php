<?php

include 'Class/ventas.php';

$ventas= new Ventas();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de ventas </title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <div class="alert alert-danger">
        <div class="row">
            <div class="form-row">
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
                        <button type="submit" name="submit" class="btn btn-primary" id="search">Buscar <i class="bi bi-search"></i></button>
                        <!-- spinner -->
                        <div id="boxLoading"></div> 
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
    if (isset($_GET['desde'])) {
        $todasLasVentas = json_decode($ventas->traerVentas($desde, $hasta));
        die();
    ?>

        <table class="table table-striped table-bordered display mt-2" id="tableDinamic" style="width: 99%;" data-page-length="100">
            <thead class="thead-dark">
                <tr>
                    <th style="position: sticky; top: 0; z-index: 10; width: 100px;" class="col-1">NRO. SUC</th>
                    <th style="position: sticky; top: 0; z-index: 10;">SUCURSAL</th>
                    <th style="position: sticky; top: 0; z-index: 10;">TOTAL VENTAS</th>
                    <th style="position: sticky; top: 0; z-index: 10;">TARJETA</th>
                    <th style="position: sticky; top: 0; z-index: 10;">EFECTIVO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">CUENTA DNI</th>
                    <th style="position: sticky; top: 0; z-index: 10;">MERCADO PAGO QR</th>
                    <th style="position: sticky; top: 0; z-index: 10;">MERCADO PAGO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">BONUS SHOPPING</th>
                    <th style="position: sticky; top: 0; z-index: 10;">DOLARES</th>
                    <th style="position: sticky; top: 0; z-index: 10;">EUROS</th>
                    <th style="position: sticky; top: 0; z-index: 10;">PROMO BANCO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">TOTAL TARJETAS</th>
                    <th style="position: sticky; top: 0; z-index: 10;">TOTAL CONTADO</th>
                </tr>
            </thead>
            <tbody>
                <?php
                
                foreach ($todasLasVentas as $valor => $key) {
                ?>
                    <tr>                        
                        <td><?= $key->NRO_SUCURSAL ?></td>
                        <td><?= $key->DESC_SUCURSAL ?></td>
                        <td><?= $key->VENTAS ?></td>
                        <td><?= $key->TARJETA ?></td>
                        <td><?= $key->EFECTIVO ?></td>
                        <td><?= $key->CUENTA_DNI ?></td>
                        <td><?= $key->MERCADO_PAGO_QR ?></td>
                        <td><?= $key->MERCADO_PAGO ?></td>
                        <td><?= $key->BONUS_SHOPPING ?></td>
                        <td><?= $key->DOLARES ?></td>
                        <td><?= $key->EUROS ?></td>
                        <td><?= $key->PROMO_BANCO ?></td>
                        <td><?= $key->TOTAL_TARJETAS ?></td>
                        <td><?= $key->TOTAL_CONTADO ?></td>
                    </tr>
            </tbody>
        <?php
                }
        ?>
        </table>
    <?php
    }
    ?>
    <script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>

<script>

    //Spinner listOrdenesActivas.php//
    var btn = document.querySelectorAll('.btn-primary');
    btn.forEach(el => {
        el.addEventListener("click", ()=>{$("#boxLoading").addClass("loading")});
    })

</script>

</html>