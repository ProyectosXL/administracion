/**
 * editar-estimacion.js - Lógica de PCI adaptada a estructura real
 * Maneja 13 conceptos con TIPO_VALOR (P=Porcentaje, I=Importe)
 */

// Variables globales
let datosDespacho = null;
let conceptos = [];
let estimacionExistente = null;
let estaConfirmada = false;

// Mapeo de IDs de conceptos según BD real
const CONCEPTOS_ID = {
    FLETE: 1,
    SEGURO: 2,
    DERECHOS: 3,
    TASA_ESTADISTICA: 4,
    IVA_GENERAL: 5,
    IVA_ADICIONAL: 6,
    IIGG: 7,
    IIBB: 8,
    SIM: 9,
    ANTIDUMPING: 10,
    DESPACHANTE: 11,
    TERMINAL: 12,
    SUMA_ASEGURADA: 13
};

// Orden de visualización (incluye conceptos calculados no guardados en BD)
const ORDEN_VISUALIZACION = [
    { tipo: 'calculado', nombre: 'FOB', esEditable: false },
    { tipo: 'concepto', id_ce: 1, nombre: 'Flete' },
    { tipo: 'concepto', id_ce: 2, nombre: 'Seguro' },
    { tipo: 'calculado', nombre: 'CIF', esEditable: false },
    { tipo: 'concepto', id_ce: 3, nombre: 'Derechos' },
    { tipo: 'concepto', id_ce: 4, nombre: 'Tasa estadistica' },
    { tipo: 'calculado', nombre: 'Base imponible', esEditable: false },
    { tipo: 'concepto', id_ce: 5, nombre: 'IVA General' },
    { tipo: 'concepto', id_ce: 6, nombre: 'IVA Adicional' },
    { tipo: 'concepto', id_ce: 7, nombre: 'IIGG' },
    { tipo: 'concepto', id_ce: 8, nombre: 'IIBB' },
    { tipo: 'concepto', id_ce: 9, nombre: 'SIM' },
    { tipo: 'concepto', id_ce: 10, nombre: 'Antidumping' },
    { tipo: 'calculado', nombre: 'Total nacionalización', esEditable: false },
    { tipo: 'concepto', id_ce: 11, nombre: 'Despachante' },
    { tipo: 'concepto', id_ce: 12, nombre: 'Terminal' },
    { tipo: 'calculado', nombre: 'Total Cashflow', esEditable: false },
    { tipo: 'concepto', id_ce: 13, nombre: 'Suma asegurada' }
];

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const idDespacho = urlParams.get('id');
    
    if (!idDespacho) {
        mostrarError('No se especificó el ID del despacho');
        return;
    }
    
    $('#idDespacho').val(idDespacho);
    cargarDatosEstimacion(idDespacho);
});

/**
 * Cargar datos del despacho y conceptos
 */
function cargarDatosEstimacion(idDespacho) {
    mostrarLoading(true);
    
    $.ajax({
        url: '../../controller/cargarEstimacion.php',
        method: 'GET',
        data: { id: idDespacho },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                datosDespacho = response.data.despacho;
                conceptos = response.data.conceptos;
                estimacionExistente = response.data.estimacion;
                estaConfirmada = response.data.confirmada;
                
                // DEBUG: Ver valores del concepto DESPACHANTE
                console.log('=== DEBUG DESPACHANTE ===');
                console.log('Despachante del despacho:', datosDespacho.DESPACHANTE);
                console.log('TODOS los conceptos:', conceptos);
                const conceptoDespachante = conceptos.find(c => c.CONCEPTO === 'DESPACHANTE' || c.CONCEPTO === 'Despachante');
                if (conceptoDespachante) {
                    console.log('Concepto DESPACHANTE encontrado:', conceptoDespachante);
                } else {
                    console.log('Concepto DESPACHANTE NO encontrado');
                }
                if (estimacionExistente) {
                    console.log('TODA la estimacion existente:', estimacionExistente);
                    const estimacionDespachante = estimacionExistente.find(e => e.CONCEPTO === 'DESPACHANTE' || e.CONCEPTO === 'Despachante');
                    if (estimacionDespachante) {
                        console.log('Estimacion DESPACHANTE encontrada:', estimacionDespachante);
                    } else {
                        console.log('Estimacion DESPACHANTE NO encontrada');
                    }
                }
                console.log('=========================');
                
                $('#estaConfirmada').val(estaConfirmada ? '1' : '0');
                
                mostrarInformacionDespacho();
                generarFormularioConceptos();
                
                if (estaConfirmada) {
                    bloquearFormulario();
                }
            } else {
                mostrarError(response.message || 'Error al cargar los datos');
            }
            mostrarLoading(false);
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            mostrarError('Error al conectar con el servidor');
            mostrarLoading(false);
        }
    });
}

