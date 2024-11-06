<?php

include 'Class/proveedor.php';
include 'Class/ordenDeCompra.php';

$proveedor = new Proveedor();
$todosLosProveedores = $proveedor->traerProveedores();
$todosLosProveedores = json_decode($todosLosProveedores);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Title Page-->
    <title>Costos Importacion</title>

    <!-- Librerias Bootstrap -->
    <!-- CSS only -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
                        <!------------------------------------------------------------>

    <!-- Icons font CSS-->
    <link href="assets/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">
    <link href="assets/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <!-- Font special for pages-->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <!-- Vendor CSS-->
    <link href="assets/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="assets/datepicker/daterangepicker.css" rel="stylesheet" media="all">

    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">
    <!-- Main CSS-->
    <link href="css/style.css" rel="stylesheet" media="all">
    
</head>
<style>
.contenedor {
    display: flex;
    flex-wrap: wrap; /* Permite el ajuste de los elementos en varias líneas */
}

</style>

<body>
    <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
        <div class="wrapper wrapper--w680">
            <div class="card card-1">
                <div class="card-heading"></div>
                <div class="card-body">
                    <h2 class="title"><i class="bi bi-folder-check"></i> Datos de cabecera - Costos de Nacionalizacion</h2>


                    <div class="row" style="margin-bottom:10px;margin-left:8px">
                    Orden De Compra Manual 
           

                        <div class="col" id="checkOrden"><input type="checkbox" id="ordenManual" onchange="traerOrden()"></div>

           
                    </div>
                    <div id="entorno" hidden><?= (isset($_SESSION['entorno'])) ? $_SESSION['entorno'] : 'central' ?></div>

                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                      <!--   <div class="rs-select2 js-select-simple select--no-search"> -->
                                            <select id="proveedor" style="width: 283.16px;">
                                            <option selected disabled>PROVEEDOR</option>
                                            <?php
                                        
                                            foreach($todosLosProveedores as $valor => $value){
                                            /* $cuenta=$value-> */
                                            ?>
                                            <option id="proveedor-" value="<?= $value->COD_PROVEE; ?>"><?= $value->NOM_PROVEE; ?></option>
                                            <?php   
                                            }
                                            ?>
                                            </select>
                                            <div class="select-dropdown"></div>
                                        <!-- </div>      -->   
                                    </div>    
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 mayusc" type="text" placeholder="Nº ORDEN PROVEEDOR" id="contenedor">
                                    </div>    
                                </div>
                            </div>
                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 js-datepicker4" type="text" placeholder="FECHA DESP. ADUANA" id="fechaDespacho">
                                        <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar4"></i>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 mayusc" type="text" placeholder="DESPACHO N°" id="despacho" required>
                                    </div>    
                                </div>
                            </div>
                            <div class="row row-space">                                
                            </div>
                            
                            <div class="row row-space">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 soloNum" type="text" placeholder="NUMERO BL" id="numeroBl">
                                    </div>    
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 mayusc" type="text" placeholder="MATERIAL" id="material" oninput="validarTextoEntrada(this, '[a-záéíóúñ ]')">
                                    </div>    
                                </div>
                            </div>
                            <div class="row row-space">
                                
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 mayusc" type="text" placeholder="FACTURA PROVEEDOR" id="facturaProveedor">
                                    </div>    
                                </div>

                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 mayusc" type="text" value="CHINA" placeholder="ORIGEN" id="origen">
                                    </div>    
                                </div>
                            </div>
                            <div class="row row-space">
                                
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 decimales currencyInput" onkeyup="calcular()" type="text" placeholder="VALOR F.O.B. U$S" id="valorFobDolar">
                                    </div>    
                                </div>

                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 decimales currencyInput" onkeyup="calcular()" type="text" placeholder="TIPO DE CAMBIO DESPACHO" id="tipoCambio">
                                    </div>    
                                </div>
                            </div>
                            <div class="row row-space">
                              
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input class="input--style-1 decimales" type="text" placeholder="VALOR F.O.B. $" id="valorFobPeso" readonly>
                                    </div>
                                </div>      
                            </div>
                            <div class="row row-space">
                       
                                
                                    <div class="col-md-5" >
                                        <div class="input-group">
                                            <div style="margin-right:20px">ORDENES DE COMPRA</div>
                                            <div><button id="btnAddOrdenCompra"><i class="bi bi-plus-circle-fill"></i></button></div>
                                            
                                        </div>    
                                    <div id="ordenesSeleccionadas" class="contenedor"></div>
                                    
                                </div>
                      

                                <div class="col-md-5">
                                    <div class="input-group">
                                        <div class="rs-select2 js-select-simple select--no-search ">
                                            <select id="formaPago" style="width: 283.16px;">
                                                <option disabled="disabled" selected="selected">FORMA DE PAGO</option>
                                                <option>PAGO ANTICIPADO</option>
                                                <option>PAGO VISTA</option>
                                                <option>PAGO DIFERIDO</option>
                                            </select>
                                            <div class="select-dropdown"></div>
                                        </div>        
                                    </div>    
                                </div>
                            </div>
                        <div class="p-t-20">
                            <button class="btn btn-primary" id="btnSave" onclick="guardarCabeceraUy()" >Guardar <i class="bi bi-cloud-download"></i></button>
                        </div>
                </div>
            </div>
        </div>
    </div>

    
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Jquery JS-->
    <script src="assets/jquery/jquery.min.js"></script>
    <!-- Vendor JS-->
    <script src="assets/select2/select2.min.js"></script>
    <script src="assets/datepicker/moment.min.js"></script>
    <script src="assets/datepicker/daterangepicker.js"></script>

    <!-- Main JS-->
    <script src="js/global.js"></script>
    <script src="js/main.js"></script>

