// /novedades/js/consultar_novedades.js

/**
 * JavaScript específico para la consulta de novedades
 */

let novedadesData = [];
let filtrosActivos = {};
let vistaActual = 'tabla';
let paginaActual = 1;
const elementosPorPagina = 10;

// Variables para ordenamiento
let ordenActual = {
    columna: null,
    direccion: 'asc'
};

/**
 * Cargar todas las novedades
 */
async function cargarNovedades() {
    mostrarLoading(true);
    
    try {
        novedadesData = await NovedadesApp.request('get_all_novedades');
        aplicarFiltros();
        actualizarEstadisticas();
        
    } catch (error) {
        console.error('Error cargando novedades:', error);
        mostrarSinResultados();
    } finally {
        mostrarLoading(false);
    }
}

/**
 * Inicializar Select2 para búsqueda de empleados - UN SOLO CAMPO
 */
function inicializarSelect2() {
    console.log('🔄 Inicializando Select2...');
    
    // Select2 para búsqueda por empleado/legajo (campo unificado)
    $('#filtro-empleado').select2({
        theme: 'bootstrap-5',
        placeholder: 'Buscar empleado...',
        allowClear: true,
        ajax: {
            url: 'controller/novedades_controller.php?action=buscar_empleados_select2',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: params.term,
                    limit: 15
                };
            },
            processResults: function (data) {
                console.log('Respuesta del servidor para empleado:', data);
                if (data.success) {
                    return {
                        results: data.data
                    };
                }
                return { results: [] };
            },
            cache: true
        },
        minimumInputLength: 2,
        language: {
            inputTooShort: function () {
                return 'Escriba al menos 2 caracteres para buscar';
            },
            noResults: function () {
                return 'No se encontraron empleados';
            },
            searching: function () {
                return 'Buscando empleados...';
            }
        }
    });

    console.log('✅ Select2 inicializado correctamente');
}

/**
 * Aplicar filtros a los datos - CORREGIDO
 */
function aplicarFiltros() {
    // Preservar filtro de período real si existe
    const periodoRealTemp = filtrosActivos.periodoReal;
    
    // Obtener valores de los filtros usando Select2
    filtrosActivos = {
        legajo: $('#filtro-legajo').val() || '', 
        sucursal: document.getElementById('filtro-sucursal').value,
        tipo: document.getElementById('filtro-tipo').value,
        empleado: $('#filtro-empleado').val() || '',
        fechaDesde: document.getElementById('fecha-desde').value,
        fechaHasta: document.getElementById('fecha-hasta').value
    };
    
    // Restaurar filtro de período real si existía
    if (periodoRealTemp) {
        filtrosActivos.periodoReal = periodoRealTemp;
    }

    console.log('Aplicando filtros:', filtrosActivos);

    // Filtrar datos
    let datosFiltrados = novedadesData.filter(novedad => {
        // Filtro por legajo (exacto)
        if (filtrosActivos.legajo && novedad.legajo.toString() !== filtrosActivos.legajo.toString()) {
            return false;
        }
        
        // Filtro por sucursal
        if (filtrosActivos.sucursal && novedad.sucursal != filtrosActivos.sucursal) {
            return false;
        }
        
        // Filtro por tipo de novedad
        if (filtrosActivos.tipo && novedad.tipo_novedad != filtrosActivos.tipo) {
            return false;
        }
        
        // Filtro por empleado (usando el legajo del select de empleado)
        if (filtrosActivos.empleado && novedad.legajo.toString() !== filtrosActivos.empleado.toString()) {
            return false;
        }
        
        // Filtro por rango de fechas (solo si NO hay filtro de período real activo)
        if (!filtrosActivos.periodoReal && (filtrosActivos.fechaDesde || filtrosActivos.fechaHasta)) {
            // Extraer la fecha de la fecha_creacion (formato: YYYY-MM-DD HH:MM:SS)
            let fechaNovedad = '';
            if (novedad.fecha_creacion) {
                // Si viene con hora, extraer solo la fecha
                fechaNovedad = novedad.fecha_creacion.includes(' ') 
                    ? novedad.fecha_creacion.split(' ')[0] 
                    : novedad.fecha_creacion;
            }
            
            console.log(`🔍 Comparando fechas - Novedad: ${fechaNovedad}, Desde: ${filtrosActivos.fechaDesde}, Hasta: ${filtrosActivos.fechaHasta}`);
            
            if (filtrosActivos.fechaDesde && fechaNovedad && fechaNovedad < filtrosActivos.fechaDesde) {
                console.log(`❌ Novedad ${novedad.id} filtrada por fecha desde`);
                return false;
            }
            
            if (filtrosActivos.fechaHasta && fechaNovedad && fechaNovedad > filtrosActivos.fechaHasta) {
                console.log(`❌ Novedad ${novedad.id} filtrada por fecha hasta`);
                return false;
            }
        }
        
        // Filtro por período real (mes/año de aplicación)
        if (filtrosActivos.periodoReal) {
            const periodoNovedad = {
                mes: parseInt(novedad.periodo_mes),
                anio: parseInt(novedad.periodo_anio)
            };
            
            if (periodoNovedad.mes !== filtrosActivos.periodoReal.mes || 
                periodoNovedad.anio !== filtrosActivos.periodoReal.anio) {
                console.log(`❌ Novedad ${novedad.id} filtrada por período real - Esperado: ${filtrosActivos.periodoReal.mes}/${filtrosActivos.periodoReal.anio}, Actual: ${periodoNovedad.mes}/${periodoNovedad.anio}`);
                return false;
            }
        }
        
        return true;
    });

    // Guardar datos filtrados para paginación
    window.datosFiltrados = datosFiltrados;
    
    // Resetear página actual
    paginaActual = 1;
    
    // Mostrar resultados
    mostrarResultados(datosFiltrados);
    actualizarEstadisticasFiltros(datosFiltrados);
}

/**
 * Mostrar resultados según la vista seleccionada
 */
function mostrarResultados(datos) {
    if (datos.length === 0) {
        mostrarSinResultados();
        return;
    }

    document.getElementById('sin-resultados').style.display = 'none';
    
    // Calcular paginación
    const inicio = (paginaActual - 1) * elementosPorPagina;
    const fin = inicio + elementosPorPagina;
    const datosPagina = datos.slice(inicio, fin);

    if (vistaActual === 'tabla') {
        mostrarTabla(datosPagina);
    } else {
        mostrarTarjetas(datosPagina);
    }

    // Actualizar paginación
    actualizarPaginacion(datos.length);
    
    // Actualizar contador de resultados
    actualizarContadorResultados(datos.length, inicio, Math.min(fin, datos.length));
}

/**
 * Mostrar vista de tabla
 */
