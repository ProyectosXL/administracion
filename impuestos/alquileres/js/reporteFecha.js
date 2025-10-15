/**
 * reporteFecha.js
 * Maneja la funcionalidad de la pestaña "Reporte a Fecha"
 * Muestra todas las sucursales en columnas con conceptos en filas
 */

// Variables globales para el reporte a fecha
let datosReporteFecha = null;
let tablaReporteFecha = null;

// Inicialización
$(document).ready(function() {
    inicializarReporteFecha();
});

/**
 * Inicializa los eventos y configuraciones del reporte a fecha
 */
function inicializarReporteFecha() {
    // Evento para aplicar filtros
    $('#btnAplicarReporte').on('click', function() {
        aplicarFiltrosReporte();
    });
    
    // Evento para limpiar filtros
    $('#btnLimpiarReporte').on('click', function() {
        limpiarFiltrosReporte();
    });
    
    // Evento para exportar
    $('#btnExportarReporte').on('click', function() {
        exportarReporteFecha();
    });
    
    // Evento para mostrar leyenda
    $('#btnLeyendaReporte').on('click', function() {
        mostrarLeyendaReporte();
    });
    
    // Validación de fechas
    $('#fechaDesdeReporte, #fechaHastaReporte').on('change', function() {
        validarRangoFechasReporte();
    });
}

/**
 * Aplica los filtros y carga los datos del reporte
 */
function aplicarFiltrosReporte() {
    const fechaDesde = $('#fechaDesdeReporte').val();
    const fechaHasta = $('#fechaHastaReporte').val();
    
    // Validaciones
    if (!fechaDesde || !fechaHasta) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Por favor seleccione ambas fechas',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3498db'
        });
        return;
    }
    
    if (new Date(fechaDesde) > new Date(fechaHasta)) {
        Swal.fire({
            icon: 'error',
            title: 'Rango inválido',
            text: 'La fecha desde no puede ser mayor a la fecha hasta',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#e74c3c'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Cargando datos',
        text: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Realizar petición AJAX
    $.ajax({
        url: 'Controller/getReporteFecha.php',
        method: 'POST',
        data: {
            fechaDesde: fechaDesde,
            fechaHasta: fechaHasta
        },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            console.log('Respuesta del servidor:', response);
            
            if (response.success) {
                datosReporteFecha = response.data;
                renderizarReporteFecha(response.data);
                mostrarSeccionesReporte();
                actualizarKPIsReporte(response.data);
            } else {
                console.error('Error en respuesta:', response);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: `<p>${response.message || 'Error al cargar los datos'}</p>
                           ${response.file ? `<small>Archivo: ${response.file}:${response.line}</small>` : ''}`,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#e74c3c'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('Error AJAX:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            let errorMsg = 'No se pudo cargar el reporte. Intente nuevamente.';
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    errorMsg = response.message;
                    if (response.file) {
                        errorMsg += `\n\nArchivo: ${response.file}:${response.line}`;
                    }
                }
            } catch (e) {
                errorMsg += '\n\n' + xhr.responseText;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: errorMsg,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#e74c3c'
            });
        }
    });
}

/**
 * Renderiza la tabla del reporte con los datos recibidos
 */
function renderizarReporteFecha(data) {
    const headerRow = $('#headerRowReporte');
    const tableBody = $('#tableBodyReporte');
    const footerRow = $('#footerRowReporte');
    
    // Limpiar contenido previo
    headerRow.find('th:not(.fixed-column)').remove();
    tableBody.empty();
    footerRow.find('td:not(.fixed-column)').remove();
    
    // Agregar headers de sucursales
    data.sucursales.forEach(sucursal => {
        headerRow.append(`
            <th class="text-center" title="${sucursal.nombre}">
                ${sucursal.numero}
                <br>
                <small style="font-size: 0.75em; font-weight: normal;">${sucursal.nombre}</small>
            </th>
        `);
    });
    
    // NO agregar columna de totales - ya no se necesita
    
    // Agregar filas de conceptos
    data.conceptos.forEach(concepto => {
        // Determinar estilo según tipo de fila
        let rowStyle = '';
        let rowClass = '';
        let cellClass = '';
        
        if (concepto.is_subtotal) {
            rowStyle = 'background-color: #d5dbdb; font-weight: bold;';
        } else if (concepto.is_metric) {
            rowStyle = 'background-color: #e8f5e9; font-weight: 600;';
        } else if (concepto.is_percentage) {
            rowStyle = 'background-color: #fff3e0; font-weight: bold; font-size: 1.1em;';
            rowClass = 'row-porcentaje-costo';
        }
        
        let row = `<tr style="${rowStyle}" class="${rowClass}">
            <td class="fixed-column" style="font-weight: 600;">${concepto.nombre}</td>`;
        
        // Agregar valores por sucursal
        data.sucursales.forEach(sucursal => {
            const valor = concepto.valores[sucursal.id] || 0;
            
            let formattedValue;
            if (concepto.is_percentage) {
                formattedValue = valor !== null ? formatearPorcentaje(valor) : '-';
            } else {
                formattedValue = formatearMoneda(valor);
            }
            
            row += `<td class="text-right ${cellClass}" data-valor="${valor}">${formattedValue}</td>`;
        });
        
        // NO agregar columna de totales
        
        row += `</tr>`;
        
        tableBody.append(row);
    });
    
    // NO calcular ni mostrar footer con totales
    
    // Inicializar DataTable si no existe
    if (tablaReporteFecha) {
        tablaReporteFecha.destroy();
    }
    
    tablaReporteFecha = $('#tablaReporteFecha').DataTable({
        responsive: false,
        scrollX: true,
        scrollCollapse: true,
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        fixedColumns: {
            leftColumns: 1
        },
        language: {
            decimal: ",",
            thousands: ".",
            emptyTable: "No hay datos disponibles"
        }
    });
}

