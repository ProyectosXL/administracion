/**
 * indicadores.js
 * Módulo de la pestaña "Indicadores – Costos de Ocupación".
 * Depende de: jQuery, Chart.js 3.9.1, chartjs-plugin-datalabels, SweetAlert2.
 * Se inicializa automáticamente cuando se activa la pestaña #indicadores.
 */

/* ─── Estado del módulo ─────────────────────────────────────── */
const IndicadoresModule = {
    chart:          null,
    data:           null,           // último payload del servidor
    selectedId:     null,           // ID de la sucursal seleccionada
    currentFilter:  null,           // 'verde' | 'amarillo' | 'rojo' | null
    fechaDesde:     '',
    fechaHasta:     '',
    initialized:    false,
};

/* ─── Umbrales (deben coincidir con IndicadoresService.php) ─── */
const IND_UMBRAL_VERDE  = 15;   // ≤ 15% → Eficiente
const IND_UMBRAL_ROJO   = 18;   // > 18% → Requiere acción
const IND_OBJETIVO_PCT  = 15;   // 15% objetivo (break-even y gap)
const IND_LIMITE_PCT    = 18;   // línea roja visual en gráfico

/* ─── Bootstrap del módulo ──────────────────────────────────── */
$(document).ready(function () {

    // Activar la pestaña → cargar datos por primera vez
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#indicadores' && !IndicadoresModule.initialized) {
            IndicadoresModule.initialized = true;
            _initPills();
            _loadDefault();
        }
    });

    // Si #indicadores es la pestaña activa por defecto al cargar la página, inicializar directamente
    if ($('#indicadores').hasClass('active') && !IndicadoresModule.initialized) {
        IndicadoresModule.initialized = true;
        _initPills();
        _loadDefault();
    }

    // Botón "Aplicar" del rango personalizado
    $(document).on('click', '#indBtnAplicar', function () {
        const desde = $('#indFechaDesde').val();
        const hasta = $('#indFechaHasta').val();
        if (!desde || !hasta) {
            Swal.fire({ icon: 'warning', title: 'Fechas incompletas', text: 'Seleccioná ambas fechas.', confirmButtonText: 'Entendido' });
            return;
        }
        if (new Date(desde) > new Date(hasta)) {
            Swal.fire({ icon: 'error', title: 'Rango inválido', text: 'La fecha "desde" debe ser anterior a "hasta".', confirmButtonText: 'Entendido' });
            return;
        }
        fetchIndicadores(desde, hasta);
    });

    // Filtros de semáforo (click en contador)
    $(document).on('click', '#semFilterVerde',    () => _toggleSemFilter('verde'));
    $(document).on('click', '#semFilterAmarillo', () => _toggleSemFilter('amarillo'));
    $(document).on('click', '#semFilterRojo',     () => _toggleSemFilter('rojo'));
});

/* ─── Inicializar píldoras de período ───────────────────────── */
function _initPills() {
    $('#indPeriodPills').on('click', '.period-pill', function () {
        const months = parseInt($(this).data('months'));
        $('#indPeriodPills .period-pill').removeClass('active');
        $(this).addClass('active');

        if (months === 0) {
            // personalizado
            $('#indCustomRange').show();
        } else {
            $('#indCustomRange').hide();
            const { desde, hasta } = _calcRange(months);
            fetchIndicadores(desde, hasta);
        }
    });
}

/* ─── Cargar datos con el período default (12 meses) ────────── */
function _loadDefault() {
    $('#indPeriodPills .period-pill[data-months="12"]').trigger('click');
}

/* ─── Calcular rango de fechas para N meses hacia atrás ─────── */
function _calcRange(months) {
    // Hasta: último día del mes anterior al actual
    const hoy    = new Date();
    const hasta  = new Date(hoy.getFullYear(), hoy.getMonth(), 0);   // último día mes anterior
    // Desde: primer día del mes, months-1 atrás desde 'hasta'
    const desde  = new Date(hasta.getFullYear(), hasta.getMonth() - (months - 1), 1);

    return {
        desde: _fmtDate(desde),
        hasta: _fmtDate(hasta),
    };
}

function _fmtDate(d) {
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
}

