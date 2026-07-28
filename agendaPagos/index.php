<?php
// index.php - Pantalla principal de la Agenda y Control de Medios de Pago
// Cargamos Dotenv para las variables de entorno si existe
require_once __DIR__ . '/../vendor/autoload.php'; // Cargamos el cargador de clases estándar de composer
// Si no, incluimos el Dotenv localmente usando la clase classEnv del sistema o phpdotenv
require_once __DIR__ . '/../class/classEnv.php'; 

// Cargar variables de entorno del archivo .env en W:\
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    try {
        $dotenv = new DotEnv($envPath);
        // Las variables se cargan al entorno o se leen con listVars
        $envVars = $dotenv->listVars();
    } catch (Exception $e) {
        $envVars = $_ENV;
    }
} else {
    $envVars = $_ENV;
}

// Establecer fechas predeterminadas (hoy)
$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$procesador = $_GET['procesador'] ?? 'todos';
$estado = $_GET['estado'] ?? 'todos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Medios de Pago | Administración</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="fa-solid fa-calculator-combined text-info me-2 fs-4"></i>
                <span class="fw-bold tracking-tight">Control de Medios de Pago</span>
            </a>
            <div class="d-flex text-light align-items-center small">
                <span><i class="fa-regular fa-clock me-1"></i> Actualizado hoy</span>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
        


        <!-- Filtros -->
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-body">
                <form id="filter-form" method="GET" class="row g-3 align-items-end">
                    <div class="col-12 col-md-2">
                        <label for="desde" class="form-label fw-semibold text-secondary small">Fecha Desde</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fa-regular fa-calendar"></i></span>
                            <input type="date" id="desde" name="desde" class="form-control" value="<?php echo htmlspecialchars($desde); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="hasta" class="form-label fw-semibold text-secondary small">Fecha Hasta</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fa-regular fa-calendar"></i></span>
                            <input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo htmlspecialchars($hasta); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="sucursal" class="form-label fw-semibold text-secondary small">Local / Sucursal</label>
                        <select id="sucursal" name="sucursal" class="form-select form-select-sm">
                            <option value="todos">Todos los locales</option>
                            <option value="Local 1 - Abasto">Local 1 - Abasto</option>
                            <option value="Local 2 - Palermo">Local 2 - Palermo</option>
                            <option value="Local 3 - Belgrano">Local 3 - Belgrano</option>
                            <option value="Local 4 - Centro">Local 4 - Centro</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="procesador" class="form-label fw-semibold text-secondary small">Procesadora</label>
                        <select id="procesador" name="procesador" class="form-select form-select-sm">
                            <option value="todos" <?php echo $procesador === 'todos' ? 'selected' : ''; ?>>Todas las pasarelas</option>
                            <option value="gocuotas" <?php echo $procesador === 'gocuotas' ? 'selected' : ''; ?>>GoCuotas</option>
                            <option value="mercadopago" <?php echo $procesador === 'mercadopago' ? 'selected' : ''; ?>>MercadoPago</option>
                            <option value="getnet" <?php echo $procesador === 'getnet' ? 'selected' : ''; ?>>Getnet</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="estado" class="form-label fw-semibold text-secondary small">Estado Cobro</label>
                        <select id="estado" name="estado" class="form-select form-select-sm">
                            <option value="todos" <?php echo $estado === 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                            <option value="approved" <?php echo $estado === 'approved' ? 'selected' : ''; ?>>Aprobado</option>
                            <option value="pending" <?php echo $estado === 'pending' ? 'selected' : ''; ?>>Pendiente / En Proceso</option>
                            <option value="rejected" <?php echo $estado === 'rejected' ? 'selected' : ''; ?>>Rechazado</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Filtrar Agenda
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pestañas de Navegación -->
        <ul class="nav nav-tabs mb-4 gap-2 border-bottom-0" id="agenda-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold px-4 border rounded-3 shadow-sm" id="tablero-tab" data-bs-toggle="tab" data-bs-target="#tablero-pane" type="button" role="tab" aria-controls="tablero-pane" aria-selected="true">
                    <i class="fa-solid fa-gauge-high me-2 text-primary"></i>Consola de Transacciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4 border rounded-3 shadow-sm" id="control-diario-tab" data-bs-toggle="tab" data-bs-target="#control-diario-pane" type="button" role="tab" aria-controls="control-diario-pane" aria-selected="false">
                    <i class="fa-solid fa-calendar-check me-2 text-success"></i>Control Diario por Local
                </button>
            </li>
        </ul>

        <div class="tab-content" id="agenda-tab-content">
            <!-- Pestaña 1: Consola de Transacciones -->
            <div class="tab-pane fade show active" id="tablero-pane" role="tabpanel" aria-labelledby="tablero-tab" tabindex="0">
                
                <!-- Indicadores / Métricas Clave -->
        <div class="row g-4 mb-4" id="metrics-container">
            <!-- Métricas Generales -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase tracking-wider small fw-semibold text-muted">Venta Bruta Total</span>
                        <div class="metric-icon bg-light-primary text-primary"><i class="fa-solid fa-money-bill-wave"></i></div>
                    </div>
                    <h3 class="fw-bold mb-1" id="metric-gross-total">$0,00</h3>
                    <p class="text-muted small mb-0"><span id="metric-trans-count">0</span> transacciones registradas</p>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase tracking-wider small fw-semibold text-muted">Retenciones y Comisiones</span>
                        <div class="metric-icon bg-light-danger text-danger"><i class="fa-solid fa-percent"></i></div>
                    </div>
                    <h3 class="fw-bold mb-1 text-danger" id="metric-fees-total">$0,00</h3>
                    <p class="text-muted small mb-0">Tasa promedio estimada: <span id="metric-fee-percentage">0%</span></p>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase tracking-wider small fw-semibold text-muted">Neto a Acreditar</span>
                        <div class="metric-icon bg-light-success text-success"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    </div>
                    <h3 class="fw-bold mb-1 text-success" id="metric-net-total">$0,00</h3>
                    <p class="text-muted small mb-0">Dinero real a ingresar a cuentas</p>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase tracking-wider small fw-semibold text-muted">Ticket Promedio</span>
                        <div class="metric-icon bg-light-warning text-warning"><i class="fa-solid fa-ticket"></i></div>
                    </div>
                    <h3 class="fw-bold mb-1" id="metric-ticket-average">$0,00</h3>
                    <p class="text-muted small mb-0">Valor medio por operación de cobro</p>
                </div>
            </div>
        </div>

        <!-- Distribución de Cobros por Procesadora -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="card-title fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-pie text-secondary me-2"></i>Distribución de Medios</h5>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div class="d-flex flex-column gap-3" id="provider-distribution-bars">
                            <!-- Progreso por pasarelas -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-secondary small"><i class="fa-solid fa-credit-card text-pink me-1"></i> GoCuotas (Débito en Cuotas)</span>
                                    <span class="small fw-bold" id="dist-gocuotas-percentage">0% ($0,00)</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-pink" id="dist-gocuotas-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-secondary small"><i class="fa-solid fa-wallet text-sky me-1"></i> MercadoPago</span>
                                    <span class="small fw-bold" id="dist-mercadopago-percentage">0% ($0,00)</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-sky" id="dist-mercadopago-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-secondary small"><i class="fa-solid fa-building-columns text-purple me-1"></i> Getnet (Santander)</span>
                                    <span class="small fw-bold" id="dist-getnet-percentage">0% ($0,00)</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-purple" id="dist-getnet-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Previsión de Agenda de Acreditaciones -->
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold mb-0 text-dark"><i class="fa-solid fa-timeline text-secondary me-2"></i>Calendario Estimado de Acreditación</h5>
                        <span class="badge bg-light-info text-info rounded-pill px-2 py-1 fs-9">Flujo de Caja</span>
                    </div>
                    <div class="card-body">
                        <div class="row row-cols-1 row-cols-md-3 g-3" id="acreditation-forecast">
                            <div class="col">
                                <div class="p-3 border rounded-3 bg-light-subtle h-100">
                                    <div class="d-flex align-items-center text-success mb-2">
                                        <i class="fa-solid fa-bolt-lightning me-2"></i>
                                        <span class="fw-bold text-secondary small">Acreditación Inmediata</span>
                                    </div>
                                    <h4 class="fw-bold mb-1" id="forecast-immediate">$0,00</h4>
                                    <p class="text-muted fs-8 mb-0">Dinero disponible hoy. Típico de MercadoPago en modo instantáneo.</p>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-3 border rounded-3 bg-light-subtle h-100">
                                    <div class="d-flex align-items-center text-primary mb-2">
                                        <i class="fa-solid fa-clock-rotate-left me-2"></i>
                                        <span class="fw-bold text-secondary small">Próximas 24/48 Hs</span>
                                    </div>
                                    <h4 class="fw-bold mb-1" id="forecast-short">$0,00</h4>
                                    <p class="text-muted fs-8 mb-0">Dinero en tránsito bancario. Típico de ventas directas de Getnet.</p>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-3 border rounded-3 bg-light-subtle h-100">
                                    <div class="d-flex align-items-center text-warning mb-2">
                                        <i class="fa-solid fa-calendar-days me-2"></i>
                                        <span class="fw-bold text-secondary small">Liquidaciones Programadas</span>
                                    </div>
                                    <h4 class="fw-bold mb-1" id="forecast-scheduled">$0,00</h4>
                                    <p class="text-muted fs-8 mb-0">Cobro en cuotas (GoCuotas / Getnet Cuotas). Acreditaciones semanales/mensuales.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Cobros -->
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="card-title fw-bold mb-0 text-dark"><i class="fa-solid fa-list-check text-secondary me-2"></i>Detalle de Transacciones</h5>
                <div class="d-flex gap-2">
                    <input type="text" id="quick-search" class="form-control form-control-sm" placeholder="Búsqueda rápida (Cliente, ID)..." style="width: 250px;">
                    <button class="btn btn-sm btn-outline-secondary" id="export-excel-btn"><i class="fa-regular fa-file-excel me-1"></i> Exportar</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="payments-table">
                        <thead class="table-light">
                            <tr>
                                <th>Transacción ID</th>
                                <th>Fecha Pago</th>
                                <th>Procesadora</th>
                                <th>Cliente</th>
                                <th>Planes / Cuotas</th>
                                <th class="text-end">Bruto</th>
                                <th class="text-end">Comisión/Imp.</th>
                                <th class="text-end">Neto</th>
                                <th>Est. Acreditación</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="payments-tbody">
                            <!-- Los cobros se insertarán aquí dinámicamente mediante AJAX -->
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    Cargando transacciones de la agenda...
                                </td>
                            </tr>
                        </tbody>
                    </table>
            </div></div></div></div><!-- Fin de Pestaña 1 -->

            <!-- Pestaña 2: Control Diario por Local (Excel matching) -->
            <div class="tab-pane fade" id="control-diario-pane" role="tabpanel" aria-labelledby="control-diario-tab" tabindex="0">
                <!-- Tarjetas KPI de Conciliación -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Esperado API (Bruto)</span>
                                <div class="metric-icon bg-light-primary text-primary"><i class="fa-solid fa-cloud-arrow-down"></i></div>
                            </div>
                            <h4 class="fw-bold mb-1" id="kpi-reconcile-expected">$0,00</h4>
                            <p class="text-muted small mb-0">Total registrado en pasarelas</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Declarado Físico</span>
                                <div class="metric-icon bg-light-success text-success"><i class="fa-solid fa-store"></i></div>
                            </div>
                            <h4 class="fw-bold mb-1" id="kpi-reconcile-declared">$0,00</h4>
                            <p class="text-muted small mb-0">Total informado por locales</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Diferencia Total</span>
                                <div class="metric-icon bg-light-danger text-danger" id="kpi-reconcile-diff-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                            </div>
                            <h4 class="fw-bold mb-1 text-danger" id="kpi-reconcile-diff">$0,00</h4>
                            <p class="text-muted small mb-0" id="kpi-reconcile-diff-status">Discrepancias a conciliar</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Planillas Conciliadas</span>
                                <div class="metric-icon bg-light-warning text-warning" id="kpi-reconcile-status-icon"><i class="fa-solid fa-list-check"></i></div>
                            </div>
                            <h4 class="fw-bold mb-1" id="kpi-reconcile-status">0%</h4>
                            <p class="text-muted small mb-0" id="kpi-reconcile-count">0 de 0 controladas</p>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-scale-balanced text-secondary me-2"></i>Conciliación de Medios de Pago Diaria
                            </h5>
                            <span class="text-muted small">Controla las ventas brutas reportadas físicamente en cada sucursal contra lo acreditado por API</span>
                        </div>
                        <button class="btn btn-sm btn-success" id="export-excel-reconcile-btn">
                            <i class="fa-regular fa-file-excel me-1"></i> Exportar Planilla de Control
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="reconcile-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha Venta</th>
                                        <th>Sucursal / Local</th>
                                        <th>Pasarela/Procesador</th>
                                        <th class="text-end">Importe API (Esperado)</th>
                                        <th class="text-end" style="width: 250px;">Declarado Físico (Local)</th>
                                        <th class="text-end">Diferencia</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="reconcile-tbody">
                                    <!-- Los datos agregados se insertarán aquí dinámicamente -->
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            Cargando planilla de conciliación...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div><!-- Fin de Pestaña 2 -->
        </div><!-- Fin tab-content -->

    </div>



    <!-- Modal Detalles de Transacción -->
    <div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" id="details-modal-content">
                <!-- Se rellena vía JS -->
            </div>
        </div>
    </div>

    <!-- jQuery & Bootstrap 5 JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/app.js"></script>
</body>
</html>
