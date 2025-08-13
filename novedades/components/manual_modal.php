<!-- Modal Manual de Uso -->
<div class="modal fade" id="manualModal" tabindex="-1" aria-labelledby="manualModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="manualModalLabel">
                    <i class="fas fa-book me-2"></i>Manual de Usuario - Sistema de Novedades RRHH
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Navegación del manual -->
                <ul class="nav nav-tabs mb-3" id="manualTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="inicio-tab" data-bs-toggle="tab" data-bs-target="#inicio" type="button" role="tab">
                            <i class="fas fa-home me-1"></i>Inicio Rápido
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="nueva-novedad-tab" data-bs-toggle="tab" data-bs-target="#nueva-novedad" type="button" role="tab">
                            <i class="fas fa-plus-circle me-1"></i>Nueva Novedad
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="consultar-tab" data-bs-toggle="tab" data-bs-target="#consultar" type="button" role="tab">
                            <i class="fas fa-search me-1"></i>Consultar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tipos-tab" data-bs-toggle="tab" data-bs-target="#tipos" type="button" role="tab">
                            <i class="fas fa-tags me-1"></i>Tipos de Novedad
                        </button>
                    </li>
                </ul>

                <!-- Contenido del manual -->
                <div class="tab-content" id="manualTabsContent">
                    
                    <!-- Inicio Rápido -->
                    <div class="tab-pane fade show active" id="inicio" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <h4><i class="fas fa-rocket text-primary me-2"></i>Bienvenido al Sistema de Novedades RRHH</h4>
                                <p class="lead">Este sistema te permite registrar novedades laborales que impactan en la liquidación de sueldos de forma digital y trazable.</p>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    <strong>Período Actual:</strong> Las novedades se registran para el período del 28 del mes anterior al 27 del mes actual.
                                </div>

                                <h5>¿Qué podés hacer acá?</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card border-primary">
                                            <div class="card-body text-center">
                                                <i class="fas fa-plus-circle fa-2x text-primary mb-2"></i>
                                                <h6>Crear Nueva Novedad</h6>
                                                <p class="small">Registrá cambios que afectan al empleado: salarios, puestos, horas extras, etc.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-body text-center">
                                                <i class="fas fa-search fa-2x text-success mb-2"></i>
                                                <h6>Consultar Novedades</h6>
                                                <p class="small">Mirá, filtrá y reportá las novedades registradas por período y empleado.</p>
                                
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <strong>Consejo:</strong> Usá los filtros rápidos por período para encontrar novedades de hoy, esta semana, este mes o el período actual.
                                </div>
                                
                                <h6 class="mt-3">Filtros Disponibles:</h6>
                                <ul class="small">
                                    <li><strong>Empleado/Legajo:</strong> Buscá por nombre o número de legajo</li>
                                    <li><strong>Sucursal:</strong> Filtrá por ubicación</li>
                                    <li><strong>Tipo de Novedad:</strong> Elegí el tipo específico</li>
                                    <li><strong>Rango de Fechas:</strong> Establecé fecha desde y hasta</li>
                                    <li><strong>Filtros Rápidos:</strong> Hoy, Esta semana, Este mes, Período actual</li>
                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mt-4">Pasos básicos para crear una novedad:</h5>
                                <ol class="list-group list-group-numbered">
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="ms-2 me-auto">
                                            <div class="fw-bold">Seleccionar Empleado</div>
                                            Buscá por nombre, apellido o legajo
                                        </div>
                                        <span class="badge bg-primary rounded-pill">1</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="ms-2 me-auto">
                                            <div class="fw-bold">Elegir Tipo de Novedad</div>
                                            Seleccioná de la lista disponible
                                        </div>
                                        <span class="badge bg-primary rounded-pill">2</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="ms-2 me-auto">
                                            <div class="fw-bold">Completar Datos</div>
                                            Llená los campos específicos según el tipo
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
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6><i class="fas fa-lightbulb me-1"></i>Consejos Útiles</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <small>Siempre verificá que el empleado sea correcto</small>
                                        </div>
                                        <div class="mb-3">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <small>Las fechas de vigencia son importantes para la liquidación</small>
                                        </div>
                                        <div class="mb-3">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <small>Usá las observaciones para agregar contexto adicional</small>
                                        </div>
                                        <div class="mb-0">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <small>Consultá regularmente las novedades creadas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nueva Novedad -->
                    <div class="tab-pane fade" id="nueva-novedad" role="tabpanel">
                        <h4><i class="fas fa-plus-circle text-primary me-2"></i>Crear Nueva Novedad</h4>
                        <p>Esta sección te permite registrar cualquier cambio que afecte la liquidación de sueldos de un empleado.</p>

                        <h5>1. Datos del Empleado</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <p><strong>Búsqueda de Empleado:</strong> Utilizá el campo de búsqueda para encontrar al empleado por:</p>
                                <ul>
                                    <li><strong>Nombre:</strong> Escribí parte del nombre</li>
                                    <li><strong>Apellido:</strong> Escribí parte del apellido</li>
                                    <li><strong>Legajo:</strong> Escribí el número exacto</li>
                                </ul>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Importante:</strong> El legajo y la sucursal se completan automáticamente al seleccionar el empleado.
                                </div>
                            </div>
                        </div>

                        <h5>2. Tipos de Novedad Disponibles</h5>
                        <p>Los tipos disponibles son:</p>
                        
                        <div class="accordion" id="tiposAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#administrativos">
                                        <i class="fas fa-user-tie me-2"></i>Tipos Administrativos
                                    </button>
                                </h2>
                                <div id="administrativos" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <ul class="list-group">
                                            <li class="list-group-item">Cambio de Sucursal</li>
                                            <li class="list-group-item">Nuevo Puesto</li>
                                            <li class="list-group-item">Nuevo Salario Neto</li>
                                            <li class="list-group-item">Ajuste de Premios</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#operativos">
                                        <i class="fas fa-clock me-2"></i>Tipos Operativos
                                    </button>
                                </h2>
                                <div id="operativos" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <ul class="list-group">
                                            <li class="list-group-item">Horas Extras</li>
                                            <li class="list-group-item">Horas Adicionales</li>
                                            <li class="list-group-item">Ausencias</li>
                                            <li class="list-group-item">Cortes</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#produccion">
                                        <i class="fas fa-industry me-2"></i>Tipos de Producción
                                    </button>
                                </h2>
                                <div id="produccion" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <ul class="list-group">
                                            <li class="list-group-item">Producción 25%</li>
                                            <li class="list-group-item">Producción 50%</li>
                                            <li class="list-group-item">Producción 100%</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4">3. Completar y Guardar</h5>
                        <div class="alert alert-info">
                            <ul class="mb-0">
                                <li><strong>Campos Obligatorios:</strong> Completá todos los campos marcados con *</li>
                                <li><strong>Observaciones:</strong> Podés agregar información adicional</li>
                                <li><strong>Revisión:</strong> Verificá todos los datos antes de guardar</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Consultar -->
                    <div class="tab-pane fade" id="consultar" role="tabpanel">
                        <h4><i class="fas fa-search text-primary me-2"></i>Consultar Novedades</h4>
                        <p>En esta sección podés buscar, filtrar y generar reportes de las novedades registradas.</p>

                        <h5>Filtros Disponibles</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros de Empleado</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled mb-0">
                                            <li><i class="fas fa-user me-2 text-primary"></i>Por nombre del empleado</li>
                                            <li><i class="fas fa-id-card me-2 text-primary"></i>Por número de legajo</li>
                                            <li><i class="fas fa-building me-2 text-primary"></i>Por sucursal</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Filtros de Fecha</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled mb-0">
                                            <li><i class="fas fa-calendar-day me-2 text-success"></i>Por fecha específica</li>
                                            <li><i class="fas fa-calendar-week me-2 text-success"></i>Por rango de fechas</li>
                                            <li><i class="fas fa-tag me-2 text-success"></i>Por tipo de novedad</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5>Funcionalidades</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="fas fa-eye fa-2x text-info mb-3"></i>
                                        <h6>Ver Detalles</h6>
                                        <p class="small">Hacé clic en cualquier novedad para ver todos los detalles completos.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="fas fa-file-excel fa-2x text-success mb-3"></i>
                                        <h6>Exportar a Excel</h6>
                                        <p class="small">Descargá los resultados filtrados en formato Excel para análisis.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <i class="fas fa-print fa-2x text-warning mb-3"></i>
                                        <h6>Imprimir Reportes</h6>
                                        <p class="small">Generá reportes impresos de las consultas realizadas.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tip:</strong> Utilizá la búsqueda en tiempo real escribiendo en los campos de filtro. Los resultados se actualizan automáticamente.
                        </div>
                    </div>

                    <!-- Tipos de Novedad -->
                    <div class="tab-pane fade" id="tipos" role="tabpanel">
                        <h4><i class="fas fa-tags text-primary me-2"></i>Tipos de Novedad - Guía Detallada</h4>
                        <p>Cada tipo de novedad tiene campos específicos y reglas de negocio particulares.</p>

                        <div class="accordion" id="tiposDetalleAccordion">
                            <!-- Cambio de Sucursal -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#cambioSucursal">
                                        <i class="fas fa-building me-2"></i>Cambio de Sucursal
                                    </button>
                                </h2>
                                <div id="cambioSucursal" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Campos Requeridos:</h6>
                                                <ul>
                                                    <li><strong>Nueva Sucursal:</strong> Seleccionar de la lista disponible</li>
                                                    <li><strong>Fecha de Vigencia:</strong> Cuándo entra en efecto el cambio</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Nuevo Puesto -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#nuevoPuesto">
                                        <i class="fas fa-briefcase me-2"></i>Nuevo Puesto
                                    </button>
                                </h2>
                                <div id="nuevoPuesto" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Campos Requeridos:</h6>
                                                <ul>
                                                    <li><strong>Puesto:</strong> Seleccionar de la lista de puestos disponibles</li>
                                                    <li><strong>Fecha de Vigencia:</strong> Cuándo asume el nuevo puesto</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Uso Típico:</h6>
                                                <ul class="text-muted">
                                                    <li>Promociones internas</li>
                                                    <li>Cambios de área</li>
                                                    <li>Asignación de nuevas responsabilidades</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Nuevo Salario -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#nuevoSalario">
                                        <i class="fas fa-dollar-sign me-2"></i>Nuevo Salario Neto
                                    </button>
                                </h2>
                                <div id="nuevoSalario" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Campos Requeridos:</h6>
                                                <ul>
                                                    <li><strong>Importe:</strong> Nuevo salario neto en pesos</li>
                                                    <li><strong>Fecha de Vigencia:</strong> Cuándo comienza a regir</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Horas Extras -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#horasExtras">
                                        <i class="fas fa-clock me-2"></i>Horas Extras
                                    </button>
                                </h2>
                                <div id="horasExtras" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6>Campos Requeridos:</h6>
                                                <ul>
                                                    <li><strong>Cantidad de Horas:</strong> Número entero de horas trabajadas</li>
                                                    <li><strong>Fecha de Vigencia:</strong> Fecha de las horas extras</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Producción -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tiposProduccion">
                                        <i class="fas fa-industry me-2"></i>Tipos de Producción
                                    </button>
                                </h2>
                                <div id="tiposProduccion" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <h6>Campos Requeridos:</h6>
                                        <ul>
                                            <li><strong>Producción 25%:</strong> Cantidad de Unidades + Fecha de Vigencia</li>
                                            <li><strong>Producción 50%:</strong> Cantidad de Unidades + Fecha de Vigencia</li>
                                            <li><strong>Producción 100%:</strong> Cantidad de Unidades + Fecha de Vigencia</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para el manual */
.modal-xl {
    max-width: 90%;
}

#manualModal .nav-tabs .nav-link {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

#manualModal .tab-content {
    max-height: 70vh;
    overflow-y: auto;
}

#manualModal .card {
    transition: transform 0.2s;
}

#manualModal .card:hover {
    transform: translateY(-2px);
}

/* Asegurar que el modal funcione correctamente */
#manualModal .modal-header {
    position: relative;
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: calc(0.375rem - 1px);
    border-top-right-radius: calc(0.375rem - 1px);
}

#manualModal .modal-title {
    margin-bottom: 0;
    line-height: 1.5;
}

#manualModal .btn-close {
    position: static;
    float: none;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1;
    color: #000;
    text-shadow: 0 1px 0 #fff;
    opacity: 0.5;
}

@media print {
    .modal-header {
        display: none !important;
    }
    
    .nav-tabs {
        display: none !important;
    }
    
    .tab-pane {
        display: block !important;
    }
}
</style>
