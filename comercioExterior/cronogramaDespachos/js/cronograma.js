/**
 * Cronograma de Despachos - JavaScript
 * Gestión de vista de calendario y timeline de contenedores
 */

let despachos = [];
let vistaActual = 'calendario'; // 'calendario' o 'grilla'
let mesActual = new Date();
let despachoSeleccionado = null;
let filtroEstado = 'todos'; // 'todos', 'est-emb', 'emb', 'arr', 'desp', 'rec'

// Configuración de iconos por estado
const ICONOS_ESTADOS = {
    origen: '🏭',
    embarcado: '🚢',
    arribado: '🛃',
    despachado: '🚚',
    recibido: '📦'
};

const LABELS_ESTADOS = {
    origen: 'En Origen',
    embarcado: 'Embarcado',
    arribado: 'Arribado',
    despachado: 'Despachado',
    recibido: 'Recibido'
};

// ========== INICIALIZACIÓN ==========
$(document).ready(function() {
    cargarDespachos();
    configurarEventListeners();
    actualizarMesDisplay();
});

function configurarEventListeners() {
    // Botones de vista
    $('.btn-view').on('click', function() {
        $('.btn-view').removeClass('active');
        $(this).addClass('active');
        vistaActual = $(this).data('view');
        renderizarVista();
    });
    
    // Navegación de mes
    $('#btnMesAnterior').on('click', () => cambiarMes(-1));
    $('#btnMesSiguiente').on('click', () => cambiarMes(1));
    $('#btnMesActual').on('click', () => {
        mesActual = new Date();
        actualizarMesDisplay();
        renderizarVista();
    });
    
    // Filtro de estado
    $('#filtroEstado').on('change', function() {
        filtroEstado = $(this).val();
        renderizarVista();
    });
    
    // Cerrar modal
    $(document).on('click', '.modal-overlay', function(e) {
        if (e.target === this) {
            cerrarModal();
        }
    });
    
    $(document).on('click', '.btn-close-modal', cerrarModal);
}

// ========== CARGAR DATOS ==========
function cargarDespachos() {
    $.ajax({
        url: 'controller/obtenerDespachos.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                despachos = response.data;
                renderizarVista();
            } else {
                mostrarError('Error al cargar despachos: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            mostrarError('Error al conectar con el servidor');
        }
    });
}

// ========== RENDERIZADO ==========
function renderizarVista() {
    if (vistaActual === 'calendario') {
        renderizarCalendario();
    } else {
        renderizarGrilla();
    }
}

function renderizarCalendario() {
    const container = $('#contenidoPrincipal');
    container.html(`
        <div class="calendario-view">
            <div class="calendario-header">
                <div class="calendario-mes" id="calendarioMes"></div>
                <div class="calendario-nav">
                    <button class="btn-mes" id="btnMesAnterior">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <button class="btn-mes" id="btnMesActual">Hoy</button>
                    <button class="btn-mes" id="btnMesSiguiente">
                        Siguiente <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
            <div class="calendario-leyenda">
                <span class="leyenda-titulo">Leyenda:</span>
                <div class="leyenda-items">
                    <div class="leyenda-pill est-emb">
                        <span class="leyenda-icono">📅</span>
                        <span class="leyenda-texto">Embarque Estimado</span>
                    </div>
                    <div class="leyenda-pill emb">
                        <span class="leyenda-icono">🚢</span>
                        <span class="leyenda-texto">Embarque Real</span>
                    </div>
                    <div class="leyenda-pill arr">
                        <span class="leyenda-icono">🛃</span>
                        <span class="leyenda-texto">Arribo</span>
                    </div>
                    <div class="leyenda-pill desp">
                        <span class="leyenda-icono">🚚</span>
                        <span class="leyenda-texto">Despacho Aduana</span>
                    </div>
                    <div class="leyenda-pill rec">
                        <span class="leyenda-icono">📦</span>
                        <span class="leyenda-texto">Recepción</span>
                    </div>
                </div>
            </div>
            <div class="calendario-grid" id="calendarioGrid"></div>
        </div>
    `);
    
    actualizarMesDisplay();
    generarCalendario();
    
    // Reconfigurar event listeners después de renderizar
    $('#btnMesAnterior').on('click', () => cambiarMes(-1));
    $('#btnMesSiguiente').on('click', () => cambiarMes(1));
    $('#btnMesActual').on('click', () => {
        mesActual = new Date();
        actualizarMesDisplay();
        renderizarVista();
    });
}

