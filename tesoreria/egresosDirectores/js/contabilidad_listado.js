/**
 * Gestión del listado de egresos pagados para Contabilidad
 */

// Variables globales para almacenar datos
let comprasPagadasData = [];
let retirosPagadosData = [];

/**
 * Carga el historial de solicitudes ya pagadas
 */
async function cargarHistorialPagadas() {
    mostrarLoading();
    
    try {
        // Cargar todas las solicitudes con estado PAGADO
        const url = 'controller/solicitud_controller.php?accion=listar&estado=PAGADO';
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            // Filtrar por tipo de motivo
            comprasPagadasData = result.data.filter(s => s.motivo === 'COMPRA_PERSONAL');
            retirosPagadosData = result.data.filter(s => s.motivo === 'RETIRO_DINERO');
            
            mostrarHistorialComprasPagadas(comprasPagadasData);
            mostrarHistorialRetirosPagados(retirosPagadosData);
        } else {
            console.error('Error al cargar historial');
        }
    } catch (error) {
        console.error('Error en cargarHistorialPagadas:', error);
    } finally {
        ocultarLoading();
    }
}

/**
 * Filtra compras pagadas por rango de fechas
 */
async function filtrarComprasPagadas() {
    mostrarLoading();
    
    try {
        const fechaDesde = document.getElementById('fechaDesdeCompras').value;
        const fechaHasta = document.getElementById('fechaHastaCompras').value;
        
        let url = 'controller/solicitud_controller.php?accion=listar&estado=PAGADO';
        
        if (fechaDesde) {
            url += `&fecha_desde=${fechaDesde}`;
        }
        
        if (fechaHasta) {
            url += `&fecha_hasta=${fechaHasta}`;
        }
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            comprasPagadasData = result.data.filter(s => s.motivo === 'COMPRA_PERSONAL');
            mostrarHistorialComprasPagadas(comprasPagadasData);
        } else {
            console.error('Error al filtrar compras');
        }
    } catch (error) {
        console.error('Error en filtrarComprasPagadas:', error);
    } finally {
        ocultarLoading();
    }
}

/**
 * Filtra retiros pagados por rango de fechas
 */
async function filtrarRetirosPagados() {
    mostrarLoading();
    
    try {
        const fechaDesde = document.getElementById('fechaDesdeRetiros').value;
        const fechaHasta = document.getElementById('fechaHastaRetiros').value;
        
        let url = 'controller/solicitud_controller.php?accion=listar&estado=PAGADO';
        
        if (fechaDesde) {
            url += `&fecha_desde=${fechaDesde}`;
        }
        
        if (fechaHasta) {
            url += `&fecha_hasta=${fechaHasta}`;
        }
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            retirosPagadosData = result.data.filter(s => s.motivo === 'RETIRO_DINERO');
            mostrarHistorialRetirosPagados(retirosPagadosData);
        } else {
            console.error('Error al filtrar retiros');
        }
    } catch (error) {
        console.error('Error en filtrarRetirosPagados:', error);
    } finally {
        ocultarLoading();
    }
}

/**
 * Limpia los filtros de compras
 */
function limpiarFiltrosCompras() {
    document.getElementById('fechaDesdeCompras').value = '';
    document.getElementById('fechaHastaCompras').value = '';
    cargarHistorialPagadas();
}

/**
 * Limpia los filtros de retiros
 */
function limpiarFiltrosRetiros() {
    document.getElementById('fechaDesdeRetiros').value = '';
    document.getElementById('fechaHastaRetiros').value = '';
    cargarHistorialPagadas();
}

/**
 * Muestra el historial de compras pagadas
 */
