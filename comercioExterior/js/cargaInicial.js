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
    
    // Sección 3 - Datos Financieros y Aduana
    if (datos.TIPO_CAMBIO) $('#tipoCambio').val(datos.TIPO_CAMBIO);
    if (datos.VALOR_FOB_PESO) $('#valorFobPeso').val(datos.VALOR_FOB_PESO);
    if (datos.FORMA_PAGO) $('#formaPago').val(datos.FORMA_PAGO).trigger('change');
    if (datos.FECHA_PAGO) {
        $('#fechaPago').val(datos.FECHA_PAGO);
    }
    if (datos.FECHA_EST_PAGO) {
        $('#fechaEstPago').val(datos.FECHA_EST_PAGO);
        // Marcar como manual para evitar recálculo
        fechaEstPagoIsManual = true;
    }
    if (datos.FECHA_DESP_ADU) {
        $('#fechaDespAdu').val(datos.FECHA_DESP_ADU);
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
 * Recalcula Fecha Estimada de Pago (Fecha base + 5 días)
 * Prioridad: ETD (Sec 2) > Fecha Est. Embarque (Sec 1)
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
<<<<<<< Updated upstream
    console.log('Estados manuales - Arribo:', fechaArriboIsManual, 'Pago:', fechaPagoIsManual, 'Despacho:', fechaDespachoIsManual);
    recalcularFechaArribo();
    recalcularFechaPago();
=======
    console.log('Estados manuales - Arribo:', fechaArriboIsManual, 'Despacho:', fechaDespachoIsManual, 'Est. Pago:', fechaEstPagoIsManual);
    recalcularFechaArribo();
    recalcularFechaEstimadaPago();
>>>>>>> Stashed changes
    // No llamar recalcularFechaDespacho aquí porque ya se llama dentro de recalcularFechaArribo
    // recalcularFechaDespacho();
    console.log('=== Fin de recálculo ===');
}

<<<<<<< Updated upstream
=======
// ========== FUNCIONES DE GESTIÓN DE PAGOS ==========

/**
 * Calcula y actualiza el saldo pendiente de pago
 */
function calcularSaldoPendiente() {
    // Remover $ y separadores de miles del valor FOB
    const valorFobPeso = parseFloat($('#valorFobPeso').val().replace(/[$,.]/g, '')) || 0;
    const totalPagado = pagosArray.reduce((sum, pago) => sum + parseFloat(pago.monto || 0), 0);
    const saldoPendiente = valorFobPeso - totalPagado;
    
    const colorSaldo = saldoPendiente > 0 ? '#dc3545' : '#28a745';
    $('#saldoPendiente').html(`Saldo Pendiente: <span style="color: ${colorSaldo};">$${Math.round(saldoPendiente).toLocaleString('es-UY')}</span>`);
    
    return saldoPendiente;
}

/**
 * Renderiza la tabla de pagos
 */
