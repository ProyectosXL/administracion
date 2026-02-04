/**
 * cargaInicial.js - Script para la carga inicial de despachos de importación
 * Maneja cálculos automáticos, validaciones y gestión de órdenes de compra
 */

// ========== VARIABLES GLOBALES Y FLAGS ==========
let fechaArriboIsManual = false;
let fechaPagoIsManual = false;
let fechaDespachoIsManual = false;
let fechaEstPagoIsManual = false; // Flag para fecha estimada de pago
let cargandoDatos = false; // Flag para evitar marcar como manual durante carga inicial

// ========== FUNCIONES DE VALIDACIÓN DE FECHAS HÁBILES ==========

/**
 * Convierte fecha DD/MM/YYYY a YYYY-MM-DD
 */
function convertirFechaAISO(fechaDDMMYYYY) {
    if (!fechaDDMMYYYY) return '';
    const partes = fechaDDMMYYYY.split('/');
    if (partes.length === 3) {
        return `${partes[2]}-${partes[1]}-${partes[0]}`;
    }
    return fechaDDMMYYYY;
}

/**
 * Convierte fecha YYYY-MM-DD a DD/MM/YYYY
 */
function convertirFechaADDMMYYYY(fechaISO) {
    if (!fechaISO) return '';
    const partes = fechaISO.split('-');
    if (partes.length === 3) {
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }
    return fechaISO;
}

/**
 * Verifica si una fecha es día hábil (no fin de semana ni feriado)
 * @param {string} fecha - Fecha en formato DD/MM/YYYY o YYYY-MM-DD
 * @return {boolean}
 */
function esDiaHabil(fecha) {
    if (!fecha) return true;
    
    // Convertir a formato ISO si viene en DD/MM/YYYY
    let fechaISO = fecha.includes('/') ? convertirFechaAISO(fecha) : fecha;
    
    const date = new Date(fechaISO + 'T00:00:00');
    const diaSemana = date.getDay();  // 0=Domingo, 6=Sábado
    
    // Verificar fin de semana
    if (diaSemana === 0 || diaSemana === 6) {
        return false;
    }
    
    // Verificar feriado
    return !feriadosArgentinos.includes(fechaISO);
}

/**
 * Obtiene el motivo por el cual no es día hábil
 * @param {string} fecha - Fecha en formato DD/MM/YYYY o YYYY-MM-DD
 * @return {string}
 */
function obtenerMotivoNoHabil(fecha) {
    if (!fecha) return '';
    
    let fechaISO = fecha.includes('/') ? convertirFechaAISO(fecha) : fecha;
    const date = new Date(fechaISO + 'T00:00:00');
    const diaSemana = date.getDay();
    
    if (diaSemana === 0) return "Domingo";
    if (diaSemana === 6) return "Sábado";
    if (feriadosArgentinos.includes(fechaISO)) return "Feriado";
    
    return "Día no hábil";
}

/**
 * Obtiene el siguiente día hábil
 * @param {string} fecha - Fecha en formato DD/MM/YYYY o YYYY-MM-DD
 * @return {string} Siguiente día hábil en formato original
 */
function obtenerSiguienteDiaHabil(fecha) {
    if (!fecha) return '';
    
    const formatoOriginal = fecha.includes('/') ? 'DD/MM/YYYY' : 'YYYY-MM-DD';
    let fechaISO = fecha.includes('/') ? convertirFechaAISO(fecha) : fecha;
    
    let date = new Date(fechaISO + 'T00:00:00');
    let intentos = 0;
    const maxIntentos = 15;
    
    while (intentos < maxIntentos) {
        date.setDate(date.getDate() + 1);
        const fechaStr = formatearFechaISO(date);
        
        if (esDiaHabil(fechaStr)) {
            return formatoOriginal === 'DD/MM/YYYY' ? convertirFechaADDMMYYYY(fechaStr) : fechaStr;
        }
        
        intentos++;
    }
    
    return fecha;
}

/**
 * Formatea fecha como YYYY-MM-DD
 */
