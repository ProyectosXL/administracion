<!-- /novedades/components/modal_empleado.php -->
<div class="modal fade" id="modalBuscarEmpleado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-search me-2"></i>
                    Buscar Empleado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <label for="buscar-legajo" class="form-label">Número de Legajo</label>
                        <input type="number" class="form-control" id="buscar-legajo" 
                               placeholder="Ingrese el número de legajo">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100" onclick="buscarEmpleado()">
                            <i class="fas fa-search me-1"></i>
                            Buscar
                        </button>
                    </div>
                </div>
                
                <div id="resultado-empleado" class="mt-3" style="display: none;">
                    <div class="alert alert-success">
                        <h6 class="alert-heading">
                            <i class="fas fa-user-check me-1"></i>
                            Empleado Encontrado
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Nombre:</strong> <span id="empleado-nombre"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Apellido:</strong> <span id="empleado-apellido"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Legajo:</strong> <span id="empleado-legajo"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Sucursal:</strong> <span id="empleado-sucursal"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="error-empleado" class="mt-3" style="display: none;">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <span id="mensaje-error"></span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btn-seleccionar-empleado" 
                        style="display: none;" onclick="seleccionarEmpleado()">
                    <i class="fas fa-check me-1"></i>
                    Seleccionar
                </button>
            </div>
        </div>
    </div>
</div>