/**
 * Mostrar información del despacho
 */
function mostrarInformacionDespacho() {
    $('#despacheInfo').text(`Despacho #${datosDespacho.ID} - ${datosDespacho.PROVEEDOR}`);
    $('#infoProveedor').text(datosDespacho.PROVEEDOR);
    $('#infoContenedor').text(datosDespacho.CONTENEDOR);
    $('#infoMaterial').text(datosDespacho.MATERIAL);
    $('#infoOrdenCompra').text(datosDespacho.ORDEN_COMPRA);
    $('#valorFOB').val(datosDespacho.VALOR_FOB_DOLAR);
    
    if (estaConfirmada) {
        $('#estadoBadge').html('<i class="bi bi-check-circle-fill"></i> Confirmado')
            .removeClass('badge-warning').addClass('badge-success');
    }
}

/**
 * Generar formulario con todos los conceptos en el orden correcto
 */
function generarFormularioConceptos() {
    const tbody = $('#tablaConceptos');
    tbody.empty();
    
    // Generar filas según ORDEN_VISUALIZACION
    ORDEN_VISUALIZACION.forEach((item, index) => {
        let fila;
        
        if (item.tipo === 'calculado') {
            // Fila de valor calculado (FOB, CIF, Base Imponible, etc.)
            fila = generarFilaCalculada(item.nombre);
        } else {
            // Fila de concepto de BD
            const concepto = conceptos.find(c => c.ID_CE === item.id_ce);
            if (!concepto) return; // Si no existe el concepto, saltar
            
            const valorExistente = estimacionExistente ? 
                estimacionExistente.find(e => e.ID_CE == concepto.ID_CE) : null;
            
            const param1 = valorExistente ? valorExistente.VALOR_DEFAULT_1 : concepto.VALOR_DEFAULT_1;
            const param2 = valorExistente ? valorExistente.VALOR_DEFAULT_2 : concepto.VALOR_DEFAULT_2;
            const confirmado = valorExistente ? valorExistente.CONFIRMADO : 0;
            
            // Determinar importe inicial según estado:
            // - Si CONFIRMADO = 1: usar IMPORTE de BD (valor histórico congelado)
            // - Si CONFIRMADO = 0: usar parámetro para inicializar, luego se recalculará
            let importe = 0;
            
            if (confirmado === 1 && valorExistente && valorExistente.IMPORTE !== null) {
                // Modo confirmado: usar valor guardado en BD
                const importeGuardado = parseFloat(valorExistente.IMPORTE);
                importe = !isNaN(importeGuardado) ? importeGuardado : 0;
            } else {
                // Modo borrador o nuevo: usar parámetro como valor inicial
                if (param1 !== null && param1 !== undefined) {
                    const param1Num = parseFloat(param1);
                    importe = !isNaN(param1Num) ? param1Num : 0;
                }
            }
            
            fila = generarFilaConcepto(concepto, param1, param2, importe, confirmado, index);
        }
        
        tbody.append(fila);
    });
    
    // Calcular importes iniciales
    calcularTodosLosConceptos();
}

/**
 * Generar fila para valores calculados no editables (FOB, CIF, etc.)
 */
function generarFilaCalculada(nombre) {
    return `
        <tr class="fila-calculada" data-concepto-calculado="${nombre}">
            <td class="concepto-nombre">
                <strong>${nombre}</strong>
            </td>
            <td class="text-center">
                <span class="badge bg-warning">Calculado</span>
            </td>
            <td class="text-center">
                <span class="parametro-badge">-</span>
            </td>
            <td class="text-center">
                <span class="parametro-badge">-</span>
            </td>
            <td>
                <input type="text" 
                       class="form-control-plaintext importe-calculado text-end fw-bold" 
                       value="0,00" 
                       readonly
                       style="background-color: #f8f9fa;">
            </td>
        </tr>
    `;
}