/* ─── AJAX principal ────────────────────────────────────────── */
function fetchIndicadores(fechaDesde, fechaHasta) {
    IndicadoresModule.fechaDesde = fechaDesde;
    IndicadoresModule.fechaHasta = fechaHasta;

    // Label del período activo
    $('#indPeriodLabel').text(
        _fmtDateHuman(fechaDesde) + ' – ' + _fmtDateHuman(fechaHasta)
    );

    // UI: mostrar loading
    $('#indEmptyState').hide();
    $('#indContent').hide();
    $('#loadingIndicadores').show();

    $.ajax({
        url:      'Controller/indicadoresController.php',
        method:   'POST',
        cache:    false,
        data: {
            accion:      'fetch',
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta,
        },
        dataType: 'json',
        success(response) {
            $('#loadingIndicadores').hide();

            if (response.success) {
                IndicadoresModule.data       = response.data;
                IndicadoresModule.selectedId = null;
                IndicadoresModule.currentFilter = null;
                _render(response.data);
                $('#indContent').fadeIn(250);
            } else {
                _showError(response.message || 'No se pudieron obtener los indicadores.');
            }
        },
        error(xhr) {
            $('#loadingIndicadores').hide();
            let msg = 'Error de conexión con el servidor.';
            try {
                const r = JSON.parse(xhr.responseText);
                if (r.message) msg = r.message;
            } catch (_) {}
            _showError(msg);
        },
    });
}

/* ─── Render completo ───────────────────────────────────────── */
function _render(data) {
    _renderKPIs(data.kpis_cadena, data.fecha_desde_ant, data.fecha_hasta_ant);
    _renderRanking(data.ranking);
    _renderSemaforo(data.sucursales, data.semaforo_counts);
    _renderDestacado(data.ranking);
    $('#indDetailSection').hide();
    $('#indInsightBox').hide();
}

/* ─── Destacado automático (sucursal con mayor costo) ───────── */
function _renderDestacado(ranking) {
    const $el = $('#indDestacadoCritico');
    if (!$el.length || !ranking || !ranking.length) return;
    const peor = ranking[0]; // ranking viene ordenado mayor→menor
    if (peor && peor.pct_actual !== null) {
        $el.show().html(
            `<i class="bi bi-exclamation-triangle-fill"></i> Sucursal con mayor costo de ocupación: <strong>${_esc(peor.nombre)}</strong> — <strong>${peor.pct_actual.toFixed(1)}%</strong>`
        );
    } else {
        $el.hide();
    }
}

/* ─── KPI Cards ─────────────────────────────────────────────── */
function _renderKPIs(kpis, fechaDesdeAnt, fechaHastaAnt) {
    // KPI 1: % Costo Cadena
    const pct    = kpis.pct_actual;
    const semCl  = _semClass(pct);
    const semLbl = _semLabel(pct);

    $('#kpiCadenaVal')
        .text(pct !== null ? pct.toFixed(1) + '%' : '—')
        .removeClass('val-good val-warn val-danger')
        .addClass(semCl === 'verde' ? 'val-good' : semCl === 'amarillo' ? 'val-warn' : 'val-danger');

    $('#kpiCadenaBadge')
        .text(semLbl)
        .removeClass('good warn danger neutral')
        .addClass(semCl === 'verde' ? 'good' : semCl === 'amarillo' ? 'warn' : 'danger');

    // Variación pp inline
    const pp = kpis.variacion_pp;
    if (pp !== null) {
        const dir   = pp > 0 ? 'up' : pp < 0 ? 'down' : 'flat';
        const sign  = pp > 0 ? '+' : '';
        const icon  = pp > 0 ? '▲' : pp < 0 ? '▼' : '■';
        $('#kpiCadenaYoy')
            .html(`<span>${icon}</span> ${sign}${Math.abs(pp).toFixed(1)} pp vs año anterior`)
            .removeClass('up down flat')
            .addClass(dir);
    } else {
        $('#kpiCadenaYoy').text('Sin datos comparativos');
    }

    // KPI 2: Variación YoY
    const vr     = kpis.variacion_relativa;
    const vrSign = vr !== null && vr > 0 ? '+' : '';
    const vrCl   = vr === null ? 'neutral' : vr > 0 ? 'danger' : vr < 0 ? 'good' : 'neutral';
    const vrLbl  = vr === null ? '—' : vr > 5 ? 'Deterioro' : vr > 0 ? 'Leve alza' : vr < 0 ? 'Mejora' : 'Sin cambio';

    $('#kpiYoyVal')
        .text(vr !== null ? vrSign + vr.toFixed(1) + '%' : '—')
        .removeClass('val-good val-warn val-danger val-purple')
        .addClass(vr === null ? 'val-purple' : vr > 0 ? 'val-danger' : 'val-good');

    $('#kpiYoyBadge').text(vrLbl).removeClass('good warn danger neutral info').addClass(vrCl);
    $('#kpiYoyPeriod').text(
        fechaDesdeAnt && fechaHastaAnt
            ? `Comparado con ${_fmtDateHuman(fechaDesdeAnt)} – ${_fmtDateHuman(fechaHastaAnt)}`
            : ''
    );

    // KPI 3: Sucursales en rojo
    const nRojo  = IndicadoresModule.data.semaforo_counts.rojo || 0;
    const nTotal = (IndicadoresModule.data.sucursales || []).filter(s => s.pct_actual !== null).length;
    const pctRed = nTotal > 0 ? Math.round((nRojo / nTotal) * 100) : 0;

    $('#kpiRojoVal').html(`${nRojo}<small style="font-size:18px;color:#95a5a6">/${nTotal}</small>`);
    $('#kpiRojoBadge')
        .text(`${pctRed}% de la red`)
        .removeClass('good warn danger neutral')
        .addClass(nRojo === 0 ? 'good' : nRojo <= 2 ? 'warn' : 'danger');
}

