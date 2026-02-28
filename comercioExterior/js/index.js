/**
 * index.js - Script principal para el sistema de comercio exterior
 * Maneja el toggle de entorno (Argentina/Uruguay), navegación de pestañas y sidebar contraíble
 */

$(document).ready(function() {
    console.log('Sistema de Comercio Exterior iniciado');
    
    // Debug inicial
    console.log('=== DEBUG INICIAL ===');
    console.log('Sidebar encontrado:', $('#sidebar').length);
    console.log('Main content encontrado:', $('.main-content').length);
    console.log('Tab content encontrado:', $('#mainTabsContent').length);
    console.log('Tab panes encontrados:', $('.tab-pane').length);
    console.log('Tab pane activo:', $('.tab-pane.active').length);
    console.log('Iframes encontrados:', $('.content-iframe').length);
    console.log('===================');

    /**
     * MANEJO DEL SIDEBAR CONTRAÍBLE
     */
    $('#sidebarToggle').on('click', function() {
        const sidebar = $('#sidebar');
        sidebar.toggleClass('collapsed');
        
        // Guardar preferencia en localStorage
        const isCollapsed = sidebar.hasClass('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
        
        // Actualizar ícono del botón
        const icon = $(this).find('i');
        if (isCollapsed) {
            icon.removeClass('bi-list').addClass('bi-chevron-right');
        } else {
            icon.removeClass('bi-chevron-right').addClass('bi-list');
        }
    });

    // Restaurar estado del sidebar desde localStorage
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed) {
        $('#sidebar').addClass('collapsed');
        $('#sidebarToggle i').removeClass('bi-list').addClass('bi-chevron-right');
    }

    // En móvil, hacer que el sidebar se expanda/colapse con un toque
    if (window.innerWidth <= 768) {
        $('#sidebar').on('mouseenter', function() {
            $(this).addClass('expanded');
        }).on('mouseleave', function() {
            $(this).removeClass('expanded');
        });
    }

    /**
     * MANEJO DEL TOGGLE DE ENTORNO (Argentina/Uruguay)
     */
    $('#country-toggle').on('change', function() {
        const isChecked = $(this).is(':checked');
        const entorno = isChecked ? 'uy' : 'central';
        const entornoNombre = isChecked ? 'Uruguay (UY)' : 'Argentina (ARG)';
        const entornoCorto = isChecked ? 'UY' : 'ARG';

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
                    console.log('Entorno cambiado exitosamente a:', response.entorno);
                    
                    // Actualizar texto del entorno
                    $('#environment-text').html(`<strong>${entornoCorto}</strong>`);

                    // Intercambiar las banderas
                    const flagLeft = $('#flag-left');
                    const flagRight = $('#flag-right');
                    
                    const tempSrc = flagLeft.attr('src');
                    const tempAlt = flagLeft.attr('alt');
                    
                    flagLeft.attr('src', flagRight.attr('src'));
                    flagLeft.attr('alt', flagRight.attr('alt'));
                    flagRight.attr('src', tempSrc);
                    flagRight.attr('alt', tempAlt);

                    // Agregar pequeño delay para asegurar que la sesión se ha escrito
                    setTimeout(function() {
                        // Recargar iframes con el parámetro de entorno
                        reloadAllIframes(entorno);
                        
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
                    }, 300); // 300ms de delay para asegurar escritura de sesión
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
    
    // Inicializar tabs de Bootstrap
    const triggerTabList = document.querySelectorAll('button[data-bs-toggle="tab"]');
    triggerTabList.forEach(triggerEl => {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', event => {
            event.preventDefault();
            tabTrigger.show();
        });
    });
    
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).data('bs-target');
        console.log('Pestaña cambiada a:', target);

        // Recargar el iframe de la pestaña activa si es necesario
        const iframe = $(target).find('iframe');
        if (iframe.length && !iframe.data('loaded')) {
            iframe.data('loaded', true);
        }
    });
    
    // Debug: Verificar que las tabs estén correctas
    console.log('Tabs encontradas:', triggerTabList.length);
    console.log('Tab activa inicial:', document.querySelector('.tab-pane.active')?.id);

    // Activar pestaña según hash de la URL (ej: index.php#costos)
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        setTimeout(function() {
            const tabBtn = document.querySelector(`button[data-bs-target="#${hash}"]`);
            if (tabBtn) {
                bootstrap.Tab.getOrCreateInstance(tabBtn).show();
            }
        }, 150);
    }

    /**
     * Recargar todos los iframes
     */
    function reloadAllIframes(entorno) {
        console.log('Recargando iframes con entorno:', entorno);
        $('.content-iframe').each(function() {
            const src = $(this).attr('src');
            // Limpiar cualquier parámetro previo
            const baseSrc = src.split('?')[0].split('&')[0];
            // Agregar entorno y timestamp para forzar recarga
            const newSrc = baseSrc + '?entorno=' + (entorno || 'central') + '&_t=' + new Date().getTime();
            console.log('Recargando iframe:', baseSrc, '→', newSrc);
            $(this).attr('src', newSrc);
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
     * INICIALIZAR TOOLTIPS DE BOOTSTRAP
     */
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover'
        });
    });

    /**
     * ATAJOS DE TECLADO
     */
    $(document).on('keydown', function(e) {
        // Alt + 1, 2, 3, 4, 5 para cambiar de pestaña
        if (e.altKey) {
            switch(e.key) {
                case '1':
                    e.preventDefault();
                    $('#gestion-tab').tab('show');
                    break;
                case '2':
                    e.preventDefault();
                    $('#pci-tab').tab('show');
                    break;
                case '3':
                    e.preventDefault();
                    $('#cronograma-tab').tab('show');
                    break;
                case '4':
                    e.preventDefault();
                    $('#costos-tab').tab('show');
                    break;
                case '5':
                    e.preventDefault();
                    $('#dashboard-tab').tab('show');
                    break;
            }
        }
        
        // Ctrl + B para toggle del sidebar
        if (e.ctrlKey && e.key === 'b') {
            e.preventDefault();
            $('#sidebarToggle').click();
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
    console.log('Alt + 1: Gestión de Despachos');
    console.log('Alt + 2: Proyección de Costos');
    console.log('Alt + 3: Cronograma de Despachos');
    console.log('Alt + 4: Costos de Nacionalización');
    console.log('Alt + 5: Dashboard');
    console.log('Ctrl + B: Toggle Sidebar');
});
