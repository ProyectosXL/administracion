// /novedades/js/consultar_novedades.js

/**
 * JavaScript específico para la consulta de novedades
 */

// Mapeo de estados: números del frontend a strings de la base de datos
const ESTADOS_MAP = {
    1: 'Enviada',
    2: 'En Revisión', 
    3: 'Aprobada',
    4: 'A Revisar',
    5: 'Procesada'
};

// Función para convertir número de estado a string
function mapearEstado(numeroEstado) {
    return ESTADOS_MAP[numeroEstado] || 'Enviada';
}

/**
 * Convertir fecha de dd/mm/aaaa a aaaa-mm-dd para campos input type="date"
 */
function convertirFechaParaInput(fecha) {
    if (!fecha) return '';
    
    // Convertir a string si es necesario
    const fechaStr = String(fecha);
    
    // Si ya está en formato ISO (aaaa-mm-dd), devolverla tal cual (sin hora)
    if (/^\d{4}-\d{2}-\d{2}/.test(fechaStr)) {
        return fechaStr.split(' ')[0].split('T')[0];
    }
    
    // Si está en formato dd/mm/aaaa, convertir
    if (/^\d{2}\/\d{2}\/\d{4}/.test(fechaStr)) {
        const partes = fechaStr.split(' ')[0].split('/');
        return `${partes[2]}-${partes[1]}-${partes[0]}`;
    }
    
    console.warn('⚠️ Formato de fecha no reconocido:', fecha);
    return '';
}

/**
 * Convertir fecha de dd/mm/aaaa a aaaa-mm-dd para enviar al backend
 */
function convertirFechaParaBackend(fecha) {
    if (!fecha) return '';
    
    // Convertir a string si es necesario
    const fechaStr = String(fecha).trim();
    
    // Si ya está en formato ISO (aaaa-mm-dd), devolverla tal cual
    if (/^\d{4}-\d{2}-\d{2}/.test(fechaStr)) {
        return fechaStr.split(' ')[0].split('T')[0];
    }
    
    // Si está en formato dd/mm/aaaa, convertir a aaaa-mm-dd
    if (/^\d{2}\/\d{2}\/\d{4}/.test(fechaStr)) {
        const partes = fechaStr.split('/');
        const dia = partes[0].padStart(2, '0');
        const mes = partes[1].padStart(2, '0');
        const anio = partes[2];
        return `${anio}-${mes}-${dia}`;
    }
    
    console.warn('⚠️ Formato de fecha no reconocido para backend:', fecha);
    return fecha; // Devolver tal cual si no coincide con ningún formato
}

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

    // Select2 para tipos de novedad con búsqueda
    $('#filtro-tipo').select2({
        theme: 'bootstrap-5',
        placeholder: 'Todos los tipos',
        allowClear: true,
        language: {
            noResults: function () {
                return 'No se encontraron tipos de novedad';
            },
            searching: function () {
                return 'Buscando tipos...';
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
        centro_costos: document.getElementById('filtro-centro-costos').value,
        tipo: $('#filtro-tipo').val() || '',
        estado: document.getElementById('filtro-estado').value,
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
        
        // Filtro por centro de costos
        if (filtrosActivos.centro_costos && novedad.codigo_centro_costos != filtrosActivos.centro_costos) {
            return false;
        }
        
        // Filtro por tipo de novedad
        if (filtrosActivos.tipo && novedad.tipo_novedad != filtrosActivos.tipo) {
            return false;
        }
        
        // Filtro por estado - usar estado_numero para comparación
        if (filtrosActivos.estado && novedad.estado_numero != parseInt(filtrosActivos.estado)) {
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
        const fechaVigenciaHasta = novedad.fecha_vigencia_hasta ? NovedadesApp.formatearFecha(novedad.fecha_vigencia_hasta) : '-';
        const contexto = NovedadesApp.contextoDesdeTipo ? NovedadesApp.contextoDesdeTipo(parseInt(novedad.tipo_novedad)) : 'numero';
        let valor = '';
        if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
            valor = NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, contexto) : novedad.valor_numerico;
        } else {
            valor = '-';
        }
        const fechaRegistro = NovedadesApp.formatearFecha(novedad.fecha_creacion);
        console.log('📊 Tabla - Novedad ID:', novedad.id, 'estado_numero:', novedad.estado_numero);
        const estado = obtenerBadgeEstado(novedad.estado_numero || 1);
        
        // Formatear período
        let periodoDisplay = '-';
        if (novedad.periodo_mes && novedad.periodo_anio) {
            periodoDisplay = `${novedad.periodo_mes}/${novedad.periodo_anio}`;
        }

        html += `
            <tr class="novedad-row" data-id="${novedad.id}">
                <td>
                    <small>${fechaRegistro}</small>
                </td>
                <td>
                    <strong>${novedad.legajo}</strong>
                </td>
                <td>
                    <strong>${novedad.nombre} ${novedad.apellido}</strong>
                </td>
                <td>
                    <span class="badge bg-light text-dark">${novedad.centro_costos_display || (novedad.descripcion_centro_costos || 'Sin centro') + ' (' + (novedad.codigo_centro_costos || '') + ')'}</span>
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
                <td>
                    <small>${fechaVigenciaHasta}</small>
                </td>
                <td>${valor !== '-' ? `<strong>${valor}</strong>` : '-'}</td>
                <td>${estado}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info btn-sm" onclick="verDetalleNovedad(${novedad.id})" 
                                title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${NovedadesApp.puedeEditarEstados() ? `
                        <button class="btn btn-outline-secondary btn-sm" onclick="mostrarModalCambiarEstado(${novedad.id}, ${novedad.estado_numero || 1})" 
                                title="Cambiar estado">
                            <i class="fas fa-exchange-alt"></i>
                        </button>` : ''}
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
        console.log('🎴 Tarjetas - Novedad ID:', novedad.id, 'estado_numero:', novedad.estado_numero);
        const estado = obtenerBadgeEstado(novedad.estado_numero || 1);

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
                            <strong>Centro de Costos:</strong><br>
                            <span class="badge bg-light text-dark">${novedad.centro_costos_display || (novedad.descripcion_centro_costos || 'Sin centro') + ' (' + (novedad.codigo_centro_costos || '') + ')'}</span>
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
                            ${NovedadesApp.puedeEditarEstados() ? `
                            <button class="btn btn-outline-secondary btn-sm" onclick="mostrarModalCambiarEstado(${novedad.id}, ${novedad.estado_numero || 1})" title="Cambiar estado">
                                <i class="fas fa-exchange-alt"></i>
                            </button>` : ''}
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
 * Obtener badge de estado basado en el valor real del estado
 */
function obtenerBadgeEstado(estadoNumero) {
    console.log('🔍 obtenerBadgeEstado recibió:', estadoNumero, 'tipo:', typeof estadoNumero);
    
    const estados = {
        1: { texto: 'Enviada', clase: 'bg-info text-white' },
        2: { texto: 'En Revisión', clase: 'bg-warning text-dark' },
        3: { texto: 'Aprobada', clase: 'bg-success text-white' },
        4: { texto: 'A Revisar', clase: 'bg-danger text-white' },
        5: { texto: 'Procesada', clase: 'bg-secondary text-white' }
    };
    
    const estado = estados[estadoNumero] || { texto: 'Estado Desconocido', clase: 'bg-light text-dark' };
    console.log('🎯 Estado seleccionado:', estado);
    
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
                            <tr><td><strong>Centro de Costos:</strong></td><td>${novedad.centro_costos_display || novedad.descripcion_centro_costos || 'Centro ' + novedad.codigo_centro_costos}</td></tr>
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
                            <tr><td><strong>Estado:</strong></td><td>${obtenerBadgeEstado(novedad.estado_numero || 1)}</td></tr>
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
            // Para cambio de sucursal, obtener información de sucursales
            let sucursalActualDetalle = 'Cargando...';
            let nuevaSucursalDetalle = 'No especificado';
            
            // Obtener sucursal actual del centro de costos del empleado
            if (novedad.codigo_centro_costos) {
                // Usar una función async para obtener la sucursal
                obtenerSucursalPorCentroCostos(novedad.codigo_centro_costos).then(sucursal => {
                    if (sucursal) {
                        // Actualizar el elemento después de cargar
                        const elemento = document.querySelector(`[data-novedad-id="${novedad.id}"] .sucursal-actual-text`);
                        if (elemento) {
                            elemento.textContent = sucursal;
                        }
                    }
                }).catch(error => {
                    console.error('Error obteniendo sucursal actual:', error);
                    const elemento = document.querySelector(`[data-novedad-id="${novedad.id}"] .sucursal-actual-text`);
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
            
            html += `
                <div class="col-12" data-novedad-id="${novedad.id}">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-building me-2"></i>Detalles del Cambio de Sucursal</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Sucursal Actual:</strong><br>
                                    <span class="badge bg-secondary sucursal-actual-text">${sucursalActualDetalle}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Nueva Sucursal:</strong><br>
                                    <span class="badge bg-primary">${nuevaSucursalDetalle}</span>
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
            
        case 2: // Nueva Posición
            // Extraer nueva posición de las observaciones
            // Extraer detalle de la nueva posición - USAR CAMPOS DIRECTOS EN LUGAR DE OBSERVACIONES
            let nuevoPuestoDetalle = '';
            
            // PRIORITARIO: Usar el campo puesto directo si existe
            if (novedad.puesto && novedad.puesto !== 'Cambio centro de costos' && novedad.puesto !== 'Ajuste Salario' && novedad.puesto !== 'Premio') {
                nuevoPuestoDetalle = novedad.puesto;
            } else if (novedad.observaciones) {
                // Solo como fallback: extraer de observaciones si no hay puesto directo
                // Buscar el patrón más completo primero
                let match = novedad.observaciones.match(/Nuevo puesto:\s*([^-]*?)\s*(?:\([^)]*?\))?(?:\s+hasta\s+\d{2}\/\d{2}\/\d{4})?(?:\s*-|$)/);
                if (match) {
                    nuevoPuestoDetalle = match[1].trim();
                } else {
                    // Buscar con etiquetas HTML
                    match = novedad.observaciones.match(/Nuevo puesto:\s*<[^>]*>([^<]+)<[^>]*>/);
                    if (match) {
                        nuevoPuestoDetalle = match[1].trim();
                    }
                }
            }
            
            // Nueva Posición - siempre permanente, sin tipo ni fecha hasta
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Detalles de la Nueva Posición</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Nueva Posición:</strong><br>
                                    <span class="badge bg-success">${nuevoPuestoDetalle || 'No especificado'}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-6">
                                    <strong>Fecha de vigencia:</strong><br>
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

        case 12: // Plus de caja (compatibilidad)
        case 32: // Plus de caja (ID real BD)
            // Extraer tipo de plus de observaciones
            let tipoPlusCaja = 'No especificado';
            if (novedad.observaciones) {
                const match = novedad.observaciones.match(/Tipo:\s*(.+)/);
                if (match) {
                    tipoPlusCaja = match[1].trim();
                }
            }
            
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-cash-register me-2"></i>Detalles del Plus de Caja</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Tipo de Plus:</strong><br>
                                    <span class="badge bg-primary">${tipoPlusCaja}</span>
                                </div>
                                ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0 ? `
                                <div class="col-md-4">
                                    <strong>Importe:</strong><br>
                                    <span class="h5">${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico}</span>
                                </div>
                                ` : ''}
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

        case 13: // Plus de Sub-Encargada (compatibilidad)
        case 33: // Plus de Sub-Encargada (ID real BD)
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i>Detalles del Plus de Sub-Encargada</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Plus de Sub-Encargada</strong><br>
                                    ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0 ? 
                                        `<span class="h5">${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico}</span>` : 
                                        '<span class="text-muted">Sin importe específico</span>'
                                    }
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

        case 14: // Plus de Encargada (compatibilidad)
        case 34: // Plus de Encargada (ID real BD)
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-user-cog me-2"></i>Detalles del Plus de Encargada</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Plus de Encargada</strong><br>
                                    ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0 ? 
                                        `<span class="h5">${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico}</span>` : 
                                        '<span class="text-muted">Sin importe específico</span>'
                                    }
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

        case 15: // Premio Local (compatibilidad)
        case 35: // Premio Local (ID real BD)
            // Construir lista de aplicaciones - SOLO vendedora y sub-encargada
            let aplicacionesPremio = [];
            
            // Conversión robusta y simple usando doble negación (!!)
            // Cualquier valor truthy no-cero se considera true
            const aplicaVendedora = !!(novedad.aplica_vendedora && novedad.aplica_vendedora != 0 && novedad.aplica_vendedora != '0');
            const aplicaSubEncargada = !!(novedad.aplica_sub_encargada && novedad.aplica_sub_encargada != 0 && novedad.aplica_sub_encargada != '0');
            
            if (aplicaVendedora) aplicacionesPremio.push('Vendedora');
            if (aplicaSubEncargada) aplicacionesPremio.push('Sub-Encargada');
            const aplicaTexto = aplicacionesPremio.length > 0 ? aplicacionesPremio.join(', ') : 'No especificado';
            
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-trophy me-2"></i>Detalles del Premio Local</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Importe del Premio:</strong><br>
                                    ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0 ? 
                                        `<span class="h5">${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico}</span>` : 
                                        '<span class="text-muted">No especificado</span>'
                                    }
                                </div>
                                <div class="col-md-4">
                                    <strong>Aplica a:</strong><br>
                                    <span class="badge bg-info">${aplicaTexto}</span>
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

        case 16: // Comisión Individual (compatibilidad)
        case 36: // Comisión Individual (ID real BD)
            // COMISIÓN INDIVIDUAL: Solo un porcentaje simple
            const porcentajeIndividualDetalle = novedad.porcentaje_1 ? parseFloat(novedad.porcentaje_1) : 0;
            
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Detalles de Comisión Individual</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Tipo de Comisión:</strong><br>
                                    <span class="badge bg-primary">Individual</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Porcentaje:</strong><br>
                                    <span class="h5">${porcentajeIndividualDetalle}%</span>
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

        case 17: // Comisión sobre Local (compatibilidad)
        case 37: // Comisión sobre Local (ID real BD)
            // COMISIÓN SOBRE LOCAL: Con estructura de tope
            const tieneTopeLocalDetalle = novedad.tiene_tope;
            const colorComisionDetalle = tieneTopeLocalDetalle ? 'bg-warning text-dark' : 'bg-info text-white';
            const iconoComisionDetalle = 'fa-building';
            const tipoComisionTextoDetalle = 'sobre el Local';
            const estructuraComisionDetalle = tieneTopeLocalDetalle ? 'Con tope' : 'Sin tope';
            
            let porcentajesTextoDetalle = '';
            if (tieneTopeLocalDetalle) {
                const p1 = novedad.porcentaje_1 ? parseFloat(novedad.porcentaje_1) : 0;
                const p2 = novedad.porcentaje_2 ? parseFloat(novedad.porcentaje_2) : 0;
                porcentajesTextoDetalle = `Primer porcentaje: ${p1}%, Segundo porcentaje: ${p2}%`;
            } else {
                const p = novedad.porcentaje_1 ? parseFloat(novedad.porcentaje_1) : 0;
                porcentajesTextoDetalle = `Porcentaje único: ${p}%`;
            }
            
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header ${colorComisionDetalle}">
                            <h6 class="mb-0"><i class="fas ${iconoComisionDetalle} me-2"></i>Detalles de Comisión ${tipoComisionTextoDetalle}</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Tipo de Comisión:</strong><br>
                                    <span class="badge bg-secondary">${tipoComisionTextoDetalle}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Estructura:</strong><br>
                                    <span class="detalle-estructura-comision badge ${novedad.tiene_tope ? 'bg-warning text-dark' : 'bg-success'}">${estructuraComisionDetalle}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-4">
                                    <strong>Fecha de Vigencia:</strong><br>
                                    ${NovedadesApp.formatearFecha(novedad.fecha_vigencia)}
                                </div>
                                ` : ''}
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="alert alert-info detalle-porcentajes-comision">
                                        <i class="fas fa-percentage me-2"></i>
                                        <strong>Porcentajes:</strong> ${porcentajesTextoDetalle}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            break;

        case 18: // Premios - Ajuste General (compatibilidad)
        case 38: // Premios - Ajuste General (ID real BD)
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-adjust me-2"></i>Detalles del Ajuste General</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Importe del Ajuste:</strong><br>
                                    ${novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0 ? 
                                        `<span class="h5">${NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico}</span>` : 
                                        '<span class="text-muted">No especificado</span>'
                                    }
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

        case 16: // Comisión Individual (compatibilidad)
        case 36: // Comisión Individual (ID real BD)
            // COMISIÓN INDIVIDUAL: Solo un porcentaje simple
            const porcentajeIndividual = novedad.porcentaje_1 ? parseFloat(novedad.porcentaje_1) : 0;
            
            camposHTML = `
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Comisión Individual</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="edit_porcentaje_individual" class="form-label">Porcentaje Individual</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="edit_porcentaje_individual" 
                                            value="${porcentajeIndividual}" step="0.01" min="0.01">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_fecha_vigencia_comision_individual" class="form-label">Fecha Vigencia</label>
                                    <input type="date" class="form-control" id="edit_fecha_vigencia_comision_individual" 
                                        value="${novedad.fecha_vigencia || ''}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            break;

        case 17: // Comisión sobre Local (compatibilidad)
        case 37: // Comisión sobre Local (ID real BD)
            // COMISIÓN SOBRE LOCAL: Con estructura de tope
            const tieneTopeLocal = novedad.tiene_tope;
            
            camposHTML = `
                <div class="col-md-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-building me-2"></i>Comisión sobre el Local</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="edit-tiene-tope-local" class="form-label">Estructura</label>
                                    <select class="form-select" id="edit-tiene-tope-local">
                                        <option value="0" ${!tieneTopeLocal ? 'selected' : ''}>Sin tope</option>
                                        <option value="1" ${tieneTopeLocal ? 'selected' : ''}>Con tope</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_fecha_vigencia_comision_local" class="form-label">Fecha Vigencia</label>
                                    <input type="date" class="form-control" id="edit_fecha_vigencia_comision_local" 
                                        value="${novedad.fecha_vigencia || ''}">
                                </div>
                            </div>`;
            
            if (tieneTopeLocal) {
                const p1 = novedad.porcentaje_1 ? parseFloat(novedad.porcentaje_1) : 0;
                const p2 = novedad.porcentaje_2 ? parseFloat(novedad.porcentaje_2) : 0;
                camposHTML += `
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="edit-porcentaje-1-local" class="form-label">Primer Porcentaje</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="edit-porcentaje-1-local" 
                                            value="${p1}" step="0.01" min="0.01">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit-porcentaje-2-local" class="form-label">Segundo Porcentaje</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="edit-porcentaje-2-local" 
                                            value="${p2}" step="0.01" min="0.01">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>`;
            } else {
                const p = novedad.porcentaje_1 ? parseFloat(novedad.porcentaje_1) : 0;
                camposHTML += `
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="edit-porcentaje-unico-local" class="form-label">Porcentaje Único</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="edit-porcentaje-unico-local" 
                                            value="${p}" step="0.01" min="0.01">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>`;
            }
            
            camposHTML += `
                        </div>
                    </div>
                </div>`;
            break;

        case 18: // Premios - Ajuste General (compatibilidad)
        case 38: // Premios - Ajuste General (ID real BD)
            camposHTML = `
                <div class="col-md-6">
                    <div class="card border-secondary">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-adjust me-2"></i>Premios - Ajuste General</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="edit-importe-ajuste-general" class="form-label">Importe del Ajuste</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="edit-importe-ajuste-general" 
                                            value="${novedad.valor_numerico || ''}" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_fecha_vigencia_ajuste" class="form-label">Fecha Vigencia</label>
                                    <input type="date" class="form-control" id="edit_fecha_vigencia_ajuste" 
                                        value="${novedad.fecha_vigencia || ''}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            break;

        case 53: // Reemplazo - siempre temporario
            // Reemplazo: Similar a Nueva Posición
            let puestoReemplazoDetalle = '';
            
            // PRIORITARIO: Usar el campo puesto directo si existe
            if (novedad.puesto && novedad.puesto !== 'Cambio centro de costos' && novedad.puesto !== 'Ajuste Salario' && novedad.puesto !== 'Premio') {
                puestoReemplazoDetalle = novedad.puesto;
            } else if (novedad.observaciones) {
                // Solo como fallback: extraer de observaciones si no hay puesto directo
                let match = novedad.observaciones.match(/Reemplazo:\s*([^-]*?)\s*(?:\([^)]*?\))?(?:\s+hasta\s+\d{2}\/\d{2}\/\d{4})?(?:\s*-|$)/);
                if (match) {
                    puestoReemplazoDetalle = match[1].trim();
                } else {
                    // Buscar con etiquetas HTML
                    match = novedad.observaciones.match(/Reemplazo:\s*<[^>]*>([^<]+)<[^>]*>/);
                    if (match) {
                        puestoReemplazoDetalle = match[1].trim();
                    }
                }
            }
            
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Detalles del Reemplazo</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Puesto de Reemplazo:</strong><br>
                                    <span class="badge bg-info">${puestoReemplazoDetalle || 'No especificado'}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-4">
                                    <strong>Fecha de Inicio:</strong><br>
                                    ${NovedadesApp.formatearFecha(novedad.fecha_vigencia)}
                                </div>
                                ` : ''}
                                ${novedad.fecha_vigencia_hasta ? `
                                <div class="col-md-4">
                                    <strong>Fecha de Fin:</strong><br>
                                    ${NovedadesApp.formatearFecha(novedad.fecha_vigencia_hasta)}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
            break;

        case 55: // A prueba - siempre temporario
            // A prueba: Similar a Reemplazo
            let puestoAPruebaDetalle = '';
            
            // PRIORITARIO: Usar el campo puesto directo si existe
            if (novedad.puesto && novedad.puesto !== 'Cambio centro de costos' && novedad.puesto !== 'Ajuste Salario' && novedad.puesto !== 'Premio') {
                puestoAPruebaDetalle = novedad.puesto;
            } else if (novedad.observaciones) {
                // Solo como fallback: extraer de observaciones si no hay puesto directo
                let match = novedad.observaciones.match(/A prueba:\s*([^-]*?)\s*(?:\([^)]*?\))?(?:\s+hasta\s+\d{2}\/\d{2}\/\d{4})?(?:\s*-|$)/);
                if (match) {
                    puestoAPruebaDetalle = match[1].trim();
                } else {
                    // Buscar con etiquetas HTML
                    match = novedad.observaciones.match(/A prueba:\s*<[^>]*>([^<]+)<[^>]*>/);
                    if (match) {
                        puestoAPruebaDetalle = match[1].trim();
                    }
                }
            }
            
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-user-clock me-2"></i>Detalles de prueba</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Puesto de prueba:</strong><br>
                                    <span class="badge bg-info">${puestoAPruebaDetalle || 'No especificado'}</span>
                                </div>
                                ${novedad.fecha_vigencia ? `
                                <div class="col-md-4">
                                    <strong>Fecha de Inicio:</strong><br>
                                    ${NovedadesApp.formatearFecha(novedad.fecha_vigencia)}
                                </div>
                                ` : ''}
                                ${novedad.fecha_vigencia_hasta ? `
                                <div class="col-md-4">
                                    <strong>Fecha de Fin:</strong><br>
                                    ${NovedadesApp.formatearFecha(novedad.fecha_vigencia_hasta)}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>`;
            break;

        case 54: // Aumento Salarial
            // Detectar tipo de aumento basándose en qué campo tiene valor
            const tienePorcentaje = novedad.porcentaje_1 && parseFloat(novedad.porcentaje_1) > 0;
            const tieneMonto = novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0;
            const esPorcentaje = tienePorcentaje;
            const iconoAumento = esPorcentaje ? 'fa-percentage text-primary' : 'fa-dollar-sign text-success';
            const textTipoAumento = esPorcentaje ? 'Porcentaje' : 'Monto Fijo';
            
            let valorAumento = '';
            if (esPorcentaje && novedad.porcentaje_1) {
                // IMPORTANTE: El porcentaje se guarda como decimal (0.21), multiplicar por 100 para mostrar (21%)
                const porcentajeDisplay = (parseFloat(novedad.porcentaje_1) * 100).toFixed(2);
                valorAumento = porcentajeDisplay + '%';
            } else if (!esPorcentaje && novedad.valor_numerico) {
                valorAumento = NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda') : '$' + novedad.valor_numerico;
            }
            
            html += `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Detalles del Aumento Salarial</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Tipo de Aumento:</strong><br>
                                    <span class="badge ${esPorcentaje ? 'bg-primary' : 'bg-success'}">
                                        <i class="fas ${iconoAumento} me-1"></i>${textTipoAumento}
                                    </span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Valor del Aumento:</strong><br>
                                    <span class="h5">${valorAumento || 'No especificado'}</span>
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
    }
    
    // Cerrar la fila de detalles específicos
    html += `</div>`;
    
    // Agregar observaciones si existen
    if (novedad.observaciones && novedad.observaciones.trim() !== '') {
        html += `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-comment-alt me-2"></i>Observaciones</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">${novedad.observaciones}</p>
                        </div>
                    </div>
                </div>
            </div>`;
    }
    
    // Establecer el contenido en el modal
    contenido.innerHTML = html;
    
    // Mostrar el modal
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
 * Limpiar filtros - ACTUALIZADO PARA CAMPOS SELECT2
 */
