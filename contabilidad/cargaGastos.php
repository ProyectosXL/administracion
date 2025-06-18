<?php

include 'Class/rubroContable.php';
include 'Class/prorrateo.php';
include 'Class/centroCosto.php';
include 'Class/cuentaContable.php';
include 'Class/gasto.php';
include 'Class/articulos.php';

$rubroContable = new RubroContable();
$todosLosRubros = $rubroContable->traerRubrosContables();
$todosLosRubros = json_decode($todosLosRubros);

$metodoProrrateo = new Prorrateo();
$todosLosMetodos = $metodoProrrateo->traerMetodosProrrateo();
$todosLosMetodos = json_decode($todosLosMetodos);

$centroCosto = new CentroCosto();
$todosLosCentrosCosto = $centroCosto->traerCentroCostos();
$todosLosCentrosCosto = json_decode($todosLosCentrosCosto);

$cuentaContable = new CuentaContable();
$todasLasCuentasContables = $cuentaContable->traerCuentasContables();
$todasLasCuentasContables = json_decode($todasLasCuentasContables);

$gastos = new Gasto();

$articulo = new Articulo();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");


if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
    $checked = 'checked';
}else{
    $checked = '';
}
    
$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$dataOnValue = ($checkedValue === 'uy') ? 'UY' : 'ARG';
$dataOffValue = ($checkedValue === 'uy') ? 'ARG' : 'UY';
$imageOn = ($checkedValue === 'central') ? 'images/bandera_con_sol__55757_std.jpg' : 'images/UY.png';
$imageOff = ($checkedValue === 'central') ? 'images/UY.png' : 'images/bandera_con_sol__55757_std.jpg';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga Gastos</title>

    <!-- Including Font Awesome CSS from CDN to show icons -->
    <link rel="stylesheet" href=" https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    </link>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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

    <span class="select2-search__field"></span>
    <div class="alert alert-secondary">
        <div class="row">
            <a href="http://192.168.0.13:8000/" style="display:inline-block;">
                <img src="../image/home-button.png" style="width:50px;height:45px; margin-left: 0.5rem; margin-right:0.5rem; margin-top:1rem;transition: transform 0.3s;" title="Menú" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            </a>
            <div id="titlePrincipal" class="col-md-auto" style="margin-right:50%">
                <h3 class="title"><i class="bi bi-ui-checks"></i> Carga de Gastos - Informe Económico</h3>
            </div>
            <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;margin-top:10px" onchange="cambiarEntorno(this)" id="checkEntorno" >

        </div>
    </div>

    <table class="table table-striped table-bordered display" id="tableDinamic" style="width: 99%;" data-page-length="100">
        <thead class="thead-dark">
            <tr>
                <th style="position: sticky; top: 0; z-index: 10;"></th>
                <th style="position: sticky; top: 0; z-index: 10; width: 100px;" class="col-1">FECHA</th>
                <th style="position: sticky; top: 0; z-index: 10;">COD. AUXILIAR</th>
                <th style="position: sticky; top: 0; z-index: 10;">AUXILIAR</th>
                <th style="position: sticky; top: 0; z-index: 10;">SECTOR</th>
                <th style="position: sticky; top: 0; z-index: 10;">COD. CUENTA</th>
                <th style="position: sticky; top: 0; z-index: 10;">DESC. CUENTA</th>
                <th style="position: sticky; top: 0; z-index: 10;">IMPORTE</th>
                <th style="position: sticky; top: 0; z-index: 10;">LEYENDA</th>
                <th style="position: sticky; top: 0; z-index: 10;">COD. RUBRO</th>
                <th style="position: sticky; top: 0; z-index: 10;">RUBRO CONTABLE</th>
                <th style="position: sticky; top: 0; z-index: 10;">COD. PRORRATEO</th>
                <th style="position: sticky; top: 0; z-index: 10;">DESC. PRORRATEO</th>
                <th style="position: sticky; top: 0; z-index: 10;">NRO. SUC.</th>
                <th style="position: sticky; top: 0; z-index: 10;" title="Colocar plazo de amortización">AMORTIZAR</th>
            </tr>
        </thead>
        <tbody>
            <td></td>
            <td><input type="date" class="fecha" value="<?= $hasta ?>"></td>
            <td>
                <select class="codCentro select-auxiliar">
                    <option selected disabled></option>
                    <?php
                    foreach ($todosLosCentrosCosto as $valor => $value) {
                    ?>
                        <option value="<?= $value->COD_AUXILIAR; ?>"><?= $value->COD_AUXILIAR . '-' . $value->DESC_AUXILIAR; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </td>
            <td class="auxiliar"></td>
            <td class="sector"></td>
            <td>
                <select class="codCuenta" style="width: 210px;" onchange="seleccionarCodRubro(this)">
                    <option selected disabled></option>
                    <?php
                    foreach ($todasLasCuentasContables as $valor => $value) {
                    ?>
                        <option value="<?= $value->COD_CUENTA; ?>"><?= $value->COD_CUENTA . '-' . $value->DESC_CUENTA; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </td>
            <td class="cuenta"></td>
            <td><input class="importe" type="number" style="width: 110px;"></input></td>
            <td><input class="leyenda" type="text"></input></td>
            <td>
                <select class="codRubro" style="width: 140px;" class="mi-selector" id="codRubro">
                    <option selected disabled></option>
                    <?php
                    foreach ($todosLosRubros as $valor => $value) {
                    ?>
                        <option value="<?= $value->COD_RUBRO; ?>" ><?= $value->COD_RUBRO . '-' . $value->RUBRO_CONTABLE; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </td>
            <td class="rubro"></td>
            <td>
                <select class="codProrrateo" style="width: 160px;">
                    <option selected disabled></option>
                    <?php
                    foreach ($todosLosMetodos as $valor => $value) {
                    ?>
                        <option value="<?= $value->COD_PRORRATEO; ?>"><?= $value->COD_PRORRATEO . '-' . $value->DESC_PRORRATEO; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </td>
            <td class="descProrrateo"></td>
            <td class="suc"></td>
            <td><input class="amortizar" type="number" style="width: 60px;"></td>
            </tr>
        </tbody>
    </table>
    <input type="button" id="btnaddrow" class="btn-primary" value="+" onclick="guardarGasto();">

    <div class="alert alert-danger">
        <div class="row" style="margin-top: -1rem; margin-left: 0.5rem">
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
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
    if (isset($_GET['desde'])) {
        $todosLosGastos = $gastos->traerGastos2($desde, $hasta);
    ?>

        <table class="table table-striped table-bordered display mt-2" id="tableDinamic" style="width: 99%;" data-page-length="100">
            <thead class="thead-dark">
                <tr>
                    <th style="position: sticky; top: 0; z-index: 10;">ID</th>
                    <th style="position: sticky; top: 0; z-index: 10; width: 100px;" class="col-1">FECHA</th>
                    <th style="position: sticky; top: 0; z-index: 10;">COD. AUXILIAR</th>
                    <th style="position: sticky; top: 0; z-index: 10;">AUXILIAR</th>
                    <th style="position: sticky; top: 0; z-index: 10;">SECTOR</th>
                    <th style="position: sticky; top: 0; z-index: 10;">COD. CUENTA</th>
                    <th style="position: sticky; top: 0; z-index: 10;">DESC. CUENTA</th>
                    <th style="position: sticky; top: 0; z-index: 10;">IMPORTE</th>
                    <th style="position: sticky; top: 0; z-index: 10;">LEYENDA</th>
                    <th style="position: sticky; top: 0; z-index: 10;">COD. RUBRO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">RUBRO CONTABLE</th>
                    <th style="position: sticky; top: 0; z-index: 10;">COD. PRORRATEO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">DESC. PRORRATEO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">NRO. SUC.</th>
                    <th style="position: sticky; top: 0; z-index: 10;" title="Colocar plazo de amortización">AMORTIZAR</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $todosLosGastos = json_decode($todosLosGastos);
                foreach ($todosLosGastos as $valor => $key) {

                ?>
                    <tr>
                    <tr>
                        <td><?= $key->ID_CTA_2 ?></td>
                        <td><?= substr($key->FECHA->date, 0, 10); ?></td>
                        <td><?= $key->COD_AUXILIAR ?></td>
                        <td><?= $key->DESC_AUXILIAR ?></td>
                        <td><?= $key->SECTOR ?></td>
                        <td><?= $key->COD_CUENTA ?></td>
                        <td><?= $key->DESC_CUENTA ?></td>
                        <td><?= number_format($key->SALDO, 2) ?></td>
                        <td><?= $key->DESC_LEYENDA ?></td>
                        <td><?= $key->COD_RUBRO ?></td>
                        <td><?= $key->RUBRO_CONTABLE ?></td>
                        <td><?= $key->COD_PRORRATEO ?></td>
                        <td><?= $key->DESC_PRORRATEO ?></td>
                        <td><?= $key->NUM_SUCURSAL ?></td>
                        <td><?= $key->AMORTIZAR ?></td>
                        <td hidden><?= $key->ID_CTA_2?></td>
                        <td><button class="btn btn-danger" onclick="eliminarGasto(this)"><i class="bi bi-trash btn-delete"></i></button></td>
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
    <script src="js/carga.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>

    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script> -->


</body>

<script>
    // In your Javascript (external .js resource or <script> tag)
    $(document).ready(function() {


        document.querySelector(".toggle").style.width="40px"
        document.querySelector(".toggle-on").style.fontSize="0"
        document.querySelector(".toggle-off").style.fontSize="0"
        document.querySelector('.toggle.btn.btn-primary').style.height = '38px'
        document.querySelector('.toggle.btn.btn-primary').style.marginTop = '25px'


        $('.select-auxiliar').select2();
    });
    $(document).ready(function() {
        $('.codCuenta').select2();
    });
    $(document).ready(function() {
        $('#codRubro').select2();
    });
    $(document).ready(function() {
        $('.codProrrateo').select2();
    });

   

    // Set selected 
    


    const seleccionarCodRubro = (e) => {

        let codCuenta = e.parentElement.parentElement.childNodes[10].childNodes[1].value
        let sector = e.parentElement.parentElement.childNodes[8].textContent

        $.ajax({
                url: 'Controller/consultarCodRubro.php?codCuenta=' + codCuenta + '&sector=' + sector,
                method: 'GET',
                success : function(data) {
                    let codRubro = 0
                    data = JSON.parse(data)


                    
                    if (data != 0){
                        // console.log(data[0].COD_RUBRO);
                        codRubro = data[0].COD_RUBRO;
                    }
                
                    if(codRubro != 0){
                        completarCampoRubro(codRubro);

                    }
                    $('#codRubro').val(codRubro);
                    $('#codRubro').select2().trigger('change');

                }
            })
    }
    const eliminarGasto = (e) =>{
        let id = e.parentElement.parentElement.childNodes[31].textContent;
        $.ajax({
                url: 'Controller/eliminarGasto.php',
                method: 'POST',
                data:{
                    id:id
                },
                success : function(data) {
                    window.location.reload();
                }
            })

    }
</script>

</html>