/* ─── Ranking Chart (barras horizontales, Chart.js) ─────────── */
function _renderRanking(ranking) {
    const canvas = document.getElementById('indRankingChart');
    if (!canvas) return;

    if (IndicadoresModule.chart) {
        IndicadoresModule.chart.destroy();
        IndicadoresModule.chart = null;
    }

    // Altura dinámica según cantidad de sucursales
    const barH   = 36;
    const minH   = 300;
    const height = Math.max(minH, ranking.length * barH + 80);
    canvas.parentElement.style.height = height + 'px';

    const labels  = ranking.map(s => {
        const gap = s.pct_actual !== null ? (s.pct_actual - IND_OBJETIVO_PCT) : null;
        const gapStr = gap !== null ? ` (${gap > 0 ? '+' : ''}${gap.toFixed(1)} pp)` : '';
        return s.nombre + gapStr;
    });
    const valores = ranking.map(s => s.pct_actual ?? 0);
    const colors  = valores.map(v =>
        v < IND_UMBRAL_VERDE ? '#27ae60' :
        v < IND_UMBRAL_ROJO  ? '#e67e22' :
                               '#e74c3c'
    );
    const maxVal  = Math.max(...valores, IND_UMBRAL_ROJO + 5);

    IndicadoresModule.chart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: '% Costo de Ocupación',
                data:  valores,
                backgroundColor: colors.map(c => c + 'cc'),  // 80% opacidad
                borderColor:     colors,
                borderWidth:     1.5,
                borderRadius:    4,
                barPercentage:   0.7,
                categoryPercentage: 0.85,
            }],
        },
        options: {
            indexAxis:            'y',
            responsive:           true,
            maintainAspectRatio:  false,
            animation:            { duration: 500, easing: 'easeOutQuart' },
            plugins: {
                legend:      { display: false },
                datalabels: {
                    anchor:    'end',
                    align:     'end',
                    formatter: v => v.toFixed(1) + '%',
                    font:      { weight: 'bold', size: 11 },
                    color:     '#2c3e50',
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const s = IndicadoresModule.data.ranking[ctx.dataIndex];
                            const lines = [` Costo: ${ctx.parsed.x.toFixed(2)}%`];
                            const gap = s.pct_actual !== null ? s.pct_actual - IND_OBJETIVO_PCT : null;
                            if (gap !== null) {
                                const sign = gap > 0 ? '+' : '';
                                lines.push(` Gap vs obj. 12%: ${sign}${gap.toFixed(2)} pp`);
                            }
                            if (s.pct_anterior !== null) {
                                lines.push(` Año ant.: ${s.pct_anterior.toFixed(2)}%`);
                                if (s.variacion_pp !== null) {
                                    const sign = s.variacion_pp > 0 ? '+' : '';
                                    lines.push(` Δ YoY: ${sign}${s.variacion_pp.toFixed(2)} pp`);
                                }
                            }
                            return lines;
                        },
                    },
                },
                annotation: undefined,
            },
            scales: {
                x: {
                    min:   0,
                    max:   maxVal,
                    ticks: {
                        callback: v => v + '%',
                        font:     { size: 11 },
                        color:    '#95a5a6',
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' },
                },
                y: {
                    ticks: { font: { size: 12, weight: '600' }, color: '#2c3e50' },
                    grid:  { display: false },
                },
            },
            // Líneas de referencia vía plugin annotation (si está disponible)
            // Si no está, las dibujamos con afterDraw
        },
        plugins: [
            ChartDataLabels,
            // Plugin inline para líneas de referencia
            {
                id: 'refLines',
                afterDraw(chart) {
                    const { ctx, scales: { x, y } } = chart;
                    const refs = [
                        { val: IND_UMBRAL_VERDE, color: '#27ae60', label: '15% eficiente' },
                        { val: IND_LIMITE_PCT,   color: '#e74c3c', label: '18% límite'   },
                    ];
                    refs.forEach(({ val, color, label }) => {
                        const xPos = x.getPixelForValue(val);
                        ctx.save();
                        ctx.beginPath();
                        ctx.moveTo(xPos, y.top);
                        ctx.lineTo(xPos, y.bottom);
                        ctx.strokeStyle = color;
                        ctx.lineWidth   = 1.5;
                        ctx.setLineDash([5, 4]);
                        ctx.stroke();

                        ctx.fillStyle  = color;
                        ctx.font       = 'bold 10px sans-serif';
                        ctx.textAlign  = 'center';
                        ctx.fillText(label, xPos, y.top - 5);
                        ctx.restore();
                    });
                },
            },
        ],
    });

    // Click en barra → seleccionar sucursal
    canvas.onclick = function (evt) {
        const pts = IndicadoresModule.chart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, false);
        if (pts.length) {
            const idx = pts[0].index;
            const suc = IndicadoresModule.data.ranking[idx];
            _selectSucursal(suc.id);
        }
    };
    canvas.style.cursor = 'pointer';
}

