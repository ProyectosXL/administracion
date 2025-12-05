$(document).ready(function() {
    
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

    // Columnas para la VISTA DETALLE (dentro del modal)
// Mantén esta definición de columnas fuera para que sea accesible
const columnsDetalle = [
    {
        data: null,
        orderable: false,
        className: 'select-checkbox text-center',
        title: '<input type="checkbox" class="form-check-input" id="select-all-invoices" title="Seleccionar Todo">',
        render: function(data, type, row) {
            return `<input type="checkbox" class="form-check-input invoice-checkbox" value="${row.N_COMP}">`;
        }
    },
    { data: 'FECHA_EMIS', title: 'F. Emisión' },
    { data: 'T_COMP', title: 'Tipo' },
    { data: 'N_COMP', title: 'Comprobante' },
    { data: 'IMPORTE', title: 'Importe Bruto', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '$ ') },
    { data: 'IMPORTE_NETO', title: 'Importe Neto', className: 'text-end', render: $.fn.dataTable.render.number('.', ',', 2, '$ ') },
    { data: 'FECHA_PROB_COBRO', title: 'F. Prob. Cobro' },
    { data: 'PPP', title: 'PPP', className: 'text-center' } // Nueva columna PPP
];

    // Función para inicializar las tablas principales (resumen)
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
            
            // --- CAMBIOS PRINCIPALES ---
            // 1. Eliminamos fixedHeader porque scrollY lo reemplaza
            // fixedHeader: true, 

            // 2. Activamos el scroll vertical para el cuerpo de la tabla
            scrollY: '55vh', // Altura del área de scroll (55% de la altura de la ventana)
            scrollCollapse: true, // La tabla se encoge si hay pocos registros
            paging: true // Mantenemos la paginación
        });
    }
}

    // NUEVA FUNCIÓN PARA ACTUALIZAR LAS TARJETAS
function actualizarCardsDeResumen(summary) {
    const options = { style: 'currency', currency: 'ARS', minimumFractionDigits: 2 };
    
    $('#summary-total-neto').text(summary.totalNeto.toLocaleString('es-AR', options));
    $('#summary-total-comprobantes').text(summary.totalComprobantes.toLocaleString('es-AR'));
    $('#summary-total-clientes').text(summary.totalClientes.toLocaleString('es-AR'));
}

    // Carga inicial de Franquicias
    initializeDataTable('#tabla-franquicias', 'api/cobranzas_controller.php?tipo=franquicias');

    // Manejar el clic en las pestañas para cargar la tabla correspondiente
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr("id");
        if (target === 'franquicias-tab') {
            initializeDataTable('#tabla-franquicias', 'api/cobranzas_controller.php?tipo=franquicias');
        } else if (target === 'mayoristas-tab') {
            initializeDataTable('#tabla-mayoristas', 'api/cobranzas_controller.php?tipo=mayoristas');
        }
    });


    // --- LÓGICA PARA ABRIR Y CARGAR EL MODAL DE DETALLE ---
    let detalleTable = null; // Variable para guardar la instancia de la tabla de detalle

// --- LÓGICA PARA ABRIR Y CARGAR EL MODAL DE DETALLE (CON SELECCIÓN DE EMAIL) ---

$('.tab-content').on('click', '.btn-detalle', function() {
    const codClient = $(this).data('cod-client');
    const razonSoci = $(this).data('razon-soci');
    const tipo = $('.nav-tabs .nav-link.active').attr('id').includes('franquicias') ? 'franquicias' : 'mayoristas';
    const urlDetalle = `api/cobranzas_controller.php?tipo=${tipo}&cod_client=${codClient}`;
    
    $('#nombreClienteModal').text(razonSoci);
    const container = $('#detalle-content-container');
    
    const loadingHtml = `
        <div class="d-flex justify-content-center align-items-center" style="min-height: 250px;">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
            <strong class="ms-3 h5">Cargando...</strong>
        </div>`;

    container.html(loadingHtml);
    $('#btn-enviar-email-seleccion').prop('disabled', true); // Deshabilitar botón al cargar
    
    const detalleModal = new bootstrap.Modal(document.getElementById('detalleClienteModal'));
    detalleModal.show();

    $.ajax({
        url: urlDetalle,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            container.empty();
            container.append('<table id="tabla-detalle-cliente" class="table table-striped table-hover" style="width:100%"></table>');
            
            $('#tabla-detalle-cliente').DataTable({
                data: response.data,
                columns: columnsDetalle,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                order: [[1, 'desc']], // Ordenar por F. Emisión
                dom: 'Bfrtip',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fa-solid fa-file-excel"></i> Exportar a Excel',
                    className: 'btn btn-success btn-sm',
                    title: `Detalle Cobranza - ${razonSoci}`
                }],
                destroy: true,
                select: { // Necesario para que DataTables maneje la selección
                    style: 'os',
                    selector: 'td:first-child'
                }
            });
        },
        error: function() {
            container.html('<div class="alert alert-danger">Error al cargar los datos. Por favor, intente de nuevo.</div>');
        }
    });
});

// --- NUEVA LÓGICA PARA MANEJAR LA SELECCIÓN Y EL BOTÓN ---

