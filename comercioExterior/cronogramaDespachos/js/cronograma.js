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

// Etiquetas por tipo de evento del calendario (distinto de los estados).
const LABELS_EVENTOS = {
    'est-emb': 'Embarque estimado',
    'emb': 'Embarque real',
    'arr-estimado': 'Arribo estimado',
    'arr-real': 'Arribo real',
    'desp': 'Despacho de aduana',
    'rec': 'Recepción'
};

// ========== PREFERENCIAS EN localStorage ==========
// Envueltas en try/catch: en ventana privada o con las cookies de sitio
// bloqueadas el solo hecho de tocar localStorage tira excepcion.
const PREF_PANEL_COLAPSADO = 'cronograma.panelColapsado';

function leerPreferencia(clave, porDefecto) {
    try {
        const v = localStorage.getItem(clave);
        return v === null ? porDefecto : v;
    } catch (e) {
        return porDefecto;
    }
}

function guardarPreferencia(clave, valor) {
    try {
        localStorage.setItem(clave, valor);
    } catch (e) {
        /* sin persistencia, la sesion sigue funcionando igual */
    }
}

// Arranca expandido: solo se colapsa si el usuario lo dejo asi.
let panelColapsado = leerPreferencia(PREF_PANEL_COLAPSADO, '0') === '1';

// Densidad de los badges del calendario. Compacto por defecto.
const PREF_DENSIDAD = 'cronograma.densidad';
const MAX_EVENTOS_VISIBLES = { compacto: 5, comodo: 3 };

let densidad = leerPreferencia(PREF_DENSIDAD, 'compacto') === 'comodo' ? 'comodo' : 'compacto';

// ========== HELPERS ==========

/**
 * Fecha local en formato YYYY-MM-DD.
 *
 * Reemplaza a toISOString().split('T')[0], que convierte a UTC: al este de
 * Greenwich adelanta un dia y al oeste lo atrasa, segun la hora. Las fechas
 * del cronograma son fechas de calendario sin hora, asi que se arman con los
 * getters locales.
 */
