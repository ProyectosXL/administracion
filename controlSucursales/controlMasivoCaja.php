<?php 
    session_start();
require_once "Class/sucursal.php";


if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
    $checked = 'checked';
}else{
    $checked = '';
}
    
$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$dataOnValue = ($checkedValue === 'uy') ? 'UY' : 'ARG';
$dataOffValue = ($checkedValue === 'uy') ? 'ARG' : 'UY';
$imageOn = ($checkedValue === 'central') ? '../assets/images/bandera_con_sol__55757_std.jpg' : '../assets/images/UY.png';
$imageOff = ($checkedValue === 'central') ? '../assets/images/UY.png' : '../assets/images/bandera_con_sol__55757_std.jpg';




if(isset($_GET['mes']) &&$_GET['mes'] != "" ){
    $mes = $_GET['mes'];
}else{
    $mes = date("m");
}
if(isset($_GET['anio']) &&$_GET['anio'] != "" ){
    $anio = $_GET['anio'];
}else{
    $anio = date("Y");
}

$dataSucursal = (isset($_GET['sucursal'])) ?  explode("-", $_GET['sucursal']) : ['2','UNICENTER'];

$medioPagoSelected = (isset($_GET['medioPago'])) ? explode("-", $_GET['medioPago'])  : "MODO_QR";



$currentYear = date('Y',  strtotime( date("Y-m-d")));
$yearDif = $currentYear - 2023;

$sucursal = new Sucursal();
$todosLosLocales= $sucursal->traerLocales(true);
$todosLosMediosDePago = $sucursal->traerTodosLosMediosDePago();


$periodoRerverse = $anio."-".$mes;

$primerDia = date('Y-m-01', strtotime($periodoRerverse));

$ultimoDia = date('Y-m-t', strtotime($primerDia));


$todosLosImportes= $sucursal->traerImportesTotalesPorPeriodo($dataSucursal[0], $primerDia, $ultimoDia,str_replace("_", " ", $medioPagoSelected[1]));

$verificado = true;

foreach ($todosLosImportes as $key => $value) {

        if($value['VERIFICADO'] == 0){
            $verificado = false;
        }

}


