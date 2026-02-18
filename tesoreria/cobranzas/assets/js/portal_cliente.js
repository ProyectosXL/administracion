$(document).ready(function () {

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

    // --- FUNCIONES PARA MANEJO DE CUOTAS EN CONTRAPROPUESTA (MOVIDAS A ÁMBITO GLOBAL) ---
    function generarCamposCuotasNeg(cantidad, total, fechaLimite) {
        const $cuotasListNeg = $('#negociacion-cuotas-list');
        $cuotasListNeg.empty();
        const montoIndividual = (total / cantidad).toFixed(2);
        let acumulado = 0;
        for (let i = 1; i <= cantidad; i++) {
            const monto = (i === cantidad) ? (total - acumulado).toFixed(2) : montoIndividual;
            acumulado += parseFloat(monto);
            const row = `
            <div class="row g-2 mb-2 neg-cuota-row">
                <div class="col-1 small fw-bold mt-1">${i}.</div>
                <div class="col-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control neg-cuota-monto" value="${monto}" readonly>
                    </div>
                </div>
                <div class="col-5">
                    <input type="date" class="form-control form-control-sm neg-cuota-fecha" value="${fechaLimite}" max="${fechaLimite}">
                </div>
            </div>`;
            $cuotasListNeg.append(row);
        }
    }

    function cargarCuotasExistentesNeg(cuotas, fechaLimite) {
        const $cuotasListNeg = $('#negociacion-cuotas-list');
        $cuotasListNeg.empty();
        cuotas.forEach(c => {
            const row = `
            <div class="row g-2 mb-2 neg-cuota-row">
                <div class="col-1 small fw-bold mt-1">${c.num_cuota}.</div>
                <div class="col-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control neg-cuota-monto" value="${c.monto}" readonly>
                    </div>
                </div>
                <div class="col-5">
                    <input type="date" class="form-control form-control-sm neg-cuota-fecha" value="${c.fecha_vencimiento}" max="${fechaLimite}">
                </div>
            </div>`;
            $cuotasListNeg.append(row);
        });
    }

    function recalcularPropuestaCliente() {
        const medioPago = $('#negociacion-medio-pago').val();
        const fechaPago = $('#negociacion-fecha-pago').val();

        // Obtenemos el nuevo porcentaje de descuento aplicable para todas las líneas elegibles
        const nuevoPorcentajeGeneral = calcularPorcentajeDescuento(medioPago, fechaPago);

        let totalBruto = 0;
        let totalNeto = 0;

        $('#tabla-detalle-propuesta-cliente tbody tr').each(function () {
            const tr = $(this);
            const bruto = parseFloat(tr.data('importe-bruto'));
            const tipoComp = tr.data('tcomp');
            const esNC = tipoComp && tipoComp.startsWith('NC');
            const descuentoOriginal = parseFloat(tr.data('descuento-original'));

            const nComp = (tr.data('ncomp') || '').trim();

            // ======================= REGLAS DE NEGOCIO A00115 SINCRONIZADAS =======================
            let porcentajeAplicar = 0;

            // Si es de la sucursal 115, tiene reglas especiales
            if (nComp.startsWith('A00115')) {
                if (tipoComp === 'FAC') {
                    porcentajeAplicar = 0; // FAC de la 115 nunca llevan descuento
                } else if (tipoComp === 'NCP') {
                    porcentajeAplicar = nuevoPorcentajeGeneral; // NCP de la 115 SI llevan descuento
                } else {
                    porcentajeAplicar = 0; // Otras NC de la 115 no llevan
                }
            }
            // Regla general para el resto de sucursales
            else if (descuentoOriginal > 0) {
                porcentajeAplicar = nuevoPorcentajeGeneral;
            }
            // =======================================================================================

            // Calculamos el nuevo importe neto
            let netoRecalculado = bruto * (1 - (porcentajeAplicar / 100));
            // ======================== FIN DE LA LÓGICA CORRECTA Y SIMPLIFICADA =========================

            // Actualizamos la tabla visualmente
            tr.find('.descuento-cell').text(porcentajeAplicar.toFixed(2) + ' %');

            // Aplicamos el signo negativo para visualización si es NC
            const netoFinal = esNC ? -netoRecalculado : netoRecalculado;
            tr.find('.importe-neto-cell').text(netoFinal.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));

            // Sumamos a los totales
            totalBruto += esNC ? -bruto : bruto;
            totalNeto += netoFinal;
        });

        // Actualizamos los totales de la tabla y los KPIs
        const f = (num) => num.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
        $('#total-bruto-tabla').text(f(totalBruto));
        $('#total-neto-tabla').text(f(totalNeto));
        $('#totalPropuestoKPI').text(f(totalNeto));
        $('#medioPagoKPI').text(medioPago);
        $('#fechaPropuestaKPI').text(new Date(fechaPago + 'T00:00:00').toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric' }));

        // --- NUEVO: Recalcular Cuotas si están visibles ---
        const $cantCuotasNeg = $('#negociacion-cantidad-cuotas');
        if ($cantCuotasNeg.length && parseInt($cantCuotasNeg.val()) > 1) {
            generarCamposCuotasNeg(parseInt($cantCuotasNeg.val()), totalNeto, fechaPago);
        }
    }

    // Eventos que disparan el recálculo
    $('#detallePropuestaModal').on('change', '#negociacion-medio-pago, #negociacion-fecha-pago', function () {
        recalcularPropuestaCliente();
    });
    // ======================= INICIO DE LA NUEVA LÓGICA =======================
    // Nueva función para cargar los KPIs
    function cargarKPIsCliente() {
        $.ajax({
            url: 'api/propuestas_controller.php?action=obtener_kpis_cliente',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const data = response.data;
                    const options = { style: 'currency', currency: 'ARS' };

                    // Validar y formatear cada valor, usando 0 si es null/undefined
                    const deudaTotal = parseFloat(data.deudaTotalPendiente || 0);
                    const montoNegociacion = parseFloat(data.montoEnNegociacion || 0);
                    const pendientePago = parseFloat(data.pendienteDePago || 0);
                    const requiereAccion = parseInt(data.propuestasRequierenAccion || 0);

                    $('#kpi-deuda-total').text(deudaTotal.toLocaleString('es-AR', options));
                    $('#kpi-monto-negociacion').text(montoNegociacion.toLocaleString('es-AR', options));
                    $('#kpi-pendiente-pago').text(pendientePago.toLocaleString('es-AR', options));
                    $('#kpi-requiere-accion').text(requiereAccion);
                }
            },
            error: function () {
                console.error('Error al cargar KPIs del cliente');
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
            // Columna 0: ID Propuesta
            { data: 'id', title: 'ID Propuesta' },

            // Columna 1: Local (la que añadimos)
            { data: 'cod_cliente', title: 'Local' },

            // Columna 2: Fecha Creación
            { data: 'fecha_creacion', title: 'Fecha Creación' },

            // Columna 3: Monto Total
            { data: 'total_propuesto', title: 'Monto Total', render: $.fn.dataTable.render.number('.', ',', 2, '$ ') },

            // Columna 4: Estado
            {
                data: 'estado',
                title: 'Estado',
                render: function (data) {
                    let badgeClass = 'secondary';
                    if (data === 'PENDIENTE_APROBACION_CLIENTE') badgeClass = 'warning';
                    if (data === 'PENDIENTE_APROBACION_FINAL') badgeClass = 'info';
                    if (data === 'ACEPTADA') badgeClass = 'success';
                    if (data === 'CONTRAPROPUESTA_CLIENTE') badgeClass = 'primary';
                    if (data === 'DOCUMENTACION_ADJUNTADA') badgeClass = 'dark';
                    if (data === 'VENCIDA') badgeClass = 'danger';
                    return `<span class="badge bg-${badgeClass}">${data.replace(/_/g, ' ')}</span>`;
                }
            },

            // Columna 5: Acciones
            {
                data: null,
                title: 'Acciones',
                orderable: false,
                className: 'text-center',
                render: function (data, type, row) {
                    // Pasamos el cod_cliente al botón para usarlo después
                    let btnVer = `<button class="btn btn-primary btn-sm btn-detalle" data-id="${row.id}" data-cod-cliente="${row.cod_cliente}" title="Ver Detalle"><i class="fa-solid fa-eye"></i></button>`;
                    if (row.estado === 'VENCIDA') {
                        return btnVer;
                    }

                    return btnVer;
                }
            }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[1, 'desc']]
    });


    // 2. LÓGICA PARA ABRIR EL MODAL Y CARGAR EL DETALLE
    $(document).on('click', '.btn-detalle', function () {
        const idPropuesta = $(this).data('id');
        // Leemos el código del cliente desde el atributo data del botón
        const codCliente = $(this).data('cod-cliente');

        const modal = new bootstrap.Modal(document.getElementById('detallePropuestaModal'));
        const contentDiv = $('#detalle-propuesta-content');

        $('#btn-aceptar-propuesta').data('id', idPropuesta);
        $('#btn-enviar-contrapropuesta').data('id', idPropuesta);

        contentDiv.html('<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>');

        // ======================= TÍTULO DEL MODAL MODIFICADO =======================
        $('#detallePropuestaModalLabel').text(`Detalle de Propuesta #${idPropuesta} (Local: ${codCliente})`);

        $('#btn-aceptar-propuesta, #btn-enviar-contrapropuesta').hide();
        modal.show();

        $.ajax({
            url: `api/propuestas_controller.php?action=ver_detalle&id=${idPropuesta}`,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    renderizarDetallePropuesta(response.data);
                } else {
                    contentDiv.html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            },
            error: function () {
                contentDiv.html('<div class="alert alert-danger">Error al cargar los detalles.</div>');
            }
        });
    });

    // ======================= INICIO DE LA LÓGICA CORREGIDA PARA SUBIR ARCHIVOS =======================

    // ======================= LÓGICA PARA SUBIR ARCHIVOS POR CUOTA =======================

    // Evento para adjuntar desde DENTRO del detalle de cuota
    $('#detalle-propuesta-content').on('click', '.btn-adjuntar-interne', function () {
        const idPropuesta = $(this).data('id');
        const idCuota = $(this).data('id-cuota');

        $('#uploadPropuestaId').val(idPropuesta);
        $('#uploadCuotaId').val(idCuota);
        $('#uploadDocForm')[0].reset();
        $('.progress').hide();

        const uploadModal = new bootstrap.Modal(document.getElementById('uploadDocModal'));
        uploadModal.show();
    });

    // Evento para el botón "Subir" dentro del modal (este debería estar bien, pero lo revisamos)
