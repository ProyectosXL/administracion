
<!-- Modal Gestión de Módulos -->
<div class="modal fade" id="modalModulos" tabindex="-1" role="dialog" aria-labelledby="modalModulosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalModulosLabel">
                    <i class="bi bi-gear-fill"></i> Gestión de Módulos - Período: <span id="periodoModulo"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i> 
                    Desde aquí puede eliminar y reprocesar módulos específicos sin afectar los demás.
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tablaModulos">
                        <thead class="thead-dark">
                            <tr>
                                <th>Módulo</th>
                                <th>Registros</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="bodyModulos">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
                
                <div id="logOperaciones" class="mt-3" style="display:none;">
                    <h6>Log de operaciones:</h6>
                    <div class="border p-2 bg-light" style="max-height: 150px; overflow-y: auto;">
                        <ul id="listaLog" class="mb-0 small"></ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>