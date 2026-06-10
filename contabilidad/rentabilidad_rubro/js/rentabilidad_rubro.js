/**
 * rentabilidad_rubro.js
 * Lógica de UI para el reporte de Rentabilidad por Rubro
 */

'use strict';

/* ── Constantes ─────────────────────────────────────────────────────────────── */
const CTRL_URL = 'controller/rentabilidad_rubro_controller.php';

/**
 * 6 filas de la tabla + separadores.
 * participacion_venta se muestra como null ('—') en la columna TOTAL.
 */
const FILAS_CONFIG = [
    { clave: 'venta',               label: 'VENTA',                  tipo: 'moneda',   clase: '',             colorear: false },
    { clave: 'costo',               label: 'COSTO',                  tipo: 'moneda',   clase: '',             colorear: false },
    { separator: true },
    { clave: 'resultado_bruto',     label: 'RESULTADO BRUTO',        tipo: 'moneda',   clase: 'tr-resultado', colorear: true  },
    { clave: 'margen_bruto',        label: 'Margen Bruto',           tipo: 'pct',      clase: 'tr-ratio',     colorear: false },
    { separator: true },
    { clave: 'markup',              label: 'Markup (Venta/Costo)',    tipo: 'decimal2', clase: 'tr-ratio',     colorear: false },
    { clave: 'participacion_venta', label: '% Participación venta',  tipo: 'pct',      clase: 'tr-ratio',     colorear: false },
];

const MESES_ES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

const TAB_TITULOS = {
    1: 'Informe Económico por Rubro',
    2: 'Informe Económico por Origen de Producción',
    3: 'Informe Económico por Categoría',
};

function periodoLabel(p) {
    const [m, a] = p.split('-');
    return `${MESES_ES[parseInt(m, 10) - 1]} ${a}`;
}

/* ── Estado ─────────────────────────────────────────────────────────────────── */

/**
 * Caché de reportes y filtros propios por solapa.
 * Los filtros comunes (desde, hasta, canal, moneda) viven en el DOM y son
 * compartidos; cada solapa solo guarda sus filtros específicos y su resultado.
 */
const estadoPorTab = {
    1: { reporte: null, desde: '', hasta: '', canal: '' },
    2: { reporte: null, desde: '', hasta: '', canal: '', rubro: '' },
    3: { reporte: null, desde: '', hasta: '', canal: '', rubro: '', color: '' },
};

let monedaActual  = 'ARS';
let tabActual     = 1;
let _modalContext = { desde: '', hasta: '', canal: '', faltantes: [] };



/* ── Formato de números ─────────────────────────────────────────────────────── */

function fmtMoneda(val) {
    if (val === null || val === undefined) return '<span class="val-null">—</span>';
    const num    = parseFloat(val);
    const sign   = num < 0 ? '-' : '';
    const entFmt = Math.round(Math.abs(num)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const prefix = monedaActual === 'USD' ? 'U$S' : '$';
    return `${sign}${prefix} ${entFmt}`;
}

function fmtPct(val) {
    if (val === null || val === undefined) return '<span class="val-null">—</span>';
    return (parseFloat(val) * 100).toFixed(1).replace('.', ',') + ' %';
}

/** Porcentaje como texto plano (sin HTML), para sub-stats y KPI pct */
function fmtPctTexto(val) {
    if (val === null || val === undefined) return '—';
    return (parseFloat(val) * 100).toFixed(1).replace('.', ',') + ' %';
}

function fmtDecimal2(val) {
    if (val === null || val === undefined) return '<span class="val-null">—</span>';
    return parseFloat(val).toFixed(2).replace('.', ',');
}

function formatear(val, tipo) {
    switch (tipo) {
        case 'moneda':   return fmtMoneda(val);
        case 'pct':      return fmtPct(val);
        case 'decimal2': return fmtDecimal2(val);
        default:         return val;
    }
}

function clasePorSigno(val, colorear, esResultado) {
    if (!colorear || val === null) return '';
    const n = parseFloat(val);
    if (n > 0 && esResultado) return 'val-positive';
    if (n < 0)                return 'val-negative';
    return '';
}

/* ── Validación de períodos ─────────────────────────────────────────────────── */

function validarPeriodo(str) {
    if (!/^\d{1,2}-\d{4}$/.test(str.trim())) return false;
    const [m, a] = str.split('-').map(Number);
    return m >= 1 && m <= 12 && a >= 2000 && a <= 2100;
}

function periodoMayor(a, b) {
    const [mA, aA] = a.split('-').map(Number);
    const [mB, aB] = b.split('-').map(Number);
    return aA > aB || (aA === aB && mA > mB);
}

/* ── Carga de datos maestros ────────────────────────────────────────────────── */

async function cargarCanales() {
    try {
        const r    = await fetch(`${CTRL_URL}?action=get_canales`);
        const json = await r.json();
        if (!json.success) return;
        const sel = document.getElementById('selectCanal');
        json.canales.forEach(c => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = c;
            sel.appendChild(opt);
        });
    } catch (e) {
        console.warn('No se pudieron cargar los canales:', e);
    }
}