function renderizarTablaPagos() {
    const tbody = $('#tbodyPagos');
    tbody.empty();
    
    if (pagosArray.length === 0) {
        tbody.append(`
            <tr id="sinPagos">
                <td colspan="5" style="text-align: center; color: #999; padding: 30px;">
                    <i class="bi bi-inbox" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                    No hay pagos registrados. Haz clic en "Agregar Pago" para comenzar.
                </td>
            </tr>
        `);
    } else {
        pagosArray.forEach((pago, index) => {
            const isReadonly = pago.guardado ? 'readonly disabled' : '';
            const readonlyStyle = pago.guardado ? 'background-color: #e9ecef; cursor: not-allowed;' : '';
            
            const row = `
                <tr>
                    <td><input type="text" class="form-control form-control-sm js-datepicker-pagos" data-index="${index}" value="${pago.fechaPago}" style="font-size: 13px; ${readonlyStyle}" ${isReadonly}></td>
                    <td>
                        <select class="form-select form-select-sm" data-index="${index}" data-field="formaPago" style="font-size: 13px; ${readonlyStyle}" ${isReadonly}>
                            <option value="">Seleccione...</option>
                            <option value="PAGO ANTICIPADO" ${pago.formaPago === 'PAGO ANTICIPADO' ? 'selected' : ''}>PAGO ANTICIPADO</option>
                            <option value="PAGO VISTA" ${pago.formaPago === 'PAGO VISTA' ? 'selected' : ''}>PAGO VISTA</option>
                            <option value="PAGO DIFERIDO" ${pago.formaPago === 'PAGO DIFERIDO' ? 'selected' : ''}>PAGO DIFERIDO</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select form-select-sm" data-index="${index}" data-field="medioPago" style="font-size: 13px; ${readonlyStyle}" ${isReadonly}>
                            <option value="">Seleccione...</option>
                            <option value="Transferencia" ${pago.medioPago === 'Transferencia' ? 'selected' : ''}>Transferencia</option>
                            <option value="Cheque" ${pago.medioPago === 'Cheque' ? 'selected' : ''}>Cheque</option>
                            <option value="Tarjeta" ${pago.medioPago === 'Tarjeta' ? 'selected' : ''}>Tarjeta</option>
                        </select>
                    </td>
                    <td><input type="text" class="form-control form-control-sm monto-input" data-index="${index}" data-field="monto" value="${pago.monto ? '$' + Math.round(parseFloat(pago.monto)).toLocaleString('es-UY') : ''}" style="font-size: 13px; ${readonlyStyle}" ${isReadonly}></td>
                    <td style="text-align: center;">
                        ${pago.guardado ? 
                            '<span style="color: #28a745; font-size: 18px;" title="Pago guardado"><i class="bi bi-check-circle-fill"></i></span>' :
                            `<button type="button" class="btn btn-sm btn-danger" onclick="eliminarPago(${index})" title="Eliminar pago">
                                <i class="bi bi-trash"></i>
                            </button>`
                        }
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
        
        // Reinicializar datepickers solo para los inputs editables
        $('.js-datepicker-pagos:not([readonly])').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            autoApply: true,
            locale: {format: 'DD/MM/YYYY'}
        }).on('apply.daterangepicker', function(ev, picker) {
            const index = $(this).data('index');
            pagosArray[index].fechaPago = picker.startDate.format('DD/MM/YYYY');
            calcularSaldoPendiente();
            
            // Validar fecha hábil
            setTimeout(() => validarCampoFechaHabil($(this)), 100);
        });
    }
    
    // Actualizar eventos de cambio para inputs
    // Usar 'input' para guardar el valor sin formato
    $('#tbodyPagos input[data-field="monto"]').on('input', function() {
        const index = $(this).data('index');
        // Remover $ y separadores de miles, mantener solo números
        let valor = $(this).val().replace(/[$,.]/g, '');
        
        // Guardar el valor numérico sin formato
        if (valor && !isNaN(valor)) {
            pagosArray[index].monto = valor;
        } else if (!valor) {
            pagosArray[index].monto = '';
        }
        
        calcularSaldoPendiente();
    });
    
    // Usar 'blur' para formatear cuando pierde el foco
    $('#tbodyPagos input[data-field="monto"]').on('blur', function() {
        const index = $(this).data('index');
        let valor = $(this).val().replace(/[$,.]/g, '');
        
        // Formatear el valor en el input solo al perder foco
        if (valor && !isNaN(valor)) {
            $(this).val('$' + Math.round(parseFloat(valor)).toLocaleString('es-UY'));
        }
    });
    
    // Usar 'focus' para quitar el formato al editar
    $('#tbodyPagos input[data-field="monto"]').on('focus', function() {
        const index = $(this).data('index');
        // Al hacer foco, mostrar solo el número sin formato
        if (pagosArray[index].monto) {
            $(this).val(pagosArray[index].monto);
        }
    });
    
    $('#tbodyPagos select[data-field]').on('change', function() {
        const index = $(this).data('index');
        const field = $(this).data('field');
        pagosArray[index][field] = $(this).val();
    });
    
    calcularSaldoPendiente();
}

/**
 * Agrega un nuevo pago vacío
 */
function agregarPago() {
    const nuevoPago = {
        id: null, // null para nuevos pagos
        fechaPago: moment().format('DD/MM/YYYY'),
        formaPago: '',
        medioPago: '',
        monto: '',
        guardado: false // Marcar como no guardado para que sea editable
    };
    
    pagosArray.push(nuevoPago);
    renderizarTablaPagos();
}

/**
 * Elimina un pago del array
 */
function eliminarPago(index) {
    Swal.fire({
        title: '¿Eliminar pago?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            pagosArray.splice(index, 1);
            renderizarTablaPagos();
        }
    });
}

/**
 * Carga los pagos desde el servidor
 */
function cargarPagos(idEncabezado) {
    $.ajax({
        url: '../controller/obtenerPagos.php',
        method: 'GET',
        data: { idEncabezado: idEncabezado },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                pagosArray = response.data.map(pago => {
                    // SQL Server devuelve FECHA_PAGO como objeto con propiedad 'date'
                    let fechaFormateada = '';
                    if (pago.FECHA_PAGO) {
                        if (typeof pago.FECHA_PAGO === 'object' && pago.FECHA_PAGO.date) {
                            fechaFormateada = moment(pago.FECHA_PAGO.date).format('DD/MM/YYYY');
                        } else if (typeof pago.FECHA_PAGO === 'string') {
                            fechaFormateada = moment(pago.FECHA_PAGO).format('DD/MM/YYYY');
                        }
                    }
                    
                    return {
                        id: pago.ID,
                        fechaPago: fechaFormateada,
                        formaPago: pago.FORMA_PAGO,
                        medioPago: pago.MEDIO_PAGO,
                        monto: pago.MONTO,
                        guardado: false // Permitir edición de pagos existentes
                    };
                });
                renderizarTablaPagos();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar pagos:', error);
        }
    });
}

/**
 * Valida que todos los pagos tengan datos completos
 */
function validarPagos() {
    for (let i = 0; i < pagosArray.length; i++) {
        const pago = pagosArray[i];
        if (!pago.fechaPago || !pago.formaPago || !pago.medioPago || !pago.monto || parseFloat(pago.monto) <= 0) {
            Swal.fire({
                title: 'Datos incompletos',
                text: `El pago #${i + 1} tiene campos vacíos o inválidos`,
                icon: 'warning',
                confirmButtonColor: '#7066e0'
            });
            return false;
        }
    }
    return true;
}

