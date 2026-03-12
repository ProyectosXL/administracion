/**
 * rentabilidad_rubro.js
 * Lógica de UI para el reporte de Rentabilidad por Rubro
 */

'use strict';

/* ── Constantes ─────────────────────────────────────────────────────────────── */
const CTRL_URL = 'controller/rentabilidad_rubro_controller.php';

const FILAS_CONFIG = [
    // [clave, etiqueta, tipo, esResultado, colorearSegunSigno]
    { clave: 'venta',                      label: 'VENTA',                          tipo: 'moneda',   clase: '',             colorear: false },
    { clave: 'costo',                      label: 'COSTO',                          tipo: 'moneda',   clase: '',             colorear: false },
    { clave: 'markup',                     label: 'Markup (Venta / Costo)',          tipo: 'decimal2', clase: 'tr-ratio',     colorear: false },
    { separator: true },
    { clave: 'resultado_bruto',            label: 'RESULTADO BRUTO',                tipo: 'moneda',   clase: 'tr-resultado', colorear: true  },
    { clave: 'rel_costo_venta',            label: 'Relación costo s/ ventas',        tipo: 'pct',      clase: 'tr-ratio',     colorear: false },
    { separator: true },
    { section: 'Gastos Comerciales' },
    { clave: 'gastos_comercializacion',    label: 'Total Gastos Comercialización',   tipo: 'moneda',   clase: 'tr-gasto',     colorear: false },
    { separator: true },
    { clave: 'resultado_comercial',        label: 'RESULTADO COMERCIAL',             tipo: 'moneda',   clase: 'tr-resultado', colorear: true  },
    { clave: 'rel_resultado_comercial',    label: 'Relación s/ ventas',              tipo: 'pct',      clase: 'tr-ratio',     colorear: false },
    { separator: true },
    { section: 'Gastos Operativos' },
    { clave: 'gastos_personal',            label: 'Total Gastos de Personal',        tipo: 'moneda',   clase: 'tr-gasto',     colorear: false },
    { clave: 'gastos_ocupacion',           label: 'Total Gastos Ocupación',          tipo: 'moneda',   clase: 'tr-gasto',     colorear: false },
    { clave: 'otros_gastos_operativos',    label: 'Total Otros Gastos Operativos',   tipo: 'moneda',   clase: 'tr-gasto',     colorear: false },
    { clave: 'total_gastos_operativos',    label: 'Total Gastos Operativos',         tipo: 'moneda',   clase: 'tr-total-cat', colorear: false },
    { separator: true },
    { clave: 'resultado_operativo',        label: 'RESULTADO OPERATIVO',             tipo: 'moneda',   clase: 'tr-resultado', colorear: true  },
    { clave: 'rel_resultado_operativo',    label: 'Relación s/ ventas',              tipo: 'pct',      clase: 'tr-ratio',     colorear: false },
    { separator: true },
    { clave: 'bienes_de_uso',             label: 'Bienes de uso',                   tipo: 'moneda',   clase: 'tr-gasto',     colorear: false },
    { clave: 'contribucion_marginal_neta', label: 'CONTRIBUCIÓN MARGINAL NETA',      tipo: 'moneda',   clase: 'tr-contribucion', colorear: true },
    { separator: true },
    { section: 'Gastos de Estructura' },
    { clave: 'gastos_estructura',          label: 'Total Gastos de Estructura',      tipo: 'moneda',   clase: 'tr-gasto',     colorear: false },
    { clave: 'rel_costo_total',            label: 'Relación costo total s/ ventas',  tipo: 'pct',      clase: 'tr-ratio',     colorear: false },
    { separator: true },
    { clave: 'resultado_explotacion',      label: 'RESULTADO EXPLOTACIÓN',           tipo: 'moneda',   clase: 'tr-resultado', colorear: true  },
    { clave: 'rel_resultado_explotacion',  label: 'Relación s/ ventas',              tipo: 'pct',      clase: 'tr-ratio',     colorear: false },
];

const MESES_ES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

/** Convierte '1-2025' → 'Enero 2025' */
function periodoLabel(p) {
    const [m, a] = p.split('-');
    return `${MESES_ES[parseInt(m, 10) - 1]} ${a}`;
}

/* ── Estado ─────────────────────────────────────────────────────────────────── */
let ultimoReporte  = null;
let monedaActual   = 'ARS';
let _modalContext  = { desde: '', hasta: '', canal: '', faltantes: [] };

