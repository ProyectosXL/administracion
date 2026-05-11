<div class="modal fade" id="parametrosModal" tabindex="-1" aria-labelledby="parametrosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="parametrosModalLabel"><i class="fa-solid fa-cogs me-2"></i>Gestión de Parámetros de Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="alert-container"></div>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h6 id="form-title" class="card-title mb-3">Agregar/Editar Parámetro</h6>
                        <form id="form-parametros">
                            <input type="hidden" id="param-id" name="id">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label for="param-cod-client" class="form-label">Cód. Cliente</label>
                                    <input type="text" class="form-control form-control-sm" id="param-cod-client" name="cod_client" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="param-medio-pago" class="form-label">Medio Pago Def.</label>
                                    <select class="form-select form-select-sm" id="param-medio-pago" name="medio_pago">
                                        <option value="ECHECK">ECHECK</option>
                                        <option value="TRANSFERENCIA">TRANSFERENCIA</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="param-dias-pp" class="form-label">Días PP Máx.</label>
                                    <input type="number" class="form-control form-control-sm" id="param-dias-pp" name="dias_pp_max" placeholder="15">
                                </div>
                                <div class="col-md-2">
                                    <label for="param-desc-pp" class="form-label">Desc. PP Máx. (%)</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" id="param-desc-pp" name="desc_pp_max" placeholder="8.00">
                                </div>
                                <div class="col-md-2">
                                    <label for="param-cant-sug" class="form-label">Cant. Comprob. Sug.</label>
                                    <input type="number" class="form-control form-control-sm" id="param-cant-sug" name="cant_comprobantes_sug" placeholder="10">
                                </div>
                                <div class="col-md-2">
                                    <label for="param-porc-sug" class="form-label">% Monto Sug.</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" id="param-porc-sug" name="porc_monto_sug" placeholder="100.00">
                                </div>
                                <div class="col-12 text-end">
                                    <button type="button" class="btn btn-secondary btn-sm me-1" id="btn-cancelar-edicion" style="display: none;">Cancelar</button>
                                    <button type="submit" class="btn btn-primary btn-sm px-4">
                                        <i class="fa-solid fa-save me-1"></i><span class="btn-text">Guardar</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tabla-parametros" class="table table-striped table-hover table-bordered" style="width:100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>