<?php
/**
 * tabla_reporte.php
 * Contenedor de la tabla del reporte (el contenido se renderiza vía JS)
 */
?>
<!-- KPI Cards — montos principales -->
<section id="kpiSection" class="kpi-section" style="display:none;">
    <div class="kpi-grid">
        <div class="kpi-card" id="kpiVenta">
            <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Venta Total</div>
                <div class="kpi-value" id="kpiVentaVal">—</div>
            </div>
        </div>
        <div class="kpi-card kpi-accent" id="kpiRB">
            <div class="kpi-icon"><i class="bi bi-bar-chart-line"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Resultado Bruto</div>
                <div class="kpi-value" id="kpiRBVal">—</div>
                <div class="kpi-sub" id="kpiRBPct">—</div>
            </div>
        </div>
        <div class="kpi-card kpi-accent" id="kpiRO">
            <div class="kpi-icon"><i class="bi bi-layers"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Resultado Operativo</div>
                <div class="kpi-value" id="kpiROVal">—</div>
                <div class="kpi-sub" id="kpiROPct">—</div>
            </div>
        </div>
        <div class="kpi-card kpi-accent" id="kpiRE">
            <div class="kpi-icon"><i class="bi bi-award"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Resultado Explotación</div>
                <div class="kpi-value" id="kpiREVal">—</div>
                <div class="kpi-sub" id="kpiREPct">—</div>
            </div>
        </div>
    </div>
</section>

<!-- KPI Cards — estructura de gastos (% sobre venta) -->
<section id="kpiPctSection" class="kpi-section kpi-pct-section" style="display:none;">
    <div class="kpi-pct-header">
        <i class="bi bi-pie-chart"></i>
        Estructura de gastos sobre venta
    </div>
    <div class="kpi-grid kpi-grid-3">
        <div class="kpi-card kpi-pct" id="kpiGastosCom">
            <div class="kpi-icon kpi-icon-pct"><i class="bi bi-cart-check"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Gastos Comercialización</div>
                <div class="kpi-value kpi-value-pct" id="kpiGastosComVal">—</div>
            </div>
        </div>
        <div class="kpi-card kpi-pct" id="kpiGastosOp">
            <div class="kpi-icon kpi-icon-pct"><i class="bi bi-gear"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Gastos Operativos</div>
                <div class="kpi-value kpi-value-pct" id="kpiGastosOpVal">—</div>
                <div class="kpi-sub kpi-sub-mini" id="kpiGastosOpSub">—</div>
            </div>
        </div>
        <div class="kpi-card kpi-pct" id="kpiGastosEst">
            <div class="kpi-icon kpi-icon-pct"><i class="bi bi-building"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Gastos Estructura</div>
                <div class="kpi-value kpi-value-pct" id="kpiGastosEstVal">—</div>
            </div>
        </div>
    </div>
</section>

<!-- Estado vacío / inicial -->
<section id="estadoInicial" class="estado-inicial">
    <div class="estado-icon"><i class="bi bi-bar-chart-steps"></i></div>
    <h3>Reporte de Rentabilidad por Rubro</h3>
    <p>Seleccioná el período y hacé clic en <strong>Aplicar</strong> para generar el informe.</p>
</section>

<!-- Spinner de carga -->
<section id="loadingSection" class="loading-section" style="display:none;">
    <div class="spinner-ring"></div>
    <p>Calculando rentabilidad…</p>
</section>

<!-- Tabla del reporte -->
<section id="tablaSection" class="tabla-section" style="display:none;">
    <div class="tabla-header-meta">
        <div class="tabla-titulo" id="tablaTitulo">Informe Económico por Rubro</div>
        <div class="tabla-header-right">
            <div class="tabla-meta" id="tablaMeta"></div>
            <div class="base-calculo-chip" id="baseCalculoChip" style="display:none;">
                <i class="bi bi-calculator"></i>
                <span>Base prorrateo:</span>
                <strong id="baseCalculoMonto">—</strong>
                <span id="baseCalculoFuente" class="base-calculo-fuente"></span>
                <span class="bc-tooltip-wrap">
                    <i class="bi bi-info-circle bc-info-icon"></i>
                    <div class="bc-tooltip" id="bcTooltip">
                        <span class="bc-tooltip-title">Composición de la base de prorrateo</span>
                        <div class="bc-calc">
                            <div class="bc-calc-row">
                                <span class="bc-calc-label">Base prorrateo</span>
                                <strong class="bc-calc-val" id="bcTooltipBase">—</strong>
                            </div>
                            <div class="bc-calc-row bc-calc-minus">
                                <span class="bc-calc-label"><span class="bc-calc-op">−</span> Recupero de promociones <em>(Rubro 1.8.)</em></span>
                                <strong class="bc-calc-val" id="bcTooltipDiff">—</strong>
                            </div>
                            <div class="bc-calc-row bc-calc-result">
                                <span class="bc-calc-label"><span class="bc-calc-op">=</span> Venta total</span>
                                <strong class="bc-calc-val" id="bcTooltipVenta">—</strong>
                            </div>
                        </div>
                    </div>
                </span>
            </div>
        </div>
    </div>
    <div class="tabla-scroll-wrapper">
        <table id="tablaReporte" class="tabla-reporte">
            <thead id="tablaHead"></thead>
            <tbody id="tablaBody"></tbody>
        </table>
    </div>
</section>
