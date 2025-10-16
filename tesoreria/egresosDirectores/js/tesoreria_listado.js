/**
 * Gestión del listado de facturas listas para Tesorería
 */

let archivosComprobantes = {
    comprobanteTransferencia: null,
    ordenPago: null,
    retenciones: []
};

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
 * Abre el modal para adjuntar comprobantes de pago
 */
function abrirModalPago(idSolicitud, tipo) {
    document.getElementById('idSolicitudPago').value = idSolicitud;
    document.getElementById('tipoSolicitudPago').value = tipo;
    
    // Limpiar archivos previos
    archivosComprobantes = {
        comprobanteTransferencia: null,
        ordenPago: null,
        retenciones: []
    };
    
    document.getElementById('comprobanteTransferencia').value = '';
    document.getElementById('ordenPago').value = '';
    document.getElementById('retenciones').value = '';
    document.getElementById('observacionesPago').value = '';
    
    // Mostrar/ocultar campos según el tipo
    const comprobantesExtra = document.getElementById('comprobantesExtra');
    const infoComprobantes = document.getElementById('infoComprobantes');
    
    if (tipo === 'COMPRA_PERSONAL') {
        comprobantesExtra.classList.remove('d-none');
        document.getElementById('ordenPago').required = true;
        document.getElementById('retenciones').required = true;
        
        infoComprobantes.innerHTML = `
            <strong>Compra Personal:</strong> Debes adjuntar:
            <ul class="mb-0">
                <li>Comprobante de transferencia</li>
                <li>Orden de pago</li>
                <li>Retenciones (puedes adjuntar varios)</li>
            </ul>
        `;
    } else {
        comprobantesExtra.classList.add('d-none');
        document.getElementById('ordenPago').required = false;
        document.getElementById('retenciones').required = false;
        
        infoComprobantes.innerHTML = `
            <strong>Retiro de Dinero:</strong> Solo debes adjuntar el comprobante de transferencia.
        `;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('modalAdjuntarComprobantes'));
    modal.show();
}

/**
 * Guarda los comprobantes y marca como PAGADO
 */
async function guardarComprobantesPago() {
    const idSolicitud = document.getElementById('idSolicitudPago').value;
    const tipo = document.getElementById('tipoSolicitudPago').value;
    const observaciones = document.getElementById('observacionesPago').value;
    
    // Validar que se hayan cargado los archivos
    const comprobanteTransf = document.getElementById('comprobanteTransferencia').files[0];
    if (!comprobanteTransf) {
        mostrarAlerta('Error', 'Debe adjuntar el comprobante de transferencia');
        return;
    }
    
    if (tipo === 'COMPRA_PERSONAL') {
        const ordenPago = document.getElementById('ordenPago').files[0];
        const retenciones = document.getElementById('retenciones').files;
        
        if (!ordenPago) {
            mostrarAlerta('Error', 'Debe adjuntar la orden de pago');
            return;
        }
        
        if (retenciones.length === 0) {
            mostrarAlerta('Error', 'Debe adjuntar las retenciones');
            return;
        }
    }
    
    mostrarLoading();
    
    try {
        // 1. Subir archivos
        await subirComprobanteTransferencia(idSolicitud, comprobanteTransf);
        
        if (tipo === 'COMPRA_PERSONAL') {
            const ordenPago = document.getElementById('ordenPago').files[0];
            const retenciones = document.getElementById('retenciones').files;
            
            await subirOrdenPago(idSolicitud, ordenPago);
            
            for (let i = 0; i < retenciones.length; i++) {
                await subirRetencion(idSolicitud, retenciones[i]);
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
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo registrar el pago');
    } finally {
        ocultarLoading();
    }
}

/**
 * Sube el comprobante de transferencia
 */
async function subirComprobanteTransferencia(idSolicitud, archivo) {
    return subirArchivo(idSolicitud, archivo, 'COMPROBANTE_TRANSFERENCIA');
}

/**
 * Sube la orden de pago
 */
async function subirOrdenPago(idSolicitud, archivo) {
    return subirArchivo(idSolicitud, archivo, 'ORDEN_PAGO');
}

/**
 * Sube una retención
 */
async function subirRetencion(idSolicitud, archivo) {
    return subirArchivo(idSolicitud, archivo, 'RETENCION');
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
    
    // Recargar al cambiar de pestaña
    document.getElementById('retiros-tab')?.addEventListener('shown.bs.tab', function() {
        cargarFacturasListas();
    });
});

// Exportar funciones para uso global
window.verImagenCompleta = verImagenCompleta;