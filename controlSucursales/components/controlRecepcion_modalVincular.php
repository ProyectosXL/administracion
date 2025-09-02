
<!-- Modal para vincular recibos -->
    <div class="modal fade" id="modalVincularRecibo" tabindex="-1" aria-labelledby="modalVincularReciboLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVincularReciboLabel">Vincular Recibo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="infoComprobanteSeleccionado" class="alert alert-primary" role="alert" style="display: none;">
                        <!-- La información del comprobante se insertará aquí -->
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Buscar por Nro. Comprobante o Leyenda...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="tablaRecibosVincular" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo Comp.</th>
                                    <th>Comprobante</th>
                                    <th>Monto</th>
                                    <th>Leyenda</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán aquí mediante JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>