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
    flex-wrap: wrap;
    gap: 8px;
}

/* Estilos para las secciones del formulario */
.seccion-formulario {
    background: #f8f9fa;
    border-left: 4px solid #7066e0;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.seccion-titulo {
    color: #7066e0;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e0e0e0;
}

/* Estilos para labels de campos */
.label-campo {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #555;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-auto {
    background: #4caf50;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 500;
    margin-left: 5px;
}

/* Indicadores visuales para campos calculados */
.campo-calculado {
    background-color: #e8f5e9 !important;
    border-left: 3px solid #4caf50 !important;
}

.campo-manual-override {
    background-color: #fff3e0 !important;
    border-left: 3px solid #ff9800 !important;
}

.campo-readonly {
    background-color: #f5f5f5 !important;
    cursor: not-allowed !important;
}

/* Estilos para el botón de agregar orden */
#btnAddOrdenCompra {
    background: #7066e0;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

#btnAddOrdenCompra:hover {
    background: #5a50d2;
    transform: scale(1.05);
}

</style>

<body>
    <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">
        <div class="wrapper wrapper--w680">
            <div class="card card-1">
                <div class="card-heading"></div>
                <div class="card-body">
                    <h2 class="title"><i class="bi bi-folder-check"></i> Datos de cabecera - Costos de Nacionalización</h2>

                    <div class="row" style="margin-bottom:10px;margin-left:8px">
                        <span>Orden De Compra Manual</span>
                        <div class="col" id="checkOrden"><input type="checkbox" id="ordenManual" onchange="traerOrden()"></div>
                    </div>
                    
                    <div id="entorno" hidden><?= (isset($_SESSION['entorno'])) ? $_SESSION['entorno'] : 'central' ?></div>
                    <input type="hidden" id="modoEdicion" value="false">

                    <!-- ========== SECCIÓN 1: DATOS INICIALES ========== -->
                    <div class="seccion-formulario mt-4 mb-4">
                        <h4 class="seccion-titulo"><i class="bi bi-clipboard-data"></i> Sección 1 - Datos Iniciales</h4>
                        
                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Proveedor</label>
                                <div class="input-group">
                                    <select id="proveedor" style="width: 283.16px;" required>
                                        <option selected disabled>Seleccione...</option>
                                        <?php
                                        foreach($todosLosProveedores as $valor => $value){
                                        ?>
                                        <option id="proveedor-" value="<?= $value->COD_PROVEE; ?>"><?= $value->NOM_PROVEE; ?></option>
                                        <?php   
                                        }
                                        ?>
                                    </select>
                                    <div class="select-dropdown"></div>
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Nº Orden Proveedor</label>
                                <div class="input-group">
                                    <input class="input--style-1 mayusc" type="text" id="contenedor" required>
                                </div>    
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Material</label>
                                <div class="input-group">
                                    <input class="input--style-1 mayusc" type="text" id="material" required>
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Origen</label>
                                <div class="input-group">
                                    <input class="input--style-1 mayusc" type="text" value="CHINA" id="origen" required>
                                </div>    
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Valor F.O.B. U$S</label>
                                <div class="input-group">
                                    <input class="input--style-1 decimales currencyInput" onkeyup="recalcularFobPesos()" type="text" id="valorFobDolar" required>
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Órdenes de Compra</label>
                                <div class="input-group">
                                    <button type="button" id="btnAddOrdenCompra" style="width: 100%;"><i class="bi bi-plus-circle-fill"></i> Agregar Orden</button>
                                </div>    
                                <div id="ordenesSeleccionadas" class="contenedor"></div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Fecha Estimada Embarque</label>
                                <div class="input-group">
                                    <input class="input--style-1 js-datepicker-estimada" type="text" id="fechaEmb" required>
                                    <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar-estimada"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========== SECCIÓN 2: DATOS DE EMBARQUE ========== -->
                    <div class="seccion-formulario mt-4 mb-4">
                        <h4 class="seccion-titulo"><i class="bi bi-ship"></i> Sección 2 - Datos de Embarque</h4>
                        
                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Fecha Embarque - ETD</label>
                                <div class="input-group">
                                    <input class="input--style-1 js-datepicker-etd" type="text" id="fechaEtd">
                                    <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar-etd"></i>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Fecha Arribo - ETA <span class="badge-auto">Auto</span></label>
                                <div class="input-group">
                                    <input class="input--style-1 js-datepicker-arribo" type="text" id="fechaArr">
                                    <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar-arribo"></i>
                                </div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Número BL</label>
                                <div class="input-group">
                                    <input class="input--style-1 soloNum" type="text" id="numeroBl">
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Factura Proveedor</label>
                                <div class="input-group">
                                    <input class="input--style-1 mayusc" type="text" id="factura">
                                </div>    
                            </div>
                        </div>
                    </div>

                    <!-- ========== SECCIÓN 3: DATOS FINANCIEROS Y ADUANA ========== -->
                    <div class="seccion-formulario mt-4 mb-4">
                        <h4 class="seccion-titulo"><i class="bi bi-cash-coin"></i> Sección 3 - Datos Financieros y Aduana</h4>
                        
                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Tipo de Cambio</label>
                                <div class="input-group">
                                    <input class="input--style-1 decimales currencyInput" onkeyup="recalcularFobPesos()" type="text" id="tipoCambio">
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Valor F.O.B. $ <span class="badge-auto">Auto</span></label>
                                <div class="input-group">
                                    <input class="input--style-1 decimales" type="text" id="valorFobPeso" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Forma de Pago</label>
                                <div class="input-group">
                                    <div class="rs-select2 js-select-simple select--no-search">
                                        <select id="formaPago" style="width: 283.16px;">
                                            <option disabled="disabled" selected="selected">Seleccione...</option>
                                            <option>PAGO ANTICIPADO</option>
                                            <option>PAGO VISTA</option>
                                            <option>PAGO DIFERIDO</option>
                                        </select>
                                        <div class="select-dropdown"></div>
                                    </div>        
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Fecha de Pago <span class="badge-auto">Auto</span></label>
                                <div class="input-group">
                                    <input class="input--style-1 js-datepicker-pago" type="text" id="fechaPago">
                                    <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar-pago"></i>
                                </div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Fecha Nacionalización <span class="badge-auto">Auto</span></label>
                                <div class="input-group">
                                    <input class="input--style-1 js-datepicker-despacho" type="text" id="fechaDespAdu">
                                    <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar-despacho"></i>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Despacho N°</label>
                                <div class="input-group">
                                    <input class="input--style-1 mayusc" type="text" id="despacho">
                                </div>    
                            </div>
                        </div>
                    </div>

                    <div class="p-t-20">
                        <button class="btn btn-primary" id="btnSave" onclick="guardarCabecera()">Guardar <i class="bi bi-cloud-download"></i></button>
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

