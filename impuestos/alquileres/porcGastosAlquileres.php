<?php
    require_once "Class/Alquiler.php";
    require_once "../../controlSucursales/Class/sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $conceptos = $alquiler->traerConceptosPorcentaje();
    $locales = $sucursal->traerLocales();
    
    $conceptoFiltrado = isset($_GET['conceptos']) ? $_GET['conceptos'] : "6-Porc. S/ventas brutas";

    $idConcepto = explode("-", $conceptoFiltrado)[0];
    $descConcepto = explode("-", $conceptoFiltrado)[1];
    $porcentajePorSucursal = $alquiler->traerPorcentajeSucursal($idConcepto);

    
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
    $imageOn = ($checkedValue === 'central') ? '../../assets/images/bandera_con_sol__55757_std.jpg' : '../../assets/images/UY.png';
    $imageOff = ($checkedValue === 'central') ? '../../assets/images/UY.png' : '../../assets/images/bandera_con_sol__55757_std.jpg';


    ?>

    <!DOCTYPE html>
    <html lang="en">

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
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestion de Conceptos</title>
        
        <!-- INCLUDES CSS -->
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
        ?>
        

        </link>

    </head>

    <body>

        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Gestion de Conceptos</h6></div>
                    <div class="card card-1">
                        <div id="username" hidden><?= $_SESSION['username'] ?></div>
                        <div class="row" style="margin-left:50px">
                            <a href="http://192.168.0.13:8000/" style="display:inline-block;margin-right: 20px">
                                <img src="../../image/home-button.png" style="width:50px;height:45px;margin-right:1rem;transition: transform 0.3s;" title="Menú" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                            </a>
                            <h3><i class="bi bi-archive-fill" style="margin-right: 20px; font-size: 50px"></i>Gestion de Conceptos - <?= $descConcepto ?></h3>
                            <div style="margin-left:50%;margin-top:10px">
                                <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;" onchange="cambiarEntorno(this)" id="checkEntorno" >

                            </div>
                        </div>
                        <form action="">
                        <div class="container-fluid" style="margin-left: 0px;">

                            <div class="row" style="width:1300px; margin-left: 60px">
                                <div clas="col" style="width:550px;">
                                    <div class="row">
                                        <div style="text-align:left;margin-right:5px">Conceptos:</div>
                                        <div style="margin-right:5px">
                                            <select name="conceptos" id="conceptos" style="height:40px">
                                                <?php 
                                                foreach ($conceptos as  $value) {

                                                ?>
                                                    <option value="<?= $value['ID_CA'] ?>-<?= $value['CONCEPTO'] ?>" <?php  if($idConcepto == $value['ID_CA']){ echo "selected"; }?>><?= $value['CONCEPTO'] ?></option>
                                                <?php } ;?>
                                            </select>
                                        </div>
                                        <div >
                                            <button class="btn btn-primary submit"style="height:40px">Filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4" style="width:550px;">
                                    <div class="row" >
                                        <div  style="text-align:left;margin-right:5px">Sucursal:</div>
                                        <div  style="margin-right:5px">
                                                <select name="locales" id="locales"  class="form-select form-select-lg mb-3"  style="height:40px">
                                                    <?php 
                                                    foreach ($locales as  $value) {
                                                    ?>
                                                        <option value="<?= $value['NRO_SUCURSAL'] ?>"><?= $value['DESC_SUCURSAL'] ?></option>
                                                    <?php } ;?>
                                            </select>
                                        </div>
                                        <div>
                                            <button class="btn btn-success" type="button" style="width:110px;height:40px" onclick="agregar()">Agregar <i class="bi bi-plus-square"></i></button>

                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        </form>
                        <div class="row" style="margin-left:65px;margin-top:20px">
                            <div class="table-responsive" id="tableIndex">
                                <table class="table table-hover table-condensed table-striped text-center" style="width: 30%;" cellspacing="0" data-page-length="100">
                                    <thead class="thead-dark" style="font-size: small;">
                                        <th scope="col" style="width: 6%">SUCURSAL</th>
                                        <th scope="col" style="width: 15%">NOMBRE</th>
                                        <th scope="col" style="width: 8%">PORCENTAJE</th>
                                        <th scope="col" style="width: 8%"></th>

                                    </thead>

                                    <tbody id="tableVb" style="font-size: small;">
                                        <?php foreach ($porcentajePorSucursal as $key => $value) { ?>
                                            <tr>
                                                <td hidden><?= $value['ID_PA'] ?></td>
                                                <td><?= $value['NRO_SUCURS'] ?></td>
                                                <td><?= $value['DESC_SUCURS'] ?></td>
                                                <td><input type="text" value="<?= $value['PORCENTAJE'] ?>" style="width:50px;height:30px;text-align:center" onchange="actualizarPorcentaje(this)"></td>
                                                <td><input type="button" class="btn btn-danger" value="X" onclick="eliminarPorcentaje(this)"></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
                require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php';
            ?>
        <script src="js/cargaDePorcentaje.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
        <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
    </body>

    </html>
    <script>    
        $(document).ready(function() {

            document.querySelector(".toggle").style.width="40px"
            document.querySelector(".toggle-on").style.fontSize="0"
            document.querySelector(".toggle-off").style.fontSize="0"
            document.querySelector('.toggle.btn.btn-primary').style.height = '38px'

        })

    </script>
