/**
 * Gestión del listado de facturas pendientes para Proveedores
 */

/**
 * Carga las facturas pendientes (solo COMPRA_PERSONAL con estado SOLICITADO)
 */
async function cargarFacturasPendientes() {
    mostrarLoading();
    
    try {
        // Filtrar solo COMPRA_PERSONAL en estado SOLICITADO
        const url = 'controller/solicitud_controller.php?accion=listar&estado=SOLICITADO';
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            // Filtrar solo compras personales
            const comprasPersonales = result.data.filter(
                solicitud => solicitud.motivo === 'COMPRA_PERSONAL'
            );
            mostrarFacturasPendientes(comprasPersonales);
        } else {
            console.error('Error al cargar facturas:', result.message);
            document.getElementById('facturasPendientes').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Error al cargar facturas: ${result.message}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error en cargarFacturasPendientes:', error);
        document.getElementById('facturasPendientes').innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> Error de conexión
            </div>
        `;
    } finally {
        ocultarLoading();
    }
}

/**
 * Muestra las facturas pendientes en la interfaz
 */
function mostrarFacturasPendientes(facturas) {
    const contenedor = document.getElementById('facturasPendientes');
    
    if (!facturas || facturas.length === 0) {
        contenedor.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>No hay facturas pendientes</h5>
                <p>Cuando los directores envíen solicitudes de compra personal, aparecerán aquí.</p>
            </div>
        `;
        return;
    }
    
    const formatoMoneda = new Intl.NumberFormat('es-AR', { 
        style: 'currency', 
        currency: 'ARS',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    let html = '<div class="table-responsive"><table class="table table-hover">';
    html += `
        <thead class="table-dark">
            <tr>
                <th>ID Solicitud</th>
                <th>Director</th>
                <th>Fecha</th>
                <th class="text-end">Importe</th>
                <th class="text-center">Archivos</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
    `;
    
    facturas.forEach(factura => {
        const fecha = new Date(factura.fecha_solicitud).toLocaleDateString('es-AR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        
        const importe = formatoMoneda.format(factura.importe);
        
        html += `
            <tr>
                <td><code>${factura.id_solicitud}</code></td>
                <td><strong>${factura.nombre_director}</strong></td>
                <td>${fecha}</td>
                <td class="text-end">${importe}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-info" onclick="verArchivosFactura('${factura.id_solicitud}')">
                        <i class="bi bi-paperclip"></i> ${factura.cantidad_archivos || 0}
                    </button>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary" onclick="abrirModalOrdenCompra('${factura.id_solicitud}')">
                        <i class="bi bi-file-earmark-check"></i> Cargar O.C.
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    contenedor.innerHTML = html;
}

/**
 * Ver archivos adjuntos de una factura
 */
async function verArchivosFactura(idSolicitud) {
    mostrarLoading();
    
    try {
        const response = await fetch(`controller/archivo_controller.php?accion=listar&id_solicitud=${idSolicitud}`);
        const result = await response.json();
        
        if (result.success) {
            if (result.data.length === 0) {
                mostrarAlerta('Archivos', 'No hay archivos adjuntos en esta solicitud');
                return;
            }
            
            let html = '<div class="list-group">';
            
            result.data.forEach(archivo => {
                const fecha = new Date(archivo.fecha_carga).toLocaleDateString('es-AR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
                const sizeKB = (archivo.tamanio_bytes / 1024).toFixed(2);
                const icon = obtenerIconoArchivo(archivo.mime_type);
                const esImagen = archivo.mime_type.startsWith('image/');
                
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex gap-3 flex-grow-1">
                                <div class="text-primary">
                                    <i class="bi ${icon} fs-2"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${archivo.nombre_archivo}</h6>
                                    <div class="text-muted small">
                                        ${archivo.tipo_archivo} • ${sizeKB} KB
                                    </div>
                                    <div class="text-muted small">
                                        <i class="bi bi-calendar3"></i> ${fecha}
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0">
                                ${esImagen ? `
                                    <button class="btn btn-sm btn-outline-primary" onclick="verImagenCompleta(${archivo.id}, '${archivo.nombre_archivo.replace(/'/g, "\\'")}')">
                                        <i class="bi bi-eye"></i> Ver
                                    </button>
                                ` : ''}
                                <button class="btn btn-sm btn-primary" onclick="descargarArchivo(${archivo.id})">
                                    <i class="bi bi-download"></i> Descargar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            const modalElement = document.getElementById('modalVerArchivo');
            document.getElementById('modalVerArchivoContenido').innerHTML = html;
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudieron obtener los archivos');
    } finally {
        ocultarLoading();
    }
}

/**
 * Ver imagen completa en modal
 */
async function verImagenCompleta(idArchivo, nombreArchivo) {
    console.log('Intentando ver imagen:', idArchivo, nombreArchivo);
    mostrarLoading();
    
    try {
        const url = `controller/archivo_controller.php?accion=descargar&id=${idArchivo}`;
        console.log('Fetching desde:', url);
        
        const response = await fetch(url);
        const result = await response.json();
        
        console.log('Resultado:', result);
        
        if (result.success && result.data) {
            const modalId = 'modalImagenCompleta';
            let modalElement = document.getElementById(modalId);
            
            if (!modalElement) {
                console.log('Creando modal de imagen');
                modalElement = document.createElement('div');
                modalElement.id = modalId;
                modalElement.className = 'modal fade';
                modalElement.innerHTML = `
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalImagenCompletaTitulo"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center bg-light p-4">
                                <img id="modalImagenCompletaImg" class="img-fluid" style="max-width: 100%; height: auto;" alt="">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-primary" onclick="descargarArchivo(${idArchivo})">
                                    <i class="bi bi-download"></i> Descargar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modalElement);
            }
            
            const titulo = document.getElementById('modalImagenCompletaTitulo');
            const imagen = document.getElementById('modalImagenCompletaImg');
            
            if (titulo) titulo.textContent = nombreArchivo;
            if (imagen) {
                const imgSrc = `data:${result.data.mime_type};base64,${result.data.archivo}`;
                console.log('Estableciendo src de imagen, tamaño base64:', result.data.archivo.length);
                imagen.src = imgSrc;
                imagen.alt = nombreArchivo;
            }
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            console.log('Modal mostrado');
        } else {
            console.error('Error en resultado:', result);
            mostrarAlerta('Error', result.message || 'No se pudo cargar la imagen');
        }
    } catch (error) {
        console.error('Error en verImagenCompleta:', error);
        mostrarAlerta('Error', 'No se pudo cargar la imagen: ' + error.message);
    } finally {
        ocultarLoading();
    }
}

