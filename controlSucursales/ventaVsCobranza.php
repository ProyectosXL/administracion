<?php

include 'Class/ventas.php';

$ventas= new Ventas();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
    $checked = 'checked';
}else{
    $checked = '';
}
    
$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$dataOnValue = ($checkedValue === 'suc_uy') ? 'UY' : 'ARG';
$dataOffValue = ($checkedValue === 'suc_uy') ? 'ARG' : 'UY';
$imageOn = ($checkedValue === 'central') ? '../assets/images/bandera_con_sol__55757_std.jpg' : '../assets/images/UY.png';
$imageOff = ($checkedValue === 'central') ? '../assets/images/UY.png' : '../assets/images/bandera_con_sol__55757_std.jpg';


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas vs Cobranza </title>

    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>

    <style>
        #inputText {
            line-height: 1.2; /* Ajusta el valor para controlar el espacio entre líneas */
        }
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
        <div style="margin-left:1%">
            <div class="row">
                
                <h4 class="ml-3 mt-4"><i class="bi bi-card-checklist"></i>  Ventas vs Cobranza por comprobante <a style="color: #6c757d;"><?php if (isset($_GET['desde'])){ echo $desde ?> a <?php echo $hasta ;}?></a></h4>
            </div>
            <div class="row" style='margin-left:1%'>   
                    <div class="col mt-2">
                        <form action="">
                   
                            <label>Desde:</label>
                            <input type="date" class="" name="desde" value="<?= $desde ?>">
                    

                        
                            <label>Hasta:</label>
                            <input type="date" class="mr-3" name="hasta" value="<?= $hasta ?>" >
                  
                            <button type="submit" name="submit" class="btn btn-primary ventaVsCobranza " id="search">Buscar <i class="bi bi-search"></i></button>
                            <button type="submit" name="submit" class="btn btn-success ventaVsCobranza " id="btnExport">Exportar <i class="bi bi-file-earmark-excel"></i></button>
               
                            <label id="textBusqueda" class="ml-5">Busqueda rapida:</label>
                            <input type="text" id="textBox" placeholder="Sobre cualquier campo..." onkeyup="myFunction()" class=""></input>
                            <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;" onchange="cambiarEntorno(this)" id="checkEntorno" >

    
            
                            <div id="boxLoading"></div> 
                        </form>
                    </div>
            </div>
            
        </div>
    </div>
        

    <?php

    if (isset($_GET['desde'])) {
        $todasLasVentas = json_decode($ventas->traerComprobantes($desde,$hasta));

    ?>

        <table class="table table-striped table-bordered display mt-2" data-page-length="100">
            <thead class="thead-dark">
                    <th class="col-">FECHA</th>
                    <th class="col-">NRO. SUCURSAL</th>
                    <th style="width: 230px;">SUCURSAL</th>
                    <th class="col-">TIPO COMPROBANTE</th>
                    <th class="col-">NRO. COMPROBANTE</th>
                    <th class="col-">VENTA $</th>
                    <th class="col-">COBRANZA $</th>
                    <th class="col-" style="color: #28a745;">DIFERENCIA $</th>
                    <th class="col-" style="text-align:center">EXCLUIR</th>
                    <th class="col-" style="text-align:center">OBSERVACIONES</th>
            </thead>

            <tbody id="table">
                <?php
                foreach ($todasLasVentas as $valor => $key) {
                ?>
                    <tr>                        
                        <td><?= substr($key->FECHA->date, 0, 10)?></td>
                        <td><?= $key->NRO_SUCURS ?></td>
                        <td><?= $key->DESC_SUCURSAL ?></td>
                        <td><?= $key->T_COMP ?></td>
                        <td><?= $key->N_COMP ?></td>
                        <td><?= number_format($key->IMP_VENTA, 0, '', '.') ?></td>
                        <td><?= number_format($key->IMP_COBRANZA, 0, '', '.') ?></td>
                        <td><?= number_format($key->DIFERENCIA, 0, '', '.') ?></td>
                        <td style="text-align:center"><input type="checkbox" onchange="confirmarVentaVsCobranza(this)"></td>
                        <td style="text-align:center"><textarea name="" id="inputText" cols="30" rows="2" oninput="checkLineBreak(this)" ></textarea></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    <?php
    }
    ?>
    
    <script src="js/main.js" charset="utf-8"></script>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php';
    ?>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
        <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
</body>

<script>

    //Spinner listOrdenesActivas.php//
    var btn = document.querySelectorAll('.btn-primary');
    btn.forEach(el => {
        el.addEventListener("click", ()=>{$("#boxLoading").addClass("loading")});
    })

    $(document).ready(() => {
        $("#btnExport").click(function() {
            $("#table").table2excel({
                // exclude CSS class
                exclude: ".noE  xl",
                name: "Ventas por medio de pago",
                filename: "Ventas por medio de pago", //do not include extension
                fileext: ".xlsx" // file extension
            });
        });

        
    
            document.querySelector(".toggle").style.width="40px"
            document.querySelector(".toggle-on").style.fontSize="0"
            document.querySelector(".toggle-off").style.fontSize="0"
            document.querySelector('.toggle.btn.btn-primary').style.height = '38px'




    });
    var lineBreakAdded = false;

    function checkLineBreak(div) {
        
        var maxLength = 26; 
        var input = div

        let text = input.value;

        
        if (input.value.length >= maxLength && !lineBreakAdded) {
            input.value = input.value.replace(new RegExp('(.{' + maxLength + '})', 'g'), '$1\n');
            lineBreakAdded = true;
        }

        if (input.value.length < maxLength) {
            lineBreakAdded = false;
        }
    }
</script>

</html>