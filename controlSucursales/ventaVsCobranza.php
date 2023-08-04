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
    <title>Ventas vs Cobranza </title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
        #inputText {
            line-height: 1.2; /* Ajusta el valor para controlar el espacio entre líneas */
        }
    </style>
</head>

<body>

    <div class="alert alert-secondary">
       
        <div class="row">
            <div class="form-row">
            <h4 class="ml-3 mt-4"><i class="bi bi-card-checklist"></i>  Ventas vs Cobranza por comprobante <a style="color: #6c757d;"><?php if (isset($_GET['desde'])){ echo $desde ?> a <?php echo $hasta ;}?></a></h4>
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
                        <button type="submit" name="submit" class="btn btn-success" id="btnExport">Exportar <i class="bi bi-file-earmark-excel"></i></button>
                        <div class="mt-2" id="busqRapida">
                            <label id="textBusqueda">Busqueda rapida:</label>
                            <input type="text" id="textBox" placeholder="Sobre cualquier campo..." onkeyup="myFunction()" class="form-control form-control-sm"></input>
                        </div>
                        <!-- spinner -->
                        <div id="boxLoading"></div> 
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php

    if (isset($_GET['desde'])) {
        $todasLasVentas = json_decode($ventas->traerComprobantes($desde,$hasta));

    ?>

        <table class="table table-striped table-bordered display mt-2" data-page-length="100">
            <thead class="thead-dark">
                    <th class="col-">FECHA</th>
                    <th class="col-">NRO. SUCURSAL</th>
                    <th style="width: 230px;">SUCURSAL</th>
                    <th class="col-">TIPO COMPROBANTE</th>
                    <th class="col-">NRO. COMPROBANTE</th>
                    <th class="col-">VENTA $</th>
                    <th class="col-">COBRANZA $</th>
                    <th class="col-" style="color: #28a745;">DIFERENCIA $</th>
                    <th class="col-" style="text-align:center">EXCLUIR</th>
                    <th class="col-" style="text-align:center">OBSERVACIONES</th>
            </thead>

            <tbody id="table">
                <?php
                foreach ($todasLasVentas as $valor => $key) {
                ?>
                    <tr>                        
                        <td><?= substr($key->FECHA->date, 0, 10)?></td>
                        <td><?= $key->NRO_SUCURS ?></td>
                        <td><?= $key->DESC_SUCURSAL ?></td>
                        <td><?= $key->T_COMP ?></td>
                        <td><?= $key->N_COMP ?></td>
                        <td><?= number_format($key->IMP_VENTA, 0, '', '.') ?></td>
                        <td><?= number_format($key->IMP_COBRANZA, 0, '', '.') ?></td>
                        <td><?= number_format($key->DIFERENCIA, 0, '', '.') ?></td>
                        <td style="text-align:center"><input type="checkbox" onchange="confirmarVentaVsCobranza(this)"></td>
                        <td style="text-align:center"><textarea name="" id="inputText" cols="30" rows="2" oninput="checkLineBreak(this)" ></textarea></td>
                    </tr>
                <?php
                }
                ?>
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

</body>

<script>

    //Spinner listOrdenesActivas.php//
    var btn = document.querySelectorAll('.btn-primary');
    btn.forEach(el => {
        el.addEventListener("click", ()=>{$("#boxLoading").addClass("loading")});
    })

    $(document).ready(() => {
        $("#btnExport").click(function() {
            $("#table").table2excel({
                // exclude CSS class
                exclude: ".noE  xl",
                name: "Ventas por medio de pago",
                filename: "Ventas por medio de pago", //do not include extension
                fileext: ".xlsx" // file extension
            });
        });

    });
    var lineBreakAdded = false;

    function checkLineBreak(div) {
        
        var maxLength = 26; 
        var input = div

        let text = input.value;

        
        if (input.value.length >= maxLength && !lineBreakAdded) {
            input.value = input.value.replace(new RegExp('(.{' + maxLength + '})', 'g'), '$1\n');
            lineBreakAdded = true;
        }

        if (input.value.length < maxLength) {
            lineBreakAdded = false;
        }
    }
</script>

</html>