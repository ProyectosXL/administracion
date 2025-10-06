/**
 * Script para manejar solicitudes de directores
 * Versión corregida que usa id_director
 */

let directoresCache = [];

/**
 * Inicialización al cargar la página
 */
document.addEventListener('DOMContentLoaded', function() {
    cargarDirectores();
    configurarFormulario();
    configurarFiltros();
    
    // Cargar solicitudes si estamos en la pestaña de listado
    const tabListado = document.getElementById('listado-tab');
    if (tabListado) {
        tabListado.addEventListener('shown.bs.tab', function() {
            cargarSolicitudes();
        });
    }
});

/**
 * Carga la lista de directores desde el servidor
 */
async function cargarDirectores() {
    mostrarLoading();
    
    try {
        const response = await fetch('controller/solicitud_controller.php?accion=obtener_directores');
        const result = await response.json();
        
        if (result.success) {
            directoresCache = result.data;
            llenarSelectDirectores();
        } else {
            console.error('Error al cargar directores:', result.message);
            mostrarAlerta('Error', 'No se pudieron cargar los directores');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'Error de conexión al cargar directores');
    } finally {
        ocultarLoading();
    }
}

/**
 * Llena los selects de directores
 */
function llenarSelectDirectores() {
    const selectSolicitud = document.getElementById('idDirector');
    const selectFiltro = document.getElementById('filtroDirector');
    
    // Limpiar opciones existentes
    if (selectSolicitud) {
        selectSolicitud.innerHTML = '<option value="">Seleccione un director</option>';
        directoresCache.forEach(director => {
            const option = document.createElement('option');
            option.value = director.id_director;
            option.textContent = director.nombre_director;
            selectSolicitud.appendChild(option);
        });
    }
    
    // Llenar filtro
    if (selectFiltro) {
        selectFiltro.innerHTML = '<option value="">Todos</option>';
        directoresCache.forEach(director => {
            const option = document.createElement('option');
            option.value = director.id_director;
            option.textContent = director.nombre_director;
            selectFiltro.appendChild(option);
        });
    }
}

/**
 * Configura el formulario de nueva solicitud
 */
function configurarFormulario() {
    const form = document.getElementById('formSolicitud');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        await crearSolicitud();
    });
    
    // Configurar input de importe
    const importeInput = document.getElementById('importeSolicitud');
    if (importeInput) {
        importeInput.addEventListener('input', formatearImporte);
    }
    
    // Mostrar/ocultar sección de archivos según motivo
    const motivoSelect = document.getElementById('motivoSolicitud');
    if (motivoSelect) {
        motivoSelect.addEventListener('change', function() {
            const archivoSection = document.getElementById('archivoFacturaSection');
            if (archivoSection) {
                if (this.value === 'COMPRA_PERSONAL') {
                    archivoSection.style.display = 'block';
                } else {
                    archivoSection.style.display = 'none';
                }
            }
        });
    }
}

/**
 * Formatea el input de importe
 */
function formatearImporte(e) {
    let valor = e.target.value;
    // Eliminar caracteres no numéricos excepto puntos y comas
    valor = valor.replace(/[^0-9.,]/g, '');
    // Reemplazar comas por puntos
    valor = valor.replace(',', '.');
    e.target.value = valor;
}

/**
 * Crea una nueva solicitud
 */
async function crearSolicitud() {
    mostrarLoading();
    
    try {
        const formData = new FormData();
        formData.append('accion', 'crear');
        formData.append('id_director', document.getElementById('idDirector').value);
        formData.append('motivo', document.getElementById('motivoSolicitud').value);
        formData.append('importe', document.getElementById('importeSolicitud').value);
        formData.append('observaciones', document.getElementById('observacionesSolicitud').value || '');
        
        // Validar datos requeridos
        if (!formData.get('id_director')) {
            throw new Error('Debe seleccionar un director');
        }
        
        if (!formData.get('motivo')) {
            throw new Error('Debe seleccionar un motivo');
        }
        
        if (!formData.get('importe') || parseFloat(formData.get('importe')) <= 0) {
            throw new Error('Debe ingresar un importe válido');
        }
        
        const response = await fetch('controller/solicitud_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarAlerta('Éxito', `Solicitud creada correctamente. ID: ${result.id_solicitud}`);
            document.getElementById('formSolicitud').reset();
            
            // Si hay archivo y es compra personal, subirlo
            const archivoInput = document.getElementById('archivoFactura');
            if (archivoInput && archivoInput.files.length > 0 && 
                document.getElementById('motivoSolicitud').value === 'COMPRA_PERSONAL') {
                await subirArchivo(result.id_solicitud, archivoInput.files[0], 'FACTURA');
            }
            
            // Cambiar a la pestaña de listado para ver la solicitud
            const tabListado = document.getElementById('listado-tab');
            if (tabListado) {
                tabListado.click();
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', error.message);
    } finally {
        ocultarLoading();
    }
}

/**
 * Configura los filtros
 */
function configurarFiltros() {
    const btnAplicar = document.getElementById('btnAplicarFiltros');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    
    if (btnAplicar) {
        btnAplicar.addEventListener('click', aplicarFiltros);
    }
    
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', limpiarFiltros);
    }
}

/**
 * Aplica filtros al listado
 */
function aplicarFiltros() {
    const filtros = {
        id_director: document.getElementById('filtroDirector')?.value || '',
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

/**
 * Carga las solicitudes con filtros opcionales
 */
async function cargarSolicitudes(filtros = {}) {
    mostrarLoading();
    
    try {
        let url = 'controller/solicitud_controller.php?accion=listar';
        
        // Agregar filtros a la URL
        Object.keys(filtros).forEach(key => {
            if (filtros[key]) {
                url += `&${key}=${encodeURIComponent(filtros[key])}`;
            }
        });
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            mostrarListaSolicitudes(result.data);
        } else {
            console.error('Error al cargar solicitudes:', result.message);
            mostrarAlerta('Error', 'No se pudieron cargar las solicitudes');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'Error de conexión al cargar solicitudes');
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
 * Obtiene el nombre del director por ID
 */
function obtenerNombreDirector(idDirector) {
    const director = directoresCache.find(d => d.id_director == idDirector);
    return director ? director.nombre_director : 'Director no encontrado';
}

// Exportar funciones para uso global
window.cargarDirectores = cargarDirectores;
window.crearSolicitud = crearSolicitud;
window.aplicarFiltros = aplicarFiltros;
window.limpiarFiltros = limpiarFiltros;
window.obtenerNombreDirector = obtenerNombreDirector;