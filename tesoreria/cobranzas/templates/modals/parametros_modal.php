<div class="modal fade" id="parametrosModal" tabindex="-1" aria-labelledby="parametrosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="parametrosModalLabel">
                    <i class="fa-solid fa-cogs me-2"></i>Gestión de Parámetros de Descuento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <!-- Contenedor para alertas dinámicas -->
                <div id="alert-container"></div>
                
                <!-- Formulario para agregar/editar dentro de una tarjeta -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h6 id="form-title" class="card-title mb-3">Agregar Nuevo Parámetro</h6>
                        <form id="form-parametros">
                            <input type="hidden" id="param-id" name="id">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label for="param-cod-client" class="form-label">Cód. Cliente</label>
                                    <input type="text" class="form-control form-control-sm" id="param-cod-client" name="cod_client" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="param-desc-compra" class="form-label">Desc. Compra</label>
                                    <input type="number" step="0.0001" class="form-control form-control-sm" id="param-desc-compra" name="desc_compra" placeholder="0.0400">
                                </div>
                                <div class="col-md-2">
                                    <label for="param-desc-flete" class="form-label">Desc. Flete</label>
                                    <input type="number" step="0.0001" class="form-control form-control-sm" id="param-desc-flete" name="desc_flete" placeholder="0.0200">
                                </div>
                                <div class="col-md-2">
                                    <label for="param-dias-pp" class="form-label">Días PP</label>
                                    <input type="number" class="form-control form-control-sm" id="param-dias-pp" name="dias_pp" placeholder="15">
                                </div>
                                 <div class="col-md-2">
                                    <label for="param-desc-pp" class="form-label">Desc. PP</label>
                                    <input type="number" step="0.0001" class="form-control form-control-sm" id="param-desc-pp" name="desc_pp" placeholder="0.0800">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fa-solid fa-save me-1"></i><span class="btn-text">Guardar</span>
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm w-100 mt-1" id="btn-cancelar-edicion" style="display: none;">Cancelar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de parámetros existentes -->
                <div class="table-responsive">
                    <table id="tabla-parametros" class="table table-striped table-hover table-bordered" style="width:100%">
                        <!-- El contenido se cargará con AJAX -->
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>