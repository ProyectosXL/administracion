<?php
// agendaPagos/controlPromocionesBanco.php - Módulo de Control Promociones Banco XL
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../class/classEnv.php';

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$banco = $_GET['banco'] ?? 'todos';
$sucursal = $_GET['sucursal'] ?? 'todos';
$estado = $_GET['estado'] ?? 'todos';

$sucursalesConfig = file_exists(__DIR__ . '/config/gocuotas_sucursales.php') 
    ? require __DIR__ . '/config/gocuotas_sucursales.php' 
    : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Promociones Banco | Administración XL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
        }

        .navbar-banco {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 3px solid #d97706;
        }

        .card-metric-banco {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }

        .card-metric-banco:hover {
            transform: translateY(-3px);
        }

        .badge-banco {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Navegación Superior -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-banco shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="menu.php">
                <i class="fa-solid fa-building-columns text-warning me-2 fs-4"></i>
                <span class="fw-bold">Control Promociones Banco</span>
            </a>
            <div class="d-flex text-light align-items-center small gap-3">
                <a href="menu.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-grid-2 me-1"></i> Menú Principal
                </a>
                <span><i class="fa-regular fa-clock me-1"></i> Auditoría Bancaria ($ LOCAL)</span>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">

        <!-- Filtros Superiores -->
        <div class="card border-0 shadow-sm mb-4 rounded-3 bg-white">
            <div class="card-body">
                <form id="promo-filter-form" method="GET" class="row g-3 align-items-end">
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
                            <option value="todos">Todos los locales (<?php echo count($sucursalesConfig); ?> Sucursales)</option>
                            <?php foreach ($sucursalesConfig as $key => $conf): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $sucursal === $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($conf['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="banco" class="form-label fw-semibold text-secondary small">Entidad / Promo</label>
                        <select id="banco" name="banco" class="form-select form-select-sm">
                            <option value="todos" <?php echo $banco==='todos'?'selected':''; ?>>Todos los Bancos</option>
                            <option value="galicia" <?php echo $banco==='galicia'?'selected':''; ?>>Banco Galicia</option>
                            <option value="provincia" <?php echo $banco==='provincia'?'selected':''; ?>>Banco Provincia / Cuenta DNI</option>
                            <option value="santander" <?php echo $banco==='santander'?'selected':''; ?>>Banco Santander</option>
                            <option value="macro" <?php echo $banco==='macro'?'selected':''; ?>>Banco Macro</option>
                            <option value="bbva" <?php echo $banco==='bbva'?'selected':''; ?>>BBVA Francés</option>
                            <option value="naranja" <?php echo $banco==='naranja'?'selected':''; ?>>Naranja X</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="estado" class="form-label fw-semibold text-secondary small">Estado</label>
                        <select id="estado" name="estado" class="form-select form-select-sm">
                            <option value="todos"      <?php echo ($estado==='todos' || empty($estado)) ? 'selected':''; ?>>Todos los estados</option>
                            <option value="ok"         <?php echo $estado==='ok' ? 'selected':''; ?>>OK / Conciliado</option>
                            <option value="revisado"   <?php echo $estado==='revisado' ? 'selected':''; ?>>Revisado (Manual)</option>
                            <option value="diferencia" <?php echo $estado==='diferencia' ? 'selected':''; ?>>Con Diferencia</option>
                            <option value="pendiente"  <?php echo $estado==='pendiente' ? 'selected':''; ?>>Pendiente</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-warning btn-sm w-100 text-dark fw-bold">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Consultar Promos
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Indicadores KPI Promociones Bancarias -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card card-metric-banco p-3 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1">$ CENTRAL</span>
                            <h3 class="fw-bold mb-0 text-dark" id="kpi-gross-promo">$0,00</h3>
                        </div>
                        <div class="rounded-circle bg-primary-subtle p-3 text-primary">
                            <i class="fa-solid fa-calculator fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-metric-banco p-3 border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1">$ LOCAL (V_creditospromos)</span>
                            <h3 class="fw-bold mb-0 text-info" id="kpi-bank-share">$0,00</h3>
                        </div>
                        <div class="rounded-circle bg-info-subtle p-3 text-info">
                            <i class="fa-solid fa-database fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-metric-banco p-3 border-start border-4 border-danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Diferencia Acumulada</span>
                            <h3 class="fw-bold mb-0 text-danger" id="kpi-merchant-cost">$0,00</h3>
                        </div>
                        <div class="rounded-circle bg-danger-subtle p-3 text-danger">
                            <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-metric-banco p-3 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Días Conciliados OK</span>
                            <h3 class="fw-bold mb-0 text-success" id="kpi-count-promo">0 / 0</h3>
                        </div>
                        <div class="rounded-circle bg-success-subtle p-3 text-success">
                            <i class="fa-solid fa-circle-check fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Control de Promociones Bancarias -->
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-list-check text-warning me-2"></i>Auditoría de Promociones Bancarias ($ CENTRAL vs $ LOCAL)
                </h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="alert('Exportando planilla auditora de promociones bancarias...');">
                    <i class="fa-regular fa-file-excel me-1"></i> Exportar XLS Auditoría
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Venta</th>
                                <th>Local / Sucursal</th>
                                <th>Promoción / Entidad Bancaria</th>
                                <th class="text-end">$ CENTRAL</th>
                                <th class="text-end">$ LOCAL</th>
                                <th class="text-end">Diferencia</th>
                                <th class="text-center">Estado Auditoría</th>
                                <th class="text-center" style="min-width: 170px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="promo-tbody">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="spinner-border spinner-border-sm text-warning me-2" role="status"></div>
                                    Consultando bases locales y vistas V_creditospromosbancarias...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Detalle de Comprobantes y Diferencias -->
    <div class="modal fade" id="modalDetalleComprobantes" tabindex="-1" aria-labelledby="modalDetalleLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalDetalleLabel">
                        <i class="fa-solid fa-magnifying-glass-chart text-warning me-2"></i>Auditoría de Comprobantes: <span id="modal-sucursal-nombre" class="text-warning"></span> (<span id="modal-fecha-str"></span>)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <!-- Resumen KPIs del día -->
                    <div class="row g-3 mb-4" id="modal-kpis-container">
                        <div class="col-md-3">
                            <div class="card p-3 border-0 shadow-sm bg-white border-start border-4 border-info">
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Reintegro Local Activo</span>
                                <h4 class="fw-bold mb-0 text-info" id="m-kpi-local-activo">$0,00</h4>
                                <small class="text-muted" id="m-kpi-local-facturas-cant">0 facturas vigentes</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card p-3 border-0 shadow-sm bg-white border-start border-4 border-warning">
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Reintegro Anulado / Devuelto</span>
                                <h4 class="fw-bold mb-0 text-warning" id="m-kpi-local-anulado">$0,00</h4>
                                <small class="text-muted" id="m-kpi-local-anuladas-cant">Devoluciones / NCs</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card p-3 border-0 shadow-sm bg-white border-start border-4 border-primary">
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Total Central (Cta 250800)</span>
                                <h4 class="fw-bold mb-0 text-primary" id="m-kpi-central">$0,00</h4>
                                <small class="text-muted" id="m-kpi-central-cant">Comprobantes imputados</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card p-3 border-0 shadow-sm bg-white border-start border-4 border-danger">
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Diferencia Neta</span>
                                <h4 class="fw-bold mb-0 text-danger" id="m-kpi-diferencia">$0,00</h4>
                                <small class="text-muted">Desvío de auditoría</small>
                            </div>
                        </div>
                    </div>

                    <!-- Banner de Observaciones / Estado Manual si existe -->
                    <div id="modal-audit-banner" class="alert alert-info d-none shadow-sm mb-4"></div>

                    <!-- Pestañas de detalle -->
                    <ul class="nav nav-pills mb-3 gap-2" id="detailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold" id="tab-invoices-btn" data-bs-toggle="pill" data-bs-target="#tab-invoices" type="button" role="tab">
                                <i class="fa-solid fa-receipt me-1"></i> Facturas y Cupones Local (<span id="tab-count-invoices">0</span>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="tab-ncs-btn" data-bs-toggle="pill" data-bs-target="#tab-ncs" type="button" role="tab">
                                <i class="fa-solid fa-file-invoice-dollar me-1"></i> Notas de Crédito Local (<span id="tab-count-ncs">0</span>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="tab-central-btn" data-bs-toggle="pill" data-bs-target="#tab-central" type="button" role="tab">
                                <i class="fa-solid fa-building-columns me-1"></i> Comprobantes Central (<span id="tab-count-central">0</span>)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content card border-0 shadow-sm p-3 bg-white" id="detailTabsContent">
                        <!-- Tab 1: Facturas Local -->
                        <div class="tab-pane fade show active table-responsive" id="tab-invoices" role="tabpanel">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Comprobante</th>
                                        <th>Hora</th>
                                        <th>Cliente</th>
                                        <th>Cupón</th>
                                        <th>Promoción</th>
                                        <th class="text-end">Venta Tarjeta</th>
                                        <th class="text-center">% Reint.</th>
                                        <th class="text-end">$ Reintegro</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-invoices-tbody"></tbody>
                            </table>
                        </div>

                        <!-- Tab 2: NCs Local -->
                        <div class="tab-pane fade table-responsive" id="tab-ncs" role="tabpanel">
                            <div class="alert alert-light border small text-secondary mb-3">
                                <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                Muestra las Notas de Crédito emitidas en el local con sus renglones de artículos de reintegro (ej. <code>*****VISA RIO</code>, <code>*****VISA PROV</code>) para detectar errores tipográficos o comprobantes de anulación.
                            </div>
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Comprobante NC</th>
                                        <th>Hora</th>
                                        <th>Usuario</th>
                                        <th>Renglón / Artículo</th>
                                        <th class="text-end">Importe Renglón</th>
                                        <th class="text-end">Total NC</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-ncs-tbody"></tbody>
                            </table>
                        </div>

                        <!-- Tab 3: Central -->
                        <div class="tab-pane fade table-responsive" id="tab-central" role="tabpanel">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Comprobante</th>
                                        <th>Cuenta Contable</th>
                                        <th>Cliente</th>
                                        <th class="text-end">Total Comprobante</th>
                                        <th class="text-end">$ Imputado a Promo</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-central-tbody"></tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            function formatMoney(amount) {
                return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(amount || 0);
            }

            let currentRows = [];
            let currentKpis = {};

            function fetchBankPromos() {
                const desde = $('#desde').val();
                const hasta = $('#hasta').val();
                const sucursal = $('#sucursal').val();
                const banco = $('#banco').val();

                $('#promo-tbody').html(`
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-warning me-2" role="status"></div>
                            Consultando bases de cada sucursal (V_creditospromosbancarias)...
                        </td>
                    </tr>
                `);

                $.ajax({
                    url: 'Controller/PaymentController.php',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'get_bank_promos',
                        desde: desde,
                        hasta: hasta,
                        sucursal: sucursal,
                        banco: banco
                    },
                    success: function (res) {
                        if (res.success) {
                            currentRows = res.data.rows || [];
                            currentKpis = res.data.kpis || {};
                            renderPromos();
                        } else {
                            $('#promo-tbody').html(`
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-danger">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> ${res.message}
                                    </td>
                                </tr>
                            `);
                        }
                    },
                    error: function () {
                        $('#promo-tbody').html(`
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-danger">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Error al conectar con las bases de datos de las sucursales.
                                    </td>
                                </tr>
                        `);
                    }
                });
            }

            function renderPromos() {
                const estadoSel = $('#estado').val();
                let filteredRows = currentRows;

                if (estadoSel !== 'todos') {
                    filteredRows = currentRows.filter(p => p.status === estadoSel);
                }

                if (!filteredRows || filteredRows.length === 0) {
                    $('#promo-tbody').html(`
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                No hay operaciones de promociones bancarias registradas para el estado seleccionado.
                            </td>
                        </tr>
                    `);
                    $('#kpi-gross-promo').text(formatMoney(currentKpis.total_sistema || 0));
                    $('#kpi-bank-share').text(formatMoney(currentKpis.total_control || 0));
                    $('#kpi-merchant-cost').text(formatMoney(currentKpis.total_diferencia || 0));
                    $('#kpi-count-promo').text(`${currentKpis.count_ok || 0} / ${currentKpis.count_total || 0}`);
                    return;
                }

                let html = '';
                filteredRows.forEach(p => {
                    const formattedDate = new Date(p.fecha + 'T00:00:00').toLocaleDateString('es-AR', {
                        day: '2-digit', month: '2-digit', year: 'numeric'
                    });

                    let statusBadge = '';
                    if (p.status === 'ok') {
                        const isManual = (p.verificado == 1);
                        statusBadge = `<span class="badge bg-success-subtle text-success badge-banco" title="${p.observaciones || ''}"><i class="fa-solid fa-circle-check me-1"></i>OK / Conciliado${isManual ? ' <small class="text-muted fw-normal">(Manual)</small>' : ''}</span>`;
                    } else if (p.status === 'revisado') {
                        statusBadge = `<span class="badge bg-primary-subtle text-primary badge-banco" title="${p.observaciones || ''}"><i class="fa-solid fa-user-check me-1"></i>Revisado (Manual)</span>`;
                    } else if (p.status === 'pendiente') {
                        statusBadge = '<span class="badge bg-secondary-subtle text-secondary badge-banco"><i class="fa-regular fa-clock me-1"></i>Pendiente</span>';
                    } else {
                        statusBadge = '<span class="badge bg-danger-subtle text-danger badge-banco"><i class="fa-solid fa-triangle-exclamation me-1"></i>Diferencia</span>';
                    }

                    if (p.observaciones) {
                        statusBadge += `<small class="d-block text-muted text-truncate mt-1" style="max-width: 150px;" title="${p.observaciones}"><i class="fa-solid fa-comment-dots text-info me-1"></i>${p.observaciones}</small>`;
                    }

                    const diffVal = p.diferencia;
                    const diffTextClass = Math.abs(diffVal) < 0.01 ? 'text-success' : 'text-danger';

                    html += `
                        <tr>
                            <td class="fw-semibold text-secondary">${formattedDate}</td>
                            <td class="fw-bold text-dark">${p.sucursal}</td>
                            <td><span class="badge bg-light text-dark border fw-semibold">${p.promocion}</span></td>
                            <td class="text-end fw-bold text-dark">${formatMoney(p.monto_sistema)}</td>
                            <td class="text-end fw-bold text-info">${formatMoney(p.monto_control)}</td>
                            <td class="text-end fw-bold ${diffTextClass}">${formatMoney(diffVal)}</td>
                            <td class="text-center">${statusBadge}</td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <button class="btn btn-sm btn-outline-primary btn-ver-detalle px-2 py-1" 
                                            data-fecha="${p.fecha}" 
                                            data-suc="${p.nro_sucursal}" 
                                            data-nomb="${p.sucursal}" 
                                            title="Ver Detalle de Comprobantes y Diferencias">
                                        <i class="fa-solid fa-magnifying-glass-chart me-1"></i>Detalle
                                    </button>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle px-2 py-1" 
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Cambiar Estado de Auditoría">
                                            <i class="fa-solid fa-sliders"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                            <li><h6 class="dropdown-header py-1">Auditoría Manual</h6></li>
                                            <li>
                                                <a class="dropdown-item text-primary btn-cambiar-estado" href="#" 
                                                   data-fecha="${p.fecha}" data-suc="${p.nro_sucursal}" data-estado="revisado">
                                                    <i class="fa-solid fa-user-check me-2"></i>Marcar Revisado
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-success btn-cambiar-estado" href="#" 
                                                   data-fecha="${p.fecha}" data-suc="${p.nro_sucursal}" data-estado="ok">
                                                    <i class="fa-solid fa-check-double me-2"></i>Forzar OK / Conciliado
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <a class="dropdown-item text-secondary btn-cambiar-estado" href="#" 
                                                   data-fecha="${p.fecha}" data-suc="${p.nro_sucursal}" data-estado="auto">
                                                    <i class="fa-solid fa-rotate-left me-2"></i>Restablecer Automático
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                $('#promo-tbody').html(html);
                $('#kpi-gross-promo').text(formatMoney(currentKpis.total_sistema || 0));
                $('#kpi-bank-share').text(formatMoney(currentKpis.total_control || 0));
                $('#kpi-merchant-cost').text(formatMoney(currentKpis.total_diferencia || 0));
                $('#kpi-count-promo').text(`${currentKpis.count_ok || 0} / ${currentKpis.count_total || 0}`);
            }

            // --- APERTURA DE MODAL DETALLE DE COMPROBANTES ---
            $(document).on('click', '.btn-ver-detalle', function(e) {
                e.preventDefault();
                const fecha = $(this).data('fecha');
                const nroSucursal = $(this).data('suc');
                const nombreSucursal = $(this).data('nomb');

                const fechaFormatted = new Date(fecha + 'T00:00:00').toLocaleDateString('es-AR', {
                    day: '2-digit', month: '2-digit', year: 'numeric'
                });

                $('#modal-sucursal-nombre').text(nombreSucursal);
                $('#modal-fecha-str').text(fechaFormatted);

                $('#m-kpi-local-activo').text('$0,00');
                $('#m-kpi-local-anulado').text('$0,00');
                $('#m-kpi-central').text('$0,00');
                $('#m-kpi-diferencia').text('$0,00');

                $('#modal-invoices-tbody').html('<tr><td colspan="9" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Consultando comprobantes en el local...</td></tr>');
                $('#modal-ncs-tbody').html('<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Consultando Notas de Crédito...</td></tr>');
                $('#modal-central-tbody').html('<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Consultando Central...</td></tr>');
                $('#modal-audit-banner').addClass('d-none').html('');

                const modalObj = new bootstrap.Modal(document.getElementById('modalDetalleComprobantes'));
                modalObj.show();

                $.ajax({
                    url: 'Controller/PaymentController.php',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'get_bank_promo_detail',
                        fecha: fecha,
                        nro_sucursal: nroSucursal
                    },
                    success: function(res) {
                        if (!res.success) {
                            $('#modal-invoices-tbody').html(`<tr><td colspan="9" class="text-danger py-3 text-center">${res.message}</td></tr>`);
                            return;
                        }

                        const d = res.data;
                        const tot = d.totales || {};

                        $('#m-kpi-local-activo').text(formatMoney(tot.reintegro_activo_local || 0));
                        $('#m-kpi-local-anulado').text(formatMoney(tot.reintegro_devuelto_local || 0));
                        $('#m-kpi-central').text(formatMoney(tot.total_central_imputado || 0));

                        const diffNet = Math.round(((tot.total_central_imputado || 0) - (tot.reintegro_activo_local || 0)) * 100) / 100;
                        $('#m-kpi-diferencia').text(formatMoney(diffNet));
                        $('#m-kpi-diferencia').removeClass('text-danger text-success').addClass(Math.abs(diffNet) < 0.01 ? 'text-success' : 'text-danger');

                        $('#m-kpi-local-facturas-cant').text(`${(d.local_invoices || []).filter(i => !i.is_canceled).length} facturas vigentes`);
                        $('#m-kpi-central-cant').text(`${(d.central_vouchers || []).length} comprobantes en Cta 250800`);

                        // Banner de auditoria
                        if (d.central_resumen) {
                            const cr = d.central_resumen;
                            if (cr.verificado > 0 || cr.observaciones) {
                                let verifStr = cr.verificado == 1 ? '<span class="badge bg-success me-2">OK / Conciliado Manual</span>' : '<span class="badge bg-primary me-2">Revisado Manual</span>';
                                $('#modal-audit-banner').removeClass('d-none').html(`
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Estado de Auditoría:</strong> ${verifStr}
                                            <span class="ms-2"><strong>Observaciones:</strong> ${cr.observaciones || 'Sin observaciones'}</span>
                                        </div>
                                        <small class="text-muted">Modificado por: <strong>${cr.usuario || 'Auditor'}</strong> (${cr.fecha_modif || ''})</small>
                                    </div>
                                `);
                            }
                        }

                        // 1. Facturas Local
                        const invoices = d.local_invoices || [];
                        $('#tab-count-invoices').text(invoices.length);
                        if (invoices.length === 0) {
                            $('#modal-invoices-tbody').html('<tr><td colspan="9" class="text-center py-4 text-muted">No se registraron facturas con cupones de promoción para esta fecha.</td></tr>');
                        } else {
                            let invHtml = '';
                            invoices.forEach(inv => {
                                const cancelBadge = inv.is_canceled 
                                    ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Anulada / Cancelada</span>'
                                    : '<span class="badge bg-success-subtle text-success border border-success-subtle">Activa</span>';
                                
                                const rowClass = inv.is_canceled ? 'table-danger-subtle opacity-75' : '';

                                invHtml += `
                                    <tr class="${rowClass}">
                                        <td class="fw-bold text-dark">${inv.t_comp} ${inv.n_comp}</td>
                                        <td class="text-secondary small">${inv.hora || '-'}</td>
                                        <td class="small">${inv.cod_cliente || '-'}</td>
                                        <td><span class="badge bg-light text-dark border">${inv.n_cupon || '-'}</span></td>
                                        <td class="small text-truncate" style="max-width: 220px;" title="${inv.promocion}">${inv.promocion}</td>
                                        <td class="text-end fw-semibold">${formatMoney(inv.importe_cupon)}</td>
                                        <td class="text-center">${inv.porc_reintegro}%</td>
                                        <td class="text-end fw-bold text-info">${formatMoney(inv.reintegro)}</td>
                                        <td class="text-center">${cancelBadge}</td>
                                    </tr>
                                `;
                            });
                            $('#modal-invoices-tbody').html(invHtml);
                        }

                        // 2. NCs Local
                        const ncs = d.local_credit_notes || [];
                        $('#tab-count-ncs').text(ncs.length);
                        if (ncs.length === 0) {
                            $('#modal-ncs-tbody').html('<tr><td colspan="6" class="text-center py-4 text-muted">No se emitieron Notas de Crédito en el local para esta fecha.</td></tr>');
                        } else {
                            let ncHtml = '';
                            ncs.forEach(nc => {
                                const isPromoArt = nc.articulo.startsWith('*****');
                                const artBadge = isPromoArt 
                                    ? `<span class="badge bg-warning-subtle text-dark border border-warning">${nc.articulo}</span>`
                                    : `<span class="text-secondary">${nc.articulo}</span>`;

                                ncHtml += `
                                    <tr>
                                        <td class="fw-bold text-dark">${nc.t_comp} ${nc.n_comp}</td>
                                        <td class="text-secondary small">${nc.hora || '-'}</td>
                                        <td class="small">${nc.usuario || '-'}</td>
                                        <td>${artBadge}</td>
                                        <td class="text-end fw-bold text-danger">${formatMoney(nc.importe_renglon)}</td>
                                        <td class="text-end text-muted small">${formatMoney(nc.importe_nc)}</td>
                                    </tr>
                                `;
                            });
                            $('#modal-ncs-tbody').html(ncHtml);
                        }

                        // 3. Central Vouchers
                        const central = d.central_vouchers || [];
                        $('#tab-count-central').text(central.length);
                        if (central.length === 0) {
                            $('#modal-central-tbody').html('<tr><td colspan="5" class="text-center py-4 text-muted">No se encontraron comprobantes imputados a la cuenta 250800 en Central.</td></tr>');
                        } else {
                            let centHtml = '';
                            central.forEach(c => {
                                centHtml += `
                                    <tr>
                                        <td class="fw-bold text-dark">${c.t_comp} ${c.n_comp}</td>
                                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">${c.cuenta}</span></td>
                                        <td class="small">${c.cliente || '-'}</td>
                                        <td class="text-end text-muted">${formatMoney(c.importe_total)}</td>
                                        <td class="text-end fw-bold text-primary">${formatMoney(c.monto_imputado)}</td>
                                    </tr>
                                `;
                            });
                            $('#modal-central-tbody').html(centHtml);
                        }
                    },
                    error: function() {
                        $('#modal-invoices-tbody').html('<tr><td colspan="9" class="text-danger py-3 text-center">Error de comunicación con el servidor al cargar detalle.</td></tr>');
                    }
                });
            });

            // --- GESTIÓN DE ESTADOS MANUALES ---
            $(document).on('click', '.btn-cambiar-estado', function(e) {
                e.preventDefault();
                const fecha = $(this).data('fecha');
                const nroSucursal = $(this).data('suc');
                const nuevoEstado = $(this).data('estado');

                let title = '';
                let confirmText = '';
                let confirmColor = '#d33';

                if (nuevoEstado === 'ok') {
                    title = '¿Forzar estado OK / Conciliado?';
                    confirmText = 'Sí, marcar como OK';
                    confirmColor = '#198754';
                } else if (nuevoEstado === 'revisado') {
                    title = '¿Marcar como Revisado (Manual)?';
                    confirmText = 'Sí, marcar Revisado';
                    confirmColor = '#0d6efd';
                } else {
                    title = '¿Restablecer cálculo automático?';
                    confirmText = 'Sí, restablecer';
                    confirmColor = '#6c757d';
                }

                Swal.fire({
                    title: title,
                    text: 'Puedes ingresar una observación o motivo de la auditoría (opcional):',
                    input: 'textarea',
                    inputPlaceholder: 'Ej: Diferencia revisada y justificada por anulación de cupón / error de tipeo en caja...',
                    showCancelButton: true,
                    confirmButtonColor: confirmColor,
                    cancelButtonColor: '#adb5bd',
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const observacion = result.value || '';
                        $.ajax({
                            url: 'Controller/PaymentController.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'update_promo_status',
                                fecha: fecha,
                                nro_sucursal: nroSucursal,
                                estado: nuevoEstado,
                                observacion: observacion
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Actualizado',
                                        text: res.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                    fetchBankPromos();
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'No se pudo comunicar con el servidor.', 'error');
                            }
                        });
                    }
                });
            });

            $('#promo-filter-form').on('submit', function(e) {
                e.preventDefault();
                fetchBankPromos();
            });

            $('#banco, #sucursal').on('change', function() {
                fetchBankPromos();
            });

            $('#estado').on('change', function() {
                renderPromos();
            });

            fetchBankPromos();
        });
    </script>
</body>
</html>
