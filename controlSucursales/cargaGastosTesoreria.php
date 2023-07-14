<?php
    require_once "Class/sucursal.php";



$sucursal = new Sucursal();
$todosLosLocales= $sucursal->traerLocales();

$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m',  strtotime( date("Y-m-d")));
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y',  strtotime( date("Y-m-d")));

$fechaParaMostrar = $mes."/".$anio;

$currentYear = date('Y',  strtotime( date("Y-m-d")));

$yearDif = $currentYear - 2023;

$periodo = (int)$mes."-".$anio;

$periodoRerverse = $anio."-".$mes;

$primerDia = date('Y-m-01', strtotime($periodoRerverse));


$ultimoDia = date('Y-m-t', strtotime($primerDia));

$gastosTesoreria = $sucursal->traerGastosTesoreria($primerDia, $ultimoDia);

$keys = array_keys($gastosTesoreria[0]);

  

?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Carga Gastos Tesoreria</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        </link>
        <style>
            #myTable_filter input[type="search"] {
                margin-right:20px;
            }
        </style>

    </head>

    <body>

        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                <div class="wrapper wrapper--w880"><div style="color:white; text-align:center"><h6>Carga Gastos Tesoreria</h6></div>
                    <div class="card card-1">
                        <div id="periodo" hidden><?= $periodo ?></div>
                        <div class="row" style="margin-left:50px">
                            <h3><strong><i class="bi bi-bank2" style="margin-right:20px;font-size:50px"></i>Carga Gastos Tesoreria - <?= $fechaParaMostrar ?></strong></h3>
                        </div>

                        <form action="#" method="get" style="margin-bottom:20px">

                            <div class="row" style="margin-top:10px">

                                <div  style="margin-left:70px;width:120px">Mes:  
                                    <select name="mes" id="selectMes"  style="width:70px; height:45px; text-align:center">
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
                                
                                <div  style="margin-right:5px">Año: 
                                <select name="anio" id="selectAnio"  style="width:70px; height:45px; text-align:center">
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


                                <div>   
                                    <button class="btn btn-primary btn-submit" value="" style="height:45px;margin-left:5px;width:100px">filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                </div>

                            </div>

                        </form>
            
                        <table class="table table-striped table-bordered table-sm table-hover" id="tablaGastosTesoreria" style="width: 100%;height:100px" cellspacing="0" data-page-length="100">
                            <thead class="thead-dark" style="">
                                <tr style="text-align:center">

                                    <th style="text-align:center;width:5%" > NRO.SUCURSAL</th>
                                    <th  style="text-align:center;width:20%" > DESC_SUCURSAL </th>
                                    <?php 
                                        foreach ($keys as $key => $value) {
                                            if($key > 1)
                                            echo "<th style='text-align:center;width:10%'>".$value."</th>";
                                        }
                                    ?>
                                    <th style="text-align:center;width:5%">CARGADO</th>
                                  
                           

                                </tr>
                            </thead>
                            <tbody>
                            
                                <?php 
                                    foreach ($todosLosLocales as $key => $value) {
                                            echo "<tr>";
                                            echo "<td style='text-align:center'>".$value['NRO_SUCURSAL']."</td>";
                                            echo "<td style='text-align:center'>".$value['DESC_SUCURSAL']."</td>";

                                            foreach ($keys as $x => $k) {
                                                if($x > 1){
                                                    $total = 0;
                                                    foreach ($gastosTesoreria as $key => $gasto) {
                                                        if($gasto['NRO_SUCURSAL'] == $value['NRO_SUCURSAL']){
                                                            $total += $gasto[$k];
                                                        }
                                                    }
                                                    echo "<td style='text-align:center'>".$total."</td>";
                                                }
                            
                                            }

                                            echo "<td style='text-align:center;'><input type='checkbox' onchange='checkControl(this)' id='checkControl'></td>";
                                            echo "</tr>";
                                            
                                    }
                                      

                                    
                                ?>
                                <td></td>
               
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

        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

    </body>

</html>

<script src="js/gastosTesoreria.js"></script>