function generarCalendario() {
    const grid = $('#calendarioGrid');
    grid.empty();
    
    // Headers de días
    const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    diasSemana.forEach(dia => {
        grid.append(`<div class="calendario-dia-header">${dia}</div>`);
    });
    
    // Obtener primer y último día del mes
    const primerDia = new Date(mesActual.getFullYear(), mesActual.getMonth(), 1);
    const ultimoDia = new Date(mesActual.getFullYear(), mesActual.getMonth() + 1, 0);
    
    // Días del mes anterior para completar la primera semana
    const diasAnteriores = primerDia.getDay();
    for (let i = diasAnteriores - 1; i >= 0; i--) {
        const fecha = new Date(primerDia);
        fecha.setDate(fecha.getDate() - i - 1);
        grid.append(crearDiaCalendario(fecha, true));
    }
    
    // Días del mes actual
    for (let dia = 1; dia <= ultimoDia.getDate(); dia++) {
        const fecha = new Date(mesActual.getFullYear(), mesActual.getMonth(), dia);
        grid.append(crearDiaCalendario(fecha, false));
    }
    
    // Días del mes siguiente para completar la última semana
    const diasSiguientes = 7 - ((diasAnteriores + ultimoDia.getDate()) % 7);
    if (diasSiguientes < 7) {
        for (let i = 1; i <= diasSiguientes; i++) {
            const fecha = new Date(ultimoDia);
            fecha.setDate(fecha.getDate() + i);
            grid.append(crearDiaCalendario(fecha, true));
        }
    }
}

function crearDiaCalendario(fecha, otroMes) {
    const fechaStr = fecha.toISOString().split('T')[0];
    const eventos = obtenerEventosPorFecha(fechaStr);
    
    let eventosHtml = '';
    eventos.forEach(evento => {
        eventosHtml += `
            <div class="evento-pill ${evento.tipo}" onclick='abrirDetalleDespacho(${JSON.stringify(evento.despacho)})' title="${evento.texto}">
                <div class="evento-icono">${evento.icono}</div>
                <div class="evento-info">
                    <div class="evento-contenedor">${evento.contenedor}</div>
                    <div class="evento-proveedor">${evento.proveedor}</div>
                </div>
            </div>
        `;
    });
    
    return `
        <div class="calendario-dia ${otroMes ? 'otro-mes' : ''}">
            <div class="dia-numero">${fecha.getDate()}</div>
            <div class="dia-eventos">${eventosHtml}</div>
        </div>
    `;
}

function obtenerEventosPorFecha(fecha) {
    const eventos = [];
    
    despachos.forEach(despacho => {
        const proveedor = despacho.PROVEEDOR || 'Sin proveedor';
        const contenedor = despacho.CONTENEDOR || despacho.ORDEN_COMPRA;
        const textoCompleto = `${proveedor} - ${contenedor}`;
        
        // Fecha estimada de embarque
        if (despacho.FECHA_EST_EMB === fecha && (filtroEstado === 'todos' || filtroEstado === 'est-emb')) {
            eventos.push({
                tipo: 'est-emb',
                texto: textoCompleto,
                proveedor: proveedor,
                contenedor: contenedor,
                icono: '📅',
                despacho: despacho
            });
        }
        
        // Fecha real de embarque
        if (despacho.FECHA_EMB === fecha && (filtroEstado === 'todos' || filtroEstado === 'emb')) {
            eventos.push({
                tipo: 'emb',
                texto: textoCompleto,
                proveedor: proveedor,
                contenedor: contenedor,
                icono: '🚢',
                despacho: despacho
            });
        }
        
        // Fecha de arribo
        if (despacho.FECHA_ARR === fecha && (filtroEstado === 'todos' || filtroEstado === 'arr')) {
            eventos.push({
                tipo: 'arr',
                texto: textoCompleto,
                proveedor: proveedor,
                contenedor: contenedor,
                icono: '🛃',
                despacho: despacho
            });
        }
        
        // Fecha despacho aduana
        if (despacho.FECHA_DESP_ADU === fecha && (filtroEstado === 'todos' || filtroEstado === 'desp')) {
            eventos.push({
                tipo: 'desp',
                texto: textoCompleto,
                proveedor: proveedor,
                contenedor: contenedor,
                icono: '🚚',
                despacho: despacho
            });
        }
        
        // Fecha recibido
        if (despacho.FECHA_REC === fecha && (filtroEstado === 'todos' || filtroEstado === 'rec')) {
            eventos.push({
                tipo: 'rec',
                texto: textoCompleto,
                proveedor: proveedor,
                contenedor: contenedor,
                icono: '📦',
                despacho: despacho
            });
        }
    });
    
    return eventos;
}