function limpiarFiltros() {
    // Limpiar selects normales
    document.getElementById('filtro-centro-costos').value = '';
    document.getElementById('filtro-estado').value = '';
    document.getElementById('fecha-desde').value = '';
    document.getElementById('fecha-hasta').value = '';
    
    // Limpiar Select2 campos
    $('#filtro-empleado').val(null).trigger('change');
    $('#filtro-tipo').val(null).trigger('change');
    
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
    const centrosCostosUnicos = new Set(novedadesData.map(n => n.descripcion_centro_costos || 'Centro de Costos ' + n.codigo_centro_costos)).size;
    const empleadosUnicos = new Set(novedadesData.map(n => n.legajo)).size;
    
    // Sumar valores monetarios según el tipo de novedad
    const valorTotal = novedadesData.reduce((sum, n) => {
        const tipo = parseInt(n.tipo_novedad);
        const valorNumerico = parseFloat(n.valor_numerico) || 0;
        
        // Tipos con valores monetarios directos en valor_numerico:
        // 3: Nuevo salario, 4: Ajuste premios
        // 12/32: Plus de caja, 13/33: Plus sub-encargada, 14/34: Plus encargada
        // 15/35: Premio local, 18/38: Premios - Ajuste General
        if (tipo === 3 || tipo === 4 || 
            tipo === 12 || tipo === 32 || 
            tipo === 13 || tipo === 33 || 
            tipo === 14 || tipo === 34 || 
            tipo === 15 || tipo === 35 || 
            tipo === 18 || tipo === 38) {
            return sum + valorNumerico;
        }
        
        // Tipo 54: Aumento Salarial - solo sumar si es tipo monto (no porcentaje)
        if (tipo === 54) {
            const tieneMonto = valorNumerico > 0;
            const tienePorcentaje = n.porcentaje_1 && parseFloat(n.porcentaje_1) > 0;
            // Solo sumar si tiene monto y NO tiene porcentaje (para evitar contar los de tipo porcentaje)
            if (tieneMonto && !tienePorcentaje) {
                return sum + valorNumerico;
            }
        }
        
        return sum;
    }, 0);

    document.getElementById('total-resultados').textContent = totalNovedades;
    document.getElementById('total-centros-costos-filtro').textContent = centrosCostosUnicos;
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
        if (filtrosActivos.estado && novedad.estado_numero != parseInt(filtrosActivos.estado)) return false;
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

    // Crear datos para XLSX - ACTUALIZADO según columnas de la tabla actual
    const headers = [
        'Fecha Registro',
        'Legajo', 
        'Empleado',
        'Centro de Costos',
        'Tipo de Novedad',
        'Período',
        'Vigencia Desde',
        'Vigencia Hasta',
        'Valor',
        'Estado',
        'Observaciones'
    ];
    
    const data = [
        headers,
        ...datosFiltrados.map(novedad => [
            // Fecha Registro
            NovedadesApp.formatearFecha(novedad.fecha_creacion),
            
            // Legajo
            novedad.legajo,
            
            // Empleado (Nombre completo)
            `${novedad.nombre} ${novedad.apellido}`,
            
            // Centro de Costos
            novedad.descripcion_centro_costos || novedad.codigo_centro_costos || novedad.sucursal || '',
            
            // Tipo de Novedad
            novedad.tipo_descripcion,
            
            // Período
            novedad.periodo_mes && novedad.periodo_anio ? 
                `${novedad.periodo_mes.toString().padStart(2, '0')}/${novedad.periodo_anio}` : '',
            
            // Vigencia Desde
            novedad.fecha_vigencia ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : '',
            
            // Vigencia Hasta
            novedad.fecha_vigencia_hasta ? NovedadesApp.formatearFecha(novedad.fecha_vigencia_hasta) : '',
            
            // Valor - usar la misma lógica que en la tabla
            (() => {
                const contexto = NovedadesApp.contextoDesdeTipo ? NovedadesApp.contextoDesdeTipo(parseInt(novedad.tipo_novedad)) : 'numero';
                if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                    return NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, contexto) : novedad.valor_numerico;
                }
                return '-';
            })(),
            
            // Estado
            (() => {
                const estados = {
                    1: 'Enviada',
                    2: 'En Revisión',
                    3: 'Aprobada',
                    4: 'A Revisar',
                    5: 'Procesada'
                };
                return estados[novedad.estado_numero] || novedad.estado || '';
            })(),
            
            // Observaciones
            novedad.observaciones || ''
        ])
    ];

    // Usar SheetJS (xlsx) para generar el archivo
    if (typeof XLSX === 'undefined') {
        NovedadesApp.mostrarError('No se encontró la librería XLSX. Por favor, incluya SheetJS (xlsx.full.min.js) en la página.');
        return;
    }

    const ws = XLSX.utils.aoa_to_sheet(data);
    
    // Ajustar anchos de columnas
    ws['!cols'] = [
        { wch: 12 }, // Fecha Registro
        { wch: 8 },  // Legajo
        { wch: 30 }, // Empleado
        { wch: 20 }, // Centro de Costos
        { wch: 25 }, // Tipo de Novedad
        { wch: 10 }, // Período
        { wch: 12 }, // Vigencia Desde
        { wch: 12 }, // Vigencia Hasta
        { wch: 20 }, // Valor
        { wch: 12 }, // Estado
        { wch: 40 }  // Observaciones
    ];
    
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Novedades');
    
    const fecha = new Date().toISOString().split('T')[0];
    XLSX.writeFile(wb, `novedades_${fecha}.xlsx`);

    NovedadesApp.mostrarExito('Archivo XLSX exportado correctamente');
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
        if (filtrosActivos.estado && novedad.estado_numero != parseInt(filtrosActivos.estado)) return false;
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
    
    // Preparar información de filtros aplicados
    let filtrosInfo = [];
    if (filtrosActivos.legajo) filtrosInfo.push(`Legajo: ${filtrosActivos.legajo}`);
    if (filtrosActivos.empleado) filtrosInfo.push(`Empleado: ${filtrosActivos.empleado}`);
    if (filtrosActivos.sucursal) {
        const selectSucursal = document.getElementById('filtro-sucursal');
        const textoSucursal = selectSucursal.options[selectSucursal.selectedIndex].text;
        filtrosInfo.push(`Centro de Costos: ${textoSucursal}`);
    }
    if (filtrosActivos.tipo) {
        const selectTipo = document.getElementById('filtro-tipo');
        const textoTipo = selectTipo.options[selectTipo.selectedIndex].text;
        filtrosInfo.push(`Tipo: ${textoTipo}`);
    }
    if (filtrosActivos.estado) {
        const selectEstado = document.getElementById('filtro-estado');
        const textoEstado = selectEstado.options[selectEstado.selectedIndex].text;
        filtrosInfo.push(`Estado: ${textoEstado}`);
    }
    
    let html = `
        <html>
        <head>
            <title>Reporte de Novedades</title>
            <style>
                @page { size: landscape; margin: 1cm; }
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    margin: 0; 
                    padding: 15px;
                    font-size: 10pt;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 20px;
                    border-bottom: 2px solid #0d6efd;
                    padding-bottom: 10px;
                }
                .header h1 { 
                    color: #0d6efd; 
                    margin: 0 0 5px 0;
                    font-size: 18pt;
                }
                .header p { 
                    color: #6c757d; 
                    margin: 0;
                    font-size: 11pt;
                }
                .info-section {
                    background: #f8f9fa;
                    padding: 10px;
                    margin-bottom: 15px;
                    border-radius: 4px;
                    border-left: 4px solid #0d6efd;
                }
                .info-section p {
                    margin: 3px 0;
                    font-size: 9pt;
                }
                .filtros-aplicados {
                    font-size: 9pt;
                    color: #495057;
                    font-style: italic;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    font-size: 9pt;
                }
                th, td { 
                    border: 1px solid #dee2e6; 
                    padding: 6px 8px; 
                    text-align: left; 
                }
                th { 
                    background-color: #0d6efd; 
                    color: white;
                    font-weight: 600;
                    font-size: 9pt;
                }
                tr:nth-child(even) { background-color: #f8f9fa; }
                .text-center { text-align: center; }
                .badge {
                    padding: 3px 6px;
                    border-radius: 3px;
                    font-size: 8pt;
                    font-weight: 500;
                }
                .badge-enviada { background-color: #cfe2ff; color: #084298; }
                .badge-revision { background-color: #fff3cd; color: #664d03; }
                .badge-aprobada { background-color: #d1e7dd; color: #0f5132; }
                .badge-revisar { background-color: #f8d7da; color: #842029; }
                .badge-procesada { background-color: #d3d3d4; color: #141619; }
                .footer { 
                    margin-top: 20px; 
                    text-align: center; 
                    font-size: 8pt; 
                    color: #6c757d;
                    border-top: 1px solid #dee2e6;
                    padding-top: 10px;
                }
                @media print {
                    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Reporte de Novedades RRHH</h1>
                <p>Listado Completo de Novedades</p>
            </div>
            
            <div class="info-section">
                <p><strong>Total de registros:</strong> ${datosFiltrados.length}</p>
                <p><strong>Fecha de generación:</strong> ${new Date().toLocaleDateString('es-AR')} ${new Date().toLocaleTimeString('es-AR')}</p>
                ${filtrosInfo.length > 0 ? `<p class="filtros-aplicados"><strong>Filtros aplicados:</strong> ${filtrosInfo.join(' | ')}</p>` : ''}
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">Fecha Registro</th>
                        <th style="width: 5%;">Legajo</th>
                        <th style="width: 15%;">Empleado</th>
                        <th style="width: 12%;">Centro de Costos</th>
                        <th style="width: 15%;">Tipo de Novedad</th>
                        <th style="width: 7%;">Período</th>
                        <th style="width: 8%;">Vigencia Desde</th>
                        <th style="width: 8%;">Vigencia Hasta</th>
                        <th style="width: 10%;">Valor</th>
                        <th style="width: 8%;">Estado</th>
                        <th style="width: 14%;">Observaciones</th>
                    </tr>
                </thead>
                <tbody>
    `;

    datosFiltrados.forEach(novedad => {
        const contexto = NovedadesApp.contextoDesdeTipo ? NovedadesApp.contextoDesdeTipo(parseInt(novedad.tipo_novedad)) : 'numero';
        const valor = (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) 
            ? (NovedadesApp.formatearValor ? NovedadesApp.formatearValor(novedad.valor_numerico, contexto) : novedad.valor_numerico)
            : '-';
        
        const periodoDisplay = (novedad.periodo_mes && novedad.periodo_anio) 
            ? `${novedad.periodo_mes.toString().padStart(2, '0')}/${novedad.periodo_anio}` 
            : '-';
        
        // Estados
        const estados = {
            1: { texto: 'Enviada', clase: 'badge-enviada' },
            2: { texto: 'En Revisión', clase: 'badge-revision' },
            3: { texto: 'Aprobada', clase: 'badge-aprobada' },
            4: { texto: 'A Revisar', clase: 'badge-revisar' },
            5: { texto: 'Procesada', clase: 'badge-procesada' }
        };
        const estadoInfo = estados[novedad.estado_numero] || { texto: novedad.estado || '-', clase: '' };
        
        html += `
            <tr>
                <td style="font-size: 8pt;">${NovedadesApp.formatearFecha(novedad.fecha_creacion)}</td>
                <td><strong>${novedad.legajo}</strong></td>
                <td>${novedad.nombre} ${novedad.apellido}</td>
                <td style="font-size: 8pt;">${novedad.descripcion_centro_costos || novedad.codigo_centro_costos || novedad.sucursal || '-'}</td>
                <td style="font-size: 8pt;">${novedad.tipo_descripcion}</td>
                <td class="text-center" style="font-size: 8pt;">${periodoDisplay}</td>
                <td style="font-size: 8pt;">${novedad.fecha_vigencia ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : '-'}</td>
                <td style="font-size: 8pt;">${novedad.fecha_vigencia_hasta ? NovedadesApp.formatearFecha(novedad.fecha_vigencia_hasta) : '-'}</td>
                <td><strong>${valor}</strong></td>
                <td class="text-center"><span class="badge ${estadoInfo.clase}">${estadoInfo.texto}</span></td>
                <td style="font-size: 8pt;">${novedad.observaciones || '-'}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
            
            <div class="footer">
                <p>Sistema de Novedades RRHH | Generado el ${new Date().toLocaleDateString('es-AR')} a las ${new Date().toLocaleTimeString('es-AR')}</p>
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
async function imprimirNovedad(id) {
    console.log('Iniciando impresión para ID:', id);
    
    if (!id) {
        console.error('ID de novedad no válido:', id);
        mostrarAlerta('Error: ID de novedad no válido', 'danger');
        return;
    }

    try {
        // Obtener datos COMPLETOS desde el backend (igual que mostrarDetalleNovedad)
        const novedad = await NovedadesApp.request('get_novedad', { id: id });
        
        if (!novedad) {
            mostrarAlerta('Novedad no encontrada', 'danger');
            return;
        }

        const tipo = parseInt(novedad.tipo_novedad);
        const ventanaImpresion = window.open('', '_blank');
    
    let html = `
        <html>
        <head>
            <title>Novedad - ${novedad.tipo_descripcion} - ${novedad.nombre} ${novedad.apellido}</title>
            <style>
                @page { 
                    size: A4; 
                    margin: 15mm; 
                }
                body { 
                    font-family: 'Segoe UI', Arial, sans-serif; 
                    margin: 0;
                    padding: 15px;
                    font-size: 11pt;
                    line-height: 1.4;
                    color: #212529;
                }
                .container {
                    max-width: 100%;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 20px; 
                    border-bottom: 2px solid #333;
                    padding-bottom: 12px;
                }
                .header h1 { 
                    color: #212529; 
                    margin: 0 0 5px 0;
                    font-size: 18pt;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .header .subtitle { 
                    color: #495057; 
                    margin: 3px 0;
                    font-size: 10pt;
                }
                .section { 
                    margin-bottom: 15px;
                    page-break-inside: avoid;
                }
                .section-title { 
                    background-color: #f8f9fa;
                    color: #212529; 
                    padding: 6px 10px;
                    margin: 0 0 10px 0;
                    font-size: 11pt;
                    font-weight: 600;
                    border-left: 4px solid #495057;
                    text-transform: uppercase;
                    letter-spacing: 0.3px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse;
                    margin-bottom: 8px;
                }
                th, td { 
                    padding: 6px 10px;
                    text-align: left;
                    border-bottom: 1px solid #dee2e6;
                    vertical-align: top;
                }
                th { 
                    background-color: #f8f9fa;
                    font-weight: 600;
                    width: 32%;
                    font-size: 10pt;
                }
                td {
                    font-size: 10pt;
                }
                .value-highlight { 
                    font-weight: 600;
                    font-size: 14pt;
                }
                .badge { 
                    display: inline-block; 
                    padding: 3px 8px;
                    border-radius: 3px;
                    font-size: 9pt;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.3px;
                }
                .badge-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
                .badge-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
                .badge-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
                .badge-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
                .badge-primary { background-color: #cfe2ff; color: #084298; border: 1px solid #b6d4fe; }
                .badge-secondary { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
                .alert-box {
                    background-color: #fff3cd;
                    border-left: 3px solid #856404;
                    padding: 8px 12px;
                    margin: 8px 0;
                    font-size: 10pt;
                }
                .footer { 
                    margin-top: 20px;
                    padding-top: 12px;
                    text-align: center;
                    font-size: 9pt;
                    color: #6c757d;
                    border-top: 1px solid #dee2e6;
                }
                .row-data {
                    display: table;
                    width: 100%;
                    margin-bottom: 6px;
                }
                .row-data .label {
                    display: table-cell;
                    width: 32%;
                    font-weight: 600;
                    padding: 4px 0;
                    font-size: 10pt;
                }
                .row-data .value {
                    display: table-cell;
                    padding: 4px 0;
                    font-size: 10pt;
                }
                @media print {
                    body { padding: 0; }
                    .section { page-break-inside: avoid; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Registro de Novedad</h1>
                    <div class="subtitle">Sistema de Gestión de Novedades - Recursos Humanos</div>
                    <div class="subtitle">Período: ${(novedad.periodo_mes && novedad.periodo_anio) ? novedad.periodo_mes + '/' + novedad.periodo_anio : 'Período Actual'}</div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">Información del Empleado</h2>
                    <table>
                        <tr><th>Legajo</th><td>${novedad.legajo}</td></tr>
                        <tr><th>Nombre Completo</th><td>${novedad.nombre} ${novedad.apellido}</td></tr>
                        <tr><th>Centro de Costos</th><td>${novedad.centro_costos_display || novedad.descripcion_centro_costos || 'Centro ' + novedad.codigo_centro_costos}</td></tr>
                    </table>
                </div>
                
                <div class="section">
                    <h2 class="section-title">Información General de la Novedad</h2>
                    <table>
                        <tr><th>Tipo de Novedad</th><td><span class="badge badge-primary">${novedad.tipo_descripcion}</span></td></tr>
                        <tr><th>Estado</th><td><span class="badge ${getEstadoClass(novedad.estado_numero || 1)}">${getEstadoText(novedad.estado_numero || 1)}</span></td></tr>
                        <tr><th>Fecha de Registro</th><td>${NovedadesApp.formatearFecha(novedad.fecha_creacion)}</td></tr>
                        ${novedad.fecha_vigencia ? `<tr><th>Fecha de Vigencia</th><td>${NovedadesApp.formatearFecha(novedad.fecha_vigencia)}</td></tr>` : ''}
                    </table>
                </div>
    `;

    // Agregar detalles específicos según el tipo de novedad
    switch(tipo) {
        case 1: // Cambio de sucursal
            let nuevaSucursal = 'No especificado';
            if (novedad.observaciones) {
                let match = novedad.observaciones.match(/Nueva sucursal:\s*<[^>]*>([^<]+)<[^>]*>/);
                if (match) {
                    nuevaSucursal = match[1].trim();
                } else {
                    match = novedad.observaciones.match(/Nueva sucursal:\s*([^-<\\n]+)/);
                    if (match) nuevaSucursal = match[1].trim();
                }
            }
            html += `
                <div class="section">
                    <h2 class="section-title">Detalles del Cambio de Sucursal</h2>
                    <table>
                        <tr><th>Sucursal Actual</th><td>${novedad.descripcion_centro_costos || 'Centro ' + novedad.codigo_centro_costos}</td></tr>
                        <tr><th>Nueva Sucursal</th><td>${nuevaSucursal}</td></tr>
                    </table>
                </div>`;
            break;

        case 2: // Nueva Posición
            html += `
                <div class="section">
                    <h2 class="section-title">Detalles del Cambio de Puesto</h2>
                    <table>
                        <tr><th>Nueva Posición</th><td>${novedad.puesto || 'No especificado'}</td></tr>
                        ${novedad.fecha_vigencia ? `<tr><th>Fecha de vigencia</th><td>${novedad.fecha_vigencia}</td></tr>` : ''}
                    </table>
                </div>`;
            break;

        case 3: // Nuevo salario neto
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                html += `
                    <div class="section">
                        <h2 class="section-title">Detalles del Nuevo Salario</h2>
                        <table>
                            <tr><th>Nuevo Salario Neto</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda')}</td></tr>
                        </table>
                    </div>`;
            }
            break;

        case 4: // Ajuste de premios
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                html += `
                    <div class="section">
                        <h2 class="section-title">Detalles del Ajuste de Premios</h2>
                        <table>
                            <tr><th>Monto del Ajuste</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda')}</td></tr>
                        </table>
                    </div>`;
            }
            break;

        case 5: // Horas extras
        case 6: // Horas adicionales
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                html += `
                    <div class="section">
                        <h2 class="section-title">Detalles de ${tipo === 5 ? 'Horas Extras' : 'Horas Adicionales'}</h2>
                        <table>
                            <tr><th>Cantidad de Horas</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'horas')}</td></tr>
                        </table>
                    </div>`;
            }
            break;

        case 7: // Permiso
            html += `
                <div class="section">
                    <h2 class="section-title">Detalles del Permiso</h2>
                    <table>
                        ${novedad.fecha_permiso ? `<tr><th>Fecha del Permiso</th><td>${NovedadesApp.formatearFecha(novedad.fecha_permiso)}</td></tr>` : ''}
                        ${novedad.tipo_permiso ? `<tr><th>Tipo de Permiso</th><td>${novedad.tipo_permiso}</td></tr>` : ''}
                        <tr><th>Compensa</th><td><span class="badge ${novedad.compensa ? 'badge-success' : 'badge-danger'}">${novedad.compensa ? 'Sí' : 'No'}</span></td></tr>
                    </table>
                </div>`;
            break;

        case 8: // Cortes
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                html += `
                    <div class="section">
                        <h2 class="section-title">Detalles de Cortes</h2>
                        <table>
                            <tr><th>Cantidad de Cortes</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'cortes')}</td></tr>
                        </table>
                    </div>`;
            }
            break;

        case 9: // Producción 25%
        case 10: // Producción 50%
        case 11: // Producción 100%
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                const porcentaje = tipo === 9 ? '25%' : (tipo === 10 ? '50%' : '100%');
                html += `
                    <div class="section">
                        <h2 class="section-title">Detalles de Producción</h2>
                        <table>
                            <tr><th>Porcentaje</th><td><span class="badge badge-success">${porcentaje}</span></td></tr>
                            <tr><th>Cantidad de Unidades</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'unidades')}</td></tr>
                        </table>
                    </div>`;
            }
            break;

        case 12: // Plus de caja (compatibilidad)
        case 32: // Plus de caja
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                html += `
                    <div class="section">
                        <h2 class="section-title">Detalles del Plus de Caja</h2>
                        <table>
                            <tr><th>Importe</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda')}</td></tr>
                        </table>
                    </div>`;
            }
            break;

        case 13: // Plus de Sub-Encargada (compatibilidad)
        case 33: // Plus de Sub-Encargada
            const tieneImporteSub = novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0;
            html += `
                <div class="section">
                    <h2 class="section-title">Detalles del Plus de Sub-Encargada</h2>
                    <table>
                        <tr><th>Tipo de Plus</th><td><span class="badge ${tieneImporteSub ? 'badge-success' : 'badge-info'}">${tieneImporteSub ? 'Con importe fijo' : 'Sin importe fijo'}</span></td></tr>
                        ${tieneImporteSub ? `<tr><th>Importe</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda')}</td></tr>` : ''}
                    </table>
                </div>`;
            break;

        case 14: // Plus de Encargada (compatibilidad)
        case 34: // Plus de Encargada
            const tieneImporteEnc = novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0;
            html += `
                <div class="section">
                    <h2 class="section-title">Detalles del Plus de Encargada</h2>
                    <table>
                        <tr><th>Tipo de Plus</th><td><span class="badge ${tieneImporteEnc ? 'badge-success' : 'badge-info'}">${tieneImporteEnc ? 'Con importe fijo' : 'Sin importe fijo'}</span></td></tr>
                        ${tieneImporteEnc ? `<tr><th>Importe</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda')}</td></tr>` : ''}
                    </table>
                </div>`;
            break;

        case 15: // Premio Local (compatibilidad)
        case 35: // Premio Local
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                // Conversión robusta: cualquier valor "truthy" no-cero se considera true
                const aplicaVendedora = !!(novedad.aplica_vendedora && novedad.aplica_vendedora != 0 && novedad.aplica_vendedora != '0');
                const aplicaSubEnc = !!(novedad.aplica_sub_encargada && novedad.aplica_sub_encargada != 0 && novedad.aplica_sub_encargada != '0');
                
                html += `
                    <div class="section">
                        <h2 class="section-title">Detalles del Premio Local</h2>
                        <table>
                            <tr><th>Importe del Premio</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda')}</td></tr>
                            <tr><th>Aplica a Vendedora</th><td><span class="badge ${aplicaVendedora ? 'badge-success' : 'badge-secondary'}">${aplicaVendedora ? 'Sí' : 'No'}</span></td></tr>
                            <tr><th>Aplica a Sub-Encargada</th><td><span class="badge ${aplicaSubEnc ? 'badge-success' : 'badge-secondary'}">${aplicaSubEnc ? 'Sí' : 'No'}</span></td></tr>
                        </table>
                    </div>`;
            }
            break;

        case 16: // Comisión Individual (compatibilidad)
        case 36: // Comisión Individual
            const tieneTope = Boolean(novedad.tiene_tope);
            const porc1 = parseFloat(novedad.porcentaje_1 || 0) * 100;
            const porc2 = parseFloat(novedad.porcentaje_2 || 0) * 100;
            html += `
                <div class="section">
                    <h2 class="section-title">Detalles de la Comisión Individual</h2>
                    <table>
                        <tr><th>Tipo de Comisión</th><td><span class="badge badge-info">Individual</span></td></tr>
                        <tr><th>Estructura</th><td><span class="badge ${tieneTope ? 'badge-warning' : 'badge-success'}">${tieneTope ? 'Con tope' : 'Sin tope'}</span></td></tr>
                        ${tieneTope ? `
                            <tr><th>Primer Porcentaje</th><td>${porc1.toFixed(2)}% (hasta el tope)</td></tr>
                            <tr><th>Segundo Porcentaje</th><td>${porc2.toFixed(2)}% (sobre excedente)</td></tr>
                        ` : `
                            <tr><th>Porcentaje Único</th><td class="value-highlight">${porc1.toFixed(2)}%</td></tr>
                        `}
                    </table>
                </div>`;
            break;

        case 17: // Comisión sobre Local (compatibilidad)
        case 37: // Comisión sobre Local
            const tieneTopeLocal = Boolean(novedad.tiene_tope);
            const porc1Local = parseFloat(novedad.porcentaje_1 || 0) * 100;
            const porc2Local = parseFloat(novedad.porcentaje_2 || 0) * 100;
            html += `
                <div class="section">
                    <h2 class="section-title">Detalles de la Comisión sobre Local</h2>
                    <table>
                        <tr><th>Tipo de Comisión</th><td><span class="badge badge-primary">Sobre Local</span></td></tr>
                        <tr><th>Estructura</th><td><span class="badge ${tieneTopeLocal ? 'badge-warning' : 'badge-success'}">${tieneTopeLocal ? 'Con tope' : 'Sin tope'}</span></td></tr>
                        ${tieneTopeLocal ? `
                            <tr><th>Primer Porcentaje</th><td>${porc1Local.toFixed(2)}% (hasta el tope)</td></tr>
                            <tr><th>Segundo Porcentaje</th><td>${porc2Local.toFixed(2)}% (sobre excedente)</td></tr>
                        ` : `
                            <tr><th>Porcentaje Único</th><td class="value-highlight">${porc1Local.toFixed(2)}%</td></tr>
                        `}
                    </table>
                </div>`;
            break;

        case 18: // Premios - Ajuste General (compatibilidad)
        case 38: // Premios - Ajuste General
            if (novedad.valor_numerico && parseFloat(novedad.valor_numerico) !== 0) {
                html += `
                    <div class="section">
                        <h2 class="section-title">Detalles del Ajuste General de Premios</h2>
                        <table>
                            <tr><th>Importe del Ajuste</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda')}</td></tr>
                            <tr><th>Descripción</th><td>Ajuste general aplicado a premios</td></tr>
                        </table>
                    </div>`;
            }
            break;

        case 53: // Reemplazo - siempre temporario
            const fechaFinReemplazoFormateada = novedad.fecha_vigencia_hasta || '';
            html += `
                <div class="section">
                    <h2 class="section-title">Detalles del Reemplazo</h2>
                    <table>
                        <tr><th>Puesto de Reemplazo</th><td>${novedad.puesto || 'No especificado'}</td></tr>
                        ${novedad.fecha_vigencia ? `<tr><th>Fecha de Inicio</th><td>${novedad.fecha_vigencia}</td></tr>` : ''}
                        ${fechaFinReemplazoFormateada ? `<tr><th>Fecha de Finalización</th><td>${fechaFinReemplazoFormateada}</td></tr>` : ''}
                    </table>
                    ${fechaFinReemplazoFormateada ? `<div class="alert-box">El empleado regresará a su puesto original el ${fechaFinReemplazoFormateada}.</div>` : ''}
                </div>`;
            break;

        case 55: // A prueba - siempre temporario
            const fechaFinAPruebaFormateada = novedad.fecha_vigencia_hasta || '';
            html += `
                <div class="section">
                    <h2 class="section-title">Detalles de prueba</h2>
                    <table>
                        <tr><th>Puesto de prueba</th><td>${novedad.puesto || 'No especificado'}</td></tr>
                        ${novedad.fecha_vigencia ? `<tr><th>Fecha de Inicio</th><td>${novedad.fecha_vigencia}</td></tr>` : ''}
                        ${fechaFinAPruebaFormateada ? `<tr><th>Fecha de Finalización</th><td>${fechaFinAPruebaFormateada}</td></tr>` : ''}
                    </table>
                    ${fechaFinAPruebaFormateada ? `<div class="alert-box">El empleado finalizará el período de prueba el ${fechaFinAPruebaFormateada}.</div>` : ''}
                </div>`;
            break;

        case 54: // Aumento Salarial
            const tienePorcentaje = novedad.porcentaje_1 && parseFloat(novedad.porcentaje_1) > 0;
            const tieneMonto = novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0;
            const porcentajeDisplay = tienePorcentaje ? (parseFloat(novedad.porcentaje_1) * 100).toFixed(2) : '';
            html += `
                <div class="section">
                    <h2 class="section-title">Detalles del Aumento Salarial</h2>
                    <table>
                        <tr><th>Tipo de Aumento</th><td><span class="badge ${tienePorcentaje ? 'badge-primary' : 'badge-success'}">${tienePorcentaje ? 'Porcentaje' : 'Monto Fijo'}</span></td></tr>
                        ${tienePorcentaje ? `
                            <tr><th>Porcentaje de Aumento</th><td class="value-highlight">${porcentajeDisplay}%</td></tr>
                        ` : `
                            <tr><th>Monto del Aumento</th><td class="value-highlight">${NovedadesApp.formatearValor(novedad.valor_numerico, 'moneda')}</td></tr>
                        `}
                    </table>
                </div>`;
            break;
    }

    // Agregar observaciones si existen
    if (novedad.observaciones && novedad.observaciones.trim() !== '') {
        html += `
            <div class="section">
                <h2 class="section-title">Observaciones</h2>
                <p style="margin: 0; padding: 8px 10px; line-height: 1.5;">${novedad.observaciones}</p>
            </div>`;
    }

    html += `
            <div class="footer">
                <p style="margin: 3px 0;"><strong>Sistema de Gestión de Novedades - Recursos Humanos</strong></p>
                <p style="margin: 3px 0;">Documento generado el ${new Date().toLocaleDateString('es-AR', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric'
                })} a las ${new Date().toLocaleTimeString('es-AR', { 
                    hour: '2-digit', 
                    minute: '2-digit'
                })}</p>
                <p style="margin: 3px 0;">Este documento es una copia fiel del registro en el sistema</p>
            </div>
            </div>
        </body>
        </html>
    `;

    ventanaImpresion.document.write(html);
    ventanaImpresion.document.close();
    ventanaImpresion.onload = function() {
        ventanaImpresion.print();
    };
        
    } catch (error) {
        console.error('Error al imprimir novedad:', error);
        mostrarAlerta('Error al cargar datos para impresión: ' + error.message, 'danger');
    }
}

// Funciones auxiliares para la impresión
function getEstadoClass(estadoNumero) {
    const clases = {
        1: 'badge-info',
        2: 'badge-warning',
        3: 'badge-success',
        4: 'badge-danger',
        5: 'badge-secondary'
    };
    return clases[estadoNumero] || 'badge-secondary';
}

function getEstadoText(estadoNumero) {
    const textos = {
        1: 'Enviada',
        2: 'En Revisión',
        3: 'Aprobada',
        4: 'A Revisar',
        5: 'Procesada'
    };
    return textos[estadoNumero] || 'Estado Desconocido';
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
    
    // Event listeners para filtros normales
    const filtroCentroCostos = document.getElementById('filtro-centro-costos');
    const filtroEstado = document.getElementById('filtro-estado');
    
    if (filtroCentroCostos) {
        filtroCentroCostos.addEventListener('change', aplicarFiltros);
    }
    
    if (filtroEstado) {
        filtroEstado.addEventListener('change', aplicarFiltros);
    }
    
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
        
        $('#filtro-tipo').on('change', function() {
            console.log('Cambio en filtro-tipo:', $(this).val());
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
 * Cargar datos base (centros de costos y tipos de novedad)
 */
async function cargarDatosBase() {
    try {
        // Cargar centros de costos
        const centrosCostos = await NovedadesApp.request('get_centros_costos');
        const centroCostosSelect = document.getElementById('filtro-centro-costos');
        centroCostosSelect.innerHTML = '<option value="">Todos los centros</option>';
        
        centrosCostos.forEach(centro => {
            const option = document.createElement('option');
            option.value = centro.codigo;
            option.textContent = `${centro.codigo} - ${centro.descripcion}`;
            centroCostosSelect.appendChild(option);
        });

        // Cargar tipos de novedad ordenados alfabéticamente
        const tipos = await NovedadesApp.request('get_tipos_novedad');
        
        // Ordenar tipos alfabéticamente por descripción
        tipos.sort((a, b) => a.descripcion.localeCompare(b.descripcion, 'es', { sensitivity: 'base' }));
        
        const tipoSelect = document.getElementById('filtro-tipo');
        tipoSelect.innerHTML = '<option value="">Todos los tipos</option>';
        
        tipos.forEach(tipo => {
            const option = document.createElement('option');
            option.value = tipo.id;
            option.textContent = tipo.descripcion;
            tipoSelect.appendChild(option);
        });
        
        // Reinicializar Select2 para tipos de novedad después de cargar los datos
        if ($('#filtro-tipo').hasClass('select2-hidden-accessible')) {
            $('#filtro-tipo').select2('destroy');
        }
        $('#filtro-tipo').select2({
            theme: 'bootstrap-5',
            placeholder: 'Todos los tipos',
            allowClear: true,
            language: {
                noResults: function () {
                    return 'No se encontraron tipos de novedad';
                },
                searching: function () {
                    return 'Buscando tipos...';
                }
            }
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
    const centrosCostosUnicos = new Set(datosFiltrados.map(n => n.codigo_centro_costos)).size;
    const empleadosUnicos = new Set(datosFiltrados.map(n => n.legajo)).size;
    
    // Sumar valores monetarios según el tipo de novedad
    const valorTotal = datosFiltrados.reduce((sum, n) => {
        const tipo = parseInt(n.tipo_novedad);
        const valorNumerico = parseFloat(n.valor_numerico) || 0;
        
        // Tipos con valores monetarios directos en valor_numerico:
        // 3: Nuevo salario, 4: Ajuste premios
        // 12/32: Plus de caja, 13/33: Plus sub-encargada, 14/34: Plus encargada
        // 15/35: Premio local, 18/38: Premios - Ajuste General
        if (tipo === 3 || tipo === 4 || 
            tipo === 12 || tipo === 32 || 
            tipo === 13 || tipo === 33 || 
            tipo === 14 || tipo === 34 || 
            tipo === 15 || tipo === 35 || 
            tipo === 18 || tipo === 38) {
            return sum + valorNumerico;
        }
        
        // Tipo 54: Aumento Salarial - solo sumar si es tipo monto (no porcentaje)
        if (tipo === 54) {
            const tieneMonto = valorNumerico > 0;
            const tienePorcentaje = n.porcentaje_1 && parseFloat(n.porcentaje_1) > 0;
            // Solo sumar si tiene monto y NO tiene porcentaje (para evitar contar los de tipo porcentaje)
            if (tieneMonto && !tienePorcentaje) {
                return sum + valorNumerico;
            }
        }
        
        return sum;
    }, 0);

    document.getElementById('total-resultados').textContent = totalNovedades;
    document.getElementById('total-centros-costos-filtro').textContent = centrosCostosUnicos;
    document.getElementById('total-empleados-filtro').textContent = empleadosUnicos;
    document.getElementById('total-valores').textContent = NovedadesApp.formatearValor ? NovedadesApp.formatearValor(valorTotal, 'moneda') : valorTotal;
}

/**
 * Editar una novedad existente
 */
async function editarNovedad(id) {
    try {
        console.log('🔄 Iniciando edición de novedad ID:', id);
        
        // Obtener los datos actuales de la novedad
        console.log('📡 Obteniendo datos de novedad...');
        const novedad = await NovedadesApp.request('get_novedad', { id: id });
        console.log('✅ Datos obtenidos:', novedad);
        
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
        try {
            console.log('🔄 Generando formulario de edición...');
            await generarFormularioEdicion(novedad);
            console.log('✅ Formulario generado');
        } catch (formError) {
            console.error('❌ Error generando formulario:', formError);
            document.getElementById('contenido-formulario-editar').innerHTML = `
                <div class="alert alert-danger">
                    <h5>Error cargando formulario</h5>
                    <p>${formError.message}</p>
                    <button class="btn btn-outline-danger btn-sm" onclick="location.reload()">
                        Recargar página
                    </button>
                </div>
            `;
        }
        
    } catch (error) {
        console.error('❌ Error abriendo formulario de edición:', error);
        NovedadesApp.mostrarError('Error abriendo formulario de edición: ' + error.message);
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
        const [tipos, puestos] = await Promise.all([
            NovedadesApp.request('get_tipos_novedad'),
            NovedadesApp.request('get_puestos')
        ]);
        
        const formularioHtml = `
            <div class="row g-3">
                <!-- Información del empleado (solo lectura) -->
                <div class="col-md-4">
                    <label for="edit-legajo-display" class="form-label">Legajo</label>
                    <div class="form-control-plaintext bg-light p-2 rounded border">
                        <strong>${novedad.legajo}</strong>
                    </div>
                    <!-- Campo oculto para mantener el valor -->
                    <input type="hidden" id="edit-legajo" value="${novedad.legajo}">
                </div>
                <div class="col-md-4">
                    <label for="edit-nombre-display" class="form-label">Nombre</label>
                    <div class="form-control-plaintext bg-light p-2 rounded border">
                        <strong>${novedad.nombre}</strong>
                    </div>
                    <!-- Campo oculto para mantener el valor -->
                    <input type="hidden" id="edit-nombre" value="${novedad.nombre}">
                </div>
                <div class="col-md-4">
                    <label for="edit-apellido-display" class="form-label">Apellido</label>
                    <div class="form-control-plaintext bg-light p-2 rounded border">
                        <strong>${novedad.apellido}</strong>
                    </div>
                    <!-- Campo oculto para mantener el valor -->
                    <input type="hidden" id="edit-apellido" value="${novedad.apellido}">
                </div>
                
                <!-- Centro de Costos (solo lectura) -->
                <div class="col-md-6">
                    <label for="edit-centro-costos" class="form-label">Centro de Costos</label>
                    <div class="form-control-plaintext bg-light p-2 rounded">
                        <span class="badge bg-secondary">
                            ${novedad.centro_costos_display || (novedad.descripcion_centro_costos || 'Sin centro') + ' (' + (novedad.codigo_centro_costos || '') + ')'}
                        </span>
                    </div>
                </div>
                
                <!-- Tipo de Novedad (Solo lectura - NO editable) -->
                <div class="col-md-6">
                    <label for="edit-tipo-novedad-display" class="form-label">Tipo de Novedad</label>
                    <div class="form-control-plaintext bg-light p-2 rounded border">
                        <span class="badge bg-primary">${tipos.find(t => t.id == novedad.tipo_novedad)?.descripcion || 'Desconocido'}</span>
                    </div>
                    <!-- Campo oculto para mantener el valor -->
                    <input type="hidden" id="edit-tipo-novedad" value="${novedad.tipo_novedad}">
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
        
        // Cargar datos específicos después de generar el formulario
        await cargarDatosFormularioEdicion(novedad);
        
    } catch (error) {
        console.error('Error generando formulario:', error);
        document.getElementById('contenido-formulario-editar').innerHTML = 
            '<div class="alert alert-danger">Error cargando formulario</div>';
    }
}

