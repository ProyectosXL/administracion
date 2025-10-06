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
        
        if (filtros.nombre_director) {
            url += `&nombre_director=${encodeURIComponent(filtros.nombre_director)}`;
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
        }
    } catch (error) {
        console.error('Error en cargarSolicitudes:', error);
        mostrarAlerta('Error', 'No se pudieron cargar las solicitudes');
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
    
    let html = '';
    
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
            <div class="card solicitud-card ${estadoClass} mb-3">
                <div class="card-body">
                    <div class="solicitud-header">
                        <div>
                            <span class="solicitud-id">${solicitud.id_solicitud}</span>
                            <h5 class="solicitud-director mt-1">${solicitud.nombre_director}</h5>
                        </div>
                        <div class="text-end">
                            <span class="badge estado-badge ${estadoClass}">${estadoTexto}</span>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <span class="motivo-badge ${motivoClass}">
                                    <i class="bi ${solicitud.motivo === 'COMPRA_PERSONAL' ? 'bi-receipt' : 'bi-cash-coin'}"></i>
                                    ${motivoTexto}
                                </span>
                            </p>
                            <p class="solicitud-importe mb-0">${importe}</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="solicitud-fecha mb-2">
                                <i class="bi bi-calendar"></i> ${fecha}
                            </p>
                            <p class="mb-0">
                                <i class="bi bi-paperclip"></i> 
                                ${solicitud.cantidad_archivos || 0} archivo(s)
                            </p>
                        </div>
                    </div>
                    
                    ${solicitud.observaciones ? `
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-chat-left-text"></i> ${solicitud.observaciones}
                            </small>
                        </div>
                    ` : ''}
                    
                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="verDetalle('${solicitud.id_solicitud}')">
                            <i class="bi bi-eye"></i> Ver Detalle
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="verHistorial('${solicitud.id_solicitud}')">
                            <i class="bi bi-clock-history"></i> Historial
                        </button>
                        ${solicitud.cantidad_archivos > 0 ? `
                            <button class="btn btn-sm btn-outline-info" onclick="verArchivos('${solicitud.id_solicitud}')">
                                <i class="bi bi-paperclip"></i> Archivos
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
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
            
            const html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información General</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>ID Solicitud:</strong></td>
                                <td>${solicitud.id_solicitud}</td>
                            </tr>
                            <tr>
                                <td><strong>Director:</strong></td>
                                <td>${solicitud.nombre_director}</td>
                            </tr>
                            <tr>
                                <td><strong>Motivo:</strong></td>
                                <td>${motivoTexto}</td>
                            </tr>
                            <tr>
                                <td><strong>Importe:</strong></td>
                                <td class="text-success"><strong>${formatoMoneda.format(solicitud.importe)}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Estado:</strong></td>
                                <td><span class="badge estado-${solicitud.estado.toLowerCase()}">${estadoTexto}</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Detalles Adicionales</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Fecha Solicitud:</strong></td>
                                <td>${fecha}</td>
                            </tr>
                            <tr>
                                <td><strong>Archivos Adjuntos:</strong></td>
                                <td>${solicitud.cantidad_archivos || 0}</td>
                            </tr>
                            ${solicitud.observaciones ? `
                                <tr>
                                    <td colspan="2">
                                        <strong>Observaciones:</strong><br>
                                        <small>${solicitud.observaciones}</small>
                                    </td>
                                </tr>
                            ` : ''}
                        </table>
                    </div>
                </div>
            `;
            
            mostrarAlerta('Detalle de Solicitud', html);
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
                    
                    const estadoAnterior = item.estado_anterior ? obtenerTextoEstado(item.estado_anterior) : 'Ninguno';
                    const estadoNuevo = obtenerTextoEstado(item.estado_nuevo);
                    
                    html += `
                        <div class="historial-item">
                            <div class="historial-fecha">${fecha}</div>
                            <div class="historial-cambio">
                                ${estadoAnterior} → ${estadoNuevo}
                            </div>
                            <div class="historial-usuario">
                                <i class="bi bi-person"></i> ${item.usuario}
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
                const fecha = new Date(archivo.fecha_carga).toLocaleDateString('es-AR');
                const sizeKB = (archivo.tamanio_bytes / 1024).toFixed(2);
                const icon = obtenerIconoArchivo(archivo.mime_type);
                
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi ${icon} text-primary"></i>
                                <strong>${archivo.nombre_archivo}</strong>
                                <br>
                                <small class="text-muted">
                                    ${archivo.tipo_archivo} - ${sizeKB} KB - ${fecha}
                                </small>
                            </div>
                            <button class="btn btn-sm btn-outline-primary" onclick="descargarArchivo(${archivo.id})">
                                <i class="bi bi-download"></i>
                            </button>
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
 * Aplica filtros al listado
 */
function aplicarFiltros() {
    const filtros = {
        nombre_director: document.getElementById('filtroDirector')?.value || '',
        estado: document.getElementById('filtroEstado')?.value || '',
        fecha_desde: document.getElementById('filtroFechaDesde')?.value || '',
        fecha_hasta: document.getElementById('filtroFechaHasta')?.value || ''
    };
    
    cargarSolicitudes(filtros);
}

/**
 * Limpia los filtros
 */
function limpiarFiltros() {
    document.getElementById('filtroDirector').value = '';
    document.getElementById('filtroEstado').value = '';
    document.getElementById('filtroFechaDesde').value = '';
    document.getElementById('filtroFechaHasta').value = '';
    
    cargarSolicitudes();
}

// Cargar solicitudes cuando se muestra la pestaña
document.getElementById('listado-tab')?.addEventListener('shown.bs.tab', function() {
    cargarSolicitudes();
});

// Botón actualizar
document.getElementById('btnActualizar')?.addEventListener('click', function(e) {
    e.preventDefault();
    const tabActivo = document.querySelector('.tab-pane.active');
    if (tabActivo && tabActivo.id === 'listado') {
        cargarSolicitudes();
        mostrarAlerta('Éxito', 'Datos actualizados correctamente');
    }
});