$(document).ready(function() {
    const spinner = $('#loading-spinner');
    const tablaResultados = $('#tabla-resultados');
    const btnConsultar = $('#btn-consultar');

    // --- EVENT LISTENERS ---

    btnConsultar.on('click', function() {
        ejecutarSPMasivoYRecargarTabla();
    });

    $('#resultado-consulta').on('click', '.btn-actualizar-sucursal', function() {
        const nroSucursal = $(this).data('sucursal');
        ejecutarSPPorSucursal(nroSucursal);
    });

    // --- FUNCTIONS ---

    /**
     * Llama al SP masivo y, si tiene éxito, llama a la función que carga la tabla.
     */
    function ejecutarSPMasivoYRecargarTabla() {
        const fechaDesde = $('#fecha-desde').val();
        const fechaHasta = $('#fecha-hasta').val();

        if (!fechaDesde || !fechaHasta) {
            alert('Por favor, seleccione un rango de fechas.');
            return;
        }

        // Paso 1: Ejecutar el SP masivo.
        realizarAjax('Controller/controlVentasController.php', { 
            action: 'ejecutar_masivo', 
            desde: fechaDesde, 
            hasta: fechaHasta 
        }, function(response) {
            // Paso 2: Si el SP se ejecutó bien, pedir los datos para la tabla.
            if (response.success) {
                cargarDatosEnTabla();
            } else {
                alert('Error al ejecutar el proceso: ' + response.message);
            }
        });
    }

    /**
     * Ejecuta el SP para una sucursal específica y luego recarga la tabla completa.
     */
    function ejecutarSPPorSucursal(nroSucursal) {
        const fechaDesde = $('#fecha-desde').val();
        const fechaHasta = $('#fecha-hasta').val();

        realizarAjax('Controller/controlVentasController.php', { 
            action: 'ejecutar_sucursal', 
            desde: fechaDesde, 
            hasta: fechaHasta, 
            nro_sucurs: nroSucursal 
        }, function(response) {
            if(response.success){
                cargarDatosEnTabla(); 
            }
        });
    }

    /**
     * Obtiene los datos de la BD y los renderiza en la tabla.
     */
    function cargarDatosEnTabla() {
        const fechaDesde = $('#fecha-desde').val();
        const fechaHasta = $('#fecha-hasta').val();

        realizarAjax('Controller/controlVentasController.php', { 
            action: 'obtener_datos', 
            desde: fechaDesde, 
            hasta: fechaHasta 
        }, function(response) {
            if (response.success) {
                renderizarTabla(response.data);
            } else {
                alert('Error al cargar los datos: ' + response.message);
            }
        });
    }

    function renderizarTabla(data) {
        tablaResultados.empty();
        if (!data || data.length === 0) {
            tablaResultados.html('<tr><td colspan="8" class="text-center">No se encontraron resultados.</td></tr>');
            return;
        }

        $.each(data, function(index, fila) {
            const estadoBadge = fila.ESTADO === 'OK' 
                ? '<span class="badge badge-success">OK</span>' 
                : '<span class="badge badge-danger">Diferencia</span>';
            
            let refreshedDate = 'N/A';
            if(fila.REFRESHED_AT && fila.REFRESHED_AT.date){
                refreshedDate = new Date(fila.REFRESHED_AT.date).toLocaleString();
            }

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
                        <button class="btn btn-info btn-sm btn-actualizar-sucursal" data-sucursal="${fila.NUM_SUC}" title="Actualizar esta sucursal">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
            tablaResultados.append(filaHtml);
        });
    }

    function realizarAjax(url, data, successCallback) {
        spinner.show();
        btnConsultar.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(response) {
                if (response && response.debug_info) {
                    console.group("--- Gemini Debug Info ---");
                    console.log("Database:", response.debug_info.database);
                    console.log("SQL:", response.debug_info.sql);
                    console.groupEnd();
                }

                if (successCallback) successCallback(response);
            },
            error: function(xhr, status, error) {
                console.error("Error en AJAX:", xhr.responseText);
                alert('Ocurrió un error inesperado. Revise la consola para más detalles.');
            },
            complete: function() {
                spinner.hide();
                btnConsultar.prop('disabled', false);
            }
        });
    }

    function formatCurrency(value) {
        const number = parseFloat(value);
        if (isNaN(number)) {
            return '$ 0.00';
        }
        return number.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
    }
});