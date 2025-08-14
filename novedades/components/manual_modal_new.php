<?php
// Determinar si el usuario es RRHH para mostrar contenido específico
require_once(__DIR__ . '/../config/permisos.php');
$esUsuarioRRHH = (isset($_SESSION['TipoUsuario']) && $_SESSION['TipoUsuario'] === 'RRHH');
?>

<!-- Modal Manual de Usuario -->
<div class="modal fade" id="manualModal" tabindex="-1" aria-labelledby="manualModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="manualModalLabel">
                    <i class="fas fa-book-open me-2"></i>Manual de Usuario - Sistema de Novedades RRHH
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <!-- Body -->
            <div class="modal-body">
                <!-- Navegación por tabs -->
                <ul class="nav nav-tabs mb-3" id="manualTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="inicio-tab" data-bs-toggle="tab" data-bs-target="#inicio" 
                                type="button" role="tab" aria-controls="inicio" aria-selected="true">
                            <i class="fas fa-home"></i> Inicio
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="nueva-novedad-tab" data-bs-toggle="tab" data-bs-target="#nueva-novedad" 
                                type="button" role="tab" aria-controls="nueva-novedad" aria-selected="false">
                            <i class="fas fa-plus-circle"></i> Nueva
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="consultar-tab" data-bs-toggle="tab" data-bs-target="#consultar" 
                                type="button" role="tab" aria-controls="consultar" aria-selected="false">
                            <i class="fas fa-search"></i> Consultar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tipos-tab" data-bs-toggle="tab" data-bs-target="#tipos" 
                                type="button" role="tab" aria-controls="tipos" aria-selected="false">
                            <i class="fas fa-tags"></i> Tipos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="periodos-tab" data-bs-toggle="tab" data-bs-target="#periodos" 
                                type="button" role="tab" aria-controls="periodos" aria-selected="false">
                            <i class="fas fa-calendar-alt"></i> Períodos
                        </button>
                    </li>
                    <?php if ($esUsuarioRRHH): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="gestion-tipos-tab" data-bs-toggle="tab" data-bs-target="#gestion-tipos" 
                                type="button" role="tab" aria-controls="gestion-tipos" aria-selected="false">
                            <i class="fas fa-cogs"></i> Gestión
                        </button>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Contenido de las tabs -->
                <div class="tab-content" id="manualTabsContent">
                    
                    <!-- Tab Inicio -->
                    <div class="tab-pane fade show active" id="inicio" role="tabpanel">
                        <h4><i class="fas fa-rocket text-primary me-2"></i>Sistema de Novedades RRHH</h4>
                        <p>Sistema integral para registrar novedades laborales con cálculo automático de períodos.</p>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-sparkles me-2"></i>
                            <strong>Nuevo:</strong> Cálculo automático del período de aplicación según el tipo de novedad.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-plus-circle fa-2x text-primary mb-3"></i>
                                        <h6>Nueva Novedad</h6>
                                        <p class="small">Registrá cambios con cálculo automático del período de aplicación.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-search fa-2x text-info mb-3"></i>
                                        <h6>Consultar</h6>
                                        <p class="small">Visualizá todas las novedades con filtros avanzados y exportación.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-calendar-alt fa-2x text-warning mb-3"></i>
                                        <h6>Períodos Inteligentes</h6>
                                        <p class="small">El sistema calcula automáticamente el período según fechas de cierre.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4">Proceso Rápido</h5>
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Seleccionar Empleado</div>
                                    Usá el autocompletado por nombre o legajo
                                </div>
                                <span class="badge bg-primary rounded-pill">1</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Elegir Tipo</div>
                                    El período se calcula automáticamente
                                </div>
                                <span class="badge bg-primary rounded-pill">2</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Completar Datos</div>
                                    Llená los campos específicos del tipo
                                </div>
                                <span class="badge bg-primary rounded-pill">3</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">Guardar</div>
                                    Revisá y confirmá la novedad
                                </div>
                                <span class="badge bg-primary rounded-pill">4</span>
                            </li>
                        </ol>
                    </div>

                    <!-- Tab Nueva Novedad -->
                    <div class="tab-pane fade" id="nueva-novedad" role="tabpanel">
                        <h4><i class="fas fa-plus-circle text-primary me-2"></i>Crear Nueva Novedad</h4>
                        <p>Registrá cambios que afecten la liquidación con cálculo automático del período.</p>

                        <div class="alert alert-success">
                            <i class="fas fa-sparkles me-2"></i>
                            <strong>Nuevo:</strong> El sistema calcula automáticamente el período según el tipo y puede asignar al período siguiente si es necesario.
                        </div>

                        <h5><i class="fas fa-user-search text-info me-2"></i>Búsqueda de Empleado</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <p><strong>Autocompletado Inteligente:</strong></p>
                                <ul>
                                    <li><strong>Nombre:</strong> Escribí parte del nombre</li>
                                    <li><strong>Apellido:</strong> O parte del apellido</li>
                                    <li><strong>Legajo:</strong> O el número exacto</li>
                                </ul>
                                <div class="alert alert-info">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    El legajo y sucursal se completan automáticamente.
                                </div>
                            </div>
                        </div>

                        <h5><i class="fas fa-calendar-alt text-warning me-2"></i>Cálculo Automático de Período</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <p><strong>Al seleccionar el tipo, el sistema:</strong></p>
                                <ol>
                                    <li>Consulta la configuración (días de cierre y corte)</li>
                                    <li>Verifica la fecha actual contra los límites</li>
                                    <li>Considera feriados argentinos vía API</li>
                                    <li>Calcula automáticamente el período correspondiente</li>
                                </ol>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="alert alert-success">
                                            <h6><i class="fas fa-check me-2"></i>A Tiempo</h6>
                                            <p class="small mb-0">Antes del cierre = <strong>período actual</strong></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-warning">
                                            <h6><i class="fas fa-forward me-2"></i>Tardío</h6>
                                            <p class="small mb-0">Después del cierre = <strong>período siguiente</strong></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Consultar -->
                    <div class="tab-pane fade" id="consultar" role="tabpanel">
                        <h4><i class="fas fa-search text-primary me-2"></i>Consultar Novedades</h4>
                        <p>Visualizá <strong>TODAS las novedades registradas</strong>, incluyendo período actual, siguiente y anteriores.</p>

                        <div class="alert alert-success">
                            <i class="fas fa-sparkles me-2"></i>
                            <strong>Nuevo:</strong> La consulta muestra automáticamente todas las novedades sin limitarse al período actual.
                        </div>

                        <h5><i class="fas fa-filter text-warning me-2"></i>Filtros Disponibles</h5>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Importante:</strong> "Fecha de Registro" y "Período Real" NO se pueden usar simultáneamente.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Por Empleado</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="small mb-0">
                                            <li>Autocompletado inteligente</li>
                                            <li>Por nombre del empleado</li>
                                            <li>Por número de legajo</li>
                                            <li>Botón limpiar rápido</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Por Fecha Registro</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="small mb-0">
                                            <li>Fecha "Desde" y "Hasta"</li>
                                            <li>Cuándo se registró</li>
                                            <li>Útil para auditorías</li>
                                            <li class="text-warning">No combinar con Período Real</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Por Período Real</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="small mb-0">
                                            <li>Mes y Año específicos</li>
                                            <li>Período de liquidación</li>
                                            <li>Preparar liquidaciones</li>
                                            <li class="text-warning">No combinar con Fecha Registro</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Tipos -->
                    <div class="tab-pane fade" id="tipos" role="tabpanel">
                        <h4><i class="fas fa-tags text-primary me-2"></i>Tipos de Novedad</h4>
                        <p>Cada tipo tiene campos específicos y reglas particulares.</p>

                        <div class="accordion" id="tiposAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#cambioSucursal">
                                        <i class="fas fa-building me-2"></i>Cambio de Sucursal
                                    </button>
                                </h2>
                                <div id="cambioSucursal" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <h6>Campos Requeridos:</h6>
                                        <ul>
                                            <li><strong>Nueva Sucursal:</strong> Seleccionar de la lista</li>
                                            <li><strong>Fecha de Vigencia:</strong> Cuándo entra en efecto</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#nuevoSalario">
                                        <i class="fas fa-dollar-sign me-2"></i>Nuevo Salario Neto
                                    </button>
                                </h2>
                                <div id="nuevoSalario" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <h6>Campos Requeridos:</h6>
                                        <ul>
                                            <li><strong>Importe:</strong> Nuevo salario neto en pesos</li>
                                            <li><strong>Fecha de Vigencia:</strong> Cuándo comienza a regir</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#horasExtras">
                                        <i class="fas fa-clock me-2"></i>Horas Extras
                                    </button>
                                </h2>
                                <div id="horasExtras" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <h6>Campos Requeridos:</h6>
                                        <ul>
                                            <li><strong>Cantidad:</strong> Número entero de horas trabajadas</li>
                                            <li><strong>Fecha de Vigencia:</strong> Fecha de las horas extras</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Períodos -->
                    <div class="tab-pane fade" id="periodos" role="tabpanel">
                        <h4><i class="fas fa-calendar-alt text-primary me-2"></i>Lógica de Períodos</h4>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Funcionalidad Clave:</strong> El sistema calcula automáticamente el período según el tipo y fecha actual.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-cogs text-success me-2"></i>¿Cómo Funciona?</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Proceso Automático:</h6>
                                        <ol class="small">
                                            <li>Seleccionás el tipo de novedad</li>
                                            <li>El sistema consulta los días configurados</li>
                                            <li>Calcula si hay tiempo para período actual</li>
                                            <li>Considera feriados argentinos vía API</li>
                                            <li>Asigna el período correspondiente</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-calendar text-warning me-2"></i>Días de Cierre</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <p><strong>Día de Cierre:</strong> Último día para registrar en período actual</p>
                                        <p><strong>Día de Corte:</strong> Día límite para que tenga efecto</p>
                                        <div class="alert alert-warning small">
                                            <strong>Especial:</strong> El valor "1" = primer día hábil del mes
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4"><i class="fas fa-example text-info me-2"></i>Ejemplos Prácticos</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-check me-2"></i>A Tiempo</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="small mb-0">
                                            <li><strong>Fecha:</strong> 15 de agosto 2025</li>
                                            <li><strong>Tipo:</strong> "Nuevo Salario" (cierre: 20)</li>
                                            <li><strong>Resultado:</strong> Período actual</li>
                                            <li class="text-success"><strong>✓ Motivo:</strong> Antes del día de cierre</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-forward me-2"></i>Tardío</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="small mb-0">
                                            <li><strong>Fecha:</strong> 25 de agosto 2025</li>
                                            <li><strong>Tipo:</strong> "Nuevo Salario" (cierre: 20)</li>
                                            <li><strong>Resultado:</strong> Período siguiente</li>
                                            <li class="text-warning"><strong>⚠ Motivo:</strong> Pasamos el día de cierre</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Gestión Tipos (solo RRHH) -->
                    <?php if ($esUsuarioRRHH): ?>
                    <div class="tab-pane fade" id="gestion-tipos" role="tabpanel">
                        <h4><i class="fas fa-cogs text-primary me-2"></i>Gestión de Tipos</h4>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-user-shield me-2"></i>
                            <strong>Acceso Exclusivo:</strong> Solo disponible para usuarios RRHH.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-settings text-success me-2"></i>Configuración</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Datos configurables:</h6>
                                        <ul class="small">
                                            <li><strong>Descripción:</strong> Nombre del tipo</li>
                                            <li><strong>Estado:</strong> Activo/Inactivo</li>
                                            <li><strong>Día de Cierre:</strong> Último día para registrar</li>
                                            <li><strong>Día de Corte:</strong> Límite para aplicar</li>
                                            <li><strong>Permisos:</strong> Qué usuarios pueden usarlo</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-users text-info me-2"></i>Permisos</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Tipos de usuario:</h6>
                                        <ul class="small">
                                            <li><strong>☑️ RRHH:</strong> Recursos Humanos</li>
                                            <li><strong>☑️ Administrador:</strong> Usuarios administrativos</li>
                                            <li><strong>☑️ Comercial:</strong> Área comercial</li>
                                            <li><strong>☑️ Producción:</strong> Área productiva</li>
                                        </ul>
                                        <div class="alert alert-info small mt-2">
                                            Los cambios se aplican inmediatamente
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para el modal del manual */
.modal-xl {
    max-width: 90%;
}