async function cargarRubros() {
    try {
        const r    = await fetch(`${CTRL_URL}?action=get_rubros`);
        const json = await r.json();
        if (!json.success) return;
        const sel = document.getElementById('selectRubro');
        sel.innerHTML = '<option value="">— Todos los rubros —</option>';
        json.rubros.forEach(rb => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = rb;
            sel.appendChild(opt);
        });
    } catch (e) {
        console.warn('No se pudieron cargar los rubros:', e);
    }
}

async function cargarColoresPorRubro(rubro) {
    const sel = document.getElementById('selectColor');
    sel.innerHTML = '<option value="">— Todos los colores —</option>';
    if (!rubro) return;
    try {
        const fd = new FormData();
        fd.append('rubro', rubro);
        const r    = await fetch(`${CTRL_URL}?action=get_colores`, { method: 'POST', body: fd });
        const json = await r.json();
        if (!json.success) return;
        json.colores.forEach(c => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = c;
            sel.appendChild(opt);
        });
    } catch (e) {
        console.warn('No se pudieron cargar los colores:', e);
    }
}

/* ── Lógica de tabs ──────────────────────────────────────────────────────────── */

/** Guarda filtros específicos de la solapa antes de salir de ella */
function _guardarFiltrosTab(tab) {
    if (tab >= 2) estadoPorTab[tab].rubro = document.getElementById('selectRubro').value;
    if (tab === 3) estadoPorTab[tab].color = document.getElementById('selectColor').value;
}

/** Restaura filtros específicos y el reporte cacheado al entrar a una solapa */
async function cambiarTab(tab) {
    if (tab === tabActual) return;

    // Guardar estado de la solapa que dejamos
    _guardarFiltrosTab(tabActual);

    tabActual = tab;

    // Botones de tabs
    document.querySelectorAll('.rr-tab').forEach(btn => {
        const t = parseInt(btn.dataset.tab, 10);
        btn.classList.toggle('active', t === tab);
        btn.setAttribute('aria-selected', t === tab ? 'true' : 'false');
    });

    // Filtros condicionales visibles según solapa
    document.getElementById('filtroRubroGroup').style.display = tab >= 2 ? '' : 'none';
    document.getElementById('filtroColorGroup').style.display = tab === 3 ? '' : 'none';

    // Restaurar filtros propios de la solapa destino
    const est = estadoPorTab[tab];
    if (tab >= 2) {
        document.getElementById('selectRubro').value = est.rubro || '';
    }
    if (tab === 3) {
        // Recargar opciones de color si había un rubro guardado y restaurar selección
        if (est.rubro) {
            await cargarColoresPorRubro(est.rubro);
            document.getElementById('selectColor').value = est.color || '';
        } else {
            document.getElementById('selectColor').innerHTML =
                '<option value="">— Todos los colores —</option>';
        }
    }

    // Restaurar reporte cacheado, o auto-aplicar si los filtros comunes son válidos
    if (est.reporte) {
        _renderReporteCacheado(tab);
    } else {
        const desde = document.getElementById('inputDesde').value.trim();
        const hasta  = document.getElementById('inputHasta').value.trim();
        const canal  = document.getElementById('selectCanal').value;
        if (validarPeriodo(desde) && validarPeriodo(hasta) && !periodoMayor(desde, hasta)) {
            await cargarReporte(desde, hasta, canal);
        } else {
            setEstado('inicial');
            document.getElementById('btnExportar').disabled = true;
        }
    }
}

/**
 * Re-renderiza la UI a partir del reporte cacheado en estadoPorTab[tab].
 * Útil al volver a una solapa ya cargada o al cambiar moneda.
 */
