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
        error: function() {
            // Añadimos un log de error para fallos de conexión
            console.error("Fallo la llamada AJAX para cargar el dashboard del admin.");
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
    } else {
        // --- VISTAS PENDIENTES (FRANQUICIAS Y MAYORISTAS) ---
        $('#summary-cards').show(); // Mostramos los KPIs de deuda total
        $('#btn-abrir-parametros').show(); // Mostramos el botón de parámetros
        
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
            success: function(response) {
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
            error: function() {
                container.html('<div class="alert alert-danger">Error al cargar los datos.</div>');
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
        
        $('#tabla-detalle-cliente .invoice-checkbox:checked').each(function() {
            const tr = $(this).closest('tr');
            const rowData = tablaDetalle.row(tr).data();
            const esNC = rowData.T_COMP.trim().startsWith('NC');
            
            let bruto = parseFloat(rowData.IMPORTE);
            // Leemos el neto de la celda, que ya está calculado y tiene el signo correcto
            let neto = parseFloat(tr.find('.importe-neto-cell').text().replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0;
            
            totalBruto += esNC ? -bruto : bruto;
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

    $('#detalleClienteModal').on('change', 'input[type="checkbox"]', function() {
        
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
    
    $('#btn-enviar-propuesta').on('click', function() {
        // ======================= ESTA LÍNEA ES LA CORRECCIÓN =======================
        // Leemos el código del cliente desde el modal, donde lo guardamos antes
        const codCliente = $('#detalleClienteModal').data('cod-cliente');
        // =========================================================================
        const btn = $(this);
    
    let comprobantesSeleccionados = [];
    let totalNetoSeleccionado = 0;
    const tablaDetalle = $('#tabla-detalle-cliente').DataTable();

    $('#tabla-detalle-cliente .invoice-checkbox:checked').each(function() {
        const tr = $(this).closest('tr');
        const rowData = tablaDetalle.row(tr).data();

        if (rowData) {
            const descuento = parseFloat(tr.find('.descuento-input').val()) || 0;
            const importeBrutoOriginal = parseFloat(rowData.IMPORTE);
            let importeNetoRecalculado = importeBrutoOriginal * (1 - (descuento / 100));
            const esNC = rowData.T_COMP.trim().startsWith('NC');
            
            comprobantesSeleccionados.push({
                t_comp: rowData.T_COMP,
                n_comp: rowData.N_COMP,
                importe_bruto: importeBrutoOriginal,
                importe_neto: importeNetoRecalculado,
                porcentaje_descuento: descuento
            });
            
            totalNetoSeleccionado += esNC ? -importeNetoRecalculado : importeNetoRecalculado;
        }
    });
    
    if (comprobantesSeleccionados.length === 0) {
        Swal.fire('Atención', 'Por favor, seleccione al menos un comprobante.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Finalizar Propuesta',
        html: `
            <p class="mb-2">Monto Total de la Propuesta: <strong>${totalNetoSeleccionado.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</strong></p>
            <div class="mb-3 text-start">
                <label for="swal-medio-pago" class="form-label">Medio de Pago</label>
                <select id="swal-medio-pago" class="form-select">
                    <option value="ECHECK" selected>ECHECK</option>
                    <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                </select>
            </div>
            <div class="mb-2 text-start">
                <label class="form-label">Fecha Propuesta de Pago</label>
                <div id="datepicker-container-swal"></div>
                <input type="hidden" id="fecha-propuesta-swal-input">
            </div>
        `,
        confirmButtonText: 'Crear Propuesta',
        showCancelButton: true,
        didOpen: () => {
            $("#datepicker-container-swal").datepicker({
                dateFormat: "yy-mm-dd", minDate: 0,
                onSelect: function(dateText) {
                    $('#fecha-propuesta-swal-input').val(dateText);
                }
            });
            const hoy = new Date();
            $("#datepicker-container-swal").datepicker('setDate', hoy);
            $('#fecha-propuesta-swal-input').val($.datepicker.formatDate('yy-mm-dd', hoy));
        },
        preConfirm: () => {
            const fecha = $('#fecha-propuesta-swal-input').val();
            if (!fecha) {
                Swal.showValidationMessage('Por favor, seleccione una fecha.');
                return false;
            }
            return {
                fecha: fecha,
                medioPago: $('#swal-medio-pago').val()
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { fecha, medioPago } = result.value;
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: 'api/cobranzas_controller.php?action=crear_propuesta',
                type: 'POST',
                data: {
                    // La variable 'codCliente' definida al principio del evento es visible aquí
                    cod_cliente: codCliente, 
                    comprobantes: comprobantesSeleccionados,
                    total_propuesto: totalNetoSeleccionado,
                    fecha_propuesta_pago: fecha,
                    medio_de_pago: medioPago
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('¡Éxito!', response.message, 'success').then(() => {
                            $('#detalleClienteModal').modal('hide');
                            // Verificamos si la tabla de gestión existe antes de recargarla
                            if (tablaGestion) {
                                tablaGestion.ajax.reload();
                            }
                        });
                    } else {
                        // Mostramos el mensaje de error específico que viene del backend
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Mostramos un error más detallado en la consola
                    console.error("Error AJAX:", jqXHR.responseText, textStatus, errorThrown);
                    Swal.fire('Error de Comunicación', 'No se pudo completar la solicitud. Revisa la consola para más detalles.', 'error');
                },
                complete: function() {
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
                        render: function(data) {
                            // Convertimos de 0.08 a 8.00 %
                            return (parseFloat(data) * 100).toFixed(2) + ' %';
                        }
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
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    resetFormularioParametros();
                    parametrosTable.ajax.reload();
                } else {
                    showAlert('Error: ' + response.message, 'danger');
                }
            },
            error: function() {
                showAlert('Error de conexión con el servidor.', 'danger');
            }
        });
    });
$('#tabla-parametros').on('click', '.btn-editar', function() {
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
        // 1. Preparamos la interfaz para la vista de gestión
        $('#summary-cards').hide();
        $('#gestion-dashboard').show();
        $('#btn-abrir-parametros').hide();

        // 2. Cargamos/Recargamos los datos del dashboard (KPIs y gráficos)
        cargarDashboardGestion();

        // 3. Ejecutamos la sincronización en segundo plano
        sincronizarEstados();
        
        // 4. Inicializamos o recargamos la tabla de DataTables
        if (tablaGestion) {
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

$('body').on('click', '.btn-ver-propuesta-admin', function() {
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
        success: function(response) {
            if (response.success) {
                renderizarDetallePropuestaAdmin(response.data);
            } else {
                contentDiv.html(`<div class="alert alert-danger">${response.message}</div>`);
            }
        },
        error: function() {
            contentDiv.html('<div class="alert alert-danger">Error al cargar los detalles.</div>');
        }
    });
});
// ======================== FIN DE LA CORRECCIÓN DEL EVENTO =========================
    // *** MODIFICADO ***: Renderiza el modal de gestión con descuentos editables si es necesario
function renderizarDetallePropuestaAdmin(data) {
    const { propuesta, items, historial, adjuntos } = data;
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
                        <p class="card-text fs-4 fw-bold text-primary mb-0">${(parseFloat(propuesta.total_propuesto) || 0).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted text-uppercase small">Fecha Propuesta de Pago</h6>
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

    let adjuntosHtml = '';
    if (adjuntos && adjuntos.length > 0) {
        adjuntosHtml = `<h5 class="mt-4"><i class="fa-solid fa-folder-open me-2"></i>Comprobantes Adjuntos</h5><ul class="list-group">`;
        adjuntos.forEach(adjunto => {
            const url = adjunto.ruta_archivo; // Usar la ruta relativa que guardas
            const fechaSubida = new Date(adjunto.fecha_subida).toLocaleString('es-AR');
            adjuntosHtml += `<li class="list-group-item d-flex justify-content-between align-items-center"><div><i class="fa-solid fa-file-arrow-down me-2"></i> ${adjunto.nombre_archivo}<small class="d-block text-muted">Subido el: ${fechaSubida}</small></div><a href="${url}" target="_blank" class="btn btn-outline-primary btn-sm">Descargar</a></li>`;
        });
        adjuntosHtml += '</ul>';
    }
    
    let esEditable = propuesta.estado === 'CONTRAPROPUESTA_CLIENTE';
    let itemsHtml = `<h5 class="mt-4">Facturas Incluidas</h5><table class="table table-sm table-bordered" id="tabla-detalle-propuesta-admin"><thead class="table-light"><tr><th class="text-center">Tipo</th><th>Comprobante</th><th class="text-end">Importe Bruto</th><th class="text-center">% Descuento</th><th class="text-end">Importe Neto</th>${esEditable ? '<th class="text-center">Acciones</th>' : ''}</tr></thead><tbody>`;

    let totalBrutoTabla = 0;
    let totalNetoTabla = 0;

    items.forEach(item => {
        const bruto = parseFloat(item.importe_bruto) || 0;
        const neto = parseFloat(item.importe_neto) || 0;
        const descuento = parseFloat(item.porcentaje_descuento) || 0;
        const esNC = item.t_comp_factura && item.t_comp_factura.trim().startsWith('NC');
        
        totalBrutoTabla += esNC ? -bruto : bruto;
        totalNetoTabla += esNC ? -neto : neto;

        const descuentoHtml = esEditable ? `<input type="number" class="form-control form-control-sm descuento-input-admin" value="${descuento.toFixed(2)}" min="0" max="100" step="0.01" style="width: 80px;">` : `${descuento.toFixed(2)} %`;
        const accionesHtml = esEditable ? `<td class="text-center"><button class="btn btn-danger btn-sm btn-eliminar-factura-propuesta" title="Quitar factura"><i class="fa-solid fa-trash"></i></button></td>` : '';

        itemsHtml += `<tr data-importe-bruto="${bruto}" data-ncomp="${item.n_comp_factura}" data-tcomp="${item.t_comp_factura || ''}"><td class="text-center">${item.t_comp_factura || 'N/A'}</td><td>${item.n_comp_factura}</td><td class="text-end ${esNC ? 'text-danger' : ''}">${(esNC ? -bruto : bruto).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td><td class="text-center">${descuentoHtml}</td><td class="text-end fw-bold importe-neto-cell-admin ${esNC ? 'text-danger' : ''}">${(esNC ? -neto : neto).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>${accionesHtml}</tr>`;
    });
    
    itemsHtml += `</tbody><tfoot class="table-light"><tr><td colspan="2" class="text-end"><strong>Totales:</strong></td><td class="text-end fw-bolder" id="total-bruto-tabla">${totalBrutoTabla.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td><td></td><td class="text-end fw-bolder" id="total-neto-tabla">${totalNetoTabla.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>${esEditable ? '<td></td>' : ''}</tr></tfoot></table>`;
        
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
        
        historialHtml += `
            <div class="timeline-item timeline-item-${align}">
                <div class="timeline-icon"><i class="fas ${icon}"></i></div>
                <div class="timeline-content">
                    <span class="timeline-date">${h.fecha_evento}</span>
                    <p><strong>${h.descripcion}</strong></p>
                    ${h.comentario ? `<p class="fst-italic bg-light p-2 rounded">"${h.comentario}"</p>` : ''}
                    ${adjuntoHtml} <!-- Se añade el HTML de la imagen aquí -->
                </div>
            </div>`;
    });
    historialHtml += '</div>';
    // ======================== FIN DE LA MODIFICACIÓN DEL HISTORIAL ========================

    let accionAdminHtml = '';
    
    if (esEditable) {
        accionAdminHtml = `<div class="card bg-light border-primary mt-4"><div class="card-body"><h5 class="card-title">Acción Requerida: Contrapropuesta del Cliente</h5><p>El cliente ha propuesto nuevas condiciones. Revise los cambios y decida si aceptar la contrapropuesta.</p><div class="mb-3"><label for="comentario-admin" class="form-label"><strong>Añadir un comentario (Opcional):</strong></label><textarea class="form-control" id="comentario-admin" rows="2"></textarea></div>                <div class="mb-3">
                    <label for="historial-adjunto-admin" class="form-label"><small>Adjuntar imagen (opcional):</small></label>
                    <input class="form-control form-control-sm" type="file" id="historial-adjunto-admin" accept="image/png, image/jpeg, image/gif">
                </div><button class="btn btn-success" id="btn-aceptar-contrapropuesta" data-id="${propuesta.id}"><i class="fa-solid fa-check-double me-2"></i> Aceptar Contrapropuesta</button></div></div>`;
    }

    const footer = $('#detallePropuestaModal .modal-footer');
    footer.find('#btn-exportar-excel').remove();
    footer.prepend('<button class="btn btn-success me-auto" id="btn-exportar-excel"><i class="fa-solid fa-file-excel me-2"></i>Exportar a Excel</button>');
    $('#btn-exportar-excel').show();

    contentDiv.html(resumenHtml + itemsHtml  + historialHtml + adjuntosHtml + accionAdminHtml);
}

$('#detallePropuestaModal').on('click', '#btn-exportar-excel', function() {
    // Necesitamos los datos de la propuesta, que obtendremos de la misma llamada AJAX
    const idPropuesta = $('#detallePropuestaModalLabel').text().replace('Revisar Propuesta #', '');
    $.ajax({
        url: `api/propuestas_controller.php?action=ver_detalle&id=${idPropuesta}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                exportarPropuestaExcel(response.data);
            }
        }
    });
});
    
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
        recalcularTotalesAdmin();
    });
    
$('#detallePropuestaModal').on('click', '#btn-aceptar-contrapropuesta', function() {
    const idPropuesta = $(this).data('id');
    const comentario = $('#comentario-admin').val();
    const btn = $(this);

    // ======================= INICIO DE LA RECOLECCIÓN DE DATOS =======================
    let comprobantesFinales = [];
    let totalNetoFinal = 0;

    $('#tabla-detalle-propuesta-admin tbody tr').each(function() {
        const tr = $(this);
        const bruto = parseFloat(tr.data('importe-bruto'));
        const descuento = parseFloat(tr.find('.descuento-input-admin').val()) || 0;
        const neto = bruto * (1 - (descuento / 100));

        comprobantesFinales.push({
            t_comp: tr.data('tcomp'),
            n_comp: tr.data('ncomp'),
            importe_bruto: bruto,
            importe_neto: neto,
            porcentaje_descuento: descuento
        });
        
        // El neto ya tiene el signo correcto, lo leemos de la celda
        const netoTexto = tr.find('.importe-neto-cell-admin').text();
        const netoReal = parseFloat(netoTexto.replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0;
        totalNetoFinal += netoReal;
    });
    // ======================== FIN DE LA RECOLECCIÓN DE DATOS =========================

    Swal.fire({
        title: '¿Aceptar y guardar cambios?',
        html: `La propuesta cambiará a estado 'ACEPTADA' con un nuevo total de <strong>${totalNetoFinal.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</strong>. ¿Continuar?`,
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
                success: function(response) {
                    if (response.success) {
                        Swal.fire('¡Éxito!', response.message, 'success');
                        $('#detallePropuestaModal').modal('hide');
                        tablaGestion.ajax.reload(); // Recarga la tabla principal
                        cargarDashboardGestion(); // Recarga los KPIs
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error de conexión.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        }
    });
});
$('#detallePropuestaModal').on('click', '.btn-eliminar-factura-propuesta', function() {
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
            tr.fadeOut(300, function() { 
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
        success: function(response) {
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
        error: function() {
            Swal.close();
            console.error('Error de conexión durante la sincronización automática.');
        }
    });
}

// ===== NUEVA FUNCIÓN DE RECÁLCULO PARA EL MODAL ADMIN =====
function recalcularTotalesAdmin() {
    let totalBruto = 0;
    let totalNeto = 0;

    $('#tabla-detalle-propuesta-admin tbody tr').each(function() {
        const tr = $(this);
        const bruto = parseFloat(tr.data('importe-bruto'));
        const esNC = tr.data('tcomp').trim().startsWith('NC');
        const netoTexto = tr.find('.importe-neto-cell-admin').text();
        const neto = parseFloat(netoTexto.replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0;
        
        totalBruto += esNC ? -bruto : bruto;
        totalNeto += neto; // El neto ya tiene el signo correcto por la visualización
    });
    
    const f = (num) => num.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });

    $('#total-bruto-tabla').text(f(totalBruto));
    $('#total-neto-tabla').text(f(totalNeto));
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
        if(ws[cell]) ws[cell].s = { font: { bold: true } };
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
    ws['!cols'] = [ {wch:10}, {wch:20}, {wch:15}, {wch:12}, {wch:15} ];

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
        success: function(response) {
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
                    dateClick: function(info) {
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

                    eventClick: function(info) {
                        // Mantenemos la funcionalidad de abrir el detalle completo al hacer clic en un evento específico
                        const idPropuesta = info.event.extendedProps.id;
                        // Simulamos un clic en un botón que ya tiene el evento asignado
                        const btn = $('<button class="btn-ver-propuesta-admin" data-id="' + idPropuesta + '"></button>');
                        $('body').append(btn);
                        btn.click();
                        btn.remove();
                    },
                    eventDidMount: function(info) {
                        $(info.el).tooltip({ title: info.event.extendedProps.cliente, placement: 'top', trigger: 'hover', container: 'body' });
                    }
                });
                
                calendarInstance.render();
            }
        }
    });
}
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