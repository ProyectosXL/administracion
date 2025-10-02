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
    
    // Renderizar sparkline
    renderizarSparkline(data.kpis.tendencia_6m);
}

/**
 * Renderiza los KPIs del dashboard
 */
function renderizarKPIs(kpis) {
    // Promedio 12 meses
    if (kpis.promedio_12m !== null) {
        $('#kpiPromedio12m').text(formatearPorcentaje(kpis.promedio_12m));
    } else {
        $('#kpiPromedio12m').text('--');
    }
    
    // Último mes con datos disponibles
    if (kpis.costo_ultimo_mes !== null) {
        $('#kpiUltimoMes').text(formatearPorcentaje(kpis.costo_ultimo_mes));
        
        // Actualizar label con el mes correspondiente si está disponible
        if (kpis.ultimo_mes_con_datos) {
            const mesFormateado = formatearMes(kpis.ultimo_mes_con_datos);
            $('.kpi-card').eq(1).find('.kpi-label').text(`Último mes (${mesFormateado})`);
        }
        
        // Badge con color
        const badge = $('#kpiBadgeUltimoMes');
        badge.removeClass('badge-success badge-warning badge-danger');
        
        if (kpis.estado_ultimo_mes === 'success') {
            badge.addClass('badge-success').text('Óptimo');
            $('#kpiIconUltimoMes').removeClass('kpi-warning kpi-danger').addClass('kpi-success');
        } else if (kpis.estado_ultimo_mes === 'warning') {
            badge.addClass('badge-warning').text('Moderado');
            $('#kpiIconUltimoMes').removeClass('kpi-success kpi-danger').addClass('kpi-warning');
        } else {
            badge.addClass('badge-danger').text('Alto');
            $('#kpiIconUltimoMes').removeClass('kpi-success kpi-warning').addClass('kpi-danger');
        }
    } else {
        $('#kpiUltimoMes').text('--');
        $('#kpiBadgeUltimoMes').text('');
        $('.kpi-card').eq(1).find('.kpi-label').text('Último mes');
    }
    
    // Variación mensual
    renderizarVariacionMensual(kpis);
    
    // Variación vs promedio
    renderizarVariacionVsPromedio(kpis);
}

/**
 * Renderiza el KPI de variación mensual
 */
function renderizarVariacionMensual(kpis) {
    const variacion = kpis.variacion_mensual;
    const estado = kpis.estado_variacion;
    
    if (variacion !== null) {
        // Formatear el valor de variación
        const valorFormateado = Math.abs(variacion).toFixed(2);
        const signo = variacion >= 0 ? '+' : '';
        $('#kpiVariacion').text(`${signo}${variacion.toFixed(2)}%`);
        
        // Actualizar icono y color según el estado
        const icono = $('#kpiIconVariacion');
        const trend = $('#kpiTrendVariacion');
        
        icono.removeClass('kpi-success kpi-warning kpi-danger kpi-info');
        
        if (estado === 'success') {
            // Mejoró (variación negativa)
            icono.addClass('kpi-success');
            icono.html('<i class="bi bi-arrow-down-right"></i>');
            trend.html('<i class="bi bi-triangle-fill text-success" style="transform: rotate(180deg); font-size: 12px;"></i>');
        } else if (estado === 'danger') {
            // Empeoró (variación positiva)
            icono.addClass('kpi-danger');
            icono.html('<i class="bi bi-arrow-up-right"></i>');
            trend.html('<i class="bi bi-triangle-fill text-danger" style="font-size: 12px;"></i>');
        } else {
            // Sin cambio
            icono.addClass('kpi-info');
            icono.html('<i class="bi bi-arrow-left-right"></i>');
            trend.html('<i class="bi bi-dash-circle text-info" style="font-size: 12px;"></i>');
        }
        
        // Actualizar label con información de los meses comparados
        if (kpis.ultimo_mes_con_datos && kpis.penultimo_mes_con_datos) {
            const ultimoMesFormat = formatearMes(kpis.ultimo_mes_con_datos);
            const penultimoMesFormat = formatearMes(kpis.penultimo_mes_con_datos);
            $('.kpi-card').eq(3).find('.kpi-label').text(`Variación mensual (${penultimoMesFormat} vs ${ultimoMesFormat})`);
        }
        
    } else {
        $('#kpiVariacion').text('--');
        $('#kpiTrendVariacion').html('');
        $('#kpiIconVariacion').removeClass('kpi-success kpi-warning kpi-danger').addClass('kpi-info');
        $('#kpiIconVariacion').html('<i class="bi bi-arrow-left-right"></i>');
        $('.kpi-card').eq(3).find('.kpi-label').text('Variación mensual');
    }
}

/**
 * Renderiza el KPI de variación vs promedio
 */
