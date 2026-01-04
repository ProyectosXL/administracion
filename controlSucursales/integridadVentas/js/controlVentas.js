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
                    <td>
                        <button class="integridadVentas_btn-actualizar btn-actualizar-sucursal" data-sucursal="${fila.NUM_SUC}" title="Actualizar esta sucursal">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
                        </button>
                    </td>
                </tr>
            `;
            tablaResultados.append(filaHtml);
        });

        actualizarTotales(totalImporteCentral, totalImporteLocal, totalDiferencia);
    }

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
