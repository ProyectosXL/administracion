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
    <link rel="icon" href="../image/icono.jpg?v=2">


    <link rel="stylesheet" href="css/control-gastos-actions.css">
    <link rel="stylesheet" href="css/control-gastos-modern.css">

</head>

<body>  

    <div class="row align-items-center">

        <a href="http://192.168.0.13:8000/" class="btn-home" title="Volver al menú">
            <img src="../image/home-button.png" alt="Menú">
        </a>

        <div class="progressbar-wrapper">
            <div hidden id="periodo" attr-periodo= "<?= $periodo ?>" style="margin-top:-2rem;"></div>
            <ul class="progressbar" >
                <li class="" id="paso1"><span class="paso-label" data-tooltip="Calcular y grabar las ventas sin IVA">Paso 1</span></li>
                <li class="" id="paso2"><span class="paso-label" data-tooltip="Verificar que la venta coincida con la cobranza (sucursales)">Paso 2</span></li>
                <li class="" id="paso3"><span class="paso-label" data-tooltip="Verificar artículos sin costo de nacionalización">Paso 3</span></li>
                <li class="" id="paso4"><span class="paso-label" data-tooltip="Verificar artículos sin precio de costo">Paso 4</span></li>
                <li class="" id="paso5"><span class="paso-label" data-tooltip="Calcular y grabar el costo de mercadería vendida">Paso 5</span></li>
                <li class="" id="paso6"><span class="paso-label" data-tooltip="Calcular y grabar los métodos de prorrateo">Paso 6</span></li>
                <li class="" id="paso7"><span class="paso-label" data-tooltip="Traer los registros para control integral">Paso 7</span></li>
                <li class="" id="paso8"><span class="paso-label" data-tooltip="Aplicar coeficiente de ajuste por inflación">Paso 8</span></li>
            </ul>
            <div id="pasoTooltipBox" class="paso-tooltip-box" role="tooltip"></div>
        </div>
        <div>
            <button class="btn btn-primary ml-1 mt-3" id="btnEjecutar" style="margin-right:10">Ejecutar <i class="bi bi-check2-square"></i></button>
            <button class="btn btn-warning mt-3" id="btnGestionModulos" onclick="abrirGestionModulos()">
                <i class="bi bi-gear-fill"></i> Gestión
            </button>

            <div class="custom-toggle-container" onclick="cambiarEntornoCustom(this)" title="Cambiar entorno ARG / UY">
                <div class="toggle-flag <?= $checkedValue === 'central' ? 'active' : '' ?>" data-entorno="central">
                    <img src="images/bandera_con_sol__55757_std.jpg" alt="ARG">
                </div>
                <div class="toggle-flag <?= $checkedValue === 'uy' ? 'active' : '' ?>" data-entorno="uy">
                    <img src="images/UY.png" alt="UY">
                </div>
            </div>

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
                                <option value="4" <?= (isset($_GET['selectEstado']) &&  $_GET['selectEstado'] == '4') ? "selected" : "" ?>>Pendiente prorratear</option>
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

                <!-- Dropdown Exportar -->
                <div class="accion-dropdown exportar dropdown">
                    <button class="btn btn-ejecutar dropdown-toggle" type="button" id="btnExportarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bi bi-file-earmark-excel"></i> Exportar
                    </button>
                    <div class="dropdown-menu" aria-labelledby="btnExportarDropdown" id="menuExportar">
                        <a class="dropdown-item ejecutar-item" href="#" onclick="resumen(); return false;">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Resumen IE
                        </a>
                        <a class="dropdown-item ejecutar-item" href="#" onclick="exportarGastosExcluidos(); return false;">
                            <i class="bi bi-file-earmark-excel"></i> Exportar Excluidos
                        </a>
                    </div>
                </div>
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

    <script>
    var todosLosRubros       = <?= json_encode($todosLosRubros) ?>;
    var todosLosMetodos      = <?= json_encode($todosLosMetodos) ?>;
    var todosLosCentrosCosto = <?= $centroCostos ?>;
    var gastosData           = <?= $todosLosGastos ?>;
    </script>

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
                    <th style="position: sticky; top: 0; z-index: 10;"><i class="bi bi-x-square biHeader" data-toggle="tooltip" data-placement="top" title="Excluir gasto"></i></th>
                    <th style="position: sticky; top: 0; z-index: 10;"><i class="bi bi-check2-square biHeader" data-toggle="tooltip" data-placement="top" title="Gasto controlado"></i></th>
                    <th style="position: sticky; top: 0; z-index: 10;"><i class="bi bi-graph-up biHeader" data-toggle="tooltip" data-placement="top" title="Gasto amortizado"></i></th>
                    <th style="position: sticky; top: 0; z-index: 10;">MODULO</th>
                    <th style="position: sticky; top: 0; z-index: 10;">NRO. SUC.</th>
                    <th style="position: sticky; top: 0; z-index: 10;">ID</th>
                </tr>
            </thead>
            <tbody id="gastos-tbody"></tbody>
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






</body>

    
<script>

    $(document).ready(function() {
        // La tabla se pinta vía AJAX en renderizarTablaGastos() (llamada desde functions.js)
        // DataTable y select2 de .codRubro se inicializan dentro de esa función

        $('.codCuenta').select2();

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

        // Pintar tabla si hay filtros activos
        if (document.getElementById('gastos-tbody')) {
            renderizarTablaGastos();
        }
    });

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
        $('[data-toggle="tooltip"]').tooltip({ container: 'body' })
    })

    // Tooltip custom para etiquetas de pasos
    ;(function() {
        var box   = document.getElementById('pasoTooltipBox');
        var open  = null;

        document.querySelectorAll('.paso-label[data-tooltip]').forEach(function(span) {
            span.addEventListener('click', function(e) {
                e.stopPropagation();
                if (open === span && box.classList.contains('visible')) {
                    box.classList.remove('visible');
                    open = null;
                    return;
                }
                box.textContent = span.getAttribute('data-tooltip');
                // Posicionar encima del span
                var r = span.getBoundingClientRect();
                box.style.left = '0';
                box.style.top  = '0';
                box.classList.add('visible');
                var bw = box.offsetWidth;
                var bh = box.offsetHeight;
                var left = r.left + r.width / 2 - bw / 2;
                var top  = r.top - bh - 8;
                // Evitar salir de la pantalla por los bordes
                left = Math.max(8, Math.min(left, window.innerWidth - bw - 8));
                if (top < 8) top = r.bottom + 8;
                box.style.left = left + 'px';
                box.style.top  = top  + 'px';
                open = span;
            });
        });

        document.addEventListener('click', function() {
            box.classList.remove('visible');
            open = null;
        });
    }());

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