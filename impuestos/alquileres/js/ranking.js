/**
 * ranking.js
 * Módulo para gestionar la pestaña de Ranking de % Costo de Ocupación YoY
 */

// Estado del módulo
const RankingModule = {
    chart: null,
    currentPeriod: 12, // Por defecto 12 meses
    currentTopN: 10,
    customDates: {
        desde: null,
        hasta: null
    },
    data: null
};

/**
 * Inicializa el módulo de ranking
 */
function initRankingModule() {
    console.log('Inicializando módulo de Ranking...');
    
    // Event listeners para píldoras de período
    $('.pill-btn').on('click', function() {
        const period = $(this).data('period');
        handlePeriodChange(period);
    });
    
    // Event listener para Top N selector
    $('#rankingTopN').on('change', function() {
        const topN = $(this).val();
        RankingModule.currentTopN = topN === 'all' ? 'all' : parseInt(topN);
        
        // Ajustar altura del contenedor
        adjustChartHeight(RankingModule.currentTopN);
        
        // Recargar datos con el nuevo Top N
        if (RankingModule.currentPeriod === 'custom') {
            if (RankingModule.customDates.desde && RankingModule.customDates.hasta) {
                loadRankingDataCustom(RankingModule.customDates.desde, RankingModule.customDates.hasta);
            }
        } else {
            loadRankingData(RankingModule.currentPeriod);
        }
    });
    
    // Event listeners para rango personalizado
    $('#rankingFechaDesde, #rankingFechaHasta').on('change', function() {
        validateCustomRange();
    });
    
    // Event listener para exportar
    $('#btnExportarRanking').on('click', function() {
        exportRankingChart();
    });
    
    // Event listener para leyenda
    $('#btnRankingLegend').on('click', function() {
        showRankingLegend();
    });
    
    // Cargar datos iniciales (12 meses por defecto)
    loadRankingData(12);
}

/**
 * Ajusta la altura del contenedor del gráfico según el Top N seleccionado
 */
function adjustChartHeight(topN) {
    const container = document.querySelector('.chart-container');
    if (!container) return;
    
    let height;
    switch(topN) {
        case 5:
            height = '600px';
            break;
        case 10:
            height = '1000px';
            break;
        case 20:
            height = '1900px';
            break;
        case 'all':
        default:
            height = '2000px';
            break;
    }
    
    container.style.height = height;
    
    // Si el gráfico ya existe, lo redibuja con la nueva altura
    if (RankingModule.chart) {
        RankingModule.chart.resize();
    }
}

/**
 * Maneja el cambio de período
 */
function handlePeriodChange(period) {
    // Actualizar UI de píldoras
    $('.pill-btn').removeClass('active');
    $(`.pill-btn[data-period="${period}"]`).addClass('active');
    
    if (period === 'custom') {
        // Mostrar inputs de rango personalizado
        $('.custom-range-inputs').slideDown();
        RankingModule.currentPeriod = 'custom';
    } else {
        // Ocultar inputs personalizados
        $('.custom-range-inputs').slideUp();
        RankingModule.currentPeriod = parseInt(period);
        
        // Cargar datos para el período seleccionado
        loadRankingData(period);
    }
}

/**
 * Valida y aplica el rango personalizado
 */
function validateCustomRange() {
    const desde = $('#rankingFechaDesde').val();
    const hasta = $('#rankingFechaHasta').val();
    
    if (!desde || !hasta) return;
    
    if (new Date(desde) > new Date(hasta)) {
        $('#rankingFechaHasta').addClass('is-invalid');
        return;
    }
    
    $('#rankingFechaHasta').removeClass('is-invalid');
    
    RankingModule.customDates = { desde, hasta };
    loadRankingDataCustom(desde, hasta);
}

/**
 * Carga datos del ranking para un período predefinido (N meses)
 */