function formatearFechaISO(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Valida campo de fecha y muestra advertencia si no es día hábil
 * Cambia automáticamente a fecha sugerida y muestra alerta informativa
 * @param {jQuery} $campo - Campo jQuery a validar
 */
function validarCampoFechaHabil($campo) {
    const fecha = $campo.val();
    
    if (!fecha || cargandoDatos) return;
    
    // Verificar si el campo tiene override
    if ($campo.data('override-fecha-habil')) {
        return;
    }
    
    if (!esDiaHabil(fecha)) {
        const motivo = obtenerMotivoNoHabil(fecha);
        const fechaSugerida = obtenerSiguienteDiaHabil(fecha);
        
        // Cambiar automáticamente a la fecha sugerida
        $campo.val(fechaSugerida);
        
        // Sincronizar datepicker con la nueva fecha
        const $picker = $campo.data('daterangepicker');
        if ($picker && fechaSugerida) {
            const fechaMoment = moment(fechaSugerida, 'DD/MM/YYYY');
            if (fechaMoment.isValid()) {
                $picker.setStartDate(fechaMoment);
                $picker.setEndDate(fechaMoment);
            }
        }
        
        // Mostrar alerta informativa
        mostrarAdvertenciaFecha($campo, fecha, fechaSugerida, motivo);
        
        // Disparar recálculos dependientes según el campo
        const campoId = $campo.attr('id');
        if (campoId === 'fechaArr' && !fechaDespachoIsManual) {
            // Si cambió fecha arribo, recalcular fecha despacho
            recalcularFechaDespacho();
        }
    } else {
        ocultarAdvertenciaFecha($campo);
    }
}

/**
 * Muestra advertencia informativa de cambio automático de fecha
 */
function mostrarAdvertenciaFecha($campo, fechaOriginal, fechaSugerida, motivo) {
    // Remover advertencia previa si existe
    ocultarAdvertenciaFecha($campo);
    
    const campoId = $campo.attr('id');
    const mensaje = `
        <div class="alerta-fecha-no-habil" data-campo="${campoId}">
            <i class="bi bi-info-circle"></i>
            La fecha <strong>${fechaOriginal}</strong> es <strong>${motivo}</strong>.
            <br>
            Se cambió automáticamente a: <strong>${fechaSugerida}</strong>
            <small style="display: block; margin-top: 5px; opacity: 0.8;">
                Puede modificarla manualmente si lo desea. Click para cerrar.
            </small>
        </div>
    `;
    
    $campo.after(mensaje);
}

/**
 * Oculta advertencia de fecha no hábil
 */
function ocultarAdvertenciaFecha($campo) {
    $campo.next('.alerta-fecha-no-habil').remove();
    $campo.removeClass('campo-advertencia-fecha');
}

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
        
        // Sincronizar datepicker
        const $picker = $('#fechaArr').data('daterangepicker');
        if ($picker && nuevaFechaArribo) {
            $picker.setStartDate(moment(nuevaFechaArribo, 'DD/MM/YYYY'));
            $picker.setEndDate(moment(nuevaFechaArribo, 'DD/MM/YYYY'));
        }
        
        marcarCampoCalculado('#fechaArr');
        
        // Validar fecha hábil después de recalcular (si no estamos cargando datos)
        if (!cargandoDatos) {
            setTimeout(() => validarCampoFechaHabil($('#fechaArr')), 100);
        }
        
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
 * Recalcula la Fecha Estimada de Pago (Fecha base + 5 días)
 * Usa fecha de embarque real si existe, sino fecha estimada
 */
function recalcularFechaEstimadaPago() {
    if (fechaEstPagoIsManual) {
        console.log('Fecha Est. Pago en modo manual, no se recalcula');
        return;
    }
    
    const fechaBase = obtenerFechaBase();
    console.log('Recalculando Fecha Est. Pago. Fecha base:', fechaBase ? fechaBase.format('DD/MM/YYYY') : 'null');
    
    if (fechaBase) {
        const nuevaFechaEstPago = sumarDias(fechaBase, 5);
        console.log('Nueva Fecha Est. Pago calculada:', nuevaFechaEstPago);
        $('#fechaEstPago').val(nuevaFechaEstPago);
        
        // Sincronizar datepicker
        const $picker = $('#fechaEstPago').data('daterangepicker');
        if ($picker && nuevaFechaEstPago) {
            $picker.setStartDate(moment(nuevaFechaEstPago, 'DD/MM/YYYY'));
            $picker.setEndDate(moment(nuevaFechaEstPago, 'DD/MM/YYYY'));
        }
        
        marcarCampoCalculado('#fechaEstPago');
        
        // Validar fecha hábil después de recalcular (si no estamos cargando datos)
        if (!cargandoDatos) {
            setTimeout(() => validarCampoFechaHabil($('#fechaEstPago')), 100);
        }
    } else {
        $('#fechaEstPago').val('');
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
    if (datos.DESPACHANTE) {
        $('#despachante').val(datos.DESPACHANTE).trigger('change');
    }
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
        // Marcar como manual para evitar recálculo
        fechaArriboIsManual = true;
    }
    
    // ETA Confirmada - checkbox
    if (datos.ETA_CONFIRMADA) {
        $('#etaConfirmada').prop('checked', datos.ETA_CONFIRMADA === 1 || datos.ETA_CONFIRMADA === '1');
    }
    if (datos.NUMERO_BL) $('#numeroBl').val(datos.NUMERO_BL);
    if (datos.FACTURA) $('#factura').val(datos.FACTURA);
    if (datos.PUERTO_ORIGEN) {
        $('#puertoOrigen').val(datos.PUERTO_ORIGEN).trigger('change');
        console.log('Puerto Origen cargado:', datos.PUERTO_ORIGEN);
    }
    if (datos.TERMINAL) {
        $('#terminal').val(datos.TERMINAL).trigger('change');
        console.log('Terminal cargado:', datos.TERMINAL);
    }
    
    // Sección 3 - Datos Financieros y Aduana
    if (datos.TIPO_CAMBIO) $('#tipoCambio').val(datos.TIPO_CAMBIO);
    if (datos.VALOR_FOB_PESO) {
        const valorFormateado = '$ ' + parseFloat(datos.VALOR_FOB_PESO).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        $('#valorFobPeso').val(valorFormateado);
    }
    if (datos.FORMA_PAGO) $('#formaPago').val(datos.FORMA_PAGO).trigger('change');
    if (datos.FECHA_PAGO) {
        $('#fechaPago').val(datos.FECHA_PAGO);
    }
    if (datos.FECHA_EST_PAGO) {
        $('#fechaEstPago').val(datos.FECHA_EST_PAGO);
        // Sincronizar datepicker
        if ($('.js-datepicker-est-pago').data('daterangepicker')) {
            const fecha = moment(datos.FECHA_EST_PAGO, 'DD/MM/YYYY');
            $('.js-datepicker-est-pago').data('daterangepicker').setStartDate(fecha);
            $('.js-datepicker-est-pago').data('daterangepicker').setEndDate(fecha);
        }
        // NO marcar como manual - es solo carga de datos de BD
        // El flag manual se activará solo cuando el usuario lo edite después
        fechaEstPagoIsManual = false;
    }
    if (datos.FECHA_DESP_ADU) {
        $('#fechaDespAdu').val(datos.FECHA_DESP_ADU);
        // Sincronizar datepicker
        if ($('.js-datepicker-despacho').data('daterangepicker')) {
            const fecha = moment(datos.FECHA_DESP_ADU, 'DD/MM/YYYY');
            $('.js-datepicker-despacho').data('daterangepicker').setStartDate(fecha);
            $('.js-datepicker-despacho').data('daterangepicker').setEndDate(fecha);
        }
        // Marcar como manual para evitar recálculo
        fechaDespachoIsManual = true;
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
    
    // Sincronizar datepickers con las fechas cargadas desde BD
    sincronizarDatepickers();
    
    console.log('Datos cargados correctamente');
    console.log('Todos los datos recibidos:', datos);
    
    // Cargar pagos si hay ID de despacho
    const idDespacho = $('#idDespacho').val();
    if (idDespacho) {
        cargarPagos(idDespacho);
    }
    
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
 * Carga los pagos del despacho desde BD
 */
function cargarPagos(idDespacho) {
    console.log('Cargando pagos del despacho:', idDespacho);
    
    $.ajax({
        url: '../controller/traerPagosController.php',
        method: 'POST',
        data: { id_despacho: idDespacho },
        dataType: 'json',
        success: function(response) {
            console.log('Pagos cargados:', response);
            if (response && response.pagos && response.pagos.length > 0) {
                renderizarTablaPagos(response.pagos);
                // Actualizar saldo pendiente
                if (response.saldoPendiente !== undefined) {
                    actualizarSaldoPendiente(response.saldoPendiente);
                }
            } else {
                console.log('No hay pagos para este despacho');
                $('#tbodyPagos').html('');
            }
        },
        error: function(err) {
            console.error('Error al cargar pagos:', err);
        }
    });
}

/**
 * Renderiza la tabla de pagos
 */
function renderizarTablaPagos(pagos) {
    const tbody = $('#tbodyPagos');
    tbody.html(''); // Limpiar tabla
    
    if (!pagos || pagos.length === 0) {
        // Mostrar mensaje "No hay pagos"
        $('#sinPagos').show();
        return;
    }
    
    // Ocultar mensaje "No hay pagos"
    $('#sinPagos').hide();
    
    pagos.forEach(pago => {
        const fila = `
            <tr>
                <td>
                    <a href="#" class="link-primary" data-bs-toggle="modal" data-bs-target="#modalEditarFechaPago" onclick="abrirModalEditarFecha(${pago.ID}, '${pago.FECHA_PAGO}')">
                        ${pago.FECHA_PAGO || '-'}
                        <i class="bi bi-pencil-square ms-1"></i>
                    </a>
                </td>
                <td>${pago.FORMA_PAGO || '-'}</td>
                <td>${pago.MEDIO_PAGO || '-'}</td>
                <td class="text-end">$${parseFloat(pago.MONTO || 0).toLocaleString('es-AR', {minimumFractionDigits: 2})}</td>
                <td style="text-align: center;"><button class="btn btn-sm btn-danger" onclick="eliminarPago(${pago.ID})"><i class="bi bi-trash"></i></button></td>
            </tr>
        `;
        tbody.append(fila);
    });
}

/**
 * Abre modal para editar fecha de pago
 */
function abrirModalEditarFecha(idPago, fechaActual) {
    $('#idPagoEdit').val(idPago);
    $('#fechaPagoEdit').val(fechaActual);
    console.log('Editando pago ID:', idPago, 'Fecha:', fechaActual);
    
    // Inicializar datepicker del modal si no está inicializado
    if (!$('.js-datepicker-edit-fecha').data('daterangepicker')) {
        $('.js-datepicker-edit-fecha').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            autoApply: true,
            locale: {format: 'DD/MM/YYYY'}
        });
    }
    
    // Establecer la fecha en el datepicker
    const picker = $('.js-datepicker-edit-fecha').data('daterangepicker');
    if (picker) {
        const fecha = moment(fechaActual, 'DD/MM/YYYY');
        if (fecha.isValid()) {
            picker.setStartDate(fecha);
            picker.setEndDate(fecha);
        }
    }
}

/**
 * Guarda la fecha de pago editada
 */
function guardarFechaPago() {
    const idPago = $('#idPagoEdit').val();
    const nuevaFecha = $('#fechaPagoEdit').val();
    
    if (!idPago || !nuevaFecha) {
        Swal.fire('Error', 'Fecha requerida', 'error');
        return;
    }
    
    $.ajax({
        url: '../controller/actualizarFechaPagoController.php',
        method: 'POST',
        data: {
            id_pago: idPago,
            fecha_pago: nuevaFecha
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire('Éxito', 'Fecha actualizada correctamente', 'success');
                $('#modalEditarFechaPago').modal('hide');
                // Recargar pagos
                const idDespacho = $('#idDespacho').val();
                cargarPagos(idDespacho);
            } else {
                Swal.fire('Error', response.error || 'Error al actualizar', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error en la solicitud', 'error');
        }
    });
}

/**
 * Actualiza el saldo pendiente
 */
function actualizarSaldoPendiente(saldo) {
    $('#saldoPendiente').text('$ ' + parseFloat(saldo).toLocaleString('es-AR', {minimumFractionDigits: 2}));
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
            
            // Sincronizar datepicker
            const $picker = $('#fechaDespAdu').data('daterangepicker');
            if ($picker && nuevaFechaDespacho) {
                $picker.setStartDate(moment(nuevaFechaDespacho, 'DD/MM/YYYY'));
                $picker.setEndDate(moment(nuevaFechaDespacho, 'DD/MM/YYYY'));
            }
            
            marcarCampoCalculado('#fechaDespAdu');
            
            // Validar fecha hábil después de recalcular (si no estamos cargando datos)
            if (!cargandoDatos) {
                setTimeout(() => validarCampoFechaHabil($('#fechaDespAdu')), 100);
            }
        } else {
            console.log('Fecha Arribo no es válida para moment');
        }
    } else {
        console.log('Fecha Arribo está vacía');
    }
}

