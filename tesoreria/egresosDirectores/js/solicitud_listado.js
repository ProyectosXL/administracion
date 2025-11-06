/**
 * Gestión del listado de solicitudes
 */

/**
 * Carga las solicitudes
 */
async function cargarSolicitudes(filtros = {}) {
    mostrarLoading();
    
    try {
        let url = 'controller/solicitud_controller.php?accion=listar';
        
        if (filtros.id_director) {
            url += `&id_director=${encodeURIComponent(filtros.id_director)}`;
        }
        
        if (filtros.estado) {
            url += `&estado=${encodeURIComponent(filtros.estado)}`;
        }
        
        if (filtros.fecha_desde) {
            url += `&fecha_desde=${filtros.fecha_desde}`;
        }
        
        if (filtros.fecha_hasta) {
            url += `&fecha_hasta=${filtros.fecha_hasta}`;
        }
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            mostrarListaSolicitudes(result.data);
        } else {
            console.error('Error al cargar solicitudes:', result.message);
            mostrarListaSolicitudes([]);
        }
    } catch (error) {
        console.error('Error en cargarSolicitudes:', error);
        mostrarAlerta('Error', 'No se pudieron cargar las solicitudes');
        mostrarListaSolicitudes([]);
    } finally {
        ocultarLoading();
    }
}

/**
 * Muestra la lista de solicitudes
 */
