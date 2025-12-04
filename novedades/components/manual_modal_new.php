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
                        <p>Sistema integral para registrar novedades laborales con <strong>18 tipos diferentes</strong>, cálculo automático de períodos, validaciones inteligentes y gestión completa de estados.</p>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-sparkles me-2"></i>
                            <strong>Características Avanzadas:</strong> Autocompletado inteligente, cálculo automático de períodos considerando feriados argentinos, validaciones en tiempo real, y funcionalidades de edición en línea.
                        </div>

                        <!-- Módulos Principales -->
                        <h5><i class="fas fa-th-large text-info me-2"></i>Módulos del Sistema</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card text-center h-100 border-primary">
                                    <div class="card-body">
                                        <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                                        <h6>Nueva Novedad</h6>
                                        <ul class="small text-start">
                                            <li>18 tipos de novedad disponibles</li>
                                            <li>Modo único o múltiple</li>
                                            <li>Cálculo automático de período</li>
                                            <li>Validaciones específicas por tipo</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center h-100 border-info">
                                    <div class="card-body">
                                        <i class="fas fa-search fa-3x text-info mb-3"></i>
                                        <h6>Consultar Novedades</h6>
                                        <ul class="small text-start">
                                            <li>Filtros avanzados múltiples</li>
                                            <li>Edición en línea completa</li>
                                            <li>Gestión de estados</li>
                                            <li>Exportación a Excel</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center h-100 border-warning">
                                    <div class="card-body">
                                        <i class="fas fa-cogs fa-3x text-warning mb-3"></i>
                                        <h6>Gestión de Tipos <small>(RRHH)</small></h6>
                                        <ul class="small text-start">
                                            <li>Configurar días de cierre</li>
                                            <li>Gestionar permisos por usuario</li>
                                            <li>Activar/desactivar tipos</li>
                                            <li>Configurar aplicación de períodos</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Flujo Completo -->
                        <h5><i class="fas fa-project-diagram text-success me-2"></i>Flujo de Trabajo Completo</h5>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-play text-primary me-2"></i>Creación (Usuario):</h6>
                                        <ol class="small">
                                            <li><strong>Seleccionar empleado:</strong> Autocompletado inteligente</li>
                                            <li><strong>Elegir tipo(s):</strong> Según permisos del usuario</li>
                                            <li><strong>Completar campos:</strong> Específicos por tipo</li>
                                            <li><strong>Confirmar período:</strong> Calculado automáticamente</li>
                                            <li><strong>Guardar:</strong> Estado inicial "Enviada"</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-tasks text-warning me-2"></i>Procesamiento (RRHH):</h6>
                                        <ol class="small">
                                            <li><strong>Consultar novedades:</strong> Filtrar por período/empleado</li>
                                            <li><strong>Revisar información:</strong> Validar datos ingresados</li>
                                            <li><strong>Editar si necesario:</strong> Modificar campos específicos</li>
                                            <li><strong>Cambiar estado:</strong> Aprobar/Revisar/Procesar</li>
                                            <li><strong>Exportar a liquidación:</strong> Excel con filtros</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Proceso Rápido -->
                        <h5><i class="fas fa-lightning-bolt text-warning me-2"></i>Inicio Rápido</h5>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center p-3">
                                        <div class="display-6 text-primary mb-2">1</div>
                                        <h6 class="small">Seleccionar Empleado</h6>
                                        <p class="small text-muted mb-0">Buscar por nombre o legajo</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center p-3">
                                        <div class="display-6 text-info mb-2">2</div>
                                        <h6 class="small">Elegir Tipo</h6>
                                        <p class="small text-muted mb-0">El período se calcula automáticamente</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center p-3">
                                        <div class="display-6 text-warning mb-2">3</div>
                                        <h6 class="small">Completar Datos</h6>
                                        <p class="small text-muted mb-0">Campos específicos del tipo</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center p-3">
                                        <div class="display-6 text-success mb-2">4</div>
                                        <h6 class="small">Guardar</h6>
                                        <p class="small text-muted mb-0">Revisar y confirmar</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Novedades Destacadas -->
                        <div class="alert alert-light border mt-4">
                            <h6><i class="fas fa-star text-warning me-2"></i>Funcionalidades Destacadas</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="small">
                                        <li><i class="fas fa-magic text-primary me-2"></i><strong>Autocompletado:</strong> Búsqueda inteligente de empleados</li>
                                        <li><i class="fas fa-calendar-alt text-success me-2"></i><strong>Períodos:</strong> Cálculo automático considerando feriados</li>
                                        <li><i class="fas fa-shield-alt text-info me-2"></i><strong>Permisos:</strong> Tipos filtrados por rol de usuario</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="small">
                                        <li><i class="fas fa-edit text-warning me-2"></i><strong>Edición:</strong> Modificación en línea de novedades</li>
                                        <li><i class="fas fa-file-excel text-success me-2"></i><strong>Exportación:</strong> Excel con datos filtrados</li>
                                        <li><i class="fas fa-layer-group text-secondary me-2"></i><strong>Modos:</strong> Creación única o múltiple</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Nueva Novedad -->
                    <div class="tab-pane fade" id="nueva-novedad" role="tabpanel">
                        <h4><i class="fas fa-plus-circle text-primary me-2"></i>Crear Nueva Novedad</h4>
                        <p>Registrá cambios que afecten la liquidación con cálculo automático del período y validaciones dinámicas.</p>

                        <div class="alert alert-success">
                            <i class="fas fa-sparkles me-2"></i>
                            <strong>Funcionalidades Avanzadas:</strong> Autocompletado inteligente, validaciones en tiempo real, y cálculo automático de períodos.
                        </div>

                        <!-- Proceso Paso a Paso -->
                        <h5><i class="fas fa-list-ol text-warning me-2"></i>Proceso Paso a Paso</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border-primary h-100">
                                    <div class="card-body text-center">
                                        <div class="display-6 text-primary mb-2">1</div>
                                        <h6 class="card-title">Seleccionar Modo</h6>
                                        <p class="small">Único (1 novedad) o Múltiple (varias para mismo empleado)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info h-100">
                                    <div class="card-body text-center">
                                        <div class="display-6 text-info mb-2">2</div>
                                        <h6 class="card-title">Buscar Empleado</h6>
                                        <p class="small">Autocompletado por nombre, apellido o legajo</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-warning h-100">
                                    <div class="card-body text-center">
                                        <div class="display-6 text-warning mb-2">3</div>
                                        <h6 class="card-title">Elegir Tipo(s)</h6>
                                        <p class="small">Seleccionar tipo(s) de novedad según permisos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success h-100">
                                    <div class="card-body text-center">
                                        <div class="display-6 text-success mb-2">4</div>
                                        <h6 class="card-title">Completar y Guardar</h6>
                                        <p class="small">Llenar campos específicos y confirmar</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Búsqueda de Empleado -->
                        <h5><i class="fas fa-user-search text-info me-2"></i>Búsqueda Inteligente de Empleado</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-search text-primary me-2"></i>Funciona con:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Nombre completo o parcial:</strong> "Juan", "Juan Carlos"</li>
                                            <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Apellido completo o parcial:</strong> "García", "Gar"</li>
                                            <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Nombre y Apellido:</strong> "Juan García"</li>
                                            <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Número de legajo:</strong> "1234", "12"</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-magic text-success me-2"></i>Completado Automático:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Legajo del empleado</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Nombre y apellido exactos</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Centro de costos actual</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Sucursal actual</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <strong>Consejo:</strong> Escribí al menos 2 caracteres para activar el autocompletado.
                                </div>
                            </div>
                        </div>

                        <!-- Modos de Creación -->
                        <h5><i class="fas fa-layer-group text-warning me-2"></i>Modos de Creación</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Modo Único</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Una novedad por empleado</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Período de aplicación personalizable</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Campo de observaciones disponible</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Ideal para casos específicos</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Modo Múltiple</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Varias novedades para el mismo empleado</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Período calculado automáticamente</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Botones para agregar/quitar tipos</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Eficiente para múltiples cambios</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cálculo Automático de Período -->
                        <h5><i class="fas fa-calendar-alt text-success me-2"></i>Cálculo Automático de Período</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-cogs text-primary me-2"></i>¿Cómo Funciona?</h6>
                                        <ol class="small">
                                            <li>Al seleccionar el tipo de novedad</li>
                                            <li>El sistema consulta la configuración (días de cierre)</li>
                                            <li>Verifica la fecha actual contra los límites</li>
                                            <li>Considera feriados argentinos vía API</li>
                                            <li>Calcula automáticamente el período correspondiente</li>
                                            <li>Muestra información visual del resultado</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-calendar-check text-warning me-2"></i>Períodos Posibles</h6>
                                        <div class="alert alert-success small">
                                            <strong><i class="fas fa-check me-2"></i>Período Actual:</strong> Si estás antes del día de cierre
                                        </div>
                                        <div class="alert alert-warning small">
                                            <strong><i class="fas fa-forward me-2"></i>Período Siguiente:</strong> Si pasaste el día de cierre
                                        </div>
                                        <div class="alert alert-info small">
                                            <strong><i class="fas fa-info-circle me-2"></i>Período Personalizado:</strong> Solo en modo único
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Validaciones y Controles -->
                        <h5><i class="fas fa-shield-alt text-info me-2"></i>Validaciones y Controles</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6><i class="fas fa-lock text-primary me-2"></i>Permisos</h6>
                                        <ul class="small">
                                            <li>Solo tipos permitidos por usuario</li>
                                            <li>Verificación automática de acceso</li>
                                            <li>Filtrado dinámico de opciones</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6><i class="fas fa-check-double text-success me-2"></i>Campos</h6>
                                        <ul class="small">
                                            <li>Validación en tiempo real</li>
                                            <li>Campos específicos por tipo</li>
                                            <li>Formatos automáticos (fechas, números)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Errores</h6>
                                        <ul class="small">
                                            <li>Mensajes específicos por campo</li>
                                            <li>Destacado visual de errores</li>
                                            <li>Prevención antes de guardar</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Consultar -->
                    <div class="tab-pane fade" id="consultar" role="tabpanel">
                        <h4><i class="fas fa-search text-primary me-2"></i>Consultar Novedades</h4>
                        <p>Visualizá y administrá <strong>TODAS las novedades registradas</strong> con funcionalidades avanzadas de filtrado, edición y exportación.</p>

                        <div class="alert alert-success">
                            <i class="fas fa-sparkles me-2"></i>
                            <strong>Funcionalidades Completas:</strong> Filtrado avanzado, edición en línea, cambio de estados, y exportación a Excel.
                        </div>

                        <!-- Filtros Principales -->
                        <h5><i class="fas fa-filter text-warning me-2"></i>Sistema de Filtros</h5>
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Restricción Importante:</strong> "Fecha de Registro" y "Período Real" NO se pueden usar simultáneamente.
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Filtro por Empleado</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-search text-primary me-2"></i><strong>Autocompletado inteligente</strong></li>
                                            <li><i class="fas fa-user text-info me-2"></i>Por nombre del empleado</li>
                                            <li><i class="fas fa-id-card text-info me-2"></i>Por número de legajo</li>
                                            <li><i class="fas fa-eraser text-warning me-2"></i>Botón limpiar rápido disponible</li>
                                        </ul>
                                        <div class="alert alert-info small">
                                            <i class="fas fa-lightbulb me-2"></i>
                                            Escribí al menos 2 caracteres para activar el autocompletado.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-building me-2"></i>Filtro por Centro de Costos</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-list text-primary me-2"></i><strong>Lista desplegable completa</strong></li>
                                            <li><i class="fas fa-home text-info me-2"></i>Incluye Casa Central</li>
                                            <li><i class="fas fa-store text-info me-2"></i>Todas las sucursales activas</li>
                                            <li><i class="fas fa-filter text-warning me-2"></i>Filtra por ubicación del empleado</li>
                                        </ul>
                                        <div class="alert alert-success small">
                                            <i class="fas fa-check me-2"></i>
                                            Útil para ver novedades por sucursal específica.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Filtro por Fecha de Registro</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-calendar-day text-primary me-2"></i><strong>Fecha "Desde" y "Hasta"</strong></li>
                                            <li><i class="fas fa-clock text-info me-2"></i>Basado en cuándo se registró la novedad</li>
                                            <li><i class="fas fa-search text-info me-2"></i>Útil para auditorías temporales</li>
                                            <li><i class="fas fa-chart-line text-warning me-2"></i>Permite análisis de períodos específicos</li>
                                        </ul>
                                        <div class="alert alert-warning small">
                                            <i class="fas fa-ban me-2"></i>
                                            <strong>No combinar</strong> con Período Real
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Filtro por Período Real</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-calendar-alt text-primary me-2"></i><strong>Mes y Año específicos</strong></li>
                                            <li><i class="fas fa-calculator text-info me-2"></i>Período real de liquidación</li>
                                            <li><i class="fas fa-file-invoice text-info me-2"></i>Para preparar liquidaciones</li>
                                            <li><i class="fas fa-filter text-warning me-2"></i>Filtra por período de aplicación</li>
                                        </ul>
                                        <div class="alert alert-warning small">
                                            <i class="fas fa-ban me-2"></i>
                                            <strong>No combinar</strong> con Fecha de Registro
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Funcionalidades de la Vista -->
                        <h5><i class="fas fa-cogs text-info me-2"></i>Funcionalidades de la Vista</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border-success h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-edit fa-2x text-success mb-3"></i>
                                        <h6>Edición en Línea</h6>
                                        <ul class="small text-start">
                                            <li>Modificar campos específicos</li>
                                            <li>Validación automática</li>
                                            <li>Guardado instantáneo</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-info h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-exchange-alt fa-2x text-info mb-3"></i>
                                        <h6>Cambio de Estados</h6>
                                        <ul class="small text-start">
                                            <li>Enviada → En Revisión</li>
                                            <li>En Revisión → Aprobada</li>
                                            <li>Aprobada → Procesada</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-warning h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-excel fa-2x text-warning mb-3"></i>
                                        <h6>Exportación</h6>
                                        <ul class="small text-start">
                                            <li>Excel con filtros aplicados</li>
                                            <li>Todos los campos incluidos</li>
                                            <li>Formato profesional</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estados de Novedad -->
                        <h5><i class="fas fa-flag text-secondary me-2"></i>Estados de Novedad</h5>
                        <div class="row g-2">
                            <div class="col-md-2">
                                <div class="card text-center">
                                    <div class="card-body p-2">
                                        <span class="badge bg-primary">1</span>
                                        <div class="small mt-1">Enviada</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card text-center">
                                    <div class="card-body p-2">
                                        <span class="badge bg-warning">2</span>
                                        <div class="small mt-1">En Revisión</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card text-center">
                                    <div class="card-body p-2">
                                        <span class="badge bg-success">3</span>
                                        <div class="small mt-1">Aprobada</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card text-center">
                                    <div class="card-body p-2">
                                        <span class="badge bg-danger">4</span>
                                        <div class="small mt-1">A Revisar</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card text-center">
                                    <div class="card-body p-2">
                                        <span class="badge bg-dark">5</span>
                                        <div class="small mt-1">Procesada</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Consejos de Uso -->
                        <div class="alert alert-light border mt-4">
                            <h6><i class="fas fa-lightbulb text-warning me-2"></i>Consejos de Uso</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="small">
                                        <li><strong>Para liquidaciones:</strong> Usar filtro "Período Real"</li>
                                        <li><strong>Para auditorías:</strong> Usar filtro "Fecha de Registro"</li>
                                        <li><strong>Para empleado específico:</strong> Combinar con otros filtros</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="small">
                                        <li><strong>Exportar a Excel:</strong> Aplicar filtros primero</li>
                                        <li><strong>Editar novedades:</strong> Hacer clic en el ícono de edición</li>
                                        <li><strong>Ver detalles:</strong> Expandir la fila correspondiente</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Tipos -->
                    <div class="tab-pane fade" id="tipos" role="tabpanel">
                        <h4><i class="fas fa-tags text-primary me-2"></i>Tipos de Novedad</h4>
                        <p>La aplicación maneja <strong>18 tipos de novedad</strong> diferentes con campos específicos y validaciones.</p>

                        <div class="accordion" id="tiposAccordion">
                            <!-- Tipo 1: Cambio de Sucursal -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#tipo1">
                                        <i class="fas fa-building me-2 text-primary"></i>1. Cambio de Sucursal
                                    </button>
                                </h2>
                                <div id="tipo1" class="accordion-collapse collapse show">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Campos Requeridos:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Nueva Sucursal:</strong> Seleccionar de lista de sucursales</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Cuándo entra en efecto</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info small">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Se registra automáticamente el centro de costos actual del empleado.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo 2: Nueva Posición -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipo2">
                                        <i class="fas fa-user-tie me-2 text-info"></i>2. Nueva Posición
                                    </button>
                                </h2>
                                <div id="tipo2" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Campos Requeridos:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Nueva Posición:</strong> Autocompletado desde sistema</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Desde cuándo</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info small">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Todas las nuevas posiciones son <strong>permanentes</strong>.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo 3: Nuevo Salario -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipo3">
                                        <i class="fas fa-dollar-sign me-2 text-success"></i>3. Nuevo Salario Neto
                                    </button>
                                </h2>
                                <div id="tipo3" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Campos Requeridos:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Importe:</strong> Nuevo salario neto en pesos</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Cuándo comienza a regir</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info small">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Solo usuarios <strong>ADMIN</strong> y <strong>RRHH</strong> pueden crear este tipo.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo 4: Ajuste de Premios -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipo4">
                                        <i class="fas fa-award me-2 text-warning"></i>4. Ajuste de Premios
                                    </button>
                                </h2>
                                <div id="tipo4" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Campos Requeridos:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Importe:</strong> Monto del ajuste en pesos</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Período de aplicación</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-success small">
                                                    <i class="fas fa-users me-2"></i>
                                                    Disponible para <strong>ADMIN</strong>, <strong>COMERCIAL</strong> y <strong>RRHH</strong>.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipos 5-6: Horas -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipos56">
                                        <i class="fas fa-clock me-2 text-primary"></i>5-6. Horas Extras y Adicionales
                                    </button>
                                </h2>
                                <div id="tipos56" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Campos Requeridos:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Cantidad:</strong> Número de horas (acepta decimales)</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Fecha de las horas trabajadas</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info small">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Disponible para todos los tipos de usuario.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo 7: Permisos -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipo7">
                                        <i class="fas fa-calendar-times me-2 text-info"></i>7. Permisos
                                    </button>
                                </h2>
                                <div id="tipo7" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Campos Requeridos:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha del Permiso:</strong> Día específico</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Compensa:</strong> SÍ/NO si compensa el día</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-warning small">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    Debe especificar si el permiso será compensado con trabajo en otro día.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo 8: Cortes -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipo8">
                                        <i class="fas fa-minus-circle me-2 text-danger"></i>8. Cortes
                                    </button>
                                </h2>
                                <div id="tipo8" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Campos Requeridos:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Cantidad de Cortes:</strong> Número entero</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Período de aplicación</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info small">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Disponible para todos los tipos de usuario.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipos 9-11: Producción -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipos911">
                                        <i class="fas fa-chart-line me-2 text-success"></i>9-11. Producción (25%, 50%, 100%)
                                    </button>
                                </h2>
                                <div id="tipos911" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Campos Requeridos:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Cantidad de Unidades:</strong> Producidas</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Período de producción</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-warning small">
                                                    <i class="fas fa-industry me-2"></i>
                                                    Solo disponible para usuarios de <strong>PRODUCCIÓN</strong> y <strong>RRHH</strong>.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipos 12-14: Plus -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipos1214">
                                        <i class="fas fa-plus-circle me-2 text-primary"></i>12-14. Plus (Caja, Sub-Enc., Encargada)
                                    </button>
                                </h2>
                                <div id="tipos1214" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Plus de Caja:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Tipo:</strong> Recibo o Premio</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Importe:</strong> Solo si es tipo "Premio"</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Aplicación</li>
                                                </ul>
                                                <h6 class="mt-3"><i class="fas fa-check-square text-success me-2"></i>Plus Sub-Enc./Encargada:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>¿Tiene importe?:</strong> SÍ/NO</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Importe:</strong> Solo si respondió SÍ</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Aplicación</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info small">
                                                    <i class="fas fa-users me-2"></i>
                                                    Disponible para <strong>ADMIN</strong>, <strong>COMERCIAL</strong> y <strong>RRHH</strong>.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo 15: Premio Local -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipo15">
                                        <i class="fas fa-trophy me-2 text-warning"></i>15. Premio Local
                                    </button>
                                </h2>
                                <div id="tipo15" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Campos Requeridos:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Importe del Premio:</strong> En pesos</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Período de aplicación</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Aplica a:</strong> Vendedora y/o Sub-Encargada</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-warning small">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    Debe seleccionar al menos una opción en "Aplica a".
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipos 16-17: Comisiones -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipos1617">
                                        <i class="fas fa-percentage me-2 text-info"></i>16-17. Comisiones
                                    </button>
                                </h2>
                                <div id="tipos1617" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Comisión Individual:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Porcentaje:</strong> Valor decimal (ej: 0.15 para 0.15%)</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Aplicación</li>
                                                </ul>
                                                <h6 class="mt-3"><i class="fas fa-check-square text-success me-2"></i>Comisión sobre Local:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Estructura:</strong> Sin tope (1 %) o Con tope (2 %)</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Porcentajes:</strong> Según estructura elegida</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Aplicación</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-info small">
                                                    <i class="fas fa-calculator me-2"></i>
                                                    Los porcentajes se ingresan como decimales. Ejemplo: 0.75 para 0.75%.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tipo 18: Premios Ajuste General -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tipo18">
                                        <i class="fas fa-adjust me-2 text-secondary"></i>18. Premios - Ajuste General
                                    </button>
                                </h2>
                                <div id="tipo18" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-square text-success me-2"></i>Campos Requeridos:</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Importe del Ajuste:</strong> En pesos</li>
                                                    <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Fecha de Vigencia:</strong> Período de aplicación</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-success small">
                                                    <i class="fas fa-users me-2"></i>
                                                    Disponible para <strong>ADMIN</strong>, <strong>COMERCIAL</strong> y <strong>RRHH</strong>.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modos de Creación -->
                        <div class="alert alert-info mt-4">
                            <h6><i class="fas fa-layer-group me-2"></i>Modos de Creación</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-user me-2"></i>Modo Único:</strong>
                                    <p class="small mb-0">Una novedad por empleado. Permite período personalizado.</p>
                                </div>
                                <div class="col-md-6">
                                    <strong><i class="fas fa-users me-2"></i>Modo Múltiple:</strong>
                                    <p class="small mb-0">Varias novedades para el mismo empleado. Período automático.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Períodos -->
                    <div class="tab-pane fade" id="periodos" role="tabpanel">
                        <h4><i class="fas fa-calendar-alt text-primary me-2"></i>Sistema de Períodos Inteligente</h4>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Funcionalidad Clave:</strong> El sistema calcula automáticamente el período según el tipo de novedad, fecha actual, días de cierre configurados y feriados argentinos.
                        </div>

                        <!-- Conceptos Fundamentales -->
                        <h5><i class="fas fa-info-circle text-info me-2"></i>Conceptos Fundamentales</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar-times me-2"></i>Día de Cierre</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Definición:</strong> Último día del mes para registrar una novedad que aplique al período actual.</p>
                                        <ul class="small">
                                            <li><strong>Antes del cierre:</strong> Novedad aplica al período actual</li>
                                            <li><strong>Después del cierre:</strong> Novedad aplica al período siguiente</li>
                                            <li><strong>Valor especial "1":</strong> Primer día hábil del mes</li>
                                        </ul>
                                        <div class="alert alert-info small">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Cada tipo de novedad puede tener un día de cierre diferente.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-cut me-2"></i>Día de Corte</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Definición:</strong> Día límite del mes para que la novedad tenga efecto en ese período.</p>
                                        <ul class="small">
                                            <li><strong>Período:</strong> La novedad se aplica al período calculado</li>
                                            <li><strong>Fecha Vigencia:</strong> Se aplica desde la fecha específica ingresada</li>
                                            <li><strong>Período Siguiente:</strong> Se fuerza al siguiente período</li>
                                        </ul>
                                        <div class="alert alert-warning small">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            El día de corte define cuándo tiene efecto la novedad, no cuándo se puede registrar.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Proceso de Cálculo -->
                        <h5><i class="fas fa-cogs text-success me-2"></i>Proceso Automático de Cálculo</h5>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-list-ol text-primary me-2"></i>Pasos del Algoritmo:</h6>
                                        <ol class="small">
                                            <li><strong>Usuario selecciona tipo de novedad</strong></li>
                                            <li><strong>Sistema consulta configuración:</strong>
                                                <ul>
                                                    <li>Día de cierre del tipo</li>
                                                    <li>Día de corte del tipo</li>
                                                    <li>Configuración de aplicación</li>
                                                </ul>
                                            </li>
                                            <li><strong>Obtiene fecha actual del sistema</strong></li>
                                            <li><strong>Consulta API de feriados argentinos</strong></li>
                                            <li><strong>Calcula período correspondiente</strong></li>
                                            <li><strong>Muestra resultado visual al usuario</strong></li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-brain text-warning me-2"></i>Consideraciones Especiales:</h6>
                                        <ul class="small">
                                            <li><strong>Feriados:</strong> Si el día de cierre cae en feriado, se toma el día hábil anterior</li>
                                            <li><strong>Fines de semana:</strong> Similar tratamiento que feriados</li>
                                            <li><strong>Primer día hábil:</strong> Cálculo especial para valor "1"</li>
                                            <li><strong>Días especiales:</strong> 25 y 31 de diciembre tienen lógica especial</li>
                                        </ul>
                                        <div class="alert alert-success small">
                                            <i class="fas fa-check me-2"></i>
                                            El sistema es totalmente automático y preciso.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ejemplos Prácticos -->
                        <h5><i class="fas fa-examples text-info me-2"></i>Ejemplos Prácticos Detallados</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-check me-2"></i>Ejemplo: A Tiempo</h6>
                                    </div>
                                    <div class="card-body">
                                        <h6>Escenario:</h6>
                                        <ul class="small">
                                            <li><strong>Fecha actual:</strong> 15 de agosto 2025</li>
                                            <li><strong>Tipo novedad:</strong> "Nuevo Salario"</li>
                                            <li><strong>Día de cierre configurado:</strong> 20</li>
                                            <li><strong>Día de corte:</strong> "Período"</li>
                                        </ul>
                                        <h6>Resultado:</h6>
                                        <div class="alert alert-success small">
                                            <strong>✓ Período: Agosto 2025</strong><br>
                                            <em>Motivo: Estamos antes del día 20, por lo tanto la novedad se aplicará al período actual (agosto).</em>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-forward me-2"></i>Ejemplo: Tardío</h6>
                                    </div>
                                    <div class="card-body">
                                        <h6>Escenario:</h6>
                                        <ul class="small">
                                            <li><strong>Fecha actual:</strong> 25 de agosto 2025</li>
                                            <li><strong>Tipo novedad:</strong> "Nuevo Salario"</li>
                                            <li><strong>Día de cierre configurado:</strong> 20</li>
                                            <li><strong>Día de corte:</strong> "Período"</li>
                                        </ul>
                                        <h6>Resultado:</h6>
                                        <div class="alert alert-warning small">
                                            <strong>⚠ Período: Septiembre 2025</strong><br>
                                            <em>Motivo: Pasamos el día 20, por lo tanto la novedad se aplicará al período siguiente (septiembre).</em>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Ejemplo: Primer Día Hábil</h6>
                                    </div>
                                    <div class="card-body">
                                        <h6>Escenario:</h6>
                                        <ul class="small">
                                            <li><strong>Fecha actual:</strong> 2 de septiembre 2025 (miércoles)</li>
                                            <li><strong>Tipo novedad:</strong> "Horas Extras"</li>
                                            <li><strong>Día de cierre configurado:</strong> 1 (primer día hábil)</li>
                                            <li><strong>1 de septiembre 2025:</strong> Martes (día hábil)</li>
                                        </ul>
                                        <h6>Resultado:</h6>
                                        <div class="alert alert-warning small">
                                            <strong>⚠ Período: Octubre 2025</strong><br>
                                            <em>Motivo: El primer día hábil de septiembre (1/9) ya pasó, por lo tanto aplica al período siguiente.</em>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-secondary">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0"><i class="fas fa-holiday-village me-2"></i>Ejemplo: Con Feriado</h6>
                                    </div>
                                    <div class="card-body">
                                        <h6>Escenario:</h6>
                                        <ul class="small">
                                            <li><strong>Fecha actual:</strong> 8 de diciembre 2025</li>
                                            <li><strong>Tipo novedad:</strong> "Ajuste Premios"</li>
                                            <li><strong>Día de cierre configurado:</strong> 8</li>
                                            <li><strong>8 de diciembre:</strong> Inmaculada Concepción (feriado)</li>
                                        </ul>
                                        <h6>Resultado:</h6>
                                        <div class="alert alert-info small">
                                            <strong>ℹ Período: Diciembre 2025</strong><br>
                                            <em>Motivo: El día 8 es feriado, se toma el día hábil anterior (5/12) y estamos en fecha.</em>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Casos Especiales -->
                        <h5><i class="fas fa-exclamation-circle text-warning me-2"></i>Casos Especiales</h5>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-calendar-times text-danger me-2"></i>Fin de Año</h6>
                                        <ul class="small">
                                            <li><strong>25 de diciembre:</strong> Navidad - se toma día hábil anterior</li>
                                            <li><strong>31 de diciembre:</strong> Último día del año - lógica especial</li>
                                            <li><strong>1 de enero:</strong> Año Nuevo - afecta primer día hábil</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-globe-americas text-primary me-2"></i>Feriados Argentinos</h6>
                                        <ul class="small">
                                            <li><strong>API externa:</strong> Consulta automática de feriados</li>
                                            <li><strong>Feriados puente:</strong> Incluidos en el cálculo</li>
                                            <li><strong>Actualizaciones:</strong> Automáticas cada año</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-user-cog text-info me-2"></i>Modo Personalizado</h6>
                                        <ul class="small">
                                            <li><strong>Solo modo único:</strong> Permitido modificar período</li>
                                            <li><strong>Modo múltiple:</strong> Siempre período automático</li>
                                            <li><strong>Validaciones:</strong> Se mantienen para fechas futuras</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Gestión Tipos (solo RRHH) -->
                    <?php if ($esUsuarioRRHH): ?>
                    <div class="tab-pane fade" id="gestion-tipos" role="tabpanel">
                        <h4><i class="fas fa-cogs text-primary me-2"></i>Gestión de Tipos de Novedad</h4>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-user-shield me-2"></i>
                            <strong>Acceso Exclusivo:</strong> Solo disponible para usuarios RRHH. Permite configurar completamente el comportamiento de cada tipo de novedad.
                        </div>

                        <!-- Funcionalidades Principales -->
                        <h5><i class="fas fa-tasks text-success me-2"></i>Funcionalidades de Gestión</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Crear Nuevos Tipos</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Código único:</strong> Identificador interno</li>
                                            <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Descripción:</strong> Nombre visible para usuarios</li>
                                            <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Estado inicial:</strong> Activo por defecto</li>
                                            <li><i class="fas fa-caret-right text-primary me-2"></i><strong>Permisos:</strong> Asignación por tipo de usuario</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Tipos Existentes</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-caret-right text-warning me-2"></i><strong>Modificar descripción:</strong> Cambiar nombre visible</li>
                                            <li><i class="fas fa-caret-right text-warning me-2"></i><strong>Activar/Desactivar:</strong> Controlar disponibilidad</li>
                                            <li><i class="fas fa-caret-right text-warning me-2"></i><strong>Ajustar permisos:</strong> Por tipo de usuario</li>
                                            <li><i class="fas fa-caret-right text-warning me-2"></i><strong>Configurar fechas:</strong> Días de cierre y corte</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración Detallada -->
                        <h5><i class="fas fa-settings text-info me-2"></i>Parámetros Configurables</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar-times me-2"></i>Días de Cierre</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="small"><strong>Opciones disponibles:</strong></p>
                                        <ul class="small">
                                            <li><strong>1-31:</strong> Día específico del mes</li>
                                            <li><strong>"1er día hábil":</strong> Primer día hábil del mes</li>
                                        </ul>
                                        <div class="alert alert-info small">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Define hasta cuándo se puede registrar para el período actual.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-cut me-2"></i>Período de Aplicación</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="small"><strong>Tipos de aplicación:</strong></p>
                                        <ul class="small">
                                            <li><strong>"Período":</strong> Aplica al período calculado</li>
                                            <li><strong>"Fecha Vigencia":</strong> Desde fecha específica</li>
                                            <li><strong>"Período siguiente":</strong> Fuerza al próximo</li>
                                        </ul>
                                        <div class="alert alert-success small">
                                            <i class="fas fa-check me-2"></i>
                                            Controla cómo se aplica la novedad en el tiempo.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Permisos por Usuario</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="small"><strong>Tipos de usuario:</strong></p>
                                        <ul class="small">
                                            <li><i class="fas fa-user-shield text-danger me-2"></i><strong>RRHH:</strong> Acceso total (siempre activo)</li>
                                            <li><i class="fas fa-user-cog text-primary me-2"></i><strong>Administrador:</strong> Configurable</li>
                                            <li><i class="fas fa-user-tie text-info me-2"></i><strong>Comercial:</strong> Configurable</li>
                                            <li><i class="fas fa-industry text-success me-2"></i><strong>Producción:</strong> Configurable</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Proceso de Configuración -->
                        <h5><i class="fas fa-list-ol text-secondary me-2"></i>Proceso de Configuración</h5>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-plus-circle text-primary me-2"></i>Crear Nuevo Tipo:</h6>
                                        <ol class="small">
                                            <li>Hacer clic en "Crear Nuevo Tipo"</li>
                                            <li>Ingresar código único (sin espacios)</li>
                                            <li>Escribir descripción clara y descriptiva</li>
                                            <li>Configurar día de cierre (1-31 o "1er día hábil")</li>
                                            <li>Seleccionar período de aplicación</li>
                                            <li>Marcar permisos por tipo de usuario</li>
                                            <li>Guardar y verificar que aparezca en la lista</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-edit text-warning me-2"></i>Editar Tipo Existente:</h6>
                                        <ol class="small">
                                            <li>Localizar el tipo en la tabla</li>
                                            <li>Hacer clic en el botón "Editar"</li>
                                            <li>Modificar los campos necesarios</li>
                                            <li>Ajustar permisos si es necesario</li>
                                            <li>Cambiar estado activo/inactivo</li>
                                            <li>Guardar cambios</li>
                                            <li>Verificar que los cambios se reflejen</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Consideraciones Importantes -->
                        <h5><i class="fas fa-exclamation-triangle text-danger me-2"></i>Consideraciones Importantes</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-ban me-2"></i>Restricciones</h6>
                                    <ul class="small mb-0">
                                        <li><strong>Código único:</strong> No se puede repetir</li>
                                        <li><strong>Tipos con novedades:</strong> No se pueden eliminar</li>
                                        <li><strong>RRHH siempre activo:</strong> No se puede desactivar</li>
                                        <li><strong>Validación inmediata:</strong> Cambios se aplican de inmediato</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-lightbulb me-2"></i>Mejores Prácticas</h6>
                                    <ul class="small mb-0">
                                        <li><strong>Nombres descriptivos:</strong> Usar descripciones claras</li>
                                        <li><strong>Códigos consistentes:</strong> Seguir convención de naming</li>
                                        <li><strong>Pruebas graduales:</strong> Activar por etapas</li>
                                        <li><strong>Documentar cambios:</strong> Mantener registro de modificaciones</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Impacto de los Cambios -->
                        <div class="alert alert-warning mt-3">
                            <h6><i class="fas fa-sync-alt me-2"></i>Impacto de los Cambios</h6>
                            <p class="small mb-0">
                                Los cambios en la configuración de tipos se aplican <strong>inmediatamente</strong> a todos los usuarios. 
                                Los tipos desactivados dejan de estar disponibles para crear nuevas novedades, pero las existentes se mantienen sin cambios.
                            </p>
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