function _renderReporteCacheado(tab) {
    const est  = estadoPorTab[tab];
    const json = est.reporte;
    if (!json) return;

    actualizarKPIs(json.kpis, json.base_calculo);

    // Meta info reconstruida desde los valores guardados
    const canalLabel  = est.canal ? ` · Canal: ${est.canal}` : '';
    const monedaLabel = monedaActual === 'USD'
        ? ` · USD (TCC: ${json.tcc_promedio ? parseFloat(json.tcc_promedio).toFixed(2) : '—'})`
        : '';
    const metaPeriodo = est.desde === est.hasta
        ? periodoLabel(est.desde)
        : `${periodoLabel(est.desde)} — ${periodoLabel(est.hasta)}`;

    document.getElementById('tablaTitulo').textContent = TAB_TITULOS[tab];
    document.getElementById('tablaMeta').textContent   =
        `Período: ${metaPeriodo}${canalLabel}${monedaLabel}`;

    // Chip de base de prorrateo
    const bc   = json.base_calculo;
    const chip = document.getElementById('baseCalculoChip');
    if (bc && bc.monto) {
        document.getElementById('baseCalculoMonto').textContent  = fmtMoneda(bc.monto);
        document.getElementById('baseCalculoFuente').textContent    = '(Venta total)';
        document.getElementById('bcTooltipNormales').innerHTML      = fmtMoneda(bc.venta_normales ?? 0);
        document.getElementById('bcTooltipRecuperos').innerHTML     = fmtMoneda(bc.recuperos ?? 0);
        document.getElementById('bcTooltipProrrateables').innerHTML = fmtMoneda(bc.prorrateables ?? 0);
        document.getElementById('bcTooltipVenta').innerHTML         = fmtMoneda(bc.monto);
        chip.style.display = 'flex';
    } else {
        chip.style.display = 'none';
    }

    renderTabla(json.dimensiones, json.data);
    setEstado('tabla');
    document.getElementById('btnExportar').disabled = false;
}

/* ── KPI Cards ──────────────────────────────────────────────────────────────── */

function actualizarKPIs(kpis, bc) {
    // Cards principales
    const sec = document.getElementById('kpiSection');
    sec.style.display = 'block';

    // Venta Total = importe actual (tabla rubros) + |Recupero de promociones (1.8.)| (IE).
    // Son orígenes distintos (RO_T_RENT_BRUTA_RUBRO + RO_T_RESUMEN_FINAL_IE), por eso se suman.
    const recupero18     = Math.abs(bc?.recupero_18 ?? 0);
    const ventaRubros    = bc?.venta_total_real ?? kpis.venta_total;
    const ventaTotalCard = ventaRubros + recupero18;
    document.getElementById('kpiVentaVal').innerHTML = fmtMoneda(ventaTotalCard);

    // Tooltip de composición de venta total
    const wrap = document.getElementById('kpiVentaDesgloseWrap');
    if (wrap && bc?.venta_total_real != null) {
        document.getElementById('kpiVentaNorm').innerHTML       = fmtMoneda(ventaRubros);
        document.getElementById('kpiVentaRecupero18').innerHTML = fmtMoneda(recupero18);
        document.getElementById('kpiVentaTotalVal').innerHTML   = fmtMoneda(ventaTotalCard);
        wrap.style.display = 'inline-flex';
    }

    document.getElementById('kpiRBVal').innerHTML = fmtMoneda(kpis.resultado_bruto);
    document.getElementById('kpiRBPct').textContent = kpis.rel_resultado_bruto !== null
        ? fmtPctTexto(kpis.rel_resultado_bruto) + ' s/vta' : '—';

    document.getElementById('kpiROVal').innerHTML = fmtMoneda(kpis.resultado_operativo);
    document.getElementById('kpiROPct').textContent = kpis.rel_resultado_operativo !== null
        ? fmtPctTexto(kpis.rel_resultado_operativo) + ' s/vta' : '—';

    document.getElementById('kpiREVal').innerHTML = fmtMoneda(kpis.resultado_explotacion);
    document.getElementById('kpiREPct').textContent = kpis.rel_resultado_explotacion !== null
        ? fmtPctTexto(kpis.rel_resultado_explotacion) + ' s/vta' : '—';

    // Colorear KPIs de resultado según signo
    ['kpiRBVal','kpiROVal','kpiREVal'].forEach(id => {
        const el  = document.getElementById(id);
        const num = parseFloat(el.textContent.replace(/[$.U]/g, '').replace(/\./g, '').replace(',', '.'));
        el.classList.toggle('val-positive', num > 0);
        el.classList.toggle('val-negative', num < 0);
    });

    // Cards de % gastos
    const pctSec = document.getElementById('kpiPctSection');
    pctSec.style.display = 'block';

    document.getElementById('kpiGastosComVal').textContent = fmtPctTexto(kpis.pct_gastos_comercializacion);
    document.getElementById('kpiGastosOpVal').textContent  = fmtPctTexto(kpis.pct_gastos_operativos);
    document.getElementById('kpiGastosEstVal').textContent = fmtPctTexto(kpis.pct_gastos_estructura);

    const personal  = fmtPctTexto(kpis.pct_gastos_personal);
    const ocupacion = fmtPctTexto(kpis.pct_gastos_ocupacion);
    const otros     = fmtPctTexto(kpis.pct_otros_gastos_operativos);
    document.getElementById('kpiGastosOpSub').textContent =
        `Personal: ${personal} · Ocupación: ${ocupacion} · Otros: ${otros}`;
}

