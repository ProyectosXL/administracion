/**
 * Cronograma de Despachos - JavaScript
 * Gestión de vista de calendario y timeline de contenedores
 */

let despachos = [];
let vistaActual = 'calendario'; // 'calendario' o 'grilla'
let mesActual = new Date();
let despachoSeleccionado = null;
let filtrosActivos = ['est-emb', 'emb', 'arr-estimado', 'arr-real', 'desp', 'rec']; // Filtros múltiples

// Todo esto llega en la misma respuesta que los despachos, para no tener que
// resolver nada por AJAX en medio de un render.
let rubrosPorOC = {};   // { '<ORDEN_COMPRA>': [{ rubro, cantidad }, ...] }
let aliasProveedor = {}; // { '<COD_PROVEE>': 'LC' }
let iconosRubro = {};    // { '<RUBRO>': 'bi-gem' | '👟' }
let motivosFecha = [];   // [{ codigo, label }, ...]

// Defaults de red: si el endpoint no los trae, son los mismos valores que
// antes estaban hardcodeados en este archivo.
let parametrosDias = {
    DIAS_EMB_ARR: 45,
    DIAS_ARR_DESP: 7,
    DIAS_DESP_REC: 3,
    DIAS_ARR_DIST: 10
};

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
    
    // Filtros múltiples
    $(document).on('click', '#btnFiltros', function(e) {
        e.stopPropagation();
        $('#filtrosDropdown').toggleClass('show');
    });
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.filtros-container').length) {
            $('#filtrosDropdown').removeClass('show');
        }
    });
    
    $(document).on('change', '.filtro-checkbox', function() {
        actualizarFiltros();
    });
    
    $(document).on('click', '#btnLimpiarFiltros', function() {
        $('.filtro-checkbox').prop('checked', false);
        actualizarFiltros();
    });
    
    // Cerrar modal
    $(document).on('click', '.modal-overlay', function(e) {
        if (e.target === this) {
            cerrarModal();
        }
    });
    
    $(document).on('click', '.btn-close-modal', cerrarModal);
}

