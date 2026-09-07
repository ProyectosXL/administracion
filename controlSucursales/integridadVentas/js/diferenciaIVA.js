/**
 * diferenciaIVA.js - Lógica para la pestaña de Diferencia IVA Ventas
 */

$(document).ready(function() {
    // Solo ejecutar si estamos en la pestaña de diferencia IVA
    if ($('#tabla-diferencia-iva').length === 0) {
        return;
    }

    const tablaResultados = $('#tabla-resultados-iva');
    const btnConsultar = $('#btn-consultar-iva');

    // Event Listeners
    btnConsultar.on('click', function() {
        ejecutarSPMasivoYRecargarTablaIVA();
    });

    $('#btn-descargar-excel-iva').on('click', function() {
        descargarExcelIVA();
    });

    $('#btn-descargar-detalle-iva').on('click', function() {
        descargarDetalleIVA();
    });

    // Delegación de evento para botones de actualizar por sucursal
    $('#resultado-consulta-iva').on('click', '.btn-actualizar-sucursal-iva', function() {
        const nroSucursal = $(this).data('sucursal');
        ejecutarSPPorSucursalIVA(nroSucursal);
    });

    // Delegación de evento para ver detalle de comprobantes (botón "Ver detalle")
    $('#resultado-consulta-iva').on('click', '.btn-ver-detalle-iva', function() {
        const btn = $(this);
        abrirModalDetalleIVA(btn.data('sucursal'), btn.data('cod'), btn.data('comprobantes'));
    });

    // Exportar detalle a Excel desde el modal
    $(document).on('click', '#btn-exportar-detalle-iva-nuevo', function() {
        exportarDetalleExcel();
    });

    // Carga inicial de datos existentes en la tabla (sin ejecutar SP)
    cargarDatosEnTablaIVA();

    /**
     * Ejecutar el proceso masivo y recargar la tabla (con spinner)
     */
    function ejecutarSPMasivoYRecargarTablaIVA() {
        const fechaDesde = $('#fecha-desde-iva').val();
        const fechaHasta = $('#fecha-hasta-iva').val();

        if (!fechaDesde || !fechaHasta) {
            alert('Por favor, seleccione un rango de fechas.');
            return;
        }

        realizarAjaxIVA('Controller/diferenciaIVAController.php', {
            action: 'ejecutar_masivo',
            desde: fechaDesde,
            hasta: fechaHasta
        }, function(response) {
            if (response.success) {
                cargarDatosEnTablaIVA();
            } else {
                alert('Error al ejecutar el proceso: ' + response.message);
            }
        }, true);
    }

    /**
     * Ejecutar el SP para una sucursal específica
     */
    function ejecutarSPPorSucursalIVA(nroSucursal) {
        const fechaDesde = $('#fecha-desde-iva').val();
        const fechaHasta = $('#fecha-hasta-iva').val();

        realizarAjaxIVA('Controller/diferenciaIVAController.php', {
            action: 'ejecutar_sucursal',
            desde: fechaDesde,
            hasta: fechaHasta,
            nro_sucurs: nroSucursal
        }, function(response) {
            if (response.success) {
                cargarDatosEnTablaIVA();
            }
        }, true);
    }

    /**
     * Cargar datos en la tabla
     */
    function cargarDatosEnTablaIVA(callback) {
        const fechaDesde = $('#fecha-desde-iva').val() || '';
        const fechaHasta = $('#fecha-hasta-iva').val() || '';

        realizarAjaxIVA('Controller/diferenciaIVAController.php', {
            action: 'obtener_datos',
            desde: fechaDesde,
            hasta: fechaHasta
        }, function(response) {
            if (response.success) {
                renderizarTablaIVA(response.data);
                if (callback && typeof callback === 'function') {
                    callback();
                }
            } else {
                alert('Error al cargar los datos: ' + response.message);
            }
        }, false);
    }

    /**
     * Renderizar la tabla con los datos
     */
    function renderizarTablaIVA(data) {
        tablaResultados.empty();
        
        if (!data || data.length === 0) {
            tablaResultados.html('<tr><td colspan="9" class="text-center text-muted"><i class="bi bi-info-circle me-2"></i>No se encontraron resultados para el período seleccionado.</td></tr>');
            actualizarTotalesIVA(0, 0, 0, 0);
            return;
        }

        let totalComprobantesDif = 0;
        let totalImporteLocal = 0;
        let totalImporteCentral = 0;
        let totalDiferenciaNeta = 0;

        $.each(data, function(index, fila) {
            const estadoBadge = fila.ESTADO_CONEXION === 1
                ? '<span class="integridadVentas_badge-ok">OK</span>'
                : '<span class="integridadVentas_badge-error">ERROR</span>';

            let refreshedDate = 'N/A';
            if (fila.EJECUCION_AT && fila.EJECUCION_AT.date) {
                const dateString = fila.EJECUCION_AT.date;
                const date = new Date(dateString);
                
                refreshedDate = date.toLocaleString('es-AR', {
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric',
                    hour: '2-digit', 
                    minute: '2-digit',
                    timeZone: 'America/Argentina/Buenos_Aires'
                });
            }

            totalComprobantesDif += parseInt(fila.COMPROBANTES_CON_DIF) || 0;
            totalImporteLocal += parseFloat(fila.IMPORTE_LOCAL_TOTAL) || 0;
            totalImporteCentral += parseFloat(fila.IMPORTE_CENTRAL_TOTAL) || 0;
            totalDiferenciaNeta += parseFloat(fila.DIFERENCIA_NETA) || 0;

            const filaHtml = `
                <tr>
                    <td>${fila.NUM_SUC}</td>
                    <td>${fila.COD_SUCURSAL || 'N/A'}</td>
                    <td>${fila.COMPROBANTES_CON_DIF}</td>
                    <td>${formatCurrency(fila.IMPORTE_LOCAL_TOTAL)}</td>
                    <td>${formatCurrency(fila.IMPORTE_CENTRAL_TOTAL)}</td>
                    <td>${formatCurrency(fila.DIFERENCIA_NETA)}</td>
                    <td>${estadoBadge}</td>
                    <td>${refreshedDate}</td>
                    <td style="white-space:nowrap;">
                        <button class="integridadVentas_btn-actualizar btn-actualizar-sucursal-iva" data-sucursal="${fila.NUM_SUC}" title="Actualizar esta sucursal">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
                        </button>
                        <button class="integridadVentas_btn-detalle btn-ver-detalle-iva ms-1"
                                data-sucursal="${fila.NUM_SUC}"
                                data-cod="${fila.COD_SUCURSAL || ''}"
                                data-comprobantes="${fila.COMPROBANTES_CON_DIF}"
                                title="Ver detalle de comprobantes">
                            <i class="bi bi-list-columns-reverse"></i> Ver detalle
                        </button>
                    </td>
                </tr>
            `;
            tablaResultados.append(filaHtml);
        });

        actualizarTotalesIVA(totalComprobantesDif, totalImporteLocal, totalImporteCentral, totalDiferenciaNeta);
    }

    /**
     * Actualizar los totales en el footer
     */
    function actualizarTotalesIVA(totalComp, totalLocal, totalCentral, totalDif) {
        $('#total-comprobantes-dif').text(totalComp.toLocaleString('es-AR'));
        $('#total-importe-local-iva').text(formatCurrency(totalLocal));
        $('#total-importe-central-iva').text(formatCurrency(totalCentral));
        $('#total-diferencia-neta-iva').text(formatCurrency(totalDif));
    }

    /**
     * Wrapper para llamadas AJAX
     */
    function realizarAjaxIVA(url, data, successCallback, showSpinner = false) {
        if (showSpinner) mostrarLoading();
        btnConsultar.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'text',
            success: function(responseText) {
                try {
                    const response = JSON.parse(responseText);
                    console.log('Respuesta del servidor:', response);
                    if (response && response.success === false) {
                        alert('Error: ' + (response.message || 'Error desconocido'));
                        console.error('Error del servidor:', response);
                    } else {
                        successCallback(response);
                    }
                } catch (e) {
                    console.error("Error al parsear JSON:", e);
                    console.error("Respuesta del servidor:", responseText);
                    alert('Error: La respuesta del servidor no es válida.\n' + responseText.substring(0, 500));
                }
            },
            error: function(xhr, status, error) {
                console.error("Error en AJAX IVA:", {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                let errorMsg = 'Error ' + xhr.status + ': ';
                if (xhr.responseText) {
                    errorMsg += xhr.responseText.substring(0, 500);
                } else {
                    errorMsg += error || 'Error desconocido';
                }
                alert('Ocurrió un error inesperado.\n' + errorMsg);
            },
            complete: function() {
                if (showSpinner) ocultarLoading();
                btnConsultar.prop('disabled', false);
            }
        });
    }

    /**
     * Descargar el resumen por sucursal en formato Excel
     */
    function descargarExcelIVA() {
        enviarDescargaIVA('Controller/exportarExcelIVA.php');
    }

    /**
     * Descargar el detalle de comprobantes en formato Excel
     */
    function descargarDetalleIVA() {
        enviarDescargaIVA('Controller/exportarDetalleIVA.php');
    }

    /**
     * Envía el rango de fechas por POST al controlador de exportación indicado
     */
    function enviarDescargaIVA(action) {
        const fechaDesde = $('#fecha-desde-iva').val();
        const fechaHasta = $('#fecha-hasta-iva').val();

        if (!fechaDesde || !fechaHasta) {
            alert('Por favor, seleccione un rango de fechas.');
            return;
        }

        const form = $('<form>', {
            method: 'POST',
            action: action
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

    /**
     * Abrir modal con detalle de comprobantes de una sucursal
     */
    function abrirModalDetalleIVA(nroSucursal, codSucursal, totalComprobantes) {
        // Resetear tarjetas del modal al estado de carga
        $('#dv-m-sucursal-iva').text('Suc. ' + nroSucursal);
        $('#dv-m-cod-sucursal-iva').text(codSucursal || '—');
        $('#dv-m-comprobantes-iva').text((totalComprobantes || 0) + ' comprobante' + (String(totalComprobantes) === '1' ? '' : 's'));
        $('#dv-m-central-iva').text('—').css('color', '');
        $('#dv-m-local-iva').text('—').css('color', '');
        $('#dv-m-diferencia-iva').text('—').css('color', '');
        $('#dv-m-estado-badge-iva').html('<span style="color:#64748b;font-size:.75rem;">Cargando…</span>');
        $('#modalDetalleIVA .dv-card-diferencia').removeClass('dv-card-diff-bad');

        $('#tbody-detalle-iva-comprobantes').empty();
        $('#tfoot-detalle-iva').hide();
        $('#dv-loading-iva').show();
        $('#dv-tabla-container-iva').hide();

        // Abrir el modal
        const modal = new bootstrap.Modal(document.getElementById('modalDetalleIVA'));
        modal.show();

        // Cargar datos del detalle
        $.ajax({
            url: 'Controller/diferenciaIVAController.php',
            type: 'POST',
            dataType: 'text',
            data: {
                action: 'obtener_detalle',
                nro_sucursal: nroSucursal
            },
            success: function(responseText) {
                try {
                    const response = JSON.parse(responseText);
                    if (response.success) {
                        renderizarDetalleIVA(response.data);
                    } else {
                        alert('Error: ' + response.message);
                        modal.hide();
                    }
                } catch (e) {
                    console.error("Error al parsear JSON:", e);
                    console.error("Respuesta del servidor:", responseText);
                    alert('Error al cargar el detalle de comprobantes.');
                    modal.hide();
                }
            },
            error: function(xhr) {
                console.error("Error en AJAX:", xhr.responseText);
                alert('Error al cargar el detalle de comprobantes.');
                modal.hide();
            },
            complete: function() {
                $('#dv-loading-iva').hide();
                $('#dv-tabla-container-iva').show();
            }
        });
    }

    /**
     * Renderizar tabla de detalle de comprobantes en el modal (estilo Precision Analytics)
     */
    function renderizarDetalleIVA(data) {
        const tbody = $('#tbody-detalle-iva-comprobantes');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html(`
                <tr class="dv-empty-row">
                    <td colspan="6">
                        <div class="dv-empty-state">
                            <i class="bi bi-inbox dv-empty-icon"></i>
                            <p>No se encontraron comprobantes con diferencias</p>
                        </div>
                    </td>
                </tr>`);
            $('#dv-table-count-iva').text('0 registros');
            $('#tfoot-detalle-iva').hide();
            return;
        }

        let totLocal = 0, totCentral = 0, totDif = 0;

        data.forEach(function(comp) {
            const impL = parseFloat(comp.IVA_LOCAL) || 0;
            const impC = parseFloat(comp.IVA_CENTRAL) || 0;
            const dif  = parseFloat(comp.DIFERENCIA) || 0;

            totLocal   += impL;
            totCentral += impC;
            totDif     += dif;

            let difClass = 'dv-dif-cero';
            if (dif > 0.005)  difClass = 'dv-dif-positiva';
            if (dif < -0.005) difClass = 'dv-dif-negativa';

            // Formatear fecha
            let fecha = '—';
            if (comp.FECHA_COMPROBANTE) {
                if (comp.FECHA_COMPROBANTE.date) {
                    fecha = formatFechaCorta(comp.FECHA_COMPROBANTE.date.substring(0, 10));
                } else if (typeof comp.FECHA_COMPROBANTE === 'string') {
                    fecha = formatFechaCorta(comp.FECHA_COMPROBANTE.substring(0, 10));
                }
            }

            tbody.append(`
                <tr>
                    <td><span class="dv-badge-tcomp">${comp.TIPO_COMPROBANTE || '—'}</span></td>
                    <td class="dv-fecha-cell">${comp.NRO_COMPROBANTE || '—'}</td>
                    <td class="dv-fecha-cell">${fecha}</td>
                    <td class="dv-imp-cell">${formatCurrency(impL)}</td>
                    <td class="dv-imp-cell">${formatCurrency(impC)}</td>
                    <td class="dv-dif-cell ${difClass}">${formatCurrency(dif)}</td>
                </tr>`);
        });

        // Totales en footer
        $('#dv-tot-local-iva').text(formatCurrency(totLocal));
        $('#dv-tot-central-iva').text(formatCurrency(totCentral));
        const totDifEl = $('#dv-tot-diferencia-iva');
        totDifEl.text(formatCurrency(totDif));
        totDifEl.css('color', Math.abs(totDif) < 0.01 ? '#15803d' : '#dc2626');
        $('#tfoot-detalle-iva').show();

        $('#dv-table-count-iva').text(data.length + ' registro' + (data.length !== 1 ? 's' : ''));

        // Actualizar tarjetas con los valores reales
        $('#dv-m-central-iva').text(formatCurrency(totCentral));
        $('#dv-m-local-iva').text(formatCurrency(totLocal));
        $('#dv-m-diferencia-iva').text(formatCurrency(totDif));
        const cardDif = $('#modalDetalleIVA .dv-card-diferencia');
        if (Math.abs(totDif) < 0.01) {
            $('#dv-m-diferencia-iva').css('color', '#15803d');
            $('#dv-m-estado-badge-iva').html('<span style="color:#15803d;font-size:.75rem;">✔ Sin diferencias</span>');
            cardDif.removeClass('dv-card-diff-bad');
        } else {
            $('#dv-m-diferencia-iva').css('color', '#dc2626');
            $('#dv-m-estado-badge-iva').html('<span style="color:#dc2626;font-size:.75rem;">⚠ Con diferencias</span>');
            cardDif.addClass('dv-card-diff-bad');
        }
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
        } catch (e) { return fechaStr; }
    }

    /**
     * Exportar detalle a Excel
     */
    function exportarDetalleExcel() {
        const nroSucursal = ($('#dv-m-sucursal-iva').text() || '').replace('Suc. ', '').trim();
        const codSucursal = ($('#dv-m-cod-sucursal-iva').text() || '').trim();

        $("#tabla-detalle-iva-comprobantes").table2excel({
            exclude: "",
            name: "Detalle IVA",
            filename: `Detalle_IVA_Sucursal_${nroSucursal}_${codSucursal}`,
            fileext: ".xls",
            exclude_img: true,
            exclude_links: true,
            exclude_inputs: true
        });
    }
});