/**
 * Generar HTML para una fila de concepto
 */
function generarFilaConcepto(concepto, param1, param2, importe, confirmado, index) {
    const tipoValor = concepto.TIPO_VALOR; // 'P' = Porcentaje, 'I' = Importe
    const tipoTexto = tipoValor === 'P' ? 'Porcentaje' : 'Importe Fijo';
    const param1Display = param1 !== null ? formatearParametro(param1, tipoValor) : '-';
    const param2Display = param2 !== null ? formatearParametro(param2, tipoValor) : '-';
    
    // Determinar si el campo debe ser readonly:
    // - Si el concepto está CONFIRMADO (confirmado=1), entonces readonly
    // - Si está en BORRADOR (confirmado=0 o undefined), entonces editable
    const esConfirmado = confirmado === 1;
    const readonlyAttr = esConfirmado ? 'readonly' : '';
    const confirmadoClass = esConfirmado ? 'confirmado' : '';
    
    return `
        <tr data-concepto-id="${concepto.ID_CE}" 
            data-concepto-nombre="${concepto.CONCEPTO}" 
            data-tipo="${tipoValor}"
            data-confirmado="${confirmado || 0}">
            <td class="concepto-nombre">
                <strong>${concepto.CONCEPTO}</strong>
            </td>
            <td class="text-center">
                <span class="badge bg-${tipoValor === 'P' ? 'info' : 'secondary'}">${tipoTexto}</span>
            </td>
            <td class="text-center">
                <span class="parametro-badge">${param1Display}</span>
                <input type="hidden" class="param1" value="${param1 || 0}">
            </td>
            <td class="text-center">
                <span class="parametro-badge">${param2Display}</span>
                <input type="hidden" class="param2" value="${param2 || 0}">
            </td>
            <td>
                <input type="text" 
                       class="form-control importe-editable text-end ${confirmadoClass}" 
                       value="${formatearMoneda(importe)}" 
                       data-valor="${importe}"
                       ${readonlyAttr}
                       onkeyup="handleImporteChange(this)"
                       onblur="formatearCampoMoneda(this)">
            </td>
        </tr>
    `;
}

/**
 * Manejar cambio en un importe editable
 */
function handleImporteChange(input) {
    const valor = parseNumero($(input).val());
    $(input).data('valor', valor);
    
    // Recalcular todos los conceptos
    calcularTodosLosConceptos();
}

/**
 * Calcular todos los conceptos según la lógica de negocio
 */
