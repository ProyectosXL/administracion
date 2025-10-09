<!-- Modal de Ayuda -->
<div class="modal fade" id="modalAyuda" tabindex="-1" aria-labelledby="modalAyudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalAyudaLabel">
                    <i class="fas fa-question-circle me-2"></i>Ayuda - Contratos Alquiler Franquicias
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="accordionAyuda">
                    <!-- Sección: Cómo usar la aplicación -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingUso">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUso" aria-expanded="true" aria-controls="collapseUso">
                                <i class="fas fa-play-circle me-2"></i>¿Cómo usar la aplicación?
                            </button>
                        </h2>
                        <div id="collapseUso" class="accordion-collapse collapse show" aria-labelledby="headingUso" data-bs-parent="#accordionAyuda">
                            <div class="accordion-body">
                                <ul>
                                    <li><strong>Buscar contratos:</strong> Use los filtros de sucursal y vigencia para encontrar contratos específicos.</li>
                                    <li><strong>Subir archivos:</strong> Haga clic en "Subir" para adjuntar documentos PDF (contratos comerciales, de locación o habilitaciones).</li>
                                    <li><strong>Ver archivos:</strong> Use el botón "Ver" para visualizar documentos ya cargados.</li>
                                    <li><strong>Eliminar archivos:</strong> Use el botón "Eliminar" para quitar documentos de la base de datos.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Tipos de documentos -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingDocumentos">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDocumentos" aria-expanded="false" aria-controls="collapseDocumentos">
                                <i class="fas fa-file-alt me-2"></i>Tipos de documentos
                            </button>
                        </h2>
                        <div id="collapseDocumentos" class="accordion-collapse collapse" aria-labelledby="headingDocumentos" data-bs-parent="#accordionAyuda">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-handshake text-warning"></i> Contrato Comercial</h6>
                                        <p class="small">Acuerdo comercial entre la empresa y la franquicia.</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-home text-primary"></i> Contrato Locación</h6>
                                        <p class="small">Contrato de alquiler del local o espacio físico.</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-certificate text-success"></i> Habilitación Local</h6>
                                        <p class="small">Documentos de habilitación municipal y permisos legales.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Estados y vigencias -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingEstados">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEstados" aria-expanded="false" aria-controls="collapseEstados">
                                <i class="fas fa-traffic-light me-2"></i>Estados y vigencias
                            </button>
                        </h2>
                        <div id="collapseEstados" class="accordion-collapse collapse" aria-labelledby="headingEstados" data-bs-parent="#accordionAyuda">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-check-circle text-success"></i> Contrato Vigente</h6>
                                        <p class="small">El contrato está dentro del rango de fechas de vigencia.</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-times-circle text-danger"></i> Contrato Vencido</h6>
                                        <p class="small">El contrato está fuera del rango de fechas de vigencia.</p>
                                    </div>
                                </div>
                                <hr>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Importante:</strong> Los contratos próximos a vencer (45 días) aparecerán en el botón "Pronto vencimiento".
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Funciones especiales -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFunciones">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFunciones" aria-expanded="false" aria-controls="collapseFunciones">
                                <i class="fas fa-tools me-2"></i>Funciones especiales
                            </button>
                        </h2>
                        <div id="collapseFunciones" class="accordion-collapse collapse" aria-labelledby="headingFunciones" data-bs-parent="#accordionAyuda">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-exclamation-triangle text-warning"></i> Contratos pendientes</h6>
                                        <p class="small">Muestra sucursales que no tienen contratos vigentes actualmente. Permite descargar listado en Excel.</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-clock text-info"></i> Pronto vencimiento</h6>
                                        <p class="small">Lista contratos que vencen en los próximos 45 días para renovación oportuna.</p>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-search text-primary"></i> Búsqueda rápida</h6>
                                        <p class="small">Use el cuadro de búsqueda en la tabla para filtrar rápidamente por cualquier campo.</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-sort text-secondary"></i> Ordenamiento</h6>
                                        <p class="small">Haga clic en cualquier encabezado de columna para ordenar los resultados.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Consejos y buenas prácticas -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingConsejos">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseConsejos" aria-expanded="false" aria-controls="collapseConsejos">
                                <i class="fas fa-lightbulb me-2"></i>Consejos y buenas prácticas
                            </button>
                        </h2>
                        <div id="collapseConsejos" class="accordion-collapse collapse" aria-labelledby="headingConsejos" data-bs-parent="#accordionAyuda">
                            <div class="accordion-body">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-file-pdf me-2"></i>Archivos PDF</h6>
                                    <ul class="mb-0">
                                        <li>Solo se aceptan archivos en formato PDF</li>
                                        <li>Asegúrese de que los documentos sean legibles</li>
                                        <li>Use nombres descriptivos para los archivos</li>
                                    </ul>
                                </div>
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-calendar-check me-2"></i>Gestión de fechas</h6>
                                    <ul class="mb-0">
                                        <li>Revise regularmente los contratos próximos a vencer</li>
                                        <li>Mantenga actualizados los documentos de habilitación</li>
                                        <li>Use el filtro "Actual" para ver solo contratos vigentes</li>
                                    </ul>
                                </div>
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Precauciones</h6>
                                    <ul class="mb-0">
                                        <li>La eliminación de archivos es permanente</li>
                                        <li>Confirme siempre antes de eliminar documentos</li>
                                        <li>Mantenga copias de seguridad de documentos importantes</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>