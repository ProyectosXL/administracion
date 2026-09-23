<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda - Cronograma de Contenedores</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --color-est-emb: #5BC0DE;
            --color-emb: #4A89DC;
            --color-arr: #8CC152;
            --color-desp: #F5A623;
            --color-rec: #AC92EC;
            --color-origen: #95A5A6;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #F5F7FA;
            padding: 20px;
        }
        
        .ayuda-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .ayuda-header {
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .ayuda-header h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .ayuda-header p {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
        }
        
        .ayuda-body {
            padding: 30px;
        }
        
        .estado-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 4px solid;
        }
        
        .estado-icon {
            font-size: 32px;
            min-width: 40px;
            text-align: center;
        }
        
        .estado-info h5 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .estado-info p {
            margin: 0;
            font-size: 14px;
            color: #6c757d;
        }
        
        .estado-origen { background: #f8f9fa; border-left-color: var(--color-origen); }
        .estado-embarcado { background: #e3f2fd; border-left-color: var(--color-emb); }
        .estado-arribado { background: #e8f5e9; border-left-color: var(--color-arr); }
        .estado-despachado { background: #fff3e0; border-left-color: var(--color-desp); }
        .estado-recibido { background: #f3e5f5; border-left-color: var(--color-rec); }
        
        .timeline-demo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
            position: relative;
        }
        
        .timeline-demo::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 60px;
            right: 60px;
            height: 3px;
            background: #dee2e6;
            transform: translateY(-50%);
        }
        
        .timeline-demo-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 1;
        }
        
        .timeline-demo-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 3px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .timeline-demo-label {
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            color: #6c757d;
        }
        
        .color-legend {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            margin-right: 12px;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
            color: white;
        }
        
        .color-est-emb { background: var(--color-est-emb); }
        .color-emb { background: var(--color-emb); }
        .color-arr { background: var(--color-arr); }
        .color-desp { background: var(--color-desp); }
        .color-rec { background: var(--color-rec); }
        
        .feature-card {
            display: flex;
            gap: 16px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .feature-icon {
            font-size: 32px;
            color: #667eea;
        }
        
        .feature-content h5 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .feature-content p {
            margin: 0;
            font-size: 14px;
            color: #6c757d;
        }
        
        .accordion-button:not(.collapsed) {
            background-color: #667eea;
            color: white;
        }
        
        .estimado-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #fff3cd;
            border: 1px dashed #ffc107;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-style: italic;
        }
        
        .btn-volver {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-volver:hover {
            background: #5568d3;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="ayuda-container">
        <div class="ayuda-header">
            <h1><i class="bi bi-info-circle"></i> Guía del Cronograma de Contenedores</h1>
            <p>Documentación completa sobre estados, flujo logístico y funcionalidades</p>
        </div>
        
        <div class="ayuda-body">
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
                    <h3 class="mb-4">Estados de Contenedores</h3>
                    
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
                            <h5>Arribado</h5>
                            <p>El contenedor ha llegado al puerto de destino y está en proceso de documentación aduanera.</p>
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
                        <h5><i class="bi bi-exclamation-triangle"></i> Fechas Estimadas</h5>
                        <p class="mb-2">Cuando un contenedor <strong>no tiene fecha real de embarque confirmada</strong>, todas las fechas mostradas son <strong>estimaciones</strong>.</p>
                        <p class="mb-0">Estas fechas estimadas se identifican con:</p>
                        <ul class="mb-0 mt-2">
                            <li>Etiqueta <span class="estimado-badge"><i class="bi bi-clock-history"></i> (estimado)</span> en el timeline</li>
                            <li>Iconos con borde punteado</li>
                            <li>Texto en cursiva</li>
                            <li>Símbolo ~ antes de la fecha</li>
                        </ul>
                    </div>
                </div>
                
                <!-- TAB: Flujo Logístico -->
                <div class="tab-pane fade" id="flujo" role="tabpanel">
                    <h3 class="mb-4">Flujo del Proceso Logístico</h3>
                    
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
                            <div class="timeline-demo-label">Arribado</div>
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
                    
                    <div class="accordion" id="flujoAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#paso1">
                                    <strong>Paso 1:</strong> &nbsp; Preparación en Origen
                                </button>
                            </h2>
                            <div id="paso1" class="accordion-collapse collapse show" data-bs-parent="#flujoAccordion">
                                <div class="accordion-body">
                                    <ul>
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
                                    <ul>
                                        <li>El contenedor se carga en el buque</li>
                                        <li>Se confirma la <strong>fecha real de embarque</strong></li>
                                        <li>Desde este momento, las fechas siguientes pasan de estimadas a calculadas</li>
                                        <li>El contenedor inicia su tránsito marítimo: los días los fija DIAS_EMB_ARR en Parámetros &rsaquo; Cronograma</li>
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
                                    <ul>
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
                                    <ul>
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
                                    <ul>
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
                    <h3 class="mb-4">Código de Colores</h3>
                    <p class="text-muted mb-4">El sistema utiliza colores específicos para identificar rápidamente cada etapa del proceso:</p>
                    
                    <h5 class="mt-4 mb-3">Eventos en Calendario</h5>
                    <div class="mb-4">
                        <span class="color-legend color-est-emb">📅 Fecha Estimada Embarque</span>
                        <span class="color-legend color-emb">🚢 Fecha Real Embarque</span>
                        <span class="color-legend color-arr">🛃 Fecha Arribo</span>
                        <span class="color-legend color-desp">🚚 Fecha Despacho Aduana</span>
                        <span class="color-legend color-rec">📦 Fecha Recepción</span>
                    </div>
                    
                    <h5 class="mt-5 mb-3">Guía de Interpretación</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6><i class="bi bi-circle-fill text-info"></i> Tonos Azules</h6>
                                    <p class="small text-muted mb-0">Etapas de planificación y embarque (origen y tránsito marítimo)</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6><i class="bi bi-circle-fill text-success"></i> Verde</h6>
                                    <p class="small text-muted mb-0">Arribo exitoso al puerto de destino</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6><i class="bi bi-circle-fill text-warning"></i> Naranja</h6>
                                    <p class="small text-muted mb-0">Proceso aduanero y documentación</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6><i class="bi bi-circle-fill" style="color: #AC92EC;"></i> Violeta</h6>
                                    <p class="small text-muted mb-0">Proceso completado - Mercadería recibida</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- TAB: Funcionalidades -->
                <div class="tab-pane fade" id="funciones" role="tabpanel">
                    <h3 class="mb-4">Funcionalidades del Sistema</h3>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📅</div>
                        <div class="feature-content">
                            <h5>Vista de Calendario</h5>
                            <p>Visualiza todos los contenedores en un calendario mensual. Cada evento (embarque, arribo, despacho, recepción) aparece como una pill de color en la fecha correspondiente. Navega entre meses con los botones anterior/siguiente o vuelve al mes actual.</p>
                        </div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">🗂️</div>
                        <div class="feature-content">
                            <h5>Vista de Tarjetas</h5>
                            <p>Muestra todos los contenedores en formato de tarjetas organizadas. Cada card incluye el proveedor, contenedor, orden de compra, estado actual y próximo hito. El borde izquierdo se colorea según el estado.</p>
                        </div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <div class="feature-content">
                            <h5>Timeline Detallado</h5>
                            <p>Al hacer click en cualquier contenedor, se abre un modal con el timeline completo del proceso logístico. Los pasos completados se muestran iluminados y con sus fechas reales. La barra de progreso indica visualmente el avance.</p>
                        </div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">⏱️</div>
                        <div class="feature-content">
                            <h5>Identificación de Estimaciones</h5>
                            <p>El sistema diferencia automáticamente entre fechas reales y estimadas. Cuando no hay fecha real de embarque, todas las fechas se marcan claramente como estimadas con iconos especiales, texto en cursiva y bordes punteados.</p>
                        </div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">🔍</div>
                        <div class="feature-content">
                            <h5>Información Detallada</h5>
                            <p>Cada contenedor muestra información completa: proveedor, código de proveedor, contenedor, orden de compra y todas las fechas del proceso logístico. El estado actual se destaca con un badge de color.</p>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <h5><i class="bi bi-lightbulb"></i> Consejos de Uso</h5>
                        <ul class="mb-0">
                            <li>Usa la <strong>vista de calendario</strong> para planificación y seguimiento cronológico</li>
                            <li>Usa la <strong>vista de tarjetas</strong> para tener una visión general de todos los contenedores</li>
                            <li>Haz <strong>click en cualquier contenedor</strong> para ver su timeline completo y fechas detalladas</li>
                            <li>Presta atención a los <strong>badges de estimación</strong> para identificar fechas tentativas</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <a href="../index.php" class="btn-volver">
                    <i class="bi bi-arrow-left"></i> Volver al Cronograma
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
