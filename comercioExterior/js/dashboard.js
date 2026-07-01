// Dashboard JavaScript con manejo de errores mejorado
class Dashboard {
    constructor() {
        this.charts = {};
        this.filters = {
            fechaDesde: null,
            fechaHasta: null,
            proveedor: null
        };
        this.baseUrl = this.getBaseUrl();
        this.debugMode = true; // Cambiar a false en producción
        this.init();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        const pathSegments = currentPath.split('/');
        const baseIndex = pathSegments.findIndex(segment => segment.toLowerCase() === 'comercioexterior');
        
        if (baseIndex !== -1) {
            const basePath = pathSegments.slice(0, baseIndex + 1).join('/');
            return window.location.origin + basePath + '/';
        }
        
        // Fallback
        return window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
    }

    // Configurar fechas por defecto (últimos 6 meses)
    setDefaultDates() {
        const today = new Date();
        const sixMonthsAgo = new Date();
        sixMonthsAgo.setMonth(today.getMonth() - 6);
        
        const fechaDesde = sixMonthsAgo.toISOString().split('T')[0];
        const fechaHasta = today.toISOString().split('T')[0];
        
        // Establecer en los inputs
        document.getElementById('filter-fecha-desde').value = fechaDesde;
        document.getElementById('filter-fecha-hasta').value = fechaHasta;
        
        // Establecer en los filtros internos
        this.filters.fechaDesde = fechaDesde;
        this.filters.fechaHasta = fechaHasta;
        
        // Actualizar el indicador de período
        this.updatePeriodIndicator();
        
        this.log('Fechas por defecto configuradas:', { fechaDesde, fechaHasta });
    }

    // Actualizar indicador de período seleccionado
    updatePeriodIndicator() {
        const subtitle = document.querySelector('.dashboard-subtitle');
        if (!subtitle) return;
        
        const fechaDesde = this.filters.fechaDesde || 'No definida';
        const fechaHasta = this.filters.fechaHasta || 'No definida';
        
        const formatDate = (dateStr) => {
            if (!dateStr || dateStr === 'No definida') return dateStr;
            try {
                return new Date(dateStr + 'T00:00:00').toLocaleDateString('es-ES');
            } catch {
                return dateStr;
            }
        };
        
        const originalText = 'Panel de control y análisis de costos de nacionalización';
        const periodText = `${originalText}<br><small class="text-muted">Período: ${formatDate(fechaDesde)} - ${formatDate(fechaHasta)}</small>`;
        
        subtitle.innerHTML = periodText;
    }

    log(message, data = null) {
        if (this.debugMode) {
            console.log(`[Dashboard] ${message}`, data || '');
        }
    }

    init() {
        this.log('Inicializando Dashboard');
        this.log('Base URL:', this.baseUrl);
        this.setDefaultDates(); // Configurar fechas por defecto
        this.setupEventListeners();
        this.loadInitialData();
        this.loadProveedores();
    }

    setupEventListeners() {
        // Filtros
        document.getElementById('btn-filter').addEventListener('click', () => this.applyFilters());
        document.getElementById('btn-clear').addEventListener('click', () => this.clearFilters());

        // Auto-refresh cada 5 minutos
        setInterval(() => this.refreshData(), 300000);
    }

    async loadInitialData() {
        this.log('Cargando datos iniciales');
        try {
            await Promise.all([
                this.loadKPIs(),
                this.loadEvolucionMensual(),
                this.loadProveedorBarChart(), // Cambiado para usar el método correcto
                this.loadTopProveedores(),
                this.loadDistribucionCostos()
            ]);
            this.log('Todos los datos iniciales cargados correctamente');
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.showError('Error al cargar los datos del dashboard');
        }
    }

