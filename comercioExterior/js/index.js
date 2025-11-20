/**
 * index.js - Script principal para el sistema de comercio exterior
 * Maneja el toggle de entorno (Argentina/Uruguay) y la navegación de pestañas
 */

$(document).ready(function() {
    console.log('Sistema de Comercio Exterior iniciado');

    /**
     * MANEJO DEL TOGGLE DE ENTORNO (Argentina/Uruguay)
     */
    $('#country-toggle').on('change', function() {
        const isChecked = $(this).is(':checked');
        const entorno = isChecked ? 'uy' : 'central';
        const entornoNombre = isChecked ? 'Uruguay (UY)' : 'Argentina (ARG)';

        // Mostrar loading
        Swal.fire({
            title: 'Cambiando entorno...',
            text: `Cambiando a ${entornoNombre}`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        // Realizar cambio de entorno
        $.ajax({
            url: 'controller/cambiarEntorno.php',
            type: 'POST',
            data: {
                entorno: entorno
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Actualizar texto del entorno
                    $('#environment-text').html(`Entorno: <strong>${entornoNombre}</strong>`);

                    // Intercambiar las banderas
                    const flagLeft = $('#flag-left');
                    const flagRight = $('#flag-right');
                    
                    const tempSrc = flagLeft.attr('src');
                    const tempAlt = flagLeft.attr('alt');
                    
                    flagLeft.attr('src', flagRight.attr('src'));
                    flagLeft.attr('alt', flagRight.attr('alt'));
                    flagRight.attr('src', tempSrc);
                    flagRight.attr('alt', tempAlt);

                    // Recargar iframes para actualizar el contenido
                    reloadAllIframes();

                    // Cerrar loading y mostrar éxito
                    Swal.fire({
                        title: '¡Entorno cambiado!',
                        text: `Ahora estás trabajando en ${entornoNombre}`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    throw new Error(response.message || 'Error al cambiar entorno');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cambiar entorno:', error);
                
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo cambiar el entorno. Por favor, intenta nuevamente.',
                    icon: 'error',
                    confirmButtonColor: '#dc2626'
                });

                // Revertir el toggle
                $('#country-toggle').prop('checked', !isChecked);
            }
        });
    });

    /**
     * MANEJO DE PESTAÑAS
     */
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).data('bs-target');
        console.log('Pestaña cambiada a:', target);

        // Recargar el iframe de la pestaña activa si es necesario
        const iframe = $(target).find('iframe');
        if (iframe.length && !iframe.data('loaded')) {
            iframe.data('loaded', true);
        }
    });

    /**
     * Recargar todos los iframes
     */
    function reloadAllIframes() {
        $('.content-iframe').each(function() {
            const src = $(this).attr('src');
            $(this).attr('src', src);
        });
    }

    /**
     * MANEJO DE ERRORES EN IFRAMES
     */
    $('.content-iframe').on('error', function() {
        console.error('Error cargando iframe:', $(this).attr('src'));
        
        $(this).parent().html(
            '<div class="alert alert-danger m-4" role="alert">' +
            '<i class="bi bi-exclamation-triangle"></i> ' +
            'Error al cargar el contenido. Por favor, recarga la página.' +
            '</div>'
        );
    });

    /**
     * OPTIMIZACIÓN: Lazy loading de iframes
     * Solo cargar el contenido cuando se activa la pestaña
     */
    $('button[data-bs-toggle="tab"]').on('show.bs.tab', function (e) {
        const target = $(e.target).data('bs-target');
        const iframe = $(target).find('iframe');
        
        if (iframe.length && !iframe.attr('src')) {
            const dataSrc = iframe.attr('data-src');
            if (dataSrc) {
                iframe.attr('src', dataSrc);
            }
        }
    });

    /**
     * ATAJOS DE TECLADO
     */
    $(document).on('keydown', function(e) {
        // Alt + 1, 2, 3 para cambiar de pestaña
        if (e.altKey) {
            switch(e.key) {
                case '1':
                    e.preventDefault();
                    $('#costos-tab').tab('show');
                    break;
                case '2':
                    e.preventDefault();
                    $('#dashboard-tab').tab('show');
                    break;
                case '3':
                    e.preventDefault();
                    $('#carga-tab').tab('show');
                    break;
            }
        }
    });

    /**
     * INDICADOR DE LOADING PARA IFRAMES
     */
    $('.content-iframe').on('load', function() {
        $(this).addClass('loaded');
    });

    // Mostrar tooltip con atajos de teclado
    console.log('%cAtajos de teclado disponibles:', 'font-weight: bold; color: #7066e0');
    console.log('Alt + 1: Costos de Nacionalización');
    console.log('Alt + 2: Dashboard');
    console.log('Alt + 3: Nuevo Despacho');
});