/* ── Render de tabla ────────────────────────────────────────────────────────── */

function renderTabla(dimensiones, data) {
    const allCols = [...dimensiones, 'TOTAL'];

    /* THEAD */
    const head = document.getElementById('tablaHead');
    head.innerHTML = '';
    const trH = document.createElement('tr');

    const thC = document.createElement('th');
    thC.textContent = 'Concepto';
    trH.appendChild(thC);

    allCols.forEach(col => {
        const th = document.createElement('th');
        th.className = `th-rubro${col === 'TOTAL' ? ' col-total' : ''}`;

        const inner  = document.createElement('div');
        inner.className = 'th-rubro-inner';

        const nombre = document.createElement('span');
        nombre.className   = 'th-rubro-nombre';
        nombre.textContent = col;

        inner.appendChild(nombre);
        th.appendChild(inner);
        trH.appendChild(th);
    });
    head.appendChild(trH);

    /* TBODY */
    const body = document.getElementById('tablaBody');
    body.innerHTML = '';

    FILAS_CONFIG.forEach(fila => {

        if (fila.separator) {
            const tr = document.createElement('tr');
            tr.className = 'tr-separator';
            const td = document.createElement('td');
            td.colSpan = allCols.length + 1;
            tr.appendChild(td);
            body.appendChild(tr);
            return;
        }

        if (fila.section) {
            const tr = document.createElement('tr');
            tr.className = 'tr-section-header';
            const tdL = document.createElement('td');
            tdL.className   = 'td-concepto';
            tdL.textContent = fila.section;
            tr.appendChild(tdL);
            const tdF = document.createElement('td');
            tdF.colSpan = allCols.length;
            tr.appendChild(tdF);
            body.appendChild(tr);
            return;
        }

        const tr = document.createElement('tr');
        if (fila.clase) tr.className = fila.clase;

        // Celda concepto
        const tdC = document.createElement('td');
        tdC.className = 'td-concepto';

        // Badge de coeficiente para filas de gasto
        const mostrarCoef = fila.clase === 'tr-gasto' || fila.clase === 'tr-total-cat';
        const totalRow    = data['TOTAL'];
        if (mostrarCoef && totalRow && totalRow.venta && totalRow[fila.clave] != null) {
            const pct = Math.abs(totalRow[fila.clave]) / totalRow.venta * 100;
            tdC.classList.add('td-concepto--coef');
            tdC.innerHTML =
                `<span class="concepto-nombre">${fila.label}</span>` +
                `<span class="coef-pct">${pct.toFixed(1).replace('.', ',')} %</span>`;
        } else {
            tdC.textContent = fila.label;
        }
        tr.appendChild(tdC);

        // Celdas de datos
        allCols.forEach(col => {
            const td  = document.createElement('td');
            const val = data[col]?.[fila.clave];
            const esResultado = fila.clase?.includes('resultado') || fila.clase?.includes('contribucion');

            td.className = `td-num${col === 'TOTAL' ? ' col-total' : ''}`;

            const signClass = clasePorSigno(val, fila.colorear, esResultado);
            const fmtVal    = formatear(val, fila.tipo);

            if (typeof fmtVal === 'string' && fmtVal.startsWith('<')) {
                td.innerHTML = fmtVal;
            } else {
                td.innerHTML = signClass
                    ? `<span class="${signClass}">${fmtVal}</span>`
                    : fmtVal;
            }
            tr.appendChild(td);
        });

        body.appendChild(tr);
    });
}

/* ── Alertas ────────────────────────────────────────────────────────────────── */

