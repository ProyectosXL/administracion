/**
 * Gestión del listado de facturas listas para Tesorería
 */

// No necesitamos un objeto separado, usaremos directamente los inputs con multiple

/**
 * Carga las facturas listas para pago
 * - Compras personales: estado CARGADO (ya tienen O.C.)
 * - Retiros de dinero: estado CARGADO (van directo a tesorería)
 */
async function cargarFacturasListas() {
    mostrarLoading();
    
    try {
        // Cargar todas las solicitudes con estado CARGADO
        const url = 'controller/solicitud_controller.php?accion=listar&estado=CARGADO';
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            // Filtrar por tipo de motivo
            const compras = result.data.filter(s => s.motivo === 'COMPRA_PERSONAL');
            const retiros = result.data.filter(s => s.motivo === 'RETIRO_DINERO');
            
            mostrarComprasListas(compras);
            mostrarRetirosListos(retiros);
        } else {
            console.error('Error al cargar facturas');
        }
    } catch (error) {
        console.error('Error en cargarFacturasListas:', error);
    } finally {
        ocultarLoading();
    }
}

/**
 * Carga el historial de solicitudes ya pagadas
 */
async function cargarHistorialPagadas() {
    try {
        // Cargar todas las solicitudes con estado PAGADO
        const url = 'controller/solicitud_controller.php?accion=listar&estado=PAGADO';
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            // Filtrar por tipo de motivo
            const comprasPagadas = result.data.filter(s => s.motivo === 'COMPRA_PERSONAL');
            const retirosPagados = result.data.filter(s => s.motivo === 'RETIRO_DINERO');
            
            mostrarHistorialComprasPagadas(comprasPagadas);
            mostrarHistorialRetirosPagados(retirosPagados);
        } else {
            console.error('Error al cargar historial');
        }
    } catch (error) {
        console.error('Error en cargarHistorialPagadas:', error);
    }
}

/**
 * Muestra el historial de compras pagadas
 */