/* ─── Semáforo ──────────────────────────────────────────────── */
function _renderSemaforo(sucursales, counts) {
    // Contadores
    $('#semCountVerde').text(counts.verde || 0);
    $('#semCountAmarillo').text(counts.amarillo || 0);
    $('#semCountRojo').text(counts.rojo || 0);

    _buildSemList(sucursales);
}

function _buildSemList(sucursales) {
    const filter = IndicadoresModule.currentFilter;
    const list   = $('#semList').empty();

    // Ordenar: rojo primero, luego amarillo, luego verde; dentro de cada grupo por pct desc
    const ordered = [...sucursales].sort((a, b) => {
        const order = { rojo: 0, amarillo: 1, verde: 2, sin_datos: 3 };
        const diff  = order[a.semaforo] - order[b.semaforo];
        if (diff !== 0) return diff;
        return (b.pct_actual ?? 0) - (a.pct_actual ?? 0);
    });

    ordered.forEach(s => {
        if (filter && s.semaforo !== filter) return;

        const cl    = s.semaforo === 'sin_datos' ? 'neutral' : s.semaforo;
        const clCss = s.semaforo === 'amarillo'  ? 'amarillo' : s.semaforo;
        const isSelected = s.id === IndicadoresModule.selectedId;

        const item = $(`
            <div class="sem-item${isSelected ? ' selected' : ''}" data-id="${s.id}">
                <div class="sem-dot ${clCss}"></div>
                <div class="sem-item-name">${_esc(s.nombre)}</div>
                <div class="sem-item-pct ${clCss}">
                    ${s.pct_actual !== null ? s.pct_actual.toFixed(1) + '%' : '—'}
                </div>
            </div>
        `);

        item.on('click', () => _selectSucursal(s.id));
        list.append(item);
    });
}

function _toggleSemFilter(color) {
    IndicadoresModule.currentFilter =
        IndicadoresModule.currentFilter === color ? null : color;

    // Resaltar contador activo
    ['Verde', 'Amarillo', 'Rojo'].forEach(c => {
        const isActive = IndicadoresModule.currentFilter === c.toLowerCase();
        $(`#semFilter${c}`).toggleClass('font-weight-bold', isActive);
        $(`#semCount${c}`).css('opacity', isActive ? 1 : 0.5);
    });

    if (IndicadoresModule.data) {
        _buildSemList(IndicadoresModule.data.sucursales);
    }
}

