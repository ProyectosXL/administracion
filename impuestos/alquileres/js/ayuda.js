/**
 * JavaScript para Modal de Ayuda Contextual
 * Sistema de Gestión de Alquileres
 */

// Inicialización al cargar el documento
$(document).ready(function() {
    initializeAyuda();
});

/**
 * Inicializa la funcionalidad del sistema de ayuda
 */
function initializeAyuda() {
    // Verificar si el modal ya existe, si no, agregarlo
    if ($('#modalAyuda').length === 0) {
        console.warn('El modal de ayuda no está incluido en la página');
        return;
    }

    // Crear botón flotante de ayuda
    crearBotonAyudaFlotante();

    // Event listeners
    attachEventListeners();

    // Inicializar tooltips dentro del modal
    initializeTooltips();

    // Guardar preferencias del usuario
    cargarPreferenciasAyuda();
}

/**
 * Crea el botón flotante de ayuda
 */
function crearBotonAyudaFlotante() {
    // Verificar si ya existe
    if ($('#btnAyudaFlotante').length > 0) {
        return;
    }

    // Crear botón
    const btnAyuda = $(`
        <button id="btnAyudaFlotante" 
                type="button" 
                data-toggle="tooltip" 
                data-placement="left" 
                title="¿Necesitás ayuda? Hacé clic aquí">
            <i class="fas fa-question"></i>
        </button>
    `);

    // Agregar al body
    $('body').append(btnAyuda);

    // Inicializar tooltip
    btnAyuda.tooltip();

    // Event listener para abrir modal
    btnAyuda.on('click', function() {
        abrirModalAyuda();
    });
}

/**
 * Abre el modal de ayuda
 * @param {string} pestana - ID de la pestaña a abrir (opcional)
 */
function abrirModalAyuda(pestana = 'flujo') {
    $('#modalAyuda').modal('show');

    // Activar la pestaña específica
    setTimeout(() => {
        $(`#${pestana}-tab`).tab('show');
    }, 100);

    // Tracking (opcional - para analytics)
    trackAyudaAbierta(pestana);
}

/**
 * Cierra el modal de ayuda
 */
function cerrarModalAyuda() {
    $('#modalAyuda').modal('hide');
}

/**
 * Adjunta event listeners a elementos del modal
 */
function attachEventListeners() {
    // Cuando se muestra el modal
    $('#modalAyuda').on('shown.bs.modal', function () {
        // Focus en el primer elemento
        $(this).find('.nav-link.active').focus();
    });

    // Cuando se oculta el modal
    $('#modalAyuda').on('hidden.bs.modal', function () {
        // Guardar la última pestaña vista
        const pestanaActiva = $('.nav-ayuda .nav-link.active').attr('aria-controls');
        guardarPreferencia('ultimaPestana', pestanaActiva);
    });

    // Tracking de pestañas
    $('.nav-ayuda .nav-link').on('shown.bs.tab', function (e) {
        const pestanaId = $(e.target).attr('aria-controls');
        trackPestanaVista(pestanaId);
    });

    // Expandir/colapsar accordions con animación suave
    $('.collapse').on('show.bs.collapse', function () {
        $(this).closest('.ayuda-card').addClass('card-expandida');
    });

    $('.collapse').on('hide.bs.collapse', function () {
        $(this).closest('.ayuda-card').removeClass('card-expandida');
    });

    // Click en botones de ejemplo (no hacen nada, son ilustrativos)
    $('.btn-example').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
}

/**
 * Inicializa tooltips dentro del modal
 */
function initializeTooltips() {
    $('#modalAyuda [data-toggle="tooltip"]').tooltip();
}

/**
 * Guarda una preferencia del usuario en localStorage
 * @param {string} key - Clave de la preferencia
 * @param {any} value - Valor a guardar
 */
function guardarPreferencia(key, value) {
    try {
        localStorage.setItem(`ayudaAlquileres_${key}`, JSON.stringify(value));
    } catch (e) {
        console.warn('No se pudo guardar la preferencia:', e);
    }
}

/**
 * Obtiene una preferencia del usuario desde localStorage
 * @param {string} key - Clave de la preferencia
 * @param {any} defaultValue - Valor por defecto si no existe
 * @returns {any}
 */
function obtenerPreferencia(key, defaultValue = null) {
    try {
        const value = localStorage.getItem(`ayudaAlquileres_${key}`);
        return value ? JSON.parse(value) : defaultValue;
    } catch (e) {
        console.warn('No se pudo obtener la preferencia:', e);
        return defaultValue;
    }
}

/**
 * Carga las preferencias guardadas del usuario
 */
function cargarPreferenciasAyuda() {
    // Cargar última pestaña vista
    const ultimaPestana = obtenerPreferencia('ultimaPestana', 'flujo');
    
    // Aplicar al abrir el modal
    $('#modalAyuda').on('show.bs.modal', function (e) {
        // Solo si es la primera vez que se abre en esta sesión
        if (!$(this).data('pestana-cargada')) {
            setTimeout(() => {
                $(`#${ultimaPestana}-tab`).tab('show');
                $(this).data('pestana-cargada', true);
            }, 100);
        }
    });
}

/**
 * Búsqueda dentro del contenido de ayuda
 * @param {string} termino - Término a buscar
 */