function aISO(fecha) {
    const y = fecha.getFullYear();
    const m = String(fecha.getMonth() + 1).padStart(2, '0');
    const d = String(fecha.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

/** Suma dias corridos a una fecha 'YYYY-MM-DD' y devuelve otra 'YYYY-MM-DD'. */
function sumarDias(fechaIso, dias) {
    if (!fechaIso) return null;
    const f = new Date(fechaIso + 'T00:00:00');
    f.setDate(f.getDate() + dias);
    return aISO(f);
}

/**
 * Escapa texto para interpolarlo en HTML. Los nombres de proveedor traen
 * comillas y ampersands ("GUANG ZHOU YILAN LEATHER CO.,LTD.") y ademas se
 * usan dentro de atributos.
 */
function escaparHtml(texto) {
    return String(texto === null || texto === undefined ? '' : texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/** Todas las OCs de un grupo de contenedor. */
function despachosDelGrupo(idGrupo) {
    return despachos.filter(d => d.ID_GRUPO === idGrupo);
}

/**
 * Alias de 2 letras del proveedor.
 * Si no hay alias configurado se derivan las 2 primeras letras del nombre y
 * se marca como provisorio, para que el badge lo muestre atenuado y se note
 * que falta cargarlo.
 */
function aliasDeDespacho(despacho) {
    const codigo = despacho.COD_PROVEE;
    const configurado = codigo ? aliasProveedor[codigo] : null;

    if (configurado) {
        return { texto: configurado, provisorio: false };
    }

    const letras = (despacho.PROVEEDOR || '')
        .replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/g, '')
        .slice(0, 2)
        .toUpperCase();

    return { texto: letras || '??', provisorio: true };
}

// Un warning por rubro y no uno por badge: si no, el mismo rubro sin mapear
// inunda la consola en cada render.
const rubrosSinIconoAvisados = new Set();

function iconoDeRubro(rubro) {
    if (!rubro || rubro === 'SIN RUBRO') {
        return 'bi-question-circle';
    }
    if (iconosRubro[rubro]) {
        return iconosRubro[rubro];
    }
    if (!rubrosSinIconoAvisados.has(rubro)) {
        rubrosSinIconoAvisados.add(rubro);
        console.warn(`[cronograma] Rubro sin icono configurado: "${rubro}". Se usa bi-tag.`);
    }
    return 'bi-tag';
}

/** La columna ICONO admite clase de bootstrap-icons o un emoji suelto. */
function renderizarIcono(valor) {
    if (typeof valor === 'string' && valor.indexOf('bi-') === 0) {
        return `<i class="bi ${escaparHtml(valor)}"></i>`;
    }
    return escaparHtml(valor);
}

/**
 * Rubros de un grupo, sumando las cantidades de TODAS sus OCs y ordenados
 * por cantidad descendente.
 */
function rubrosDelGrupo(listaDespachos) {
    const acumulado = new Map();

    listaDespachos.forEach(d => {
        const rubros = rubrosPorOC[d.ORDEN_COMPRA] || [];
        rubros.forEach(r => {
            acumulado.set(r.rubro, (acumulado.get(r.rubro) || 0) + r.cantidad);
        });
    });

    return Array.from(acumulado, ([rubro, cantidad]) => ({ rubro, cantidad }))
        .sort((a, b) => b.cantidad - a.cantidad);
}

function aplicarEstadoPanel() {
    $('body').toggleClass('panel-colapsado', panelColapsado);
}

function aplicarEstadoDensidad() {
    $('body').toggleClass('densidad-comodo', densidad === 'comodo');
    $('.btn-densidad').removeClass('active')
        .filter(`[data-densidad="${densidad}"]`).addClass('active');
}

// ========== TOOLTIP ==========
// Un unico div reutilizable, no un Tooltip de Bootstrap por badge: el
// calendario pinta cientos de badges y se reconstruye entero en cada render,
// asi que instanciar y destruir un tooltip por elemento cuesta caro y deja
// instancias huerfanas. Aca hay un solo nodo que se reposiciona.
const TOOLTIP_DELAY_MS = 150;

let $tooltip = null;
let tooltipTimer = null;

function obtenerTooltip() {
    if (!$tooltip) {
        $tooltip = $('<div class="cronograma-tooltip" role="tooltip"></div>').appendTo('body');
    }
    return $tooltip;
}

function ocultarTooltip() {
    // Sin delay al ocultar: si el puntero ya salio, el tooltip estorba.
    clearTimeout(tooltipTimer);
    if ($tooltip) {
        $tooltip.removeClass('visible');
    }
}

/**
 * Dias restantes al arribo del grupo, en texto.
 * Usa el arribo real si existe y si no el estimado.
 */
function textoDiasAlArribo(despacho) {
    const fechaArribo = despacho.FECHA_ARR || calcularFechaArriboEstimada(despacho);
    if (!fechaArribo) return null;

    const dias = calcularDiasRestantes(fechaArribo);
    const confirmado = !!despacho.FECHA_ARR;
    const matiz = confirmado ? '' : ' (estimado)';

    if (dias < 0) {
        return { texto: `Atrasado ${Math.abs(dias)} días${matiz}`, clase: 'urgencia-atrasado' };
    }
    if (dias === 0) {
        return { texto: `Arriba hoy${matiz}`, clase: 'urgencia-alta' };
    }
    return {
        texto: `Faltan ${dias} ${dias === 1 ? 'día' : 'días'} para el arribo${matiz}`,
        clase: obtenerClaseUrgencia(dias)
    };
}

function construirTooltip(evento) {
    const representante = evento.despacho;
    const alias = aliasDeDespacho(representante);
    const proveedor = representante.PROVEEDOR || 'Sin proveedor';

    // Todas las OCs del grupo, no solo la del representante.
    const ordenes = evento.despachos.map(d => (d.ORDEN_COMPRA || '').trim()).filter(Boolean);
    const etiquetaOC = ordenes.length > 1 ? `OCs (${ordenes.length})` : 'OC';

    const rubros = rubrosDelGrupo(evento.despachos);
    const rubrosVisibles = rubros.slice(0, 5);
    const rubrosRestantes = rubros.length - rubrosVisibles.length;

    let rubrosHtml = '';
    if (rubrosVisibles.length > 0) {
        rubrosHtml = `
            <div class="tt-rubros">
                ${rubrosVisibles.map(r => `
                    <div class="tt-rubro">
                        <span class="tt-rubro-icono">${renderizarIcono(iconoDeRubro(r.rubro))}</span>
                        <span class="tt-rubro-nombre">${escaparHtml(r.rubro)}</span>
                        <span class="tt-rubro-cantidad">${r.cantidad.toLocaleString('es-AR')}</span>
                    </div>
                `).join('')}
                ${rubrosRestantes > 0
                    ? `<div class="tt-rubro tt-rubro-mas">y ${rubrosRestantes} más</div>`
                    : ''}
            </div>
        `;
    }

    const dias = textoDiasAlArribo(representante);
    const diasHtml = dias
        ? `<div class="tt-dias ${dias.clase}">${escaparHtml(dias.texto)}</div>`
        : '';

    // La recepcion viene de Tango y no se puede editar; se avisa desde aca
    // para que quede claro antes de intentar arrastrarla.
    const notaRec = evento.tipo === 'rec'
        ? '<div class="tt-nota">Fecha de recepción tomada de Tango, no editable</div>'
        : '';

    return `
        <div class="tt-header">
            <span class="tt-alias${alias.provisorio ? ' alias-provisorio' : ''}">${escaparHtml(alias.texto)}</span>
            <span class="tt-proveedor">${escaparHtml(proveedor)}</span>
        </div>
        <div class="tt-evento tipo-${evento.tipo}">
            <span class="tt-dot"></span>
            ${escaparHtml(LABELS_EVENTOS[evento.tipo] || evento.tipo)} · ${formatearFecha(evento.fecha)}
        </div>
        <div class="tt-fila">
            <span class="tt-label">Contenedor</span>
            <span class="tt-valor">${escaparHtml(representante.CONTENEDOR || '-')}</span>
        </div>
        <div class="tt-fila">
            <span class="tt-label">${etiquetaOC}</span>
            <span class="tt-valor">${escaparHtml(ordenes.join(', ') || '-')}</span>
        </div>
        ${diasHtml}
        ${rubrosHtml}
        ${notaRec}
    `;
}

/**
 * Posiciona el tooltip pegado al badge, volteando arriba/abajo e
 * izquierda/derecha cuando se saldria del viewport.
 */
function posicionarTooltip($tt, elemento) {
    const ancla = elemento.getBoundingClientRect();
    const ancho = $tt.outerWidth();
    const alto = $tt.outerHeight();
    const margen = 8;
    const separacion = 6;

    let izq = ancla.left;
    let arriba = ancla.bottom + separacion;

    // Volteo vertical: si no entra abajo, va arriba del badge.
    if (arriba + alto > window.innerHeight - margen) {
        const posArriba = ancla.top - alto - separacion;
        arriba = (posArriba >= margen) ? posArriba
                                       : Math.max(margen, window.innerHeight - alto - margen);
    }

    // Volteo horizontal: se alinea al borde derecho del badge.
    if (izq + ancho > window.innerWidth - margen) {
        izq = Math.max(margen, ancla.right - ancho);
    }
    if (izq < margen) {
        izq = margen;
    }

    $tt.css({ left: `${izq}px`, top: `${arriba}px` });
}

function mostrarTooltip(elemento) {
    const $pill = $(elemento);
    const idGrupo = parseInt($pill.data('id-grupo'), 10);
    const tipo = $pill.data('tipo');

    // La fecha viaja en el badge y no en la celda: los badges del popover
    // viven fuera de la grilla del calendario.
    const fecha = $pill.data('fecha');
    if (!fecha) return;

    const evento = obtenerEventosPorFecha(fecha)
        .find(e => e.idGrupo === idGrupo && e.tipo === tipo);
    if (!evento) return;

    const $tt = obtenerTooltip();
    $tt.html(construirTooltip(evento));
    // Se posiciona con el contenido ya puesto: antes de eso no se puede medir.
    posicionarTooltip($tt, elemento);
    $tt.addClass('visible');
}

// ========== POPOVER "+N MÁS" ==========
function cerrarPopoverDia() {
    $('.popover-dia').remove();
}

/**
 * Lista completa de eventos de un dia. Los badges son los mismos que en la
 * celda, asi que siguen siendo clickeables y abren el detalle.
 */
function abrirPopoverDia(fechaStr, elementoAncla) {
    cerrarPopoverDia();

    const eventos = obtenerEventosPorFecha(fechaStr);
    if (eventos.length === 0) return;

    const html = `
        <div class="popover-dia">
            <div class="popover-dia-header">
                <span>${formatearFecha(fechaStr)}</span>
                <span class="popover-dia-conteo">${eventos.length} eventos</span>
            </div>
            <div class="popover-dia-body">
                ${eventos.map(crearBadgeEvento).join('')}
            </div>
        </div>
    `;

    const $pop = $(html).appendTo('body');

    // Posicionado sobre el chip, con volteo cuando se sale del viewport.
    const ancla = elementoAncla.getBoundingClientRect();
    const ancho = $pop.outerWidth();
    const alto = $pop.outerHeight();
    const margen = 8;

    let izq = ancla.left;
    let arriba = ancla.bottom + 4;

    if (izq + ancho > window.innerWidth - margen) {
        izq = Math.max(margen, window.innerWidth - ancho - margen);
    }
    if (arriba + alto > window.innerHeight - margen) {
        arriba = Math.max(margen, ancla.top - alto - 4);
    }

    $pop.css({ left: `${izq}px`, top: `${arriba}px` });
}

function alternarPanel() {
    panelColapsado = !panelColapsado;
    guardarPreferencia(PREF_PANEL_COLAPSADO, panelColapsado ? '1' : '0');
    aplicarEstadoPanel();
    // Colapsar ensancha las celdas, o sea que cambia cuantos badges entran
    // por dia: hay que rehacer el calendario, no solo mover el panel.
    renderizarVista();
}

// ========== INICIALIZACIÓN ==========
$(document).ready(function() {
    aplicarEstadoPanel();
    aplicarEstadoDensidad();
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
    
    // Tooltip propio sobre los badges. Delegado, asi sobrevive a los renders.
    $(document).on('mouseenter', '.evento-pill', function() {
        const elemento = this;
        clearTimeout(tooltipTimer);
        tooltipTimer = setTimeout(() => mostrarTooltip(elemento), TOOLTIP_DELAY_MS);
    });

    $(document).on('mouseleave', '.evento-pill', ocultarTooltip);

    // Si no se oculta en estos tres casos, el tooltip queda flotando sobre
    // contenido que ya no existe: el badge se va con el re-render, o la
    // celda se scrollea y el tooltip queda apuntando al vacio.
    $(document).on('click', '.evento-pill', ocultarTooltip);
    $(window).on('resize', ocultarTooltip);

    // En fase de captura y no delegado: el evento scroll no burbujea, asi que
    // un $(document).on('scroll', '.calendario-view', ...) nunca se
    // dispararia. Con capture:true se ven tambien los scrolls internos.
    document.addEventListener('scroll', ocultarTooltip, true);

    // Abrir el detalle desde cualquier badge, tarjeta o card del panel.
    // Delegado y por ID: nada de serializar el despacho en el HTML.
    $(document).on('click', '.evento-pill', function() {
        // Los badges del popover son los mismos: al abrir el detalle hay que
        // cerrarlo, si no queda flotando detras del modal.
        cerrarPopoverDia();
        abrirDetallePorGrupo($(this).data('id-grupo'));
    });

    $(document).on('click', '.card-proximo-arribo, .despacho-card', function() {
        abrirDetallePorId($(this).data('id'));
    });

    // Chip "+N mas": lista completa del dia
    $(document).on('click', '.evento-mas', function(e) {
        e.stopPropagation();
        abrirPopoverDia($(this).data('fecha'), this);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.popover-dia, .evento-mas').length) {
            cerrarPopoverDia();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarPopoverDia();
        }
    });

    // Densidad de los badges
    $(document).on('click', '.btn-densidad', function() {
        const nueva = $(this).data('densidad');
        if (nueva === densidad) return;
        densidad = nueva;
        guardarPreferencia(PREF_DENSIDAD, densidad);
        aplicarEstadoDensidad();
        renderizarVista();
    });

    // Colapsar / expandir el panel de proximos arribos.
    // Delegados: el panel se vuelve a construir en cada render, asi que un
    // listener directo se perderia.
    $(document).on('click', '#btnPanelToggle', function(e) {
        e.preventDefault();
        alternarPanel();
    });

    // Colapsado, toda la solapa es el area de click para reexpandir.
    $(document).on('click', '#panelSolapa', function(e) {
        e.preventDefault();
        alternarPanel();
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
    // Los badges se reconstruyen: cualquier tooltip o popover abierto queda
    // apuntando a un nodo que ya no existe.
    ocultarTooltip();
    cerrarPopoverDia();

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
                        <span class="leyenda-dot"></span>
                        <span class="leyenda-texto">Embarque Estimado</span>
                    </div>
                    <div class="leyenda-pill emb">
                        <span class="leyenda-dot"></span>
                        <span class="leyenda-texto">Embarque Real</span>
                    </div>
                    <div class="leyenda-pill arr-estimado">
                        <span class="leyenda-dot"></span>
                        <span class="leyenda-texto">Arribo Estimado</span>
                    </div>
                    <div class="leyenda-pill arr-real">
                        <span class="leyenda-dot"></span>
                        <span class="leyenda-texto">Arribo Real</span>
                    </div>
                    <div class="leyenda-pill desp">
                        <span class="leyenda-dot"></span>
                        <span class="leyenda-texto">Despacho Aduana</span>
                    </div>
                    <div class="leyenda-pill rec">
                        <span class="leyenda-dot"></span>
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
    const fechaStr = aISO(fecha);
    const eventos = obtenerEventosPorFecha(fechaStr);

    const maximo = MAX_EVENTOS_VISIBLES[densidad];
    let visibles = eventos;
    let ocultos = 0;

    // Si no entran todos se muestra uno menos, para hacerle lugar al chip.
    if (eventos.length > maximo) {
        visibles = eventos.slice(0, maximo - 1);
        ocultos = eventos.length - visibles.length;
    }

    let eventosHtml = visibles.map(crearBadgeEvento).join('');

    if (ocultos > 0) {
        eventosHtml += `
            <button type="button" class="evento-mas" data-fecha="${fechaStr}">
                +${ocultos} más
            </button>
        `;
    }

    return `
        <div class="calendario-dia ${otroMes ? 'otro-mes' : ''}" data-fecha="${fechaStr}">
            <div class="dia-numero">${fecha.getDate()}</div>
            <div class="dia-eventos">${eventosHtml}</div>
        </div>
    `;
}

/**
 * Badge de un evento.
 *
 * Sin icono de estado: el color ya identifica el tipo de hito y el icono
 * gastaba ancho que ahora usan el alias y los rubros. Los emojis de estado
 * siguen en el timeline del modal.
 *
 * El despacho NO se serializa en el HTML: viaja el ID de grupo y el click se
 * resuelve por delegacion buscando en el array. Serializar un objeto dentro
 * de un atributo se rompe con las comillas de los nombres de proveedor.
 */
function crearBadgeEvento(evento) {
    const representante = evento.despacho;
    const alias = aliasDeDespacho(representante);
    const contenedor = representante.CONTENEDOR || representante.ORDEN_COMPRA || 'Sin contenedor';

    const rubros = rubrosDelGrupo(evento.despachos);
    const rubrosVisibles = rubros.slice(0, 3);
    const rubrosRestantes = rubros.length - rubrosVisibles.length;

    let iconosHtml = rubrosVisibles
        .map(r => renderizarIcono(iconoDeRubro(r.rubro)))
        .join('');

    if (rubrosRestantes > 0) {
        iconosHtml += `<span class="evento-rubros-mas">+${rubrosRestantes}</span>`;
    }

    const proveedorHtml = (densidad === 'comodo')
        ? `<div class="evento-proveedor">${escaparHtml(representante.PROVEEDOR || 'Sin proveedor')}</div>`
        : '';

    return `
        <div class="evento-pill ${evento.tipo}"
             data-id-grupo="${evento.idGrupo}"
             data-tipo="${evento.tipo}"
             data-fecha="${evento.fecha}">
            <div class="evento-info">
                <div class="evento-titulo">
                    <span class="evento-alias${alias.provisorio ? ' alias-provisorio' : ''}">${escaparHtml(alias.texto)}</span>
                    <span class="evento-contenedor">${escaparHtml(contenedor)}</span>
                </div>
                ${proveedorHtml}
            </div>
            <span class="evento-rubros">${iconosHtml}</span>
        </div>
    `;
}

/**
 * Eventos de un dia, ya deduplicados por grupo de contenedor.
 *
 * Antes se emitia un evento por OC: un contenedor con 3 OCs pintaba 3 badges
 * identicos el mismo dia. Ahora se agrupa por ID_GRUPO + tipo de evento y se
 * emite uno solo, que se lleva la lista de OCs del grupo para que el tooltip,
 * el modal y la suma de rubros la usen.
 */
function obtenerEventosPorFecha(fecha) {
    const porClave = new Map();

    despachos.forEach(despacho => {
        const tipos = [];

        if (despacho.FECHA_EST_EMB === fecha) {
            tipos.push('est-emb');
        }
        if (despacho.FECHA_EMB === fecha) {
            tipos.push('emb');
        }
        if (despacho.FECHA_ARR === fecha) {
            tipos.push(parseInt(despacho.ETA_CONFIRMADA) === 1 ? 'arr-real' : 'arr-estimado');
        }
        if (despacho.FECHA_DESP_ADU === fecha) {
            tipos.push('desp');
        }
        if (despacho.FECHA_REC === fecha) {
            tipos.push('rec');
        }

        tipos.forEach(tipo => {
            if (!filtrosActivos.includes(tipo)) return;

            const clave = `${despacho.ID_GRUPO}|${tipo}`;

            if (porClave.has(clave)) {
                porClave.get(clave).despachos.push(despacho);
            } else {
                porClave.set(clave, {
                    tipo: tipo,
                    fecha: fecha,
                    idGrupo: despacho.ID_GRUPO,
                    despachos: [despacho]
                });
            }
        });
    });

    // Representante estable: la OC principal del grupo si esta entre las del
    // evento, y si no la de ID mas bajo. Sin esto el badge podria cambiar de
    // proveedor entre renders, porque el orden de 'despachos' es por FECHA_MOV.
    return Array.from(porClave.values()).map(evento => {
        evento.despachos.sort((a, b) => a.ID - b.ID);
        evento.despacho = evento.despachos.find(d => d.ID === evento.idGrupo) || evento.despachos[0];
        return evento;
    });
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

    // El badge de la solapa cuenta TODOS los arribos pendientes, no los 3
    // que se listan: colapsado, ese numero es la unica senal que queda.
    const totalPendientes = despachosConArribo.length;

    let html = `
        <div class="panel-proximos-arribos">
            <button type="button" class="panel-solapa" id="panelSolapa"
                    title="Expandir panel de próximos arribos">
                <i class="bi bi-ship"></i>
                <span class="panel-solapa-badge">${totalPendientes}</span>
                <span class="panel-solapa-texto">Próximos Arribos</span>
            </button>
            <div class="panel-contenido">
                <div class="panel-header">
                    <h3><i class="bi bi-ship"></i> Próximos Arribos</h3>
                    <button type="button" class="btn-panel-toggle" id="btnPanelToggle"
                            title="Colapsar panel">
                        <i class="bi bi-chevron-right"></i>
                    </button>
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
        return aISO(fechaEmb);
    }
    
    // Si solo hay fecha estimada de embarque, sumar 45 días
    if (despacho.FECHA_EST_EMB) {
        const fechaEstEmb = new Date(despacho.FECHA_EST_EMB + 'T00:00:00');
        fechaEstEmb.setDate(fechaEstEmb.getDate() + 45);
        return aISO(fechaEstEmb);
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
        <div class="card-proximo-arribo arribo-${tipoArribo}" data-id="${despacho.ID}">
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
        <div class="despacho-card estado-${estado} ${claseArriboReal}" data-id="${despacho.ID}">
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

/** Abre el detalle a partir del ID de encabezado (tarjetas y panel). */
function abrirDetallePorId(id) {
    const despacho = despachos.find(d => d.ID === parseInt(id, 10));
    if (despacho) {
        abrirDetalleDespacho(despacho);
    }
}

/**
 * Abre el detalle a partir del grupo de contenedor (badges del calendario).
 * Se muestra la OC principal del grupo; las demas quedan disponibles en
 * evento.despachos para el modal y el tooltip.
 */
function abrirDetallePorGrupo(idGrupo) {
    const grupo = despachosDelGrupo(parseInt(idGrupo, 10));
    if (grupo.length === 0) return;

    grupo.sort((a, b) => a.ID - b.ID);
    const principal = grupo.find(d => d.ID === parseInt(idGrupo, 10)) || grupo[0];
    abrirDetalleDespacho(principal);
}

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

    // El modal ya esta en el DOM: se puede medir y animar la barra.
    animarBarraProgresoColoreada();
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
        fechas.arribo = aISO(fechaArr);
        
        const fechaDesp = new Date(fechaArr);
        fechaDesp.setDate(fechaDesp.getDate() + 7);
        fechas.despacho = aISO(fechaDesp);
        
        const fechaRec = new Date(fechaDesp);
        fechaRec.setDate(fechaRec.getDate() + 3);
        fechas.recepcion = aISO(fechaRec);
    }
    // Si solo hay fecha estimada de embarque
    else if (despacho.FECHA_EST_EMB) {
        const fechaEstEmb = new Date(despacho.FECHA_EST_EMB + 'T00:00:00');
        
        const fechaArr = new Date(fechaEstEmb);
        fechaArr.setDate(fechaArr.getDate() + 45);
        fechas.arribo = aISO(fechaArr);
        
        const fechaDesp = new Date(fechaArr);
        fechaDesp.setDate(fechaDesp.getDate() + 7);
        fechas.despacho = aISO(fechaDesp);
        
        const fechaRec = new Date(fechaDesp);
        fechaRec.setDate(fechaRec.getDate() + 3);
        fechas.recepcion = aISO(fechaRec);
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

// La barra de progreso se anima con una llamada directa desde
// abrirDetalleDespacho(), justo despues de insertar el modal.
// Antes se disparaba con $(document).on('DOMNodeInserted', ...): un evento
// deprecado, que ademas corria varias veces por render.

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