function mostrarAlerta(msg) {
    const el = document.getElementById('alertaFiltros');
    el.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> ${msg}`;
    el.style.display = 'flex';
}

function ocultarAlerta() {
    document.getElementById('alertaFiltros').style.display = 'none';
}

/* ── Estado de la vista ─────────────────────────────────────────────────────── */

function setEstado(estado) {
    document.getElementById('estadoInicial').style.display  = estado === 'inicial'  ? '' : 'none';
    document.getElementById('loadingSection').style.display = estado === 'loading'  ? '' : 'none';
    document.getElementById('tablaSection').style.display   = estado === 'tabla'    ? '' : 'none';
    if (estado !== 'tabla') {
        document.getElementById('kpiSection').style.display    = 'none';
        document.getElementById('kpiPctSection').style.display = 'none';
    }
}

/* ── Modal de procesamiento ─────────────────────────────────────────────────── */

function abrirModal(faltantes, desde, hasta, canal) {
    _modalContext = { desde, hasta, canal, faltantes };

    const lista = document.getElementById('mpLista');
    lista.innerHTML = '';
    faltantes.forEach(f => {
        const li = document.createElement('li');
        li.dataset.periodo = f.periodo;
        li.innerHTML =
            `<span class="mp-item-icon"><i class="bi bi-calendar2-minus"></i></span>` +
            `<span class="mp-item-label">${f.nombre}</span>` +
            `<span class="mp-item-status">Sin datos</span>`;
        lista.appendChild(li);
    });

    document.getElementById('mpProgressWrap').style.display = 'none';
    document.getElementById('mpProgressBar').style.width    = '0%';
    document.getElementById('mpBtnProcesar').disabled       = false;
    document.getElementById('mpBtnCancelar').disabled       = false;
    document.getElementById('mpBtnLabel').textContent       = 'Procesar todos';
    document.getElementById('modalProcesamiento').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalProcesamiento').style.display = 'none';
}

async function procesarPeriodos() {
    const { desde, hasta, canal, faltantes } = _modalContext;
    const total = faltantes.length;

    document.getElementById('mpBtnProcesar').disabled = true;
    document.getElementById('mpBtnCancelar').disabled = true;
    document.getElementById('mpProgressWrap').style.display = 'block';

    let procesados = 0;
    let hayError   = false;

    for (const f of faltantes) {
        const li = document.querySelector(`#mpLista li[data-periodo="${f.periodo}"]`);
        if (li) {
            li.className = 'mp-processing';
            li.querySelector('.mp-item-icon').innerHTML     = '<i class="bi bi-arrow-repeat"></i>';
            li.querySelector('.mp-item-status').textContent = 'Procesando…';
        }

        try {
            const fd = new FormData();
            fd.append('fecha_desde', f.fecha_desde);
            fd.append('fecha_hasta', f.fecha_hasta);
            const r    = await fetch(`${CTRL_URL}?action=procesar_periodo`, { method: 'POST', body: fd });
            const json = await r.json();
            if (li) {
                if (json.success) {
                    li.className = 'mp-ok';
                    li.querySelector('.mp-item-icon').innerHTML     = '<i class="bi bi-check-circle-fill"></i>';
                    li.querySelector('.mp-item-status').textContent = 'Procesado';
                } else {
                    hayError = true;
                    li.className = 'mp-error';
                    li.querySelector('.mp-item-icon').innerHTML     = '<i class="bi bi-x-circle-fill"></i>';
                    li.querySelector('.mp-item-status').textContent = json.message || 'Error';
                }
            }
        } catch (e) {
            hayError = true;
            if (li) {
                li.className = 'mp-error';
                li.querySelector('.mp-item-icon').innerHTML     = '<i class="bi bi-x-circle-fill"></i>';
                li.querySelector('.mp-item-status').textContent = 'Error de conexión';
            }
        }

        procesados++;
        document.getElementById('mpProgressBar').style.width =
            `${Math.round(procesados / total * 100)}%`;
    }

    if (hayError) {
        document.getElementById('mpBtnCancelar').disabled  = false;
        document.getElementById('mpBtnLabel').textContent  = 'Reintentar';
        document.getElementById('mpBtnProcesar').disabled  = false;
        return;
    }

    document.getElementById('mpBtnLabel').textContent = 'Cargando reporte…';
    cerrarModal();
    await cargarReporte(desde, hasta, canal);
}

/* ── Fetch del reporte ──────────────────────────────────────────────────────── */