function mostrarHistorialComprasPagadas(compras) {
    const contenedor = document.getElementById('historialComprasPagadas');
    
    if (!compras || compras.length === 0) {
        contenedor.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-archive"></i>
                <h5>No hay registros históricos</h5>
                <p>Las compras pagadas aparecerán aquí.</p>
            </div>
        `;
        return;
    }
    
    contenedor.innerHTML = generarTablaHistorial(compras, 'COMPRA_PERSONAL');
}

/**
 * Muestra el historial de retiros pagados
 */
function mostrarHistorialRetirosPagados(retiros) {
    const contenedor = document.getElementById('historialRetirosPagados');
    
    if (!retiros || retiros.length === 0) {
        contenedor.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-archive"></i>
                <h5>No hay registros históricos</h5>
                <p>Los retiros pagados aparecerán aquí.</p>
            </div>
        `;
        return;
    }
    
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
                <th class="text-center">Acciones</th>
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
                        <i class="bi bi-clock-history"></i> Historial
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

/**
 * Muestra las compras personales listas para pago
 */
function mostrarComprasListas(compras) {
    const contenedor = document.getElementById('comprasListas');
    
    if (!compras || compras.length === 0) {
        contenedor.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>No hay compras listas para pago</h5>
                <p>Las facturas con orden de compra autorizada aparecerán aquí.</p>
            </div>
        `;
        return;
    }
    
    contenedor.innerHTML = generarTablaFacturas(compras, 'COMPRA_PERSONAL');
}

/**
 * Muestra los retiros listos para pago
 */
function mostrarRetirosListos(retiros) {
    const contenedor = document.getElementById('retirosListos');
    
    if (!retiros || retiros.length === 0) {
        contenedor.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>No hay retiros listos para pago</h5>
                <p>Los retiros de dinero autorizados aparecerán aquí.</p>
            </div>
        `;
        return;
    }
    
    contenedor.innerHTML = generarTablaFacturas(retiros, 'RETIRO_DINERO');
}

/**
 * Genera la tabla HTML para las facturas
 */
function generarTablaFacturas(facturas, tipo) {
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
        
        // Para retiros de dinero, solo mostrar 0 sin botón
        const columnArchivos = tipo === 'RETIRO_DINERO' 
            ? '<span class="text-muted">0</span>'
            : `<button class="btn btn-sm btn-outline-info" onclick="verArchivosFactura('${factura.id_solicitud}')">
                   <i class="bi bi-paperclip"></i> ${factura.cantidad_archivos || 0}
               </button>`;
        
        html += `
            <tr>
                <td><code>${factura.id_solicitud}</code></td>
                <td><strong>${factura.nombre_director}</strong></td>
                <td>${fecha}</td>
                <td class="text-end">${importe}</td>
                <td class="text-center">
                    ${columnArchivos}
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-success" onclick="abrirModalPago('${factura.id_solicitud}', '${tipo}')">
                        <i class="bi bi-cash-stack"></i> Pagar
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
            
            // Si el modal ya existe, obtener la instancia de Bootstrap y destruirla
            if (modalElement) {
                const existingModal = bootstrap.Modal.getInstance(modalElement);
                if (existingModal) {
                    existingModal.dispose();
                }
                modalElement.remove();
            }
            
            console.log('Creando modal de imagen');
            modalElement = document.createElement('div');
            modalElement.id = modalId;
            modalElement.className = 'modal fade';
            modalElement.setAttribute('tabindex', '-1');
            modalElement.setAttribute('aria-hidden', 'true');
            modalElement.innerHTML = `
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalImagenCompletaTitulo"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
            
            const titulo = document.getElementById('modalImagenCompletaTitulo');
            const imagen = document.getElementById('modalImagenCompletaImg');
            
            if (titulo) titulo.textContent = nombreArchivo;
            if (imagen) {
                const imgSrc = `data:${result.data.mime_type};base64,${result.data.archivo}`;
                console.log('Estableciendo src de imagen, tamaño base64:', result.data.archivo.length);
                imagen.src = imgSrc;
                imagen.alt = nombreArchivo;
            }
            
            // Crear modal con backdrop propio y animación
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            
            // Evento cuando el modal se muestre - ajustar z-index
            modalElement.addEventListener('shown.bs.modal', function handler() {
                console.log('Modal imagen mostrado - ajustando z-index');
                modalElement.style.zIndex = '1060';
                const backdrop = document.querySelector('.modal-backdrop:last-of-type');
                if (backdrop) {
                    backdrop.style.zIndex = '1059';
                    backdrop.classList.add('modal-backdrop-imagen');
                }
            }, { once: true });
            
            // Evento cuando el modal se oculte - limpiar
            modalElement.addEventListener('hidden.bs.modal', function handler() {
                console.log('Modal imagen cerrado - limpiando');
                
                // Eliminar el modal del DOM
                modalElement.remove();
                
                // Limpiar backdrops específicos de la imagen
                const backdropImagen = document.querySelector('.modal-backdrop-imagen');
                if (backdropImagen) {
                    backdropImagen.remove();
                }
                
                // Resetear z-index de cualquier backdrop restante
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    backdrop.style.zIndex = '';
                });
            }, { once: true });
            
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
 * Abre el modal para adjuntar comprobantes de pago
 */
