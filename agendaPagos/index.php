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

$desde      = $_GET['desde']      ?? date('Y-m-d');
$hasta      = $_GET['hasta']      ?? date('Y-m-d');
$procesador = $_GET['procesador'] ?? 'todos';
$estado     = $_GET['estado']     ?? 'todos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Medios de Pago | Administracion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="fa-solid fa-calculator-combined text-info me-2 fs-4"></i>
                <span class="fw-bold">Control de Medios de Pago</span>
            </a>
            <div class="d-flex text-light align-items-center small">
                <span><i class="fa-regular fa-clock me-1"></i> Actualizado hoy</span>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">

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
                            <option value="todos"       <?php echo $procesador==='todos'       ? 'selected':''; ?>>Todas las pasarelas</option>
                            <option value="gocuotas"    <?php echo $procesador==='gocuotas'    ? 'selected':''; ?>>GoCuotas</option>
                            <option value="mercadopago" <?php echo $procesador==='mercadopago' ? 'selected':''; ?>>MercadoPago</option>
                            <option value="getnet"      <?php echo $procesador==='getnet'      ? 'selected':''; ?>>Getnet</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="estado" class="form-label fw-semibold text-secondary small">Estado Cobro</label>
                        <select id="estado" name="estado" class="form-select form-select-sm">
                            <option value="todos"    <?php echo $estado==='todos'    ? 'selected':''; ?>>Todos los estados</option>
                            <option value="approved" <?php echo $estado==='approved' ? 'selected':''; ?>>Aprobado</option>
                            <option value="pending"  <?php echo $estado==='pending'  ? 'selected':''; ?>>Pendiente / En Proceso</option>
                            <option value="rejected" <?php echo $estado==='rejected' ? 'selected':''; ?>>Rechazado</option>
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

        <ul class="nav nav-tabs mb-4 gap-2 border-bottom-0" id="agenda-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4 border rounded-3 shadow-sm" id="tablero-tab"
                        data-bs-toggle="tab" data-bs-target="#tablero-pane" type="button" role="tab" aria-selected="false">
                    <i class="fa-solid fa-gauge-high me-2 text-primary"></i>Consola de Transacciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4 border rounded-3 shadow-sm" id="control-diario-tab"
                        data-bs-toggle="tab" data-bs-target="#control-diario-pane" type="button" role="tab" aria-selected="false">
                    <i class="fa-solid fa-calendar-check me-2 text-success"></i>Control Diario por Local
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold px-4 border rounded-3 shadow-sm" id="liquidaciones-tab"
                        data-bs-toggle="tab" data-bs-target="#liquidaciones-pane" type="button" role="tab" aria-selected="true">
                    <i class="fa-solid fa-file-invoice-dollar me-2 text-warning"></i>Liquidaciones
                </button>
            </li>
        </ul>

        <div class="tab-content" id="agenda-tab-content">

            <!-- Pestaña 1: Consola de Transacciones -->
            <div class="tab-pane fade" id="tablero-pane" role="tabpanel" aria-labelledby="tablero-tab" tabindex="0">
                <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                    <i class="fa-solid fa-gauge-high fa-3x mb-3 text-primary opacity-50"></i>
                    <h5 class="fw-bold text-secondary">Consola de Transacciones</h5>
                    <p class="text-muted small">Esta seccion esta disponible para conectar con la fuente de datos de transacciones en tiempo real.</p>
                </div>
            </div>

            <!-- Pestaña 2: Control Diario por Local -->
            <div class="tab-pane fade" id="control-diario-pane" role="tabpanel" aria-labelledby="control-diario-tab" tabindex="0">
                <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                    <i class="fa-solid fa-calendar-check fa-3x mb-3 text-success opacity-50"></i>
                    <h5 class="fw-bold text-secondary">Control Diario por Local</h5>
                    <p class="text-muted small">Esta seccion esta disponible para la conciliacion diaria entre ventas registradas en locales y las procesadoras de pago.</p>
                </div>
            </div>

            <!-- Pestaña 3: Liquidaciones GoCuotas (activa por defecto) -->
            <div class="tab-pane fade show active" id="liquidaciones-pane" role="tabpanel" aria-labelledby="liquidaciones-tab" tabindex="0">

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
                                <span class="text-uppercase tracking-wider small fw-semibold text-muted">Proxima Acreditacion</span>
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
                            <input type="text" id="liq-quick-search" class="form-control form-control-sm" placeholder="Buscar ID, metodo..." style="width: 220px;">
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
                                        <th>ID Liquidacion</th>
                                        <th>Fecha Pago</th>
                                        <th>Metodo</th>
                                        <th class="text-end">Bruto</th>
                                        <th class="text-end">Retencion</th>
                                        <th class="text-end">Neto Acreditado</th>
                                        <th>Vencimiento / Acreditacion</th>
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

            <!-- IDs ocultos requeridos por JS heredado -->
            <div class="d-none" aria-hidden="true">
                <span id="metric-gross-total"></span>
                <span id="metric-fees-total"></span>
                <span id="metric-net-total"></span>
                <span id="metric-ticket-average"></span>
                <span id="metric-trans-count"></span>
                <span id="metric-fee-percentage"></span>
                <span id="dist-gocuotas-percentage"></span>
                <div id="dist-gocuotas-bar"></div>
                <span id="dist-mercadopago-percentage"></span>
                <div id="dist-mercadopago-bar"></div>
                <span id="dist-getnet-percentage"></span>
                <div id="dist-getnet-bar"></div>
                <span id="forecast-immediate"></span>
                <span id="forecast-short"></span>
                <span id="forecast-scheduled"></span>
                <table><tbody id="payments-tbody"></tbody></table>
                <table><tbody id="reconcile-tbody"></tbody></table>
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