/**
 * Sincroniza todos los datepickers con los valores actuales de los inputs
 * Se usa después de cargar datos desde BD
 */
function sincronizarDatepickers() {
    console.log('Sincronizando datepickers con valores de inputs...');
    
    // Fecha Estimada de Embarque
    const fechaEstEmb = $('#fechaEstEmb').val();
    if (fechaEstEmb) {
        const $picker = $('#fechaEstEmb').data('daterangepicker');
        if ($picker) {
            const fecha = moment(fechaEstEmb, 'DD/MM/YYYY');
            if (fecha.isValid()) {
                $picker.setStartDate(fecha);
                $picker.setEndDate(fecha);
                console.log('Fecha Est. Embarque sincronizada:', fechaEstEmb);
            }
        }
    }
    
    // Fecha de Embarque (ETD)
    const fechaEmb = $('#fechaEmb').val();
    if (fechaEmb) {
        const $picker = $('#fechaEmb').data('daterangepicker');
        if ($picker) {
            const fecha = moment(fechaEmb, 'DD/MM/YYYY');
            if (fecha.isValid()) {
                $picker.setStartDate(fecha);
                $picker.setEndDate(fecha);
                console.log('Fecha Embarque (ETD) sincronizada:', fechaEmb);
            }
        }
    }
    
    // Fecha Arribo (ETA)
    const fechaArr = $('#fechaArr').val();
    if (fechaArr) {
        const $picker = $('#fechaArr').data('daterangepicker');
        if ($picker) {
            const fecha = moment(fechaArr, 'DD/MM/YYYY');
            if (fecha.isValid()) {
                $picker.setStartDate(fecha);
                $picker.setEndDate(fecha);
                console.log('Fecha Arribo sincronizada:', fechaArr);
            }
        }
    }
    
    // Fecha Estimada de Pago
    const fechaEstPago = $('#fechaEstPago').val();
    if (fechaEstPago) {
        const $picker = $('#fechaEstPago').data('daterangepicker');
        if ($picker) {
            const fecha = moment(fechaEstPago, 'DD/MM/YYYY');
            if (fecha.isValid()) {
                $picker.setStartDate(fecha);
                $picker.setEndDate(fecha);
                console.log('Fecha Est. Pago sincronizada:', fechaEstPago);
            }
        }
    }
    
    // Fecha de Nacionalización
    const fechaDespAdu = $('#fechaDespAdu').val();
    if (fechaDespAdu) {
        const $picker = $('#fechaDespAdu').data('daterangepicker');
        if ($picker) {
            const fecha = moment(fechaDespAdu, 'DD/MM/YYYY');
            if (fecha.isValid()) {
                $picker.setStartDate(fecha);
                $picker.setEndDate(fecha);
                console.log('Fecha Nacionalización sincronizada:', fechaDespAdu);
            }
        }
    }
    
    console.log('Sincronización de datepickers completada');
}

