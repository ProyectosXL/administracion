<!-- Modal Nuevo Contrato -->
<div class="modal fade" id="nuevoContratoModal" tabindex="-1" aria-labelledby="nuevoContratoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nuevoContratoModalLabel">
                    <i class="fas fa-plus-circle me-2 text-success"></i>Nuevo Contrato
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="contratoAlquilerForm" method="POST">
                    <div class="mb-3">
                        <label for="franquicia" class="form-label">Franquicia</label>
                        <select class="form-select" id="franquicia" name="franquicia" required>
                            <option value="">Seleccione una franquicia</option>
                            <?php foreach ($franquicias as $franquicia): ?>
                                <option value="<?php echo htmlspecialchars($franquicia['NRO_SUCURSAL']); ?>">
                                    <?php echo htmlspecialchars($franquicia['NRO_SUCURSAL']); ?> -
                                    <?php echo htmlspecialchars($franquicia['DESC_SUCURSAL']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h6 class="mt-3">Vigencia</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="fechaDesde" class="form-label">Desde</label>
                            <input type="date" class="form-control" id="fechaDesde" name="fechaDesde" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fechaHasta" class="form-label">Hasta</label>
                            <input type="date" class="form-control" id="fechaHasta" name="fechaHasta" required>
                        </div>
                    </div>

                    <h6 class="mt-3">Contrato Comercial <span class="text-danger">*</span></h6>
                    <div class="mb-3">
                        <div class="file-input-group">
                            <input type="file" class="form-control" id="contratoComercial" name="contratoComercial" accept=".pdf" required>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-primary view-file" title="Ver archivo"><i class="fas fa-eye"></i></button>
                                <button type="button" class="btn btn-danger delete-file" title="Eliminar archivo"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="file-size-info" id="sizeInfoContratoComercial"></div>
                        <div class="compression-status" id="compressionContratoComercial">
                            <i class="fas fa-compress-alt status-icon"></i> El servidor intentará optimizar el archivo
                        </div>
                        <div class="upload-progress">
                            <div class="progress"><div class="progress-bar" role="progressbar"></div></div>
                        </div>
                    </div>

                    <h6 class="mt-3">Contrato Locación</h6>
                    <div class="mb-3">
                        <div class="file-input-group">
                            <input type="file" class="form-control" id="contratoLocacion" name="contratoLocacion" accept=".pdf">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-primary view-file" title="Ver archivo"><i class="fas fa-eye"></i></button>
                                <button type="button" class="btn btn-danger delete-file" title="Eliminar archivo"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="file-size-info" id="sizeInfoContratoLocacion"></div>
                        <div class="compression-status" id="compressionContratoLocacion">
                            <i class="fas fa-compress-alt status-icon"></i> El servidor intentará optimizar el archivo
                        </div>
                        <div class="upload-progress">
                            <div class="progress"><div class="progress-bar" role="progressbar"></div></div>
                        </div>
                    </div>

                    <h6 class="mt-3">Habilitación Local</h6>
                    <div class="mb-3">
                        <div class="file-input-group">
                            <input type="file" class="form-control" id="habilitacion" name="habilitacion" accept=".pdf">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-primary view-file" title="Ver archivo"><i class="fas fa-eye"></i></button>
                                <button type="button" class="btn btn-danger delete-file" title="Eliminar archivo"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="file-size-info" id="sizeInfoHabilitacion"></div>
                        <div class="compression-status" id="compressionHabilitacion">
                            <i class="fas fa-compress-alt status-icon"></i> El servidor intentará optimizar el archivo
                        </div>
                        <div class="upload-progress">
                            <div class="progress"><div class="progress-bar" role="progressbar"></div></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="submit" form="contratoAlquilerForm" class="btn btn-success" id="btnGuardar">
                    <span id="spinner" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display:none;"></span>
                    <i class="fas fa-save me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>
