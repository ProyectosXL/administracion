/**
 * Script para gestión de Egresos Socios
 * Maneja filtros, carga de datos y renderizado de tablas
 */

// Variables globales
let fechaDesdeEgresosSocios = '';
let fechaHastaEgresosSocios = '';
let detalleCompletoEgresosSocios = []; // Almacena el detalle completo sin filtrar
let directoresDisponibles = []; // Lista de directores disponibles
let resumenCompletoEgresosSocios = null; // Almacena el resumen completo
let cargandoDatosEgresosSocios = false; // Flag para evitar cargas concurrentes

/**
 * Inicialización cuando el documento está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    // Configurar fechas por defecto (últimos 15 días)
    configurarFechasPorDefecto();
    
    // Agregar listener para cuando se active la pestaña
    const tabEgresosSocios = document.getElementById('egresos-socios-tab');
    if (tabEgresosSocios) {
        tabEgresosSocios.addEventListener('shown.bs.tab', function() {
            console.log('🔄 Pestaña Egresos Socios activada - recargando datos...');
            // Asegurar que las fechas estén configuradas
            if (!fechaDesdeEgresosSocios || !fechaHastaEgresosSocios) {
                configurarFechasPorDefecto();
            }
            // Recargar datos SIEMPRE que se muestre la pestaña
            cargarDatosEgresosSocios();
        });
    }
    
    // Agregar listeners a los inputs de fecha
    const inputDesde = document.getElementById('fechaEgresosSociosDesde');
    const inputHasta = document.getElementById('fechaEgresosSociosHasta');
    
    if (inputDesde) {
        inputDesde.addEventListener('change', function() {
            fechaDesdeEgresosSocios = this.value;
        });
    }
    
    if (inputHasta) {
        inputHasta.addEventListener('change', function() {
            fechaHastaEgresosSocios = this.value;
        });
    }
    
    // Agregar listener al filtro de director en detalle
    const filtroDirector = document.getElementById('filtroDirectorDetalle');
    if (filtroDirector) {
        filtroDirector.addEventListener('change', function() {
            filtrarDetallesPorDirector(this.value);
        });
    }
});

/**
 * Configura las fechas por defecto (mes actual completo)
 */
function configurarFechasPorDefecto() {
    const hoy = new Date();
    
    // Primer día del mes actual
    const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    
    // Último día del mes actual
    const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
    
    fechaDesdeEgresosSocios = formatearFecha(primerDiaMes);
    fechaHastaEgresosSocios = formatearFecha(ultimoDiaMes);
    
    // Actualizar inputs
    const inputDesde = document.getElementById('fechaEgresosSociosDesde');
    const inputHasta = document.getElementById('fechaEgresosSociosHasta');
    
    if (inputDesde) inputDesde.value = fechaDesdeEgresosSocios;
    if (inputHasta) inputHasta.value = fechaHastaEgresosSocios;
}

/**
 * Formatea una fecha al formato YYYY-MM-DD
 */