function mostrarTabla(datos) {
    const tbody = document.getElementById('tbody-novedades');
    let html = '';

    datos.forEach(novedad => {
        const fechaVigencia = novedad.fecha_vigencia ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : '-';
        const contexto = NovedadesApp.contextoDesdeTipo ? NovedadesApp.contextoDesdeTipo(parseInt(novedad.tipo_novedad)) : 'numero';
        let valor = '';
        if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
            valor = NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, contexto) : novedad.valor_numerico;
        } else {
            valor = '-';
        }
        const fechaRegistro = NovedadesApp.formatearFecha(novedad.fecha_creacion);
        const estado = obtenerBadgeEstado();
        
        // Formatear período
        let periodoDisplay = '-';
        if (novedad.periodo_mes && novedad.periodo_anio) {
            periodoDisplay = `${novedad.periodo_mes}/${novedad.periodo_anio}`;
        }

        html += `
            <tr class="novedad-row" data-id="${novedad.id}">
                <td>
                    <div class="d-flex align-items-center">
                        <div>
                            <strong>${novedad.nombre} ${novedad.apellido}</strong><br>
                            <small class="text-muted">Legajo: ${novedad.legajo}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark">${novedad.nombre_sucursal || 'Sucursal ' + novedad.sucursal}</span>
                </td>
                <td>
                    <span class="badge bg-primary">${novedad.tipo_descripcion}</span>
                </td>
                <td class="tabla-periodo">
                    <span class="badge bg-secondary badge-periodo">${periodoDisplay}</span>
                </td>
                <td>
                    <small>${fechaVigencia}</small>
                </td>
                <td>${valor !== '-' ? `<strong>${valor}</strong>` : '-'}</td>
                <td>
                    <small>${fechaRegistro}</small>
                </td>
                <td>${estado}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info btn-sm" onclick="verDetalleNovedad(${novedad.id})" 
                                title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="editarNovedad(${novedad.id})" 
                                title="Editar novedad">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="confirmarEliminarNovedad(${novedad.id})" 
                                title="Eliminar novedad">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="imprimirNovedad(${novedad.id})" 
                                title="Imprimir">
                            <i class="fas fa-print"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    
    // Mostrar vista de tabla
    document.getElementById('vista-tabla').style.display = 'block';
    document.getElementById('vista-tarjetas').style.display = 'none';
}

/**
 * Mostrar vista de tarjetas
 */
function mostrarTarjetas(datos) {
    const container = document.getElementById('container-tarjetas');
    let html = '';

    datos.forEach(novedad => {
        const fechaVigencia = novedad.fecha_vigencia ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : '';
        const contexto = NovedadesApp.contextoDesdeTipo ? NovedadesApp.contextoDesdeTipo(parseInt(novedad.tipo_novedad)) : 'numero';
        const valor = (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) ? (NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, contexto) : novedad.valor_numerico) : '';
        const fechaRegistro = NovedadesApp.formatearFecha(novedad.fecha_creacion);
        const estado = obtenerBadgeEstado();

        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card novedad-card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>
                            ${novedad.nombre} ${novedad.apellido}
                        </h6>
                        <small>Legajo: ${novedad.legajo}</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Tipo:</strong><br>
                            <span class="badge bg-primary">${novedad.tipo_descripcion}</span>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Sucursal:</strong><br>
                            <span class="badge bg-light text-dark">${novedad.nombre_sucursal || 'Sucursal ' + novedad.sucursal}</span>
                        </div>
                        
                        ${fechaVigencia ? `
                        <div class="mb-2">
                            <strong>Fecha Vigencia:</strong><br>
                            <small>${fechaVigencia}</small>
                        </div>` : ''}
                        
                        ${valor ? `
                        <div class="mb-2">
                            <strong>Valor:</strong><br>
                            <span class="h6">${valor}</span>
                        </div>` : ''}
                        
                        <div class="mb-2">
                            <strong>Registrado:</strong><br>
                            <small class="text-muted">${fechaRegistro}</small>
                        </div>
                        
                        <div class="mb-3">
                            ${estado}
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="btn-group w-100" role="group">
                            <button class="btn btn-outline-info btn-sm" onclick="verDetalleNovedad(${novedad.id})" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="editarNovedad(${novedad.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="confirmarEliminarNovedad(${novedad.id})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="imprimirNovedad(${novedad.id})" title="Imprimir">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    
    // Mostrar vista de tarjetas
    document.getElementById('vista-tabla').style.display = 'none';
    document.getElementById('vista-tarjetas').style.display = 'block';
}

/**
 * Obtener badge de estado aleatorio (simulado)
 */
function obtenerBadgeEstado() {
    const estados = [
        { texto: 'Registrada', clase: 'bg-info' },
        { texto: 'En Revisión', clase: 'bg-warning' },
        { texto: 'Aprobada', clase: 'bg-success' },
        { texto: 'Procesada', clase: 'bg-secondary' }
    ];
    
    const estado = estados[Math.floor(Math.random() * estados.length)];
    return `<span class="badge ${estado.clase}">${estado.texto}</span>`;
}

/**
 * Ver detalle de novedad
 */
async function verDetalleNovedad(id) {
    try {
        const novedad = await NovedadesApp.request('get_novedad', { id });
        mostrarModalDetalleCompleto(novedad);
        
    } catch (error) {
        NovedadesApp.mostrarError('Error al obtener los detalles de la novedad');
    }
}

/**
 * Mostrar modal con detalle completo
 */
function mostrarModalDetalleCompleto(novedad) {
    const contenido = document.getElementById('contenido-detalle-novedad');
    
    let html = `
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
                            <tr><td><strong>Fecha Registro:</strong></td><td>${NovedadesApp.formatearFecha(novedad.fecha_creacion)}</td></tr>
                            ${novedad.fecha_vigencia ? `<tr><td><strong>Fecha Vigencia:</strong></td><td>${NovedadesApp.formatearFecha(novedad.fecha_vigencia)}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Información específica según el tipo de novedad
    const tipo = parseInt(novedad.tipo_novedad);
    
    html += `<div class="row mt-3">`;
    
    switch (tipo) {
        case 1: // Cambio de sucursal
            // Extraer nueva sucursal de las observaciones
            let nuevaSucursalDetalle = '';
            if (novedad.observaciones) {
                // Buscar con etiquetas HTML primero
                let match = novedad.observaciones.match(/Nueva sucursal:\s*<[^>]*>([^<]+)<[^>]*>/);
                if (match) {
                    nuevaSucursalDetalle = match[1].trim();
                } else {
                    // Buscar sin etiquetas como fallback
                    match = novedad.observaciones.match(/Nueva sucursal:\s*([^-<\n]+)/);
                    if (match) {
                        nuevaSucursalDetalle = match[1].trim();
                    }
                }
            }
            
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-building me-2"></i>Detalles del Cambio de Sucursal</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Sucursal Actual:</strong><br>
                                    <span class="badge bg-secondary">${novedad.nombre_sucursal || 'Sucursal ' + novedad.sucursal}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Nueva Sucursal:</strong><br>
                                    <span class="badge bg-primary">${nuevaSucursalDetalle || 'No especificada'}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-4">
                                    <strong>Fecha de Vigencia:</strong><br>
                                    ${NovedadesApp.formatearFecha(novedad.fecha_vigencia)}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
            break;
            
        case 2: // Nuevo puesto
            // Extraer nuevo puesto de las observaciones
            let nuevoPuestoDetalle = '';
            if (novedad.observaciones) {
                // Buscar con etiquetas HTML primero
                let match = novedad.observaciones.match(/Nuevo puesto:\s*<[^>]*>([^<]+)<[^>]*>/);
                if (match) {
                    nuevoPuestoDetalle = match[1].trim();
                } else {
                    // Buscar sin etiquetas como fallback
                    match = novedad.observaciones.match(/Nuevo puesto:\s*([^-<\n]+)/);
                    if (match) {
                        nuevoPuestoDetalle = match[1].trim();
                    }
                }
            }
            // Fallback al campo puesto si existe
            if (!nuevoPuestoDetalle && novedad.puesto) {
                nuevoPuestoDetalle = novedad.puesto;
            }
            
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Detalles del Nuevo Puesto</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Nuevo Puesto:</strong><br>
                                    <span class="badge bg-success">${nuevoPuestoDetalle || 'No especificado'}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-6">
                                    <strong>Fecha de Vigencia:</strong><br>
                                    ${NovedadesApp.formatearFecha(novedad.fecha_vigencia)}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
            break;
            
        case 3: // Nuevo salario neto
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                html += `
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
                html += `
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
                html += `
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
                html += `
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
                html += `
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
                html += `
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
                html += `
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
                html += `
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
    
    html += `</div>`;

    // Información de permiso (solo para tipo 7)
    if (novedad.fecha_permiso) {
        html += `
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
                                    ${NovedadesApp.formatearFecha(novedad.fecha_permiso)}
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
        html += `
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

    contenido.innerHTML = html;
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalDetalleNovedad'));
    modal.show();
}

/**
 * Cambiar vista (tabla/tarjetas)
 */
function cambiarVista(vista) {
    vistaActual = vista;
    
    // Actualizar botones
    document.getElementById('btn-vista-tabla').classList.toggle('active', vista === 'tabla');
    document.getElementById('btn-vista-tarjetas').classList.toggle('active', vista === 'tarjetas');
    
    // Volver a mostrar resultados
    aplicarFiltros();
}

/**
 * Limpiar filtros - ACTUALIZADO PARA CAMPO ÚNICO
 */
function limpiarFiltros() {
    // Limpiar selects normales
    document.getElementById('filtro-sucursal').value = '';
    document.getElementById('filtro-tipo').value = '';
    document.getElementById('fecha-desde').value = '';
    document.getElementById('fecha-hasta').value = '';
    
    // Limpiar Select2 único
    $('#filtro-empleado').val(null).trigger('change');
    
    // Limpiar filtros personalizados
    if (filtrosActivos.periodoReal) {
        delete filtrosActivos.periodoReal;
    }
    
    filtrosActivos = {};
    paginaActual = 1;
    aplicarFiltros();
    
    console.log('Filtros limpiados');
}

/**
 * Filtrar por períodos predefinidos
 */
function filtrarPeriodo(periodo) {
    console.log(`🔄 Aplicando filtro de período: ${periodo}`);
    
    // Limpiar filtro de período real para usar filtro por fecha de registro
    if (filtrosActivos.periodoReal) {
        delete filtrosActivos.periodoReal;
        console.log('🧹 Limpiado filtro de período real para usar filtro por fecha');
    }
    
    const hoy = new Date();
    const fechaDesde = document.getElementById('fecha-desde');
    const fechaHasta = document.getElementById('fecha-hasta');
    
    if (!fechaDesde || !fechaHasta) {
        console.error('❌ No se encontraron los campos de fecha');
        return;
    }
    
    switch(periodo) {
        case 'hoy':
            const hoyStr = hoy.toISOString().split('T')[0];
            fechaDesde.value = hoyStr;
            fechaHasta.value = hoyStr;
            break;
            
        case 'semana':
            const inicioSemana = new Date(hoy);
            inicioSemana.setDate(hoy.getDate() - hoy.getDay());
            const finSemana = new Date(inicioSemana);
            finSemana.setDate(inicioSemana.getDate() + 6);
            
            fechaDesde.value = inicioSemana.toISOString().split('T')[0];
            fechaHasta.value = finSemana.toISOString().split('T')[0];
            break;
            
        case 'mes':
            const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            const finMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            
            fechaDesde.value = inicioMes.toISOString().split('T')[0];
            fechaHasta.value = finMes.toISOString().split('T')[0];
            break;
            
        case 'actual':
            // Período del 28 del mes anterior al 27 del mes actual
            const fechaActual = new Date();
            let mesAnterior, añoAnterior;
            
            if (fechaActual.getMonth() === 0) {
                mesAnterior = 11;
                añoAnterior = fechaActual.getFullYear() - 1;
            } else {
                mesAnterior = fechaActual.getMonth() - 1;
                añoAnterior = fechaActual.getFullYear();
            }
            
            const inicioPeriodo = new Date(añoAnterior, mesAnterior, 28);
            const finPeriodo = new Date(fechaActual.getFullYear(), fechaActual.getMonth(), 27);
            
            fechaDesde.value = inicioPeriodo.toISOString().split('T')[0];
            fechaHasta.value = finPeriodo.toISOString().split('T')[0];
            break;
    }
    
    console.log(`📅 Fechas establecidas - Desde: ${fechaDesde.value}, Hasta: ${fechaHasta.value}`);
    console.log('🔍 Filtros activos antes de aplicar:', filtrosActivos);
    
    // Aplicar filtros automáticamente
    aplicarFiltros();
}

/**
 * Filtrar por período real (basado en período_mes y período_anio, no en fecha de registro)
 */
function filtrarPorPeriodoReal(tipoPeriodo) {
    console.log(`🔄 Aplicando filtro por período real: ${tipoPeriodo}`);
    
    // Limpiar filtros de fecha de registro para evitar conflictos
    const fechaDesde = document.getElementById('fecha-desde');
    const fechaHasta = document.getElementById('fecha-hasta');
    if (fechaDesde) fechaDesde.value = '';
    if (fechaHasta) fechaHasta.value = '';
    
    const hoy = new Date();
    let mesObjetivo, anioObjetivo;
    
    if (tipoPeriodo === 'actual') {
        // Calcular período actual basado en la lógica de períodos (28-27)
        const dia = hoy.getDate();
        
        if (dia <= 27) {
            // Estamos en el período actual
            mesObjetivo = hoy.getMonth() + 1; // getMonth() devuelve 0-11, necesitamos 1-12
            anioObjetivo = hoy.getFullYear();
        } else {
            // Después del 27, ya estamos en el período siguiente
            mesObjetivo = hoy.getMonth() + 2;
            anioObjetivo = hoy.getFullYear();
            
            if (mesObjetivo > 12) {
                mesObjetivo = 1;
                anioObjetivo++;
            }
        }
        
    } else if (tipoPeriodo === 'siguiente') {
        // Calcular período siguiente
        const dia = hoy.getDate();
        
        if (dia <= 27) {
            // Período siguiente es el próximo mes
            mesObjetivo = hoy.getMonth() + 2;
            anioObjetivo = hoy.getFullYear();
        } else {
            // Ya estamos en "período siguiente", así que el siguiente es +1 más
            mesObjetivo = hoy.getMonth() + 3;
            anioObjetivo = hoy.getFullYear();
        }
        
        if (mesObjetivo > 12) {
            mesObjetivo = mesObjetivo - 12;
            anioObjetivo++;
        }
    }
    
    // Agregar filtro personalizado por período
    filtrosActivos.periodoReal = {
        mes: mesObjetivo,
        anio: anioObjetivo
    };
    
    console.log(`📅 Filtrando por período real: ${mesObjetivo.toString().padStart(2, '0')}/${anioObjetivo}`);
    
    // Aplicar filtros
    aplicarFiltros();
}

/**
 * Mostrar/ocultar loading
 */
function mostrarLoading(mostrar) {
    document.getElementById('loading-resultados').style.display = mostrar ? 'block' : 'none';
    document.getElementById('vista-tabla').style.display = mostrar ? 'none' : (vistaActual === 'tabla' ? 'block' : 'none');
    document.getElementById('vista-tarjetas').style.display = mostrar ? 'none' : (vistaActual === 'tarjetas' ? 'block' : 'none');
}

/**
 * Mostrar mensaje de sin resultados
 */
function mostrarSinResultados() {
    document.getElementById('sin-resultados').style.display = 'block';
    document.getElementById('vista-tabla').style.display = 'none';
    document.getElementById('vista-tarjetas').style.display = 'none';
}

/**
 * Actualizar estadísticas generales
 */
function actualizarEstadisticas() {
    const totalNovedades = novedadesData.length;
    const sucursalesUnicas = new Set(novedadesData.map(n => n.nombre_sucursal || 'Sucursal ' + n.sucursal)).size;
    const empleadosUnicos = new Set(novedadesData.map(n => n.legajo)).size;
    
    // Solo sumar valores monetarios (tipos 3 y 4: salarios y premios)
    const valorTotal = novedadesData.reduce((sum, n) => {
        const tipo = parseInt(n.tipo_novedad);
        if (tipo === 3 || tipo === 4) { // Solo salarios y premios
            return sum + (parseFloat(n.valor_numerico) || 0);
        }
        return sum;
    }, 0);

    document.getElementById('total-resultados').textContent = totalNovedades;
    document.getElementById('total-sucursales-filtro').textContent = sucursalesUnicas;
    document.getElementById('total-empleados-filtro').textContent = empleadosUnicos;
    document.getElementById('total-valores').textContent = NovedadesApp.formatearValor ? NovedadesApp.formatearValor(valorTotal, 'moneda') : valorTotal;
}

/**
 * Actualizar paginación
 */
function actualizarPaginacion(totalElementos) {
    const totalPaginas = Math.ceil(totalElementos / elementosPorPagina);
    const paginacion = document.getElementById('paginacion');
    
    if (totalPaginas <= 1) {
        paginacion.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Botón anterior
    html += `
        <li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="cambiarPagina(${paginaActual - 1})">
                <i class="fas fa-chevron-left"></i>
            </button>
        </li>
    `;
    
    // Páginas
    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= paginaActual - 2 && i <= paginaActual + 2)) {
            html += `
                <li class="page-item ${i === paginaActual ? 'active' : ''}">
                    <button class="page-link" onclick="cambiarPagina(${i})">${i}</button>
                </li>
            `;
        } else if (i === paginaActual - 3 || i === paginaActual + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Botón siguiente
    html += `
        <li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
            <button class="page-link" onclick="cambiarPagina(${paginaActual + 1})">
                <i class="fas fa-chevron-right"></i>
            </button>
        </li>
    `;
    
    paginacion.innerHTML = html;
}

/**
 * Cambiar página - CORREGIDO
 */
function cambiarPagina(pagina) {
    // Usar datos filtrados para calcular páginas correctamente
    const datosActuales = window.datosFiltrados || novedadesData;
    const totalPaginas = Math.ceil(datosActuales.length / elementosPorPagina);
    
    if (pagina < 1 || pagina > totalPaginas) return;
    
    paginaActual = pagina;
    
    // Mostrar resultados de la página actual
    mostrarResultados(datosActuales);
}

/**
 * Actualizar contador de resultados
 */
function actualizarContadorResultados(total, desde, hasta) {
    document.getElementById('resultados-desde').textContent = total > 0 ? desde + 1 : 0;
    document.getElementById('resultados-hasta').textContent = hasta;
    document.getElementById('resultados-total').textContent = total;
}

/**
 * Exportar a Excel
 */
function exportarExcel() {
    // Obtener datos filtrados
    let datosFiltrados = novedadesData.filter(novedad => {
        if (filtrosActivos.legajo && !novedad.legajo.toString().includes(filtrosActivos.legajo)) return false;
        if (filtrosActivos.sucursal && novedad.sucursal != filtrosActivos.sucursal) return false;
        if (filtrosActivos.tipo && novedad.tipo_novedad != filtrosActivos.tipo) return false;
        if (filtrosActivos.empleado) {
            const nombreCompleto = `${novedad.nombre} ${novedad.apellido}`.toLowerCase();
            if (!nombreCompleto.includes(filtrosActivos.empleado)) return false;
        }
        return true;
    });

    if (datosFiltrados.length === 0) {
        NovedadesApp.mostrarError('No hay datos para exportar');
        return;
    }

    // Crear CSV
    const headers = ['Legajo', 'Nombre', 'Apellido', 'Sucursal', 'Tipo de Novedad', 'Fecha Vigencia', 'Valor', 'Fecha Permiso', 'Compensa', 'Observaciones', 'Fecha Registro'];
    const csvContent = [
        headers.join(','),
        ...datosFiltrados.map(novedad => [
            novedad.legajo,
            `"${novedad.nombre}"`,
            `"${novedad.apellido}"`,
            novedad.sucursal,
            `"${novedad.tipo_descripcion}"`,
            (novedad.fecha_vigencia ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : ''),
            (() => { 
                const ctx = NovedadesApp.contextoDesdeTipo ? NovedadesApp.contextoDesdeTipo(parseInt(novedad.tipo_novedad)) : 'numero'; 
                return (novedad.valor_numerico && parseFloat(novedad.valor_numerico)!==0) ? (NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, ctx) : novedad.valor_numerico) : ''; 
            })(),
            (novedad.fecha_permiso ? NovedadesApp.formatearFecha(novedad.fecha_permiso) : ''),
            novedad.compensa ? 'Sí' : 'No',
            `"${(novedad.observaciones || '').replace(/"/g, '""')}"`,
            NovedadesApp.formatearFecha(novedad.fecha_creacion)
        ].join(','))
    ].join('\n');

    // Descargar archivo
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `novedades_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    NovedadesApp.mostrarExito('Archivo exportado correctamente');
}

/**
 * Imprimir reporte
 */
function imprimirReporte() {
    // Obtener datos filtrados
    let datosFiltrados = novedadesData.filter(novedad => {
        if (filtrosActivos.legajo && !novedad.legajo.toString().includes(filtrosActivos.legajo)) return false;
        if (filtrosActivos.sucursal && novedad.sucursal != filtrosActivos.sucursal) return false;
        if (filtrosActivos.tipo && novedad.tipo_novedad != filtrosActivos.tipo) return false;
        if (filtrosActivos.empleado) {
            const nombreCompleto = `${novedad.nombre} ${novedad.apellido}`.toLowerCase();
            if (!nombreCompleto.includes(filtrosActivos.empleado)) return false;
        }
        return true;
    });

    if (datosFiltrados.length === 0) {
        NovedadesApp.mostrarError('No hay datos para imprimir');
        return;
    }

    // Crear ventana de impresión
    const ventanaImpresion = window.open('', '_blank');
    
    let html = `
        <html>
        <head>
            <title>Reporte de Novedades</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: #0d6efd; margin-bottom: 5px; }
                .header p { color: #6c757d; margin: 0; }
                .periodo { background: #e3f2fd; padding: 10px; text-align: center; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; font-size: 12px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .text-center { text-align: center; }
                .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Sistema de Novedades RRHH</h1>
                <p>Reporte de Novedades Registradas</p>
            </div>
            
            <div class="periodo">
                <strong>Período: 28/07/2025 - 27/08/2025</strong>
            </div>
            
            <p><strong>Total de registros:</strong> ${datosFiltrados.length}</p>
            <p><strong>Fecha de generación:</strong> ${new Date().toLocaleDateString('es-AR')}</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Legajo</th>
                        <th>Empleado</th>
                        <th>Sucursal</th>
                        <th>Tipo de Novedad</th>
                        <th>Fecha Vigencia</th>
                        <th>Valor</th>
                        <th>Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>
    `;

    datosFiltrados.forEach(novedad => {
        const contexto = NovedadesApp.contextoDesdeTipo(parseInt(novedad.tipo_novedad));
        html += `
            <tr>
                <td>${novedad.legajo}</td>
                <td>${novedad.nombre} ${novedad.apellido}</td>
                <td>${novedad.sucursal}</td>
                <td>${novedad.tipo_descripcion}</td>
                <td>${novedad.fecha_vigencia ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : '-'}</td>
                <td>${novedad.valor_numerico ? NovedadesApp.formatearValor(novedad.valor_numerico, contexto) : '-'}</td>
                <td>${NovedadesApp.formatearFecha(novedad.fecha_creacion)}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
            
            <div class="footer">
                <p>Generado por Sistema de Novedades RRHH - ${new Date().toLocaleString('es-AR')}</p>
            </div>
        </body>
        </html>
    `;

    ventanaImpresion.document.write(html);
    ventanaImpresion.document.close();
    
    // Imprimir después de cargar
    ventanaImpresion.onload = function() {
        ventanaImpresion.print();
    };
}

/**
 * Imprimir novedad individual
 */
function imprimirNovedad(id) {
    const novedad = novedadesData.find(n => n.id == id);
    if (!novedad) return;

    const ventanaImpresion = window.open('', '_blank');
    
    let html = `
        <html>
        <head>
            <title>Detalle de Novedad</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: #0d6efd; margin-bottom: 5px; }
                .section { margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; }
                .section h3 { margin-top: 0; color: #0d6efd; }
                table { width: 100%; }
                th, td { padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Detalle de Novedad</h1>
                <p>Período: 28/07/2025 - 27/08/2025</p>
            </div>
            
            <div class="section">
                <h3>Información del Empleado</h3>
                <table>
                    <tr><th>Legajo:</th><td>${novedad.legajo}</td></tr>
                    <tr><th>Nombre:</th><td>${novedad.nombre} ${novedad.apellido}</td></tr>
                    <tr><th>Sucursal:</th><td>${novedad.sucursal}</td></tr>
                </table>
            </div>
            
            <div class="section">
                <h3>Información de la Novedad</h3>
                <table>
                    <tr><th>Tipo:</th><td>${novedad.tipo_descripcion}</td></tr>
                    <tr><th>Fecha de Registro:</th><td>${NovedadesApp.formatearFecha(novedad.fecha_creacion)}</td></tr>
                    ${novedad.fecha_vigencia ? `<tr><th>Fecha de Vigencia:</th><td>${NovedadesApp.formatearFecha(novedad.fecha_vigencia)}</td></tr>` : ''}
                    ${novedad.valor_numerico ? `<tr><th>Valor:</th><td>${NovedadesApp.formatearValor(novedad.valor_numerico, NovedadesApp.contextoDesdeTipo(parseInt(novedad.tipo_novedad)))}</td></tr>` : ''}
                </table>
            </div>
            
            ${novedad.observaciones ? `
            <div class="section">
                <h3>Observaciones</h3>
                <p>${novedad.observaciones}</p>
            </div>` : ''}
            
            <div style="margin-top: 50px; text-align: center; font-size: 12px; color: #6c757d;">
                <p>Generado el ${new Date().toLocaleString('es-AR')}</p>
            </div>
        </body>
        </html>
    `;

    ventanaImpresion.document.write(html);
    ventanaImpresion.document.close();
    ventanaImpresion.onload = function() {
        ventanaImpresion.print();
    };
}

/**
 * Imprimir detalle desde modal
 */
function imprimirDetalle() {
    window.print();
}

/**
 * Configurar búsqueda en tiempo real
 */
function configurarBusquedaTiempoReal() {
    const filtroEmpleado = document.getElementById('filtro-empleado');
    const filtroLegajo = document.getElementById('filtro-legajo');
    
    let timeoutId;
    
    [filtroEmpleado, filtroLegajo].forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(aplicarFiltros, 500);
        });
    });
}

/**
 * Inicialización específica de la consulta - ACTUALIZADA
 */
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos base primero
    cargarDatosBase();
    
    // Cargar novedades
    cargarNovedades();
    
    // Inicializar Select2 cuando jQuery esté disponible
    if (typeof $ !== 'undefined') {
        inicializarSelect2();
    } else {
        // Si jQuery no está disponible aún, esperar un poco
        setTimeout(() => {
            if (typeof $ !== 'undefined') {
                inicializarSelect2();
            }
        }, 500);
    }
    
    // Agregar event listeners para los filtros de fecha
    const fechaDesde = document.getElementById('fecha-desde');
    const fechaHasta = document.getElementById('fecha-hasta');
    
    if (fechaDesde) {
        fechaDesde.addEventListener('change', aplicarFiltros);
    }
    
    if (fechaHasta) {
        fechaHasta.addEventListener('change', aplicarFiltros);
    }
    
    // Event listeners para otros filtros
    const filtroSucursal = document.getElementById('filtro-sucursal');
    const filtroTipo = document.getElementById('filtro-tipo');
    
    if (filtroSucursal) {
        filtroSucursal.addEventListener('change', aplicarFiltros);
    }
    
    if (filtroTipo) {
        filtroTipo.addEventListener('change', aplicarFiltros);
    }
    
    // Event listeners para filtros normales
    document.getElementById('filtro-sucursal').addEventListener('change', aplicarFiltros);
    document.getElementById('filtro-tipo').addEventListener('change', aplicarFiltros);
    
    // Event listeners para Select2 (se configuran después de inicializar)
    setTimeout(() => {
        $('#filtro-legajo').on('change', function() {
            console.log('Cambio en filtro-legajo:', $(this).val());
            aplicarFiltros();
        });
        
        $('#filtro-empleado').on('change', function() {
            console.log('Cambio en filtro-empleado:', $(this).val());
            aplicarFiltros();
        });
    }, 1000);
    
    // Event listeners para ordenamiento de columnas
    document.querySelectorAll('.sortable').forEach(columna => {
        columna.addEventListener('click', function() {
            const nombreColumna = this.getAttribute('data-column');
            ordenarPor(nombreColumna);
        });
    });
    
    console.log('Consulta de Novedades - Inicializada correctamente');
    console.log('🔧 Event listeners de ordenamiento agregados');
});

/**
 * Cargar datos base (sucursales y tipos de novedad)
 */
async function cargarDatosBase() {
    try {
        // Cargar sucursales con Casa Central
        const sucursales = await NovedadesApp.request('get_sucursales_con_casa_central');
        const sucursalSelect = document.getElementById('filtro-sucursal');
        sucursalSelect.innerHTML = '<option value="">Todas las sucursales</option>';
        
        sucursales.forEach(sucursal => {
            const option = document.createElement('option');
            option.value = sucursal.numero;
            option.textContent = `${sucursal.numero} - ${sucursal.descripcion}`;
            sucursalSelect.appendChild(option);
        });

        // Cargar tipos de novedad
        const tipos = await NovedadesApp.request('get_tipos_novedad');
        const tipoSelect = document.getElementById('filtro-tipo');
        tipoSelect.innerHTML = '<option value="">Todos los tipos</option>';
        
        tipos.forEach(tipo => {
            const option = document.createElement('option');
            option.value = tipo.id;
            option.textContent = tipo.descripcion;
            tipoSelect.appendChild(option);
        });

        console.log('✅ Datos base cargados correctamente');
    } catch (error) {
        console.error('❌ Error cargando datos base:', error);
        NovedadesApp.mostrarError('Error cargando datos base');
    }
}

/**
 * Actualizar estadísticas de filtros
 */
function actualizarEstadisticasFiltros(datosFiltrados) {
    const totalNovedades = datosFiltrados.length;
    const sucursalesUnicas = new Set(datosFiltrados.map(n => n.sucursal)).size;
    const empleadosUnicos = new Set(datosFiltrados.map(n => n.legajo)).size;
    
    // Solo sumar valores monetarios (tipos 3 y 4: salarios y premios)
    const valorTotal = datosFiltrados.reduce((sum, n) => {
        const tipo = parseInt(n.tipo_novedad);
        if (tipo === 3 || tipo === 4) { // Solo salarios y premios
            return sum + (parseFloat(n.valor_numerico) || 0);
        }
        return sum;
    }, 0);

    document.getElementById('total-resultados').textContent = totalNovedades;
    document.getElementById('total-sucursales-filtro').textContent = sucursalesUnicas;
    document.getElementById('total-empleados-filtro').textContent = empleadosUnicos;
    document.getElementById('total-valores').textContent = NovedadesApp.formatearValor ? NovedadesApp.formatearValor(valorTotal, 'moneda') : valorTotal;
}

/**
 * Editar una novedad existente
 */
async function editarNovedad(id) {
    try {
        // Obtener los datos actuales de la novedad
        const novedad = await NovedadesApp.request('get_novedad', { id: id });
        
        // Crear formulario de edición modal
        const modalHtml = `
            <div class="modal fade" id="modalEditarNovedad" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                <i class="fas fa-edit me-2"></i>
                                Editar Novedad - ${novedad.nombre} ${novedad.apellido}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="form-editar-novedad" data-id="${id}">
                                <div id="contenido-formulario-editar">
                                    <div class="text-center">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Cargando formulario...</span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-warning" onclick="guardarEdicionNovedad()">
                                <i class="fas fa-save me-1"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remover modal existente si existe
        const modalExistente = document.getElementById('modalEditarNovedad');
        if (modalExistente) {
            modalExistente.remove();
        }
        
        // Agregar modal al DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalEditarNovedad'));
        modal.show();
        
        // Generar formulario con datos actuales
        await generarFormularioEdicion(novedad);
        
    } catch (error) {
        console.error('Error abriendo formulario de edición:', error);
        NovedadesApp.mostrarError('Error abriendo formulario de edición');
    }
}

/**
 * Confirmar eliminación de novedad
 */
function confirmarEliminarNovedad(id) {
    if (confirm('¿Está seguro de que desea eliminar esta novedad? Esta acción no se puede deshacer.')) {
        eliminarNovedad(id);
    }
}

/**
 * Eliminar una novedad
 */
async function eliminarNovedad(id) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        
        const respuesta = await fetch('controller/novedades_controller.php?action=eliminar_novedad', {
            method: 'POST',
            body: formData
        });
        
        const resultado = await respuesta.json();
        
        if (resultado.success) {
            NovedadesApp.mostrarExito('Novedad eliminada exitosamente');
            await cargarNovedades();
        } else {
            NovedadesApp.mostrarError('Error eliminando novedad: ' + resultado.message);
        }
        
    } catch (error) {
        console.error('Error eliminando novedad:', error);
        NovedadesApp.mostrarError('Error eliminando novedad');
    }
}

