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
        url: 'Controller/CostoOcupacionController.php',
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
        
        // Trigger click
        excelButton.trigger();
        
        // Remover botón
        excelButton.remove();
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
        title: 'Leyenda de Colores',
        icon: 'info',
        html: `
            <div style="text-align: left; padding: 20px;">
                <h4 style="margin-bottom: 15px; color: #2c3e50;">% Costo de Ocupación:</h4>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="width: 20px; height: 20px; background-color: #d4edda; border: 1px solid #c3e6cb; margin-right: 10px; border-radius: 3px;"></div>
                    <strong style="color: #27ae60;">Verde:</strong> Menos del 15% - Óptimo
                </div>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="width: 20px; height: 20px; background-color: #fff3cd; border: 1px solid #ffeaa7; margin-right: 10px; border-radius: 3px;"></div>
                    <strong style="color: #f39c12;">Amarillo:</strong> Entre 15% y 20% - Moderado
                </div>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="width: 20px; height: 20px; background-color: #f8d7da; border: 1px solid #f5c6cb; margin-right: 10px; border-radius: 3px;"></div>
                    <strong style="color: #e74c3c;">Rojo:</strong> Más del 20% - Alto
                </div>
                <hr style="margin: 15px 0;">
                <h4 style="margin-bottom: 15px; color: #2c3e50;">Agrupación de Conceptos:</h4>
                <div style="margin-bottom: 8px;">
                    <strong>Alquiler:</strong> Alquiler + Complementario + Baulera
                </div>
                <div style="margin-bottom: 8px;">
                    <strong>Llave:</strong> 25% de (Alquiler + Porc. S/ventas brutas + Porc. S/ventas netas)
                </div>
                <div style="margin-bottom: 8px;">
                    <strong>Alquiler porcentual:</strong> Porc. S/ventas brutas + Porc. S/ventas netas
                </div>
                <div style="margin-bottom: 8px;">
                    <strong>Gastos varios:</strong> Gastos publicidad + Gastos administrativos
                </div>
                <div style="margin-bottom: 8px;">
                    <strong>Expensas:</strong> Expensas + imp expensables
                </div>
                <div style="margin-bottom: 8px;">
                    <strong>Fondo de promoción %:</strong> Fondo de promoción (% VMM) + Fondo promoción mensual
                </div>
            </div>
        `,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#3498db',
        width: '600px'
    });
}

/**
 * Formatea un número como moneda
 */
function formatearMoneda(valor) {
    if (valor === null || valor === undefined || isNaN(valor)) {
        return '$0';
    }
    
    return ' Actualizar icono y color
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
            // Sin cambio
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
        url: 'Controller/CostoOcupacionController.php?accion=obtenerRangoDefault',
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
        
        // + Math.round(valor).toLocaleString('es-AR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
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
} Actualizar icono y color
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
            // Sin cambio
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
        url: 'Controller/CostoOcupacionController.php?accion=obtenerRangoDefault',
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
        
        //