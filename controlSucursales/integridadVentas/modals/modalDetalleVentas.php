<!-- ============================================================
     Modal: Detalle de Ventas por Sucursal
     Diseño: "Precision Analytics" — Financial Forensics Dark UI
     ============================================================ -->

<!-- Tipografía premium para el modal -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,300;0,400;0,500;1,400&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">

<div class="modal fade" id="modalDetalleVentas" tabindex="-1" aria-labelledby="modalDetalleVentasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable dv-modal-dialog">
        <div class="modal-content dv-modal-content">

            <!-- ── HEADER ─────────────────────────────────────── -->
            <div class="modal-header dv-modal-header">
                <div class="dv-header-grid-pattern"></div>
                <div class="dv-header-inner">
                    <div class="dv-header-title-group">
                        <div class="dv-header-icon-wrap">
                            <i class="bi bi-bar-chart-steps"></i>
                        </div>
                        <div>
                            <h5 class="modal-title dv-title" id="modalDetalleVentasLabel">
                                Detalle de Ventas
                            </h5>
                            <p class="dv-subtitle">Comparativa central vs. local por tipo de comprobante</p>
                        </div>
                    </div>
                    <button type="button" class="dv-close-btn" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="dv-header-accent-line"></div>
            </div>

            <!-- ── BODY ───────────────────────────────────────── -->
            <div class="modal-body dv-modal-body">

                <!-- Tarjetas de resumen -->
                <div class="dv-metrics-row" id="dv-metrics-row">
                    <div class="dv-metric-card dv-card-sucursal">
                        <span class="dv-metric-label">Sucursal</span>
                        <span class="dv-metric-value" id="dv-m-sucursal">—</span>
                        <span class="dv-metric-sub" id="dv-m-cod-sucursal">—</span>
                    </div>
                    <div class="dv-metric-card dv-card-central">
                        <span class="dv-metric-label">
                            <i class="bi bi-building me-1"></i>Importe Central
                        </span>
                        <span class="dv-metric-value dv-mono" id="dv-m-central">$ 0,00</span>
                        <span class="dv-metric-sub" id="dv-m-periodo">—</span>
                    </div>
                    <div class="dv-metric-card dv-card-local">
                        <span class="dv-metric-label">
                            <i class="bi bi-shop me-1"></i>Importe Local
                        </span>
                        <span class="dv-metric-value dv-mono" id="dv-m-local">$ 0,00</span>
                        <span class="dv-metric-sub" id="dv-m-registros">— registros</span>
                    </div>
                    <div class="dv-metric-card dv-card-diferencia">
                        <span class="dv-metric-label">
                            <i class="bi bi-plusminus me-1"></i>Diferencia
                        </span>
                        <span class="dv-metric-value dv-mono" id="dv-m-diferencia">$ 0,00</span>
                        <span class="dv-metric-sub" id="dv-m-estado-badge">—</span>
                    </div>
                </div>

                <!-- Spinner de carga -->
                <div id="dv-loading" class="dv-loading-state" style="display: none;">
                    <div class="dv-spinner-ring"></div>
                    <p class="dv-loading-text">Consultando base de datos remota…</p>
                </div>

                <!-- Contenedor de tabla -->
                <div id="dv-tabla-container">

                    <!-- Barra de filtro rápido -->
                    <div class="dv-table-toolbar">
                        <div class="dv-toolbar-left">
                            <span class="dv-table-count" id="dv-table-count">0 registros</span>
                            <span class="dv-toolbar-sep">|</span>
                            <button class="dv-filter-btn active" data-filter="all">Todos</button>
                            <button class="dv-filter-btn" data-filter="DIFIERE">
                                <span class="dv-dot dv-dot-error"></span> Con diferencia
                            </button>
                            <button class="dv-filter-btn" data-filter="OK">
                                <span class="dv-dot dv-dot-ok"></span> Sin diferencia
                            </button>
                        </div>
                        <div class="dv-toolbar-right">
                            <button class="dv-export-btn" id="btn-exportar-detalle-ventas">
                                <i class="bi bi-file-earmark-excel me-1"></i>Exportar
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive dv-table-wrapper">
                        <table class="dv-table" id="tabla-detalle-ventas">
                            <thead>
                                <tr>
                                    <th class="dv-th-tipo">Tipo Comp.</th>
                                    <th class="dv-th-fecha">Fecha</th>
                                    <th class="dv-th-num text-end">Cant. Central</th>
                                    <th class="dv-th-imp text-end">Importe Central</th>
                                    <th class="dv-th-num text-end">Cant. Local</th>
                                    <th class="dv-th-imp text-end">Importe Local</th>
                                    <th class="dv-th-dif text-end">Diferencia</th>
                                    <th class="dv-th-est text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-detalle-ventas">
                                <tr class="dv-empty-row">
                                    <td colspan="8">
                                        <div class="dv-empty-state">
                                            <i class="bi bi-inbox dv-empty-icon"></i>
                                            <p>No hay datos para mostrar</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot id="tfoot-detalle-ventas" style="display: none;">
                                <tr class="dv-tfoot-row">
                                    <td colspan="2" class="dv-tfoot-label">TOTALES</td>
                                    <td class="dv-tfoot-num text-end dv-mono" id="dv-tot-cant-central">0</td>
                                    <td class="dv-tfoot-imp text-end dv-mono" id="dv-tot-central">$ 0,00</td>
                                    <td class="dv-tfoot-num text-end dv-mono" id="dv-tot-cant-local">0</td>
                                    <td class="dv-tfoot-imp text-end dv-mono" id="dv-tot-local">$ 0,00</td>
                                    <td class="dv-tfoot-dif text-end dv-mono" id="dv-tot-diferencia">$ 0,00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
            <!-- ── /BODY ──────────────────────────────────────── -->

            <!-- ── FOOTER ─────────────────────────────────────── -->
            <div class="modal-footer dv-modal-footer">
                <button type="button" class="dv-btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cerrar
                </button>
            </div>

        </div>
    </div>
</div>