function mostrarListaSolicitudes(solicitudes) {
    const contenedor = document.getElementById('listadoSolicitudes');
    
    if (!solicitudes || solicitudes.length === 0) {
        contenedor.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>No hay solicitudes registradas</h5>
                <p>Las solicitudes que crees aparecerán aquí.</p>
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
    
    // Iniciar el contenedor con sistema de grid responsive
    // col-12 = 1 columna en móvil
    // col-md-6 = 2 columnas en tablets (pantallas medianas)
    // col-lg-4 = 3 columnas en escritorio (pantallas grandes)
    let html = '<div class="row g-3">';
    
    solicitudes.forEach(solicitud => {
        const fecha = new Date(solicitud.fecha_solicitud).toLocaleDateString('es-AR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const importe = formatoMoneda.format(solicitud.importe);
        
        const estadoClass = `estado-${solicitud.estado.toLowerCase()}`;
        const estadoTexto = obtenerTextoEstado(solicitud.estado);
        
        const motivoClass = solicitud.motivo === 'COMPRA_PERSONAL' ? 'motivo-compra' : 'motivo-retiro';
        const motivoTexto = solicitud.motivo === 'COMPRA_PERSONAL' ? 'Compra Personal' : 'Retiro de Dinero';
        
        html += `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card solicitud-card ${estadoClass} h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="solicitud-header">
                            <div class="flex-grow-1">
                                <span class="solicitud-id">${solicitud.id_solicitud}</span>
                                <h6 class="solicitud-director mt-1 mb-0">${solicitud.nombre_director}</h6>
                            </div>
                            <span class="badge estado-badge ${estadoClass}">${estadoTexto}</span>
                        </div>
                        
                        <div class="mt-2">
                            <span class="motivo-badge ${motivoClass}">
                                <i class="bi ${solicitud.motivo === 'COMPRA_PERSONAL' ? 'bi-receipt' : 'bi-cash-coin'}"></i>
                                ${motivoTexto}
                            </span>
                            <div class="solicitud-importe mt-2">${importe}</div>
                        </div>
                        
                        <div class="mt-2">
                            <div class="solicitud-fecha mb-1">
                                <i class="bi bi-calendar3"></i> ${fecha}
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-paperclip"></i> ${solicitud.cantidad_archivos || 0} archivo(s)
                            </small>
                        </div>
                        
                        ${solicitud.observaciones ? `
                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted">
                                    <i class="bi bi-chat-left-text"></i> ${solicitud.observaciones}
                                </small>
                            </div>
                        ` : ''}
                        
                        <div class="mt-auto pt-2 border-top d-flex gap-1 flex-wrap">
                            <button class="btn btn-sm btn-outline-primary" onclick="verDetalle('${solicitud.id_solicitud}')">
                                <i class="bi bi-eye"></i> Detalle
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="verHistorial('${solicitud.id_solicitud}')">
                                <i class="bi bi-clock-history"></i> Historial
                            </button>
                            ${solicitud.cantidad_archivos > 0 ? `
                                <button class="btn btn-sm btn-outline-info" onclick="verArchivos('${solicitud.id_solicitud}')">
                                    <i class="bi bi-paperclip"></i> Archivos (${solicitud.cantidad_archivos})
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    contenedor.innerHTML = html;
}

/**
 * Obtiene el texto del estado
 */
function obtenerTextoEstado(estado) {
    const estados = {
        'SOLICITADO': 'Solicitado',
        'CARGADO': 'Cargado',
        'PAGADO': 'Pagado'
    };
    return estados[estado] || estado;
}

/**
 * Ver detalle de solicitud
 */
async function verDetalle(idSolicitud) {
    mostrarLoading();
    
    try {
        const response = await fetch(`controller/solicitud_controller.php?accion=obtener&id_solicitud=${idSolicitud}`);
        const result = await response.json();
        
        if (result.success) {
            const solicitud = result.data;
            const formatoMoneda = new Intl.NumberFormat('es-AR', { 
                style: 'currency', 
                currency: 'ARS',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
            
            const fecha = new Date(solicitud.fecha_solicitud).toLocaleDateString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const motivoTexto = solicitud.motivo === 'COMPRA_PERSONAL' ? 'Compra Personal' : 'Retiro de Dinero';
            const estadoTexto = obtenerTextoEstado(solicitud.estado);
            
            // Crear modal personalizado para detalle
            const modalId = 'modalDetalleSolicitud';
            let modalElement = document.getElementById(modalId);
            
            if (!modalElement) {
                modalElement = document.createElement('div');
                modalElement.id = modalId;
                modalElement.className = 'modal fade';
                modalElement.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detalle de Solicitud</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="modalDetalleContenido"></div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modalElement);
            }
            
            const contenidoHtml = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-info-circle"></i> Información General</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="45%" class="text-muted">ID Solicitud:</td>
                                <td><strong>${solicitud.id_solicitud}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Director:</td>
                                <td>${solicitud.nombre_director}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Motivo:</td>
                                <td>${motivoTexto}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Importe:</td>
                                <td class="text-success fs-5"><strong>${formatoMoneda.format(solicitud.importe)}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Estado:</td>
                                <td><span class="badge estado-${solicitud.estado.toLowerCase()}">${estadoTexto}</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-calendar-check"></i> Detalles Adicionales</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="45%" class="text-muted">Fecha Solicitud:</td>
                                <td>${fecha}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Archivos Adjuntos:</td>
                                <td><span class="badge bg-secondary">${solicitud.cantidad_archivos || 0}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                ${solicitud.observaciones || solicitud.observaciones_proveedores || solicitud.observaciones_tesoreria ? `
                    <div class="mt-3">
                        <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-chat-left-text"></i> Observaciones</h6>
                        ${solicitud.observaciones ? `
                            <div class="mb-2">
                                <strong class="text-primary d-block mb-1">
                                    <i class="bi bi-person-circle"></i> Director:
                                </strong>
                                <p class="bg-light p-2 rounded small mb-0">${solicitud.observaciones}</p>
                            </div>
                        ` : ''}
                        ${solicitud.observaciones_proveedores ? `
                            <div class="mb-2">
                                <strong class="text-info d-block mb-1">
                                    <i class="bi bi-building"></i> Proveedores:
                                </strong>
                                <p class="bg-light p-2 rounded small mb-0">${solicitud.observaciones_proveedores}</p>
                            </div>
                        ` : ''}
                        ${solicitud.observaciones_tesoreria ? `
                            <div class="mb-2">
                                <strong class="text-success d-block mb-1">
                                    <i class="bi bi-cash-stack"></i> Tesorería:
                                </strong>
                                <p class="bg-light p-2 rounded small mb-0">${solicitud.observaciones_tesoreria}</p>
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
            `;
            
            document.getElementById('modalDetalleContenido').innerHTML = contenidoHtml;
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo obtener el detalle');
    } finally {
        ocultarLoading();
    }
}

/**
 * Ver historial de solicitud
 */
async function verHistorial(idSolicitud) {
    mostrarLoading();
    
    try {
        const response = await fetch(`controller/solicitud_controller.php?accion=historial&id_solicitud=${idSolicitud}`);
        const result = await response.json();
        
        if (result.success) {
            const contenedor = document.getElementById('modalHistorialContenido');
            
            if (result.data.length === 0) {
                contenedor.innerHTML = '<p class="text-muted">No hay historial disponible</p>';
            } else {
                let html = '';
                
                result.data.forEach(item => {
                    const fecha = new Date(item.fecha_cambio).toLocaleDateString('es-AR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    // Personalizar primer cambio según el tipo
                    let estadoAnterior, estadoNuevo, usuario;
                    
                    if (!item.estado_anterior && (item.usuario === 'SISTEMA' || item.usuario === 'DIRECTORES')) {
                        // Es el primer cambio (creación de la solicitud)
                        estadoAnterior = 'Creado';
                        estadoNuevo = obtenerTextoEstado(item.estado_nuevo);
                        usuario = 'DIRECTORES';
                    } else {
                        estadoAnterior = item.estado_anterior ? obtenerTextoEstado(item.estado_anterior) : 'Ninguno';
                        estadoNuevo = obtenerTextoEstado(item.estado_nuevo);
                        usuario = item.usuario;
                    }
                    
                    html += `
                        <div class="historial-item">
                            <div class="historial-fecha">${fecha}</div>
                            <div class="historial-cambio">
                                ${estadoAnterior} → ${estadoNuevo}
                            </div>
                            <div class="historial-usuario">
                                <i class="bi bi-person"></i> ${usuario}
                            </div>
                            ${item.observaciones ? `
                                <div class="mt-1">
                                    <small class="text-muted">${item.observaciones}</small>
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
                
                contenedor.innerHTML = html;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('modalHistorial'));
            modal.show();
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo obtener el historial');
    } finally {
        ocultarLoading();
    }
}

/**
 * Ver archivos de solicitud
 */
async function verArchivos(idSolicitud) {
    mostrarLoading();
    
    try {
        const response = await fetch(`controller/archivo_controller.php?accion=listar&id_solicitud=${idSolicitud}`);
        const result = await response.json();
        
        if (result.success) {
            if (result.data.length === 0) {
                mostrarAlerta('Archivos', 'No hay archivos adjuntos');
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
function obtenerIconoArchivo(mimeType) {
    if (mimeType.startsWith('image/')) {
        return 'bi-file-image';
    } else if (mimeType === 'application/pdf') {
        return 'bi-file-pdf';
    } else {
        return 'bi-file-earmark';
    }
}

/**
 * Aplica filtros al listado
 */
function aplicarFiltros() {
    const filtros = {
        estado: document.getElementById('filtroEstado')?.value || '',
        fecha_desde: document.getElementById('filtroFechaDesde')?.value || '',
        fecha_hasta: document.getElementById('filtroFechaHasta')?.value || ''
    };
    
    console.log('Aplicando filtros:', filtros);
    cargarSolicitudes(filtros);
}

/**
 * Limpia los filtros
 */
function limpiarFiltros() {
    document.getElementById('filtroEstado').value = '';
    document.getElementById('filtroFechaDesde').value = '';
    document.getElementById('filtroFechaHasta').value = '';
    
    cargarSolicitudes();
}

// NOTA: El listener para cargar solicitudes está en cada página específica (directores.php, proveedores.php)
// para permitir filtrado personalizado por usuario

// Botón actualizar
document.getElementById('btnActualizar')?.addEventListener('click', function(e) {
    e.preventDefault();
    const tabActivo = document.querySelector('.tab-pane.active');
    if (tabActivo && tabActivo.id === 'listado') {
        cargarSolicitudes();
        mostrarAlerta('Éxito', 'Datos actualizados correctamente');
    }
});

// Exportar funciones para uso global
window.cargarSolicitudes = cargarSolicitudes;
window.aplicarFiltros = aplicarFiltros;
window.limpiarFiltros = limpiarFiltros;
window.verDetalle = verDetalle;
window.verHistorial = verHistorial;
window.verArchivos = verArchivos;
window.descargarArchivo = descargarArchivo;
window.verImagenCompleta = verImagenCompleta;