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
                            </tr>
                        </thead>
                        <tbody id="promo-tbody">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
                        <td colspan="7" class="text-center py-5 text-muted">
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
                                    <td colspan="7" class="text-center py-4 text-danger">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> ${res.message}
                                    </td>
                                </tr>
                            `);
                        }
                    },
                    error: function () {
                        $('#promo-tbody').html(`
                            <tr>
                                <td colspan="7" class="text-center py-4 text-danger">
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
                            <td colspan="7" class="text-center py-5 text-muted">
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
                        statusBadge = '<span class="badge bg-success-subtle text-success badge-banco"><i class="fa-solid fa-circle-check me-1"></i>OK / Conciliado</span>';
                    } else if (p.status === 'pendiente') {
                        statusBadge = '<span class="badge bg-secondary-subtle text-secondary badge-banco"><i class="fa-regular fa-clock me-1"></i>Pendiente</span>';
                    } else {
                        statusBadge = '<span class="badge bg-danger-subtle text-danger badge-banco"><i class="fa-solid fa-triangle-exclamation me-1"></i>Diferencia</span>';
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
                        </tr>
                    `;
                });

                $('#promo-tbody').html(html);
                $('#kpi-gross-promo').text(formatMoney(currentKpis.total_sistema || 0));
                $('#kpi-bank-share').text(formatMoney(currentKpis.total_control || 0));
                $('#kpi-merchant-cost').text(formatMoney(currentKpis.total_diferencia || 0));
                $('#kpi-count-promo').text(`${currentKpis.count_ok || 0} / ${currentKpis.count_total || 0}`);
            }

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
