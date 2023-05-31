<?php
    require_once "../../contabilidad/Class/alquiler.php";
    require_once "../../contabilidad/Class/sucursal.php";

    
    // $selectSucursal = isset($_GET['selectSucursal']) ?  $_GET['selectSucursal'] : '%';
        
    // $selectSucursal = explode("-",$selectSucursal);
    $alquiler = new Alquiler();
    $conceptos = $alquiler->traerConceptos();
    $sucursal = new Sucursal();

    $todosLosLocales= $sucursal->traerLocales();
 
    if(isset($_GET['fecha']) &&$_GET['fecha'] != "" ){
        $fecha = $_GET['fecha'];
    }else{
        $fecha = date("Y-m-d");
    }
    var_dump();
    
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Control Diario Caja Sucursales</title>
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
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Carga de Alquileres</h6></div>
                    <div class="card card-1">
                        
                        <div class="row" style="margin-left:50px">
                            <h3><strong><i class="bi bi-bank2" style="margin-right:20px;font-size:50px"></i>Alquileres</strong></h3>
                        </div>

                        <form action="#" method="get" style="margin-bottom:20px">
                            <div class="row" style="margin-top:10px">

                                <div class="col-4" style="margin-left:50px">
                                    Desde: <input type="date" style="width:150px; height:45px" id='desde' name="desde" value="<?php echo $desde; ?>">
                                    Hasta: <input type="date" style="width:150px; height:45px" id='hasta' name="hasta" value="<?php echo $hasta; ?>">
                                    <button class="btn btn-primary btn-submit" value="" style="height:45px;margin-left:2px;position:relative;margin-bottom:8px">filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                </div>
                          
                            
                                <div class="col" style="margin-left:80px">
                                    <h4>
                                        <button class="btn btn-success btn_exportar" id="btnControlar" style=" height:45px"  onclick="controlar()"> Procesar<i class="bi bi-check2-square"></i></button>
                                    </h4>
                                </div>

                            </div>
                        </form>

                        <div style="margin-left:50px;margin-bottom:10px"><strong><i class="bi bi-check-circle">Control Por Sucursal</i></strong></div>
                        
                        <table class="table " id="my-table">
                            <thead class="thead-dark">
                                <tr>
                                  
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                   
                                </tr>
                            </tbody>
                        </table>
                        <!-- <div style="margin-bottom:20px;margin-left:40%"><button class="btn btn-info" value="+" id="btnAdd" onclick="agregarCierre()">Agregar Cierre</button></div> -->
                        <table class="table table-striped table-bordered" id="" style="font-size :12px;" >
                            <thead class="thead-dark">
                                <tr id="tutorial">
                                    <th style="text-align:center;width:30px">ID</th>
                                    <th style="text-align:center;width:200px"  >CONCEPTOS </th>
                                    <?php 
                                        foreach ($todosLosLocales as $key => $value) {    
                                    ?>
                                            <th style="text-align:center;width:50px"><?= $value['NRO_SUCURSAL']?></th>

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
                                            <td style="position: sticky; top: 0; z-index: 10;text-align:center"><?= $value['ID_CA']?> </td>
                                            <td style="position: sticky; top: 0; z-index: 10;text-align:center"><?= $value['CONCEPTO']?> </td>
                                            <?php
                                                foreach ($todosLosLocales as $k => $val) {
                                                    if($value['carga_manual'] == 1){
                                                        echo "<td style='text-align:center'><input type='text' class='form-control' value='0'></td>";

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

        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

    </body>

</html>
<script src="js/jquery.table2excel.js"></script>

<script src="js/controlDiario.js"></script>
