<?php
    session_start();
    require_once "Class/sucursal.php";
    $selectSucursal = isset($_GET['selectSucursal']) ?  $_GET['selectSucursal'] : '2-UNICENTER';
        
    $selectSucursal = explode("-",$selectSucursal);

    $fecha_actual = date("Y-m-d");
 
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
    $todosLosLocales= $sucursal->traerLocales(true);

    $data = null;

    if($desde != null && $hasta != null){
        
        $data = $sucursal->traerGastosCajaSucursales($desde,$hasta,$selectSucursal[0],true);
    
    }

    // --- Lógica de entorno y banderas ---
    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $checked = ($checkedValue == 'central') ? 'checked' : '';
    $dataOnValue = ($checkedValue === 'suc_uy') ? 'UY' : 'ARG';
    $dataOffValue = ($checkedValue === 'suc_uy') ? 'ARG' : 'UY';
    $imageOn = ($checkedValue === 'central') ? '../contabilidad/images/bandera_con_sol__55757_std.jpg' : '../contabilidad/images/UY.png';
    $imageOff = ($checkedValue === 'central') ? '../contabilidad/images/UY.png' : '../contabilidad/images/bandera_con_sol__55757_std.jpg';

?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Carga Factura Sucursales</title>

        <!-- INCLUDE CSS FILES -->
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
        ?>
        
        </link>

        <!-- Bootstrap Toggle CSS -->
        <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
        <!-- Bootstrap Toggle JS -->
        <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>

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

    </head>

    <body>

        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Carga Factura Sucursales</h6></div>
                    <div class="card card-1">
                        
                        <div class="row" style="margin-left:50px; margin-top:20px">
                            <input type="checkbox" <?= $checked ?> data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;margin-top:10px; margin-right:20px" onchange="cambiarEntorno(this)" id="checkEntorno" >
                            <h3><strong><i class="bi bi-cash-stack" style="margin-right:20px;font-size:40px;"></i>Carga Factura Sucursales- <?= $selectSucursal[1] ?></strong></h3>
                        </div>

                        <form class="form-inline" action="#" method="get" style="margin-bottom:20px">

                            <div class="row" style="margin-top:10px">

                                <div style="margin-left:90px">Desde : <input type="date" class="form-control" id="desde" name="desde" value="<?=  $desde ?>"></div>
                                <div style="margin-left:30px">Hasta : <input type="date" class="form-control" id="hasta"  name="hasta" value="<?=  $hasta ?>"></div>
                                
                                <div style="margin-left:30px">Sucursal :  

                                    <select name="selectSucursal" id="selectSucursal" class="form-control">

                                    <?php 
                                        foreach ($todosLosLocales as $key => $value) {
                                    ?>
                                            <option value="<?php echo ($value['NRO_SUCURSAL']."-".$value['DESC_SUCURSAL']   ) ?>" <?php if ($selectSucursal[0] == $value['NRO_SUCURSAL']){ echo "selected";} ?> ><?= $value['DESC_SUCURSAL'] ?></option>

                                    <?php 
                                        } 
                                    ?>
            
                                    </select>

                                </div>

                                <div>   
                                    <button class="btn btn-primary btn-submit" id="btnSubmit" value="" >filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                </div>

                            </div>

                        </form>
                        
                        <div id="carruselImagenes" class="modal fade" tabindex="-1" aria-hidden="true" style="margin-left:10%;max-width:70%"></div>

            
                        <table class="table table-striped table-bordered" id="myTable" cellspacing="0" data-page-length="100">
                            <thead class="thead-dark" >
                                <tr style="text-align:center">

                                    <th > FECHA </th>
                                    <th > NRO.SUCURSAL</th>
                                    <th > TIPO COMP. </th>
                                    <th > COMPROBANTE </th>
                                    <th > COD.CUENTA </th>
                                    <th > CUENTA </th>
                                    <th > MONTO </th>
                                    <th > LEYENDA </th>
                                    <th > VER </th>
                                    <th > CONTABILIZADA </th>

                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if($data != null){
                                    foreach ($data as $key => $gasto) {
                     
                                        $fecha = ( $gasto['FECHA_RECIBIDO'] != null ) ? $gasto['FECHA_RECIBIDO']->format("Y-m-d") : "";
                                        $fechaRecibido = "";
                                        if($gasto['RECIBIDO'] == 1){
                                            $fechaRecibido = "data-toggle='tooltip' data-placement='top' title='FECHA RECIBIDO: $fecha'";
                                        }
                                ?>
            
                                        <tr>
                                            <td id="td_myTable" ><?= $gasto['FECHA']->format("Y-m-d") ?></td>
                                            <td id="td_myTable" ><?= $gasto['NRO_SUCURS'] ?></td>
                                            <td id="td_myTable" ><?= $gasto['COD_COMP'] ?></td>
                                            <td id="td_myTable"   data-toggle="tooltip" data-placement="top" title="USUARIO: <?= $gasto['USUARIO']?>" ><?= $gasto['N_COMP'] ?></td>
                                            <td id="td_myTable" ><?= $gasto['COD_CTA'] ?></td>
                                            <td id="td_myTable" ><?= $gasto['DESC_CUENTA'] ?></td>
                                            <?php 
                                                if($gasto['MONTO'] < 0){

                                                    $monto = $gasto['MONTO'] * -1;
                                                    $valor = "- $". number_format($monto, 0, '.','.');
                                                    echo "<td style='text-align:center'>$valor</td>";

                                                }else{

                                                    $valor = "$". number_format($gasto['MONTO'], 0, '.','.');
                                                    echo "<td style='text-align:center'>$valor</td>";

                                                }
                                            ?>
                                            <td id="td_myTable" ><?= $gasto['LEYENDA'] ?></td>

                                            <td id="td_myTable" >
                                                <?php if($gasto['guardado'] == 1) { ?>

                                                <button class="btn btn-warning" style="margin-left:5px; padding:.3rem .5rem;"  onclick="mostrarImagen(this)">
                                                    <i class="bi bi-eye" style="color:white"></i>
                                                </button>

                                                <?php } ?>
                                            </td>
                                          
                                            <td id="td_myTable" ><input type='checkbox' class='form-check-input' id="checkFactura" onchange='checkContabilizar(this)'  <?= ($gasto['CONTABILIZADA'] == 1) ? "checked=true " : "" ?> ></td>
                                         
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

    </body>

</html>

<!-- INCLUDE JS FILES -->

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

<link rel="stylesheet" type="text/css" href="../comercioExterior/assets/select2/select2.min.css">

<script src="js/cargaFacturaSucursales.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>

    $("#selectSucursal").select2();
    document.querySelector(".select2-selection.select2-selection--single").style.height = "44px"
    document.querySelector("#select2-selectSucursal-container").style.marginTop = "8px"


</script>
