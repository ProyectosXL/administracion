// ========================================================================
//                              APP.JS - COMPLETO
// ========================================================================

$(document).ready(function () {
    let chartEstadosInstance = null;
    let chartActividadInstance = null;

    function cargarDashboardGestion() {
        $.ajax({
            url: 'api/propuestas_controller.php?action=obtener_dashboard_admin',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const data = response.data;

                    // ======================= INICIO DE LA MODIFICACIÓN DE KPIs =======================

                    // Función auxiliar para formatear a moneda
                    const formatoMoneda = (valor) => {
                        // Si el valor es nulo o indefinido, lo tratamos como 0
                        const numero = parseFloat(valor || 0);
                        return numero.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
                    };

                    // Asegúrate de que los IDs en tu index.php coincidan con estos
                    $('#kpi-propuestas-activas').text(data.kpis.totalActivas || '0');
                    $('#kpi-monto-negociacion').text(formatoMoneda(data.kpis.montoEnNegociacion));

                    // Nuevo KPI para el Monto Vencido
                    $('#kpi-monto-vencido').text(formatoMoneda(data.kpis.montoVencido));

                    $('#kpi-requieren-accion').text(data.kpis.contrapropuestas || '0');
                    $('#kpi-aceptadas-mes').text(data.kpis.aceptadasMes || '0');

                    // ======================== FIN DE LA MODIFICACIÓN DE KPIs =========================

                    // Renderizar Gráfico de Estados (sin cambios)
                    if (data.graficoEstados) {
                        renderizarChartEstados(data.graficoEstados);
                    }

                    // Renderizar Gráfico de Actividad (sin cambios)
                    if (data.graficoActividad) {
                        renderizarChartActividad(data.graficoActividad);
                    }
                } else {
                    // Añadimos un log de error para facilitar la depuración
                    console.error("El backend devolvió un error al cargar el dashboard:", response.message);
                }
            },
            error: function () {
                // Añadimos un log de error para fallos de conexión
                console.error("Fallo la llamada AJAX para cargar el dashboard del admin.");
            }
        });
    }

    function renderizarChartEstados(data) {
        const ctx = document.getElementById('chartEstados').getContext('2d');
        const labels = data.map(item => item.estado.replace(/_/g, ' '));
        const cantidades = data.map(item => item.cantidad);
        const total = cantidades.reduce((a, b) => a + b, 0);

        // Mapeo de colores estricto por estado para evitar repeticiones
        const colorMap = {
            'PENDIENTE_APROBACION_CLIENTE': 'rgba(255, 193, 7, 0.8)',   // Amarillo
            'CONTRAPROPUESTA_CLIENTE': 'rgba(13, 110, 253, 0.8)',      // Azul
            'ACEPTADA': 'rgba(25, 135, 84, 0.8)',                     // Verde
            'PENDIENTE_APROBACION_FINAL': 'rgba(253, 126, 20, 0.8)',    // Naranja
            'DOCUMENTACION_ADJUNTADA': 'rgba(108, 117, 125, 0.8)',     // Gris
            'PAGADO': 'rgba(13, 202, 240, 0.8)',                       // Cian
            'VENCIDA': 'rgba(220, 53, 69, 0.8)',                       // Rojo
            'RECHAZADA': 'rgba(0, 0, 0, 0.8)'                          // Negro
        };

        const backgroundColors = data.map(item => colorMap[item.estado] || 'rgba(200, 200, 200, 0.8)');

        if (chartEstadosInstance) {
            chartEstadosInstance.destroy();
        }

        chartEstadosInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Propuestas',
                    data: cantidades,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(color => color.replace('0.8', '1')),
                    borderWidth: 2
                }]
            },
            plugins: [ChartDataLabels], // Registrar el plugin para este gráfico
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '50%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 11 }
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 11
                        },
                        formatter: (value, ctx) => {
                            const percentage = ((value / total) * 100).toFixed(1);
                            // Solo mostramos si el porcentaje es significativo para que no se amontonen
                            return value > 0 ? `${value}\n(${percentage}%)` : null;
                        },
                        anchor: 'center',
                        align: 'center',
                        offset: 0,
                        textAlign: 'center',
                        textShadowColor: 'rgba(0, 0, 0, 0.5)',
                        textShadowBlur: 4
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = context.raw;
                                const pct = ((val / total) * 100).toFixed(1);
                                return ` ${context.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function renderizarChartActividad(data) {
        const ctx = document.getElementById('chartActividadReciente').getContext('2d');
        const labels = [];
        const dataPoints = [];
        const hoy = new Date();

        for (let i = 6; i >= 0; i--) {
            const fecha = new Date();
            fecha.setDate(hoy.getDate() - i);
            const fechaStr = fecha.toISOString().split('T')[0];
            labels.push(fecha.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' }));

            const diaData = data.find(d => d.dia === fechaStr);
            dataPoints.push(diaData ? diaData.cantidad : 0);
        }

        if (chartActividadInstance) {
            chartActividadInstance.destroy();
        }

        chartActividadInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Propuestas Aceptadas',
                    data: dataPoints,
                    backgroundColor: 'rgba(25, 135, 84, 0.6)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Columnas para la VISTA RESUMEN (la principal)
    const columnsResumen = [
        { data: 'COD_CLIENT', title: 'Código' },
        { data: 'RAZON_SOCI', title: 'Razón Social', className: 'w-50' },
        { data: 'CANT_FACTURAS', title: 'Facturas', className: 'text-center' },
        { data: 'TOTAL_BRUTO', title: 'Total Bruto', render: $.fn.dataTable.render.number('.', ',', 2, '$ ') },
        { data: 'TOTAL_NETO', title: 'Total Neto', render: $.fn.dataTable.render.number('.', ',', 2, '$ ') },
        {
            data: null,
            title: 'Acciones',
            orderable: false,
            className: 'text-center',
            render: function (data, type, row) {
                return `<button class="btn btn-primary btn-sm btn-detalle" data-cod-client="${row.COD_CLIENT}" data-razon-soci="${row.RAZON_SOCI}" title="Ver Detalle de Facturas">
                            <i class="fa-solid fa-list-check"></i>
                        </button>`;
            }
        }
    ];

    // *** MODIFICADO ***: Columnas para la VISTA DETALLE (dentro del modal de creación)
    const columnsDetalle = [
        {
            data: null, orderable: false, className: 'select-checkbox text-center',
            title: '<input type="checkbox" class="form-check-input" id="select-all-invoices" title="Seleccionar Todo">',
            render: function (data, type, row) {
                return `<input type="checkbox" class="form-check-input invoice-checkbox" value="${row.N_COMP}">`;
            }
        },
        { data: 'FECHA_EMIS', title: 'F. Emisión' },
        // ======================= NUEVA COLUMNA AÑADIDA =======================
        {
            data: 'T_COMP',
            title: 'Tipo Comp.',
            className: 'text-center'
        },
        // =====================================================================
        { data: 'N_COMP', title: 'Comprobante' },
        {
            data: 'ESTADO',
            title: 'Estado',
            className: 'text-center'
        },
        {
            data: 'IMPORTE',
            title: 'Importe Bruto',
            className: 'text-end importe-bruto-cell',
            render: function (data, type, row) {
                let valor = parseFloat(data);
                // Si es Nota de Crédito (empieza con NC), lo hacemos negativo
                if (row.T_COMP.trim().startsWith('NC')) {
                    valor = valor * -1;
                }
                const numeroFormateado = $.fn.dataTable.render.number('.', ',', 2, '$ ').display(valor);
                return `<span class="${valor < 0 ? 'text-danger' : ''}">${numeroFormateado}</span>`;
            }
        },
        {
            // ======================= INICIO DE LA CORRECCIÓN A00115 =======================
            data: null, title: '% Descuento', className: 'text-center', orderable: false,
            render: function (data, type, row) {
                const tComp = (row.T_COMP || '').trim();
                const nComp = (row.N_COMP || '').trim();
                const bruto = parseFloat(row.IMPORTE) || 0;
                const netoOriginal = parseFloat(row.IMPORTE_NETO) || 0;
                const estado = (row.ESTADO || '').trim();
                let initialDiscount = 0;

                // Lógica de descuento por defecto (según parámetros del cliente)
                initialDiscount = (parseFloat(row.DESC_PP_MAX) || 0) * 100;

                // Si el medio por defecto es Transferencia, solemos bajar 2 puntos (del 8 al 6)
                const medioDef = (row.MEDIO_PAGO_DEFAULT || '').toString().trim().toUpperCase();
                if ((medioDef === 'TRANSFERENCIA' || medioDef === 'TRANSFERERENCIA') && Math.abs(initialDiscount - 8) < 0.05) {
                    initialDiscount = 6;
                }

                // Si el ERP ya calculó un descuento (común en FAC de sucursales normales), lo respetamos
                if (bruto > 0 && bruto > netoOriginal) {
                    const calcu = ((bruto - netoOriginal) / bruto) * 100;
                    if (calcu > initialDiscount) initialDiscount = calcu;
                }

                // REGLAS ESPECIALES REQUERIDAS PARA SUCURSAL A00115
                if (nComp.startsWith('A00115')) {
                    if (tComp === 'FAC') {
                        initialDiscount = 0; // FAC de la 115: Fuerza 0%
                    } else if (tComp === 'NCP') {
                        // NCP de la 115: Mantiene el descuento (ya asignado arriba)
                    } else {
                        initialDiscount = 0; // Otras NC (NCR, etc) de la 115: Fuerza 0%
                    }
                }
                // (Para el resto de sucursales, tanto FAC como NC/NCR conservan el descuento)

                return `<input type="number" class="form-control form-control-sm descuento-input" value="${initialDiscount.toFixed(2)}" min="0" max="100" step="0.01" style="width: 80px;">`;
            }
            // ======================== FIN DE LA CORRECCIÓN A00115 =========================
        },
        {
            data: 'IMPORTE_NETO',
            title: 'Importe Neto',
            className: 'text-end fw-bold importe-neto-cell',
            render: function (data, type, row) {
                const tComp = (row.T_COMP || '').trim();
                const nComp = (row.N_COMP || '').trim();
                const bruto = parseFloat(row.IMPORTE) || 0;
                const netoOriginal = parseFloat(row.IMPORTE_NETO) || 0;

                let initialDiscount = (parseFloat(row.DESC_PP_MAX) || 0) * 100;
                if (bruto > 0 && bruto > netoOriginal) {
                    const calcu = ((bruto - netoOriginal) / bruto) * 100;
                    if (calcu > initialDiscount) initialDiscount = calcu;
                }

                // REGLAS ESPECIALES REQUERIDAS PARA SUCURSAL A00115
                if (nComp.startsWith('A00115')) {
                    if (tComp === 'FAC') {
                        initialDiscount = 0;
                    } else if (tComp === 'NCP') {
                        // Mantiene el descuento
                    } else {
                        initialDiscount = 0;
                    }
                }
                // (Para el resto de sucursales, tanto FAC como NC/NCR conservan el descuento)

                // Forzamos el importe neto basado en el descuento calculado arriba
                let valor = bruto * (1 - (initialDiscount / 100));

                // Aplicamos signo negativo si es NC
                if (tComp.startsWith('NC')) {
                    valor = valor * -1;
                }

                const numeroFormateado = $.fn.dataTable.render.number('.', ',', 2, '$ ').display(valor);
                return `<span class="${valor < 0 ? 'text-danger fw-bold' : ''}">${numeroFormateado}</span>`;
            }
        },
        { data: 'PPP', title: 'PPP' },
        { data: 'FECHA_PROB_COBRO', title: 'F. Prob. Cobro' },
    ];

    function initializeDataTable(tableId, url) {
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().ajax.url(url).load();
        } else {
            $(tableId).DataTable({
                ajax: {
                    url: url,
                    dataSrc: function (json) {
                        actualizarCardsDeResumen(json.summary);
                        return json.data;
                    }
                },
                columns: columnsResumen,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                responsive: true,
                autoWidth: false,
                order: [[4, 'desc']],
                scrollY: '55vh',
                scrollCollapse: true,
                paging: true
            });
        }
    }

    function actualizarCardsDeResumen(summary) {
        const options = { style: 'currency', currency: 'ARS', minimumFractionDigits: 2 };
        $('#summary-total-neto').text(summary.totalNeto.toLocaleString('es-AR', options));
        $('#summary-total-comprobantes').text(summary.totalComprobantes.toLocaleString('es-AR'));
        $('#summary-total-clientes').text(summary.totalClientes.toLocaleString('es-AR'));
    }

    initializeDataTable('#tabla-franquicias', 'api/cobranzas_controller.php?tipo=franquicias');

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetId = $(e.target).attr("id");

        // Ocultamos todos los dashboards al principio para evitar solapamientos
        $('#gestion-dashboard').hide();
        $('#summary-cards').hide();
        $('#btn-abrir-parametros').hide(); // Ocultamos el botón de parámetros también por defecto

        if (targetId === 'gestion-tab') {
            // --- VISTA GESTIÓN ---
            $('#gestion-dashboard').show(); // Mostramos el dashboard de gestión
            initializeGestionDataTable();
        } else if (targetId === 'cronograma-tab') {
            // --- VISTA CRONOGRAMA ---
            // No mostramos ningún dashboard de KPIs aquí, solo el calendario
            initializeCronogramaAdmin();
        } else if (targetId === 'indicadores-tab') {
            // --- VISTA INDICADORES ---
            cargarIndicadoresPro();
        } else {
            // --- VISTA PENDIENTES (FRANQUICIAS) ---
            $('#summary-cards').show(); // Mostramos los KPIs de deuda total
            $('#btn-abrir-parametros').show(); // Mostramos el botón de parámetros
            initializeDataTable('#tabla-franquicias', 'api/cobranzas_controller.php?tipo=franquicias');
        }
    });

    // --- LÓGICA PARA ABRIR Y CARGAR EL MODAL DE DETALLE (CREACIÓN DE PROPUESTA) ---
    $('.tab-content').on('click', '.btn-detalle', function () {
        const codClient = $(this).data('cod-client');
        const razonSoci = $(this).data('razon-soci');
        const tipo = $('.nav-tabs .nav-link.active').attr('id').includes('franquicias') ? 'franquicias' : 'mayoristas';
        const urlDetalle = `api/cobranzas_controller.php?tipo=${tipo}&cod_client=${codClient}&razon_soci=${encodeURIComponent(razonSoci)}`;

        $('#nombreClienteModal').text(razonSoci);

        // ======================= ESTA LÍNEA ES LA CLAVE =======================
        // Guardamos el código del cliente en el propio modal para poder leerlo después
        $('#detalleClienteModal').data('cod-cliente', codClient);
        // =====================================================================

        const container = $('#detalle-content-container');

        container.html('<div class="d-flex justify-content-center align-items-center" style="min-height: 250px;"><div class="spinner-border text-primary" role="status"></div></div>');
        $('#btn-enviar-propuesta').prop('disabled', true);
        const detalleModal = new bootstrap.Modal(document.getElementById('detalleClienteModal'));
        detalleModal.show();

        $.ajax({
            url: urlDetalle,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                container.empty();
                // ** NUEVO **: Añadimos 3 contenedores en el footer del modal
                container.append('<table id="tabla-detalle-cliente" class="table table-striped table-hover" style="width:100%"></table>');
                $('#detalleClienteModal .modal-footer').prepend(`
                    <div id="footer-resumen" class="d-flex justify-content-between w-100 align-items-center">
                        <div id="total-bruto-container"><strong>Total Bruto:</strong> <span class="fw-normal">$ 0,00</span></div>
                        <div id="total-neto-container"><strong>Total Neto:</strong> <span class="fw-normal">$ 0,00</span></div>
                        <div id="fecha-promedio-container"><strong>F.P. Cobro Prom.:</strong> <span class="fw-normal">N/A</span></div>
                    </div>
                `);

                $('#tabla-detalle-cliente').DataTable({
                    data: response.data,
                    columns: columnsDetalle,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                    order: [[1, 'desc']],
                    dom: 'Bfrtip',
                    buttons: [{ extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Exportar', className: 'btn btn-success btn-sm' }],
                    destroy: true, paging: false, info: false
                });

                // ** NUEVO **: Llamamos a la nueva función de totales
                actualizarTotalesPropuesta();
            },
            error: function (xhr, status, error) {
                console.error("Error al cargar detalle:", { xhr, status, error });
                container.html(`<div class="alert alert-danger">Error al cargar los datos. Detalles: ${error || 'Error desconocido'}. Verifique la consola.</div>`);
            }
        });
    });

    // *** AÑADIDO ***: Limpiar el total del footer cuando se cierra el modal
    $('#detalleClienteModal').on('hidden.bs.modal', function () {
        // Ahora borramos el contenedor principal de los resúmenes
        $('#footer-resumen').remove();
    });

    // *** NUEVA FUNCIÓN MEJORADA ***: Para recalcular Bruto, Neto y Fecha Promedio
    function actualizarTotalesPropuesta() {
        let totalBruto = 0;
        let totalNeto = 0;
        let fechas = [];
        const tablaDetalle = $('#tabla-detalle-cliente').DataTable();

        $('#tabla-detalle-cliente .invoice-checkbox:checked').each(function () {
            const tr = $(this).closest('tr');
            const rowData = tablaDetalle.row(tr).data();
            const tComp = rowData.T_COMP.trim();
            const estado = rowData.ESTADO.trim();
            const esNegativo = tComp.startsWith('NC');

            let bruto = parseFloat(rowData.IMPORTE);
            // Leemos el neto de la celda, que ya está calculado y tiene el signo correcto
            let neto = parseFloat(tr.find('.importe-neto-cell').text().replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0;

            totalBruto += esNegativo ? -bruto : bruto;
            totalNeto += neto;

            if (rowData.FECHA_PROB_COBRO) {
                // Parseamos la fecha correctamente. Asumimos formato AAAA-MM-DD.
                const parts = rowData.FECHA_PROB_COBRO.split('-');
                if (parts.length === 3) {
                    // new Date(año, mes - 1, día)
                    fechas.push(new Date(parts[0], parts[1] - 1, parts[2]).getTime());
                }
            }
        });

        // Calcular fecha promedio
        let fechaPromedioStr = 'N/A';
        if (fechas.length > 0) {
            const promedioTimestamp = fechas.reduce((a, b) => a + b, 0) / fechas.length;
            const fechaPromedio = new Date(promedioTimestamp);
            fechaPromedioStr = fechaPromedio.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }

        // Función para formatear a moneda
        const f = (num) => num.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

        // Actualizamos los nuevos contenedores
        $('#total-bruto-container span').text(f(totalBruto));
        $('#total-neto-container span').text(f(totalNeto));
        $('#fecha-promedio-container span').text(fechaPromedioStr);
    }

    // *** NUEVO EVENTO ***: Para recalcular el neto de una fila cuando cambia el descuento
    $('#detalleClienteModal').on('input', '.descuento-input', function () {
        const input = $(this);
        const tr = input.closest('tr');
        const tablaDetalle = $('#tabla-detalle-cliente').DataTable();
        const rowData = tablaDetalle.row(tr).data();

        if (!rowData) return;

        const importeBruto = parseFloat(rowData.IMPORTE);
        let descuento = parseFloat(input.val());

        if (isNaN(descuento) || descuento < 0) descuento = 0;
        if (descuento > 100) {
            descuento = 100;
            input.val(100);
        }

        // ======================= INICIO DE LA MODIFICACIÓN A00115 =======================
        const tipoComp = rowData.T_COMP.trim();
        const nComp = (rowData.N_COMP || '').trim();

        // Lógica especial para la sucursal A00115
        if (nComp.startsWith('A00115')) {
            if (tipoComp === 'FAC') {
                descuento = 0;
                input.val('0.00').prop('disabled', true);
            } else if (tipoComp === 'NCP') {
                input.prop('disabled', false);
            }
        } else {
            // El resto de NC/NCR y FAC de otras sucursales siguen siendo editables
            input.prop('disabled', false);
        }
        // ======================== FIN DE LA MODIFICACIÓN A00115 =========================

        const estadoComp = (rowData.ESTADO || '').trim();

        // Forzamos el importe neto basado en el descuento
        let importeNeto = importeBruto * (1 - (descuento / 100));

        // Si es Nota de Crédito, el neto también es negativo
        if (tipoComp.startsWith('NC')) {
            // Aseguramos que sea negativo independientemente de si el importeBruto ya lo era
            importeNeto = Math.abs(importeNeto) * -1;
        } else {
            importeNeto = Math.abs(importeNeto);
        }

        const numeroFormateado = $.fn.dataTable.render.number('.', ',', 2, '$ ').display(importeNeto);
        tr.find('.importe-neto-cell').html(`<span class="${importeNeto < 0 ? 'text-danger fw-bold' : ''}">${numeroFormateado}</span>`);

        actualizarTotalesPropuesta();
    });

    function actualizarEstadoBotonPropuesta() {
        const seleccionados = $('.invoice-checkbox:checked').length;
        $('#btn-enviar-propuesta').prop('disabled', seleccionados === 0);
    }

    // *** MODIFICADO ***: Eventos de selección ahora también actualizan el total
    $('#detalleClienteModal').on('click', '#select-all-invoices', function () {
        $('.invoice-checkbox').prop('checked', this.checked);
        actualizarEstadoBotonPropuesta();
        actualizarTotalesPropuesta();
    });

    $('#detalleClienteModal').on('change', 'input[type="checkbox"]', function () {

        // Si se hizo clic en el checkbox "seleccionar todo"
        if ($(this).is('#select-all-invoices')) {
            // Sincronizamos todos los checkboxes de las filas con el estado del principal
            $('.invoice-checkbox').prop('checked', this.checked);
        } else {
            // Si se desmarca una fila, nos aseguramos de que "seleccionar todo" también se desmarque.
            if (!this.checked) {
                $('#select-all-invoices').prop('checked', false);
            }
        }

        // Actualizamos el estado del botón de envío
        const seleccionados = $('.invoice-checkbox:checked').length;
        $('#btn-enviar-propuesta').prop('disabled', seleccionados === 0);

        // Finalmente, recalculamos los totales
        actualizarTotalesPropuesta();
    });

    $('#btn-enviar-propuesta').on('click', function () {
        // ======================= ESTA LÍNEA ES LA CORRECCIÓN =======================
        // Leemos el código del cliente desde el modal, donde lo guardamos antes
        const codCliente = $('#detalleClienteModal').data('cod-cliente');
        // =========================================================================
        const btn = $(this);

        let comprobantesSeleccionados = [];
        let totalNetoSeleccionado = 0;
        const tablaDetalle = $('#tabla-detalle-cliente').DataTable();

        $('#tabla-detalle-cliente .invoice-checkbox:checked').each(function () {
            const tr = $(this).closest('tr');
            const rowData = tablaDetalle.row(tr).data();

            if (rowData) {
                const descuento = parseFloat(tr.find('.descuento-input').val()) || 0;
                const importeBrutoOriginal = parseFloat(rowData.IMPORTE);
                let importeNetoRecalculado = importeBrutoOriginal * (1 - (descuento / 100));
                const tComp = rowData.T_COMP.trim();
                const estado = rowData.ESTADO.trim();
                const esNegativo = tComp.startsWith('NC');

                comprobantesSeleccionados.push({
                    t_comp: rowData.T_COMP,
                    n_comp: rowData.N_COMP,
                    importe_bruto: importeBrutoOriginal,
                    importe_neto: importeNetoRecalculado,
                    porcentaje_descuento: descuento
                });

                totalNetoSeleccionado += esNegativo ? -importeNetoRecalculado : importeNetoRecalculado;
            }
        });

        if (comprobantesSeleccionados.length === 0) {
            Swal.fire('Atención', 'Por favor, seleccione al menos un comprobante.', 'warning');
            return;
        }

        // Obtenemos el medio de pago por defecto del cliente (si está configurado)
        const firstRow = tablaDetalle.row($('#tabla-detalle-cliente .invoice-checkbox:checked').first().closest('tr')).data();
        const defaultMedio = (firstRow && firstRow.MEDIO_PAGO_DEFAULT) ? firstRow.MEDIO_PAGO_DEFAULT.toUpperCase() : 'ECHECK';

        Swal.fire({
            title: 'Finalizar Propuesta',
            width: '600px',
            html: `
            <p class="mb-3">Monto Total de la Propuesta: <strong class="fs-5 text-primary" id="swal-total-display">${totalNetoSeleccionado.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</strong></p>
            
            <div class="row g-3 text-start">
                <div class="col-md-6">
                    <label for="swal-medio-pago" class="form-label fw-bold">Medio de Pago</label>
                    <select id="swal-medio-pago" class="form-select shadow-sm">
                        <option value="ECHECK" ${defaultMedio === 'ECHECK' ? 'selected' : ''}>ECHECK</option>
                        <option value="TRANSFERENCIA" ${defaultMedio.includes('TRANSFERENCIA') || defaultMedio.includes('TRANSFERERENCIA') ? 'selected' : ''}>TRANSFERENCIA</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="swal-cantidad-cuotas" class="form-label fw-bold">Plan de Pagos</label>
                    <select id="swal-cantidad-cuotas" class="form-select shadow-sm">
                        <option value="1" selected>1 Pago Único</option>
                        <option value="2">2 Pagos</option>
                        <option value="3">3 Pagos</option>
                        <option value="4">4 Pagos</option>
                        <option value="5">5 Pagos</option>
                        <option value="6">6 Pagos</option>
                    </select>
                </div>
            </div>

            <div class="mb-3 mt-3 text-start">
                <label class="form-label fw-bold">Fecha Límite de la Propuesta</label>
                <div id="datepicker-container-swal" class="border rounded bg-white shadow-sm"></div>
                <input type="hidden" id="fecha-propuesta-swal-input">
            </div>

            <div id="cuotas-container" class="mt-4 p-3 bg-light border rounded" style="display:none;">
                <h6 class="border-bottom pb-2 mb-3"><i class="fa-solid fa-list-ol me-2"></i>Desglose de Cobranza</h6>
                <div id="cuotas-list"></div>
                <div class="mt-2 text-end small">
                    <span id="suma-cuotas-test" class="badge bg-secondary">Total: $ 0.00</span>
                </div>
            </div>
        `,
            confirmButtonText: 'Enviar Propuesta',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                const $datepicker = $("#datepicker-container-swal");
                const $inputFecha = $('#fecha-propuesta-swal-input');
                const $cuotasContainer = $('#cuotas-container');
                const $cuotasList = $('#cuotas-list');
                const $cantCuotas = $('#swal-cantidad-cuotas');

                $datepicker.datepicker({
                    dateFormat: "yy-mm-dd",
                    minDate: 0,
                    onSelect: function (dateText) {
                        $inputFecha.val(dateText).trigger('change'); // Disparamos el cambio
                        // Validar las fechas de las cuotas si ya existen
                        $('.cuota-fecha').each(function () {
                            $(this).attr('max', dateText); // Sincronizamos el máximo permitido
                            const val = $(this).val();
                            if (val && val > dateText) {
                                $(this).addClass('is-invalid');
                            } else {
                                $(this).removeClass('is-invalid');
                            }
                        });
                    }
                });

                const hoy = new Date();
                $datepicker.datepicker('setDate', hoy);
                $inputFecha.val($.datepicker.formatDate('yy-mm-dd', hoy));

                const $medioPagoSelect = $('#swal-medio-pago');

                // --- Lógica para ajustar descuentos según Medio de Pago ---
                $('#swal-medio-pago').on('change', function () {
                    const medio = $(this).val();
                    let cambioRelativo = false;

                    $('#tabla-detalle-cliente .invoice-checkbox:checked').each(function () {
                        const tr = $(this).closest('tr');
                        const input = tr.find('.descuento-input');
                        let currentDesc = parseFloat(input.val());

                        // Regla: 8% para ECHECK, 6% para TRANSFERENCIA (usamos redondeo para evitar decimales flotantes)
                        let simplifiedDesc = Math.round(currentDesc * 100) / 100;

                        // Solo automatizamos si el descuento es el 'estándar'. Si el usuario puso 0 o un valor manual, lo respetamos.
                        // Cambio 8 -> 6 al pasar a Transferencia
                        if (medio === 'TRANSFERENCIA' && simplifiedDesc > 7.5 && simplifiedDesc < 8.5) {
                            input.val(6).trigger('input');
                            cambioRelativo = true;
                        }
                        // Cambio 6 -> 8 al pasar a Echeck
                        else if (medio === 'ECHECK' && simplifiedDesc > 5.5 && simplifiedDesc < 6.5) {
                            input.val(8).trigger('input');
                            cambioRelativo = true;
                        }
                    });

                    if (cambioRelativo) {
                        // Recalculamos el total capturando el valor del footer que ya se actualizó por el trigger('input')
                        const nuevoTotalTexto = $('#total-neto-container span').text();
                        $('#swal-total-display').text(nuevoTotalTexto);

                        // Actualizamos la variable local de referencia para el preConfirm y cuotas
                        totalNetoSeleccionado = parseFloat(nuevoTotalTexto.replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0;

                        // Si hay cuotas, refrescamos el desglose
                        const cant = parseInt($cantCuotas.val());
                        if (cant > 1) {
                            generarCamposCuotas(cant, totalNetoSeleccionado, $inputFecha.val());
                        }
                    }
                });

                // Disparamos manualmente el cambio al abrir para aplicar reglas si el default ya es Transferencia
                setTimeout(() => {
                    $medioPagoSelect.trigger('change');
                }, 100);

                $cantCuotas.on('change', function () {
                    const cant = parseInt($(this).val());
                    if (cant > 1) {
                        $cuotasContainer.show();
                        generarCamposCuotas(cant, totalNetoSeleccionado, $inputFecha.val());
                    } else {
                        $cuotasContainer.hide();
                    }
                });

                function generarCamposCuotas(cantidad, total, fechaLimite) {
                    $cuotasList.empty();
                    const montoIndividual = (total / cantidad).toFixed(2);
                    let acumulado = 0;

                    for (let i = 1; i <= cantidad; i++) {
                        // Ajuste para la última cuota para evitar errores de decimales
                        const monto = (i === cantidad) ? (total - acumulado).toFixed(2) : montoIndividual;
                        acumulado += parseFloat(monto);

                        const cuotaHtml = `
                        <div class="row g-2 mb-2 align-items-center cuota-row">
                            <div class="col-1 text-center fw-bold text-muted">${i}.</div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control cuota-monto shadow-none" value="${monto}" step="0.01" readonly>
                                </div>
                            </div>
                            <div class="col-5">
                                <input type="date" class="form-control form-control-sm cuota-fecha shadow-none" value="${fechaLimite}" max="${fechaLimite}">
                            </div>
                        </div>`;
                        $cuotasList.append(cuotaHtml);
                    }
                    actualizarTestSuma();
                }

                function actualizarTestSuma() {
                    let suma = 0;
                    $('.cuota-monto').each(function () { suma += parseFloat($(this).val()) || 0; });
                    $('#suma-cuotas-test').text('Suma: ' + suma.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));
                }

                // Si se cambia la fecha límite, actualizar el max de las cuotas
                $inputFecha.on('change', function () {
                    $('.cuota-fecha').attr('max', $(this).val());
                });
            },
            preConfirm: () => {
                const fecha = $('#fecha-propuesta-swal-input').val();
                const medioPago = $('#swal-medio-pago').val();
                const cantCuotas = parseInt($('#swal-cantidad-cuotas').val());

                if (!fecha) {
                    Swal.showValidationMessage('Por favor, seleccione una fecha límite.');
                    return false;
                }

                let cuotasArr = [];
                if (cantCuotas > 1) {
                    let totalCuotas = 0;
                    let errorFechas = false;
                    $('.cuota-row').each(function (index) {
                        const m = parseFloat($(this).find('.cuota-monto').val());
                        const f = $(this).find('.cuota-fecha').val();

                        if (!f) {
                            errorFechas = true;
                            return false;
                        }

                        if (f > fecha) {
                            errorFechas = true;
                            Swal.showValidationMessage(`La cuota ${index + 1} excede la fecha de la propuesta.`);
                            return false;
                        }

                        totalCuotas += m;
                        cuotasArr.push({ num_cuota: index + 1, monto: m, fecha_vencimiento: f });
                    });

                    if (errorFechas && !Swal.getValidationMessage()) {
                        Swal.showValidationMessage('Complete todas las fechas de las cuotas.');
                        return false;
                    }

                    // Pequeño margen por decimales
                    const totalDiferencia = Math.abs(totalCuotas - totalNetoSeleccionado);
                    if (totalDiferencia > 0.5) { // Un margen un poco mayor por redondeos masivos
                        Swal.showValidationMessage('La suma de las cuotas debe coincidir con el total.');
                        return false;
                    }
                }

                // --- RE-RECOLECTAMOS LOS DATOS JUSTO ANTES DE ENVIAR ---
                // (Para capturar los cambios de descuento hechos por el cambio de Medio de Pago)
                let comprobantesFinales = [];
                let totalRelativoFinal = 0;
                const tablaDetalleInner = $('#tabla-detalle-cliente').DataTable();

                $('#tabla-detalle-cliente .invoice-checkbox:checked').each(function () {
                    const tr = $(this).closest('tr');
                    const rowData = tablaDetalleInner.row(tr).data();
                    const desc = parseFloat(tr.find('.descuento-input').val()) || 0;
                    const bruto = parseFloat(rowData.IMPORTE);
                    const neto = bruto * (1 - (desc / 100));
                    const tComp = rowData.T_COMP.trim();
                    const estado = rowData.ESTADO.trim();
                    const esNegativo = tComp.startsWith('NC') || (tComp === 'REC' && estado === 'CTA');

                    comprobantesFinales.push({
                        t_comp: rowData.T_COMP,
                        n_comp: rowData.N_COMP,
                        importe_bruto: bruto,
                        importe_neto: neto,
                        porcentaje_descuento: desc
                    });
                    totalRelativoFinal += esNegativo ? -neto : neto;
                });

                return {
                    fecha: fecha,
                    medioPago: medioPago,
                    cuotas: cuotasArr,
                    comprobantes: comprobantesFinales,
                    total_final: totalRelativoFinal
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { fecha, medioPago, cuotas, comprobantes, total_final } = result.value;
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: 'api/cobranzas_controller.php?action=crear_propuesta',
                    type: 'POST',
                    data: {
                        cod_cliente: codCliente,
                        comprobantes: comprobantes,
                        total_propuesto: total_final,
                        fecha_propuesta_pago: fecha,
                        medio_de_pago: medioPago,
                        cuotas: cuotas
                    },
                    dataType: 'json',
                    timeout: 60000,
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: response.message
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        if (status === 'timeout' || xhr.status === 0) {
                            Swal.fire({
                                title: '⚠️ Verificando Envío',
                                text: 'La conexión demoró más de lo esperado. Comprobando si la propuesta se generó...',
                                icon: 'info',
                                timer: 4000,
                                showConfirmButton: false,
                                didOpen: () => { Swal.showLoading(); }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            console.error("Error AJAX:", xhr.responseText);
                            Swal.fire('Error de Comunicación', 'No se pudo completar la solicitud. Por favor intenta nuevamente.', 'error');
                        }
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-2"></i> Enviar Propuesta');
                    }
                });
            }
        });
    });

    // --- LÓGICA PARA EL MODAL DE PARÁMETROS (Sin cambios) ---
    let parametrosTable = null;
    function showAlert(message, type = 'success') {
        const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                              ${message}
                              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                           </div>`;
        $('#alert-container').html(alertHtml);
    }
    function cargarParametros() {
        const url = 'api/parametros_controller.php?action=read';
        if (parametrosTable) {
            parametrosTable.ajax.url(url).load();
        } else {
            parametrosTable = $('#tabla-parametros').DataTable({
                ajax: { url: url, dataSrc: 'data' },
                columns: [
                    { data: 'ID', title: 'ID' },
                    { data: 'COD_CLIENT', title: 'Cód. Cliente' },
                    { data: 'MEDIO_PAGO_DEFAULT', title: 'Medio Pago Def.' },
                    { data: 'DIAS_PP_MAX', title: 'Días PP Máx.', className: 'text-center' },
                    {
                        data: 'DESC_PP_MAX',
                        title: 'Desc. PP Máx.',
                        className: 'text-end',
                        render: function (data) {
                            // Convertimos de 0.08 a 8.00 %
                            return (parseFloat(data) * 100).toFixed(2) + ' %';
                        }
                    },
                    {
                        data: null, title: 'Acciones', orderable: false, className: 'text-center',
                        render: function (data, type, row) {
                            return `<button class="btn btn-warning btn-sm me-1 btn-editar" data-id="${row.ID}" title="Editar"><i class="fa-solid fa-pencil"></i></button>
                                    <button class="btn btn-danger btn-sm btn-eliminar" data-id="${row.ID}" title="Eliminar"><i class="fa-solid fa-trash"></i></button>`;
                        }
                    }
                ],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                responsive: true,
                autoWidth: false,
                order: [[1, 'asc']]
            });
        }
    }
    $('#btn-abrir-parametros').on('click', function () {
        $('#alert-container').html('');
        resetFormularioParametros();
        cargarParametros();
    });
    $('#form-parametros').on('submit', function (e) {
        e.preventDefault();
        const action = $('#param-id').val() ? 'update' : 'create';
        // Construimos el FormData correctamente con los nuevos nombres
        let formData = {
            action: action,
            id: $('#param-id').val(),
            cod_client: $('#param-cod-client').val(),
            medio_pago: $('#param-medio-pago').val(),
            dias_pp_max: $('#param-dias-pp').val(),
            desc_pp_max: $('#param-desc-pp').val()
        };

        $.ajax({
            url: 'api/parametros_controller.php',
            type: 'POST',
            data: formData, // Enviamos el objeto
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    resetFormularioParametros();
                    parametrosTable.ajax.reload();
                } else {
                    showAlert('Error: ' + response.message, 'danger');
                }
            },
            error: function () {
                showAlert('Error de conexión con el servidor.', 'danger');
            }
        });
    });
    $('#tabla-parametros').on('click', '.btn-editar', function () {
        const data = parametrosTable.row($(this).parents('tr')).data();
        $('#form-title').text('Editar Parámetro');
        $('#param-id').val(data.ID);
        $('#param-cod-client').val(data.COD_CLIENT);
        $('#param-medio-pago').val(data.MEDIO_PAGO_DEFAULT);
        $('#param-dias-pp').val(data.DIAS_PP_MAX);
        // Convertimos de 0.08 a 8.00 para el input
        $('#param-desc-pp').val((parseFloat(data.DESC_PP_MAX) * 100).toFixed(2));
        $('#btn-cancelar-edicion').show();
        $('.btn-text').text('Actualizar');
    });
    $('#tabla-parametros').on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        $('#btn-confirmar-delete').data('id', id);
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        confirmModal.show();
    });
    $('#btn-confirmar-delete').on('click', function () {
        const id = $(this).data('id');
        if (id) {
            $.ajax({
                url: 'api/parametros_controller.php', type: 'POST', data: { action: 'delete', id: id }, dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showAlert(response.message, 'info');
                        parametrosTable.ajax.reload();
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                complete: function () {
                    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
                    confirmModal.hide();
                }
            });
        }
    });
    $('#btn-cancelar-edicion').on('click', function () {
        resetFormularioParametros();
    });
    function resetFormularioParametros() {
        $('#form-title').text('Agregar Nuevo Parámetro');
        $('#form-parametros')[0].reset();
        $('#param-id').val('');
        $('#btn-cancelar-edicion').hide();
        $('.btn-text').text('Guardar');
        $('#alert-container').html('');
    }

    // ========================================================================
    //  LÓGICA PARA LA PESTAÑA DE GESTIÓN DE PROPUESTAS (ADMIN)
    // ========================================================================
    let tablaGestion = null;

    function initializeGestionDataTable() {
        $('#summary-cards').hide();
        $('#gestion-dashboard').show();
        $('#btn-abrir-parametros').hide();

        cargarDashboardGestion();
        sincronizarEstados();

        // Siempre (re)creamos la tabla para asegurar que las nuevas definiciones de columnas se apliquen
        tablaGestion = $('#tabla-gestion-propuestas').DataTable({
            destroy: true, // Importante: permite actualizar la definición de la tabla sin refrescar la página
            ajax: {
                url: 'api/propuestas_controller.php?action=listar_admin',
                dataSrc: 'data',
                data: function (d) {
                    d.f_desde = $('#filter-fecha-desde').val();
                    d.f_hasta = $('#filter-fecha-hasta').val();
                    d.codigo = $('#filter-codigo').val();
                    d.razon = $('#filter-razon-social').val();
                    d.estado = $('#filter-estado').val();
                }
            },
            columns: [
                { data: 'id', title: 'ID', className: 'text-center fw-bold' },
                { data: 'cod_cliente', title: 'Código', className: 'text-center' },
                { data: 'razon_social', title: 'Razón Social' },
                { data: 'total_propuesto', title: 'Monto', render: $.fn.dataTable.render.number('.', ',', 2, '$ '), className: 'text-end fw-bold' },
                {
                    data: 'dias_plazo',
                    title: 'Plazo',
                    type: 'num',
                    className: 'text-center',
                    render: function (data, type, row) {
                        if (data === null || data === undefined) {
                            return type === 'sort' ? -1 : '-';
                        }
                        const diffDays = parseInt(data);
                        if (type === 'sort') return diffDays;

                        let colorClass = 'text-muted';
                        if (diffDays > 30) colorClass = 'text-danger fw-bold';
                        else if (diffDays > 15) colorClass = 'text-warning fw-bold';
                        else if (diffDays > 0) colorClass = 'text-success fw-bold';

                        return `<span class="${colorClass}">${diffDays} días</span>`;
                    }
                },
                {
                    data: 'fecha_ultima_modificacion',
                    title: 'Últ. Act.',
                    render: function (data) {
                        return data ? new Date(data).toLocaleString('es-AR') : '-';
                    }
                },
                {
                    data: 'estado', title: 'Estado',
                    render: function (data) {
                        let badgeClass = 'secondary';
                        if (data === 'PAGADO') badgeClass = 'success';
                        if (data === 'DOCUMENTACION_ADJUNTADA') badgeClass = 'dark';
                        if (data === 'CONTRAPROPUESTA_CLIENTE') badgeClass = 'primary';
                        if (data === 'PENDIENTE_APROBACION_CLIENTE') badgeClass = 'warning text-dark';
                        if (data === 'PENDIENTE_APROBACION_FINAL') badgeClass = 'info';
                        if (data === 'ACEPTADA') badgeClass = 'success';
                        return `<span class="badge bg-${badgeClass}">${data.replace(/_/g, ' ')}</span>`;
                    }
                },
                {
                    data: null, title: 'Acciones', orderable: false, className: 'text-center',
                    render: function (data, type, row) {
                        return `
                        <div class="btn-group">
                            <button class="btn btn-outline-info btn-sm btn-ver-propuesta-admin" data-id="${row.id}" title="Revisar / Editar">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm btn-eliminar-propuesta" data-id="${row.id}" title="Eliminar definitivamente">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>`;
                    }
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
            order: [[4, 'desc']], // Por defecto, ordenamos por la columna 'Plazo' (índice 4) Descendente
            responsive: true,
            drawCallback: function (settings) {
                const api = this.api();
                const filteredData = api.rows({ filter: 'applied' }).data().toArray();
                let totalDias = 0;
                let count = 0;

                // Definimos los estados que consideramos "Aceptados en adelante"
                const estadosValidos = ['ACEPTADA', 'DOCUMENTACION_ADJUNTADA', 'PAGADO'];

                filteredData.forEach(row => {
                    if (row.dias_plazo !== null && row.dias_plazo !== undefined && estadosValidos.includes(row.estado)) {
                        totalDias += parseInt(row.dias_plazo);
                        count++;
                    }
                });
                const promedio = count > 0 ? (totalDias / count).toFixed(1) : '0';
                $('#kpi-promedio-plazo').text(promedio);
            }
        });
    }

    // Eventos para los botones de filtrado (Agrega esto al final del $(document).ready)
    $('#btn-aplicar-filtros').on('click', function () {
        if (tablaGestion) tablaGestion.ajax.reload();
    });

    $('#btn-limpiar-filtros').on('click', function () {
        $('#filter-form-gestion')[0].reset();
        if (tablaGestion) tablaGestion.ajax.reload();
    });

    $('body').on('click', '.btn-ver-propuesta-admin', function () {
        const idPropuesta = $(this).data('id');

        // Si el modal del día está abierto, lo cerramos para evitar superposición
        const cronogramaModal = bootstrap.Modal.getInstance(document.getElementById('cronogramaDiaModal'));
        if (cronogramaModal) {
            cronogramaModal.hide();
        }

        const modal = new bootstrap.Modal(document.getElementById('detallePropuestaModal'));
        const contentDiv = $('#detalle-propuesta-content');

        contentDiv.html('<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>');
        $('#detallePropuestaModalLabel').text(`Revisar Propuesta #${idPropuesta}`);
        $('#detallePropuestaModal .modal-footer button').hide();
        $('#detallePropuestaModal .modal-footer button[data-bs-dismiss="modal"]').show();
        modal.show();

        $.ajax({
            url: `api/propuestas_controller.php?action=ver_detalle&id=${idPropuesta}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    renderizarDetallePropuestaAdmin(response.data);
                } else {
                    contentDiv.html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            },
            error: function () {
                contentDiv.html('<div class="alert alert-danger">Error al cargar los detalles.</div>');
            }
        });
    });

    function enviarAvisosVencimiento() {
        // Para evitar ejecutar esto en cada clic, guardamos una marca de tiempo en la sesión del navegador
        const ultimaVerificacion = sessionStorage.getItem('ultimaVerificacionVencimiento');
        const ahora = new Date().getTime();

        // Si no ha pasado al menos 1 hora (3600000 milisegundos), no hacemos nada.
        if (ultimaVerificacion && (ahora - ultimaVerificacion < 3600000)) {
            console.log('Avisos de vencimiento ya verificados recientemente. Omitiendo.');
            return;
        }

        console.log('Ejecutando verificación de avisos de vencimiento...');

        // Hacemos la llamada AJAX en segundo plano, sin molestar al usuario
        $.ajax({
            url: 'api/propuestas_controller.php?action=enviar_avisos_vencimiento',
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // Guardamos la marca de tiempo de la verificación exitosa
                    sessionStorage.setItem('ultimaVerificacionVencimiento', ahora);
                    console.log(response.message);
                    // Si se enviaron avisos, mostramos una notificación no intrusiva
                    if (response.message && !response.message.includes("enviaron 0")) {
                        // Usamos una notificación pequeña tipo "toast"
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'info',
                            title: response.message
                        });
                    }
                } else {
                    console.error('Error al enviar avisos de vencimiento:', response.message);
                }
            },
            error: function () {
                console.error('Error de conexión al intentar enviar avisos de vencimiento.');
            }
        });
    }
    // ======================== FIN DE LA CORRECCIÓN DEL EVENTO =========================
    // *** MODIFICADO ***: Renderiza el modal de gestión con descuentos editables si es necesario
    function renderizarDetallePropuestaAdmin(data) {
        const { propuesta, items, historial, adjuntos, cuotas } = data;
        const contentDiv = $('#detalle-propuesta-content');

        // --- Mantenemos tu código original para el resumen y lo mejoramos ---
        let fechaHtml = propuesta.fecha_propuesta_pago
            ? new Date(propuesta.fecha_propuesta_pago + 'T00:00:00').toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric' })
            : 'No definida';

        // Añadimos una tercera tarjeta para el Medio de Pago y ajustamos las columnas
        let resumenHtml = `
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-light shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted text-uppercase small">Total Propuesto</h6>
                        <p class="card-text fs-4 fw-bold text-primary mb-0" id="total-propuesto-kpi">${(parseFloat(propuesta.total_propuesto) || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted text-uppercase small">Fecha Límite Pago</h6>
                        <p class="card-text fs-4 fw-bold mb-0">${fechaHtml}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted text-uppercase small">Medio de Pago</h6>
                        <p class="card-text fs-4 fw-bold mb-0">${propuesta.medio_de_pago || 'N/A'}</p>
                    </div>
                </div>
            </div>
        </div>
    `;

        let cuotasHtml = `<div id="cuotas-container-admin">`;
        if (cuotas && cuotas.length > 0) {
            cuotasHtml += `<h5 class="mt-4"><i class="fa-solid fa-list-check me-2 text-success"></i>Esquema de Facilidades de Pago</h5><div class="row row-cols-1 row-cols-md-3 g-2 mb-3">`;
            cuotas.forEach(c => {
                const fVenc = new Date(c.fecha_vencimiento + 'T00:00:00').toLocaleDateString('es-AR');

                // Buscar adjuntos de esta cuota para el admin
                let adjuntosCuotaHtml = '';
                const adjuntosCuota = (adjuntos || []).filter(a => a.id_cuota == c.id);
                if (adjuntosCuota.length > 0) {
                    adjuntosCuotaHtml = '<div class="mt-2 border-top pt-1 text-start">';
                    adjuntosCuota.forEach(a => {
                        adjuntosCuotaHtml += `<div class="x-small d-flex justify-content-between align-items-center mb-1" style="font-size: 0.75rem;">
                            <span class="text-truncate" style="max-width: 120px;" title="${a.nombre_archivo}"><i class="fa-solid fa-file-invoice-dollar me-1 text-success"></i>${a.nombre_archivo}</span>
                            <div class="btn-group">
                                <a href="${a.ruta_archivo}" target="_blank" class="btn btn-xs btn-outline-primary" title="Ver archivo"><i class="fa-solid fa-eye"></i></a>
                                <button class="btn btn-xs btn-danger btn-eliminar-adjunto" data-id-adjunto="${a.id}" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>`;
                    });
                    adjuntosCuotaHtml += '</div>';
                }

                cuotasHtml += `
                <div class="col">
                    <div class="card border-success bg-white shadow-sm h-100">
                        <div class="card-body p-2 text-center">
                            <span class="badge bg-success mb-1">Pago ${c.num_cuota}</span>
                            <div class="fw-bold fs-6 cuota-monto-admin" data-original-monto="${c.monto}">${parseFloat(c.monto).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</div>
                            <div class="small text-muted cuota-fecha-admin">${fVenc}</div>
                            ${adjuntosCuotaHtml}
                        </div>
                    </div>
                </div>`;
            });
            cuotasHtml += `</div>`;
        }
        cuotasHtml += `</div>`;

        let adjuntosHtml = '';
        const adjuntosGenerales = (adjuntos || []).filter(a => !a.id_cuota);
        if (adjuntosGenerales.length > 0) {
            adjuntosHtml = `<h5 class="mt-4"><i class="fa-solid fa-folder-open me-2"></i>Documentación General</h5><ul class="list-group">`;
            adjuntosGenerales.forEach(adjunto => {
                const url = adjunto.ruta_archivo;
                const fechaSubida = new Date(adjunto.fecha_subida).toLocaleString('es-AR');
                adjuntosHtml += `<li class="list-group-item d-flex justify-content-between align-items-center"><div><i class="fa-solid fa-file-arrow-down me-2 text-primary"></i> ${adjunto.nombre_archivo}<small class="d-block text-muted">Subido el: ${fechaSubida}</small></div><div class="btn-group"><a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" title="Ver archivo"><i class="fa-solid fa-eye"></i></a><button class="btn btn-sm btn-danger shadow-sm btn-eliminar-adjunto" data-id-adjunto="${adjunto.id}" title="Eliminar"><i class="fa-solid fa-trash"></i></button></div></li>`;
            });
            adjuntosHtml += '</ul>';
        }

        let esEditable = propuesta.estado === 'CONTRAPROPUESTA_CLIENTE';
        let puedeEditarDescuento = esEditable && (typeof globalUsuarioNombre !== 'undefined' && globalUsuarioNombre === 'SilviaF');
        let itemsHtml = `<h5 class="mt-4">Facturas Incluidas</h5><table class="table table-sm table-bordered" id="tabla-detalle-propuesta-admin"><thead class="table-light"><tr><th class="text-center">Fecha</th><th class="text-center">Tipo</th><th>Comprobante</th><th class="text-end">Importe Bruto</th><th class="text-center">% Descuento</th><th class="text-end">Importe Neto</th>${esEditable ? '<th class="text-center">Acciones</th>' : ''}</tr></thead><tbody>`;

        let totalBrutoTabla = 0;
        let totalNetoTabla = 0;

        items.forEach(item => {
            const bruto = parseFloat(item.importe_bruto) || 0;
            const neto = parseFloat(item.importe_neto) || 0;
            const descuento = parseFloat(item.porcentaje_descuento) || 0;
            const tComp = (item.t_comp_factura || '').trim();
            const nComp = (item.n_comp_factura || '').trim();
            // En la vista admin, si es REC asumimos que es negativo (porque solo entran los CTA)
            const esNegativo = tComp.startsWith('NC') || tComp === 'REC';

            // Usamos Math.abs para asegurar que no se invierta el signo si el bruto ya viene negativo de la BD
            const brutoReal = esNegativo ? -Math.abs(bruto) : Math.abs(bruto);
            const netoReal = esNegativo ? -Math.abs(neto) : Math.abs(neto);

            totalBrutoTabla += brutoReal;
            totalNetoTabla += netoReal;

            let inputDisabled = '';
            if (puedeEditarDescuento) {
                // Reglas de negocio A00115: Bloquear descuentos SOLO en FAC de la 115
                if (nComp.startsWith('A00115') && tComp === 'FAC') {
                    inputDisabled = 'disabled';
                }
            } else {
                inputDisabled = 'disabled';
            }

            const descuentoHtml = puedeEditarDescuento ? `<input type="number" class="form-control form-control-sm descuento-input-admin" value="${descuento.toFixed(2)}" min="0" max="100" step="0.01" style="width: 80px;" ${inputDisabled}>` : `<span class="descuento-texto-admin">${descuento.toFixed(2)} %</span><input type="hidden" class="descuento-input-admin" value="${descuento.toFixed(2)}">`;
            const accionesHtml = esEditable ? `<td class="text-center"><button class="btn btn-danger btn-sm btn-eliminar-factura-propuesta" title="Quitar factura"><i class="fa-solid fa-trash"></i></button></td>` : '';

            itemsHtml += `<tr data-importe-bruto="${brutoReal}" data-ncomp="${nComp}" data-tcomp="${tComp}"><td class="text-center">${item.fecha_emision || 'N/A'}</td><td class="text-center">${tComp || 'N/A'}</td><td>${nComp}</td><td class="text-end ${esNegativo ? 'text-danger' : ''}">${brutoReal.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td><td class="text-center">${descuentoHtml}</td><td class="text-end fw-bold importe-neto-cell-admin ${esNegativo ? 'text-danger' : ''}">${netoReal.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td>${accionesHtml}</tr>`;
        });

        itemsHtml += `</tbody><tfoot class="table-light"><tr><td colspan="3" class="text-end"><strong>Totales:</strong></td><td class="text-end fw-bolder" id="total-bruto-tabla">${totalBrutoTabla.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td><td></td><td class="text-end fw-bolder" id="total-neto-tabla">${totalNetoTabla.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td>${esEditable ? '<td></td>' : ''}</tr></tfoot></table>`;

        // ======================= INICIO DE LA MODIFICACIÓN DEL HISTORIAL =======================
        let historialHtml = '<h5>Historial</h5><div class="timeline">';
        historial.forEach(h => {
            const icon = h.tipo_usuario === 'ADMIN' ? 'fa-user-shield' : 'fa-user-tie';
            const align = h.tipo_usuario === 'ADMIN' ? 'left' : 'right';

            // Verificamos si hay una imagen adjunta para este evento del historial
            let adjuntoHtml = '';
            if (h.ruta_adjunto) {
                adjuntoHtml = `
                <div class="mt-2 historial-adjunto">
                    <a href="${h.ruta_adjunto}" target="_blank" title="Ver imagen adjunta">
                        <img src="${h.ruta_adjunto}" alt="Adjunto del historial" class="img-thumbnail" style="max-width: 150px; cursor: pointer;">
                    </a>
                </div>`;
            }

            // --- Lógica para mostrar el Snapshot (JSON) ---
            let snapshotBtn = '';
            if (h.json_data) {
                try {
                    const snap = JSON.parse(h.json_data);
                    const totalFmt = (parseFloat(snap.total) || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
                    const fechaFmt = snap.fecha ? snap.fecha.split(' ')[0] : 'N/A';

                    // Preparamos un string detallado para mostrar
                    let infoSnap = `Total: ${totalFmt} | Fecha: ${fechaFmt} | Medio: ${snap.medio_pago || 'N/A'}`;
                    if (snap.cuotas && snap.cuotas.length > 0) {
                        infoSnap += ` | Pagos: ${snap.cuotas.length}`;
                    }

                    snapshotBtn = `
                    <div class="mt-2 text-start">
                        <button class="btn btn-xs btn-outline-info p-1 px-2 border-0 bg-light btn-ver-snapshot" style="font-size: 0.7rem;" 
                                data-snapshot='${h.json_data.replace(/'/g, "&apos;")}'>
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Ver condiciones de esta versión
                        </button>
                    </div>`;
                } catch (e) {
                    console.error("Error parsing snapshot JSON", e);
                }
            }

            historialHtml += `
            <div class="timeline-item timeline-item-${align}">
                <div class="timeline-icon"><i class="fas ${icon}"></i></div>
                <div class="timeline-content">
                    <span class="timeline-date">${h.fecha_evento}</span>
                    <p><strong>${h.descripcion}</strong></p>
                    ${h.comentario ? `<p class="fst-italic bg-light p-2 rounded">"${h.comentario}"</p>` : ''}
                    ${adjuntoHtml}
                    ${snapshotBtn}
                </div>
            </div>`;
        });
        historialHtml += '</div>';

        // --- MANEJADOR GLOBAL PARA VER SNAPSHOTS (EN APP.JS) ---
        $(document).off('click', '.btn-ver-snapshot').on('click', '.btn-ver-snapshot', function (e) {
            e.preventDefault();
            try {
                const snap = JSON.parse($(this).attr('data-snapshot'));
                const f = (n) => (parseFloat(n) || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
                const fechaFmt = snap.fecha ? snap.fecha.split(' ')[0] : 'N/A';

                let html = `<div class='text-start small'>
                    <p><strong>Fecha de pago:</strong> ${fechaFmt}</p>
                    <p><strong>Monto Total:</strong> <span class='text-primary fw-bold'>${f(snap.total)}</span></p>
                    <p><strong>Medio:</strong> ${snap.medio_pago || 'N/A'}</p>`;

                if (snap.cuotas && snap.cuotas.length > 0) {
                    html += `<hr><p class='fw-bold mb-1'>Esquema de Pagos:</p>
                    <ul class='list-unstyled mb-0'>
                        ${snap.cuotas.map(c => `<li><i class='fa-solid fa-circle-check text-success me-1'></i> ${f(c.monto)} (${c.fecha_vencimiento})</li>`).join('')}
                    </ul>`;
                }

                html += `</div>`;

                Swal.fire({
                    title: 'Detalles de esta Versión',
                    html: html,
                    icon: 'info',
                    confirmButtonText: 'Cerrar',
                    width: '450px'
                });
            } catch (err) {
                console.error("Error al mostrar snapshot", err);
            }
        });
        // ======================== FIN DE LA MODIFICACIÓN DEL HISTORIAL ========================

        let accionAdminHtml = '';

        if (esEditable) {
            accionAdminHtml = `<div class="card bg-light border-primary mt-4"><div class="card-body"><h5 class="card-title">Acción Requerida: Contrapropuesta del Cliente</h5><p>El cliente ha propuesto nuevas condiciones. Revise los cambios y decida si aceptar la contrapropuesta.</p><div class="mb-3"><label for="comentario-admin" class="form-label"><strong>Añadir un comentario (Opcional):</strong> <small class="text-muted">(Puedes presionar Ctrl + V para pegar una captura)</small></label><textarea class="form-control" id="comentario-admin" rows="2" placeholder="Escribe tu comentario o pega una imagen aquí..."></textarea></div>                <div class="mb-3">
                    <label for="historial-adjunto-admin" class="form-label"><small>Adjuntar imagen (opcional):</small> <span id="badge-imagen-pegada" class="badge bg-success d-none ms-2"><i class="fa-solid fa-check"></i> Imagen capturada del portapapeles</span></label>
                    <input class="form-control form-control-sm" type="file" id="historial-adjunto-admin" accept="image/png, image/jpeg, image/gif">
                </div><button class="btn btn-success" id="btn-aceptar-contrapropuesta" data-id="${propuesta.id}"><i class="fa-solid fa-check-double me-2"></i> Aceptar Contrapropuesta</button></div></div>`;
        }

        const footer = $('#detallePropuestaModal .modal-footer');
        footer.find('#btn-exportar-excel').remove();
        footer.prepend('<button class="btn btn-success me-auto" id="btn-exportar-excel"><i class="fa-solid fa-file-excel me-2"></i>Exportar a Excel</button>');
        $('#btn-exportar-excel').show();

        contentDiv.html(resumenHtml + itemsHtml + cuotasHtml + historialHtml + adjuntosHtml + accionAdminHtml);
    }

    $('#detallePropuestaModal').on('click', '#btn-exportar-excel', function () {
        // Necesitamos los datos de la propuesta, que obtendremos de la misma llamada AJAX
        const idPropuesta = $('#detallePropuestaModalLabel').text().replace('Revisar Propuesta #', '');
        $.ajax({
            url: `api/propuestas_controller.php?action=ver_detalle&id=${idPropuesta}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    exportarPropuestaExcel(response.data);
                }
            }
        });
    });

    // *** NUEVO EVENTO ***: Pegar imagen en el comentario admin con Ctrl + V
    $('#detallePropuestaModal').off('paste', '#comentario-admin').on('paste', '#comentario-admin', function (e) {
        const items = (e.originalEvent || e).clipboardData.items;
        let imageF = null;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf("image") === 0) {
                imageF = items[i].getAsFile();
                break;
            }
        }
        
        if (imageF) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(imageF);
            // Inyectar secretamente el archivo pegado dentro de nuestro input de carga original
            document.getElementById('historial-adjunto-admin').files = dataTransfer.files;
            
            // Mostrar un indicador visual de que funcionó
            $('#badge-imagen-pegada').removeClass('d-none');
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '¡Imagen adjuntada!',
                text: 'La captura de tu portapapeles se enviará oculta y segura junto con tu respuesta.',
                showConfirmButton: false,
                timer: 3500
            });
        }
    });

    // *** NUEVO EVENTO ***: Lógica de recálculo para el modal de GESTIÓN
    $('#detallePropuestaModal').on('input', '.descuento-input-admin', function () {
        const input = $(this);
        const tr = input.closest('tr');
        const importeBruto = parseFloat(tr.data('importe-bruto'));
        const tipoComp = (tr.data('tcomp') || '').trim();
        const nComp = (tr.data('ncomp') || '').trim();
        let descuento = parseFloat(input.val());

        if (isNaN(descuento) || descuento < 0) descuento = 0;
        if (descuento > 100) {
            descuento = 100;
            input.val(100);
        }

        // ======================= REGLAS DE NEGOCIO A00115 EN GESTIÓN =======================
        if (nComp.startsWith('A00115')) {
            if (tipoComp === 'FAC') {
                descuento = 0;
                input.val('0.00').prop('disabled', true);
            }
        } else {
            // El resto son editables
            input.prop('disabled', false);
        }
        // ===================================================================================

        const esNC = tipoComp.startsWith('NC') || tipoComp === 'REC';
        const brutoBasePositivo = Math.abs(importeBruto);
        let importeNeto = brutoBasePositivo * (1 - (descuento / 100));
        
        // Asignamos el signo que corresponde a la nota de crédito o recibo
        if (esNC) importeNeto = -importeNeto;

        const cellNeto = tr.find('.importe-neto-cell-admin');
        cellNeto.text(importeNeto.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));

        if (esNC) {
            cellNeto.addClass('text-danger');
        } else {
            cellNeto.removeClass('text-danger');
        }

        recalcularTotalesAdmin();
    });

    $('#detallePropuestaModal').on('click', '#btn-aceptar-contrapropuesta', function () {
        const idPropuesta = $(this).data('id');
        const comentario = $('#comentario-admin').val();
        const btn = $(this);

        // ======================= INICIO DE LA RECOLECCIÓN DE DATOS =======================
        let comprobantesFinales = [];
        let totalNetoFinal = 0;

        $('#tabla-detalle-propuesta-admin tbody tr').each(function () {
            const tr = $(this);
            const bruto = parseFloat(tr.data('importe-bruto'));
            const descuento = parseFloat(tr.find('.descuento-input-admin').val()) || 0;
            const esNC = (tr.data('tcomp') || '').startsWith('NC') || (tr.data('tcomp') || '') === 'REC';
            
            const brutoBasePositivo = Math.abs(bruto);
            let neto = brutoBasePositivo * (1 - (descuento / 100));
            if (esNC) neto = -neto;

            comprobantesFinales.push({
                t_comp: tr.data('tcomp'),
                n_comp: tr.data('ncomp'),
                importe_bruto: bruto,
                importe_neto: neto,
                porcentaje_descuento: descuento
            });

            // Usamos el neto ya calculado para el total final
            totalNetoFinal += neto;
        });
        // ======================== FIN DE LA RECOLECCIÓN DE DATOS =========================

        Swal.fire({
            title: '¿Aceptar y guardar cambios?',
            html: `La propuesta cambiará a estado 'ACEPTADA' con un nuevo total de <strong>${totalNetoFinal.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</strong>. ¿Continuar?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, Aceptar y Guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true);

                const formData = new FormData();
                formData.append('id_propuesta', idPropuesta);
                formData.append('comentario', comentario);
                // --- Nuevos datos que enviamos ---
                formData.append('total_propuesto', totalNetoFinal);
                formData.append('comprobantes', JSON.stringify(comprobantesFinales));

                // Recolectar cuotas actualizadas con recálculo matemático seguro en el mismo acto (garantía de precisión sin parseos raros del DOM)
                const cuotasActualizadas = [];
                const $cuotasDom = $('#detallePropuestaModal .cuota-monto-admin');
                const numCuotasToSave = $cuotasDom.length;

                if (numCuotasToSave > 0) {
                    const montoPorCuota = Math.floor((totalNetoFinal / numCuotasToSave) * 100) / 100;
                    let totalAsignado = 0;

                    $cuotasDom.each(function (idx) {
                        let montoFinal;
                        if (idx === numCuotasToSave - 1) {
                            // La última cuota absorbe la diferencia de centavos
                            montoFinal = totalNetoFinal - totalAsignado;
                        } else {
                            montoFinal = montoPorCuota;
                            totalAsignado += montoFinal;
                        }

                        const fechaTxt = $(this).closest('.card-body').find('.cuota-fecha-admin').text().trim();
                        // Necesitamos la fecha en formato YYYY-MM-DD para el SQL
                        const partes = fechaTxt.split('/');
                        let fechaISO = '';
                        if (partes.length === 3) {
                            fechaISO = `${partes[2]}-${partes[1]}-${partes[0]}`;
                        } else {
                            fechaISO = fechaTxt;
                        }

                        cuotasActualizadas.push({
                            num_cuota: idx + 1,
                            monto: Math.round(montoFinal * 100) / 100,
                            fecha_vencimiento: fechaISO
                        });
                    });
                }
                formData.append('cuotas', JSON.stringify(cuotasActualizadas));

                const adjuntoFile = $('#historial-adjunto-admin')[0].files[0];
                if (adjuntoFile) {
                    formData.append('historial_adjunto', adjuntoFile);
                }

                $.ajax({
                    url: 'api/propuestas_controller.php?action=aceptar_contrapropuesta_admin',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    timeout: 60000,
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('¡Éxito!', response.message, 'success');
                            $('#detallePropuestaModal').modal('hide');
                            if (typeof tablaGestion !== 'undefined') tablaGestion.ajax.reload();
                            if (typeof cargarDashboardGestion === 'function') cargarDashboardGestion();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        if (status === 'timeout' || xhr.status === 0) {
                            Swal.fire({
                                title: '⚠️ Verificando Operación',
                                text: 'La conexión demoró más de lo esperado. Estamos comprobando si la acción se completó...',
                                icon: 'info',
                                timer: 3000,
                                showConfirmButton: false,
                                didOpen: () => { Swal.showLoading(); }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', 'No se pudo completar la acción. Revisa la consola o intenta de nuevo.', 'error');
                        }
                    },
                    complete: function () {
                        btn.prop('disabled', false);
                    }
                });
            }
        });
    });
    $('#detallePropuestaModal').on('click', '.btn-eliminar-factura-propuesta', function () {
        // Usamos SweetAlert para una mejor UX
        Swal.fire({
            title: '¿Quitar esta factura?',
            text: "La factura se eliminará solo de esta propuesta.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, quitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const tr = $(this).closest('tr');
                tr.fadeOut(300, function () {
                    $(this).remove();
                    // ===== LLAMADA A LA NUEVA FUNCIÓN DE RECÁLCULO =====
                    recalcularTotalesAdmin();
                });
            }
        });
    });
    // ======================= EVENTO DE SINCRONIZACIÓN CORREGIDO Y EN SU LUGAR =======================
    function sincronizarEstados() {
        // 1. Mostramos un modal de "Cargando..." no intrusivo
        Swal.fire({
            title: 'Sincronizando Estados',
            html: 'Buscando propuestas pagadas para actualizar...',
            timer: 2000, // Se cierra solo después de 2 segundos como máximo
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: 'api/propuestas_controller.php?action=sincronizar_estados_pagados',
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                // Cerramos el modal de "cargando"
                Swal.close();

                if (response.success) {
                    // 2. Si hubo cambios, mostramos un modal de éxito
                    if (response.message.includes("Se actualizaron") && !response.message.includes("Se actualizaron 0")) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Sincronización Exitosa!',
                            text: response.message,
                            timer: 9000, // Se cierra solo
                            showConfirmButton: false
                        });

                        // Recargamos los datos para reflejar los cambios
                        if (tablaGestion) {
                            tablaGestion.ajax.reload();
                        }
                        cargarDashboardGestion();
                    } else {
                        // 3. Si no hubo cambios, lo informamos en la consola (sin molestar al usuario)
                        console.log('Sincronización finalizada, sin cambios detectados.');
                    }
                } else {
                    console.error('Error durante la sincronización automática:', response.message);
                }
            },
            error: function () {
                Swal.close();
                console.error('Error de conexión durante la sincronización automática.');
            }
        });
    }

    // ===== NUEVA FUNCIÓN DE RECÁLCULO PARA EL MODAL ADMIN =====
    function recalcularTotalesAdmin() {
        let totalBruto = 0;
        let totalNeto = 0;

        $('#tabla-detalle-propuesta-admin tbody tr').each(function () {
            const tr = $(this);
            const bruto = parseFloat(tr.data('importe-bruto'));
            const tComp = tr.data('tcomp').trim();
            // En admin asumimos que REC es negativo
            const esNegativo = tComp.startsWith('NC') || tComp === 'REC';
            const netoTexto = tr.find('.importe-neto-cell-admin').text();
            
            // Limpieza robusta de espacios para evitar NaN con signo negativo: "- $ 10" -> "-10"
            // También lidiamos con posible texto como " -$ 10" usando regular expressions más fuertes
            const netoMonto = netoTexto.replace(/\$/g, '').replace(/[^\d,\.-]/g, '').replace(/\./g, '').replace(',', '.');
            const neto = parseFloat(netoMonto) || 0;

            const brutoAsignable = esNegativo ? -Math.abs(bruto) : Math.abs(bruto);
            
            totalBruto += brutoAsignable;
            totalNeto += neto; // El neto ya tiene el signo correcto gracias a la UI
        });

        const f = (num) => num.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

        $('#total-bruto-tabla').text(f(totalBruto));
        $('#total-neto-tabla').text(f(totalNeto));

        // Aplicar color rojo si el total es negativo
        if (totalBruto < 0) $('#total-bruto-tabla').addClass('text-danger'); else $('#total-bruto-tabla').removeClass('text-danger');
        if (totalNeto < 0) $('#total-neto-tabla').addClass('text-danger'); else $('#total-neto-tabla').removeClass('text-danger');

        // Actualizar KPI Superior
        $('#total-propuesto-kpi').text(f(totalNeto));
        if (totalNeto < 0) $('#total-propuesto-kpi').addClass('text-danger'); else $('#total-propuesto-kpi').removeClass('text-danger');

        // --- Recalcular Cuotas proporcionalmente ---
        const $cuotas = $('.cuota-monto-admin');
        const numCuotas = $cuotas.length;
        if (numCuotas > 0) {
            const montoPorCuota = Math.floor((totalNeto / numCuotas) * 100) / 100;
            let totalAsignado = 0;

            $cuotas.each(function (idx) {
                let montoFinal;
                if (idx === numCuotas - 1) {
                    // La última cuota absorbe el redondeo
                    montoFinal = totalNeto - totalAsignado;
                } else {
                    montoFinal = montoPorCuota;
                    totalAsignado += montoFinal;
                }
                $(this).text(f(montoFinal));
            });
        }
    }
    // ===== NUEVA FUNCIÓN PARA EXPORTAR A EXCEL =====
    function exportarPropuestaExcel(data) {
        const { propuesta, items } = data;

        // 1. Preparar los datos
        let excelData = [
            ['Detalle de Propuesta de Pago'], // Título
            [], // Fila vacía
            ['ID Propuesta:', propuesta.id, '', 'Cliente:', propuesta.cod_cliente],
            ['Monto Total:', parseFloat(propuesta.total_propuesto), '', 'Fecha de Pago:', new Date(propuesta.fecha_propuesta_pago + 'T00:00:00').toLocaleDateString('es-AR')],
            ['Medio de Pago:', propuesta.medio_de_pago],
            [], // Fila vacía
            ['Comprobantes Incluidos'],
            ['Tipo', 'Comprobante', 'Importe Bruto', '% Descuento', 'Importe Neto'] // Cabecera de la tabla
        ];

        // 2. Añadir las filas de los items
        let totalBrutoItems = 0;
        items.forEach(item => {
            const esNC = item.t_comp_factura && item.t_comp_factura.trim().startsWith('NC');
            const bruto = parseFloat(item.importe_bruto) * (esNC ? -1 : 1);
            const neto = parseFloat(item.importe_neto) * (esNC ? -1 : 1);
            const descuento = parseFloat(item.porcentaje_descuento) || 0;

            totalBrutoItems += bruto;

            excelData.push([
                item.t_comp_factura,
                item.n_comp_factura,
                bruto,
                descuento > 0 ? descuento / 100 : 0, // Guardar como número (0.08) o 0
                neto
            ]);
        });

        // 3. Añadir totales
        excelData.push([]); // Fila vacía
        excelData.push(['', 'Totales:', totalBrutoItems, '', parseFloat(propuesta.total_propuesto)]);

        // 4. Crear la hoja de cálculo
        const ws = XLSX.utils.aoa_to_sheet(excelData);

        // 5. Aplicar formatos (VERSIÓN SEGURA Y ROBUSTA)
        // --- Estilos de fuente ---
        if (ws['A1']) ws['A1'].s = { font: { bold: true, sz: 16 } };
        if (ws['A7']) ws['A7'].s = { font: { bold: true } };
        ['A8', 'B8', 'C8', 'D8', 'E8'].forEach(cell => {
            if (ws[cell]) ws[cell].s = { font: { bold: true } };
        });

        // --- Formatos de número para celdas de datos ---
        for (let i = 9; i < 9 + items.length; i++) {
            // Verificamos si la celda existe ANTES de aplicarle el formato
            if (ws[`C${i}`]) ws[`C${i}`].z = '$#,##0.00';
            if (ws[`D${i}`]) ws[`D${i}`].z = '0.00%';
            if (ws[`E${i}`]) ws[`E${i}`].z = '$#,##0.00';
        }

        // --- Formatos de número para la fila de totales ---
        const totalRowIndex = 9 + items.length + 2;
        if (ws[`C${totalRowIndex}`]) ws[`C${totalRowIndex}`].z = '$#,##0.00';
        if (ws[`E${totalRowIndex}`]) ws[`E${totalRowIndex}`].z = '$#,##0.00';
        if (ws['B4']) ws['B4'].z = '$#,##0.00'; // Formatear el monto total en la cabecera

        // Ajustar anchos de columnas (opcional pero mejora la apariencia)
        ws['!cols'] = [{ wch: 10 }, { wch: 20 }, { wch: 15 }, { wch: 12 }, { wch: 15 }];

        // 6. Crear el libro y descargar
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, `Propuesta ${propuesta.id}`);
        XLSX.writeFile(wb, `Propuesta_${propuesta.id}_${propuesta.cod_cliente}.xlsx`);
    }
    // ======================= NUEVA FUNCIÓN MEJORADA PARA CRONOGRAMA ADMIN =======================
    let calendarInstance = null; // Guardamos la instancia para evitar reinicializar

    function initializeCronogramaAdmin() {
        $.ajax({
            url: 'api/propuestas_controller.php?action=obtener_cronograma_admin',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const eventos = response.data;
                    const calendarEl = document.getElementById('fullcalendar-admin');
                    const f = (num) => parseFloat(num || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

                    // --- 1. Calcular y poblar los KPIs ---
                    const hoy = new Date();
                    const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
                    const finMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
                    const inicioSemana = new Date(hoy);
                    inicioSemana.setDate(hoy.getDate() - hoy.getDay());
                    const finSemana = new Date(inicioSemana);
                    finSemana.setDate(inicioSemana.getDate() + 6);

                    let totalMes = 0, totalSemana = 0, cantidadMes = 0;
                    let proximosVencimientos = [];

                    eventos.forEach(evento => {
                        const fechaEvento = new Date(evento.start + 'T00:00:00');
                        if (fechaEvento >= inicioMes && fechaEvento <= finMes) {
                            totalMes += parseFloat(evento.extendedProps.monto);
                            cantidadMes++;
                        }
                        if (fechaEvento >= inicioSemana && fechaEvento <= finSemana) {
                            totalSemana += parseFloat(evento.extendedProps.monto);
                        }
                        if (fechaEvento >= hoy) {
                            proximosVencimientos.push(evento);
                        }
                    });

                    $('#cronograma-kpi-mes').text(f(totalMes));
                    $('#cronograma-kpi-semana').text(f(totalSemana));
                    $('#cronograma-kpi-cantidad').text(cantidadMes);

                    // ======================= INICIO DE LA CORRECCIÓN VISUAL =======================
                    // --- 2. Poblar la lista de próximos vencimientos (CON FORMATO Y MEDIO DE PAGO) ---
                    proximosVencimientos.sort((a, b) => new Date(a.start) - new Date(b.start));
                    const listaHtml = proximosVencimientos.slice(0, 5).map(evento => {
                        const medioDePagoHtml = evento.extendedProps.medio_de_pago
                            ? `<i class="fa-solid fa-credit-card mx-1"></i> ${evento.extendedProps.medio_de_pago}`
                            : '';

                        return `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${new Date(evento.start + 'T00:00:00').toLocaleDateString('es-AR')}</strong> - Propuesta #${evento.extendedProps.id}
                                <small class="d-block text-muted">
                                    ${evento.extendedProps.cliente}
                                    ${medioDePagoHtml}
                                </small>
                            </div>
                            <span class="badge bg-primary rounded-pill">${f(evento.extendedProps.monto)}</span>
                        </li>`;
                    }).join('');
                    $('#proximos-vencimientos-lista').html(listaHtml || '<li class="list-group-item text-muted">No hay vencimientos próximos.</li>');
                    // ======================== FIN DE LA CORRECCIÓN VISUAL =========================

                    if (calendarInstance) {
                        calendarInstance.destroy();
                    }

                    calendarInstance = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        locale: 'es',
                        height: 'auto',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek'
                        },
                        events: eventos,

                        // ======================= INICIO DE LA NUEVA FUNCIONALIDAD =======================
                        // Evento que se dispara al hacer clic en un día (tenga o no eventos)
                        dateClick: function (info) {
                            const eventosDelDia = eventos.filter(evento => evento.start === info.dateStr);

                            // Si no hay eventos para este día, no hacemos nada
                            if (eventosDelDia.length === 0) {
                                return;
                            }

                            // Abrimos el modal con el resumen del día
                            const modal = new bootstrap.Modal(document.getElementById('cronogramaDiaModal'));
                            const fechaFormateada = new Date(info.dateStr + 'T00:00:00').toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric' });
                            $('#cronogramaDiaModalLabel').text(`Vencimientos para el ${fechaFormateada}`);

                            let modalBodyHtml = '';
                            eventosDelDia.forEach(evento => {
                                modalBodyHtml += `
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="card-title mb-1">Propuesta #${evento.extendedProps.id}</h6>
                                                <p class="card-text mb-0"><small class="text-muted">${evento.extendedProps.cliente}</small></p>
                                                <p class="card-text"><small><i class="fa-solid fa-credit-card me-1"></i>${evento.extendedProps.medio_de_pago}</small></p>
                                            </div>
                                            <div class="text-end">
                                                <h5 class="text-primary">${f(evento.extendedProps.monto)}</h5>
                                                <button class="btn btn-sm btn-outline-primary btn-ver-propuesta-admin mt-2" data-id="${evento.extendedProps.id}">
                                                    Ver Detalle Completo
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            });

                            $('#cronogramaDiaModalBody').html(modalBodyHtml);
                            modal.show();
                        },
                        // ======================== FIN DE LA NUEVA FUNCIONALIDAD =========================

                        eventClick: function (info) {
                            // Mantenemos la funcionalidad de abrir el detalle completo al hacer clic en un evento específico
                            const idPropuesta = info.event.extendedProps.id;
                            // Simulamos un clic en un botón que ya tiene el evento asignado
                            const btn = $('<button class="btn-ver-propuesta-admin" data-id="' + idPropuesta + '"></button>');
                            $('body').append(btn);
                            btn.click();
                            btn.remove();
                        },
                        eventDidMount: function (info) {
                            $(info.el).tooltip({ title: info.event.extendedProps.cliente, placement: 'top', trigger: 'hover', container: 'body' });
                        }
                    });

                    calendarInstance.render();
                }
            }
        });
    }
    // Lógica para eliminar propuesta con SweetAlert2
    $('#tabla-gestion-propuestas').on('click', '.btn-eliminar-propuesta', function () {
        const idPropuesta = $(this).data('id');

        Swal.fire({
            title: '¿Estás seguro?',
            text: `¡Esta acción eliminará la propuesta #${idPropuesta} y es irreversible!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, ¡eliminarla!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/propuestas_controller.php?action=eliminar_propuesta',
                    type: 'POST',
                    data: { id_propuesta: idPropuesta },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('¡Eliminada!', response.message, 'success');
                            tablaGestion.ajax.reload();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    }
                });
            }
        });
    });
    // =========================================================================================================
    // 1. LÓGICA DE ALTERNANCIA DE GRÁFICOS
    // =========================================================================================================
    $('#btn-toggle-charts').on('click', function () {
        const chartsRow = $('#charts-row');
        const icon = $(this).find('i');

        chartsRow.slideToggle(400, function () {
            if (chartsRow.is(':visible')) {
                icon.removeClass('fa-eye-slash').addClass('fa-chart-line');
                $(this).addClass('btn-outline-primary').removeClass('btn-primary');
            } else {
                icon.removeClass('fa-chart-line').addClass('fa-eye-slash');
                $(this).addClass('btn-primary').removeClass('btn-outline-primary');
            }
        });
    });

    // =========================================================================================================
    // 2. LÓGICA DE INDICADORES PRO (NUEVA SOLAPA)
    // =========================================================================================================
    $('#indicadores-tab').on('click', function () {
        cargarIndicadoresPro();
    });

    // Variables globales para instancias de gráficos en la solapa de indicadores
    var chartTendenciaPro = null;
    var chartEstadosPro = null;

    function cargarIndicadoresPro() {
        const container = $('#container-indicadores');
        container.html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-3 text-muted">Cargando analíticas avanzadas...</p></div>');

        $.ajax({
            url: 'api/propuestas_controller.php?action=obtener_indicadores_pro',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const data = response.data;
                    const f = (v) => parseFloat(v || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

                    // Cálculos para rankings
                    const maxDeudaProp = data.ranking_deuda.length > 0 ? Math.max.apply(null, data.ranking_deuda.map(function (d) { return d.monto; })) : 1;
                    const maxHoras = data.ranking_demora.length > 0 ? Math.max.apply(null, data.ranking_demora.map(function (d) { return d.horas; })) : 1;
                    const maxDeudaTotal = (data.ranking_deuda_total && data.ranking_deuda_total.length > 0) ? Math.max.apply(null, data.ranking_deuda_total.map(function (d) { return d.deuda; })) : 1;

                    let html = `
                        <!-- SECCIÓN 1: KPIs PRINCIPALES -->
                        <div class="col-12 mb-4">
                            <div class="row g-3">
                                <!-- KPI 1: Beneficio Otorgado -->
                                <div class="col-xl col-md-4">
                                    <div class="card kpi-card border-0 h-100 shadow-hover" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        <div class="card-body text-white p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <p class="text-uppercase small mb-1 opacity-75 fw-semibold" style="font-size: 0.7rem;">Beneficio Total</p>
                                                    <h3 class="mb-0 fw-bold counter" data-target="${data.total_beneficio_otorgado}">0</h3>
                                                </div>
                                                <div class="kpi-icon"><i class="fa-solid fa-hand-holding-dollar fa-lg opacity-25"></i></div>
                                            </div>
                                            <div class="d-flex flex-column small opacity-90">
                                                <span><i class="fa-solid fa-file-invoice-dollar me-1"></i>${data.conteo_comprobantes_beneficio} comprobantes</span>
                                                <span><i class="fa-solid fa-calculator me-1"></i>Avg: ${f(data.promedio_beneficio_pesos)}/u</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- KPI 2: Tasa de Conversión -->
                                <div class="col-xl col-md-4">
                                    <div class="card kpi-card border-0 h-100 shadow-hover" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                        <div class="card-body text-white p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <p class="text-uppercase small mb-1 opacity-75 fw-semibold" style="font-size: 0.7rem;">Conversión</p>
                                                    <h3 class="mb-0 fw-bold">${data.tasa_conversion}%</h3>
                                                </div>
                                                <div class="kpi-icon"><i class="fa-solid fa-bullseye fa-lg opacity-25"></i></div>
                                            </div>
                                            <div class="mb-1 small opacity-90">
                                                <i class="fa-solid fa-check-double me-1"></i>${data.cantidad_concretadas} de ${data.total_propuestas} exitosas
                                            </div>
                                            <div class="progress" style="height: 4px; background: rgba(255,255,255,0.2);">
                                                <div class="progress-bar bg-white progress-animated" style="width: 0%" data-width="${data.tasa_conversion}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- KPI 3: Eficiencia de Cobranza -->
                                <div class="col-xl col-md-4">
                                    <div class="card kpi-card border-0 h-100 shadow-hover" style="background: linear-gradient(135deg, #2af598 0%, #009efd 100%);">
                                        <div class="card-body text-white p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <p class="text-uppercase small mb-1 opacity-75 fw-semibold" style="font-size: 0.7rem;">Eficiencia Cobro</p>
                                                    <h3 class="mb-0 fw-bold">${data.eficiencia_cobranza}%</h3>
                                                </div>
                                                <div class="kpi-icon"><i class="fa-solid fa-chart-pie fa-lg opacity-25"></i></div>
                                            </div>
                                            <div class="mb-1 small opacity-90 d-flex flex-column" style="font-size: 0.65rem;">
                                                <span><i class="fa-solid fa-money-bill-1-wave me-1"></i>Cobrado: ${f(data.total_cobrado_historico)}</span>
                                                <span class="text-warning"><i class="fa-solid fa-clock-rotate-left me-1"></i>Falta: ${f(data.deuda_total_sistema)}</span>
                                            </div>
                                            <div class="progress" style="height: 4px; background: rgba(255,255,255,0.2);">
                                                <div class="progress-bar bg-warning progress-animated" style="width: 0%" data-width="${data.eficiencia_cobranza}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- KPI 4: Tiempo Promedio -->
                                <div class="col-xl col-md-6">
                                    <div class="card kpi-card border-0 h-100 shadow-hover" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                        <div class="card-body text-white p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <p class="text-uppercase small mb-1 opacity-75 fw-semibold" style="font-size: 0.7rem;">Tiempo Rta.</p>
                                                    <h3 class="mb-0 fw-bold">${data.tiempo_promedio_horas} hs</h3>
                                                </div>
                                                <div class="kpi-icon"><i class="fa-solid fa-clock fa-lg opacity-25"></i></div>
                                            </div>
                                            <small class="opacity-75">Desde creación a respuesta</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- KPI 5: Método Preferido -->
                                <div class="col-xl col-md-6">
                                    <div class="card kpi-card border-0 h-100 shadow-hover" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                        <div class="card-body text-white p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <p class="text-uppercase small mb-1 opacity-75 fw-semibold" style="font-size: 0.7rem;">Método TOP</p>
                                                    <h3 class="mb-0 fw-bold" style="font-size: 1.1rem;">${data.metodo_preferido.medio || 'N/A'}</h3>
                                                </div>
                                                <div class="kpi-icon"><i class="fa-solid fa-credit-card fa-lg opacity-25"></i></div>
                                            </div>
                                            <small class="opacity-75">${data.metodo_preferido.cantidad || 0} operaciones reales</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 2: GRÁFICOS INTERACTIVOS -->
                        <div class="col-xl-8 mb-4">
                            <div class="card border-0 shadow-sm h-100 ranking-card">
                                <div class="card-header bg-white py-3 border-0 d-flex align-items-center">
                                    <div class="chart-icon me-3 text-primary"><i class="fa-solid fa-chart-line"></i></div>
                                    <h6 class="mb-0 fw-bold">Tendencia de Propuestas (30 días)</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartTendenciaPro" style="min-height: 280px;"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 mb-4">
                            <div class="card border-0 shadow-sm h-100 ranking-card">
                                <div class="card-header bg-white py-3 border-0 d-flex align-items-center">
                                    <div class="chart-icon me-3 text-info"><i class="fa-solid fa-chart-pie"></i></div>
                                    <h6 class="mb-0 fw-bold">Distribución por Estados</h6>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <canvas id="chartEstadosPro" style="max-height: 220px;"></canvas>
                                    <div id="estadosLegend" class="mt-3 small row g-2"></div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 3: RANKINGS DE GESTIÓN -->
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm ranking-card">
                                <div class="card-header bg-gradient-warning text-white border-0 py-3">
                                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-trophy me-2"></i>Top 5: Mayor Deuda Activa</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        ${data.ranking_deuda.map(function (d, idx) {
                        const percentage = (d.monto / maxDeudaProp) * 100;
                        const medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                        return `
                                                <div class="list-group-item border-0 ranking-item">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div>
                                                            <span class="ranking-number me-2">${medals[idx]}</span>
                                                            <span class="fw-bold">${d.cliente}</span>
                                                            <div class="small text-muted ms-5">${d.codigo}</div>
                                                        </div>
                                                        <span class="badge bg-soft-primary text-primary">${f(d.monto)}</span>
                                                    </div>
                                                    <div class="progress" style="height: 5px;">
                                                        <div class="progress-bar bg-warning progress-animated" style="width: 0%" data-width="${percentage}%"></div>
                                                    </div>
                                                </div>
                                            `;
                    }).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm ranking-card">
                                <div class="card-header bg-gradient-danger text-white border-0 py-3">
                                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-hourglass-half me-2"></i>Top 5: Mayor Demora</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        ${data.ranking_demora.map(function (d, idx) {
                        const percentage = (d.horas / maxHoras) * 100;
                        const medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                        return `
                                                <div class="list-group-item border-0 ranking-item">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div>
                                                            <span class="ranking-number me-2">${medals[idx]}</span>
                                                            <span class="fw-bold">${d.cliente}</span>
                                                            <div class="small text-muted ms-5">${d.codigo}</div>
                                                        </div>
                                                        <span class="badge bg-danger rounded-pill"><i class="fa-regular fa-clock me-1"></i>${d.horas} hs</span>
                                                    </div>
                                                    <div class="progress" style="height: 5px;">
                                                        <div class="progress-bar bg-danger progress-animated" style="width: 0%" data-width="${percentage}%"></div>
                                                    </div>
                                                </div>
                                            `;
                    }).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 4: RANKING DE DEUDA TOTAL (SISTEMA CENTRAL) -->
                        <div class="col-12 mt-4">
                            <div class="card border-0 shadow-sm ranking-card">
                                <div class="card-header bg-gradient-info text-white border-0 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="ranking-icon me-3"><i class="fa-solid fa-sack-dollar"></i></div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">Top 10: Mayor Deuda Total en el Sistema</h6>
                                            <small class="opacity-75">Clientes con mayor saldo pendiente según el sistema central</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        ${(data.ranking_deuda_total && data.ranking_deuda_total.length > 0) ? data.ranking_deuda_total.map(function (d, idx) {
                        const percentage = (d.deuda / maxDeudaTotal) * 100;
                        const medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];
                        return `
                                                <div class="list-group-item border-0 ranking-item">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div>
                                                            <span class="ranking-number me-3">${medals[idx]}</span>
                                                            <span class="fw-bold">${d.cliente}</span>
                                                            <small class="text-muted ms-2">${d.codigo}</small>
                                                        </div>
                                                        <span class="badge bg-info text-white px-3 py-2 fs-6">${f(d.deuda)}</span>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-info progress-animated" style="width: 0%" data-width="${percentage}%"></div>
                                                    </div>
                                                </div>
                                            `;
                    }).join('') : `
                                            <div class="list-group-item border-0 text-center py-5">
                                                <i class="fa-solid fa-check-circle fa-3x text-success mb-3"></i>
                                                <p class="text-muted mb-0">No se encontraron datos de deuda en el sistema central.</p>
                                            </div>
                                        `}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    container.html(html);

                    // --- INICIALIZACIÓN DE ANIMACIONES ---
                    $('.kpi-card').each(function (i) {
                        var $card = $(this);
                        $card.css({ opacity: 0, transform: 'translateY(20px)' });
                        setTimeout(function () {
                            $card.css({
                                opacity: 1,
                                transform: 'translateY(0)',
                                transition: 'all 0.5s ease'
                            });
                        }, i * 100);
                    });

                    $('.counter').each(function () {
                        const $this = $(this);
                        const target = parseFloat($this.data('target'));
                        $({ Counter: 0 }).animate({ Counter: target }, {
                            duration: 2000,
                            easing: 'swing',
                            step: function () {
                                if (target > 1000) {
                                    $this.text(f(this.Counter));
                                } else {
                                    $this.text(Math.ceil(this.Counter).toLocaleString('es-AR'));
                                }
                            }
                        });
                    });

                    setTimeout(function () {
                        $('.progress-animated').each(function () {
                            $(this).css('width', $(this).data('width'));
                        });
                    }, 500);

                    // --- INICIALIZACIÓN DE GRÁFICOS (con destrucción de instancias previas) ---

                    // 1. Tendencia (Gráfico Mixto: Columnas para Cantidad, Línea para Monto)
                    const ctxTendencia = document.getElementById('chartTendenciaPro');
                    if (ctxTendencia && data.tendencia_30dias && data.tendencia_30dias.length > 0) {
                        if (chartTendenciaPro) chartTendenciaPro.destroy();

                        chartTendenciaPro = new Chart(ctxTendencia, {
                            data: {
                                labels: data.tendencia_30dias.map(function (t) { return new Date(t.fecha).toLocaleDateString('es-AR', { day: '2-digit', month: 'short' }); }),
                                datasets: [{
                                    type: 'bar',
                                    label: 'Cantidad Propuestas',
                                    data: data.tendencia_30dias.map(function (t) { return t.cantidad; }),
                                    backgroundColor: 'rgba(78, 115, 223, 0.5)',
                                    borderColor: '#4e73df',
                                    borderWidth: 1,
                                    yAxisID: 'y'
                                }, {
                                    type: 'line',
                                    label: 'Monto Total',
                                    data: data.tendencia_30dias.map(function (t) { return t.monto; }),
                                    borderColor: '#1cc88a',
                                    backgroundColor: 'transparent',
                                    tension: 0.4,
                                    pointBackgroundColor: '#1cc88a',
                                    fill: false,
                                    yAxisID: 'y1'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { position: 'top' },
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                let label = context.dataset.label || '';
                                                if (label) label += ': ';
                                                if (context.parsed.y !== null) {
                                                    label += context.datasetIndex === 1 ? f(context.parsed.y) : context.parsed.y;
                                                }
                                                return label;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Cantidad' }, grid: { drawOnChartArea: true } },
                                    y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Monto ($)' }, grid: { drawOnChartArea: false } }
                                }
                            }
                        });
                    }

                    // 2. Estados (Doughnut con Leyenda Detallada)
                    const ctxEstados = document.getElementById('chartEstadosPro');
                    if (ctxEstados && data.distribucion_estados && data.distribucion_estados.length > 0) {
                        if (chartEstadosPro) chartEstadosPro.destroy();

                        const estadosMap = {
                            'PENDIENTE_APROBACION_CLIENTE': { label: 'Pendiente Cliente', color: '#f6c23e' },
                            'ACEPTADA': { label: 'Aceptada', color: '#1cc88a' },
                            'RECHAZADA': { label: 'Rechazada', color: '#e74a3b' },
                            'PAGADO': { label: 'Pagado', color: '#36b9cc' },
                            'CONTRAPROPUESTA_CLIENTE': { label: 'Contrapropuesta', color: '#f093fb' },
                            'DOCUMENTACION_ADJUNTADA': { label: 'Con Documentos', color: '#4e73df' },
                            'VENCIDA': { label: 'Vencida', color: '#858796' },
                            'PENDIENTE_APROBACION_FINAL': { label: 'Pendiente Final', color: '#fd7e14' }
                        };

                        const totalEstados = data.distribucion_estados.reduce(function (acc, curr) { return acc + parseInt(curr.cantidad); }, 0);

                        chartEstadosPro = new Chart(ctxEstados, {
                            type: 'doughnut',
                            data: {
                                labels: data.distribucion_estados.map(function (e) { return (estadosMap[e.estado] && estadosMap[e.estado].label) ? estadosMap[e.estado].label : e.estado; }),
                                datasets: [{
                                    data: data.distribucion_estados.map(function (e) { return e.cantidad; }),
                                    backgroundColor: data.distribucion_estados.map(function (e) { return (estadosMap[e.estado] && estadosMap[e.estado].color) ? estadosMap[e.estado].color : '#858796'; }),
                                    hoverOffset: 10,
                                    borderWidth: 2,
                                    borderColor: '#ffffff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false }
                                },
                                cutout: '70%'
                            }
                        });

                        // Generar leyenda personalizada
                        let legendHtml = '';
                        data.distribucion_estados.forEach(function (e) {
                            const info = estadosMap[e.estado] || { label: e.estado, color: '#858796' };
                            const pct = ((parseInt(e.cantidad) / totalEstados) * 100).toFixed(1);
                            legendHtml += `
                                <div class="col-6 mb-1">
                                    <div class="d-flex align-items-center">
                                        <div style="width: 10px; height: 10px; background: ${info.color}; border-radius: 50%;" class="me-2"></div>
                                        <div class="flex-grow-1 text-truncate" title="${info.label}" style="font-size: 0.75rem;">${info.label}</div>
                                        <div class="ms-1 fw-bold" style="font-size: 0.75rem;">${pct}% <small class="text-muted fw-normal">(${e.cantidad})</small></div>
                                    </div>
                                </div>
                            `;
                        });
                        $('#estadosLegend').html(legendHtml);
                    }
                }
            },
            error: function () {
                container.html('<div class="col-12 text-center text-danger p-5"><i class="fa-solid fa-triangle-exclamation fa-3x mb-3"></i><p class="h5">Error al cargar los indicadores avanzados</p></div>');
            }
        });
    }

    // Estilo complementario PRO - Sistema de diseño premium
    const stylePro = `
        <style>
            /* === NAVEGACIÓN DE TABS === */
            .card-header.bg-white { border-bottom: none !important; }
            .nav-tabs.card-header-tabs { 
                border-bottom: none !important; 
                gap: 8px; 
                padding: 0 15px; 
                background: linear-gradient(to bottom, #f8f9fc 0%, #ffffff 100%);
            }
            .nav-tabs .nav-link { 
                border: none !important; 
                border-radius: 30px !important; 
                color: #858796; 
                font-weight: 600; 
                padding: 10px 20px; 
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
                background: #f8f9fc !important;
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                position: relative;
                overflow: hidden;
            }
            .nav-tabs .nav-link::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
                transition: left 0.5s;
            }
            .nav-tabs .nav-link:hover::before {
                left: 100%;
            }
            .nav-tabs .nav-link:hover { 
                background: #eaecf4 !important; 
                color: #4e73df; 
                transform: translateY(-2px);
            }
            .nav-tabs .nav-link.active { 
                background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important; 
                color: #fff !important; 
                box-shadow: 0 8px 16px rgba(78, 115, 223, 0.3); 
                transform: translateY(-2px);
            }
            
            /* === KPI CARDS === */
            .kpi-card { 
                position: relative; 
                overflow: hidden; 
                border-radius: 16px !important;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .kpi-card::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                opacity: 0;
                transition: opacity 0.4s;
            }
            .kpi-card:hover::before {
                opacity: 1;
            }
            .shadow-hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            }
            .shadow-hover:hover {
                box-shadow: 0 12px 28px rgba(0,0,0,0.15);
                transform: translateY(-4px);
            }
            .kpi-icon {
                animation: float 3s ease-in-out infinite;
            }
            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-10px); }
            }
            
            /* === GRADIENTES === */
            .bg-gradient-warning { 
                background: linear-gradient(135deg, #f6c23e 0%, #f4b619 100%); 
            }
            .bg-gradient-danger { 
                background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%); 
            }
            .bg-gradient-info { 
                background: linear-gradient(135deg, #36b9cc 0%, #258391 100%); 
            }
            
            /* === RANKINGS === */
            .ranking-card { 
                border-radius: 16px !important; 
                overflow: hidden; 
                transition: all 0.3s ease;
            }
            .ranking-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            }
            .ranking-item {
                padding: 1.25rem 1.5rem;
                transition: all 0.3s ease;
                background: #fff;
            }
            .ranking-item:hover {
                background: linear-gradient(to right, #f8f9fc 0%, #ffffff 100%);
                transform: translateX(5px);
            }
            .ranking-number {
                font-size: 1.5rem;
                font-weight: bold;
                display: inline-block;
                min-width: 40px;
                text-align: center;
            }
            .ranking-icon {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
            }
            
            /* === PROGRESS BARS === */
            .progress {
                background-color: rgba(0,0,0,0.05);
                border-radius: 10px;
                overflow: hidden;
            }
            .progress-bar {
                transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
                border-radius: 10px;
            }
            .progress-animated {
                position: relative;
                overflow: hidden;
            }
            .progress-animated::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
                right: 0;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
                animation: shimmer 2s infinite;
            }
            @keyframes shimmer {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }
            
            /* === CHARTS === */
            .chart-icon {
                width: 40px;
                height: 40px;
                background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
            }
            
            /* === BADGES === */
            .badge {
                font-weight: 600;
                border-radius: 8px;
                padding: 0.5rem 0.75rem;
            }
            
            /* === UTILITIES === */
            .bg-soft-primary { 
                background-color: rgba(78, 115, 223, 0.1); 
            }
            body { 
                background-color: #f8f9fc; 
            }
            
            /* === LOADING SPINNER === */
            .spinner-border {
                width: 3rem;
                height: 3rem;
                border-width: 0.3rem;
            }
            
            /* === RESPONSIVE === */
            @media (max-width: 768px) {
                .kpi-card {
                    margin-bottom: 1rem;
                }
                .ranking-number {
                    font-size: 1.2rem;
                    min-width: 30px;
                }
            }
        </style>
    `;

    function cargarReportes() {
        console.log("Iniciando carga de reportes plazos...");
        
        // Bloquear tablas con mensaje de carga (Vaciando previos si existen)
        if ($.fn.DataTable.isDataTable('#tabla-reporte-franquicias')) {
            $('#tabla-reporte-franquicias').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#tabla-reporte-razon-social')) {
            $('#tabla-reporte-razon-social').DataTable().destroy();
        }

        const loadingHtml = '<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Cargando datos estratégicos...</p></td></tr>';
        $('#tabla-reporte-franquicias, #tabla-reporte-razon-social').html('<tbody>' + loadingHtml + '</tbody>');

        $.ajax({
            url: 'api/propuestas_controller.php?action=obtener_reporte_plazos',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                console.log("Respuesta recibida:", response);
                
                // Limpiar HTML de carga para que DataTables tome el control limpio
                $('#tabla-reporte-franquicias, #tabla-reporte-razon-social').empty();

                if (response.success) {
                    // Inicializar Tabla por Franquicia 
                    $('#tabla-reporte-franquicias').DataTable({
                        data: response.reporte_franquicias,
                        columns: [
                            { data: 'codigo', title: 'Código' },
                            { data: 'razon_social', title: 'Razón Social' },
                            { 
                                data: 'avg_propuesto', 
                                title: 'Promedio Plazo Propuesto Final (Días)', 
                                className: 'text-center fw-bold',
                                render: (data) => `<span class="badge bg-light text-dark border">${data} días</span>`
                            },
                            { 
                                data: 'avg_real', 
                                title: 'Promedio Plazo Pago Final (Días)', 
                                className: 'text-center fw-bold',
                                render: (data) => `<span class="badge bg-soft-success text-success border-success">${data} días</span>`
                            }
                        ],
                        order: [[3, 'desc']],
                        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                        dom: 'Bfrtip',
                        buttons: [
                            { extend: 'excel', text: '<i class="fa-solid fa-file-excel me-1"></i> Excel', className: 'btn btn-success btn-sm' },
                            { extend: 'pdf', text: '<i class="fa-solid fa-file-pdf me-1"></i> PDF', className: 'btn btn-danger btn-sm' },
                            { extend: 'print', text: '<i class="fa-solid fa-print me-1"></i> Imprimir', className: 'btn btn-info btn-sm' }
                        ]
                    });

                    // Inicializar Tabla por Razón Social
                    $('#tabla-reporte-razon-social').DataTable({
                        data: response.reporte_razon_social,
                        columns: [
                            { data: 'razon_social', title: 'Razón Social' },
                            { 
                                data: 'avg_propuesto', 
                                title: 'Promedio Plazo Propuesto Final (Días)', 
                                className: 'text-center fw-bold',
                                render: (data) => `<span class="badge bg-light text-dark border">${data} días</span>`
                            },
                            { 
                                data: 'avg_real', 
                                title: 'Promedio Plazo Pago Final (Días)', 
                                className: 'text-center fw-bold',
                                render: (data) => `<span class="badge bg-soft-success text-success border-success">${data} días</span>`
                            }
                        ],
                        order: [[2, 'desc']],
                        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                        dom: 'Bfrtip',
                        buttons: [
                            { extend: 'excel', text: '<i class="fa-solid fa-file-excel me-1"></i> Excel', className: 'btn btn-success btn-sm' },
                            { extend: 'pdf', text: '<i class="fa-solid fa-file-pdf me-1"></i> PDF', className: 'btn btn-danger btn-sm' }
                        ]
                    });
                } else {
                    Swal.fire('Atención', 'El servidor no pudo procesar el reporte: ' + (response.message || 'Error desconocido'), 'warning');
                    $('#tabla-reporte-franquicias, #tabla-reporte-razon-social').html('<tr><td colspan="4" class="text-center text-danger py-5">No se pudieron cargar los datos del reporte.</td></tr>');
                }
            },
            error: function (xhr, status, error) {
                console.error("Error AJAX reporte:", error);
                $('#tabla-reporte-franquicias, #tabla-reporte-razon-social').html('<tr><td colspan="4" class="text-center text-danger py-5">Error de conexión con el servidor.</td></tr>');
                Swal.fire('Error de Conexión', 'No se pudo conectar con el servidor para obtener el reporte.', 'error');
            }
        });
    }

    // Listener para cuando se activa la pestaña de reportes
    $(document).on('shown.bs.tab', '#reportes-tab', function () {
        cargarReportes();
    });

    // Evento para eliminar adjuntos (Administración)
    $('#detalle-propuesta-content').on('click', '.btn-eliminar-adjunto', function () {
        const idAdjunto = $(this).data('id-adjunto');
        const titleText = $('#detallePropuestaModalLabel').text();
        const match = titleText.match(/#(\d+)/);
        const idPropuesta = match ? match[1] : null;

        Swal.fire({
            title: '¿Eliminar comprobante?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/propuestas_controller.php?action=eliminar_adjunto',
                    type: 'POST',
                    data: { id_adjunto: idAdjunto },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('¡Eliminado!', response.message, 'success');
                            if (idPropuesta) {
                                $.ajax({
                                    url: `api/propuestas_controller.php?action=ver_detalle&id=${idPropuesta}`,
                                    type: 'GET',
                                    dataType: 'json',
                                    success: function (res) {
                                        if (res.success) renderizarDetallePropuestaAdmin(res.data);
                                    }
                                });
                            }
                            if (typeof tablaGestion !== 'undefined') tablaGestion.ajax.reload(null, false);
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'No se pudo eliminar el archivo.', 'error');
                    }
                });
            }
        });
    });

    $('head').append(stylePro);

}); // <--- ESTE ES EL ÚNICO Y CORRECTO CIERRE PARA $(document).ready()