function actualizarFiltros() {
    filtrosActivos = [];
    $('.filtro-checkbox:checked').each(function() {
        filtrosActivos.push($(this).val());
    });
    
    // Actualizar badge
    const totalFiltros = $('.filtro-checkbox').length;
    const filtrosSeleccionados = filtrosActivos.length;
    
    if (filtrosSeleccionados === totalFiltros) {
        $('#badgeFiltros').text('').hide();
    } else if (filtrosSeleccionados === 0) {
        $('#badgeFiltros').text('0').show();
    } else {
        $('#badgeFiltros').text(filtrosSeleccionados).show();
    }
    
    renderizarVista();
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
                rubrosPorOC = response.rubros || {};
                aliasProveedor = response.alias || {};
                iconosRubro = response.iconosRubro || {};
                motivosFecha = response.motivos || [];
                if (response.parametros) {
                    parametrosDias = $.extend({}, parametrosDias, response.parametros);
                }
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
        renderizarPanelProximosArribos();
    } else {
        renderizarGrilla();
        renderizarPanelProximosArribos();
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
                    <div class="leyenda-pill arr-estimado">
                        <span class="leyenda-icono">🛃</span>
                        <span class="leyenda-texto">Arribo Estimado</span>
                    </div>
                    <div class="leyenda-pill arr-real">
                        <span class="leyenda-icono">🛃</span>
                        <span class="leyenda-texto">Arribo Real</span>
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
        if (despacho.FECHA_EST_EMB === fecha && filtrosActivos.includes('est-emb')) {
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
        if (despacho.FECHA_EMB === fecha && filtrosActivos.includes('emb')) {
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
        if (despacho.FECHA_ARR === fecha) {
            // Determinar si es arribo real o estimado
            const etaConfirmada = parseInt(despacho.ETA_CONFIRMADA) === 1;
            const tipoArribo = etaConfirmada ? 'arr-real' : 'arr-estimado';
            
            if (filtrosActivos.includes(tipoArribo)) {
                eventos.push({
                    tipo: tipoArribo,
                    texto: textoCompleto,
                    proveedor: proveedor,
                    contenedor: contenedor,
                    icono: '🛃',
                    despacho: despacho
                });
            }
        }
        
        // Fecha despacho aduana
        if (despacho.FECHA_DESP_ADU === fecha && filtrosActivos.includes('desp')) {
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
        if (despacho.FECHA_REC === fecha && filtrosActivos.includes('rec')) {
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

// ========== PANEL PRÓXIMOS ARRIBOS ==========
function renderizarPanelProximosArribos() {
    // Filtrar despachos que no han sido recibidos aún (independientemente de si ya arribaron)
    const despachosConArribo = despachos.filter(d => {
        // Mostrar si no ha sido recibido y tiene fecha de arribo (real o estimada)
        const tieneArribo = d.FECHA_ARR || calcularFechaArriboEstimada(d);
        return !d.FECHA_REC && tieneArribo;
    });
    
    // Ordenar por fecha de arribo (usar real si existe, sino estimada)
    despachosConArribo.sort((a, b) => {
        const fechaA = a.FECHA_ARR || calcularFechaArriboEstimada(a);
        const fechaB = b.FECHA_ARR || calcularFechaArriboEstimada(b);
        if (!fechaA) return 1;
        if (!fechaB) return -1;
        return new Date(fechaA) - new Date(fechaB);
    });
    
    // Tomar solo los 3 primeros
    const proximosArribos = despachosConArribo.slice(0, 3);
    
    let html = `
        <div class="panel-proximos-arribos">
            <div class="panel-header">
                <h3><i class="bi bi-ship"></i> Próximos Arribos</h3>
            </div>
            <div class="panel-body">
    `;
    
    if (proximosArribos.length === 0) {
        html += '<div class="panel-empty">No hay arribos pendientes</div>';
    } else {
        proximosArribos.forEach(despacho => {
            html += crearCardProximoArribo(despacho);
        });
    }
    
    html += `
            </div>
            <div class="panel-footer">
                <a href="#" class="panel-link" id="verTodosLink">Ver todos →</a>
            </div>
        </div>
    `;
    
    // Remover panel anterior si existe
    $('.panel-proximos-arribos').remove();
    
    // Agregar nuevo panel
    $('body').append(html);
    
    // Event listener para ver todos
    $('#verTodosLink').on('click', function(e) {
        e.preventDefault();
        $('.btn-view').removeClass('active');
        $('.btn-view[data-view="grilla"]').addClass('active');
        vistaActual = 'grilla';
        renderizarVista();
    });
}

function calcularFechaArriboEstimada(despacho) {
    if (despacho.FECHA_ARR) return despacho.FECHA_ARR;
    
    // Si hay fecha real de embarque, sumar 45 días
    if (despacho.FECHA_EMB) {
        const fechaEmb = new Date(despacho.FECHA_EMB + 'T00:00:00');
        fechaEmb.setDate(fechaEmb.getDate() + 45);
        return fechaEmb.toISOString().split('T')[0];
    }
    
    // Si solo hay fecha estimada de embarque, sumar 45 días
    if (despacho.FECHA_EST_EMB) {
        const fechaEstEmb = new Date(despacho.FECHA_EST_EMB + 'T00:00:00');
        fechaEstEmb.setDate(fechaEstEmb.getDate() + 45);
        return fechaEstEmb.toISOString().split('T')[0];
    }
    
    return null;
}

function crearCardProximoArribo(despacho) {
    const fechaArribo = despacho.FECHA_ARR || calcularFechaArriboEstimada(despacho);
    const diasRestantes = calcularDiasRestantes(fechaArribo);
    const claseUrgencia = obtenerClaseUrgencia(diasRestantes);
    const contenedor = despacho.CONTENEDOR || 'Sin contenedor';
    
    // Determinar si es arribo real o estimado
    const etaConfirmada = parseInt(despacho.ETA_CONFIRMADA) === 1;
    const tipoArribo = etaConfirmada ? 'real' : 'estimado';
    const colorArribo = etaConfirmada ? 'var(--color-arr-real)' : 'var(--color-arr-estimado)';
    const iconoArribo = etaConfirmada ? '✓' : '~';
    const textoArribo = etaConfirmada ? 'Arribo Real' : 'Arribo Estimado';
    
    // Calcular progreso
    const progreso = calcularProgreso(despacho);
    
    return `
        <div class="card-proximo-arribo arribo-${tipoArribo}" onclick='abrirDetalleDespacho(${JSON.stringify(despacho)})'>
            <div class="arribo-header">
                <div class="arribo-contenedor">${contenedor}</div>
                <div class="arribo-countdown ${claseUrgencia}">
                    ${diasRestantes >= 0 ? `En ${diasRestantes} días` : `Atrasado ${Math.abs(diasRestantes)} días`}
                </div>
            </div>
            <div class="arribo-tipo-badge" style="background: ${colorArribo};">
                ${iconoArribo} ${textoArribo}
            </div>
            <div class="arribo-proveedor">${despacho.PROVEEDOR}</div>
            <div class="arribo-fecha">
                <i class="bi bi-calendar-event"></i> ${formatearFecha(fechaArribo)}
            </div>
            <div class="arribo-progreso">
                ${crearIndicadorProgreso(progreso)}
            </div>
        </div>
    `;
}

function calcularDiasRestantes(fecha) {
    if (!fecha) return 999;
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const fechaObj = new Date(fecha + 'T00:00:00');
    const diff = fechaObj - hoy;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

function obtenerClaseUrgencia(dias) {
    if (dias < 0) return 'urgencia-atrasado';
    if (dias < 7) return 'urgencia-alta';
    if (dias <= 14) return 'urgencia-media';
    return 'urgencia-baja';
}

function calcularProgreso(despacho) {
    // Retorna objeto con estado de cada etapa
    return {
        origen: true, // Siempre completado
        embarque: !!despacho.FECHA_EMB,
        arribo: !!despacho.FECHA_ARR,
        despacho: !!despacho.FECHA_DESP_ADU,
        recepcion: !!despacho.FECHA_REC
    };
}

function crearIndicadorProgreso(progreso) {
    const etapas = [
        { key: 'origen', completado: progreso.origen },
        { key: 'embarque', completado: progreso.embarque },
        { key: 'arribo', completado: progreso.arribo },
        { key: 'despacho', completado: progreso.despacho },
        { key: 'recepcion', completado: progreso.recepcion }
    ];
    
    let html = '';
    etapas.forEach(etapa => {
        const clase = etapa.completado ? 'punto-completado' : 'punto-pendiente';
        html += `<span class="punto-progreso ${clase}"></span>`;
    });
    
    return html;
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
    
    // Determinar si es arribo real cuando el estado es arribado
    const etaConfirmada = parseInt(despacho.ETA_CONFIRMADA) === 1;
    const claseArriboReal = (estado === 'arribado' && etaConfirmada) ? 'arribo-confirmado' : '';
    
    return `
        <div class="despacho-card estado-${estado} ${claseArriboReal}" onclick='abrirDetalleDespacho(${JSON.stringify(despacho)})'>
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
    
    // Calcular fechas estimadas si hay embarque real
    const fechasEstimadas = calcularFechasEstimadas(despacho);
    
    // Determinar si ETA está confirmada
    const etaConfirmada = parseInt(despacho.ETA_CONFIRMADA) === 1;
    
    const pasos = [
        { 
            key: 'origen', 
            label: 'En Origen', 
            icono: '🏭', 
            fecha: null, 
            fechaEstimada: null,
            completado: true, 
            estimado: false,
            demorado: false
        },
        { 
            key: 'embarcado', 
            label: tieneEmbarqueReal ? 'Embarcado' : 'Embarque (estimado)', 
            icono: '🚢', 
            fecha: despacho.FECHA_EMB || despacho.FECHA_EST_EMB,
            fechaEstimada: despacho.FECHA_EST_EMB,
            completado: !!despacho.FECHA_EMB, 
            estimado: !tieneEmbarqueReal,
            demorado: verificarDemora(despacho.FECHA_EMB, despacho.FECHA_EST_EMB)
        },
        { 
            key: 'arribado', 
            label: etaConfirmada ? 'Arribo Real' : 'Arribo Estimado', 
            icono: '🛃', 
            fecha: despacho.FECHA_ARR || fechasEstimadas.arribo,
            fechaEstimada: fechasEstimadas.arribo,
            completado: !!despacho.FECHA_ARR && tieneEmbarqueReal, 
            estimado: !etaConfirmada,
            esArriboReal: etaConfirmada,
            demorado: verificarDemora(despacho.FECHA_ARR, fechasEstimadas.arribo)
        },
        { 
            key: 'despachado', 
            label: tieneEmbarqueReal ? 'Despachado' : 'Despacho (estimado)', 
            icono: '🚚', 
            fecha: despacho.FECHA_DESP_ADU || fechasEstimadas.despacho,
            fechaEstimada: fechasEstimadas.despacho,
            completado: !!despacho.FECHA_DESP_ADU && tieneEmbarqueReal, 
            estimado: !tieneEmbarqueReal || !despacho.FECHA_DESP_ADU,
            demorado: verificarDemora(despacho.FECHA_DESP_ADU, fechasEstimadas.despacho)
        },
        { 
            key: 'recibido', 
            label: tieneEmbarqueReal ? 'Recibido' : 'Recepción (estimada)', 
            icono: '📦', 
            fecha: despacho.FECHA_REC || fechasEstimadas.recepcion,
            fechaEstimada: fechasEstimadas.recepcion,
            completado: !!despacho.FECHA_REC && tieneEmbarqueReal, 
            estimado: !tieneEmbarqueReal || !despacho.FECHA_REC,
            demorado: verificarDemora(despacho.FECHA_REC, fechasEstimadas.recepcion)
        }
    ];
    
    const pasosCompletados = pasos.filter(p => p.completado).length;
    const porcentajeProgreso = ((pasosCompletados - 1) / (pasos.length - 1)) * 100;
    
    // Calcular countdown hasta recepción
    const fechaRecepcion = despacho.FECHA_REC || fechasEstimadas.recepcion;
    const diasHastaRecepcion = calcularDiasRestantes(fechaRecepcion);
    const countdownTexto = diasHastaRecepcion > 0 
        ? `Disponible en aproximadamente ${diasHastaRecepcion} días`
        : diasHastaRecepcion === 0
        ? 'Disponible hoy'
        : `Recibido hace ${Math.abs(diasHastaRecepcion)} días`;
    
    let pasosHtml = '';
    pasos.forEach((paso, index) => {
        const claseCompletado = paso.completado ? 'completed' : '';
        const claseRecibido = paso.key === 'recibido' ? 'recibido' : '';
        const claseEstimado = paso.estimado ? 'estimado' : '';
        const claseArriboReal = paso.esArriboReal ? 'arribo-real' : '';
        const claseDemorado = paso.demorado ? 'demorado' : '';
        
        // Determinar color de la barra para este segmento
        let colorBarra = '';
        if (index < pasosCompletados && index < pasos.length - 1) {
            // Segmento completado - usar color del paso siguiente
            const pasoSiguiente = pasos[index + 1];
            if (pasoSiguiente.key === 'embarcado') colorBarra = 'var(--color-emb)';
            else if (pasoSiguiente.key === 'arribado') colorBarra = 'var(--color-arr)';
            else if (pasoSiguiente.key === 'despachado') colorBarra = 'var(--color-desp)';
            else if (pasoSiguiente.key === 'recibido') colorBarra = 'var(--color-rec)';
        }
        
        pasosHtml += `
            <div class="timeline-step ${claseCompletado} ${claseRecibido} ${claseEstimado} ${claseArriboReal} ${claseDemorado}" data-color="${colorBarra}">
                <div class="timeline-icon">${paso.icono}</div>
                <div class="timeline-label">
                    <div class="timeline-label-title">${paso.label}${paso.demorado ? ' <i class="bi bi-exclamation-circle text-danger"></i>' : ''}</div>
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
                    <div class="timeline-line-background"></div>
                    <div class="timeline-line-progress" id="timelineProgress"></div>
                    ${pasosHtml}
                </div>
            </div>
            <div class="timeline-countdown">
                <i class="bi bi-hourglass-split"></i> ${countdownTexto}
            </div>
        </div>
    `;
}

function calcularFechasEstimadas(despacho) {
    const fechas = {
        arribo: null,
        despacho: null,
        recepcion: null
    };
    
    // Si hay fecha real de embarque, calcular desde ahí
    if (despacho.FECHA_EMB) {
        const fechaEmb = new Date(despacho.FECHA_EMB + 'T00:00:00');
        
        const fechaArr = new Date(fechaEmb);
        fechaArr.setDate(fechaArr.getDate() + 45);
        fechas.arribo = fechaArr.toISOString().split('T')[0];
        
        const fechaDesp = new Date(fechaArr);
        fechaDesp.setDate(fechaDesp.getDate() + 7);
        fechas.despacho = fechaDesp.toISOString().split('T')[0];
        
        const fechaRec = new Date(fechaDesp);
        fechaRec.setDate(fechaRec.getDate() + 3);
        fechas.recepcion = fechaRec.toISOString().split('T')[0];
    }
    // Si solo hay fecha estimada de embarque
    else if (despacho.FECHA_EST_EMB) {
        const fechaEstEmb = new Date(despacho.FECHA_EST_EMB + 'T00:00:00');
        
        const fechaArr = new Date(fechaEstEmb);
        fechaArr.setDate(fechaArr.getDate() + 45);
        fechas.arribo = fechaArr.toISOString().split('T')[0];
        
        const fechaDesp = new Date(fechaArr);
        fechaDesp.setDate(fechaDesp.getDate() + 7);
        fechas.despacho = fechaDesp.toISOString().split('T')[0];
        
        const fechaRec = new Date(fechaDesp);
        fechaRec.setDate(fechaRec.getDate() + 3);
        fechas.recepcion = fechaRec.toISOString().split('T')[0];
    }
    
    return fechas;
}

function verificarDemora(fechaReal, fechaEstimada) {
    if (!fechaReal || !fechaEstimada) return false;
    
    const real = new Date(fechaReal + 'T00:00:00');
    const estimada = new Date(fechaEstimada + 'T00:00:00');
    
    return real > estimada;
}

function cerrarModal() {
    $('.modal-overlay').remove();
    despachoSeleccionado = null;
}

// Animar barra de progreso coloreada después de que se renderice el modal
$(document).on('DOMNodeInserted', '.timeline-track', function() {
    animarBarraProgresoColoreada();
});

function animarBarraProgresoColoreada() {
    const steps = $('.timeline-step');
    const progressContainer = $('#timelineProgress');
    
    if (steps.length === 0 || progressContainer.length === 0) return;
    
    let html = '';
    steps.each(function(index) {
        if (index === steps.length - 1) return; // No procesar el último
        
        const $step = $(this);
        const isCompleted = $step.hasClass('completed');
        const color = $step.data('color');
        
        if (isCompleted && color) {
            const segmentWidth = 100 / (steps.length - 1);
            html += `<div class="progress-segment" style="width: ${segmentWidth}%; background: ${color};"></div>`;
        }
    });
    
    progressContainer.html(html);
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
