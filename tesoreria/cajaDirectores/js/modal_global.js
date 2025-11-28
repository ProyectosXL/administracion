/**
 * Funciones globales para modales y alertas
 */

console.log('[MODAL_GLOBAL] Archivo cargado - Version:', new Date().getTime());

// Variable global para el modal actual
let modalActual = null;

/**
 * Función global para mostrar alertas
 */
function mostrarAlerta(titulo, mensaje) {
    console.log('[ALERTA] === FUNCION EJECUTADA === Titulo:', titulo, 'Mensaje:', mensaje);
    
    try {
        console.log('[ALERTA] Dentro del try-catch');
        
        // Limpiar estado de modales anteriores
        console.log('[ALERTA] Llamando a limpiarModalPrevio...');
        limpiarModalPrevio();
        console.log('[ALERTA] limpiarModalPrevio completado');
        
        const modalElement = document.getElementById('modalAlerta');
        console.log('[ALERTA] modalElement:', modalElement);
        
        if (!modalElement) {
            console.error('[ALERTA] Modal de alerta no encontrado');
            // Fallback a alert nativo
            alert(titulo + ': ' + mensaje);
            return;
        }
        
        // Actualizar contenido
        const tituloElement = document.getElementById('modalAlertaTitulo');
        const mensajeElement = document.getElementById('modalAlertaMensaje');
        const modalHeader = modalElement.querySelector('.modal-header');
        
        console.log('[ALERTA] Elementos encontrados:', {
            tituloElement: !!tituloElement,
            mensajeElement: !!mensajeElement,
            modalHeader: !!modalHeader
        });
        
        if (tituloElement) tituloElement.textContent = titulo;
        if (mensajeElement) mensajeElement.innerHTML = mensaje;
        
        // Agregar clase especial si el título es "Éxito"
        if (modalHeader) {
            // Resetear estilos primero
            modalHeader.classList.remove('modal-header-exito');
            modalHeader.style.backgroundColor = '';
            modalHeader.style.borderBottom = '';
            
            if (tituloElement) {
                tituloElement.style.color = '';
                tituloElement.style.fontWeight = '';
            }
            
            console.log('[ALERTA] Verificando titulo:', titulo, 'Es Exito?', titulo === 'Éxito');
            
            if (titulo === 'Éxito') {
                console.log('[ALERTA] APLICANDO ESTILOS DE EXITO');
                
                modalHeader.classList.add('modal-header-exito');
                
                // Aplicar estilos inline directamente
                modalHeader.style.backgroundColor = '#d4edda';
                modalHeader.style.borderBottom = '2px solid #c3e6cb';
                
                if (tituloElement) {
                    tituloElement.style.color = '#155724';
                    tituloElement.style.fontWeight = '600';
                }
                
                console.log('[ALERTA] Estilos aplicados:', {
                    bgColor: modalHeader.style.backgroundColor,
                    border: modalHeader.style.borderBottom,
                    titleColor: tituloElement ? tituloElement.style.color : 'N/A'
                });
            }
        }
        
        // Crear nueva instancia del modal
        console.log('[ALERTA] Creando instancia de modal...');
        modalActual = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        // Event listener para limpiar al cerrar
        modalElement.addEventListener('hidden.bs.modal', function() {
            limpiarModalPrevio();
            modalActual = null;
        }, { once: true });
        
        // Mostrar modal
        console.log('[ALERTA] Mostrando modal...');
        modalActual.show();
        console.log('[ALERTA] === FIN ===');
        
    } catch (error) {
        console.error('[ALERTA] ERROR CAPTURADO:', error);
        console.error('[ALERTA] Stack:', error.stack);
        alert(titulo + ': ' + mensaje);
    }
}

/**
 * Función para limpiar estado de modales previos
 */
function limpiarModalPrevio() {
    // Cerrar modal actual si existe
    if (modalActual) {
        try {
            modalActual.hide();
        } catch (e) {
            console.warn('Error al cerrar modal previo:', e);
        }
        modalActual = null;
    }
    
    // Limpiar backdrops residuales
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Limpiar clases del body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // Limpiar estado de todos los modales
    const modales = document.querySelectorAll('.modal');
    modales.forEach(modal => {
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    });
}

/**
 * Función global para mostrar confirmación
 */
function mostrarConfirmacion(titulo, mensaje, callback) {
    // Limpiar estado de modales anteriores
    limpiarModalPrevio();
    
    const modalElement = document.getElementById('modalConfirmar');
    if (!modalElement) {
        console.error('Modal de confirmación no encontrado');
        // Fallback a confirm nativo
        if (confirm(titulo + ': ' + mensaje)) {
            callback(true);
        } else {
            callback(false);
        }
        return;
    }
    
    // Actualizar contenido
    const tituloElement = document.getElementById('modalConfirmarTitulo');
    const mensajeElement = document.getElementById('modalConfirmarMensaje');
    const botonAceptar = document.getElementById('modalConfirmarAceptar');
    
    if (tituloElement) tituloElement.textContent = titulo;
    if (mensajeElement) mensajeElement.innerHTML = mensaje;
    
    // Crear nueva instancia del modal
    modalActual = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    // Manejar respuesta
    const manejarRespuesta = (resultado) => {
        limpiarModalPrevio();
        modalActual = null;
        callback(resultado);
    };
    
    // Event listeners (usar once para evitar acumulación)
    if (botonAceptar) {
        botonAceptar.addEventListener('click', () => manejarRespuesta(true), { once: true });
    }
    
    modalElement.addEventListener('hidden.bs.modal', () => manejarRespuesta(false), { once: true });
    
    // Mostrar modal
    modalActual.show();
}

// Limpiar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    limpiarModalPrevio();
});