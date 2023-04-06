<?php

include 'Class/gastos.php';
include 'Class/rubroContable.php';
include 'Class/prorrateo.php';
include 'Class/articulos.php';
include 'Class/cuentaContable.php';

$gastos = new Gastos();
$articulo = new Articulo();
$rubroContable = new RubroContable();
$todosLosRubros = $rubroContable->traerRubrosContables();
$todosLosRubros = json_decode($todosLosRubros);

$metodoProrrateo = new Prorrateo();
$todosLosMetodos = $metodoProrrateo->traerMetodosProrrateo();
$todosLosMetodos = json_decode($todosLosMetodos);

$codRubro = isset($_GET['codRubro']) ? $_GET['codRubro'] : '%' ;
$codCuenta = isset($_GET['codCuenta']) ?  $_GET['codCuenta'] : '%';

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");
$columna = isset($_GET['textBox']) ? $_GET['textBox'] : null;

$cuenta = new CuentaContable ();
$cuentas = $cuenta->traerCodCuentaAll();

$cuentas = json_decode($cuentas, true);;

$data = [];

foreach ($cuentas as $key => $value) {

    $data[$key]['COD_CUENTA'] = $value['COD_CUENTA'];
    $data[$key]['DESC_CUENTA'] = $value['DESC_CUENTA'];

}


$todosLosArticulos = $articulo->traerArticulosSinCostoNac();
$todosLosGastos = $gastos->traerGastos($desde, $hasta, "%", $codRubro,$columna,$codCuenta);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Gastos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    </link>

</head>
<body>

    <div class="alert alert-secondary">
        <div class="row">
            <div id="titlePrincipal" class="col-md-auto">
                <h3 class="title"><i class="bi bi-ui-checks"></i> Consulta de Gastos</h3>
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
                            <label>Codigo de Cuenta:</label>
                            <select class="form-control form-control-sm codCuenta" name="codCuenta" style="width: 300px;">
                            <option  value="%" selected>Todos</option>
                                        <?php 
                                            foreach ($data as $cuenta ) {
                                      ?> 
                                            <option  value="<?= $cuenta['COD_CUENTA'] ?>"><?= $cuenta['COD_CUENTA'] ?> - <?= $cuenta['DESC_CUENTA'] ?></option>
                                        <?php
                                            }

                                        ?>
                            </select>
                        </div>
                        <div>
                            <label for="Rubro">Rubro:</label>
                            <select class="form-control form-control-sm codRubro" name="codRubro">
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
                            <label id="textBusqueda">Busqueda rapida:</label>
                            <input type="text" id="textBox"  name="textBox" placeholder="Leyenda - Razon Social - Nro Comp" class="form-control form-control-sm" style="width: 250px;"></input>  
                        </div>
                        <div>
                            <button type="submit" name="submit" class="btn btn-primary" id="search"><i class="bi bi-search"></i></button>
                        </div>
                    </div>

                </form>
            </div>
            </div>
        </div>
    </div>


    <?php

        if (isset($_GET['desde'])) {

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
                            <select class="codRubro" style="width: 140px;">
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
                            <select class="codProrrateoC" style="width: 2.2rem;">
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

    <!-- <script src="js/functions.js"></script> -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="js/consultaGastos.js"></script>

    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

    <link rel="stylesheet" type="text/css" href="../comercioExterior/assets/select2/select2.min.css">
    <script src="../comercioExterior/assets/select2/select2.min.js"></script>
 









    <script>
        $(document).ready(function() {
            $('#myTable').DataTable({
                responsive: true,
            });

            $('.codCuenta').select2();

            $('.codRubro').select2();
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

?>