function mostrarHistorialComprasPagadas(compras) {
    const contenedor = document.getElementById('historialComprasPagadas');
    const resumen = document.getElementById('resumenCompras');
    
    if (!compras || compras.length === 0) {
        contenedor.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-archive"></i>
                <h5>No hay registros</h5>
                <p>No se encontraron compras pagadas con los filtros aplicados.</p>
            </div>
        `;
        resumen.innerHTML = '';
        return;
    }
    
    // Calcular total
    const total = compras.reduce((sum, c) => sum + parseFloat(c.importe), 0);
    const formatoMoneda = new Intl.NumberFormat('es-AR', { 
        style: 'currency', 
        currency: 'ARS',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    resumen.innerHTML = `
        <div class="alert alert-primary d-flex justify-content-between align-items-center">
            <div>
                <strong><i class="bi bi-graph-up"></i> Total de registros:</strong> ${compras.length}
            </div>
            <div>
                <strong><i class="bi bi-cash-stack"></i> Importe Total:</strong> ${formatoMoneda.format(total)}
            </div>
        </div>
    `;
    
    contenedor.innerHTML = generarTablaHistorial(compras, 'COMPRA_PERSONAL');
}

/**
 * Muestra el historial de retiros pagados
 */
function mostrarHistorialRetirosPagados(retiros) {
    const contenedor = document.getElementById('historialRetirosPagados');
    const resumen = document.getElementById('resumenRetiros');
    
    if (!retiros || retiros.length === 0) {
        contenedor.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-archive"></i>
                <h5>No hay registros</h5>
                <p>No se encontraron retiros pagados con los filtros aplicados.</p>
            </div>
        `;
        resumen.innerHTML = '';
        return;
    }
    
    // Calcular total
    const total = retiros.reduce((sum, r) => sum + parseFloat(r.importe), 0);
    const formatoMoneda = new Intl.NumberFormat('es-AR', { 
        style: 'currency', 
        currency: 'ARS',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    resumen.innerHTML = `
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <div>
                <strong><i class="bi bi-graph-up"></i> Total de registros:</strong> ${retiros.length}
            </div>
            <div>
                <strong><i class="bi bi-cash-stack"></i> Importe Total:</strong> ${formatoMoneda.format(total)}
            </div>
        </div>
    `;
    
    contenedor.innerHTML = generarTablaHistorial(retiros, 'RETIRO_DINERO');
}

/**
 * Genera tabla HTML para el historial
 */
function generarTablaHistorial(solicitudes, tipo) {
    const formatoMoneda = new Intl.NumberFormat('es-AR', { 
        style: 'currency', 
        currency: 'ARS',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    let html = '<div class="table-responsive"><table class="table table-hover table-sm">';
    html += `
        <thead class="table-secondary">
            <tr>
                <th>ID Solicitud</th>
                <th>Director</th>
                <th>Fecha Solicitud</th>
                <th class="text-end">Importe</th>
                <th class="text-center">Archivos</th>
                <th class="text-center">Historial</th>
            </tr>
        </thead>
        <tbody>
    `;
    
    solicitudes.forEach(solicitud => {
        const fecha = new Date(solicitud.fecha_solicitud).toLocaleDateString('es-AR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        
        const importe = formatoMoneda.format(solicitud.importe);
        
        // Para retiros de dinero, solo mostrar 0 sin botón
        const columnArchivos = tipo === 'RETIRO_DINERO' 
            ? '<span class="text-muted">0</span>'
            : `<button class="btn btn-sm btn-outline-info" onclick="verArchivosFactura('${solicitud.id_solicitud}')">
                   <i class="bi bi-paperclip"></i> ${solicitud.cantidad_archivos || 0}
               </button>`;
        
        html += `
            <tr>
                <td><code>${solicitud.id_solicitud}</code></td>
                <td><strong>${solicitud.nombre_director}</strong></td>
                <td>${fecha}</td>
                <td class="text-end">${importe}</td>
                <td class="text-center">
                    ${columnArchivos}
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-secondary" onclick="verHistorialEstados('${solicitud.id_solicitud}')">
                        <i class="bi bi-clock-history"></i> Ver
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
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
    mostrarLoading();
    
    try {
        const url = `controller/archivo_controller.php?accion=descargar&id=${idArchivo}`;
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success && result.data) {
            const modalId = 'modalImagenCompleta';
            let modalElement = document.getElementById(modalId);
            
            if (!modalElement) {
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
                imagen.src = imgSrc;
                imagen.alt = nombreArchivo;
            }
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            mostrarAlerta('Error', result.message || 'No se pudo cargar la imagen');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo cargar la imagen');
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
 * Ver el historial de estados de una solicitud
 */
async function verHistorialEstados(idSolicitud) {
    mostrarLoading();
    
    try {
        const response = await fetch(`controller/solicitud_controller.php?accion=historial&id_solicitud=${idSolicitud}`);
        const result = await response.json();
        
        if (result.success) {
            mostrarModalHistorial(idSolicitud, result.data);
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
 * Muestra el modal con el historial de estados
 */
function mostrarModalHistorial(idSolicitud, historial) {
    let html = '<div class="timeline">';
    
    if (historial.length === 0) {
        html = '<div class="alert alert-info">No hay historial de cambios para esta solicitud.</div>';
    } else {
        historial.forEach((cambio, index) => {
            const fecha = new Date(cambio.fecha_cambio).toLocaleString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const estadoAnterior = cambio.estado_anterior || 'INICIO';
            const estadoNuevo = cambio.estado_nuevo;
            
            let iconoEstado = 'bi-circle-fill';
            let colorEstado = 'text-secondary';
            
            if (estadoNuevo === 'SOLICITADO') {
                iconoEstado = 'bi-file-earmark-plus';
                colorEstado = 'text-primary';
            } else if (estadoNuevo === 'CARGADO') {
                iconoEstado = 'bi-file-earmark-check';
                colorEstado = 'text-warning';
            } else if (estadoNuevo === 'PAGADO') {
                iconoEstado = 'bi-cash-coin';
                colorEstado = 'text-success';
            }
            
            html += `
                <div class="timeline-item mb-3 ${index === 0 ? 'border-start border-3 border-success' : ''}">
                    <div class="d-flex align-items-start">
                        <div class="${colorEstado} me-3">
                            <i class="bi ${iconoEstado} fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0">${estadoAnterior} → ${estadoNuevo}</h6>
                                <small class="text-muted">${fecha}</small>
                            </div>
                            <p class="mb-1"><strong>Usuario:</strong> ${cambio.usuario}</p>
                            ${cambio.observaciones ? `<p class="mb-0 text-muted small">${cambio.observaciones}</p>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    html += '</div>';
    
    // Crear o actualizar el modal
    const modalId = 'modalHistorialEstados';
    let modalElement = document.getElementById(modalId);
    
    if (!modalElement) {
        modalElement = document.createElement('div');
        modalElement.id = modalId;
        modalElement.className = 'modal fade';
        modalElement.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-clock-history"></i> Historial de Estados
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="modalHistorialEstadosContenido">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalElement);
    }
    
    document.getElementById('modalHistorialEstadosContenido').innerHTML = `
        <div class="mb-3">
            <strong>Solicitud:</strong> <code>${idSolicitud}</code>
        </div>
        <hr>
        ${html}
    `;
    
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

/**
 * Exporta las compras pagadas a Excel
 */
function exportarComprasExcel() {
    if (!comprasPagadasData || comprasPagadasData.length === 0) {
        mostrarAlerta('Advertencia', 'No hay datos para exportar');
        return;
    }
    
    // Preparar datos para exportar (sin columnas de botones)
    const datosExport = comprasPagadasData.map(compra => ({
        'ID Solicitud': compra.id_solicitud,
        'Director': compra.nombre_director,
        'Fecha Solicitud': new Date(compra.fecha_solicitud).toLocaleDateString('es-AR'),
        'Importe': parseFloat(compra.importe),
        'Estado': compra.estado,
        'Observaciones': compra.observaciones || '',
        'Obs. Proveedores': compra.observaciones_proveedores || '',
        'Obs. Tesorería': compra.observaciones_tesoreria || ''
    }));
    
    // Crear el libro de trabajo
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(datosExport);
    
    // Ajustar ancho de columnas
    ws['!cols'] = [
        { wch: 20 },  // ID Solicitud
        { wch: 25 },  // Director
        { wch: 15 },  // Fecha
        { wch: 15 },  // Importe
        { wch: 12 },  // Estado
        { wch: 30 },  // Observaciones
        { wch: 30 },  // Obs. Proveedores
        { wch: 30 }   // Obs. Tesorería
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Compras Pagadas');
    
    // Generar nombre de archivo con fecha
    const fechaHoy = new Date().toISOString().split('T')[0];
    const nombreArchivo = `Compras_Pagadas_${fechaHoy}.xlsx`;
    
    // Descargar
    XLSX.writeFile(wb, nombreArchivo);
    
    mostrarAlerta('Éxito', `Archivo ${nombreArchivo} descargado correctamente`);
}

/**
 * Exporta los retiros pagados a Excel
 */
function exportarRetirosExcel() {
    if (!retirosPagadosData || retirosPagadosData.length === 0) {
        mostrarAlerta('Advertencia', 'No hay datos para exportar');
        return;
    }
    
    // Preparar datos para exportar (sin columnas de botones)
    const datosExport = retirosPagadosData.map(retiro => ({
        'ID Solicitud': retiro.id_solicitud,
        'Director': retiro.nombre_director,
        'Fecha Solicitud': new Date(retiro.fecha_solicitud).toLocaleDateString('es-AR'),
        'Importe': parseFloat(retiro.importe),
        'Estado': retiro.estado,
        'Observaciones': retiro.observaciones || '',
        'Obs. Tesorería': retiro.observaciones_tesoreria || ''
    }));
    
    // Crear el libro de trabajo
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(datosExport);
    
    // Ajustar ancho de columnas
    ws['!cols'] = [
        { wch: 20 },  // ID Solicitud
        { wch: 25 },  // Director
        { wch: 15 },  // Fecha
        { wch: 15 },  // Importe
        { wch: 12 },  // Estado
        { wch: 30 },  // Observaciones
        { wch: 30 }   // Obs. Tesorería
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Retiros Pagados');
    
    // Generar nombre de archivo con fecha
    const fechaHoy = new Date().toISOString().split('T')[0];
    const nombreArchivo = `Retiros_Pagados_${fechaHoy}.xlsx`;
    
    // Descargar
    XLSX.writeFile(wb, nombreArchivo);
    
    mostrarAlerta('Éxito', `Archivo ${nombreArchivo} descargado correctamente`);
}

// Cargar historial al iniciar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarHistorialPagadas();
    
    // Establecer fecha hasta como hoy por defecto
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fechaHastaCompras').value = hoy;
    document.getElementById('fechaHastaRetiros').value = hoy;
});

// Exportar funciones para uso global
window.verImagenCompleta = verImagenCompleta;
window.verHistorialEstados = verHistorialEstados;
window.filtrarComprasPagadas = filtrarComprasPagadas;
window.filtrarRetirosPagados = filtrarRetirosPagados;
window.limpiarFiltrosCompras = limpiarFiltrosCompras;
window.limpiarFiltrosRetiros = limpiarFiltrosRetiros;
window.exportarComprasExcel = exportarComprasExcel;
window.exportarRetirosExcel = exportarRetirosExcel;