function renderizarVariacionVsPromedio(kpis) {
    const variacion = kpis.variacion_vs_promedio;
    const estado = kpis.estado_vs_promedio;
    
    if (variacion !== null && kpis.promedio_12m !== null) {
        // Formatear el valor de variación
        const signo = variacion >= 0 ? '+' : '';
        $('#kpiVsPromedio').text(`${signo}${variacion.toFixed(2)}%`);
        
        // Actualizar icono y color según el estado
        const icono = $('#kpiIconVsPromedio');
        const trend = $('#kpiTrendVsPromedio');
        
        icono.removeClass('kpi-success kpi-warning kpi-danger kpi-info');
        
        if (estado === 'success') {
            // Por debajo del promedio (mejor)
            icono.addClass('kpi-success');
            icono.html('<i class="bi bi-arrow-down-circle"></i>');
            trend.html('<i class="bi bi-check-circle text-success" style="font-size: 12px;"></i>');
        } else if (estado === 'danger') {
            // Significativamente por encima del promedio (peor)
            icono.addClass('kpi-danger');
            icono.html('<i class="bi bi-arrow-up-circle"></i>');
            trend.html('<i class="bi bi-exclamation-triangle text-danger" style="font-size: 12px;"></i>');
        } else {
            // Cerca del promedio
            icono.addClass('kpi-warning');
            icono.html('<i class="bi bi-graph-up-arrow"></i>');
            trend.html('<i class="bi bi-info-circle text-warning" style="font-size: 12px;"></i>');
        }
        
        // Actualizar label con información del promedio
        if (kpis.ultimo_mes_con_datos) {
            const mesFormateado = formatearMes(kpis.ultimo_mes_con_datos);
            $('.kpi-card').eq(2).find('.kpi-label').text(`Vs Promedio (${mesFormateado} vs 12m)`);
        }
        
    } else {
        $('#kpiVsPromedio').text('--');
        $('#kpiTrendVsPromedio').html('');
        $('#kpiIconVsPromedio').removeClass('kpi-success kpi-warning kpi-danger').addClass('kpi-info');
        $('#kpiIconVsPromedio').html('<i class="bi bi-graph-up-arrow"></i>');
        $('.kpi-card').eq(2).find('.kpi-label').text('Vs Promedio');
    }
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
 * Renderiza el sparkline de tendencia
 */
function renderizarSparkline(datos) {
    const canvas = document.getElementById('chartTendencia');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    
    // Limpiar canvas
    ctx.clearRect(0, 0, width, height);
    
    // Filtrar valores válidos
    const valoresValidos = datos.filter(v => v !== null && v !== undefined);
    
    if (valoresValidos.length === 0) {
        ctx.fillStyle = '#999';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Sin datos', width / 2, height / 2);
        return;
    }
    
    // Calcular min y max
    const min = Math.min(...valoresValidos);
    const max = Math.max(...valoresValidos);
    const range = max - min || 1;
    
    // Padding
    const padding = 5;
    const chartWidth = width - (2 * padding);
    const chartHeight = height - (2 * padding);
    
    // Calcular puntos
    const puntos = [];
    const step = chartWidth / (valoresValidos.length - 1 || 1);
    
    valoresValidos.forEach((valor, i) => {
        const x = padding + (i * step);
        const y = padding + chartHeight - ((valor - min) / range * chartHeight);
        puntos.push({ x, y });
    });
    
    // Dibujar línea
    ctx.strokeStyle = '#3498db';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(puntos[0].x, puntos[0].y);
    
    for (let i = 1; i < puntos.length; i++) {
        ctx.lineTo(puntos[i].x, puntos[i].y);
    }
    
    ctx.stroke();
    
    // Dibujar puntos
    puntos.forEach(punto => {
        ctx.fillStyle = '#2c3e50';
        ctx.beginPath();
        ctx.arc(punto.x, punto.y, 3, 0, 2 * Math.PI);
        ctx.fill();
    });
    
    // Área bajo la curva (opcional, con transparencia)
    ctx.globalAlpha = 0.1;
    ctx.fillStyle = '#3498db';
    ctx.beginPath();
    ctx.moveTo(puntos[0].x, height - padding);
    ctx.lineTo(puntos[0].x, puntos[0].y);
    
    for (let i = 1; i < puntos.length; i++) {
        ctx.lineTo(puntos[i].x, puntos[i].y);
    }
    
    ctx.lineTo(puntos[puntos.length - 1].x, height - padding);
    ctx.closePath();
    ctx.fill();
    ctx.globalAlpha = 1.0;
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
                <h4 style="margin-bottom: 15px; color: #2c3e50;">Indicadores de Variación:</h4>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="margin-right: 10px;"><i class="bi bi-arrow-down-right" style="color: #27ae60;"></i></div>
                    <strong style="color: #27ae60;">Flecha Verde:</strong> Mejora (reducción del costo)
                </div>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="margin-right: 10px;"><i class="bi bi-arrow-up-right" style="color: #e74c3c;"></i></div>
                    <strong style="color: #e74c3c;">Flecha Roja:</strong> Empeora (aumento del costo)
                </div>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="margin-right: 10px;"><i class="bi bi-arrow-left-right" style="color: #17a2b8;"></i></div>
                    <strong style="color: #17a2b8;">Flecha Horizontal:</strong> Sin cambios significativos
                </div>
                <hr style="margin: 15px 0;">
                <h4 style="margin-bottom: 15px; color: #2c3e50;">Comparación vs Promedio:</h4>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="margin-right: 10px;"><i class="bi bi-arrow-down-circle" style="color: #27ae60;"></i></div>
                    <strong style="color: #27ae60;">Por debajo:</strong> Mejor que el promedio histórico
                </div>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="margin-right: 10px;"><i class="bi bi-graph-up-arrow" style="color: #f39c12;"></i></div>
                    <strong style="color: #f39c12;">Cerca del promedio:</strong> Dentro del rango normal
                </div>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="margin-right: 10px;"><i class="bi bi-arrow-up-circle" style="color: #e74c3c;"></i></div>
                    <strong style="color: #e74c3c;">Por encima:</strong> Peor que el promedio histórico
                </div>
                <hr style="margin: 15px 0;">
                <p style="font-size: 14px; color: #7f8c8d; margin: 0;">
                    <i class="bi bi-info-circle"></i> 
                    Los colores se aplican tanto a las celdas individuales como a los indicadores KPI. La variación mensual compara los dos últimos meses con datos disponibles, y la comparación vs promedio evalúa el último mes contra el promedio de 12 meses.
                </p>
            </div>
        `,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#3498db',
        width: '500px'
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