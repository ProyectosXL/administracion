// Dashboard JavaScript
class Dashboard {
    constructor() {
        this.charts = {};
        this.filters = {
            fechaDesde: null,
            fechaHasta: null,
            proveedor: null
        };
        this.init();
    }

    init() {
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
        try {
            await Promise.all([
                this.loadKPIs(),
                this.loadEvolucionMensual(),
                this.loadTopProveedores(),
                this.loadDistribucionCostos()
            ]);
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.showError('Error al cargar los datos del dashboard');
        }
    }

    async apiCall(action, params = {}) {
        const url = new URL('api/dashboard.php', window.location.origin + window.location.pathname.replace('dashboard.php', ''));
        url.searchParams.append('action', action);
        
        // Corregir nombres de parámetros para que coincidan con el backend
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

        try {
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Respuesta no es JSON:', text);
                throw new Error('La respuesta del servidor no es JSON válido');
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error en la API');
            }
            
            return data.data;
        } catch (error) {
            console.error('Error en apiCall:', error);
            throw error;
        }
    }

    async loadKPIs() {
        try {
            const kpis = await this.apiCall('kpis_generales', this.filters);
            this.renderKPIs(kpis);
        } catch (error) {
            console.error('Error cargando KPIs:', error);
        }
    }

    renderKPIs(kpis) {
        // Total Despachos
        document.getElementById('kpi-total-despachos').textContent = this.formatNumber(kpis.total_despachos);
        
        // Promedio Costo General
        document.getElementById('kpi-promedio-costo').textContent = kpis.promedio_costo_general + '%';
        
        // Valor Total Importado
        document.getElementById('kpi-valor-total').textContent = + this.formatCurrency(kpis.valor_total_importado);
        
        // Total Proveedores
        document.getElementById('kpi-total-proveedores').textContent = this.formatNumber(kpis.total_proveedores);
        
        // Costo Mínimo
        document.getElementById('kpi-costo-minimo').textContent = kpis.minimo_costo + '%';
        
        // Costo Máximo
        document.getElementById('kpi-costo-maximo').textContent = kpis.maximo_costo + '%';
    }

    async loadEvolucionMensual() {
        try {
            const data = await this.apiCall('evolucion_mensual');
            this.renderEvolucionChart(data);
        } catch (error) {
            console.error('Error cargando evolución mensual:', error);
            this.showChartError('evolucion-chart');
        }
    }

    renderEvolucionChart(data) {
        const ctx = document.getElementById('evolucion-chart').getContext('2d');
        
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

    async loadProveedorBarChart() {
        try {
            const data = await this.apiCall('promedio_barras_proveedor', this.filters);
            this.renderProveedorBarChart(data);
        } catch (error) {
            console.error('Error cargando datos por proveedor:', error);
            this.showChartError('proveedor-chart');
        }
    }

    renderProveedorChart(data) {
        const ctx = document.getElementById('proveedor-chart').getContext('2d');
        
        if (this.charts.proveedor) {
            this.charts.proveedor.destroy();
        }

        // Agrupar por proveedor y obtener los top 10
        const proveedoresData = {};
        data.forEach(item => {
            if (!proveedoresData[item.proveedor]) {
                proveedoresData[item.proveedor] = [];
            }
            proveedoresData[item.proveedor].push({
                fecha: `${item.mes}/${item.anio}`,
                costo: item.promedio_costo_nac,
                despachos: item.total_despachos
            });
        });

        // Obtener top 10 proveedores por promedio
        const topProveedores = Object.keys(proveedoresData)
            .map(proveedor => ({
                nombre: proveedor,
                promedio: proveedoresData[proveedor].reduce((sum, item) => sum + item.costo, 0) / proveedoresData[proveedor].length
            }))
            .sort((a, b) => b.promedio - a.promedio)
            .slice(0, 10);

        const datasets = topProveedores.map((prov, index) => ({
            label: prov.nombre.length > 25 ? prov.nombre.substring(0, 25) + '...' : prov.nombre,
            data: proveedoresData[prov.nombre].map(item => item.costo),
            borderColor: this.getColorForProvider(index),
            backgroundColor: this.getColorForProvider(index, 0.1),
            borderWidth: 2,
            fill: false,
            tension: 0.3
        }));

        // Crear labels únicos ordenados
        const allDates = [...new Set(data.map(item => `${item.mes}/${item.anio}`))].sort();

        this.charts.proveedor = new Chart(ctx, {
            type: 'line',
            data: {
                labels: allDates,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Promedio Mensual por Proveedor (Top 10)',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: { size: 10 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Costo de Nacionalización (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Periodo (Mes/Año)'
                        }
                    }
                }
            }
        });
    }

    async loadTopProveedores() {
        try {
            const data = await this.apiCall('top_proveedores', this.filters);
            this.renderTopProveedoresTable(data);
        } catch (error) {
            console.error('Error cargando top proveedores:', error);
        }
    }

    renderTopProveedoresTable(data) {
        const tbody = document.getElementById('top-proveedores-table');
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

    async loadDistribucionCostos() {
        try {
            const data = await this.apiCall('distribucion_costos', this.filters);
            this.renderDistribucionChart(data);
        } catch (error) {
            console.error('Error cargando distribución de costos:', error);
            this.showChartError('distribucion-chart');
        }
    }

    renderDistribucionChart(data) {
        const ctx = document.getElementById('distribucion-chart').getContext('2d');
        
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
        try {
            const proveedores = await this.apiCall('lista_proveedores');
            this.renderProveedoresSelect(proveedores);
        } catch (error) {
            console.error('Error cargando proveedores:', error);
        }
    }

    renderProveedoresSelect(proveedores) {
        const select = document.getElementById('filter-proveedor');
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

        // Recargar datos con filtros (excepto evolución mensual)
        this.loadKPIs();
        this.loadProveedorChart();
        this.loadTopProveedores();
        this.loadDistribucionCostos();

        // Mostrar notificación
        this.showNotification('Filtros aplicados correctamente', 'success');
    }

    clearFilters() {
        document.getElementById('filter-fecha-desde').value = '';
        document.getElementById('filter-fecha-hasta').value = '';
        document.getElementById('filter-proveedor').value = '';
        
        this.filters = {
            fechaDesde: null,
            fechaHasta: null,
            proveedor: null
        };

        // Recargar todos los datos
        this.loadInitialData();
        this.showNotification('Filtros limpiados', 'info');
    }

    refreshData() {
        console.log('Actualizando datos del dashboard...');
        this.loadInitialData();
    }

    // Utility functions
    formatNumber(num) {
        return new Intl.NumberFormat('es-ES').format(num);
    }

    formatCurrency(num) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    getColorForYear(index) {
        const colors = ['#2563eb', '#059669', '#d97706', '#dc2626', '#0891b2'];
        return colors[index % colors.length];
    }

    getColorForProvider(index, alpha = 1) {
        const colors = [
            '#2563eb', '#059669', '#d97706', '#dc2626', '#0891b2',
            '#7c3aed', '#ec4899', '#f59e0b', '#10b981', '#3b82f6'
        ];
        const color = colors[index % colors.length];
        
        if (alpha < 1) {
            // Convertir hex a rgba
            const hex = color.replace('#', '');
            const r = parseInt(hex.substr(0, 2), 16);
            const g = parseInt(hex.substr(2, 2), 16);
            const b = parseInt(hex.substr(4, 2), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
        
        return color;
    }

    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill"></i>
            ${message}
        `;
        
        const container = document.querySelector('.dashboard-container');
        container.insertBefore(errorDiv, container.firstChild);
        
        setTimeout(() => errorDiv.remove(), 5000);
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
                    No se pudieron cargar los datos. Inténtalo más tarde.
                </div>
            </div>
        `;
    }

    updateFilterDisplay() {
        // Crear o actualizar indicador de período seleccionado
        let filterIndicator = document.getElementById('filter-period-indicator');
        
        if (!filterIndicator) {
            filterIndicator = document.createElement('div');
            filterIndicator.id = 'filter-period-indicator';
            filterIndicator.className = 'alert alert-info mt-2';
            filterIndicator.style.cssText = `
                background: #e0f2fe;
                border: 1px solid #0891b2;
                color: #0891b2;
                padding: 0.5rem 1rem;
                border-radius: 8px;
                font-size: 0.875rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            `;
            
            const filtersSection = document.querySelector('.filters-section');
            filtersSection.appendChild(filterIndicator);
        }
        
        const fechaDesde = this.filters.fechaDesde || 'No definida';
        const fechaHasta = this.filters.fechaHasta || 'No definida';
        const proveedor = this.filters.proveedor || 'Todos';
        
        // Formatear fechas para mejor lectura
        const formatDate = (dateStr) => {
            if (!dateStr || dateStr === 'No definida') return dateStr;
            try {
                return new Date(dateStr + 'T00:00:00').toLocaleDateString('es-ES');
            } catch {
                return dateStr;
            }
        };
        
        filterIndicator.innerHTML = `
            <i class="bi bi-info-circle"></i>
            <strong>Período aplicado:</strong> 
            ${formatDate(fechaDesde)} - ${formatDate(fechaHasta)} | 
            <strong>Proveedor:</strong> ${proveedor === '' ? 'Todos' : proveedor}
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
        }, 3000);
    }
}

// Inicializar dashboard cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new Dashboard();
});

// Inicializar Chart.js defaults
Chart.defaults.font.family = 'Inter, -apple-system, BlinkMacSystemFont, sans-serif';
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = '#e2e8f0';
Chart.defaults.backgroundColor = '#f8fafc';