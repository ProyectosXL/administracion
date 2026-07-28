// js/app.js - Lógica dinámica del frontend de la Agenda de Pagos
$(document).ready(function () {
    // Referencias a elementos
    const $tbody = $('#payments-tbody');
    const $grossMetric = $('#metric-gross-total');
    const $feesMetric = $('#metric-fees-total');
    const $netMetric = $('#metric-net-total');
    const $ticketMetric = $('#metric-ticket-average');
    const $transCount = $('#metric-trans-count');
    const $feePercentage = $('#metric-fee-percentage');

    // Distribución bars
    const $distGoCuotasPercentage = $('#dist-gocuotas-percentage');
    const $distGoCuotasBar = $('#dist-gocuotas-bar');
    const $distMPPercentage = $('#dist-mercadopago-percentage');
    const $distMPBar = $('#dist-mercadopago-bar');
    const $distGetnetPercentage = $('#dist-getnet-percentage');
    const $distGetnetBar = $('#dist-getnet-bar');

    // Pronósticos
    const $forecastImmediate = $('#forecast-immediate');
    const $forecastShort = $('#forecast-short');
    const $forecastScheduled = $('#forecast-scheduled');

    let currentPayments = [];

    // Cargar transacciones de la API
    function fetchPayments() {
        $tbody.html(`
            <tr>
                <td colspan="11" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Buscando transacciones...
                </td>
            </tr>
        `);

        const desde = $('#desde').val();
        const hasta = $('#hasta').val();
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
                procesador: procesador,
                estado: estado
            },
            success: function (res) {
                if (res.success) {
                    currentPayments = res.data.payments;
                    filterAndRender();
                } else {
                    $tbody.html(`
                        <tr>
                            <td colspan="11" class="text-center py-4 text-danger">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> ${res.message}
                            </td>
                        </tr>
                    `);
                }
            },
            error: function () {
                $tbody.html(`
                    <tr>
                        <td colspan="11" class="text-center py-4 text-danger">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Error al conectar con el servidor.
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Formatear monedas
    function formatMoney(amount) {
        return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(amount);
    }

    // Actualizar tarjetas de métricas y gráficos
    function updateMetrics(metrics) {
        $grossMetric.text(formatMoney(metrics.gross_total));
        $feesMetric.text(formatMoney(metrics.fees_total));
        $netMetric.text(formatMoney(metrics.net_total));
        $ticketMetric.text(formatMoney(metrics.ticket_average));
        $transCount.text(metrics.count);
        $feePercentage.text(metrics.fee_percentage + '%');

        // Distribución por pasarelas
        const gocuotas = metrics.distribution.gocuotas;
        const mp = metrics.distribution.mercadopago;
        const getnet = metrics.distribution.getnet;

        $distGoCuotasPercentage.text(`${gocuotas.percentage}% (${formatMoney(gocuotas.gross)})`);
        $distGoCuotasBar.css('width', gocuotas.percentage + '%');

        $distMPPercentage.text(`${mp.percentage}% (${formatMoney(mp.gross)})`);
        $distMPBar.css('width', mp.percentage + '%');

        $distGetnetPercentage.text(`${getnet.percentage}% (${formatMoney(getnet.gross)})`);
        $distGetnetBar.css('width', getnet.percentage + '%');

        // Previsión de caja
        $forecastImmediate.text(formatMoney(metrics.forecast.immediate));
        $forecastShort.text(formatMoney(metrics.forecast.short));
        $forecastScheduled.text(formatMoney(metrics.forecast.scheduled));
    }

    // Renderizar la tabla de cobros
    function renderTable(payments) {
        if (payments.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="11" class="text-center py-4 text-muted">
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
                providerBadge = '<span class="badge bg-pink"><i class="fa-solid fa-credit-card me-1"></i>GoCuotas</span>';
            } else if (p.provider === 'mercadopago') {
                providerBadge = '<span class="badge bg-sky"><i class="fa-solid fa-wallet me-1"></i>MercadoPago</span>';
            } else if (p.provider === 'getnet') {
                providerBadge = '<span class="badge bg-purple"><i class="fa-solid fa-building-columns me-1"></i>Getnet</span>';
            }

            let statusBadge = '';
            if (p.status === 'approved') {
                statusBadge = '<span class="badge-status bg-success-subtle text-success"><i class="fa-solid fa-check-circle me-1"></i>Aprobado</span>';
            } else if (p.status === 'pending') {
                statusBadge = '<span class="badge-status bg-warning-subtle text-warning"><i class="fa-solid fa-clock me-1"></i>En Proceso</span>';
            } else {
                statusBadge = '<span class="badge-status bg-danger-subtle text-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Rechazado</span>';
            }

            const formattedDate = new Date(p.date).toLocaleDateString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const formattedAcredDate = new Date(p.acreditation_date).toLocaleDateString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            html += `
                <tr data-id="${p.id}">
                    <td class="fw-semibold text-secondary small">${p.id}</td>
                    <td>${formattedDate}</td>
                    <td>${providerBadge}</td>
                    <td class="fw-semibold">${p.client_name}</td>
                    <td><span class="badge bg-light text-dark border">${p.installments} cuotas</span></td>
                    <td class="text-end fw-bold">${formatMoney(p.gross_amount)}</td>
                    <td class="text-end text-danger">${formatMoney(p.fee_amount)}</td>
                    <td class="text-end text-success fw-bold">${formatMoney(p.net_amount)}</td>
                    <td>
                        <div class="small">
                            <span class="d-block fw-semibold text-secondary">${formattedAcredDate}</span>
                            <span class="d-block text-muted fs-9">${p.acreditation_type}</span>
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

    // Consultar al enviar filtros
    $('#filter-form').on('submit', function (e) {
        e.preventDefault();
        fetchPayments();
    });

    // Búsqueda rápida en tiempo real en la tabla
    $('#quick-search').on('keyup', function () {
        const value = $(this).val().toLowerCase();
        $('#payments-tbody tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });



    // Ver detalles de acreditación / liquidaciones en modal
    $tbody.on('click', '.view-details-btn', function () {
        const id = $(this).data('id');
        const payment = currentPayments.find(p => p.id === id);

        if (!payment) return;

        let timelineHtml = '';
        if (payment.installments_detail) {
            payment.installments_detail.forEach(inst => {
                const badgeClass = inst.status === 'acreditado' ? 'node-acreditado' : 'node-pendiente';
                const statusLabel = inst.status === 'acreditado' ? '<span class="badge bg-success-subtle text-success ms-2">Cobrado</span>' : '<span class="badge bg-warning-subtle text-warning ms-2">Programado</span>';
                
                const instDate = new Date(inst.acreditation_date).toLocaleDateString('es-AR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });

                timelineHtml += `
                    <div class="settlement-node ${badgeClass}">
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

        const formattedDate = new Date(payment.date).toLocaleDateString('es-AR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const modalHtml = `
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-circle-info me-2 text-info"></i>Detalle de Transacción</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-secondary small fw-semibold">ID: ${payment.id}</span>
                    <span class="text-secondary small">${formattedDate}</span>
                </div>
                
                <div class="row g-2 mb-3 bg-light p-3 rounded-3 border">
                    <div class="col-6">
                        <span class="text-muted d-block fs-9 text-uppercase">Cliente</span>
                        <strong class="text-dark small">${payment.client_name}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block fs-9 text-uppercase">Concepto</span>
                        <strong class="text-dark small">${payment.description}</strong>
                    </div>
                </div>

                <div class="row g-2 mb-3 text-center">
                    <div class="col-4 border-end">
                        <span class="text-muted d-block fs-9 text-uppercase">Bruto</span>
                        <strong class="text-dark">${formatMoney(payment.gross_amount)}</strong>
                    </div>
                    <div class="col-4 border-end">
                        <span class="text-muted d-block fs-9 text-uppercase">Comisión+IVA</span>
                        <strong class="text-danger">${formatMoney(payment.fee_amount)}</strong>
                    </div>
                    <div class="col-4">
                        <span class="text-muted d-block fs-9 text-uppercase">Neto Real</span>
                        <strong class="text-success">${formatMoney(payment.net_amount)}</strong>
                    </div>
                </div>

                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-regular fa-calendar-check me-2 text-secondary"></i>Esquema de Liquidación</h6>
                
                <div class="settlement-timeline">
                    ${timelineHtml}
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar Detalle</button>
            </div>
        `;

        $('#details-modal-content').html(modalHtml);
        $('#paymentDetailsModal').modal('show');
    });

    // Construir la planilla de conciliación agrupada por Fecha, Sucursal y Procesador
    function buildReconciliationTable(payments) {
        const $reconcileTbody = $('#reconcile-tbody');
        
        // Filtrar transacciones aprobadas para la conciliación
        const approvedPayments = payments.filter(p => p.status === 'approved');

        if (approvedPayments.length === 0) {
            $reconcileTbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        No hay ventas aprobadas para conciliar en este período.
                    </td>
                </tr>
            `);
            return;
        }

        // Agrupar por Fecha (solo fecha, sin hora), Sucursal y Proveedor
        const groups = {};
        approvedPayments.forEach(p => {
            const dateOnly = p.date.split(' ')[0]; // YYYY-MM-DD
            const sucursal = p.sucursal || 'Local General';
            const provider = p.provider;
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
            groups[key].expected_amount += p.gross_amount;
            groups[key].transactions.push(p);
        });

        // Generar HTML
        let html = '';
        Object.keys(groups).forEach(key => {
            const g = groups[key];
            
            // Cargar valor guardado en localStorage si existe, o dejar vacío
            const cacheKey = `declared_${key}`;
            const savedValue = localStorage.getItem(cacheKey) || '';
            
            const expected = g.expected_amount;
            const declared = savedValue !== '' ? parseFloat(savedValue) : 0;
            const diff = expected - declared;

            let statusBadge = '';
            if (savedValue === '') {
                statusBadge = '<span class="badge bg-secondary-subtle text-secondary small py-1 px-2 rounded-pill"><i class="fa-regular fa-clock me-1"></i>Pendiente</span>';
            } else if (Math.abs(diff) < 0.01) {
                statusBadge = '<span class="badge bg-success-subtle text-success small py-1 px-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i>Conciliado</span>';
            } else {
                statusBadge = '<span class="badge bg-danger-subtle text-danger small py-1 px-2 rounded-pill"><i class="fa-solid fa-triangle-exclamation me-1"></i>Diferencia</span>';
            }

            let providerBadge = '';
            if (g.provider === 'gocuotas') {
                providerBadge = '<span class="badge bg-pink"><i class="fa-solid fa-credit-card me-1"></i>GoCuotas</span>';
            } else if (g.provider === 'mercadopago') {
                providerBadge = '<span class="badge bg-sky"><i class="fa-solid fa-wallet me-1"></i>MercadoPago</span>';
            } else if (g.provider === 'getnet') {
                providerBadge = '<span class="badge bg-purple"><i class="fa-solid fa-building-columns me-1"></i>Getnet</span>';
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
                            <input type="number" step="0.01" class="form-control text-end declared-input" 
                                   value="${savedValue}" placeholder="Ingresar..." style="max-width: 150px;">
                        </div>
                    </td>
                    <td class="text-end fw-bold diff-column text-${Math.abs(diff) < 0.01 ? 'success' : 'danger'}">
                        ${formatMoney(diff)}
                    </td>
                    <td class="text-center status-column">${statusBadge}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-success auto-match-btn" style="padding: 2px 6px; font-size: 0.75rem;">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto
                        </button>
                    </td>
                </tr>
            `;
        });

        $reconcileTbody.html(html);
        recalculateReconcileKPIs();
    }

    // Calcular y actualizar indicadores KPI de conciliación
    function recalculateReconcileKPIs() {
        let totalExpected = 0;
        let totalDeclared = 0;
        let totalControlled = 0;
        let totalRows = 0;

        $('#reconcile-tbody tr').each(function () {
            // Ignorar filas de carga vacías
            if ($(this).find('td').length < 5) return;

            const expected = parseFloat($(this).data('expected')) || 0;
            const $input = $(this).find('.declared-input');
            const valStr = $input.val();
            
            totalRows++;
            totalExpected += expected;

            if (valStr !== '') {
                totalDeclared += parseFloat(valStr) || 0;
                totalControlled++;
            }
        });

        const totalDiff = totalExpected - totalDeclared;
        const statusPercentage = totalRows > 0 ? Math.round((totalControlled / totalRows) * 100) : 0;

        // Actualizar UI
        $('#kpi-reconcile-expected').text(formatMoney(totalExpected));
        $('#kpi-reconcile-declared').text(formatMoney(totalDeclared));
        
        const $diffKpi = $('#kpi-reconcile-diff');
        $diffKpi.text(formatMoney(totalDiff));
        
        const $diffIcon = $('#kpi-reconcile-diff-icon');
        const $diffStatus = $('#kpi-reconcile-diff-status');
        
        if (totalRows === 0) {
            $diffKpi.removeClass('text-success').addClass('text-muted');
            $diffIcon.removeClass('bg-light-success text-success bg-light-danger text-danger').addClass('bg-light text-secondary');
            $diffStatus.text('Sin registros');
        } else if (Math.abs(totalDiff) < 0.01) {
            $diffKpi.removeClass('text-danger text-muted').addClass('text-success');
            $diffIcon.removeClass('bg-light-danger text-danger bg-light text-secondary').addClass('bg-light-success text-success');
            $diffStatus.text('Conciliación perfecta');
        } else {
            $diffKpi.removeClass('text-success text-muted').addClass('text-danger');
            $diffIcon.removeClass('bg-light-success text-success bg-light text-secondary').addClass('bg-light-danger text-danger');
            $diffStatus.text('Discrepancias detectadas');
        }

        $('#kpi-reconcile-status').text(statusPercentage + '%');
        $('#kpi-reconcile-count').text(`${totalControlled} de ${totalRows} controladas`);
    }

    // Manejar cambios en el importe declarado físicamente
    $('#reconcile-tbody').on('input', '.declared-input', function () {
        const $row = $(this).closest('tr');
        const key = $row.data('key');
        const expected = parseFloat($row.data('expected'));
        const valStr = $(this).val();

        let declared = 0;
        let diff = expected;

        if (valStr !== '') {
            declared = parseFloat(valStr);
            diff = expected - declared;
            localStorage.setItem(`declared_${key}`, valStr);
        } else {
            localStorage.removeItem(`declared_${key}`);
        }

        // Actualizar columna de diferencia
        const $diffCol = $row.find('.diff-column');
        $diffCol.text(formatMoney(diff));

        // Actualizar badge de estado
        const $statusCol = $row.find('.status-column');
        if (valStr === '') {
            $diffCol.removeClass('text-success').addClass('text-danger');
            $statusCol.html('<span class="badge bg-secondary-subtle text-secondary small py-1 px-2 rounded-pill"><i class="fa-regular fa-clock me-1"></i>Pendiente</span>');
        } else if (Math.abs(diff) < 0.01) {
            $diffCol.removeClass('text-danger').addClass('text-success');
            $statusCol.html('<span class="badge bg-success-subtle text-success small py-1 px-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i>Conciliado</span>');
        } else {
            $diffCol.removeClass('text-success').addClass('text-danger');
            $statusCol.html('<span class="badge bg-danger-subtle text-danger small py-1 px-2 rounded-pill"><i class="fa-solid fa-triangle-exclamation me-1"></i>Diferencia</span>');
        }

        recalculateReconcileKPIs();
    });

    // Botón de autocompletado (Auto-match)
    $('#reconcile-tbody').on('click', '.auto-match-btn', function () {
        const $row = $(this).closest('tr');
        const expected = parseFloat($row.data('expected'));
        const $input = $row.find('.declared-input');
        
        $input.val(expected.toFixed(2)).trigger('input');
    });

    // Exportar planilla de conciliación
    $('#export-excel-reconcile-btn').on('click', function () {
        alert('Generando y exportando Planilla Excel de Conciliación Diaria...\n\nSe exportaron agrupados por local y día: Ventas API, Ventas Declaradas y Diferencias detectadas.');
    });

    // Botón exportar simulación transacciones
    $('#export-excel-btn').on('click', function () {
        alert('Exportando planilla unificada de transacciones XLS...');
    });

    // Función para filtrar y renderizar de forma reactiva en el cliente
    function filterAndRender() {
        const sucursal = $('#sucursal').val();
        let filtered = currentPayments;

        if (sucursal !== 'todos') {
            filtered = currentPayments.filter(p => p.sucursal === sucursal);
        }

        // Renderizar tablas
        renderTable(filtered);
        buildReconciliationTable(filtered);

        // Recalcular métricas sobre la marcha
        let gross_total = 0;
        let fees_total = 0;
        let net_total = 0;
        let approved_count = 0;

        let dist = {
            gocuotas: 0,
            mercadopago: 0,
            getnet: 0
        };

        let forecast = {
            immediate: 0,
            short: 0,
            scheduled: 0
        };

        filtered.forEach(p => {
            gross_total += p.gross_amount;
            fees_total += p.fee_amount;
            net_total += p.net_amount;

            if (p.status === 'approved') {
                approved_count++;
                
                // Forecast (Acreditaciones aprobadas)
                const type = p.acreditation_type.toLowerCase();
                if (type.includes('inmediata') || type.includes('adelantada')) {
                    forecast.immediate += p.net_amount;
                } else if (type.includes('24 horas') || type.includes('48 horas') || type.includes('14 dias') || type.includes('días')) {
                    forecast.short += p.net_amount;
                } else {
                    forecast.scheduled += p.net_amount;
                }
            }

            // Distribución
            if (p.provider === 'gocuotas') dist.gocuotas += p.gross_amount;
            if (p.provider === 'mercadopago') dist.mercadopago += p.gross_amount;
            if (p.provider === 'getnet') dist.getnet += p.gross_amount;
        });

        const count = filtered.length;
        const ticket_average = count > 0 ? (gross_total / count) : 0;
        const fee_percentage = gross_total > 0 ? ((fees_total / gross_total) * 100).toFixed(1) : '0';

        const distTotal = dist.gocuotas + dist.mercadopago + dist.getnet;
        const distGoCuotasPercentage = distTotal > 0 ? Math.round((dist.gocuotas / distTotal) * 100) : 0;
        const distMPPercentage = distTotal > 0 ? Math.round((dist.mercadopago / distTotal) * 100) : 0;
        const distGetnetPercentage = distTotal > 0 ? Math.round((dist.getnet / distTotal) * 100) : 0;

        // Actualizar elementos DOM
        $grossMetric.text(formatMoney(gross_total));
        $feesMetric.text(formatMoney(fees_total));
        $netMetric.text(formatMoney(net_total));
        $ticketMetric.text(formatMoney(ticket_average));
        $transCount.text(count);
        $feePercentage.text(fee_percentage + '%');

        $distGoCuotasPercentage.text(`${distGoCuotasPercentage}% (${formatMoney(dist.gocuotas)})`);
        $distGoCuotasBar.css('width', distGoCuotasPercentage + '%');

        $distMPPercentage.text(`${distMPPercentage}% (${formatMoney(dist.mercadopago)})`);
        $distMPBar.css('width', distMPPercentage + '%');

        $distGetnetPercentage.text(`${distGetnetPercentage}% (${formatMoney(dist.getnet)})`);
        $distGetnetBar.css('width', distGetnetPercentage + '%');

        $forecastImmediate.text(formatMoney(forecast.immediate));
        $forecastShort.text(formatMoney(forecast.short));
        $forecastScheduled.text(formatMoney(forecast.scheduled));
    }

    // Escuchar el cambio en el selector de sucursal
    $('#sucursal').on('change', function () {
        filterAndRender();
    });

    // Carga inicial
    fetchPayments();
});
