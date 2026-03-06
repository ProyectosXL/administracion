/**
 * Módulo de Costo de Ocupación
 * Maneja la lógica del frontend para análisis de costos
 */

let dataTableInstance = null;
let datosActuales = null;

$(document).ready(function() {
    inicializarEventos();
});

/**
 * Inicializa los event listeners
 */
function inicializarEventos() {
    $('#btnAplicar').on('click', cargarDatos);
    $('#btnLimpiar').on('click', limpiarFiltros);
    $('#btnExportar').on('click', exportarDatos);
    $('#btnLeyenda').on('click', mostrarLeyenda);
    $('#btnVerEvolucion').on('click', mostrarEvolucion);
    
    // Enter en los inputs
    $('#selectSucursal, #fechaDesde, #fechaHasta').on('keypress', function(e) {
        if (e.which === 13) {
            cargarDatos();
        }
    });
}

/**
 * Carga los datos desde el servidor
 */
function cargarDatos() {
    const idSucursal = $('#selectSucursal').val();
    const fechaDesde = $('#fechaDesde').val();
    const fechaHasta = $('#fechaHasta').val();
    
    // Validar sucursal
    if (!idSucursal) {
        Swal.fire({
            icon: 'warning',
            title: 'Datos incompletos',
            text: 'Debe seleccionar una sucursal',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    // Validar fechas
    if (fechaDesde && fechaHasta && new Date(fechaDesde) > new Date(fechaHasta)) {
        Swal.fire({
            icon: 'error',
            title: 'Fechas inválidas',
            text: 'La fecha "Desde" debe ser anterior a la fecha "Hasta"',
            confirmButtonText: 'Entendido'
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
    
    // Petición AJAX
    $.ajax({
        url: 'Controller/costoOcupacionController.php',
        method: 'POST',
        data: {
            action: 'fetch',
            id_sucursal: idSucursal,
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta
        },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (response.success) {
                datosActuales = response.data;
                renderizarDatos(response.data);
            } else {
                mostrarError(response.message || 'No se encontraron datos');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('Error AJAX:', error);
            mostrarError('Error de conexión con el servidor');
        }
    });
}

/**
 * Renderiza los datos en la interfaz
 */
function renderizarDatos(data) {
    // Ocultar empty state
    $('#emptyState').hide();
    
    // Mostrar secciones
    $('#kpisSection').fadeIn();
    $('#tableSection').fadeIn();
    
    // Renderizar KPIs
    renderizarKPIs(data.kpis);
    
    // Renderizar tabla
    renderizarTabla(data.meses, data.filas);
}

/**
 * Renderiza los KPIs del dashboard (actualizado)
 */
function renderizarKPIs(kpis) {
    // KPI 1: Acumulado 12 meses
    if (kpis.acumulado_12m !== null) {
        $('#kpiAcumulado12m').text(formatearPorcentaje(kpis.acumulado_12m));
    } else {
        $('#kpiAcumulado12m').text('--');
    }
    
    // KPI 2: Variación anual
    const variacion = kpis.variacion_anual;
    const estado = kpis.variacion_anual_estado;
    
    if (variacion !== null) {
        const signo = variacion >= 0 ? '+' : '';
        $('#kpiVariacionAnual').text(`${signo}${variacion.toFixed(2)}%`);
        
        // Actualizar icono y color
        const icono = $('#kpiIconVariacion');
        const trend = $('#kpiTrendVariacion');
        
        icono.removeClass('kpi-success kpi-warning kpi-danger kpi-info');
        
        if (estado === 'success') {
            // Mejoró (bajó el costo)
            icono.addClass('kpi-success');
            icono.html('<i class="bi bi-arrow-down-circle"></i>');
            trend.html('<i class="bi bi-check-circle text-success" style="font-size: 14px;"></i>');
        } else if (estado === 'danger') {
            // Empeoró (subió el costo)
            icono.addClass('kpi-danger');
            icono.html('<i class="bi bi-arrow-up-circle"></i>');
            trend.html('<i class="bi bi-exclamation-triangle text-danger" style="font-size: 14px;"></i>');
        } else {
            // Sin cambios significativos
            icono.addClass('kpi-info');
            icono.html('<i class="bi bi-arrow-left-right"></i>');
            trend.html('<i class="bi bi-dash-circle text-info" style="font-size: 14px;"></i>');
        }
    } else {
        $('#kpiVariacionAnual').text('--');
        $('#kpiTrendVariacion').html('');
        $('#kpiIconVariacion').removeClass('kpi-success kpi-warning kpi-danger').addClass('kpi-info');
        $('#kpiIconVariacion').html('<i class="bi bi-arrow-left-right"></i>');
    }
}

/**
 * Muestra un mensaje de error
 */
function mostrarError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje,
        confirmButtonText: 'Entendido'
    });
}

/**
 * Muestra la leyenda de colores para el % de costo de ocupación
 */
function mostrarLeyenda() {
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
 * Formatea un número como moneda
 */
function formatearMoneda(valor) {
    if (valor === null || valor === undefined || isNaN(valor)) {
        return '$0';
    }
    
    return '$' + Math.round(valor).toLocaleString('es-AR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

/**
 * Muestra el modal de evolución
 */
function mostrarEvolucion() {
    if (!datosActuales || !datosActuales.kpis) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin datos',
            text: 'Debe cargar los datos primero',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    const datosGrafico = datosActuales.kpis.grafico_datos;
    const kpis = datosActuales.kpis;
    
    abrirModalEvolucion(datosGrafico, kpis);
}

/**
 * Renderiza la tabla con DataTables
 */
function renderizarTabla(meses, filas) {
    // Destruir tabla existente si existe
    if (dataTableInstance) {
        dataTableInstance.destroy();
        $('#tablaCostoOcupacion').empty();
    }
    
    // Construir header
    let headerHtml = '<thead><tr class="header-row">';
    headerHtml += '<th class="fixed-column"><i class="bi bi-list-ul"></i> Concepto</th>';
    
    meses.forEach(mes => {
        const mesFormateado = formatearMes(mes);
        headerHtml += `<th class="text-right">${mesFormateado}</th>`;
    });
    
    headerHtml += '<th class="text-right total-column"><strong>Total</strong></th>';
    headerHtml += '</tr></thead>';
    
    // Construir body
    let bodyHtml = '<tbody>';
    
    filas.forEach(fila => {
        let rowClass = '';
        
        if (fila.is_subtotal) {
            rowClass = 'row-subtotal';
        } else if (fila.is_metric) {
            rowClass = 'row-metric';
        } else if (fila.is_percentage) {
            rowClass = 'row-percentage';
        }
        
        bodyHtml += `<tr class="${rowClass}">`;
        bodyHtml += `<td class="fixed-column"><strong>${fila.concepto}</strong></td>`;
        
        // Columnas de meses
        meses.forEach(mes => {
            const valor = fila.meses[mes];
            let contenido = '';
            let cellClass = 'text-right';
            
            if (fila.is_percentage) {
                if (valor !== null && valor !== undefined) {
                    contenido = formatearPorcentaje(valor);
                    
                    // Aplicar color condicional
                    if (valor < 15) {
                        cellClass += ' bg-success-light';
                    } else if (valor >= 15 && valor <= 20) {
                        cellClass += ' bg-warning-light';
                    } else {
                        cellClass += ' bg-danger-light';
                    }
                } else {
                    contenido = '–';
                }
            } else {
                contenido = formatearMoneda(valor);
            }
            
            bodyHtml += `<td class="${cellClass}">${contenido}</td>`;
        });
        
        // Columna Total
        let totalContenido = '';
        let totalClass = 'text-right total-column';
        
        if (fila.is_percentage) {
            if (fila.total !== null && fila.total !== undefined) {
                totalContenido = formatearPorcentaje(fila.total);
                
                if (fila.total < 15) {
                    totalClass += ' bg-success-light';
                } else if (fila.total >= 15 && fila.total <= 20) {
                    totalClass += ' bg-warning-light';
                } else {
                    totalClass += ' bg-danger-light';
                }
            } else {
                totalContenido = '–';
            }
        } else {
            totalContenido = formatearMoneda(fila.total);
        }
        
        bodyHtml += `<td class="${totalClass}"><strong>${totalContenido}</strong></td>`;
        bodyHtml += '</tr>';
    });
    
    // Agregar filas YoY al final
    if (datosActuales && datosActuales.porcentaje_anterior_yoy !== undefined) {
        // Buscar la fila de % Costo de Ocupación actual para tener los valores mensuales
        let filaPorcentajeActual = null;
        filas.forEach(fila => {
            if (fila.is_percentage && fila.concepto && fila.concepto.toLowerCase().includes('costo')) {
                filaPorcentajeActual = fila;
            }
        });
        
        // Fila de % Costo de Ocupación Período Anterior
        bodyHtml += `<tr class="row-yoy-anterior" style="font-weight: bold;">
            <td class="fixed-column"><strong>% Costo de Ocupación (Período Anterior YoY)</strong></td>`;
        
        // Mostrar el promedio del período anterior en cada mes
        // (es un valor único que se repite para comparación visual)
        const porcentajeAnterior = datosActuales.porcentaje_anterior_yoy;
        meses.forEach(() => {
            const valorAnteriorFormateado = porcentajeAnterior !== null 
                ? formatearPorcentaje(porcentajeAnterior) 
                : '–';
            bodyHtml += `<td class="text-right">${valorAnteriorFormateado}</td>`;
        });
        
        // Total del período anterior
        const valorAnteriorFormateadoTotal = porcentajeAnterior !== null 
            ? formatearPorcentaje(porcentajeAnterior) 
            : '–';
        bodyHtml += `<td class="text-right total-column"><strong>${valorAnteriorFormateadoTotal}</strong></td>`;
        bodyHtml += `</tr>`;
        
        // Fila de Variación Relativa (mes a mes)
        bodyHtml += `<tr class="row-yoy-variacion" style="font-weight: bold;">
            <td class="fixed-column"><strong>Variación Relativa (%)</strong></td>`;
        
        // Calcular variación relativa para cada mes
        meses.forEach(mes => {
            let variacionHtml = '–';
            
            if (filaPorcentajeActual && porcentajeAnterior !== null && porcentajeAnterior !== 0) {
                const porcentajeMesActual = filaPorcentajeActual.meses[mes];
                
                if (porcentajeMesActual !== null && porcentajeMesActual !== undefined) {
                    const variacionRelativa = ((porcentajeMesActual - porcentajeAnterior) / porcentajeAnterior) * 100;
                    const signo = variacionRelativa > 0 ? '+' : '';
                    const colorClass = variacionRelativa > 0 ? 'text-danger' : 'text-success';
                    variacionHtml = `<span class="${colorClass}">${signo}${variacionRelativa.toFixed(2)}%</span>`;
                }
            }
            
            bodyHtml += `<td class="text-right">${variacionHtml}</td>`;
        });
        
        // Variación total
        let variacionTotalHtml = '–';
        if (datosActuales.kpis && datosActuales.kpis.porcentaje_costo_ocupacion_12m !== null && 
            porcentajeAnterior !== null && porcentajeAnterior !== 0) {
            const porcentajeActual = parseFloat(datosActuales.kpis.porcentaje_costo_ocupacion_12m);
            const variacionRelativa = ((porcentajeActual - porcentajeAnterior) / porcentajeAnterior) * 100;
            const signo = variacionRelativa > 0 ? '+' : '';
            const colorClass = variacionRelativa > 0 ? 'text-danger' : 'text-success';
            variacionTotalHtml = `<span class="${colorClass}">${signo}${variacionRelativa.toFixed(2)}%</span>`;
        }
        bodyHtml += `<td class="text-right total-column"><strong>${variacionTotalHtml}</strong></td>`;
        bodyHtml += `</tr>`;
    }
    
    bodyHtml += '</tbody>';
    
    // Actualizar HTML
    $('#tablaCostoOcupacion').html(headerHtml + bodyHtml);
    
    // Inicializar DataTable
    dataTableInstance = $('#tablaCostoOcupacion').DataTable({
        responsive: false,
        scrollX: true,
        scrollY: '500px',
        scrollCollapse: true,
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        fixedColumns: {
            leftColumns: 1
        },
        language: {
            emptyTable: "No hay datos disponibles",
            zeroRecords: "No se encontraron registros"
        },
        dom: 'Bfrtip',
        buttons: []
    });
}

/**
 * Limpia los filtros y resetea la vista
 */
function limpiarFiltros() {
    // Limpiar select
    $('#selectSucursal').val('').trigger('change');
    
    // Resetear fechas al rango default
    $.ajax({
        url: 'Controller/costoOcupacionController.php?accion=obtenerRangoDefault',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#fechaDesde').val(response.data.desde);
                $('#fechaHasta').val(response.data.hasta);
            }
        }
    });
    
    // Ocultar secciones y mostrar empty state
    $('#kpisSection').hide();
    $('#tableSection').hide();
    $('#emptyState').fadeIn();
    
    // Limpiar datos
    datosActuales = null;
    
    if (dataTableInstance) {
        dataTableInstance.destroy();
        $('#tablaCostoOcupacion').empty();
        dataTableInstance = null;
    }
}

/**
 * Exporta los datos a Excel
 */
function exportarDatos() {
    if (!datosActuales) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin datos',
            text: 'No hay datos para exportar',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    // Usar DataTables buttons para exportar
    if (dataTableInstance) {
        // Crear botón temporal de Excel
        const excelButton = dataTableInstance.button().add(0, {
            extend: 'excel',
            text: 'Excel',
            title: 'Costo de Ocupación',
            filename: 'costo_ocupacion_' + new Date().toISOString().split('T')[0],
            exportOptions: {
                columns: ':visible'
            }
        });
        
        // Trigger click
        excelButton.trigger();
        
        // Remover botón
        excelButton.remove();
    }
}

/**
 * Formatea un número como porcentaje
 */
function formatearPorcentaje(valor) {
    if (valor === null || valor === undefined || isNaN(valor)) {
        return '0.00%';
    }
    
    return valor.toFixed(2) + '%';
}

/**
 * Formatea un mes de YYYY-MM a formato legible
 */
function formatearMes(mesStr) {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 
                   'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    
    const [anio, mes] = mesStr.split('-');
    const mesNum = parseInt(mes) - 1;
    
    return `${meses[mesNum]} ${anio}`;
}