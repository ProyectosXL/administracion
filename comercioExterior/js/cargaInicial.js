/**
 * cargaInicial.js - Script para la carga inicial de despachos de importación
 * Maneja cálculos automáticos, validaciones y gestión de órdenes de compra
 */

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

// ========== GESTIÓN DE ÓRDENES DE COMPRA ==========

/**
 * Añadir orden de compra al contenedor visual
 */
function agregarOrdenAlContenedor(orden) {
    const div = document.createElement('div');
    div.id = 'ordenDeCompra';
    
    div.innerHTML = `
        <span style="overflow: hidden; text-overflow: ellipsis;" id="nroOrdenSpan">${orden}</span> 
        <button class="btn-delete" data-orden="${orden}">
            <i class="bi bi-x-circle" style="color:white;"></i>
        </button>
    `;
    
    document.querySelector("#ordenesSeleccionadas").appendChild(div);
    
    // Configurar botón de eliminación
    const deleteButton = div.querySelector('.btn-delete');
    deleteButton.addEventListener('click', function(e) {
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
        }, 300);
    });
}

/**
 * Función para traer orden manual
 */
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

    // ========== MODAL PARA AGREGAR ORDEN DE COMPRA ==========
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
        let ordenesSeleccionadasArray = Array.from(ordenesSeleccionadas).map(el => el.textContent.trim().replace('×', '').trim());
        
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

        // Mostrar modal mejorado
        Swal.fire({
            title: 'Añadir Orden de Compra',
            html: modalHTML,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-check-circle"></i> Guardar',
            cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
            confirmButtonColor: '#7066e0',
            cancelButtonColor: '#6c757d',
            focusConfirm: false,
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
                    agregarOrdenAlContenedor(orden);
                });
                
                return true;
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
            }
        });
    });

    console.log('cargaInicial.js cargado correctamente');
});
