
<!-- Modal de Ayuda Mejorado - Control Recepción Efectivo -->
<div class="modal fade" id="controlRecepcion_modalAyuda" tabindex="-1" aria-labelledby="controlRecepcion_modalAyudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header controlRecepcion_modal-header">
                <h5 class="modal-title" id="controlRecepcion_modalAyudaLabel">
                    <i class="bi bi-question-circle-fill me-2"></i>
                    Ayuda - Control Recepción Efectivo de Sucursales
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body controlRecepcion_modal-body">
                <ul class="nav nav-tabs" id="controlRecepcion_tabsAyuda" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="controlRecepcion_tab-general" data-bs-toggle="tab" data-bs-target="#controlRecepcion_general" type="button" role="tab">
                            <i class="bi bi-info-circle me-1"></i>¿Qué es esta pantalla?
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="controlRecepcion_tab-filtros" data-bs-toggle="tab" data-bs-target="#controlRecepcion_filtros" type="button" role="tab">
                            <i class="bi bi-funnel me-1"></i>Filtros
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="controlRecepcion_tab-estados" data-bs-toggle="tab" data-bs-target="#controlRecepcion_estados" type="button" role="tab">
                            <i class="bi bi-check2-square me-1"></i>Estados y Flujo
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="controlRecepcion_tab-acciones" data-bs-toggle="tab" data-bs-target="#controlRecepcion_acciones" type="button" role="tab">
                            <i class="bi bi-gear me-1"></i>Acciones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="controlRecepcion_tab-iconos" data-bs-toggle="tab" data-bs-target="#controlRecepcion_iconos" type="button" role="tab">
                            <i class="bi bi-eye me-1"></i>Iconos y Símbolos
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="controlRecepcion_tabContent">
                    <!-- Tab General -->
                    <div class="tab-pane fade show active" id="controlRecepcion_general" role="tabpanel">
                        <div class="controlRecepcion_help-section">
                            <h6 class="controlRecepcion_section-title">
                                <i class="bi bi-cash-stack text-primary me-2"></i>Propósito del Sistema
                            </h6>
                            <p class="controlRecepcion_help-text">
                                Esta pantalla permite gestionar y controlar la <strong>recepción de efectivo</strong> proveniente de las sucursales. 
                                Es una herramienta fundamental para el seguimiento de los comprobantes de retiro de fondos (RAF) y su ciclo completo 
                                desde el despacho hasta la vinculación contable.
                            </p>
                            
                            <div class="controlRecepcion_info-box">
                                <h6><i class="bi bi-diagram-3 me-2"></i>Flujo del Proceso:</h6>
                                <ol class="controlRecepcion_process-list">
                                    <li><strong>Despacho:</strong> La sucursal genera el comprobante y lo despacha con precinto</li>
                                    <li><strong>Recepción:</strong> El efectivo llega físicamente a tesorería central</li>
                                    <li><strong>Control:</strong> Se verifica que el monto recibido coincida con el declarado</li>
                                    <li><strong>Vinculación:</strong> Se asocia con el recibo contable correspondiente</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Filtros -->
                    <div class="tab-pane fade" id="controlRecepcion_filtros" role="tabpanel">
                        <div class="controlRecepcion_help-section">
                            <h6 class="controlRecepcion_section-title">
                                <i class="bi bi-calendar-range text-success me-2"></i>Filtros de Fecha
                            </h6>
                            <p class="controlRecepcion_help-text">
                                Use los campos <strong>Desde</strong> y <strong>Hasta</strong> para delimitar el período de consulta. 
                                Por defecto se muestran los registros de la última semana.
                            </p>

                            <h6 class="controlRecepcion_section-title mt-4">
                                <i class="bi bi-filter text-warning me-2"></i>Filtros de Estado
                            </h6>
                            <div class="controlRecepcion_filter-grid">
                                <div class="controlRecepcion_filter-item">
                                    <span class="controlRecepcion_filter-label">Todos</span>
                                    <span class="controlRecepcion_filter-desc">Muestra todos los registros sin filtrar</span>
                                </div>
                                <div class="controlRecepcion_filter-item">
                                    <span class="controlRecepcion_filter-label">Pendiente Recibir</span>
                                    <span class="controlRecepcion_filter-desc">Comprobantes despachados pero aún no recibidos en tesorería</span>
                                </div>
                                <div class="controlRecepcion_filter-item">
                                    <span class="controlRecepcion_filter-label">Pendiente Control</span>
                                    <span class="controlRecepcion_filter-desc">Ya recibidos pero falta verificar los montos</span>
                                </div>
                                <div class="controlRecepcion_filter-item">
                                    <span class="controlRecepcion_filter-label">Pendiente Cargar</span>
                                    <span class="controlRecepcion_filter-desc">Controlados pero falta vincular con el recibo contable</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Estados -->
                    <div class="tab-pane fade" id="controlRecepcion_estados" role="tabpanel">
                        <div class="controlRecepcion_help-section">
                            <h6 class="controlRecepcion_section-title">
                                <i class="bi bi-arrow-right-circle text-info me-2"></i>Flujo de Estados
                            </h6>
                            
                            <div class="controlRecepcion_workflow">
                                <div class="controlRecepcion_workflow-step">
                                    <div class="controlRecepcion_step-number">1</div>
                                    <div class="controlRecepcion_step-content">
                                        <h6>Despachado</h6>
                                        <p>El comprobante fue generado y enviado desde la sucursal. Se muestra la fecha y hora de despacho.</p>
                                        <span class="controlRecepcion_step-indicator">✅ Fecha visible</span>
                                    </div>
                                </div>

                                <div class="controlRecepcion_workflow-step">
                                    <div class="controlRecepcion_step-number">2</div>
                                    <div class="controlRecepcion_step-content">
                                        <h6>Recibido</h6>
                                        <p>El efectivo llegó físicamente a tesorería. Marque el checkbox para confirmar la recepción.</p>
                                        <span class="controlRecepcion_step-indicator">☑️ Checkbox disponible</span>
                                    </div>
                                </div>

                                <div class="controlRecepcion_workflow-step">
                                    <div class="controlRecepcion_step-number">3</div>
                                    <div class="controlRecepcion_step-content">
                                        <h6>Controlado</h6>
                                        <p>Se verificó que el monto físico coincida con el declarado. Solo disponible después de marcar como recibido.</p>
                                        <span class="controlRecepcion_step-indicator">☑️ Checkbox condicional</span>
                                    </div>
                                </div>

                                <div class="controlRecepcion_workflow-step">
                                    <div class="controlRecepcion_step-number">4</div>
                                    <div class="controlRecepcion_step-content">
                                        <h6>Vinculado/Cargado</h6>
                                        <p>Se asoció con el recibo contable correspondiente para completar el proceso.</p>
                                        <span class="controlRecepcion_step-indicator">🔗 Proceso completo</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Acciones -->
                    <div class="tab-pane fade" id="controlRecepcion_acciones" role="tabpanel">
                        <div class="controlRecepcion_help-section">
                            <h6 class="controlRecepcion_section-title">
                                <i class="bi bi-hand-index text-primary me-2"></i>Acciones Disponibles
                            </h6>

                            <div class="controlRecepcion_action-grid">
                                <div class="controlRecepcion_action-item">
                                    <div class="controlRecepcion_action-icon">
                                        <i class="bi bi-box-arrow-in-down text-primary"></i>
                                    </div>
                                    <div class="controlRecepcion_action-content">
                                        <h6>Marcar como Recibido</h6>
                                        <p>Confirma que el efectivo llegó físicamente a tesorería. Una vez marcado, se habilitará el control.</p>
                                        <small class="text-muted">Acción irreversible - Solo para registros no recibidos</small>
                                    </div>
                                </div>

                                <div class="controlRecepcion_action-item">
                                    <div class="controlRecepcion_action-icon">
                                        <i class="bi bi-check-square text-success"></i>
                                    </div>
                                    <div class="controlRecepcion_action-content">
                                        <h6>Marcar como Controlado</h6>
                                        <p>Indica que se verificó el monto físico vs. el declarado. Solo disponible para registros ya recibidos.</p>
                                        <small class="text-muted">Requiere que esté marcado como recibido</small>
                                    </div>
                                </div>

                                <div class="controlRecepcion_action-item">
                                    <div class="controlRecepcion_action-icon">
                                        <i class="bi bi-save text-info"></i>
                                    </div>
                                    <div class="controlRecepcion_action-content">
                                        <h6>Guardar Observaciones</h6>
                                        <p>Permite agregar comentarios o notas sobre el comprobante. Una vez guardadas, no se pueden modificar.</p>
                                        <small class="text-muted">Útil para registrar incidencias o aclaraciones</small>
                                    </div>
                                </div>

                                <div class="controlRecepcion_action-item">
                                    <div class="controlRecepcion_action-icon">
                                        <i class="bi bi-link-45deg text-warning"></i>
                                    </div>
                                    <div class="controlRecepcion_action-content">
                                        <h6>Vincular Recibo</h6>
                                        <p>Asocia el comprobante con un recibo de tesorería. Los montos deben coincidir exactamente para permitir la vinculación.</p>
                                        <small class="text-muted">Solo para comprobantes que aún no están vinculados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Iconos -->
                    <div class="tab-pane fade" id="controlRecepcion_iconos" role="tabpanel">
                        <div class="controlRecepcion_help-section">
                            <h6 class="controlRecepcion_section-title">
                                <i class="bi bi-palette text-info me-2"></i>Guía de Iconos y Estados Visuales
                            </h6>

                            <div class="controlRecepcion_icon-grid">
                                <div class="controlRecepcion_icon-item">
                                    <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                    <span><strong>Proceso Completado</strong><br>El estado correspondiente fue marcado exitosamente</span>
                                </div>

                                <div class="controlRecepcion_icon-item">
                                    <input type="checkbox" class="form-check-input" disabled>
                                    <span><strong>Acción Pendiente</strong><br>Checkbox disponible para marcar el estado</span>
                                </div>

                                <div class="controlRecepcion_icon-item">
                                    <i class="bi bi-box-arrow-in-down text-muted"></i>
                                    <span><strong>Recibido</strong><br>Indica si el efectivo fue recibido físicamente</span>
                                </div>

                                <div class="controlRecepcion_icon-item">
                                    <i class="bi bi-check-square text-muted"></i>
                                    <span><strong>Controlado</strong><br>Muestra si se verificaron los montos</span>
                                </div>

                                <div class="controlRecepcion_icon-item">
                                    <i class="bi bi-cloud-arrow-up-fill text-muted"></i>
                                    <span><strong>Cargado/Vinculado</strong><br>Indica si está asociado con un recibo contable</span>
                                </div>

                                <div class="controlRecepcion_icon-item">
                                    <i class="bi bi-save text-primary"></i>
                                    <span><strong>Guardar</strong><br>Botón para guardar observaciones</span>
                                </div>

                                <div class="controlRecepcion_icon-item">
                                    <i class="bi bi-link-45deg text-info"></i>
                                    <span><strong>Vincular</strong><br>Botón para asociar con recibo de tesorería</span>
                                </div>
                            </div>

                            <div class="controlRecepcion_info-box mt-4">
                                <h6><i class="bi bi-lightbulb me-2"></i>Consejos de Uso:</h6>
                                <ul class="controlRecepcion_tips-list">
                                    <li>Los datos se pueden filtrar escribiendo en el campo de búsqueda de la tabla</li>
                                    <li>Haga clic en los encabezados de columna para ordenar los datos</li>
                                    <li>Use las observaciones para documentar cualquier irregularidad</li>
                                    <li>Los precintos ayudan a verificar la integridad del envío</li>
                                    <li>La vinculación es el paso final para completar el proceso contable</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer controlRecepcion_modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>