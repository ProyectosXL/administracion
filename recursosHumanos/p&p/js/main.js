
// Variables globales
let currentDocument = null;
        
// Función para mostrar tabs
function showTab(tabId) {
    // Ocultar todos los contenidos de tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Eliminar la clase active de todos los tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Mostrar el contenido del tab seleccionado
    document.getElementById(tabId + '-content').style.display = 'block';
    
    // Añadir clase active al tab seleccionado
    const activeTab = document.querySelector(`.tab[onclick="showTab('${tabId}')"]`);
    if (activeTab) {
        activeTab.classList.add('active');
    }
    
    // Activar enlace en el menú lateral
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('onclick') && link.getAttribute('onclick').includes(tabId)) {
            link.classList.add('active');
        }
    });
}

// Función para visualizar un documento
function viewDocument(documentId) {
    // En una implementación real, se haría una solicitud AJAX para obtener los detalles del documento
    fetch('Controller/obtener_documento.php?id=' + documentId)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                currentDocument = data.documento;
                
                // Establecer título
                document.getElementById('pdfTitle').textContent = currentDocument.titulo;
                
                // Cargar el PDF en el visor
                const pdfViewer = document.getElementById('pdfViewer');
                
                // En un sistema real utilizaríamos PDF.js para mostrar el PDF
                pdfViewer.innerHTML = `
                    <div style="padding: 20px; text-align: center;">
                        <h4>Visualizando: ${currentDocument.titulo}</h4>
                        <p>Sector: ${currentDocument.sector_nombre}</p>
                        <p>Fecha: ${currentDocument.fecha_actualizacion}</p>
                        <p>En una implementación real, aquí se mostraría el PDF usando una biblioteca como PDF.js</p>
                        <div class="pdf-placeholder">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                    </div>
                `;
                
                // Mostrar el modal
                document.getElementById('pdfViewerModal').style.display = 'block';
            } else {
                showNotification('Error al cargar el documento', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al cargar el documento', 'error');
            
            // Como prueba, mostrar un visor simulado
            simulateDocumentView(documentId);
        });
}

// Función para simular la visualización de un documento (para desarrollo)
function simulateDocumentView(documentId) {
    // Datos simulados
    const documents = document.querySelectorAll('.policy-item');
    let documentTitle = "Documento #" + documentId;
    
    // Buscar título en los elementos de la lista
    documents.forEach(item => {
        if (item.querySelector('.action-btn.view').getAttribute('onclick').includes(documentId)) {
            documentTitle = item.querySelector('h4').textContent;
        }
    });
    
    currentDocument = {
        id: documentId,
        titulo: documentTitle,
        sector_nombre: "Sector de prueba",
        fecha_actualizacion: new Date().toLocaleDateString()
    };
    
    // Establecer título
    document.getElementById('pdfTitle').textContent = currentDocument.titulo;
    
    // Cargar simulación de PDF
    const pdfViewer = document.getElementById('pdfViewer');
    pdfViewer.innerHTML = `
        <div style="padding: 20px; text-align: center;">
            <h4>Visualizando: ${currentDocument.titulo}</h4>
            <p>Sector: ${currentDocument.sector_nombre}</p>
            <p>Fecha: ${currentDocument.fecha_actualizacion}</p>
            <p>En una implementación real, aquí se mostraría el PDF usando una biblioteca como PDF.js</p>
            <div class="pdf-placeholder">
                <i class="fas fa-file-pdf"></i>
            </div>
        </div>
    `;
    
    // Mostrar el modal
    document.getElementById('pdfViewerModal').style.display = 'block';
}

// Función para descargar un documento
function downloadDocument(documentId) {
    // En una implementación real, redirigir a un script que gestione la descarga
    window.location.href = 'descargar_documento.php?id=' + documentId;
    
    // Mostrar notificación
    showNotification('Iniciando descarga...', 'info');
}

// Función para descargar el documento actual en visualización
function downloadCurrentPdf() {
    if (currentDocument) {
        downloadDocument(currentDocument.id);
    }
}

// Función para imprimir el PDF actual
function printPdf() {
    showNotification('Preparando documento para impresión...', 'info');
    // En una implementación real, se utilizaría la API de impresión del navegador
    // window.print();
}

// Funciones para el modal del PDF
function closePdfViewer() {
    document.getElementById('pdfViewerModal').style.display = 'none';
    currentDocument = null;
}

