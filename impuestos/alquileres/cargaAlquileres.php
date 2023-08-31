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
    $estado = $alquiler->traerEstado($periodo);

    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();
    $detalle = $alquiler->traerDetalle($periodo);
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
            <?php
                require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
            ?>

            </link>

        </head>
        <body style="width:2800px">

            <div class="alert alert-secondary">
                <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                    <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Carga de Alquileres</h6></div>
                        <div class="card card-1">

                            <div class="row" style="margin-left:50px">
                                <h3><strong><i class="bi bi-bank2" style="margin-right:20px;font-size:40px"></i>Alquileres - <?= $fechaParaMostrar ?></strong></h3>
                            </div>

                            <form class="form-inline" action="#" method="get" style="margin-bottom:20px">
                                <div style="margin-top:10px">
                                    <div hidden ><input type="text" id="userName" name="userName" value="<?= $userName ?>"></div>
                                    <div hidden id="periodo"><?= isset($periodo) ? $periodo : "" ?></div>
                                        <div class="form-group" style="margin-left:50px">
                                            <label for="email">Mes: </label>
                                            <select class="form-control ml-2" style="width: 5rem;" name="mes" id="selectMes">
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
                                            <label class="ml-2" for="email">Año: </label>
                                            <select class="form-control ml-2" style="width: 5rem;" name="anio" id="selectAnio">
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
                                        
                                            <button class="btn btn-primary btn-submit ml-2">filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                        </div>
                                    </div>
                                    <div class="btn-with-icon">
                                        <button style="margin-left:20rem; margin-top:0.5rem;" type="button" class="btn btn-success" onclick="procesar()">Procesar <i class="bi bi-check-circle" style="color:white"></i></button>
                                    </div>
                                    <div class="btn-with-icon">
                                        <?php 
                                        if($estado == 1){
                                            echo '<div  id="estado" hidden>1</div>';
                                            echo '<button type="button" class="btn btn-primary" value="Abrir Periodo" style="margin-left: 20px; margin-top:0.5rem;" onclick="abrirPeriodo()">Abrir Periodo <i class="bi bi-unlock"></i></button>';
                                        }else{
                                            echo '<div  id="estado" hidden>0</div>';
                                            echo '<button type="button" class="btn btn-danger" value="Cerrar Periodo" style="margin-left: 20px; margin-top:0.5rem;" onclick="cerrarPeriodo()">Cerrar Periodo <i class="bi bi-lock"></i></button>';
                                        }
                                        ?>
                                        <!-- <span class="bi bi-check-circle-fill" style="color:white"></span> -->
                                    </div>
                            </form>

                            <div style="margin-left:50px;margin-bottom:10px"><strong><i class="bi bi-check-circle"> Control Por Sucursal</i></strong></div>
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
                                                                    if($estado == 1){
                                                                        foreach ($detalle as $key => $det) {

                                                                            if($det['NRO_SUCURS'] == $k && $det['ID_CA'] == $value['ID_CA']){

                                                                                $porcentajeDelLocal = $det['PORCENTAJE_APLICADO'];

                                                                            }
                                                                           
                                                                        }
                                                                    }else{

                                                                        $porcentajeDelLocal = $porcentaje['PORCENTAJE'];

                                                                    }
                                                                    
                                                                    
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
                                                            <td style='text-align:center;padding-top:3px;padding-bottom:3'><input type="text" value="<?= ($val[$value['CONCEPTO']] < 0) ? "-" : "" ?>$<?php echo number_format($valor, 0, ',', '.') ?>"  attr-realvalue="<?= $val[$value['CONCEPTO']] ?>" class='form-control form-control-sm'  style="width:100px"id='input-<?=$value['ID_CA']?>-<?=$k?>' onchange='totalizar(this)' <?= in_array($value['ID_CA'],$readOn) ? "readOnly" : "" ?> <?php if($value['carga_manual'] != 1) {echo ' data-toggle="tooltip" data-placement="top" title="PORCENTAJE : '.$porcentajeDelLocal.'% - VALOR DE RENTABILIDAD: $'.number_format($rentabilidadDelConcepto, 0, ',', '.').'"'; } ?> attr-porcentaje='<?= $porcentajeDelLocal?>'></td>

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
            
            <?php
                require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php';
            ?>

            <script src="js/cargaAlquileres.js"></script>

        </body>

    </html>
<script>

document.ready = comprobarEstado(<?= $estado ?>);

$(document).ready(function() {

    $(function() {
        $('[data-toggle="tooltip"]').tooltip()
    })

    if(<?= $result['CONTEO'] ?> == 0){
        insertarDetalle();
    }else{
        actualizarCargaAutomatica(<?= $estado ?>);
    }

});

</script>