function formatearFecha(fecha) {
    const year = fecha.getFullYear();
    const month = String(fecha.getMonth() + 1).padStart(2, '0');
    const day = String(fecha.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Formatea una fecha al formato DD/MM/YYYY
 * Maneja tanto fechas (YYYY-MM-DD) como datetime (YYYY-MM-DD HH:MM:SS)
 */
function formatearFechaDisplay(fechaStr) {
    if (!fechaStr) return '';
    
    // Si es un datetime, extraer solo la parte de fecha
    const soloFecha = fechaStr.split(' ')[0];
    
    const partes = soloFecha.split('-');
    if (partes.length !== 3) return fechaStr;
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

/**
 * Formatea un datetime al formato DD/MM/YYYY HH:MM
 */
function formatearFechaHoraDisplay(fechaStr) {
    if (!fechaStr) return '';
    
    const partes = fechaStr.split(' ');
    if (partes.length !== 2) return formatearFechaDisplay(fechaStr);
    
    const fecha = partes[0];
    const hora = partes[1];
    
    const partesFecha = fecha.split('-');
    if (partesFecha.length !== 3) return fechaStr;
    
    // Extraer solo HH:MM (sin segundos)
    const partesHora = hora.split(':');
    const horaFormato = partesHora.length >= 2 ? `${partesHora[0]}:${partesHora[1]}` : hora;
    
    return `${partesFecha[2]}/${partesFecha[1]}/${partesFecha[0]} ${horaFormato}`;
}

/**
 * Formatea un número como moneda
 */
function formatearMoneda(valor) {
    const numero = parseFloat(valor) || 0;
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(numero);
}

/**
 * Aplica los filtros de fecha
 */
function aplicarFiltrosEgresosSocios() {
    // Validar fechas
    if (!fechaDesdeEgresosSocios || !fechaHastaEgresosSocios) {
        mostrarAlerta('Por favor, seleccione ambas fechas', 'warning');
        return;
    }
    
    // Validar que fecha desde sea menor o igual a fecha hasta
    if (fechaDesdeEgresosSocios > fechaHastaEgresosSocios) {
        mostrarAlerta('La fecha "Desde" debe ser anterior o igual a la fecha "Hasta"', 'warning');
        return;
    }
    
    // Cargar datos
    cargarDatosEgresosSocios();
}

/**
 * Limpia los filtros y recarga con valores por defecto
 */
function limpiarFiltrosEgresosSocios() {
    configurarFechasPorDefecto();
    cargarDatosEgresosSocios();
}

/**
 * Carga todos los datos de egresos socios
 */
async function cargarDatosEgresosSocios() {
    // Evitar llamadas concurrentes
    if (cargandoDatosEgresosSocios) {
        console.log('⏸️ Ya hay una carga en progreso, ignorando nueva llamada');
        return;
    }
    
    cargandoDatosEgresosSocios = true;
    
    try {
        // Validar que las fechas estén configuradas
        if (!fechaDesdeEgresosSocios || !fechaHastaEgresosSocios) {
            console.warn('⚠️ Fechas no configuradas, configurando por defecto');
            configurarFechasPorDefecto();
        }
        
        console.log('📊 Cargando datos de Egresos Socios:', fechaDesdeEgresosSocios, 'a', fechaHastaEgresosSocios);
        
        // Mostrar indicadores de carga
        mostrarCargando('tablaResumenSocios');
        mostrarCargando('tablaDetalleSocios');
        
        // Cargar resumen y detalle en paralelo
        const [resumen, detalle, total] = await Promise.all([
            cargarResumenEgresosSocios(),
            cargarDetalleEgresosSocios(),
            cargarTotalEgresosSocios()
        ]);
        
        // Renderizar datos
        renderizarResumen(resumen);
        renderizarDetalle(detalle);
        actualizarTotalEgresosSocios(total);
        
        // Guardar datos completos para exportación y filtrado
        resumenCompletoEgresosSocios = resumen;
        detalleCompletoEgresosSocios = detalle;
        
        // Actualizar opciones del filtro de directores usando el resumen (tiene todos los directores)
        actualizarFiltroDirectores(resumen);
        
        console.log('✅ Datos de Egresos Socios cargados correctamente');
        
    } catch (error) {
        console.error('❌ Error al cargar datos de Egresos Socios:', error);
        mostrarError('tablaResumenSocios', 'Error al cargar el resumen');
        mostrarError('tablaDetalleSocios', 'Error al cargar el detalle');
        mostrarAlerta('Error al cargar los datos de egresos socios: ' + error.message, 'danger');
    } finally {
        // Siempre liberar el flag, incluso si hay error
        cargandoDatosEgresosSocios = false;
    }
}

/**
 * Carga el resumen de egresos por director
 */
async function cargarResumenEgresosSocios() {
    const params = new URLSearchParams({
        action: 'obtener_resumen',
        fecha_desde: fechaDesdeEgresosSocios,
        fecha_hasta: fechaHastaEgresosSocios
    });
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 segundos timeout
    
    try {
        const response = await fetch(`controller/egresos_socios_controller.php?${params}`, {
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error al obtener resumen');
        }
        
        return data.data;
    } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            throw new Error('La petición tardó demasiado tiempo');
        }
        throw error;
    }
}

/**
 * Carga el detalle completo de egresos
 */
async function cargarDetalleEgresosSocios() {
    const params = new URLSearchParams({
        action: 'obtener_detalle',
        fecha_desde: fechaDesdeEgresosSocios,
        fecha_hasta: fechaHastaEgresosSocios
    });
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 segundos timeout
    
    try {
        const response = await fetch(`controller/egresos_socios_controller.php?${params}`, {
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error al obtener detalle');
        }
        
        return data.data;
    } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            throw new Error('La petición tardó demasiado tiempo');
        }
        throw error;
    }
}

/**
 * Carga el total de egresos
 */
async function cargarTotalEgresosSocios() {
    const params = new URLSearchParams({
        action: 'obtener_total',
        fecha_desde: fechaDesdeEgresosSocios,
        fecha_hasta: fechaHastaEgresosSocios
    });
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 segundos timeout
    
    try {
        const response = await fetch(`controller/egresos_socios_controller.php?${params}`, {
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error al obtener total');
        }
        
        return data.total;
    } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            throw new Error('La petición tardó demasiado tiempo');
        }
        throw error;
    }
}