/**
 * Actualiza los KPIs del reporte
 */
function actualizarKPIsReporte(data) {
    // KPI 1: Cantidad de sucursales
    const cantidadSucursales = data.sucursales.length;
    $('#kpiCantidadSucursales').text(cantidadSucursales);
    
    // Buscar la fila de % Costo de Ocupación
    let conceptoPorcentajeCosto = null;
    data.conceptos.forEach(concepto => {
        if (concepto.is_percentage && concepto.nombre.toLowerCase().includes('costo')) {
            conceptoPorcentajeCosto = concepto;
        }
    });
    
    if (conceptoPorcentajeCosto) {
        // KPI 2: % Costo de Ocupación Total (promedio de todas las sucursales)
        let sumaPorcentajes = 0;
        let contadorValidos = 0;
        
        data.sucursales.forEach(sucursal => {
            const valor = conceptoPorcentajeCosto.valores[sucursal.id];
            if (valor !== null && valor !== undefined && !isNaN(valor)) {
                sumaPorcentajes += parseFloat(valor);
                contadorValidos++;
            }
        });
        
        const porcentajeTotal = contadorValidos > 0 ? sumaPorcentajes / contadorValidos : 0;
        $('#kpiPorcentajeCostoTotal').text(formatearPorcentaje(porcentajeTotal));
        
        // KPI 3: Menor Costo de Ocupación (mejor sucursal)
        let menorValor = Infinity;
        let sucursalMenor = null;
        
        data.sucursales.forEach(sucursal => {
            const valor = conceptoPorcentajeCosto.valores[sucursal.id];
            if (valor !== null && valor !== undefined && !isNaN(valor) && valor < menorValor) {
                menorValor = parseFloat(valor);
                sucursalMenor = sucursal;
            }
        });
        
        if (sucursalMenor) {
            $('#kpiMenorCosto').text(formatearPorcentaje(menorValor));
            $('#kpiMenorCostoDetalle').html(
                `<i class="bi bi-building"></i> Sucursal ${sucursalMenor.numero} - ${sucursalMenor.nombre}`
            );
        } else {
            $('#kpiMenorCosto').text('--');
            $('#kpiMenorCostoDetalle').text('--');
        }
        
        // KPI 4: Mayor Costo de Ocupación (peor sucursal)
        let mayorValor = -Infinity;
        let sucursalMayor = null;
        
        data.sucursales.forEach(sucursal => {
            const valor = conceptoPorcentajeCosto.valores[sucursal.id];
            if (valor !== null && valor !== undefined && !isNaN(valor) && valor > mayorValor) {
                mayorValor = parseFloat(valor);
                sucursalMayor = sucursal;
            }
        });
        
        if (sucursalMayor) {
            $('#kpiMayorCosto').text(formatearPorcentaje(mayorValor));
            $('#kpiMayorCostoDetalle').html(
                `<i class="bi bi-building"></i> Sucursal ${sucursalMayor.numero} - ${sucursalMayor.nombre}`
            );
        } else {
            $('#kpiMayorCosto').text('--');
            $('#kpiMayorCostoDetalle').text('--');
        }
    } else {
        // Si no hay datos de porcentaje
        $('#kpiPorcentajeCostoTotal').text('--');
        $('#kpiMenorCosto').text('--');
        $('#kpiMenorCostoDetalle').text('--');
        $('#kpiMayorCosto').text('--');
        $('#kpiMayorCostoDetalle').text('--');
    }
}

