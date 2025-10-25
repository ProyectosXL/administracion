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
    
    // FUNCIONALIDAD DE SUCURSALES OCULTAS DESHABILITADA
    // $sucursalesOcultas = $alquiler->traerSucursalesOcultas($periodo);
    $arraySucursalesOcultas = [];
    $sucursalesOcultasArray = [];
    // if(count($sucursalesOcultas) > 0){
    //     $arraySucursalesOcultas = json_decode($sucursalesOcultas[0]['JSON_LOCALES'],true);
    //     $sucursalesOcultasArray = explode(',', $arraySucursalesOcultas['sucursales']);
    // }

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Inicializar entorno por defecto si no existe
    if (!isset($_SESSION['entorno'])) {
        $_SESSION['entorno'] = 'central';
    }
    
    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $checked = ($checkedValue === 'central') ? 'checked' : '';
    $dataOnValue = 'ARG';
    $dataOffValue = 'UY';
    $imagenBandera = ($checkedValue === 'central') ? 
        '../../assets/images/bandera_con_sol__55757_std.jpg' : 
        '../../assets/images/UY.png';
    $nombrePais = ($checkedValue === 'central') ? 'Argentina' : 'Uruguay';
    

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
            
            <!-- Font Awesome -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            
            <!-- CSS Custom para Carga Alquileres -->
            <link rel="stylesheet" href="css/cargaAlquileres.css">
            
            <!-- CSS para Modal de Ayuda -->
            <link rel="stylesheet" href="css/ayuda.css">
            
            <!-- STICKY COLUMNS - USANDO TRANSFORM -->
            <style>
                .table-responsive {
                    overflow-x: auto;
                    overflow-y: visible;
                }
                
                #tablaAlquileres {
                    border-collapse: separate;
                    border-spacing: 0;
                }
                
                /* Columnas fijas usando transform */
                #tablaAlquileres th:nth-child(1),
                #tablaAlquileres td:nth-child(1) {
                    position: relative;
                    background-color: white;
                    width: 60px;
                    min-width: 60px;
                    max-width: 60px;
                }
                
                #tablaAlquileres th:nth-child(2),
                #tablaAlquileres td:nth-child(2) {
                    position: relative;
                    background-color: white;
                    width: 250px;
                    min-width: 250px;
                    max-width: 250px;
                    border-right: 2px solid #dee2e6 !important;
                }
                
                #tablaAlquileres thead th:nth-child(1),
                #tablaAlquileres thead th:nth-child(2) {
                    background-color: #343a40;
                }
                
                /* Mantener colores alternados */
                #tablaAlquileres tbody tr:nth-child(even) td:nth-child(1),
                #tablaAlquileres tbody tr:nth-child(even) td:nth-child(2) {
                    background-color: #f2f2f2;
                }
                
                #tablaAlquileres tbody tr:last-child td:nth-child(1),
                #tablaAlquileres tbody tr:last-child td:nth-child(2) {
                    background-color: #e9ecef;
                    font-weight: bold;
                }
            </style>
            
            <script>
                // Script inline para manejar el scroll
                document.addEventListener('DOMContentLoaded', function() {
                    const tableWrapper = document.querySelector('.table-responsive');
                    const tabla = document.getElementById('tablaAlquileres');
                    
                    if (!tableWrapper || !tabla) return;
                    
                    function actualizarColumnasFijas() {
                        const scrollLeft = tableWrapper.scrollLeft;
                        
                        // Obtener todas las celdas de la primera y segunda columna
                        const col1 = tabla.querySelectorAll('th:nth-child(1), td:nth-child(1)');
                        const col2 = tabla.querySelectorAll('th:nth-child(2), td:nth-child(2)');
                        
                        col1.forEach(celda => {
                            celda.style.transform = `translateX(${scrollLeft}px)`;
                            celda.style.zIndex = scrollLeft > 0 ? '10' : '1';
                            if (scrollLeft > 0) {
                                celda.style.boxShadow = '2px 0 5px rgba(0,0,0,0.1)';
                            } else {
                                celda.style.boxShadow = 'none';
                            }
                        });
                        
                        col2.forEach(celda => {
                            celda.style.transform = `translateX(${scrollLeft}px)`;
                            celda.style.zIndex = scrollLeft > 0 ? '10' : '1';
                            if (scrollLeft > 0) {
                                celda.style.boxShadow = '2px 0 5px rgba(0,0,0,0.1)';
                            } else {
                                celda.style.boxShadow = 'none';
                            }
                        });
                    }
                    
                    tableWrapper.addEventListener('scroll', actualizarColumnasFijas);
                    actualizarColumnasFijas(); // Ejecutar una vez al cargar
                });
            </script>

            </link>

        </head>
        <style>
              /* Toggle styles */
        .toggle-on, .toggle-off {
            font-size: 12px !important;
            font-weight: bold !important;
            color: white !important;
            text-shadow: none !important;
            line-height: 30px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-size: auto !important;
            background-image: none !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            height: auto !important;
            width: auto !important;
            min-height: 34px !important;
            min-width: 45px !important;
        }

        .toggle.btn {
            height: 38px !important;
            min-width: 90px !important;
            border-radius: 6px !important;
            padding: 0 !important;
        }

        .toggle-on {
            background-color: #007bff !important;
            border-color: #007bff !important;
        }
        
        .toggle-off {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
        }

        .flag-indicator {
            width: 40px;
            height: 30px;
            margin-left: 10px;
            border-radius: 4px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border: 2px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .environment-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .country-label {
            color: white;
            font-size: 14px;
            font-weight: bold;
            margin-left: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        </style>
        <body>

            <div class="alert alert-secondary">
                <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                        <div class="card card-1">

                            <div class="row" style="margin-left:50px">
                                <a href="http://192.168.0.13:8000/" style="display:inline-block;">
                                    <img src="../../image/home-button.png" style="width:50px;height:45px;margin-right:1rem;transition: transform 0.3s;" title="Menú" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                </a>
                                <h3><strong><i class="bi bi-bank2" style="margin-right:20px;font-size:40px"></i>Carga de Gastos de Alquiler - <?= $fechaParaMostrar ?></strong></h3>
                                <div class="environment-toggle" style="margin-left:30%">
                                    <div class="environment-controls">
                                        <input type="checkbox" <?= $checked ?> data-toggle="toggle" 
                                               data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" 
                                               class="custom-toggle" onchange="cambiarEntorno(this)" 
                                               id="checkEntorno">
                                        <div class="flag-indicator" style="background-image: url('<?= $imagenBandera ?>');" 
                                             title="<?= $nombrePais ?>"></div>
                                        <span class="country-label"><?= $nombrePais ?></span>
                                    </div>
                                </div>
                            </div>

                            <form class="form-inline" action="#" method="get" style="margin-bottom:20px;">
                                <div style="margin-top:10px; width:100%;">

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
                                        
                                            <button class="btn btn-primary btn-submit ml-2" data-toggle="tooltip" data-placement="top" title="Filtrar por mes y año">filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                        </div>
                                        
                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px;">
                                            <div class="btn-group" style="margin-left:2rem; display: flex; gap: 10px; flex-wrap: nowrap; align-items: center;">
                                                <button type="button" class="btn btn-info" onclick="AplicarAjuste()" data-toggle="tooltip" data-placement="top" title="Aplicar el coeficiente de ajuste a los conceptos 4, 5 y 18">Aplicar Ajuste <i class="bi bi-check-circle" style="color:white"></i></button>
                                                <button style="width:140px" type="button" class="btn btn-success" onclick="procesar()" data-toggle="tooltip" data-placement="top" title="Enviar datos procesados al informe económico">Procesar <i class="bi bi-check-circle" style="color:white"></i></button>
                                                <?php 
                                                if($estado == 1){
                                                    echo '<div  id="estado" hidden>1</div>';
                                                    echo '<button type="button" class="btn btn-primary" value="Abrir Periodo" onclick="abrirPeriodo()" data-toggle="tooltip" data-placement="top" title="Reabrir el período para realizar modificaciones">Abrir Periodo <i class="bi bi-unlock"></i></button>';
                                                }else{
                                                    echo '<div  id="estado" hidden>0</div>';
                                                    echo '<button type="button" class="btn btn-secondary" value="Cerrar Periodo" onclick="cerrarPeriodo()" data-toggle="tooltip" data-placement="top" title="Cerrar el período y deshabilitar modificaciones">Cerrar Periodo <i class="bi bi-lock"></i></button>';
                                                }
                                                ?>
                                                <!-- <span class="bi bi-check-circle-fill" style="color:white"></span> -->
                                                <button style="width:180px" type="button" class="btn btn-danger" onclick="revertirProcesamiento()" data-toggle="tooltip" data-placement="top" title="Eliminar el procesamiento para poder volver a procesar el período">Revertir Proc. <i class="bi bi-arrow-counterclockwise" style="color:white"></i></button>
                                            </div>
                                            
                                            <div style="margin-right:50px;">
                                                <button style="width:180px; margin-left: 5rem;" type="button" class="btn btn-warning" onclick="descargarPDF()" data-toggle="tooltip" data-placement="top" title="Descargar reporte en formato PDF"><i class="bi bi-file-earmark-pdf" style="color:white"></i> Descargar PDF</button>
                                            </div>
                                        </div>
                                    </div>
                            </form>

                            <?php 
                                // Calcular ancho mínimo según cantidad de sucursales
                                $cantidadSucursales = count($todosLosLocales);
                                // Cada columna de sucursal necesita aprox 120px + las columnas fijas (260px)
                                $anchoMinimo = 260 + ($cantidadSucursales * 120);
                                $width = "min-width: {$anchoMinimo}px;";
                            ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-sm table-hover" id="tablaAlquileres" style="font-size :12px;<?= $width ?>" >
                                    <thead class="thead-dark">
                                        <tr>
                                            <th id="thIdConcepto">ID</th>
                                            <th id="thConcepto">CONCEPTOS</th>
                                            <?php 
                                                foreach ($todosLosLocales as $key => $value) {   

                                                    if (in_array($value['NRO_SUCURSAL'], $sucursalesOcultasArray)) {
                                               
                                                        continue;
                                                    } 
                                            ?>
                                                <th id="sucursal" class="suc<?= $value['NRO_SUCURSAL']?>" attr-infosuc="<?= $value['DESC_SUCURSAL']?>-<?= $value['NRO_SUCURSAL']?>"><?= $value['NRO_SUCURSAL']?></th>
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
                                                    <td id="idConcepto"><?= $value['ID_CA']?> </td>
                                                    <td id="concepto"><strong><?= $value['CONCEPTO']?></strong> </td>
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
                            </div> <!-- Fin table-responsive -->
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
            <script src="js/ayuda.js"></script>

            <!-- Incluir Modal de Ayuda -->
            <?php include 'components/modalAyuda.php'; ?>

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