// ========== VARIABLES GLOBALES Y FLAGS ==========
let fechaArriboIsManual = false;
let fechaPagoIsManual = false;
let fechaDespachoIsManual = false;

// ========== FUNCIONES DE CÁLCULO AUTOMÁTICO ==========

/**
 * Obtiene la fecha base (FECHA_EMB - ETD)
 * Esta es la fecha estimada de embarque
 */
function obtenerFechaBase() {
    const fechaEmb = $('#fechaEmb').val();
    
    if (fechaEmb && fechaEmb.trim() !== '') {
        return moment(fechaEmb, 'DD/MM/YYYY');
    }
    return null;
}

/**
 * Suma días a una fecha y retorna en formato DD/MM/YYYY
 */
function sumarDias(fechaMoment, dias) {
    if (!fechaMoment || !fechaMoment.isValid()) return '';
    return fechaMoment.clone().add(dias, 'days').format('DD/MM/YYYY');
}

/**
 * Recalcula la Fecha de Arribo - ETA (FECHA_ARR = FECHA_EMB + 45 días)
 */
function recalcularFechaArribo() {
    if (fechaArriboIsManual) return; // No recalcular si está en modo manual
    
    const fechaBase = obtenerFechaBase();
    if (fechaBase) {
        const nuevaFechaArribo = sumarDias(fechaBase, 45);
        $('#fechaArr').val(nuevaFechaArribo);
        marcarCampoCalculado('#fechaArr');
    }
}

