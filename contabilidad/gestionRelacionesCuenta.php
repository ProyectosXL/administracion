<?php
    require_once "Class/cuentaContable.php";
    include 'Class/centroCosto.php';
    include 'Class/rubroContable.php';
    include 'Class/gasto.php';
    include 'Class/prorrateo.php';


    $cuenta = new CuentaContable();
    $centroCostos = new CentroCosto();
    $rubroContable = new RubroContable();
    $gasto = new Gasto();
    $prorrateo = new Prorrateo();

    $codigosDeCuenta = $cuenta->traerCuentasContables();
    $codigosDeCuenta = json_decode($codigosDeCuenta); 


    $centroCostos = $centroCostos->traerSectoresCentroCostos();


    $todosLosRubros = $rubroContable->traerRubrosContables();
    $todosLosRubros = json_decode($todosLosRubros);

    $RelacionCuentaRubroContable = $gasto->traerRelacionCuentaRubroContable();
    $metodosProrrateo = $prorrateo->traerMetodosProrrateo();
    $arrayMetodosProrrateo = json_decode($metodosProrrateo);

    
    ?>

    <!DOCTYPE html>
    <html lang="en">
        <style>
                .table-wrapper {
            width: 110%;
            height: 630px; 
            overflow: auto;
            overflow-x:hidden;
            }

            .table-wrapper table thead {
            position: -webkit-sticky; 
            position: sticky;
            top: 0;
            left: 0;
            }

            .select2-container .select2-results__option {
                font-size: 12px; /* Cambia el tamaño de fuente de las opciones */
            }

        </style>

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestion de Relaciones</title>
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
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Gestion Relaciones Cuenta - rubro contable</h6></div>
                    <div class="card card-1">
                        <div id="username" hidden><?= $_SESSION['username'] ?></div>

                        <div class="row" style="margin-left:80px">
                            <h3><i class="bi bi-wrench-adjustable" style="margin-right:10px;font-size:50px"></i>Gestión Relaciones Cuenta - Rubro Contable</h3>
                        </div>
                   
                        <div class="row" style="margin-left:65px;margin-top:20px">
                            <div class="col-10" style="padding-right:10px">
                            
                                <div class="table-responsive" id="tableIndex">
                                    <table class="table table-hover table-condensed table-striped text-center" style="width: 100%" cellspacing="0" data-page-length="100">
                                        <thead class="thead-dark" style="font-size: small;">
                                            <th  style="width:5%">COD. CUENTA</th>
                                            <th  style="width:20%">DESC. CUENTA</th>
                                            <th  style="width:5%">SECTOR</th>
                                            <th  style="width:10%">COD. RUBRO</th>
                                            <th  style="width:30%">RUBRO CONTABLE </th>
                                            <th  style="width:10%">COD. PRORRATEO</th>
                                            <th  style="width:20%">DESC. PRORRATEO </th>

                                        </thead>

                                        <tbody id="tableVb" style="font-size: small;">

                                            <td style="width:100px">

                                                <select class="codCuenta" name="codCuenta" id="codCuenta" style="width:100px; height:35px; text-align:center" onchange="traerDescCuenta(this)" >
                                                    <option disabled="disabled" selected></option>
                                                    <?php 
                                                    foreach ($codigosDeCuenta as $key => $value) {
                                                        echo "<option value='".$value->COD_CUENTA."' attr-desc-cuenta='".$value->DESC_CUENTA."'>".$value->COD_CUENTA."</option>";
                                                    }
                                                    ?>

                                                </select>

                                            </td>

                                            <td id="descCuenta"></td>

                                            <td>
                                                <select class="sector" name="sector" id="sector" style="width:170px; height:35px; text-align:center" >
                                                    <option disabled="disabled" selected></option>
                                                    <?php 
                                                        foreach ($centroCostos as $key => $value) {
                                                            echo "<option value='".$value['SECTOR']."'>".$value['SECTOR']."</option>";
                                                        }
                                                    ?>

                                                </select>
                                            </td>

                                            <td>
                                                <select  class="codRubro" name="codRubro" id="codRubro" style="width:200px; height:35px; text-align:center;font-size:12px" onchange="traerDescRubro(this)" >
                                                    <option disabled="disabled" selected></option>
                                                    <?php 
                                                        foreach ($todosLosRubros as $key => $value) {

                                                            echo "<option value='".$value->COD_RUBRO."' attr-desc-rubro='".$value->RUBRO_CONTABLE."' style='font-size:12px'>".$value->COD_RUBRO."-".$value->RUBRO_CONTABLE."</option>";
                                                           
                                                        }
                                                    
                                                    ?>

                                                </select>
                                            </td>

                                            <td id="rubroContable"></td>
                                            <td>

                                                <select class="codProrrateo" name="codProrrateo" id="codProrrateo" style="width:180px; height:35px; text-align:center" onchange="traerDescProrrateo(this)">
                                                        <option disabled="disabled" selected></option>
                                                        <?php 
                                                            foreach ($arrayMetodosProrrateo as $key => $value) {
                                                                echo "<option value='".$value->COD_PRORRATEO."' attr-desc-prorrateo='".$value->DESC_PRORRATEO."'>".$value->COD_PRORRATEO.'-'.$value->DESC_PRORRATEO."</option>";
                                                            }
                                                        ?>
                                                </select>
                                                
                                            
                                            </td>
                                            <td id="descProrrateo"></td>
                                        
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-1" style="margin-top:55px;padding-left:1px"><button class="btn btn-success"  title="Agregar" data-toggle="tooltip" data-placement="bottom"  onclick="agregar()"><i class="bi bi-plus-square"></i></button></div>    
                        </div>
                        <div class="row" style="margin-left:65px;margin-top:20px">
                            <div class="col-10">
                            
                                <div class="table-wrapper" id="tableIndex">
                                    <table id="tableData" class="table table-hover table-condensed table-striped text-center"  cellspacing="0" data-page-length="100">
                                        <thead class="thead-dark" style="font-size: small;">
                                            <th scope="col" style="width: 6%">COD. CUENTA</th>
                                            <th scope="col" style="width: 15%">DESC. CUENTA</th>
                                            <th scope="col" style="width: 8%">SECTOR</th>
                                            <th scope="col" style="width: 8%">COD. RUBRO</th>
                                            <th scope="col" style="width: 8%">RUBRO CONTABLE </th>

                                        </thead>

                                        <tbody id="tableVb" style="font-size: small;">
                                                        <?php 
                                                            foreach ($RelacionCuentaRubroContable as $key => $value) {
                                                        ?>

                                                            <tr>
                                                                <td><?= $value['COD_CUENTA'] ?></td>
                                                                <td><?= $value['DESC_CUENTA'] ?></td>
                                                                <td><?= $value['SECTOR'] ?></td>
                                                                <td><?= $value['COD_RUBRO'] ?></td>
                                                                <td><?= $value['RUBRO_CONTABLE'] ?></td>
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
        <script src="js/gestionRelacionesCuenta.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    </body>

    </html>
    <script>    


    $('.codCuenta').select2();
    $('.sector').select2();
    $('.codRubro').select2();
    $('.codProrrateo').select2();
    $(document).ready( function () {
        $('#tableData').DataTable({
            "bInfo": false,
            "aaSorting": false,
            'columnDefs': [
                {
                    "targets": "_all", // your case first column
                    "className": "text-center",
                    "sortable": false,
             
                },
            ],
            "oLanguage": {

                "sSearch": "Busqueda rapida sobre cualquier campo :"

            },
        });
    } );
    $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        })

        $('#myModal').modal('toggle')

    </script>