/**
 * Renderiza la tabla resumen por director
 */
function renderizarResumen(resumen) {
    const container = document.getElementById('tablaResumenSocios');
    
    if (!resumen || !resumen.directores || Object.keys(resumen.directores).length === 0) {
        container.innerHTML = `
            <div class="mensaje-sin-datos">
                <i class="bi bi-inbox"></i>
                <p>No hay datos para mostrar en el período seleccionado</p>
            </div>
        `;
        return;
    }
    
    // Obtener lista de directores ordenada
    const directores = Object.keys(resumen.directores).sort();
    
    // Calcular totales de EFECTIVO por director
    const totalesEfectivo = {};
    directores.forEach(director => {
        totalesEfectivo[director] = 0;
    });
    
    Object.keys(resumen.efectivo).forEach(fecha => {
        directores.forEach(director => {
            const valor = resumen.efectivo[fecha][director] || 0;
            totalesEfectivo[director] += valor;
        });
    });
    
    // Calcular totales de TRANSFERENCIA por director
    const totalesTransferencia = {};
    directores.forEach(director => {
        totalesTransferencia[director] = 0;
    });
    
    Object.keys(resumen.transferencia).forEach(fecha => {
        directores.forEach(director => {
            const valor = resumen.transferencia[fecha][director] || 0;
            totalesTransferencia[director] += valor;
        });
    });
    
    // Construir HTML de la tabla
    let html = '<table class="table table-bordered table-sm">';
    
    // Encabezado
    html += '<thead><tr>';
    html += '<th style="width: 15%;">Tipo</th>';
    directores.forEach(director => {
        html += `<th>${director}</th>`;
    });
    html += '</tr></thead>';
    
    // Cuerpo
    html += '<tbody>';
    
    // Fila EFECTIVO
    html += '<tr class="fila-tipo">';
    html += '<td class="tipo-label">EFECTIVO</td>';
    directores.forEach(director => {
        const valor = totalesEfectivo[director];
        const clase = valor > 0 ? 'valor-positivo' : 'valor-cero';
        html += `<td class="${clase}">${formatearMoneda(valor)}</td>`;
    });
    html += '</tr>';
    
    // Fila TRANSFERENCIA
    html += '<tr class="fila-tipo">';
    html += '<td class="tipo-label">TRANSFERENCIA</td>';
    directores.forEach(director => {
        const valor = totalesTransferencia[director];
        const clase = valor > 0 ? 'valor-positivo' : 'valor-cero';
        html += `<td class="${clase}">${formatearMoneda(valor)}</td>`;
    });
    html += '</tr>';
    
    // Fila de TOTAL
    html += '<tr class="fila-total">';
    html += '<td>TOTAL</td>';
    
    directores.forEach(director => {
        const total = resumen.totales[director] || 0;
        html += `<td>${formatearMoneda(total)}</td>`;
    });
    
    html += '</tr>';
    
    html += '</tbody></table>';
    
    container.innerHTML = html;
}

/**
 * Renderiza la tabla detalle de egresos
 */