/**
 * Recalcula todos los campos de fechas automáticas
 */
function recalcularTodasLasFechas() {
    console.log('=== Iniciando recálculo de todas las fechas ===');
    console.log('Estados manuales - Arribo:', fechaArriboIsManual, 'Pago:', fechaPagoIsManual, 'Despacho:', fechaDespachoIsManual, 'Est. Pago:', fechaEstPagoIsManual);
    recalcularFechaArribo();
    recalcularFechaPago();
    recalcularFechaEstimadaPago();
    // No llamar recalcularFechaDespacho aquí porque ya se llama dentro de recalcularFechaArribo
    // recalcularFechaDespacho();
    console.log('=== Fin de recálculo ===');
}

/**
 * Calcula FOB en Pesos = FOB U$S × Tipo de Cambio
 * Se recalcula cada vez que cualquiera de los dos valores cambie
 * En Uruguay: FOB Peso = FOB Dólar (mismo valor, ya que se ingresa en pesos uruguayos)
 */
/**
 * Calcula FOB en Pesos = FOB U$S × Tipo de Cambio
 */
/**
 * Calcula FOB en Pesos = FOB U$S × Tipo de Cambio
 * Soporta inputs con punto (1452.50) o coma (1452,50)
 */
function recalcularFobPesos() {
    const entorno = $('#entorno').text().trim();
    
    // Función robusta para parsear números
    const parsearNumeroHibrido = (valor) => {
        if (!valor) return 0;
        let str = valor.toString().replace('$', '').replace(/\s/g, '');
        
        // Si tiene coma, es formato AR: borrar puntos, cambiar coma a punto
        if (str.includes(',')) {
            str = str.replace(/\./g, ''); // Borrar miles
            str = str.replace(',', '.');  // Decimal
        }
        // Si NO tiene coma, asumimos que el punto (si existe) ya es decimal
        // (No hacemos replace del punto)
        
        return parseFloat(str) || 0;
    };

    const valorFobDolar = parsearNumeroHibrido($('#valorFobDolar').val());
    
    if (entorno === 'uy') {
        if (valorFobDolar > 0) {
            const valorFormateado = '$ ' + valorFobDolar.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            $('#valorFobPeso').val(valorFormateado);
            marcarCampoCalculado('#valorFobPeso');
        } else {
            $('#valorFobPeso').val('');
        }
    } else {
        const tipoCambio = parsearNumeroHibrido($('#tipoCambio').val());
        
        if (valorFobDolar > 0 && tipoCambio > 0) {
            const valorFobPeso = valorFobDolar * tipoCambio;
            // Formatear para mostrar en pantalla (siempre muestra con coma decimal)
            const valorFormateado = '$ ' + valorFobPeso.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            $('#valorFobPeso').val(valorFormateado);
            marcarCampoCalculado('#valorFobPeso');
        } else {
            $('#valorFobPeso').val('');
        }
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
        // Para select2, deshabilitar el select y el contenedor
        $('#despachante').prop('disabled', true).addClass('campo-readonly');
        $('#despachante').next('.select-dropdown').find('.select2-container').css({
            'pointer-events': 'none',
            'opacity': '0.6',
            'background-color': '#e9ecef'
        });
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
        // Para select2, habilitar el select y el contenedor
        $('#despachante').prop('disabled', false).removeClass('campo-readonly');
        $('#despachante').next('.select-dropdown').find('.select2-container').css({
            'pointer-events': 'auto',
            'opacity': '1',
            'background-color': ''
        });
        $('#btnAddOrdenCompra').prop('disabled', false).css('opacity', '1');
        
        // Secciones 2 y 3 visibles pero no editables (se calculan automáticamente)
        $('#fechaEmb, #numeroBl, #factura').prop('readonly', true).addClass('campo-readonly');
        $('#tipoCambio, #fechaEstPago, #fechaDespAdu, #despacho').prop('readonly', true).addClass('campo-readonly');
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
    div.className = 'orden-de-compra-item';
    div.id = 'ordenDeCompra';
    
    // Asegurarse de que el texto de la orden sea limpio
    const ordenLimpia = orden.trim();
    
    // En modo edición, no mostrar botón de eliminar
    if (modoEdicion) {
        div.innerHTML = `
            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                  id="nroOrdenSpan">${ordenLimpia}</span>
        `;
    } else {
        div.innerHTML = `
            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                  id="nroOrdenSpan">${ordenLimpia}</span> 
            <button class="btn-delete" data-orden="${ordenLimpia}">
                <i class="bi bi-x-circle" style="color:white;"></i>
            </button>
        `;
    }
    
    const contenedor = document.querySelector("#ordenesSeleccionadas");
    contenedor.appendChild(div);
    
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
            url: '../controller/traerOrdenManualController.php',
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

/**
 * Inicializa todos los datepickers del formulario
 */
function inicializarDatepickers() {
    console.log('=== Inicializando Datepickers ===');
    
    // Fecha Estimada de Embarque
    $('.js-datepicker-estimada').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });
    
    // Fecha de Embarque Real (ETD)
    $('.js-datepicker-etd').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        autoUpdateInput: false,
        locale: {format: 'DD/MM/YYYY'}
    }).on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY'));
        console.log('Fecha ETD seleccionada:', picker.startDate.format('DD/MM/YYYY'));
        recalcularTodasLasFechas();
    });
    
    // Fecha de Arribo (ETA)
    $('.js-datepicker-arribo').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    }).on('apply.daterangepicker', function(ev, picker) {
        setTimeout(() => validarCampoFechaHabil($(this)), 100);
    });
    
    // Fecha de Pago
    $('.js-datepicker-pago').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });
    
    // Fecha de Nacionalización (Despacho)
    $('.js-datepicker-despacho').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    }).on('apply.daterangepicker', function(ev, picker) {
        setTimeout(() => validarCampoFechaHabil($(this)), 100);
    });
    
    // Fecha Estimada de Pago
    console.log('Inicializando datepicker para .js-datepicker-est-pago');
    $('.js-datepicker-est-pago').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    }).on('apply.daterangepicker', function(ev, picker) {
        console.log('Evento apply.daterangepicker en fechaEstPago');
        if (!cargandoDatos) {
            fechaEstPagoIsManual = true;
            console.log('Fecha Est. Pago - Manual Override ACTIVADO');
        }
        setTimeout(() => validarCampoFechaHabil($(this)), 100);
    });
    
    // Event listeners para cambios en fechas base que afectan fechaEstPago
    $(document).on('apply.daterangepicker', '.js-datepicker-estimada', function(ev, picker) {
        console.log('FECHA_EST_EMB cambió - recalculando fechaEstPago');
        recalcularFechaEstimadaPago();
    });
    
    $(document).on('apply.daterangepicker', '.js-datepicker-etd', function(ev, picker) {
        console.log('FECHA_EMB (ETD) cambió - resetear manual flag y recalcular fechaEstPago');
        fechaEstPagoIsManual = false;
        recalcularFechaEstimadaPago();
    });
    
    // Event listener para cerrar alerta informativa
    $(document).on('click', '.alerta-fecha-no-habil', function() {
        const campoId = $(this).data('campo');
        const $campo = $('#' + campoId);
        ocultarAdvertenciaFecha($campo);
    });
    
    // Checkbox ETA Confirmada
    $(document).on('change', '#etaConfirmada', function() {
        const $fechaArr = $('#fechaArr');
        if ($(this).is(':checked')) {
            $fechaArr.css({
                'border-left': '3px solid #28a745',
                'background-color': '#f0f9f0'
            });
        } else {
            $fechaArr.css({
                'border-left': '',
                'background-color': ''
            });
        }
    });
    
    // Aplicar estilo inicial si está marcado
    if ($('#etaConfirmada').is(':checked')) {
        $('#fechaArr').css({
            'border-left': '3px solid #28a745',
            'background-color': '#f0f9f0'
        });
    }
    
    // Datepicker para modal de Agregar Pago
    $('.js-datepicker-nuevo-pago').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });
    
    console.log('=== Datepickers Inicializados ===');
}


