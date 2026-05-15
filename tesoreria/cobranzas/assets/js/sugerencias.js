/**
 * LÓGICA FINAL PARA SUGERENCIAS DE PROPUESTAS
 * - Soporte para comprobantes negativos (NC)
 * - Columnas Código y Razón Social separadas (Mimético con Franquicias)
 * - Eliminación de comprobantes de la propuesta
 * - DESCUENTOS AUTOMÁTICOS SEGÚN FECHA Y MEDIO DE PAGO
 * - FECHA LÍMITE SUGERIDA: Newest Invoice + 15 business days
 */
window.tablaSugerencias = null;
let sugerenciaSeleccionada = null;

const reglasDescuentoGlobal = {
    'ECHECK': { 15: 8, 22: 6, 29: 4 },
    'TRANSFERENCIA': { 15: 6, 22: 4, 29: 2 }
};

function calcularPorcentajeDescuentoAuto(medioPago, fechaPropuesta, fechaOriginal) {
    if (!reglasDescuentoGlobal[medioPago] || !fechaPropuesta || !fechaOriginal) return 0;
    
    const fSel = new Date(fechaPropuesta + 'T00:00:00');
    const fOrig = new Date(fechaOriginal + 'T00:00:00');
    
    // Diferencia en días entre la fecha seleccionada y la original sugerida por el sistema
    const diffTime = fSel.getTime() - fOrig.getTime();
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) return reglasDescuentoGlobal[medioPago][15]; // No debería ocurrir por el 'min' del input
    
    if (diffDays < 7) return reglasDescuentoGlobal[medioPago][15];  // Semana 1 (0-6 días de retraso): 8%
    if (diffDays < 14) return reglasDescuentoGlobal[medioPago][22]; // Semana 2 (7-13 días de retraso): 6%
    if (diffDays < 21) return reglasDescuentoGlobal[medioPago][29]; // Semana 3 (14-20 días de retraso): 4%
    
    return 0; // Semana 4 en adelante: 0%
}

let isSugerenciasLoading = false;
function initializeSugerenciasDataTable() {
    if (typeof $ === 'undefined' || isSugerenciasLoading) return;
    isSugerenciasLoading = true;
    const url = 'api/cobranzas_controller.php?tipo=sugerencias&recalcular=true';
        $('#summary-cards').hide();
        $('#gestion-dashboard').hide();
        $('#btn-abrir-parametros').hide();
        
        if ($.fn.DataTable.isDataTable('#tabla-sugerencias')) {
            window.tablaSugerencias.ajax.url(url).load(() => { isSugerenciasLoading = false; });
        } else {
            window.tablaSugerencias = $('#tabla-sugerencias').DataTable({
                ajax: { 
                    url: 'api/cobranzas_controller.php?action=get_resumen_cobranzas&tipo=sugerencias' + (window.forzarRecalculoSugerencias ? '&recalcular=true' : ''), 
                    dataSrc: 'data',
                    complete: () => { isSugerenciasLoading = false; window.forzarRecalculoSugerencias = false; }
                },
                columns: [
                    { data: 'COD_CLIENT', title: 'Código', className: 'font-weight-bold text-gray-800' },
                    { data: 'RAZON_SOCI', title: 'Razón Social' },
                    { data: null, title: 'Segmento', className: 'text-center', render: (d) => `${d.COMPROBANTES.length} ítems` },
                    { data: 'TOTAL_BRUTO_SUG', title: 'Monto Bruto', render: $.fn.dataTable.render.number('.', ',', 2, '$ '), className: 'text-end' },
                    { data: 'TOTAL_NETO_SUG', title: 'Monto Neto', render: $.fn.dataTable.render.number('.', ',', 2, '$ '), className: 'text-end font-weight-bold text-gray-800' },
                    { 
                        data: 'FECHA_SUGERIDA', 
                        title: 'F. Límite Propuesta', 
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `<span class="badge bg-light text-dark border">${data}</span>`;
                            }
                            return data;
                        }, 
                        className: 'text-center' 
                    },
                    { data: null, title: 'Acciones', orderable: false, className: 'text-center', render: (d) => `<button class="btn btn-outline-info btn-sm btn-revisar-sugerencia" data-cod="${d.COD_CLIENT}" data-idx="${d.IDX}"><i class="fa-solid fa-eye me-1"></i> Revisar Sugerencia</button>` }
                ],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                responsive: true, order: [[5, 'asc']], scrollY: '55vh', scrollCollapse: true, paging: true,
                destroy: true
            });
        }
}

