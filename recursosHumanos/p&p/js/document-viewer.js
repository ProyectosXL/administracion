
// Funciones para el visor de documentos PDF

// Inicializar el visor de PDF
function initPdfViewer() {
    // Configuración del visor de PDF
    // En una implementación real, inicializaría PDF.js u otra biblioteca de visualización
    
    // Cerrar el modal cuando se hace clic en el botón de cierre
    const closeButton = document.querySelector('.close-modal');
    if (closeButton) {
        closeButton.addEventListener('click', closePdfViewer);
    }
}

// Abrir el visor de PDF con un documento
function openPdfViewer(pdfTitle, pdfUrl = null) {
    document.getElementById('pdfTitle').textContent = pdfTitle;
    document.getElementById('pdfViewerModal').style.display = 'block';
    
    // En una implementación real, cargaría el PDF usando PDF.js
    // Aquí solo mostramos una simulación
    const pdfViewer = document.getElementById('pdfViewer');
    
    if (pdfUrl) {
        // Si tenemos una URL real del PDF, la usaríamos
        pdfViewer.innerHTML = `
            <div style="padding: 20px; text-align: center;">
                <h4>Visualizando: ${pdfTitle}</h4>
                <p>Cargando documento desde: ${pdfUrl}</p>
                <div class="pdf-controls">
                    <button class="pdf-btn" onclick="searchInPdf()"><i class="fas fa-search"></i> Buscar en documento</button>
                    <button class="pdf-btn" onclick="downloadCurrentPdf()"><i class="fas fa-download"></i> Descargar</button>
                    <button class="pdf-btn" onclick="printPdf()"><i class="fas fa-print"></i> Imprimir</button>
                </div>
            </div>
        `;
    } else {
        // Simulación de un visor de PDF
        pdfViewer.innerHTML = `
            <div style="padding: 20px; text-align: center;">
                <h4>Simulación de PDF - ${pdfTitle}</h4>
                <p>En una implementación real, aquí se mostraría el contenido del documento PDF.</p>
                <p>También se podría incluir funcionalidad de búsqueda dentro del documento.</p>
                <div class="pdf-controls">
                    <button class="pdf-btn" onclick="searchInPdf()"><i class="fas fa-search"></i> Buscar en documento</button>
                    <button class="pdf-btn" onclick="downloadCurrentPdf()"><i class="fas fa-download"></i> Descargar</button>
                    <button class="pdf-btn" onclick="printPdf()"><i class="fas fa-print"></i> Imprimir</button>
                </div>
            </div>
        `;
    }
}

// Cerrar el visor de PDF
function closePdfViewer() {
    document.getElementById('pdfViewerModal').style.display = 'none';
}

// Cargar y mostrar un documento por su ID
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
    window.location.href = 'Controller/descargar_documento.php?id=' + documentId;
    
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

// Función para verificar la versión de un documento
function checkDocumentVersion(documentId) {
    // En una implementación real, verificaríamos si hay una versión más reciente
    fetch('verificar_version.php?id=' + documentId)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'outdated') {
                showNotification('Hay una versión más reciente de este documento', 'warning');
            }
        })
        .catch(error => {
            console.error('Error al verificar versión:', error);
        });
}

// Función para manejar favoritos
function toggleFavorite(documentId) {
    // En una implementación real, esto se guardaría en la base de datos
    const starBtn = document.querySelector(`.policy-item .action-btn.star[data-id="${documentId}"]`);
    
    if (starBtn) {
        const isFavorite = starBtn.classList.contains('active');
        
        if (isFavorite) {
            // Quitar de favoritos
            starBtn.innerHTML = '<i class="far fa-star"></i>';
            starBtn.classList.remove('active');
            showNotification('Documento eliminado de favoritos', 'info');
        } else {
            // Añadir a favoritos
            starBtn.innerHTML = '<i class="fas fa-star"></i>';
            starBtn.classList.add('active');
            showNotification('Documento añadido a favoritos', 'success');
        }
    }
}

// Función para copiar texto al portapapeles
function copyToClipboard(text) {
    // Crear un elemento temporal para copiar el texto
    const tempElement = document.createElement('textarea');
    tempElement.value = text;
    document.body.appendChild(tempElement);
    tempElement.select();
    
    try {
        const successful = document.execCommand('copy');
        const msg = successful ? 'Texto copiado al portapapeles' : 'No se pudo copiar el texto';
        showNotification(msg, successful ? 'success' : 'error');
    } catch (err) {
        showNotification('Error al copiar el texto', 'error');
    }
    
    document.body.removeChild(tempElement);
}