/**
 * cargaInicial.js - Script para la carga inicial de despachos de importación
 * Maneja cálculos automáticos, validaciones y gestión de órdenes de compra
 */

// ========== VARIABLES GLOBALES Y FLAGS ==========
let fechaArriboIsManual = false;
let fechaPagoIsManual = false;
let fechaDespachoIsManual = false;
let cargandoDatos = false; // Flag para evitar marcar como manual durante carga inicial

// ========== FUNCIONES DE CÁLCULO AUTOMÁTICO ==========

/**
 * Obtiene la fecha base según LÓGICA PRIORITARIA:
 * 1️⃣ Si existe Fecha de Embarque (ETD - FECHA_EMB) → usarla
 * 2️⃣ Si NO existe → usar Fecha Estimada de Embarque (FECHA_EST_EMB)
 */
function obtenerFechaBase() {
    // 1️⃣ PRIORIDAD: Fecha de Embarque real ETD (fechaEmb = FECHA_EMB)
    const fechaEmb = $('#fechaEmb').val();
    if (fechaEmb && fechaEmb.trim() !== '') {
        return moment(fechaEmb, 'DD/MM/YYYY');
    }
    
    // 2️⃣ FALLBACK: Fecha Estimada de Embarque (fechaEstEmb = FECHA_EST_EMB)
    const fechaEstEmb = $('#fechaEstEmb').val();
    if (fechaEstEmb && fechaEstEmb.trim() !== '') {
        return moment(fechaEstEmb, 'DD/MM/YYYY');
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
 * Recalcula la Fecha de Arribo - ETA (Fecha base + 45 días)
 * Usa fecha de embarque real si existe, sino fecha estimada
 */
function recalcularFechaArribo() {
    if (fechaArriboIsManual) {
        console.log('Fecha Arribo en modo manual, no se recalcula');
        return; // No recalcular si está en modo manual
    }
    
    const fechaBase = obtenerFechaBase();
    console.log('Recalculando Fecha Arribo. Fecha base:', fechaBase ? fechaBase.format('DD/MM/YYYY') : 'null');
    
    if (fechaBase) {
        const nuevaFechaArribo = sumarDias(fechaBase, 45);
        console.log('Nueva Fecha Arribo calculada:', nuevaFechaArribo);
        $('#fechaArr').val(nuevaFechaArribo);
        marcarCampoCalculado('#fechaArr');
        
        // Al cambiar fecha arribo, recalcular fecha despacho si no es manual
        console.log('Recalculando Fecha Despacho desde recalcularFechaArribo');
        recalcularFechaDespacho();
    } else {
        $('#fechaArr').val('');
    }
}

/**
 * Recalcula la Fecha de Pago (Fecha base + 5 días)
 * Usa fecha de embarque real si existe, sino fecha estimada
 */
function recalcularFechaPago() {
    if (fechaPagoIsManual) {
        console.log('Fecha Pago en modo manual, no se recalcula');
        return;
    }
    
    const fechaBase = obtenerFechaBase();
    console.log('Recalculando Fecha Pago. Fecha base:', fechaBase ? fechaBase.format('DD/MM/YYYY') : 'null');
    
    if (fechaBase) {
        const nuevaFechaPago = sumarDias(fechaBase, 5);
        console.log('Nueva Fecha Pago calculada:', nuevaFechaPago);
        $('#fechaPago').val(nuevaFechaPago);
        marcarCampoCalculado('#fechaPago');
    } else {
        $('#fechaPago').val('');
    }
}

/**
 * Carga los datos de un despacho existente en el formulario (modo edición)
 */
function cargarDatosDespacho(datos) {
    console.log('Cargando datos del despacho:', datos);
    
    // Activar flag de carga para evitar marcar campos como manuales
    cargandoDatos = true;
    
    // Sección 1 - Datos Iniciales
    if (datos.COD_PROVEE) {
        $('#proveedor').val(datos.COD_PROVEE).trigger('change');
    }
    if (datos.CONTENEDOR) $('#contenedor').val(datos.CONTENEDOR);
    if (datos.MATERIAL) $('#material').val(datos.MATERIAL);
    if (datos.ORIGEN) $('#origen').val(datos.ORIGEN);
    if (datos.VALOR_FOB_DOLAR) $('#valorFobDolar').val(datos.VALOR_FOB_DOLAR);
    if (datos.FECHA_EST_EMB) $('#fechaEstEmb').val(datos.FECHA_EST_EMB);
    if (datos.ORDEN_COMPRA) {
        // Cargar órdenes de compra (manejar múltiples si están separadas por coma)
        const ordenes = datos.ORDEN_COMPRA.split(',');
        ordenes.forEach(oc => {
            if (oc.trim()) {
                agregarOrdenAlContenedor(oc.trim());
            }
        });
    }
    if (datos.OCM) $('#ordenManual').prop('checked', datos.OCM === '1' || datos.OCM === 1);
    
    // Sección 2 - Datos de Embarque
    if (datos.FECHA_EMB) {
        $('#fechaEmb').val(datos.FECHA_EMB);
    }
    if (datos.FECHA_ARR) {
        $('#fechaArr').val(datos.FECHA_ARR);
        // Importante: NO marcar como manual, solo cargar el valor
    }
    if (datos.NUMERO_BL) $('#numeroBl').val(datos.NUMERO_BL);
    if (datos.FACTURA) $('#factura').val(datos.FACTURA);
    
    // Sección 3 - Datos Financieros y Aduana
    if (datos.TIPO_CAMBIO) $('#tipoCambio').val(datos.TIPO_CAMBIO);
    if (datos.VALOR_FOB_PESO) $('#valorFobPeso').val(datos.VALOR_FOB_PESO);
    if (datos.FORMA_PAGO) $('#formaPago').val(datos.FORMA_PAGO).trigger('change');
    if (datos.FECHA_PAGO) {
        $('#fechaPago').val(datos.FECHA_PAGO);
    }
    if (datos.FECHA_DESP_ADU) {
        $('#fechaDespAdu').val(datos.FECHA_DESP_ADU);
    }
    if (datos.GASTOS_PUERTO_DOLAR) $('#gastosPuertoDolar').val(datos.GASTOS_PUERTO_DOLAR);
    if (datos.GASTOS_PUERTO_PESO) $('#gastosPuertoPeso').val(datos.GASTOS_PUERTO_PESO);
    if (datos.FLETE_INTERNACIONAL) $('#fleteInternacional').val(datos.FLETE_INTERNACIONAL);
    if (datos.SEGURO) $('#seguro').val(datos.SEGURO);
    if (datos.DERECHOS) $('#derechos').val(datos.DERECHOS);
    if (datos.TASA_ESTADISTICA) $('#tasaEstadistica').val(datos.TASA_ESTADISTICA);
    if (datos.IVA_ADICIONAL) $('#ivaAdicional').val(datos.IVA_ADICIONAL);
    if (datos.GASTO_DESPACHANTE) $('#gastoDespachante').val(datos.GASTO_DESPACHANTE);
    if (datos.ANTICIPO) $('#anticipo').val(datos.ANTICIPO);
    
    // IMPORTANTE: Guardar el número de despacho para cargarlo después de los recálculos
    const numeroDespacho = datos.DESPACHO || null;
    
    // Desactivar flag de carga
    cargandoDatos = false;
    
    console.log('Datos cargados correctamente');
    console.log('Todos los datos recibidos:', datos);
    
    // Cargar el número de despacho DESPUÉS de que se desactive cargandoDatos
    // para que los recálculos no lo sobrescriban
    if (numeroDespacho) {
        console.log('Cargando número de despacho después de recálculos:', numeroDespacho);
        // Usar setTimeout para asegurar que se ejecuta después de los recálculos
        setTimeout(function() {
            $('#despacho').val(numeroDespacho);
            console.log('Número de despacho cargado:', numeroDespacho);
        }, 100);
    }
}

/**
 * Recalcula la Fecha de Nacionalización (FECHA_DESP_ADU = FECHA_ARR + 2 días)
 */
function recalcularFechaDespacho() {
    if (fechaDespachoIsManual) {
        console.log('Fecha Despacho en modo manual, no se recalcula');
        return;
    }
    
    const fechaArr = $('#fechaArr').val();
    console.log('Recalculando Fecha Despacho. Fecha Arribo actual:', fechaArr);
    
    if (fechaArr && fechaArr.trim() !== '') {
        const fechaArriboMoment = moment(fechaArr, 'DD/MM/YYYY');
        if (fechaArriboMoment.isValid()) {
            const nuevaFechaDespacho = sumarDias(fechaArriboMoment, 2);
            console.log('Nueva Fecha Despacho calculada:', nuevaFechaDespacho);
            $('#fechaDespAdu').val(nuevaFechaDespacho);
            marcarCampoCalculado('#fechaDespAdu');
        } else {
            console.log('Fecha Arribo no es válida para moment');
        }
    } else {
        console.log('Fecha Arribo está vacía');
    }
}

/**
 * Recalcula todos los campos de fechas automáticas
 */
function recalcularTodasLasFechas() {
    console.log('=== Iniciando recálculo de todas las fechas ===');
    console.log('Estados manuales - Arribo:', fechaArriboIsManual, 'Pago:', fechaPagoIsManual, 'Despacho:', fechaDespachoIsManual);
    recalcularFechaArribo();
    recalcularFechaPago();
    // No llamar recalcularFechaDespacho aquí porque ya se llama dentro de recalcularFechaArribo
    // recalcularFechaDespacho();
    console.log('=== Fin de recálculo ===');
}

/**
 * Calcula FOB en Pesos = FOB U$S × Tipo de Cambio
 * Se recalcula cada vez que cualquiera de los dos valores cambie
 */
function recalcularFobPesos() {
    const valorFobDolar = parseFloat($('#valorFobDolar').val().replace(/,/g, '')) || 0;
    const tipoCambio = parseFloat($('#tipoCambio').val().replace(/,/g, '')) || 0;
    
    if (valorFobDolar > 0 && tipoCambio > 0) {
        const valorFobPeso = valorFobDolar * tipoCambio;
        $('#valorFobPeso').val(valorFobPeso.toFixed(2));
        marcarCampoCalculado('#valorFobPeso');
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
        if (!cargandoDatos && e.date && $(this).val().trim() !== '') {
            console.log('Usuario modificó Fecha Arribo manualmente');
            fechaArriboIsManual = true;
            marcarCampoManual('#fechaArr');
            // Al cambiar manualmente fecha arribo, recalcular despacho si no es manual
            recalcularFechaDespacho();
        }
    });
    verificarCampoVacio('#fechaArr', fechaArriboIsManual, 'fechaArriboIsManual');

    // Fecha Pago
    $('#fechaPago').on('dp.change', function(e) {
        if (!cargandoDatos && e.date && $(this).val().trim() !== '') {
            console.log('Usuario modificó Fecha Pago manualmente');
            fechaPagoIsManual = true;
            marcarCampoManual('#fechaPago');
        }
    });
    verificarCampoVacio('#fechaPago', fechaPagoIsManual, 'fechaPagoIsManual');

    // Fecha Nacionalización (FECHA_DESP_ADU)
    $('#fechaDespAdu').on('dp.change', function(e) {
        if (!cargandoDatos && e.date && $(this).val().trim() !== '') {
            console.log('Usuario modificó Fecha Despacho manualmente');
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
        $('#fechaEstEmb').prop('readonly', true).addClass('campo-readonly');
        $('#btnAddOrdenCompra').prop('disabled', true).css('opacity', '0.5');
        
        // Valor FOB U$S sigue editable
        $('#valorFobDolar').prop('readonly', false).removeClass('campo-readonly');
        
        // Secciones 2 y 3 editables
        $('#fechaEmb, #numeroBl, #factura, #fechaArr').prop('readonly', false).removeClass('campo-readonly');
        $('#tipoCambio, #formaPago, #fechaPago, #fechaDespAdu, #despacho').prop('readonly', false).removeClass('campo-readonly');
        
    } else {
        // MODO ALTA INICIAL: Sección 1 obligatoria y editable
        $('#proveedor').prop('disabled', false).removeClass('campo-readonly');
        $('#contenedor, #material, #origen, #fechaEstEmb, #valorFobDolar').prop('readonly', false).removeClass('campo-readonly');
        $('#btnAddOrdenCompra').prop('disabled', false).css('opacity', '1');
        
        // Secciones 2 y 3 visibles pero no editables (se calculan automáticamente)
        $('#fechaEmb, #numeroBl, #factura').prop('readonly', true).addClass('campo-readonly');
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
    const fechaEstEmb = $('#fechaEstEmb').val();
    const ordenesSeleccionadas = $('#ordenesSeleccionadas').children().length;
    
    console.log('Validando Sección 1:', {
        proveedor, ordenProveedor, material, origen, valorFobDolar, fechaEstEmb, ordenesSeleccionadas
    });
    
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
    
    if (!fechaEstEmb || fechaEstEmb.trim() === '') {
        Swal.fire({icon: 'error', title: 'Error', text: 'Debe ingresar la fecha estimada de embarque', confirmButtonColor: '#3085d6'});
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
    const modoEdicion = $('#modoEdicion').val() === 'true';
    const div = document.createElement('div');
    div.id = 'ordenDeCompra';
    
    // En modo edición, no mostrar botón de eliminar
    if (modoEdicion) {
        div.innerHTML = `
            <span style="overflow: hidden; text-overflow: ellipsis;" id="nroOrdenSpan">${orden}</span>
        `;
    } else {
        div.innerHTML = `
            <span style="overflow: hidden; text-overflow: ellipsis;" id="nroOrdenSpan">${orden}</span> 
            <button class="btn-delete" data-orden="${orden}">
                <i class="bi bi-x-circle" style="color:white;"></i>
            </button>
        `;
    }
    
    document.querySelector("#ordenesSeleccionadas").appendChild(div);
    
    // Solo configurar botón de eliminación si NO estamos en modo edición
    if (!modoEdicion) {
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
    // Verificar si estamos en modo edición
    const modoEdicion = $('#modoEdicion').val() === 'true';
    
    if (modoEdicion && typeof datosDespacho !== 'undefined' && datosDespacho) {
        // MODO EDICIÓN - Cargar datos existentes
        cargarDatosDespacho(datosDespacho);
        establecerModoFormulario(true);
    } else {
        // MODO ALTA - Establecer modo inicial
        establecerModoFormulario(false);
    }
    
    // IMPORTANTE: Siempre limpiar campo ETD al inicio (en modo alta debe estar vacío)
    if (!modoEdicion) {
        $('#fechaEmb').val('');
        console.log('Campo FECHA_EMB limpiado en modo alta');
    }
    
    // Configurar manual override
    configurarManualOverride();
    
    // Ejecutar cálculos iniciales si hay datos precargados
    recalcularTodasLasFechas();
    recalcularFobPesos();
    
    // Event listeners para recálculos automáticos de fechas
    
    // Cuando cambia Fecha Estimada de Embarque (FECHA_EST_EMB)
    $('#fechaEstEmb').on('dp.change', function() {
        recalcularTodasLasFechas();
    });
    
    $('#fechaEstEmb').on('change', function() {
        recalcularTodasLasFechas();
    });
    
    // Cuando cambia Fecha de Embarque real ETD (FECHA_EMB) - PRIORIDAD MÁXIMA
    $('#fechaEmb').on('dp.change', function() {
        // Al cambiar la fecha real, recalcular TODOS los campos que dependan
        // y que NO estén en modo manual
        recalcularTodasLasFechas();
    });
    
    $('#fechaEmb').on('change', function() {
        recalcularTodasLasFechas();
    });
    
    // Event listeners para cálculo de FOB en Pesos
    $('#valorFobDolar').on('input change', function() {
        recalcularFobPesos();
    });
    
    $('#tipoCambio').on('input change', function() {
        recalcularFobPesos();
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
        autoUpdateInput: false,
        locale: {format: 'DD/MM/YYYY'}
    }).on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY'));
        console.log('Fecha ETD seleccionada:', picker.startDate.format('DD/MM/YYYY'));
        // Recalcular todas las fechas dependientes
        recalcularTodasLasFechas();
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

// ========== FUNCIÓN DE GUARDADO ==========

/**
 * Guarda los datos del despacho en la base de datos
 */
function guardarCabecera() {
    // Si es modo alta inicial, validar solo Sección 1
    const modoEdicion = document.getElementById('modoEdicion').value === 'true';
    
    if (!modoEdicion) {
        // Validar campos obligatorios de Sección 1
        if (!validarSeccion1()) {
            return;
        }
    }

    // Mostrar confirmación antes de procesar
    Swal.fire({
        title: '¿Desea guardar los cambios?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#7066e0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        // Capturar datos del formulario
        const cod_proveedor = $('#proveedor').val();
        const proveedor = $('#proveedor option:selected').text();
        const contenedor = $('#contenedor').val();
        const material = $('#material').val();
        const origen = $('#origen').val();
        const valorFobDolar = $('#valorFobDolar').val();
        const fechaEstEmb = $('#fechaEstEmb').val();
        
        // Órdenes de compra
        let ocm = $('#ordenManual').is(':checked') ? 1 : 0;
        let ordenCompra = [];
        $('#nroOrdenSpan').each(function() {
            ordenCompra.push($(this).text().trim().split(" ")[0]);
        });
        
        // Sección 2 - Datos de Embarque
        const fechaEmb = $('#fechaEmb').val();
        const numeroBl = $('#numeroBl').val();
        const factura = $('#factura').val();
        const fechaArr = $('#fechaArr').val();
        
        // Sección 3 - Datos Financieros y Aduana
        const tipoCambio = $('#tipoCambio').val();
        const valorFobPeso = $('#valorFobPeso').val();
        const formaPago = $('#formaPago').val();
        const fechaPago = $('#fechaPago').val();
        const fechaDespAdu = $('#fechaDespAdu').val();
        const despacho = $('#despacho').val();
 
        // Preparar datos para enviar
        const dataToSend = {
            // Si es edición, incluir ID
            id: modoEdicion ? $('#idDespacho').val() : null,
            modoEdicion: modoEdicion,
            
            // Sección 1 - Datos Iniciales
            cod_proveedor: cod_proveedor,
            proveedor: proveedor,
            contenedor: contenedor,
            material: material,
            origen: origen,
            valorFobDolar: valorFobDolar.replace(/,/g, ""),
            fechaEstEmb: fechaEstEmb,
            ordenCompra: JSON.stringify(ordenCompra),
            ocm: ocm,
            
            // Campos calculados automáticamente
            fechaArr: fechaArr,
            fechaPago: fechaPago,
            fechaDespAdu: fechaDespAdu,
            
            // Sección 2 - Datos de Embarque (solo enviar en modo edición)
            fechaEmb: (modoEdicion && fechaEmb) ? fechaEmb : '',
            numeroBl: numeroBl,
            factura: factura,
            
            // Sección 3 - Datos Financieros y Aduana
            tipoCambio: tipoCambio.replace(/,/g, ""),
            valorFobPeso: valorFobPeso.replace(/,/g, ""),
            formaPago: formaPago,
            despacho: despacho
        };
        
        console.log('Datos a enviar:', dataToSend);
        
        // Enviar datos al servidor
        $.ajax({
            url: 'Controller/insertarEncabezado.php',
            method: 'POST',
            dataType: 'json',
            data: dataToSend,
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                
                if (response.success) {
                    Swal.fire({
                        title: '¡Despacho guardado correctamente!',
                        text: response.message || 'Los datos han sido guardados exitosamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#7066e0'
                    }).then(function () {
                        // Si estamos en modo edición, regresar a la lista de pendientes
                        if (modoEdicion) {
                            window.location.href = 'gestionDespachos.php';
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error al guardar',
                        text: response.message || 'Ocurrió un error al guardar el despacho',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al guardar:', error);
                console.error('Respuesta del servidor:', xhr.responseText);
                
                let errorMessage = 'Error al conectar con el servidor';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    errorMessage = xhr.responseText || errorMessage;
                }
                
                Swal.fire({
                    title: 'Error',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#d33'
                });
            }
        });
    });
}