/**
 * Recalcula la Fecha de Pago (Fecha base + 5 días)
 */
function recalcularFechaPago() {
    if (fechaPagoIsManual) return;
    
    const fechaBase = obtenerFechaBase();
    if (fechaBase) {
        const nuevaFechaPago = sumarDias(fechaBase, 5);
        $('#fechaPago').val(nuevaFechaPago);
        marcarCampoCalculado('#fechaPago');
    }
}

/**
 * Recalcula la Fecha de Nacionalización (FECHA_DESP_ADU = FECHA_ARR + 2 días)
 */
function recalcularFechaDespacho() {
    if (fechaDespachoIsManual) return;
    
    const fechaArr = $('#fechaArr').val();
    if (fechaArr && fechaArr.trim() !== '') {
        const fechaArriboMoment = moment(fechaArr, 'DD/MM/YYYY');
        const nuevaFechaDespacho = sumarDias(fechaArriboMoment, 2);
        $('#fechaDespAdu').val(nuevaFechaDespacho);
        marcarCampoCalculado('#fechaDespAdu');
    }
}

/**
 * Recalcula todos los campos de fechas automáticas
 */
function recalcularTodasLasFechas() {
    recalcularFechaArribo();
    recalcularFechaPago();
    recalcularFechaDespacho();
}

/**
 * Calcula FOB en Pesos = FOB U$S × Tipo de Cambio
 */
function recalcularFobPesos() {
    const valorFobDolar = parseFloat($('#valorFobDolar').val().replace(/,/g, '')) || 0;
    const tipoCambio = parseFloat($('#tipoCambio').val().replace(/,/g, '')) || 0;
    
    if (valorFobDolar > 0 && tipoCambio > 0) {
        const valorFobPeso = valorFobDolar * tipoCambio;
        $('#valorFobPeso').val(valorFobPeso.toFixed(2));
    } else {
        $('#valorFobPeso').val('');
    }
}

// ========== FUNCIONES DE MANUAL OVERRIDE ==========

/**
 * Marca visualmente un campo como calculado automáticamente
 */
function marcarCampoCalculado(selector) {
    $(selector).removeClass('campo-manual-override').addClass('campo-calculado');
}

/**
 * Marca visualmente un campo como editado manualmente
 */
function marcarCampoManual(selector) {
    $(selector).removeClass('campo-calculado').addClass('campo-manual-override');
}

/**
 * Reactiva el cálculo automático cuando un campo se vacía
 */
function verificarCampoVacio(selector, flagVariable, flagName) {
    $(selector).on('change', function() {
        const valor = $(this).val();
        if (!valor || valor.trim() === '') {
            // Campo vacío: reactivar cálculo automático
            window[flagName] = false;
            $(this).removeClass('campo-manual-override campo-calculado');
        }
    });
}

/**
 * Detecta edición manual en campos calculados
 */
function configurarManualOverride() {
    // Fecha Arribo (FECHA_ARR - ETA)
    $('#fechaArr').on('dp.change', function(e) {
        if (e.date && $(this).val().trim() !== '') {
            fechaArriboIsManual = true;
            marcarCampoManual('#fechaArr');
            // Al cambiar manualmente fecha arribo, recalcular despacho si no es manual
            recalcularFechaDespacho();
        }
    });
    verificarCampoVacio('#fechaArr', fechaArriboIsManual, 'fechaArriboIsManual');

    // Fecha Pago
    $('#fechaPago').on('dp.change', function(e) {
        if (e.date && $(this).val().trim() !== '') {
            fechaPagoIsManual = true;
            marcarCampoManual('#fechaPago');
        }
    });
    verificarCampoVacio('#fechaPago', fechaPagoIsManual, 'fechaPagoIsManual');

    // Fecha Nacionalización (FECHA_DESP_ADU)
    $('#fechaDespAdu').on('dp.change', function(e) {
        if (e.date && $(this).val().trim() !== '') {
            fechaDespachoIsManual = true;
            marcarCampoManual('#fechaDespAdu');
        }
    });
    verificarCampoVacio('#fechaDespAdu', fechaDespachoIsManual, 'fechaDespachoIsManual');
}