/**
 * Muestra las secciones del reporte (KPIs y tabla)
 */
function mostrarSeccionesReporte() {
    $('#emptyStateReporte').hide();
    $('#kpisSectionReporte').fadeIn();
    $('#tableSectionReporte').fadeIn();
}

/**
 * Limpia los filtros y oculta el reporte
 */
function limpiarFiltrosReporte() {
    $('#fechaDesdeReporte').val('');
    $('#fechaHastaReporte').val('');
    
    $('#kpisSectionReporte').hide();
    $('#tableSectionReporte').hide();
    $('#emptyStateReporte').fadeIn();
    
    datosReporteFecha = null;
    
    if (tablaReporteFecha) {
        tablaReporteFecha.destroy();
        tablaReporteFecha = null;
    }
}

/**
 * Valida el rango de fechas
 */
function validarRangoFechasReporte() {
    const fechaDesde = $('#fechaDesdeReporte').val();
    const fechaHasta = $('#fechaHastaReporte').val();
    
    if (fechaDesde && fechaHasta) {
        if (new Date(fechaDesde) > new Date(fechaHasta)) {
            $('#fechaHastaReporte').addClass('is-invalid');
        } else {
            $('#fechaHastaReporte').removeClass('is-invalid');
        }
    }
}

/**
 * Exporta el reporte a Excel
 */
function exportarReporteFecha() {
    if (!datosReporteFecha) {
        Swal.fire({
            icon: 'warning',
            title: 'No hay datos',
            text: 'Primero debe generar el reporte',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3498db'
        });
        return;
    }
    
    const fechaDesde = $('#fechaDesdeReporte').val();
    const fechaHasta = $('#fechaHastaReporte').val();
    
    // Crear formulario oculto para enviar datos
    const form = $('<form>', {
        method: 'POST',
        action: 'Controller/exportarReporteFecha.php',
        target: '_blank'
    });
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'fechaDesde',
        value: fechaDesde
    }));
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'fechaHasta',
        value: fechaHasta
    }));
    
    form.append($('<input>', {
        type: 'hidden',
        name: 'datos',
        value: JSON.stringify(datosReporteFecha)
    }));
    
    $('body').append(form);
    form.submit();
    form.remove();
}

/**
 * Muestra la leyenda de colores
 */
function mostrarLeyendaReporte() {
    Swal.fire({
        title: 'Leyenda de Colores',
        html: `
            <div style="text-align: left; padding: 10px;">
                <div style="margin-bottom: 10px;">
                    <span style="display: inline-block; width: 20px; height: 20px; background-color: #e8f5e9; border: 1px solid #ccc; margin-right: 10px;"></span>
                    <strong>Verde claro:</strong> Valores positivos bajos
                </div>
                <div style="margin-bottom: 10px;">
                    <span style="display: inline-block; width: 20px; height: 20px; background-color: #ffebee; border: 1px solid #ccc; margin-right: 10px;"></span>
                    <strong>Rojo claro:</strong> Valores negativos o críticos
                </div>
                <div style="margin-bottom: 10px;">
                    <span style="display: inline-block; width: 20px; height: 20px; background-color: #fff3e0; border: 1px solid #ccc; margin-right: 10px;"></span>
                    <strong>Naranja claro:</strong> Valores de atención
                </div>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#3498db'
    });
}

/**
 * Obtiene la clase de color según el concepto y valor
 */
function obtenerColorClase(conceptoId, valor) {
    const valorNum = parseFloat(valor);
    
    // Conceptos que deberían ser bajos (gastos)
    const conceptosGastos = [1, 2, 3, 4, 5]; // IDs de conceptos de gastos
    
    if (conceptosGastos.includes(parseInt(conceptoId))) {
        if (valorNum > 10000) return 'bg-warning-light';
        if (valorNum > 5000) return 'bg-info-light';
        return 'bg-success-light';
    }
    
    // Conceptos que deberían ser altos (ingresos)
    if (valorNum < 0) return 'bg-danger-light';
    if (valorNum < 5000) return 'bg-warning-light';
    return 'bg-success-light';
}

/**
 * Formatea un número como moneda
 */
function formatearMoneda(valor) {
    const numero = parseFloat(valor) || 0;
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(numero);
}

/**
 * Formatea un número como porcentaje
 */
function formatearPorcentaje(valor) {
    const numero = parseFloat(valor) || 0;
    return numero.toFixed(2) + '%';
}
