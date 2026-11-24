<?php
// Determinar si el usuario es RRHH para mostrar contenido específico
require_once(__DIR__ . '/../config/usuario_config.php');
$esUsuarioRRHH = ($TIPO_USUARIO_ACTUAL === 4); // 4 = Recursos Humanos
?>

<!-- Modal Manual de Usuario -->
<div class="modal fade" id="manualModal" tabindex="-1" aria-labelledby="manualModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header -->
            <div class="modal-header text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #0d6efd 0%, #2563eb 100%);">
                <h5 class="modal-title fw-semibold" id="manualModalLabel">
                    <i class="fas fa-book-open me-2"></i>Manual de Usuario - Sistema de Novedades RRHH
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Barra de navegación fija superior -->
            <div class="bg-light border-bottom px-4 py-2">
                <ul class="nav nav-pills flex-nowrap overflow-auto gap-1" id="manualTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-nowrap" id="inicio-tab" data-bs-toggle="tab" data-bs-target="#inicio"
                                type="button" role="tab" aria-controls="inicio" aria-selected="true">
                            <i class="fas fa-home me-1"></i>Inicio
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="nueva-novedad-tab" data-bs-toggle="tab" data-bs-target="#nueva-novedad"
                                type="button" role="tab" aria-controls="nueva-novedad" aria-selected="false">
                            <i class="fas fa-plus-circle me-1"></i>Nueva Novedad
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="consultar-tab" data-bs-toggle="tab" data-bs-target="#consultar"
                                type="button" role="tab" aria-controls="consultar" aria-selected="false">
                            <i class="fas fa-search me-1"></i>Consultar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="tipos-tab" data-bs-toggle="tab" data-bs-target="#tipos"
                                type="button" role="tab" aria-controls="tipos" aria-selected="false">
                            <i class="fas fa-tags me-1"></i>Tipos de Novedad
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="periodos-tab" data-bs-toggle="tab" data-bs-target="#periodos"
                                type="button" role="tab" aria-controls="periodos" aria-selected="false">
                            <i class="fas fa-calendar-alt me-1"></i>Períodos y Cierres
                        </button>
                    </li>
                    <?php if ($esUsuarioRRHH): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="gestion-tipos-tab" data-bs-toggle="tab" data-bs-target="#gestion-tipos"
                                type="button" role="tab" aria-controls="gestion-tipos" aria-selected="false">
                            <i class="fas fa-cogs me-1"></i>Gestión de Tipos
                        </button>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Body con scroll único limpio -->
            <div class="modal-body p-4">
                <div class="tab-content" id="manualTabsContent">

                    <!-- Tab Inicio -->
                    <div class="tab-pane fade show active" id="inicio" role="tabpanel">
                        <div class="p-3 p-md-4 rounded-3 border bg-primary-subtle bg-opacity-10 mb-4" style="background-color: #f0f6ff; border-color: #cfe2ff !important;">
                            <div class="row align-items-center">
                                <div class="col-md-9">
                                    <h4 class="text-primary fw-bold mb-1">
                                        <i class="fas fa-rocket me-2"></i>Sistema de Gestión de Novedades RRHH
                                    </h4>
                                    <p class="text-secondary mb-0">
                                        Plataforma integrada con Tango Sueldos y Sucursales Lakers para registrar cambios de personal, comisiones, pluses, premios y movimientos con control de períodos.
                                    </p>
                                </div>
                                <div class="col-md-3 text-md-end mt-2 mt-md-0">
                                    <span class="badge bg-success px-3 py-2">
                                        <i class="fas fa-sync-alt me-1"></i>Integrado con Tango
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="manual-feature-card h-100 p-3 border rounded-3 bg-white">
                                    <div class="manual-icon-circle bg-primary bg-opacity-10 text-primary mb-2">
                                        <i class="fas fa-file-medical"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">Novedad Única</h6>
                                    <p class="small text-muted mb-0">Carga puntual de un concepto (individual o escala global de Premio Local para todos los locales).</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="manual-feature-card h-100 p-3 border rounded-3 bg-white">
                                    <div class="manual-icon-circle bg-success bg-opacity-10 text-success mb-2">
                                        <i class="fas fa-tasks"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">Checklist Múltiple</h6>
                                    <p class="small text-muted mb-0">Formulario guiado de Propuesta, Durante la Prueba y Confirmación para cargar todos los conceptos en un lote.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="manual-feature-card h-100 p-3 border rounded-3 bg-white">
                                    <div class="manual-icon-circle bg-info bg-opacity-10 text-info mb-2">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">Consulta y Exportación</h6>
                                    <p class="small text-muted mb-0">Filtros por empleado, sucursal, estado y rango de fechas, con ordenamiento real y exportación a Excel.</p>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold mb-3"><i class="fas fa-route text-primary me-2"></i>Flujo Rápido de Carga</h5>
                        <div class="row g-3">
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <span class="badge rounded-pill bg-primary mb-2">Paso 1</span>
                                    <div class="fw-bold mb-1">Elegir Modo</div>
                                    <div class="small text-muted">Seleccioná <strong>Novedad Única</strong> o <strong>Checklist Múltiple</strong>.</div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <span class="badge rounded-pill bg-primary mb-2">Paso 2</span>
                                    <div class="fw-bold mb-1">Seleccionar Empleado</div>
                                    <div class="small text-muted">Buscá por nombre o legajo (excepto Premio Local global).</div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <span class="badge rounded-pill bg-primary mb-2">Paso 3</span>
                                    <div class="fw-bold mb-1">Completar Conceptos</div>
                                    <div class="small text-muted">Cargá fechas, porcentajes, sucursales o marcas del checklist.</div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <span class="badge rounded-pill bg-success mb-2">Paso 4</span>
                                    <div class="fw-bold mb-1">Confirmar y Guardar</div>
                                    <div class="small text-muted">El sistema asigna el período y guarda de forma atómica.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Nueva Novedad -->
                    <div class="tab-pane fade" id="nueva-novedad" role="tabpanel">
                        <h5 class="fw-bold text-primary mb-3"><i class="fas fa-plus-circle me-2"></i>Modos de Carga en Nueva Novedad</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-header bg-primary text-white py-2">
                                        <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Modo 1: Novedad Única</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="small mb-0 ps-3">
                                            <li class="mb-1"><strong>Búsqueda de Empleado:</strong> Trae legajo, centro de costos y puesto actual desde Tango sin duplicados.</li>
                                            <li class="mb-1"><strong>Premio Local global:</strong> Oculta la selección de empleado y actualiza la escala de 3 niveles para <em>todos los locales</em> (los 6 importes son obligatorios).</li>
                                            <li class="mb-1"><strong>Comisión Individual (Supervisoras):</strong> Permite agregar múltiples sucursales <strong>PROPIOS</strong> con `%` y tope opcional dentro de una sola novedad.</li>
                                            <li class="mb-0"><strong>Vencimiento opcional:</strong> En Cambio de Sucursal y Pluses, si la fecha de vencimiento se deja vacía, se considera <em>Sin vencimiento</em>.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-header bg-success text-white py-2">
                                        <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Modo 2: Checklist Múltiple Predeterminado</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="small mb-0 ps-3">
                                            <li class="mb-1"><strong>Encabezado de Propuesta:</strong> Precarga Local origen y Posición actual desde Tango; permite indicar Local destino, Puesto a prueba, Inicio y Finalización.</li>
                                            <li class="mb-1"><strong>Bloque DURANTE LA PRUEBA:</strong> Permite habilitar Reemplazo temporario, Cambio de Sucursal, Pluses (`SI/NO` o `Recibo/Premio`), Comisiones, Premio Obj Loc (`Vendedora`/`Encargada`), Ajuste Gral. y Sueldo Básico.</li>
                                            <li class="mb-1"><strong>Bloque CONFIRMACIÓN:</strong> Incluye Nuevo Puesto con selección de <strong>Convenio (`Comercio` / `FC`)</strong> y condiciones definitivas al efectivizarse.</li>
                                            <li class="mb-0"><strong>Guardado en lote atómico:</strong> Todas las novedades tildadas se validan y guardan juntas bajo un mismo `lote_id`.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mb-0 small">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Puestos unificados:</strong> Todos los listados de puestos se obtienen directamente de <code>TAREA_HABITUAL</code> en Tango (por ejemplo, <code>SUB ENCARGADA</code>).
                        </div>
                    </div>

                    <!-- Tab Consultar -->
                    <div class="tab-pane fade" id="consultar" role="tabpanel">
                        <h5 class="fw-bold text-primary mb-3"><i class="fas fa-search me-2"></i>Consultar, Ordenar y Exportar Novedades</h5>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <h6 class="fw-bold text-primary"><i class="fas fa-filter me-1"></i>Filtros Combinables</h6>
                                    <ul class="small mb-0 ps-3">
                                        <li>Empleado / Legajo (búsqueda rápida)</li>
                                        <li>Centro de Costos y Tipo de Novedad</li>
                                        <li>Estado (Enviada, En Revisión, Aprobada, A Revisar, Procesada)</li>
                                        <li>Rango <strong>Registro Desde / Hasta</strong></li>
                                        <li>Filtros rápidos: Hoy, Semana, Mes, Período actual / siguiente</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <h6 class="fw-bold text-success"><i class="fas fa-sort me-1"></i>Ordenamiento Real</h6>
                                    <ul class="small mb-0 ps-3">
                                        <li>Hacé clic en cualquier columna (<strong>Registro</strong>, <strong>Legajo</strong>, <strong>Empleado</strong>, <strong>Período</strong>, <strong>Vig. desde</strong>, <strong>Vig. hasta</strong>, <strong>Valor</strong>, <strong>Estado</strong>).</li>
                                        <li>Las fechas se ordenan cronológicamente (año/mes/día).</li>
                                        <li>Los valores vacíos quedan siempre al final de la lista.</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <h6 class="fw-bold text-info"><i class="fas fa-tools me-1"></i>Acciones por Fila</h6>
                                    <ul class="small mb-0 ps-3">
                                        <li><i class="fas fa-eye text-info me-1"></i><strong>Ver detalle:</strong> Muestra tabla de tramos, sucursales PROPIOS o vencimiento.</li>
                                        <li><i class="fas fa-exchange-alt text-secondary me-1"></i><strong>Cambiar estado:</strong> Flujo de revisión RRHH.</li>
                                        <li><i class="fas fa-edit text-warning me-1"></i><strong>Editar / Eliminar:</strong> Corrección directa.</li>
                                        <li><i class="fas fa-file-excel text-success me-1"></i><strong>Excel / Imprimir:</strong> Reporte fiel del filtro activo.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning small mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Los botones rápidos de <strong>Período actual / siguiente</strong> filtran por período de liquidación y reemplazan el filtro por fecha de registro calendario.
                        </div>
                    </div>

                    <!-- Tab Tipos -->
                    <div class="tab-pane fade" id="tipos" role="tabpanel">
                        <h5 class="fw-bold text-primary mb-3"><i class="fas fa-tags me-2"></i>Referencia de Tipos de Novedad Activos</h5>

                        <div class="accordion" id="tiposAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#tCambioSucursal">
                                        <i class="fas fa-building text-primary me-2"></i>Cambio de Sucursal / Centro de Costos (ID 1)
                                    </button>
                                </h2>
                                <div id="tCambioSucursal" class="accordion-collapse collapse show" data-bs-parent="#tiposAccordion">
                                    <div class="accordion-body small">
                                        Registra el traslado hacia una nueva sucursal o área con <strong>Fecha de vigencia</strong> obligatoria y <strong>Fecha de vencimiento opcional</strong> (vacía = <em>Sin vencimiento</em>).
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tPuestoReemp">
                                        <i class="fas fa-user-tag text-success me-2"></i>Nueva Posición (ID 2) y Reemplazo (ID 53)
                                    </button>
                                </h2>
                                <div id="tPuestoReemp" class="accordion-collapse collapse" data-bs-parent="#tiposAccordion">
                                    <div class="accordion-body small">
                                        Los puestos se cargan desde Tango (<code>TAREA_HABITUAL</code>). En <strong>Nueva Posición</strong> desde el Checklist de Confirmación se informa además el <strong>Convenio</strong> (<code>Comercio</code> o <code>FC</code>). En <strong>Reemplazo</strong> temporario se indica fecha desde y hasta.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tPluses">
                                        <i class="fas fa-plus-circle text-info me-2"></i>Pluses: Caja (32), Sub-Encargada (33) y Encargada (34)
                                    </button>
                                </h2>
                                <div id="tPluses" class="accordion-collapse collapse" data-bs-parent="#tiposAccordion">
                                    <div class="accordion-body small">
                                        No llevan importes. En <strong>Plus de Caja</strong> se elige <code>Recibo</code> o <code>Premio</code>; en <strong>Plus de Sub-Encargada</strong> (equivalente al Plus de Caja) y <strong>Plus de Encargada</strong> se indica <code>SI</code> o <code>NO</code>. Todos admiten fecha de vencimiento opcional.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tPremioLocal">
                                        <i class="fas fa-trophy text-warning me-2"></i>Premio Local (ID 35) y Ajustes de Premios (ID 4 y 38)
                                    </button>
                                </h2>
                                <div id="tPremioLocal" class="accordion-collapse collapse" data-bs-parent="#tiposAccordion">
                                    <div class="accordion-body small">
                                        <ul class="mb-0 ps-3">
                                            <li><strong>Premio Local (Carga Única):</strong> Actualización global para <strong>todos los locales</strong> en 3 tramos obligatorios (<em>1ro en ranking</em>, <em>Cumple objetivo</em>, <em>1ro que no llega</em>) para Encargada/Sub y Vendedora.</li>
                                            <li><strong>Premio Obj Loc (Checklist):</strong> Indica si aplica <code>Vendedora</code> o <code>Encargada</code> para ese empleado.</li>
                                            <li><strong>Ajuste de Premios (4) / Ajuste General (38):</strong> El importe es <strong>no obligatorio</strong>.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tComisiones">
                                        <i class="fas fa-percentage text-primary me-2"></i>Comisión Individual (ID 36) y Comisión sobre Local (ID 37)
                                    </button>
                                </h2>
                                <div id="tComisiones" class="accordion-collapse collapse" data-bs-parent="#tiposAccordion">
                                    <div class="accordion-body small">
                                        <ul class="mb-0 ps-3">
                                            <li><strong>Comisión Individual:</strong> Para Supervisoras permite seleccionar múltiples sucursales <strong>PROPIOS</strong>, definir <code>%</code> y tope opcional por cada una en una única novedad.</li>
                                            <li><strong>Comisión sobre Local:</strong> Permite <em>Sin Tope</em> o <em>Con Tope</em> (guía orientativa <code>0,30% - 1,00%</code> y <code>0,15% - 0,50%</code>, rango de <code>0,05%</code>).</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tOtros">
                                        <i class="fas fa-clock text-secondary me-2"></i>Aumento Salarial (54), Horas (5/6) y Permisos (7)
                                    </button>
                                </h2>
                                <div id="tOtros" class="accordion-collapse collapse" data-bs-parent="#tiposAccordion">
                                    <div class="accordion-body small">
                                        <strong>Aumento Salarial</strong> admite porcentaje, monto fijo o escala de Sueldo Básico (<code>Vendedora</code>/<code>Encargada</code> desde Checklist). <strong>Horas Extras/Adicionales</strong> registran cantidad de horas y <strong>Permisos</strong> registran fecha y si compensa o no.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Períodos -->
                    <div class="tab-pane fade" id="periodos" role="tabpanel">
                        <h5 class="fw-bold text-primary mb-3"><i class="fas fa-calendar-alt me-2"></i>Lógica de Períodos y Días de Cierre</h5>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <h6 class="fw-bold text-success mb-2"><i class="fas fa-cogs me-1"></i>¿Cómo se calcula el período?</h6>
                                    <ol class="small mb-0 ps-3">
                                        <li>Se consulta el <strong>Día de Cierre</strong> y el <strong>Período de Aplicación</strong> configurados para el tipo.</li>
                                        <li>Si el día de cierre es <code>1er día hábil</code>, el sistema calcula el primer día hábil considerando feriados argentinos.</li>
                                        <li>En <strong>Carga Única</strong>, el sistema sugiere el período resultante (editable si corresponde).</li>
                                        <li>En <strong>Checklist Múltiple</strong>, podés elegir el mes y año global en el encabezado.</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <h6 class="fw-bold text-warning mb-2"><i class="fas fa-calendar-check me-1"></i>Ejemplos de Cierre</h6>
                                    <div class="alert alert-success py-2 small mb-2">
                                        <strong>A tiempo:</strong> Carga el 15 del mes con cierre día 20 &rarr; Impacta en el <strong>período actual</strong>.
                                    </div>
                                    <div class="alert alert-warning py-2 small mb-0">
                                        <strong>Posterior al cierre:</strong> Carga el 25 del mes con cierre día 20 &rarr; Se sugiere el <strong>período siguiente</strong>.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Gestión Tipos (solo RRHH) -->
                    <?php if ($esUsuarioRRHH): ?>
                    <div class="tab-pane fade" id="gestion-tipos" role="tabpanel">
                        <h5 class="fw-bold text-primary mb-3"><i class="fas fa-cogs me-2"></i>Gestión de Tipos de Novedad (Solo RRHH)</h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <h6 class="fw-bold mb-2"><i class="fas fa-toggle-on text-success me-1"></i>Permisos en Tiempo Real</h6>
                                    <p class="small text-muted mb-2">Desde la tabla principal podés activar o desactivar rápidamente qué áreas ven cada tipo:</p>
                                    <ul class="small mb-0 ps-3">
                                        <li><strong>Admin:</strong> Usuarios administrativos</li>
                                        <li><strong>Comercial:</strong> Área comercial / locales</li>
                                        <li><strong>Producción:</strong> Área productiva</li>
                                        <li><strong>RRHH:</strong> Recursos Humanos</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <h6 class="fw-bold mb-2"><i class="fas fa-edit text-primary me-1"></i>Edición Completa del Tipo</h6>
                                    <p class="small text-muted mb-0">Con el botón <i class="fas fa-edit text-primary"></i> de cada fila podés editar código, descripción, estado Activo/Inactivo, permisos por perfil, <strong>Día de Cierre</strong> y <strong>Período de Aplicación</strong>. Los cambios impactan de inmediato en toda la aplicación.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer bg-light border-top py-2 px-4">
                <small class="text-muted me-auto">
                    <i class="fas fa-info-circle me-1"></i>Sistema de Gestión de Novedades - Recursos Humanos
                </small>
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
#manualModal .modal-dialog {
    max-width: 1140px;
}

#manualModal .nav-pills .nav-link {
    color: #495057;
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.45rem 0.9rem;
    border-radius: 0.5rem;
    transition: all 0.15s ease;
}

#manualModal .nav-pills .nav-link:hover {
    background-color: #e9ecef;
    color: #0d6efd;
}

#manualModal .nav-pills .nav-link.active {
    background-color: #0d6efd;
    color: #fff;
    box-shadow: 0 2px 6px rgba(13, 110, 253, 0.25);
}

#manualModal .manual-icon-circle {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

#manualModal .manual-feature-card {
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

#manualModal .manual-feature-card:hover {
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.07);
    border-color: #b6d4fe !important;
}

#manualModal .card:hover {
    transform: none;
}

#manualModal .accordion-button {
    font-size: 0.92rem;
    font-weight: 600;
    padding: 0.8rem 1rem;
}

#manualModal .accordion-button:not(.collapsed) {
    background-color: #f0f6ff;
    color: #0d6efd;
}
</style>