// Función para habilitar/deshabilitar el botón de envío
function actualizarEstadoBotonEmail() {
    const seleccionados = $('.invoice-checkbox:checked').length;
    $('#btn-enviar-email-seleccion').prop('disabled', seleccionados === 0);
}

// Evento para el checkbox "Seleccionar Todo"
$('#detalleClienteModal').on('click', '#select-all-invoices', function() {
    $('.invoice-checkbox').prop('checked', this.checked);
    actualizarEstadoBotonEmail();
});

// Evento para los checkboxes individuales
$('#detalleClienteModal').on('click', '.invoice-checkbox', function() {
    // Si se desmarca uno, desmarcar "Seleccionar Todo"
    if (!this.checked) {
        $('#select-all-invoices').prop('checked', false);
    }
    actualizarEstadoBotonEmail();
});

// Evento para el botón de Enviar Email
$('#btn-enviar-email-seleccion').on('click', function() {
    const comprobantesSeleccionados = [];
    $('.invoice-checkbox:checked').each(function() {
        comprobantesSeleccionados.push($(this).val());
    });

    if (comprobantesSeleccionados.length > 0) {
        alert("Comprobantes seleccionados para enviar por email:\n\n" + comprobantesSeleccionados.join('\n'));
        // Futuro: Aquí iría la llamada AJAX para enviar el email
        // $.post('api/enviar_email_controller.php', { comprobantes: comprobantesSeleccionados }, function(response) { ... });
    } else {
        alert("Por favor, seleccione al menos un comprobante.");
    }
});

// --- LÓGICA PARA EL MODAL DE PARÁMETROS (REINTEGRADA Y MEJORADA) ---

    let parametrosTable = null; // Variable para la instancia de la tabla de parámetros

    // Función para mostrar alertas bonitas dentro del modal
    function showAlert(message, type = 'success') {
        const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                              ${message}
                              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                           </div>`;
        $('#alert-container').html(alertHtml);
    }

    // Función para cargar o recargar los datos en la tabla de parámetros
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
                        data: 'DESC_COMPRA', 
                        title: 'Desc. Compra',
                        className: 'text-end', // Alinea los números a la derecha
                        render: function(data) {
                            // Si el dato es nulo o vacío, devuelve un string vacío
                            if (data === null || data === '') return '';
                            // Convierte a número y formatea a 4 decimales
                            return parseFloat(data).toFixed(4);
                        }
                    },
                    { 
                        data: 'DESC_FLETE', 
                        title: 'Desc. Flete',
                        className: 'text-end',
                        render: function(data) {
                            if (data === null || data === '') return '';
                            return parseFloat(data).toFixed(4);
                        }
                    },
                    { 
                        data: 'DIAS_PP', 
                        title: 'Días PP',
                        className: 'text-center', // Centra los enteros
                        render: function(data) {
                            // Para los enteros, solo nos aseguramos de que no muestre 'null'
                            return data === null ? '' : data;
                        }
                    },
                    { 
                        data: 'DESC_PP', 
                        title: 'Desc. PP',
                        className: 'text-end',
                        render: function(data) {
                            if (data === null || data === '') return '';
                            return parseFloat(data).toFixed(4);
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
                order: [[1, 'asc']] // Ordenar por código de cliente
            });
        }
    }

    // Evento que se dispara al hacer clic en el engranaje para abrir el modal
    $('#btn-abrir-parametros').on('click', function() {
        $('#alert-container').html(''); // Limpiar alertas anteriores
        resetFormularioParametros();
        cargarParametros();
    });

    // Enviar el formulario para Crear o Actualizar un parámetro
    $('#form-parametros').on('submit', function(e) {
        e.preventDefault();
        const action = $('#param-id').val() ? 'update' : 'create';
        let formData = $(this).serialize() + '&action=' + action;

        $.ajax({
            url: 'api/parametros_controller.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    resetFormularioParametros();
                    parametrosTable.ajax.reload(); // Recargar la tabla eficientemente
                } else {
                    showAlert('Error: ' + response.message, 'danger');
                }
            }
        });
    });
    
    // Rellenar el formulario al hacer clic en el botón Editar
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

    // Eliminar un parámetro
$('#tabla-parametros').on('click', '.btn-eliminar', function() {
    const id = $(this).data('id');
    // Guardamos el ID en el botón de confirmación del nuevo modal
    $('#btn-confirmar-delete').data('id', id);
    // Abrimos el modal de confirmación
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    confirmModal.show();
});

$('#btn-confirmar-delete').on('click', function() {
    const id = $(this).data('id'); // Recuperamos el ID que guardamos antes

    if (id) {
        $.ajax({
            url: 'api/parametros_controller.php',
            type: 'POST',
            data: { action: 'delete', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'info'); // Muestra la alerta bonita
                    parametrosTable.ajax.reload(); // Recarga la tabla
                } else {
                    showAlert('Error: ' + response.message, 'danger');
                }
            },
            finally: function() {
                // Ocultamos el modal de confirmación, haya funcionado o no
                const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
                confirmModal.hide();
            }
        });
    }
});

    // Resetear el formulario con el botón Cancelar
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

});