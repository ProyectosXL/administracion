<?php
    require_once "Class/sucursal.php";

 
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


    $todosLosLocales= $sucursal->traerLocales();
    $newArray = [];

    foreach ($controlMensual as $key => $control) {

        $newArray[$key]['FECHA'] = $control['FECHA']->format("Y-m-d") ;

        $count = 0;
        foreach ($control as $ke => $val) {
            if($count == 0){
                $count++;
                continue;
            }
            $newArray[$key]['nro_sucursal'][$ke] = $val;
        }

    }
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Control Diario Caja Sucursales</title>
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
        ?>
        
        </link>

    </head>

    <body>

        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Control Diario Sucursales</h6></div>
                    <div class="card card-1">
                        
                        <div class="row" style="margin-left:50px; margin-top:20px;">
                            <h3><strong><i class="bi bi-cash-stack" style="margin-right:20px;font-size:40px"></i>Control Mensual Caja Sucursales - Periodo : <?= $periodo ?></strong></h3>
                        </div>

                        <form class="form-inline" action="#" method="get" style="margin-bottom:20px; margin-top:10px;">
                            

                                <div style="margin-left:50px">Periodo : <input type="month" class="form-control" id='fecha' name="fecha" value="<?php echo $fecha; ?>"></div>
                                <button class="btn btn-primary btn-submit ml-3" value="">filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                            
                        </form>

                        <div style="margin-left:50px;margin-bottom:10px"><strong><i class="bi bi-check-circle"> Control Por Sucursal</i></strong></div>
                        
                        <table class="table table-bordered" id="tablaControlMensual" style="text-align: center;">
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
                            <tbody style="justify-content: center; align-items: center;">
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
                                                            <td><?= ($val != null) ? "<i class='bi bi-check-circle-fill' style='color: #28a745;'></i>" : "" ?></td>
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
                    </div>
                </div>
            </div>
        </div>
        
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php';
        ?>

    </body>

</html>
<script src="js/jquery.table2excel.js"></script>

<script src="js/controlDiario.js"></script>