/* ─── Selección de sucursal ─────────────────────────────────── */
function _selectSucursal(id) {
    IndicadoresModule.selectedId = id;

    // Resaltar en la lista
    if (IndicadoresModule.data) {
        _buildSemList(IndicadoresModule.data.sucursales);
    }

    // Buscar en los datos ya cargados (evitar un AJAX extra)
    const suc = (IndicadoresModule.data?.sucursales || []).find(s => s.id === id);
    if (suc) {
        _renderDetalle(suc);
    }
}

/* ─── Panel de detalle + break-even ─────────────────────────── */
function _renderDetalle(suc) {
    $('#indDetailSection').show();

    // Encabezado
    const cl = suc.semaforo === 'sin_datos' ? '' : suc.semaforo;
    const dotColor = cl === 'verde' ? '#27ae60' : cl === 'amarillo' ? '#e67e22' : '#e74c3c';
    $('#detSucDot').css({ background: dotColor, boxShadow: `0 0 6px ${dotColor}` });
    $('#detSucNombre').text(suc.nombre);
    $('#detSucPeriod').text(
        _fmtDateHuman(IndicadoresModule.fechaDesde) + ' – ' +
        _fmtDateHuman(IndicadoresModule.fechaHasta)
    );

    // Valores
    _setDetVal('#detPctActual', suc.pct_actual !== null ? suc.pct_actual.toFixed(2) + '%' : '—',
        cl === 'verde' ? 'good' : cl === 'amarillo' ? 'warn' : 'danger');

    _setDetVal('#detPctAnt', suc.pct_anterior !== null ? suc.pct_anterior.toFixed(2) + '%' : 'Sin datos');

    const pp    = suc.variacion_pp;
    const ppCl  = pp === null ? '' : pp > 0 ? 'danger' : 'good';
    _setDetVal('#detVarPP', pp !== null ? (pp > 0 ? '+' : '') + pp.toFixed(2) + ' pp' : '—', ppCl);

    const vr   = suc.variacion_relativa;
    const vrCl = vr === null ? '' : vr > 0 ? 'danger' : 'good';
    _setDetVal('#detVarRel', vr !== null ? (vr > 0 ? '+' : '') + vr.toFixed(2) + '%' : '—', vrCl);

    _setDetVal('#detVenta', _fmtPesos(suc.venta_neta), 'info');
    _setDetVal('#detCosto', _fmtPesos(suc.costo_total));

    // Barra de posición (mapea 0-24% → 0-100%)
    const pct   = suc.pct_actual ?? 0;
    const barW  = Math.min(100, Math.max(0, (pct / 24) * 100));
    const barBg = cl === 'verde' ? '#27ae60' : cl === 'amarillo' ? '#e67e22' : '#e74c3c';
    $('#detBarFill').css({ width: barW + '%', background: barBg });

    // Gap vs objetivo 12%
    const gap = suc.pct_actual !== null ? suc.pct_actual - IND_OBJETIVO_PCT : null;
    const gapCl  = gap === null ? '' : gap <= 0 ? 'good' : gap <= 4 ? 'warn' : 'danger';
    const gapSign = gap !== null && gap > 0 ? '+' : '';
    _setDetVal('#detGapObjetivo', gap !== null ? `${gapSign}${gap.toFixed(2)} pp` : '—', gapCl);

    // Insight automático
    _renderInsight(suc);

    // Break-even
    _renderBreakeven(suc);

    // Scroll suave hacia el detalle
    $('html, body').animate({ scrollTop: $('#indDetailSection').offset().top - 80 }, 350);
}