async function cargarReporte(desde, hasta, canal) {
    const btnAplicar  = document.getElementById('btnAplicar');
    const btnExportar = document.getElementById('btnExportar');

    btnAplicar.disabled  = true;
    btnExportar.disabled = true;
    setEstado('loading');

    try {
        const fd = new FormData();
        fd.append('desde',  desde);
        fd.append('hasta',  hasta);
        fd.append('canal',  canal);
        fd.append('moneda', monedaActual);

        let actionUrl;
        if (tabActual === 1) {
            actionUrl = `${CTRL_URL}?action=get_reporte`;
        } else if (tabActual === 2) {
            fd.append('rubro', document.getElementById('selectRubro').value);
            actionUrl = `${CTRL_URL}?action=get_reporte_origen`;
        } else {
            fd.append('rubro', document.getElementById('selectRubro').value);
            fd.append('color', document.getElementById('selectColor').value);
            actionUrl = `${CTRL_URL}?action=get_reporte_categoria`;
        }

        const r    = await fetch(actionUrl, { method: 'POST', body: fd });
        const json = await r.json();

        if (!json.success && json.code === 'PERIODOS_FALTANTES') {
            setEstado('inicial');
            abrirModal(json.periodos_faltantes, desde, hasta, canal);
            return;
        }

        if (!json.success) {
            setEstado('inicial');
            mostrarAlerta(json.message || 'Error al obtener el reporte.');
            return;
        }

        if (!json.dimensiones || json.dimensiones.length === 0) {
            setEstado('inicial');
            mostrarAlerta('No se encontraron datos para los filtros seleccionados.');
            return;
        }

        // Guardar en caché de la solapa activa
        estadoPorTab[tabActual].reporte = json;
        estadoPorTab[tabActual].desde   = desde;
        estadoPorTab[tabActual].hasta   = hasta;
        estadoPorTab[tabActual].canal   = canal;
        if (tabActual >= 2) estadoPorTab[tabActual].rubro = document.getElementById('selectRubro').value;
        if (tabActual === 3) estadoPorTab[tabActual].color = document.getElementById('selectColor').value;

        // KPIs
        actualizarKPIs(json.kpis, json.base_calculo);

        // Meta info
        const canalLabel  = canal ? ` · Canal: ${canal}` : '';
        const monedaLabel = monedaActual === 'USD'
            ? ` · USD (TCC: ${json.tcc_promedio ? parseFloat(json.tcc_promedio).toFixed(2) : '—'})`
            : '';
        const metaPeriodo = desde === hasta
            ? periodoLabel(desde)
            : `${periodoLabel(desde)} — ${periodoLabel(hasta)}`;

        document.getElementById('tablaTitulo').textContent = TAB_TITULOS[tabActual];
        document.getElementById('tablaMeta').textContent   =
            `Período: ${metaPeriodo}${canalLabel}${monedaLabel}`;

        // Base de prorrateo
        const bc   = json.base_calculo;
        const chip = document.getElementById('baseCalculoChip');
        if (bc && bc.monto) {
            document.getElementById('baseCalculoMonto').textContent  = fmtMoneda(bc.monto);
            document.getElementById('baseCalculoFuente').textContent    = '(Venta total)';
            document.getElementById('bcTooltipNormales').innerHTML      = fmtMoneda(bc.venta_normales ?? 0);
            document.getElementById('bcTooltipRecuperos').innerHTML     = fmtMoneda(bc.recuperos ?? 0);
            document.getElementById('bcTooltipProrrateables').innerHTML = fmtMoneda(bc.prorrateables ?? 0);
            document.getElementById('bcTooltipVenta').innerHTML         = fmtMoneda(bc.monto);

            chip.style.display = 'flex';
        } else {
            chip.style.display = 'none';
        }

        // Tabla — usa 'dimensiones' como lista de columnas
        renderTabla(json.dimensiones, json.data);
        setEstado('tabla');
        btnExportar.disabled = false;

    } catch (e) {
        setEstado('inicial');
        mostrarAlerta('Error de conexión. Por favor, intentá de nuevo.');
        console.error(e);
    } finally {
        btnAplicar.disabled = false;
    }
}

async function aplicarFiltros() {
    const desde = document.getElementById('inputDesde').value.trim();
    const hasta = document.getElementById('inputHasta').value.trim();
    const canal = document.getElementById('selectCanal').value;

    ocultarAlerta();
    document.getElementById('inputDesde').classList.remove('error');
    document.getElementById('inputHasta').classList.remove('error');

    if (!desde || !validarPeriodo(desde)) {
        document.getElementById('inputDesde').classList.add('error');
        mostrarAlerta('El período Desde no es válido. Use el formato M-AAAA (ej: 1-2025).');
        return;
    }

    if (!hasta || !validarPeriodo(hasta)) {
        document.getElementById('inputHasta').classList.add('error');
        mostrarAlerta('El período Hasta no es válido. Use el formato M-AAAA (ej: 12-2025).');
        return;
    }

    if (periodoMayor(desde, hasta)) {
        mostrarAlerta('El período Desde no puede ser mayor que el período Hasta.');
        return;
    }

    await cargarReporte(desde, hasta, canal);
}

