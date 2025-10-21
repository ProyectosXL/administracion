/**
 * Locatarios IRSA - Script Principal
 * Gestión de exportación de datos de locatarios
 */

// Configuración
const CONFIG = {
    controllerPath: 'Controller/ventas.php',
    dateFormat: 'YYYY-MM-DD'
};

// Elementos del DOM
const elementos = {
    desde: null,
    hasta: null,
    suc: null,
    boton: null,
    loader: null,
    alert: null
};

/**
 * Inicialización del sistema
 */
document.addEventListener('DOMContentLoaded', function() {
    inicializarElementos();
    configurarEventos();
    establecerFechasDefecto();
    validarFormulario();
});

/**
 * Inicializa las referencias a elementos del DOM
 */
function inicializarElementos() {
    elementos.desde = document.getElementById('desde');
    elementos.hasta = document.getElementById('hasta');
    elementos.suc = document.getElementById('suc');
    elementos.boton = document.getElementById('boton');
    elementos.loader = document.getElementById('loader');
    elementos.alert = document.getElementById('alert');
}

/**
 * Configura los event listeners
 */
function configurarEventos() {
    // Validación en tiempo real
    elementos.desde.addEventListener('change', validarFormulario);
    elementos.hasta.addEventListener('change', validarFormulario);
    elementos.suc.addEventListener('change', validarFormulario);
    
    // Evento del botón de exportar
    elementos.boton.addEventListener('click', exportar);
    
    // Enter para enviar
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !elementos.boton.disabled) {
            exportar();
        }
    });
}

/**
 * Establece fechas por defecto (último mes)
 */
function establecerFechasDefecto() {
    const hoy = new Date();
    const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    
    elementos.hasta.value = formatearFecha(hoy);
    elementos.desde.value = formatearFecha(primerDiaMes);
}

/**
 * Formatea una fecha al formato YYYY-MM-DD
 * @param {Date} fecha - Fecha a formatear
 * @returns {string} Fecha formateada
 */
function formatearFecha(fecha) {
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    return `${año}-${mes}-${dia}`;
}

/**
 * Valida el formulario
 * @returns {boolean} True si es válido
 */
function validarFormulario() {
    const desde = elementos.desde.value;
    const hasta = elementos.hasta.value;
    const suc = elementos.suc.value;
    
    let esValido = true;
    let mensaje = '';
    
    // Validar que todos los campos estén completos
    if (!desde || !hasta || !suc) {
        esValido = false;
        mensaje = 'Por favor, complete todos los campos';
    }
    
    // Validar que la fecha desde sea menor o igual a la fecha hasta
    if (esValido && new Date(desde) > new Date(hasta)) {
        esValido = false;
        mensaje = 'La fecha "Desde" debe ser anterior o igual a la fecha "Hasta"';
    }
    
    // Validar que no sea un rango muy grande (más de 1 año)
    if (esValido) {
        const diffTime = Math.abs(new Date(hasta) - new Date(desde));
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        if (diffDays > 365) {
            esValido = false;
            mensaje = 'El rango de fechas no puede ser mayor a 1 año';
        }
    }
    
    // Actualizar estado del botón
    elementos.boton.disabled = !esValido;
    
    // Mostrar mensaje de error si existe
    if (!esValido && mensaje) {
        mostrarAlerta(mensaje, 'warning');
    } else {
        ocultarAlerta();
    }
    
    return esValido;
}

/**
 * Función principal de exportación
 */
function exportar() {
    if (!validarFormulario()) {
        return;
    }
    
    const desde = elementos.desde.value;
    const hasta = elementos.hasta.value;
    const suc = elementos.suc.value;
    const sucNombre = elementos.suc.options[elementos.suc.selectedIndex].text;
    
    // Deshabilitar botón y mostrar loader
    elementos.boton.disabled = true;
    elementos.boton.innerHTML = '<span class="loader"></span> Exportando...';
    
    // Construir URL con parámetros
    const params = new URLSearchParams({
        desde: desde,
        hasta: hasta,
        suc: suc
    });
    
    const url = `${CONFIG.controllerPath}?${params.toString()}`;
    
    // Crear elemento de descarga temporal
    const link = document.createElement('a');
    link.href = url;
    link.download = `locatarios_${sucNombre}_${desde}_${hasta}.txt`;
    link.style.display = 'none';
    
    document.body.appendChild(link);
    
    try {
        // Iniciar descarga
        link.click();
        
        // Mensaje de éxito
        setTimeout(() => {
            mostrarAlerta(`Exportación iniciada para ${sucNombre} (${desde} - ${hasta})`, 'success');
            resetearBoton();
        }, 500);
        
    } catch (error) {
        console.error('Error en la exportación:', error);
        mostrarAlerta('Error al iniciar la exportación. Por favor, intente nuevamente.', 'danger');
        resetearBoton();
    } finally {
        // Limpiar elemento temporal
        setTimeout(() => {
            document.body.removeChild(link);
        }, 1000);
    }
}

/**
 * Resetea el estado del botón
 */
function resetearBoton() {
    elementos.boton.disabled = false;
    elementos.boton.innerHTML = '📥 Exportar TXT';
}

/**
 * Muestra una alerta
 * @param {string} mensaje - Mensaje a mostrar
 * @param {string} tipo - Tipo de alerta (success, danger, warning)
 */
function mostrarAlerta(mensaje, tipo) {
    if (!elementos.alert) return;
    
    elementos.alert.className = `alert alert-${tipo} show`;
    elementos.alert.textContent = mensaje;
    
    // Auto-ocultar después de 5 segundos
    setTimeout(ocultarAlerta, 5000);
}

/**
 * Oculta la alerta
 */
function ocultarAlerta() {
    if (!elementos.alert) return;
    elementos.alert.classList.remove('show');
}

/**
 * Obtiene el nombre de la sucursal seleccionada
 * @returns {string} Nombre de la sucursal
 */
function obtenerNombreSucursal() {
    return elementos.suc.options[elementos.suc.selectedIndex].text;
}

/**
 * Registra eventos en consola para debugging
 */
function log(mensaje, datos = null) {
    if (console && console.log) {
        const timestamp = new Date().toLocaleTimeString();
        console.log(`[${timestamp}] ${mensaje}`, datos || '');
    }
}

// Exportar funciones para uso global
window.exportar = exportar;
