/**
 * parametros.js - Gestión de Parámetros de Importación
 */

let tablaParametros;
let parametroActual = null;

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
        success: function(response) {
            console.log('Respuesta recibida:', response);
            if (response.success && response.data) {
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
    const tbody = $('#tablaParametros tbody');
    tbody.empty();
    
    datos.forEach(function(param) {
        const tipoTexto = param.TIPO_VALOR === 'P' ? 'Porcentaje' : 'Importe Fijo';
        const tipoBadge = param.TIPO_VALOR === 'P' ? 'info' : 'secondary';
        
        const param1Display = param.VALOR_DEFAULT_1 !== null ? 
            formatearParametro(param.VALOR_DEFAULT_1, param.TIPO_VALOR) : '-';
        
        const param2Display = param.VALOR_DEFAULT_2 !== null ? 
            formatearParametro(param.VALOR_DEFAULT_2, param.TIPO_VALOR) : '-';
        
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
                    <button class="btn btn-sm btn-primary" onclick='abrirModalEditar(${JSON.stringify(param)})' title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
        `;
        
        tbody.append(fila);
    });
    
    // Inicializar o destruir DataTable
    if ($.fn.DataTable.isDataTable('#tablaParametros')) {
        tablaParametros.destroy();
    }
    
    tablaParametros = $('#tablaParametros').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
        },
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true
    });
}

/**
 * Abrir modal de edición
 */
function abrirModalEditar(parametro) {
    parametroActual = parametro;
    
    // Llenar campos del modal
    $('#editIdCe').val(parametro.ID_CE);
    $('#editConcepto').val(parametro.CONCEPTO);
    
    // Mostrar tipo de valor con formato visual mejorado
    const tipoTexto = parametro.TIPO_VALOR === 'P' ? 'Porcentaje (P)' : 'Importe Fijo (I)';
    const tipoDesc = parametro.TIPO_VALOR === 'P' ? 
        'Los valores se multiplican (ej: 0.21 = 21%)' : 
        'Valor fijo en moneda';
    
    $('#editTipoValor').text(tipoTexto);
    $('#editTipoValorContainer').attr('title', tipoDesc);
    
    // Configurar Parámetro 1
    // Para porcentajes, mostrar como porcentaje (multiplicar por 100)
    let valor1Display = parametro.VALOR_DEFAULT_1 || '';
    if (valor1Display !== '' && parametro.TIPO_VALOR === 'P') {
        valor1Display = (parseFloat(valor1Display) * 100).toString();
    }
    $('#editValorDefault1').val(valor1Display);
    
    const param1Unidad = parametro.TIPO_VALOR === 'P' ? '%' : 'USD';
    $('#editParam1Unidad').text(param1Unidad);
    
    const param1Help = obtenerAyudaParametro(parametro.CONCEPTO, 1);
    $('#editParam1Help').text(param1Help);
    
    // Configurar Parámetro 2
    if (parametro.VALOR_DEFAULT_2 !== null || requiereParam2(parametro.CONCEPTO)) {
        $('#divParam2').show();
        
        // Para porcentajes, mostrar como porcentaje (multiplicar por 100)
        let valor2Display = parametro.VALOR_DEFAULT_2 || '';
        if (valor2Display !== '' && parametro.TIPO_VALOR === 'P') {
            valor2Display = (parseFloat(valor2Display) * 100).toString();
        }
        $('#editValorDefault2').val(valor2Display);
        
        const param2Unidad = parametro.TIPO_VALOR === 'P' ? '%' : 'USD';
        $('#editParam2Unidad').text(param2Unidad);
        
        const param2Help = obtenerAyudaParametro(parametro.CONCEPTO, 2);
        $('#editParam2Help').text(param2Help);
    } else {
        $('#divParam2').hide();
    }
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalEditarParametro'));
    modal.show();
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
    let valor1 = $('#editValorDefault1').val() || null;
    let valor2 = $('#editValorDefault2').val() || null;
    
    // Si es porcentaje, convertir de % a decimal (dividir entre 100)
    if (parametroActual.TIPO_VALOR === 'P') {
        if (valor1 !== null) {
            valor1 = (parseFloat(valor1) / 100).toString();
        }
        if (valor2 !== null) {
            valor2 = (parseFloat(valor2) / 100).toString();
        }
    }
    
    const data = {
        id_ce: $('#editIdCe').val(),
        valor_default_1: valor1,
        valor_default_2: valor2
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
                Swal.fire({
                    title: '¡Actualizado!',
                    text: 'Parámetro actualizado correctamente',
                    icon: 'success',
                    confirmButtonColor: '#667eea',
                    timer: 2000
                }).then(() => {
                    // Cerrar modal
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarParametro')).hide();
                    // Recargar tabla
                    cargarParametros();
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
function formatearParametro(valor, tipo) {
    if (tipo === 'P') {
        // Porcentaje: mostrar como % con 2-4 decimales
        const porcentaje = parseFloat(valor) * 100;
        return porcentaje.toFixed(4).replace(/\.?0+$/, '') + '%';
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