function loadRankingData(months) {
    // Calcular fechas
    const hasta = new Date();
    hasta.setDate(1); // Primer día del mes actual
    hasta.setMonth(hasta.getMonth()); // Mes actual
    hasta.setDate(0); // Último día del mes anterior
    
    const desde = new Date(hasta);
    desde.setMonth(desde.getMonth() - (months - 1));
    desde.setDate(1); // Primer día del mes inicial
    
    const fechaDesde = desde.toISOString().split('T')[0];
    const fechaHasta = hasta.toISOString().split('T')[0];
    
    fetchRankingData(fechaDesde, fechaHasta);
}

/**
 * Carga datos del ranking para un rango personalizado
 */
function loadRankingDataCustom(desde, hasta) {
    fetchRankingData(desde, hasta);
}

/**
 * Realiza la petición AJAX para obtener datos del ranking
 */
function fetchRankingData(fechaDesde, fechaHasta) {
    Swal.fire({
        title: 'Cargando ranking',
        text: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: 'Controller/rankingController.php',
        method: 'POST',
        data: {
            fechaDesde: fechaDesde,
            fechaHasta: fechaHasta
        },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            console.log('Respuesta ranking:', response);
            
            if (response.success) {
                RankingModule.data = response.data;
                updatePeriodInfo(response.data);
                renderRankingChart(response.data);
                showRankingSection();
                
                // Mostrar alerta si hay datos incompletos
                if (response.data.tiene_datos_incompletos) {
                    $('#rankingNoDataAlert').fadeIn();
                } else {
                    $('#rankingNoDataAlert').hide();
                }
            } else {
                console.error('Error en respuesta:', response);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: `<p>${response.message || 'Error al cargar el ranking'}</p>
                           ${response.file ? `<small>Archivo: ${response.file}:${response.line}</small>` : ''}`,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#e74c3c'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('Error AJAX:', error);
            console.error('Response:', xhr.responseText);
            
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo cargar el ranking. Intente nuevamente.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#e74c3c'
            });
        }
    });
}

/**
 * Actualiza la información del período
 */
function updatePeriodInfo(data) {
    const formatoFecha = (fecha) => {
        const d = new Date(fecha + 'T00:00:00');
        return d.toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric' });
    };
    
    $('#periodoActualText').text(
        `${formatoFecha(data.periodo_actual.desde)} - ${formatoFecha(data.periodo_actual.hasta)}`
    );
    
    $('#periodoAnteriorText').text(
        `${formatoFecha(data.periodo_anterior.desde)} - ${formatoFecha(data.periodo_anterior.hasta)}`
    );
}

/**
 * Renderiza el gráfico de ranking con Chart.js
 */
