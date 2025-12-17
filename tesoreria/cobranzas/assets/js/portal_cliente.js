$(document).ready(function() {

        // ======================= NUEVA LÓGICA DE CÁLCULO DE DESCUENTOS =======================
    const reglasDescuento = {
        'ECHECK': { 15: 8, 22: 6, 29: 4 },
        'TRANSFERENCIA': { 15: 6, 22: 4, 29: 2 }
    };

    function calcularPorcentajeDescuento(medioPago, fechaPropuesta) {
        if (!reglasDescuento[medioPago] || !fechaPropuesta) return 0;
        
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0); // Normalizamos la fecha de hoy
        const fechaPago = new Date(fechaPropuesta + 'T00:00:00');
        
        // Calculamos la diferencia en días
        const diffTime = fechaPago - hoy;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays <= 15) return reglasDescuento[medioPago][15];
        if (diffDays <= 22) return reglasDescuento[medioPago][22];
        if (diffDays <= 29) return reglasDescuento[medioPago][29];
        
        return 0; // Más de 29 días, 0% de descuento
    }

function recalcularPropuestaCliente() {
    const medioPago = $('#negociacion-medio-pago').val();
    const fechaPago = $('#negociacion-fecha-pago').val();
    
    const nuevoPorcentaje = calcularPorcentajeDescuento(medioPago, fechaPago);

    let totalBruto = 0;
    let totalNeto = 0;

    $('#tabla-detalle-propuesta-cliente tbody tr').each(function() {
        const tr = $(this);
        const bruto = parseFloat(tr.data('importe-bruto'));
        const tipoComp = tr.data('tcomp');
        const esNC = tipoComp && tipoComp.startsWith('NC');
        
        // ======================= INICIO DE LA MODIFICACIÓN =======================
        // Leemos el descuento original que guardamos en el 'tr'
        const descuentoOriginal = parseFloat(tr.data('descuento-original'));
        // ======================== FIN DE LA MODIFICACIÓN =========================

        let neto = bruto;
        let descuentoActual = descuentoOriginal; // Por defecto, mantenemos el original

        // Solo aplicamos el nuevo descuento si el original NO era 0 y no es una NC
        if (descuentoOriginal > 0 && !esNC) {
            descuentoActual = nuevoPorcentaje;
            neto = bruto * (1 - (descuentoActual / 100));
        }

        // Actualizamos la tabla visualmente
        tr.find('.descuento-cell').text(descuentoActual.toFixed(2) + ' %');
        const netoFinal = esNC ? -neto : neto;
        tr.find('.importe-neto-cell').text(netoFinal.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));

        totalBruto += esNC ? -bruto : bruto;
        totalNeto += netoFinal;
    });

        // Actualizamos los totales de la tabla y los KPIs
        const f = (num) => num.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
        $('#total-bruto-tabla').text(f(totalBruto));
        $('#total-neto-tabla').text(f(totalNeto));
        $('#totalPropuestoKPI').text(f(totalNeto)); // El KPI principal
        $('#medioPagoKPI').text(medioPago);
        $('#fechaPropuestaKPI').text(new Date(fechaPago + 'T00:00:00').toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric' }));
    }

    // Eventos que disparan el recálculo
    $('#detallePropuestaModal').on('change', '#negociacion-medio-pago, #negociacion-fecha-pago', function() {
        recalcularPropuestaCliente();
    });
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
                
                // ======== NUEVA LÍNEA ========
                $('#kpi-pendiente-pago').text(parseFloat(data.pendienteDePago).toLocaleString('es-AR', options));
                // =============================
                
                $('#kpi-requiere-accion').text(data.propuestasRequierenAccion);
            }
        }
        });
    }

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
                
                // ======================= NUEVO ESTADO "VENCIDA" =======================
                if (data === 'VENCIDA') badgeClass = 'danger';
                
                return `<span class="badge bg-${badgeClass}">${data.replace(/_/g, ' ')}</span>`;
            }
        },
        {
            data: null,
            title: 'Acciones',
            orderable: false,
            className: 'text-center',
            render: function(data, type, row) {
                let btnVer = `<button class="btn btn-primary btn-sm btn-detalle" data-id="${row.id}" title="Ver Detalle"><i class="fa-solid fa-eye"></i></button>`;
                let btnAdjuntar = '';
                
                // ======================= LÓGICA DE BOTONES MODIFICADA =======================
                // Si la propuesta está VENCIDA, solo se puede ver, no se puede hacer nada más.
                if (row.estado === 'VENCIDA') {
                    return btnVer;
                }

                // Si está ACEPTADA (y no vencida), mostramos el botón de adjuntar comprobante.
                if (row.estado === 'ACEPTADA') {
                    btnAdjuntar = `<button class="btn btn-info btn-sm ms-1 btn-adjuntar" data-id="${row.id}" title="Adjuntar Comprobante">
                                      <i class="fa-solid fa-paperclip"></i>
                                  </button>`;
                }

                // La función inicializarCronograma() no debería estar aquí, se llama una vez al cargar la página
                // inicializarCronograma(); 
                
                return btnVer + btnAdjuntar;
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

    // 1. --- TARJETAS DE RESUMEN (Igual que el Admin) ---
    let fechaHtml = propuesta.fecha_propuesta_pago 
        ? new Date(propuesta.fecha_propuesta_pago + 'T00:00:00').toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric' })
        : 'No definida';

    let resumenHtml = `
        <div class="row mb-4">
            <div class="col-md-4"><div class="card bg-light shadow-sm h-100"><div class="card-body text-center">
                <h6 class="card-title text-muted text-uppercase small">Total Propuesto</h6>
                <p class="card-text fs-4 fw-bold text-primary mb-0" id="totalPropuestoKPI">${(parseFloat(propuesta.total_propuesto) || 0).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</p>
            </div></div></div>
            <div class="col-md-4"><div class="card bg-light shadow-sm h-100"><div class="card-body text-center">
                <h6 class="card-title text-muted text-uppercase small">Fecha Propuesta de Pago</h6>
                <p class="card-text fs-4 fw-bold mb-0" id="fechaPropuestaKPI">${fechaHtml}</p>
            </div></div></div>
            <div class="col-md-4"><div class="card bg-light shadow-sm h-100"><div class="card-body text-center">
                <h6 class="card-title text-muted text-uppercase small">Medio de Pago</h6>
                <p class="card-text fs-4 fw-bold mb-0" id="medioPagoKPI">${propuesta.medio_de_pago || 'N/A'}</p>
            </div></div></div>
        </div>
    `;

    // 2. --- TABLA DE FACTURAS (Igual que el Admin) ---
    let itemsHtml = `
        <h5 class="mt-4">Comprobantes Incluidos</h5>
        <table class="table table-sm table-bordered" id="tabla-detalle-propuesta-cliente">
            <thead class="table-light"><tr><th>Comprobante</th><th class="text-end">Importe Bruto</th><th class="text-center">% Descuento</th><th class="text-end">Importe Neto</th></tr></thead>
            <tbody>`;

    let totalBrutoTabla = 0;
    let totalNetoTabla = 0;

    items.forEach(item => {
        const bruto = parseFloat(item.importe_bruto) || 0;
        const neto = parseFloat(item.importe_neto) || 0;
        const descuento = parseFloat(item.porcentaje_descuento) || 0;
        
        const esNC = item.t_comp_factura && item.t_comp_factura.trim().startsWith('NC');
        totalBrutoTabla += esNC ? -bruto : bruto;
        totalNetoTabla += esNC ? -neto : neto;

        itemsHtml += `
                <tr data-importe-bruto="${bruto}" data-tcomp="${item.t_comp_factura || ''}" data-descuento-original="${descuento}">
                    <td>${item.n_comp_factura}</td>
                    <td class="text-end ${esNC ? 'text-danger' : ''}">${(esNC ? -bruto : bruto).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>
                    <td class="text-center descuento-cell">${descuento.toFixed(2)} %</td>
                    <td class="text-end fw-bold importe-neto-cell ${esNC ? 'text-danger' : ''}">${(esNC ? -neto : neto).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>
                </tr>`;
    });

    itemsHtml += `
            </tbody>
            <tfoot class="table-light"><tr>
                <td class="text-end"><strong>Totales:</strong></td>
                <td class="text-end fw-bolder" id="total-bruto-tabla">${totalBrutoTabla.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>
                <td></td>
                <td class="text-end fw-bolder" id="total-neto-tabla">${totalNetoTabla.toLocaleString('es-AR', {style: 'currency', currency: 'ARS'})}</td>
            </tr></tfoot>
        </table>`;

    // 3. --- SECCIÓN DE NEGOCIACIÓN (NUEVA) ---
    let negociacionHtml = '';
    const esNegociable = propuesta.estado === 'PENDIENTE_APROBACION_CLIENTE' || propuesta.estado === 'PENDIENTE_APROBACION_FINAL';
    
    if (esNegociable) {
        const fechaActual = propuesta.fecha_propuesta_pago ? propuesta.fecha_propuesta_pago.split(' ')[0] : new Date().toISOString().split('T')[0];
        
        negociacionHtml = `
            <div class="card bg-light border-primary mt-4">
                <div class="card-body">
                    <h5 class="card-title">Negociar Propuesta</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="negociacion-medio-pago" class="form-label"><strong>Cambiar Medio de Pago:</strong></label>
                            <select class="form-select" id="negociacion-medio-pago">
                                <option value="ECHECK" ${propuesta.medio_de_pago === 'ECHECK' ? 'selected' : ''}>ECHECK</option>
                                <option value="TRANSFERENCIA" ${propuesta.medio_de_pago === 'TRANSFERENCIA' ? 'selected' : ''}>TRANSFERENCIA</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="negociacion-fecha-pago" class="form-label"><strong>Proponer Nueva Fecha:</strong></label>
                            <input type="date" class="form-control" id="negociacion-fecha-pago" value="${fechaActual}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="comentario-contrapropuesta" class="form-label"><strong>Agregar Comentario (Requerido para Contrapropuesta):</strong></label>
                        <textarea class="form-control" id="comentario-contrapropuesta" rows="2" placeholder="Ej: Deseo eliminar un comprobante"></textarea>
                    </div>
                </div>
            </div>
        `;
    }


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


        contentDiv.html(resumenHtml + itemsHtml + negociacionHtml + historialHtml);
    }
    
// 3. LÓGICA PARA LOS BOTONES DE ACCIÓN DEL MODAL
    $('#btn-aceptar-propuesta, #btn-enviar-contrapropuesta').on('click', function() {
        const idPropuesta = $(this).data('id');
        const esAceptar = $(this).attr('id') === 'btn-aceptar-propuesta';
        
        // ======================= INICIO DE LA MODIFICACIÓN =======================
        if (esAceptar) {
            // Flujo de Aceptar (sin cambios)
            const nuevoEstado = 'ACEPTADA';
            const comentario = $('#comentario-contrapropuesta').val(); // Puede aceptar con un comentario

            Swal.fire({
                title: '¿Confirmar Aceptación?',
                text: "Esta acción generará el compromiso de pago. ¿Desea continuar?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, Aceptar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarAccion(idPropuesta, nuevoEstado, comentario, null); // Pasamos null para los datos de contrapropuesta
                }
            });

        } else {
            // Flujo de ENVIAR CONTRAPROPUESTA
            const comentario = $('#comentario-contrapropuesta').val();
            if (!comentario) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Comentario Requerido',
                    text: 'Debe agregar un comentario para poder enviar una contrapropuesta.',
                });
                return;
            }

            // Recolectamos los datos de la contrapropuesta
            const datosContrapropuesta = {
                nuevo_total: parseFloat($('#total-neto-tabla').text().replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0,
                nueva_fecha: $('#negociacion-fecha-pago').val(),
                nuevo_medio_pago: $('#negociacion-medio-pago').val()
            };
            
            enviarAccion(idPropuesta, 'CONTRAPROPUESTA_CLIENTE', comentario, datosContrapropuesta);
        }
        // ======================== FIN DE LA MODIFICACIÓN =========================
    });