/* ── Utilidades de formato ──────────────────────────────────────────────────── */

/** Formatea número en estilo argentino sin decimales: $ 1.234.567 */
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

/** Determina clase CSS para colorear según signo */
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
    // Devuelve true si a > b
    const [mA, aA] = a.split('-').map(Number);
    const [mB, aB] = b.split('-').map(Number);
    return aA > aB || (aA === aB && mA > mB);
}

/* ── Carga de canales ───────────────────────────────────────────────────────── */

async function cargarCanales() {
    try {
        const r    = await fetch(`${CTRL_URL}?action=get_canales`);
        const json = await r.json();
        if (!json.success) return;

        const sel = document.getElementById('selectCanal');
        json.canales.forEach(c => {
            const opt = document.createElement('option');
            opt.value       = c;
            opt.textContent = c;
            sel.appendChild(opt);
        });
    } catch (e) {
        console.warn('No se pudieron cargar los canales:', e);
    }
}

/* ── KPI Cards ──────────────────────────────────────────────────────────────── */

function actualizarKPIs(kpis) {
    const sec = document.getElementById('kpiSection');
    sec.style.display = 'block';

    document.getElementById('kpiVentaVal').innerHTML = fmtMoneda(kpis.venta_total);

    document.getElementById('kpiRBVal').innerHTML  = fmtMoneda(kpis.resultado_bruto);
    document.getElementById('kpiRBPct').innerHTML  = kpis.rel_resultado_bruto !== null
        ? fmtPct(kpis.rel_resultado_bruto) + ' s/vta'
        : '—';

    document.getElementById('kpiROVal').innerHTML  = fmtMoneda(kpis.resultado_operativo);
    document.getElementById('kpiROPct').innerHTML  = kpis.rel_resultado_operativo !== null
        ? fmtPct(kpis.rel_resultado_operativo) + ' s/vta'
        : '—';

    document.getElementById('kpiREVal').innerHTML  = fmtMoneda(kpis.resultado_explotacion);
    document.getElementById('kpiREPct').innerHTML  = kpis.rel_resultado_explotacion !== null
        ? fmtPct(kpis.rel_resultado_explotacion) + ' s/vta'
        : '—';

    // Colorear KPI según signo
    ['kpiRBVal','kpiROVal','kpiREVal'].forEach(id => {
        const el  = document.getElementById(id);
        const num = parseFloat(el.textContent.replace(/[$.]/g,'').replace(',','.'));
        el.classList.toggle('val-positive', num > 0);
        el.classList.toggle('val-negative', num < 0);
    });
}

/* ── Render de tabla ────────────────────────────────────────────────────────── */