#manualModal .nav-tabs {
    font-size: 0.9rem;
    border-bottom: 2px solid #dee2e6;
}

#manualModal .nav-tabs .nav-link {
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid transparent;
    border-radius: 0.25rem 0.25rem 0 0;
}

#manualModal .nav-tabs .nav-link.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

#manualModal .nav-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
    background-color: #f8f9fa;
}

#manualModal .tab-content {
    max-height: 65vh;
    overflow-y: auto;
    padding: 1rem 0;
}

#manualModal .card {
    transition: transform 0.2s;
    margin-bottom: 1rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

#manualModal .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
}

#manualModal .alert {
    margin-bottom: 1rem;
    border-left: 4px solid;
}

#manualModal .alert-success {
    border-left-color: #198754;
}

#manualModal .alert-warning {
    border-left-color: #ffc107;
}

#manualModal .alert-info {
    border-left-color: #0dcaf0;
}

#manualModal h4 {
    font-size: 1.25rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #f8f9fa;
    padding-bottom: 0.5rem;
}

#manualModal h5 {
    font-size: 1.1rem;
    margin-bottom: 0.75rem;
    margin-top: 1.5rem;
}

#manualModal h6 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

#manualModal .small {
    font-size: 0.875rem;
}

/* Modal responsivo */
#manualModal .modal-body {
    padding: 1.5rem;
}

#manualModal .modal-dialog {
    margin: 1rem auto;
}

/* Responsive design */
@media (max-width: 768px) {
    #manualModal .nav-tabs .nav-link {
        font-size: 0.75rem;
        padding: 0.4rem 0.6rem;
    }
    
    #manualModal .tab-content {
        max-height: 50vh;
    }
    
    #manualModal .modal-body {
        padding: 1rem;
    }
}

/* Acordeones */
#manualModal .accordion-button {
    font-size: 0.9rem;
    padding: 0.75rem 1rem;
}

#manualModal .accordion-body {
    padding: 1rem;
}

/* Mejoras visuales */
#manualModal .list-group-numbered > li::before {
    font-weight: bold;
    color: #0d6efd;
}

#manualModal .badge {
    font-size: 0.75em;
}

/* Scrollbar personalizada */
#manualModal .tab-content::-webkit-scrollbar {
    width: 8px;
}

#manualModal .tab-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#manualModal .tab-content::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

#manualModal .tab-content::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>
