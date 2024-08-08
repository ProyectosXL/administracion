<?php
    require_once "Class/sucursal.php";
    $selectSucursal = isset($_GET['selectSucursal']) ?  $_GET['selectSucursal'] : '2-UNICENTER';
    $estado = isset($_GET['selectEstado']) ?  $_GET['selectEstado'] : '1';
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

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
  
    if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
        $checked = 'checked';
    }else{
        $checked = '';
    }
        
    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $dataOnValue = ($checkedValue === 'uy') ? 'UY' : 'ARG';
    $dataOffValue = ($checkedValue === 'uy') ? 'ARG' : 'UY';
    $imageOn = ($checkedValue === 'central') ? '../assets/images/bandera_con_sol__55757_std.jpg' : '../assets/images/UY.png';
    $imageOff = ($checkedValue === 'central') ? '../assets/images/UY.png' : '../assets/images/bandera_con_sol__55757_std.jpg';
    

    $sucursal = new Sucursal();
    $todosLosLocales= $sucursal->traerLocales(true);

    $data = null;

 
        
    $data = $sucursal->traerGastosAutorizarSucursales($desde, $hasta, $selectSucursal[0], $estado);
    


?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Autorizar gasto caja sucursales</title>

        <!-- INCLUDE CSS FILES -->
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
        ?>
        
        </link>
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
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Autorización Gastos de Sucursales</h6></div>
                    <div class="card card-1">
                        
                        <div class="row" style="margin-left:50px">
                            <h3><strong><i class="bi bi-cash-stack" style="margin-right:20px;font-size:40px"></i>Autorización Gastos de Sucursales</strong></h3>
                        </div>

                        <form class="form-inline" action="#" method="get" style="margin-bottom:20px">

                            <div class="row" style="margin-top:10px;width:100%">

                                <div style="margin-left:90px">Desde : <input type="date" class="form-control" id="desde" name="desde" value="<?=  $desde ?>"></div>
                                <div style="margin-left:30px">Hasta : <input type="date" class="form-control" id="hasta"  name="hasta" value="<?=  $hasta ?>"></div>
                                
                                <div style="margin-left:30px">Sucursal :  

                                    <select name="selectSucursal" id="selectSucursal" class="form-control">
                                        <option value="%">TODOS</option>
                                    <?php 
                                        foreach ($todosLosLocales as $key => $value) {
                                    ?>
                                            <option value="<?php echo ($value['NRO_SUCURSAL']."-".$value['DESC_SUCURSAL']   ) ?>" <?php if ($selectSucursal[0] == $value['NRO_SUCURSAL']){ echo "selected";} ?> ><?= $value['DESC_SUCURSAL'] ?></option>

                                    <?php 
                                        } 
                                    ?>
            
                                    </select>

                                </div>
                                <div style="margin-left:5%">
                                    Estado
                                    <select name="selectEstado" id="selectEstado" class="form-control">
                                        
                                        <option value="0"  <?= (isset($_GET['selectEstado']) && $_GET['selectEstado'] == '0') ? "selected" : '' ?> >TODOS</option>
                                        <option value="1"   <?= (!isset($_GET['selectEstado']) || $_GET['selectEstado'] == '1') ? "selected" : '' ?>>PENDIENTES</option>
                                        <option value="2"   <?= (isset($_GET['selectEstado']) && $_GET['selectEstado'] == '2') ? "selected" : '' ?>>AUTORIZADOS</option>

                                    </select>
                                </div>

                                <div>   
                                    <button class="btn btn-primary btn-submit ml-3" id="btnSubmit" style="margin-right:200px" value="" >filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                    <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;" onchange="cambiarEntorno(this)" id="checkEntorno" >

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
                                    <th > AUTORIZAR </th>
                              

                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if($data != null){
                                    foreach ($data as $key => $gasto) {
                             
                                ?>
            
                                        <tr>
                                            <td id="td_myTable" style="text-align:center" ><?= $gasto['FECHA']->format("Y-m-d") ?></td>
                                            <td id="td_myTable" style="text-align:center" ><?= $gasto['NRO_SUCURS'] ?></td>
                                            <td id="td_myTable" style="text-align:center" ><?= $gasto['COD_COMP'] ?></td>
                                            <td id="td_myTable" style="text-align:center"   data-toggle="tooltip" data-placement="top" title="USUARIO: <?= $gasto['USUARIO']?>" ><?= $gasto['N_COMP'] ?></td>
                                            <td id="td_myTable" style="text-align:center" ><?= $gasto['COD_CTA'] ?></td>
                                            <td id="td_myTable" style="text-align:center" ><?= $gasto['DESC_CUENTA'] ?></td>
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
                                            <td id="td_myTable" style="text-align:center" ><?= $gasto['LEYENDA'] ?></td>

                                            <td id="td_myTable" style="text-align:center" >
                                                <?php if($gasto['guardado'] == 1) { ?>

                                                <button class="btn btn-warning" style="margin-left:5px; padding:.3rem .5rem;"  onclick="mostrarImagen(this)">
                                                    <i class="bi bi-eye" style="color:white"></i>
                                                </button>

                                                <?php } ?>
                                            </td>
                                            <?php
                                      
                                            if($gasto['AUTORIZADO'] != 1) { ?>
                                                <td style="text-align:center"><input type="checkbox" style="width:20px;height:20px" onclick="autorizar(this)"></td>
                                            <?php 
                                            }else{
                                                ?>
                                                <td  data-toggle="tooltip" data-placement="top" title="AUTORIZADO:<?= $gasto['FECHA_AUTORIZADO']->format("Y-m-d") ?>"><i class="bi bi-check-circle-fill" style="font-size:30px;color:#4caf50;" ></i></td>
                                                <?php
                                            }
                                            ?>
                                           
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
<?php
    require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php';
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
<script>
    $("#selectSucursal").select2();
    document.querySelector(".select2-selection.select2-selection--single").style.height = "44px"
    document.querySelector("#select2-selectSucursal-container").style.marginTop = "8px"

  $(document).ready( function () {
    
    document.querySelector(".toggle").style.width="40px"
    document.querySelector(".toggle-on").style.fontSize="0"
    document.querySelector(".toggle-off").style.fontSize="0"
    document.querySelector('.toggle.btn.btn-primary').style.height = '38px'


    })
</script>
