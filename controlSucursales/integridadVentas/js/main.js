/**
 * main.js - Funciones compartidas para Integridad de Ventas
 */

// Restaurar pestaña activa desde URL al cargar la página
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab) {
        const tabButton = document.querySelector(`[data-bs-target="#${activeTab}"]`);
        if (tabButton) {
            const tab = new bootstrap.Tab(tabButton);
            tab.show();
        }
    }
});

/**
 * Función para cambiar el entorno (Argentina/Uruguay)
 * @param {HTMLSelectElement} selectElement - El elemento <select> que disparó el evento
 */
function cambiarEntorno(selectElement) {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
    
    const entorno = (selectElement.value === "ARG") ? 0 : 1;
    
    $.ajax({
        url: 'Controller/cambiarEntorno.php',
        method: 'POST',
        data: { entorno: entorno },
        success: function (data) {
            location.reload();
        },
        error: function(xhr, status, error) {
            console.error('Error al cambiar entorno:', error);
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            alert('Error al cambiar el país. Por favor, intente nuevamente.');
        }
    });
}

/**
 * Mostrar el overlay de carga
 */
function mostrarLoading() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
}

/**
 * Ocultar el overlay de carga
 */
function ocultarLoading() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

/**
 * Formatear número a moneda argentina
 * @param {number} value - El valor a formatear
 * @returns {string} Valor formateado
 */
function formatCurrency(value) {
    const number = parseFloat(value);
    if (isNaN(number)) {
        return '$ 0.00';
    }
    return number.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
}

/**
 * Formatear número con separadores de miles
 * @param {number} num - El número a formatear
 * @returns {string} Número formateado
 */
function parseNumber(num) {
    return num.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Inicialización al cargar el documento
$(document).ready(function() {
    // Inicializar tooltips de Bootstrap si existen
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Ocultar loading al cargar
    ocultarLoading();

    console.log('Integridad de Ventas - Sistema inicializado correctamente');
});