/**
 * Generar formulario de edición con datos actuales
 */
async function generarFormularioEdicion(novedad) {
    try {
        // Cargar datos necesarios para el formulario
        const [sucursales, tipos, puestos] = await Promise.all([
            NovedadesApp.request('get_sucursales'),
            NovedadesApp.request('get_tipos_novedad'),
            NovedadesApp.request('get_puestos')
        ]);
        
        const formularioHtml = `
            <div class="row g-3">
                <!-- Información del empleado -->
                <div class="col-md-4">
                    <label for="edit-legajo" class="form-label">Legajo</label>
                    <input type="number" class="form-control" id="edit-legajo" value="${novedad.legajo}" required>
                </div>
                <div class="col-md-4">
                    <label for="edit-nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="edit-nombre" value="${novedad.nombre}" required>
                </div>
                <div class="col-md-4">
                    <label for="edit-apellido" class="form-label">Apellido</label>
                    <input type="text" class="form-control" id="edit-apellido" value="${novedad.apellido}" required>
                </div>
                
                <!-- Sucursal y Tipo -->
                <div class="col-md-6">
                    <label for="edit-sucursal" class="form-label">Sucursal</label>
                    <select class="form-select" id="edit-sucursal" required>
                        ${sucursales.map(s => `
                            <option value="${s.numero}" ${s.numero == novedad.sucursal ? 'selected' : ''}>
                                ${s.descripcion}
                            </option>
                        `).join('')}
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="edit-tipo-novedad" class="form-label">Tipo de Novedad</label>
                    <select class="form-select" id="edit-tipo-novedad" required onchange="actualizarFormularioEdicion()">
                        ${tipos.map(t => `
                            <option value="${t.id}" ${t.id == novedad.tipo_novedad ? 'selected' : ''}>
                                ${t.descripcion}
                            </option>
                        `).join('')}
                    </select>
                </div>
                
                <!-- Campos dinámicos según tipo -->
                <div id="campos-dinamicos-edit">
                    ${generarCamposDinamicosEdicion(novedad)}
                </div>
                
                <!-- Observaciones -->
                <div class="col-12">
                    <label for="edit-observaciones" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="edit-observaciones" rows="3">${novedad.observaciones || ''}</textarea>
                </div>
            </div>
        `;
        
        document.getElementById('contenido-formulario-editar').innerHTML = formularioHtml;
        
    } catch (error) {
        console.error('Error generando formulario:', error);
        document.getElementById('contenido-formulario-editar').innerHTML = 
            '<div class="alert alert-danger">Error cargando formulario</div>';
    }
}