function renderTabla(rubros, data) {
    const allCols = [...rubros, 'TOTAL'];

    /* ── THEAD ── */
    const head = document.getElementById('tablaHead');
    head.innerHTML = '';
    const trH = document.createElement('tr');

    // Columna concepto
    const thC = document.createElement('th');
    thC.textContent = 'Concepto';
    trH.appendChild(thC);

    // Columnas de rubros
    allCols.forEach(rubro => {
        const th = document.createElement('th');
        th.className = `th-rubro${rubro === 'TOTAL' ? ' col-total' : ''}`;

        const inner = document.createElement('div');
        inner.className = 'th-rubro-inner';

        const nombre = document.createElement('span');
        nombre.className    = 'th-rubro-nombre';
        nombre.textContent  = rubro;

        inner.appendChild(nombre);
        th.appendChild(inner);
        trH.appendChild(th);
    });

    head.appendChild(trH);

    /* ── TBODY ── */
    const body = document.getElementById('tablaBody');
    body.innerHTML = '';

    FILAS_CONFIG.forEach(fila => {

        // Separador
        if (fila.separator) {
            const tr = document.createElement('tr');
            tr.className = 'tr-separator';
            const td = document.createElement('td');
            td.colSpan = allCols.length + 1;
            tr.appendChild(td);
            body.appendChild(tr);
            return;
        }

        // Header de sección
        if (fila.section) {
            const tr = document.createElement('tr');
            tr.className = 'tr-section-header';
            const tdLabel = document.createElement('td');
            tdLabel.className   = 'td-concepto';
            tdLabel.textContent = fila.section;
            tr.appendChild(tdLabel);
            const tdFill = document.createElement('td');
            tdFill.colSpan = allCols.length;
            tr.appendChild(tdFill);
            body.appendChild(tr);
            return;
        }

        // Fila de dato
        const tr = document.createElement('tr');
        if (fila.clase) tr.className = fila.clase;

        // Celda concepto — con badge de coeficiente para filas de gasto
        const tdC = document.createElement('td');
        tdC.className = 'td-concepto';

        const mostrarCoef = fila.clase === 'tr-gasto' || fila.clase === 'tr-total-cat';
        const totalRow    = data['TOTAL'];
        if (mostrarCoef && totalRow && totalRow.venta && totalRow[fila.clave] != null) {
            const pct    = Math.abs(totalRow[fila.clave]) / totalRow.venta * 100;
            const pctFmt = pct.toFixed(1).replace('.', ',');
            tdC.classList.add('td-concepto--coef');
            tdC.innerHTML =
                `<span class="concepto-nombre">${fila.label}</span>` +
                `<span class="coef-pct">${pctFmt} %</span>`;
        } else {
            tdC.textContent = fila.label;
        }
        tr.appendChild(tdC);

        // Celdas de datos
        allCols.forEach(rubro => {
            const td  = document.createElement('td');
            const val = data[rubro]?.[fila.clave];
            const esResultado = fila.clase?.includes('resultado') || fila.clase?.includes('contribucion');

            td.className = `td-num${rubro === 'TOTAL' ? ' col-total' : ''}`;

            const signClass = clasePorSigno(val, fila.colorear, esResultado);
            const fmtVal    = formatear(val, fila.tipo);

            // Si el formateo ya devuelve HTML (val-null), usarlo directamente
            if (fmtVal.startsWith('<')) {
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

/* ── Alerta de errores de filtro ─────────────────────────────────────────────── */

function mostrarAlerta(msg) {
    const el = document.getElementById('alertaFiltros');
    el.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> ${msg}`;
    el.style.display = 'flex';
}

function ocultarAlerta() {
    document.getElementById('alertaFiltros').style.display = 'none';
}

/* ── Mostrar/ocultar secciones ──────────────────────────────────────────────── */

function setEstado(estado) {
    // estados: 'inicial' | 'loading' | 'tabla'
    document.getElementById('estadoInicial').style.display   = estado === 'inicial'  ? '' : 'none';
    document.getElementById('loadingSection').style.display  = estado === 'loading'  ? '' : 'none';
    document.getElementById('tablaSection').style.display    = estado === 'tabla'    ? '' : 'none';
    if (estado !== 'tabla') {
        document.getElementById('kpiSection').style.display  = 'none';
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
        document.getElementById('mpProgressBar').style.width = `${Math.round(procesados / total * 100)}%`;
    }

    if (hayError) {
        // Reactivar botón cerrar para que el usuario vea los errores
        document.getElementById('mpBtnCancelar').disabled = false;
        document.getElementById('mpBtnLabel').textContent = 'Reintentar';
        document.getElementById('mpBtnProcesar').disabled = false;
        return;
    }

    // Todo OK → cerrar modal y cargar el reporte
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

        const r    = await fetch(`${CTRL_URL}?action=get_reporte`, { method: 'POST', body: fd });
        const json = await r.json();

        // Períodos faltantes → abrir modal de procesamiento
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

        ultimoReporte = json;

        // KPIs
        actualizarKPIs(json.kpis);

        // Meta info
        const canalLabel  = canal ? ` · Canal: ${canal}` : '';
        const monedaLabel = monedaActual === 'USD'
            ? ` · USD (TCC: ${json.tcc_promedio ? parseFloat(json.tcc_promedio).toFixed(2) : '—'})`
            : '';
        const metaPeriodo = desde === hasta
            ? periodoLabel(desde)
            : `${periodoLabel(desde)} — ${periodoLabel(hasta)}`;
        document.getElementById('tablaTitulo').textContent = 'Informe Económico por Rubro';
        document.getElementById('tablaMeta').textContent   =
            `Período: ${metaPeriodo}${canalLabel}${monedaLabel}`;

        // Base de prorrateo
        const bc    = json.base_calculo;
        const chip  = document.getElementById('baseCalculoChip');
        if (bc && bc.monto) {
            document.getElementById('baseCalculoMonto').textContent  = fmtMoneda(bc.monto);
            document.getElementById('baseCalculoFuente').textContent =
                bc.fuente === 'sinIVA' ? '(Ventas sin IVA)' : '(Venta total — fallback)';

            // Recupero de promociones = base prorrateo − venta total (Rubro 1.8.)
            const ventaTotal = json.kpis?.venta_total ?? 0;
            const diferencia = bc.monto - ventaTotal;
            document.getElementById('bcTooltipBase').innerHTML  = fmtMoneda(bc.monto);
            document.getElementById('bcTooltipDiff').innerHTML  = fmtMoneda(diferencia);
            document.getElementById('bcTooltipVenta').innerHTML = fmtMoneda(ventaTotal);

            chip.style.display = 'flex';
        } else {
            chip.style.display = 'none';
        }

        // Tabla
        renderTabla(json.rubros, json.data);
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

    // Validaciones
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
    if (!ultimoReporte) return;

    const tabla = document.getElementById('tablaReporte');
    if (!tabla) return;

    // Clonar tabla para limpiar HTML de formato
    const clone = tabla.cloneNode(true);

    // Reemplazar innerHTML de cada td por su textContent
    clone.querySelectorAll('td, th').forEach(cell => {
        cell.innerHTML = cell.textContent.trim();
    });

    // Crear workbook con SheetJS si está disponible
    if (typeof XLSX !== 'undefined') {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(clone);
        XLSX.utils.book_append_sheet(wb, ws, 'Rentabilidad por Rubro');

        const desde = document.getElementById('inputDesde').value;
        const hasta = document.getElementById('inputHasta').value;
        XLSX.writeFile(wb, `Rentabilidad_Rubro_${desde}_${hasta}.xlsx`);
    } else {
        // Fallback: exportar como HTML descargable
        const htmlContent = `
            <html><head><meta charset="utf-8">
            <style>table{border-collapse:collapse}td,th{border:1px solid #ccc;padding:6px 10px;font-size:12px}</style>
            </head><body>${clone.outerHTML}</body></html>
        `;
        const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `Rentabilidad_Rubro_${document.getElementById('inputDesde').value}.xls`;
        a.click();
        URL.revokeObjectURL(url);
    }
}

/* ── Auto-formato de inputs de período ─────────────────────────────────────── */

function setupPeriodoInput(id) {
    const inp = document.getElementById(id);
    inp.addEventListener('input', () => {
        // Solo dígitos y guión
        inp.value = inp.value.replace(/[^\d-]/g, '');
        inp.classList.remove('error');
        ocultarAlerta();
    });
    inp.addEventListener('keydown', e => {
        if (e.key === 'Enter') aplicarFiltros();
    });
}

/* ── Init ──────────────────────────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', () => {

    // Precargar período actual (mes actual - 1)
    const hoy   = new Date();
    const mesD  = hoy.getMonth() === 0 ? 12 : hoy.getMonth(); // mes anterior
    const anioD = hoy.getMonth() === 0 ? hoy.getFullYear() - 1 : hoy.getFullYear();
    document.getElementById('inputDesde').value = `${mesD}-${anioD}`;
    document.getElementById('inputHasta').value = `${mesD}-${anioD}`;

    setupPeriodoInput('inputDesde');
    setupPeriodoInput('inputHasta');

    document.getElementById('btnAplicar').addEventListener('click', aplicarFiltros);
    document.getElementById('btnExportar').addEventListener('click', exportarExcel);

    // Modal de procesamiento
    document.getElementById('mpBtnCerrar').addEventListener('click', cerrarModal);
    document.getElementById('mpBtnCancelar').addEventListener('click', cerrarModal);
    document.getElementById('mpBtnProcesar').addEventListener('click', procesarPeriodos);

    // Toggle de moneda: re-carga el reporte si ya hay datos
    document.querySelectorAll('input[name="moneda"]').forEach(radio => {
        radio.addEventListener('change', async () => {
            monedaActual = radio.value;
            if (ultimoReporte !== null) {
                const desde = document.getElementById('inputDesde').value.trim();
                const hasta = document.getElementById('inputHasta').value.trim();
                const canal = document.getElementById('selectCanal').value;
                await cargarReporte(desde, hasta, canal);
            }
        });
    });

    cargarCanales();
    setEstado('inicial');
});