function searchInPdf() {
    // Simular búsqueda dentro del PDF
    const searchTerm = prompt("Introducir término a buscar en el documento:");
    if (searchTerm && searchTerm.trim() !== '') {
        showNotification(`Buscando "${searchTerm}" en el documento actual.`, 'info');
    }
}

// Funciones para el glosario
function showGlossary() {
    document.getElementById('glossaryModal').style.display = 'block';
}

function closeGlossary() {
    document.getElementById('glossaryModal').style.display = 'none';
}

// Filtrar términos del glosario
document.getElementById('glossarySearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const glossaryItems = document.querySelectorAll('.glossary-item');
    const glossaryLetters = document.querySelectorAll('.glossary-letter');
    let visibleCount = 0;
    
    glossaryItems.forEach(item => {
        const term = item.querySelector('h4').textContent.toLowerCase();
        const definition = item.querySelector('p').textContent.toLowerCase();
        
        if (term.includes(searchTerm) || definition.includes(searchTerm)) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Mostrar/ocultar encabezados de letra
    glossaryLetters.forEach(letter => {
        const nextList = letter.nextElementSibling;
        if (nextList && nextList.classList.contains('glossary-list')) {
            const visibleItems = nextList.querySelectorAll('.glossary-item[style="display: block"]').length;
            letter.style.display = visibleItems > 0 ? 'block' : 'none';
        }
    });
    
    // Mostrar mensaje si no hay resultados
    const noResultsMessage = document.querySelector('.no-results-message');
    if (noResultsMessage) {
        noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    }
});

// Filtrar políticas por sector
function filterPolicies() {
    const sectorId = document.getElementById('sectorFilterPolicies').value;
    window.location.href = 'index.php?tipo=politica&sector=' + sectorId;
}

// Filtrar procedimientos por sector
function filterProcedures() {
    const sectorId = document.getElementById('sectorFilterProcedures').value;
    window.location.href = 'index.php?tipo=procedimiento&sector=' + sectorId;
}

// Filtrar documentos por sector específico
function filterBySection(sectorId) {
    window.location.href = 'index.php?sector=' + sectorId;
}

// Gestión de formularios en configuración
document.addEventListener('DOMContentLoaded', function() {
    // Toggle formulario de nuevo sector
    const newSectorBtn = document.getElementById('newSectorBtn');
    if (newSectorBtn) {
        newSectorBtn.addEventListener('click', function() {
            document.getElementById('newSectorForm').style.display = 'block';
        });
    }
    
    const cancelSectorBtn = document.getElementById('cancelSectorBtn');
    if (cancelSectorBtn) {
        cancelSectorBtn.addEventListener('click', function() {
            document.getElementById('newSectorForm').style.display = 'none';
        });
    }
    
    // Toggle formulario de nuevo término
    const newTermBtn = document.getElementById('newTermBtn');
    if (newTermBtn) {
        newTermBtn.addEventListener('click', function() {
            document.getElementById('newTermForm').style.display = 'block';
        });
    }
    
    const cancelTermBtn = document.getElementById('cancelTermBtn');
    if (cancelTermBtn) {
        cancelTermBtn.addEventListener('click', function() {
            document.getElementById('newTermForm').style.display = 'none';
        });
    }
    
    // Búsqueda global
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    window.location.href = 'index.php?busqueda=' + encodeURIComponent(searchTerm);
                }
            }
        });
    }
    
    // Alternar la visibilidad de la barra lateral
    const toggleSidebar = document.getElementById('toggleSidebar');
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });
    }
    
    // Cerrar los modales si el usuario hace clic fuera de ellos
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    };
});

// Funciones de utilidad
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'info' ? 'fa-info-circle' : type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="close-notification">&times;</button>
    `;
    
    // Añadir al contenedor
    const container = document.querySelector('.notification-container');
    container.appendChild(notification);
    
    // Configurar eliminación automática
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 4000);
    
    // Configurar cierre manual
    notification.querySelector('.close-notification').addEventListener('click', () => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
}

// Funciones para editar y eliminar sectores
function editSector(sectorId) {
    // En una implementación real, cargar los datos del sector y mostrar formulario
    showNotification('Función de edición no implementada', 'info');
}

function confirmDeleteSector(sectorId) {
    if (confirm('¿Está seguro que desea eliminar este sector? Esta acción puede afectar a los documentos asociados.')) {
        // Enviar solicitud de eliminación
        window.location.href = 'eliminar_sector.php?id=' + sectorId;
    }
}