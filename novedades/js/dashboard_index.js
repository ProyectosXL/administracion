// /novedades/js/dashboard_index.js

/**
 * JavaScript específico para la página principal (dashboard)
 */

/**
 * Cargar estadísticas del dashboard
 */
async function cargarEstadisticas() {
    try {
        // Obtener TODAS las novedades
        const todasNovedades = await NovedadesApp.request('get_all_novedades');
        document.getElementById('total-novedades-todas').textContent = todasNovedades.length;
        
        // Obtener novedades del período actual solamente
        const novedadesPeriodo = await NovedadesApp.request('get_novedades');
        document.getElementById('novedades-periodo').textContent = novedadesPeriodo.length;

        // Obtener sucursales
        const sucursales = await NovedadesApp.request('get_sucursales');
        document.getElementById('total-sucursales').textContent = sucursales.length;

        // Mostrar últimas novedades (usar todas las novedades para mostrar variedad)
        mostrarUltimasNovedades(todasNovedades.slice(0, 5));

    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        // Mostrar valores por defecto en caso de error
        document.getElementById('total-novedades-todas').textContent = '0';
        document.getElementById('novedades-periodo').textContent = '0';
        document.getElementById('total-sucursales').textContent = '0';
    }
}

/**
 * Mostrar las últimas novedades en el dashboard
 */
