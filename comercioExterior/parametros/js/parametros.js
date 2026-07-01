/**
 * parametros.js - Gestión de Parámetros de Importación
 */

let tablaParametros;
let parametroActual = null;
let entornoActual = 'central';
let listaConceptosOriginales = [];

$(document).ready(function() {
    cargarParametros();
});

/**
 * Cargar y mostrar parámetros en DataTable
 */
function cargarParametros() {
    $.ajax({
        url: 'controller/listarParametros.php',
        method: 'GET',
        dataType: 'json',
        cache: false,
        success: function(response) {
            console.log('Respuesta recibida:', response);
            if (response.success && response.data) {
                entornoActual = response.entorno || 'central';
                listaConceptosOriginales = response.data;
                renderizarTabla(response.data);
            } else {
                mostrarError(response.message || 'Error al cargar los parámetros');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error completo:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            let mensaje = 'Error al conectar con el servidor';
            
            if (xhr.responseText) {
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    mensaje = errorData.message || mensaje;
                } catch (e) {
                    mensaje = 'Error del servidor: ' + xhr.responseText.substring(0, 100);
                }
            }
            
            mostrarError(mensaje);
        }
    });
}

/**
 * Renderizar DataTable con los parámetros
 */
function renderizarTabla(datos) {
    // 1. Destruir DataTable si existe antes de vaciar o modificar el DOM
    if (tablaParametros) {
        tablaParametros.destroy();
    } else if ($.fn.DataTable.isDataTable('#tablaParametros')) {
        $('#tablaParametros').DataTable().destroy();
    }

    const tbody = $('#tablaParametros tbody');
    tbody.empty();
    
    datos.forEach(function(param) {
        const tipoTexto = param.TIPO_VALOR === 'P' ? 'Porcentaje' : 'Importe Fijo';
        const tipoBadge = param.TIPO_VALOR === 'P' ? 'info' : 'secondary';
        
        let param1Display = '';
        if (param.VALOR_DEFAULT_1 !== null) {
            if (entornoActual === 'uy' && param.TIPO_VALOR === 'I') {
                const mon = param.MONEDA || 'USD';
                const tc = parseFloat(param.TIPO_CAMBIO || 1);
                const valDefault = parseFloat(param.VALOR_DEFAULT_1);
                const valUyu = parseFloat(param.VALOR_DEFAULT_1_UYU !== null ? param.VALOR_DEFAULT_1_UYU : (valDefault * tc));
                
                if (mon === 'USD') {
                    param1Display = `U$D ${valDefault.toLocaleString('es-UY', {minimumFractionDigits: 2})} <br><small class="text-muted">≈ $UYU ${valUyu.toLocaleString('es-UY', {minimumFractionDigits: 2})}</small>`;
                } else {
                    param1Display = `$UYU ${valUyu.toLocaleString('es-UY', {minimumFractionDigits: 2})} <br><small class="text-muted">≈ U$D ${valDefault.toLocaleString('es-UY', {minimumFractionDigits: 2})}</small>`;
                }
            } else {
                param1Display = formatearParametro(param.VALOR_DEFAULT_1, param.TIPO_VALOR, param.ID_REF_CONCEPTO);
            }
        } else {
            param1Display = '-';
        }
        
        let param2Display = '';
        if (param.VALOR_DEFAULT_2 !== null) {
            if (entornoActual === 'uy' && param.TIPO_VALOR === 'I') {
                const mon = param.MONEDA || 'USD';
                const tc = parseFloat(param.TIPO_CAMBIO || 1);
                const valDefault = parseFloat(param.VALOR_DEFAULT_2);
                const valUyu = parseFloat(param.VALOR_DEFAULT_2_UYU !== null ? param.VALOR_DEFAULT_2_UYU : (valDefault * tc));
                
                if (mon === 'USD') {
                    param2Display = `U$D ${valDefault.toLocaleString('es-UY', {minimumFractionDigits: 2})} <br><small class="text-muted">≈ $UYU ${valUyu.toLocaleString('es-UY', {minimumFractionDigits: 2})}</small>`;
                } else {
                    param2Display = `$UYU ${valUyu.toLocaleString('es-UY', {minimumFractionDigits: 2})} <br><small class="text-muted">≈ U$D ${valDefault.toLocaleString('es-UY', {minimumFractionDigits: 2})}</small>`;
                }
            } else {
                param2Display = formatearParametro(param.VALOR_DEFAULT_2, param.TIPO_VALOR, param.ID_REF_CONCEPTO);
            }
        } else {
            param2Display = '-';
        }
        
        const fechaActua = param.ULT_ACTUA ? 
            formatearFecha(param.ULT_ACTUA) : '-';
        
        const fila = `
            <tr>
                <td class="text-center fw-bold">${param.ID_CE}</td>
                <td><strong>${param.CONCEPTO}</strong></td>
                <td class="text-center">
                    <span class="badge bg-${tipoBadge} tipo-badge">${tipoTexto}</span>
                </td>
                <td class="text-end">
                    <span class="badge bg-light text-dark">${param1Display}</span>
                </td>
                <td class="text-end">
                    <span class="badge bg-light text-dark">${param2Display}</span>
                </td>
                <td class="text-center">
                    <small class="text-muted">${fechaActua}</small>
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <button class="btn btn-sm btn-primary" onclick='abrirModalEditar(${JSON.stringify(param)})' title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick='eliminarConcepto(${param.ID_CE}, "${param.CONCEPTO}")' title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        
        tbody.append(fila);
    });
    
    // Inicializar la tabla de nuevo con un breve retardo para asegurar que el DOM está listo
    setTimeout(function() {
        tablaParametros = $('#tablaParametros').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            pageLength: 25,
            responsive: true
        });
    }, 50);
}

/**
 * Abrir modal de edición
 */
function abrirModalEditar(parametro) {
    parametroActual = parametro;
    
    // Llenar campos del modal
    $('#editIdCe').val(parametro.ID_CE);
    $('#editConcepto').val(parametro.CONCEPTO);
    
    // Llenar select de referencias
    const selectRef = $('#editRefConcepto');
    selectRef.empty().append('<option value="">(Base por defecto, ej: CIF/FOB)</option>');
    listaConceptosOriginales.forEach(c => {
        if (c.ID_CE != parametro.ID_CE) {
            selectRef.append(`<option value="${c.ID_CE}">${c.CONCEPTO}</option>`);
        }
    });
    selectRef.val(parametro.ID_REF_CONCEPTO || '');

    // Mostrar tipo de valor con formato visual mejorado
    $('#editTipoValor').val(parametro.TIPO_VALOR);
    
    // Escuchar cambios en el tipo de valor para ajustar dinámicamente las unidades mostradas y Calcular sobre selector
    $('#editTipoValor').off('change').on('change', function() {
        const nuevoTipo = $(this).val();
        const unidad = nuevoTipo === 'P' ? '%' : 'USD';
        $('#editParam1Unidad').text(unidad);
        $('#editParam2Unidad').text(unidad);
        
        if (nuevoTipo === 'P') {
            $('#divEditRefConcepto').show();
        } else {
            $('#divEditRefConcepto').hide();
            $('#editRefConcepto').val('');
        }
        actualizarVistaPreviaCambiaria();
    });
    
    // Moneda y Tipo de Cambio para Uruguay
    $('#editMoneda').val(parametro.MONEDA || 'USD');
    $('#editTipoCambio').val(parametro.TIPO_CAMBIO || '1.0000');
    
    // Desvincular e inicializar eventos para actualización automática de vista previa cambiaria
    $('#editMoneda, #editTipoCambio, #editValorDefault1, #editValorDefault2, #editRefConcepto').off('input change').on('input change', function() {
        actualizarVistaPreviaCambiaria();
    });
    
    // Configurar Parámetro 1
    // Si la moneda seleccionada es UYU, el valor de entrada debe ser UYU
    let valor1Display = '';
    if (entornoActual === 'uy' && parametro.TIPO_VALOR === 'I' && parametro.MONEDA === 'UYU') {
        valor1Display = parametro.VALOR_DEFAULT_1_UYU !== null ? parametro.VALOR_DEFAULT_1_UYU : '';
    } else {
        valor1Display = parametro.VALOR_DEFAULT_1 || '';
    }
    
    if (valor1Display !== '' && parametro.TIPO_VALOR === 'P') {
        valor1Display = (parseFloat(valor1Display) * 100).toString();
    }
    $('#editValorDefault1').val(valor1Display);
    
    const param1Unidad = parametro.TIPO_VALOR === 'P' ? '%' : (entornoActual === 'uy' && parametro.MONEDA === 'UYU' ? 'UYU' : 'USD');
    $('#editParam1Unidad').text(param1Unidad);
    
    const param1Help = obtenerAyudaParametro(parametro.CONCEPTO, 1);
    $('#editParam1Help').text(param1Help);
    
    // Configurar Parámetro 2
    if (parametro.VALOR_DEFAULT_2 !== null || requiereParam2(parametro.CONCEPTO)) {
        $('#divParam2').show();
        
        let valor2Display = '';
        if (entornoActual === 'uy' && parametro.TIPO_VALOR === 'I' && parametro.MONEDA === 'UYU') {
            valor2Display = parametro.VALOR_DEFAULT_2_UYU !== null ? parametro.VALOR_DEFAULT_2_UYU : '';
        } else {
            valor2Display = parametro.VALOR_DEFAULT_2 || '';
        }
        
        if (valor2Display !== '' && parametro.TIPO_VALOR === 'P') {
            valor2Display = (parseFloat(valor2Display) * 100).toString();
        }
        $('#editValorDefault2').val(valor2Display);
        
        const param2Unidad = parametro.TIPO_VALOR === 'P' ? '%' : (entornoActual === 'uy' && parametro.MONEDA === 'UYU' ? 'UYU' : 'USD');
        $('#editParam2Unidad').text(param2Unidad);
        
        const param2Help = obtenerAyudaParametro(parametro.CONCEPTO, 2);
        $('#editParam2Help').text(param2Help);
    } else {
        $('#divParam2').hide();
    }
    
    // Ejecutar actualización de vista previa cambiaria inicial
    $('#editTipoValor').trigger('change');
    $('#editRefConcepto').val(parametro.ID_REF_CONCEPTO || '');
    actualizarVistaPreviaCambiaria();
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalEditarParametro'));
    modal.show();
}

/**
 * Función que actualiza en tiempo real la previsualización del tipo de cambio
 */
function actualizarVistaPreviaCambiaria() {
    if (entornoActual !== 'uy') {
        $('#divMonedaTc').hide();
        $('#divValorConvertido').hide();
        return;
    }
    
    const tipo = $('#editTipoValor').val();
    
    if (tipo === 'P') {
        // Porcentaje
        $('#divMonedaTc').hide();
        
        const refId = $('#editRefConcepto').val();
        const pct = parseFloat($('#editValorDefault1').val()) || 0;
        
        if (refId) {
            const refConcepto = listaConceptosOriginales.find(c => c.ID_CE == refId);
            if (refConcepto) {
                let valorRefUyu = 0;
                let tc = parseFloat(refConcepto.TIPO_CAMBIO) || 1.0;
                
                if (refConcepto.VALOR_DEFAULT_1_UYU !== null && refConcepto.VALOR_DEFAULT_1_UYU !== undefined) {
                    valorRefUyu = parseFloat(refConcepto.VALOR_DEFAULT_1_UYU);
                } else {
                    const valDef = parseFloat(refConcepto.VALOR_DEFAULT_1) || 0;
                    valorRefUyu = valDef * tc;
                }
                
                const valorFinalUyu = valorRefUyu * (pct / 100);
                
                $('#divValorConvertido').show();
                $('#spanValorConvertido1').html(`<strong>Valor estimado en $UYU:</strong> $UYU ${valorFinalUyu.toLocaleString('es-UY', {minimumFractionDigits: 2, maximumFractionDigits: 2})} <br><small class="text-muted">Calculado como ${pct}% de ${refConcepto.CONCEPTO} ($UYU ${valorRefUyu.toLocaleString('es-UY', {minimumFractionDigits: 2})})</small>`);
                $('#spanValorConvertido2').hide();
            } else {
                $('#divValorConvertido').hide();
            }
        } else {
            $('#divValorConvertido').hide();
        }
        return;
    }
    
    // Importe Fijo (tipo === 'I')
    $('#divMonedaTc').show();
    $('#divValorConvertido').show();
    
    const moneda = $('#editMoneda').val();
    const tc = parseFloat($('#editTipoCambio').val()) || 0;
    
    const v1 = parseFloat($('#editValorDefault1').val()) || 0;
    const v2 = parseFloat($('#editValorDefault2').val()) || 0;
    
    // Actualizar unidades de los inputs
    $('#editParam1Unidad').text(moneda);
    $('#editParam2Unidad').text(moneda);
    
    if (tc > 0) {
        if (moneda === 'USD') {
            const v1Uyu = v1 * tc;
            const v2Uyu = v2 * tc;
            
            $('#spanValorConvertido1').html(`<strong>Parámetro 1:</strong> U$D ${v1.toFixed(2)} ≈ $UYU ${v1Uyu.toFixed(2)}`);
            if ($('#divParam2').is(':visible')) {
                $('#spanValorConvertido2').show().html(`<strong>Parámetro 2:</strong> U$D ${v2.toFixed(2)} ≈ $UYU ${v2Uyu.toFixed(2)}`);
            } else {
                $('#spanValorConvertido2').hide();
            }
        } else {
            const v1Usd = v1 / tc;
            const v2Usd = v2 / tc;
            
            $('#spanValorConvertido1').html(`<strong>Parámetro 1:</strong> $UYU ${v1.toFixed(2)} ≈ U$D ${v1Usd.toFixed(2)}`);
            if ($('#divParam2').is(':visible')) {
                $('#spanValorConvertido2').show().html(`<strong>Parámetro 2:</strong> $UYU ${v2.toFixed(2)} ≈ U$D ${v2Usd.toFixed(2)}`);
            } else {
                $('#spanValorConvertido2').hide();
            }
        }
    } else {
        $('#spanValorConvertido1').text('Tipo de cambio inválido');
        $('#spanValorConvertido2').hide();
    }
}

/**
 * Determinar si un concepto requiere Parámetro 2
 */
function requiereParam2(concepto) {
    const conceptosConParam2 = ['Despachante', 'Suma asegurada'];
    return conceptosConParam2.includes(concepto);
}

/**
 * Obtener texto de ayuda para cada parámetro
 */
function obtenerAyudaParametro(concepto, numParam) {
    const ayudas = {
        'Flete': { 1: 'Costo de flete por defecto en USD' },
        'Seguro': { 1: 'Porcentaje sobre (FOB + Flete). Ejemplo: 5 = 5%' },
        'Derechos': { 1: 'Porcentaje sobre CIF. Ejemplo: 21 = 21%' },
        'Tasa estadistica': { 1: 'Porcentaje sobre CIF. Ejemplo: 3 = 3%' },
        'IVA General': { 1: 'Porcentaje sobre Base Imponible. Ejemplo: 21 = 21%' },
        'IVA Adicional': { 1: 'Porcentaje sobre Base Imponible. Ejemplo: 10.5 = 10.5%' },
        'IIGG': { 1: 'Porcentaje sobre Base Imponible. Ejemplo: 6 = 6%' },
        'IIBB': { 1: 'Porcentaje sobre Base Imponible. Ejemplo: 3.5 = 3.5%' },
        'SIM': { 1: 'Valor fijo en moneda local' },
        'Antidumping': { 1: 'Valor por defecto (generalmente 0)' },
        'Despachante': { 
            1: 'Honorario base del despachante en USD',
            2: 'Multiplicador para incluir impuestos. Ejemplo: 21 = 21% adicional'
        },
        'Terminal': { 1: 'Costo fijo de terminal en USD' },
        'Suma asegurada': { 
            1: 'Primer multiplicador en porcentaje. Ejemplo: 10 = 10%',
            2: 'Segundo multiplicador en porcentaje. Ejemplo: 10 = 10%'
        }
    };
    
    return ayudas[concepto]?.[numParam] || '';
}

/**
 * Guardar cambios del parámetro
 */
function guardarParametro() {
    const form = $('#formEditarParametro');
    
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    
    // Obtener valores del formulario
    let valor1 = $('#editValorDefault1').val();
    let valor2 = $('#editValorDefault2').val();
    
    let val1Num = (valor1 !== '' && valor1 !== null && valor1 !== undefined) ? parseFloat(valor1) : null;
    let val2Num = (valor2 !== '' && valor2 !== null && valor2 !== undefined) ? parseFloat(valor2) : null;
    
    const tipoSeleccionado = $('#editTipoValor').val();
    
    // Si es porcentaje, convertir de % a decimal (dividir entre 100)
    if (tipoSeleccionado === 'P') {
        if (val1Num !== null) val1Num = val1Num / 100;
        if (val2Num !== null) val2Num = val2Num / 100;
    }
    
    let moneda = 'USD';
    let tc = 1.0;
    let val1Uyu = val1Num;
    let val2Uyu = val2Num;
    
    if (entornoActual === 'uy' && tipoSeleccionado === 'I') {
        moneda = $('#editMoneda').val() || 'USD';
        tc = parseFloat($('#editTipoCambio').val()) || 1.0;
        
        if (moneda === 'USD') {
            val1Uyu = val1Num !== null ? val1Num * tc : null;
            val2Uyu = val2Num !== null ? val2Num * tc : null;
        } else {
            // Se ingresó en UYU
            val1Uyu = val1Num;
            val2Uyu = val2Num;
            // Guardamos el convertido a USD en las columnas base
            val1Num = val1Num !== null && tc > 0 ? val1Num / tc : null;
            val2Num = val2Num !== null && tc > 0 ? val2Num / tc : null;
        }
    }
    
    const data = {
        id_ce: $('#editIdCe').val(),
        tipo_valor: tipoSeleccionado,
        valor_default_1: val1Num,
        valor_default_2: val2Num,
        moneda: moneda,
        tipo_cambio: tc,
        valor_default_1_uyu: val1Uyu,
        valor_default_2_uyu: val2Uyu,
        id_ref_concepto: $('#editRefConcepto').val() || null
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
        url: 'controller/actualizarParametro.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Cerrar modal inmediatamente
                const modalEl = document.getElementById('modalEditarParametro');
                const modalInst = bootstrap.Modal.getInstance(modalEl);
                if (modalInst) {
                    modalInst.hide();
                }
                
                // Recargar tabla inmediatamente
                cargarParametros();
                
                Swal.fire({
                    title: '¡Actualizado!',
                    text: 'Parámetro actualizado correctamente',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message || 'Error al actualizar el parámetro',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al guardar los cambios',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    });
}

/**
 * Utilidades de formato
 */
function formatearParametro(valor, tipo, idRef = null) {
    if (tipo === 'P') {
        // Porcentaje: mostrar como % con 2-4 decimales
        const porcentaje = parseFloat(valor) * 100;
        let txt = porcentaje.toFixed(4).replace(/\.?0+$/, '') + '%';
        if (idRef) {
            const refConcepto = listaConceptosOriginales.find(c => c.ID_CE == idRef);
            if (refConcepto) {
                txt += ` <br><small class="text-muted">de ${refConcepto.CONCEPTO}</small>`;
            }
        }
        return txt;
    } else {
        // Importe: mostrar con formato moneda
        return parseFloat(valor).toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    
    // Si es objeto Date
    if (fecha instanceof Date) {
        return fecha.toLocaleDateString('es-AR') + ' ' + fecha.toLocaleTimeString('es-AR');
    }
    
    // Si es string, intentar parsear
    const d = new Date(fecha);
    if (!isNaN(d.getTime())) {
        return d.toLocaleDateString('es-AR') + ' ' + d.toLocaleTimeString('es-AR');
    }
    
    return fecha;
}

function mostrarError(mensaje) {
    Swal.fire({
        title: 'Error',
        text: mensaje,
        icon: 'error',
        confirmButtonColor: '#dc3545'
    });
}

/**
 * Abrir modal de creación de concepto
 */
function abrirModalNuevoConcepto() {
    $('#formNuevoConcepto')[0].reset();
    
    // Llenar select de referencias para nuevo concepto
    const selectNuevoRef = $('#nuevoRefConcepto');
    selectNuevoRef.empty().append('<option value="">(Base por defecto, ej: CIF/FOB)</option>');
    listaConceptosOriginales.forEach(c => {
        selectNuevoRef.append(`<option value="${c.ID_CE}">${c.CONCEPTO}</option>`);
    });
    
    // Tipo de valor cambia dinámicamente las unidades, visibilidad de Calcular sobre, y de moneda/TC
    $('#nuevoTipoValor').off('change').on('change', function() {
        const tipo = $(this).val();
        if (tipo === 'P') {
            $('#divNuevoRefConcepto').show();
        } else {
            $('#divNuevoRefConcepto').hide();
            $('#nuevoRefConcepto').val('');
        }
        
        if (entornoActual === 'uy' && tipo === 'I') {
            $('#divNuevoMonedaTc').show();
            $('#divNuevoValorConvertido').show();
            const moneda = $('#nuevoMoneda').val();
            $('#nuevoParam1Unidad').text(moneda);
            $('#nuevoParam2Unidad').text(moneda);
        } else {
            $('#divNuevoMonedaTc').hide();
            $('#divNuevoValorConvertido').hide();
            const unidad = tipo === 'P' ? '%' : 'USD';
            $('#nuevoParam1Unidad').text(unidad);
            $('#nuevoParam2Unidad').text(unidad);
        }
        actualizarVistaPreviaNuevoConcepto();
    });

    $('#nuevoMoneda').off('change').on('change', function() {
        const moneda = $(this).val();
        $('#nuevoParam1Unidad').text(moneda);
        $('#nuevoParam2Unidad').text(moneda);
        actualizarVistaPreviaNuevoConcepto();
    });

    $('#nuevoTipoCambio, #nuevoValorDefault1, #nuevoValorDefault2, #nuevoRefConcepto').off('input change').on('input change', function() {
        actualizarVistaPreviaNuevoConcepto();
    });

    // Configurar estado inicial
    $('#nuevoTipoValor').trigger('change');
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalNuevoConcepto'));
    modal.show();
}

/**
 * Vista previa cambiaria para nuevo concepto
 */
function actualizarVistaPreviaNuevoConcepto() {
    if (entornoActual !== 'uy') {
        $('#divNuevoMonedaTc').hide();
        $('#divNuevoValorConvertido').hide();
        return;
    }

    const tipo = $('#nuevoTipoValor').val();

    if (tipo === 'P') {
        // Porcentaje
        $('#divNuevoMonedaTc').hide();
        
        const refId = $('#nuevoRefConcepto').val();
        const pct = parseFloat($('#nuevoValorDefault1').val()) || 0;
        
        if (refId) {
            const refConcepto = listaConceptosOriginales.find(c => c.ID_CE == refId);
            if (refConcepto) {
                let valorRefUyu = 0;
                let tc = parseFloat(refConcepto.TIPO_CAMBIO) || 1.0;
                
                if (refConcepto.VALOR_DEFAULT_1_UYU !== null && refConcepto.VALOR_DEFAULT_1_UYU !== undefined) {
                    valorRefUyu = parseFloat(refConcepto.VALOR_DEFAULT_1_UYU);
                } else {
                    const valDef = parseFloat(refConcepto.VALOR_DEFAULT_1) || 0;
                    valorRefUyu = valDef * tc;
                }
                
                const valorFinalUyu = valorRefUyu * (pct / 100);
                
                $('#divNuevoValorConvertido').show();
                $('#spanNuevoValorConvertido1').html(`<strong>Valor estimado en $UYU:</strong> $UYU ${valorFinalUyu.toLocaleString('es-UY', {minimumFractionDigits: 2, maximumFractionDigits: 2})} <br><small class="text-muted">Calculado como ${pct}% de ${refConcepto.CONCEPTO} ($UYU ${valorRefUyu.toLocaleString('es-UY', {minimumFractionDigits: 2})})</small>`);
                $('#spanNuevoValorConvertido2').hide();
            } else {
                $('#divNuevoValorConvertido').hide();
            }
        } else {
            $('#divNuevoValorConvertido').hide();
        }
        return;
    }

    // Importe Fijo (tipo === 'I')
    $('#divNuevoMonedaTc').show();
    $('#divNuevoValorConvertido').show();

    const moneda = $('#nuevoMoneda').val();
    const tc = parseFloat($('#nuevoTipoCambio').val()) || 0;
    const v1 = parseFloat($('#nuevoValorDefault1').val()) || 0;
    const v2 = parseFloat($('#nuevoValorDefault2').val()) || 0;

    if (tc > 0) {
        if (moneda === 'USD') {
            const v1Uyu = v1 * tc;
            const v2Uyu = v2 * tc;
            $('#spanNuevoValorConvertido1').html(`<strong>Parámetro 1:</strong> U$D ${v1.toFixed(2)} ≈ $UYU ${v1Uyu.toFixed(2)}`);
            if ($('#divNuevoParam2').is(':visible') || v2 > 0) {
                $('#divNuevoParam2').show();
                $('#spanNuevoValorConvertido2').show().html(`<strong>Parámetro 2:</strong> U$D ${v2.toFixed(2)} ≈ $UYU ${v2Uyu.toFixed(2)}`);
            } else {
                $('#spanNuevoValorConvertido2').hide();
            }
        } else {
            const v1Usd = v1 / tc;
            const v2Usd = v2 / tc;
            $('#spanNuevoValorConvertido1').html(`<strong>Parámetro 1:</strong> $UYU ${v1.toFixed(2)} ≈ U$D ${v1Usd.toFixed(2)}`);
            if ($('#divNuevoParam2').is(':visible') || v2 > 0) {
                $('#divNuevoParam2').show();
                $('#spanNuevoValorConvertido2').show().html(`<strong>Parámetro 2:</strong> $UYU ${v2.toFixed(2)} ≈ U$D ${v2Usd.toFixed(2)}`);
            } else {
                $('#spanNuevoValorConvertido2').hide();
            }
        }
    } else {
        $('#spanNuevoValorConvertido1').text('Tipo de cambio inválido');
        $('#spanNuevoValorConvertido2').hide();
    }
}

/**
 * Guardar nuevo concepto
 */
function guardarNuevoConcepto() {
    const form = $('#formNuevoConcepto');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }

    let valor1 = $('#nuevoValorDefault1').val();
    let valor2 = $('#nuevoValorDefault2').val();

    let val1Num = (valor1 !== '' && valor1 !== null && valor1 !== undefined) ? parseFloat(valor1) : 0;
    let val2Num = (valor2 !== '' && valor2 !== null && valor2 !== undefined) ? parseFloat(valor2) : null;

    const tipo = $('#nuevoTipoValor').val();

    if (tipo === 'P') {
        val1Num = val1Num / 100;
        if (val2Num !== null) val2Num = val2Num / 100;
    }

    let moneda = 'USD';
    let tc = 1.0;
    let val1Uyu = val1Num;
    let val2Uyu = val2Num;

    if (entornoActual === 'uy' && tipo === 'I') {
        moneda = $('#nuevoMoneda').val() || 'USD';
        tc = parseFloat($('#nuevoTipoCambio').val()) || 1.0;

        if (moneda === 'USD') {
            val1Uyu = val1Num !== null ? val1Num * tc : null;
            val2Uyu = val2Num !== null ? val2Num * tc : null;
        } else {
            val1Uyu = val1Num;
            val2Uyu = val2Num;
            val1Num = val1Num !== null && tc > 0 ? val1Num / tc : null;
            val2Num = val2Num !== null && tc > 0 ? val2Num / tc : null;
        }
    }

    const data = {
        concepto: $('#nuevoConceptoNombre').val(),
        tipo_valor: tipo,
        valor_default_1: val1Num,
        valor_default_2: val2Num,
        moneda: moneda,
        tipo_cambio: tc,
        valor_default_1_uyu: val1Uyu,
        valor_default_2_uyu: val2Uyu,
        id_ref_concepto: $('#nuevoRefConcepto').val() || null
    };

    Swal.fire({
        title: 'Creando concepto...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'controller/crearConcepto.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Cerrar modal
                const modalEl = document.getElementById('modalNuevoConcepto');
                const modalInst = bootstrap.Modal.getInstance(modalEl);
                if (modalInst) {
                    modalInst.hide();
                }
                
                // Recargar tabla
                cargarParametros();

                Swal.fire({
                    title: '¡Creado!',
                    text: 'Concepto creado correctamente',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message || 'Error al crear el concepto',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al crear el concepto',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    });
}

/**
 * Eliminar concepto
 */
function eliminarConcepto(id, nombre) {
    Swal.fire({
        title: '¿Está seguro de eliminar este concepto?',
        text: `El concepto "${nombre}" será eliminado de los parámetros. Esto no alterará estimaciones pasadas, pero no aparecerá en nuevas proyecciones.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Eliminando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'controller/eliminarConcepto.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id_ce: id }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        cargarParametros();
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: 'Concepto eliminado correctamente',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Error al eliminar el concepto',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al eliminar el concepto',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        }
    });
}
