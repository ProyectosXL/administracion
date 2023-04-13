<?php

include 'Class/gastos.php';
include 'Class/rubroContable.php';
include 'Class/prorrateo.php';
include 'Class/articulos.php';

$gastos = new Gastos();
$articulo = new Articulo();
$rubroContable = new RubroContable();
$todosLosRubros = $rubroContable->traerRubrosContables();
$todosLosRubros = json_decode($todosLosRubros);

$metodoProrrateo = new Prorrateo();
$todosLosMetodos = $metodoProrrateo->traerMetodosProrrateo();
$todosLosMetodos = json_decode($todosLosMetodos);

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");

$periodo = str_replace("0","",substr($hasta, 5, 2)).'-'.substr($hasta, 0, 4);



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Gastos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="select2/select2.min.css">

    <script src="select2/select2.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    </link>

</head>

<?php

// $todosLosArticulos = $articulo->traerArticulosSinCostoNac();

?>

<body>

    <div class="row">
        <div class="progressbar-wrapper">
            <div hidden id="periodo" attr-periodo= "<?= $periodo ?>"></div>
            <ul class="progressbar">
                <li class="" id="paso1" data-toggle="tooltip" data-placement="bottom" title="Verificar artículos sin costo de nacionalización">Paso</li>
                <li class="" id="paso2"  data-toggle="tooltip" data-placement="bottom" title="Verificar artículos sin precio de costo">Paso</li>
                <li class="" id="paso3"  data-toggle="tooltip" data-placement="bottom" title="Calcular y grabar las ventas sin IVA">Paso</li>
                <li class="" id="paso4"  data-toggle="tooltip" data-placement="bottom" title="Verificar que la venta coincida con la cobranza">Paso</li>
                <li class="" id="paso5" data-toggle="tooltip" data-placement="bottom" title="Calcular y grabar los métodos de prorrateo">Paso</li>
                <li class="" id="paso6" data-toggle="tooltip" data-placement="bottom" title="Traer los registros para control integral">Paso</li>
                <li class="" id="paso7" data-toggle="tooltip" data-placement="bottom" title="Aplicar coeficiente de ajuste por inflación">Paso</li>
            </ul>
        </div>
        <div>
            <button class="btn btn-primary ml-1 mt-3" id="btnEjecutar">Ejecutar <i class="bi bi-check2-square"></i></button>
            <!-- spinner -->
            <div id="boxLoading"></div>
        </div>
    </div>

    <div class="alert alert-secondary">
        <div class="row">
            <div id="titlePrincipal" class="col-md-auto">
                <h3 class="title"><i class="bi bi-ui-checks"></i> Control de Gastos</h3>
            </div>
            <div class="form-row">
                <form>
                    <div class="contenedor">
                        <div class="col-">
                            <label>Desde:</label>
                            <input type="date" class="form-control form-control-sm" name="desde" value="<?= $desde ?>">
                        </div>

                        <div class="col-">
                            <label>Hasta:</label>
                            <input type="date" class="form-control form-control-sm" name="hasta" value="<?= $hasta ?>">
                        </div>
                        <div id="estado">
                            <label>Estado:</label>
                            <select class="form-control form-control-sm estado" name="estado">
                                <option value="" selected>Todos</option>
                                <option value="1">Amortizar</option>
                                <option value="2">Excluidos</option>
                                <option value="3">Pendiente asignar</option>
                                <option value="0">Pendiente control</option>
                            </select>
                        </div>
                        <div>
                            <label for="Rubro">Rubro:</label>
                            <select class="form-control form-control-sm" name="codRubro">
                                <option selected disabled>Todos</option>
                                <?php
                                foreach ($todosLosRubros as $valor => $value) {
                                ?>
                                    <option value="<?= $value->COD_RUBRO; ?>"><?= $value->COD_RUBRO . '-' . $value->RUBRO_CONTABLE; ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" name="submit" class="btn btn-primary" id="search"><i class="bi bi-search"></i></button>
                        </div>
                    </div>

                </form>
            </div>
            <div class="btn-group">
                <button class="btn btn-danger mt-3" id="btnAmort">Amortizar <i class="bi bi-calendar2-week"></i></button>
                <button class="btn btn-info mt-3" style="margin-left: 0;" id="btnProrrateo">Prorratear <i class="bi bi-file-text"></i></button>
                <button class="btn btn-success mt-3" style="margin-left: 0;" id="btnSend">Procesar <i class="bi bi-check2-square"></i></button>
            </div>
            <div id="contCheck">
                <label id="titleCheck">Acciones masivas</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" onclick="checkExcluirAll(this);" value="" id="defaultCheck1">
                    <label class="form-check-label" for="defaultCheck1">Excluir</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" onclick="checkControladoAll(this);" value="" id="defaultCheck2">
                    <label class="form-check-label checkControladoAll" for="defaultCheck2">Controlar</label>
                </div>
            </div>
        </div>
    </div>

    <?php

    if (isset($_GET['desde'])) {

        if (isset($_GET['estado']) != '') {
            $estado = $_GET['estado'];
        } else {
            $estado = '%';
        }

        if (isset($_GET['codRubro']) != '') {
            $codRubro = $_GET['codRubro'];
        } else {
            $codRubro = '%';
        }

        $todosLosGastos = $gastos->traerGastos($desde, $hasta, $estado, $codRubro);

    ?>



        <table class="table table-striped table-bordered" id="myTable" style="width: 99%;" cellspacing="0" data-page-length="100">
            <thead class="thead-dark">
                <tr>
                    <th style="position: sticky; top: 0; z-index: 10; width: 200px;" class="col-1">FECHA / PERIODO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">AUXILIAR</th>
                    <th style="position: sticky; top: 0; z-index: 10;">SECTOR</th>
                    <th style="position: sticky; top: 0; z-index: 10;">COD. CUENTA</th>
                    <th style="position: sticky; top: 0; z-index: 10;">DESC. CUENTA</th>
                    <th style="position: sticky; top: 0; z-index: 10;">SALDO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">LEYENDA</th>
                    <th style="position: sticky; top: 0; z-index: 10;">TIPO COMP.</th>
                    <th style="position: sticky; top: 0; z-index: 10;">RAZON SOCIAL</th>
                    <th style="position: sticky; top: 0; z-index: 10;">NRO. COMP.</th>
                    <th style="position: sticky; top: 0; z-index: 10;">COD. RUBRO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">RUBRO CONTABLE</th>
                    <th style="position: sticky; top: 0; z-index: 10;">COD. PRORRATEO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">DESC. PRORRATEO</th>
                    <th style="position: sticky; top: 0; z-index: 10;" title="Colocar plazo de amortización">AMORT.</th>
                    <th style="position: sticky; top: 0; z-index: 10;"><i class="bi bi-x-square biHeader" data-toggle="tooltip" data-placement="bottom" title="Excluir gasto"></i></th>
                    <th style="position: sticky; top: 0; z-index: 10;"><i class="bi bi-check2-square biHeader" data-toggle="tooltip" data-placement="bottom" title="Gasto controlado"></i></th>
                    <th style="position: sticky; top: 0; z-index: 10;"><i class="bi bi-graph-up biHeader" data-toggle="tooltip" data-placement="bottom" title="Gasto amortizado"></i></th>
                    <th style="position: sticky; top: 0; z-index: 10;">MODULO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">NRO. SUC.</th>
                    <th style="position: sticky; top: 0; z-index: 10;">ID</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $todosLosGastos = json_decode($todosLosGastos);
                foreach ($todosLosGastos as $valor => $key) {
                ?>
                    <tr>
                        <td><?= substr($key->FECHA->date, 0, 10) . ' / ' . $key->PERIODO; ?></td>
                        <td><?= $key->DESC_AUXILIAR ?></td>
                        <td><?= $key->SECTOR ?></td>
                        <td><?= $key->COD_CUENTA ?></td>
                        <td style="width: 20rem;"><?= $key->DESC_CUENTA ?></td>
                        <td><?= number_format($key->SALDO, 2) ?></td>
                        <td><?= $key->DESC_LEYENDA ?></td>
                        <td><?= $key->T_COMP ?></td>
                        <td><?= $key->RAZON_SOCIAL ?></td>
                        <td><?= $key->N_COMP ?></td>
                        <td>
                            <select class="codRubro" style="width: 3rem;">
                                <option selected disabled><?= $key->COD_RUBRO ?></option>
                                <?php
                                foreach ($todosLosRubros as $valor => $value) {
                                ?>
                                    <option value="<?= $value->COD_RUBRO; ?>"><?= $value->COD_RUBRO . '-' . $value->RUBRO_CONTABLE; ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </td>
                        <td><?= $key->RUBRO_CONTABLE ?></td>
                        <td>
                            <select class="codProrrateo" style="width: 2.2rem;">
                                <option selected disabled><?= $key->COD_PRORRATEO ?></option>
                                <?php
                                foreach ($todosLosMetodos as $valor => $value) {
                                ?>
                                    <option value="<?= $value->COD_PRORRATEO; ?>"><?= $value->COD_PRORRATEO . '-' . $value->DESC_PRORRATEO; ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </td>
                        <td><?= $key->DESC_PRORRATEO ?></td>
                        <td><?php if ($key->AMORTIZADO == 1) { ?>
                                <input class="amortiza" type="number" id="amortiza" min="0" name="inputNum" value="<?= $key->AMORTIZAR ?>" disabled>
                            <?php } else { ?>
                                <input class="amortiza" type="number" id="amortiza" min="1" name="inputNum" value="<?= $key->AMORTIZAR ?>">
                            <?php } ?>
                        </td>
                        <td><input class="checkExcluir" type="checkbox" <?php if ($key->EXCLUIR == 1) {
                                                                            echo 'checked';
                                                                        } ?>></td>
                        <td><input class="checkControlado" type="checkbox" <?php if ($key->CONTROLADO == 1) {
                                                                                echo 'checked';
                                                                            } ?>></td>
                        <td><input class="checkAmortizado" type="checkbox" <?php if ($key->AMORTIZADO == 1) {
                                                                                echo 'checked';
                                                                            } ?> disabled></td>
                        <td><?= $key->MODULO ?></td>
                        <td><?= $key->NUM_SUCURSAL ?></td>
                        <td><?= $key->ID ?></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>

    <?php
    }
    ?>

    <script src="js/functions.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>


    <script>
        $(document).ready(function() {
            $('#myTable').DataTable({
                responsive: true,
            });
            
        });

        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        })



        $('#myModal').modal('toggle')
    </script>

</body>

</html>

<?php

include('articuloSinCn.php');

include('articuloSinPrecioCosto.php');
include('ventasCobranzaTotal.php');


?>