?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Control masivo de cobranza</title>

        <!-- INCLUDE CSS FILES -->
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
        ?>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

        <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css"> -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        </link>
        <style>

            .select2-dropdown.select2-dropdown--above {

                width:300px;
            }
            input[type='search'] {
                margin-right:45px
            }
            .dataTables_length{
                margin-left:50px
            }
            .dataTables_info{
                margin-left:50px
            }
            #tablaArticulos_paginate{
                margin-right:42px 
            }
            thead {
                position: sticky;
                top: 0;
            }
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
        
      
        <input type="file" name="archivos[]" id="archivos" multiple accept=".pdf, .jpg, .png" style="display: none;" />
        <div id="carruselImagenes" class="modal fade" tabindex="-1" aria-hidden="true" style="margin-left:10%;max-width:80%"></div>
        <div id="nroSucursal" hidden><?= $nroSucurs; ?></div>

        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">

                <div class="wrapper wrapper--w880"><div style="color:white; text-align:center"><h6>Control Masivo de Cobranza</h6></div>

                    <div class="card card-1">
                        <div id="periodo" hidden><?= $periodo ?></div>
                        <div class="row" style="margin-left:50px; margin-top:20px">
                        

                            <h3><strong><i class="bi bi-cash" style="margin-right:20px;font-size:40px"></i>Control Masivo de Cobranza - <?= $dataSucursal[1] ?>( <?= $medioPagoSelected[1] ?>)</strong></h3>


                        </div>
                        <form class="form-inline" action="#">
                          
                            <div style="margin-bottom:20px">

                                <div class="row" style="margin-top:10px">
                                        <?php
                               
                                        ?>
                                    <div style="margin-left:4rem" >Mes : 


                                    <select name="mes" id="mes" class="form-control">
                                        <?php 
                                            for ($i=1; $i <= 12 ; $i++) { 
                                                if(strlen($i) == 1){
                                                    $i = "0".$i;
                                                }
                                        ?>

                                        <option value="<?=$i?>" <?php if($mes == $i ) echo "selected"?>><?=$i?></option>

                                        <?php
                                            }
                                        ?>

                                    </select>

                                    </div>
                                    <div style="margin-left:1rem">Año : 
                                    <select name="anio" id="selectAño" class="form-control">
                                        <option value="2022">2022</option>
                                        <?php 
                                            for ($i=0; $i <= $yearDif ; $i++) { 
                                                $y = 2023 + $i;
                                        ?>

                                            <option value="<?=$y?>" <?php if($anio == $y ) echo "selected"?>><?=$y?></option>

                                            
                                        <?php
                                            }
                                        ?>

                                    </select>
                                    </div>

                                    <div style="margin-left:1rem">Sucursal : 
                                    <select name="sucursal" id="sucursal" class="form-control">
                                        <?php   
                                            foreach ($todosLosLocales as $key => $local) {

                                        ?>

                                                <option value="<?= $local['NRO_SUCURSAL'] ?>-<?= $local['DESC_SUCURSAL'] ?>" <?= ($dataSucursal[0] == $local['NRO_SUCURSAL']) ? "selected" : "" ?>><?= $local['DESC_SUCURSAL'] ?></option>


                                        <?php
                                            }
                                        ?>
                                        
                                    </select>
                                    </div>
                                    <div style="margin-left:1rem">Medio pago : 
                                    <select name="medioPago" id="medioPago" class="form-control">
                                        <?php 
                                            foreach ($todosLosMediosDePago as $key => $medioPago) {
                                        ?>

                                                <option value="<?= $medioPago['ID_MP'] ?>-<?= $medioPago['MEDIO_PAGO'] ?>" <?= ($medioPagoSelected[0] == $medioPago['ID_MP']) ? "selected" : "" ?>><?= $medioPago['MEDIO_PAGO'] ?></option>


                                        <?php
                                            }
                                        ?>
                                        
                                    </select>
                                    </div>

                                    <button class="btn btn-primary btn-submit ml-2" onclick= "">Filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                    <div style="margin-left:2rem;">
                                        <button class="btn btn-primary btn-secondary" type="button" onclick= "guardar()">Guardar <i class="bi bi-box-arrow-down" style="color:white"></i></button>
                                        <button class="btn btn-primary btn-primary ml-2" type="button" id="controlar" <?= ($verificado == true) ? "hidden" : "" ?>>Controlar <i class="bi bi-check-circle" style="color:white"></i></button>
                                        <button name="btnExport" type="button" class="btn btn-success  ml-2" id="btnExport" style="margin-right:200px" >Exportar <i class="bi bi-file-earmark-excel"></i></button>
                                        <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;margin-top:10px" onchange="cambiarEntorno(this)" id="checkEntorno" >
                                            
                                    </div>

                                </div>

                            </div>
                        </form>
            

                        <table class="table table-striped table-bordered table-sm table-hover" id="tablaControl" style="width: 95%; height:100px; margin-left:50px" cellspacing="0" data-page-length="100">
                            <thead class="thead-dark" style="">
                                <tr>
                                    <th style="text-align:center;width: 3%;" >FECHA</th>
                                    <th style="text-align:center;width: 3%;" >$ SISTEMA</th>
                                    <th style="text-align:center;width: 3%;" >COTIZACION TC</th>
                                    <th style="text-align:center;width: 3%;" >TOTAL PESOS</th>
                                    <th style="text-align:center;width: 3%;" >$ CONTROL</th>
                                    <th style="text-align:center;width: 5%;">DIFERENCIA</th>

                                    <th style="text-align:center;width: 15%;" >OBSERVACIONES</th>
                                    
                                </tr>
                            </thead>
                            <tbody>

                                            
                            <?php 
                                foreach ($todosLosImportes as $key => $importe) {
                                    if(in_array($medioPagoSelected[0], ['6','9'])){
                                        $importe['IMPORTE_$_FISICO'] = $importe['IMPORTE_$_SISTEMA'] ;
                                    }
                                    $totalEnPesos = $importe['IMPORTE_$_SISTEMA'] * $importe['COTIZACION_TC'];
                                    if($totalEnPesos == "-0"){
                                        $totalEnPesos = 0;
                                    }
                            ?>
                            
                                    <tr>
                                        <td style="text-align:center"><?= $importe['FECHA']->format("Y-m-d") ?></td>
                                        <?php  
                                            
                                            $valorEnSistema = ($importe['IMPORTE_$_SISTEMA'] != null) ? $importe['IMPORTE_$_SISTEMA'] : 0;
                                          
                                            if ($valorEnSistema < 0){

                                                echo "<td style='text-align:center' id='valorSistema'>- $".number_format($valorEnSistema*-1, 0, ',', '.')."</td>";
                                            }else{
                                                echo "<td style='text-align:center' id='valorSistema'>$".number_format($valorEnSistema, 0, ',', '.')."</td>";
                                            }
                                        ?>

                                        <td style="text-align:center">$<?= number_format($importe['COTIZACION_TC'], 0, ',', '.') ?></td>
                                        <td style="text-align:center">$<?=  number_format($totalEnPesos, 0, ',', '.')  ?></td>
                                        <td style="text-align:center"><input type="text" style="text-align:center;width:100%" onchange="calcularDiferecias(this)" id="valorFisico" value="$<?= number_format($importe['IMPORTE_$_FISICO'], 0, ',', '.') ?>" <?= ($importe['VERIFICADO'] == 1) ? "disabled" : "" ?>></td>

                                        <td style="text-align:center" id="diferencias">0</td>
                                        <td style="text-align:center"><input type="text" style="width:100%" value="<?= $importe['OBSERVACIONES'] ?>" id="observacion" <?= ($importe['VERIFICADO'] == 1) ? "disabled" : "" ?>></td>
                                        <td style="text-align:center" hidden ><?= $importe['ID'] ?></td>
                                        
                                    </tr>
                            <?php
                                }
                            ?>
                                        
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>total</td>
                                    <td id="totalEnSistema"  style="text-align:center"></td>
                                    <td></td>
                                    <td></td>
                                    <td id="totalFisico"     style="text-align:center"></td>
                                    <td id="totalDiferencia" style="text-align:center"></td>
                                    <td></td>

                                </tr>
                            </tfoot>

            
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="js/controlMasivo.js"></script>
        <script src="https://cdn.datatables.net/fixedheader/3.1.9/js/dataTables.fixedHeader.min.js"></script>
        <script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
        <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
        <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>


    </body>

</html>
<script>

    $('#tablaArticulos').DataTable({
        "bLengthChange": true,
        "language": {
                    "lengthMenu": "mostrar _MENU_ registros",
                    "info":           "Mostrando registros del _START_ al _END_ de un total de  _TOTAL_ registros",
                    "paginate": {
                        "next":       "Siguiente",
                        "previous":   "Anterior"
                    },

        },
    
        
        "bInfo": true,
        "aaSorting": false,
        'columnDefs': [
            {
                "targets": "_all", 
                "className": "text-center",
                "sortable": false,
         
            },
        ],
        "oLanguage": {
    
            "sSearch": "Busqueda rapida:",
            "sSearchPlaceholder" : "Sobre cualquier campo"
            
    
        },
    });


    $(document).ready( function () {
        document.querySelector(".toggle").style.width="40px"
        document.querySelector(".toggle-on").style.fontSize="0"
        document.querySelector(".toggle-off").style.fontSize="0"
        document.querySelector('.toggle.btn.btn-primary').style.height = '38px'
    })


</script>

<!-- <script src="js/gastosTesoreria.js"></script> -->