function renderizarDetalle(detalle) {
    const container = document.getElementById('tablaDetalleSocios');
    
    if (!detalle || detalle.length === 0) {
        container.innerHTML = `
            <div class="mensaje-sin-datos">
                <i class="bi bi-inbox"></i>
                <p>No hay egresos registrados en el período seleccionado</p>
            </div>
        `;
        return;
    }
    
    // Calcular total de importes
    let totalImporte = 0;
    detalle.forEach(item => {
        totalImporte += parseFloat(item.importe) || 0;
    });
    
    // Construir HTML de la tabla
    let html = '<table class="table table-striped table-hover table-sm">';
    
    // Encabezado
    html += `
        <thead>
            <tr>
                <th class="col-fecha">FECHA</th>
                <th class="col-codigo">CÓDIGO</th>
                <th class="col-director">DIRECTOR</th>
                <th class="col-motivo">MOTIVO</th>
                <th class="col-origen">ORIGEN</th>
                <th class="col-importe">IMPORTE</th>
                <th class="col-acciones">ACCIONES</th>
            </tr>
        </thead>
    `;
    
    // Cuerpo
    html += '<tbody>';
    
    detalle.forEach(item => {
        const origenClase = item.origen === 'MANUAL' ? 'badge-manual' : 'badge-app';
        
        html += '<tr>';
        html += `<td class="col-fecha">${formatearFechaDisplay(item.fecha)}</td>`;
        html += `<td class="col-codigo">${item.codigo}</td>`;
        html += `<td class="col-director">${item.director}</td>`;
        html += `<td class="col-motivo">${item.motivo}</td>`;
        html += `<td class="col-origen"><span class="badge badge-origen ${origenClase}">${item.origen}</span></td>`;
        html += `<td class="col-importe">${formatearMoneda(item.importe)}</td>`;
        html += `<td class="col-acciones">
            <button class="btn btn-sm btn-info" onclick="verDetalleEgresoSocio('${item.origen}', '${item.codigo}')" title="Ver detalle">
                <i class="bi bi-eye"></i>
            </button>
        </td>`;
        html += '</tr>';
    });
    
    html += '</tbody>';
    
    // Footer con total de registros y total de importes
    html += `
        <tfoot>
            <tr class="table-active fw-bold">
                <td colspan="6" class="text-end">
                    <strong>TOTAL:</strong>
                </td>
                <td class="col-importe text-danger">
                    <strong>${formatearMoneda(totalImporte)}</strong>
                </td>
            </tr>
        </tfoot>
    `;
    
    html += '</table>';
    
    container.innerHTML = html;
}

/**
 * Actualiza el total de egresos en la tarjeta
 */
function actualizarTotalEgresosSocios(total) {
    const elemento = document.getElementById('totalEgresosSocios');
    if (elemento) {
        elemento.textContent = formatearMoneda(total);
    }
}

/**
 * Muestra un indicador de carga
 */
function mostrarCargando(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="cargando-datos">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p>Cargando datos...</p>
            </div>
        `;
    }
}

/**
 * Muestra un mensaje de error
 */
function mostrarError(containerId, mensaje) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> ${mensaje}
            </div>
        `;
    }
}

/**
 * Muestra una alerta temporal (requiere Bootstrap)
 * COMENTADO: Ahora se usa la función global de modal_global.js
 */
/*
function mostrarAlerta(mensaje, tipo = 'info') {
    // Crear contenedor de alertas si no existe
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '9999';
        alertContainer.style.maxWidth = '400px';
        document.body.appendChild(alertContainer);
    }
    
    // Crear alerta
    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${tipo} alert-dismissible fade show" role="alert">
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}
*/

/**
 * Actualiza el filtro de directores con los disponibles en el detalle
 */
function actualizarFiltroDirectores(resumen) {
    const selectFiltro = document.getElementById('filtroDirectorDetalle');
    if (!selectFiltro) return;
    
    // Obtener lista de TODOS los directores desde el resumen
    // (incluye todos los directores, incluso si tienen $0 en el período)
    if (resumen && resumen.directores) {
        directoresDisponibles = Object.keys(resumen.directores).sort();
    } else {
        directoresDisponibles = [];
    }
    
    // Limpiar y reconstruir opciones
    selectFiltro.innerHTML = '<option value="">Todos los directores</option>';
    
    directoresDisponibles.forEach(director => {
        const option = document.createElement('option');
        option.value = director;
        option.textContent = director;
        selectFiltro.appendChild(option);
    });
    
    // Resetear selección
    selectFiltro.value = '';
}

