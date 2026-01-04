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

    // Delegación de evento para botones de actualizar por sucursal
    $('#resultado-consulta-iva').on('click', '.btn-actualizar-sucursal-iva', function() {
        const nroSucursal = $(this).data('sucursal');
        ejecutarSPPorSucursalIVA(nroSucursal);
    });

    // Delegación de evento para ver detalle de comprobantes (click en la fila)
    $('#resultado-consulta-iva').on('click', 'tbody tr', function(e) {
        // No abrir modal si se hizo click en el botón de actualizar
        if ($(e.target).closest('.btn-actualizar-sucursal-iva').length > 0) {
            return;
        }
        
        const nroSucursal = $(this).find('td:first').text();
        const codSucursal = $(this).find('td:eq(1)').text();
        const totalComprobantes = $(this).find('td:eq(2)').text();
        
        if (nroSucursal && nroSucursal !== 'Haga clic') {
            abrirModalDetalleIVA(nroSucursal, codSucursal, totalComprobantes);
        }
    });

    // Exportar detalle a Excel desde el modal
    $('#btn-exportar-detalle-iva').on('click', function() {
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
                    <td>
                        <button class="integridadVentas_btn-actualizar btn-actualizar-sucursal-iva" data-sucursal="${fila.NUM_SUC}" title="Actualizar esta sucursal">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
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
     * Descargar datos en formato Excel
     */
    function descargarExcelIVA() {
        const fechaDesde = $('#fecha-desde-iva').val();
        const fechaHasta = $('#fecha-hasta-iva').val();

        if (!fechaDesde || !fechaHasta) {
            alert('Por favor, seleccione un rango de fechas.');
            return;
        }

        const form = $('<form>', {
            method: 'POST',
            action: 'Controller/exportarExcelIVA.php'
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
        // Actualizar información del header del modal
        $('#modal-nro-sucursal').text(nroSucursal);
        $('#modal-nombre-sucursal').text(codSucursal);
        $('#modal-total-comprobantes').text(totalComprobantes);

        // Mostrar spinner y ocultar tabla
        $('#modal-loading').show();
        $('#modal-tabla-container').hide();

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
                        renderizarDetalleComprobantes(response.data);
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
                $('#modal-loading').hide();
                $('#modal-tabla-container').show();
            }
        });
    }

    /**
     * Renderizar tabla de detalle de comprobantes en el modal
     */
    function renderizarDetalleComprobantes(data) {
        const tbody = $('#tbody-detalle-comprobantes-iva');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center text-muted">No se encontraron comprobantes con diferencias.</td></tr>');
            actualizarTotalesModal(0, 0, 0);
            return;
        }

        let totalIvaLocal = 0;
        let totalIvaCentral = 0;
        let totalDiferencia = 0;

        data.forEach(function(comp) {
            totalIvaLocal += parseFloat(comp.IVA_LOCAL) || 0;
            totalIvaCentral += parseFloat(comp.IVA_CENTRAL) || 0;
            totalDiferencia += parseFloat(comp.DIFERENCIA) || 0;

            // Formatear fecha
            let fechaFormateada = 'N/A';
            if (comp.FECHA_COMPROBANTE) {
                if (comp.FECHA_COMPROBANTE.date) {
                    fechaFormateada = comp.FECHA_COMPROBANTE.date.substring(0, 10);
                } else if (typeof comp.FECHA_COMPROBANTE === 'string') {
                    fechaFormateada = comp.FECHA_COMPROBANTE.substring(0, 10);
                }
            }

            const filaHtml = `
                <tr>
                    <td>${comp.NRO_SUCURSAL}</td>
                    <td>${comp.TIPO_COMPROBANTE || 'N/A'}</td>
                    <td>${comp.NRO_COMPROBANTE || 'N/A'}</td>
                    <td>${fechaFormateada}</td>
                    <td>${formatCurrency(comp.IVA_LOCAL)}</td>
                    <td>${formatCurrency(comp.IVA_CENTRAL)}</td>
                    <td>${formatCurrency(comp.DIFERENCIA)}</td>
                </tr>
            `;
            tbody.append(filaHtml);
        });

        actualizarTotalesModal(totalIvaLocal, totalIvaCentral, totalDiferencia);
    }

    /**
     * Actualizar totales en el footer del modal
     */
    function actualizarTotalesModal(totalLocal, totalCentral, totalDif) {
        $('#modal-total-iva-local').text(formatCurrency(totalLocal));
        $('#modal-total-iva-central').text(formatCurrency(totalCentral));
        $('#modal-total-diferencia').text(formatCurrency(totalDif));
    }

    /**
     * Exportar detalle a Excel
     */
    function exportarDetalleExcel() {
        const nroSucursal = $('#modal-nro-sucursal').text();
        const codSucursal = $('#modal-nombre-sucursal').text();
        
        $("#tabla-detalle-comprobantes-iva").table2excel({
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
