/**
 * Cronograma de Despachos - JavaScript
 * Gestión de vista de calendario y timeline de contenedores
 */

let despachos = [];
let vistaActual = 'calendario'; // 'calendario' o 'grilla'
let mesActual = new Date();
let despachoSeleccionado = null;
let filtrosActivos = ['est-emb', 'emb', 'arr-estimado', 'arr-real', 'desp', 'rec', 'dist']; // Filtros múltiples

// IDs de grupo seleccionados en el filtro por contenedor. Mientras haya al
// menos uno, manda sobre los filtros de estado. No se persiste: es una
// consulta puntual, no una preferencia.
let contenedoresSeleccionados = [];

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
    DIAS_DESP_REC: 2,   // recepción estimada = arribo + 9
    DIAS_ARR_DIST: 10,  // distribución estimada = arribo + 10, un día después
    DIAS_REC_DIST: 1    // con recepción REAL, al día siguiente
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

/**
 * Columna que escribe cada tipo de evento.
 *
 * 'rec' vale null a proposito: la recepción sale de Tango (STA20,
 * comprobantes 'RP') y el cronograma no la escribe, asi que su badge no se
 * puede arrastrar. El servidor lo rechaza igual.
 */
const CAMPO_POR_TIPO = {
    'est-emb': 'FECHA_EST_EMB',
    'emb': 'FECHA_EMB',
    'arr-estimado': 'FECHA_ARR',
    'arr-real': 'FECHA_ARR',
    'desp': 'FECHA_DESP_ADU',
    'dist': 'FECHA_DISTRI',
    'rec': null
};

const ETIQUETAS_CAMPOS = {
    'FECHA_EST_EMB': 'Embarque estimado',
    'FECHA_EMB': 'Embarque real',
    'FECHA_ARR': 'Arribo',
    'FECHA_DESP_ADU': 'Despacho de aduana',
    'FECHA_REC': 'Recepción',
    'FECHA_DISTRI': 'Distribución'
};

// Orden cronológico del flujo, espejo de CronogramaFechas::ORDEN_FLUJO.
const ORDEN_FLUJO = [
    'FECHA_EST_EMB', 'FECHA_EMB', 'FECHA_ARR',
    'FECHA_DESP_ADU', 'FECHA_REC', 'FECHA_DISTRI'
];