function renderRankingChart(data) {
    // Ajustar altura del contenedor antes de renderizar
    adjustChartHeight(RankingModule.currentTopN);
    
    // Determinar cantidad a mostrar
    let ranking = data.ranking;
    const topN = RankingModule.currentTopN;
    
    if (topN !== 'all') {
        ranking = ranking.slice(0, parseInt(topN));
    }
    
    // Preparar datos para el gráfico
    const labels = ranking.map(item => item.nombre);
    const dataActual = ranking.map(item => item.porcentaje_actual);
    const dataAnterior = ranking.map(item => item.porcentaje_anterior || 0);
    
    // Destruir gráfico anterior si existe
    if (RankingModule.chart) {
        RankingModule.chart.destroy();
    }
    
    const ctx = document.getElementById('rankingChart').getContext('2d');
    
    RankingModule.chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Período Actual',
                    data: dataActual,
                    backgroundColor: 'rgba(231, 76, 60, 0.8)',
                    borderColor: 'rgb(231, 76, 60)',
                    borderWidth: 2,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                },
                {
                    label: 'Año Anterior (YoY)',
                    data: dataAnterior,
                    backgroundColor: 'rgba(52, 152, 219, 0.6)',
                    borderColor: 'rgb(52, 152, 219)',
                    borderWidth: 2,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }
            ]
        },
        options: {
            indexAxis: 'y', // Barras horizontales
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: false
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 15
                    }
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
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
                            const dataIndex = context.dataIndex;
                            const item = ranking[dataIndex];
                            const value = context.parsed.x;
                            
                            let lines = [];
                            
                            if (context.dataset.label === 'Período Actual') {
                                lines.push(`Actual: ${value.toFixed(2)}%`);
                                
                                if (item.porcentaje_anterior !== null) {
                                    lines.push(`Anterior: ${item.porcentaje_anterior.toFixed(2)}%`);
                                    lines.push(`---`);
                                    
                                    if (item.diferencia_pp !== null) {
                                        const signo = item.diferencia_pp > 0 ? '+' : '';
                                        lines.push(`Δ p.p.: ${signo}${item.diferencia_pp.toFixed(2)} p.p.`);
                                    }
                                    
                                    if (item.diferencia_relativa !== null) {
                                        const signo = item.diferencia_relativa > 0 ? '+' : '';
                                        lines.push(`Δ %: ${signo}${item.diferencia_relativa.toFixed(1)}%`);
                                    }
                                } else {
                                    lines.push('Sin datos YoY');
                                }
                            } else {
                                lines.push(`Año Anterior: ${value.toFixed(2)}%`);
                            }
                            
                            return lines;
                        }
                    }
                },
                datalabels: {
                    display: true,
                    anchor: 'center',
                    align: 'center',
                    formatter: (value) => value > 0 ? value.toFixed(1) + '%' : '',
                    font: {
                        weight: 'bold',
                        size: 12
                    },
                    color: '#fff',
                    textStrokeColor: 'rgba(0,0,0,0.3)',
                    textStrokeWidth: 2
                }
            },
            scales: {
                x: {
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
                y: {
                    ticks: {
                        font: {
                            size: 13,
                            weight: '600'
                        },
                        color: '#2c3e50'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

/**
 * Muestra la sección del ranking
 */
function showRankingSection() {
    $('#rankingEmptyState').hide();
    $('#rankingPeriodInfo').fadeIn();
    $('#rankingChartSection').fadeIn();
}

/**
 * Exporta el gráfico como imagen
 */
function exportRankingChart() {
    if (!RankingModule.chart) {
        Swal.fire({
            icon: 'warning',
            title: 'No hay datos',
            text: 'Primero debe generar el ranking',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3498db'
        });
        return;
    }
    
    const link = document.createElement('a');
    link.download = 'ranking-costo-ocupacion.png';
    link.href = RankingModule.chart.toBase64Image();
    link.click();
}

/**
 * Muestra la leyenda del gráfico
 */
function showRankingLegend() {
    Swal.fire({
        title: 'Leyenda del Ranking',
        html: `
            <div style="text-align: left; padding: 10px;">
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <div style="width: 30px; height: 20px; background-color: rgba(231, 76, 60, 0.8); margin-right: 10px; border: 2px solid rgb(231, 76, 60);"></div>
                        <strong>Período Actual:</strong> % Costo de Ocupación del período seleccionado
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <div style="width: 30px; height: 20px; background-color: rgba(52, 152, 219, 0.6); margin-right: 10px; border: 2px solid rgb(52, 152, 219);"></div>
                        <strong>Año Anterior (YoY):</strong> % del mismo período hace 12 meses
                    </div>
                </div>
                
                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px;">
                    <h4 style="margin-top: 0; color: #2c3e50;">Métricas en Tooltip:</h4>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>Actual:</strong> Porcentaje del período actual</li>
                        <li><strong>Anterior:</strong> Porcentaje del año anterior</li>
                        <li><strong>Δ p.p.:</strong> Diferencia en puntos porcentuales (Actual - Anterior)</li>
                        <li><strong>Δ %:</strong> Variación porcentual relativa</li>
                    </ul>
                </div>
                
                <div style="margin-top: 15px; padding: 10px; background-color: #fff3e0; border-left: 4px solid #ff9800; border-radius: 4px;">
                    <strong>Nota:</strong> Las sucursales están ordenadas de mayor a menor % Costo de Ocupación (las con peor indicador aparecen primero).
                </div>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#3498db',
        width: '600px'
    });
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    // Inicializar solo cuando se active la pestaña de Ranking
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#ranking') {
            if (!RankingModule.chart && !RankingModule.data) {
                initRankingModule();
            }
        }
    });
});