/**
 * Generar campos dinámicos para edición según tipo de novedad
 */
function generarCamposDinamicosEdicion(novedad) {
    const tipo = parseInt(novedad.tipo_novedad);
    let campos = '';
    
    // Fecha de vigencia (común para varios tipos)
    if ([1, 2, 3, 4, 5, 6, 8, 9, 10, 11].includes(tipo)) {
        const fechaVigencia = novedad.fecha_vigencia ? novedad.fecha_vigencia.split(' ')[0] : '';
        campos += `
            <div class="col-md-6">
                <label for="edit-fecha-vigencia" class="form-label">Fecha de Vigencia</label>
                <input type="date" class="form-control" id="edit-fecha-vigencia" value="${fechaVigencia}" required>
            </div>
        `;
    }
    
    // Campos específicos según tipo
    switch(tipo) {
        case 1: // Cambio sucursal
            // Extraer nueva sucursal de las observaciones si existe
            let nuevaSucursal = '';
            if (novedad.observaciones) {
                // Buscar con etiquetas HTML primero
                let match = novedad.observaciones.match(/Nueva sucursal:\s*<[^>]*>([^<]+)<[^>]*>/);
                if (match) {
                    nuevaSucursal = match[1].trim();
                } else {
                    // Buscar sin etiquetas como fallback
                    match = novedad.observaciones.match(/Nueva sucursal:\s*([^-<\n]+)/);
                    if (match) {
                        nuevaSucursal = match[1].trim();
                    }
                }
            }
            
            campos += `
                <div class="col-md-6">
                    <label for="edit-nueva-sucursal" class="form-label">Nueva Sucursal</label>
                    <select class="form-select" id="edit-nueva-sucursal" required>
                        <option value="">Seleccionar sucursal...</option>
                        <!-- Se llenarán dinámicamente -->
                    </select>
                </div>
            `;
            
            // Cargar sucursales después de renderizar
            setTimeout(() => cargarSucursalesParaEdicion(nuevaSucursal), 100);
            break;
            
        case 2: // Cambio de puesto
            // Extraer nuevo puesto de las observaciones si existe
            let nuevoPuesto = '';
            if (novedad.observaciones) {
                // Buscar con etiquetas HTML primero
                let match = novedad.observaciones.match(/Nuevo puesto:\s*<[^>]*>([^<]+)<[^>]*>/);
                if (match) {
                    nuevoPuesto = match[1].trim();
                } else {
                    // Buscar sin etiquetas como fallback
                    match = novedad.observaciones.match(/Nuevo puesto:\s*([^-<\n]+)/);
                    if (match) {
                        nuevoPuesto = match[1].trim();
                    }
                }
            }
            
            campos += `
                <div class="col-md-6">
                    <label for="edit-puesto" class="form-label">Nuevo Puesto</label>
                    <select class="form-select" id="edit-puesto" required>
                        <option value="">Seleccionar puesto...</option>
                        <!-- Se llenarán dinámicamente -->
                    </select>
                </div>
            `;
            
            // Cargar puestos después de renderizar
            setTimeout(() => cargarPuestosParaEdicion(nuevoPuesto), 100);
            break;
            
        case 3: // Nuevo salario
            campos += `
                <div class="col-md-6">
                    <label for="edit-nuevo-salario" class="form-label">Nuevo Salario Neto</label>
                    <input type="number" class="form-control" id="edit-nuevo-salario" 
                           value="${novedad.valor_numerico || ''}" step="0.01" required>
                </div>
            `;
            break;
            
        case 4: // Ajuste premios
            campos += `
                <div class="col-md-6">
                    <label for="edit-monto-ajuste" class="form-label">Monto de Ajuste</label>
                    <input type="number" class="form-control" id="edit-monto-ajuste" 
                           value="${novedad.valor_numerico || ''}" step="0.01" required>
                </div>
            `;
            break;
            
        case 5: // Horas extras
        case 6: // Horas adicionales
            campos += `
                <div class="col-md-6">
                    <label for="edit-cantidad-horas" class="form-label">Cantidad de Horas</label>
                    <input type="number" class="form-control" id="edit-cantidad-horas" 
                           value="${novedad.valor_numerico || ''}" step="0.25" required>
                </div>
            `;
            break;
            
        case 7: // Permisos
            const fechaPermiso = novedad.fecha_permiso ? novedad.fecha_permiso.split(' ')[0] : '';
            campos += `
                <div class="col-md-6">
                    <label for="edit-fecha-permiso" class="form-label">Fecha del Permiso</label>
                    <input type="date" class="form-control" id="edit-fecha-permiso" 
                           value="${fechaPermiso}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">¿Compensa?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="edit-compensa" 
                               id="edit-compensa-si" value="1" ${novedad.compensa ? 'checked' : ''}>
                        <label class="form-check-label" for="edit-compensa-si">Sí</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="edit-compensa" 
                               id="edit-compensa-no" value="0" ${!novedad.compensa ? 'checked' : ''}>
                        <label class="form-check-label" for="edit-compensa-no">No</label>
                    </div>
                </div>
            `;
            break;
            
        case 8: // Cortes
        case 9: // Producción 25%
        case 10: // Producción 50%
        case 11: // Producción 100%
            const labelTipo = tipo === 8 ? 'Cantidad de Cortes' : 'Cantidad de Unidades';
            campos += `
                <div class="col-md-6">
                    <label for="edit-cantidad-unidades" class="form-label">${labelTipo}</label>
                    <input type="number" class="form-control" id="edit-cantidad-unidades" 
                           value="${novedad.valor_numerico || ''}" required>
                </div>
            `;
            break;
    }
    
    return campos;
}