// ========== LÓGICA DE ETAPAS (ALTA vs EDICIÓN) ==========

/**
 * Controla el modo de edición del formulario
 */
function establecerModoFormulario(esEdicion) {
    $('#modoEdicion').val(esEdicion ? 'true' : 'false');
    
    if (esEdicion) {
        // MODO EDICIÓN: Sección 1 readonly excepto Valor FOB U$S
        $('#proveedor').prop('disabled', true).addClass('campo-readonly');
        $('#contenedor').prop('readonly', true).addClass('campo-readonly');
        $('#material').prop('readonly', true).addClass('campo-readonly');
        $('#origen').prop('readonly', true).addClass('campo-readonly');
        $('#fechaEmb').prop('readonly', true).addClass('campo-readonly');
        $('#btnAddOrdenCompra').prop('disabled', true).css('opacity', '0.5');
        
        // Valor FOB U$S sigue editable
        $('#valorFobDolar').prop('readonly', false).removeClass('campo-readonly');
        
        // Secciones 2 y 3 editables
        $('#fechaEtd, #numeroBl, #factura, #fechaArr').prop('readonly', false).removeClass('campo-readonly');
        $('#tipoCambio, #formaPago, #fechaPago, #fechaDespAdu, #despacho').prop('readonly', false).removeClass('campo-readonly');
        
    } else {
        // MODO ALTA INICIAL: Sección 1 obligatoria y editable
        $('#proveedor').prop('disabled', false).removeClass('campo-readonly');
        $('#contenedor, #material, #origen, #fechaEmb, #valorFobDolar').prop('readonly', false).removeClass('campo-readonly');
        $('#btnAddOrdenCompra').prop('disabled', false).css('opacity', '1');
        
        // Secciones 2 y 3 visibles pero no editables (se calculan automáticamente)
        $('#fechaEtd, #numeroBl, #factura').prop('readonly', true).addClass('campo-readonly');
        $('#tipoCambio, #formaPago, #fechaPago, #fechaDespAdu, #despacho').prop('readonly', true).addClass('campo-readonly');
        $('#fechaArr').prop('readonly', true); // Este siempre es calculado inicialmente
    }
}

/**
 * Valida campos obligatorios de Sección 1 en alta inicial
 */
function validarSeccion1() {
    const proveedor = $('#proveedor').val();
    const ordenProveedor = $('#contenedor').val();
    const material = $('#material').val();
    const origen = $('#origen').val();
    const valorFobDolar = $('#valorFobDolar').val();
    const fechaEmb = $('#fechaEmb').val();
    const ordenesSeleccionadas = $('#ordenesSeleccionadas').children().length;
    
    if (!proveedor || proveedor === 'PROVEEDOR') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe seleccionar un proveedor', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (!ordenProveedor || ordenProveedor.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar el número de orden del proveedor', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (!material || material.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar el material', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (!origen || origen.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar el origen', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (!valorFobDolar || valorFobDolar.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar el valor FOB en dólares', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (!fechaEmb || fechaEmb.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar la fecha estimada de embarque (ETD)', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    if (ordenesSeleccionadas === 0 && !$('#ordenManual').is(':checked')) {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe agregar al menos una orden de compra o marcar orden manual', confirmButtonColor: '#3085d6'});
        return false;
    }
    
    return true;
}

// ========== INICIALIZACIÓN Y EVENT LISTENERS ==========

$(document).ready(function() {
    // Establecer modo inicial (alta)
    establecerModoFormulario(false);
    
    // Configurar manual override
    configurarManualOverride();
    
    // Event listeners para recálculos automáticos
    $('#fechaEmb').on('dp.change', function() {
        recalcularTodasLasFechas();
    });
    
    // Calcular fechas iniciales cuando se ingresa fecha estimada
    $('#fechaEmb').on('change', function() {
        recalcularTodasLasFechas();
    });
    
    // Inicializar datepickers
    $('.js-datepicker-estimada').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });
    
    $('.js-datepicker-etd').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });
    
    $('.js-datepicker-arribo').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });
    
    $('.js-datepicker-pago').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });
    
    $('.js-datepicker-despacho').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });

    // Reemplazar el modal actual con uno más moderno y funcional
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
