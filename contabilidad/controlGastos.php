<?php

include 'Class/gasto.php';
include 'Class/rubroContable.php';
include 'Class/prorrateo.php';

include 'Class/centroCosto.php';

$centroCostos = new CentroCosto();
$centroCostos = $centroCostos->traerCentroCostos();
$currentYear = date('Y',  strtotime( date("Y-m-d")));
$yearDif = $currentYear - 2023;
$todosLosCentrosCosto = json_decode($centroCostos);

include 'Class/articulos.php';
include 'Class/cuentaContable.php';
include 'resumenIe.php';



$gastos = new Gasto();
$articulo = new Articulo();
$rubroContable = new RubroContable();
$todosLosRubros = $rubroContable->traerRubrosContables();
$todosLosRubros = json_decode($todosLosRubros);

$metodoProrrateo = new Prorrateo();
$todosLosMetodos = $metodoProrrateo->traerMetodosProrrateo();
$todosLosMetodos = json_decode($todosLosMetodos);

$anio = isset($_GET['anio']) ? $_GET['anio'] : date("Y");
$mes = isset($_GET['mes']) ? $_GET['mes'] : date("m");

$periodo = (int)$mes.'-'.$anio;

// Divide el periodo en mes y año
list($mes, $anio) = explode('-', $periodo);

// Obtén la fecha del primer día del mes
$fechaInicio = date("Y-m-d", strtotime("$anio-$mes-01"));

// Obtén la fecha del último día del mes
$fechaFin = date("Y-m-d", strtotime("last day of $anio-$mes"));

$desde = $fechaInicio;
$hasta = $fechaFin;



$cuenta = new CuentaContable ();
$cuentas = $cuenta->traerCodCuentaAll();
$codCuenta = (isset($_GET['codCuenta'])) ? $_GET['codCuenta'] : '%';
$cuentas = json_decode($cuentas, true);;

$data = [];