/* ── Exportar a Excel ──────────────────────────────────────────────────────── */

function exportarExcel() {
    if (!estadoPorTab[tabActual].reporte) return;

    const tabla = document.getElementById('tablaReporte');
    if (!tabla) return;

    const clone = tabla.cloneNode(true);
    clone.querySelectorAll('td, th').forEach(cell => {
        cell.innerHTML = cell.textContent.trim();
    });

    if (typeof XLSX !== 'undefined') {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(clone);
        const tabNombre = ['PorRubro', 'PorOrigen', 'PorCategoria'][tabActual - 1];
        XLSX.utils.book_append_sheet(wb, ws, `Rentabilidad_${tabNombre}`);

        const desde = document.getElementById('inputDesde').value;
        const hasta = document.getElementById('inputHasta').value;
        XLSX.writeFile(wb, `Rentabilidad_${tabNombre}_${desde}_${hasta}.xlsx`);
    } else {
        const htmlContent = `<html><head><meta charset="utf-8">
            <style>table{border-collapse:collapse}td,th{border:1px solid #ccc;padding:6px 10px;font-size:12px}</style>
            </head><body>${clone.outerHTML}</body></html>`;
        const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href = url;
        a.download = `Rentabilidad_${document.getElementById('inputDesde').value}.xls`;
        a.click();
        URL.revokeObjectURL(url);
    }
}

/* ── Auto-formato de inputs de período ─────────────────────────────────────── */

function setupPeriodoInput(id) {
    const inp = document.getElementById(id);
    inp.addEventListener('input', () => {
        inp.value = inp.value.replace(/[^\d-]/g, '');
        inp.classList.remove('error');
        ocultarAlerta();
    });
    inp.addEventListener('keydown', e => {
        if (e.key === 'Enter') aplicarFiltros();
    });
}

/* ── Modal de información ────────────────────────────────────────────────── */

function abrirModalInfo() {
    const modal = document.getElementById('modalInfo');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    _inicializarAcordeon();
}

function cerrarModalInfo() {
    document.getElementById('modalInfo').style.display = 'none';
    document.body.style.overflow = '';
}

/** Fija el max-height inicial de cada item del acordeón según su estado */
function _inicializarAcordeon() {
    document.querySelectorAll('.mi-item').forEach(item => {
        const body = item.querySelector('.mi-item-body');
        if (!body) return;
        if (item.classList.contains('mi-open')) {
            body.style.maxHeight = body.scrollHeight + 'px';
        } else {
            body.style.maxHeight = '0';
        }
    });
}

function setupModalInfo() {
    const modal   = document.getElementById('modalInfo');
    const overlay = modal;

    // Abrir
    document.getElementById('btnInfoReporte').addEventListener('click', abrirModalInfo);

    // Cerrar con botones
    document.getElementById('miBtnCerrar').addEventListener('click',    cerrarModalInfo);
    document.getElementById('miBtnEntendido').addEventListener('click', cerrarModalInfo);

    // Cerrar al hacer clic en el overlay (fuera de la card)
    overlay.addEventListener('click', e => {
        if (e.target === overlay) cerrarModalInfo();
    });

    // Cerrar con Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal.style.display === 'flex') cerrarModalInfo();
    });

    // ── Acordeón ────────────────────────────────────────────────────────────
    document.querySelectorAll('.mi-item-header').forEach(header => {
        header.addEventListener('click', () => {
            const item      = header.closest('.mi-item');
            const estaAbierto = item.classList.contains('mi-open');

            // Cerrar todos
            document.querySelectorAll('.mi-item').forEach(i => {
                i.classList.remove('mi-open');
                i.querySelector('.mi-item-header').setAttribute('aria-expanded', 'false');
                const b = i.querySelector('.mi-item-body');
                if (b) b.style.maxHeight = '0';
            });

            // Abrir el clickeado si estaba cerrado
            if (!estaAbierto) {
                item.classList.add('mi-open');
                header.setAttribute('aria-expanded', 'true');
                const body = item.querySelector('.mi-item-body');
                if (body) body.style.maxHeight = body.scrollHeight + 'px';
            }
        });
    });
}

