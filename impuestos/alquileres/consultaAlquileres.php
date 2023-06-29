<?php
    require_once "Controller/AlquilerController.php";
    require_once "datosConsultaAlquileres.php";
?>
<!DOCTYPE html>
    <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Consulta Evolucion Alquileres</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
            <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
            <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

            <!-- Bootstrap Icons -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

            </link>

        </head>

        <body>

            <div class="alert alert-secondary">
                <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                    <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Consulta Evolucion Alquileres</h6></div>
                        <div class="card card-1">

                            <div class="row" style="margin-left:50px">
                                <h3><strong><i class="bi bi-bank2" style="margin-right:20px;font-size:50px"></i>Alquileres - <?= $fechaParaMostrar ?></strong></h3>
                            </div>

                            <form action="#" method="get" style="margin-bottom:20px">
                                <div class="row" style="margin-top:10px">
                                    <div class="col-4" style="margin-left:50px">
                                    <div hidden ><input type="text" id="userName" name="userName" value="<?= $userName ?>"></div>
                                    </div>

                                 </div>
                            </form>
                            <div class="" >
                                <table class="table table-striped table-bordered table-sm table-hover" id="tablaAlquileres" style="font-size :12px;">
                                   
                                    <thead class="thead-dark">
                                        <tr>
                                            <th style="text-align:center;width:30px">ID</th>
                                            <th style="text-align:center;width:100px"  >CONCEPTOS </th>
                                            <?php
                                            foreach ($meses as $mes) {
                                            ?>
                                                <th id="meses"><?= $mes ?></th>
                                            <?php 
                                            }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                        foreach ($conceptos as $x => $concepto) {
                                    ?>
                                            <tr data-toggle="collapse" data-target="#row-<?=$x?>" class="accordion-toggle" onclick="mostrarDetalle(this)">
                                                    <td style="text-align:center" id="idConcepto"><?= $concepto['ID_CA']?> </td>
                                                    <td style="text-align:center" id="concepto"><?= $concepto['CONCEPTO']?> </td>
                                    
                                                    <?php 
                                                        $conteoMeses = 0;
                                                        foreach ($traerArrayPeriodo as $key => $value) {
                                                      
                                                           

                                                            $total = $data[$concepto['ID_CA']][$value]['TOTAL'];           
                                                    ?>
                                                                <td id="td-<?=$concepto['ID_CA']?>-<?=$conteoMeses?>">$<?php echo number_format($total, 0, ',', '.') ?></td>

                                                    <?php 
                                                        $conteoMeses ++;
                                                        }
                                                    ?>
                                            </tr>

                                            <?php 
                                                foreach ($todosLosLocales as  $local) {     
                                            ?>
                                                    <tr  id="trHidden<?=$x?>" hidden >

                                                        <td  class="hiddenRow">
                                                            <div class="collapse" id="row-<?=$x?>">
                                                                <div>
                                                                    
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <td  class="hiddenRow">
                                                            <div class="collapse" id="row-<?=$x?>">
                                                                <div>
                                                                    <?= $local['DESC_SUCURSAL'] ?>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <?php 
                        
                                                            foreach ($traerArrayPeriodo as $periodo) {

                                                                foreach ($dataDetalladaPorSucursal as  $detalle) {
                                                    
                                                                    if($detalle['ID_CA'] == $concepto['ID_CA'] && $detalle['NRO_SUCURS'] == $local['NRO_SUCURSAL'] && $detalle['PERIODO'] == $periodo){
                                                        ?>
                                                                        <td  class="hiddenRow">
                                                                            <div class="collapse" id="row-<?=$x?>">
                                                                                <div>
                                                                                    $<?php  echo number_format((int)$detalle['IMPORTE'], 0, ',', '.')  ?>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                        <?php
                                                                    }
                                                                }    
                                                            }
                                                     
                                                         ?>
                                                    </tr>
                                            <?php
                                                }
                                            ?>
                                    <?php 
                                        }
                                    ?>   
                                    <tr>
                                        <td></td>
                                        <td>TOTAL</td>
                                        <?php
                                            $contador = 0;
                                            foreach ($meses as  $mes) {
                                                
                                             
                                        ?>
                                            <td id="total-<?= $contador ?>"></td>
                                        <?php 
                                                $contador ++;
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
            <script src="js/consultaAlquileres.js"></script>

        </body>

    </html>

    <script>
        document.ready = totalizar();
    </script>

