<?php
    require_once "Class/categoria.php";

    $categoria = new Categoria();
    $rubros = $categoria->traerRubros();

    $datosRubro = (isset($_GET['rubros'])) ? $_GET['rubros'] : "A-ACCESORIOS" ;

    $siglaRubro = explode("-", $datosRubro)[0];
    $descRubro = explode("-", $datosRubro)[1];

    $data = $categoria->traerVistaRubroCategoriaCodificacion($siglaRubro);
    $ultimo = count($data) - 1;
    
    $ultimoCodigo = 0;
    
    if($ultimo >= 0){
        $ultimoCodigo = $data[$ultimo]['CATEGORIA'];
    }
    
        
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestion categoria productos</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
        <link rel="stylesheet" href="css/gestionCategoriaProductos.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        

        </link>

    </head>


    <body>

        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
                <div class="wrapper wrapper--w680"><div style="color:white; text-align:center"><h6>Gestión Categoría Productos</h6></div>
                    <div class="card card-1">
                        <div id="username" hidden><?= $_SESSION['username'] ?></div>

                        <div class="row" style="margin-left:80px">
                            <h3><i class="bi bi-wrench-adjustable" style="margin-right:10px;font-size:50px"></i>Gestión Categoría Productos - <?= $descRubro ?></h3>
                        </div>
                        <form action="">
                            <div  class="row" style="margin-left:80px">
                                <select name="rubros" id="rubros" style="width: 130px;margin-right:10px" >

                                    <?php 
                                        foreach ($rubros as $key => $value) {
                                           
                                   
                                    
                                    ?>
                                        <option value="<?= $value['SIGLA'] ?>-<?= $value['DESC_RUBRO'] ?>" <?php if( $descRubro == $value['DESC_RUBRO']){ echo "selected" ; }?> ><?= $value['DESC_RUBRO'] ?></option>
                                    <?php 
                                         }
                                    ?>

                                </select>
                                <button type="submit" class="btn btn-primary" style="width: 6rem;">Filtrar <i class="bi bi-search"></i></button>
                            </div>
                        </form>
                        <div class="row" style="margin-left:65px;margin-top:20px">
                            <div class="col-5" style="padding-right:10px">
                            
                                <div class="table-responsive" id="tableIndex">
                                    <table class="table table-hover table-condensed table-striped text-center" style="width: 100%" cellspacing="0" data-page-length="100">
                                        <thead class="thead-dark" style="font-size: small;">
                                            <th  style="width:5%">COD. CATEGORIA</th>
                                            <th  style="width:20%">DESC. CATEGORIA</th>
                                         

                                        </thead>

                                        <tbody id="tableVb" style="font-size: small;">
                                            <td id="codCategoria"><input type="number"></td>
                                            <td ><input type="text" style="width:100%;height:30px" id="descCategoria"></td>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-1" style="margin-top:55px;padding-left:1px"><button class="btn btn-success"  title="Agregar" data-toggle="tooltip" data-placement="bottom"  onclick="agregar('<?= $siglaRubro ?>')"><i class="bi bi-plus-square"></i></button></div>    
                        </div>

                        <div class="row" style="margin-left:65px;margin-top:20px">
                            <div class="col-10">
                            
                                <div class="table-wrapper" id="tableIndex">
                                    <table id="tableData" class="table table-hover table-condensed table-striped text-center"  cellspacing="0" data-page-length="100">
                                        <thead class="thead-dark" style="font-size: small;">
                                            <th scope="col" style="width: 6%">RUBRO</th>
                                            <th scope="col" style="width: 15%">DESC. RUBRO</th>
                                            <th scope="col" style="width: 8%">CATEGORIA</th>
                                            <th scope="col" style="width: 8%">DESC. CATEGORIA</th>
                                            <th scope="col" style="width: 8%"></th>
                                            

                                        </thead>

                                        <tbody id="tableVb" style="font-size: small;">
                                            <?php 
                                                foreach ($data as $key => $value) {
                                            ?>
                                                    <tr>
                                                        <td><?= $value['RUBRO'] ?></td>
                                                        <td><?= $value['DESC_RUBRO'] ?></td>
                                                        <td><?= $value['CATEGORIA'] ?></td>
                                                        <td><?= $value['DESC_CATEGORIA'] ?></td>
                                                        <td><button class="btn btn-warning" onclick="editar(this)"><i class="bi bi-pencil-square"></i></button></td>
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
        <script src="js/gestionCategoriaProductos.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    </body>

    </html>
    <script>    

    $(document).ready( function () {
        $('#tableData').DataTable({
            "bInfo": false,
            "aaSorting": false,
            'columnDefs': [
                {
                    "targets": "_all", 
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

    </script>