// Etiquetas por tipo de evento del calendario (distinto de los estados).
const LABELS_EVENTOS = {
    'est-emb': 'Embarque estimado',
    'emb': 'Embarque real',
    'arr-estimado': 'Arribo estimado',
    'arr-real': 'Arribo real',
    'desp': 'Despacho de aduana',
    'rec': 'Recepción',
    'dist': 'Distribución'
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

// ========== FILTRO POR CONTENEDOR ==========

/** Los grupos de contenedor, uno por ID_GRUPO, listos para el combo. */
function construirGrupos() {
    const grupos = new Map();

    despachos.forEach(d => {
        if (!grupos.has(d.ID_GRUPO)) {
            grupos.set(d.ID_GRUPO, []);
        }
        grupos.get(d.ID_GRUPO).push(d);
    });

    return Array.from(grupos, ([idGrupo, lista]) => {
        lista.sort((a, b) => a.ID - b.ID);
        const principal = lista.find(d => d.ID === idGrupo) || lista[0];
        const alias = aliasDeDespacho(principal);

        return {
            idGrupo: idGrupo,
            despachos: lista,
            alias: alias,
            contenedor: principal.CONTENEDOR || 'Sin contenedor',
            proveedor: principal.PROVEEDOR || 'Sin proveedor',
            ordenes: lista.map(d => (d.ORDEN_COMPRA || '').trim()).filter(Boolean)
        };
    }).sort((a, b) => a.contenedor.localeCompare(b.contenedor, 'es'));
}

function inicializarFiltroContenedor() {
    const $select = $('#filtroContenedor');
    if ($select.length === 0 || typeof $select.select2 !== 'function') return;

    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    $select.empty();

    construirGrupos().forEach(g => {
        const etiqueta = `${g.alias.texto} ${g.contenedor} — ${g.proveedor}`;
        const sufijo = g.despachos.length > 1 ? ` [${g.despachos.length} OCs]` : '';

        $select.append(
            $('<option>', {
                value: g.idGrupo,
                text: etiqueta + sufijo,
                // El buscador tiene que encontrar por contenedor, proveedor y
                // numero de OC: los tres van concatenados en el data.
                'data-busqueda': `${g.contenedor} ${g.proveedor} ${g.alias.texto} ${g.ordenes.join(' ')}`
            })
        );
    });

    $select.select2({
        placeholder: $select.data('placeholder'),
        allowClear: true,
        width: '260px',
        matcher: function (params, data) {
            if ($.trim(params.term) === '') return data;
            if (typeof data.text === 'undefined') return null;

            const termino = params.term.toUpperCase();
            const busqueda = ($(data.element).data('busqueda') || data.text).toString().toUpperCase();

            return busqueda.indexOf(termino) > -1 ? data : null;
        }
    });

    $select.on('change', function () {
        contenedoresSeleccionados = ($(this).val() || []).map(v => parseInt(v, 10));
        aplicarEstadoFiltroContenedor();
        renderizarVista();
    });
}

function aplicarEstadoFiltroContenedor() {
    const hay = contenedoresSeleccionados.length > 0;
    $('#avisoFiltrosSuspendidos').prop('hidden', !hay);
    $('#btnFiltros').toggleClass('filtros-suspendidos', hay);
}

function limpiarFiltroContenedor() {
    contenedoresSeleccionados = [];
    $('#filtroContenedor').val(null).trigger('change.select2');
    aplicarEstadoFiltroContenedor();
    renderizarVista();
}

// ========== TIRA DE RECORRIDO ==========
// Los hitos de un contenedor caen en meses distintos: el 78% de los grupos
// reparte sus fechas en 3 o mas meses. Sin esta tira habria que navegar el
// calendario a ciegas para reconstruir un recorrido.
// El embarque ocupa UN solo hito, no dos. Cuando hay fecha real, la estimada
// deja de ser un hito propio y baja a linea de comparacion en gris: ya
// cumplio su funcion y como hito al mismo nivel solo agrega ruido.
const HITOS_RECORRIDO = [
    { tipo: 'emb',          campo: 'FECHA_EMB',      label: 'Embarque' },
    { tipo: 'arr-estimado', campo: 'FECHA_ARR',      label: 'Arribo' },
    { tipo: 'desp',         campo: 'FECHA_DESP_ADU', label: 'Despacho' },
    // Recepcion es el ingreso al deposito central; distribucion es la salida
    // de ahi a los locales, o sea que va despues.
    { tipo: 'rec',          campo: 'FECHA_REC',      label: 'Recepción' },
    { tipo: 'dist',         campo: 'FECHA_DISTRI',   label: 'Distribución' }
];

/** Diferencia en dias corridos entre dos fechas 'YYYY-MM-DD'. */
function diasEntre(desde, hasta) {
    if (!desde || !hasta) return null;
    const a = new Date(desde + 'T00:00:00');
    const b = new Date(hasta + 'T00:00:00');
    return Math.round((b - a) / (1000 * 60 * 60 * 24));
}

/**
 * Resuelve que mostrar en un hito: la fecha real si existe, y si no la
 * estimada. Para el embarque devuelve ademas la estimada como comparacion.
 */
function datosHitoRecorrido(despacho, hito) {
    if (hito.campo === 'FECHA_EMB') {
        const real = despacho.FECHA_EMB;
        const estimada = despacho.FECHA_EST_EMB;

        if (real) {
            return {
                fecha: real,
                estimado: false,
                label: 'Embarque',
                comparacion: (estimada && estimada !== real) ? estimada : null
            };
        }
        return { fecha: estimada, estimado: true, label: 'Est. embarque', comparacion: null };
    }

    const real = despacho[hito.campo];
    if (real) {
        return { fecha: real, estimado: false, label: hito.label, comparacion: null };
    }

    return {
        fecha: estimarHitoRecorrido(despacho, hito.campo),
        estimado: true,
        label: hito.label,
        comparacion: null
    };
}

function renderizarTiraRecorrido() {
    $('#tiraRecorrido').remove();
    if (contenedoresSeleccionados.length === 0) return;

    const grupos = construirGrupos()
        .filter(g => contenedoresSeleccionados.includes(g.idGrupo));

    if (grupos.length === 0) return;

    const filas = grupos.map(g => {
        const principal = g.despachos.find(d => d.ID === g.idGrupo) || g.despachos[0];

        const hitos = HITOS_RECORRIDO.map(h => {
            const datos = datosHitoRecorrido(principal, h);
            let tipo = h.tipo;

            // El arribo cambia de color segun este confirmado o no.
            if (h.campo === 'FECHA_ARR' && parseInt(principal.ETA_CONFIRMADA) === 1) {
                tipo = 'arr-real';
            }
            // Sin fecha real de embarque, el hito toma el color de estimado.
            if (h.campo === 'FECHA_EMB' && datos.estimado) {
                tipo = 'est-emb';
            }

            if (!datos.fecha) {
                return `<div class="hito hito-vacio"><span class="hito-dot"></span>
                            <span class="hito-label">${h.label}</span>
                            <span class="hito-fecha">—</span></div>`;
            }

            // Linea gris de comparacion contra lo que se habia estimado, con
            // el desvio en dias. Es lo unico que la estimada sigue aportando
            // una vez que hay fecha real.
            let comparacionHtml = '';
            if (datos.comparacion) {
                const desvio = diasEntre(datos.comparacion, datos.fecha);
                const signo = desvio > 0 ? '+' : '';
                const claseDesvio = desvio > 0 ? ' desvio-tarde' : (desvio < 0 ? ' desvio-temprano' : '');
                comparacionHtml = `
                    <span class="hito-comparacion${claseDesvio}">
                        est. ${formatearFecha(datos.comparacion)}
                        ${desvio !== 0 ? `<b>${signo}${desvio} d</b>` : ''}
                    </span>
                `;
            }

            return `
                <button type="button" class="hito hito-${tipo}${datos.estimado ? ' hito-estimado' : ''}"
                        data-fecha="${datos.fecha}"
                        title="Ir a ${formatearFecha(datos.fecha)}${datos.estimado ? ' (estimado)' : ''}">
                    <span class="hito-dot"></span>
                    <span class="hito-label">${datos.label}</span>
                    <span class="hito-fecha">${formatearFecha(datos.fecha)}</span>
                    ${comparacionHtml}
                </button>
            `;
        }).join('<span class="hito-union"></span>');

        return `
            <div class="recorrido-fila">
                <div class="recorrido-titulo">
                    <span class="recorrido-alias${g.alias.provisorio ? ' alias-provisorio' : ''}">${escaparHtml(g.alias.texto)}</span>
                    <span class="recorrido-contenedor">${escaparHtml(g.contenedor)}</span>
                    <span class="recorrido-proveedor">${escaparHtml(g.proveedor)}</span>
                    ${g.despachos.length > 1
                        ? `<span class="recorrido-ocs">${g.despachos.length} OCs</span>`
                        : ''}
                </div>
                <div class="recorrido-hitos">${hitos}</div>
            </div>
        `;
    }).join('');

    const html = `
        <div class="tira-recorrido" id="tiraRecorrido">
            <div class="recorrido-header">
                <span><i class="bi bi-signpost-split"></i>
                      Recorrido de ${grupos.length} contenedor${grupos.length > 1 ? 'es' : ''}</span>
                <button type="button" class="btn-limpiar-contenedores" id="btnLimpiarContenedores">
                    <i class="bi bi-x-circle"></i> Limpiar selección
                </button>
            </div>
            ${filas}
        </div>
    `;

    $('#contenidoPrincipal').before(html);
}

/** Fecha estimada de un hito que todavia no tiene valor real. */
function estimarHitoRecorrido(despacho, campo) {
    const estimadas = calcularFechasEstimadas(despacho);

    if (campo === 'FECHA_ARR')      return estimadas.arribo;
    if (campo === 'FECHA_DESP_ADU') return estimadas.despacho;
    if (campo === 'FECHA_DISTRI')   return estimadas.distribucion;
    if (campo === 'FECHA_REC')      return estimadas.recepcion;
    return null;
}

// ========== DRAG & DROP ==========
// API nativa HTML5, sin libreria. El estado del arrastre vive aca porque
// dataTransfer solo es legible en dragstart y drop, no en dragover.
let arrastreActual = null;

function iniciarArrastre(elemento, evento) {
    const $pill = $(elemento);
    const tipo = $pill.data('tipo');
    const campo = CAMPO_POR_TIPO[tipo];

    if (!campo) return false;

    arrastreActual = {
        idGrupo: parseInt($pill.data('id-grupo'), 10),
        tipo: tipo,
        campo: campo,
        fechaOrigen: $pill.data('fecha')
    };

    // Se usa dataTransfer igual, porque sin setData Firefox no arranca el
    // arrastre.
    if (evento && evento.dataTransfer) {
        evento.dataTransfer.effectAllowed = 'move';
        evento.dataTransfer.setData('text/plain', String(arrastreActual.idGrupo));
    }

    $pill.addClass('arrastrando');
    ocultarTooltip();
    return true;
}

// Algunos navegadores disparan un click despues del drop. Sin esta guarda se
// abriria el detalle encima del modal de confirmación.
let recienArrastrado = false;

function terminarArrastre() {
    $('.evento-pill').removeClass('arrastrando');
    $('.calendario-dia').removeClass('destino-activo');
    arrastreActual = null;
    recienArrastrado = true;
    setTimeout(() => { recienArrastrado = false; }, 100);
}

/**
 * Coherencia calculada en el front, solo para mostrar el impacto antes de
 * confirmar. La validacion que manda es la del servidor.
 */
function evaluarCoherencia(campo, fechaNueva, despacho) {
    const errores = [];
    const advertencias = [];
    const posicion = ORDEN_FLUJO.indexOf(campo);

    ORDEN_FLUJO.forEach((otro, i) => {
        if (otro === campo || !despacho[otro]) return;

        const valorOtro = despacho[otro];
        const anterior = i < posicion;
        const invierte = anterior ? (valorOtro > fechaNueva) : (valorOtro < fechaNueva);
        if (!invierte) return;

        // Que cuenta como fecha en firme depende de la fila.
        let esReal;
        if (otro === 'FECHA_EST_EMB') esReal = false;
        else if (otro === 'FECHA_ARR') esReal = parseInt(despacho.ETA_CONFIRMADA) === 1;
        else if (otro === 'FECHA_DISTRI') esReal = despacho.DIST_ORIGEN === 'C';
        else esReal = true;

        const texto = `${ETIQUETAS_CAMPOS[campo]} quedaría ${anterior ? 'antes' : 'después'} de ` +
                      `${ETIQUETAS_CAMPOS[otro]} (${formatearFecha(valorOtro)})`;

        if (esReal) {
            errores.push(texto + ', que es una fecha en firme');
        } else {
            advertencias.push(texto + ', que es una estimación');
        }
    });

    return { errores, advertencias };
}

/** Que fechas derivadas se van a recalcular, para mostrarlo antes de aceptar. */
function calcularImpactoCascada(campo, fechaNueva, grupo) {
    if (campo !== 'FECHA_ARR') return [];

    return grupo.map(d => {
        if (d.DIST_ORIGEN !== 'A') {
            return {
                oc: d.ORDEN_COMPRA,
                intacta: true,
                motivo: d.DIST_ORIGEN === 'C'
                    ? 'está confirmada'
                    : 'fue movida a mano'
            };
        }
        // Misma regla que CronogramaFechas::calcularDistribucion.
        const nueva = d.FECHA_REC
            ? sumarDias(d.FECHA_REC, parametrosDias.DIAS_REC_DIST)
            : sumarDias(fechaNueva, parametrosDias.DIAS_ARR_DIST);

        return { oc: d.ORDEN_COMPRA, intacta: false, anterior: d.FECHA_DISTRI, nueva: nueva };
    });
}

function abrirModalConfirmacion(campo, fechaOrigen, fechaDestino, idGrupo) {
    const grupo = despachosDelGrupo(idGrupo);
    if (grupo.length === 0) return;

    grupo.sort((a, b) => a.ID - b.ID);
    const principal = grupo.find(d => d.ID === idGrupo) || grupo[0];
    const alias = aliasDeDespacho(principal);

    const dias = diasEntre(fechaOrigen, fechaDestino);
    const signo = dias > 0 ? '+' : '';

    const { errores, advertencias } = evaluarCoherencia(campo, fechaDestino, principal);

    // Espejo de CronogramaFechas::motivoObservacionObligatoria.
    let obligatoria = null;
    if (campo === 'FECHA_ARR' && parseInt(principal.ETA_CONFIRMADA) === 1) {
        obligatoria = 'El arribo está confirmado (ETA confirmada)';
    } else if (campo === 'FECHA_DISTRI' && principal.DIST_ORIGEN === 'C') {
        obligatoria = 'La fecha de distribución está confirmada';
    }

    const cascada = calcularImpactoCascada(campo, fechaDestino, grupo);

    const cascadaHtml = cascada.length === 0 ? '' : `
        <div class="conf-bloque">
            <div class="conf-bloque-titulo">Impacto en cascada</div>
            ${cascada.map(c => c.intacta
                ? `<div class="conf-cascada intacta">
                       <i class="bi bi-lock"></i> ${escaparHtml(c.oc.trim())}:
                       la distribución queda intacta porque ${c.motivo}
                   </div>`
                : `<div class="conf-cascada">
                       <i class="bi bi-arrow-return-right"></i> ${escaparHtml(c.oc.trim())}:
                       distribución ${c.anterior ? formatearFecha(c.anterior) : '(sin fecha)'}
                       → <b>${formatearFecha(c.nueva)}</b>
                   </div>`
            ).join('')}
        </div>`;

    const ocsHtml = grupo.length <= 1 ? '' : `
        <div class="conf-bloque">
            <div class="conf-bloque-titulo">
                ${grupo.length} órdenes de compra afectadas
            </div>
            <div class="conf-nota">El cambio impacta a todo el grupo del contenedor.</div>
            ${grupo.map(d => `<div class="conf-oc">${escaparHtml((d.ORDEN_COMPRA || '').trim())}</div>`).join('')}
        </div>`;

    const erroresHtml = errores.length === 0 ? '' : `
        <div class="conf-alerta conf-error">
            <div class="conf-alerta-titulo"><i class="bi bi-x-octagon"></i> El servidor va a rechazar este movimiento</div>
            ${errores.map(e => `<div>${escaparHtml(e)}</div>`).join('')}
        </div>`;

    const avisosHtml = advertencias.length === 0 ? '' : `
        <div class="conf-alerta conf-aviso">
            <div class="conf-alerta-titulo"><i class="bi bi-exclamation-triangle"></i> Advertencia</div>
            ${advertencias.map(a => `<div>${escaparHtml(a)}</div>`).join('')}
        </div>`;

    const html = `
        <div class="modal-overlay modal-confirmacion" id="modalConfirmacionFecha">
            <div class="modal-content conf-modal">
                <div class="modal-header">
                    <div class="modal-title-group">
                        <h2>Mover ${escaparHtml(ETIQUETAS_CAMPOS[campo])}</h2>
                        <p>
                            <span class="conf-alias${alias.provisorio ? ' alias-provisorio' : ''}">${escaparHtml(alias.texto)}</span>
                            <strong>${escaparHtml(principal.CONTENEDOR || 'Sin contenedor')}</strong>
                            · ${escaparHtml(principal.PROVEEDOR || 'Sin proveedor')}
                        </p>
                    </div>
                    <button class="btn-close-modal" data-accion="cancelar">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="conf-movimiento">
                        <div class="conf-fecha">
                            <span class="conf-fecha-label">Desde</span>
                            <span class="conf-fecha-valor">${formatearFecha(fechaOrigen)}</span>
                        </div>
                        <div class="conf-flecha">
                            <i class="bi bi-arrow-right"></i>
                            <span class="conf-diferencia ${dias > 0 ? 'tarde' : (dias < 0 ? 'temprano' : '')}">
                                ${dias === 0 ? 'mismo día' : `${signo}${dias} ${Math.abs(dias) === 1 ? 'día' : 'días'}`}
                            </span>
                        </div>
                        <div class="conf-fecha">
                            <span class="conf-fecha-label">Hasta</span>
                            <span class="conf-fecha-valor destino">${formatearFecha(fechaDestino)}</span>
                        </div>
                    </div>

                    ${erroresHtml}
                    ${avisosHtml}
                    ${cascadaHtml}
                    ${ocsHtml}

                    <div class="conf-bloque">
                        <label class="conf-label" for="confMotivo">Motivo</label>
                        <select class="conf-input" id="confMotivo">
                            <option value="">Sin especificar</option>
                            ${motivosFecha.map(m =>
                                `<option value="${escaparHtml(m.codigo)}">${escaparHtml(m.label)}</option>`
                            ).join('')}
                        </select>
                    </div>

                    <div class="conf-bloque">
                        <label class="conf-label" for="confObservacion">
                            Observación
                            ${obligatoria
                                ? '<span class="conf-obligatorio">obligatoria</span>'
                                : '<span class="conf-opcional">opcional</span>'}
                        </label>
                        ${obligatoria
                            ? `<div class="conf-nota conf-nota-destacada">${escaparHtml(obligatoria)}</div>`
                            : ''}
                        <textarea class="conf-input" id="confObservacion" rows="3" maxlength="500"
                                  placeholder="${obligatoria ? 'Explicá por qué se mueve esta fecha…' : 'Opcional'}"></textarea>
                        <div class="conf-contador"><span id="confContador">0</span>/500</div>
                    </div>

                    <div class="conf-error-servidor" id="confErrorServidor" hidden></div>
                </div>

                <div class="conf-acciones">
                    <button class="btn-conf-cancelar" data-accion="cancelar">Cancelar</button>
                    <button class="btn-conf-aceptar" id="btnConfirmarFecha"
                            data-campo="${campo}"
                            data-id-grupo="${idGrupo}"
                            data-id="${principal.ID}"
                            data-fecha="${fechaDestino}"
                            data-obligatoria="${obligatoria ? '1' : '0'}">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    `;

    $('body').append(html);
    $('#confObservacion').trigger('focus');
}

function cerrarModalConfirmacion() {
    $('#modalConfirmacionFecha').remove();
}

function confirmarMovimientoFecha() {
    const $btn = $('#btnConfirmarFecha');
    const obligatoria = $btn.data('obligatoria') === 1 || $btn.data('obligatoria') === '1';
    const observacion = $('#confObservacion').val().trim();
    const motivo = $('#confMotivo').val();
    const $error = $('#confErrorServidor');

    if (obligatoria && observacion === '') {
        $error.prop('hidden', false).text('La observación es obligatoria para este movimiento.');
        $('#confObservacion').trigger('focus');
        return;
    }

    $btn.prop('disabled', true).text('Guardando…');
    $error.prop('hidden', true);

    $.ajax({
        url: 'controller/actualizarFechaCronograma.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            idEncabezado: $btn.data('id'),
            campo: $btn.data('campo'),
            fechaNueva: $btn.data('fecha'),
            motivo: motivo,
            observacion: observacion
        }),
        dataType: 'json',
        success: function (respuesta) {
            if (!respuesta.success) {
                mostrarErrorConfirmacion(respuesta);
                return;
            }
            aplicarDespachosActualizados(respuesta.data);
            cerrarModalConfirmacion();
            // Tambien se cierra el modal de detalle si estaba abierto: sus
            // fechas quedaron viejas.
            cerrarModal();
            renderizarVista();

            // Sin este aviso el movimiento se guardaba en silencio: el modal
            // se cerraba y no quedaba ninguna senal de que habia pasado algo,
            // ni de donde mirar el registro.
            mostrarAvisoMovimiento(respuesta, $btn.data('campo'), $btn.data('id'));
        },
        error: function (xhr) {
            mostrarErrorConfirmacion(xhr.responseJSON || {
                message: 'No se pudo guardar el cambio.'
            });
        },
        complete: function () {
            $btn.prop('disabled', false).text('Confirmar');
        }
    });
}

