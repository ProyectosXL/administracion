// /novedades/js/consultar_novedades.js

/**
 * JavaScript específico para la consulta de novedades
 */

let novedadesData = [];
let filtrosActivos = {};
let vistaActual = 'tabla';
let paginaActual = 1;
const elementosPorPagina = 10;

/**
 * Cargar todas las novedades
 */
async function cargarNovedades() {
    mostrarLoading(true);
    
    try {
        novedadesData = await NovedadesApp.request('get_novedades');
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
 * Aplicar filtros a los datos - ACTUALIZADO CON FILTROS DE FECHA
 */
function aplicarFiltros() {
    // Obtener valores de los filtros
    filtrosActivos = {
        empleado: $('#filtro-empleado').val() || '', // Solo un campo empleado/legajo
        sucursal: document.getElementById('filtro-sucursal').value,
        tipo: document.getElementById('filtro-tipo').value,
        fechaDesde: document.getElementById('fecha-desde').value,
        fechaHasta: document.getElementById('fecha-hasta').value
    };

    console.log('Aplicando filtros:', filtrosActivos);

    // Filtrar datos
    let datosFiltrados = novedadesData.filter(novedad => {
        // Filtro por empleado/legajo (busca tanto en legajo como en nombre)
        if (filtrosActivos.empleado) {
            const empleadoFiltro = filtrosActivos.empleado.toString().toLowerCase();
            const legajoStr = novedad.legajo.toString();
            const nombreCompleto = (novedad.nombre + ' ' + novedad.apellido).toLowerCase();
            
            if (!legajoStr.includes(empleadoFiltro) && !nombreCompleto.includes(empleadoFiltro)) {
                return false;
            }
        }
        
        // Filtro por sucursal
        if (filtrosActivos.sucursal && novedad.sucursal != filtrosActivos.sucursal) {
            return false;
        }
        
        // Filtro por tipo de novedad
        if (filtrosActivos.tipo && novedad.tipo_novedad != filtrosActivos.tipo) {
            return false;
        }
        
        // Filtro por fecha desde
        if (filtrosActivos.fechaDesde) {
            const fechaDesde = new Date(filtrosActivos.fechaDesde);
            const fechaVigencia = new Date(novedad.fecha_vigencia);
            if (fechaVigencia < fechaDesde) {
                return false;
            }
        }
        
        // Filtro por fecha hasta
        if (filtrosActivos.fechaHasta) {
            const fechaHasta = new Date(filtrosActivos.fechaHasta);
            const fechaVigencia = new Date(novedad.fecha_vigencia);
            if (fechaVigencia > fechaHasta) {
                return false;
            }
        }
        
        return true;
    });

    // Resetear página actual
    paginaActual = 1;
    
    // Mostrar resultados
    mostrarResultados(datosFiltrados);
    actualizarEstadisticasFiltros(datosFiltrados);
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
 * Cargar todas las novedades
 */
async function cargarNovedades() {
    mostrarLoading(true);
    
    try {
        novedadesData = await NovedadesApp.request('get_novedades');
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
 * Aplicar filtros a los datos - CORREGIDO
 */
function aplicarFiltros() {
    // Obtener valores de los filtros usando Select2
    filtrosActivos = {
        legajo: $('#filtro-legajo').val() || '', 
        sucursal: document.getElementById('filtro-sucursal').value,
        tipo: document.getElementById('filtro-tipo').value,
        empleado: $('#filtro-empleado').val() || '' 
    };

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
        const valor = novedad.valor_numerico ? NovedadesApp.formatearValor(novedad.valor_numerico) : '-';
        const fechaRegistro = NovedadesApp.formatearFecha(novedad.fecha_creacion);
        const estado = obtenerBadgeEstado();

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
                <td>
                    <small>${fechaVigencia}</small>
                </td>
                <td>
                    <strong class="text-success">${valor}</strong>
                </td>
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
        const valor = novedad.valor_numerico ? NovedadesApp.formatearValor(novedad.valor_numerico) : '';
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
                            <span class="h6 text-success">${valor}</span>
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
                        <div class="btn-group w-100">
                            <button class="btn btn-outline-info btn-sm" onclick="verDetalleNovedad(${novedad.id})">
                                <i class="fas fa-eye me-1"></i>
                                Ver
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="imprimirNovedad(${novedad.id})">
                                <i class="fas fa-print me-1"></i>
                                Imprimir
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
                            <tr><td><strong>Período:</strong></td><td>${novedad.periodo_mes}/${novedad.periodo_anio}</td></tr>
                            <tr><td><strong>Fecha Registro:</strong></td><td>${NovedadesApp.formatearFecha(novedad.fecha_creacion)}</td></tr>
                            ${novedad.fecha_vigencia ? `<tr><td><strong>Fecha Vigencia:</strong></td><td>${NovedadesApp.formatearFecha(novedad.fecha_vigencia)}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Información específica según el tipo
    if (novedad.valor_numerico) {
        html += `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Valor Monetario</h6>
                        </div>
                        <div class="card-body text-center">
                            <h3 class="text-success">${NovedadesApp.formatearValor(novedad.valor_numerico)}</h3>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

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
                                <div class="col-md-4">
                                    <strong>Fecha:</strong><br>
                                    ${NovedadesApp.formatearFecha(novedad.fecha_permiso)}
                                </div>
                                <div class="col-md-4">
                                    <strong>Tipo:</strong><br>
                                    <span class="badge bg-secondary">${novedad.tipo_permiso}</span>
                                </div>
                                <div class="col-md-4">
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
    
    filtrosActivos = {};
    paginaActual = 1;
    aplicarFiltros();
    
    console.log('Filtros limpiados');
}

/**
 * Filtrar por períodos predefinidos
 */
function filtrarPeriodo(periodo) {
    const hoy = new Date();
    const fechaDesde = document.getElementById('fecha-desde');
    const fechaHasta = document.getElementById('fecha-hasta');
    
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
    
    // Aplicar filtros automáticamente
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
    const valorTotal = novedadesData.reduce((sum, n) => sum + (parseFloat(n.valor_numerico) || 0), 0);

    document.getElementById('total-resultados').textContent = totalNovedades;
    document.getElementById('total-sucursales-filtro').textContent = sucursalesUnicas;
    document.getElementById('total-empleados-filtro').textContent = empleadosUnicos;
    document.getElementById('total-valores').textContent = NovedadesApp.formatearValor(valorTotal);
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
    const headers = ['Legajo', 'Nombre', 'Apellido', 'Sucursal', 'Tipo de Novedad', 'Fecha Vigencia', 'Valor', 'Fecha Permiso', 'Tipo Permiso', 'Compensa', 'Observaciones', 'Fecha Registro'];
    const csvContent = [
        headers.join(','),
        ...datosFiltrados.map(novedad => [
            novedad.legajo,
            `"${novedad.nombre}"`,
            `"${novedad.apellido}"`,
            novedad.sucursal,
            `"${novedad.tipo_descripcion}"`,
            novedad.fecha_vigencia || '',
            novedad.valor_numerico || '',
            novedad.fecha_permiso || '',
            novedad.tipo_permiso || '',
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
        html += `
            <tr>
                <td>${novedad.legajo}</td>
                <td>${novedad.nombre} ${novedad.apellido}</td>
                <td>${novedad.sucursal}</td>
                <td>${novedad.tipo_descripcion}</td>
                <td>${novedad.fecha_vigencia ? NovedadesApp.formatearFecha(novedad.fecha_vigencia) : '-'}</td>
                <td>${novedad.valor_numerico ? NovedadesApp.formatearValor(novedad.valor_numerico) : '-'}</td>
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
                    ${novedad.valor_numerico ? `<tr><th>Valor:</th><td>${NovedadesApp.formatearValor(novedad.valor_numerico)}</td></tr>` : ''}
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
    
    console.log('Consulta de Novedades - Inicializada correctamente');
});

/**
 * Cargar datos base (sucursales y tipos de novedad)
 */
async function cargarDatosBase() {
    try {
        // Cargar sucursales
        const sucursales = await NovedadesApp.request('get_sucursales');
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
    const valorTotal = datosFiltrados.reduce((sum, n) => sum + (parseFloat(n.valor_numerico) || 0), 0);

    document.getElementById('total-resultados').textContent = totalNovedades;
    document.getElementById('total-sucursales-filtro').textContent = sucursalesUnicas;
    document.getElementById('total-empleados-filtro').textContent = empleadosUnicos;
    document.getElementById('total-valores').textContent = NovedadesApp.formatearValor(valorTotal);
}