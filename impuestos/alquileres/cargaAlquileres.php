<?php
    require_once "Class/Alquiler.php";
    require_once "Controller/AlquilerController.php";

    $alquiler = new Alquiler();

    $mes = isset($_GET['mes']) ? $_GET['mes'] : date('m',  strtotime( date("Y-m-d")));
    $anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y',  strtotime( date("Y-m-d")));
    
    $fechaParaMostrar = $mes."/".$anio;
    $currentYear = date('Y',  strtotime( date("Y-m-d")));
    $yearDif = $currentYear - 2023;
    $fecha = $anio."-".$mes;

    $periodo = (int)$mes."-".$anio;

    $userName = $_GET['userName'];
 
    $result = $alquiler->conteoDetalle($periodo);

    $todosLosLocales = traerLocales();
    $conceptos = traerConceptos();


    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();
    
    $rentabilidadNeta = $alquiler->traerRentabilidadNeta($fecha);
    $rentabilidadBruta = $alquiler->traerRentabilidadBruta($periodo); 
    
    if($result['CONTEO'] > 0){
 
        $newArray = traerDetalleAlquiler($fecha,$periodo);
    }else{


        $newArray = cargarAlquieres($fecha,$periodo);
    }

?>

<!DOCTYPE html>
    <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Carga Alquileres</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
            <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
            <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

            <!-- Bootstrap Icons -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <link rel="stylesheet" href="Css/cargaAlquileres.css">

            </link>

        </head>
        <body style="width:2800px">

            <div class="alert alert-secondary">
                <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                    <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Carga de Alquileres</h6></div>
                        <div class="card card-1">

                            <div class="row" style="margin-left:50px">
                                <h3><strong><i class="bi bi-bank2" style="margin-right:20px;font-size:50px"></i>Alquileres - <?= $fechaParaMostrar ?></strong></h3>
                            </div>

                            <form action="#" method="get" style="margin-bottom:20px">
                                <div class="row" style="margin-top:10px">
                                    <div class="col-4" style="margin-left:50px">
                                    <div hidden ><input type="text" id="userName" name="userName" value="<?= $userName ?>"></div>
                                    <div hidden id="periodo"><?= isset($periodo) ? $periodo : "" ?></div>
                                        mes:
                                        <select name="mes" id="selectMes">
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
                                        año:
                                        <select name="anio" id="selectAnio">
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
                                        <button class="btn btn-primary btn-submit" value="" style="height:45px;margin-left:2px;position:relative;margin-bottom:8px">filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                    </div>
                                    <div class="btn-with-icon">
                                        <input type="button" class="btn btn-success" value="Procesar " style="margin-left: 200px; height: 45px;width:110px" onclick="procesar()"/>
                                        <span class="bi bi-check-circle-fill" style="color:white"></span>
                                    </div>
                                </div>
                            </form>

                            <div style="margin-left:50px;margin-bottom:10px"><strong><i class="bi bi-check-circle">Control Por Sucursal</i></strong></div>
                                <table class="table table-striped table-bordered table-sm table-hover" id="tablaAlquileres" style="font-size :12px;" >
                                    <thead class="thead-dark">
                                        <tr>
                                            <th style="text-align:center;width:30px" id="thIdConcepto">ID</th>
                                            <th style="text-align:center;width:100px" id="thConcepto"  >CONCEPTOS </th>
                                            <?php 
                                                foreach ($todosLosLocales as $key => $value) {    
                                            ?>
                                                <th style="text-align:center;width:50px" id="sucursal" attr-infosuc="<?= $value['DESC_SUCURSAL']?>-<?= $value['NRO_SUCURSAL']?>"><?= $value['NRO_SUCURSAL']?></th>
                                            <?php
                                                }
                                            ?>

                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php 
                                            foreach ($conceptos as $key => $value) {    
                                        ?>
                                                <tr>
                                                    <td style="text-align:center" id="idConcepto"><?= $value['ID_CA']?> </td>
                                                    <td style="text-align:center" id="concepto"><strong><?= $value['CONCEPTO']?></strong> </td>
                                                    <?php   
                                                        foreach ($newArray as $k => $val) {

                                                            $porcentajeDelLocal = 0;
                                                            $rentabilidadDelConcepto = 0;
                                                            
                                                            // solo lectura inputs automaticos
                                                            $readOn = [6,7,9,13,14,15,16,17];   

                                                            foreach ($traerPorcentajes as $porcentaje) {
  
                                                                if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $k){
                                                                    
                                                                   $porcentajeDelLocal = $porcentaje['PORCENTAJE'];
                                                                    
                                                               }
                                           
                                                            }

                                                            if(in_array($value['ID_CA'], ["6", "15","16"])){

                                                                foreach ($rentabilidadBruta as $rentabilidad) {
                                                                    if($rentabilidad['NRO_SUCURSAL'] == $k){
                                                                        $rentabilidadDelConcepto = $rentabilidad['IMPORTE'];
                                                                    }
                                                                }

                                                            }

                                                            if(in_array($value['ID_CA'], ["7", "14","17"])){

                                                                foreach ($rentabilidadNeta as $rentabilidad) {
                                                                    if($rentabilidad['NRO_SUCURS'] == $k){
                                                                        $rentabilidadDelConcepto = $rentabilidad['VENTA'];
                                                                    }
                                                                }

                                                            }

                                                            $valor = $val[$value['CONCEPTO']];
                                                            if($valor < 0){
                                                                $valor = $valor * -1;
                                                            }
                                                    ?>  
                                                            <td style='text-align:center;padding-top:3px;padding-bottom:3'><input type="text" value="<?= ($val[$value['CONCEPTO']] < 0) ? "-" : "" ?>$<?php echo number_format($valor, 0, ',', '.') ?>"  attr-realvalue="<?= $val[$value['CONCEPTO']] ?>" class='form-control form-control-sm'  style="width:100px"id='input-<?=$value['ID_CA']?>-<?=$k?>' onchange='totalizar(this)' <?= in_array($value['ID_CA'],$readOn) ? "readOnly" : "" ?> <?php if($value['carga_manual'] != 1) {echo ' data-toggle="tooltip" data-placement="top" title="PORCENTAJE : '.$porcentajeDelLocal.'% - VALOR DE RENTABILIDAD: $'.number_format($rentabilidadDelConcepto, 0, ',', '.').'"'; } ?>></td>

                                                    <?php 
                                                        }
                                                    ?>

                                                </tr>
                                        <?php
                                            }
                                        ?>

                                        <tr>
                                            <td></td>
                                            <td id="concepto"><h5 style="font-size:15px" >Total</h5></td>
                                        <?php
                                            foreach ($todosLosLocales as $local) {
                                        ?>
                                                <td id="total-<?= $local['NRO_SUCURSAL'] ?>"></td>
                                        <?php
                                            }
                                        ?>

                                        </tr>

                                    </tbody>

                                </table>
                            </div>
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
            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
            <script src="js/cargaAlquileres.js"></script>

        </body>

    </html>
<script>

document.ready = totalizar();

$(document).ready(function() {

    $(function() {
        $('[data-toggle="tooltip"]').tooltip()
    })

    if(<?= $result['CONTEO'] ?> == 0){
        insertarDetalle();
    }else{
        actualizarCargaAutomatica();
    }

});

</script>