function renderizarGrilla() {
    const container = $('#contenidoPrincipal');
    let html = '<div class="grilla-view">';
    
    despachos.forEach(despacho => {
        const proximoHito = obtenerProximoHito(despacho);
        html += crearCardDespacho(despacho, proximoHito);
    });
    
    html += '</div>';
    container.html(html);
}

function crearCardDespacho(despacho, proximoHito) {
    const estado = despacho.ESTADO;
    const icono = ICONOS_ESTADOS[estado];
    const label = LABELS_ESTADOS[estado];
    const esEstimado = !despacho.FECHA_EMB;
    
    return `
        <div class="despacho-card estado-${estado}" onclick='abrirDetalleDespacho(${JSON.stringify(despacho)})'>
            <div class="card-header">
                <div>
                    <div class="card-proveedor">${despacho.PROVEEDOR}</div>
                    <div class="card-contenedor">${icono} ${despacho.CONTENEDOR || 'Sin contenedor'}</div>
                    ${esEstimado ? '<div class="badge-estimado"><i class="bi bi-clock-history"></i> Fechas estimadas</div>' : ''}
                </div>
                <div class="card-estado-badge estado-${estado}">${label}</div>
            </div>
            
            <div class="card-info">
                <div class="card-info-item">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>OC: ${despacho.ORDEN_COMPRA}</span>
                </div>
            </div>
            
            ${proximoHito ? `
                <div class="card-proximo-hito">
                    <div class="proximo-hito-label">Próximo Hito</div>
                    <div class="proximo-hito-texto">${proximoHito}</div>
                </div>
            ` : ''}
        </div>
    `;
}

function obtenerProximoHito(despacho) {
    if (!despacho.FECHA_EMB) {
        return despacho.FECHA_EST_EMB ? `Embarca el ${formatearFecha(despacho.FECHA_EST_EMB)}` : 'Sin fecha de embarque';
    }
    if (!despacho.FECHA_ARR) {
        return 'Esperando arribo';
    }
    if (!despacho.FECHA_DESP_ADU) {
        return `Arribó el ${formatearFecha(despacho.FECHA_ARR)}`;
    }
    if (!despacho.FECHA_REC) {
        return `Despachado el ${formatearFecha(despacho.FECHA_DESP_ADU)}`;
    }
    return `Recibido el ${formatearFecha(despacho.FECHA_REC)}`;
}

