
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard - Comercio Exterior</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Dashboard CSS -->
    <link rel="stylesheet" href="css/dashboard.css">

    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">
</head>

<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">
                    <i class="bi bi-graph-up-arrow"></i>
                    Dashboard de Comercio Exterior
                </h1>
                <p class="dashboard-subtitle">Panel de control y análisis de costos de nacionalización</p>
            </div>
            <div class="header-actions">
                <a href="mostrarOrden.php" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                    Volver a Despachos
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <h3 class="filters-title">
                <i class="bi bi-funnel"></i>
                Filtros
            </h3>
            <div class="filters-form">
                <div class="filter-group">
                    <label class="filter-label" for="filter-fecha-desde">
                        <i class="bi bi-calendar-date"></i> Fecha Desde
                    </label>
                    <input type="date" class="form-control-modern" id="filter-fecha-desde">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label" for="filter-fecha-hasta">
                        <i class="bi bi-calendar-check"></i> Fecha Hasta
                    </label>
                    <input type="date" class="form-control-modern" id="filter-fecha-hasta">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label" for="filter-proveedor">
                        <i class="bi bi-building"></i> Proveedor
                    </label>
                    <select class="form-control-modern" id="filter-proveedor">
                        <option value="">Cargando...</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="button" class="btn-filter" id="btn-filter">
                        <i class="bi bi-search"></i>
                        Aplicar Filtros
                    </button>
                </div>
                
                <div class="filter-group">
                    <button type="button" class="btn-clear" id="btn-clear">
                        <i class="bi bi-arrow-clockwise"></i>
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- KPIs Grid -->
        <div class="kpis-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Total Despachos</div>
                    <div class="kpi-icon primary">
                        <i class="bi bi-archive"></i>
                    </div>
                </div>
                <div class="kpi-value" id="kpi-total-despachos">-</div>
                <div class="kpi-description">Despachos procesados</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Costo Promedio</div>
                    <div class="kpi-icon success">
                        <i class="bi bi-percent"></i>
                    </div>
                </div>
                <div class="kpi-value" id="kpi-promedio-costo">-</div>
                <div class="kpi-description">Costo de nacionalización</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Valor Total</div>
                    <div class="kpi-icon info">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
                <div class="kpi-value" id="kpi-valor-total">-</div>
                <div class="kpi-description">FOB importado</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Proveedores</div>
                    <div class="kpi-icon warning">
                        <i class="bi bi-building"></i>
                    </div>
                </div>
                <div class="kpi-value" id="kpi-total-proveedores">-</div>
                <div class="kpi-description">Proveedores activos</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Costo Mínimo</div>
                    <div class="kpi-icon success">
                        <i class="bi bi-arrow-down"></i>
                    </div>
                </div>
                <div class="kpi-value" id="kpi-costo-minimo">-</div>
                <div class="kpi-description">Menor costo registrado</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title">Costo Máximo</div>
                    <div class="kpi-icon danger">
                        <i class="bi bi-arrow-up"></i>
                    </div>
                </div>
                <div class="kpi-value" id="kpi-costo-maximo">-</div>
                <div class="kpi-description">Mayor costo registrado</div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Evolución Mensual -->
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <div class="chart-title">
                            <i class="bi bi-graph-up"></i>
                            Evolución Mensual del Costo de Nacionalización
                        </div>
                        <div class="chart-subtitle">Últimos 3 años - Promedio mensual por año</div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="evolucion-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Secondary Charts -->
        <div class="secondary-charts">
            <!-- Gráfico de Barras por Proveedor -->
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <div class="chart-title">
                            <i class="bi bi-bar-chart"></i>
                            Promedio de Costo por Proveedor
                        </div>
                        <div class="chart-subtitle">Ordenado de mayor a menor - Con filtros aplicados</div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="proveedor-chart"></canvas>
                </div>
            </div>

            <!-- Distribución de Costos -->
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <div class="chart-title">
                            <i class="bi bi-pie-chart"></i>
                            Distribución por Rangos de Costo
                        </div>
                        <div class="chart-subtitle">< 40%, Entre 40% y 80%, > 80%</div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="distribucion-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Proveedores Table -->
        <div class="table-card">
            <div class="table-header">
                <h3 class="table-title">
                    <i class="bi bi-trophy"></i>
                    Top 10 Proveedores por Costo de Nacionalización
                </h3>
            </div>
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Ranking</th>
                            <th>Proveedor</th>
                            <th>Código</th>
                            <th>Promedio Costo</th>
                            <th>Total Despachos</th>
                            <th>Valor FOB Total</th>
                        </tr>
                    </thead>
                    <tbody id="top-proveedores-table">
                        <tr>
                            <td colspan="6" class="text-center">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>