function calcularTodosLosConceptos() {
    const fob = parseFloat($('#valorFOB').val()) || 0;
    
    // 1. FOB (valor fijo del despacho)
    setValorCalculado('FOB', fob);
    
    // 2. Flete (ID_CE=1): Importe fijo editable
    const flete = getConceptoValorEditable(CONCEPTOS_ID.FLETE);
    
    // 3. Seguro (ID_CE=2): (FOB + Flete) * Parámetro1
    const seguroParam = getConceptoParam1(CONCEPTOS_ID.SEGURO);
    const seguro = (fob + flete) * seguroParam;
    setConceptoValorCalculado(CONCEPTOS_ID.SEGURO, seguro);
    
    // 4. CIF (calculado, no guardado): FOB + Flete + Seguro
    const cif = fob + flete + seguro;
    setValorCalculado('CIF', cif);
    
    // 5. Derechos (ID_CE=3): CIF * Parámetro1
    const derechosParam = getConceptoParam1(CONCEPTOS_ID.DERECHOS);
    const derechos = cif * derechosParam;
    setConceptoValorCalculado(CONCEPTOS_ID.DERECHOS, derechos);
    
    // 6. Tasa Estadística (ID_CE=4): CIF * Parámetro1
    const tasaParam = getConceptoParam1(CONCEPTOS_ID.TASA_ESTADISTICA);
    const tasaEstadistica = cif * tasaParam;
    setConceptoValorCalculado(CONCEPTOS_ID.TASA_ESTADISTICA, tasaEstadistica);
    
    // 7. Base Imponible (calculada, no guardada): CIF + Derechos + Tasa
    const baseImponible = cif + derechos + tasaEstadistica;
    setValorCalculado('Base imponible', baseImponible);
    
    // 8. IVA General (ID_CE=5): Base Imponible * Parámetro1
    const ivaGeneralParam = getConceptoParam1(CONCEPTOS_ID.IVA_GENERAL);
    const ivaGeneral = baseImponible * ivaGeneralParam;
    setConceptoValorCalculado(CONCEPTOS_ID.IVA_GENERAL, ivaGeneral);
    
    // 9. IVA Adicional (ID_CE=6): Base Imponible * Parámetro1
    const ivaAdicionalParam = getConceptoParam1(CONCEPTOS_ID.IVA_ADICIONAL);
    const ivaAdicional = baseImponible * ivaAdicionalParam;
    setConceptoValorCalculado(CONCEPTOS_ID.IVA_ADICIONAL, ivaAdicional);
    
    // 10. IIGG (ID_CE=7): Base Imponible * Parámetro1
    const iiggParam = getConceptoParam1(CONCEPTOS_ID.IIGG);
    const iigg = baseImponible * iiggParam;
    setConceptoValorCalculado(CONCEPTOS_ID.IIGG, iigg);
    
    // 11. IIBB (ID_CE=8): Base Imponible * Parámetro1
    const iibbParam = getConceptoParam1(CONCEPTOS_ID.IIBB);
    const iibb = baseImponible * iibbParam;
    setConceptoValorCalculado(CONCEPTOS_ID.IIBB, iibb);
    
    // 12. SIM (ID_CE=9): Importe fijo editable
    const sim = getConceptoValorEditable(CONCEPTOS_ID.SIM);
    
    // 13. Antidumping (ID_CE=10): Valor manual editable
    const antidumping = getConceptoValorEditable(CONCEPTOS_ID.ANTIDUMPING);
    
    // 14. Total Nacionalización (calculado): Suma de Seguro + conceptos 5-13
    const totalNac = seguro + derechos + tasaEstadistica + ivaGeneral + 
                     ivaAdicional + iigg + iibb + sim + antidumping;
    setValorCalculado('Total nacionalización', totalNac);
    
    // 15. Despachante (ID_CE=11): Cálculo especial
    // Fórmula: ((FOB * 0.01) + Param1) * 1.21
    // Param1 = valor base (450 Laffitte, 120 Farre)
    // 1.21 = IVA fijo del 21%
    const despachanteParam1 = getConceptoParam1(CONCEPTOS_ID.DESPACHANTE);
    const despachante = ((fob * 0.01) + despachanteParam1) * 1.21;
    
    // Debug: mostrar valores en consola
    console.log('=== CÁLCULO DESPACHANTE ===');
    console.log('FOB:', fob);
    console.log('FOB * 0.01:', fob * 0.01);
    console.log('Parámetro 1:', despachanteParam1);
    console.log('(FOB * 0.01) + Param1:', (fob * 0.01) + despachanteParam1);
    console.log('Despachante final:', despachante);
    console.log('==========================');
    
    setConceptoValorCalculado(CONCEPTOS_ID.DESPACHANTE, despachante);
    
    // 16. Terminal (ID_CE=12): Importe fijo editable
    const terminal = getConceptoValorEditable(CONCEPTOS_ID.TERMINAL);
    
    // 17. Total Cashflow (calculado): Total Nac + Despachante + Terminal
    const totalCashflow = totalNac + despachante + terminal;
    setValorCalculado('Total Cashflow', totalCashflow);
    
    // 18. Suma Asegurada (ID_CE=13): ((FOB + Flete) * (1 + Param1)) * (1 + Param2)
    const sumaParam1 = getConceptoParam1(CONCEPTOS_ID.SUMA_ASEGURADA);
    const sumaParam2 = getConceptoParam2(CONCEPTOS_ID.SUMA_ASEGURADA);
    const sumaAsegurada = ((fob + flete) * (1 + sumaParam1)) * (1 + sumaParam2);
    setConceptoValorCalculado(CONCEPTOS_ID.SUMA_ASEGURADA, sumaAsegurada);
}

/**
 * Obtener valor editable de un concepto por ID
 */
function getConceptoValorEditable(idCe) {
    const $row = $(`tr[data-concepto-id="${idCe}"]`);
    const valor = $row.find('.importe-editable').data('valor');
    return parseFloat(valor) || 0;
}

/**
 * Obtener parámetro 1 de un concepto por ID
 */
function getConceptoParam1(idCe) {
    const $row = $(`tr[data-concepto-id="${idCe}"]`);
    return parseFloat($row.find('.param1').val()) || 0;
}