/**
 * Cargar datos específicos en el formulario de edición
 */
async function cargarDatosFormularioEdicion(novedad) {
    const tipo = parseInt(novedad.tipo_novedad);
    
    try {
        // Para cambio de sucursal, cargar lista de sucursales
        if (tipo === 1) {
            await cargarSucursalesFormularioEdicion(novedad);
        }
        
        // Para cambio de puesto, cargar lista de puestos
        if (tipo === 2) {
            await cargarPuestosFormularioEdicion(novedad);
        }
        
        // Para reemplazo, cargar lista de puestos
        if (tipo === 53) {
            await cargarPuestosReemplazoFormularioEdicion(novedad);
        }
        
        // Para a prueba, cargar lista de puestos
        if (tipo === 55) {
            await cargarPuestosAPruebaFormularioEdicion(novedad);
        }
        
    } catch (error) {
        console.error('Error cargando datos del formulario:', error);
    }
}

/**
 * Cargar sucursales en el formulario de edición
 */
async function cargarSucursalesFormularioEdicion(novedad) {
    try {
        const sucursales = await NovedadesApp.request('get_sucursales');
        const selectSucursal = document.getElementById('edit-nueva-sucursal');
        const valorActual = document.getElementById('edit-nueva-sucursal-actual')?.value;
        
        if (selectSucursal && sucursales) {
            // Limpiar opciones existentes (excepto la primera)
            selectSucursal.innerHTML = '<option value="">Seleccionar sucursal...</option>';
            
            // Agregar opciones de sucursales
            sucursales.forEach(sucursal => {
                const option = document.createElement('option');
                option.value = sucursal.numero;
                option.textContent = sucursal.descripcion;
                
                // Seleccionar si coincide con el valor actual
                if (valorActual && (valorActual == sucursal.numero || valorActual.includes(sucursal.descripcion))) {
                    option.selected = true;
                }
                
                selectSucursal.appendChild(option);
            });
            
            console.log('✅ Sucursales cargadas en formulario de edición');
        }
    } catch (error) {
        console.error('Error cargando sucursales:', error);
    }
}