function enviarAccion(idPropuesta, nuevoEstado, comentario, datosContrapropuesta) {
    let postData = {
        id_propuesta: idPropuesta,
        nuevo_estado: nuevoEstado,
        comentario: comentario
    };
    if (datosContrapropuesta) {
        postData.contrapropuesta = datosContrapropuesta;
    }

    // Deshabilitamos los botones para evitar doble clic
    $('#btn-enviar-contrapropuesta, #btn-aceptar-propuesta').prop('disabled', true);

    $.ajax({
        url: 'api/propuestas_controller.php?action=actualizar_estado',
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire('¡Acción Realizada!', response.message, 'success');

                // ======================= INICIO DE LA MODIFICACIÓN =======================
                // Recargamos los datos del modal y de la página principal en segundo plano
                tablaPropuestas.ajax.reload();
                if ($('#cronograma-calendario').length > 0) {
                     $('#cronograma-calendario').datepicker('destroy').empty();
                     inicializarCronograma();
                }

                // Si fue una contrapropuesta, recargamos el contenido del modal
                if (nuevoEstado === 'CONTRAPROPUESTA_CLIENTE') {
                    // Volvemos a pedir los datos actualizados de la propuesta
                    $.ajax({
                        url: `api/propuestas_controller.php?action=ver_detalle&id=${idPropuesta}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(detailResponse) {
                            if (detailResponse.success) {
                                // Renderizamos el modal de nuevo con los datos frescos
                                renderizarDetallePropuesta(detailResponse.data);
                                // Ocultamos los controles de negociación porque la pelota está del lado del admin ahora
                                $('#negociar-propuesta-card, #btn-enviar-contrapropuesta, #btn-aceptar-propuesta').hide();
                            }
                        }
                    });
                } else {
                    // Si fue aceptada, simplemente cerramos el modal
                    $('#detallePropuestaModal').modal('hide');
                }
                // ======================== FIN DE LA MODIFICACIÓN =========================
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error de conexión al realizar la acción.', 'error');
        },
        complete: function() {
            // Habilitamos los botones de nuevo (solo si no se cerró el modal)
            $('#btn-enviar-contrapropuesta, #btn-aceptar-propuesta').prop('disabled', false);
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