/**
 * Obtener parámetro 2 de un concepto por ID
 */
function getConceptoParam2(idCe) {
    const $row = $(`tr[data-concepto-id="${idCe}"]`);
    const param2 = $row.find('.param2').val();
    return param2 ? parseFloat(param2) : 1; // Default 1 si no existe
}

/**
 * Establecer valor calculado de un concepto
 */
function setConceptoValorCalculado(idCe, valor) {
    const $row = $(`tr[data-concepto-id="${idCe}"]`);
    const $input = $row.find('.importe-editable');
    const confirmado = parseInt($row.data('confirmado')) || 0;
    
    // NO actualizar si:
    // 1. El concepto está confirmado (valores congelados)
    // 2. El usuario está editando el campo actualmente
    if (confirmado === 1 || $input.is(':focus')) {
        return;
    }
    
    // Actualizar el valor calculado en modo borrador
    $input.val(formatearMoneda(valor)).data('valor', valor);
}

/**
 * Establecer valor de fila calculada (FOB, CIF, Base Imponible, etc.)
 */
function setValorCalculado(nombre, valor) {
    const $row = $(`tr[data-concepto-calculado="${nombre}"]`);
    $row.find('.importe-calculado').val(formatearMoneda(valor));
}

/**
 * Guardar estimación
 */
function guardarEstimacion() {
    if (estaConfirmada) {
        Swal.fire({
            title: 'Estimación confirmada',
            text: 'No se puede modificar una estimación confirmada',
            icon: 'warning',
            confirmButtonColor: '#7066e0'
        });
        return;
    }
    
    const conceptosData = [];
    
    // Solo guardar conceptos de BD (no los calculados como FOB, CIF, etc.)
    $('#tablaConceptos tr[data-concepto-id]').each(function() {
        const $row = $(this);
        const idCe = $row.data('concepto-id');
        const param1 = parseFloat($row.find('.param1').val()) || null;
        const param2 = parseFloat($row.find('.param2').val()) || null;
        const importe = $row.find('.importe-editable').data('valor') || 0;
        
        conceptosData.push({
            id_ce: idCe,
            valor_default_1: param1,
            valor_default_2: param2,
            importe: parseFloat(importe)
        });
    });
    
    const data = {
        id_mg: parseInt($('#idDespacho').val()),
        conceptos: conceptosData
    };
    
    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '../../controller/guardarEstimacion.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: '¡Guardado!',
                    text: response.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    // Volver a la pestaña de proyección de costos
                    window.location.href = '../proyeccionCostos.php';
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al guardar la estimación',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    });
}

/**
 * Bloquear formulario cuando está confirmado
 */
function bloquearFormulario() {
    $('.importe-editable').prop('readonly', true).addClass('confirmado');
    $('#btnGuardar').prop('disabled', true).html('<i class="bi bi-lock"></i> Confirmado');
    $('#btnGuardarBottom').prop('disabled', true).html('<i class="bi bi-lock"></i> Confirmado');
}

/**
 * Utilidades de formato
 */
function formatearMoneda(valor) {
    return parseFloat(valor).toLocaleString('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatearParametro(valor, tipo) {
    if (tipo === 'P') {
        // Porcentaje: mostrar como % con 2 decimales
        return (parseFloat(valor) * 100).toFixed(2) + '%';
    } else {
        // Importe: mostrar con formato moneda
        return formatearMoneda(valor);
    }
}

function formatearNumero(valor, decimales = 6) {
    return parseFloat(valor).toLocaleString('es-AR', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    });
}

function parseNumero(valor) {
    if (typeof valor === 'number') return valor;
    return parseFloat(valor.toString().replace(/\./g, '').replace(',', '.')) || 0;
}

function formatearCampoMoneda(input) {
    const valor = parseNumero($(input).val());
    $(input).val(formatearMoneda(valor)).data('valor', valor);
}

function mostrarLoading(mostrar) {
    if (mostrar) {
        $('#loadingOverlay').fadeIn();
    } else {
        $('#loadingOverlay').fadeOut();
    }
}

function mostrarError(mensaje) {
    Swal.fire({
        title: 'Error',
        text: mensaje,
        icon: 'error',
        confirmButtonColor: '#dc3545'
    }).then(() => {
        window.location.href = '../proyeccionCostos.php';
    });
}
