/**
 * Helper para mejorar la experiencia responsive
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Función para detectar scroll en tablas responsive
    function setupTableScrollIndicators() {
        const tables = document.querySelectorAll('.table-responsive');
        
        tables.forEach(table => {
            // Verificar si puede hacer scroll
            const updateScrollIndicator = () => {
                const canScrollLeft = table.scrollLeft > 0;
                const canScrollRight = table.scrollLeft < (table.scrollWidth - table.clientWidth - 1);
                
                if (canScrollLeft) {
                    table.classList.add('can-scroll-left');
                } else {
                    table.classList.remove('can-scroll-left');
                }
                
                if (canScrollRight) {
                    table.classList.add('can-scroll-right');
                } else {
                    table.classList.remove('can-scroll-right');
                }
                
                // Marcar como scrolled si se ha desplazado
                if (table.scrollLeft > 0) {
                    table.classList.add('scrolled');
                } else {
                    table.classList.remove('scrolled');
                }
            };
            
            // Actualizar al hacer scroll
            table.addEventListener('scroll', updateScrollIndicator);
            
            // Actualizar al cargar y al redimensionar
            window.addEventListener('resize', updateScrollIndicator);
            
            // Observar cambios en el contenido de la tabla
            const observer = new MutationObserver(updateScrollIndicator);
            observer.observe(table, { childList: true, subtree: true });
            
            // Verificar inicialmente
            setTimeout(updateScrollIndicator, 100);
        });
    }
    
    // Ejecutar setup inicial
    setupTableScrollIndicators();
    
    // Re-ejecutar cuando cambien las pestañas (para tablas que se cargan dinámicamente)
    const tabTriggers = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabTriggers.forEach(trigger => {
        trigger.addEventListener('shown.bs.tab', function() {
            setTimeout(setupTableScrollIndicators, 200);
        });
    });
    
    // Mejorar comportamiento táctil en dispositivos móviles
    if ('ontouchstart' in window) {
        // Agregar clase para dispositivos táctiles
        document.body.classList.add('touch-device');
        
        // Mejorar feedback visual en botones táctiles
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            });
            
            btn.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.style.opacity = '1';
                }, 100);
            });
        });
    }
    
    // Ajustar altura de modales en móviles
    function adjustModalHeight() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                const modalBody = this.querySelector('.modal-body');
                if (modalBody && window.innerWidth <= 767) {
                    const maxHeight = window.innerHeight - 150;
                    modalBody.style.maxHeight = maxHeight + 'px';
                }
            });
        });
    }
    
    adjustModalHeight();
    
    // Optimizar rendimiento en scroll
    let scrollTimeout;
    document.addEventListener('scroll', function() {
        if (scrollTimeout) {
            window.cancelAnimationFrame(scrollTimeout);
        }
        
        scrollTimeout = window.requestAnimationFrame(function() {
            // Aquí se pueden agregar acciones optimizadas al hacer scroll
        });
    }, { passive: true });
    
    // Helper para detectar si el usuario está en un dispositivo móvil
    window.isMobile = function() {
        return window.innerWidth <= 767;
    };
    
    // Helper para detectar si el usuario está en tablet
    window.isTablet = function() {
        return window.innerWidth > 767 && window.innerWidth <= 1024;
    };
    
    // Helper para detectar si hay scroll horizontal disponible
    window.hasHorizontalScroll = function(element) {
        return element.scrollWidth > element.clientWidth;
    };
    
    console.log('✅ Responsive helper inicializado');
});