/**
 * Descarga un archivo
 */
async function descargarArchivo(idArchivo) {
    mostrarLoading();
    
    try {
        const response = await fetch(`controller/archivo_controller.php?accion=descargar&id=${idArchivo}`);
        const result = await response.json();
        
        if (result.success) {
            const linkDescarga = document.createElement('a');
            linkDescarga.href = `data:${result.data.mime_type};base64,${result.data.archivo}`;
            linkDescarga.download = result.data.nombre_archivo;
            linkDescarga.click();
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo descargar el archivo');
    } finally {
        ocultarLoading();
    }
}

/**
 * Obtiene el icono según el tipo de archivo
 */
function obtenerIconoArchivo(tipo) {
    if (tipo.startsWith('image/')) return 'bi-file-image';
    if (tipo.includes('pdf')) return 'bi-file-pdf';
    if (tipo.includes('word')) return 'bi-file-word';
    if (tipo.includes('excel') || tipo.includes('spreadsheet')) return 'bi-file-excel';
    return 'bi-file-earmark';
}

/**
 * Abre el modal para cargar orden de compra
 */
function abrirModalOrdenCompra(idSolicitud) {
    document.getElementById('idSolicitudOrden').value = idSolicitud;
    document.getElementById('numeroOrdenCompra').value = '';
    document.getElementById('observacionesOrden').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('modalCargarOrden'));
    modal.show();
}

/**
 * Guarda la orden de compra y cambia estado a CARGADO
 */
async function guardarOrdenCompra() {
    const idSolicitud = document.getElementById('idSolicitudOrden').value;
    const numeroOrden = document.getElementById('numeroOrdenCompra').value;
    const observaciones = document.getElementById('observacionesOrden').value;
    
    if (!numeroOrden.trim()) {
        mostrarAlerta('Error', 'Debe ingresar el número de orden de compra');
        return;
    }
    
    mostrarLoading();
    
    try {
        const formData = new FormData();
        formData.append('accion', 'actualizar_estado');
        formData.append('id_solicitud', idSolicitud);
        formData.append('estado', 'CARGADO');
        formData.append('usuario', 'PROVEEDORES');
        formData.append('observaciones', `O.C. ${numeroOrden} - ${observaciones}`);
        
        const response = await fetch('controller/solicitud_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('modalCargarOrden')).hide();
            
            mostrarAlerta('Éxito', 'Orden de compra cargada correctamente. La factura está lista para pago.');
            
            // Recargar listado
            cargarFacturasPendientes();
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo guardar la orden de compra');
    } finally {
        ocultarLoading();
    }
}

// Cargar facturas al iniciar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarFacturasPendientes();
});

// Exportar funciones para uso global
window.verImagenCompleta = verImagenCompleta;