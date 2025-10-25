/**
 * Sistema de columnas fijas para tablas con scroll horizontal
 * Mantiene las primeras 2 columnas visibles mientras se hace scroll
 */

(function() {
    'use strict';
    
    function inicializarColumnasFijas() {
        const wrapper = document.getElementById('tablaWrapper');
        const tabla = document.getElementById('tablaAlquileres');
        
        if (!wrapper || !tabla) {
            console.warn('No se encontró la tabla o el wrapper');
            return;
        }
        
        // Crear contenedor para columnas fijas
        let columnasFijas = document.getElementById('columnasFijasContainer');
        if (!columnasFijas) {
            columnasFijas = document.createElement('div');
            columnasFijas.id = 'columnasFijasContainer';
            columnasFijas.className = 'columnas-fijas';
            wrapper.appendChild(columnasFijas);
        }
        
        function actualizarColumnasFijas() {
            // Clonar la tabla
            const tablaClonada = tabla.cloneNode(true);
            tablaClonada.id = 'tablaAlquileresClonada';
            
            // Remover todas las columnas excepto las primeras 2
            const filasClonadas = tablaClonada.querySelectorAll('tr');
            filasClonadas.forEach(fila => {
                const celdas = Array.from(fila.children);
                // Mantener solo las primeras 2 columnas
                celdas.forEach((celda, index) => {
                    if (index > 1) {
                        celda.remove();
                    }
                });
            });
            
            // Limpiar contenedor y agregar tabla clonada
            columnasFijas.innerHTML = '';
            columnasFijas.appendChild(tablaClonada);
            
            // Sincronizar alturas de filas
            sincronizarAlturas();
        }
        
        function sincronizarAlturas() {
            const filasOriginales = tabla.querySelectorAll('tr');
            const filasClonadas = columnasFijas.querySelectorAll('tr');
            
            filasOriginales.forEach((filaOriginal, index) => {
                if (filasClonadas[index]) {
                    const altura = filaOriginal.offsetHeight;
                    filasClonadas[index].style.height = altura + 'px';
                }
            });
        }
        
        function manejarScroll() {
            const scrollLeft = wrapper.scrollLeft;
            
            if (scrollLeft > 0) {
                columnasFijas.style.display = 'block';
                columnasFijas.style.boxShadow = '3px 0 8px rgba(0,0,0,0.2)';
            } else {
                columnasFijas.style.display = 'none';
            }
        }
        
        // Inicializar
        actualizarColumnasFijas();
        
        // Eventos
        wrapper.addEventListener('scroll', manejarScroll);
        
        // Observar cambios en la tabla (cuando se actualicen valores)
        const observer = new MutationObserver(() => {
            sincronizarAlturas();
        });
        
        observer.observe(tabla, {
            childList: true,
            subtree: true,
            characterData: true
        });
        
        // Sincronizar en resize
        window.addEventListener('resize', () => {
            sincronizarAlturas();
        });
        
        // Sincronizar después de que la tabla esté completamente cargada
        setTimeout(() => {
            sincronizarAlturas();
        }, 500);
    }
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarColumnasFijas);
    } else {
        inicializarColumnasFijas();
    }
    
    // Reinicializar cuando se recargue la página via AJAX
    window.reiniciarColumnasFijas = inicializarColumnasFijas;
})();
