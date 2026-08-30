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
                        <button class="nav-link" id="edicion-tab" data-bs-toggle="tab" data-bs-target="#edicion" type="button" role="tab">
                            <i class="bi bi-arrows-move"></i> Mover Fechas
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
                                <p>El contenedor ingresó al <strong>depósito central</strong>. Esta fecha se toma de Tango y no es editable desde el cronograma.</p>
                            </div>
                        </div>

                        <div class="estado-card estado-distribuido">
                            <div class="estado-icon">🏬</div>
                            <div class="estado-info">
                                <h5>Distribución</h5>
                                <p>La mercadería sale del depósito central hacia los locales. Es el <strong>estado final</strong> del proceso, posterior a la recepción.</p>
                                <p class="small mb-0">Se calcula sola: mientras no haya recepción real es <strong>arribo + 10 días</strong>, y en cuanto Tango confirma la recepción pasa a ser <strong>el día siguiente</strong>. Los días son configurables en Parámetros › Cronograma.</p>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <h6><i class="bi bi-diagram-2"></i> Cómo se agrupan los contenedores</h6>
                            <p class="mb-0 small">Un contenedor con varias órdenes de compra muestra <strong>un solo badge por día y por estado</strong>, no uno por OC. Al mover una fecha, el cambio <strong>impacta a todas las OCs del grupo</strong>.</p>
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
                            <span class="color-legend color-est-emb">Embarque Estimado</span>
                            <span class="color-legend color-emb">Embarque Real</span>
                            <span class="color-legend color-arr-estimado">Arribo Estimado</span>
                            <span class="color-legend color-arr-real">Arribo Real</span>
                            <span class="color-legend color-desp">Despacho Aduana</span>
                            <span class="color-legend color-rec">Recepción</span>
                            <span class="color-legend color-dist">Distribución</span>
                        </div>

                        <div class="alert alert-secondary small">
                            <strong>Los badges del calendario ya no llevan icono de estado:</strong>
                            el color alcanza para identificar el hito, y ese espacio ahora lo usan
                            el alias del proveedor y los iconos de rubro. Los emojis siguen en el
                            timeline del detalle.
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
                    
                    <!-- TAB: Mover Fechas -->
                    <div class="tab-pane fade" id="edicion" role="tabpanel">
                        <h4 class="mb-3">Mover fechas</h4>
                        <p class="text-muted small mb-4">
                            Las fechas se pueden arrastrar de un día a otro dentro del calendario.
                            Nada se guarda hasta confirmar.
                        </p>

                        <h6 class="mb-2">Qué se puede arrastrar</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm align-middle small">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th style="width: 110px;">Arrastrable</th>
                                        <th>Condición</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Embarque estimado</td><td><span class="badge bg-success">Sí</span></td><td>—</td></tr>
                                    <tr><td>Embarque real</td><td><span class="badge bg-success">Sí</span></td><td>—</td></tr>
                                    <tr>
                                        <td>Arribo</td><td><span class="badge bg-success">Sí</span></td>
                                        <td>Si la ETA está <strong>confirmada</strong>, la observación es <strong>obligatoria</strong></td>
                                    </tr>
                                    <tr><td>Despacho de aduana</td><td><span class="badge bg-success">Sí</span></td><td>—</td></tr>
                                    <tr>
                                        <td>Distribución</td><td><span class="badge bg-success">Sí</span></td>
                                        <td>Si está <strong>confirmada</strong>, la observación es <strong>obligatoria</strong>. Moverla a mano evita que se siga recalculando sola</td>
                                    </tr>
                                    <tr class="table-light">
                                        <td>Recepción</td><td><span class="badge bg-secondary">No</span></td>
                                        <td>Viene de Tango, es de sólo lectura</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="mb-2">Antes de confirmar</h6>
                        <p class="small">Al soltar el badge se abre una ventana que muestra:</p>
                        <ul class="small">
                            <li>La fecha de origen y la de destino, con la <strong>diferencia en días</strong>.</li>
                            <li>El <strong>impacto en cascada</strong>: qué fecha de distribución se va a recalcular y a qué valor, y cuáles quedan intactas porque fueron movidas a mano o confirmadas.</li>
                            <li>Las <strong>OCs afectadas</strong>, si el contenedor tiene varias.</li>
                            <li>Un combo de <strong>motivo</strong> y un campo de <strong>observación</strong> de hasta 500 caracteres.</li>
                        </ul>
                        <p class="small">Cancelar no modifica nada.</p>

                        <div class="alert alert-warning small">
                            <h6><i class="bi bi-exclamation-octagon"></i> Movimientos bloqueados</h6>
                            <p class="mb-1">El sistema <strong>rechaza</strong> un movimiento que invierta el orden respecto de una fecha ya <strong>en firme</strong>: por ejemplo un embarque real posterior al arribo, o cualquier fecha posterior a la recepción.</p>
                            <p class="mb-0">Si el conflicto es sólo contra una <strong>estimación</strong>, avisa pero deja continuar.</p>
                        </div>

                        <div class="alert alert-info small">
                            <h6><i class="bi bi-calendar-range"></i> Mover entre meses</h6>
                            <p class="mb-0">El arrastre sólo funciona dentro del mes visible. Para mover una fecha a otro mes, abrí el detalle del contenedor y usá los campos de <strong>Editar fechas</strong>: piden lo mismo y quedan registrados igual.</p>
                        </div>

                        <div class="alert alert-secondary small mb-0">
                            <h6><i class="bi bi-clock-history"></i> Historial</h6>
                            <p class="mb-0">Todo cambio de fecha queda registrado, tanto el hecho desde el cronograma como el hecho desde <strong>Gestión de Despachos</strong>. Se consulta en el detalle del contenedor, con el valor anterior, el nuevo, el motivo, la observación y de dónde vino el cambio.</p>
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

                        <div class="feature-card">
                            <div class="feature-icon">🔎</div>
                            <div class="feature-content">
                                <h5>Filtro por contenedor</h5>
                                <p>El buscador del header encuentra por <strong>contenedor, proveedor o número de OC</strong> y admite varios a la vez.</p>
                                <p class="small mb-1">Con al menos uno seleccionado, el calendario <strong>ignora los filtros de estado</strong> y muestra todos los hitos de esos contenedores: la idea es ver el recorrido completo. Un aviso junto al botón de Filtros lo indica.</p>
                                <p class="small mb-0">Debajo del header aparece la <strong>tira de recorrido</strong>, con todos los hitos y sus fechas. Cada hito es un enlace que lleva el calendario a ese mes, que es lo que resuelve que las fechas de un contenedor caigan en meses distintos.</p>
                            </div>
                        </div>

                        <div class="feature-card">
                            <div class="feature-icon">📏</div>
                            <div class="feature-content">
                                <h5>Densidad de los badges</h5>
                                <p>Dos modos, con el interruptor del header:</p>
                                <ul class="small mb-1">
                                    <li><strong>Compacto</strong> (por defecto): una línea por badge, entran 5 por día.</li>
                                    <li><strong>Cómodo</strong>: dos líneas, suma el nombre del proveedor, entran 3 por día.</li>
                                </ul>
                                <p class="small mb-0">Si un día tiene más eventos de los que entran, aparece un chip <strong>+N más</strong> que abre la lista completa. La preferencia se recuerda.</p>
                            </div>
                        </div>

                        <div class="feature-card">
                            <div class="feature-icon">🏷️</div>
                            <div class="feature-content">
                                <h5>Alias e iconos de rubro en el badge</h5>
                                <p>Cada badge muestra <code>ALIAS CONTENEDOR</code> y hasta <strong>3 iconos de rubro</strong>, ordenados por cantidad y sumando todas las OCs del contenedor. Si hay más rubros, se indica con <code>+N</code>.</p>
                                <p class="small mb-1">Un alias <strong>en gris e itálica</strong> significa que ese proveedor <strong>no tiene alias cargado</strong> y se están mostrando las 2 primeras letras de su nombre. Eso no distingue proveedores parecidos, así que conviene configurarlo.</p>
                                <p class="small mb-0">Se configura con el botón <strong>Alias</strong> del header, sin salir del cronograma. Los iconos de rubro se administran en <strong>Parámetros › Iconos de Rubro</strong>.</p>
                            </div>
                        </div>

                        <div class="feature-card">
                            <div class="feature-icon">🚢</div>
                            <div class="feature-content">
                                <h5>Panel de Próximos Arribos</h5>
                                <p>Se colapsa a una solapa lateral con el chevron de su cabecera. Colapsado, el calendario se ensancha y la solapa muestra la cantidad de arribos pendientes. La preferencia se recuerda.</p>
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
