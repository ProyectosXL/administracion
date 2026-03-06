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
                // Verificar si hay sucursales y si hay datos
                if (!response.data.sucursales || response.data.sucursales.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin sucursales',
                        text: 'No se encontraron sucursales para el entorno seleccionado.',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#f39c12'
                    });
                    return;
                }
                
                // Verificar si hay conceptos con datos
                let hayDatos = false;
                if (response.data.conceptos && response.data.conceptos.length > 0) {
                    for (let concepto of response.data.conceptos) {
                        for (let idSucursal in concepto.valores) {
                            if (concepto.valores[idSucursal] != 0) {
                                hayDatos = true;
                                break;
                            }
                        }
                        if (hayDatos) break;
                    }
                }
                
                if (!hayDatos) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin datos',
                        html: `No se encontraron datos para el período seleccionado:<br>
                               <strong>Desde:</strong> ${fechaDesde}<br>
                               <strong>Hasta:</strong> ${fechaHasta}<br><br>
                               <small>Intente con otro rango de fechas o verifique que existan datos cargados en el sistema para este período.</small>`,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#3498db'
                    });
                    // Renderizar la tabla vacía de todas formas para que el usuario vea la estructura
                    datosReporteFecha = response.data;
                    renderizarReporteFecha(response.data);
                    mostrarSeccionesReporte();
                    return;
                }
                
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
    // Destruir DataTable existente primero
    if (tablaReporteFecha) {
        tablaReporteFecha.destroy();
        tablaReporteFecha = null;
    }
    
    const headerRow = $('#headerRowReporte');
    const tableBody = $('#tableBodyReporte');
    const footerRow = $('#footerRowReporte');
    
    // Limpiar contenido previo completamente
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
    
    // Agregar fila de % Costo de Ocupación Período Anterior (YoY)
    let rowAnterior = `<tr class="row-yoy-anterior" style="font-weight: bold;">
        <td class="fixed-column" style="font-weight: 600;">% Costo de Ocupación (Período Anterior YoY)</td>`;
    
    data.sucursales.forEach(sucursal => {
        const valorAnterior = data.porcentajesAnteriores[sucursal.id];
        const formattedValue = valorAnterior !== null && valorAnterior !== undefined 
            ? formatearPorcentaje(valorAnterior) 
            : '-';
        
        rowAnterior += `<td class="text-right">${formattedValue}</td>`;
    });
    
    rowAnterior += `</tr>`;
    tableBody.append(rowAnterior);
    
    // Agregar fila de Variación Relativa (%)
    let rowVariacion = `<tr class="row-yoy-variacion" style="font-weight: bold;">
        <td class="fixed-column" style="font-weight: 600;">Variación Relativa (%)</td>`;
    
    data.sucursales.forEach(sucursal => {
        // Buscar % Costo de Ocupación actual
        let porcentajeActual = null;
        data.conceptos.forEach(concepto => {
            if (concepto.is_percentage && concepto.nombre.toLowerCase().includes('costo')) {
                porcentajeActual = concepto.valores[sucursal.id];
            }
        });
        
        const porcentajeAnterior = data.porcentajesAnteriores[sucursal.id];
        
        let variacionHtml = '-';
        if (porcentajeActual !== null && porcentajeAnterior !== null && porcentajeAnterior !== 0) {
            const variacionRelativa = ((porcentajeActual - porcentajeAnterior) / porcentajeAnterior) * 100;
            const signo = variacionRelativa > 0 ? '+' : '';
            const colorClass = variacionRelativa > 0 ? 'text-danger' : 'text-success';
            variacionHtml = `<span class="${colorClass}">${signo}${variacionRelativa.toFixed(2)}%</span>`;
        }
        
        rowVariacion += `<td class="text-right">${variacionHtml}</td>`;
    });
    
    rowVariacion += `</tr>`;
    tableBody.append(rowVariacion);
    
    // NO calcular ni mostrar footer con totales
    
    // Asegurarse de que la tabla esté completamente renderizada antes de inicializar DataTable
    // Esperar un breve momento para que el DOM se actualice
    setTimeout(function() {
        // Inicializar DataTable
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
    }, 100);
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
        title: '<i class="bi bi-info-circle-fill"></i> Guía de Interpretación',
        showCloseButton: true,
        html: `
            <div style="text-align: left; max-height: 600px; overflow-y: auto;">
                <!-- Sección Rangos de Costo -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
                    <h5 style="margin: 0 0 15px 0; display: flex; align-items: center; font-weight: 600;">
                        <i class="bi bi-speedometer2" style="font-size: 24px; margin-right: 10px;"></i>
                        Rangos de % Costo de Ocupación
                    </h5>
                    
                    <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px; margin-bottom: 10px; backdrop-filter: blur(10px);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; flex: 1;">
                                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #27ae60, #229954); border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 12px; box-shadow: 0 2px 8px rgba(39, 174, 96, 0.4);">
                                    <i class="bi bi-check-circle-fill" style="color: white; font-size: 18px;"></i>
                                </div>
                                <div>
                                    <strong style="font-size: 15px;">Óptimo</strong>
                                    <div style="font-size: 13px; opacity: 0.9;">Gestión eficiente</div>
                                </div>
                            </div>
                            <div style="background: rgba(255,255,255,0.95); color: #27ae60; padding: 6px 14px; border-radius: 20px; font-weight: bold; font-size: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                                &lt; 15%
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px; margin-bottom: 10px; backdrop-filter: blur(10px);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; flex: 1;">
                                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #f39c12, #e67e22); border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 12px; box-shadow: 0 2px 8px rgba(243, 156, 18, 0.4);">
                                    <i class="bi bi-exclamation-circle-fill" style="color: white; font-size: 18px;"></i>
                                </div>
                                <div>
                                    <strong style="font-size: 15px;">Moderado</strong>
                                    <div style="font-size: 13px; opacity: 0.9;">Requiere atención</div>
                                </div>
                            </div>
                            <div style="background: rgba(255,255,255,0.95); color: #f39c12; padding: 6px 14px; border-radius: 20px; font-weight: bold; font-size: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                                15% - 20%
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px; backdrop-filter: blur(10px);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; flex: 1;">
                                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #e74c3c, #c0392b); border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 12px; box-shadow: 0 2px 8px rgba(231, 76, 60, 0.4);">
                                    <i class="bi bi-x-circle-fill" style="color: white; font-size: 18px;"></i>
                                </div>
                                <div>
                                    <strong style="font-size: 15px;">Alto</strong>
                                    <div style="font-size: 13px; opacity: 0.9;">Acción urgente necesaria</div>
                                </div>
                            </div>
                            <div style="background: rgba(255,255,255,0.95); color: #e74c3c; padding: 6px 14px; border-radius: 20px; font-weight: bold; font-size: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                                &gt; 20%
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sección Agrupación de Conceptos -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px solid #e9ecef;">
                    <h5 style="margin: 0 0 15px 0; color: #2c3e50; display: flex; align-items: center; font-weight: 600;">
                        <i class="bi bi-list-check" style="font-size: 22px; margin-right: 10px; color: #3498db;"></i>
                        Agrupación de Conceptos
                    </h5>
                    
                    <div style="display: grid; gap: 10px;">
                        <div style="background: white; padding: 12px; border-radius: 8px; border-left: 4px solid #3498db; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="color: #3498db; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center;">
                                <i class="bi bi-building" style="margin-right: 8px;"></i>
                                Alquiler
                            </div>
                            <div style="color: #7f8c8d; font-size: 14px;">Alquiler + Complementario + Valor mínimo mensual</div>
                        </div>
                        
                        <div style="background: white; padding: 12px; border-radius: 8px; border-left: 4px solid #9b59b6; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="color: #9b59b6; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center;">
                                <i class="bi bi-key-fill" style="margin-right: 8px;"></i>
                                Llave
                            </div>
                            <div style="color: #7f8c8d; font-size: 14px; margin-bottom: 6px;">[25% de (Alquiler + Complementario + VMM)] + Comisiones + FPC Lanzamiento</div>
                            <div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 8px; border-radius: 6px; font-size: 13px; display: flex; align-items: start;">
                                <i class="bi bi-info-circle-fill" style="margin-right: 8px; margin-top: 2px; flex-shrink: 0;"></i>
                                <span>Solo se calcula si existe valor en <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 3px;">RO_V_CONTRATOS_VALOR_LLAVE</code>, de lo contrario es 0</span>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 12px; border-radius: 8px; border-left: 4px solid #e67e22; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="color: #e67e22; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center;">
                                <i class="bi bi-percent" style="margin-right: 8px;"></i>
                                Alquiler porcentual
                            </div>
                            <div style="color: #7f8c8d; font-size: 14px;">Porc. S/ventas brutas + Porc. S/ventas netas</div>
                        </div>
                        
                        <div style="background: white; padding: 12px; border-radius: 8px; border-left: 4px solid #16a085; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="color: #16a085; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center;">
                                <i class="bi bi-megaphone-fill" style="margin-right: 8px;"></i>
                                Fondo de promoción
                            </div>
                            <div style="color: #7f8c8d; font-size: 14px;">Fondo de promoción (% VMM) + Fondo promoción mensual</div>
                        </div>
                        
                        <div style="background: white; padding: 12px; border-radius: 8px; border-left: 4px solid #27ae60; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="color: #27ae60; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center;">
                                <i class="bi bi-box" style="margin-right: 8px;"></i>
                                Baulera
                            </div>
                            <div style="color: #7f8c8d; font-size: 14px;">Se calcula por separado (no incluida en Alquiler)</div>
                        </div>
                        
                        <div style="background: white; padding: 12px; border-radius: 8px; border-left: 4px solid #e74c3c; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="color: #e74c3c; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center;">
                                <i class="bi bi-cash-stack" style="margin-right: 8px;"></i>
                                Gastos varios
                            </div>
                            <div style="color: #7f8c8d; font-size: 14px;">Gastos publicidad + Gastos administrativos</div>
                        </div>
                        
                        <div style="background: white; padding: 12px; border-radius: 8px; border-left: 4px solid #f39c12; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="color: #f39c12; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center;">
                                <i class="bi bi-receipt" style="margin-right: 8px;"></i>
                                Expensas
                            </div>
                            <div style="color: #7f8c8d; font-size: 14px;">Expensas + impuestos expensables</div>
                        </div>
                        
                        <div style="background: white; padding: 12px; border-radius: 8px; border-left: 4px solid #95a5a6; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="color: #95a5a6; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center;">
                                <i class="bi bi-arrow-left-right" style="margin-right: 8px;"></i>
                                Diferencia
                            </div>
                            <div style="color: #7f8c8d; font-size: 14px;">Diferencia de acuerdo</div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        confirmButtonText: '<i class="bi bi-check-lg"></i> Entendido',
        confirmButtonColor: '#3498db',
        width: '700px',
        customClass: {
            popup: 'swal-wide-popup',
            htmlContainer: 'swal-html-no-padding'
        }
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
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(numero);
}

/**
 * Formatea un número como porcentaje
 */
function formatearPorcentaje(valor) {
    const numero = parseFloat(valor) || 0;
    return numero.toFixed(2) + '%';
}