// ========== MODAL DETALLE ==========
function abrirDetalleDespacho(despacho) {
    despachoSeleccionado = despacho;
    const estado = despacho.ESTADO;
    const label = LABELS_ESTADOS[estado];
    const esEstimado = !despacho.FECHA_EMB;
    
    const modalHtml = `
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-group">
                        <h2>${despacho.PROVEEDOR}</h2>
                        <p>${despacho.CONTENEDOR || 'Sin contenedor'} · <span class="card-estado-badge estado-${estado}">${label}</span></p>
                        ${esEstimado ? '<div class="alert-estimado"><i class="bi bi-info-circle"></i> Las fechas mostradas son estimaciones hasta que se confirme el embarque real</div>' : ''}
                    </div>
                    <button class="btn-close-modal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div class="detalle-info-grid">
                        <div class="detalle-info-item">
                            <label>Orden de Compra</label>
                            <value>${despacho.ORDEN_COMPRA}</value>
                        </div>
                        <div class="detalle-info-item">
                            <label>Código Proveedor</label>
                            <value>${despacho.COD_PROVEE}</value>
                        </div>
                        <div class="detalle-info-item">
                            <label>Contenedor</label>
                            <value>${despacho.CONTENEDOR || '-'}</value>
                        </div>
                        <div class="detalle-info-item">
                            <label>Fecha Est. Embarque</label>
                            <value>${despacho.FECHA_EST_EMB ? formatearFecha(despacho.FECHA_EST_EMB) : '-'}</value>
                        </div>
                    </div>
                    
                    ${crearTimeline(despacho)}
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modalHtml);
}

function crearTimeline(despacho) {
    // Si no hay FECHA_EMB, todas las fechas son estimadas
    const tieneEmbarqueReal = !!despacho.FECHA_EMB;
    
    const pasos = [
        { key: 'origen', label: 'En Origen', icono: '🏭', fecha: null, completado: true, estimado: false },
        { key: 'embarcado', label: tieneEmbarqueReal ? 'Embarcado' : 'Embarque (estimado)', icono: '🚢', fecha: despacho.FECHA_EMB || despacho.FECHA_EST_EMB, completado: !!despacho.FECHA_EMB, estimado: !tieneEmbarqueReal },
        { key: 'arribado', label: tieneEmbarqueReal ? 'Arribado' : 'Arribo (estimado)', icono: '🛃', fecha: despacho.FECHA_ARR, completado: !!despacho.FECHA_ARR && tieneEmbarqueReal, estimado: !tieneEmbarqueReal },
        { key: 'despachado', label: tieneEmbarqueReal ? 'Despachado' : 'Despacho (estimado)', icono: '🚚', fecha: despacho.FECHA_DESP_ADU, completado: !!despacho.FECHA_DESP_ADU && tieneEmbarqueReal, estimado: !tieneEmbarqueReal },
        { key: 'recibido', label: tieneEmbarqueReal ? 'Recibido' : 'Recepción (estimada)', icono: '📦', fecha: despacho.FECHA_REC, completado: !!despacho.FECHA_REC && tieneEmbarqueReal, estimado: !tieneEmbarqueReal }
    ];
    
    const pasosCompletados = pasos.filter(p => p.completado).length;
    const porcentajeProgreso = ((pasosCompletados - 1) / (pasos.length - 1)) * 100;
    
    let pasosHtml = '';
    pasos.forEach(paso => {
        const claseCompletado = paso.completado ? 'completed' : '';
        const claseRecibido = paso.key === 'recibido' ? 'recibido' : '';
        const claseEstimado = paso.estimado ? 'estimado' : '';
        
        pasosHtml += `
            <div class="timeline-step ${claseCompletado} ${claseRecibido} ${claseEstimado}">
                <div class="timeline-icon">${paso.icono}</div>
                <div class="timeline-label">
                    <div class="timeline-label-title">${paso.label}</div>
                    <div class="timeline-label-date">${paso.fecha ? formatearFecha(paso.fecha) : '-'}</div>
                </div>
            </div>
        `;
    });
    
    return `
        <div class="timeline-container">
            <div class="timeline-title">Timeline de Proceso Logístico</div>
            <div class="timeline">
                <div class="timeline-track">
                    <div class="timeline-line">
                        <div class="timeline-progress" style="width: ${porcentajeProgreso}%"></div>
                    </div>
                    ${pasosHtml}
                </div>
            </div>
        </div>
    `;
}

function cerrarModal() {
    $('.modal-overlay').remove();
    despachoSeleccionado = null;
}

// ========== UTILIDADES ==========
function cambiarMes(delta) {
    mesActual.setMonth(mesActual.getMonth() + delta);
    actualizarMesDisplay();
    renderizarVista();
}

function actualizarMesDisplay() {
    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                   'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const mesNombre = meses[mesActual.getMonth()];
    const año = mesActual.getFullYear();
    $('#calendarioMes').text(`${mesNombre} ${año}`);
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    const date = new Date(fecha + 'T00:00:00');
    return date.toLocaleDateString('es-UY', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function mostrarError(mensaje) {
    console.error(mensaje);
    alert(mensaje);
}
