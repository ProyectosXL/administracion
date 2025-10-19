<!-- Pestaña Ranking: % Costo de Ocupación YoY -->
<div class="ranking-container">
    <!-- Filtros Section -->
    <div class="filters-section">
        <div class="section-title">
            <i class="bi bi-funnel"></i>
            <span>Período de Análisis</span>
        </div>
        
        <div class="filters-form">
            <!-- Píldoras de filtros rápidos -->
            <div class="filter-group">
                <div class="filter-pills">
                    <button type="button" class="pill-btn" data-period="3" aria-label="Últimos 3 meses">
                        <i class="bi bi-calendar3"></i> Últimos 3 meses
                    </button>
                    <button type="button" class="pill-btn" data-period="6" aria-label="Últimos 6 meses">
                        <i class="bi bi-calendar3"></i> Últimos 6 meses
                    </button>
                    <button type="button" class="pill-btn active" data-period="12" aria-label="Últimos 12 meses">
                        <i class="bi bi-calendar3"></i> Últimos 12 meses
                    </button>
                    <button type="button" class="pill-btn" data-period="custom" aria-label="Rango personalizado">
                        <i class="bi bi-calendar-range"></i> Personalizado
                    </button>
                </div>
                
                <!-- Rango personalizado (oculto por defecto) -->
                <div class="custom-range-inputs" style="display: none;">
                    <div class="filter-field">
                        <label for="rankingFechaDesde" class="filter-label">
                            <i class="bi bi-calendar-event"></i> Desde:
                        </label>
                        <input type="date" id="rankingFechaDesde" class="form-control" 
                               style="height: calc(2.25rem + 2px);">
                    </div>
                    
                    <div class="filter-field">
                        <label for="rankingFechaHasta" class="filter-label">
                            <i class="bi bi-calendar-event"></i> Hasta:
                        </label>
                        <input type="date" id="rankingFechaHasta" class="form-control" 
                               style="height: calc(2.25rem + 2px);">
                    </div>
                </div>
                
                <!-- Selector de Top N -->
                <div class="filter-field" style="display: flex; align-items: center; gap: 10px;">
                    <label for="rankingTopN" class="filter-label" style="margin-bottom: 0;">
                        <i class="bi bi-trophy"></i> Mostrar:
                    </label>
                    <select id="rankingTopN" class="form-control" style="width: 150px; height: calc(2.25rem + 2px);">
                        <option value="5">Top 5</option>
                        <option value="10" selected>Top 10</option>
                        <option value="20">Top 20</option>
                        <option value="all">Todos</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del período -->
    <div class="period-info" id="rankingPeriodInfo" style="display: none;">
        <div class="info-card">
            <div class="info-item">
                <i class="bi bi-calendar-check"></i>
                <div>
                    <span class="info-label">Período Actual:</span>
                    <span class="info-value" id="periodoActualText">--</span>
                </div>
            </div>
            <div class="info-item">
                <i class="bi bi-calendar-event"></i>
                <div>
                    <span class="info-label">Período Anterior (YoY):</span>
                    <span class="info-value" id="periodoAnteriorText">--</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico Section -->
    <div class="chart-section" id="rankingChartSection" style="display: none;">
        <div class="chart-header">
            <h2>
                <i class="bi bi-bar-chart"></i> 
                Ranking: % Costo de Ocupación (Actual vs Año Anterior)
            </h2>
            <div class="chart-actions">
                <button class="legend-btn" id="btnRankingLegend" title="Ver leyenda">
                    <i class="bi bi-info-circle"></i>
                    Leyenda
                </button>
                <button class="export-btn" id="btnExportarRanking" title="Exportar imagen">
                    <i class="bi bi-download"></i>
                    Exportar
                </button>
            </div>
        </div>
        
        <!-- Canvas para el gráfico -->
        <div class="chart-container" style="position: relative; height: 700px; max-width: 1300px; margin: 20px auto 0;">
            <canvas id="rankingChart"></canvas>
        </div>
        
        <!-- Aviso cuando no hay datos previos -->
        <div class="alert alert-info" id="rankingNoDataAlert" style="display: none; margin-top: 20px;">
            <i class="bi bi-info-circle"></i>
            <span>Algunas sucursales no tienen datos del período anterior para comparación YoY.</span>
        </div>
    </div>

    <!-- Estado inicial -->
    <div class="empty-state" id="rankingEmptyState">
        <div class="empty-icon">
            <i class="bi bi-bar-chart"></i>
        </div>
        <h3>Seleccione un período para ver el ranking</h3>
        <p>Elija un rango de tiempo para visualizar el ranking de % Costo de Ocupación comparado con el año anterior</p>
    </div>
</div>

<style>
/* Estilos específicos para la pestaña Ranking */
.ranking-container {
    width: 100%;
}

.filter-pills {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.pill-btn {
    padding: 10px 20px;
    border: 2px solid #e0e0e0;
    background: white;
    border-radius: 25px;
    font-weight: 600;
    color: #7f8c8d;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.pill-btn:hover {
    border-color: #3498db;
    color: #3498db;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
}

.pill-btn.active {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    border-color: #2980b9;
    color: white;
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.pill-btn:focus {
    outline: 3px solid rgba(52, 152, 219, 0.3);
    outline-offset: 2px;
}

.custom-range-inputs {
    display: flex;
    gap: 15px;
    margin-top: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.period-info {
    margin: 20px 0;
}

.info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    justify-content: space-around;
    gap: 20px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.info-item {
    display: flex;
    align-items: center;
    gap: 15px;
    color: white;
}

.info-item i {
    font-size: 2rem;
    opacity: 0.9;
}

.info-label {
    display: block;
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 5px;
}

.info-value {
    display: block;
    font-size: 1.1rem;
    font-weight: bold;
}

.chart-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #ecf0f1;
}

.chart-header h2 {
    font-size: 1.5rem;
    color: #2c3e50;
    margin: 0;
}

.chart-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 768px) {
    .filter-pills {
        flex-direction: column;
    }
    
    .pill-btn {
        width: 100%;
        justify-content: center;
    }
    
    .custom-range-inputs {
        flex-direction: column;
    }
    
    .info-card {
        flex-direction: column;
    }
    
    /* Alturas proporcionales en móvil según Top N */
    .chart-container[style*="600px"] {
        height: 400px !important;
    }
    
    .chart-container[style*="1000px"] {
        height: 600px !important;
    }
    
    .chart-container[style*="1900px"],
    .chart-container[style*="2000px"] {
        height: 800px !important;
    }
}
</style>