foreach ($cuentas as $key => $value) {

    $data[$key]['COD_CUENTA'] = $value['COD_CUENTA'];
    $data[$key]['DESC_CUENTA'] = $value['DESC_CUENTA'];

}

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
    <title>Control de Gastos</title>

    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    <link rel="stylesheet" href="css/control-gastos-actions.css">
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

    <a href="http://192.168.0.13:8000/" style="display:inline-block;">
        <img src="../image/home-button.png" style="width:50px;height:45px;margin-right:1rem; margin-top:0.5rem;transition: transform 0.3s;" title="Menú" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
    </a>
    <div class="row">

        <div class="progressbar-wrapper">
            <div hidden id="periodo" attr-periodo= "<?= $periodo ?>" style="margin-top:-2rem;"></div>
            <ul class="progressbar" >
                <li class="" id="paso1" data-toggle="tooltip" data-placement="bottom" title="Calcular y grabar las ventas sin IVA">Paso</li>
                <li class="" id="paso2"  data-toggle="tooltip" data-placement="bottom" title="Verificar que la venta coincida con la cobranza (sucursales)">Paso</li>
                <li class="" id="paso3"  data-toggle="tooltip" data-placement="bottom" title="Verificar artículos sin costo de nacionalización">Paso</li>
                <li class="" id="paso4"  data-toggle="tooltip" data-placement="bottom" title="Verificar artículos sin precio de costo">Paso</li>
                <li class="" id="paso5" data-toggle="tooltip" data-placement="bottom" title="Calcular y grabar el costo de mercadería vendida">Paso</li>
                <li class="" id="paso6" data-toggle="tooltip" data-placement="bottom" title="Calcular y grabar los métodos de prorrateo">Paso</li>
                <li class="" id="paso7" data-toggle="tooltip" data-placement="bottom" title="Traer los registros para control integral">Paso</li>
                <li class="" id="paso8" data-toggle="tooltip" data-placement="bottom" title="Aplicar coeficiente de ajuste por inflación">Paso</li>
            </ul>
        </div>
        <div>
            <button class="btn btn-primary ml-1 mt-3" id="btnEjecutar" style="margin-right:10">Ejecutar <i class="bi bi-check2-square"></i></button>
            <button class="btn btn-warning mt-3" id="btnGestionModulos" onclick="abrirGestionModulos()">
                <i class="bi bi-gear-fill"></i> Gestión Módulos
            </button>

            <div style="display: inline-block; vertical-align: middle; margin-left: 20px;" class="mt-3">
                <div class="alert alert-info" role="alert" style="margin: 0; padding: 8px 15px; display: inline-block;">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <span id="environment-info">Entorno actual: <strong><?php echo ($checkedValue === 'central') ? 'Argentina (ARG)' : 'Uruguay (UY)'; ?></strong></span>
                </div>
            </div>

            <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;margin-top:10px;margin-left:10px" onchange="cambiarEntorno(this)" id="checkEntorno" >

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
                <form method="GET" action="controlGastos.php">
                    <div class="contenedor">
                        <input type="hidden" name="desde" value="<?= $desde ?>" id="desde">
                        <input type="hidden" name="hasta" value="<?= $hasta ?>" id="hasta">
                        
                        <div  class="col-">
                        <label > Mes :</label> 
                        <select name="mes" id="mes" style="width:60px" class="form-control form-control-sm">
                        
                            <option value="01" <?php if($mes == '01'){echo 'selected'; }?> >01</option>
                            <option value="02" <?php if($mes == '02'){echo 'selected'; }?> >02</option>
                            <option value="03" <?php if($mes == '03'){echo 'selected'; }?> >03</option>
                            <option value="04" <?php if($mes == '04'){echo 'selected'; }?> >04</option>
                            <option value="05" <?php if($mes == '05'){echo 'selected'; }?> >05</option>
                            <option value="06" <?php if($mes == '06'){echo 'selected'; }?> >06</option>
                            <option value="07" <?php if($mes == '07'){echo 'selected'; }?> >07</option>
                            <option value="08" <?php if($mes == '08'){echo 'selected'; }?> >08</option>
                            <option value="09" <?php if($mes == '09'){echo 'selected'; }?> >09</option>
                            <option value="10" <?php if($mes == '10'){echo 'selected'; }?> >10</option>
                            <option value="11" <?php if($mes == '11'){echo 'selected'; }?> >11</option>
                            <option value="12" <?php if($mes == '12'){echo 'selected'; }?> >12</option>
                        
                        </select>
                        </div>
                        <div  class="col-">
                        <label > Año :</label> 
                        <select name="anio" id="selectAño" style="width:75px" class="form-control form-control-sm">
                            
                            <option value="2022">2022</option>
                            <?php 
                                for ($i=0; $i <= $yearDif ; $i++) { 
                                    $y = 2023 + $i;
                            ?>
                                <option value="<?=$y?>" <?= ($anio == $y) ? 'selected' : '' ?>><?=$y?></option>
                            
                            <?php
                                }
                            ?>

                        </select>
                        </div>
                        <div id="estado">
                            <label>Estado:</label>
                            <select class="form-control form-control-sm estado" name="selectEstado" id="selectEstado">
                                <option value="%" <?= (isset($_GET['selectEstado']) &&  $_GET['selectEstado'] == '%') ? "selected" : "" ?>>Todos</option>
                                <option value="1" <?= (isset($_GET['selectEstado']) &&  $_GET['selectEstado'] == '1') ? "selected" : "" ?>>Amortizar</option>
                                <option value="2" <?= (isset($_GET['selectEstado']) &&  $_GET['selectEstado'] == '2') ? "selected" : "" ?>>Excluidos</option>
                                <option value="3" <?= (isset($_GET['selectEstado']) &&  $_GET['selectEstado'] == '3') ? "selected" : "" ?>>Pendiente asignar</option>
                                <option value="0" 
                                <?php
                                    if (isset($_GET['selectEstado'])){
                                        if($_GET['selectEstado'] == '0') {
                                            echo "selected";
                                        }
                                    } else{
                                        echo "selected";
                                    }
                                ?> >Pendiente control</option>
                            </select>
                        </div>
                        <div>
                            <label for="Rubro">Rubro:</label>
                            <select class="form-control form-control-sm codRubro" name="codRubro" >
                                <option value="%" <?= (isset($_GET['codRubro']) && $_GET['codRubro'] == '%') ? "selected" : "" ?>>Todos</option>
                                <?php
                                $codRubroSelected = isset($_GET['codRubro']) ? $_GET['codRubro'] : '%';
                                foreach ($todosLosRubros as $valor => $value) {
                                ?>
                                    <option value="<?= $value->COD_RUBRO; ?>" <?= ($codRubroSelected == $value->COD_RUBRO) ? "selected" : "" ?>><?= $value->COD_RUBRO . '-' . $value->RUBRO_CONTABLE; ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>

                        <div >
                            <label>Codigo de Cuenta:</label>
                            <select class="form-control form-control-sm codCuenta" name="codCuenta" style="width: 180px;">
                            <option  value="%" selected>Todos</option>
                                        <?php 
                                            foreach ($data as $cuenta ) {
                                      ?> 
                                            <option  value="<?= $cuenta['COD_CUENTA'] ?>" <?= ($codCuenta == $cuenta['COD_CUENTA']) ? "selected" : "" ?>><?= $cuenta['COD_CUENTA'] ?> - <?= $cuenta['DESC_CUENTA'] ?></option>
                                        <?php
                                            }

                                        ?>
                            </select>
                        </div>
                        
                        <div>
                            <button type="submit" class="btn btn-primary" id="search"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                        </div>
                    </div>

                </form>
            </div>
            <div class="acciones-container mt-3">
                <!-- Dropdown Amortizar -->
                <div class="accion-dropdown amortizar dropdown">
                    <button class="btn btn-ejecutar dropdown-toggle" type="button" id="btnAmortizarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bi bi-calendar2-week"></i> Amortizar
                    </button>
                    <div class="dropdown-menu" aria-labelledby="btnAmortizarDropdown" id="menuAmortizar">
                        <a class="dropdown-item ejecutar-item" href="#" onclick="AccionesControl.ejecutarAmortizacion(); return false;">
                            <i class="bi bi-play-fill"></i> Ejecutar Amortización
                        </a>
                    </div>
                </div>

                <!-- Dropdown Prorratear -->
                <div class="accion-dropdown prorratear dropdown">
                    <button class="btn btn-ejecutar dropdown-toggle" type="button" id="btnProrratearDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bi bi-file-text"></i> Prorratear
                    </button>
                    <div class="dropdown-menu" aria-labelledby="btnProrratearDropdown" id="menuProrratear">
                        <a class="dropdown-item ejecutar-item" href="#" onclick="AccionesControl.ejecutarProrrateo(); return false;">
                            <i class="bi bi-play-fill"></i> Ejecutar Prorrateo
                        </a>
                    </div>
                </div>

                <!-- Dropdown Procesar -->
                <div class="accion-dropdown procesar dropdown">
                    <button class="btn btn-ejecutar dropdown-toggle" type="button" id="btnProcesarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bi bi-check2-square"></i> Procesar
                    </button>
                    <div class="dropdown-menu" aria-labelledby="btnProcesarDropdown" id="menuProcesar">
                        <a class="dropdown-item ejecutar-item" href="#" onclick="AccionesControl.ejecutarProcesamiento(); return false;">
                            <i class="bi bi-play-fill"></i> Ejecutar Proceso
                        </a>
                    </div>
                </div>

                <!-- Botón Resumen IE (sin cambios) -->
                <button class="btn btn-resumen-ie" id="btnResumen" onclick="resumen()">
                    <i class="bi bi-file-earmark-excel"></i> Resumen IE
                </button>

                <!-- Botón Exportar Excluidos -->
                <button class="btn btn-success" id="btnExportarExcluidos" onclick="exportarGastosExcluidos()">
                    <i class="bi bi-file-earmark-excel"></i> Exportar Excluidos
                </button>
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
        
        if (isset($_GET['selectEstado']) != '') {
            $estado = $_GET['selectEstado'];

        } else {
            $estado = '0';
        }
        
        if (isset($_GET['codRubro']) != '') {
            $codRubro = $_GET['codRubro'];
        } else {
            $codRubro = '%';
        }

        $todosLosGastos = $gastos->traerGastos($desde, $hasta, $estado, $codRubro, $codCuenta);

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

                foreach($todosLosGastos as $valor => $key){
            ?>
            <tr>
                <td><?=  substr($key->FECHA->date, 0, 10); ?></td>

                <td><select class="auxiliar" id="selectCentroCosto" onchange="cambiarCentroCosto(this)">
                    <?php 

                     if($key->COD_AUXILIAR == 'SinAsignar'){
                        
                        echo '<option value="" attr-sector = "" attr-numSucursal = "" attr-codAuxiliar="" selected>Sin Asignar</option>';

                    }

                    foreach ($todosLosCentrosCosto as  $y => $centro) {              
               
                    ?>
                            <option value="" attr-sector = "<?= $centro->SECTOR ?>" attr-numSucursal = "<?= $centro->NUM_SUCURSAL ?>" attr-codAuxiliar="<?= $centro->COD_AUXILIAR?>" <?= ($key->COD_AUXILIAR == $centro->COD_AUXILIAR) ? 'selected' : '' ?>>
                                <?php
                                    echo($centro->DESC_AUXILIAR);
                                ?>
                            </option>
                    <?php
                    }
                        
                    ?>
                    </select>
                </td>

                <td><?=  $key->SECTOR ?></td>
                <td><?=  $key->COD_CUENTA ?></td>
                <td style="width: 20rem;"><?= $key->DESC_CUENTA ?></td>
                <td><input type="text" value="<?=  number_format($key->SALDO, 2) ?>" onchange= "actualizarSaldo(this)" ></td>
                <td><?=  $key->DESC_LEYENDA ?></td>
                <td><?=  $key->T_COMP ?></td>
                <td><?=  $key->RAZON_SOCIAL ?></td>
                <td><?=  $key->N_COMP ?></td>
                <td>
                    <select class="codRubro" style="width: 8rem;" onchange="completarCampoRubro(this)">
                        <option selected disabled><?=  $key->COD_RUBRO ?></option>
                        <?php           
                        foreach($todosLosRubros as $valor => $value){
                        ?>
                        <option value="<?= $value->COD_RUBRO; ?>"><?= $value->COD_RUBRO.'-'.$value->RUBRO_CONTABLE; ?></option>
                        <?php   
                         }
                        ?>
                    </select>
                </td>
                <td><?=  $key->RUBRO_CONTABLE ?></td>
                <td>
                    <select class="codProrrateo" style="width: 2.2rem;">
                        <option selected disabled><?=  $key->COD_PRORRATEO ?></option>
                        <?php           
                        foreach($todosLosMetodos as $valor => $value){
                        ?>
                        <option value="<?= $value->COD_PRORRATEO; ?>"><?= $value->COD_PRORRATEO.'-'.$value->DESC_PRORRATEO; ?></option>
                        <?php   
                         }
                        ?>
                    </select>
                </td>
                <td><?=  $key->DESC_PRORRATEO ?></td>
                <td><?php if ($key->AMORTIZADO == 1){?>
                    <input class="amortiza" type="number" id="amortiza" min="0" name="inputNum" value="<?=  $key->AMORTIZAR ?>" disabled>
                <?php } else { ?> 
                    <input class="amortiza" type="number" id="amortiza" min="1" name="inputNum" value="<?=  $key->AMORTIZAR ?>">
                <?php } ?> 
                </td>
                <td><input class="checkExcluir" type="checkbox" <?php if ($key->EXCLUIR == 1) {echo 'checked';} ?>></td>
                <td><input class="checkControlado" type="checkbox" <?php if ($key->CONTROLADO == 1) {echo 'checked';} ?>></td>
                <td><input class="checkAmortizado" type="checkbox" <?php if ($key->AMORTIZADO == 1) {echo 'checked';} ?> disabled></td>
                <td><?=  $key->MODULO ?></td>
                <td><?=  $key->NUM_SUCURSAL ?></td>
                <td><?=$key->ID?></td>
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
    <script src="js/control-gastos-actions.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

    <link rel="stylesheet" type="text/css" href="../comercioExterior/assets/select2/select2.min.css">

    <link rel="stylesheet" type="text/css" href="../comercioExterior/assets/select2/select2.min.css">
    <script src="../comercioExterior/assets/select2/select2.min.js"></script>
    <!-- <script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script> -->
    <script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>






</body>

    
<script>

    $(document).ready(function() {
        $('#myTable').DataTable({
            responsive: true,
        });

        $('.codRubro').select2();
        document.querySelector(".toggle").style.width="40px"
    document.querySelector(".toggle-on").style.fontSize="0"
    document.querySelector(".toggle-off").style.fontSize="0"
    document.querySelector('.toggle.btn.btn-primary').style.height = '38px'
    document.querySelector('.toggle.btn.btn-primary').style.marginTop = 'px'
    
    // Validar módulos al cargar la página
    validarModulos();

    // Actualizar campos desde/hasta cuando cambian mes o año
    $('#mes, #selectAño').on('change', function() {
        actualizarFechas();
    });

    // Actualizar fechas antes de enviar el formulario
    $('form').on('submit', function(e) {
        actualizarFechas();
    });
        
    });
    
    $('.codCuenta').select2();

    function actualizarFechas() {
        var mes = $('#mes').val();
        var anio = $('#selectAño').val();
        
        // Calcular primer y último día del mes
        var primerDia = anio + '-' + mes + '-01';
        var ultimoDia = new Date(anio, mes, 0).getDate();
        var ultimaFecha = anio + '-' + mes + '-' + ultimoDia;
        
        $('#desde').val(primerDia);
        $('#hasta').val(ultimaFecha);
    }

    $(function() {
        $('[data-toggle="tooltip"]').tooltip()
    })

    $('#myModal').modal('toggle')



</script>

</html>

<?php

include('modals/articuloSinCn.php');
include('modals/coeficientesAjuste.php');
include('modals/articuloSinPrecioCosto.php');
include('modals/ventasCobranzaTotal.php');
include('modals/ventasBrutasPorSucursal.php');
include('modals/modulosGastos.php');

?>