function abrirModalPago(idSolicitud, tipo) {
    document.getElementById('idSolicitudPago').value = idSolicitud;
    document.getElementById('tipoSolicitudPago').value = tipo;
    
    // Limpiar inputs y vistas previas
    const inputComprobantes = document.getElementById('comprobanteTransferencia');
    const inputOrdenPago = document.getElementById('ordenPago');
    const inputRetenciones = document.getElementById('retenciones');
    
    inputComprobantes.value = '';
    inputOrdenPago.value = '';
    inputRetenciones.value = '';
    
    document.getElementById('observacionesPago').value = '';
    document.getElementById('listaComprobantesTransferencia').innerHTML = '';
    document.getElementById('listaRetenciones').innerHTML = '';
    document.getElementById('vistaOrdenPago').innerHTML = '';
    
    // Configurar eventos de cambio para mostrar vista previa automática
    inputComprobantes.onchange = () => mostrarVistaPrevia(inputComprobantes, 'listaComprobantesTransferencia');
    inputRetenciones.onchange = () => mostrarVistaPrevia(inputRetenciones, 'listaRetenciones');
    inputOrdenPago.onchange = () => mostrarVistaPrevia(inputOrdenPago, 'vistaOrdenPago');
    
    // Mostrar/ocultar campos según el tipo
    const comprobantesExtra = document.getElementById('comprobantesExtra');
    const infoComprobantes = document.getElementById('infoComprobantes');
    
    if (tipo === 'COMPRA_PERSONAL') {
        comprobantesExtra.classList.remove('d-none');
        inputOrdenPago.required = true;
        
        infoComprobantes.innerHTML = `
            <strong>Compra Personal:</strong> Debes adjuntar:
            <ul class="mb-0">
                <li>Comprobante(s) de transferencia (obligatorio, puedes seleccionar varios)</li>
                <li>Orden de pago (obligatorio)</li>
                <li>Retención(es) (opcional, puedes seleccionar varias)</li>
            </ul>
        `;
    } else {
        comprobantesExtra.classList.add('d-none');
        inputOrdenPago.required = false;
        
        infoComprobantes.innerHTML = `
            <strong>Retiro de Dinero:</strong> Adjunta uno o más comprobantes de transferencia.
        `;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('modalAdjuntarComprobantes'));
    modal.show();
}

/**
 * Muestra vista previa de archivos seleccionados
 */