function mostrarErrorConfirmacion(respuesta) {
    const detalle = (respuesta.errores || []).map(e => `<div>· ${escaparHtml(e)}</div>`).join('');
    $('#confErrorServidor')
        .prop('hidden', false)
        .html(`<strong>${escaparHtml(respuesta.message || 'Error')}</strong>${detalle}`);
}

/**
 * Aviso flotante tras guardar un movimiento, con acceso directo al historial.
 * Se cierra solo a los 8 segundos.
 */
function mostrarAvisoMovimiento(respuesta, campo, idEncabezado) {
    $('.aviso-movimiento').remove();

    const recalculadas = Object.keys(respuesta.recalculadas || {}).length;
    const avisos = (respuesta.advertencias || []).length;

    const detalle = [
        `${respuesta.ocsAfectadas} ${respuesta.ocsAfectadas === 1 ? 'orden de compra' : 'órdenes de compra'}`,
        recalculadas > 0
            ? `${recalculadas} distribución${recalculadas === 1 ? '' : 'es'} recalculada${recalculadas === 1 ? '' : 's'}`
            : null
    ].filter(Boolean).join(' · ');

    const $aviso = $(`
        <div class="aviso-movimiento">
            <div class="aviso-icono"><i class="bi bi-check-circle-fill"></i></div>
            <div class="aviso-cuerpo">
                <div class="aviso-titulo">${escaparHtml(ETIQUETAS_CAMPOS[campo] || campo)} actualizado</div>
                <div class="aviso-detalle">${escaparHtml(detalle)}</div>
                ${avisos > 0
                    ? `<div class="aviso-advertencia"><i class="bi bi-exclamation-triangle"></i>
                           ${escaparHtml(respuesta.advertencias[0])}</div>`
                    : ''}
                <button type="button" class="aviso-link" data-id="${idEncabezado}">
                    Ver historial de cambios
                </button>
            </div>
            <button type="button" class="aviso-cerrar" aria-label="Cerrar">&times;</button>
        </div>
    `).appendTo('body');

    setTimeout(() => $aviso.addClass('visible'), 10);
    setTimeout(() => {
        $aviso.removeClass('visible');
        setTimeout(() => $aviso.remove(), 300);
    }, 8000);
}