$(document).ready(function() {
    $(document).on('click', '#btn-generar-sugerencias', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fa-solid fa-sync fa-spin me-1"></i> Recalculando...').prop('disabled', true);
        
        window.forzarRecalculoSugerencias = true;
        if ($.fn.DataTable.isDataTable('#tabla-sugerencias')) {
            window.tablaSugerencias.ajax.reload(function() {
                btn.html(originalHtml).prop('disabled', false);
            }, false);
        } else {
            initializeSugerenciasDataTable();
            setTimeout(() => { btn.html(originalHtml).prop('disabled', false); }, 1500);
        }
    });

    // RECALCULO AUTOMÁTICO AL ENTRAR A LA PESTAÑA
    $(document).on('shown.bs.tab', '#sugerencias-tab', function () {
        initializeSugerenciasDataTable();
    });

    $('body').on('click', '.btn-revisar-sugerencia', function() {
        const row = tablaSugerencias.row($(this).closest('tr')).data();
        if (row) {
            sugerenciaSeleccionada = row;
            renderSugerenciaModalMimic(row);
            $('#modalSugerenciaDetalleLabel').html(`<i class="fa-solid fa-wand-magic-sparkles me-2"></i>Sugerencia de Propuesta de Pago`);
            $('#modalSugerenciaDetalle').modal('show');
        }
    });

    function renderSugerenciaModalMimic(data) {
        const fechaSug = data.FECHA_SUGERIDA;
        let headerHtml = `<div class="mb-4 text-gray-800"><h5 class="font-weight-bold">${data.COD_CLIENT} - ${data.RAZON_SOCI}</h5><p class="text-muted small mb-0">Sugerencia personalizada (Segmentada).</p></div>`;

        let resumenKpiHtml = `
            <div class="row mb-4">
                <div class="col-md-4"><div class="card bg-light shadow-sm h-100"><div class="card-body text-center"><h6 class="card-title text-muted text-uppercase small">TOTAL PROPUESTO</h6><p class="card-text h4 font-weight-bold text-primary mb-0" id="total-sug-kpi">${parseFloat(data.TOTAL_NETO_SUG).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</p></div></div></div>
                <div class="col-md-4"><div class="card bg-light shadow-sm h-100"><div class="card-body text-center shadow-none"><h6 class="card-title text-muted text-uppercase small">FECHA LÍMITE PAGO</h6><input type="date" id="fecha-propuesta-sug-modal" class="form-control form-control-sm text-center font-weight-bold border-0 bg-transparent h5 mb-0" value="${fechaSug}" min="${fechaSug}" data-fecha-original="${fechaSug}"></div></div></div>
                <div class="col-md-4"><div class="card bg-light shadow-sm h-100"><div class="card-body text-center shadow-none"><h6 class="card-title text-muted text-uppercase small">MEDIO DE PAGO</h6><select id="medio-pago-sug-modal" class="form-select form-select-sm text-center font-weight-bold border-0 bg-transparent h5 mb-0 shadow-none"><option value="ECHECK" selected>ECHECK</option><option value="TRANSFERENCIA">TRANSFERENCIA</option></select></div></div></div>
            </div>`;

        let itemsHtml = `
            <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="font-weight-bold text-gray-800 mb-0">Facturas Incluidas</h6><small class="text-muted italic">Elimina líneas o edita descuentos para ajustar</small></div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover" id="tabla-items-sugerencia">
                    <thead class="table-light text-center small text-uppercase font-weight-bold middle">
                        <tr><th style="width: 5%"></th><th style="width: 15%">Fecha</th><th style="width: 10%">Tipo</th><th style="width: 25%">Comprobante</th><th style="width: 15%" class="text-end">Importe Bruto</th><th style="width: 12%" class="text-center">% Dcto</th><th style="width: 18%" class="text-end">Importe Neto</th></tr>
                    </thead>
                    <tbody>`;

        data.COMPROBANTES.forEach(item => {
            const bruto = parseFloat(item.importe_bruto);
            itemsHtml += `
                <tr class="small" data-bruto="${bruto}" data-tcomp="${item.t_comp}" data-ncomp="${item.n_comp}" data-dcto-original="${item.porcentaje_descuento}">
                    <td class="text-center"><button class="btn btn-xs btn-outline-danger btn-quitar-item-sug border-0" title="Quitar de la propuesta"><i class="fa-solid fa-trash-can"></i></button></td>
                    <td class="text-center">${item.fecha_emision || '-'}</td>
                    <td class="text-center">${item.t_comp}</td>
                    <td>${item.n_comp}</td>
                    <td class="text-end ${bruto < 0 ? 'text-danger' : ''}">${bruto.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td>
                    <td class="text-center"><input type="number" class="form-control form-control-sm mx-auto sug-dcto-input text-center" value="${item.porcentaje_descuento.toFixed(2)}" min="0" max="100" step="0.01" style="width: 60px; height: 22px; padding: 0;"></td>
                    <td class="text-end font-weight-bold text-primary sug-neto-cell ${bruto < 0 ? 'text-danger' : ''}">${parseFloat(item.importe_neto).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td>
                </tr>`;
        });

        itemsHtml += `</tbody>
                    <tfoot class="table-light font-weight-bold">
                        <tr><td colspan="4" class="text-right">Totales Sugeridos:</td><td class="text-end" id="sug-total-bruto">${parseFloat(data.TOTAL_BRUTO_SUG).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td><td></td><td class="text-end text-gray-800 h5 font-weight-bold" id="sug-total-neto">${parseFloat(data.TOTAL_NETO_SUG).toLocaleString('es-AR', { style: 'currency', currency: 'ARS' })}</td></tr>
                    </tfoot></table>
            </div>`;

        $('#sugerencia-detalle-body').html(headerHtml + resumenKpiHtml + itemsHtml);
        
        // Forzamos el recálculo inmediato para asegurar que los totales coincidan con los ítems
        recalcularTotalesSugerencia();
    }

    // ELIMINAR ITEM
    $('#modalSugerenciaDetalle').on('click', '.btn-quitar-item-sug', function() {
        const tr = $(this).closest('tr');
        Swal.fire({
            title: '¿Quitar comprobante?',
            text: "Se excluirá de esta propuesta sugerida.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, quitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                tr.fadeOut(300, function() { $(this).remove(); recalcularTotalesSugerencia(); });
            }
        });
    });

    // Se deshabilita el recalculo automático masivo para mantener consistencia con el detalle de Franquicias
    // El usuario podrá editar los descuentos manualmente si lo desea.
    $('#modalSugerenciaDetalle').on('change', '#fecha-propuesta-sug-modal, #medio-pago-sug-modal', function() {
        const inputFecha = $('#fecha-propuesta-sug-modal');
        const pct = calcularPorcentajeDescuentoAuto($('#medio-pago-sug-modal').val(), inputFecha.val(), inputFecha.data('fecha-original'));
        $('#tabla-items-sugerencia tbody tr').each(function() {
            const tr = $(this);
            const nComp = (tr.data('ncomp') || '').trim();
            const tComp = (tr.data('tcomp') || '').trim();
            const dctoOriginal = parseFloat(tr.data('dcto-original')); // Usamos el valor numérico
            
            let pctAplicar = pct;

            // REGLA: Si la factura ya venía con 0% (ej: vencida), no se le otorga descuento nuevo jamás
            if (dctoOriginal === 0) {
                pctAplicar = 0;
            } else if (['NCR', 'NCP', 'NDP'].includes(tComp)) {
                pctAplicar = 0;
            } else if (nComp.startsWith('A00115') && tComp === 'FAC') {
                pctAplicar = 0;
            }
            
            tr.find('.sug-dcto-input').val(pctAplicar.toFixed(2)).trigger('input');
        });
    });

    // RECALCULO MANUAL
    $('#modalSugerenciaDetalle').on('input', '.sug-dcto-input', function() {
        const tr = $(this).closest('tr');
        const bruto = parseFloat(tr.data('bruto'));
        let dcto = parseFloat($(this).val()) || 0;
        if (dcto < 0) dcto = 0; if (dcto > 100) { dcto = 100; $(this).val(100.00); }
        const neto = bruto * (1 - (dcto / 100));
        tr.find('.sug-neto-cell').text(neto.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));
        recalcularTotalesSugerencia();
    });

    function recalcularTotalesSugerencia() {
        let tBruto = 0; let tNeto = 0;
        $('#tabla-items-sugerencia tbody tr').each(function() {
            const tr = $(this);
            const bruto = parseFloat(tr.data('bruto'));
            const dcto = parseFloat(tr.find('.sug-dcto-input').val()) || 0;
            tBruto += bruto;
            tNeto += bruto * (1 - (dcto / 100));
        });
        $('#sug-total-bruto').text(tBruto.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));
        $('#sug-total-neto').text(tNeto.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));
        $('#total-sug-kpi').text(tNeto.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' }));
    }

    $('#btn-crear-propuesta-desde-sug').on('click', function() {
        const btn = $(this);
        const fecha = $('#fecha-propuesta-sug-modal').val();
        if (!fecha) return Swal.fire('Atención', 'Elija fecha límite.', 'warning');

        let comps = []; let totalFinal = 0;
        $('#tabla-items-sugerencia tbody tr').each(function() {
            const tr = $(this);
            const bruto = parseFloat(tr.data('bruto'));
            const dcto = parseFloat(tr.find('.sug-dcto-input').val()) || 0;
            const neto = bruto * (1 - (dcto/100));
            comps.push({ t_comp: tr.data('tcomp'), n_comp: tr.data('ncomp'), importe_bruto: bruto, importe_neto: neto, porcentaje_descuento: dcto });
            totalFinal += neto;
        });

        if (comps.length === 0) return Swal.fire('Atención', 'No hay comprobantes en la propuesta.', 'warning');

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Enviando...');
        $.ajax({
            url: 'api/cobranzas_controller.php?action=crear_propuesta',
            type: 'POST',
            data: { 
                cod_cliente: sugerenciaSeleccionada.COD_CLIENT, 
                idx_sugerencia: sugerenciaSeleccionada.IDX, // Para limpiar el caché
                comprobantes: comps, 
                total_propuesto: totalFinal, 
                fecha_propuesta_pago: fecha, 
                medio_de_pago: $('#medio-pago-sug-modal').val(), 
                cuotas: [] 
            },
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    Swal.fire({ title: 'Éxito', text: 'Propuesta generada correctamente.', icon: 'success', timer: 2000, showConfirmButton: false }).then(() => {
                        $('#modalSugerenciaDetalle').modal('hide');
                        // Eliminamos la fila de la tabla sin recargar todo el listado
                        if (window.tablaSugerencias) {
                            window.tablaSugerencias.row($(`.btn-revisar-sugerencia[data-cod="${sugerenciaSeleccionada.COD_CLIENT}"][data-idx="${sugerenciaSeleccionada.IDX}"]`).closest('tr')).remove().draw();
                        }
                    });
                } else Swal.fire('Error', res.message, 'error');
            },
            error: () => Swal.fire('Error', 'Servidor no responde.', 'error'),
            complete: () => btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Generar y Enviar Propuesta')
        });
    });
});