</body>

</html>


<script>



    const traerOrden = ()=>{
        
        let ordenManual = document.querySelector("#ordenManual");
     
        let ordenesSeleccionadas = document.querySelector("#ordenesSeleccionadas");
        if(ordenManual.checked == true){
            $.ajax({
                url: 'Controller/traerOrdenManualController.php',
                method: 'GET',
                success : function(data) {

                    ordenesSeleccionadas.innerHTML = '';

         
                    let num = JSON.parse(data);
                    
                    if(num['nroOrden'] == null){
                         num['nroOrden'] = 0;
                     }

                    let sumaOrden = 200000000 + num['nroOrden'];
                    let orden = ` 0000${sumaOrden}`;
                    
                    const div = document.createElement('div');
                    div.id = 'ordenDeCompra'
                    div.style.border = '1px solid black';
                    div.style.margin = "5px"
                    div.style.width = '130px';
                    div.style.backgroundColor = '#7066e0';
                    div.style.color = 'white';
                    div.style.borderRadius = '10px';
                    div.innerHTML = `   ${orden}`;
                    document.querySelector("#ordenesSeleccionadas").appendChild(div);
                    

       

                }
            })
        }else{
            ordenesSeleccionadas.innerHTML = '';
        }
    }

    $(document).ready(function() {

        $('#btnAddOrdenCompra').on('click', function() {

           if(document.querySelector("#proveedor").value == 'PROVEEDOR' ){
             Swal.fire({
                icon: 'error',
                title: 'Error...',
                text: 'Debes seleccionar un proveedor!',
            })
            return 
            }

            if( document.querySelector("#ordenManual").checked == true ){

                Swal.fire({
                    icon: 'error',
                    title: 'Error...',
                    text: 'No puedes agregar ordenes de compra si seleccionaste orden manual!',
                })
                return 

            }
      

            let ordenes = localStorage.getItem('ordenes');
            ordenes = JSON.parse(ordenes)
            console.log(ordenes)
            let ordenesSeleccionadas = document.querySelectorAll("#ordenDeCompra");

            let selectOptions = '<option disabled>Selecciona una o varias opciónes</option>';

            if (ordenes && Array.isArray(ordenes)) {
                ordenes.forEach(function(orden, index) {
                    let ordenesSeleccionadas2 = []
                    ordenesSeleccionadas.forEach(element => {
                         ordenesSeleccionadas2.push(element.textContent.trim())
                    });
       
                
                    if(ordenesSeleccionadas2.includes(orden.N_ORDEN_CO.trim())){
                       
                        return;
                    }

                    selectOptions += `<option value="${orden.N_ORDEN_CO}">${orden.N_ORDEN_CO}</option>`;
                });
            }

            Swal.fire({
                title: 'Añadir Orden de Compra',
                html:
                    `<select id="ordenCompra" class="swal2-select" multiple>${selectOptions}</select>`,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                focusConfirm: false,
                preConfirm: () => {
                    const selectedOptions = Array.from(Swal.getPopup().querySelectorAll('.swal2-select option:checked'), option => option.value);
                    selectedOptions.forEach(function(orden) {
             
                    const div = document.createElement('div');
                    div.id = 'ordenDeCompra'
                    div.style.border = '1px solid black';
                    div.style.margin = "5px"
                    div.style.width = '130px';
                    div.style.backgroundColor = '#7066e0';
                    div.style.color = 'white';
                    div.style.borderRadius = '10px';
                    div.innerHTML = ` ${orden} <button class="btn-delete" data-orden="${orden}" style="border-left: 1px solid black"> <i class="bi bi-x-circle" style="color:white;margin-left:3px"></i></button>`;
                    document.querySelector("#ordenesSeleccionadas").appendChild(div);
               
               
                });

             
                const deleteButtons = document.querySelectorAll('.btn-delete');
                deleteButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        const orden = this.getAttribute('data-orden');
                   
                        this.parentNode.remove();
                                 
                    });
                });
                let result = checkOrdenesUy()
                }
            });

        });
    });
</script>
