/**
 * Funciones globales para modales y alertas
 */

// Variable global para el modal actual
let modalActual = null;

/**
 * Función global para mostrar alertas
 */
function mostrarAlerta(titulo, mensaje) {
    // Limpiar estado de modales anteriores
    limpiarModalPrevio();
    
    const modalElement = document.getElementById('modalAlerta');
    if (!modalElement) {
        console.error('Modal de alerta no encontrado');
        // Fallback a alert nativo
        alert(titulo + ': ' + mensaje);
        return;
    }
    
    // Actualizar contenido
    const tituloElement = document.getElementById('modalAlertaTitulo');
    const mensajeElement = document.getElementById('modalAlertaMensaje');
    
    if (tituloElement) tituloElement.textContent = titulo;
    if (mensajeElement) mensajeElement.textContent = mensaje;
    
    // Crear nueva instancia del modal
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
    modalActual.show();
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
    if (mensajeElement) mensajeElement.textContent = mensaje;
    
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