/**
 * Filtra el detalle de egresos por director
 */
function filtrarDetallesPorDirector(directorSeleccionado) {
    // Si no hay director seleccionado, mostrar todos
    if (!directorSeleccionado) {
        renderizarDetalle(detalleCompletoEgresosSocios);
        return;
    }
    
    // Filtrar detalle por director
    const detalleFiltrado = detalleCompletoEgresosSocios.filter(item => {
        return item.director === directorSeleccionado;
    });
    
    // Renderizar detalle filtrado
    renderizarDetalle(detalleFiltrado);
}

/**
 * Muestra el detalle completo de un egreso en un modal
 */
async function verDetalleEgresoSocio(origen, codigo) {
    try {
        // Abrir modal
        const modal = new bootstrap.Modal(document.getElementById('modalDetalleEgresoSocio'));
        modal.show();
        
        // Mostrar loader
        document.getElementById('contenidoDetalleEgresoSocio').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando información...</p>
            </div>
        `;
        
        // Cargar datos
        const params = new URLSearchParams({
            action: 'obtener_detalle_egreso',
            origen: origen,
            codigo: codigo
        });
        
        const response = await fetch(`controller/egresos_socios_controller.php?${params}`);
        
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error al obtener detalle');
        }
        
        // Renderizar detalle
        renderizarDetalleCompleto(data.data, origen);
        
    } catch (error) {
        console.error('Error al cargar detalle:', error);
        document.getElementById('contenidoDetalleEgresoSocio').innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> Error al cargar el detalle: ${error.message}
            </div>
        `;
    }
}

/**
 * Renderiza el detalle completo en el modal
 */
