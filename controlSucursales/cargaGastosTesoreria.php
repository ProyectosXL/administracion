<?php
    require_once "Class/sucursal.php";
    $selectSucursal = isset($_GET['selectSucursal']) ?  $_GET['selectSucursal'] : '2-UNICENTER';
        
    $selectSucursal = explode("-",$selectSucursal);

 
    if(isset($_GET['desde']) && $_GET['desde'] != "" ){
        $desde = $_GET['desde'];
    }else{
        $desde = null;
    }

    if(isset($_GET['hasta']) && $_GET['hasta'] != "" ){
        $hasta = $_GET['hasta'];
    }else{
        $hasta = null;
    }

    $sucursal = new Sucursal();
    $todosLosLocales= $sucursal->traerLocales();

    $data = null;

    if($desde != null && $hasta != null){
        
        $data = $sucursal->traerGastosCajaSucursales($desde,$hasta,$selectSucursal[0]);
    
    }

?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Control Egresos de Caja Sucursales</title>
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
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Control Egresos de Caja Sucursales</h6></div>
                    <div class="card card-1">
                        
                        <div class="row" style="margin-left:50px">
                            <h3><strong><i class="bi bi-cash-stack" style="margin-right:20px;font-size:50px"></i>Control Egresos de Caja - <?= $selectSucursal[1] ?></strong></h3>
                        </div>

                        <form action="#" method="get" style="margin-bottom:20px">

                            <div class="row" style="margin-top:10px">

                                <div  style="margin-left:70px;width:120px">Mes:  
                                    <select name="mes" id="mes"  style="width:70px; height:45px; text-align:center">
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>
                                        <option value="7">7</option>
                                        <option value="8">8</option>
                                        <option value="9">9</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>   
                                    </select>
                                </div>
                                
                                <div  style="margin-right:5px">Año: 
                                <select name="anio" id="anio" style="width:70px; height:45px; text-align:center">
                                    <option value="2023">2023</option>
                                </select>

                                </div>


                                <div>   
                                    <button class="btn btn-primary btn-submit" value="" style="height:45px;margin-left:5px;width:100px">filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                </div>

                            </div>

                        </form>
            
                        <table class="table table-striped table-bordered" id="myTable" style="width: 100%;" cellspacing="0" data-page-length="100">
                            <thead class="thead-dark" style="">
                                <tr style="text-align:center">

                                    <th > FECHA </th>
                                    <th > NRO.SUCURSAL</th>
                                    <th > TIPO COMP. </th>
                                    <th > COMPROBANTE </th>
                                    <th > COD.CUENTA </th>
                                    <th > CUENTA </th>
                                    <th > MONTO </th>
                                    <th > LEYENDA </th>
                                    <th > FACTURA </th>
                                    <th > CONTROL </th>

                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if($data != null){
                                    foreach ($data as $key => $gasto) {
                                ?>
            
                                        <tr>
                                            <td style='text-align:center' ><?= $gasto['FECHA']->format("Y-m-d") ?></td>
                                            <td style='text-align:center' ><?= $gasto['NRO_SUCURS'] ?></td>
                                            <td style='text-align:center' ><?= $gasto['COD_COMP'] ?></td>
                                            <td style='text-align:center'   data-toggle="tooltip" data-placement="top" title="USUARIO: <?= $gasto['USUARIO']?>" ><?= $gasto['N_COMP'] ?></td>
                                            <td style='text-align:center' ><?= $gasto['COD_CTA'] ?></td>
                                            <td style='text-align:center' ><?= $gasto['DESC_CUENTA'] ?></td>
                                            <td style='text-align:center' ><?= $gasto['MONTO'] ?></td>
                                            <td style='text-align:center' ><?= $gasto['LEYENDA'] ?></td>
                                            <td style='text-align:center' ><input type='checkbox' class='form-check-input' id="checkFactura" onchange='checkFatura(this)'  <?= ($gasto['FACTURA'] == 1) ? "checked=true disabled=true" : "" ?> ></td>
                                            <td style='text-align:center' ><input type='checkbox' class='form-check-input' id="checkControl" onchange='checkControl(this)' <?= ($gasto['CONTROL'] == 1) ? "checked=true disabled=true" : "" ?> ></td>
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
<script src="js/controlEgresos.js"></script>