/**
 * Actualizar formulario de edición cuando cambia el tipo
 */
function actualizarFormularioEdicion() {
    const tipoSeleccionado = document.getElementById('edit-tipo-novedad').value;
    if (!tipoSeleccionado) return;
    
    // Crear objeto temporal para generar campos dinámicos
    const novedadTemp = {
        tipo_novedad: tipoSeleccionado,
        fecha_vigencia: '',
        valor_numerico: '',
        fecha_permiso: '',
        tipo_permiso: '',
        compensa: false
    };
    
    document.getElementById('campos-dinamicos-edit').innerHTML = generarCamposDinamicosEdicion(novedadTemp);
}

/**
 * Guardar edición de novedad
 */
async function guardarEdicionNovedad() {
    try {
        const form = document.getElementById('form-editar-novedad');
        const id = form.dataset.id;
        
        // Recopilar datos del formulario
        const datos = {
            id: id,
            legajo: document.getElementById('edit-legajo').value,
            nombre: document.getElementById('edit-nombre').value,
            apellido: document.getElementById('edit-apellido').value,
            sucursal: document.getElementById('edit-sucursal').value,
            tipo_novedad: document.getElementById('edit-tipo-novedad').value,
            observaciones: limpiarObservaciones(document.getElementById('edit-observaciones').value, parseInt(document.getElementById('edit-tipo-novedad').value))
        };
        
        // Agregar campos específicos según tipo
        const tipo = parseInt(datos.tipo_novedad);
        
        // Fecha de vigencia
        const fechaVigencia = document.getElementById('edit-fecha-vigencia');
        if (fechaVigencia) {
            datos.fecha_vigencia = fechaVigencia.value;
            console.log(`📅 Fecha de vigencia capturada:`, fechaVigencia.value);
        } else {
            console.error('❌ No se encontró el campo edit-fecha-vigencia');
        }
        
        // Campos específicos por tipo
        switch(tipo) {
            case 1: // Cambio de sucursal
                const nuevaSucursal = document.getElementById('edit-nueva-sucursal');
                if (nuevaSucursal) {
                    datos.nueva_sucursal = nuevaSucursal.value;
                    console.log(`🏪 Nueva sucursal capturada:`, nuevaSucursal.value);
                }
                break;
                
            case 2: // Cambio de puesto
                const puesto = document.getElementById('edit-puesto');
                if (puesto) {
                    datos.puesto = puesto.value;
                    console.log(`👔 Puesto capturado:`, puesto.value);
                }
                break;
                
            case 3: // Nuevo salario
                const nuevoSalario = document.getElementById('edit-nuevo-salario');
                if (nuevoSalario) {
                    datos.importe = nuevoSalario.value; // Backend espera 'importe'
                    console.log(`💰 Nuevo salario capturado:`, nuevoSalario.value);
                }
                break;
            case 4: // Ajuste premios
                const montoAjuste = document.getElementById('edit-monto-ajuste');
                if (montoAjuste) {
                    datos.importe = montoAjuste.value; // Backend espera 'importe'
                    console.log(`🎯 Monto ajuste capturado:`, montoAjuste.value);
                }
                break;
            case 5: // Horas extras
                const cantidadHoras5 = document.getElementById('edit-cantidad-horas');
                if (cantidadHoras5) {
                    datos.cantidad_horas = cantidadHoras5.value;
                    console.log(`⏰ Cantidad horas extras capturada:`, cantidadHoras5.value);
                }
                break;
            case 6: // Horas adicionales
                const cantidadHoras6 = document.getElementById('edit-cantidad-horas');
                if (cantidadHoras6) {
                    datos.cantidad_horas = cantidadHoras6.value;
                    console.log(`⏰ Cantidad horas adicionales capturada:`, cantidadHoras6.value);
                }
                break;
            case 7: // Permisos
                datos.fecha_permiso = document.getElementById('edit-fecha-permiso').value;
                const compensaRadio = document.querySelector('input[name="edit-compensa"]:checked');
                datos.compensa = compensaRadio ? compensaRadio.value === '1' : false;
                break;
            case 8: // Cortes
                const cantidadCortesElement = document.getElementById('edit-cantidad-unidades');
                if (cantidadCortesElement) {
                    datos.cantidad_cortes = cantidadCortesElement.value; // Para cortes usar cantidad_cortes
                    console.log(`🔢 Cantidad de cortes capturada:`, cantidadCortesElement.value);
                } else {
                    console.error('❌ No se encontró el elemento edit-cantidad-unidades para cortes');
                }
                break;
            case 9: case 10: case 11: // Producción
                const cantidadUnidadesElement = document.getElementById('edit-cantidad-unidades');
                if (cantidadUnidadesElement) {
                    datos.cantidad_unidades = cantidadUnidadesElement.value; // Para producción usar cantidad_unidades
                    console.log(`🔢 Cantidad de unidades capturada para tipo ${tipo}:`, cantidadUnidadesElement.value);
                } else {
                    console.error('❌ No se encontró el elemento edit-cantidad-unidades para producción');
                }
                break;
        }
        
        console.log('📋 Datos completos a enviar:', datos);
        
        // Validar datos requeridos
        if (!datos.legajo || !datos.nombre || !datos.apellido || !datos.sucursal || !datos.tipo_novedad) {
            NovedadesApp.mostrarError('Por favor complete todos los campos requeridos');
            return;
        }
        
        // Enviar actualización usando método POST
        const respuesta = await NovedadesApp.request('editar_novedad', datos, 'POST');
        
        console.log('✅ Respuesta de edición recibida:', respuesta);
        
        // Verificar si la respuesta indica éxito
        if (respuesta && respuesta.success !== false) {
            NovedadesApp.mostrarExito('Novedad actualizada exitosamente');
            
            // Cerrar modal
            const modalElement = document.getElementById('modalEditarNovedad');
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                    console.log('📕 Modal de edición cerrado');
                } else {
                    // Fallback si no hay instancia de Bootstrap
                    modalElement.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                }
            }
            
            // Recargar datos
            await cargarNovedades();
        } else {
            NovedadesApp.mostrarError('Error actualizando novedad');
        }
        
    } catch (error) {
        console.error('Error guardando edición:', error);
        NovedadesApp.mostrarError('Error guardando los cambios');
    }
}

