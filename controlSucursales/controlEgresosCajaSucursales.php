<?php
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

        <!-- INCLUDE CSS FILES -->
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
        ?>
        
        </link>

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

                                <div style="margin-left:70px;width:250px">Desde: <input type="date" style="width:170px; height:40px" id='desde' name="desde" value="<?php echo $desde; ?>"></div>
                                
                                <div style="margin-right:20px">Hasta: <input type="date" style="width:150px; height:45px" id='hasta' name="hasta" value="<?php echo $hasta; ?>"></div>
                                
                                <div >Sucursal :  

                                    <select name="selectSucursal" id="selectSucursal" style="width:150px; height:45px">

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
                        
                        <div id="carruselImagenes" class="modal fade" tabindex="-1" aria-hidden="true" style="margin-left:10%;max-width:80%"></div>

            
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
                                    <th > RECIBIDO </th>
                                    <th > FACTURA </th>
                                    <th > CONTROL </th>

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
                                            <td id="td_myTable" <?= $fechaRecibido ?> >
                                                <?php 
                                                    if($gasto['RECIBIDO'] == 1){
                                                        echo "<i class='bi bi-check-circle-fill' style='color:green;font-size:30px'></i>";
                                                    }
                                                ?>
                                            </td>
                                            <td id="td_myTable" ><input type='checkbox' class='form-check-input' id="checkFactura" onchange='checkFactura(this)'  <?= ($gasto['FACTURA'] == 1) ? "checked=true disabled=true" : "" ?> ></td>
                                            <td id="td_myTable" ><input type='checkbox' class='form-check-input' id="checkControl" onchange='checkControl(this)' <?= ($gasto['CONTROL'] == 1) ? "checked=true disabled=true" : "" ?> ></td>
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
