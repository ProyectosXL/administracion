// ========================================================================
//                              APP.JS - COMPLETO
// ========================================================================

$(document).ready(function() {
    let chartEstadosInstance = null;
    let chartActividadInstance = null;
    
    function cargarDashboardGestion() {
        $.ajax({
            url: 'api/propuestas_controller.php?action=obtener_dashboard_admin',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    // Actualizar KPIs
                    $('#kpi-activas').text(data.kpis.totalActivas || 0);
                    const monto = parseFloat(data.kpis.montoEnNegociacion || 0);
                    $('#kpi-monto').text(monto.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));
                    $('#kpi-accion').text(data.kpis.contrapropuestas || 0);
                    $('#kpi-aceptadas').text(data.kpis.aceptadasMes || 0);
                    
                    // Renderizar Gráfico de Estados
                    renderizarChartEstados(data.graficoEstados);
                    renderizarChartActividad(data.graficoActividad);
                }
            }
        });
    }

    function renderizarChartEstados(data) {
        const ctx = document.getElementById('chartEstados').getContext('2d');
        const labels = data.map(item => item.estado.replace(/_/g, ' '));
        const cantidades = data.map(item => item.cantidad);

        const backgroundColors = [
            'rgba(255, 193, 7, 0.7)',  // Amarillo para Pendiente
            'rgba(13, 110, 253, 0.7)', // Azul para Contrapropuesta
            'rgba(25, 135, 84, 0.7)',  // Verde para Aceptada
            'rgba(13, 202, 240, 0.7)'   // Cian para Pendiente Final
        ];

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
                    borderColor: backgroundColors.map(color => color.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
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
            render: function(data, type, row) {
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
        render: function(data, type, row) {
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
        render: function(data, type, row) {
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
        // ======================= INICIO DE LA CORRECCIÓN =======================
        data: null, title: '% Descuento', className: 'text-center', orderable: false,
        render: function(data, type, row) {
            // Calculamos el descuento inicial basado en los importes
            const bruto = parseFloat(row.IMPORTE) || 0;
            const neto = parseFloat(row.IMPORTE_NETO) || 0;
            let initialDiscount = 0;

            if (bruto > 0 && bruto > neto) {
                initialDiscount = ((bruto - neto) / bruto) * 100;
            }
            
            return `<input type="number" class="form-control form-control-sm descuento-input" value="${initialDiscount.toFixed(2)}" min="0" max="100" step="0.01" style="width: 80px;">`;
        }
        // ======================== FIN DE LA CORRECCIÓN =========================
    },
    { 
        data: 'IMPORTE_NETO', 
        title: 'Importe Neto', 
        className: 'text-end fw-bold importe-neto-cell', 
        render: function(data, type, row) {
            let valor = parseFloat(data);
            if (row.T_COMP.trim().startsWith('NC')) {
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
                    dataSrc: function(json) {
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

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const targetId = $(e.target).attr("id");
        if (targetId === 'gestion-tab') {
            $('#summary-cards').hide();
            $('#gestion-dashboard').show();
            $('#btn-abrir-parametros').hide();
            cargarDashboardGestion();
            initializeGestionDataTable();
        } else {
            $('#gestion-dashboard').hide();
            $('#summary-cards').show();
            $('#btn-abrir-parametros').show();
            const tipo = targetId.includes('franquicias') ? 'franquicias' : 'mayoristas';
            initializeDataTable(`#tabla-${tipo}`, `api/cobranzas_controller.php?tipo=${tipo}`);
        }
    });

    // --- LÓGICA PARA ABRIR Y CARGAR EL MODAL DE DETALLE (CREACIÓN DE PROPUESTA) ---
    $('.tab-content').on('click', '.btn-detalle', function() {
        const codClient = $(this).data('cod-client');
        const razonSoci = $(this).data('razon-soci');
        const tipo = $('.nav-tabs .nav-link.active').attr('id').includes('franquicias') ? 'franquicias' : 'mayoristas';
        const urlDetalle = `api/cobranzas_controller.php?tipo=${tipo}&cod_client=${codClient}`;
        
        $('#nombreClienteModal').text(razonSoci);
        const container = $('#detalle-content-container');
        
        const loadingHtml = `<div class="d-flex justify-content-center align-items-center" style="min-height: 250px;"><div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div><strong class="ms-3 h5">Cargando...</strong></div>`;

        container.html(loadingHtml);
        $('#btn-enviar-propuesta').prop('disabled', true);
        $('#nombreClienteModal').data('cod-cliente', codClient);
        const detalleModal = new bootstrap.Modal(document.getElementById('detalleClienteModal'));
        detalleModal.show();

        $.ajax({
            url: urlDetalle,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                container.empty();
                // *** AÑADIDO ***: Agregamos un lugar para mostrar el total de la propuesta
                container.append('<table id="tabla-detalle-cliente" class="table table-striped table-hover" style="width:100%"></table>');
                $('#detalleClienteModal .modal-footer').prepend('<div id="total-propuesta-container" class="me-auto fs-5"><strong>Total Propuesto: <span id="total-propuesta-valor">$ 0,00</span></strong></div>');

                $('#tabla-detalle-cliente').DataTable({
                    data: response.data,
                    columns: columnsDetalle,
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                    order: [[1, 'desc']],
                    dom: 'Bfrtip',
                    buttons: [{
                        extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Exportar a Excel',
                        className: 'btn btn-success btn-sm', title: `Detalle Cobranza - ${razonSoci}`
                    }],
                    destroy: true,
                    paging: false, // Desactivamos paginación para calcular totales fácilmente
                    info: false
                });

                // *** AÑADIDO ***: Calculamos el total inicial (será 0)
                actualizarTotalPropuesta();
            },
            error: function() {
                container.html('<div class="alert alert-danger">Error al cargar los datos. Por favor, intente de nuevo.</div>');
            }
        });
    });

    // *** AÑADIDO ***: Limpiar el total del footer cuando se cierra el modal
    $('#detalleClienteModal').on('hidden.bs.modal', function () {
        $('#total-propuesta-container').remove();
    });

    // *** NUEVA FUNCIÓN ***: Para recalcular y mostrar el total de la propuesta
function actualizarTotalPropuesta() {
    let total = 0;
    const tablaDetalle = $('#tabla-detalle-cliente').DataTable();
    
    // Iteramos sobre las filas SELECCIONADAS con checkbox
    $('#tabla-detalle-cliente .invoice-checkbox:checked').each(function() {
        const tr = $(this).closest('tr');
        const rowData = tablaDetalle.row(tr).data(); // Obtenemos los datos originales de la fila
        const cellNeto = tr.find('.importe-neto-cell');
        
        // Convertimos el texto formateado ($ 1.234,56 o -$ 123,45) a un número
        let valorNeto = parseFloat(cellNeto.text().replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0;
        
        // La conversión ya tiene el signo correcto, así que solo sumamos
        total += valorNeto;
    });

    $('#total-propuesta-valor').text(total.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));
}

    // *** NUEVO EVENTO ***: Para recalcular el neto de una fila cuando cambia el descuento
$('#detalleClienteModal').on('input', '.descuento-input', function() {
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
    
    // ======================= INICIO DE LA MODIFICACIÓN =======================
    // Solo permitimos descuentos en FAC y ND (Notas de Débito)
    const tipoComp = rowData.T_COMP.trim();
    if (tipoComp.startsWith('NC')) {
        descuento = 0;
        input.val('0.00').prop('disabled', true);
    } else {
        input.prop('disabled', false);
    }
    // ======================== FIN DE LA MODIFICACIÓN =========================

    let importeNeto = importeBruto * (1 - (descuento / 100));

    // Si es Nota de Crédito, el neto también es negativo
    if (tipoComp.startsWith('NC')) {
        importeNeto = importeNeto * -1;
    }

    const numeroFormateado = $.fn.dataTable.render.number('.', ',', 2, '$ ').display(importeNeto);
    tr.find('.importe-neto-cell').html(`<span class="${importeNeto < 0 ? 'text-danger fw-bold' : ''}">${numeroFormateado}</span>`);
    
    actualizarTotalPropuesta();
});

    function actualizarEstadoBotonPropuesta() {
        const seleccionados = $('.invoice-checkbox:checked').length;
        $('#btn-enviar-propuesta').prop('disabled', seleccionados === 0);
    }

    // *** MODIFICADO ***: Eventos de selección ahora también actualizan el total
    $('#detalleClienteModal').on('click', '#select-all-invoices', function() {
        $('.invoice-checkbox').prop('checked', this.checked);
        actualizarEstadoBotonPropuesta();
        actualizarTotalPropuesta();
    });

    $('#detalleClienteModal').on('click', '.invoice-checkbox', function() {
        if (!this.checked) {
            $('#select-all-invoices').prop('checked', false);
        }
        actualizarEstadoBotonPropuesta();
        actualizarTotalPropuesta();
    });
    
$('#btn-enviar-propuesta').on('click', function() {
    const codCliente = $('#nombreClienteModal').data('cod-cliente');
    const btn = $(this);
    
    let comprobantesSeleccionados = [];
    let totalNetoSeleccionado = 0;
    const tablaDetalle = $('#tabla-detalle-cliente').DataTable();

    $('#tabla-detalle-cliente .invoice-checkbox:checked').each(function() {
        const tr = $(this).closest('tr');
        const rowData = tablaDetalle.row(tr).data();

        if (rowData) {
            const descuento = parseFloat(tr.find('.descuento-input').val()) || 0;
            const importeNetoRecalculado = parseFloat(rowData.IMPORTE) * (1 - (descuento / 100));
            
            comprobantesSeleccionados.push({
                t_comp: rowData.T_COMP,
                n_comp: rowData.N_COMP,
                importe_bruto: rowData.IMPORTE,
                importe_neto: importeNetoRecalculado,
                porcentaje_descuento: descuento
            });
            totalNetoSeleccionado += importeNetoRecalculado;
        }
    });
    
    if (comprobantesSeleccionados.length === 0) {
        Swal.fire('Atención', 'Por favor, seleccione al menos un comprobante.', 'warning');
        return;
    }

    // ======================= INICIO DE LA SOLUCIÓN =======================
    Swal.fire({
        title: 'Seleccione la Fecha de Pago',
        html: `
            <p class="mb-2">Monto Total de la Propuesta: <strong>${totalNetoSeleccionado.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</strong></p>
            <div id="datepicker-container" class="mt-3"></div>
            <input type="hidden" id="fecha-propuesta-swal">
        `,
        confirmButtonText: 'Crear Propuesta',
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            // Cuando el modal se abre, inicializamos el datepicker dentro de él
            $("#datepicker-container").datepicker({
                dateFormat: "yy-mm-dd",
                minDate: 0, // No permitir fechas pasadas
                onSelect: function(dateText) {
                    // Guardamos la fecha seleccionada en un input oculto
                    $('#fecha-propuesta-swal').val(dateText);
                }
            });
            // Seleccionamos la fecha de hoy por defecto
            $("#datepicker-container").datepicker('setDate', new Date());
            $('#fecha-propuesta-swal').val($.datepicker.formatDate('yy-mm-dd', new Date()));
        },
        preConfirm: () => {
            // Antes de confirmar, nos aseguramos de que se haya seleccionado una fecha
            const fecha = $('#fecha-propuesta-swal').val();
            if (!fecha) {
                Swal.showValidationMessage('Por favor, seleccione una fecha del calendario.');
                return false;
            }
            return fecha;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const fechaPropuesta = result.value;
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Enviando...');

            $.ajax({
                url: 'api/cobranzas_controller.php?action=crear_propuesta',
                type: 'POST',
                data: {
                    cod_cliente: codCliente,
                    comprobantes: comprobantesSeleccionados,
                    total_propuesto: totalNetoSeleccionado,
                    fecha_propuesta_pago: fechaPropuesta
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('¡Éxito!', response.message, 'success');
                        $('#detalleClienteModal').modal('hide');
                        if ($('#gestion-tab').hasClass('active')) {
                            tablaGestion.ajax.reload();
                        }
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error de conexión con el servidor.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-2"></i> Enviar Propuesta');
                }
            });
        }
    });
    // ======================== FIN DE LA SOLUCIÓN =========================
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
                    { 
                        data: 'DESC_COMPRA', title: 'Desc. Compra', className: 'text-end',
                        render: data => (data === null || data === '') ? '' : parseFloat(data).toFixed(4)
                    },
                    { 
                        data: 'DESC_FLETE', title: 'Desc. Flete', className: 'text-end',
                        render: data => (data === null || data === '') ? '' : parseFloat(data).toFixed(4)
                    },
                    { 
                        data: 'DIAS_PP', title: 'Días PP', className: 'text-center',
                        render: data => data === null ? '' : data
                    },
                    { 
                        data: 'DESC_PP', title: 'Desc. PP', className: 'text-end',
                        render: data => (data === null || data === '') ? '' : parseFloat(data).toFixed(4)
                    },
                    {
                        data: null, title: 'Acciones', orderable: false, className: 'text-center',
                        render: function(data, type, row) {
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
    $('#btn-abrir-parametros').on('click', function() {
        $('#alert-container').html('');
        resetFormularioParametros();
        cargarParametros();
    });
    $('#form-parametros').on('submit', function(e) {
        e.preventDefault();
        const action = $('#param-id').val() ? 'update' : 'create';
        let formData = $(this).serialize() + '&action=' + action;
        $.ajax({
            url: 'api/parametros_controller.php', type: 'POST', data: formData, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    resetFormularioParametros();
                    parametrosTable.ajax.reload();
                } else {
                    showAlert('Error: ' + response.message, 'danger');
                }
            }
        });
    });
    $('#tabla-parametros').on('click', '.btn-editar', function() {
        const data = parametrosTable.row($(this).parents('tr')).data();
        $('#form-title').text('Editar Parámetro');
        $('#param-id').val(data.ID);
        $('#param-cod-client').val(data.COD_CLIENT);
        $('#param-desc-compra').val(data.DESC_COMPRA);
        $('#param-desc-flete').val(data.DESC_FLETE);
        $('#param-dias-pp').val(data.DIAS_PP);
        $('#param-desc-pp').val(data.DESC_PP);
        $('#btn-cancelar-edicion').show();
        $('.btn-text').text('Actualizar');
    });
    $('#tabla-parametros').on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        $('#btn-confirmar-delete').data('id', id);
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        confirmModal.show();
    });
    $('#btn-confirmar-delete').on('click', function() {
        const id = $(this).data('id');
        if (id) {
            $.ajax({
                url: 'api/parametros_controller.php', type: 'POST', data: { action: 'delete', id: id }, dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'info');
                        parametrosTable.ajax.reload();
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                complete: function() {
                    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
                    confirmModal.hide();
                }
            });
        }
    });
    $('#btn-cancelar-edicion').on('click', function() {
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
        if ($.fn.DataTable.isDataTable('#tabla-gestion-propuestas')) {
            tablaGestion.ajax.reload();
        } else {
            tablaGestion = $('#tabla-gestion-propuestas').DataTable({
                ajax: { url: 'api/propuestas_controller.php?action=listar_admin', dataSrc: 'data' },
                columns: [
                    { data: 'id', title: 'ID' },
                    { data: 'razon_social', title: 'Cliente' },
                    { data: 'fecha_ultima_modificacion', title: 'Últ. Act.' },
                    { data: 'total_propuesto', title: 'Monto', render: $.fn.dataTable.render.number('.', ',', 2, '$ ') },
                    { 
                        data: 'estado', title: 'Estado',
                        render: function(data) {
                            let badgeClass = 'secondary';
                            if (data === 'PAGADO') badgeClass = 'success';
                            if (data === 'DOCUMENTACION_ADJUNTADA') badgeClass = 'dark';
                            if (data === 'CONTRAPROPUESTA_CLIENTE') badgeClass = 'primary';
                            if (data === 'PENDIENTE_APROBACION_CLIENTE') badgeClass = 'warning';
                            if (data === 'PENDIENTE_APROBACION_FINAL') badgeClass = 'info';
                            if (data === 'ACEPTADA') badgeClass = 'success';
                            return `<span class="badge bg-${badgeClass}">${data.replace(/_/g, ' ')}</span>`;
                        }
                    },
                    {
                        data: null, title: 'Acciones', orderable: false, className: 'text-center',
                        render: function(data, type, row) {
                            return `
                                <button class="btn btn-info btn-sm btn-ver-propuesta-admin" data-id="${row.id}" title="Ver Detalle y Historial">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </button>
                                <button class="btn btn-danger btn-sm ms-1 btn-eliminar-propuesta" data-id="${row.id}" title="Eliminar Propuesta">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            `;
                        }
                    }
                ],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                order: [[2, 'desc']]
            });
        }
    }

    $('#tabla-gestion-propuestas').on('click', '.btn-ver-propuesta-admin', function() {
        const idPropuesta = $(this).data('id');
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
            success: function(response) {
                if (response.success) {
                    renderizarDetallePropuestaAdmin(response.data);
                } else {
                    contentDiv.html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            }
        });
    });

    // *** MODIFICADO ***: Renderiza el modal de gestión con descuentos editables si es necesario
function renderizarDetallePropuestaAdmin(data) {
    const { propuesta, items, historial, adjuntos } = data;
    const contentDiv = $('#detalle-propuesta-content');
    
    // ======================= INICIO DE LA MODIFICACIÓN =======================
    // Creamos las tarjetas de resumen, igual que en el portal del cliente.
    let fechaHtml = propuesta.fecha_propuesta_pago 
        ? new Date(propuesta.fecha_propuesta_pago + 'T00:00:00').toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric' })
        : 'No definida';

    let resumenHtml = `
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-light shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted text-uppercase small">Total Propuesto</h6>
                        <p class="card-text fs-4 fw-bold text-primary mb-0">${(parseFloat(propuesta.total_propuesto) || 0).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-light shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted text-uppercase small">Fecha Propuesta de Pago</h6>
                        <p class="card-text fs-4 fw-bold mb-0">${fechaHtml}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    // ======================== FIN DE LA MODIFICACIÓN =========================

        // ======================= INICIO DE LA NUEVA LÓGICA =======================
    let adjuntosHtml = '';
    // Si el array 'adjuntos' no está vacío, creamos la sección
    if (adjuntos && adjuntos.length > 0) {
        adjuntosHtml = `
            <h5 class="mt-4">Comprobantes Adjuntos</h5>
            <ul class="list-group">
        `;
        
        adjuntos.forEach(adjunto => {
            // Asumimos que la ruta guardada es relativa a la raíz del proyecto
            // Ej: 'uploads/comprobantes/propuesta_13_1670868000.pdf'
            const url = adjunto.ruta_archivo;
            const fechaSubida = new Date(adjunto.fecha_subida).toLocaleString('es-AR');

            adjuntosHtml += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-file-arrow-down me-2"></i>
                        ${adjunto.nombre_archivo}
                        <small class="d-block text-muted">Subido el: ${fechaSubida}</small>
                    </div>
                    <a href="${url}" target="_blank" class="btn btn-outline-primary btn-sm">
                        Descargar
                    </a>
                </li>
            `;
        });

        adjuntosHtml += '</ul>';
    }
    // ======================== FIN DE LA NUEVA LÓGICA =========================
    
    let esEditable = propuesta.estado === 'CONTRAPROPUESTA_CLIENTE';

    let itemsHtml = `
        <h5 class="mt-4">Facturas Incluidas</h5>
        <table class="table table-sm table-bordered" id="tabla-detalle-propuesta-admin">
            <thead class="table-light">
                <tr>
                    <th>Comprobante</th>
                    <th class="text-end">Importe Bruto</th>
                    <th class="text-center">% Descuento</th>
                    <th class="text-end">Importe Neto</th>
                    ${esEditable ? '<th class="text-center">Acciones</th>' : ''} <!-- NUEVA COLUMNA -->
                </tr>
            </thead>
            <tbody>`;

        let totalNeto = 0;
        items.forEach(item => {
            const bruto = parseFloat(item.importe_bruto) || 0;
            const neto = parseFloat(item.importe_neto) || 0;
            const descuento = parseFloat(item.porcentaje_descuento) || 0;
            totalNeto += neto;
            
        const descuentoHtml = esEditable 
            ? `<input type="number" class="form-control form-control-sm descuento-input-admin" value="${descuento.toFixed(2)}" min="0" max="100" step="0.01" style="width: 80px;">`
            : `${descuento.toFixed(2)} %`;

        // Añadimos el botón de eliminar solo si es editable
        const accionesHtml = esEditable 
            ? `<td class="text-center"><button class="btn btn-danger btn-sm btn-eliminar-factura-propuesta" title="Quitar factura de la propuesta"><i class="fa-solid fa-trash"></i></button></td>`
            : '';

        itemsHtml += `
                <tr data-importe-bruto="${bruto}" data-ncomp="${item.n_comp_factura}">
                    <td>${item.n_comp_factura}</td>
                    <td class="text-end">${bruto.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>
                    <td class="text-center">${descuentoHtml}</td>
                    <td class="text-end fw-bold importe-neto-cell-admin">${neto.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>
                    ${accionesHtml} <!-- NUEVO TD -->
                </tr>`;
    });
    itemsHtml += `
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="${esEditable ? 4 : 3}" class="text-end"><strong>Total Propuesta:</strong></td>
                    <td class="text-end fw-bolder fs-5" id="total-propuesta-admin">${totalNeto.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>
                    ${esEditable ? '<td></td>' : ''}
                </tr>
            </tfoot>
        </table>`;
        
        let historialHtml = '<h5>Historial</h5><div class="timeline">';
        historial.forEach(h => {
            const icon = h.tipo_usuario === 'ADMIN' ? 'fa-user-shield' : 'fa-user-tie';
            const align = h.tipo_usuario === 'ADMIN' ? 'left' : 'right';
            historialHtml += `<div class="timeline-item timeline-item-${align}"><div class="timeline-icon"><i class="fas ${icon}"></i></div><div class="timeline-content"><span class="timeline-date">${h.fecha_evento}</span><p><strong>${h.descripcion}</strong></p>${h.comentario ? `<p class="fst-italic bg-light p-2 rounded">"${h.comentario}"</p>` : ''}</div></div>`;
        });
        historialHtml += '</div>';

    let accionAdminHtml = '';
    
    if (esEditable) {

        // Obtenemos la fecha actual en el formato YYYY-MM-DD para el input
        const fechaActual = propuesta.fecha_propuesta_pago ? propuesta.fecha_propuesta_pago.split(' ')[0] : new Date().toISOString().split('T')[0];
        
        accionAdminHtml = `
            <div class="card bg-light border-primary mt-4">
                <div class="card-body">
                    <h5 class="card-title">Acción Requerida</h5>
                    <p>El cliente ha enviado una contrapropuesta. Ajuste los descuentos, quite facturas o cambie la fecha de pago si es necesario y envíe la propuesta final.</p>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha-propuesta-admin" class="form-label"><strong>Fecha Propuesta de Pago:</strong></label>
                            <input type="date" class="form-control" id="fecha-propuesta-admin" value="${fechaActual}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="comentario-admin" class="form-label"><strong>Comentario para el Cliente (Opcional):</strong></label>
                        <textarea class="form-control" id="comentario-admin" rows="2" placeholder="Ej: Aceptamos quitar las facturas, se ajusta el monto final."></textarea>
                    </div>

                    <button class="btn btn-primary" id="btn-enviar-propuesta-final" data-id="${propuesta.id}">
                        <i class="fa-solid fa-paper-plane"></i> Enviar Propuesta Final
                    </button>
                </div>
            </div>
        `;
    }

        contentDiv.html(resumenHtml + itemsHtml + historialHtml + accionAdminHtml + adjuntosHtml);
    }
    
    // *** NUEVO EVENTO ***: Lógica de recálculo para el modal de GESTIÓN
    $('#detallePropuestaModal').on('input', '.descuento-input-admin', function() {
        const input = $(this);
        const tr = input.closest('tr');
        const importeBruto = parseFloat(tr.data('importe-bruto'));
        let descuento = parseFloat(input.val());

        if (isNaN(descuento) || descuento < 0) descuento = 0;
        if (descuento > 100) {
            descuento = 100;
            input.val(100);
        }

        const importeNeto = importeBruto * (1 - (descuento / 100));
        tr.find('.importe-neto-cell-admin').text(importeNeto.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));

        let totalGeneral = 0;
        $('#tabla-detalle-propuesta-admin tbody tr').each(function() {
             const cellNeto = $(this).find('.importe-neto-cell-admin');
             const valorNeto = parseFloat(cellNeto.text().replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0;
             totalGeneral += valorNeto;
        });
        $('#total-propuesta-admin').text(totalGeneral.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));
    });
    
    // Evento para el botón de enviar propuesta final (la lógica de recolección está lista)
