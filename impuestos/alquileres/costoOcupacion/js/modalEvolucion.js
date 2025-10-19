
/**
 * Módulo Modal de Evolución
 * Maneja la visualización del gráfico comparativo
 */

let chartEvolucionInstance = null;

/**
 * Abre el modal y muestra el gráfico de evolución
 * @param {Object} datosGrafico - Datos del gráfico
 * @param {Object} kpis - KPIs calculados
 */
function abrirModalEvolucion(datosGrafico, kpis) {
    if (!datosGrafico || !datosGrafico.actuales || !datosGrafico.anteriores) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No hay datos disponibles para mostrar el gráfico',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    // Abrir modal
    $('#modalEvolucion').modal('show');
    
    // Esperar a que el modal esté completamente visible
    $('#modalEvolucion').on('shown.bs.modal', function () {
        renderizarGraficoEvolucion(datosGrafico, kpis);
    });
}

/**
 * Renderiza el gráfico de evolución con Chart.js
 * @param {Object} datosGrafico - Datos del gráfico
 * @param {Object} kpis - KPIs calculados
 */
function renderizarGraficoEvolucion(datosGrafico, kpis) {
    const canvas = document.getElementById('chartEvolucion');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Destruir gráfico anterior si existe
    if (chartEvolucionInstance) {
        chartEvolucionInstance.destroy();
    }
    
    // Preparar datos
    const labels = datosGrafico.actuales.map(item => formatearMesCorto(item.mes));
    
    const datosActuales = datosGrafico.actuales.map(item => 
        item.costo !== null ? item.costo : null
    );
    
    const datosAnteriores = datosGrafico.anteriores.map(item => 
        item.costo !== null ? item.costo : null
    );
    
    // Configuración del gráfico
    chartEvolucionInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Período Actual',
                    data: datosActuales,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#3498db',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Año Anterior',
                    data: datosAnteriores,
                    borderColor: '#95a5a6',
                    backgroundColor: 'rgba(149, 165, 166, 0.1)',
                    borderWidth: 3,
                    borderDash: [5, 5],
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#95a5a6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y.toFixed(2) + '%';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '% Costo de Ocupación',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(1) + '%';
                        },
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Meses',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
    
    // Calcular y mostrar promedios
    const promedioActual = calcularPromedio(datosActuales);
    const promedioAnterior = calcularPromedio(datosAnteriores);
    
    $('#promedioActual').text(promedioActual !== null ? promedioActual.toFixed(2) + '%' : '--');
    $('#promedioAnterior').text(promedioAnterior !== null ? promedioAnterior.toFixed(2) + '%' : '--');
    
    // Mostrar variación
    mostrarVariacionAnual(promedioActual, promedioAnterior, kpis);
}

/**
 * Calcula el promedio de un array excluyendo valores null
 * @param {Array} datos - Array de valores
 * @returns {Number|null} - Promedio o null
 */
function calcularPromedio(datos) {
    const valoresValidos = datos.filter(v => v !== null && v !== undefined);
    
    if (valoresValidos.length === 0) return null;
    
    const suma = valoresValidos.reduce((acc, val) => acc + val, 0);
    return suma / valoresValidos.length;
}

/**
 * Muestra el badge de variación anual
 * @param {Number} promedioActual
 * @param {Number} promedioAnterior
 * @param {Object} kpis
 */
function mostrarVariacionAnual(promedioActual, promedioAnterior, kpis) {
    const badge = $('#variacionAnualBadge');
    badge.removeClass('success danger info');
    
    let html = '';
    
    if (kpis.variacion_anual !== null && promedioActual !== null && promedioAnterior !== null) {
        const variacion = kpis.variacion_anual;
        const estado = kpis.variacion_anual_estado;
        
        let icono = '<i class="bi bi-arrow-left-right"></i>';
        let texto = 'Sin cambios significativos';
        
        if (estado === 'success') {
            badge.addClass('success');
            icono = '<i class="bi bi-arrow-down-circle"></i>';
            texto = `Mejora del ${Math.abs(variacion).toFixed(2)}% respecto al año anterior`;
        } else if (estado === 'danger') {
            badge.addClass('danger');
            icono = '<i class="bi bi-arrow-up-circle"></i>';
            texto = `Incremento del ${Math.abs(variacion).toFixed(2)}% respecto al año anterior`;
        } else {
            badge.addClass('info');
            texto = `Variación del ${variacion >= 0 ? '+' : ''}${variacion.toFixed(2)}%`;
        }
        
        html = `${icono} ${texto}`;
    } else {
        badge.addClass('info');
        html = '<i class="bi bi-info-circle"></i> No hay suficientes datos para calcular la variación';
    }
    
    badge.html(html);
}

/**
 * Formatea un mes de YYYY-MM a formato corto (Ene 24)
 * @param {String} mesStr - Formato YYYY-MM
 * @returns {String} - Formato corto
 */
function formatearMesCorto(mesStr) {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 
                   'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    
    const [anio, mes] = mesStr.split('-');
    const mesNum = parseInt(mes) - 1;
    const anioCorto = anio.substring(2);
    
    return `${meses[mesNum]} ${anioCorto}`;
}

/**
 * Exporta el gráfico como imagen
 */
function exportarGrafico() {
    if (!chartEvolucionInstance) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No hay gráfico para exportar',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    const canvas = document.getElementById('chartEvolucion');
    const url = canvas.toDataURL('image/png');
    
    const link = document.createElement('a');
    link.download = 'evolucion_costo_ocupacion_' + new Date().toISOString().split('T')[0] + '.png';
    link.href = url;
    link.click();
    
    Swal.fire({
        icon: 'success',
        title: 'Gráfico exportado',
        text: 'El gráfico se ha descargado correctamente',
        timer: 2000,
        showConfirmButton: false
    });
}

// Event Listeners
$(document).ready(function() {
    $('#btnExportarGrafico').on('click', exportarGrafico);
    
    // Limpiar gráfico al cerrar modal
    $('#modalEvolucion').on('hidden.bs.modal', function () {
        if (chartEvolucionInstance) {
            chartEvolucionInstance.destroy();
            chartEvolucionInstance = null;
        }
    });
});