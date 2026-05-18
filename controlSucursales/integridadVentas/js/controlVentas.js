/**
 * controlVentas.js - Lógica para la pestaña de Control de Ventas por Sucursal
 */

$(document).ready(function() {
    // Solo ejecutar si estamos en la pestaña de control de ventas
    if ($('#tabla-control-ventas').length === 0) {
        return;
    }

    const tablaResultados = $('#tabla-resultados-control');
    const btnConsultar = $('#btn-consultar-control');

    // Event Listeners
    btnConsultar.on('click', function() {
        ejecutarSPMasivoYRecargarTabla();
    });

    $('#btn-descargar-excel-control').on('click', function() {
        descargarExcelControl();
    });

    // Delegación de evento para botones de actualizar por sucursal
    $('#resultado-consulta-control').on('click', '.btn-actualizar-sucursal', function() {
        const nroSucursal = $(this).data('sucursal');
        ejecutarSPPorSucursal(nroSucursal);
    });

    // Delegación de evento para botones de ver detalle
    $('#resultado-consulta-control').on('click', '.btn-ver-detalle-ventas', function() {
        const btn = $(this);
        abrirDetalleVentas({
            nro:       btn.data('sucursal'),
            cod:       btn.data('cod'),
            central:   btn.data('central'),
            local:     btn.data('local'),
            diferencia: btn.data('diferencia')
        });
    });

    // Filtros de estado dentro del modal
    $(document).on('click', '.dv-filter-btn', function() {
        $('.dv-filter-btn').removeClass('active');
        $(this).addClass('active');
        const filter = $(this).data('filter');
        filtrarFilasDetalle(filter);
    });

    // Exportar a Excel desde el modal
    $(document).on('click', '#btn-exportar-detalle-ventas', function() {
        exportarDetalleVentas();
    });

    // Carga inicial optimista
    cargarDatosEnTabla(function() {
        actualizarDatosEnBackground();
    });

    /**
     * Ejecutar el proceso masivo y recargar la tabla (con spinner)
     */
    function ejecutarSPMasivoYRecargarTabla() {
        const fechaDesde = $('#fecha-desde-control').val();
        const fechaHasta = $('#fecha-hasta-control').val();

        if (!fechaDesde || !fechaHasta) {
            alert('Por favor, seleccione un rango de fechas.');
            return;
        }

        realizarAjax('Controller/controlVentasController.php', {
            action: 'ejecutar_masivo',
            desde: fechaDesde,
            hasta: fechaHasta
        }, function(response) {
            if (response.success) {
                cargarDatosEnTabla();
            } else {
                alert('Error al ejecutar el proceso: ' + response.message);
            }
        }, true);
    }

    /**
     * Ejecutar el SP para una sucursal específica
     */
    function ejecutarSPPorSucursal(nroSucursal) {
        const fechaDesde = $('#fecha-desde-control').val();
        const fechaHasta = $('#fecha-hasta-control').val();

        realizarAjax('Controller/controlVentasController.php', {
            action: 'ejecutar_sucursal',
            desde: fechaDesde,
            hasta: fechaHasta,
            nro_sucurs: nroSucursal
        }, function(response) {
            if (response.success) {
                cargarDatosEnTabla();
            }
        }, true);
    }

    /**
     * Cargar datos en la tabla
     */
    function cargarDatosEnTabla(callback) {
        const fechaDesde = $('#fecha-desde-control').val();
        const fechaHasta = $('#fecha-hasta-control').val();

        realizarAjax('Controller/controlVentasController.php', {
            action: 'obtener_datos',
            desde: fechaDesde,
            hasta: fechaHasta
        }, function(response) {
            if (response.success) {
                renderizarTabla(response.data);
                if (callback && typeof callback === 'function') {
                    callback();
                }
            } else {
                alert('Error al cargar los datos: ' + response.message);
            }
        }, false);
    }

    /**
     * Actualizar datos en segundo plano sin bloquear la UI
     */
    function actualizarDatosEnBackground() {
        const fechaDesde = $('#fecha-desde-control').val();
        const fechaHasta = $('#fecha-hasta-control').val();

        console.log("Iniciando actualización de datos en segundo plano...");

        $.ajax({
            url: 'Controller/controlVentasController.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ejecutar_masivo',
                desde: fechaDesde,
                hasta: fechaHasta
            },
            success: function(response) {
                if (response.success) {
                    console.log("Actualización en segundo plano completada. Refrescando tabla.");
                    cargarDatosEnTabla();
                } else {
                    console.error("Error en la actualización de fondo: " + response.message);
                }
            },
            error: function(xhr) {
                console.error("Error de conexión en la actualización de fondo:", xhr.responseText);
            }
        });
    }

    /**
     * Renderizar la tabla con los datos
     */
    function renderizarTabla(data) {
        tablaResultados.empty();
        
        if (!data || data.length === 0) {
            tablaResultados.html('<tr><td colspan="8" class="text-center text-muted"><i class="bi bi-info-circle me-2"></i>No se encontraron resultados para el período seleccionado.</td></tr>');
            actualizarTotales(0, 0, 0);
            return;
        }

        let totalImporteCentral = 0;
        let totalImporteLocal = 0;
        let totalDiferencia = 0;

        $.each(data, function(index, fila) {
            const estadoBadge = fila.ESTADO === 'OK'
                ? '<span class="integridadVentas_badge-ok">OK</span>'
                : `<span class="integridadVentas_badge-error">${fila.ESTADO}</span>`;

            let refreshedDate = 'N/A';
            if (fila.REFRESHED_AT && fila.REFRESHED_AT.date) {
                // Crear fecha sin ajuste de zona horaria
                const dateString = fila.REFRESHED_AT.date;
                const date = new Date(dateString);
                
                // Formatear usando la zona horaria de Argentina
                refreshedDate = date.toLocaleString('es-AR', {
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric',
                    hour: '2-digit', 
                    minute: '2-digit',
                    timeZone: 'America/Argentina/Buenos_Aires'
                });
            }

            totalImporteCentral += parseFloat(fila.IMPORTE_CENTRAL) || 0;
            totalImporteLocal += parseFloat(fila.IMPORTE_LOCAL) || 0;
            totalDiferencia += parseFloat(fila.DIFERENCIA) || 0;

            const filaHtml = `
                <tr>
                    <td>${fila.NUM_SUC}</td>
                    <td>${fila.COD_SUCURSAL || 'N/A'}</td>
                    <td>${formatCurrency(fila.IMPORTE_CENTRAL)}</td>
                    <td>${formatCurrency(fila.IMPORTE_LOCAL)}</td>
                    <td>${formatCurrency(fila.DIFERENCIA)}</td>
                    <td>${estadoBadge}</td>
                    <td>${refreshedDate}</td>
                    <td style="white-space:nowrap;">
                        <button class="integridadVentas_btn-actualizar btn-actualizar-sucursal" data-sucursal="${fila.NUM_SUC}" title="Actualizar esta sucursal">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
                        </button>
                        <button class="integridadVentas_btn-detalle btn-ver-detalle-ventas ms-1"
                                data-sucursal="${fila.NUM_SUC}"
                                data-cod="${fila.COD_SUCURSAL || ''}"
                                data-central="${fila.IMPORTE_CENTRAL}"
                                data-local="${fila.IMPORTE_LOCAL}"
                                data-diferencia="${fila.DIFERENCIA}"
                                title="Ver detalle de comprobantes">
                            <i class="bi bi-list-columns-reverse"></i> Ver detalle
                        </button>
                    </td>
                </tr>
            `;
            tablaResultados.append(filaHtml);
        });

        actualizarTotales(totalImporteCentral, totalImporteLocal, totalDiferencia);
    }

    // ── DETALLE DE VENTAS ────────────────────────────────────────────────

    let _detalleData   = []; // cache de filas completas del detalle actual
    let _detalleParams = {}; // parámetros de la consulta activa

    /**
     * Abre el modal y carga el detalle de una sucursal
     */
    function abrirDetalleVentas(params) {
        _detalleParams = params;
        _detalleData   = [];

        const fechaDesde = $('#fecha-desde-control').val();
        const fechaHasta = $('#fecha-hasta-control').val();

        // Resetear contenido del modal — cards en estado de carga hasta tener datos reales
        $('#dv-m-sucursal').text('Suc. ' + params.nro);
        $('#dv-m-cod-sucursal').text(params.cod || '—');
        $('#dv-m-central').text('—').css('color', '');
        $('#dv-m-local').text('—').css('color', '');
        $('#dv-m-periodo').text(formatFechaCorta(fechaDesde) + ' → ' + formatFechaCorta(fechaHasta));
        $('#dv-m-registros').text('Consultando…');
        $('#dv-m-diferencia').text('—').css('color', '');
        $('#dv-m-estado-badge').html('<span style="color:#64748b;font-size:.75rem;">Cargando…</span>');
        $('.dv-card-diferencia').removeClass('dv-card-diff-bad');

        $('#tbody-detalle-ventas').empty();
        $('#tfoot-detalle-ventas').hide();
        $('.dv-filter-btn').removeClass('active').filter('[data-filter="all"]').addClass('active');
        $('#dv-loading').show();
        $('#dv-tabla-container').hide();

        const modal = new bootstrap.Modal(document.getElementById('modalDetalleVentas'));
        modal.show();

        $.ajax({
            url: 'Controller/controlVentasController.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'obtener_detalle_ventas',
                desde:      fechaDesde,
                hasta:      fechaHasta,
                nro_sucurs: params.nro
            },
            success: function(response) {
                $('#dv-loading').hide();
                $('#dv-tabla-container').show();
                if (response.success) {
                    _detalleData = response.data || [];
                    renderizarDetalleVentas(_detalleData);
                } else {
                    mostrarErrorEnDetalleModal(response.message);
                }
            },
            error: function(xhr) {
                $('#dv-loading').hide();
                $('#dv-tabla-container').show();
                mostrarErrorEnDetalleModal('Error de conexión: ' + xhr.status);
            }
        });
    }

    /**
     * Renderiza la tabla del modal con los datos del detalle
     */
    function renderizarDetalleVentas(data) {
        const tbody = $('#tbody-detalle-ventas');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html(`
                <tr class="dv-empty-row">
                    <td colspan="8">
                        <div class="dv-empty-state">
                            <i class="bi bi-inbox dv-empty-icon"></i>
                            <p>No se encontraron registros para este período</p>
                        </div>
                    </td>
                </tr>`);
            $('#dv-table-count').text('0 registros');
            $('#tfoot-detalle-ventas').hide();
            return;
        }

        let totCantC = 0, totImpC = 0, totCantL = 0, totImpL = 0, totDif = 0;

        data.forEach(function(r) {
            const dif   = parseFloat(r.DIFERENCIA) || 0;
            const impC  = parseFloat(r.IMPORTE_CENTRAL) || 0;
            const impL  = parseFloat(r.IMPORTE_LOCAL) || 0;
            const cantC = parseInt(r.CANT_CENTRAL) || 0;
            const cantL = parseInt(r.CANT_LOCAL) || 0;

            totCantC += cantC;
            totImpC  += impC;
            totCantL += cantL;
            totImpL  += impL;
            totDif   += dif;

            let rowClass = 'dv-row-ok';
            if (cantC === 0)       rowClass = 'dv-row-solo-local';
            else if (cantL === 0)  rowClass = 'dv-row-solo-central';
            else if (r.ESTADO !== 'OK') rowClass = 'dv-row-difiere';

            let difClass = 'dv-dif-cero';
            if (dif > 0.005)  difClass = 'dv-dif-positiva';
            if (dif < -0.005) difClass = 'dv-dif-negativa';

            const estadoBadge = r.ESTADO === 'OK'
                ? '<span class="dv-badge-estado dv-badge-ok"><i class="bi bi-check2"></i>OK</span>'
                : '<span class="dv-badge-estado dv-badge-difiere"><i class="bi bi-exclamation-triangle-fill"></i>DIFIERE</span>';

            const fecha = r.FECHA_EMIS ? formatFechaCorta(r.FECHA_EMIS) : '—';

            tbody.append(`
                <tr class="${rowClass}" data-estado="${r.ESTADO}">
                    <td><span class="dv-badge-tcomp">${r.T_COMP || '—'}</span></td>
                    <td class="dv-fecha-cell">${fecha}</td>
                    <td class="dv-num-cell">${cantC > 0 ? cantC : '<span style="color:#94a3b8">—</span>'}</td>
                    <td class="dv-imp-cell">${impC !== 0 ? formatCurrency(impC) : '<span style="color:#94a3b8">—</span>'}</td>
                    <td class="dv-num-cell">${cantL > 0 ? cantL : '<span style="color:#94a3b8">—</span>'}</td>
                    <td class="dv-imp-cell">${impL !== 0 ? formatCurrency(impL) : '<span style="color:#94a3b8">—</span>'}</td>
                    <td class="dv-dif-cell ${difClass}">${formatCurrency(dif)}</td>
                    <td class="text-center">${estadoBadge}</td>
                </tr>`);
        });

        // Totales en footer
        $('#dv-tot-cant-central').text(totCantC);
        $('#dv-tot-central').text(formatCurrency(totImpC));
        $('#dv-tot-cant-local').text(totCantL);
        $('#dv-tot-local').text(formatCurrency(totImpL));
        const totDifEl = $('#dv-tot-diferencia');
        totDifEl.text(formatCurrency(totDif));
        totDifEl.css('color', Math.abs(totDif) < 0.01 ? '#15803d' : '#dc2626');
        $('#tfoot-detalle-ventas').show();

        $('#dv-table-count').text(data.length + ' registro' + (data.length !== 1 ? 's' : ''));
        $('#dv-m-registros').text(data.length + ' tipo' + (data.length !== 1 ? 's' : '') + ' de comprobante');

        // Actualizar cards con los valores reales calculados desde la tabla
        $('#dv-m-central').text(formatCurrency(totImpC));
        $('#dv-m-local').text(formatCurrency(totImpL));
        $('#dv-m-diferencia').text(formatCurrency(totDif));
        const cardDif = $('.dv-card-diferencia');
        if (Math.abs(totDif) < 0.01) {
            $('#dv-m-diferencia').css('color', '#15803d');
            $('#dv-m-estado-badge').html('<span style="color:#15803d;font-size:.75rem;">✔ Sin diferencias</span>');
            cardDif.removeClass('dv-card-diff-bad');
        } else {
            $('#dv-m-diferencia').css('color', '#dc2626');
            $('#dv-m-estado-badge').html('<span style="color:#dc2626;font-size:.75rem;">⚠ Con diferencias</span>');
            cardDif.addClass('dv-card-diff-bad');
        }
    }

    /**
     * Filtra las filas visibles según el estado seleccionado
     */
    function filtrarFilasDetalle(filter) {
        const rows = $('#tbody-detalle-ventas tr[data-estado]');
        if (filter === 'all') {
            rows.show();
        } else {
            rows.hide();
            rows.filter('[data-estado="' + filter + '"]').show();
        }
    }

    /**
     * Muestra un error dentro de la tabla del modal
     */
    function mostrarErrorEnDetalleModal(msg) {
        $('#tbody-detalle-ventas').html(`
            <tr class="dv-empty-row">
                <td colspan="8">
                    <div class="dv-empty-state">
                        <i class="bi bi-exclamation-octagon dv-empty-icon" style="color:#dc2626;"></i>
                        <p style="color:#dc2626;">Error: ${msg}</p>
                    </div>
                </td>
            </tr>`);
    }

    /**
     * Exporta la tabla de detalle a Excel via tabla2excel
     */
    function exportarDetalleVentas() {
        const cod = _detalleParams.cod || _detalleParams.nro;
        const desde = $('#fecha-desde-control').val();
        const hasta  = $('#fecha-hasta-control').val();
        $('#tabla-detalle-ventas').table2excel({
            filename: 'detalle_ventas_suc_' + cod + '_' + desde + '_' + hasta + '.xls',
            name: 'Detalle Ventas'
        });
    }

    /**
     * Formatea una fecha YYYY-MM-DD a DD/MM/YYYY
     */
    function formatFechaCorta(fechaStr) {
        if (!fechaStr) return '—';
        try {
            const parts = fechaStr.split('-');
            if (parts.length === 3) return parts[2] + '/' + parts[1] + '/' + parts[0];
            const d = new Date(fechaStr);
            return d.toLocaleDateString('es-AR');
        } catch(e) { return fechaStr; }
    }

    // ── /DETALLE DE VENTAS ───────────────────────────────────────────────

    /**
     * Actualizar los totales en el footer
     */
    function actualizarTotales(totalCentral, totalLocal, totalDiferencia) {
        $('#total-importe-central').text(formatCurrency(totalCentral));
        $('#total-importe-local').text(formatCurrency(totalLocal));
        $('#total-diferencia').text(formatCurrency(totalDiferencia));
    }

    /**
     * Wrapper para llamadas AJAX
     */
    function realizarAjax(url, data, successCallback, showSpinner = false) {
        if (showSpinner) mostrarLoading();
        btnConsultar.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: successCallback,
            error: function(xhr, status, error) {
                console.error("Error en AJAX:", xhr.responseText);
                alert('Ocurrió un error inesperado. Revise la consola para más detalles.');
            },
            complete: function() {
                if (showSpinner) ocultarLoading();
                btnConsultar.prop('disabled', false);
            }
        });
    }

    /**
     * Descargar datos en formato Excel
     */
    function descargarExcelControl() {
        const fechaDesde = $('#fecha-desde-control').val();
        const fechaHasta = $('#fecha-hasta-control').val();

        if (!fechaDesde || !fechaHasta) {
            alert('Por favor, seleccione un rango de fechas.');
            return;
        }

        const form = $('<form>', {
            method: 'POST',
            action: 'Controller/exportarExcel.php'
        });

        form.append($('<input>', {
            type: 'hidden',
            name: 'desde',
            value: fechaDesde
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'hasta',
            value: fechaHasta
        }));

        $('body').append(form);
        form.submit();
        form.remove();
    }
});
