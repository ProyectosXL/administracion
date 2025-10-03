
<!-- Modal Evolución Costo de Ocupación -->
<div class="modal fade" id="modalEvolucion" tabindex="-1" role="dialog" aria-labelledby="modalEvolucionLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEvolucionLabel">
                    <i class="bi bi-graph-up-arrow"></i> Evolución del Costo de Ocupación
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Comparación:</strong> Últimos 6 meses con datos vs. mismo período año anterior
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <canvas id="chartEvolucion"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="kpi-comparison">
                            <div class="kpi-comparison-header">Período Actual</div>
                            <div class="kpi-comparison-value" id="promedioActual">--</div>
                            <div class="kpi-comparison-label">Promedio últimos 6 meses</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="kpi-comparison">
                            <div class="kpi-comparison-header">Mismo Período Año Anterior</div>
                            <div class="kpi-comparison-value" id="promedioAnterior">--</div>
                            <div class="kpi-comparison-label">Promedio 6 meses anteriores</div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="variacion-anual-badge" id="variacionAnualBadge">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btnExportarGrafico">
                    <i class="bi bi-download"></i> Exportar Gráfico
                </button>
            </div>
        </div>
    </div>
</div>