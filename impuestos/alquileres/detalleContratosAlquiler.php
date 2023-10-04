<?php 

require_once "Class/Alquiler.php";
$alquiler = new Alquiler();
$contratos = $alquiler->traerContratoAlquiler();


?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detalle contrato Alquiler</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

        <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css"> -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        </link>
        <style>
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
            
        </style>
    </head>

    <body>
        
      
        <input type="file" name="archivos[]" id="archivos" multiple accept=".pdf, .jpg, .png" style="display: none;" />
        <div id="carruselImagenes" class="modal fade" tabindex="-1" aria-hidden="true" style="margin-left:10%;max-width:80%"></div>
        <div id="nroSucursal" hidden><?= $nroSucurs; ?></div>

        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                <div class="wrapper wrapper--w880"><div style="color:white; text-align:center"><h6>Detalle contratos de alquiler</h6></div>
                    <div class="card card-1">
                        <div id="periodo" hidden><?= $periodo ?></div>
                        <div class="row" style="margin-left:50px; margin-top:30px">
                            <h3><strong><i class="bi bi-key" style="margin-right:20px;font-size:40px"></i>Detalle contratos de alquiler </strong></h3>
                        </div>
                        <form action="#">

                            <div class="form-inline" style="margin-bottom:20px">

                                <div class="row" style="margin-top:10px">

                                    <div style="margin-left:60px">Estado: 
                                        <select name="estado" id="estado" class="form-control form-control-l">

                                            <option value="%">VIGENTE</option>
                                            <option value="1">ANTERIOR</option>                                      
                                    
                                        </select>
                                    </div>

                                    <button class="btn btn-primary btn-submit ml-2" style="margin-top: -0.15em; margin-right: 5rem; height: 38px">Filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>


                                </div>

                            </div>

                        </form>

                        <table class="table table-striped table-bordered table-sm table-hover dataTable no-footer" id="tablaAlquileres" >
                            <thead class="thead-dark" style="">
                                <tr>
                                    <th style="text-align:center;width:10%" >NRO. SUCURSAL</th>
                                    <th style="text-align:center;width:10%" >SUCURSAL</th>
                                    <th style="text-align:center;width:5%" >DESDE</th>
                                    <th style="text-align:center;width:5%">HASTA</th>
                                    <th style="text-align:center;width:20%" >VALOR LLAVE</th>
                                    <th style="text-align:center;width:20%" >COMISIONES</th>
                                    <th style="text-align:center;width:20%" >FPC LANZAMIENTO</th>
                                    <th style="text-align:center;width:10%" >MESES</th>
                                    <th style="text-align:center;width:10%" ></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    foreach ($contratos as $key => $contrato) {
                                        
                                    $vigDesde = new DateTime($contrato['VIG_DESDE']->format("Y-m-d")); // Primera fecha
                                    $vigHasta = new DateTime($contrato['VIG_HASTA']->format("Y-m-d")); // Segunda fecha
                                    $hoy = new DateTime(date("Y-m-d")); // Segunda fecha
                                     
                            
                                    $diferenciaDeFechas = $vigDesde->diff($vigHasta);
                                    $mesesDiferencia = $diferenciaDeFechas->y * 12 + $diferenciaDeFechas->m;
                                    if($mesesDiferencia == 0){
                                        $mesesDiferencia = 1;
                                    }
                            
                                ?>
                                <tr>
                                    <td style="text-align:center"><?= $contrato['NRO_SUCURS'] ?></td>
                                    <td style="text-align:center"><?= $contrato['DESC_SUCURS'] ?></td>
                                    <td style="text-align:center"><?= $contrato['VIG_DESDE']->format("Y-m-d") ?></td>
                                    <td style="text-align:center"><?= $contrato['VIG_HASTA']->format("Y-m-d") ?></td>
                                    <td style="text-align:center">$<?= number_format($contrato['IMPORTE'], 0, ',', '.') ?></td>
                                    <td style="text-align:center">$<?= number_format($contrato['IMPORTE_2'], 0, ',', '.') ?></td>
                                    <td style="text-align:center">$<?= number_format($contrato['IMPORTE_3'], 0, ',', '.') ?></td>
                                    <td style="text-align:center"><?= $mesesDiferencia?></td>
                                    <?php 
                                    
                                    if ($hoy > $vigHasta) {
                                        // Ya has superado la fecha de vencimiento
                                        $dias = $vigHasta->diff($hoy)->days;
                                    ?>

                                    <td ><button class="btn btn-danger" data-toggle="tooltip" data-placement="top" title='El contrato se encuentra <?= $dias ?> dias vencido'><i class="bi bi-exclamation-circle-fill" style="color:white"></i></button></td>
                                    <?php   
                                    } else {
                               
                                        $dias = $hoy->diff($vigHasta)->days;

                                        if($dias <= 90){
                                    ?>
                                    <td ><button class="btn btn-warning" data-toggle="tooltip" data-placement="top" title='Restan <?= $dias ?> dias para vencimiento del contrato'><i class="bi bi-exclamation-circle-fill" style="color:white"></i></button></td>
                                    <?php   
                                        }else{
                                            echo "<td></td>";
                                        }
                                    }

                                    ?>

                                </tr>


                                <?php 
                                        
                                    }
                                ?>
                            
                            </tbody>
            
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
        <link rel="stylesheet" type="text/css" href="assets/select2/select2.min.css">
        <script src="assets/select2/select2.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script> -->
        <!-- <script src="js/controlFallas.js"></script> -->
    </body>

</html>
<script>
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip()
    })
  $('#tablaAlquileres').DataTable({
        "bLengthChange": true,
        "lengthMenu": [ [100], [100] ],
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
<!-- <script src="js/gastosTesoreria.js"></script> -->