/**
 * Guarda los pagos en el servidor
 */
function guardarPagosEnServidor(idEncabezado, callback) {
    // Convertir fechas al formato que espera SQL Server
    const pagosParaEnviar = pagosArray.map(pago => ({
        id: pago.id,
        fechaPago: moment(pago.fechaPago, 'DD/MM/YYYY').format('YYYY-MM-DD'),
        formaPago: pago.formaPago,
        medioPago: pago.medioPago,
        monto: parseFloat(pago.monto)
    }));
    
    $.ajax({
        url: '../controller/guardarPagos.php',
        method: 'POST',
        dataType: 'json',
        data: {
            idEncabezado: idEncabezado,
            pagos: JSON.stringify(pagosParaEnviar)
        },
        success: function(response) {
            callback(response.success);
        },
        error: function(xhr, status, error) {
            console.error('Error al guardar pagos:', error);
            callback(false);
        }
    });
}

>>>>>>> Stashed changes
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
    
    // Datepicker para Fecha Estimada de Pago
    $('.js-datepicker-est-pago').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {format: 'DD/MM/YYYY'}
    });
    
    // ========== VALIDACIÓN DE FECHAS HÁBILES ==========
    // Validar Fecha Arribo - ETA al cambiar
    $('.js-datepicker-arribo').on('apply.daterangepicker', function(ev, picker) {
        setTimeout(() => validarCampoFechaHabil($(this)), 100);
    });
    
    // Validar Fecha Estimada de Pago al cambiar
    $('.js-datepicker-est-pago').on('apply.daterangepicker', function(ev, picker) {
        setTimeout(() => validarCampoFechaHabil($(this)), 100);
    });
    
    // Validar Fecha de Nacionalización al cambiar
    $('.js-datepicker-despacho').on('apply.daterangepicker', function(ev, picker) {
        setTimeout(() => validarCampoFechaHabil($(this)), 100);
    });
    
    // Event listener para cerrar alerta informativa al hacer clic
    $(document).on('click', '.alerta-fecha-no-habil', function() {
        const campoId = $(this).data('campo');
        const $campo = $('#' + campoId);
        ocultarAdvertenciaFecha($campo);
    });
    
    // ========== CHECKBOX ETA CONFIRMADA ==========
    // Indicador visual para campo de fecha ETA según estado del checkbox
    $('#etaConfirmada').on('change', function() {
        const $fechaArr = $('#fechaArr');
        const $label = $fechaArr.closest('.col-md-5').find('.label-campo');
        
        if ($(this).is(':checked')) {
            // ETA confirmada - borde verde
            $fechaArr.css({
                'border-left': '3px solid #28a745',
                'background-color': '#f0f9f0'
            });
        } else {
            // ETA estimada - restablecer estilos
            $fechaArr.css({
                'border-left': '',
                'background-color': ''
            });
        }
    });
    
    // Aplicar estilo inicial si está marcado en carga
    if ($('#etaConfirmada').is(':checked')) {
        $('#fechaArr').css({
            'border-left': '3px solid #28a745',
            'background-color': '#f0f9f0'
        });
    }
    
    // ========== FECHA ESTIMADA DE PAGO - CONTROL MANUAL ==========
    // Marcar como manual cuando usuario cambia directamente el campo
    $('#fechaEstPago').on('apply.daterangepicker', function(ev, picker) {
        if (!cargandoDatos) {
            fechaEstPagoIsManual = true;
            console.log('Fecha Est. Pago modificada manualmente');
        }
    });
    
    // Recalcular cuando cambian fechas base (si no es manual)
    $('#fechaEstEmb').on('apply.daterangepicker', function(ev, picker) {
        recalcularFechaEstimadaPago();
    });
    
    $('#fechaEmb').on('apply.daterangepicker', function(ev, picker) {
        // Si antes no había ETD y ahora sí, resetear flag manual y recalcular
        fechaEstPagoIsManual = false;
        recalcularFechaEstimadaPago();
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
            despachante: $('#despachante').val() || 'Laffitte', // DESPACHANTE
            
            // Campos calculados automáticamente
            fechaArr: fechaArr,
            fechaPago: fechaPago,
            fechaDespAdu: fechaDespAdu,
            
            // Sección 2 - Datos de Embarque (solo enviar en modo edición)
            fechaEmb: (modoEdicion && fechaEmb) ? fechaEmb : '',
            numeroBl: numeroBl,
            factura: factura,
            
            // ETA Confirmada
            eta_confirmada: $('#etaConfirmada').is(':checked') ? 1 : 0,
            
            // Sección 3 - Datos Financieros y Aduana
            fechaEstPago: $('#fechaEstPago').val(),
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
