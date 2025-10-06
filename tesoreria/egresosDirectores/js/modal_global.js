/**
 * Funciones globales para modales y alertas
 */

let modalActual = null;

/**
 * Muestra una alerta
 */
function mostrarAlerta(titulo, mensaje) {
    limpiarModalPrevio();
    
    const modalElement = document.getElementById('modalAlerta');
    if (!modalElement) {
        console.error('Modal de alerta no encontrado');
        alert(titulo + ': ' + mensaje);
        return;
    }
    
    const tituloElement = document.getElementById('modalAlertaTitulo');
    const mensajeElement = document.getElementById('modalAlertaMensaje');
    
    if (tituloElement) tituloElement.textContent = titulo;
    if (mensajeElement) mensajeElement.textContent = mensaje;
    
    modalActual = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    modalElement.addEventListener('hidden.bs.modal', function() {
        limpiarModalPrevio();
        modalActual = null;
    }, { once: true });
    
    modalActual.show();
}

/**
 * Muestra confirmación
 */
function mostrarConfirmacion(titulo, mensaje, callback) {
    limpiarModalPrevio();
    
    const modalElement = document.getElementById('modalConfirmar');
    if (!modalElement) {
        console.error('Modal de confirmación no encontrado');
        if (confirm(titulo + ': ' + mensaje)) {
            callback(true);
        } else {
            callback(false);
        }
        return;
    }
    
    const tituloElement = document.getElementById('modalConfirmarTitulo');
    const mensajeElement = document.getElementById('modalConfirmarMensaje');
    const botonAceptar = document.getElementById('modalConfirmarAceptar');
    
    if (tituloElement) tituloElement.textContent = titulo;
    if (mensajeElement) mensajeElement.textContent = mensaje;
    
    modalActual = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    const manejarRespuesta = (resultado) => {
        limpiarModalPrevio();
        modalActual = null;
        callback(resultado);
    };
    
    if (botonAceptar) {
        botonAceptar.addEventListener('click', () => manejarRespuesta(true), { once: true });
    }
    
    modalElement.addEventListener('hidden.bs.modal', () => manejarRespuesta(false), { once: true });
    
    modalActual.show();
}

/**
 * Limpia modales previos
 */
function limpiarModalPrevio() {
    if (modalActual) {
        try {
            modalActual.hide();
        } catch (e) {
            console.warn('Error al cerrar modal previo:', e);
        }
        modalActual = null;
    }
    
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    const modales = document.querySelectorAll('.modal');
    modales.forEach(modal => {
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    });
}

/**
 * Muestra loading overlay
 */
function mostrarLoading() {
    let overlay = document.getElementById('loadingOverlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(overlay);
    }
    
    overlay.style.display = 'flex';
}

/**
 * Oculta loading overlay
 */
function ocultarLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Limpiar al cargar página
document.addEventListener('DOMContentLoaded', function() {
    limpiarModalPrevio();
});