$(document).ready(function() {
    // Verificar si estamos en modo edición
    const modoEdicion = $('#modoEdicion').val() === 'true';
    
    // PRIMERO: Inicializar todos los datepickers ANTES de cargar datos
    inicializarDatepickers();
    
    // LUEGO: Cargar datos si estamos en modo edición
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
    
    // Ejecutar cálculos iniciales solo si NO estamos cargando datos de BD
    if (!cargandoDatos) {
        recalcularTodasLasFechas();
    }
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
    
    // Event listener para botón Agregar Pago
    $('#btnAgregarPago').on('click', function() {
        // Limpiar campos del modal
        $('#fechaPagoNuevo').val('');
        $('#formaPagoNuevo').val('');
        $('#medioPagoNuevo').val('');
        $('#montoNuevo').val('');
        
        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalAgregarPago'));
        modal.show();
    });
    
    $('#tipoCambio').on('input change', function() {
        recalcularFobPesos();
    });

    // Evento para cargar órdenes cuando se selecciona un proveedor
$('#proveedor').on('change', function() {
    const proveedorSeleccionado = $(this).val();
    
    if (proveedorSeleccionado && proveedorSeleccionado !== 'PROVEEDOR') {
        cargarOrdenesPorProveedor(proveedorSeleccionado);
    } else {
        // Limpiar órdenes si no hay proveedor seleccionado
        localStorage.removeItem('ordenes');
    }
});

// Función para cargar órdenes por proveedor
function cargarOrdenesPorProveedor(codProveedor) {
    console.log('Cargando órdenes para proveedor:', codProveedor);
    
    $.ajax({
        url: '../controller/traerOrdenesController.php',
        method: 'GET',
        data: { proveedor: codProveedor },
        dataType: 'json',
        success: function(response) {
            console.log('Órdenes cargadas:', response);
            
            if (response && Array.isArray(response)) {
                // Guardar en localStorage para usar en el modal
                localStorage.setItem('ordenes', JSON.stringify(response));
                
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
                    title: `${response.length} órdenes cargadas`
                });
            } else {
                console.error('Respuesta no válida:', response);
                localStorage.removeItem('ordenes');
            }
        },
        error: function(error) {
            console.error('Error al cargar órdenes:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar las órdenes del proveedor',
                confirmButtonColor: '#3085d6'
            });
            localStorage.removeItem('ordenes');
        }
    });
}
    // ========== MODAL PARA AGREGAR ORDEN DE COMPRA ==========