/**
 * Generar campos dinámicos para edición según tipo de novedad
 */
function generarCamposDinamicosEdicion(novedad) {
    const tipo = parseInt(novedad.tipo_novedad);
    console.log('🔍 generarCamposDinamicosEdicion - Tipo novedad:', tipo, 'Novedad completa:', novedad);
    let campos = '';
    
    // Fecha de vigencia (común para varios tipos)
    if ([1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 32, 33, 34, 35, 36, 37, 38].includes(tipo)) {
        campos += `
            <div class="col-md-6">
                <label for="edit-fecha-vigencia" class="form-label">Fecha de Vigencia</label>
                <input type="text" class="form-control" id="edit-fecha-vigencia" 
                       value="${novedad.fecha_vigencia || ''}" 
                       placeholder="dd/mm/aaaa" required>
                <small class="text-muted">Formato: dd/mm/aaaa</small>
            </div>
        `;
    }
    
    // Campos específicos según tipo
    switch(tipo) {
        case 1: // Cambio de sucursal
            // Extraer sucursal nueva desde observaciones o campos específicos
            let nuevaSucursal = '';
            let nuevaSucursalId = '';
            
            // Intentar extraer de campos específicos primero
            if (novedad.sucursal_nueva_id) {
                nuevaSucursalId = novedad.sucursal_nueva_id;
            } else if (novedad.observaciones) {
                // Extraer de observaciones como fallback
                const match = novedad.observaciones.match(/Nueva sucursal:\s*([^-\n]*)/);
                if (match) {
                    nuevaSucursal = match[1].trim();
                }
            }
            
            campos += `
                <div class="col-md-6">
                    <label for="edit-nueva-sucursal" class="form-label">Nueva Sucursal</label>
                    <select class="form-select" id="edit-nueva-sucursal" required>
                        <option value="">Seleccionar sucursal...</option>
                        <!-- Se llenarán dinámicamente -->
                    </select>
                    <!-- Campo oculto para valor actual -->
                    <input type="hidden" id="edit-nueva-sucursal-actual" value="${nuevaSucursalId || nuevaSucursal}">
                </div>
            `;
            break;
            
        case 2: // Cambio de puesto
            // PRIORIZAR CAMPOS DIRECTOS: Usar puesto y tipo_nuevo_puesto directamente
            let nuevoPuesto = '';
            
            // PRIORITARIO: Usar el campo puesto directo
            if (novedad.puesto && novedad.puesto !== 'Cambio centro de costos' && novedad.puesto !== 'Ajuste Salario' && novedad.puesto !== 'Premio') {
                nuevoPuesto = novedad.puesto;
            } else if (novedad.observaciones) {
                // Solo como fallback: extraer de observaciones si no hay puesto directo
                // Buscar el patrón más completo primero
                let match = novedad.observaciones.match(/Nueva Posición:\s*([^-]*?)\s*(?:\([^)]*?\))?(?:\s+hasta\s+\d{2}\/\d{2}\/\d{4})?(?:\s*-|$)/);
                if (match) {
                    nuevoPuesto = match[1].trim();
                } else {
                    // Buscar con etiquetas HTML
                    match = novedad.observaciones.match(/Nueva Posición:\s*<[^>]*>([^<]+)<[^>]*>/);
                    if (match) {
                        nuevoPuesto = match[1].trim();
                    }
                }
            }
            
            // Determinar tipo actual (permanente/temporario)
            const tipoActual = novedad.tipo_nuevo_puesto || 'permanente';
            
            campos += `
                <div class="col-md-12">
                    <label for="edit-puesto" class="form-label">Nueva Posición</label>
                    <select class="form-select" id="edit-puesto" required>
                        <option value="">Seleccionar puesto...</option>
                        <!-- Se llenarán dinámicamente -->
                    </select>
                    <!-- Campo oculto para almacenar el puesto original como fallback -->
                    <input type="hidden" id="edit-puesto-original" value="${nuevoPuesto || ''}">
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
            campos += `
                <div class="col-md-6">
                    <label for="edit-fecha-permiso" class="form-label">Fecha del Permiso</label>
                    <input type="text" class="form-control" id="edit-fecha-permiso" 
                           value="${novedad.fecha_permiso || ''}" 
                           placeholder="dd/mm/aaaa" required>
                    <small class="text-muted">Formato: dd/mm/aaaa</small>
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

        case 12: // Plus de caja (compatibilidad)
        case 32: // Plus de caja (ID real BD)
        // Extraer tipo de plus de observaciones
        let tipoPlusCajaEdit = 'recibo'; // default
        if (novedad.observaciones) {
            const match = novedad.observaciones.match(/Tipo:\s*(.+)/);
            if (match) {
                tipoPlusCajaEdit = match[1].trim().toLowerCase();
            }
        }
        
        campos += `
            <div class="col-md-4">
                <label for="edit-tipo-plus-caja" class="form-label">Tipo de Plus</label>
                <select class="form-select" id="edit-tipo-plus-caja" required>
                    <option value="">Seleccione tipo...</option>
                    <option value="recibo" ${tipoPlusCajaEdit === 'recibo' ? 'selected' : ''}>Recibo</option>
                    <option value="premio" ${tipoPlusCajaEdit === 'premio' ? 'selected' : ''}>Premio</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="edit-importe-plus-caja" class="form-label">Importe</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="edit-importe-plus-caja" 
                        value="${novedad.valor_numerico || ''}" step="0.01" min="0">
                </div>
            </div>
        `;
        break;

        case 13: // Plus de Sub-Encargada (compatibilidad)
        case 33: // Plus de Sub-Encargada (ID real BD)
            const tieneImporteSub = novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0 ? '1' : '0';
            
            campos += `
                <div class="col-md-6">
                    <label for="edit-tiene-importe-sub" class="form-label">¿Tiene importe específico?</label>
                    <select class="form-select" id="edit-tiene-importe-sub" required onchange="toggleImporteEdicion('sub')">
                        <option value="1" ${tieneImporteSub === '1' ? 'selected' : ''}>Sí</option>
                        <option value="0" ${tieneImporteSub === '0' ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6" id="edit-campo-importe-sub" style="display: ${tieneImporteSub === '1' ? 'block' : 'none'}">
                    <label for="edit-importe-sub" class="form-label">Importe</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="edit-importe-sub" 
                            value="${novedad.valor_numerico || ''}" step="0.01" min="0">
                    </div>
                </div>
            `;
            break;

        case 14: // Plus de Encargada (compatibilidad)
        case 34: // Plus de Encargada (ID real BD)
            const tieneImporteEnc = novedad.valor_numerico && parseFloat(novedad.valor_numerico) > 0 ? '1' : '0';
            
            campos += `
                <div class="col-md-6">
                    <label for="edit-tiene-importe-enc" class="form-label">¿Tiene importe específico?</label>
                    <select class="form-select" id="edit-tiene-importe-enc" required onchange="toggleImporteEdicion('enc')">
                        <option value="1" ${tieneImporteEnc === '1' ? 'selected' : ''}>Sí</option>
                        <option value="0" ${tieneImporteEnc === '0' ? 'selected' : ''}>No</option>
                    </select>
                </div>
                <div class="col-md-6" id="edit-campo-importe-enc" style="display: ${tieneImporteEnc === '1' ? 'block' : 'none'}">
                    <label for="edit-importe-enc" class="form-label">Importe</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="edit-importe-enc" 
                            value="${novedad.valor_numerico || ''}" step="0.01" min="0">
                    </div>
                </div>
            `;
            break;

        case 15: // Premio Local (compatibilidad)
        case 35: // Premio Local (ID real BD)
            // Determinar cuál opción debe estar seleccionada basado en campos booleanos
            let aplicaSeleccionado = '';
            const vendedora = novedad.aplica_vendedora === true || novedad.aplica_vendedora === 1 || novedad.aplica_vendedora === '1';
            const subEncargada = novedad.aplica_sub_encargada === true || novedad.aplica_sub_encargada === 1 || novedad.aplica_sub_encargada === '1';
            
            if (vendedora) {
                aplicaSeleccionado = 'vendedora';
            } else if (subEncargada) {
                aplicaSeleccionado = 'sub_encargada';
            }
            
            campos += `
                <div class="col-md-4">
                    <label for="edit-importe-premio-local" class="form-label">Importe del Premio</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="edit-importe-premio-local" 
                            value="${novedad.valor_numerico || ''}" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Aplica a</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="edit-aplica-vendedora" 
                            name="edit-aplica-a" value="vendedora" ${aplicaSeleccionado === 'vendedora' ? 'checked' : ''}>
                        <label class="form-check-label" for="edit-aplica-vendedora">
                            <i class="fas fa-user me-2"></i>Vendedora
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" id="edit-aplica-sub-encargada" 
                            name="edit-aplica-a" value="sub_encargada" ${aplicaSeleccionado === 'sub_encargada' ? 'checked' : ''}>
                        <label class="form-check-label" for="edit-aplica-sub-encargada">
                            <i class="fas fa-user-tie me-2"></i>Sub-Encargada
                        </label>
                    </div>
                </div>
            `;
            break;

        case 16: // Comisión Individual (compatibilidad)
        case 36: // Comisión Individual (ID real BD)
            // COMISIÓN INDIVIDUAL: Solo un porcentaje simple
            const porcentajeIndividualEdit = novedad.porcentaje_1 ? parseFloat(novedad.porcentaje_1) : 0;
            console.log('🔍 Generando campos para Comisión Individual - porcentaje:', porcentajeIndividualEdit);
            
            campos += `
                <div class="col-md-6">
                    <label for="edit_porcentaje_individual" class="form-label">Porcentaje Individual</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="edit_porcentaje_individual" 
                            value="${porcentajeIndividualEdit}" step="0.01" min="0.01" max="100" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="edit_fecha_vigencia_comision_individual" class="form-label">Fecha Vigencia</label>
                    <input type="text" class="form-control" id="edit_fecha_vigencia_comision_individual" 
                        value="${novedad.fecha_vigencia || ''}" 
                        placeholder="dd/mm/aaaa" required>
                    <small class="text-muted">Formato: dd/mm/aaaa</small>
                </div>
            `;
            break;

        case 17: // Comisión sobre Local (compatibilidad)
        case 37: // Comisión sobre Local (ID real BD)
            // COMISIÓN SOBRE LOCAL: Con estructura de tope
            const tieneTopeLocalEdit = novedad.tiene_tope;
            const porcentaje1Edit = novedad.porcentaje_1 ? parseFloat(novedad.porcentaje_1) : 0;
            const porcentaje2Edit = novedad.porcentaje_2 ? parseFloat(novedad.porcentaje_2) : 0;
            console.log('🔍 Generando campos para Comisión sobre Local - tiene_tope:', tieneTopeLocalEdit, 'p1:', porcentaje1Edit, 'p2:', porcentaje2Edit);
            
            campos += `
                <div class="col-md-4">
                    <label for="edit-tiene-tope-local" class="form-label">Estructura</label>
                    <select class="form-select" id="edit-tiene-tope-local" required onchange="toggleCamposComisionLocalEdicion()">
                        <option value="0" ${!tieneTopeLocalEdit ? 'selected' : ''}>Sin tope</option>
                        <option value="1" ${tieneTopeLocalEdit ? 'selected' : ''}>Con tope</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="edit_fecha_vigencia_comision_local" class="form-label">Fecha Vigencia</label>
                    <input type="text" class="form-control" id="edit_fecha_vigencia_comision_local" 
                        value="${novedad.fecha_vigencia || ''}" 
                        placeholder="dd/mm/aaaa" required>
                    <small class="text-muted">Formato: dd/mm/aaaa</small>
                </div>
                
                <!-- Campos para sin tope -->
                <div class="col-md-4" id="edit-campos-sin-tope-local" style="display: ${!tieneTopeLocalEdit ? 'block' : 'none'}">
                    <label for="edit-porcentaje-unico-local" class="form-label">Porcentaje Único</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="edit-porcentaje-unico-local" 
                            value="${!tieneTopeLocalEdit ? porcentaje1Edit : ''}" step="0.01" min="0.01" max="100">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                
                <!-- Campos para con tope -->
                <div class="col-md-8" id="edit-campos-con-tope-local" style="display: ${tieneTopeLocalEdit ? 'block' : 'none'}">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="edit-porcentaje-1-local" class="form-label">Primer Porcentaje</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="edit-porcentaje-1-local" 
                                    value="${tieneTopeLocalEdit ? porcentaje1Edit : ''}" step="0.01" min="0.01" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-porcentaje-2-local" class="form-label">Segundo Porcentaje</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="edit-porcentaje-2-local" 
                                    value="${tieneTopeLocalEdit ? porcentaje2Edit : ''}" step="0.01" min="0.01" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 18: // Premios - Ajuste General (compatibilidad)
        case 38: // Premios - Ajuste General (ID real BD)
            campos += `
                <div class="col-md-6">
                    <label for="edit-importe-ajuste-general" class="form-label">Importe del Ajuste</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="edit-importe-ajuste-general" 
                            value="${novedad.valor_numerico || ''}" step="0.01" min="0" required>
                    </div>
                </div>
            `;
            break;

        case 53: // Reemplazo - siempre temporario
            campos += `
                <div class="col-md-6">
                    <label for="edit-puesto-reemplazo" class="form-label">Puesto de Reemplazo <span class="text-danger">*</span></label>
                    <select class="form-select" id="edit-puesto-reemplazo" required>
                        <option value="">Seleccionar puesto...</option>
                        <option value="${novedad.puesto || ''}" selected>${novedad.puesto || 'Puesto actual'}</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="edit-fecha-vigencia-reemplazo" class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit-fecha-vigencia-reemplazo" 
                        value="${novedad.fecha_vigencia || ''}" 
                        placeholder="dd/mm/aaaa" required>
                    <small class="text-muted">Formato: dd/mm/aaaa</small>
                </div>

                <div class="col-md-3" id="edit-campo-fecha-fin-reemplazo">
                    <label for="edit-fecha-hasta-reemplazo" class="form-label">Fecha de Fin <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit-fecha-hasta-reemplazo" 
                        value="${novedad.fecha_vigencia_hasta || ''}" 
                        placeholder="dd/mm/aaaa" required>
                    <small class="text-muted">Formato: dd/mm/aaaa</small>
                </div>
            `;
            break;

        case 55: // A prueba - siempre temporario
            campos += `
                <div class="col-md-6">
                    <label for="edit-puesto-a-prueba" class="form-label">Puesto de prueba <span class="text-danger">*</span></label>
                    <select class="form-select" id="edit-puesto-a-prueba" required>
                        <option value="">Seleccionar puesto...</option>
                        <option value="${novedad.puesto || ''}" selected>${novedad.puesto || 'Puesto actual'}</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="edit-fecha-vigencia-a-prueba" class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit-fecha-vigencia-a-prueba" 
                        value="${novedad.fecha_vigencia || ''}" 
                        placeholder="dd/mm/aaaa" required>
                    <small class="text-muted">Formato: dd/mm/aaaa</small>
                </div>

                <div class="col-md-3" id="edit-campo-fecha-fin-a-prueba">
                    <label for="edit-fecha-hasta-a-prueba" class="form-label">Fecha de Fin <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit-fecha-hasta-a-prueba" 
                        value="${novedad.fecha_vigencia_hasta || ''}" 
                        placeholder="dd/mm/aaaa" required>
                    <small class="text-muted">Formato: dd/mm/aaaa</small>
                </div>
            `;
            break;

        case 55: // A prueba - siempre temporario
            campos += `
                <div class="col-md-6">
                    <label for="edit-puesto-a-prueba" class="form-label">Puesto de prueba <span class="text-danger">*</span></label>
                    <select class="form-select" id="edit-puesto-a-prueba" required>
                        <option value="">Seleccionar puesto...</option>
                        <option value="${novedad.puesto || ''}" selected>${novedad.puesto || 'Puesto actual'}</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="edit-fecha-vigencia-a-prueba" class="form-label">Fecha de Inicio <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit-fecha-vigencia-a-prueba" 
                        value="${novedad.fecha_vigencia || ''}" 
                        placeholder="dd/mm/aaaa" required>
                    <small class="text-muted">Formato: dd/mm/aaaa</small>
                </div>

                <div class="col-md-3" id="edit-campo-fecha-fin-a-prueba">
                    <label for="edit-fecha-hasta-a-prueba" class="form-label">Fecha de Fin <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit-fecha-hasta-a-prueba" 
                        value="${novedad.fecha_vigencia_hasta || ''}" 
                        placeholder="dd/mm/aaaa" required>
                    <small class="text-muted">Formato: dd/mm/aaaa</small>
                </div>
            `;
            break;

        case 54: // Aumento Salarial
            // Detectar tipo de aumento basándose en qué campo tiene valor
            const tienePorcentajeEdit = novedad.porcentaje_1 && parseFloat(novedad.porcentaje_1) > 0;
            const tipoAumentoEdit = tienePorcentajeEdit ? 'porcentaje' : 'monto';
            // IMPORTANTE: El porcentaje se guarda como decimal (0.21), multiplicar por 100 para mostrar en edición (21)
            const porcentajeEditDisplay = novedad.porcentaje_1 ? (parseFloat(novedad.porcentaje_1) * 100).toFixed(2) : '';
            campos += `
                <!-- Tipo de Aumento -->
                <div class="col-md-12 mb-3">
                    <label class="form-label">Tipo de Aumento <span class="text-danger">*</span></label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="edit-tipo-aumento" 
                                    id="edit-tipo-aumento-porcentaje" value="porcentaje" 
                                    ${tipoAumentoEdit === 'porcentaje' ? 'checked' : ''} 
                                    onchange="toggleTipoAumentoEdicion()">
                                <label class="form-check-label" for="edit-tipo-aumento-porcentaje">
                                    <i class="fas fa-percentage text-primary me-2"></i>Porcentaje
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="edit-tipo-aumento" 
                                    id="edit-tipo-aumento-monto" value="monto" 
                                    ${tipoAumentoEdit === 'monto' ? 'checked' : ''} 
                                    onchange="toggleTipoAumentoEdicion()">
                                <label class="form-check-label" for="edit-tipo-aumento-monto">
                                    <i class="fas fa-dollar-sign text-success me-2"></i>Monto Fijo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4" id="edit-campo-porcentaje-aumento" style="display: ${tipoAumentoEdit === 'porcentaje' ? 'block' : 'none'}">
                    <label for="edit-porcentaje-aumento" class="form-label">Porcentaje <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="edit-porcentaje-aumento" 
                            value="${porcentajeEditDisplay}" step="0.01" min="0.01" max="100">
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                <div class="col-md-4" id="edit-campo-monto-aumento" style="display: ${tipoAumentoEdit === 'monto' ? 'block' : 'none'}">
                    <label for="edit-monto-aumento" class="form-label">Monto <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="edit-monto-aumento" 
                            value="${novedad.valor_numerico || ''}" step="0.01" min="0.01">
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="edit-fecha-vigencia-aumento" class="form-label">Fecha de Vigencia <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit-fecha-vigencia-aumento" 
                        value="${novedad.fecha_vigencia || ''}" 
                        placeholder="dd/mm/aaaa" required>
                    <small class="text-muted">Formato: dd/mm/aaaa</small>
                </div>
            `;
            break;
    }
    
    return campos;
}

/**
 * Wrapper para actualizarFormularioEdicion que maneja async
 */
function actualizarFormularioEdicionAsync() {
    actualizarFormularioEdicion().catch(error => {
        console.error('Error actualizando formulario de edición:', error);
    });
}

/**
 * Actualizar formulario de edición cuando cambia el tipo
 */
async function actualizarFormularioEdicion() {
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
    
    // Cargar datos específicos según el tipo
    const tipo = parseInt(tipoSeleccionado);
    if (tipo === 1) {
        // Para cambio de sucursal, cargar sucursales
        await cargarSucursalesFormularioEdicion(novedadTemp);
    } else if (tipo === 2) {
        // Para cambio de puesto, cargar puestos (si ya existe esa función)
        // await cargarPuestosFormularioEdicion(novedadTemp);
    }
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
            tipo_novedad: document.getElementById('edit-tipo-novedad').value,
            observaciones: limpiarObservaciones(document.getElementById('edit-observaciones').value, parseInt(document.getElementById('edit-tipo-novedad').value))
        };
        
        // Agregar campos específicos según tipo
        const tipo = parseInt(datos.tipo_novedad);
        
        // Fecha de vigencia - convertir al formato del backend
        const fechaVigencia = document.getElementById('edit-fecha-vigencia');
        if (fechaVigencia && fechaVigencia.value) {
            datos.fecha_vigencia = convertirFechaParaBackend(fechaVigencia.value);
            console.log(`📅 Fecha de vigencia capturada:`, fechaVigencia.value, '→', datos.fecha_vigencia);
        } else {
            console.error('❌ No se encontró el campo edit-fecha-vigencia o está vacío');
        }
        
        // Campos específicos por tipo
        switch(tipo) {
            case 1: // Cambio de sucursal
                const nuevaSucursal = document.getElementById('edit-nueva-sucursal');
                if (nuevaSucursal && nuevaSucursal.value) {
                    datos.sucursal_nueva_id = nuevaSucursal.value;
                    console.log(`🏢 Nueva sucursal capturada:`, nuevaSucursal.value);
                } else {
                    console.error('❌ No se pudo obtener el valor de la nueva sucursal');
                }
                break;
                
            case 2: // Cambio de puesto
                const puesto = document.getElementById('edit-puesto');
                const puestoOriginal = document.getElementById('edit-puesto-original');
                
                if (puesto && puesto.value) {
                    datos.puesto = puesto.value;
                    console.log(`👔 Puesto capturado desde select:`, puesto.value);
                } else if (puestoOriginal && puestoOriginal.value) {
                    datos.puesto = puestoOriginal.value;
                    console.log(`👔 Puesto capturado desde fallback:`, puestoOriginal.value);
                } else {
                    console.error('❌ No se pudo obtener el valor del puesto - verificar que el campo esté cargado');
                }
                
                // Siempre permanente
                datos.tipo_nuevo_puesto = 'permanente';
                datos.fecha_vigencia_hasta = null;
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
            
            case 12: // Plus de caja (compatibilidad)
            case 32: // Plus de caja (ID real BD)
                datos.tipo_plus_caja = document.getElementById('edit-tipo-plus-caja')?.value;
                const importePlusCaja = document.getElementById('edit-importe-plus-caja');
                if (importePlusCaja && importePlusCaja.value) {
                    datos.importe = parseFloat(importePlusCaja.value);
                }
                break;

            case 13: // Plus de Sub-Encargada (compatibilidad)
            case 33: // Plus de Sub-Encargada (ID real BD)
                datos.tiene_importe = document.getElementById('edit-tiene-importe-sub')?.value;
                if (datos.tiene_importe === '1') {
                    const importeSub = document.getElementById('edit-importe-sub');
                    if (importeSub && importeSub.value) {
                        datos.importe = parseFloat(importeSub.value);
                    }
                }
                break;

            case 14: // Plus de Encargada (compatibilidad)
            case 34: // Plus de Encargada (ID real BD)
                datos.tiene_importe = document.getElementById('edit-tiene-importe-enc')?.value;
                if (datos.tiene_importe === '1') {
                    const importeEnc = document.getElementById('edit-importe-enc');
                    if (importeEnc && importeEnc.value) {
                        datos.importe = parseFloat(importeEnc.value);
                    }
                }
                break;

            case 15: // Premio Local (compatibilidad)
            case 35: // Premio Local (ID real BD)
                const importePremioLocal = document.getElementById('edit-importe-premio-local');
                if (importePremioLocal && importePremioLocal.value) {
                    datos.importe = parseFloat(importePremioLocal.value);
                }
                // Obtener el valor del radio button seleccionado y convertir a campos booleanos
                const aplicaRadio = document.querySelector('input[name="edit-aplica-a"]:checked');
                if (aplicaRadio) {
                    datos.aplica_vendedora = aplicaRadio.value === 'vendedora';
                    datos.aplica_sub_encargada = aplicaRadio.value === 'sub_encargada';
                }
                break;

            case 16: // Comisión Individual (compatibilidad)
            case 36: // Comisión Individual (ID real BD)
                // COMISIÓN INDIVIDUAL: Solo un porcentaje simple
                const porcentajeIndEdit = document.getElementById('edit_porcentaje_individual');
                console.log('🔍 Debug Comisión Individual - elemento encontrado:', porcentajeIndEdit);
                console.log('🔍 Debug Comisión Individual - valor:', porcentajeIndEdit ? porcentajeIndEdit.value : 'ELEMENTO NO ENCONTRADO');
                
                if (porcentajeIndEdit) {
                    datos.porcentaje_individual = parseFloat(porcentajeIndEdit.value) || 0;
                    console.log('✅ Porcentaje individual agregado:', datos.porcentaje_individual);
                } else {
                    console.error('❌ Elemento edit_porcentaje_individual no encontrado en el DOM');
                }
                
                // Agregar fecha de vigencia específica para comisión individual
                const fechaVigenciaComisionInd = document.getElementById('edit_fecha_vigencia_comision_individual');
                if (fechaVigenciaComisionInd && fechaVigenciaComisionInd.value) {
                    datos.fecha_vigencia = convertirFechaParaBackend(fechaVigenciaComisionInd.value);
                }
                break;

            case 17: // Comisión sobre Local (compatibilidad)
            case 37: // Comisión sobre Local (ID real BD)
                // COMISIÓN SOBRE LOCAL: Con estructura de tope
                const tieneTopeLocal = document.getElementById('edit-tiene-tope-local');
                
                if (tieneTopeLocal) {
                    datos.tiene_tope = tieneTopeLocal.value === '1';
                    
                    if (datos.tiene_tope) {
                        // Con tope: dos porcentajes
                        const p1 = document.getElementById('edit-porcentaje-1-local');
                        const p2 = document.getElementById('edit-porcentaje-2-local');
                        if (p1) datos.porcentaje_1 = parseFloat(p1.value) || 0;
                        if (p2) datos.porcentaje_2 = parseFloat(p2.value) || 0;
                    } else {
                        // Sin tope: un porcentaje
                        const pUnico = document.getElementById('edit-porcentaje-unico-local');
                        if (pUnico) datos.porcentaje_unico = parseFloat(pUnico.value) || 0;
                    }
                }
                
                // Agregar fecha de vigencia específica para comisión sobre local
                const fechaVigenciaComisionLocal = document.getElementById('edit_fecha_vigencia_comision_local');
                if (fechaVigenciaComisionLocal && fechaVigenciaComisionLocal.value) {
                    datos.fecha_vigencia = convertirFechaParaBackend(fechaVigenciaComisionLocal.value);
                }
                break;

            case 18: // Premios - Ajuste General (compatibilidad)
            case 38: // Premios - Ajuste General (ID real BD)
                const importeAjuste = document.getElementById('edit-importe-ajuste-general');
                if (importeAjuste && importeAjuste.value) {
                    datos.importe = parseFloat(importeAjuste.value);
                }
                break;

            case 53: // Reemplazo
                const puestoReemplazo = document.getElementById('edit-puesto-reemplazo');
                if (puestoReemplazo && puestoReemplazo.value) {
                    datos.puesto = puestoReemplazo.value;
                    console.log(`👔 Puesto de reemplazo capturado:`, puestoReemplazo.value);
                }
                
                // IMPORTANTE: Capturar fecha de inicio de vigencia
                const fechaVigenciaReemplazo = document.getElementById('edit-fecha-vigencia-reemplazo');
                if (fechaVigenciaReemplazo && fechaVigenciaReemplazo.value) {
                    datos.fecha_vigencia = convertirFechaParaBackend(fechaVigenciaReemplazo.value);
                    console.log(`📅 Fecha de inicio de reemplazo capturada:`, fechaVigenciaReemplazo.value, '→', datos.fecha_vigencia);
                }
                
                // Siempre temporario
                datos.tipo_reemplazo = 'temporario';
                const fechaFinReemplazo = document.getElementById('edit-fecha-hasta-reemplazo');
                if (fechaFinReemplazo && fechaFinReemplazo.value) {
                    datos.fecha_vigencia_hasta = convertirFechaParaBackend(fechaFinReemplazo.value);
                    console.log(`📅 Fecha de fin de reemplazo capturada:`, fechaFinReemplazo.value, '→', datos.fecha_vigencia_hasta);
                }
                break;

            case 55: // A prueba
                const puestoAPrueba = document.getElementById('edit-puesto-a-prueba');
                if (puestoAPrueba && puestoAPrueba.value) {
                    datos.puesto = puestoAPrueba.value;
                    console.log(`👔 Puesto de prueba capturado:`, puestoAPrueba.value);
                }
                
                // IMPORTANTE: Capturar fecha de inicio de vigencia
                const fechaVigenciaAPrueba = document.getElementById('edit-fecha-vigencia-a-prueba');
                if (fechaVigenciaAPrueba && fechaVigenciaAPrueba.value) {
                    datos.fecha_vigencia = convertirFechaParaBackend(fechaVigenciaAPrueba.value);
                    console.log(`📅 Fecha de inicio de a prueba capturada:`, fechaVigenciaAPrueba.value, '→', datos.fecha_vigencia);
                }
                
                // Siempre temporario
                datos.tipo_reemplazo = 'temporario';
                const fechaFinAPrueba = document.getElementById('edit-fecha-hasta-a-prueba');
                if (fechaFinAPrueba && fechaFinAPrueba.value) {
                    datos.fecha_vigencia_hasta = convertirFechaParaBackend(fechaFinAPrueba.value);
                    console.log(`📅 Fecha de fin de a prueba capturada:`, fechaFinAPrueba.value, '→', datos.fecha_vigencia_hasta);
                }
                break;

            case 54: // Aumento Salarial
                // IMPORTANTE: Capturar fecha de vigencia
                const fechaVigenciaAumento = document.getElementById('edit-fecha-vigencia-aumento');
                if (fechaVigenciaAumento && fechaVigenciaAumento.value) {
                    datos.fecha_vigencia = convertirFechaParaBackend(fechaVigenciaAumento.value);
                    console.log(`📅 Fecha de vigencia de aumento capturada:`, fechaVigenciaAumento.value, '→', datos.fecha_vigencia);
                }
                
                // Obtener tipo de aumento (porcentaje/monto)
                const tipoAumentoRadios = document.getElementsByName('edit-tipo-aumento');
                let tipoAumento = 'porcentaje'; // default
                for (let radio of tipoAumentoRadios) {
                    if (radio.checked) {
                        tipoAumento = radio.value;
                        break;
                    }
                }
                datos.tipo_aumento = tipoAumento;
                console.log(`💰 Tipo de aumento capturado:`, tipoAumento);
                
                // Recopilar valor según tipo
                if (tipoAumento === 'porcentaje') {
                    const porcentajeAumento = document.getElementById('edit-porcentaje-aumento');
                    if (porcentajeAumento && porcentajeAumento.value) {
                        // NO dividir por 100 aquí - el backend lo hará (enviar 21, no 0.21)
                        datos.porcentaje_1 = parseFloat(porcentajeAumento.value);
                        datos.valor_numerico = null; // Limpiar monto
                        console.log(`📊 Porcentaje de aumento capturado: ${datos.porcentaje_1}% (backend lo convertirá a decimal)`);
                    }
                } else { // monto
                    const montoAumento = document.getElementById('edit-monto-aumento');
                    if (montoAumento && montoAumento.value) {
                        datos.valor_numerico = parseFloat(montoAumento.value);
                        datos.porcentaje_1 = null; // Limpiar porcentaje
                        console.log(`💵 Monto de aumento capturado:`, datos.valor_numerico);
                    }
                }
                break;
        }
        
        console.log('📋 Datos completos a enviar:', datos);
        
        // DEBUG: Mostrar datos específicos para depuración
        console.group('🔍 Debug - Datos de edición por tipo');
        console.log('Tipo de novedad:', tipo);
        console.log('Legajo:', datos.legajo);
        console.log('Nombre:', datos.nombre);
        console.log('Apellido:', datos.apellido);
        console.log('Fecha vigencia:', datos.fecha_vigencia);
        console.log('Observaciones:', datos.observaciones);
        
        if (tipo >= 12 && tipo <= 18 || tipo >= 32 && tipo <= 38) {
            console.log('📝 Datos para tipos 12-18/32-38:');
            console.log('- Importe:', datos.importe);
            console.log('- Tipo plus caja:', datos.tipo_plus_caja);
            console.log('- Tiene importe:', datos.tiene_importe);
            console.log('- Aplica vendedora:', datos.aplica_vendedora);
            console.log('- Aplica sub encargada:', datos.aplica_sub_encargada);
            console.log('- Tiene tope:', datos.tiene_tope);
            console.log('- Porcentaje 1:', datos.porcentaje_1);
            console.log('- Porcentaje 2:', datos.porcentaje_2);
            console.log('- Porcentaje único:', datos.porcentaje_unico);
        }
        console.groupEnd();
        
        // Validar y truncar campos según límites de BD
        if (datos.nombre && datos.nombre.length > 50) {
            console.warn(`⚠️ Truncando nombre de ${datos.nombre.length} a 50 caracteres`);
            datos.nombre = datos.nombre.substring(0, 50);
        }
        if (datos.apellido && datos.apellido.length > 50) {
            console.warn(`⚠️ Truncando apellido de ${datos.apellido.length} a 50 caracteres`);
            datos.apellido = datos.apellido.substring(0, 50);
        }
        if (datos.puesto && datos.puesto.length > 50) {
            console.warn(`⚠️ Truncando puesto de ${datos.puesto.length} a 50 caracteres`);
            datos.puesto = datos.puesto.substring(0, 50);
        }
        if (datos.tipo_permiso && datos.tipo_permiso.length > 20) {
            console.warn(`⚠️ Truncando tipo_permiso de ${datos.tipo_permiso.length} a 20 caracteres`);
            datos.tipo_permiso = datos.tipo_permiso.substring(0, 20);
        }
        if (datos.tipo_nuevo_puesto && datos.tipo_nuevo_puesto.length > 20) {
            console.warn(`⚠️ Truncando tipo_nuevo_puesto de ${datos.tipo_nuevo_puesto.length} a 20 caracteres`);
            datos.tipo_nuevo_puesto = datos.tipo_nuevo_puesto.substring(0, 20);
        }
        
        // Validar datos requeridos
        if (!datos.legajo || !datos.nombre || !datos.apellido || !datos.tipo_novedad) {
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
                
            case 'centro_costos':
                valorA = a.descripcion_centro_costos?.toLowerCase() || '';
                valorB = b.descripcion_centro_costos?.toLowerCase() || '';
                esVacioA = !valorA || valorA === '';
                esVacioB = !valorB || valorB === '';
                // Si no hay descripcion_centro_costos, usar código como fallback
                if (esVacioA) valorA = a.codigo_centro_costos ? `centro ${a.codigo_centro_costos}` : '';
                if (esVacioB) valorB = b.codigo_centro_costos ? `centro ${b.codigo_centro_costos}` : '';
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
                    valorA = a.periodo_anio * 100 + a.periodo_mes; // Ej: 2024*100 + 8 = 202548
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
                // Ordenar por estado numérico usando estado_numero
                valorA = parseInt(a.estado_numero) || parseInt(a.estado) || 1;
                valorB = parseInt(b.estado_numero) || parseInt(b.estado) || 1;
                esVacioA = !a.estado_numero && !a.estado;
                esVacioB = !b.estado_numero && !b.estado;
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
 * Cargar puestos para reemplazo en el formulario de edición
 */
async function cargarPuestosReemplazoFormularioEdicion(novedad) {
    try {
        const puestos = await NovedadesApp.request('get_puestos');
        const select = document.getElementById('edit-puesto-reemplazo');
        
        if (!select) {
            console.error('Select edit-puesto-reemplazo no encontrado');
            return;
        }
        
        // Limpiar opciones actuales
        select.innerHTML = '<option value="">Seleccionar puesto...</option>';
        
        // Agregar puestos
        puestos.forEach(puesto => {
            const option = document.createElement('option');
            option.value = puesto.nombre_puesto;
            option.textContent = puesto.nombre_puesto;
            
            // Seleccionar si coincide con el puesto actual
            if (novedad.puesto && puesto.nombre_puesto === novedad.puesto) {
                option.selected = true;
            }
            
            select.appendChild(option);
        });
        
        console.log('👔 Puestos cargados para reemplazo, seleccionado:', novedad.puesto);
        
    } catch (error) {
        console.error('Error cargando puestos para reemplazo:', error);
    }
}

/**
 * Cargar puestos en el formulario de edición para A prueba
 */
async function cargarPuestosAPruebaFormularioEdicion(novedad) {
    try {
        const puestos = await NovedadesApp.request('get_puestos');
        const select = document.getElementById('edit-puesto-a-prueba');
        
        if (!select) {
            console.error('Select edit-puesto-a-prueba no encontrado');
            return;
        }
        
        // Limpiar opciones actuales
        select.innerHTML = '<option value="">Seleccionar puesto...</option>';
        
        // Agregar puestos
        puestos.forEach(puesto => {
            const option = document.createElement('option');
            option.value = puesto.nombre_puesto;
            option.textContent = puesto.nombre_puesto;
            
            // Seleccionar si coincide con el puesto actual
            if (novedad.puesto && puesto.nombre_puesto === novedad.puesto) {
                option.selected = true;
            }
            
            select.appendChild(option);
        });
        
        console.log('👔 Puestos cargados para a prueba, seleccionado:', novedad.puesto);
        
    } catch (error) {
        console.error('Error cargando puestos para a prueba:', error);
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
            // Mantener compatibilidad con observaciones existentes que usen "centro de costos"
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nuevo centro de costos:\s*<[^>]*>([^<]+)<[^>]*>/gi, '');
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nuevo centro de costos:\s*([^-<\n]+)/gi, '');
            break;
            
        case 2: // Cambio de puesto
            // Remover " - Nueva Posición: [NOMBRE]" incluyendo etiquetas y detalles completos
            // Patrón para HTML: - Nueva Posición: <tag>NOMBRE</tag> (Tipo) hasta DD/MM/YYYY
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nueva Posición:\s*<[^>]*>([^<]+)<[^>]*>\s*\([^)]*\)(?:\s+hasta\s+\d{2}\/\d{2}\/\d{4})?/gi, '');
            // Patrón para texto plano: - Nueva Posición: NOMBRE (Tipo) hasta DD/MM/YYYY
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nueva Posición:\s*[^-]*?\([^)]*?\)(?:\s+hasta\s+\d{2}\/\d{2}\/\d{4})?/gi, '');
            // Patrón para texto simple: - Nueva Posición: NOMBRE
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nueva Posición:\s*([^-<\n]*?)(?=\s*-|\s*$|<|\n)/gi, '');
            // Mantener compatibilidad con formato anterior "Nuevo puesto:"
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nuevo puesto:\s*<[^>]*>([^<]+)<[^>]*>\s*\([^)]*\)(?:\s+hasta\s+\d{2}\/\d{2}\/\d{4})?/gi, '');
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nuevo puesto:\s*[^-]*?\([^)]*?\)(?:\s+hasta\s+\d{2}\/\d{2}\/\d{4})?/gi, '');
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Nuevo puesto:\s*([^-<\n]*?)(?=\s*-|\s*$|<|\n)/gi, '');
            break;
            
        case 5: // Horas extras
            // Remover " - X horas extras"
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*\d+\s*horas extras/gi, '');
            break;
            
        case 6: // Horas adicionales
            // Remover " - X horas adicionales"
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*\d+\s*horas adicionales/gi, '');
            break;
            
        case 8: // Cortes
            // Remover " - X cortes"
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*\d+\s*cortes/gi, '');
            break;
            
        case 9: // Producción 25%
            // Remover " - X unidades (25%)"
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*\d+\s*unidades\s*\(25%\)/gi, '');
            break;
            
        case 10: // Producción 50%
            // Remover " - X unidades (50%)"
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*\d+\s*unidades\s*\(50%\)/gi, '');
            break;
            
        case 11: // Producción 100%
            // Remover " - X unidades (100%)"
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*\d+\s*unidades\s*\(100%\)/gi, '');
            break;
            
        case 12: // Plus de caja
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Plus de caja:\s*[^-<\n]+/gi, '');
            break;

        case 13: // Plus de Sub-Encargada
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Plus Sub-Encargada:\s*[^-<\n]+/gi, '');
            break;

        case 14: // Plus de Encargada
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Plus Encargada:\s*[^-<\n]+/gi, '');
            break;

        case 15: // Premio Local
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Premio Local:\s*[^-<\n]+/gi, '');
            break;

        case 16: // Comisión Individual
        case 17: // Comisión sobre Local
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Comisión:\s*[^-<\n]+/gi, '');
            break;

        case 18: // Premios - Ajuste General
            observacionesLimpias = observacionesLimpias.replace(/\s*-\s*Ajuste General:\s*[^-<\n]+/gi, '');
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

/**
 * Cambiar el estado de una novedad
 */
async function cambiarEstadoNovedad(novedadId, nuevoEstado) {
    if (!novedadId || !nuevoEstado) {
        NovedadesApp.mostrarError('Datos de novedad y estado requeridos');
        return;
    }

    try {
        // Convertir el número de estado a string que espera la BD
        const estadoString = mapearEstado(nuevoEstado);
        
        const resultado = await NovedadesApp.request('cambiar_estado_novedad', {
            novedad_id: novedadId,
            nuevo_estado: estadoString
        }, 'POST');

        if (resultado && resultado.success) {
            NovedadesApp.mostrarExito('Estado cambiado correctamente');
            // Recargar datos para reflejar el cambio
            await cargarNovedades();
        } else {
            const errorMsg = resultado ? resultado.message : 'Respuesta vacía del servidor';
            NovedadesApp.mostrarError(errorMsg || 'Error cambiando estado');
        }
    } catch (error) {
        console.error('Error cambiando estado:', error);
        NovedadesApp.mostrarError('Error de conexión cambiando estado');
    }
}

/**
 * Mostrar modal para cambiar estado de novedad
 */
function mostrarModalCambiarEstado(novedadId, estadoActual) {
    const estados = {
        1: 'Enviada',
        2: 'En Revisión', 
        3: 'Aprobada',
        4: 'A Revisar',
        5: 'Procesada'
    };

    let optionsHtml = '';
    for (const [id, nombre] of Object.entries(estados)) {
        const selected = parseInt(id) === parseInt(estadoActual) ? 'selected' : '';
        optionsHtml += `<option value="${id}" ${selected}>${nombre}</option>`;
    }

    const modalHtml = `
        <div class="modal fade" id="modalCambiarEstado" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cambiar Estado de Novedad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="selectEstado" class="form-label">Nuevo Estado:</label>
                            <select class="form-select" id="selectEstado">
                                ${optionsHtml}
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="confirmarCambioEstado(${novedadId})">
                            Cambiar Estado
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal existente si existe
    const existingModal = document.getElementById('modalCambiarEstado');
    if (existingModal) {
        existingModal.remove();
    }

    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalCambiarEstado'));
    modal.show();
}