/** Reemplaza en el array local las OCs que devolvio el servidor. */
function aplicarDespachosActualizados(actualizados) {
    if (!Array.isArray(actualizados)) return;

    actualizados.forEach(nuevo => {
        const i = despachos.findIndex(d => d.ID === nuevo.ID);
        if (i >= 0) {
            despachos[i] = nuevo;
        }
    });
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
    
    // Cada hito de la tira navega el calendario al mes de esa fecha, que es
    // lo que resuelve que los hitos de un contenedor caigan en meses
    // distintos.
    $(document).on('click', '.hito[data-fecha]', function() {
        const fecha = new Date($(this).data('fecha') + 'T00:00:00');
        mesActual = new Date(fecha.getFullYear(), fecha.getMonth(), 1);

        // Si estaba en Tarjetas, el salto de mes solo tiene sentido en el
        // calendario.
        if (vistaActual !== 'calendario') {
            vistaActual = 'calendario';
            $('.btn-view').removeClass('active').filter('[data-view="calendario"]').addClass('active');
        }

        actualizarMesDisplay();
        renderizarVista();
    });

    $(document).on('click', '#btnLimpiarContenedores', limpiarFiltroContenedor);

    // ---------- Drag & drop de fechas ----------
    $(document).on('dragstart', '.evento-pill', function (e) {
        if (!iniciarArrastre(this, e.originalEvent)) {
            e.preventDefault();
        }
    });

    $(document).on('dragend', '.evento-pill', terminarArrastre);

    // Sin preventDefault en dragover el navegador no admite el drop.
    $(document).on('dragover', '.calendario-dia', function (e) {
        if (!arrastreActual) return;
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';
        $(this).addClass('destino-activo');
    });

    $(document).on('dragleave', '.calendario-dia', function () {
        $(this).removeClass('destino-activo');
    });

    $(document).on('drop', '.calendario-dia', function (e) {
        e.preventDefault();
        if (!arrastreActual) return;

        const destino = $(this).data('fecha');
        const arrastre = arrastreActual;
        terminarArrastre();

        // Soltar en el mismo dia no es un movimiento.
        if (!destino || destino === arrastre.fechaOrigen) return;

        abrirModalConfirmacion(arrastre.campo, arrastre.fechaOrigen, destino, arrastre.idGrupo);
    });

    $(document).on('click', '.aviso-cerrar', function () {
        $(this).closest('.aviso-movimiento').remove();
    });

    // Abre el detalle y baja directo al historial.
    $(document).on('click', '.aviso-link', function () {
        const id = $(this).data('id');
        $('.aviso-movimiento').remove();
        abrirDetallePorId(id);
        irAlHistorial();
    });

    // Atajo desde la cabecera del modal de detalle.
    $(document).on('click', '.btn-ir-historial', function () {
        irAlHistorial();
    });

    $(document).on('click', '.historial-toggle', function () {
        const $btn = $(this);
        const expandido = $btn.data('expandido') === 1 || $btn.data('expandido') === '1';
        const ocultas = $('#historialFechas .historial-fila').length - 5;

        $('#historialFechas .historial-fila').slice(5).toggleClass('historial-oculto', expandido);
        $btn.data('expandido', expandido ? '0' : '1')
            .text(expandido ? `Ver las ${ocultas} entradas restantes` : 'Ver menos');
    });

    // Editor de fechas del modal: mismo endpoint y mismo modal de motivo y
    // observación que el arrastre, para que nada esquive el historial.
    $(document).on('change', '.editor-input', function () {
        const $input = $(this);
        const nueva = $input.val();
        const original = $input.data('original');

        if (!nueva || nueva === original) return;

        // Se revierte el input: quien decide es el modal de confirmación, y
        // si se cancela el valor visible tiene que volver al anterior.
        $input.val(original);

        abrirModalConfirmacion(
            $input.data('campo'),
            original || nueva,
            nueva,
            parseInt($input.data('id-grupo'), 10)
        );
    });

    // Cancelar revierte solo: no se toco nada hasta confirmar.
    $(document).on('click', '#modalConfirmacionFecha [data-accion="cancelar"]', cerrarModalConfirmacion);

    $(document).on('click', '#modalConfirmacionFecha', function (e) {
        if (e.target === this) cerrarModalConfirmacion();
    });

    $(document).on('click', '#btnConfirmarFecha', confirmarMovimientoFecha);

    $(document).on('input', '#confObservacion', function () {
        $('#confContador').text($(this).val().length);
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') cerrarModalConfirmacion();
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
        if (recienArrastrado) return;
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
    
    // Cerrar modal. El de confirmación se excluye: tiene su propio cierre,
    // y si no cerraria tambien el modal de detalle que puede estar debajo.
    $(document).on('click', '.modal-overlay:not(.modal-confirmacion)', function(e) {
        if (e.target === this) {
            cerrarModal();
        }
    });

    $(document).on('click', '.modal-overlay:not(.modal-confirmacion) .btn-close-modal', cerrarModal);
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
                // Las opciones del combo salen de los despachos ya traidos,
                // asi que se arma recien aca.
                inicializarFiltroContenedor();
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
    } else {
        renderizarGrilla();
    }

    // La tira va entre el header y el contenido, asi que se pinta despues de
    // que #contenidoPrincipal ya tenga su HTML.
    renderizarTiraRecorrido();

    // El panel queda global a proposito: es "que se viene", no una vista
    // filtrada. No lo toca la seleccion de contenedores.
    renderizarPanelProximosArribos();
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
                    <div class="leyenda-pill dist">
                        <span class="leyenda-dot"></span>
                        <span class="leyenda-texto">Distribución</span>
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

    // Una distribucion automatica (A) o movida a mano (M) sigue siendo una
    // proyeccion; solo la confirmada (C) es una fecha en firme.
    const claseDist = (evento.tipo === 'dist' && representante.DIST_ORIGEN !== 'C')
        ? ' dist-estimada'
        : '';

    // La recepción viene de Tango: su badge no se arrastra.
    const arrastrable = CAMPO_POR_TIPO[evento.tipo] !== null;

    return `
        <div class="evento-pill ${evento.tipo}${claseDist}${arrastrable ? '' : ' no-arrastrable'}"
             ${arrastrable ? 'draggable="true"' : ''}
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
    const hayFiltroContenedor = contenedoresSeleccionados.length > 0;

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
        if (despacho.FECHA_DISTRI === fecha) {
            tipos.push('dist');
        }

        // Con contenedores seleccionados, el filtro por contenedor manda y
        // los filtros de estado quedan suspendidos: la idea es ver el
        // recorrido completo de ese contenedor, no un recorte por estado.
        if (hayFiltroContenedor) {
            if (!contenedoresSeleccionados.includes(despacho.ID_GRUPO)) return;
        }

        tipos.forEach(tipo => {
            if (!hayFiltroContenedor && !filtrosActivos.includes(tipo)) return;

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

    // Offset parametrizable (DIAS_EMB_ARR), antes 45 hardcodeado.
    const base = despacho.FECHA_EMB || despacho.FECHA_EST_EMB;
    return base ? sumarDias(base, parametrosDias.DIAS_EMB_ARR) : null;
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

    // El filtro por contenedor tambien aplica aca: seleccionar dos
    // contenedores y seguir viendo las 128 tarjetas seria incoherente.
    const visibles = contenedoresSeleccionados.length > 0
        ? despachos.filter(d => contenedoresSeleccionados.includes(d.ID_GRUPO))
        : despachos;

    visibles.forEach(despacho => {
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
                    ${crearEditorFechas(despacho)}
                    <div class="historial-fechas" id="historialFechas">
                        <div class="historial-titulo">
                            <i class="bi bi-clock-history"></i> Historial de cambios de fecha
                        </div>
                        <div class="historial-cargando">Cargando…</div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modalHtml);

    // El modal ya esta en el DOM: se puede medir y animar la barra.
    animarBarraProgresoColoreada();

    // El historial se pide aparte: no hace falta para pintar el modal y en la
    // mayoria de los contenedores va a venir vacio.
    cargarHistorialFechas(despacho.ID);
}

// ========== HISTORIAL DE CAMBIOS ==========

/**
 * Lleva la vista al historial dentro del modal.
 * Vive al fondo, despues del timeline y del editor, asi que sin esto hay que
 * saber que existe y scrollear hasta abajo para encontrarlo.
 */
function irAlHistorial() {
    // Espera a que el historial haya llegado del servidor.
    const intentar = (restantes) => {
        const $h = $('#historialFechas');
        const $modal = $('.modal-overlay:not(.modal-confirmacion) .modal-content');

        if ($h.length && $modal.length && $h.find('.historial-lista, .historial-vacio').length) {
            const destino = $h.position().top + $modal.scrollTop() - 12;
            $modal.animate({ scrollTop: destino }, 300);
            $h.addClass('historial-resaltado');
            setTimeout(() => $h.removeClass('historial-resaltado'), 1600);
            return;
        }
        if (restantes > 0) setTimeout(() => intentar(restantes - 1), 100);
    };
    intentar(20);
}

function cargarHistorialFechas(idEncabezado) {
    $.ajax({
        url: 'controller/obtenerHistorialFechas.php',
        method: 'GET',
        data: { idEncabezado: idEncabezado },
        dataType: 'json',
        success: function (respuesta) {
            if (respuesta.success) {
                renderizarHistorialFechas(respuesta.data);
            } else {
                $('#historialFechas .historial-cargando')
                    .text('No se pudo cargar el historial.');
            }
        },
        error: function () {
            $('#historialFechas .historial-cargando')
                .text('No se pudo cargar el historial.');
        }
    });
}

function renderizarHistorialFechas(entradas) {
    const $cont = $('#historialFechas');
    if ($cont.length === 0) return;

    $cont.find('.historial-cargando, .historial-lista, .historial-vacio, .historial-toggle').remove();

    if (!entradas || entradas.length === 0) {
        $cont.append(`
            <div class="historial-vacio">
                Todavía no se registraron cambios de fecha para este contenedor.
            </div>
        `);
        return;
    }

    // Contador en el titulo y atajo en la cabecera: el historial vive al
    // fondo del modal y sin estas dos senales no se sabe que hay algo abajo.
    $cont.find('.historial-contador').remove();
    $cont.find('.historial-titulo').append(
        `<span class="historial-contador">${entradas.length}</span>`
    );

    $('.modal-title-group .btn-ir-historial').remove();
    $('.modal-overlay:not(.modal-confirmacion) .modal-title-group').append(`
        <button type="button" class="btn-ir-historial">
            <i class="bi bi-clock-history"></i>
            ${entradas.length} ${entradas.length === 1 ? 'cambio registrado' : 'cambios registrados'}
        </button>
    `);

    // Colapsado por defecto a partir de 5 entradas, para que el historial no
    // empuje el resto del modal fuera de la vista.
    const colapsar = entradas.length > 5;

    const filas = entradas.map((e, i) => `
        <div class="historial-fila${colapsar && i >= 5 ? ' historial-oculto' : ''}">
            <div class="historial-cabecera">
                <span class="historial-campo">${escaparHtml(e.campoLabel)}</span>
                <span class="historial-cambio">
                    ${e.valorAnterior ? formatearFecha(e.valorAnterior) : '—'}
                    <i class="bi bi-arrow-right"></i>
                    <b>${e.valorNuevo ? formatearFecha(e.valorNuevo) : '—'}</b>
                </span>
                <span class="historial-fecha">${escaparHtml(e.fechaAlta || '')}</span>
            </div>
            <div class="historial-meta">
                <span class="historial-origen origen-${escaparHtml(e.origen.toLowerCase())}">
                    ${e.origen === 'CRONOGRAMA' ? 'Cronograma' : 'Gestión de despachos'}
                </span>
                ${e.ordenCompra ? `<span class="historial-oc">${escaparHtml(e.ordenCompra)}</span>` : ''}
                ${e.motivoLabel ? `<span class="historial-motivo">${escaparHtml(e.motivoLabel)}</span>` : ''}
                <span class="historial-usuario">${e.usuario ? escaparHtml(e.usuario) : 'sin usuario'}</span>
            </div>
            ${e.observacion
                ? `<div class="historial-observacion">${escaparHtml(e.observacion)}</div>`
                : ''}
        </div>
    `).join('');

    $cont.append(`<div class="historial-lista">${filas}</div>`);

    if (colapsar) {
        $cont.append(`
            <button type="button" class="historial-toggle" data-expandido="0">
                Ver las ${entradas.length - 5} entradas restantes
            </button>
        `);
    }
}

/**
 * Editor de fechas del modal de detalle.
 *
 * Es el camino para los movimientos entre meses, que el arrastre no cubre
 * porque solo funciona dentro del mes visible. Usa el MISMO endpoint y el
 * mismo modal de motivo y observación que el drag & drop, asi que ninguna
 * edición esquiva el historial.
 */
function crearEditorFechas(despacho) {
    const editables = [
        { campo: 'FECHA_EST_EMB',  label: 'Embarque estimado' },
        { campo: 'FECHA_EMB',      label: 'Embarque real' },
        { campo: 'FECHA_ARR',      label: 'Arribo' },
        { campo: 'FECHA_DESP_ADU', label: 'Despacho de aduana' },
        { campo: 'FECHA_DISTRI',   label: 'Distribución' }
    ];

    const filas = editables.map(e => {
        const valor = despacho[e.campo] || '';

        let nota = '';
        if (e.campo === 'FECHA_ARR' && parseInt(despacho.ETA_CONFIRMADA) === 1) {
            nota = '<span class="editor-nota">confirmada · exige observación</span>';
        } else if (e.campo === 'FECHA_DISTRI' && despacho.DIST_ORIGEN === 'C') {
            nota = '<span class="editor-nota">confirmada · exige observación</span>';
        } else if (e.campo === 'FECHA_DISTRI' && despacho.DIST_ORIGEN === 'A') {
            nota = '<span class="editor-nota tenue">automática</span>';
        }

        return `
            <div class="editor-fila">
                <label class="editor-label">${e.label}${nota}</label>
                <input type="date" class="editor-input" value="${valor}"
                       data-campo="${e.campo}" data-original="${valor}"
                       data-id-grupo="${despacho.ID_GRUPO}">
            </div>
        `;
    }).join('');

    return `
        <div class="editor-fechas">
            <div class="editor-titulo">
                <i class="bi bi-pencil-square"></i> Editar fechas
            </div>
            <div class="editor-nota-general">
                Cambiar una fecha impacta a todas las OCs del contenedor y queda
                registrada en el historial. La recepción no se edita: viene de Tango.
            </div>
            <div class="editor-grid">${filas}</div>
        </div>
    `;
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
            // Sin "(estimado)": el circulo punteado, la italica y el ~ de la
            // fecha ya lo dicen. Repetirlo desbordaba la columna.
            label: tieneEmbarqueReal ? 'Embarcado' : 'Embarque', 
            icono: '🚢', 
            fecha: despacho.FECHA_EMB || despacho.FECHA_EST_EMB,
            fechaEstimada: despacho.FECHA_EST_EMB,
            completado: !!despacho.FECHA_EMB, 
            estimado: !tieneEmbarqueReal,
            demorado: verificarDemora(despacho.FECHA_EMB, despacho.FECHA_EST_EMB)
        },
        { 
            key: 'arribado', 
            label: etaConfirmada ? 'Arribo real' : 'Arribo', 
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
            label: tieneEmbarqueReal ? 'Despachado' : 'Despacho', 
            icono: '🚚', 
            fecha: despacho.FECHA_DESP_ADU || fechasEstimadas.despacho,
            fechaEstimada: fechasEstimadas.despacho,
            completado: !!despacho.FECHA_DESP_ADU && tieneEmbarqueReal, 
            estimado: !tieneEmbarqueReal || !despacho.FECHA_DESP_ADU,
            demorado: verificarDemora(despacho.FECHA_DESP_ADU, fechasEstimadas.despacho)
        },
        {
            key: 'recibido',
            label: tieneEmbarqueReal ? 'Recibido' : 'Recepción',
            icono: '📦',
            fecha: despacho.FECHA_REC || fechasEstimadas.recepcion,
            fechaEstimada: fechasEstimadas.recepcion,
            completado: !!despacho.FECHA_REC && tieneEmbarqueReal,
            estimado: !tieneEmbarqueReal || !despacho.FECHA_REC,
            demorado: verificarDemora(despacho.FECHA_REC, fechasEstimadas.recepcion)
        },
        {
            // Ultimo paso: la salida del deposito central hacia los locales,
            // posterior a la recepcion.
            key: 'distribuido',
            // Solo DIST_ORIGEN = 'C' es una fecha en firme; 'A' (automatica)
            // y 'M' (movida a mano) siguen siendo proyecciones.
            label: 'Distribución',
            icono: '🏬',
            fecha: despacho.FECHA_DISTRI || fechasEstimadas.distribucion,
            fechaEstimada: fechasEstimadas.distribucion,
            completado: !!despacho.FECHA_DISTRI && despacho.DIST_ORIGEN === 'C',
            estimado: despacho.DIST_ORIGEN !== 'C',
            demorado: verificarDemora(despacho.FECHA_DISTRI, fechasEstimadas.distribucion)
        }
    ];
    
    // Prefijo contiguo y no filter(): el progreso es hasta donde llego la
    // cadena. Contando sueltos, un paso intermedio pendiente con uno
    // posterior cumplido inflaba el porcentaje y pintaba segmentos de mas.
    let pasosCompletados = 0;
    for (const paso of pasos) {
        if (!paso.completado) break;
        pasosCompletados++;
    }
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
            else if (pasoSiguiente.key === 'distribuido') colorBarra = 'var(--color-dist)';
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

/**
 * Cadena de fechas estimadas a partir del embarque.
 *
 * Los offsets salen de parametrosDias, que llega del endpoint y se administra
 * desde Parametros > Cronograma. Antes estaban hardcodeados 45 / 7 / 3 aca y
 * el 45 tambien en calcularFechaArriboEstimada().
 *
 * El arribo se calcula desde el embarque real si existe, y si no desde el
 * estimado. La distribucion cuelga del arribo, sea real o estimado.
 */
function calcularFechasEstimadas(despacho) {
    const fechas = {
        arribo: null,
        despacho: null,
        distribucion: null,
        recepcion: null
    };

    const base = despacho.FECHA_EMB || despacho.FECHA_EST_EMB;
    if (!base) return fechas;

    fechas.arribo      = sumarDias(base, parametrosDias.DIAS_EMB_ARR);
    fechas.despacho    = sumarDias(fechas.arribo, parametrosDias.DIAS_ARR_DESP);
    fechas.recepcion   = sumarDias(fechas.despacho, parametrosDias.DIAS_DESP_REC);

    // La distribucion es la salida del deposito hacia los locales, o sea que
    // va DESPUES de la recepcion:
    //   - con recepcion real, se distribuye al dia siguiente
    //   - sin recepcion, arribo + 10, que cae un dia despues de la
    //     recepcion estimada (arribo + 9)
    if (despacho.FECHA_REC) {
        fechas.distribucion = sumarDias(despacho.FECHA_REC, parametrosDias.DIAS_REC_DIST);
    } else {
        const arriboBase = despacho.FECHA_ARR || fechas.arribo;
        fechas.distribucion = sumarDias(arriboBase, parametrosDias.DIAS_ARR_DIST);
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
    // No toca el modal de confirmación: ese se cierra solo.
    $('.modal-overlay:not(.modal-confirmacion)').remove();
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
