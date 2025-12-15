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

        // Obtener centros de costos
        const centrosCostos = await NovedadesApp.request('get_centros_costos');
        document.getElementById('total-centros-costos').textContent = centrosCostos.length;

        // Mostrar últimas novedades (usar todas las novedades para mostrar variedad)
        mostrarUltimasNovedades(todasNovedades.slice(0, 5));

    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        // Mostrar valores por defecto en caso de error
        document.getElementById('total-novedades-todas').textContent = '0';
        document.getElementById('novedades-periodo').textContent = '0';
        document.getElementById('total-centros-costos').textContent = '0';
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
                    <th>Centro de Costos</th>
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
                // Extraer nueva posición de las observaciones
                if (novedad.observaciones) {
                    let match = novedad.observaciones.match(/Nueva Posición:\s*<[^>]*>([^<]+)<[^>]*>/);
                    if (match) {
                        tipoDetalle = `<br><small class="text-success">→ ${match[1].trim()}</small>`;
                    } else {
                        match = novedad.observaciones.match(/Nueva Posición:\s*([^-<\n]+)/);
                        if (match) {
                            tipoDetalle = `<br><small class="text-success">→ ${match[1].trim()}</small>`;
                        } else {
                            // Mantener compatibilidad con formato anterior
                            match = novedad.observaciones.match(/Nuevo puesto:\s*<[^>]*>([^<]+)<[^>]*>/);
                            if (match) {
                                tipoDetalle = `<br><small class="text-success">→ ${match[1].trim()}</small>`;
                            } else {
                                match = novedad.observaciones.match(/Nuevo puesto:\s*([^-<\n]+)/);
                                if (match) {
                                    tipoDetalle = `<br><small class="text-success">→ ${match[1].trim()}</small>`;
                                }
                            }
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

            case 53: // Reemplazo - siempre temporario
                const puestoReemplazo = novedad.puesto || 'No especificado';
                tipoDetalle = `<br><small class="text-info"><i class="fas fa-clock text-warning"></i> ${puestoReemplazo}</small>`;
                break;

            case 55: // A prueba - siempre temporario
                const puestoAPrueba = novedad.puesto || 'No especificado';
                tipoDetalle = `<br><small class="text-info"><i class="fas fa-user-clock text-warning"></i> ${puestoAPrueba}</small>`;
                break;

            case 54: // Aumento Salarial
                // Detectar si es porcentaje o monto
                const tienePorcentaje = novedad.porcentaje_1 && parseFloat(novedad.porcentaje_1) > 0;
                if (tienePorcentaje) {
                    // Multiplicar por 100 para mostrar como porcentaje
                    const porcentaje = (parseFloat(novedad.porcentaje_1) * 100).toFixed(2);
                    tipoDetalle = `<br><small class="text-primary"><i class="fas fa-percentage"></i> ${porcentaje}%</small>`;
                } else if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0) {
                    const monto = NovedadesApp.formatearValor ? 
                        NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : 
                        `$${parseFloat(novedad.valor_numerico).toLocaleString()}`;
                    tipoDetalle = `<br><small class="text-success"><i class="fas fa-dollar-sign"></i> ${monto}</small>`;
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
 * Obtener badge de estado para una novedad basado en el valor real del estado
 */
function getEstadoBadge(novedad) {
    const estados = {
        1: { texto: 'Enviada', clase: 'bg-info text-white' },
        2: { texto: 'En Revisión', clase: 'bg-warning text-dark' },
        3: { texto: 'Aprobada', clase: 'bg-success text-white' },
        4: { texto: 'A Revisar', clase: 'bg-danger text-white' },
        5: { texto: 'Procesada', clase: 'bg-secondary text-white' }
    };
    
    // Usar estado_numero si está disponible, sino intentar parsear estado string
    const estadoNumero = novedad.estado_numero || parseInt(novedad.estado) || 1;
    const estado = estados[estadoNumero] || { texto: 'Estado Desconocido', clase: 'bg-light text-dark' };
    
    return `<span class="badge ${estado.clase}">${estado.texto}</span>`;
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
                                            <tr><td><strong>Centro de Costos:</strong></td><td>${novedad.nombre_sucursal || 'Sucursal ' + novedad.sucursal}</td></tr>
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
            // Para cambio de sucursal, obtener información de sucursales
            let sucursalActualDetalle = 'Cargando...';
            let nuevaSucursalDetalle = 'No especificado';
            
            // Obtener sucursal actual del centro de costos del empleado
            if (novedad.codigo_centro_costos) {
                // Usar una función async para obtener la sucursal
                obtenerSucursalPorCentroCostos(novedad.codigo_centro_costos).then(sucursal => {
                    if (sucursal) {
                        // Actualizar el elemento después de cargar
                        const elemento = document.querySelector(`[data-novedad-dashboard-id="${novedad.id}"] .sucursal-actual-dashboard-text`);
                        if (elemento) {
                            elemento.textContent = sucursal;
                        }
                    }
                }).catch(error => {
                    console.error('Error obteniendo sucursal actual:', error);
                    const elemento = document.querySelector(`[data-novedad-dashboard-id="${novedad.id}"] .sucursal-actual-dashboard-text`);
                    if (elemento) {
                        elemento.textContent = `${novedad.descripcion_centro_costos || novedad.codigo_centro_costos} (Centro de Costos)`;
                    }
                });
                
                // Mostrar temporalmente el centro de costos
                sucursalActualDetalle = `${novedad.descripcion_centro_costos || novedad.codigo_centro_costos} (Centro de Costos)`;
            }
            
            // Extraer nueva sucursal de las observaciones
            if (novedad.observaciones) {
                // Buscar patrón de nueva sucursal en observaciones
                let match = novedad.observaciones.match(/Nueva sucursal:\s*<[^>]*>([^<]+)<[^>]*>/);
                if (match) {
                    nuevaSucursalDetalle = match[1].trim();
                } else {
                    match = novedad.observaciones.match(/Nueva sucursal:\s*([^-<\n]+)/);
                    if (match) {
                        nuevaSucursalDetalle = match[1].trim();
                    } else {
                        // Fallback: buscar patrón antiguo de centro de costos
                        match = novedad.observaciones.match(/Nuevo centro de costos:\s*<[^>]*>([^<]+)<[^>]*>/);
                        if (match) {
                            nuevaSucursalDetalle = match[1].trim();
                        } else {
                            match = novedad.observaciones.match(/Nuevo centro de costos:\s*([^-<\n]+)/);
                            if (match) {
                                nuevaSucursalDetalle = match[1].trim();
                            }
                        }
                    }
                }
            }
            
            modalHtml += `
                <div class="col-12" data-novedad-dashboard-id="${novedad.id}">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-building me-2"></i>Detalles del Cambio de Sucursal</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Sucursal Actual:</strong><br>
                                    <span class="badge bg-secondary sucursal-actual-dashboard-text">${sucursalActualDetalle}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Nueva Sucursal:</strong><br>
                                    <span class="badge bg-primary">${nuevaSucursalDetalle}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-4">
                                    <strong>Fecha de Vigencia:</strong><br>
                                    ${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : novedad.fecha_vigencia}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
            break;
            
        case 2: // Nueva Posición
            modalHtml += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Detalles de la Nueva Posición</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Nueva Posición:</strong><br>
                                    <span class="badge bg-success">${novedad.puesto || 'No especificado'}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-6">
                                    <strong>Fecha de Inicio:</strong><br>
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

        case 12: // Plus de caja (compatibilidad)
        case 32: // Plus de caja
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-cash-register me-2"></i>Plus de Caja</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico}</h3>
                            </div>
                        </div>
                    </div>`;
            }
            break;

        case 13: // Plus de Sub-Encargada (compatibilidad)
        case 33: // Plus de Sub-Encargada
            modalHtml += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i>Plus de Sub-Encargada</h6>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <strong>Tipo de Plus:</strong><br>
                                    <span class="badge ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0 ? 'bg-success' : 'bg-info'}">
                                        ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0 ? 'Con importe fijo' : 'Sin importe fijo'}
                                    </span>
                                </div>
                                ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0 ? `
                                <div class="col-md-6 text-center">
                                    <strong>Importe:</strong><br>
                                    <h4 class="text-success">${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico}</h4>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
            break;

        case 14: // Plus de Encargada (compatibilidad)
        case 34: // Plus de Encargada
            modalHtml += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-user-cog me-2"></i>Plus de Encargada</h6>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <strong>Tipo de Plus:</strong><br>
                                    <span class="badge ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0 ? 'bg-success' : 'bg-info'}">
                                        ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0 ? 'Con importe fijo' : 'Sin importe fijo'}
                                    </span>
                                </div>
                                ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0 ? `
                                <div class="col-md-6 text-center">
                                    <strong>Importe:</strong><br>
                                    <h4 class="text-primary">${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico}</h4>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
            break;

        case 15: // Premio Local (compatibilidad)
        case 35: // Premio Local
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-trophy me-2"></i>Premio Local</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 text-center">
                                        <strong>Importe del Premio:</strong><br>
                                        <h4 class="text-warning">${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico}</h4>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Aplicable a:</strong><br>
                                        <div class="mt-2">
                                            ${(Boolean(novedad.aplica_vendedora) && novedad.aplica_vendedora !== 0 && novedad.aplica_vendedora !== '0') ? '<span class="badge bg-success me-1"><i class="fas fa-check me-1"></i>Vendedora</span>' : '<span class="badge bg-secondary me-1"><i class="fas fa-times me-1"></i>Vendedora</span>'}
                                            ${(Boolean(novedad.aplica_sub_encargada) && novedad.aplica_sub_encargada !== 0 && novedad.aplica_sub_encargada !== '0') ? '<span class="badge bg-success me-1"><i class="fas fa-check me-1"></i>Sub-Encargada</span>' : '<span class="badge bg-secondary me-1"><i class="fas fa-times me-1"></i>Sub-Encargada</span>'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
            }
            break;

        case 16: // Comisión Individual (compatibilidad)
        case 36: // Comisión Individual
            modalHtml += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-percent me-2"></i>Comisión Individual</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Tipo de Comisión:</strong><br>
                                    <span class="badge bg-info">Individual</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Configuración:</strong><br>
                                    <span class="badge ${novedad.tiene_tope ? 'bg-warning text-dark' : 'bg-success'}">
                                        ${novedad.tiene_tope ? 'Con tope' : 'Sin tope'}
                                    </span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Porcentaje${novedad.tiene_tope ? 's' : ''}:</strong><br>
                                    ${novedad.tiene_tope ? 
                                        `<span class="badge bg-primary">${parseFloat(novedad.porcentaje_1 || 0)}%</span> / 
                                         <span class="badge bg-warning text-dark">${parseFloat(novedad.porcentaje_2 || 0)}%</span>` :
                                        `<span class="badge bg-primary">${parseFloat(novedad.porcentaje_1 || 0)}%</span>`
                                    }
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            break;

        case 17: // Comisión sobre Local (compatibilidad)
        case 37: // Comisión sobre Local
            modalHtml += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-purple text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <h6 class="mb-0"><i class="fas fa-store me-2"></i>Comisión sobre Local</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Tipo de Comisión:</strong><br>
                                    <span class="badge" style="background: #667eea;">Sobre Local</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Configuración:</strong><br>
                                    <span class="badge ${novedad.tiene_tope ? 'bg-warning text-dark' : 'bg-success'}">
                                        ${novedad.tiene_tope ? 'Con tope' : 'Sin tope'}
                                    </span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Porcentaje${novedad.tiene_tope ? 's' : ''}:</strong><br>
                                    ${novedad.tiene_tope ? 
                                        `<span class="badge bg-primary">${parseFloat(novedad.porcentaje_1 || 0)}%</span> / 
                                         <span class="badge bg-warning text-dark">${parseFloat(novedad.porcentaje_2 || 0)}%</span>` :
                                        `<span class="badge bg-primary">${parseFloat(novedad.porcentaje_1 || 0)}%</span>`
                                    }
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            break;

        case 18: // Premios - Ajuste General (compatibilidad)
        case 38: // Premios - Ajuste General
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                modalHtml += `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="fas fa-trophy me-2"></i>Premios - Ajuste General</h6>
                            </div>
                            <div class="card-body text-center">
                                <h3>${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico}</h3>
                                <p class="text-muted mb-0">Ajuste general de premios</p>
                            </div>
                        </div>
                    </div>`;
            }
            break;

        case 53: // Reemplazo - siempre temporario
            // IMPORTANTE: usar tipo_nuevo_puesto no tipo_reemplazo
            
            // Manejar fecha_vigencia_hasta como objeto DateTime si es necesario
            let fechaFinReemplazo = '';
            if (novedad.fecha_vigencia_hasta) {
                if (typeof novedad.fecha_vigencia_hasta === 'object' && novedad.fecha_vigencia_hasta.date) {
                    fechaFinReemplazo = novedad.fecha_vigencia_hasta.date.split(' ')[0];
                } else if (typeof novedad.fecha_vigencia_hasta === 'string') {
                    fechaFinReemplazo = novedad.fecha_vigencia_hasta.split(' ')[0];
                }
            }
            
            modalHtml += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h6 class="mb-0"><i class="fas fa-people-arrows me-2"></i>Detalles del Reemplazo</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Puesto de Reemplazo:</strong><br>
                                    <span class="badge" style="background: #f5576c;">${novedad.puesto || 'No especificado'}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-6">
                                    <strong>Fecha de Inicio:</strong><br>
                                    ${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : novedad.fecha_vigencia}
                                </div>
                                ` : ''}
                            </div>
                            ${fechaFinReemplazo ? `
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-calendar-times me-2"></i>
                                        <strong>Fecha de Finalización:</strong> ${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(fechaFinReemplazo) : fechaFinReemplazo}
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>`;
            break;

        case 55: // A prueba - siempre temporario
            // IMPORTANTE: usar tipo_nuevo_puesto no tipo_reemplazo
            
            // Manejar fecha_vigencia_hasta como objeto DateTime si es necesario
            let fechaFinAPrueba = '';
            if (novedad.fecha_vigencia_hasta) {
                if (typeof novedad.fecha_vigencia_hasta === 'object' && novedad.fecha_vigencia_hasta.date) {
                    fechaFinAPrueba = novedad.fecha_vigencia_hasta.date.split(' ')[0];
                } else if (typeof novedad.fecha_vigencia_hasta === 'string') {
                    fechaFinAPrueba = novedad.fecha_vigencia_hasta.split(' ')[0];
                }
            }
            
            modalHtml += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                            <h6 class="mb-0"><i class="fas fa-user-clock me-2"></i>Detalles de prueba</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Puesto de prueba:</strong><br>
                                    <span class="badge" style="background: #fed6e3; color: #333;">${novedad.puesto || 'No especificado'}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-6">
                                    <strong>Fecha de Inicio:</strong><br>
                                    ${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : novedad.fecha_vigencia}
                                </div>
                                ` : ''}
                            </div>
                            ${fechaFinAPrueba ? `
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        <strong>Fecha de Finalización:</strong> ${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(fechaFinAPrueba) : fechaFinAPrueba}
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>`;
            break;

        case 54: // Aumento Salarial
            // Detectar tipo de aumento basándose en qué campo tiene valor
            const tienePorcentaje = novedad.porcentaje_1 && parseFloat(novedad.porcentaje_1) > 0;
            const tieneMonto = novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0;
            const tipoAumento = tienePorcentaje ? 'porcentaje' : 'monto';
            const iconoAumento = tienePorcentaje ? 'fa-percentage text-primary' : 'fa-dollar-sign text-success';
            
            // IMPORTANTE: El porcentaje se guarda como decimal (0.21), multiplicar por 100 para mostrar (21%)
            const porcentajeDisplay = tienePorcentaje ? (parseFloat(novedad.porcentaje_1) * 100).toFixed(2) : '';
            
            modalHtml += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Detalles del Aumento Salarial</h6>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <strong>Tipo de Aumento:</strong><br>
                                    <span class="badge ${tienePorcentaje ? 'bg-primary' : 'bg-success'}">
                                        <i class="fas ${iconoAumento} me-1"></i>
                                        ${tienePorcentaje ? 'Porcentaje' : 'Monto Fijo'}
                                    </span>
                                </div>
                                <div class="col-md-4 text-center">
                                    <strong>Valor del Aumento:</strong><br>
                                    <h4 class="${tienePorcentaje ? 'text-primary' : 'text-success'}">
                                        ${tienePorcentaje ? 
                                            `${porcentajeDisplay}%` : 
                                            (NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico)
                                        }
                                    </h4>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-4">
                                    <strong>Fecha de Vigencia:</strong><br>
                                    ${NovedadesApp.formatearFecha ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : novedad.fecha_vigencia}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
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

/**
 * Obtener sucursal asociada a un centro de costos
 */
async function obtenerSucursalPorCentroCostos(codigoCentroCostos) {
    try {
        const response = await NovedadesApp.request('get_sucursal_por_centro_costos', { codigo: codigoCentroCostos });
        return response;
    } catch (error) {
        console.error('Error obteniendo sucursal por centro de costos:', error);
        return null;
    }
}