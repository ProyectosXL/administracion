$(document).ready(function() {
    const spinner = $('#loading-overlay');
    const tablaResultados = $('#tabla-resultados');
    const btnConsultar = $('#btn-consultar');

    // --- EVENT LISTENERS ---

    // El botón "Consultar" fuerza una actualización y el usuario espera.
    btnConsultar.on('click', function() {
        ejecutarSPMasivoYRecargarTabla();
    });

    // El botón de actualizar por fila también es una acción manual.
    $('#resultado-consulta').on('click', '.btn-actualizar-sucursal', function() {
        const nroSucursal = $(this).data('sucursal');
        ejecutarSPPorSucursal(nroSucursal);
    });

    // --- LÓGICA DE CARGA INICIAL (OPTIMISTA) ---
    
    // 1. Cargamos los datos existentes inmediatamente para que el usuario vea algo rápido.
    cargarDatosEnTabla(function() {
        // 2. Una vez mostrados los datos, disparamos la actualización en segundo plano.
        actualizarDatosEnBackground();
    });

    // --- FUNCTIONS ---

    /**
     * Flujo para la carga manual (botón Consultar): Muestra el spinner,
     * ejecuta el proceso masivo y al terminar, recarga la tabla.
     */
    function ejecutarSPMasivoYRecargarTabla() {
        const fechaDesde = $('#fecha-desde').val();
        const fechaHasta = $('#fecha-hasta').val();

        if (!fechaDesde || !fechaHasta) {
            alert('Por favor, seleccione un rango de fechas.');
            return;
        }

        // Esta es la llamada que bloquea al usuario, por eso se usa el spinner grande.
        realizarAjax('Controller/controlVentasController.php', { 
            action: 'ejecutar_masivo', 
            desde: fechaDesde, 
            hasta: fechaHasta 
        }, function(response) {
            if (response.success) {
                cargarDatosEnTabla(); // Recargamos la tabla con los datos frescos.
            } else {
                alert('Error al ejecutar el proceso: ' + response.message);
            }
        }, true); // El 'true' indica que debe mostrar el spinner.
    }

    /**
     * Llama al SP para una sucursal específica y recarga la tabla.
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
        }, true); // Muestra spinner para la acción manual.
    }

    /**
     * Obtiene los datos de la BD y los renderiza en la tabla.
     * Acepta un callback opcional para ejecutar después de cargar.
     * @param {function} callback - Función a ejecutar cuando los datos se hayan cargado.
     */
    function cargarDatosEnTabla(callback) {
        const fechaDesde = $('#fecha-desde').val();
        const fechaHasta = $('#fecha-hasta').val();

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
        }, true); // Muestra spinner mientras carga los datos. Es rápido.
    }

    /**
     * NUEVA FUNCIÓN: Ejecuta el proceso masivo en segundo plano SIN bloquear la UI.
     * Cuando termina, actualiza la tabla con los nuevos datos.
     */
    function actualizarDatosEnBackground() {
        const fechaDesde = $('#fecha-desde').val();
        const fechaHasta = $('#fecha-hasta').val();

        console.log("Iniciando actualización de datos en segundo plano...");
        
        // Usamos $.ajax directamente para no mostrar el spinner grande.
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
                    // Una vez terminado el proceso lento, volvemos a cargar los datos en la tabla.
                    // Esta vez no pasamos callback para evitar un bucle infinito.
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

    function renderizarTabla(data) {
        tablaResultados.empty();
        if (!data || data.length === 0) {
            tablaResultados.html('<tr><td colspan="8" class="text-center">No se encontraron resultados para el período seleccionado.</td></tr>');
            return;
        }

        $.each(data, function(index, fila) {
            const estadoBadge = fila.ESTADO === 'OK' 
                ? '<span class="badge badge-success">OK</span>' 
                : `<span class="badge badge-danger">${fila.ESTADO}</span>`;
            
            let refreshedDate = 'N/A';
            if(fila.REFRESHED_AT && fila.REFRESHED_AT.date){
                // Formateamos a un formato más legible y común en Argentina/Uruguay
                refreshedDate = new Date(fila.REFRESHED_AT.date).toLocaleString('es-AR', {
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
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

    /**
     * Wrapper para las llamadas AJAX. Ahora controla si mostrar el spinner o no.
     * @param {string} url - La URL del controlador.
     * @param {object} data - Los datos a enviar.
     * @param {function} successCallback - Función a ejecutar en caso de éxito.
     * @param {boolean} showSpinner - Si es true, muestra el overlay de carga.
     */
    function realizarAjax(url, data, successCallback, showSpinner = false) {
        if (showSpinner) spinner.show();
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
                if (showSpinner) spinner.hide();
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

/**
 * Llama al backend para cambiar el entorno en la sesión y recarga la página.
 * @param {HTMLSelectElement} selectElement - El elemento <select> que disparó el evento.
 */
function cambiarEntorno(selectElement) {
    $('#loading-overlay').show();
    const entorno = (selectElement.value === "ARG") ? 0 : 1;
    
    $.ajax({
        url: 'Controller/cambiarentorno.php',
        method: 'POST',
        data: { entorno: entorno },
        success: function (data) {
            location.reload();
        },
        error: function(xhr, status, error) {
            console.error('Error al cambiar entorno:', error);
            $('#loading-overlay').hide();
            alert('Error al cambiar el país. Por favor, intente nuevamente.');
        }
    });
}