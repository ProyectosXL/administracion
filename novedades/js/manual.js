/**
 * Funcionalidades del Manual de Usuario
 * /novedades/js/manual.js
 */

/**
 * Mostrar modal del manual de uso
 */
function mostrarManualUso() {
    const modalElement = document.getElementById('manualModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        // Analytics/tracking opcional
        console.log('Manual de uso abierto');
    }
}

/**
 * Imprimir manual completo
 */
function imprimirManual() {
    // Mostrar todas las pestañas para impresión
    const tabPanes = document.querySelectorAll('#manualModal .tab-pane');
    tabPanes.forEach(pane => {
        pane.classList.add('show', 'active');
    });
    
    // Configurar título de la página para impresión
    const originalTitle = document.title;
    document.title = 'Manual de Usuario - Sistema de Novedades RRHH';
    
    // Imprimir
    window.print();
    
    // Restaurar estado original después de imprimir
    setTimeout(() => {
        document.title = originalTitle;
        
        // Restaurar vista de pestañas
        tabPanes.forEach((pane, index) => {
            if (index === 0) {
                pane.classList.add('show', 'active');
            } else {
                pane.classList.remove('show', 'active');
            }
        });
        
        // Reactivar primera pestaña
        const firstTab = document.querySelector('#manualModal .nav-link');
        if (firstTab) {
            firstTab.classList.add('active');
        }
        
        const otherTabs = document.querySelectorAll('#manualModal .nav-link:not(:first-child)');
        otherTabs.forEach(tab => {
            tab.classList.remove('active');
        });
    }, 100);
}

/**
 * Navegación rápida a sección específica del manual
 */
function navegarASeccion(seccion) {
    mostrarManualUso();
    
    setTimeout(() => {
        const tabButton = document.querySelector(`#${seccion}-tab`);
        if (tabButton) {
            tabButton.click();
        }
    }, 500);
}

/**
 * Mostrar ayuda contextual según la página actual
 */
function mostrarAyudaContextual() {
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('nueva_novedad')) {
        navegarASeccion('nueva-novedad');
    } else if (currentPage.includes('consultar_novedades')) {
        navegarASeccion('consultar');
    } else {
        mostrarManualUso();
    }
}

/**
 * Tooltip de ayuda para campos específicos
 */
function inicializarTooltipsAyuda() {
    const tooltips = {
        'empleado-select': 'Busca empleados por nombre, apellido o legajo. El sistema mostrará sugerencias mientras escribes.',
        'fecha_vigencia': 'Fecha en que la novedad entra en efecto. Debe estar dentro del período actual.',
        'observaciones': 'Campo opcional para agregar información adicional sobre la novedad.'
    };
    
    Object.keys(tooltips).forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.setAttribute('title', tooltips[fieldId]);
            field.setAttribute('data-bs-toggle', 'tooltip');
            field.setAttribute('data-bs-placement', 'top');
        }
    });
    
    // Inicializar tooltips de Bootstrap
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }
}

/**
 * Agregar botón de ayuda flotante mejorado
 */
function agregarBotonAyudaFlotante() {
    // Verificar si ya existe el botón
    if (document.getElementById('ayuda-flotante')) {
        return;
    }

    const ayudaButton = document.createElement('button');
    ayudaButton.id = 'ayuda-flotante';
    ayudaButton.className = 'btn btn-primary position-fixed shadow-lg';
    ayudaButton.style.bottom = '20px';
    ayudaButton.style.right = '20px';
    ayudaButton.style.zIndex = '1050';
    ayudaButton.style.borderRadius = '50%';
    ayudaButton.style.width = '55px';
    ayudaButton.style.height = '55px';
    ayudaButton.style.fontSize = '20px';
    ayudaButton.style.transition = 'all 0.3s ease';
    ayudaButton.innerHTML = '<i class="fas fa-question"></i>';
    ayudaButton.title = 'Ayuda y Manual de Usuario (F1)';
    ayudaButton.onclick = mostrarAyudaContextual;
    
    // Efectos hover
    ayudaButton.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
        this.style.background = 'linear-gradient(45deg, #007bff, #0056b3)';
    });
    
    ayudaButton.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.background = '';
    });
    
    // Efecto pulsante sutil
    setInterval(function() {
        if (ayudaButton && document.body.contains(ayudaButton)) {
            ayudaButton.style.boxShadow = '0 0 20px rgba(0, 123, 255, 0.5)';
            setTimeout(function() {
                if (ayudaButton && document.body.contains(ayudaButton)) {
                    ayudaButton.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
                }
            }, 1000);
        }
    }, 3000);
    
    document.body.appendChild(ayudaButton);
}

/**
 * Inicialización cuando se carga la página
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de ayuda
    inicializarTooltipsAyuda();
    
    // Agregar botón de ayuda flotante
    agregarBotonAyudaFlotante();
    
    // Agregar evento de teclado para abrir ayuda (F1)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F1') {
            e.preventDefault();
            mostrarAyudaContextual();
        }
    });
    
    console.log('Sistema de ayuda inicializado - Presiona F1 para ayuda rápida');
});