    async apiCall(action, params = {}) {
        const url = new URL('api/dashboard.php', this.baseUrl);
        url.searchParams.append('action', action);
        
        // Obtener entorno de la URL actual si existe
        const urlParams = new URLSearchParams(window.location.search);
        const entorno = urlParams.get('entorno');
        if (entorno) {
            url.searchParams.append('entorno', entorno);
        }
        
        // Mapeo de parámetros
        const paramMap = {
            fechaDesde: 'fecha_desde',
            fechaHasta: 'fecha_hasta'
        };
        
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== '') {
                const paramName = paramMap[key] || key;
                url.searchParams.append(paramName, params[key]);
            }
        });

        this.log(`API Call: ${action}`, {
            url: url.toString(),
            params: params
        });

        try {
            const response = await fetch(url);
            
            this.log(`Response status: ${response.status}`, {
                ok: response.ok,
                headers: Object.fromEntries(response.headers.entries())
            });
            
            // Obtener el texto de la respuesta primero
            const responseText = await response.text();
            this.log('Response text:', responseText.substring(0, 500) + (responseText.length > 500 ? '...' : ''));
            
            if (!response.ok) {
                console.error('Error Response:', responseText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}. Response: ${responseText.substring(0, 200)}`);
            }
            
            // Verificar si el contenido parece ser JSON
            if (!responseText.trim().startsWith('{') && !responseText.trim().startsWith('[')) {
                console.error('Respuesta no es JSON válido:', responseText);
                throw new Error('La respuesta del servidor no es JSON válido. Respuesta: ' + responseText.substring(0, 200));
            }
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Error parsing JSON:', parseError);
                console.error('Raw response:', responseText);
                throw new Error('Error al parsear JSON: ' + parseError.message);
            }
            
            this.log('Parsed data:', data);
            
            // Log del entorno para debugging
            if (data.debug && data.debug.entorno) {
                this.log(`🌍 Entorno API: ${data.debug.entorno}`, data.debug);
            }
            
            if (!data.success) {
                throw new Error(data.error || 'Error en la API');
            }
            
            return data.data;
        } catch (error) {
            console.error(`Error en apiCall para ${action}:`, error);
            this.log(`Error en ${action}:`, error.message);
            throw error;
        }
    }

    async loadKPIs() {
        this.log('Cargando KPIs');
        try {
            const kpis = await this.apiCall('kpis_generales', this.filters);
            this.renderKPIs(kpis);
            this.log('KPIs cargados correctamente', kpis);
        } catch (error) {
            console.error('Error cargando KPIs:', error);
            this.showKPIError();
        }
    }

    renderKPIs(kpis) {
        // Total Despachos
        document.getElementById('kpi-total-despachos').textContent = this.formatNumber(kpis.total_despachos);
        
        // Promedio Costo General
        document.getElementById('kpi-promedio-costo').textContent = kpis.promedio_costo_general + '%';
        
        // Valor Total Importado
        document.getElementById('kpi-valor-total').textContent = this.formatCurrency(kpis.valor_total_importado);
        
        // Total Proveedores
        document.getElementById('kpi-total-proveedores').textContent = this.formatNumber(kpis.total_proveedores);
        
        // Costo Mínimo
        document.getElementById('kpi-costo-minimo').textContent = kpis.minimo_costo + '%';
        
        // Costo Máximo
        document.getElementById('kpi-costo-maximo').textContent = kpis.maximo_costo + '%';
    }

    showKPIError() {
        const kpiValues = [
            'kpi-total-despachos',
            'kpi-promedio-costo',
            'kpi-valor-total',
            'kpi-total-proveedores',
            'kpi-costo-minimo',
            'kpi-costo-maximo'
        ];

        kpiValues.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = 'Error';
                element.style.color = '#dc2626';
            }
        });
    }

    async loadEvolucionMensual() {
        this.log('Cargando evolución mensual');
        try {
            const data = await this.apiCall('evolucion_mensual');
            this.renderEvolucionChart(data);
            this.log('Evolución mensual cargada correctamente');
        } catch (error) {
            console.error('Error cargando evolución mensual:', error);
            this.showChartError('evolucion-chart');
        }
    }

    async loadProveedorBarChart() {
        this.log('Cargando datos por proveedor para gráfico de barras');
        try {
            const data = await this.apiCall('promedio_barras_proveedor', this.filters);
            this.renderProveedorBarChart(data);
            this.log('Gráfico de barras por proveedor cargado correctamente');
        } catch (error) {
            console.error('Error cargando datos por proveedor:', error);
            this.showChartError('proveedor-chart');
        }
    }

    renderProveedorBarChart(data) {
        const ctx = document.getElementById('proveedor-chart');
        if (!ctx) {
            console.error('Canvas proveedor-chart no encontrado');
            return;
        }
        
        if (this.charts.proveedor) {
            this.charts.proveedor.destroy();
        }

        // Tomar solo los top 10 y ordenar de mayor a menor
        const topData = data
            .sort((a, b) => b.promedio_costo_nac - a.promedio_costo_nac)
            .slice(0, 10);

        const labels = topData.map(item => {
            // Truncar nombres largos
            const nombre = item.proveedor || 'Sin nombre';
            return nombre.length > 25 ? nombre.substring(0, 25) + '...' : nombre;
        });

        const costos = topData.map(item => item.promedio_costo_nac || 0);

        // Generar colores degradados
        const colors = costos.map((_, index) => {
            const intensity = 1 - (index / topData.length);
            return `rgba(37, 99, 235, ${0.6 + intensity * 0.4})`;
        });

        const borderColors = costos.map((_, index) => {
            const intensity = 1 - (index / topData.length);
            return `rgba(37, 99, 235, ${0.8 + intensity * 0.2})`;
        });

        this.charts.proveedor = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Costo de Nacionalización (%)',
                    data: costos,
                    backgroundColor: colors,
                    borderColor: borderColors,
                    borderWidth: 2,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Top 10 Proveedores por Costo de Nacionalización',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const index = context[0].dataIndex;
                                return topData[index].proveedor || 'Sin nombre';
                            },
                            label: function(context) {
                                const index = context.dataIndex;
                                const item = topData[index];
                                return [
                                    `Costo: ${context.parsed.y.toFixed(2)}%`,
                                    `Despachos: ${item.total_despachos}`,
                                    `Valor FOB: $${item.valor_total_fob?.toLocaleString('es-ES') || '0'}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Costo de Nacionalización (%)'
                        },
                        grid: {
                            color: '#e2e8f0'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + '%';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Proveedores'
                        },
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    renderEvolucionChart(data) {
        const ctx = document.getElementById('evolucion-chart');
        if (!ctx) {
            console.error('Canvas evolucion-chart no encontrado');
            return;
        }
        
        if (this.charts.evolucion) {
            this.charts.evolucion.destroy();
        }

        // Procesar datos por año
        const years = [...new Set(data.map(item => item.anio))].sort();
        const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        
        const datasets = years.map((year, index) => {
            const yearData = data.filter(item => item.anio === year);
            const monthlyData = months.map((_, monthIndex) => {
                const monthData = yearData.find(item => item.mes === monthIndex + 1);
                return monthData ? monthData.promedio_costo_nac : null;
            });

            return {
                label: year.toString(),
                data: monthlyData,
                borderColor: this.getColorForYear(index),
                backgroundColor: this.getColorForYear(index, 0.1),
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 8
            };
        });

        this.charts.evolucion = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolución Mensual del Costo de Nacionalización (%)',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + (context.parsed.y?.toFixed(2) || 'Sin datos') + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Costo de Nacionalización (%)'
                        },
                        grid: {
                            color: '#e2e8f0'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Meses'
                        },
                        grid: {
                            color: '#e2e8f0'
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
    }

    async loadTopProveedores() {
        this.log('Cargando top proveedores');
        try {
            const data = await this.apiCall('top_proveedores', this.filters);
            this.renderTopProveedoresTable(data);
            this.log('Top proveedores cargados correctamente');
        } catch (error) {
            console.error('Error cargando top proveedores:', error);
            this.showTableError('top-proveedores-table');
        }
    }

    renderTopProveedoresTable(data) {
        const tbody = document.getElementById('top-proveedores-table');
        if (!tbody) {
            console.error('Elemento top-proveedores-table no encontrado');
            return;
        }
        
        tbody.innerHTML = '';

        data.forEach((item, index) => {
            const badgeClass = item.promedio_costo_nac > 15 ? 'status-danger' : 
                              item.promedio_costo_nac > 10 ? 'status-warning' : 'status-success';
            
            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <div class="text-truncate" style="max-width: 200px;" title="${item.proveedor}">
                            ${item.proveedor}
                        </div>
                    </td>
                    <td><code class="small">${item.cod_proveedor}</code></td>
                    <td>
                        <span class="status-badge ${badgeClass}">
                            ${item.promedio_costo_nac}%
                        </span>
                    </td>
                    <td>${this.formatNumber(item.total_despachos)}</td>
                    <td>${this.formatCurrency(item.valor_total_fob)}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    showTableError(tableId) {
        const tbody = document.getElementById(tableId);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar datos</td></tr>';
        }
    }

    async loadDistribucionCostos() {
        this.log('Cargando distribución de costos');
        try {
            const data = await this.apiCall('distribucion_costos', this.filters);
            this.renderDistribucionChart(data);
            this.log('Distribución de costos cargada correctamente');
        } catch (error) {
            console.error('Error cargando distribución de costos:', error);
            this.showChartError('distribucion-chart');
        }
    }

    renderDistribucionChart(data) {
        const ctx = document.getElementById('distribucion-chart');
        if (!ctx) {
            console.error('Canvas distribucion-chart no encontrado');
            return;
        }
        
        if (this.charts.distribucion) {
            this.charts.distribucion.destroy();
        }

        const colors = [
            '#059669', '#0891b2', '#2563eb', '#d97706', '#dc2626'
        ];

        this.charts.distribucion = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.rango_costo),
                datasets: [{
                    data: data.map(item => item.cantidad_despachos),
                    backgroundColor: colors.slice(0, data.length),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribución de Despachos por Rango de Costo',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} despachos (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    async loadProveedores() {
        this.log('Cargando lista de proveedores');
        try {
            const proveedores = await this.apiCall('lista_proveedores');
            this.renderProveedoresSelect(proveedores);
            this.log('Proveedores cargados correctamente');
        } catch (error) {
            console.error('Error cargando proveedores:', error);
        }
    }

    renderProveedoresSelect(proveedores) {
        const select = document.getElementById('filter-proveedor');
        if (!select) {
            console.error('Select filter-proveedor no encontrado');
            return;
        }
        
        select.innerHTML = '<option value="">Todos los proveedores</option>';
        
        proveedores.forEach(prov => {
            const option = document.createElement('option');
            option.value = prov.cod_proveedor;
            option.textContent = prov.proveedor;
            select.appendChild(option);
        });
    }

    applyFilters() {
        this.filters.fechaDesde = document.getElementById('filter-fecha-desde').value || null;
        this.filters.fechaHasta = document.getElementById('filter-fecha-hasta').value || null;
        this.filters.proveedor = document.getElementById('filter-proveedor').value || null;

        this.log('Aplicando filtros', this.filters);

        // Actualizar indicador de período
        this.updatePeriodIndicator();

        // Recargar datos con filtros (excepto evolución mensual)
        this.loadKPIs();
        this.loadProveedorBarChart();
        this.loadTopProveedores();
        this.loadDistribucionCostos();

        // Mostrar notificación
        this.showNotification('Filtros aplicados correctamente', 'success');
    }

    clearFilters() {
        // Restablecer a las fechas por defecto en lugar de limpiar completamente
        this.setDefaultDates();
        document.getElementById('filter-proveedor').value = '';
        
        this.filters.proveedor = null;
        // fechaDesde y fechaHasta ya se establecen en setDefaultDates()

        this.log('Filtros restablecidos a valores por defecto');

        // Recargar todos los datos
        this.loadInitialData();
        this.showNotification('Filtros restablecidos', 'info');
    }

    refreshData() {
        this.log('Actualizando datos del dashboard...');
        this.loadInitialData();
    }

    // Utility functions
    formatNumber(num) {
        return new Intl.NumberFormat('es-ES').format(num || 0);
    }

    formatCurrency(num) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(num || 0);
    }

    getColorForYear(index) {
        const colors = ['#2563eb', '#059669', '#d97706', '#dc2626', '#0891b2'];
        return colors[index % colors.length];
    }

    showError(message) {
        console.error(message);
        this.showNotification(message, 'error');
    }

    showChartError(chartId) {
        const chartElement = document.getElementById(chartId);
        if (!chartElement) {
            console.error(`Elemento ${chartId} no encontrado`);
            return;
        }
        
        const chartContainer = chartElement.parentElement;
        if (!chartContainer) {
            console.error(`Container para ${chartId} no encontrado`);
            return;
        }
        
        chartContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="empty-state-title">Error al cargar gráfico</div>
                <div class="empty-state-description">
                    No se pudieron cargar los datos. Revisa la consola para más detalles.
                </div>
            </div>
        `;
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            background: white;
            border-left: 4px solid var(--${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'}-color);
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
}

// Inicializar dashboard cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new Dashboard();
});

// Configuración de Chart.js
Chart.defaults.font.family = 'Inter, -apple-system, BlinkMacSystemFont, sans-serif';
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = '#e2e8f0';
Chart.defaults.backgroundColor = '#f8fafc';