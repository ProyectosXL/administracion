<?php
    require_once "Class/sucursal.php";
    
    $selectSucursal = isset($_GET['selectSucursal']) ?  $_GET['selectSucursal'] : '2-UNICENTER';
        
    $selectSucursal = explode("-",$selectSucursal);

 
    if(isset($_GET['fecha']) &&$_GET['fecha'] != "" ){
        $fecha = $_GET['fecha'];
    }else{
        $fecha = date("Y-m");
    }
    $periodo = str_replace("0","",substr($fecha, 5, 2)).'-'.substr($fecha, 0, 4);
    $fechaComoEntero = strtotime($fecha);
    $mes = date("m", $fechaComoEntero);
    $anio = date("Y", $fechaComoEntero);
    $first_day = mktime(0,0,0, "$mes", "01", "$anio"); //Calcula el primer día del mes que sería el 02
    
    $desde = $anio."-".$mes."-01";
    $hasta = $anio."-".$mes."-".date('t', $first_day);

    
    
    
    $sucursal = new Sucursal();
    $controlMensual = $sucursal->traerControlMensual($desde,$hasta);

    // $todosLosMediosDePago = $sucursal->traerTodosLosMediosDePago();
    $todosLosLocales= $sucursal->traerLocales();
    $newArray = [];

    foreach ($controlMensual as $key => $control) {
        // var_dump($control['FECHA']->format("Y-m-d"));
        $newArray[$key]['FECHA'] = $control['FECHA']->format("Y-m-d") ;
        // var_dump($key);
        // die();
        $count = 0;
        foreach ($control as $ke => $val) {
            if($count == 0){
                $count++;
                continue;
            }
            $newArray[$key]['nro_sucursal'][$ke] = $val;
            $count++;
            // var_dump($ke);
            // foreach ($todosLosLocales as $k => $value) {
            //     if($value['NRO_SUCURSAL'] == $ke){

            //     }
                
                
            // }
            # code...
        }
        # code...
    }
    // var_dump($newArray);
    // die();

    // $todosLosImportes= $sucursal->traerImportesTotales($selectSucursal[0],$fecha);

    // $verificados = $sucursal->traerVerificados($fecha);

    // foreach ($todosLosImportes as $key => $value) {
        
    //     foreach ($todosLosMediosDePago as &$importe) {

    //         if($value['MEDIO_PAGO'] == str_replace("_"," ",$importe['MEDIO_PAGO'])){
    

    //             $importe['IMPORTE'] = $value['IMPORTE_$_SISTEMA'];
    //             $importe['ID_VENTA'] = $value['ID'];
    //             $importe['IMPORTE_FISICO'] = $value['IMPORTE_$_FISICO'];
    //             $importe['OBSERVACIONES'] = $value['OBSERVACIONES'];
  
          
    //         }
 
    //     }
    // }
    // $localVerificado = 0;
    // foreach ($verificados as $verificado) {
    //     if($verificado['nro_sucursal'] == $selectSucursal[0] && $verificado['STATUS'] == 1){
    //         $localVerificado = 1;
    //     }
    // }
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
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Control Diario Sucursales</h6></div>
                    <div class="card card-1">
                        
                        <div class="row" style="margin-left:50px">
                            <h3><strong><i class="bi bi-cash-stack" style="margin-right:20px;font-size:50px"></i>Control Mensual Caja Sucursales - Periodo : <?= $periodo ?></strong></h3>
                        </div>

                        <form action="#" method="get" style="margin-bottom:20px">
                            <div class="row" style="margin-top:10px">

                                <div class="col-2" style="margin-left:50px">Fecha: <input type="month" style="width:150px; height:45px" id='fecha' name="fecha" value="<?php echo $fecha; ?>"></div>
                                <div class="col-3">Sucursal :  

                                    <select name="selectSucursal" id="selectSucursal" style="width:150px; height:45px">

                                    <?php 
                                        foreach ($todosLosLocales as $key => $value) {
                                    ?>
                                            <option value="<?php echo ($value['NRO_SUCURSAL']."-".$value['DESC_SUCURSAL']   ) ?>" <?php if ($selectSucursal[0] == $value['NRO_SUCURSAL']){ echo "selected";} ?> ><?= $value['DESC_SUCURSAL'] ?></option>

                                    <?php 
                                        } 
                                    ?>
            
                                    </select>

                                    <button class="btn btn-primary btn-submit" value="" style="height:45px;margin-left:2px;position:relative;margin-bottom:8px">filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                </div>
                            
                                <div class="col" style="margin-left:80px">
                                    <h4>
                                        <button class="btn btn-primary" id="btnGuardar" style=" height:45px" onclick="guardar()"><i class="fa fa-file-excel-o"></i> Guardar<i class="bi bi-file-earmark-excel"></i></button>
                                        <button class="btn btn-success btn_exportar" id="btnControlar" style=" height:45px"  onclick="controlar()"> Controlar<i class="bi bi-check2-square"></i></button>
                                    </h4>
                                </div>

                            </div>
                        </form>

                        <div style="margin-left:50px;margin-bottom:10px"><strong><i class="bi bi-check-circle">Control Por Sucursal</i></strong></div>
                        
                        <table class="table " id="my-table">
                            <thead class="thead-dark">
                                <tr> 
                                    <th>FECHA</th>   
                                    <?php 
                                        foreach ($todosLosLocales as $key => $value) {
                                    ?>
                                        <th><?= $value['NRO_SUCURSAL'] ?></th>
                                    <?php                                            
                                        }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    foreach ($newArray as $key => $value) {
                         
                                ?>
                                        <tr>
                                            <td><?= $value['FECHA'] ?></td>
                                            <?php 
                                                foreach ($value['nro_sucursal'] as $k => $val) {
                                                    foreach ($todosLosLocales as $x => $v) {
                                                        if( $v['NRO_SUCURSAL'] == $k){
                                            ?>
                                                            <td><?= ($val != null) ? "<i class='bi bi-check2-square'></i>" : "null" ?></td>
                                            <?php
                                                        }
                                                    }
                                                }
                                            ?>
                                        </tr>
                                <?php 
                                    }
                                ?>                               

                            </tbody>
                        </table>
                        <!-- <table class="table table-striped table-bordered" id="myTable" style="width: 80%;" cellspacing="0" data-page-length="100">
                            <thead class="thead-dark">
                                <tr id="tutorial">
                                    <th style="position: sticky; top: 0; z-index: 10;text-align:center">MEDIO DE PAGO</th>
                                    <th style="position: sticky; top: 0; z-index: 10;text-align:center"  >$ SISTEMA </th>
                                    <th style="position: sticky; top: 0; z-index: 10;text-align:center"  id="ultimoCierre">$ CONTROL </th>

                                  
                                    <th style="position: sticky; top: 0; z-index: 10;text-align:center">DIFERENCIA</th>
                                    <th style="position: sticky; top: 0; z-index: 10;text-align:center">OBSERVACIONES</th>

                                </tr>
                            </thead>
                            <tbody>
       
                                       
                                        
                            </tbody>
            
                        </table> -->
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