function buscarEnAyuda(termino) {
    if (!termino || termino.trim().length < 3) {
        limpiarBusqueda();
        return;
    }

    const terminoNormalizado = termino.toLowerCase();
    let resultados = 0;

    // Buscar en todos los cards
    $('.ayuda-card, .ejemplo-card').each(function() {
        const textoCard = $(this).text().toLowerCase();
        
        if (textoCard.includes(terminoNormalizado)) {
            $(this).show().addClass('resultado-busqueda');
            resultados++;
        } else {
            $(this).hide().removeClass('resultado-busqueda');
        }
    });

    // Mostrar mensaje si no hay resultados
    if (resultados === 0) {
        mostrarMensajeSinResultados();
    }
}

/**
 * Limpia los resultados de búsqueda
 */
function limpiarBusqueda() {
    $('.ayuda-card, .ejemplo-card').show().removeClass('resultado-busqueda');
    $('.mensaje-sin-resultados').remove();
}

/**
 * Muestra mensaje cuando no hay resultados
 */
function mostrarMensajeSinResultados() {
    if ($('.mensaje-sin-resultados').length > 0) return;

    const mensaje = $(`
        <div class="alert alert-info mensaje-sin-resultados" role="alert">
            <i class="fas fa-search"></i> No se encontraron resultados para tu búsqueda.
        </div>
    `);

    $('.tab-pane.active').prepend(mensaje);
}

/**
 * Expande todas las secciones de la pestaña activa
 */
function expandirTodo() {
    const pestanaActiva = $('.tab-pane.active');
    pestanaActiva.find('.collapse').collapse('show');
}

/**
 * Colapsa todas las secciones de la pestaña activa
 */
function colapsarTodo() {
    const pestanaActiva = $('.tab-pane.active');
    pestanaActiva.find('.collapse').collapse('hide');
}

/**
 * Imprime el contenido de ayuda
 */
function imprimirAyuda() {
    const contenido = $('#modalAyuda .modal-body').html();
    const ventanaImpresion = window.open('', '_blank');
    
    ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ayuda - Gestión de Alquileres</title>
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
            <link rel="stylesheet" href="css/ayuda.css">
            <style>
                @media print {
                    .nav-tabs { display: none; }
                    .tab-content { display: block !important; }
                    .tab-pane { display: block !important; opacity: 1 !important; }
                    .collapse { display: block !important; height: auto !important; }
                }
            </style>
        </head>
        <body>
            <div class="container mt-4">
                <h1>Centro de Ayuda - Gestión de Alquileres</h1>
                <hr>
                ${contenido}
            </div>
        </body>
        </html>
    `);
    
    setTimeout(() => {
        ventanaImpresion.print();
    }, 500);
}

/**
 * Navega a una sección específica de ayuda
 * @param {string} pestana - ID de la pestaña
 * @param {string} seccion - ID de la sección (collapse)
 */
function navegarASeccion(pestana, seccion) {
    // Abrir modal
    $('#modalAyuda').modal('show');

    // Esperar a que el modal esté completamente visible
    $('#modalAyuda').on('shown.bs.modal', function (e) {
        // Cambiar a la pestaña
        $(`#${pestana}-tab`).tab('show');

        // Esperar animación de pestaña
        setTimeout(() => {
            // Expandir la sección
            $(`#${seccion}`).collapse('show');

            // Scroll suave a la sección
            setTimeout(() => {
                const elemento = $(`#${seccion}`);
                if (elemento.length) {
                    $('.modal-body').animate({
                        scrollTop: elemento.offset().top - $('.modal-body').offset().top + $('.modal-body').scrollTop() - 20
                    }, 500);
                }
            }, 350);
        }, 300);
    });
}

/**
 * Tracking de eventos (opcional - para analytics)
 */
function trackAyudaAbierta(pestana) {
    // Implementar según tu sistema de analytics
    // Ejemplo: Google Analytics, Mixpanel, etc.
    console.log('Ayuda abierta - Pestaña:', pestana);
}

function trackPestanaVista(pestanaId) {
    console.log('Pestaña vista:', pestanaId);
}

/**
 * Funciones auxiliares para acceso rápido desde consola o botones
 */
window.ayuda = {
    abrir: abrirModalAyuda,
    cerrar: cerrarModalAyuda,
    buscar: buscarEnAyuda,
    expandirTodo: expandirTodo,
    colapsarTodo: colapsarTodo,
    imprimir: imprimirAyuda,
    irA: navegarASeccion
};

/**
 * Atajos de teclado
 */
$(document).on('keydown', function(e) {
    // F1 para abrir ayuda
    if (e.key === 'F1') {
        e.preventDefault();
        abrirModalAyuda();
    }

    // Escape para cerrar (si el modal está abierto)
    if (e.key === 'Escape' && $('#modalAyuda').hasClass('show')) {
        cerrarModalAyuda();
    }
});

/**
 * Ayuda contextual según la acción del usuario
 * Detecta qué botón se está usando y sugiere ayuda relevante
 */
function detectarContextoYSugerirAyuda() {
    // Cuando hace hover en "Aplicar Ajuste"
    $('button:contains("Aplicar Ajuste")').on('mouseenter', function() {
        const tooltipExistente = $(this).attr('data-original-title');
        if (!tooltipExistente || !tooltipExistente.includes('ayuda')) {
            $(this).attr('data-original-title', 
                $(this).attr('data-original-title'));
        }
    });

    // Similar para otros botones...
}

// Inicializar ayuda contextual
$(document).ready(function() {
    detectarContextoYSugerirAyuda();
});
