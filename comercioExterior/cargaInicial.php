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

// Reemplazar el modal actual con uno más moderno y funcional
$(document).ready(function() {
    $('#btnAddOrdenCompra').on('click', function() {
        // Validaciones iniciales
        if(document.querySelector("#proveedor").value == 'PROVEEDOR') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debes seleccionar un proveedor primero',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        if(document.querySelector("#ordenManual").checked == true) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No puedes agregar órdenes de compra si seleccionaste orden manual',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        // Obtener órdenes del localStorage
        let ordenes = localStorage.getItem('ordenes');
        ordenes = JSON.parse(ordenes);
        
        // Obtener órdenes ya seleccionadas
        let ordenesSeleccionadas = document.querySelectorAll("#ordenDeCompra");
        let ordenesSeleccionadasArray = Array.from(ordenesSeleccionadas).map(el => el.textContent.trim());
        
        // Preparar opciones del select
        let selectOptions = '';
        
        if (ordenes && Array.isArray(ordenes)) {
            // Filtrar órdenes para excluir las ya seleccionadas
            let ordenesFiltradas = ordenes.filter(orden => 
                !ordenesSeleccionadasArray.includes(orden.N_ORDEN_CO.trim())
            );
            
            if (ordenesFiltradas.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Información',
                    text: 'No hay más órdenes disponibles para seleccionar',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }
            
            ordenesFiltradas.forEach(function(orden) {
                selectOptions += `<option value="${orden.N_ORDEN_CO}">${orden.N_ORDEN_CO}</option>`;
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Sin datos',
                text: 'No se encontraron órdenes de compra disponibles',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        // Crear HTML personalizado para el modal
        const modalHTML = `
            <div class="modal-orden-compra">
                <p class="modal-subtitle">Selecciona una o varias órdenes de compra</p>
                <div class="select-container">
                    <select id="ordenCompra" class="swal2-select custom-select" multiple>
                        ${selectOptions}
                    </select>
                </div>
                <div id="seleccionPrevia" class="seleccion-previa"></div>
                <div class="form-hint">
                    <small><i class="bi bi-info-circle"></i> Mantén presionada la tecla Ctrl para seleccionar múltiples órdenes</small>
                </div>
            </div>
        `;

        // Estilos personalizados para el modal
        const customStyles = `
            <style>
                .modal-orden-compra {
                    padding: 10px 0;
                }
                .modal-subtitle {
                    color: #666;
                    margin-bottom: 15px;
                    font-size: 0.9rem;
                }
                .select-container {
                    position: relative;
                    margin-bottom: 15px;
                }
                .custom-select {
                    width: 100% !important;
                    max-height: 200px !important;
                    border: 1px solid #d9d9d9 !important;
                    border-radius: 8px !important;
                    padding: 8px !important;
                    font-size: 14px !important;
                    transition: border-color 0.3s ease !important;
                }
                .custom-select:focus {
                    border-color: #7066e0 !important;
                    box-shadow: 0 0 0 3px rgba(112, 102, 224, 0.25) !important;
                }
                .custom-select option {
                    padding: 8px !important;
                }
                .seleccion-previa {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 5px;
                    margin-top: 10px;
                }
                .orden-item {
                    background-color: #f1f1f1;
                    border-radius: 5px;
                    padding: 5px 10px;
                    display: inline-flex;
                    align-items: center;
                    font-size: 0.9rem;
                }
                .orden-item-text {
                    margin-right: 5px;
                }
                .form-hint {
                    margin-top: 15px;
                    color: #888;
                    font-size: 0.8rem;
                }
            </style>
        `;

        // Mostrar modal mejorado
        Swal.fire({
            title: 'Añadir Orden de Compra',
            html: customStyles + modalHTML,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-check-circle"></i> Guardar',
            cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
            confirmButtonColor: '#7066e0',
            cancelButtonColor: '#6c757d',
            focusConfirm: false,
            customClass: {
                container: 'modal-container',
                popup: 'modal-popup',
                header: 'modal-header',
                title: 'modal-title',
                closeButton: 'modal-close-button',
                content: 'modal-content',
                actions: 'modal-actions',
                confirmButton: 'modal-confirm-button',
                cancelButton: 'modal-cancel-button'
            },
            didOpen: () => {
                // Actualizar vista previa cuando se seleccionan opciones
                const selectElement = document.getElementById('ordenCompra');
                selectElement.addEventListener('change', () => {
                    const seleccionPrevia = document.getElementById('seleccionPrevia');
                    seleccionPrevia.innerHTML = '';
                    
                    const selectedOptions = Array.from(selectElement.selectedOptions);
                    selectedOptions.forEach(option => {
                        const ordenItem = document.createElement('div');
                        ordenItem.className = 'orden-item';
                        ordenItem.innerHTML = `
                            <span class="orden-item-text">${option.value}</span>
                        `;
                        seleccionPrevia.appendChild(ordenItem);
                    });
                });
            },
            preConfirm: () => {
                const selectedOptions = Array.from(
                    Swal.getPopup().querySelectorAll('.swal2-select option:checked'), 
                    option => option.value
                );
                
                if (selectedOptions.length === 0) {
                    Swal.showValidationMessage('Por favor selecciona al menos una orden de compra');
                    return false;
                }
                
                // Añadir órdenes seleccionadas al contenedor
                selectedOptions.forEach(function(orden) {
                    const div = document.createElement('div');
                    div.id = 'ordenDeCompra';
                    div.style.border = '1px solid #5a50d2';
                    div.style.margin = "5px";
                    div.style.width = '150px';
                    div.style.backgroundColor = '#7066e0';
                    div.style.color = 'white';
                    div.style.borderRadius = '10px';
                    div.style.padding = '5px 10px';
                    div.style.display = 'flex';
                    div.style.justifyContent = 'space-between';
                    div.style.alignItems = 'center';
                    div.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
                    div.style.transition = 'all 0.3s ease';
                    
                    div.innerHTML = `
                        <span style="overflow: hidden; text-overflow: ellipsis;" id="nroOrdenSpan">${orden}</span> 
                        <button class="btn-delete" data-orden="${orden}" style="background: none; border: none; cursor: pointer; padding: 0; margin-left: 5px">
                            <i class="bi bi-x-circle" style="color:white;"></i>
                        </button>
                    `;
                    
                    document.querySelector("#ordenesSeleccionadas").appendChild(div);
                    
                    // Agregar efecto hover
                    div.addEventListener('mouseover', function() {
                        this.style.backgroundColor = '#5a50d2';
                        this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
                    });
                    
                    div.addEventListener('mouseout', function() {
                        this.style.backgroundColor = '#7066e0';
                        this.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
                    });
                });
                
                // Configurar botones de eliminación
                const deleteButtons = document.querySelectorAll('.btn-delete');
                deleteButtons.forEach(function(button) {
                    button.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const orden = this.getAttribute('data-orden');
                        
                        // Animación de eliminación
                        const parentDiv = this.parentNode;
                        parentDiv.style.transform = 'scale(0.8)';
                        parentDiv.style.opacity = '0';
                        
                        setTimeout(() => {
                            parentDiv.remove();
                            
                            // Notificación toast
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'bottom-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                            
                            Toast.fire({
                                icon: 'success',
                                title: `Orden ${orden} eliminada`
                            });
                            
                            // Si es necesario, llamar a otras funciones después de eliminar
                            // checkOrdenesUy();
                        }, 300);
                    });
                });
                
                return true; // Confirmación exitosa
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar notificación de éxito
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: 'Órdenes de compra agregadas correctamente'
                });
                
                // Si es necesario, llamar a funciones adicionales
                // checkOrdenesUy();
            }
        });
    });
    
    // Mejorar estilo de los elementos de órdenes existentes
    function mejorarEstiloOrdenesExistentes() {
        const ordenesExistentes = document.querySelectorAll("#ordenDeCompra");
        
        ordenesExistentes.forEach(orden => {
            orden.style.border = '1px solid #5a50d2';
            orden.style.backgroundColor = '#7066e0';
            orden.style.padding = '5px 10px';
            orden.style.display = 'flex';
            orden.style.justifyContent = 'space-between';
            orden.style.alignItems = 'center';
            orden.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
            orden.style.transition = 'all 0.3s ease';
            
            // Agregar efectos hover
            orden.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#5a50d2';
                this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
            });
            
            orden.addEventListener('mouseout', function() {
                this.style.backgroundColor = '#7066e0';
                this.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
            });
        });
    }
    
    // Llamar a la función para mejorar el estilo al cargar la página
    mejorarEstiloOrdenesExistentes();
});