/* ── Init ──────────────────────────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', () => {

    // Precargar período anterior
    const hoy  = new Date();
    const mesD = hoy.getMonth() === 0 ? 12 : hoy.getMonth();
    const anioD = hoy.getMonth() === 0 ? hoy.getFullYear() - 1 : hoy.getFullYear();
    document.getElementById('inputDesde').value = `${mesD}-${anioD}`;
    document.getElementById('inputHasta').value = `${mesD}-${anioD}`;

    setupPeriodoInput('inputDesde');
    setupPeriodoInput('inputHasta');

    // Botones principales
    document.getElementById('btnAplicar').addEventListener('click', aplicarFiltros);
    document.getElementById('btnExportar').addEventListener('click', exportarExcel);

    // Tabs
    document.querySelectorAll('.rr-tab').forEach(btn => {
        btn.addEventListener('click', () => cambiarTab(parseInt(btn.dataset.tab, 10)));
    });

    // Filtro cascada: Rubro → Color
    document.getElementById('selectRubro').addEventListener('change', async () => {
        if (tabActual === 3) {
            await cargarColoresPorRubro(document.getElementById('selectRubro').value);
        }
    });

    // Modal de procesamiento
    document.getElementById('mpBtnCerrar').addEventListener('click', cerrarModal);
    document.getElementById('mpBtnCancelar').addEventListener('click', cerrarModal);
    document.getElementById('mpBtnProcesar').addEventListener('click', procesarPeriodos);

    // Toggle de moneda — invalida cachés de todas las solapas y recarga la activa
    document.querySelectorAll('input[name="moneda"]').forEach(radio => {
        radio.addEventListener('change', async () => {
            monedaActual = radio.value;
            // Borrar reportes cacheados (son en la moneda anterior)
            [1, 2, 3].forEach(t => { estadoPorTab[t].reporte = null; });
            // Recargar la solapa activa si tenía un reporte
            const est = estadoPorTab[tabActual];
            if (est.desde) {
                await cargarReporte(est.desde, est.hasta, est.canal);
            }
        });
    });

    // Carga inicial de datos maestros
    cargarCanales();
    cargarRubros();
    setEstado('inicial');

    // ── Modal de información ─────────────────────────────────────────────────
    setupModalInfo();

    // ── Popover de base de cálculo ──────────────────────────────────────────
    const bcIcon    = document.querySelector('.bc-info-icon');
    const bcPopover = document.getElementById('bcTooltip');

    if (bcIcon && bcPopover) {
        bcIcon.addEventListener('click', e => {
            e.stopPropagation();
            const abierto = bcPopover.classList.toggle('bc-open');
            bcIcon.classList.toggle('active', abierto);
        });

        // Cerrar al hacer clic fuera
        document.addEventListener('click', e => {
            if (!bcPopover.contains(e.target) && e.target !== bcIcon) {
                bcPopover.classList.remove('bc-open');
                bcIcon.classList.remove('active');
            }
        });

        // Cerrar con Escape
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && bcPopover.classList.contains('bc-open')) {
                bcPopover.classList.remove('bc-open');
                bcIcon.classList.remove('active');
            }
        });
    }

    // ── Popover de composición de venta total ───────────────────────────────
    const ventaIcon    = document.getElementById('kpiVentaDesgloseIcon');
    const ventaPopover = document.getElementById('kpiVentaDesglosePop');

    // Mover al body para que position:fixed no se vea afectado por el
    // transform:translateY de .kpi-card:hover
    if (ventaPopover) document.body.appendChild(ventaPopover);

    if (ventaIcon && ventaPopover) {
        ventaIcon.addEventListener('click', e => {
            e.stopPropagation();
            const abierto = !ventaPopover.classList.contains('kpi-desglose-open');
            if (abierto) {
                const rect = ventaIcon.getBoundingClientRect();
                ventaPopover.style.top  = (rect.bottom + 8) + 'px';
                ventaPopover.style.left = rect.left + 'px';
            }
            ventaPopover.classList.toggle('kpi-desglose-open', abierto);
            ventaIcon.classList.toggle('active', abierto);
        });

        document.addEventListener('click', e => {
            if (!ventaPopover.contains(e.target) && e.target !== ventaIcon) {
                ventaPopover.classList.remove('kpi-desglose-open');
                ventaIcon.classList.remove('active');
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && ventaPopover.classList.contains('kpi-desglose-open')) {
                ventaPopover.classList.remove('kpi-desglose-open');
                ventaIcon.classList.remove('active');
            }
        });
    }
});
