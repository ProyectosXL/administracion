<!-- Modal con el detalle de la cobranza de un día -->
    <div class="modal fade" id="modalDetalleDia" tabindex="-1" aria-labelledby="modalDetalleDiaLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalleDiaLabel">Detalle del día</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="detalleCargando" class="detalle-cargando">
                        <div class="spinner-border text-primary" role="status"></div>
                        <span>Calculando la cobranza del día...</span>
                    </div>

                    <div id="detalleContenido" style="display: none;">
                        <div id="detalleAlertas"></div>

                        <h6 class="detalle-titulo"><i class="bi bi-wallet2"></i> Por medio de pago</h6>
                        <p class="detalle-ayuda">
                            <strong>Calculado</strong> es lo que da hoy la lógica del SP con los comprobantes;
                            <strong>En tabla</strong> es el $ sistema grabado. Si difieren y el día no está verificado, se corrige con <em>Reprocesar</em>.
                        </p>
                        <table class="tabla-detalle" id="detallePorMedio">
                            <thead>
                                <tr>
                                    <th>Medio de pago</th>
                                    <th class="th-num">Comprobantes</th>
                                    <th class="th-num">Calculado</th>
                                    <th class="th-num">En tabla</th>
                                    <th class="th-num">Diferencia</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                        <h6 class="detalle-titulo"><i class="bi bi-bank"></i> Por cuenta de tesorería</h6>
                        <table class="tabla-detalle" id="detallePorCuenta">
                            <thead>
                                <tr>
                                    <th>Medio de pago</th>
                                    <th>Cuenta</th>
                                    <th>Descripción</th>
                                    <th class="th-num">Comprobantes</th>
                                    <th class="th-num">Importe</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                        <div class="detalle-titulo detalle-titulo-filtro">
                            <h6><i class="bi bi-receipt"></i> Comprobantes</h6>
                            <select id="detalleFiltroMedio" class="form-control form-control-sm"></select>
                        </div>
                        <table class="tabla-detalle" id="detalleComprobantes">
                            <thead>
                                <tr>
                                    <th>Comprobante</th>
                                    <th>Medio de pago</th>
                                    <th>Cuenta</th>
                                    <th class="th-num">Cotización</th>
                                    <th class="th-num">Importe</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4">TOTAL</td>
                                    <td class="td-num" id="detalleTotalComprobantes"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
