// /novedades/js/dashboard_index.js

/**
 * JavaScript específico para la página principal (dashboard)
 */

/**
 * Cargar estadísticas del dashboard
 */
async function cargarEstadisticas() {
    try {
        // Obtener novedades del período actual
        const novedades = await NovedadesApp.request('get_novedades');
        
        // Actualizar contadores
        document.getElementById('total-novedades').textContent = novedades.length;
        
        // Simular pendientes (en una implementación real vendría del backend)
        const pendientes = novedades.filter(n => !n.revisado).length;
        document.getElementById('novedades-pendientes').textContent = pendientes || 0;

        // Obtener sucursales
        const sucursales = await NovedadesApp.request('get_sucursales');
        document.getElementById('total-sucursales').textContent = sucursales.length;

        // Mostrar últimas novedades
        mostrarUltimasNovedades(novedades.slice(0, 5));

    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        // Mostrar valores por defecto en caso de error
        document.getElementById('total-novedades').textContent = '0';
        document.getElementById('novedades-pendientes').textContent = '0';
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
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    novedades.forEach(novedad => {
        const fechaCreacion = NovedadesApp.formatearFecha(novedad.fecha_creacion);
        const estadoBadge = getEstadoBadge(novedad);
        
        html += `
            <tr class="novedad-row" data-id="${novedad.id}">
                <td>
                    <strong>${novedad.nombre} ${novedad.apellido}</strong><br>
                    <small class="text-muted">Legajo: ${novedad.legajo}</small>
                </td>
                <td>
                    <span class="badge bg-primary">${novedad.tipo_descripcion}</span>
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
    try {
        const novedad = await NovedadesApp.request('get_novedad', { id });
        mostrarModalDetalle(novedad);
    } catch (error) {
        NovedadesApp.mostrarError('Error al obtener los detalles de la novedad');
    }
}

/**
 * Mostrar modal con detalle de novedad
 */
function mostrarModalDetalle(novedad) {
    const modalHtml = `
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
                                <h6 class="text-primary">Información del Empleado</h6>
                                <p><strong>Nombre:</strong> ${novedad.nombre} ${novedad.apellido}</p>
                                <p><strong>Legajo:</strong> ${novedad.legajo}</p>
                                <p><strong>Sucursal:</strong> ${novedad.sucursal}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary">Información de la Novedad</h6>
                                <p><strong>Tipo:</strong> ${novedad.tipo_descripcion}</p>
                                <p><strong>Fecha de Registro:</strong> ${NovedadesApp.formatearFecha(novedad.fecha_creacion)}</p>
                                ${novedad.fecha_vigencia ? `<p><strong>Fecha de Vigencia:</strong> ${NovedadesApp.formatearFecha(novedad.fecha_vigencia)}</p>` : ''}
                            </div>
                        </div>
                        
                        ${novedad.valor_numerico ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-primary">Valor</h6>
                                <p class="h5 text-success">${NovedadesApp.formatearValor(novedad.valor_numerico)}</p>
                            </div>
                        </div>` : ''}
                        
                        ${novedad.fecha_permiso ? `
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6 class="text-primary">Información del Permiso</h6>
                                <p><strong>Fecha:</strong> ${NovedadesApp.formatearFecha(novedad.fecha_permiso)}</p>
                                <p><strong>Tipo:</strong> ${novedad.tipo_permiso}</p>
                                <p><strong>Compensa:</strong> ${novedad.compensa ? 'Sí' : 'No'}</p>
                            </div>
                        </div>` : ''}
                        
                        ${novedad.observaciones ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-primary">Observaciones</h6>
                                <p class="border p-3 rounded bg-light">${novedad.observaciones}</p>
                            </div>
                        </div>` : ''}
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

    // Remover modal existente si existe
    const modalExistente = document.getElementById('modalDetalleNovedad');
    if (modalExistente) {
        modalExistente.remove();
    }

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
    // Cargar estadísticas al cargar la página
    cargarEstadisticas();
    
    // Actualizar estadísticas cada 30 segundos
    setInterval(cargarEstadisticas, 30000);
    
    console.log('Dashboard - Inicializado correctamente');
});