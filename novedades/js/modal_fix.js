/**
 * Script para corregir elementos problemáticos sin interferir con modales válidos
 * modal_fix.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Modal fix cargado');
    
    setTimeout(() => {
        // Solo buscar elementos modal-footer que estén mal posicionados
        // (directamente bajo body y NO dentro de un div.modal)
        const elementosProblematicos = [];
        
        document.querySelectorAll('.modal-footer').forEach(footer => {
            // Verificar si el elemento está fuera de un modal válido
            const modalPadre = footer.closest('.modal');
            const bodyDirecto = footer.parentElement && footer.parentElement.tagName === 'BODY';
            
            // Solo ocultar si está directamente bajo body Y no tiene un modal padre
            if (bodyDirecto && !modalPadre) {
                elementosProblematicos.push(footer);
            }
        });
        
        // Ocultar solo los elementos realmente problemáticos
        elementosProblematicos.forEach(elemento => {
            elemento.style.display = 'none';
            console.log('Elemento problemático ocultado:', elemento);
        });
        
        console.log('Modal fix completado:', {
            elementosOcultados: elementosProblematicos.length,
            modalEsValidos: document.querySelectorAll('.modal .modal-footer').length
        });
        
    }, 100);
    
    // Listener adicional para cuando se abren modales
    document.addEventListener('shown.bs.modal', function() {
        // Asegurar que los footers de modales sean visibles cuando se abren
        const modalesAbiertos = document.querySelectorAll('.modal.show .modal-footer');
        modalesAbiertos.forEach(footer => {
            if (footer.style.display === 'none') {
                footer.style.display = 'flex';
                console.log('Footer de modal restaurado:', footer);
            }
        });
    });
});
