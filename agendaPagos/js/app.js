// js/app.js - Lógica dinámica del frontend de la Agenda de Pagos
$(document).ready(function () {
    // Referencias a elementos
    const $tbody = $('#payments-tbody');
    const $branchesSummaryTbody = $('#branches-summary-tbody');
    const $branchesCountBadge = $('#branches-count-badge');
    const $reconcileTbody = $('#reconcile-tbody');

    const $grossMetric = $('#metric-gross-total');
    const $feesMetric = $('#metric-fees-total');
    const $netMetric = $('#metric-net-total');
    const $ticketMetric = $('#metric-ticket-average');
    const $transCount = $('#metric-trans-count');
    const $feePercentage = $('#metric-fee-percentage');

    let currentPayments = [];
    let currentPosSales = {};

    // Formatear monedas
    function formatMoney(amount) {
        return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(amount || 0);
    }

    // Cargar transacciones de la API y Ventas POS del Sistema
    function fetchPayments() {
        $tbody.html(`
            <tr>
                <td colspan="12" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Buscando transacciones en sucursales...
                </td>
            </tr>
        `);

        $branchesSummaryTbody.html(`
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Calculando resumen por local...
                </td>
            </tr>
        `);

        if ($reconcileTbody.length) {
            $reconcileTbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                        Calculando conciliación diaria...
                    </td>
                </tr>
            `);
        }

        const desde = $('#desde').val();
        const hasta = $('#hasta').val();
        const sucursal = $('#sucursal').val();
        const procesador = $('#procesador').val();
        const estado = $('#estado').val();

        $.ajax({
            url: 'Controller/PaymentController.php',
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'get_payments',
                desde: desde,
                hasta: hasta,
                sucursal: sucursal,
                procesador: procesador,
                estado: estado
            },
            success: function (res) {
                if (res.success) {
                    currentPayments = res.data.payments || [];
                    currentPosSales = res.data.pos_sales || {};
                    filterAndRender();
                } else {
                    const errHtml = `<tr><td colspan="12" class="text-center py-4 text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> ${res.message}</td></tr>`;
                    $tbody.html(errHtml);
                    $branchesSummaryTbody.html(errHtml);
                    if ($reconcileTbody.length) $reconcileTbody.html(`<tr><td colspan="8" class="text-center py-4 text-danger">${res.message}</td></tr>`);
                }
            },
            error: function () {
                const errHtml = `<tr><td colspan="12" class="text-center py-4 text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Error al conectar con el servidor.</td></tr>`;
                $tbody.html(errHtml);
                $branchesSummaryTbody.html(errHtml);
                if ($reconcileTbody.length) $reconcileTbody.html(`<tr><td colspan="8" class="text-center py-4 text-danger">Error al conectar con el servidor.</td></tr>`);
            }
        });
    }

    // Renderizar resumen por local
    function renderBranchesSummary(payments) {
        if (!payments || payments.length === 0) {
            $branchesSummaryTbody.html(`
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        No hay ventas registradas por local para los filtros seleccionados.
                    </td>
                </tr>
            `);
            $branchesCountBadge.text('0 Locales con Ventas');
            return;
        }

        const groups = {};
        let totalGrossAll = 0;

        payments.forEach(p => {
            const suc = p.sucursal || 'General';
            if (!groups[suc]) {
                groups[suc] = {
                    sucursal: suc,
                    count: 0,
                    gross: 0,
                    fee: 0,
                    net: 0
                };
            }
            groups[suc].count++;
            groups[suc].gross += p.gross_amount || 0;
            groups[suc].fee += p.fee_amount || 0;
            groups[suc].net += p.net_amount || 0;
            totalGrossAll += p.gross_amount || 0;
        });

        const branchKeys = Object.keys(groups);
        branchKeys.sort((a, b) => groups[b].gross - groups[a].gross);

        let html = '';
        branchKeys.forEach(key => {
            const b = groups[key];
            const partPct = totalGrossAll > 0 ? ((b.gross / totalGrossAll) * 100).toFixed(1) : '0';

            html += `
                <tr>
                    <td class="fw-bold text-dark">
                        <i class="fa-solid fa-store me-2 text-primary opacity-75"></i>${b.sucursal}
                    </td>
                    <td class="text-center fw-semibold">${b.count}</td>
                    <td class="text-end fw-bold text-dark">${formatMoney(b.gross)}</td>
                    <td class="text-end text-danger">${formatMoney(b.fee)}</td>
                    <td class="text-end text-success fw-bold">${formatMoney(b.net)}</td>
                    <td class="text-center">
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <div class="progress flex-grow-1" style="height: 6px; max-width: 80px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: ${partPct}%"></div>
                            </div>
                            <span class="small fw-semibold text-secondary" style="min-width: 40px;">${partPct}%</span>
                        </div>
                    </td>
                </tr>
            `;
        });

        $branchesSummaryTbody.html(html);
        $branchesCountBadge.text(`${branchKeys.length} Locales Activos`);
    }

    // Renderizar la tabla de cobros individuales
    function renderTable(payments) {
        if (!payments || payments.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="12" class="text-center py-4 text-muted">
                        No se encontraron transacciones para los filtros seleccionados.
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        payments.forEach(p => {
            let providerBadge = '';
            if (p.provider === 'gocuotas') {
                providerBadge = '<span class="badge bg-pink text-white" style="background-color: #ec4899;"><i class="fa-solid fa-credit-card me-1"></i>GoCuotas</span>';
            } else if (p.provider === 'mercadopago') {
                providerBadge = '<span class="badge bg-info text-dark"><i class="fa-solid fa-wallet me-1"></i>MercadoPago</span>';
            } else if (p.provider === 'getnet') {
                providerBadge = '<span class="badge bg-secondary"><i class="fa-solid fa-building-columns me-1"></i>Getnet</span>';
            }

            let statusBadge = '';
            if (p.status === 'approved') {
                statusBadge = '<span class="badge bg-success-subtle text-success"><i class="fa-solid fa-check-circle me-1"></i>Aprobado</span>';
            } else if (p.status === 'pending') {
                statusBadge = '<span class="badge bg-warning-subtle text-warning"><i class="fa-solid fa-clock me-1"></i>En Proceso</span>';
            } else {
                statusBadge = '<span class="badge bg-danger-subtle text-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Rechazado</span>';
            }

            const formattedDate = p.date ? new Date(p.date.replace(' ', 'T')).toLocaleDateString('es-AR', {
                day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
            }) : '—';

            const formattedAcredDate = p.acreditation_date ? new Date(p.acreditation_date + 'T00:00:00').toLocaleDateString('es-AR', {
                day: '2-digit', month: '2-digit', year: 'numeric'
            }) : '—';

            html += `
                <tr data-id="${p.id}">
                    <td class="fw-semibold text-secondary small">${p.id}</td>
                    <td class="small">${formattedDate}</td>
                    <td>${providerBadge}</td>
                    <td><span class="badge bg-light text-dark border fw-bold">${p.sucursal || 'General'}</span></td>
                    <td class="fw-semibold small">${p.client_name}</td>
                    <td><span class="badge bg-light text-dark border">${p.installments || 1} cuotas</span></td>
                    <td class="text-end fw-bold">${formatMoney(p.gross_amount)}</td>
                    <td class="text-end text-danger">${formatMoney(p.fee_amount)}</td>
                    <td class="text-end text-success fw-bold">${formatMoney(p.net_amount)}</td>
                    <td>
                        <div class="small">
                            <span class="d-block fw-semibold text-secondary">${formattedAcredDate}</span>
                            <span class="d-block text-muted" style="font-size:0.72rem;">${p.acreditation_type || ''}</span>
                        </div>
                    </td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-primary view-details-btn" data-id="${p.id}" style="padding: 2px 6px; font-size: 0.75rem;">
                            <i class="fa-solid fa-eye"></i> Detalle
                        </button>
                    </td>
                </tr>
            `;
        });

        $tbody.html(html);
    }

    // Construir la planilla de conciliación diaria por local enlazada automáticamente con la tabla $ SISTEMA
    function buildReconciliationTable(payments, posSalesMap) {
        if (!$reconcileTbody.length) return;
        posSalesMap = posSalesMap || currentPosSales || {};

        const approvedPayments = (payments || []).filter(p => p.status === 'approved');
        const groups = {};

        // Agrupar Ventas API por Fecha, Sucursal y Proveedor
        approvedPayments.forEach(p => {
            const dateOnly = (p.date || '').split(' ')[0] || '2026-08-14';
            const sucursal = p.sucursal || 'General';
            const provider = p.provider || 'gocuotas';
            const key = `${dateOnly}_${sucursal}_${provider}`;

            if (!groups[key]) {
                groups[key] = {
                    date: dateOnly,
                    sucursal: sucursal,
                    provider: provider,
                    expected_amount: 0,
                    transactions: []
                };
            }
            groups[key].expected_amount += (p.gross_amount || 0);
            groups[key].transactions.push(p);
        });

        // Asegurar la inclusión de los registros presentes en $ SISTEMA POS
        Object.keys(posSalesMap).forEach(key => {
            if (!groups[key]) {
                const parts = key.split('_');
                if (parts.length >= 3) {
                    groups[key] = {
                        date: parts[0],
                        sucursal: parts[1],
                        provider: parts[2],
                        expected_amount: 0,
                        transactions: []
                    };
                }
            }
        });

        const keys = Object.keys(groups);
        if (keys.length === 0) {
            $reconcileTbody.html(`
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        No hay ventas aprobadas ni registros POS para conciliar en los filtros seleccionados.
                    </td>
                </tr>
            `);
            return;
        }

        // Filtrado por Estado (todos, ok, diferencia)
        const estadoFilter = $('#estado').val();

        // Ordenar por fecha descendente
        keys.sort((a, b) => groups[b].date.localeCompare(groups[a].date));

        let html = '';
        keys.forEach(key => {
            const g = groups[key];
            const cacheKey = `declared_${key}`;

            const systemPosValue = posSalesMap[key];
            const savedValue = localStorage.getItem(cacheKey);

            let declaredValStr = '';
            if (savedValue !== null && savedValue !== '') {
                declaredValStr = savedValue;
            } else if (systemPosValue !== undefined) {
                declaredValStr = parseFloat(systemPosValue).toFixed(2);
            }

            const expected = g.expected_amount;
            const declared = declaredValStr !== '' ? parseFloat(declaredValStr) : 0;
            const diff = expected - declared;

            const isOk = (declaredValStr !== '' && Math.abs(diff) < 0.01);
            const isDiff = (!isOk);

            if (estadoFilter === 'ok' && !isOk) return;
            if (estadoFilter === 'diferencia' && !isDiff) return;

            let statusBadge = '';
            if (declaredValStr === '') {
                statusBadge = '<span class="badge bg-secondary-subtle text-secondary small py-1 px-2 rounded-pill"><i class="fa-regular fa-clock me-1"></i>Pendiente</span>';
            } else if (Math.abs(diff) < 0.01) {
                statusBadge = '<span class="badge bg-success-subtle text-success small py-1 px-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i>OK / Conciliado</span>';
            } else {
                statusBadge = '<span class="badge bg-danger-subtle text-danger small py-1 px-2 rounded-pill"><i class="fa-solid fa-triangle-exclamation me-1"></i>Diferencia</span>';
            }

            let providerBadge = '';
            if (g.provider === 'gocuotas') {
                providerBadge = '<span class="badge bg-pink text-white" style="background-color: #ec4899;"><i class="fa-solid fa-credit-card me-1"></i>GoCuotas</span>';
            } else if (g.provider === 'mercadopago') {
                providerBadge = '<span class="badge bg-info text-dark"><i class="fa-solid fa-wallet me-1"></i>MercadoPago</span>';
            } else if (g.provider === 'getnet') {
                providerBadge = '<span class="badge bg-secondary"><i class="fa-solid fa-building-columns me-1"></i>Getnet</span>';
            }

            const formattedDate = new Date(g.date + 'T00:00:00').toLocaleDateString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            html += `
                <tr data-key="${key}" data-expected="${expected}">
                    <td class="fw-semibold text-secondary">${formattedDate}</td>
                    <td class="fw-bold text-dark">${g.sucursal}</td>
                    <td>${providerBadge}</td>
                    <td class="text-end fw-bold">${formatMoney(expected)}</td>
                    <td class="text-end">
                        <div class="input-group input-group-sm justify-content-end">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" class="form-control text-end declared-input fw-bold" 
                                   value="${declaredValStr}" placeholder="Ingresar..." style="max-width: 150px;">
                        </div>
                    </td>
                    <td class="text-end fw-bold diff-column text-${Math.abs(diff) < 0.01 ? 'success' : 'danger'}">
                        ${formatMoney(diff)}
                    </td>
                    <td class="text-center status-column">${statusBadge}</td>
                </tr>
            `;
        });

        if (html === '') {
            $reconcileTbody.html(`
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        No hay registros que coincidan con el estado seleccionado.
                    </td>
                </tr>
            `);
        } else {
            $reconcileTbody.html(html);
        }
        updateReconcileSummaryCounters();
    }

    // Actualizar tarjetas de resumen OK / Diferencia / Total
    function updateReconcileSummaryCounters() {
        let countOk = 0;
        let countDiff = 0;
        let total = 0;

        $reconcileTbody.find('tr').each(function () {
            const $statusCol = $(this).find('.status-column');
            if ($statusCol.length) {
                total++;
                if ($statusCol.find('.bg-success-subtle').length || $statusCol.text().includes('OK')) {
                    countOk++;
                } else if ($statusCol.find('.bg-danger-subtle').length || $statusCol.text().includes('Diferencia')) {
                    countDiff++;
                }
            }
        });

        $('#reconcile-count-ok').text(countOk);
        $('#reconcile-count-diff').text(countDiff);
        $('#reconcile-count-total').text(total);

        $('#badge-ok-summary').html(`<i class="fa-solid fa-circle-check me-1"></i> OK: ${countOk}`);
        $('#badge-diff-summary').html(`<i class="fa-solid fa-triangle-exclamation me-1"></i> Diferencia: ${countDiff}`);
    }

    // Filtrar y renderizar la vista consolidada
    function filterAndRender() {
        const sucursalSel = $('#sucursal').val();
        let filtered = currentPayments;

        if (sucursalSel !== 'todos') {
            filtered = currentPayments.filter(p => (p.sucursal || '').toLowerCase() === sucursalSel.toLowerCase());
        }

        renderTable(filtered);
        renderBranchesSummary(filtered);
        buildReconciliationTable(filtered, currentPosSales);

        let gross_total = 0;
        let fees_total = 0;
        let net_total = 0;

        filtered.forEach(p => {
            gross_total += p.gross_amount || 0;
            fees_total  += p.fee_amount   || 0;
            net_total   += p.net_amount   || 0;
        });

        const count = filtered.length;
        const ticket_average = count > 0 ? (gross_total / count) : 0;
        const fee_percentage = gross_total > 0 ? ((fees_total / gross_total) * 100).toFixed(1) : '0';

        $grossMetric.text(formatMoney(gross_total));
        $feesMetric.text(formatMoney(fees_total));
        $netMetric.text(formatMoney(net_total));
        $ticketMetric.text(formatMoney(ticket_average));
        $transCount.text(count);
        $feePercentage.text(fee_percentage + '%');
    }

    // Manejar cambios en las ventas declaradas manualmente en la conciliación
    $reconcileTbody.on('input', '.declared-input', function () {
        const $row = $(this).closest('tr');
        const key = $row.data('key');
        const expected = parseFloat($row.data('expected')) || 0;
        const valStr = $(this).val();

        let declared = 0;
        let diff = expected;

        if (valStr !== '') {
            declared = parseFloat(valStr) || 0;
            diff = expected - declared;
            localStorage.setItem(`declared_${key}`, valStr);
        } else {
            localStorage.removeItem(`declared_${key}`);
        }

        const $diffCol = $row.find('.diff-column');
        $diffCol.text(formatMoney(diff));

        const $statusCol = $row.find('.status-column');
        if (valStr === '') {
            $diffCol.removeClass('text-success').addClass('text-danger');
            $statusCol.html('<span class="badge bg-secondary-subtle text-secondary small py-1 px-2 rounded-pill"><i class="fa-regular fa-clock me-1"></i>Pendiente</span>');
        } else if (Math.abs(diff) < 0.01) {
            $diffCol.removeClass('text-danger').addClass('text-success');
            $statusCol.html('<span class="badge bg-success-subtle text-success small py-1 px-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i>OK / Conciliado</span>');
        } else {
            $diffCol.removeClass('text-success').addClass('text-danger');
            $statusCol.html('<span class="badge bg-danger-subtle text-danger small py-1 px-2 rounded-pill"><i class="fa-solid fa-triangle-exclamation me-1"></i>Diferencia</span>');
        }

        updateReconcileSummaryCounters();
    });

    // Botón Auto-match
    $reconcileTbody.on('click', '.auto-match-btn', function () {
        const $row = $(this).closest('tr');
        const expected = parseFloat($row.data('expected')) || 0;
        const $input = $row.find('.declared-input');
        $input.val(expected.toFixed(2)).trigger('input');
    });

    // Escuchar cambio a pestaña Control Diario por Local
    $('#control-diario-tab').on('shown.bs.tab', function () {
        buildReconciliationTable(currentPayments, currentPosSales);
    });

    // Exportar planilla de conciliación
    $('#export-excel-reconcile-btn').on('click', function () {
        alert('Exportando Planilla de Conciliación Diaria por Local en XLS...');
    });

    // Consultar al enviar el formulario de filtros
    $('#filter-form').on('submit', function (e) {
        e.preventDefault();
        fetchPayments();
    });

    // Filtro por cambio en el selector de sucursal
    $('#sucursal').on('change', function () {
        fetchPayments();
    });

    // Filtro por cambio en el selector de estado (OK / Diferencia / Todos)
    $('#estado').on('change', function () {
        filterAndRender();
    });

    // Búsqueda rápida en tiempo real en la tabla de transacciones
    $('#quick-search').on('keyup', function () {
        const value = $(this).val().toLowerCase();
        $('#payments-tbody tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Modal de detalle de transacción
    $tbody.on('click', '.view-details-btn', function () {
        const id = $(this).data('id');
        const payment = currentPayments.find(p => p.id === id);
        if (!payment) return;

        let timelineHtml = '';
        if (payment.installments_detail) {
            payment.installments_detail.forEach(inst => {
                const statusLabel = inst.status === 'acreditado' 
                    ? '<span class="badge bg-success-subtle text-success ms-2">Cobrado</span>' 
                    : '<span class="badge bg-warning-subtle text-warning ms-2">Programado</span>';
                
                const instDate = new Date(inst.acreditation_date).toLocaleDateString('es-AR', {
                    day: '2-digit', month: '2-digit', year: 'numeric'
                });

                timelineHtml += `
                    <div class="border-start border-3 border-primary ps-3 py-2 mb-2 bg-light rounded-end">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark small">Cuota #${inst.installment_number}</span>
                            <span class="text-muted small">${instDate}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="small text-secondary">
                                Bruto: ${formatMoney(inst.gross)} | Neto: <strong class="text-success">${formatMoney(inst.net)}</strong>
                            </span>
                            ${statusLabel}
                        </div>
                    </div>
                `;
            });
        }

        const formattedDate = payment.date ? new Date(payment.date.replace(' ', 'T')).toLocaleDateString('es-AR', {
            day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
        }) : '—';

        const modalHtml = `
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-circle-info me-2 text-info"></i>Detalle de Transacción</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary small fw-semibold">ID: ${payment.id}</span>
                    <span class="badge bg-primary-subtle text-primary">${payment.sucursal || 'General'}</span>
                </div>
                
                <div class="row g-2 mb-3 bg-light p-3 rounded-3 border">
                    <div class="col-6">
                        <span class="text-muted d-block" style="font-size:0.72rem;text-transform:uppercase;">Cliente / Origen</span>
                        <strong class="text-dark small">${payment.client_name}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block" style="font-size:0.72rem;text-transform:uppercase;">Fecha Venta</span>
                        <strong class="text-dark small">${formattedDate}</strong>
                    </div>
                </div>

                <div class="row g-2 mb-3 text-center">
                    <div class="col-4 border-end">
                        <span class="text-muted d-block" style="font-size:0.72rem;text-transform:uppercase;">Bruto</span>
                        <strong class="text-dark">${formatMoney(payment.gross_amount)}</strong>
                    </div>
                    <div class="col-4 border-end">
                        <span class="text-muted d-block" style="font-size:0.72rem;text-transform:uppercase;">Comisión</span>
                        <strong class="text-danger">${formatMoney(payment.fee_amount)}</strong>
                    </div>
                    <div class="col-4">
                        <span class="text-muted d-block" style="font-size:0.72rem;text-transform:uppercase;">Neto Real</span>
                        <strong class="text-success">${formatMoney(payment.net_amount)}</strong>
                    </div>
                </div>

                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-regular fa-calendar-check me-2 text-secondary"></i>Esquema de Cuotas / Acreditación</h6>
                
                <div class="settlement-timeline">
                    ${timelineHtml}
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        `;

        $('#details-modal-content').html(modalHtml);
        $('#paymentDetailsModal').modal('show');
    });

    // Botón exportar XLS
    $('#export-excel-btn').on('click', function () {
        alert('Exportando planilla XLS de transacciones por sucursal...');
    });

    // Carga inicial al cargar la pantalla
    fetchPayments();
});

// MÓDULO LIQUIDACIONES GOCUOTAS
$(document).ready(function () {
    const $liqTbody    = $('#liq-tbody');
    const $liqGross    = $('#liq-metric-gross');
    const $liqRetained = $('#liq-metric-retained');
    const $liqNet      = $('#liq-metric-net');
    const $liqCount    = $('#liq-metric-count');
    const $liqNextDate = $('#liq-metric-next-date');
    const $liqNextAmt  = $('#liq-metric-next-amount');

    let currentLiquidaciones = [];

    function formatMoney(amount) {
        return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(amount || 0);
    }

    function fetchLiquidaciones() {
        const desde = $('#desde').val();
        const hasta = $('#hasta').val();
        const sucursal = $('#sucursal').val();

        $.ajax({
            url: 'Controller/PaymentController.php',
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'get_payments',
                desde: desde,
                hasta: hasta,
                sucursal: sucursal,
                procesador: 'gocuotas',
                estado: 'todos'
            },
            success: function (res) {
                if (res.success) {
                    currentLiquidaciones = res.data.payments || [];
                    renderLiquidaciones(currentLiquidaciones);
                    updateLiquidacionesMetrics(currentLiquidaciones);
                } else {
                    $liqTbody.html(`
                        <tr>
                            <td colspan="9" class="text-center py-4 text-danger">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> ${res.message}
                            </td>
                        </tr>
                    `);
                }
            }
        });
    }

    function renderLiquidaciones(items) {
        if (!items || items.length === 0) {
            $liqTbody.html(`
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                        No se encontraron liquidaciones de GoCuotas para el período seleccionado.
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        items.forEach(p => {
            const methodLabel = (p.description || '').replace('Pago recibido via ', '');

            const payDate = p.date ? new Date(p.date.replace(' ', 'T')).toLocaleDateString('es-AR', {
                day: '2-digit', month: '2-digit', year: 'numeric'
            }) : '—';

            const acredDate = p.acreditation_date ? new Date(p.acreditation_date + 'T00:00:00').toLocaleDateString('es-AR', {
                day: '2-digit', month: '2-digit', year: 'numeric'
            }) : '—';

            const statusBadge = p.status === 'approved'
                ? '<span class="badge bg-success-subtle text-success"><i class="fa-solid fa-check-circle me-1"></i>Acreditado</span>'
                : '<span class="badge bg-warning-subtle text-warning"><i class="fa-solid fa-clock me-1"></i>Pendiente</span>';

            html += `
                <tr data-id="${p.id}">
                    <td class="fw-semibold text-secondary small">${p.id}</td>
                    <td>${payDate}</td>
                    <td><span class="badge bg-warning text-dark"><i class="fa-solid fa-university me-1"></i>${methodLabel}</span></td>
                    <td class="text-end fw-bold">${formatMoney(p.gross_amount)}</td>
                    <td class="text-end text-danger">${formatMoney(p.fee_amount)}</td>
                    <td class="text-end text-success fw-bold">${formatMoney(p.net_amount)}</td>
                    <td>
                        <span class="d-block fw-semibold text-secondary small">${acredDate}</span>
                        <span class="d-block text-muted" style="font-size:0.75rem;">${p.acreditation_type || ''}</span>
                    </td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">
                        <button class="btn btn-outline-warning btn-sm liq-detail-btn" data-id="${p.id}" style="padding:2px 8px;font-size:0.75rem;">
                            <i class="fa-solid fa-eye"></i> Detalle
                        </button>
                    </td>
                </tr>
            `;
        });

        $liqTbody.html(html);
    }

    function updateLiquidacionesMetrics(items) {
        let gross = 0, retained = 0, net = 0;
        let nextDue = null;

        items.forEach(p => {
            gross    += p.gross_amount || 0;
            retained += p.fee_amount   || 0;
            net      += p.net_amount   || 0;

            if (p.acreditation_date) {
                const d = new Date(p.acreditation_date + 'T00:00:00');
                if (!nextDue || d < nextDue) nextDue = d;
            }
        });

        $liqGross.text(formatMoney(gross));
        $liqRetained.text(formatMoney(retained));
        $liqNet.text(formatMoney(net));
        $liqCount.text(items.length);

        if (nextDue) {
            $liqNextDate.text(nextDue.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' }));
            $liqNextAmt.text('Vencimiento de expensa más próxima');
        } else {
            $liqNextDate.text('—');
            $liqNextAmt.text('Sin vencimientos en el período');
        }
    }

    $('#liquidaciones-tab').on('shown.bs.tab', function () {
        fetchLiquidaciones();
    });
});
