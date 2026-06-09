
<!-- Modal Gestión -->
<div class="modal fade" id="modalModulos" tabindex="-1" role="dialog" aria-labelledby="modalModulosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalModulosLabel">
                    <i class="bi bi-gear-fill"></i> Gestión
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <ul class="nav nav-tabs" id="tabsGestion" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-modulos-link" data-toggle="tab" href="#paneModulos" role="tab" aria-controls="paneModulos" aria-selected="true">Módulos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-centros-link" data-toggle="tab" href="#paneCentros" role="tab" aria-controls="paneCentros" aria-selected="false">Centros de Costo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-prorrateo-link" data-toggle="tab" href="#paneProrrateo" role="tab" aria-controls="paneProrrateo" aria-selected="false">Métodos de Prorrateo</a>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="tabsGestionContent">

                    <!-- Tab Módulos -->
                    <div class="tab-pane fade show active" id="paneModulos" role="tabpanel" aria-labelledby="tab-modulos-link">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill"></i>
                            Período: <span id="periodoModulo"></span> &mdash;
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

                    <!-- Tab Centros de Costo -->
                    <div class="tab-pane fade" id="paneCentros" role="tabpanel" aria-labelledby="tab-centros-link">

                        <!-- Fila de alta -->
                        <div class="form-row mb-3 align-items-end">
                            <div class="col">
                                <label class="mb-1 small font-weight-bold">Auxiliar</label>
                                <select id="selectAuxiliarNuevo" class="form-control" style="width:100%">
                                    <option value="">-- Seleccione auxiliar --</option>
                                </select>
                            </div>
                            <div class="col">
                                <label class="mb-1 small font-weight-bold">Centro de costo</label>
                                <input type="text" id="inputCentroCosto" class="form-control" placeholder="Centro de costo" maxlength="20">
                            </div>
                            <div class="col">
                                <label class="mb-1 small font-weight-bold">Sector</label>
                                <input type="text" id="inputSectorCC" class="form-control" placeholder="Sector" maxlength="20">
                            </div>
                            <div class="col">
                                <label class="mb-1 small font-weight-bold">Nro. Sucursal</label>
                                <input type="number" id="inputNumSucCC" class="form-control" placeholder="Nro. Sucursal" min="0" step="1">
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-success" onclick="agregarCentroCosto()">Agregar</button>
                            </div>
                        </div>

                        <!-- Filtro de estado -->
                        <div class="form-row mb-2 align-items-center">
                            <div class="col-auto">
                                <label class="mb-0">Estado:</label>
                            </div>
                            <div class="col-auto">
                                <select id="filtroEstadoCC" class="form-control form-control-sm" onchange="cargarCentrosCosto()">
                                    <option value="activos">Activos</option>
                                    <option value="inactivos">Inactivos</option>
                                    <option value="todos">Todos</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tabla centros de costo -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm" id="tablaCentrosCosto">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>COD_AUXILIAR</th>
                                        <th>DESC_AUXILIAR</th>
                                        <th>CENTRO_COSTO</th>
                                        <th>SECTOR</th>
                                        <th>NRO.SUC</th>
                                        <th>ESTADO</th>
                                        <th>ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyCentrosCosto">
                                    <!-- Se llena dinámicamente -->
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <!-- Tab Métodos de Prorrateo -->
                    <div class="tab-pane fade" id="paneProrrateo" role="tabpanel" aria-labelledby="tab-prorrateo-link">

                        <!-- Filtro de estado -->
                        <div class="form-row mb-2 align-items-center">
                            <div class="col-auto">
                                <label class="mb-0">Estado:</label>
                            </div>
                            <div class="col-auto">
                                <select id="filtroEstadoProrrateo" class="form-control form-control-sm" onchange="cargarMetodosProrrateo()">
                                    <option value="activos">Activos</option>
                                    <option value="inactivos">Inactivos</option>
                                    <option value="todos">Todos</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tabla métodos de prorrateo -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm" id="tablaMetodosProrrateo">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>COD_PRORRATEO</th>
                                        <th>DESC_PRORRATEO</th>
                                        <th>ESTADO</th>
                                        <th>ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyMetodosProrrateo">
                                    <!-- Se llena dinámicamente -->
                                </tbody>
                            </table>
                        </div>

                    </div>

                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