/**
 * Ordenar datos por columna
 */
function ordenarPor(columna) {
    console.log(`🔄 Ordenando por columna: ${columna}`);
    
    // Si es la misma columna, alternar dirección
    if (ordenActual.columna === columna) {
        ordenActual.direccion = ordenActual.direccion === 'asc' ? 'desc' : 'asc';
    } else {
        ordenActual.columna = columna;
        ordenActual.direccion = 'asc';
    }
    
    // Actualizar indicadores visuales
    actualizarIndicadoresOrden();
    
    // Aplicar ordenamiento a datos filtrados
    let datosParaOrdenar = [...(window.datosFiltrados || novedadesData)]; // Crear copia
    
    datosParaOrdenar.sort((a, b) => {
        let valorA, valorB;
        let esVacioA = false, esVacioB = false;
        
        switch(columna) {
            case 'empleado':
                valorA = `${a.nombre || ''} ${a.apellido || ''}`.trim().toLowerCase();
                valorB = `${b.nombre || ''} ${b.apellido || ''}`.trim().toLowerCase();
                esVacioA = valorA === '';
                esVacioB = valorB === '';
                break;
                
            case 'sucursal':
                valorA = a.nombre_sucursal?.toLowerCase() || '';
                valorB = b.nombre_sucursal?.toLowerCase() || '';
                esVacioA = !valorA || valorA === '';
                esVacioB = !valorB || valorB === '';
                // Si no hay nombre_sucursal, usar número de sucursal como fallback
                if (esVacioA) valorA = a.sucursal ? `sucursal ${a.sucursal}` : '';
                if (esVacioB) valorB = b.sucursal ? `sucursal ${b.sucursal}` : '';
                esVacioA = valorA === '';
                esVacioB = valorB === '';
                break;
                
            case 'tipo':
                valorA = a.tipo_descripcion?.toLowerCase() || '';
                valorB = b.tipo_descripcion?.toLowerCase() || '';
                esVacioA = valorA === '';
                esVacioB = valorB === '';
                break;
                
            case 'periodo':
                // Ordenar por período (mes/año) - convertir a formato comparable
                if (a.periodo_mes && a.periodo_anio) {
                    valorA = a.periodo_anio * 100 + a.periodo_mes; // Ej: 2024*100 + 8 = 202408
                } else {
                    valorA = null;
                }
                
                if (b.periodo_mes && b.periodo_anio) {
                    valorB = b.periodo_anio * 100 + b.periodo_mes;
                } else {
                    valorB = null;
                }
                
                esVacioA = valorA === null;
                esVacioB = valorB === null;
                break;
                
            case 'vigencia':
                // Ordenar por fecha de vigencia
                valorA = a.fecha_vigencia || null;
                valorB = b.fecha_vigencia || null;
                esVacioA = !valorA || valorA === null;
                esVacioB = !valorB || valorB === null;
                break;
                
            case 'valor':
                // Ordenar por valor numérico
                valorA = a.valor_numerico ? parseFloat(a.valor_numerico) : null;
                valorB = b.valor_numerico ? parseFloat(b.valor_numerico) : null;
                esVacioA = valorA === null || isNaN(valorA) || valorA === 0;
                esVacioB = valorB === null || isNaN(valorB) || valorB === 0;
                break;
                
            case 'fecha_registro':
                // Ordenar por fecha de creación
                valorA = a.fecha_creacion || null;
                valorB = b.fecha_creacion || null;
                esVacioA = !valorA || valorA === null;
                esVacioB = !valorB || valorB === null;
                break;
                
            case 'estado':
                valorA = 'activo'; // Por ahora todos son activos
                valorB = 'activo';
                esVacioA = false;
                esVacioB = false;
                break;
                
            default:
                return 0;
        }
        
        // Los valores vacíos/null siempre van al final
        if (esVacioA && esVacioB) return 0; // Ambos vacíos, mantener orden
        if (esVacioA) return 1;  // A vacío, va al final
        if (esVacioB) return -1; // B vacío, va al final
        
        // Comparar valores no vacíos
        let resultado = 0;
        if (typeof valorA === 'string' && typeof valorB === 'string') {
            resultado = valorA.localeCompare(valorB);
        } else {
            resultado = valorA < valorB ? -1 : (valorA > valorB ? 1 : 0);
        }
        
        return ordenActual.direccion === 'desc' ? -resultado : resultado;
    });
    
    // Actualizar datos filtrados
    window.datosFiltrados = datosParaOrdenar;
    
    // Resetear paginación y mostrar resultados
    paginaActual = 1;
    mostrarResultados(datosParaOrdenar);
    actualizarEstadisticasFiltros(datosParaOrdenar);
    
    console.log(`✅ Ordenamiento aplicado: ${columna} ${ordenActual.direccion}`);
    
    // Log para debugging de valores vacíos
    const valoresVacios = datosParaOrdenar.filter(item => {
        switch(columna) {
            case 'empleado':
                return !item.nombre || !item.apellido;
            case 'periodo':
                return !item.periodo_mes || !item.periodo_anio;
            case 'vigencia':
                return !item.fecha_vigencia;
            case 'valor':
                return !item.valor_numerico || parseFloat(item.valor_numerico) === 0;
            default:
                return false;
        }
    });
    
    if (valoresVacios.length > 0) {
        console.log(`📝 ${valoresVacios.length} registros con valores vacíos movidos al final`);
    }
}