function mostrarVistaPrevia(input, contenedorId) {
    const contenedor = document.getElementById(contenedorId);
    const archivos = input.files;
    
    if (!archivos || archivos.length === 0) {
        contenedor.innerHTML = '';
        return;
    }
    
    let html = '<div class="list-group">';
    
    for (let i = 0; i < archivos.length; i++) {
        const archivo = archivos[i];
        const sizeKB = (archivo.size / 1024).toFixed(2);
        const icono = obtenerIconoArchivo(archivo.type);
        
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                <div>
                    <i class="bi ${icono} text-primary"></i>
                    <span class="ms-2">${archivo.name}</span>
                    <small class="text-muted ms-2">(${sizeKB} KB)</small>
                </div>
                <span class="badge bg-success">Seleccionado</span>
            </div>
        `;
    }
    
    html += '</div>';
    contenedor.innerHTML = html;
}

/**
 * Guarda los comprobantes y marca como PAGADO
 */
async function guardarComprobantesPago() {
    const idSolicitud = document.getElementById('idSolicitudPago').value;
    const tipo = document.getElementById('tipoSolicitudPago').value;
    const observaciones = document.getElementById('observacionesPago').value;
    
    // Obtener archivos de los inputs
    const comprobantesTransf = document.getElementById('comprobanteTransferencia').files;
    const ordenPago = document.getElementById('ordenPago').files[0];
    const retenciones = document.getElementById('retenciones').files;
    
    // Validar que se hayan cargado comprobantes de transferencia
    if (!comprobantesTransf || comprobantesTransf.length === 0) {
        mostrarAlerta('Error', 'Debe adjuntar al menos un comprobante de transferencia');
        return;
    }
    
    // Validar tamaño de archivos (máximo 10MB cada uno)
    for (let i = 0; i < comprobantesTransf.length; i++) {
        if (comprobantesTransf[i].size > 10 * 1024 * 1024) {
            mostrarAlerta('Error', `El archivo "${comprobantesTransf[i].name}" supera los 10MB`);
            return;
        }
    }
    
    if (tipo === 'COMPRA_PERSONAL') {
        if (!ordenPago) {
            mostrarAlerta('Error', 'Debe adjuntar la orden de pago');
            return;
        }
        
        if (ordenPago.size > 10 * 1024 * 1024) {
            mostrarAlerta('Error', 'La orden de pago supera los 10MB');
            return;
        }
        
        // Validar retenciones si existen
        if (retenciones) {
            for (let i = 0; i < retenciones.length; i++) {
                if (retenciones[i].size > 10 * 1024 * 1024) {
                    mostrarAlerta('Error', `La retención "${retenciones[i].name}" supera los 10MB`);
                    return;
                }
            }
        }
    }
    
    mostrarLoading();
    
    try {
        // 1. Subir comprobantes de transferencia (múltiples)
        for (let i = 0; i < comprobantesTransf.length; i++) {
            const tipoArchivo = `COMPROBANTE_TRANSFERENCIA_${i + 1}`;
            await subirArchivo(idSolicitud, comprobantesTransf[i], tipoArchivo);
        }
        
        if (tipo === 'COMPRA_PERSONAL') {
            // Subir orden de pago
            await subirOrdenPago(idSolicitud, ordenPago);
            
            // Subir retenciones (múltiples) si existen
            if (retenciones && retenciones.length > 0) {
                for (let i = 0; i < retenciones.length; i++) {
                    const tipoArchivo = `RETENCION_${i + 1}`;
                    await subirArchivo(idSolicitud, retenciones[i], tipoArchivo);
                }
            }
        }
        
        // 2. Cambiar estado a PAGADO
        const formData = new FormData();
        formData.append('accion', 'actualizar_estado');
        formData.append('id_solicitud', idSolicitud);
        formData.append('estado', 'PAGADO');
        formData.append('usuario', 'TESORERIA');
        formData.append('observaciones', observaciones || 'Pago realizado');
        
        const response = await fetch('controller/solicitud_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('modalAdjuntarComprobantes')).hide();
            
            mostrarAlerta('Éxito', 'Pago registrado correctamente');
            
            // Recargar listados
            cargarFacturasListas();
            cargarHistorialPagadas();
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo registrar el pago: ' + error.message);
    } finally {
        ocultarLoading();
    }
}

/**
 * Sube la orden de pago
 */
async function subirOrdenPago(idSolicitud, archivo) {
    return subirArchivo(idSolicitud, archivo, 'ORDEN_PAGO');
}

/**
 * Función genérica para subir archivos
 */
async function subirArchivo(idSolicitud, archivo, tipoArchivo) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = async function(e) {
            try {
                const formData = new FormData();
                formData.append('accion', 'subir');
                formData.append('id_solicitud', idSolicitud);
                formData.append('tipo_archivo', tipoArchivo);
                formData.append('nombre_archivo', archivo.name);
                formData.append('archivo_base64', e.target.result.split(',')[1]);
                formData.append('mime_type', archivo.type);
                
                const response = await fetch('controller/archivo_controller.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resolve(result);
                } else {
                    reject(new Error(result.message));
                }
            } catch (error) {
                reject(error);
            }
        };
        reader.readAsDataURL(archivo);
    });
}

// Cargar facturas al iniciar la página y al cambiar de pestaña
document.addEventListener('DOMContentLoaded', function() {
    cargarFacturasListas();
    cargarHistorialPagadas();
    
    // Recargar al cambiar de pestaña
    document.getElementById('retiros-tab')?.addEventListener('shown.bs.tab', function() {
        cargarFacturasListas();
        cargarHistorialPagadas();
    });
});

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

// Exportar funciones para uso global
window.verImagenCompleta = verImagenCompleta;
window.verHistorialEstados = verHistorialEstados;
window.abrirModalPago = abrirModalPago;
window.guardarComprobantesPago = guardarComprobantesPago;
window.verArchivosFactura = verArchivosFactura;
window.descargarArchivo = descargarArchivo;