function renderizarDetalleCompleto(detalle, origen) {
    let html = '<div class="detalle-egreso-container">';
    
    // Información principal
    html += '<div class="row mb-3">';
    html += '<div class="col-md-6">';
    html += `<div class="info-item"><strong>Código:</strong> <span class="badge bg-secondary">${detalle.codigo}</span></div>`;
    
    // Para pagos de servicios (nuevos motivos), mostrar fecha_carga como fecha principal
    const motivosPagoServicio = ['Pago de seguros', 'Pago de patentes', 'Pago de expensas', 'Pago de tarjetas', 'Transf. Haberes', 'Otros'];
    const esPagoServicio = motivosPagoServicio.includes(detalle.motivo);
    
    if (esPagoServicio && detalle.fecha_carga) {
        // Mostrar fecha de carga como fecha principal
        html += `<div class="info-item"><strong>Fecha de Pago:</strong> ${formatearFechaDisplay(detalle.fecha_carga)}</div>`;
        // Mostrar fecha de vencimiento como adicional
        if (detalle.fecha && detalle.fecha !== detalle.fecha_carga.split(' ')[0]) {
            html += `<div class="info-item text-muted"><small><strong>Vencimiento:</strong> ${formatearFechaDisplay(detalle.fecha)}</small></div>`;
        }
    } else {
        // Para otros egresos, mostrar fecha normal
        html += `<div class="info-item"><strong>Fecha:</strong> ${formatearFechaDisplay(detalle.fecha)}</div>`;
    }
    
    html += `<div class="info-item"><strong>Director:</strong> ${detalle.director}</div>`;
    html += '</div>';
    html += '<div class="col-md-6">';
    html += `<div class="info-item"><strong>Origen:</strong> <span class="badge ${origen === 'MANUAL' ? 'bg-warning text-dark' : 'bg-info'}">${origen}</span></div>`;
    html += `<div class="info-item"><strong>Motivo:</strong> ${detalle.motivo}</div>`;
    html += `<div class="info-item"><strong>Importe:</strong> <span class="text-danger fs-5 fw-bold">${formatearMoneda(detalle.importe)}</span></div>`;
    html += '</div>';
    html += '</div>';
    
    // Información del proveedor (para Pago de seguros)
    if (detalle.motivo === 'Pago de seguros' && (detalle.proveedor_nom || detalle.proveedor)) {
        html += '<hr>';
        html += '<div class="mb-3">';
        html += '<h6><i class="bi bi-person-badge-fill"></i> Información del Proveedor:</h6>';
        html += '<div class="row">';
        html += '<div class="col-md-6">';
        
        // Para origen MANUAL usa proveedor_nom, para APP usa proveedor
        const nombreProveedor = detalle.proveedor_nom || detalle.proveedor || 'N/A';
        html += `<div class="info-item"><strong>Nombre:</strong> ${nombreProveedor}</div>`;
        
        html += '</div>';
        html += '<div class="col-md-6">';
        
        // CBU y descripción (puede venir de ambos orígenes)
        const cbu = detalle.proveedor_cbu || detalle.cbu || '';
        const descripcionCbu = detalle.proveedor_descripcion_cbu || detalle.descripcion_cbu || '';
        
        if (cbu) {
            html += `<div class="info-item"><strong>CBU:</strong> ${cbu}</div>`;
        }
        if (descripcionCbu) {
            html += `<div class="info-item"><strong>Banco:</strong> ${descripcionCbu}</div>`;
        }
        
        html += '</div>';
        html += '</div>';
        html += '</div>';
    }
    
    // Observaciones
    if (detalle.observaciones) {
        html += '<hr>';
        html += '<div class="mb-3">';
        html += '<h6><i class="bi bi-chat-left-text"></i> Observaciones:</h6>';
        html += `<div class="observaciones-box">${detalle.observaciones}</div>`;
        html += '</div>';
    }
    
    // Observaciones adicionales para solicitudes de egresos (APP)
    if (origen === 'APP EGRESOS') {
        if (detalle.observaciones_proveedores) {
            html += '<div class="mb-3">';
            html += '<h6><i class="bi bi-truck"></i> Observaciones Proveedores:</h6>';
            html += `<div class="observaciones-box">${detalle.observaciones_proveedores}</div>`;
            html += '</div>';
        }
        
        if (detalle.observaciones_tesoreria) {
            html += '<div class="mb-3">';
            html += '<h6><i class="bi bi-bank"></i> Observaciones Tesorería:</h6>';
            html += `<div class="observaciones-box">${detalle.observaciones_tesoreria}</div>`;
            html += '</div>';
        }
        
        if (detalle.estado) {
            html += '<div class="mb-3">';
            html += '<h6><i class="bi bi-flag"></i> Estado:</h6>';
            html += `<span class="badge bg-success">${detalle.estado}</span>`;
            html += '</div>';
        }
    }
    
    // Foto/PDF del comprobante
    if (detalle.tiene_foto) {
        html += '<hr>';
        html += '<div class="mb-3">';
        
        const esPdf = detalle.tipo_archivo === 'application/pdf';
        
        if (esPdf) {
            // Para PDFs, mostrar botón de descarga
            html += '<h6><i class="bi bi-file-pdf"></i> Comprobante (PDF):</h6>';
            html += `<div class="text-center">`;
            html += `<button class="btn btn-primary" onclick="descargarPdfEgreso('${detalle.foto}', '${detalle.codigo}')">
                <i class="bi bi-download"></i> Descargar PDF
            </button>`;
            html += `</div>`;
        } else {
            // Para imágenes, mostrar imagen
            html += '<h6><i class="bi bi-image"></i> Comprobante:</h6>';
            html += `<div class="text-center foto-container">`;
            html += `<img src="data:${detalle.tipo_archivo || 'image/jpeg'};base64,${detalle.foto}" class="img-thumbnail foto-comprobante-detalle" alt="Comprobante" onclick="toggleFotoTamano(this)">`;
            html += `<p class="text-muted small mt-2 foto-hint">
                <i class="bi bi-zoom-in"></i> <span>Haz clic en la imagen para ampliar</span>
            </p>`;
            html += `</div>`;
        }
        
        html += '</div>';
    }
    
    // Fechas de registro/modificación
    html += '<hr>';
    html += '<div class="row text-muted small">';
    if (detalle.fecha_carga || detalle.fecha_solicitud) {
        html += '<div class="col-md-6">';
        const fechaRegistro = detalle.fecha_carga || detalle.fecha_solicitud;
        // Usar formateo con hora si es datetime, sino solo fecha
        const fechaFormateada = fechaRegistro && fechaRegistro.includes(' ') 
            ? formatearFechaHoraDisplay(fechaRegistro) 
            : formatearFechaDisplay(fechaRegistro);
        html += `<strong>Fecha de registro:</strong> ${fechaFormateada}`;
        html += '</div>';
    }
    if (detalle.fecha_modificacion) {
        html += '<div class="col-md-6">';
        html += `<strong>Última modificación:</strong> ${formatearFechaDisplay(detalle.fecha_modificacion)}`;
        if (detalle.usuario_modificacion) {
            html += ` por ${detalle.usuario_modificacion}`;
        }
        html += '</div>';
    }
    html += '</div>';
    
    html += '</div>';
    
    document.getElementById('contenidoDetalleEgresoSocio').innerHTML = html;
}

