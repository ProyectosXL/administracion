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


   
    // var_dump($conceptos);

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestion de Conceptos</title>
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
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Gestion de Conceptos</h6></div>
                    <div class="card card-1">
                        <div id="username" hidden><?= $_SESSION['username'] ?></div>
                        <div class="row" style="margin-left:50px">

                            <h3><i class="bi bi-archive-fill" style="margin-right:20px;font-size:50px"></i>Gestion de Conceptos - <?= $descConcepto ?></h3>
                        </div>
                        <form action="">
                            <div class="row" style="margin-left:50px; margin-top: 1rem; margin-bottom: 1rem;">
                                
                                <div class="col-3" id="cliente" attr-cliente="<?= $cliente?>">
                                    <label >Conceptos:</label>
                                    <select name="conceptos" id="conceptos" style="width:200px;height:40px">
                                        <?php 
                                        foreach ($conceptos as  $value) {
                            
                                        ?>
                                            <option value="<?= $value['ID_CA'] ?>-<?= $value['CONCEPTO'] ?>" <?php  if($idConcepto == $value['ID_CA']){ echo "selected"; }?>><?= $value['CONCEPTO'] ?></option>
                                        <?php } ;?>
                                    </select>
                                    <button class="btn btn-primary submit" style="width:100px;position:relative;bottom:3px;height:40px">Filtrar <i class="bi bi-funnel-fill" style="color:white"></i></button>
                                </div>
                                <div class="col-3"  >
                                    <label >Sucursal:</label>
                                    <select name="locales" id="locales" style="width:200px;height:40px">
                                        <?php 
                                        foreach ($locales as  $value) {
                            
                                        ?>
                                            <option value="<?= $value['NRO_SUCURSAL'] ?>"><?= $value['DESC_SUCURSAL'] ?></option>
                                        <?php } ;?>
                                    </select>
                                    <button class="btn btn-success" style="width:110px;position:relative;bottom:3px;height:40px" onclick="agregar()">Agregar <i class="bi bi-plus-square"></i></button>
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

                                    </thead>

                                    <tbody id="tableVb" style="font-size: small;">
                                        <?php foreach ($porcentajePorSucursal as $key => $value) { ?>
                                            <tr>
                                                <td hidden><?= $value['ID_PA'] ?></td>
                                                <td><?= $value['NRO_SUCURS'] ?></td>
                                                <td><?= $value['DESC_SUCURS'] ?></td>
                                                <td><input type="text" value="<?= $value['PORCENTAJE'] ?>" style="width:50px;height:30px;text-align:center" onchange="actualizarPorcentaje(this)"></td>
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

        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
        <script src="js/cargaDePorcentaje.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    </body>

    </html>
    <script>    
    
    </script>
