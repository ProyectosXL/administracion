<?php
    require_once "Class/sucursal.php";


    $fecha_actual = date("Y-m-d");
    $estado = (isset($_GET['selectEstado']) && $_GET['selectEstado'] != "") ? $_GET['selectEstado'] : "%";

    
    if(isset($_GET['desde']) && $_GET['desde'] != "" ){
        $desde = $_GET['desde'];
    }else{
        $desde = date("Y-m-d",strtotime($fecha_actual."- 1 week"));
    }

    if(isset($_GET['hasta']) && $_GET['hasta'] != "" ){
        $hasta = $_GET['hasta'];
    }else{
        $hasta = date("Y-m-d",strtotime($fecha_actual."- 1 day"));
    }

    $sucursal = new Sucursal();


    $data = $sucursal->traerDatosControlRecepcion($desde, $hasta, $estado);
    $locales = $sucursal->traerLocales();


?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Control recepción efectivo de sucursales</title>
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
        ?>
    <style>

    #tablaControlRecepcion_wrapper{
        width: 95%;
        margin-left: 2rem;
    }

    .dataTables_length label{
    margin-left: -30rem;
    }

    .dataTables_filter label {
    margin-right: -22rem;
    }

    </style>
  
    </head>

    <body>

        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2">
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Control recepción efectivo de sucursales</h6></div>
                    <div class="card card-1">
                        
                        <div class="row" style="margin-left:40px">
                            <h4><strong><i class="bi bi-cash" style="margin-right:20px;font-size:40px"></i>Control recepción efectivo de sucursales</strong></h4>
                        </div>

                        <form action="#">

                            <div class="form-inline">

                                <div class="row" style="margin-top:10px; margin-bottom: 1rem; margin-left:35px">
                                    <div style="margin-left:10px">Desde : <input type="date" class="form-control form-control-sm" id="desde" name="desde" value="<?=  $desde ?>"></div>
                                    <div style="margin-left:30px">Hasta: <input type="date" class="form-control form-control-sm" id="hasta"  name="hasta" value="<?=  $hasta ?>"></div>
                                    <div style="margin-left:30px">Estado: 

                                        <select class="form-control form-control-sm" name="selectEstado" id="selectEstado">

                                            <option value="%" <?= ($estado == "%") ? "selected" : "" ?>>Todos</option>
                                            <option value="0" <?= ($estado == "0") ? "selected" : "" ?>>Pendiente</option>

                                        </select>
                                     
                                        <button class="btn btn-primary btn-submit" style="margin-top: -0.25em; margin-right: 5rem; height: 35px" id="btnFiltrarControlRecepcion">filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                </div>
                            </div>
                        </form>
            
                        <table class="table table-striped table-bordered table-sm table-hover" id="tablaControlRecepcion" cellspacing="0" data-page-length="100" style="height:100px; font-size:13px;">
                            <thead class="thead-dark" style="">
                                <tr>


                                    <th class="col-" > FECHA </th>
                                    <th class="col-" > NRO.SUCURSAL</th>
                                    <th class="col-" > DESC.SUCURSAL</th>
                                    <th class="col-" > TIPO COMP. </th>
                                    <th class="col-" > COMPROBANTE </th>
                                    <th class="col-" hidden> COD.CUENTA </th>
                                    <th style="text-align:center;width:20%" hidden> CUENTA </th>
                                    <th class="col-" > MONTO </th>
                                    <th class="col-" > DESPACHADO </th>
                                    <th class="col-" > PRECINTO </th>
                                    <th class="col-" > RECIBIDO </th>
                                    <th class="col-" > CONTROLADO </th>
                                    <th>OBSERVACIONES</th>
                                    <th class="col-">ACCIONES</th>


                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                                if($data != null){
                                    foreach ($data as $key => $gasto) {
                                        
                                        $sucursal = "";
                                        foreach ($locales as $key => $local) {
                                            if ($gasto['NRO_SUCURS'] == $local['NRO_SUCURSAL']) {
                                                $sucursal = $local['DESC_SUCURSAL'];
                                            }
                                        }
                                ?>
            
                                        <tr id="trA">

                                            <td><?= $gasto['FECHA']->format("d/m/Y") ?></td>
                                            <td><?= $gasto['NRO_SUCURS'] ?></td>
                                            <td><?= $sucursal ?></td>
                                            <td><?= $gasto['COD_COMP'] ?></td>
                                            <td  data-toggle="tooltip" data-placement="top" title="USUARIO: <?= $gasto['USUARIO']?>" ><?= $gasto['N_COMP'] ?></td>
                                            <td hidden><?= $gasto['COD_CTA'] ?></td>
                                            <td hidden><?= $gasto['DESC_CUENTA'] ?></td>                                         
                                            <td><?= number_format($gasto['MONTO'], 0, ',', '.') ?></td>    
                                            <?php 
                                                if ($gasto['DESPACHADO'] == 1) {
                                                    echo "<td style='text-align:center'>" . ($gasto['FECHA_DESP'])->format("d/m/Y H:i") . "</td>";
                                                } else {
                                                    echo "<td></td>";
                                                }   
                                            ?>
                                            <?php 
                                                if ($gasto['PRECINTO'] > 1) {
                                                    echo "<td style='text-align:center'>" . ($gasto['PRECINTO']) . "</td>";
                                                } else {
                                                    echo "<td></td>";
                                                }   
                                            ?>
                                            <?php 
                                                if($gasto['RECIBIDO'] == 1){
                                                   
                                                    echo "<td style='text-align:center'><i class='bi bi-check-circle-fill' style='color:green;font-size:20px;' ></i></td>";
                                                }else{
                                                    echo "<td style='text-align:center' ><input type='checkbox' class='form-check-input' style='width:20px;height:20px' onclick='marcarRecibido(this)'></td>";
                                                }   

                                            ?>    
                                              <?php 
                                      
                                                if($gasto['CTROL_TESORERIA'] == 1){
                                                   
                                                    echo "<td style='text-align:center'><i class='bi bi-check-circle-fill' style='color:green;font-size:20px;' ></i></td>";
                                                }else{
                                                    echo "<td style='text-align:center' ><input type='checkbox' class='form-check-input' style='width:20px;height:20px' onclick='marcarControlado(this)'></td>";
                                                }   
                                            ?>                                  
                                        <td>
                                            <?php
                                                if($gasto['OBSERVACIONES'] != NULL){
                                                    echo "<textarea style='width: 100%; height: 60px; resize: none;' disabled>".$gasto['OBSERVACIONES']."</textarea>";
                                                }else{
                                                    echo '<textarea style="width: 100%; height: 60px; resize: none;"></textarea>';
                                                }
                                            ?>

                                       
                                        </td>
                                        <td>
                                            <?php
                                                if($gasto['OBSERVACIONES'] == NULL){
                                                    echo "<button class='btn btn-primary' type='button' onclick='guardarObservaciones(this)'><i class='bi bi-save'></i></button>";
                                                }
                                            ?>
                                           
                                        </td>
                                        </tr>
                                        
                                <?php 
                                    }
                                }
                                ?>   
                
                            </tbody>
            
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- INCLUDES JS -->
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php';
        ?>

    </body>

</html>

<script src="js/controlRecepcion.js"></script>

<script>
  $('#tablaControlRecepcion').DataTable({
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

</script>
