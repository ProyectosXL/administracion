<?php
/**
 * indicadoresTab.php
 * Componente de la pestaña "Indicadores – Costos de Ocupación".
 * Se incluye desde index.php con:
 *   include 'components/indicadoresTab.php';
 *
 * Variables disponibles desde index.php:
 *   $rangoDefault  → ['desde' => 'YYYY-MM-DD', 'hasta' => 'YYYY-MM-DD']
 */
?>

<!-- CSS específico de esta pestaña -->
<link rel="stylesheet" href="css/indicadores.css">

<div class="indicadores-container" id="indicadoresTab">

    <!-- ── FILTROS ─────────────────────────────────────────────── -->
    <div class="filters-section-indicadores">
        <div class="section-title">
            <i class="bi bi-funnel"></i>
            <span>Período de Análisis</span>
        </div>

        <div class="d-flex align-items-center flex-wrap" style="gap:12px">

            <!-- Píldoras de período rápido -->
            <div class="period-pills" id="indPeriodPills">
                <button class="period-pill" data-months="3">
                    <i class="bi bi-calendar3"></i> 3 meses
                </button>
                <button class="period-pill" data-months="6">
                    <i class="bi bi-calendar3"></i> 6 meses
                </button>
                <button class="period-pill active" data-months="12">
                    <i class="bi bi-calendar3"></i> 12 meses
                </button>
                <button class="period-pill" data-months="0">
                    <i class="bi bi-calendar-range"></i> Personalizado
                </button>
            </div>

            <!-- Rango personalizado (oculto por defecto) -->
            <div id="indCustomRange" style="display:none; gap:10px;" class="d-flex align-items-center flex-wrap">
                <div class="d-flex align-items-center" style="gap:6px">
                    <label for="indFechaDesde" style="font-size:12px;font-weight:600;color:#2c3e50;margin:0">
                        <i class="bi bi-calendar-event" style="color:#3498db"></i> Desde
                    </label>
                    <input type="date" id="indFechaDesde" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($rangoDefault['desde']) ?>"
                           style="height:34px;min-width:145px">
                </div>
                <div class="d-flex align-items-center" style="gap:6px">
                    <label for="indFechaHasta" style="font-size:12px;font-weight:600;color:#2c3e50;margin:0">
                        <i class="bi bi-calendar-event" style="color:#3498db"></i> Hasta
                    </label>
                    <input type="date" id="indFechaHasta" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($rangoDefault['hasta']) ?>"
                           style="height:34px;min-width:145px">
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="indBtnAplicar"
                        style="height:34px;font-weight:600;border-radius:8px;padding:0 16px">
                    <i class="bi bi-search"></i> Aplicar
                </button>
            </div>

        </div>

        <!-- Fila inferior: período activo + botón ayuda -->
        <div class="d-flex align-items-center justify-content-between flex-wrap" style="margin-top:10px;gap:8px">
            <div>
                <span style="font-size:11px;font-weight:600;color:#7f8c8d;text-transform:uppercase;letter-spacing:0.05em">
                    Período:
                </span>
                <span id="indPeriodLabel" style="font-size:12px;font-weight:700;color:#2c3e50;margin-left:4px">
                    —
                </span>
            </div>
            <button type="button" id="btnAyudaIndicadores" class="ind-help-btn" title="Ayuda sobre los indicadores"
                    data-toggle="modal" data-target="#modalAyudaIndicadores">
                <i class="bi bi-info-circle-fill"></i> ¿Cómo leer estos indicadores?
            </button>
        </div>
    </div>

    <!-- ── MODAL DE AYUDA ─────────────────────────────────────── -->
    <div class="modal fade" id="modalAyudaIndicadores" tabindex="-1" role="dialog" aria-labelledby="modalAyudaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content ind-help-modal">
                <div class="modal-header ind-help-modal-header">
                    <div class="d-flex align-items-center" style="gap:12px">
                        <div class="ind-help-icon-wrap">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" id="modalAyudaLabel">Guía de Indicadores — Costo de Ocupación</h5>
                            <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.75)">Cómo interpretar cada métrica y tomar decisiones</p>
                        </div>
                    </div>
                    <button type="button" class="ind-help-close" data-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body ind-help-modal-body">

                    <!-- ── SECCIÓN: Concepto base ── -->
                    <div class="ind-help-section">
                        <div class="ind-help-section-title">
                            <i class="bi bi-bookmark-fill"></i> Concepto base
                        </div>
                        <p class="ind-help-text">
                            El <strong>% Costo de Ocupación</strong> mide qué porción de las ventas netas se destina a pagar
                            los costos fijos del local (alquiler, expensas, fondo de promoción, etc.).
                        </p>
                        <div class="ind-help-formula">
                            % Costo de Ocupación = <strong>Costos totales del local ÷ Venta neta × 100</strong>
                        </div>
                        <p class="ind-help-text" style="margin-top:8px">
                            Cuanto <strong>menor</strong> el porcentaje, mejor: el local genera más ventas en relación a lo que paga por ocupar el espacio.
                        </p>
                    </div>

                    <!-- ── SECCIÓN: Semáforo ── -->
                    <div class="ind-help-section">
                        <div class="ind-help-section-title">
                            <i class="bi bi-traffic-light-fill"></i> Semáforo de clasificación
                        </div>
                        <div class="ind-help-sem-grid">
                            <div class="ind-help-sem-item verde">
                                <div class="ind-help-sem-badge">
                                    <span class="sem-dot verde" style="width:12px;height:12px"></span>
                                    Eficiente
                                </div>
                                <div class="ind-help-sem-range">≤ 15%</div>
                                <p>El local opera con una estructura de costos saludable. No requiere intervención.</p>
                            </div>
                            <div class="ind-help-sem-item amarillo">
                                <div class="ind-help-sem-badge">
                                    <span class="sem-dot amarillo" style="width:12px;height:12px"></span>
                                    En rango con mejora
                                </div>
                                <div class="ind-help-sem-range">15% – 18%</div>
                                <p>El costo es aceptable pero hay margen de mejora. Monitorear evolución.</p>
                            </div>
                            <div class="ind-help-sem-item rojo">
                                <div class="ind-help-sem-badge">
                                    <span class="sem-dot rojo" style="width:12px;height:12px"></span>
                                    Requiere acción
                                </div>
                                <div class="ind-help-sem-range">> 18%</div>
                                <p>El costo supera el límite aceptable. Priorizar renegociación o análisis de ventas.</p>
                            </div>
                        </div>
                    </div>

                    <!-- ── SECCIÓN: KPIs ── -->
                    <div class="ind-help-section">
                        <div class="ind-help-section-title">
                            <i class="bi bi-grid-1x2-fill"></i> Indicadores principales
                        </div>
                        <div class="ind-help-kpi-list">
                            <div class="ind-help-kpi-item">
                                <div class="ind-help-kpi-name">% Costo Cadena</div>
                                <div class="ind-help-kpi-desc">Promedio ponderado por venta neta de todas las sucursales activas en el período seleccionado. Representa la salud global de la red.</div>
                            </div>
                            <div class="ind-help-kpi-item">
                                <div class="ind-help-kpi-name">Variación vs. Año Anterior</div>
                                <div class="ind-help-kpi-desc">Diferencia en puntos porcentuales (pp) respecto al mismo período del año anterior. Un valor <span style="color:#e74c3c;font-weight:700">positivo (+)</span> indica deterioro; uno <span style="color:#27ae60;font-weight:700">negativo (–)</span> indica mejora.</div>
                            </div>
                            <div class="ind-help-kpi-item">
                                <div class="ind-help-kpi-name">Sucursales en Rojo</div>
                                <div class="ind-help-kpi-desc">Cantidad de locales con costo de ocupación superior al 16%. Son los que requieren atención prioritaria.</div>
                            </div>
                        </div>
                    </div>

                    <!-- ── SECCIÓN: Break-even ── -->
                    <div class="ind-help-section">
                        <div class="ind-help-section-title">
                            <i class="bi bi-bullseye"></i> Break-even de ocupación
                        </div>
                        <p class="ind-help-text">
                            Indica la <strong>venta mínima necesaria</strong> para que el costo de ocupación sea exactamente el 12% (objetivo saludable).
                        </p>
                        <div class="ind-help-formula">
                            Break-even = <strong>Costo total del local ÷ 0,15</strong>
                        </div>
                        <div class="ind-help-gap-examples">
                            <div class="ind-help-gap-item danger">
                                <i class="bi bi-arrow-up-circle-fill"></i>
                                <div>
                                    <strong>Brecha negativa</strong> — Las ventas actuales están <em>por debajo</em> del break-even.
                                    El local necesita aumentar ventas para alcanzar el objetivo.
                                </div>
                            </div>
                            <div class="ind-help-gap-item success">
                                <i class="bi bi-check-circle-fill"></i>
                                <div>
                                    <strong>Brecha positiva</strong> — Las ventas actuales <em>superan</em> el break-even.
                                    El local opera por encima del nivel saludable de ocupación.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── SECCIÓN: Gap vs Objetivo ── -->
                    <div class="ind-help-section">
                        <div class="ind-help-section-title">
                            <i class="bi bi-arrow-left-right"></i> Gap vs objetivo 12%
                        </div>
                        <p class="ind-help-text">
                            Diferencia directa entre el costo de ocupación actual y el objetivo del 12%.
                        </p>
                        <div class="ind-help-formula">
                            Gap = <strong>% Costo actual − 15%</strong>
                        </div>
                        <p class="ind-help-text" style="margin-top:8px">
                            Aparece en el panel de detalle de cada sucursal y en las etiquetas del ranking.
                            Un gap de <span style="color:#e74c3c;font-weight:700">+5 pp</span> significa que el local paga 5 puntos porcentuales más de lo deseable.
                        </p>
                    </div>

                    <!-- ── SECCIÓN: Cómo usar ── -->
                    <div class="ind-help-section" style="border-bottom:none">
                        <div class="ind-help-section-title">
                            <i class="bi bi-lightbulb-fill"></i> Flujo de análisis recomendado
                        </div>
                        <ol class="ind-help-steps">
                            <li>Elegí el <strong>período</strong> con las píldoras (3, 6 o 12 meses) o personalizá el rango.</li>
                            <li>Revisá el <strong>KPI Cadena</strong>: si supera el 13%, la red tiene un problema estructural.</li>
                            <li>Identificá los <strong>rojos</strong> en el semáforo (haz clic en el contador para filtrar).</li>
                            <li>Hacé clic en una barra del ranking o en una sucursal del semáforo para ver el <strong>detalle</strong>.</li>
                            <li>Analizá el <strong>break-even</strong>: determina si el problema es de ventas bajas o alquiler alto.</li>
                            <li>Leé el <strong>insight automático</strong>: te dice concretamente cuánto necesita crecer en ventas.</li>
                        </ol>
                    </div>

                </div>
                <div class="modal-footer ind-help-modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ── ESTADO VACÍO ────────────────────────────────────────── -->
    <div class="ind-empty-state" id="indEmptyState">
        <div class="empty-icon">
            <i class="bi bi-graph-up-arrow"></i>
        </div>
        <h4>Cargando indicadores…</h4>
        <p>Seleccioná un período para visualizar el análisis ejecutivo de costos de ocupación.</p>
    </div>

    <!-- ── LOADING ─────────────────────────────────────────────── -->
    <div id="loadingIndicadores" style="display:none">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Cargando…</span>
        </div>
        <p class="mt-2" style="color:#7f8c8d">Calculando indicadores…</p>
    </div>

    <!-- ── CONTENIDO PRINCIPAL (oculto hasta tener datos) ──────── -->
    <div id="indContent" style="display:none">

        <!-- KPI CARDS -->
        <div class="indicadores-kpis" id="indKpiRow">

            <!-- KPI 1: % Costo Cadena -->
            <div class="ind-kpi-card kpi-cadena">
                <i class="bi bi-building kpi-bg-icon"></i>
                <div class="ind-kpi-label">% Costo de Ocupación — Cadena</div>
                <div class="ind-kpi-value" id="kpiCadenaVal">—</div>
                <div class="ind-kpi-sub">
                    <span class="ind-kpi-badge" id="kpiCadenaBadge">—</span>
                    <span class="yoy-inline" id="kpiCadenaYoy"></span>
                </div>
            </div>

            <!-- KPI 2: Variación YoY -->
            <div class="ind-kpi-card kpi-yoy">
                <i class="bi bi-arrow-left-right kpi-bg-icon"></i>
                <div class="ind-kpi-label">Variación vs. Año Anterior — Cadena</div>
                <div class="ind-kpi-value val-purple" id="kpiYoyVal">—</div>
                <div class="ind-kpi-sub">
                    <span class="ind-kpi-badge" id="kpiYoyBadge">—</span>
                    <span style="font-size:11px;color:#7f8c8d" id="kpiYoyPeriod"></span>
                </div>
            </div>

            <!-- KPI 3: Sucursales en rojo -->
            <div class="ind-kpi-card kpi-rojo">
                <i class="bi bi-exclamation-triangle kpi-bg-icon"></i>
                <div class="ind-kpi-label">Sucursales en Rojo <span style="color:#bdc3c7;font-weight:400">(> 18%)</span></div>
                <div class="ind-kpi-value val-danger" id="kpiRojoVal">—</div>
                <div class="ind-kpi-sub">
                    <span class="ind-kpi-badge" id="kpiRojoBadge">—</span>
                    <span style="font-size:11px;color:#7f8c8d">requieren acción inmediata</span>
                </div>
            </div>

        </div>

        <!-- DESTACADO CRÍTICO -->
        <div id="indDestacadoCritico" class="ind-destacado-critico" style="display:none"></div>

        <!-- GRID: RANKING + SEMÁFORO -->
        <div class="indicadores-grid">

            <!-- RANKING -->
            <div class="ind-panel">
                <div class="ind-panel-header">
                    <h3 class="ind-panel-title">
                        <i class="bi bi-bar-chart-horizontal"></i>
                        Ranking de Sucursales — Mayor a Menor % Costo
                    </h3>
                    <div style="display:flex;gap:8px;align-items:center">
                        <span style="font-size:11px;color:#95a5a6">Referencias:</span>
                        <span style="font-size:11px;font-weight:700;color:#27ae60">15% eficiente</span>
                        <span style="font-size:11px;font-weight:700;color:#e74c3c">18% límite</span>
                    </div>
                </div>
                <div class="ind-panel-body" style="padding:12px 16px">
                    <div class="ranking-chart-wrap">
                        <canvas id="indRankingChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- SEMÁFORO SIDEBAR -->
            <div class="ind-panel semaforo-sidebar">
                <div class="ind-panel-header">
                    <h3 class="ind-panel-title">
                        <i class="bi bi-traffic-light"></i>
                        Semáforo de Sucursales
                    </h3>
                </div>

                <!-- Contadores rápidos -->
                <div class="sem-counters">
                    <div class="sem-counter-item" id="semFilterVerde" title="Filtrar verdes">
                        <div class="sem-counter-num verde" id="semCountVerde">0</div>
                        <div class="sem-counter-label">Eficiente</div>
                    </div>
                    <div class="sem-counter-item" id="semFilterAmarillo" title="Filtrar amarillas">
                        <div class="sem-counter-num amarillo" id="semCountAmarillo">0</div>
                        <div class="sem-counter-label">En rango</div>
                    </div>
                    <div class="sem-counter-item" id="semFilterRojo" title="Filtrar rojas">
                        <div class="sem-counter-num rojo" id="semCountRojo">0</div>
                        <div class="sem-counter-label">Requiere acción</div>
                    </div>
                </div>

                <!-- Lista de sucursales -->
                <div class="sem-list" id="semList"></div>

                <!-- Leyenda -->
                <div class="sem-legend-bar">
                    <div class="sem-legend-item">
                        <div class="sem-dot verde"></div> &le; 15%
                    </div>
                    <div class="sem-legend-item">
                        <div class="sem-dot amarillo"></div> 15 – 18%
                    </div>
                    <div class="sem-legend-item">
                        <div class="sem-dot rojo"></div> &gt; 18%
                    </div>
                </div>
            </div>

        </div>

        <!-- DETALLE SUCURSAL SELECCIONADA + BREAKEVEN -->
        <div class="detail-grid" id="indDetailSection" style="display:none">

            <!-- Detalle KPIs sucursal -->
            <div class="ind-panel">
                <div class="ind-panel-header">
                    <h3 class="ind-panel-title" id="detSucTitle">
                        <span class="sem-dot" id="detSucDot"></span>
                        <span id="detSucNombre">—</span>
                    </h3>
                    <span style="font-size:11px;color:#95a5a6" id="detSucPeriod"></span>
                </div>
                <div class="ind-panel-body">
                    <div class="det-row">
                        <span class="det-row-label">% Costo de Ocupación actual</span>
                        <span class="det-row-val" id="detPctActual">—</span>
                    </div>
                    <div class="det-row">
                        <span class="det-row-label">% Costo año anterior (YoY)</span>
                        <span class="det-row-val" id="detPctAnt">—</span>
                    </div>
                    <div class="det-row">
                        <span class="det-row-label">Variación en puntos porcentuales</span>
                        <span class="det-row-val" id="detVarPP">—</span>
                    </div>
                    <div class="det-row">
                        <span class="det-row-label">Variación relativa (%)</span>
                        <span class="det-row-val" id="detVarRel">—</span>
                    </div>
                    <div class="det-row">
                        <span class="det-row-label">Venta neta acumulada</span>
                        <span class="det-row-val info" id="detVenta">—</span>
                    </div>
                    <div class="det-row">
                        <span class="det-row-label">Costo total de ocupación</span>
                        <span class="det-row-val" id="detCosto">—</span>
                    </div>
                    <div class="det-row" style="background:#fff8e1;border-radius:6px;padding:9px 10px;margin-top:4px">
                        <span class="det-row-label" style="font-weight:700;color:#2c3e50">
                            <i class="bi bi-bullseye" style="color:#e67e22"></i>
                            Gap vs objetivo 15%
                        </span>
                        <span class="det-row-val" id="detGapObjetivo">—</span>
                    </div>

                    <!-- Barra de posición (0% → 24%) -->
                    <div class="position-bar-wrap">
                        <div class="position-bar-track">
                            <div class="position-bar-fill" id="detBarFill" style="width:0%"></div>
                        </div>
                        <div class="position-bar-markers">
                            <span>0%</span>
                            <span style="color:#27ae60;font-weight:700">12%</span>
                            <span style="color:#e67e22;font-weight:700">13%</span>
                            <span style="color:#e74c3c;font-weight:700">16%+</span>
                        </div>
                    </div>

                    <!-- Insight automático -->
                    <div id="indInsightBox" class="ind-insight" style="display:none"></div>
                </div>
            </div>

            <!-- Break-even -->
            <div class="ind-panel">
                <div class="ind-panel-header">
                    <h3 class="ind-panel-title">
                        <i class="bi bi-bullseye"></i>
                        Break-even de Ocupación
                    </h3>
                </div>
                <div class="ind-panel-body">
                    <p style="font-size:12px;color:#7f8c8d;margin-bottom:14px">
                        Venta neta mínima necesaria para alcanzar el objetivo del
                        <strong>15%</strong> de costo de ocupación.
                        <br><span style="font-size:11px;color:#bdc3c7">Fórmula: Costo total de ocupación ÷ 15%</span>
                    </p>

                    <!-- Gauge horizontal -->
                    <div class="be-gauge-wrap">
                        <div class="be-track">
                            <div class="be-zones">
                                <div class="be-zone-g" id="beZoneG" style="flex:15"></div>
                                <div class="be-zone-a" style="flex:5"></div>
                                <div class="be-zone-r" id="beZoneR" style="flex:80"></div>
                            </div>
                            <div class="be-needle" id="beNeedle" style="left:50%">
                                <div class="be-needle-label warn" id="beNeedleLabel">—</div>
                                <div class="be-needle-line warn" id="beNeedleLine"></div>
                            </div>
                            <!-- Marca del objetivo (break-even) -->
                            <div style="position:absolute;top:0;height:100%;width:2px;background:#3498db;opacity:0.6;transition:left 0.5s"
                                 id="beTargetLine"></div>
                        </div>
                        <div class="be-axis">
                            <span>$0</span>
                            <span id="beAxisTarget" style="color:#3498db;font-weight:700">—</span>
                            <span id="beAxisMax">—</span>
                        </div>
                    </div>

                    <!-- Resumen numérico -->
                    <div style="margin-top:14px">
                        <div class="det-row">
                            <span class="det-row-label">Venta neta actual</span>
                            <span class="det-row-val info" id="beActual">—</span>
                        </div>
                        <div class="det-row">
                            <span class="det-row-label">Break-even necesario (15%)</span>
                            <span class="det-row-val" id="beTarget" style="color:#3498db;font-weight:700">—</span>
                        </div>
                        <div class="det-row">
                            <span class="det-row-label">Brecha</span>
                            <span class="det-row-val" id="beGap">—</span>
                        </div>
                    </div>

                    <p style="font-size:10px;color:#bdc3c7;text-align:center;margin-top:12px">
                        Objetivo: el 15% de costo es el umbral saludable de ocupación
                    </p>
                </div>
            </div>

        </div>

    </div><!-- /indContent -->

</div><!-- /indicadoresTab -->