/**
 * Confirmar cambio de estado
 */
async function confirmarCambioEstado(novedadId) {
    const nuevoEstado = document.getElementById('selectEstado').value;
    
    if (!nuevoEstado) {
        NovedadesApp.mostrarError('Debe seleccionar un estado');
        return;
    }

    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalCambiarEstado'));
    modal.hide();

    // Cambiar estado
    await cambiarEstadoNovedad(novedadId, parseInt(nuevoEstado));
}

/**
 * Función para mostrar/ocultar campo de fecha de fin en edición
 */
function toggleFechaFinEdicion() {
    const radioTemporario = document.getElementById('edit-tipo-temporario');
    const campoFechaFin = document.getElementById('edit-campo-fecha-fin');
    const fechaFinInput = document.getElementById('edit-fecha-fin');
    
    if (radioTemporario && radioTemporario.checked) {
        // Mostrar campo de fecha de fin
        if (campoFechaFin) {
            campoFechaFin.style.display = 'block';
        }
        if (fechaFinInput) {
            fechaFinInput.setAttribute('required', 'required');
        }
        console.log('📅 Modo temporario activado - mostrando fecha de fin');
    } else {
        // Ocultar campo de fecha de fin
        if (campoFechaFin) {
            campoFechaFin.style.display = 'none';
        }
        if (fechaFinInput) {
            fechaFinInput.removeAttribute('required');
            fechaFinInput.value = ''; // Limpiar valor
        }
        console.log('✅ Modo permanente activado - ocultando fecha de fin');
    }
}

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

