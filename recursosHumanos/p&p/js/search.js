
// search.js
// Funciones de búsqueda para el sistema de gestión de políticas y procedimientos

// Inicializar la búsqueda
function initSearch() {
    const searchInput = document.querySelector('.search-input');
    
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        if (searchTerm.length < 2) return; // Mínimo 2 caracteres para buscar
        
        // Implementar búsqueda global
        searchDocuments(searchTerm);
        searchGlossary(searchTerm);
    });
    
    // Implementar búsqueda avanzada con teclas de acceso rápido
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            // Realizar búsqueda completa al presionar Enter
            const searchTerm = this.value.toLowerCase();
            if (searchTerm.length >= 2) {
                searchDocuments(searchTerm, true); // true para búsqueda avanzada
            }
        }
    });
}

// Buscar en documentos
function searchDocuments(searchTerm, advancedSearch = false) {
    // En una implementación real, esta función buscaría en los documentos
    // usando una API o indexación de contenido
    console.log(`Buscando documentos con el término: "${searchTerm}" (Búsqueda avanzada: ${advancedSearch})`);
    
    // Simulación de búsqueda
    const allDocumentItems = document.querySelectorAll('.policy-item');
    let matchCount = 0;
    
    allDocumentItems.forEach(item => {
        const title = item.querySelector('h4').textContent.toLowerCase();
        const details = item.querySelector('p').textContent.toLowerCase();
        
        // En búsqueda avanzada, también buscaríamos dentro del contenido del PDF
        if (title.includes(searchTerm) || details.includes(searchTerm) || 
            (advancedSearch && Math.random() > 0.7)) { // Simulación de búsqueda en contenido
            
            item.style.display = 'flex';
            item.style.backgroundColor = '#f0f9ff'; // Destacar resultados
            setTimeout(() => {
                item.style.backgroundColor = '';
            }, 2000); // Quitar el destacado después de 2 segundos
            
            matchCount++;
        } else {
            // Ocultar elementos que no coinciden si estamos en modo de búsqueda
            if (searchTerm.length > 0) {
                item.style.display = 'none';
            } else {
                item.style.display = 'flex';
            }
        }
    });
    
    // Mostrar resultados de búsqueda
    if (matchCount > 0 && searchTerm.length > 0) {
        console.log(`Se encontraron ${matchCount} documentos que coinciden con "${searchTerm}"`);
        
        // En una implementación real, podríamos mostrar un mensaje de resultados
        showNotification(`Se encontraron ${matchCount} documentos que coinciden con "${searchTerm}"`);
    }
}

// Buscar en el glosario
function searchGlossary(searchTerm) {
    // Buscar en el glosario
    const glossaryItems = document.querySelectorAll('.glossary-item');
    
    glossaryItems.forEach(item => {
        const term = item.querySelector('h4').textContent.toLowerCase();
        const description = item.querySelector('p').textContent.toLowerCase();
        
        if (term.includes(searchTerm) || description.includes(searchTerm)) {
            // Resaltar temporalmente los términos encontrados
            item.style.backgroundColor = '#f0f9ff';
            setTimeout(() => {
                item.style.backgroundColor = '';
            }, 2000);
        }
    });
}

// Funciones para aplicar filtros combinados
function applyFilters() {
    const sectorFilter = document.getElementById('sectorFilter')?.value || '';
    const typeFilter = document.getElementById('typeFilter')?.value || '';
    const dateFilter = document.getElementById('dateFilter')?.value || '';
    
    let url = 'index.php?';
    
    if (sectorFilter) {
        url += 'sector=' + sectorFilter + '&';
    }
    
    if (typeFilter) {
        url += 'tipo=' + typeFilter + '&';
    }
    
    if (dateFilter) {
        url += 'fecha=' + dateFilter + '&';
    }
    
    // Eliminar el último '&' si existe
    if (url.endsWith('&')) {
        url = url.slice(0, -1);
    }
    
    window.location.href = url;
}

// Función para reiniciar filtros
function resetFilters() {
    window.location.href = 'index.php';
}

// Filtrar políticas por sector
function filterPolicies() {
    const sectorId = document.getElementById('sectorFilterPolicies')?.value || '';
    window.location.href = 'index.php?tipo=politica&sector=' + sectorId;
}

// Filtrar procedimientos por sector
function filterProcedures() {
    const sectorId = document.getElementById('sectorFilterProcedures')?.value || '';
    window.location.href = 'index.php?tipo=procedimiento&sector=' + sectorId;
}

// Filtrar documentos por sector específico
function filterBySection(sectorId) {
    window.location.href = 'index.php?sector=' + sectorId;
}

// Buscar dentro de un PDF
function searchInPdf() {
    // Simular búsqueda dentro del PDF
    const searchTerm = prompt("Introducir término a buscar en el documento:");
    if (searchTerm && searchTerm.trim() !== '') {
        showNotification(`Buscando "${searchTerm}" en el documento actual.`, 'info');
    }
}