function mostrarUltimasNovedades(novedades) {
    const container = document.getElementById('ultimas-novedades-container');
    
    if (!novedades || novedades.length === 0) {
        container.innerHTML = `
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <h6>No hay novedades registradas</h6>
                <p>Aún no se han registrado novedades para este período.</p>
                <a href="nueva_novedad.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    Registrar Primera Novedad
                </a>
            </div>
        `;
        return;
    }

    let html = '<div class="table-responsive">';
    html += `
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Tipo de Novedad</th>
                    <th>Sucursal</th>
                    <th>Fecha de registro</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    novedades.forEach(novedad => {
        const fechaCreacion = NovedadesApp.formatearFecha(novedad.fecha_creacion);
        const estadoBadge = getEstadoBadge(novedad);
        
        // Obtener información específica según tipo de novedad
        let tipoDetalle = '';
        const tipo = parseInt(novedad.tipo_novedad);
        
        switch(tipo) {
            case 1: // Cambio de sucursal
                // Extraer nueva sucursal de las observaciones
                if (novedad.observaciones) {
                    let match = novedad.observaciones.match(/Nueva sucursal:\s*<[^>]*>([^<]+)<[^>]*>/);
                    if (match) {
                        tipoDetalle = `<br><small class="text-info">→ ${match[1].trim()}</small>`;
                    } else {
                        match = novedad.observaciones.match(/Nueva sucursal:\s*([^-<\n]+)/);
                        if (match) {
                            tipoDetalle = `<br><small class="text-info">→ ${match[1].trim()}</small>`;
                        }
                    }
                }
                break;
                
            case 2: // Cambio de puesto
                // Extraer nuevo puesto de las observaciones
                if (novedad.observaciones) {
                    let match = novedad.observaciones.match(/Nuevo puesto:\s*<[^>]*>([^<]+)<[^>]*>/);
                    if (match) {
                        tipoDetalle = `<br><small class="text-success">→ ${match[1].trim()}</small>`;
                    } else {
                        match = novedad.observaciones.match(/Nuevo puesto:\s*([^-<\n]+)/);
                        if (match) {
                            tipoDetalle = `<br><small class="text-success">→ ${match[1].trim()}</small>`;
                        }
                    }
                }
                break;
                
            case 3: case 4: // Nuevo salario / Ajuste premios
                if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0) {
                    const valor = NovedadesApp.formatearValor ? 
                        NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : 
                        `$${parseFloat(novedad.valor_numerico).toLocaleString()}`;
                    tipoDetalle = `<br><small class="text-warning">${valor}</small>`;
                }
                break;
        }
        
        html += `
            <tr class="novedad-row" data-id="${novedad.id}">
                <td>
                    <strong>${novedad.nombre} ${novedad.apellido}</strong><br>
                    <small class="text-muted">Legajo: ${novedad.legajo}</small>
                </td>
                <td>
                    <span class="badge bg-primary">${novedad.tipo_descripcion}</span>${tipoDetalle}
                </td>
                <td>${novedad.nombre_sucursal || novedad.sucursal}</td>
                <td>
                    <small>${fechaCreacion}</small>
                </td>
                <td>${estadoBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="verDetalleNovedad(${novedad.id})" 
                            title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

/**
 * Obtener badge de estado para una novedad
 */
function getEstadoBadge(novedad) {
    // En una implementación real, el estado vendría del backend
    const estados = ['Registrada', 'En Revisión', 'Aprobada', 'Procesada'];
    const estado = estados[Math.floor(Math.random() * estados.length)];
    
    const clases = {
        'Registrada': 'bg-info',
        'En Revisión': 'bg-warning',
        'Aprobada': 'bg-success',
        'Procesada': 'bg-secondary'
    };

    return `<span class="badge ${clases[estado]}">${estado}</span>`;
}

/**
 * Ver detalle de una novedad
 */
async function verDetalleNovedad(id) {
    console.log('🔍 Ver detalle novedad ID:', id);
    try {
        console.log('📡 Solicitando datos de novedad...');
        const novedad = await NovedadesApp.request('get_novedad', { id });
        console.log('📋 Datos recibidos:', novedad);
        mostrarModalDetalle(novedad);
    } catch (error) {
        console.error('❌ Error en verDetalleNovedad:', error);
        NovedadesApp.mostrarError('Error al obtener los detalles de la novedad');
    }
}

/**
 * Mostrar modal con detalle de novedad - VERSIÓN MEJORADA
 */
function mostrarModalDetalle(novedad) {
    // Remover modal existente si existe
    const modalExistente = document.getElementById('modalDetalleNovedad');
    if (modalExistente) {
        modalExistente.remove();
    }

    let modalHtml = `
        <div class="modal fade" id="modalDetalleNovedad" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-info-circle me-2"></i>
                            Detalle de Novedad
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Información del Empleado</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <tr><td><strong>Nombre:</strong></td><td>${novedad.nombre} ${novedad.apellido}</td></tr>
                                            <tr><td><strong>Legajo:</strong></td><td>${novedad.legajo}</td></tr>
                                            <tr><td><strong>Sucursal:</strong></td><td>${novedad.nombre_sucursal || 'Sucursal ' + novedad.sucursal}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información de la Novedad</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <tr><td><strong>Tipo:</strong></td><td><span class="badge bg-primary">${novedad.tipo_descripcion}</span></td></tr>
                                            <tr><td><strong>Período:</strong></td><td>${(novedad.periodo_mes && novedad.periodo_anio) ? novedad.periodo_mes + '/' + novedad.periodo_anio : 'Período actual'}</td></tr>
                                            <tr><td><strong>Fecha Registro:</strong></td><td>${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(novedad.fecha_creacion) : novedad.fecha_creacion}</td></tr>
                                            ${novedad.fecha_vigencia ? `<tr><td><strong>Fecha Vigencia:</strong></td><td>${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : novedad.fecha_vigencia}</td></tr>` : ''}
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>`;

    // Información específica según el tipo de novedad
    const tipo = parseInt(novedad.tipo_novedad);
    
    modalHtml += `<div class="row mt-3">`;
    
    switch (tipo) {
        case 1: // Cambio de sucursal
            modalHtml += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-building me-2"></i>Detalles del Cambio de Sucursal</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Sucursal Actual:</strong><br>
                                    <span class="badge bg-secondary">${novedad.nombre_sucursal || 'Sucursal ' + novedad.sucursal}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-6">
                                    <strong>Fecha de Vigencia:</strong><br>
                                    ${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : novedad.fecha_vigencia}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
            break;
            
        case 2: // Nuevo puesto
            modalHtml += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Detalles del Nuevo Puesto</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Nuevo Puesto:</strong><br>
                                    <span class="badge bg-success">${novedad.puesto || 'No especificado'}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-6">
                                    <strong>Fecha de Vigencia:</strong><br>
                                    ${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : novedad.fecha_vigencia}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
            break;
            
        case 3: // Nuevo salario neto
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Nuevo Salario Neto</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : novedad.valor_numerico}</h3>
                            </div>
                        </div>
                    </div>`;
            }
            break;
            
        case 4: // Ajuste de premios
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-trophy me-2"></i>Ajuste de Premios</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : novedad.valor_numerico}</h3>
                            </div>
                        </div>
                    </div>`;
            }
            break;
            
        case 5: // Horas extras
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Horas Extras</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'horas') : novedad.valor_numerico + ' hs'}</h3>
                            </div>
                        </div>
                    </div>`;
            }
            break;
            
        case 6: // Horas adicionales
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Horas Adicionales</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'horas') : novedad.valor_numerico + ' hs'}</h3>
                            </div>
                        </div>
                    </div>`;
            }
            break;
            
        case 8: // Cortes
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="fas fa-cut me-2"></i>Cortes</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'cortes') : novedad.valor_numerico + ' cortes'}</h3>
                            </div>
                        </div>
                    </div>`;
            }
            break;
            
        case 9: // Producción 25%
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Producción 25%</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'unidades') : novedad.valor_numerico + ' unidades'}</h3>
                            </div>
                        </div>
                    </div>`;
            }
            break;
            
        case 10: // Producción 50%
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Producción 50%</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'unidades') : novedad.valor_numerico + ' unidades'}</h3>
                            </div>
                        </div>
                    </div>`;
            }
            break;
            
        case 11: // Producción 100%
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Producción 100%</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'unidades') : novedad.valor_numerico + ' unidades'}</h3>
                            </div>
                        </div>
                    </div>`;
            }
            break;
    }
    
    modalHtml += `</div>`;

    // Información de permiso (solo para tipo 7)
    if (novedad.fecha_permiso) {
        modalHtml += `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-calendar-times me-2"></i>Información del Permiso</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Fecha:</strong><br>
                                    ${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(novedad.fecha_permiso) : novedad.fecha_permiso}
                                </div>
                                <div class="col-md-6">
                                    <strong>Compensa:</strong><br>
                                    <span class="badge ${novedad.compensa ? 'bg-success' : 'bg-danger'}">
                                        ${novedad.compensa ? 'Sí' : 'No'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    if (novedad.observaciones) {
        modalHtml += `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-comment me-2"></i>Observaciones</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">${novedad.observaciones}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    modalHtml += `
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalDetalleNovedad'));
    modal.show();

    // Limpiar modal del DOM cuando se oculte
    document.getElementById('modalDetalleNovedad').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

/**
 * Inicialización específica del dashboard
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Dashboard iniciando...');
    console.log('📋 Función mostrarModalDetalle disponible:', typeof mostrarModalDetalle === 'function');
    console.log('🔧 NovedadesApp disponible:', typeof NovedadesApp === 'object');
    
    // Cargar estadísticas al cargar la página
    cargarEstadisticas();
    
    // Actualizar estadísticas cada 30 segundos
    setInterval(cargarEstadisticas, 30000);
    
    console.log('✅ Dashboard - Inicializado correctamente');
});