/**
 * Alterna el tamaño de la foto del comprobante (ampliar/reducir)
 */
function toggleFotoTamano(imgElement) {
    const hintElement = imgElement.parentElement.querySelector('.foto-hint span');
    
    if (imgElement.classList.contains('foto-ampliada')) {
        // Reducir a tamaño normal
        imgElement.classList.remove('foto-ampliada');
        if (hintElement) {
            hintElement.innerHTML = 'Haz clic en la imagen para ampliar';
            hintElement.parentElement.querySelector('i').className = 'bi bi-zoom-in';
        }
    } else {
        // Ampliar a tamaño completo
        imgElement.classList.add('foto-ampliada');
        if (hintElement) {
            hintElement.innerHTML = 'Haz clic en la imagen para reducir';
            hintElement.parentElement.querySelector('i').className = 'bi bi-zoom-out';
        }
        
        // Hacer scroll suave hacia la imagen después de ampliar
        setTimeout(() => {
            imgElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }
}

/**
 * Exporta la tabla resumen a Excel (.xlsx)
 */
function exportarResumenExcel() {
    if (!resumenCompletoEgresosSocios || !resumenCompletoEgresosSocios.directores) {
        mostrarAlerta('No hay datos para exportar', 'warning');
        return;
    }
    
    const resumen = resumenCompletoEgresosSocios;
    const directores = Object.keys(resumen.directores).sort();
    
    // Calcular totales de EFECTIVO por director
    const totalesEfectivo = {};
    directores.forEach(director => {
        totalesEfectivo[director] = 0;
    });
    
    Object.keys(resumen.efectivo).forEach(fecha => {
        directores.forEach(director => {
            const valor = resumen.efectivo[fecha][director] || 0;
            totalesEfectivo[director] += valor;
        });
    });
    
    // Calcular totales de TRANSFERENCIA por director
    const totalesTransferencia = {};
    directores.forEach(director => {
        totalesTransferencia[director] = 0;
    });
    
    Object.keys(resumen.transferencia).forEach(fecha => {
        directores.forEach(director => {
            const valor = resumen.transferencia[fecha][director] || 0;
            totalesTransferencia[director] += valor;
        });
    });
    
    // Preparar datos para Excel
    const datosExcel = [];
    
    // Encabezado
    const encabezado = ['Tipo', ...directores];
    datosExcel.push(encabezado);
    
    // Fila EFECTIVO
    const filaEfectivo = ['EFECTIVO'];
    directores.forEach(director => {
        filaEfectivo.push(totalesEfectivo[director]);
    });
    datosExcel.push(filaEfectivo);
    
    // Fila TRANSFERENCIA
    const filaTransferencia = ['TRANSFERENCIA'];
    directores.forEach(director => {
        filaTransferencia.push(totalesTransferencia[director]);
    });
    datosExcel.push(filaTransferencia);
    
    // Fila de TOTAL
    const filaTotal = ['TOTAL'];
    directores.forEach(director => {
        const total = resumen.totales[director] || 0;
        filaTotal.push(total);
    });
    datosExcel.push(filaTotal);
    
    // Crear libro de Excel
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(datosExcel);
    
    // Ajustar ancho de columnas
    const wscols = [{ wch: 15 }]; // Primera columna
    directores.forEach(() => wscols.push({ wch: 15 })); // Columnas de directores
    ws['!cols'] = wscols;
    
    // Agregar hoja al libro
    XLSX.utils.book_append_sheet(wb, ws, 'Resumen Egresos Socios');
    
    // Generar nombre de archivo
    const nombreArchivo = `Resumen_Egresos_Socios_${fechaDesdeEgresosSocios}_a_${fechaHastaEgresosSocios}.xlsx`;
    
    // Descargar archivo
    XLSX.writeFile(wb, nombreArchivo);
    
    mostrarAlerta('Éxito', `Resumen de Egresos Socios exportado correctamente como: ${nombreArchivo}`);
}

/**
 * Exporta la tabla detalle a Excel (.xlsx)
 * Exporta solo los registros actualmente mostrados (respeta filtros)
 */
function exportarDetalleExcel() {
    // Obtener el detalle actualmente mostrado
    const filtroDirector = document.getElementById('filtroDirectorDetalle');
    let detalleAExportar = detalleCompletoEgresosSocios;
    
    // Si hay un filtro de director aplicado, filtrar los datos
    if (filtroDirector && filtroDirector.value) {
        detalleAExportar = detalleCompletoEgresosSocios.filter(item => {
            return item.director === filtroDirector.value;
        });
    }
    
    if (!detalleAExportar || detalleAExportar.length === 0) {
        mostrarAlerta('No hay datos para exportar', 'warning');
        return;
    }
    
    // Preparar datos para Excel
    const datosExcel = [];
    
    // Encabezado
    datosExcel.push(['FECHA', 'CÓDIGO', 'DIRECTOR', 'MOTIVO', 'ORIGEN', 'IMPORTE']);
    
    // Filas de datos
    let totalImporte = 0;
    detalleAExportar.forEach(item => {
        const fila = [
            formatearFechaDisplay(item.fecha),
            item.codigo,
            item.director,
            item.motivo,
            item.origen,
            parseFloat(item.importe) || 0
        ];
        datosExcel.push(fila);
        totalImporte += parseFloat(item.importe) || 0;
    });
    
    // Fila vacía
    datosExcel.push([]);
    
    // Fila de total
    datosExcel.push(['', '', '', '', 'TOTAL:', totalImporte]);
    
    // Crear libro de Excel
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(datosExcel);
    
    // Ajustar ancho de columnas
    ws['!cols'] = [
        { wch: 12 },  // FECHA
        { wch: 18 },  // CÓDIGO
        { wch: 20 },  // DIRECTOR
        { wch: 25 },  // MOTIVO
        { wch: 15 },  // ORIGEN
        { wch: 15 }   // IMPORTE
    ];
    
    // Agregar hoja al libro
    XLSX.utils.book_append_sheet(wb, ws, 'Detalle Egresos Socios');
    
    // Generar nombre de archivo
    let nombreArchivo = `Detalle_Egresos_Socios_${fechaDesdeEgresosSocios}_a_${fechaHastaEgresosSocios}`;
    if (filtroDirector && filtroDirector.value) {
        nombreArchivo += `_${filtroDirector.value.replace(/\s+/g, '_')}`;
    }
    nombreArchivo += '.xlsx';
    
    // Descargar archivo
    XLSX.writeFile(wb, nombreArchivo);
    
    mostrarAlerta('Éxito', `Detalle de Egresos Socios exportado correctamente como: ${nombreArchivo}`);
}

/**
 * Descarga un PDF desde base64
 */
function descargarPdfEgreso(base64, codigo) {
    try {
        const byteCharacters = atob(base64);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: 'application/pdf' });
        
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `factura_egreso_${codigo}.pdf`;
        link.click();
        URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Error al descargar PDF:', error);
        mostrarAlerta('Error', 'Error al descargar el PDF');
    }
}

// Hacer funciones disponibles globalmente
window.aplicarFiltrosEgresosSocios = aplicarFiltrosEgresosSocios;
window.limpiarFiltrosEgresosSocios = limpiarFiltrosEgresosSocios;
window.cargarDatosEgresosSocios = cargarDatosEgresosSocios;
window.exportarResumenExcel = exportarResumenExcel;
window.exportarDetalleExcel = exportarDetalleExcel;
window.verDetalleEgresoSocio = verDetalleEgresoSocio;
window.toggleFotoTamano = toggleFotoTamano;
window.descargarPdfEgreso = descargarPdfEgreso;