// Mejorar la función traerOrden para presentación más estilizada
const traerOrden = () => {
    let ordenManual = document.querySelector("#ordenManual");
    let ordenesSeleccionadas = document.querySelector("#ordenesSeleccionadas");
    
    if(ordenManual.checked == true) {
        // Mostrar un indicador de carga
        ordenesSeleccionadas.innerHTML = `
            <div class="text-center p-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        `;
        
        $.ajax({
            url: 'Controller/traerOrdenManualController.php',
            method: 'GET',
            success: function(data) {
                ordenesSeleccionadas.innerHTML = '';

                let num = JSON.parse(data);
                
                if(num['nroOrden'] == null) {
                    num['nroOrden'] = 0;
                }

                let sumaOrden = 200000000 + num['nroOrden'];
                let orden = `0000${sumaOrden}`;
                
                const div = document.createElement('div');
                div.id = 'ordenDeCompra';
                div.style.border = '1px solid #5a50d2';
                div.style.margin = "5px";
                div.style.width = '150px';
                div.style.backgroundColor = '#7066e0';
                div.style.color = 'white';
                div.style.borderRadius = '10px';
                div.style.padding = '8px 12px';
                div.style.display = 'flex';
                div.style.justifyContent = 'center';
                div.style.alignItems = 'center';
                div.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
                div.style.fontSize = '14px';
                div.style.fontWeight = '500';
                div.innerHTML = `<span>${orden}</span>`;
                
                // Añadir el elemento con animación
                div.style.opacity = '0';
                div.style.transform = 'translateY(10px)';
                ordenesSeleccionadas.appendChild(div);
                
                setTimeout(() => {
                    div.style.transition = 'all 0.3s ease';
                    div.style.opacity = '1';
                    div.style.transform = 'translateY(0)';
                }, 10);
                
                // Efecto hover
                div.addEventListener('mouseover', function() {
                    this.style.backgroundColor = '#5a50d2';
                    this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
                });
                
                div.addEventListener('mouseout', function() {
                    this.style.backgroundColor = '#7066e0';
                    this.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
                });

                // Notificación toast
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: 'Orden manual generada correctamente'
                });
            },
            error: function() {
                ordenesSeleccionadas.innerHTML = '';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo generar la orden manual',
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    } else {
        // Eliminar con animación
        const elementos = ordenesSeleccionadas.querySelectorAll('#ordenDeCompra');
        
        elementos.forEach(elem => {
            elem.style.transition = 'all 0.3s ease';
            elem.style.opacity = '0';
            elem.style.transform = 'scale(0.8)';
        });
        
        setTimeout(() => {
            ordenesSeleccionadas.innerHTML = '';
        }, 300);
    }
}

</script>