/**
 * Actualizar indicadores visuales de ordenamiento
 */
function actualizarIndicadoresOrden() {
    // Limpiar indicadores previos
    document.querySelectorAll('.sortable').forEach(th => {
        th.removeAttribute('data-sort');
        th.classList.remove('sorted');
    });
    
    // Agregar indicador a la columna actual
    if (ordenActual.columna) {
        const columnaActual = document.querySelector(`[data-column="${ordenActual.columna}"]`);
        if (columnaActual) {
            columnaActual.setAttribute('data-sort', ordenActual.direccion);
            columnaActual.classList.add('sorted');
        }
    }
}

/**
 * Limpiar ordenamiento actual
 */
function limpiarOrdenamiento() {
    ordenActual = {
        columna: null,
        direccion: 'asc'
    };
    actualizarIndicadoresOrden();
}

/**
 * Cargar sucursales para el select de edición
 */
async function cargarSucursalesParaEdicion(sucursalSeleccionada = '') {
    try {
        const sucursales = await NovedadesApp.request('get_sucursales');
        const select = document.getElementById('edit-nueva-sucursal');
        
        if (!select) return;
        
        // Limpiar opciones actuales (mantener la primera)
        select.innerHTML = '<option value="">Seleccionar sucursal...</option>';
        
        // Agregar sucursales
        sucursales.forEach(sucursal => {
            const option = document.createElement('option');
            option.value = sucursal.numero;
            option.textContent = sucursal.descripcion;
            
            // Seleccionar si coincide con la sucursal extraída
            if (sucursalSeleccionada && sucursal.descripcion === sucursalSeleccionada) {
                option.selected = true;
            }
            
            select.appendChild(option);
        });
        
        console.log('🏪 Sucursales cargadas para edición, seleccionada:', sucursalSeleccionada);
        
    } catch (error) {
        console.error('Error cargando sucursales para edición:', error);
    }
}

