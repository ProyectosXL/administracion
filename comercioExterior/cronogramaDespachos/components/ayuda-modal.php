<!-- Modal de Ayuda -->
<div class="modal fade" id="ayudaModal" tabindex="-1" aria-labelledby="ayudaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="ayudaModalLabel">
                        <i class="bi bi-info-circle"></i> Guía del Cronograma de Contenedores
                    </h5>
                    <div class="modal-subtitle">Documentación completa sobre estados, flujo logístico y funcionalidades</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs de navegación -->
                <ul class="nav nav-tabs mb-4" id="ayudaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="estados-tab" data-bs-toggle="tab" data-bs-target="#estados" type="button" role="tab">
                            <i class="bi bi-flag"></i> Estados
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="flujo-tab" data-bs-toggle="tab" data-bs-target="#flujo" type="button" role="tab">
                            <i class="bi bi-arrow-right-circle"></i> Flujo Logístico
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="colores-tab" data-bs-toggle="tab" data-bs-target="#colores" type="button" role="tab">
                            <i class="bi bi-palette"></i> Colores
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="funciones-tab" data-bs-toggle="tab" data-bs-target="#funciones" type="button" role="tab">
                            <i class="bi bi-gear"></i> Funcionalidades
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="ayudaTabsContent">
                    <!-- TAB: Estados -->
                    <div class="tab-pane fade show active" id="estados" role="tabpanel">
                        <h4 class="mb-3">Estados de Contenedores</h4>
                        
                        <div class="estado-card estado-origen">
                            <div class="estado-icon">🏭</div>
                            <div class="estado-info">
                                <h5>En Origen</h5>
                                <p>El contenedor está en el país de origen, esperando fecha de embarque. Aún no se ha confirmado la salida del puerto.</p>
                            </div>
                        </div>
                        
                        <div class="estado-card estado-embarcado">
                            <div class="estado-icon">🚢</div>
                            <div class="estado-info">
                                <h5>Embarcado</h5>
                                <p>El contenedor ha sido cargado en el buque y está en tránsito marítimo. Se ha confirmado la fecha real de embarque.</p>
                            </div>
                        </div>
                        
                        <div class="estado-card estado-arribado">
                            <div class="estado-icon">🛃</div>
                            <div class="estado-info">
                                <h5>Arribo Estimado</h5>
                                <p>Fecha estimada de llegada al puerto de destino basada en los plazos de tránsito marítimo. Aún no confirmado por la naviera.</p>
                            </div>
                        </div>
                        
                        <div class="estado-card estado-arribado arribo-confirmado">
                            <div class="estado-icon">🛃</div>
                            <div class="estado-info">
                                <h5>Arribo Real</h5>
                                <p>El contenedor ha llegado al puerto de destino. Fecha confirmada por la naviera (ETA_CONFIRMADA = 1) y está en proceso de documentación aduanera.</p>
                            </div>
                        </div>
                        
                        <div class="estado-card estado-despachado">
                            <div class="estado-icon">🚚</div>
                            <div class="estado-info">
                                <h5>Despachado</h5>
                                <p>El contenedor ha sido despachado de aduana y está en tránsito terrestre hacia el depósito final.</p>
                            </div>
                        </div>
                        
                        <div class="estado-card estado-recibido">
                            <div class="estado-icon">📦</div>
                            <div class="estado-info">
                                <h5>Recibido</h5>
                                <p>El contenedor ha sido recibido en el depósito. Este es el estado final del proceso logístico.</p>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-4">
                            <h6><i class="bi bi-exclamation-triangle"></i> Fechas Estimadas</h6>
                            <p class="mb-2 small">Cuando un contenedor <strong>no tiene fecha real de embarque confirmada</strong>, todas las fechas mostradas son <strong>estimaciones</strong>.</p>
                            <p class="mb-0 small">Estas fechas estimadas se identifican con:</p>
                            <ul class="mb-0 mt-2 small">
                                <li>Etiqueta <span class="estimado-badge"><i class="bi bi-clock-history"></i> (estimado)</span> en el timeline</li>
                                <li>Iconos con borde punteado</li>
                                <li>Texto en cursiva</li>
                                <li>Símbolo ~ antes de la fecha</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- TAB: Flujo Logístico -->
                    <div class="tab-pane fade" id="flujo" role="tabpanel">
                        <h4 class="mb-3">Flujo del Proceso Logístico</h4>
                        
                        <div class="timeline-demo">
                            <div class="timeline-demo-step">
                                <div class="timeline-demo-icon">🏭</div>
                                <div class="timeline-demo-label">En Origen</div>
                            </div>
                            <div class="timeline-demo-step">
                                <div class="timeline-demo-icon">🚢</div>
                                <div class="timeline-demo-label">Embarcado</div>
                            </div>
                            <div class="timeline-demo-step">
                                <div class="timeline-demo-icon">🛃</div>
                                <div class="timeline-demo-label">Arribo Estimado</div>
                            </div>
                            <div class="timeline-demo-step">
                                <div class="timeline-demo-icon">🛃</div>
                                <div class="timeline-demo-label">Arribo Real</div>
                            </div>
                            <div class="timeline-demo-step">
                                <div class="timeline-demo-icon">🚚</div>
                                <div class="timeline-demo-label">Despachado</div>
                            </div>
                            <div class="timeline-demo-step">
                                <div class="timeline-demo-icon">📦</div>
                                <div class="timeline-demo-label">Recibido</div>
                            </div>
                        </div>
                        
                        <div class="accordion accordion-flush" id="flujoAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#paso1">
                                        <strong>Paso 1:</strong> &nbsp; Preparación en Origen
                                    </button>
                                </h2>
                                <div id="paso1" class="accordion-collapse collapse show" data-bs-parent="#flujoAccordion">
                                    <div class="accordion-body">
                                        <ul class="small">
                                            <li>Se genera la orden de compra</li>
                                            <li>El proveedor prepara la mercadería</li>
                                            <li>Se establece una fecha estimada de embarque</li>
                                            <li>La mercadería se traslada al puerto de origen</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paso2">
                                        <strong>Paso 2:</strong> &nbsp; Embarque
                                    </button>
                                </h2>
                                <div id="paso2" class="accordion-collapse collapse" data-bs-parent="#flujoAccordion">
                                    <div class="accordion-body">
                                        <ul class="small">
                                            <li>El contenedor se carga en el buque</li>
                                            <li>Se confirma la <strong>fecha real de embarque</strong></li>
                                            <li>Desde este momento, las fechas siguientes pasan de estimadas a calculadas</li>
                                            <li>El contenedor inicia su tránsito marítimo (aprox. 45 días)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paso3">
                                        <strong>Paso 3:</strong> &nbsp; Arribo a Puerto
                                    </button>
                                </h2>
                                <div id="paso3" class="accordion-collapse collapse" data-bs-parent="#flujoAccordion">
                                    <div class="accordion-body">
                                        <ul class="small">
                                            <li>El buque llega al puerto de destino</li>
                                            <li>Se descarga el contenedor</li>
                                            <li>Se registra la fecha de arribo al puerto</li>
                                            <li>Comienza el proceso de documentación aduanera</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paso4">
                                        <strong>Paso 4:</strong> &nbsp; Despacho de Aduana
                                    </button>
                                </h2>
                                <div id="paso4" class="accordion-collapse collapse" data-bs-parent="#flujoAccordion">
                                    <div class="accordion-body">
                                        <ul class="small">
                                            <li>El despachante tramita la documentación</li>
                                            <li>Se pagan los impuestos correspondientes</li>
                                            <li>Aduana autoriza la liberación del contenedor</li>
                                            <li>Se registra la fecha de despacho de aduana</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paso5">
                                        <strong>Paso 5:</strong> &nbsp; Recepción en Depósito
                                    </button>
                                </h2>
                                <div id="paso5" class="accordion-collapse collapse" data-bs-parent="#flujoAccordion">
                                    <div class="accordion-body">
                                        <ul class="small">
                                            <li>El contenedor es transportado al depósito</li>
                                            <li>Se descarga y verifica la mercadería</li>
                                            <li>Se registra la fecha de recepción final</li>
                                            <li><strong>Proceso finalizado</strong> - La mercadería está disponible</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TAB: Colores -->
                    <div class="tab-pane fade" id="colores" role="tabpanel">
                        <h4 class="mb-3">Código de Colores</h4>
                        <p class="text-muted mb-4 small">El sistema utiliza colores específicos para identificar rápidamente cada etapa del proceso:</p>
                        
                        <h6 class="mt-3 mb-3">Eventos en Calendario</h6>
                        <div class="mb-4">
                            <span class="color-legend color-est-emb">📅 Embarque Estimado</span>
                            <span class="color-legend color-emb">🚢 Embarque Real</span>
                            <span class="color-legend color-arr-estimado">🛃 Arribo Estimado</span>
                            <span class="color-legend color-arr-real">🛃 Arribo Real</span>
                            <span class="color-legend color-desp">🚚 Despacho Aduana</span>
                            <span class="color-legend color-rec">📦 Recepción</span>
                        </div>
                        
                        <h6 class="mt-4 mb-3">Guía de Interpretación</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body py-2">
                                        <h6 class="small mb-1"><i class="bi bi-circle-fill text-info"></i> Tonos Azules</h6>
                                        <p class="small text-muted mb-0">Planificación y embarque (origen y tránsito marítimo)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body py-2">
                                        <h6 class="small mb-1"><i class="bi bi-circle-fill text-success"></i> Verdes</h6>
                                        <p class="small text-muted mb-0">Arribo al puerto: Verde claro (estimado) y verde oscuro (confirmado)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body py-2">
                                        <h6 class="small mb-1"><i class="bi bi-circle-fill text-warning"></i> Naranja</h6>
                                        <p class="small text-muted mb-0">Proceso aduanero y documentación</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body py-2">
                                        <h6 class="small mb-1"><i class="bi bi-circle-fill" style="color: #AC92EC;"></i> Violeta</h6>
                                        <p class="small text-muted mb-0">Proceso completado - Mercadería recibida</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TAB: Funcionalidades -->
                    <div class="tab-pane fade" id="funciones" role="tabpanel">
                        <h4 class="mb-3">Funcionalidades del Sistema</h4>
                        
                        <div class="feature-card">
                            <div class="feature-icon">📅</div>
                            <div class="feature-content">
                                <h5>Vista de Calendario</h5>
                                <p>Visualiza todos los contenedores en un calendario mensual. Cada evento aparece como una pill de color en la fecha correspondiente.</p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">🗂️</div>
                            <div class="feature-content">
                                <h5>Vista de Tarjetas</h5>
                                <p>Muestra todos los contenedores en formato de tarjetas. Cada card incluye estado actual y próximo hito.</p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">📊</div>
                            <div class="feature-content">
                                <h5>Timeline Detallado</h5>
                                <p>Al hacer click en cualquier contenedor, se abre un modal con el timeline completo del proceso logístico.</p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">⏱️</div>
                            <div class="feature-content">
                                <h5>Identificación de Estimaciones</h5>
                                <p>El sistema diferencia automáticamente entre fechas reales y estimadas con indicadores visuales claros.</p>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <h6 class="small"><i class="bi bi-lightbulb"></i> Consejos de Uso</h6>
                            <ul class="mb-0 small">
                                <li>Usa la <strong>vista de calendario</strong> para planificación cronológica</li>
                                <li>Usa la <strong>vista de tarjetas</strong> para visión general</li>
                                <li>Haz <strong>click en cualquier contenedor</strong> para ver su timeline completo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