$('#btnSubirComprobante').on('click', function () {
    const fileInput = $('#comprobanteFile')[0];
    if (fileInput.files.length === 0) {
        Swal.fire('Atención', 'Por favor, seleccione un archivo.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('id_propuesta', $('#uploadPropuestaId').val());
    formData.append('id_cuota', $('#uploadCuotaId').val());
    formData.append('comprobante', fileInput.files[0]); // Nombre exacto: 'comprobante'

    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Subiendo...');
    $('.progress').show();

    $.ajax({
        url: 'api/propuestas_controller.php?action=subir_comprobante',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function () {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function (evt) {
                if (evt.lengthComputable) {
                    const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                    $('.progress-bar').css('width', percentComplete + '%').text(percentComplete + '%');
                }
            }, false);
            return xhr;
        },
        success: function (response) {
            if (response.success) {
                Swal.fire('¡Éxito!', response.message, 'success');
                
                // Cerrar modal de subida
                bootstrap.Modal.getInstance(document.getElementById('uploadDocModal')).hide();
                
                // Recargar tablas y KPIs
                tablaPropuestas.ajax.reload(null, false); 
                cargarKPIsCliente();

                // RECARGA DEL DETALLE (Para ver el nuevo historial y estado)
                const titleText = $('#detallePropuestaModalLabel').text();
                const match = titleText.match(/#(\d+)/);
                if (match && match[1]) {
                    $.ajax({
                        url: `api/propuestas_controller.php?action=ver_detalle&id=${match[1]}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function (res) {
                            if (res.success) renderizarDetallePropuesta(res.data);
                        }
                    });
                }
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Error de conexión al servidor.', 'error');
        },
        complete: function () {
            btn.prop('disabled', false).html('<i class="fa-solid fa-upload me-2"></i>Subir');
            $('.progress').hide();
            $('.progress-bar').css('width', '0%');
        }
    });
});


    function renderizarDetallePropuesta(data) {
        const { propuesta, items, historial, adjuntos } = data;
        const contentDiv = $('#detalle-propuesta-content');

        let fechaHtml = propuesta.fecha_propuesta_pago
            ? new Date(propuesta.fecha_propuesta_pago + 'T00:00:00').toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric' })
            : 'No definida';

        let resumenHtml = `<div class="row mb-4"><div class="col-md-4"><div class="card bg-light shadow-sm h-100"><div class="card-body text-center"><h6 class="card-title text-muted text-uppercase small">Total Propuesto</h6><p class="card-text fs-4 fw-bold text-primary mb-0" id="totalPropuestoKPI">${(parseFloat(propuesta.total_propuesto) || 0).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</p></div></div></div><div class="col-md-4"><div class="card bg-light shadow-sm h-100"><div class="card-body text-center"><h6 class="card-title text-muted text-uppercase small">Fecha Propuesta de Pago</h6><p class="card-text fs-4 fw-bold mb-0" id="fechaPropuestaKPI">${fechaHtml}</p></div></div></div><div class="col-md-4"><div class="card bg-light shadow-sm h-100"><div class="card-body text-center"><h6 class="card-title text-muted text-uppercase small">Medio de Pago</h6><p class="card-text fs-4 fw-bold mb-0" id="medioPagoKPI">${propuesta.medio_de_pago || 'N/A'}</p></div></div></div></div>`;

        let adjuntosHtml = '';
        const adjuntosGenerales = (adjuntos || []).filter(a => !a.id_cuota);
        if (adjuntosGenerales.length > 0) {
            adjuntosHtml = `<h5 class="mt-4"><i class="fa-solid fa-folder-open me-2"></i>Documentación General</h5><div class="row row-cols-1 row-cols-md-2 g-3 mb-4">`;
            adjuntosGenerales.forEach(adjunto => {
                const url = adjunto.ruta_archivo;
                const extension = adjunto.nombre_archivo.split('.').pop().toLowerCase();
                const esImagen = ['jpg', 'jpeg', 'png', 'gif'].includes(extension);
                const icono = esImagen ? 'fa-image' : 'fa-file-pdf';
                adjuntosHtml += `<div class="col"><div class="card h-100 border-light shadow-sm"><div class="card-body d-flex align-items-center justify-content-between py-2"><div class="text-truncate mr-2"><i class="fa-solid ${icono} text-primary me-2"></i><span class="small fw-bold" title="${adjunto.nombre_archivo}">${adjunto.nombre_archivo}</span></div><a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" title="Ver archivo"><i class="fa-solid fa-eye"></i></a></div></div></div>`;
            });
            adjuntosHtml += `</div>`;
        }

        let itemsHtml = `<h5 class="mt-4">Facturas Incluidas</h5><table class="table table-sm table-bordered" id="tabla-detalle-propuesta-cliente"><thead class="table-light"><tr><th class="text-center">Tipo</th><th>Comprobante</th><th class="text-end">Importe Bruto</th><th class="text-center">% Descuento</th><th class="text-end">Importe Neto</th></tr></thead><tbody>`;

        let totalBrutoTabla = 0;
        let totalNetoTabla = 0;

        items.forEach(item => {
            const bruto = parseFloat(item.importe_bruto) || 0;
            const neto = parseFloat(item.importe_neto) || 0;
            const descuento = parseFloat(item.porcentaje_descuento) || 0;
            const nComp = (item.n_comp_factura || '').trim();
            const tComp = (item.t_comp_factura || '').trim();
            const esNC = tComp.startsWith('NC');

            totalBrutoTabla += esNC ? -bruto : bruto;
            totalNetoTabla += esNC ? -neto : neto;

            itemsHtml += `<tr data-importe-bruto="${bruto}" data-tcomp="${tComp}" data-ncomp="${nComp}" data-descuento-original="${descuento}"><td class="text-center">${tComp || 'N/A'}</td><td>${nComp}</td><td class="text-end ${esNC ? 'text-danger' : ''}">${(esNC ? -bruto : bruto).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td><td class="text-center descuento-cell">${descuento.toFixed(2)} %</td><td class="text-end fw-bold importe-neto-cell ${esNC ? 'text-danger' : ''}">${(esNC ? -neto : neto).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td></tr>`;
        });

        itemsHtml += `</tbody><tfoot class="table-light"><tr><td colspan="2" class="text-end"><strong>Totales:</strong></td><td class="text-end fw-bolder" id="total-bruto-tabla">${totalBrutoTabla.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td><td></td><td class="text-end fw-bolder" id="total-neto-tabla">${totalNetoTabla.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td></tr></tfoot></table>`;


        let cuotasHtml = '';
        if (data.cuotas && data.cuotas.length > 0) {
            cuotasHtml = `<h5 class="mt-4"><i class="fa-solid fa-calendar-day me-2 text-primary"></i>Plan de Pagos Acordado</h5><div class="row row-cols-1 row-cols-md-2 g-3 mb-4">`;
            data.cuotas.forEach(c => {
                const fechaFmt = new Date(c.fecha_vencimiento + 'T00:00:00').toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });

                // Buscamos adjuntos para esta cuota
                let adjuntosCuotaHtml = '';
                const adjuntosCuota = (data.adjuntos || []).filter(a => a.id_cuota == c.id);
                const tieneAdjuntos = adjuntosCuota.length > 0;

                if (tieneAdjuntos) {
                    adjuntosCuotaHtml = '<div class="mt-2 border-top pt-2">';
                    adjuntosCuota.forEach(a => {
                        adjuntosCuotaHtml += `<div class="small d-flex justify-content-between align-items-center mb-1 bg-white p-1 rounded border">
                            <span class="text-truncate text-muted" style="max-width: 130px;" title="${a.nombre_archivo}"><i class="fa-solid fa-file-invoice me-1"></i>${a.nombre_archivo}</span>
                            <div class="btn-group">
                                <a href="${a.ruta_archivo}" target="_blank" class="btn btn-xs btn-outline-primary" title="Ver archivo"><i class="fa-solid fa-eye"></i></a>
                                <button class="btn btn-xs btn-outline-danger btn-eliminar-adjunto" data-id-adjunto="${a.id}" title="Eliminar"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>`;
                    });
                    adjuntosCuotaHtml += '</div>';
                }

                // Botón adjuntar solo si la propuesta está aceptada
                const puedeAdjuntar = propuesta.estado === 'ACEPTADA';
                const checkVerde = tieneAdjuntos ? '<i class="fa-solid fa-circle-check text-success me-2" title="Documentación cargada"></i>' : '';
                const btnAdjuntarCuota = puedeAdjuntar ? `<button class="btn btn-xs btn-outline-info btn-adjuntar-interne" data-id="${propuesta.id}" data-id-cuota="${c.id}" title="Adjuntar Documento"><i class="fa-solid fa-cloud-arrow-up"></i></button>` : '';

                cuotasHtml += `
                <div class="col">
                    <div class="card border-0 bg-light shadow-sm h-100">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>
                                    ${checkVerde}
                                    <span class="badge bg-primary me-2">Pago ${c.num_cuota}</span> 
                                    <strong class="text-dark">${parseFloat(c.monto).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</strong>
                                </span>
                                <div class="d-flex align-items-center">
                                    <span class="small text-muted me-2"><i class="fa-solid fa-calendar-check me-1"></i>${fechaFmt}</span>
                                    ${btnAdjuntarCuota}
                                </div>
                            </div>
                            ${adjuntosCuotaHtml}
                        </div>
                    </div>
                </div>`;
            });
            cuotasHtml += `</div>`;
        } else if (propuesta.estado === 'ACEPTADA') {
            // Si no hay cuotas pero la propuesta está aceptada, mostramos la sección de adjuntar comprobante de pago único
            const adjuntosGeneralesPago = (adjuntos || []).filter(a => !a.id_cuota);
            const tieneAdjuntosPago = adjuntosGeneralesPago.length > 0;

            let adjuntosListHtml = '';
            if (tieneAdjuntosPago) {
                adjuntosListHtml = '<div class="mt-2 border-top pt-2">';
                adjuntosGeneralesPago.forEach(a => {
                    adjuntosListHtml += `<div class="small d-flex justify-content-between align-items-center mb-1 bg-white p-1 rounded border">
                        <span class="text-truncate text-muted" style="max-width: 200px;" title="${a.nombre_archivo}"><i class="fa-solid fa-file-invoice me-1"></i>${a.nombre_archivo}</span>
                        <div class="btn-group">
                            <a href="${a.ruta_archivo}" target="_blank" class="btn btn-xs btn-outline-primary" title="Ver archivo"><i class="fa-solid fa-eye"></i></a>
                            <button class="btn btn-xs btn-outline-danger btn-eliminar-adjunto" data-id-adjunto="${a.id}" title="Eliminar"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                    </div>`;
                });
                adjuntosListHtml += '</div>';
            }

            const checkVerde = tieneAdjuntosPago ? '<i class="fa-solid fa-circle-check text-success me-2" title="Documentación cargada"></i>' : '';
            const fechaFmt = propuesta.fecha_propuesta_pago ? new Date(propuesta.fecha_propuesta_pago + 'T00:00:00').toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' }) : 'Fecha no definida';

            cuotasHtml = `
            <h5 class="mt-4"><i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>Comprobante de Pago</h5>
            <div class="card border-0 bg-light shadow-sm mb-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            ${checkVerde}
                            <span class="badge bg-success me-2">Pago Único</span>
                            <strong class="text-dark">${parseFloat(propuesta.total_propuesto).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</strong>
                            <span class="small text-muted ms-3"><i class="fa-solid fa-calendar-check me-1"></i>${fechaFmt}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-info btn-adjuntar-interne" data-id="${propuesta.id}" data-id-cuota="" title="Adjuntar Comprobante">
                            <i class="fa-solid fa-cloud-arrow-up me-1"></i>Adjuntar Comprobante
                        </button>
                    </div>
                    ${adjuntosListHtml}
                </div>
            </div>`;
        }

        let negociacionHtml = '';
        const esNegociable = propuesta.estado === 'PENDIENTE_APROBACION_CLIENTE' || propuesta.estado === 'PENDIENTE_APROBACION_FINAL';

        if (esNegociable) {
            const fechaActual = propuesta.fecha_propuesta_pago ? propuesta.fecha_propuesta_pago.split(' ')[0] : new Date().toISOString().split('T')[0];

            negociacionHtml = `
            <div class="card bg-white border-primary mt-4 shadow-sm">
                <div class="card-header bg-primary text-white py-2">
                    <h5 class="card-title mb-0 fs-6"><i class="fa-solid fa-comments-dollar me-2"></i>Negociar Propuesta / Contrapropuesta</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="negociacion-medio-pago" class="form-label fw-bold">Medio de Pago:</label>
                            <select class="form-select" id="negociacion-medio-pago">
                                <option value="ECHECK" ${propuesta.medio_de_pago === 'ECHECK' ? 'selected' : ''}>ECHECK</option>
                                <option value="TRANSFERENCIA" ${propuesta.medio_de_pago === 'TRANSFERENCIA' ? 'selected' : ''}>TRANSFERENCIA</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="negociacion-fecha-pago" class="form-label fw-bold">Nueva Fecha Límite:</label>
                            <input type="date" class="form-control" id="negociacion-fecha-pago" value="${fechaActual}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="negociacion-cantidad-cuotas" class="form-label fw-bold">Cantidad de Pagos:</label>
                            <select class="form-select" id="negociacion-cantidad-cuotas">
                                <option value="1" ${(!data.cuotas || data.cuotas.length <= 1) ? 'selected' : ''}>1 Pago Único</option>
                                <option value="2" ${data.cuotas && data.cuotas.length === 2 ? 'selected' : ''}>2 Pagos</option>
                                <option value="3" ${data.cuotas && data.cuotas.length === 3 ? 'selected' : ''}>3 Pagos</option>
                                <option value="4" ${data.cuotas && data.cuotas.length === 4 ? 'selected' : ''}>4 Pagos</option>
                                <option value="5" ${data.cuotas && data.cuotas.length === 5 ? 'selected' : ''}>5 Pagos</option>
                                <option value="6" ${data.cuotas && data.cuotas.length === 6 ? 'selected' : ''}>6 Pagos</option>
                            </select>
                        </div>
                    </div>

                    <div id="negociacion-cuotas-container" class="mt-2 p-3 bg-light border rounded mb-3" style="${(data.cuotas && data.cuotas.length > 1) ? '' : 'display:none;'}">
                        <h6 class="small fw-bold mb-3">Plan de Pagos Propuesto:</h6>
                        <div id="negociacion-cuotas-list"></div>
                    </div>

                    <div class="mb-0">
                        <label for="comentario-contrapropuesta" class="form-label fw-bold">Comentario / Justificación (Requerido para enviar):</label>
                        <textarea class="form-control" id="comentario-contrapropuesta" rows="2" placeholder="Explique brevemente el motivo de su contrapropuesta..."></textarea>
                    </div>
                </div>
            </div>`;
        }

        // historialHtml se calcula después...
        let historialHtml = '<h5>Historial de la Negociación</h5><div class="timeline">';
        historial.forEach(h => {
            const icon = h.tipo_usuario === 'ADMIN' ? 'fa-user-shield' : 'fa-user-tie';
            const align = h.tipo_usuario === 'ADMIN' ? 'left' : 'right';

            let adjuntoHtml = '';
            if (h.ruta_adjunto) {
                adjuntoHtml = `<div class="mt-2 historial-adjunto"><a href="${h.ruta_adjunto}" target="_blank" title="Ver imagen adjunta"><img src="${h.ruta_adjunto}" alt="Adjunto del historial" class="img-thumbnail" style="max-width: 150px; cursor: pointer;"></a></div>`;
            }

            historialHtml += `<div class="timeline-item timeline-item-${align}"><div class="timeline-icon"><i class="fas ${icon}"></i></div><div class="timeline-content"><span class="timeline-date">${h.fecha_evento}</span><p><strong>${h.descripcion}</strong></p>${h.comentario ? `<p class="fst-italic bg-light p-2 rounded">Comentario: "${h.comentario}"</p>` : ''}${adjuntoHtml}</div></div>`;
        });
        historialHtml += '</div>';

        // Mostramos los botones de acción según el estado
        if (propuesta.estado === 'PENDIENTE_APROBACION_CLIENTE') {
            $('#btn-aceptar-propuesta, #btn-enviar-contrapropuesta').show();
        } else if (propuesta.estado === 'PENDIENTE_APROBACION_FINAL') {
            $('#btn-aceptar-propuesta').show();
        }

        contentDiv.html(resumenHtml + adjuntosHtml + itemsHtml + cuotasHtml + negociacionHtml + historialHtml);

        // --- Nueva lógica para manejo de cuotas en Negociación ---
        const $cantCuotasNeg = $('#negociacion-cantidad-cuotas');
        const $cuotasContainerNeg = $('#negociacion-cuotas-container');
        const $cuotasListNeg = $('#negociacion-cuotas-list');
        const $fechaLimiteNeg = $('#negociacion-fecha-pago');

        if ($cantCuotasNeg.length) {
            $cantCuotasNeg.off('change').on('change', function () {
                const cant = parseInt($(this).val());
                if (cant > 1) {
                    $cuotasContainerNeg.show();
                    // Obtenemos el total actualizado del DOM
                    const totalActual = parseFloat($('#total-neto-tabla').text().replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0;
                    generarCamposCuotasNeg(cant, totalActual, $fechaLimiteNeg.val());
                } else {
                    $cuotasContainerNeg.hide();
                }
            });

            // Si ya venía con cuotas, las cargamos en el editor
            if (data.cuotas && data.cuotas.length > 1) {
                cargarCuotasExistentesNeg(data.cuotas, $fechaLimiteNeg.val());
            }

            $fechaLimiteNeg.off('change').on('change', function () {
                // El cambio de fecha dispara recalcularPropuestaCliente()
                // que a su vez llama a generarCamposCuotasNeg si es necesario
                // Pero también actualizamos los 'max' por si acaso
                const nuevaFecha = $(this).val();
                $('.neg-cuota-fecha').attr('max', nuevaFecha);
                $('.neg-cuota-fecha').each(function () {
                    if ($(this).val() > nuevaFecha) $(this).val(nuevaFecha);
                });
            });
        }

    }

    // 3. LÓGICA PARA LOS BOTONES DE ACCIÓN DEL MODAL
    $('#btn-aceptar-propuesta, #btn-enviar-contrapropuesta').on('click', function () {
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
            const nuevaFecha = $('#negociacion-fecha-pago').val();
            const nuevoMedio = $('#negociacion-medio-pago').val();
            const cantCuotas = parseInt($('#negociacion-cantidad-cuotas').val());

            if (!comentario) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Comentario Requerido',
                    text: 'Debe agregar un comentario para poder enviar una contrapropuesta.',
                });
                return;
            }

            let cuotasArr = [];
            if (cantCuotas > 1) {
                $('.neg-cuota-row').each(function (index) {
                    const m = $(this).find('.neg-cuota-monto').val();
                    const f = $(this).find('.neg-cuota-fecha').val();
                    cuotasArr.push({ num_cuota: index + 1, monto: m, fecha_vencimiento: f });
                });
            }

            // Recolectamos los datos de la contrapropuesta
            const datosContrapropuesta = {
                nuevo_total: parseFloat($('#total-neto-tabla').text().replace(/\$\s*/, '').replace(/\./g, '').replace(',', '.')) || 0,
                nueva_fecha: $('#negociacion-fecha-pago').val(),
                nuevo_medio_pago: $('#negociacion-medio-pago').val(),
                cuotas: cuotasArr.length > 0 ? cuotasArr : null // Añadimos el array de cuotas si existe
            };

            enviarAccion(idPropuesta, 'CONTRAPROPUESTA_CLIENTE', comentario, datosContrapropuesta);
        }
    });

    // Evento para eliminar adjuntos
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
                            // Recargamos el detalle
                            if (idPropuesta) {
                                $.ajax({
                                    url: `api/propuestas_controller.php?action=ver_detalle&id=${idPropuesta}`,
                                    type: 'GET',
                                    dataType: 'json',
                                    success: function (res) {
                                        if (res.success) renderizarDetallePropuesta(res.data);
                                    }
                                });
                            }
                            tablaPropuestas.ajax.reload();
                            cargarKPIsCliente();
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

    function enviarAccion(idPropuesta, nuevoEstado, comentario, datosContrapropuesta) {
        // ===== USAMOS FormData PARA ENVIAR ARCHIVOS =====
        const formData = new FormData();
        formData.append('id_propuesta', idPropuesta);
        formData.append('nuevo_estado', nuevoEstado);
        formData.append('comentario', comentario);

        if (datosContrapropuesta) {
            // FormData no maneja objetos anidados bien, así que los aplanamos
            formData.append('contrapropuesta[nuevo_total]', datosContrapropuesta.nuevo_total);
            formData.append('contrapropuesta[nueva_fecha]', datosContrapropuesta.nueva_fecha);
            formData.append('contrapropuesta[nuevo_medio_pago]', datosContrapropuesta.nuevo_medio_pago);

            if (datosContrapropuesta.cuotas) {
                formData.append('contrapropuesta[cuotas]', JSON.stringify(datosContrapropuesta.cuotas));
            }
        }

        $.ajax({
            url: 'api/propuestas_controller.php?action=actualizar_estado',
            type: 'POST',
            data: formData, // Enviamos el objeto FormData
            processData: false,  // Importante para no procesar el FormData
            contentType: false, // Importante para que el navegador establezca el tipo correcto
            dataType: 'json',
            success: function (response) {
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
                            success: function (detailResponse) {
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
            error: function () {
                Swal.fire('Error', 'Error de conexión al realizar la acción.', 'error');
            },
            complete: function () {
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
            success: function (response) {
                if (response.success) {
                    const eventos = response.data;
                    const fechasResaltadas = eventos.map(e => e.date.split(' ')[0]);

                    function formatDate(d) {
                        return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
                    }

                    $("#cronograma-calendario").datepicker({
                        dateFormat: "yy-mm-dd",
                        beforeShowDay: function (date) {
                            const dateString = formatDate(date);
                            if (fechasResaltadas.includes(dateString)) {
                                return [true, "highlight-date", "Tiene un vencimiento de pago"];
                            }
                            return [true, ""];
                        },
                        onSelect: function (dateText) {
                            const contenedorDetalles = $('#cronograma-detalles');
                            contenedorDetalles.empty();

                            const eventosDelDia = eventos.filter(evento => evento.date.split(' ')[0] === dateText);

                            if (eventosDelDia.length > 0) {
                                let detallesHtml = `<h6 class="border-bottom pb-2">Vencimientos para el ${new Date(dateText + 'T00:00:00').toLocaleDateString('es-AR')}:</h6>`;
                                detallesHtml += '<ul class="list-group list-group-flush">';
                                eventosDelDia.forEach(evento => {
                                    const montoFormateado = parseFloat(evento.monto).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
                                    const cuotaInfo = evento.total_cuotas > 1 ? `<span class="badge bg-light text-primary border border-primary ms-2">Pago ${evento.num_cuota} de ${evento.total_cuotas}</span>` : '';

                                    detallesHtml += `
                                    <div class="card mb-2 border-left-primary shadow-sm">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Propuesta #${evento.id} ${cuotaInfo}</div>
                                                    <div class="h6 mb-0 font-weight-bold text-gray-800">${montoFormateado}</div>
                                                </div>
                                                <button class="btn btn-sm btn-outline-primary btn-detalle" data-id="${evento.id}" data-cod-cliente="${evento.cod_cliente}">Ver</button>
                                            </div>
                                        </div>
                                    </div>`;
                                });
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