/**
 * Cargar puestos para el select de edición
 */
async function cargarPuestosParaEdicion(puestoSeleccionado = '') {
    try {
        const puestos = await NovedadesApp.request('get_puestos');
        const select = document.getElementById('edit-puesto');
        
        if (!select) return;
        
        // Limpiar opciones actuales (mantener la primera)
        select.innerHTML = '<option value="">Seleccionar puesto...</option>';
        
        // Agregar puestos
        puestos.forEach(puesto => {
            const option = document.createElement('option');
            option.value = puesto.nombre_puesto;
            option.textContent = puesto.nombre_puesto;
            
            // Seleccionar si coincide con el puesto extraído
            if (puestoSeleccionado && puesto.nombre_puesto === puestoSeleccionado) {
                option.selected = true;
            }
            
            select.appendChild(option);
        });
        
        console.log('👔 Puestos cargados para edición, seleccionado:', puestoSeleccionado);
        
    } catch (error) {
        console.error('Error cargando puestos para edición:', error);
    }
}

/**
 * Limpiar observaciones de datos específicos ya extraídos
 */
function limpiarObservaciones(observaciones, tipoNovedad) {
    if (!observaciones) return observaciones;
    
    let observacionesLimpias = observaciones;
    
    switch(tipoNovedad) {
        case 1: // Cambio de sucursal
            // Remover " - Nueva sucursal: [NOMBRE]" incluyendo etiquetas
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nueva sucursal:\s*<[^>]*>([^<]+)<[^>]*>/gi, '');
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nueva sucursal:\s*([^-<\n]+)/gi, '');
            break;
            
        case 2: // Cambio de puesto
            // Remover " - Nuevo puesto: [NOMBRE]" incluyendo etiquetas
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nuevo puesto:\s*<[^>]*>([^<]+)<[^>]*>/gi, '');
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nuevo puesto:\s*([^-<\n]+)/gi, '');
            break;
    }
    
    // Limpiar <OBS> si queda solo
    observacionesLimpias = observacionesLimpias.replace(/^<OBS>\s*$/gi, '');
    observacionesLimpias = observacionesLimpias.replace(/^<OBS>\s*-\s*$/gi, '');
    
    // Limpiar espacios extra
    observacionesLimpias = observacionesLimpias.trim();
    
    console.log(`🧹 Observaciones limpiadas para tipo ${tipoNovedad}:`, observacionesLimpias);
    
    return observacionesLimpias;
}