/**
 * Toggle campos de importe en edición
 */
function toggleImporteEdicion(tipo) {
    const select = document.getElementById(`edit-tiene-importe-${tipo}`);
    const campoImporte = document.getElementById(`edit-campo-importe-${tipo}`);
    
    if (select && campoImporte) {
        if (select.value === '1') {
            campoImporte.style.display = 'block';
            const input = campoImporte.querySelector('input');
            if (input) input.setAttribute('required', 'required');
        } else {
            campoImporte.style.display = 'none';
            const input = campoImporte.querySelector('input');
            if (input) {
                input.removeAttribute('required');
                input.value = '';
            }
        }
    }
}

/**
 * Toggle campos de comisión en edición
 */
function toggleCamposComisionEdicion(sufijo) {
    const selectTope = document.getElementById(`edit-tiene-tope-${sufijo}`);
    const camposSinTope = document.getElementById(`edit-campos-sin-tope-${sufijo}`);
    const camposConTope = document.getElementById(`edit-campos-con-tope-${sufijo}`);
    
    if (selectTope.value === '1') {
        // Con tope
        if (camposConTope) {
            camposConTope.style.display = 'block';
            camposConTope.querySelectorAll('input').forEach(input => {
                input.setAttribute('required', 'required');
            });
        }
        if (camposSinTope) {
            camposSinTope.style.display = 'none';
            camposSinTope.querySelectorAll('input').forEach(input => {
                input.removeAttribute('required');
                input.value = '';
            });
        }
    } else if (selectTope.value === '0') {
        // Sin tope
        if (camposSinTope) {
            camposSinTope.style.display = 'block';
            camposSinTope.querySelectorAll('input').forEach(input => {
                input.setAttribute('required', 'required');
            });
        }
        if (camposConTope) {
            camposConTope.style.display = 'none';
            camposConTope.querySelectorAll('input').forEach(input => {
                input.removeAttribute('required');
                input.value = '';
            });
        }
    }
    
    // Actualizar el detalle visible inmediatamente
    actualizarDetalleComisionEnTiempoReal(sufijo);
}