$('#btnAddOrdenCompra').on('click', function() {
    // Validaciones iniciales
    const proveedorValor = $('#proveedor').val();
    const modoEdicion = $('#modoEdicion').val() === 'true';
    
    if(!proveedorValor || proveedorValor === 'PROVEEDOR') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Debes seleccionar un proveedor primero',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    if(document.querySelector("#ordenManual").checked) {
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
    let ordenesStorage = localStorage.getItem('ordenes');
    let ordenes = [];
    
    try {
        if (ordenesStorage) {
            ordenes = JSON.parse(ordenesStorage);
        }
    } catch (e) {
        console.error('Error al parsear órdenes:', e);
        localStorage.removeItem('ordenes');
    }
    
    // Obtener órdenes ya seleccionadas
    let ordenesSeleccionadas = document.querySelectorAll("#ordenDeCompra");
    let ordenesSeleccionadasArray = Array.from(ordenesSeleccionadas).map(el => {
        const span = el.querySelector('#nroOrdenSpan');
        return span ? span.textContent.trim() : el.textContent.trim().replace('×', '').trim();
    });
    
    // Preparar opciones del select
    let selectOptions = '';
    
    if (ordenes && Array.isArray(ordenes) && ordenes.length > 0) {
        // Filtrar órdenes para excluir las ya seleccionadas
        let ordenesFiltradas = ordenes.filter(orden => {
            const nroOrden = orden.N_ORDEN_CO ? orden.N_ORDEN_CO.trim() : '';
            return nroOrden && !ordenesSeleccionadasArray.includes(nroOrden);
        });
        
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
            const nroOrden = orden.N_ORDEN_CO ? orden.N_ORDEN_CO.trim() : '';
            if (nroOrden) {
                selectOptions += `<option value="${nroOrden}">${nroOrden}</option>`;
            }
        });
    } else {
        // Si no hay órdenes en localStorage, intentar cargarlas nuevamente
        Swal.fire({
            title: 'Cargando órdenes...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
                
                $.ajax({
                    url: '../controller/traerOrdenesController.php',
                    method: 'GET',
                    data: { proveedor: proveedorValor },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        
                        if (response && Array.isArray(response) && response.length > 0) {
                            localStorage.setItem('ordenes', JSON.stringify(response));
                            // Volver a abrir el modal con las órdenes cargadas
                            setTimeout(() => $('#btnAddOrdenCompra').click(), 100);
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Sin datos',
                                text: 'No se encontraron órdenes de compra disponibles para este proveedor',
                                confirmButtonColor: '#3085d6',
                                confirmButtonText: 'Aceptar'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudieron cargar las órdenes. Intenta nuevamente.',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
        return;
    }

    // Crear HTML personalizado para el modal
    const modalHTML = `
        <div class="modal-orden-compra">
            <p class="modal-subtitle">Selecciona una o varias órdenes de compra</p>
            <div class="select-container">
                <select id="ordenCompra" class="swal2-select custom-select" multiple style="min-height: 200px; width: 100%;">
                    ${selectOptions}
                </select>
            </div>
            <div id="seleccionPrevia" class="seleccion-previa mt-3"></div>
            <div class="form-hint mt-2">
                <small><i class="bi bi-info-circle"></i> Mantén presionada la tecla Ctrl (Cmd en Mac) para seleccionar múltiples órdenes</small>
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
        width: '600px',
        didOpen: () => {
            // Establecer tamaño del select
            const selectElement = document.getElementById('ordenCompra');
            selectElement.style.height = '200px';
            
            // Actualizar vista previa cuando se seleccionan opciones
            selectElement.addEventListener('change', () => {
                const seleccionPrevia = document.getElementById('seleccionPrevia');
                seleccionPrevia.innerHTML = '';
                
                const selectedOptions = Array.from(selectElement.selectedOptions);
                
                if (selectedOptions.length === 0) {
                    seleccionPrevia.innerHTML = '<div class="text-muted">No hay órdenes seleccionadas</div>';
                    return;
                }
                
                selectedOptions.forEach(option => {
                    const ordenItem = document.createElement('div');
                    ordenItem.className = 'orden-item mb-2 p-2 bg-light rounded';
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
                title: `${selectedOptions.length} orden(es) agregada(s) correctamente`
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

    // --- FUNCIÓN DE LIMPIEZA CLAVE PARA EVITAR ERRORES DE MONEDA ---
    const limpiarParaEnviar = (valor) => {
        if (!valor) return '0';
        let str = valor.toString();
        
        // 1. Quitar $ y espacios
        str = str.replace('$', '').replace(/\s/g, '');
        
        // 2. Detección de formato para limpiar correctamente
        if (str.includes(',')) {
            // Caso Argentina: 29.972.337,50
            str = str.replace(/\./g, ''); // Borrar puntos de mil (esto arregla el error 29.97)
            str = str.replace(',', '.');  // Cambiar coma por punto
        } 
        
        return str;
    };

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
            
            // LIMPIEZA DE NÚMEROS
            valorFobDolar: limpiarParaEnviar($('#valorFobDolar').val()),
            
            fechaEstEmb: fechaEstEmb,
            ordenCompra: JSON.stringify(ordenCompra),
            ocm: ocm,
            despachante: $('#despachante').val() || 'Laffitte', 
            
            // Campos calculados automáticamente
            fechaArr: fechaArr,
            fechaPago: fechaPago,
            fechaDespAdu: fechaDespAdu,
            
            // Sección 2 - Datos de Embarque (solo enviar en modo edición)
            fechaEmb: (modoEdicion && fechaEmb) ? fechaEmb : '',
            numeroBl: numeroBl,
            factura: factura,
            puertoOrigen: $('#puertoOrigen').val(),
            terminal: $('#terminal').val(),
            
            // ETA Confirmada
            eta_confirmada: $('#etaConfirmada').is(':checked') ? 1 : 0,
            
            // Sección 3 - Datos Financieros y Aduana
            fechaEstPago: $('#fechaEstPago').val(),
            
            // LIMPIEZA DE NÚMEROS (Aquí solucionamos el problema del valor gigante o cortado)
            tipoCambio: limpiarParaEnviar($('#tipoCambio').val()),
            valorFobPeso: limpiarParaEnviar($('#valorFobPeso').val()),
            
            formaPago: formaPago,
            despacho: despacho
        };
        
        console.log('Datos a enviar:', dataToSend);
        
        // Enviar datos al servidor
        $.ajax({
            url: '../controller/insertarEncabezado.php',
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

/**
 * Elimina un pago del despacho
 */
function eliminarPago(idPago) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Se eliminará este registro de pago. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../controller/eliminarPago.php',
                method: 'POST',
                data: { id_pago: idPago },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Eliminado', 'El pago ha sido eliminado correctamente', 'success');
                        // Recargar pagos
                        const idDespacho = $('#idDespacho').val();
                        cargarPagos(idDespacho);
                    } else {
                        Swal.fire('Error', response.message || 'No se pudo eliminar el pago', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al eliminar:', error);
                    Swal.fire('Error', 'Error al conectar con el servidor', 'error');
                }
            });
        }
    });
}

/**
 * Guarda un nuevo pago
 */
function guardarNuevoPago() {
    // Obtener valores del modal
    const fechaPago = $('#fechaPagoNuevo').val();
    const formaPago = $('#formaPagoNuevo').val();
    const medioPago = $('#medioPagoNuevo').val();
    const monto = $('#montoNuevo').val();
    const idDespacho = $('#idDespacho').val();
    
    // Validaciones
    if (!fechaPago) {
        Swal.fire('Error', 'La fecha de pago es requerida', 'error');
        return;
    }
    
    if (!formaPago) {
        Swal.fire('Error', 'Debe seleccionar una forma de pago', 'error');
        return;
    }
    
    if (!medioPago) {
        Swal.fire('Error', 'Debe seleccionar un medio de pago', 'error');
        return;
    }
    
    if (!monto || parseFloat(monto) <= 0) {
        Swal.fire('Error', 'El monto debe ser mayor a cero', 'error');
        return;
    }
    
    // Enviar datos al servidor
    $.ajax({
        url: '../controller/insertarPago.php',
        method: 'POST',
        data: {
            id_despacho: idDespacho,
            fecha_pago: fechaPago,
            forma_pago: formaPago,
            medio_pago: medioPago,
            monto: monto
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire('Éxito', 'Pago agregado correctamente', 'success');
                
                // Cerrar modal
                bootstrap.Modal.getInstance(document.getElementById('modalAgregarPago')).hide();
                
                // Recargar pagos
                cargarPagos(idDespacho);
            } else {
                Swal.fire('Error', response.message || 'No se pudo agregar el pago', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al agregar pago:', error);
            Swal.fire('Error', 'Error al conectar con el servidor', 'error');
        }
    });
}
