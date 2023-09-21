<?php
    require_once "Class/Alquiler.php";
    require_once "../../controlSucursales/Class/sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $conceptos = $alquiler->traerConceptosPorcentaje();
    $locales = $sucursal->traerLocales(true);
    
    $conceptoFiltrado = isset($_GET['conceptos']) ? $_GET['conceptos'] : "6-Porc. S/ventas brutas";

    $idConcepto = explode("-", $conceptoFiltrado)[0];
    $descConcepto = explode("-", $conceptoFiltrado)[1];
    $porcentajePorSucursal = $alquiler->traerPorcentajeSucursal($idConcepto);

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <style>



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

                            <h3><i class="bi bi-archive-fill" style="margin-right: 20px; font-size: 50px"></i>Gestion de Conceptos - <?= $descConcepto ?></h3>
                        </div>
                        <form action="">
                        <div class="container-fluid" style="margin-left: 0px;">

                            <div class="row" style="width:1300px; margin-left: 60px">
                                <div clas="col" style="width:550px;">
                                    <div class="row" style="margin-top:3rem">
                                        <div style="text-align:left;margin-right:5px">Sucursal:</div>
                                        <div style="margin-right:5px">
                                            <select name="selectSucursal" id="selectSucursal" style="height:40px;width:12rem">
                                                 <?php 
                                                    foreach ($locales as  $value) {
                                                ?>
                                                        <option value="<?= $value['NRO_SUCURSAL'] ?>-<?= $value['DESC_SUCURSAL'] ?>"><?= $value['DESC_SUCURSAL'] ?></option>
                                                <?php 
                                                    } ;
                                                ?>
                                            </select>
                                        </div>
                             
                                    </div>
                                </div>
                                <div class="col-4" style="width:550px;">
                                    <div class="row" >
                  
                                    </div>
                                </div>

                            </div>
                        </div>
                        </form>
                        <div class="row" style="margin-left:65px;margin-top:30px;margin-bottom:1rem">
                            Vigencia
                        </div>
                        <div class="contenedor">
                            <div>
                                Desde: <input type="date" style="margin-left:5px" id="desde" value="<?= $desde ?>">
                                Hasta: <input type="date" style="margin-left:5px" id="hasta" value="<?= $hasta ?>">
                            </div>

                        </div>

                        <div class="row" style="margin-left:65px;margin-top:4%;width:370px">
                            <div class="col">
                                Valor llave
                            </div>
                            <div class="col">
                                <input type="text" id="valorLlave" onchange="parseNumber(this)">
                             </div>
                        </div>
                        <div class="row" style="margin-left:65px;margin-top:1%;width:370px">
                            <div class="col">
                                Comisiones
                            </div>
                            <div class="col">
                                <input type="text" id="comisiones"  onchange="parseNumber(this)">
                             </div>
                        </div>
                        <div class="row" style="margin-left:65px;margin-top:1%;width:370px">
                            <div class="col">
                                FPC lanzamiento
                            </div>
                            <div class="col">
                                <input type="text" id="lanzamiento"  onchange="parseNumber(this)">
                             </div>
                        </div>
                        <div class="row" style="margin-left:65px;margin-top:1%;width:500px">
                            <div class="col"></div>
                            <div class="col"><button class="btn btn-success" onclick="guardar()"style="margin-left:35px">Guardar</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
                require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php';
            ?>
        <script src="js/cargaContratoAlquileres.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    </body>

    </html>
    <script>    
 $("#selectSucursal").select2();
                                                    
    </script>
