<!-- Modal para mostrar detalle de comprobantes con diferencias de IVA -->
<div class="modal fade" id="modalDetalleIVA" tabindex="-1" aria-labelledby="modalDetalleIVALabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetalleIVALabel">
                    <i class="bi bi-receipt-cutoff me-2"></i>
                    Detalle de Comprobantes con Diferencias de IVA
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Información de la sucursal -->
                <div class="alert alert-info mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Sucursal:</strong> <span id="modal-nombre-sucursal"></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Nro. Sucursal:</strong> <span id="modal-nro-sucursal"></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Total Comprobantes:</strong> <span id="modal-total-comprobantes"></span>
                        </div>
                    </div>
                </div>

                <!-- Spinner de carga -->
                <div id="modal-loading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando comprobantes...</p>
                </div>

                <!-- Tabla de comprobantes -->
                <div id="modal-tabla-container">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped table-hover" id="tabla-detalle-comprobantes-iva">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nro. Sucursal</th>
                                    <th>Tipo Comp.</th>
                                    <th>Nro. Comp.</th>
                                    <th>Fecha</th>
                                    <th>IVA Local</th>
                                    <th>IVA Central</th>
                                    <th>Diferencia</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-detalle-comprobantes-iva">
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        No hay datos para mostrar
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th colspan="4" class="text-end">TOTALES:</th>
                                    <th id="modal-total-iva-local">$ 0.00</th>
                                    <th id="modal-total-iva-central">$ 0.00</th>
                                    <th id="modal-total-diferencia">$ 0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cerrar
                </button>
                <button type="button" class="btn btn-success" id="btn-exportar-detalle-iva">
                    <i class="bi bi-file-earmark-excel me-2"></i>Exportar a Excel
                </button>
            </div>
        </div>
    </div>
</div>