$('#detallePropuestaModal').on('click', '#btn-enviar-propuesta-final', function() {
    const idPropuesta = $(this).data('id');
    const comentario = $('#comentario-admin').val();
    const btn = $(this);
    
    // ======================= LÍNEA AÑADIDA PARA SOLUCIONAR EL ERROR =======================
    // Aquí creamos la variable 'fechaPropuesta' que faltaba, tomando el valor del input de fecha.
    const fechaPropuesta = $('#fecha-propuesta-admin').val(); 
    // =======================================================================================
    
    let comprobantesActualizados = [];
    let totalPropuestoActualizado = 0;

    $('#tabla-detalle-propuesta-admin tbody tr').each(function() {
        const tr = $(this);
        const neto = parseFloat(tr.find('.importe-neto-cell-admin').text().replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0;
        comprobantesActualizados.push({
            n_comp: tr.data('ncomp'),
            importe_bruto: tr.data('importe-bruto'),
            porcentaje_descuento: parseFloat(tr.find('.descuento-input-admin').val()) || 0,
            importe_neto: neto
        });
        totalPropuestoActualizado += neto;
    });

    // Validamos que la fecha no esté vacía
    if (!fechaPropuesta) {
        alert('Por favor, seleccione una fecha propuesta de pago.');
        return;
    }

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Enviando...');

    $.ajax({
        url: 'api/propuestas_controller.php?action=actualizar_propuesta_admin',
        type: 'POST',
        data: { 
            id_propuesta: idPropuesta, 
            nuevo_estado: 'PENDIENTE_APROBACION_FINAL',
            comentario: comentario,
            total_propuesto: totalPropuestoActualizado,
            comprobantes: comprobantesActualizados,
            fecha_propuesta_pago: fechaPropuesta // Ahora la variable 'fechaPropuesta' sí existe
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                $('#detallePropuestaModal').modal('hide');
                tablaGestion.ajax.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error de conexión al servidor.');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i> Enviar Propuesta Final');
        }
    });
});
    // Evento para eliminar una factura de la propuesta en el modal de gestión
    $('#detallePropuestaModal').on('click', '.btn-eliminar-factura-propuesta', function() {
        if (confirm('¿Estás seguro de que quieres quitar esta factura de la propuesta?')) {
            const tr = $(this).closest('tr');
            tr.fadeOut(400, function() { 
                $(this).remove();
                // Forzamos el recálculo del total
                $('.descuento-input-admin').first().trigger('input');
            });
        }
    }); // <--- Cierre del evento de eliminar

    // ======================= EVENTO DE SINCRONIZACIÓN CORREGIDO Y EN SU LUGAR =======================
    $('#btn-sincronizar-estados').on('click', function() {
        const btn = $(this);
        const icon = btn.find('i');
        
        icon.addClass('fa-spin');
        btn.prop('disabled', true);

        $.ajax({
            url: 'api/propuestas_controller.php?action=sincronizar_estados_pagados',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Sincronización Completa',
                        text: response.message,
                        icon: 'info'
                    });
                    if ($('#gestion-tab').hasClass('active')) {
                        tablaGestion.ajax.reload();
                        cargarDashboardGestion(); // Recargamos también los KPIs
                    }
                } else {
                    Swal.fire('Error', 'Error en la sincronización: ' + response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de conexión con el servidor.', 'error');
            },
            complete: function() {
                icon.removeClass('fa-spin');
                btn.prop('disabled', false);
            }
        });
    });
// Lógica para eliminar propuesta con SweetAlert2
    $('#tabla-gestion-propuestas').on('click', '.btn-eliminar-propuesta', function() {
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
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('¡Eliminada!', response.message, 'success');
                            tablaGestion.ajax.reload();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    }
                });
            }
        });
    });
    // =========================================================================================================

}); // <--- ESTE ES EL ÚNICO Y CORRECTO CIERRE PARA $(document).ready()