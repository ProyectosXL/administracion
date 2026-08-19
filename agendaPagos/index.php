<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../class/classEnv.php';

$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    try {
        $dotenv = new DotEnv($envPath);
        $envVars = $dotenv->listVars();
    } catch (Exception $e) {
        $envVars = $_ENV;
    }
} else {
    $envVars = $_ENV;
}

$sucursalesConfig = file_exists(__DIR__ . '/config/gocuotas_sucursales.php') 
    ? require __DIR__ . '/config/gocuotas_sucursales.php' 
    : [];

$desde      = $_GET['desde']      ?? date('Y-m-01');
$hasta      = $_GET['hasta']      ?? date('Y-m-d');
$procesador = $_GET['procesador'] ?? 'todos';
$estado     = $_GET['estado']     ?? 'todos';
$sucursalSel= $_GET['sucursal']   ?? 'todos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Medios de Pago | Administración XL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="menu.php">
                <i class="fa-solid fa-calculator-combined text-info me-2 fs-4"></i>
                <span class="fw-bold">Control de Medios de Pago</span>
            </a>
            <div class="d-flex text-light align-items-center small gap-3">
                <a href="menu.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-grid-2 me-1"></i> Menú Principal
                </a>
                <span><i class="fa-regular fa-clock me-1"></i> Actualizado hoy</span>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">

        <!-- Filtros superiores -->
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
                    <div class="col-12 col-md-3">
                        <label for="sucursal" class="form-label fw-semibold text-secondary small">Local / Sucursal</label>
                        <select id="sucursal" name="sucursal" class="form-select form-select-sm">
                            <option value="todos" <?php echo $sucursalSel==='todos'?'selected':''; ?>>Todos los locales (20 Sucursales)</option>
                            <?php foreach ($sucursalesConfig as $key => $conf): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $sucursalSel===$key?'selected':''; ?>>
                                    <?php echo htmlspecialchars($conf['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="procesador" class="form-label fw-semibold text-secondary small">Procesadora</label>
                        <select id="procesador" name="procesador" class="form-select form-select-sm">
                            <option value="todos"       <?php echo $procesador==='todos'       ? 'selected':''; ?>>Todas las pasarelas</option>
                            <option value="gocuotas"    <?php echo $procesador==='gocuotas'    ? 'selected':''; ?>>GoCuotas</option>
                            <option value="mercadopago" <?php echo $procesador==='mercadopago' ? 'selected':''; ?>>MercadoPago</option>
                            <option value="getnet"      <?php echo $procesador==='getnet'      ? 'selected':''; ?>>Getnet</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="estado" class="form-label fw-semibold text-secondary small">Estado</label>
                        <select id="estado" name="estado" class="form-select form-select-sm">
                            <option value="todos"      <?php echo ($estado==='todos' || empty($estado)) ? 'selected':''; ?>>Todos los estados</option>
                            <option value="ok"         <?php echo $estado==='ok' ? 'selected':''; ?>>OK / Conciliado</option>
                            <option value="diferencia" <?php echo $estado==='diferencia' ? 'selected':''; ?>>Con Diferencia</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pestañas de Navegación -->
        <ul class="nav nav-tabs mb-4 gap-2 border-bottom-0" id="agenda-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold px-4 border rounded-3 shadow-sm" id="control-diario-tab"
                        data-bs-toggle="tab" data-bs-target="#control-diario-pane" type="button" role="tab" aria-selected="true">
                    <i class="fa-solid fa-calendar-check me-2 text-success"></i>Control Diario por Local
                </button>
            </li>
            <li class="nav-item d-none" role="presentation">
                <button class="nav-link fw-bold px-4 border rounded-3 shadow-sm" id="tablero-tab"
                        data-bs-toggle="tab" data-bs-target="#tablero-pane" type="button" role="tab" aria-selected="false">
                    <i class="fa-solid fa-gauge-high me-2 text-primary"></i>Consola de Transacciones
                </button>
            </li>
            <li class="nav-item d-none" role="presentation">
                <button class="nav-link fw-bold px-4 border rounded-3 shadow-sm" id="liquidaciones-tab"
                        data-bs-toggle="tab" data-bs-target="#liquidaciones-pane" type="button" role="tab" aria-selected="false">
                    <i class="fa-solid fa-file-invoice-dollar me-2 text-warning"></i>Liquidaciones
                </button>
            </li>
        </ul>

        <div class="tab-content" id="agenda-tab-content">

            <!-- Pestaña Consola de Transacciones -->
            <div class="tab-pane fade" id="tablero-pane" role="tabpanel" aria-labelledby="tablero-tab" tabindex="0">
                
                <!-- Métricas KPI principales -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Venta Bruta Total</span>
                                <div class="metric-icon bg-light-primary text-primary"><i class="fa-solid fa-store"></i></div>
                            </div>
                            <h3 class="fw-bold mb-1" id="metric-gross-total">$0,00</h3>
                            <p class="text-muted small mb-0"><span id="metric-trans-count">0</span> transacciones registradas</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Comisiones & Retenciones</span>
                                <div class="metric-icon bg-light-danger text-danger"><i class="fa-solid fa-percent"></i></div>
                            </div>
                            <h3 class="fw-bold mb-1 text-danger" id="metric-fees-total">$0,00</h3>
                            <p class="text-muted small mb-0">Costo promedio: <span id="metric-fee-percentage">0%</span></p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Neto Acreditado</span>
                                <div class="metric-icon bg-light-success text-success"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                            </div>
                            <h3 class="fw-bold mb-1 text-success" id="metric-net-total">$0,00</h3>
                            <p class="text-muted small mb-0">Fondo real depositado</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Ticket Promedio</span>
                                <div class="metric-icon bg-light-warning text-warning"><i class="fa-solid fa-receipt"></i></div>
                            </div>
                            <h3 class="fw-bold mb-1 text-warning" id="metric-ticket-average">$0,00</h3>
                            <p class="text-muted small mb-0">Promedio por compra</p>
                        </div>
                    </div>
                </div>

                <!-- Resumen por Local (Sucursal) -->
                <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-city text-primary me-2"></i>Resumen por Local (Ventas GoCuotas / Procesadoras)
                        </h5>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-bold" id="branches-count-badge">
                            20 Locales Monitoreados
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="branches-summary-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Local / Sucursal</th>
                                        <th class="text-center">Cant. Transacciones</th>
                                        <th class="text-end">Venta Bruta</th>
                                        <th class="text-end">Comisiones/Retención</th>
                                        <th class="text-end">Neto Acreditado</th>
                                        <th class="text-center">% Participación</th>
                                    </tr>
                                </thead>
                                <tbody id="branches-summary-tbody">
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                            Cargando resumen por local...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Transacciones en Tiempo Real -->
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-list-ul text-secondary me-2"></i>Consola de Transacciones en Tiempo Real
                            </h5>
                            <span class="text-muted small">Detalle individual de ventas procesadas por local</span>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" id="quick-search" class="form-control form-control-sm" placeholder="Buscar ID, local, cliente..." style="width: 220px;">
                            <button class="btn btn-sm btn-outline-secondary" id="export-excel-btn">
                                <i class="fa-regular fa-file-excel me-1"></i> Exportar XLS
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="payments-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Transacción</th>
                                        <th>Fecha y Hora</th>
                                        <th>Pasarela</th>
                                        <th>Local / Sucursal</th>
                                        <th>Cliente / Concepto</th>
                                        <th>Cuotas</th>
                                        <th class="text-end">Bruto</th>
                                        <th class="text-end">Comisión</th>
                                        <th class="text-end">Neto</th>
                                        <th>Acreditación</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="payments-tbody">
                                    <tr>
                                        <td colspan="12" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                            Consultando transacciones...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div><!-- Fin Consola de Transacciones -->

            <!-- Pestaña 1: Control Diario por Local (Activa por Defecto) -->
            <div class="tab-pane fade show active" id="control-diario-pane" role="tabpanel" aria-labelledby="control-diario-tab" tabindex="0">

                <!-- Indicadores de Resumen de Conciliación -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-success">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Conciliados (OK)</span>
                                    <h3 class="fw-bold mb-0 text-success" id="reconcile-count-ok">0</h3>
                                </div>
                                <div class="rounded-circle bg-success-subtle p-3 text-success">
                                    <i class="fa-solid fa-circle-check fa-lg"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-danger">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Con Diferencia</span>
                                    <h3 class="fw-bold mb-0 text-danger" id="reconcile-count-diff">0</h3>
                                </div>
                                <div class="rounded-circle bg-danger-subtle p-3 text-danger">
                                    <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-primary">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Total Días / Locales</span>
                                    <h3 class="fw-bold mb-0 text-primary" id="reconcile-count-total">0</h3>
                                </div>
                                <div class="rounded-circle bg-primary-subtle p-3 text-primary">
                                    <i class="fa-solid fa-list-check fa-lg"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                    <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="card-title fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-calendar-check text-success me-2"></i>Conciliación Diaria por Local
                            </h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 ms-2" id="badge-ok-summary">
                                <i class="fa-solid fa-circle-check me-1"></i> OK: 0
                            </span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" id="badge-diff-summary">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Diferencia: 0
                            </span>
                        </div>
                        <button class="btn btn-sm btn-outline-success" id="export-excel-reconcile-btn">
                            <i class="fa-regular fa-file-excel me-1"></i> Exportar Planilla Conciliada
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Local / Sucursal</th>
                                        <th>Procesador</th>
                                        <th class="text-end">Ventas API</th>
                                        <th class="text-end" style="width: 200px;">$ CENTRAL</th>
                                        <th class="text-end">Diferencia</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="reconcile-tbody">
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            Cargando conciliación...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestaña 3: Liquidaciones GoCuotas -->
            <div class="tab-pane fade" id="liquidaciones-pane" role="tabpanel" aria-labelledby="liquidaciones-tab" tabindex="0">

                <div class="row g-4 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Total Bruto Liquidado</span>
                                <div class="metric-icon bg-light-primary text-primary"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                            </div>
                            <h3 class="fw-bold mb-1" id="liq-metric-gross">$0,00</h3>
                            <p class="text-muted small mb-0"><span id="liq-metric-count">0</span> liquidaciones en el periodo</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Retenciones GoCuotas</span>
                                <div class="metric-icon bg-light-danger text-danger"><i class="fa-solid fa-percent"></i></div>
                            </div>
                            <h3 class="fw-bold mb-1 text-danger" id="liq-metric-retained">$0,00</h3>
                            <p class="text-muted small mb-0">Comisiones y retenciones descontadas</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Neto Acreditado</span>
                                <div class="metric-icon bg-light-success text-success"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                            </div>
                            <h3 class="fw-bold mb-1 text-success" id="liq-metric-net">$0,00</h3>
                            <p class="text-muted small mb-0">Dinero real depositado en cuenta</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card card-metric border-0 shadow-sm rounded-3 bg-white p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Próxima Acreditación</span>
                                <div class="metric-icon bg-light-warning text-warning"><i class="fa-solid fa-calendar-check"></i></div>
                            </div>
                            <h3 class="fw-bold mb-1 fs-6" id="liq-metric-next-date">-</h3>
                            <p class="text-muted small mb-0" id="liq-metric-next-amount">Fecha de vencimiento de expensa</p>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-list-check text-secondary me-2"></i>Detalle de Liquidaciones GoCuotas
                            </h5>
                            <span class="text-muted small">Pagos de expensas acreditados por GoCuotas al comercio en el periodo seleccionado</span>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" id="liq-quick-search" class="form-control form-control-sm" placeholder="Buscar ID, método..." style="width: 220px;">
                            <button class="btn btn-sm btn-outline-secondary" id="liq-export-btn">
                                <i class="fa-regular fa-file-excel me-1"></i> Exportar
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="liq-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Liquidación</th>
                                        <th>Fecha Pago</th>
                                        <th>Método</th>
                                        <th class="text-end">Bruto</th>
                                        <th class="text-end">Retención</th>
                                        <th class="text-end">Neto Acreditado</th>
                                        <th>Vencimiento / Acreditación</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Detalle</th>
                                    </tr>
                                </thead>
                                <tbody id="liq-tbody">
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-warning me-2" role="status"></div>
                                            Cargando liquidaciones de GoCuotas...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div><!-- Fin Liquidaciones -->

            <!-- Elementos auxiliares ocultos mantenidos por compatibilidad de JS -->
            <div class="d-none" aria-hidden="true">
                <span id="dist-gocuotas-percentage"></span>
                <div id="dist-gocuotas-bar"></div>
                <span id="dist-mercadopago-percentage"></span>
                <div id="dist-mercadopago-bar"></div>
                <span id="dist-getnet-percentage"></span>
                <div id="dist-getnet-bar"></div>
                <span id="forecast-immediate"></span>
                <span id="forecast-short"></span>
                <span id="forecast-scheduled"></span>
                <span id="kpi-reconcile-expected"></span>
                <span id="kpi-reconcile-declared"></span>
                <span id="kpi-reconcile-diff"></span>
                <span id="kpi-reconcile-diff-icon"></span>
                <span id="kpi-reconcile-diff-status"></span>
                <span id="kpi-reconcile-status"></span>
                <span id="kpi-reconcile-count"></span>
                <span id="kpi-reconcile-status-icon"></span>
            </div>

        </div><!-- Fin tab-content -->

    </div><!-- Fin container-fluid -->

    <div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" id="details-modal-content"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