/**
 * Actualizar detalle de comisión en tiempo real cuando cambia la estructura
 */
function actualizarDetalleComisionEnTiempoReal(sufijo) {
    const selectTope = document.getElementById(`edit-tiene-tope-${sufijo}`);
    const estructuraElement = document.querySelector('.detalle-estructura-comision');
    const porcentajesElement = document.querySelector('.detalle-porcentajes-comision');
    
    if (estructuraElement && porcentajesElement) {
        const tieneTopeNuevo = selectTope.value === '1';
        
        // Actualizar estructura
        estructuraElement.innerHTML = `<span class="badge ${tieneTopeNuevo ? 'bg-warning text-dark' : 'bg-success'}">${tieneTopeNuevo ? 'Con tope' : 'Sin tope'}</span>`;
        
        // Actualizar porcentajes en tiempo real
        if (tieneTopeNuevo) {
            porcentajesElement.innerHTML = '<i class="fas fa-percentage me-2"></i><strong>Porcentajes:</strong> Se actualizarán al guardar';
        } else {
            porcentajesElement.innerHTML = '<i class="fas fa-percentage me-2"></i><strong>Porcentajes:</strong> Se actualizarán al guardar';
        }
    }
}
/**
 * Función para alternar campos de comisión sobre local en modal de edición
 */
