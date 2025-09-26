<?php
    require_once "Class/Alquiler.php";
    require_once "Class/Periodo.php";
    require_once "Controller/AlquilerController.php";

    $alquiler = new Alquiler();
    $periodoClass = new Periodo();

    $mes = isset($_GET['mes']) ? $_GET['mes'] : date('m',  strtotime( date("Y-m-d")));
    $anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y',  strtotime( date("Y-m-d")));
    
    $fechaParaMostrar = $mes."/".$anio;
    $currentYear = date('Y',  strtotime( date("Y-m-d")));
    $yearDif = $currentYear - 2023;
    $fecha = $anio."-".$mes;

    $periodo = (int)$mes."-".$anio;

    
    $mesAnterior = $periodoClass->hacerPeriodo($fecha, 1);

    $ultimaFechaDelMes = $periodoClass->desHacerPeriodo($periodo);
    

    // $userName = $_GET['userName'];
 
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
    $sucursalesOcultas = $alquiler->traerSucursalesOcultas($periodo);
    $arraySucursalesOcultas = [];
    $sucursalesOcultasArray = [];
    if(count($sucursalesOcultas) > 0){
        $arraySucursalesOcultas = json_decode($sucursalesOcultas[0]['JSON_LOCALES'],true);
        $sucursalesOcultasArray = explode(',', $arraySucursalesOcultas['sucursales']);
    }

    // if (session_status() == PHP_SESSION_NONE) {
    //     session_start();
    // }
    
    if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
        $checked = 'checked';
    }else{
        $checked = '';
    }
        
    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $dataOnValue = ($checkedValue === 'uy') ? 'UY' : 'ARG';
    $dataOffValue = ($checkedValue === 'uy') ? 'ARG' : 'UY';
    $imageOn = ($checkedValue === 'central') ? '../../assets/images/bandera_con_sol__55757_std.jpg' : '../../assets/images/UY.png';
    $imageOff = ($checkedValue === 'central') ? '../../assets/images/UY.png' : '../../assets/images/bandera_con_sol__55757_std.jpg';
    
    

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
        <body style="width:2800px">

            <div class="alert alert-secondary">
                <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                    <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Carga de Alquileres</h6></div>
                        <div class="card card-1">

                            <div class="row" style="margin-left:50px">
                                <a href="http://192.168.0.13:8000/" style="display:inline-block;">
                                    <img src="../../image/home-button.png" style="width:50px;height:45px;margin-right:1rem;transition: transform 0.3s;" title="Menú" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                </a>
                                <h3><strong><i class="bi bi-bank2" style="margin-right:20px;font-size:40px"></i>Alquileres - <?= $fechaParaMostrar ?></strong></h3>
                                <div style="margin-top:30px;margin-left:50%">
                                    <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;" onchange="cambiarEntorno(this)" id="checkEntorno" >
                                </div>
                            </div>

                            <form class="form-inline" action="#" method="get" style="margin-bottom:20px">
                                <div style="margin-top:10px">

                                    <div hidden id="periodo"><?= isset($periodo) ? $periodo : "" ?></div>
                                    <div hidden id="ultimaFechaDelMes"><?= isset($ultimaFechaDelMes) ? $ultimaFechaDelMes : "" ?></div>
                                    <div hidden id="mesAnterior"><?= isset($mesAnterior) ? $mesAnterior : "" ?></div>

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
                                    <div class="btn-group">
                                        <button style="margin-left:8rem; margin-top:0.5rem;" type="button" class="btn btn-info" onclick="AplicarAjuste()">Aplicar Ajuste <i class="bi bi-check-circle" style="color:white"></i></button>
                                        <?php 
                                        if($estado == 1){
                                            echo '<div  id="estado" hidden>1</div>';
                                            echo '<button type="button" class="btn btn-primary" value="Abrir Periodo" style="margin-top:0.5rem;" onclick="abrirPeriodo()">Abrir Periodo <i class="bi bi-unlock"></i></button>';
                                        }else{
                                            echo '<div  id="estado" hidden>0</div>';
                                            echo '<button type="button" class="btn btn-secondary" value="Cerrar Periodo" style="margin-top:0.5rem;" onclick="cerrarPeriodo()">Cerrar Periodo <i class="bi bi-lock"></i></button>';
                                        }
                                        ?>
                                        <!-- <span class="bi bi-check-circle-fill" style="color:white"></span> -->
                                        <button style="margin-top:0.5rem; width:140px" type="button" class="btn btn-success" onclick="procesar()">Procesar <i class="bi bi-check-circle" style="color:white"></i></button>
                                    </div>
                                    <div style="margin-left:10rem">
                                        <select class="form-control ml-6 mt-2" id="selectOcultarSucursal">
                                            <option value="" disabled selected>Selecciona una sucursal</option>
                                            <?php 
                                                foreach ($todosLosLocales as $key => $local) {
                                                    if (in_array($local['NRO_SUCURSAL'], $sucursalesOcultasArray)) {
                                                        continue;
                                                    }
                                                    echo '<option value="'.$local['NRO_SUCURSAL'].'">'.$local['DESC_SUCURSAL'].' ('.$local['NRO_SUCURSAL'].')</option>';
                                                }
                                            ?>
                                        </select>
                                        <button class="btn btn-danger mt-2" onclick="ocultarSucursal()">Eliminar <i class="bi bi-trash"></i></button>
                                    </div>
                                    <div>
                          
                                    </div>
                            </form>

                            <div style="margin-left:50px;margin-bottom:10px"><strong><i class="bi bi-check-circle"> Control Por Sucursal</i></strong></div>
                            <?php 
                                $width = '';
                                
                                if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                                    $width = 'width:30%';
                                }
                            ?>
                                <table class="table table-striped table-bordered table-sm table-hover" id="tablaAlquileres" style="font-size :12px;<?= $width ?>" >
                                    <thead class="thead-dark">
                                        <tr>
                                            <th style="text-align:center;width:5%" id="thIdConcepto">ID</th>
                                            <th style="text-align:center;width:10%" id="thConcepto"  >CONCEPTOS </th>
                                            <?php 
                                                foreach ($todosLosLocales as $key => $value) {   

                                                    if (in_array($value['NRO_SUCURSAL'], $sucursalesOcultasArray)) {
                                               
                                                        continue;
                                                    } 
                                                    $width = '';
                                                    if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy'){
                                                        $width = 'width:1%';
                                                    }
                                            ?>
                                                <th style="text-align:center;<?= $width ?>" id="sucursal"  class = "suc<?= $value['NRO_SUCURSAL']?>" attr-infosuc="<?= $value['DESC_SUCURSAL']?>-<?= $value['NRO_SUCURSAL']?>"><?= $value['NRO_SUCURSAL']?></th>
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

                                                            
                                                            if (in_array($k, $sucursalesOcultasArray)) {
                                                    
                                                                continue;
                                                            } 
                                                
                                                    ?>  
                                                            <td style='text-align:center;padding-top:3px;padding-bottom:3' class = "suc<?= $k ?>"><input type="text" value="<?= ($val[$value['CONCEPTO']] < 0) ? "-" : "" ?>$<?php echo number_format($valor, 0, ',', '.') ?>"  attr-realvalue="<?= $val[$value['CONCEPTO']] ?>" class='form-control form-control-sm'  style="width:100px"id='input-<?=$value['ID_CA']?>-<?=$k?>' onchange='totalizar(this)' <?= in_array($value['ID_CA'],$readOn) ? "readOnly" : "" ?> <?php if($value['carga_manual'] != 1) {echo ' data-toggle="tooltip" data-placement="top" title="PORCENTAJE : '.$porcentajeDelLocal.'% - VALOR DE RENTABILIDAD: $'.number_format($rentabilidadDelConcepto, 0, ',', '.').'"'; } ?> attr-porcentaje='<?= $porcentajeDelLocal?>'></td>

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

                                                if (in_array($local['NRO_SUCURSAL'], $sucursalesOcultasArray)) {
                                               
                                                    continue;
                                                }
                                                
                                        ?>
                                                <td id="total-<?= $local['NRO_SUCURSAL'] ?>" class="suc<?= $local['NRO_SUCURSAL'] ?>"></td>
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

            <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
            <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
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

    document.querySelector(".toggle").style.width="40px"
    document.querySelector(".toggle-on").style.fontSize="0"
    document.querySelector(".toggle-off").style.fontSize="0"
    document.querySelector('.toggle.btn.btn-primary').style.height = '38px'

});

</script>
