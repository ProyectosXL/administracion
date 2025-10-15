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

    function ejecutarSPMasivoYRecargarTabla() {
        const fechaDesde = $('#fecha-desde').val();
        const fechaHasta = $('#fecha-hasta').val();

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
        });
    }

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
                : `<span class="badge badge-danger">${fila.ESTADO}</span>`;
            
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
                    console.group("--- Debug Info ---");
                    console.log("Database:", response.debug_info.database);
                    console.log("SQL:", response.debug_info.sql);
                    if(response.debug_info.sp_return) console.log("SP Return:", response.debug_info.sp_return);
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

// --- INICIO DE FUNCIÓN NUEVA ---
/**
 * Llama al backend para cambiar el entorno en la sesión y recarga la página.
 * @param {HTMLSelectElement} selectElement - El elemento <select> que disparó el evento.
 */
function cambiarEntorno(selectElement) {
    // Muestra un indicador de carga para que el usuario sepa que algo está pasando
    $('#loading-spinner').show();

    // El valor 0 es para Argentina ('central'), 1 para Uruguay ('suc_uy')
    const entorno = (selectElement.value === "ARG") ? 0 : 1;
    
    $.ajax({
        url: 'Controller/cambiarentorno.php', // Reutilizamos un controlador que ya existe para esta tarea
        method: 'POST',
        data: { entorno: entorno },
        success: function (data) {
            // Si la sesión se cambió correctamente, recargamos la página
            location.reload();
        },
        error: function(xhr, status, error) {
            console.error('Error al cambiar entorno:', error);
            $('#loading-spinner').hide(); // Ocultamos el spinner si hay un error
            alert('Error al cambiar el país. Por favor, intente nuevamente.');
        }
    });
}
// --- FIN DE FUNCIÓN NUEVA ---