function toggleCamposComisionLocalEdicion() {
    const selectTope = document.getElementById('edit-tiene-tope-local');
    const camposSinTope = document.getElementById('edit-campos-sin-tope-local');
    const camposConTope = document.getElementById('edit-campos-con-tope-local');
    
    if (!selectTope || !camposSinTope || !camposConTope) {
        console.error('No se encontraron los elementos necesarios para toggle de comisión local');
        return;
    }
    
    const tieneTopeNuevo = selectTope.value === '1';
    console.log('Cambiando estructura de comisión local - Con tope:', tieneTopeNuevo);
    
    if (tieneTopeNuevo) {
        // Mostrar campos con tope (dos porcentajes)
        camposSinTope.style.display = 'none';
        camposConTope.style.display = 'block';
        
        // Limpiar campos sin tope
        const porcentajeUnico = document.getElementById('edit-porcentaje-unico-local');
        if (porcentajeUnico) porcentajeUnico.value = '';
        
        console.log('Mostrando campos CON tope');
    } else {
        // Mostrar campo sin tope (un porcentaje)
        camposSinTope.style.display = 'block';
        camposConTope.style.display = 'none';
        
        // Limpiar campos con tope
        const porcentaje1 = document.getElementById('edit-porcentaje-1-local');
        const porcentaje2 = document.getElementById('edit-porcentaje-2-local');
        if (porcentaje1) porcentaje1.value = '';
        if (porcentaje2) porcentaje2.value = '';
        
        console.log('Mostrando campos SIN tope');
    }
}

/**
 * Toggle tipo de aumento en edición
 */
function toggleTipoAumentoEdicion() {
    const radioPorcentaje = document.getElementById('edit-tipo-aumento-porcentaje');
    const radioMonto = document.getElementById('edit-tipo-aumento-monto');
    const campoPorcentaje = document.getElementById('edit-campo-porcentaje-aumento');
    const campoMonto = document.getElementById('edit-campo-monto-aumento');
    const inputPorcentaje = document.getElementById('edit-porcentaje-aumento');
    const inputMonto = document.getElementById('edit-monto-aumento');
    
    console.log('🔄 toggleTipoAumentoEdicion ejecutado');
    
    if (radioPorcentaje && radioPorcentaje.checked) {
        // Mostrar campo porcentaje, ocultar monto
        if (campoPorcentaje) {
            campoPorcentaje.style.display = 'block';
            console.log('✅ Campo porcentaje edición mostrado');
        }
        if (campoMonto) {
            campoMonto.style.display = 'none';
            console.log('🔒 Campo monto edición ocultado');
        }
        if (inputPorcentaje) {
            inputPorcentaje.setAttribute('required', 'required');
        }
        if (inputMonto) {
            inputMonto.removeAttribute('required');
            inputMonto.value = '';
        }
    } else if (radioMonto && radioMonto.checked) {
        // Mostrar campo monto, ocultar porcentaje
        if (campoMonto) {
            campoMonto.style.display = 'block';
            console.log('✅ Campo monto edición mostrado');
        }
        if (campoPorcentaje) {
            campoPorcentaje.style.display = 'none';
            console.log('🔒 Campo porcentaje edición ocultado');
        }
        if (inputMonto) {
            inputMonto.setAttribute('required', 'required');
        }
        if (inputPorcentaje) {
            inputPorcentaje.removeAttribute('required');
            inputPorcentaje.value = '';
        }
    }
}
