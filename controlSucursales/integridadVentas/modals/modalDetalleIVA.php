<!-- ============================================================
     Modal: Detalle de Diferencias de IVA por Sucursal
     Diseño: "Precision Analytics" (comparte estilos .dv-* con
     modals/modalDetalleVentas.php; las tipografías premium ya
     las carga ese archivo)
     ============================================================ -->

<div class="modal fade" id="modalDetalleIVA" tabindex="-1" aria-labelledby="modalDetalleIVALabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable dv-modal-dialog">
        <div class="modal-content dv-modal-content">

            <!-- ── HEADER ─────────────────────────────────────── -->
            <div class="modal-header dv-modal-header">
                <div class="dv-header-grid-pattern"></div>
                <div class="dv-header-inner">
                    <div class="dv-header-title-group">
                        <div class="dv-header-icon-wrap">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                        <div>
                            <h5 class="modal-title dv-title" id="modalDetalleIVALabel">
                                Detalle de Diferencias de IVA
                            </h5>
                            <p class="dv-subtitle">Comparativa IVA central vs. local por comprobante</p>
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
                <div class="dv-metrics-row" id="dv-metrics-row-iva">
                    <div class="dv-metric-card dv-card-sucursal">
                        <span class="dv-metric-label">Sucursal</span>
                        <span class="dv-metric-value" id="dv-m-sucursal-iva">—</span>
                        <span class="dv-metric-sub" id="dv-m-cod-sucursal-iva">—</span>
                    </div>
                    <div class="dv-metric-card dv-card-central">
                        <span class="dv-metric-label">
                            <i class="bi bi-building me-1"></i>IVA Central
                        </span>
                        <span class="dv-metric-value dv-mono" id="dv-m-central-iva">$ 0,00</span>
                        <span class="dv-metric-sub" id="dv-m-comprobantes-iva">— comprobantes</span>
                    </div>
                    <div class="dv-metric-card dv-card-local">
                        <span class="dv-metric-label">
                            <i class="bi bi-shop me-1"></i>IVA Local
                        </span>
                        <span class="dv-metric-value dv-mono" id="dv-m-local-iva">$ 0,00</span>
                        <span class="dv-metric-sub">Comprobantes con diferencia</span>
                    </div>
                    <div class="dv-metric-card dv-card-diferencia">
                        <span class="dv-metric-label">
                            <i class="bi bi-plusminus me-1"></i>Diferencia
                        </span>
                        <span class="dv-metric-value dv-mono" id="dv-m-diferencia-iva">$ 0,00</span>
                        <span class="dv-metric-sub" id="dv-m-estado-badge-iva">—</span>
                    </div>
                </div>

                <!-- Spinner de carga -->
                <div id="dv-loading-iva" class="dv-loading-state" style="display: none;">
                    <div class="dv-spinner-ring"></div>
                    <p class="dv-loading-text">Consultando comprobantes con diferencias…</p>
                </div>

                <!-- Contenedor de tabla -->
                <div id="dv-tabla-container-iva">

                    <div class="dv-table-toolbar">
                        <div class="dv-toolbar-left">
                            <span class="dv-table-count" id="dv-table-count-iva">0 registros</span>
                        </div>
                        <div class="dv-toolbar-right">
                            <button class="dv-export-btn" id="btn-exportar-detalle-iva-nuevo">
                                <i class="bi bi-file-earmark-excel me-1"></i>Exportar
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive dv-table-wrapper">
                        <table class="dv-table" id="tabla-detalle-iva-comprobantes">
                            <thead>
                                <tr>
                                    <th class="dv-th-tipo">Tipo Comp.</th>
                                    <th class="dv-th-tipo">Nro. Comp.</th>
                                    <th class="dv-th-fecha">Fecha</th>
                                    <th class="dv-th-imp text-end">IVA Local</th>
                                    <th class="dv-th-imp text-end">IVA Central</th>
                                    <th class="dv-th-dif text-end">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-detalle-iva-comprobantes">
                                <tr class="dv-empty-row">
                                    <td colspan="6">
                                        <div class="dv-empty-state">
                                            <i class="bi bi-inbox dv-empty-icon"></i>
                                            <p>No hay datos para mostrar</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot id="tfoot-detalle-iva" style="display: none;">
                                <tr class="dv-tfoot-row">
                                    <td colspan="3" class="dv-tfoot-label">TOTALES</td>
                                    <td class="dv-tfoot-imp text-end dv-mono" id="dv-tot-local-iva">$ 0,00</td>
                                    <td class="dv-tfoot-imp text-end dv-mono" id="dv-tot-central-iva">$ 0,00</td>
                                    <td class="dv-tfoot-dif text-end dv-mono" id="dv-tot-diferencia-iva">$ 0,00</td>
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