function _renderBreakeven(suc) {
    // Break-even calculado con objetivo 12%: CostoTotal / 0.12
    const costoTotal = suc.costo_total || 0;
    const be         = costoTotal > 0 ? costoTotal / (IND_OBJETIVO_PCT / 100) : null;
    const actual     = suc.venta_neta;
    const brecha     = (be && actual) ? actual - be : null;
    const brechaPct  = (be && be !== 0 && brecha !== null) ? (brecha / be) * 100 : null;

    if (!be) {
        $('#beActual, #beTarget, #beGap').text('Sin datos');
        return;
    }

    // Textos
    $('#beActual').text(_fmtPesos(actual));
    $('#beTarget').text(_fmtPesos(be));

    const brechaCl = brecha === null ? '' : brecha >= 0 ? 'good' : 'danger';
    const sign     = brecha !== null && brecha >= 0 ? '+' : '';
    const brechaTxt = brecha !== null
        ? `${sign}${_fmtPesos(brecha)} (${sign}${(brechaPct || 0).toFixed(1)}%)`
        : '—';
    _setDetVal('#beGap', brechaTxt, brechaCl);

    // Gauge: mapear venta actual a 0-100% donde 100% = maxVal
    const maxVal     = Math.max(be * 1.4, actual * 1.2, 1);
    const needlePct  = Math.min(93, Math.max(4, (actual / maxVal) * 100));
    const targetPct  = Math.min(93, Math.max(4, (be     / maxVal) * 100));

    $('#beNeedle').css('left', needlePct + '%');
    $('#beTargetLine').css('left', targetPct + '%');

    const nlClass = brecha !== null && brecha >= 0 ? 'good' : 'warn';
    $('#beNeedleLabel').text(_fmtPesos(actual)).removeClass('good warn danger').addClass(nlClass);
    $('#beNeedleLine').removeClass('good warn danger').addClass(nlClass);

    $('#beAxisTarget').text(_fmtPesos(be));
    $('#beAxisMax').text(_fmtPesos(maxVal));
}

/* ─── Insight automático ────────────────────────────────────── */
function _renderInsight(suc) {
    const $box = $('#indInsightBox');
    if (!$box.length) return;

    const costoTotal = suc.costo_total || 0;
    const actual     = suc.venta_neta || 0;
    const be         = costoTotal > 0 ? costoTotal / (IND_OBJETIVO_PCT / 100) : null;
    const brecha     = be ? actual - be : null;

    if (be === null) {
        $box.hide();
        return;
    }

    $box.show();
    if (brecha < 0) {
        const faltante = Math.abs(brecha);
        $box
            .removeClass('insight-ok')
            .addClass('insight-alert')
            .html(`<i class="bi bi-arrow-up-circle-fill"></i> Esta sucursal necesita aumentar sus ventas en <strong>${_fmtPesos(faltante)}</strong> para alcanzar un nivel saludable de ocupación (${IND_OBJETIVO_PCT}%).`);
    } else {
        $box
            .removeClass('insight-alert')
            .addClass('insight-ok')
            .html(`<i class="bi bi-check-circle-fill"></i> Esta sucursal supera el nivel saludable de ocupación (${IND_OBJETIVO_PCT}%). Ventas por encima del objetivo en <strong>${_fmtPesos(brecha)}</strong>.`);
    }
}

/* ─── Helpers ───────────────────────────────────────────────── */

function _semClass(pct) {
    if (pct === null) return 'sin_datos';
    if (pct < IND_UMBRAL_VERDE) return 'verde';
    if (pct < IND_UMBRAL_ROJO)  return 'amarillo';
    return 'rojo';
}

function _semLabel(pct) {
    const cl = _semClass(pct);
    return cl === 'verde' ? 'Eficiente' : cl === 'amarillo' ? 'En rango con mejora' : cl === 'rojo' ? 'Requiere acción' : 'Sin datos';
}

function _setDetVal(selector, text, cls = '') {
    const el = $(selector);
    el.text(text);
    el.removeClass('good warn danger info');
    if (cls) el.addClass(cls);
}

function _fmtPesos(v) {
    if (v === null || v === undefined) return '—';
    return new Intl.NumberFormat('es-AR', {
        style:                 'currency',
        currency:              'ARS',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(v);
}

function _fmtDateHuman(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-');
    const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return `${parseInt(d)} ${meses[parseInt(m) - 1]} ${y}`;
}

function _esc(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function _showError(msg) {
    $('#indEmptyState').show();
    Swal.fire({
        icon:              'error',
        title:             'Error al cargar indicadores',
        text:              msg,
        confirmButtonText: 'Entendido',
        confirmButtonColor:'#e74c3c',
    });
}
