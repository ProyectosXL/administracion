$(document).ready(function() {

        // ======================= INICIO DE LA NUEVA LÓGICA =======================
    // Nueva función para cargar los KPIs
    function cargarKPIsCliente() {
        $.ajax({
            url: 'api/propuestas_controller.php?action=obtener_kpis_cliente',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    const options = { style: 'currency', currency: 'ARS' };

                    $('#kpi-deuda-total').text(parseFloat(data.deudaTotalPendiente).toLocaleString('es-AR', options));
                    $('#kpi-monto-negociacion').text(parseFloat(data.montoEnNegociacion).toLocaleString('es-AR', options));
                    $('#kpi-requiere-accion').text(data.propuestasRequierenAccion);
                }
            }
        });
    }

    // Llamamos a la función al cargar la página
    cargarKPIsCliente();
    // ======================== FIN DE LA NUEVA LÓGICA =========================

    // 1. INICIALIZAR LA TABLA PRINCIPAL DE PROPUESTAS
    const tablaPropuestas = $('#tabla-propuestas-cliente').DataTable({
        ajax: {
            url: 'api/propuestas_controller.php?action=listar_cliente',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id', title: 'ID Propuesta' },
            { data: 'fecha_creacion', title: 'Fecha Creación' },
            { data: 'total_propuesto', title: 'Monto Total', render: $.fn.dataTable.render.number('.', ',', 2, '$ ') },
            { 
                data: 'estado', 
                title: 'Estado',
                render: function(data) {
                    let badgeClass = 'secondary';
                    if (data === 'PENDIENTE_APROBACION_CLIENTE') badgeClass = 'warning';
                    if (data === 'PENDIENTE_APROBACION_FINAL') badgeClass = 'info';
                    if (data === 'ACEPTADA') badgeClass = 'success';
                    if (data === 'CONTRAPROPUESTA_CLIENTE') badgeClass = 'primary';
                    if (data === 'DOCUMENTACION_ADJUNTADA') badgeClass = 'dark';
                    return `<span class="badge bg-${badgeClass}">${data.replace(/_/g, ' ')}</span>`;
                }
            },
            {
                data: null,
                title: 'Acciones',
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    // ======================= INICIO DE LA MODIFICACIÓN =======================
                    let btnVer = `<button class="btn btn-primary btn-sm btn-detalle" data-id="${row.id}" title="Ver Detalle"><i class="fa-solid fa-eye"></i></button>`;
                    
                    let btnAdjuntar = '';
                    // Mostramos el botón de adjuntar solo si el estado es 'ACEPTADA'
                    if (row.estado === 'ACEPTADA') {
                        btnAdjuntar = `<button class="btn btn-info btn-sm ms-1 btn-adjuntar" data-id="${row.id}" title="Adjuntar Comprobante">
                                          <i class="fa-solid fa-paperclip"></i>
                                      </button>`;
                    }
                    
                    return btnVer + btnAdjuntar;
                    // ======================== FIN DE LA MODIFICACIÓN =========================
                }
            }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[1, 'desc']]
    });

    // 2. LÓGICA PARA ABRIR EL MODAL Y CARGAR EL DETALLE
    $('#tabla-propuestas-cliente').on('click', '.btn-detalle', function() {
        const idPropuesta = $(this).data('id');
        const modal = new bootstrap.Modal(document.getElementById('detallePropuestaModal'));
        const contentDiv = $('#detalle-propuesta-content');
        
        // Asignar el ID a los botones del footer para usarlos después
        $('#btn-aceptar-propuesta').data('id', idPropuesta);
        $('#btn-enviar-contrapropuesta').data('id', idPropuesta);

        // Mostrar loader y resetear
        contentDiv.html('<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>');
        $('#detallePropuestaModalLabel').text(`Detalle de Propuesta #${idPropuesta}`);
        $('#btn-aceptar-propuesta, #btn-enviar-contrapropuesta').hide();
        modal.show();

        $.ajax({
            url: `api/propuestas_controller.php?action=ver_detalle&id=${idPropuesta}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderizarDetallePropuesta(response.data);
                } else {
                    contentDiv.html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            },
            error: function() {
                contentDiv.html('<div class="alert alert-danger">Error al cargar los detalles.</div>');
            }
        });
    });

// ======================= INICIO DE LA LÓGICA CORREGIDA PARA SUBIR ARCHIVOS =======================

// Evento para el nuevo botón "Adjuntar", usando delegación de eventos
// Esto asegura que el evento funcione incluso para botones añadidos después de cargar la página
$('#tabla-propuestas-cliente tbody').on('click', '.btn-adjuntar', function() {
    const idPropuesta = $(this).data('id');
    console.log('Botón Adjuntar clickeado para propuesta ID:', idPropuesta); // <-- Línea de depuración

    // Preparamos el modal
    $('#uploadPropuestaId').val(idPropuesta);
    $('#uploadDocForm')[0].reset();
    $('.progress').hide();
    $('.progress-bar').css('width', '0%').text('0%');
    
    // Abrimos el modal
    const uploadModal = new bootstrap.Modal(document.getElementById('uploadDocModal'));
    uploadModal.show();
});

// Evento para el botón "Subir" dentro del modal (este debería estar bien, pero lo revisamos)
    $('#btnSubirComprobante').on('click', function() {
        const form = $('#uploadDocForm')[0];
        const formData = new FormData(form);
        const fileInput = $('#comprobanteFile')[0];

        if (fileInput.files.length === 0) {
            Swal.fire('Atención', 'Por favor, seleccione un archivo.', 'warning');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Subiendo...');
        $('.progress').show();

        $.ajax({
            url: 'api/propuestas_controller.php?action=subir_comprobante',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        $('.progress-bar').css('width', percentComplete + '%').text(percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire('¡Éxito!', response.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('uploadDocModal')).hide();
                    tablaPropuestas.ajax.reload();
                    cargarKPIsCliente();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de conexión. No se pudo subir el archivo.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa-solid fa-upload me-2"></i>Subir');
            }
        });
    });


function renderizarDetallePropuesta(data) {
    const { propuesta, items, historial } = data;
    const contentDiv = $('#detalle-propuesta-content');

    // ======================= INICIO DE LA CORRECCIÓN =======================
    // Formateamos la fecha para mostrarla amigablemente
    let fechaHtml = propuesta.fecha_propuesta_pago 
        ? new Date(propuesta.fecha_propuesta_pago + 'T00:00:00').toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric' })
        : 'No definida';

    let resumenHtml = `
        <div class="row mb-4 text-center">
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-muted">TOTAL PROPUESTO</h6>
                        <p class="card-text fs-4 fw-bold text-primary">${(parseFloat(propuesta.total_propuesto) || 0).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-muted">FECHA PROPUESTA DE PAGO</h6>
                        <p class="card-text fs-4 fw-bold">${fechaHtml}</p>
                    </div>
                </div>
            </div>
        </div>
    `;

        // --- HTML MEJORADO CON UNA TABLA DETALLADA (copiar el mismo bloque de app.js) ---
        let itemsHtml = `
            <h5 class="mt-4">Facturas Incluidas</h5>
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Comprobante</th>
                        <th class="text-end">Importe Bruto</th>
                        <th class="text-end">Importe Neto</th>
                        <th class="text-center">% Descuento</th>
                    </tr>
                </thead>
                <tbody>`;
        
        items.forEach(item => {
            const bruto = parseFloat(item.importe_bruto) || 0;
            const neto = parseFloat(item.importe_neto) || 0;
            let descuento = 0;
            if (bruto > 0 && bruto > neto) {
                descuento = ((bruto - neto) / bruto) * 100;
            }

            itemsHtml += `
                    <tr>
                        <td>${item.n_comp_factura}</td>
                        <td class="text-end">${bruto.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>
                        <td class="text-end">${neto.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>
                        <td class="text-center">${descuento > 0 ? descuento.toFixed(2) + '%' : '-'}</td>
                    </tr>`;
        });
        itemsHtml += '</tbody></table>';

        // Renderizar historial
        let historialHtml = '<h5>Historial de la Negociación</h5><div class="timeline">';
        historial.forEach(h => {
            const icon = h.tipo_usuario === 'ADMIN' ? 'fa-user-shield' : 'fa-user-tie';
            const align = h.tipo_usuario === 'ADMIN' ? 'left' : 'right';
            historialHtml += `
                <div class="timeline-item timeline-item-${align}">
                    <div class="timeline-icon"><i class="fas ${icon}"></i></div>
                    <div class="timeline-content">
                        <span class="timeline-date">${h.fecha_evento}</span>
                        <p><strong>${h.descripcion}</strong></p>
                        ${h.comentario ? `<p class="fst-italic bg-light p-2 rounded">Comentario: "${h.comentario}"</p>` : ''}
                    </div>
                </div>`;
        });
        historialHtml += '</div>';
        
        // Área para contrapropuesta
        let contrapropuestaHtml = '';
        if (propuesta.estado === 'PENDIENTE_APROBACION_CLIENTE') {
            contrapropuestaHtml = `
                <div class="mb-3">
                    <label for="comentario-contrapropuesta" class="form-label"><strong>Agregar Comentario (Opcional para Aceptar / Requerido para Contrapropuesta):</strong></label>
                    <textarea class="form-control" id="comentario-contrapropuesta" rows="3" placeholder="Ej: Propongo pagar en 2 cuotas..."></textarea>
                </div>
            `;
            $('#btn-aceptar-propuesta, #btn-enviar-contrapropuesta').show();
        } else if(propuesta.estado === 'PENDIENTE_APROBACION_FINAL'){
            $('#btn-aceptar-propuesta').show(); // En la propuesta final, solo se puede aceptar
        }


        contentDiv.html(resumenHtml + itemsHtml + contrapropuestaHtml + historialHtml);
    }
    
// 3. LÓGICA PARA LOS BOTONES DE ACCIÓN DEL MODAL
    $('#btn-aceptar-propuesta, #btn-enviar-contrapropuesta').on('click', function() {
        const idPropuesta = $(this).data('id');
        const esAceptar = $(this).attr('id') === 'btn-aceptar-propuesta';
        const nuevoEstado = esAceptar ? 'ACEPTADA' : 'CONTRAPROPUESTA_CLIENTE';
        const comentario = $('#comentario-contrapropuesta').val();

        if (!esAceptar && !comentario) {
            // Reemplazamos el alert nativo por SweetAlert
            Swal.fire({
                icon: 'warning',
                title: 'Comentario Requerido',
                text: 'Debe agregar un comentario para poder enviar una contrapropuesta.',
            });
            return; // Detenemos la ejecución
        }

        // Si es Aceptar, mostramos una confirmación
        if (esAceptar) {
            Swal.fire({
                title: '¿Confirmar Aceptación?',
                text: "Esta acción generará el compromiso de pago. ¿Desea continuar?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, Aceptar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarAccion(idPropuesta, nuevoEstado, comentario);
                }
            });
        } else {
            // Si es Contrapropuesta, la enviamos directamente ya que el comentario es obligatorio
            enviarAccion(idPropuesta, nuevoEstado, comentario);
        }
    });

function enviarAccion(idPropuesta, nuevoEstado, comentario) {
        $.ajax({
            url: 'api/propuestas_controller.php?action=actualizar_estado',
            type: 'POST',
            data: { id_propuesta: idPropuesta, nuevo_estado: nuevoEstado, comentario: comentario },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('¡Acción Realizada!', response.message, 'success');
                    $('#detallePropuestaModal').modal('hide');
                    tablaPropuestas.ajax.reload();
                    cargarKPIsCliente();
                    $('#cronograma-calendario').datepicker('destroy').empty();
                    $('#cronograma-detalles').html('<div class="text-center text-muted py-4"><i class="fa-solid fa-hand-pointer fa-2x mb-2"></i><p class="mb-0">Seleccione un día resaltado para ver los vencimientos.</p></div>');
                    inicializarCronograma();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de conexión al realizar la acción.', 'error');
            }
        });
    }

    function inicializarCronograma() {
        $.ajax({
            url: 'api/propuestas_controller.php?action=obtener_cronograma_cliente',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const eventos = response.data;
                    const fechasResaltadas = eventos.map(e => e.date.split(' ')[0]);

                    function formatDate(d) {
                        return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
                    }

                    $("#cronograma-calendario").datepicker({
                        dateFormat: "yy-mm-dd",
                        beforeShowDay: function(date) {
                            const dateString = formatDate(date);
                            if (fechasResaltadas.includes(dateString)) {
                                return [true, "highlight-date", "Tiene un vencimiento de pago"];
                            }
                            return [true, ""];
                        },
                        onSelect: function(dateText) {
                            const contenedorDetalles = $('#cronograma-detalles');
                            contenedorDetalles.empty();
                            
                            const eventosDelDia = eventos.filter(evento => evento.date.split(' ')[0] === dateText);

                            if (eventosDelDia.length > 0) {
                                let detallesHtml = `<h6 class="border-bottom pb-2">Vencimientos para el ${new Date(dateText + 'T00:00:00').toLocaleDateString('es-AR')}:</h6>`;
                                detallesHtml += '<ul class="list-group list-group-flush">';
                                eventosDelDia.forEach(evento => {
                                    const montoFormateado = parseFloat(evento.monto).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
                                    detallesHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">Propuesta #${evento.id}<span class="badge bg-primary rounded-pill">${montoFormateado}</span></li>`;
                                });
                                detallesHtml += '</ul>';
                                contenedorDetalles.html(detallesHtml);
                            } else {
                                // ======================= MENSAJE MEJORADO =======================
                                let noEventosHtml = `
                                    <div class="text-center text-muted py-4">
                                        <i class="fa-solid fa-calendar-check fa-2x mb-2"></i>
                                        <p class="mb-0">No hay vencimientos programados para esta fecha.</p>
                                    </div>`;
                                contenedorDetalles.html(noEventosHtml);
                                // ================================================================
                            }
                        }
                    });
                }
            }
        });
    }

    // Carga inicial de todo
    cargarKPIsCliente();
    inicializarCronograma();


});