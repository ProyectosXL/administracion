<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: login.php');
    exit();
}
include 'templates/layout/header.php';
?>

<main>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">
            <i class="fa-solid fa-file-invoice-dollar text-primary"></i>
            Cobranzas Pendientes
        </h1>
        <div class="d-flex align-items-center">
            <button class="btn btn-outline-primary me-2 shadow-sm" id="btn-toggle-charts"
                title="Ocultar/Mostrar Gráficos">
                <i class="fa-solid fa-chart-line"></i>
            </button>
            <button class="btn btn-outline-secondary shadow-sm" id="btn-abrir-parametros" data-bs-toggle="modal"
                data-bs-target="#parametrosModal" title="Gestionar Parámetros">
                <i class="fa-solid fa-gear fa-spin-hover"></i>
            </button>
        </div>
    </div>

    <!-- Tarjetas de Resumen de Deuda (Visible por defecto) -->
    <div class="row mb-4" id="summary-cards">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-left-primary h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Neto a Cobrar
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="summary-total-neto">-</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-left-success h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Comprobantes
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="summary-total-comprobantes">-</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-file-invoice fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-left-info h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Clientes con Deuda</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="summary-total-clientes">-</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard de Gestión de Propuestas (Oculto por defecto) -->
    <div id="gestion-dashboard" style="display: none;">

        <!-- ======================= INICIO DEL BLOQUE A REEMPLAZAR ======================= -->
        <div class="row mb-4">
            <!-- Propuestas Activas -->
            <div class="col-xl col-md-6 mb-4">
                <div class="card shadow-sm border-left-primary h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Propuestas Activas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-propuestas-activas">-</div>
                    </div>
                </div>
            </div>
            <!-- Monto en Negociación -->
            <div class="col-xl col-md-6 mb-4">
                <div class="card shadow-sm border-left-info h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Monto en Negociación</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-monto-negociacion">-</div>
                    </div>
                </div>
            </div>
            <!-- NUEVA TARJETA: Monto Vencido -->
            <div class="col-xl col-md-6 mb-4">
                <div class="card shadow-sm border-left-danger h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Monto Vencido</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-monto-vencido">-</div>
                    </div>
                </div>
            </div>
            <!-- Requieren Acción -->
            <div class="col-xl col-md-6 mb-4">
                <div class="card shadow-sm border-left-warning h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Requieren Acción</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-requieren-accion">-</div>
                    </div>
                </div>
            </div>
            <!-- Aceptadas (Últ. 30 días) -->
            <div class="col-xl col-md-6 mb-4">
                <div class="card shadow-sm border-left-success h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Aceptadas (Últ. 30 días)
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-aceptadas-mes">-</div>
                    </div>
                </div>
            </div>
            <!-- NUEVA TARJETA: Promedio Plazo -->
            <div class="col-xl col-md-6 mb-4">
                <div class="card shadow-sm border-left-indigo h-100 py-2" style="border-left: 0.25rem solid #6610f2 !important;">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-indigo text-uppercase mb-1" style="color: #6610f2;">Promedio Plazo (Días)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-promedio-plazo">-</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ======================== FIN DEL BLOQUE A REEMPLAZAR ========================= -->
        <div class="row" id="charts-row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white font-weight-bold">Distribución de Estados</div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div style="position: relative; height: 280px; width: 100%;"><canvas id="chartEstados"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white font-weight-bold">Propuestas Aceptadas (Últimos 7 Días)</div>
                    <div class="card-body">
                        <div style="position: relative; height: 280px; width: 100%;"><canvas
                                id="chartActividadReciente"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white pb-0">
            <ul class="nav nav-tabs card-header-tabs" id="cobranzasTab" role="tablist">
                <?php if (trim(strtolower($_SESSION['usuario_nombre'] ?? '')) === 'vvillarreal'): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="mayoristas-tab" data-bs-toggle="tab"
                        data-bs-target="#mayoristas" type="button" role="tab">
                        <i class="fa-solid fa-building me-1"></i> Mayoristas
                    </button>
                </li>
                <?php endif; ?>
                <?php if (trim(strtolower($_SESSION['usuario_nombre'] ?? '')) !== 'vvillarreal'): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="franquicias-tab" data-bs-toggle="tab"
                        data-bs-target="#franquicias" type="button" role="tab">
                        <i class="fa-solid fa-store me-1"></i> Franquicias
                    </button>
                </li>
                <?php endif; ?>
                <?php if (trim(strtolower($_SESSION['usuario_nombre'] ?? '')) !== 'vvillarreal'): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="franquicias-ga-tab" data-bs-toggle="tab"
                        data-bs-target="#franquicias-ga" type="button" role="tab">
                        <i class="fa-solid fa-file-invoice me-1"></i> Franquicias GA
                    </button>
                </li>
                <?php endif; ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="gestion-tab" data-bs-toggle="tab" data-bs-target="#gestion"
                        type="button" role="tab">
                        <i class="fa-solid fa-tasks me-1"></i> Gestión de Propuestas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cronograma-tab" data-bs-toggle="tab" data-bs-target="#cronograma"
                        type="button" role="tab">
                        <i class="fa-solid fa-calendar-alt me-1"></i> Cronograma
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reporte-gerencial-tab" data-bs-toggle="tab" data-bs-target="#reporte-gerencial"
                        type="button" role="tab">
                        <i class="fa-solid fa-chart-pie me-1"></i> Reporte Gerencial
                    </button>
                </li>
                <?php if (trim(strtolower($_SESSION['usuario_nombre'] ?? '')) !== 'vvillarreal'): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sugerencias-tab" data-bs-toggle="tab" data-bs-target="#sugerencias"
                        type="button" role="tab">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Sugerencias
                    </button>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="cobranzasTabContent">
                <!-- Dentro de <div class="tab-pane fade" id="gestion" ...> -->
                <div class="tab-pane fade" id="gestion" role="tabpanel" aria-labelledby="gestion-tab">

                    <!-- BLOQUE DE FILTROS -->
                    <div class="card border-0 bg-light mb-3 shadow-sm">
                        <div class="card-body p-3">
                            <form id="filter-form-gestion" class="row g-2 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Creación Desde</label>
                                    <input type="date" class="form-control form-control-sm" id="filter-fecha-desde">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Creación Hasta</label>
                                    <input type="date" class="form-control form-control-sm" id="filter-fecha-hasta">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Código Cliente</label>
                                    <input type="text" class="form-control form-control-sm" id="filter-codigo"
                                        placeholder="Ej: FRV01">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Razón Social</label>
                                    <input type="text" class="form-control form-control-sm" id="filter-razon-social"
                                        placeholder="Nombre del cliente...">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Estado</label>
                                    <select class="form-select form-select-sm" id="filter-estado">
                                        <option value="">Todos</option>
                                        <option value="PENDIENTE_APROBACION_CLIENTE">Pendiente Cliente</option>
                                        <option value="CONTRAPROPUESTA_CLIENTE">Contrapropuesta</option>
                                        <option value="ACEPTADA">Aceptada</option>
                                        <option value="PENDIENTE_APROBACION_FINAL">Pendiente Final</option>
                                        <option value="PAGADO">Pagado</option>
                                        <option value="VENCIDA">Vencida</option>
                                    </select>
                                </div>
                                <div class="col-md-1 d-flex gap-1">
                                    <button type="button" class="btn btn-primary btn-sm w-100" id="btn-aplicar-filtros"
                                        title="Filtrar">
                                        <i class="fa-solid fa-filter"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                                        id="btn-limpiar-filtros" title="Limpiar">
                                        <i class="fa-solid fa-eraser"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="tabla-gestion-propuestas" class="table table-striped table-hover" style="width:100%">
                        </table>
                    </div>
                </div>
                <!-- ======================= PANEL PROFESIONALIZADO DE CRONOGRAMA ======================= -->
                <div class="tab-pane fade" id="cronograma" role="tabpanel">

                    <!-- Fila de KPIs específicos del Cronograma (sin cambios) -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card shadow-sm border-left-info h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total a Vencer
                                        (Este Mes)</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800" id="cronograma-kpi-mes">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-left-success h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total a
                                        Vencer (Esta Semana)</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800" id="cronograma-kpi-semana">-
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-left-primary h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Propuestas a
                                        Vencer (Este Mes)</div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800" id="cronograma-kpi-cantidad">-
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ======================= INICIO DEL NUEVO LAYOUT DE 2 COLUMNAS ======================= -->
                    <div class="row">
                        <!-- Columna para el Calendario (más grande) -->
                        <div class="col-lg-8 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <div id="fullcalendar-admin"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna para los Próximos Vencimientos (más pequeña) -->
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fa-solid fa-hourglass-start me-2"></i>Próximos
                                        Vencimientos</h6>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush" id="proximos-vencimientos-lista">
                                        <li class="list-group-item text-muted">Cargando...</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ======================== FIN DEL NUEVO LAYOUT DE 2 COLUMNAS ========================= -->
                </div>
                <!-- ==================================================================================== -->
                <?php if (trim(strtolower($_SESSION['usuario_nombre'] ?? '')) === 'vvillarreal'): ?>
                <div class="tab-pane fade show active" id="mayoristas" role="tabpanel"
                    aria-labelledby="mayoristas-tab">
                    <div class="table-responsive">
                        <table id="tabla-mayoristas" class="table table-striped table-hover" style="width:100%">
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (trim(strtolower($_SESSION['usuario_nombre'] ?? '')) !== 'vvillarreal'): ?>
                <div class="tab-pane fade show active" id="franquicias" role="tabpanel"
                    aria-labelledby="franquicias-tab">
                    <div class="table-responsive">
                        <table id="tabla-franquicias" class="table table-striped table-hover" style="width:100%">
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (trim(strtolower($_SESSION['usuario_nombre'] ?? '')) !== 'vvillarreal'): ?>
                <!-- ======================= LIQUIDACIÓN SEMANAL FRANQUICIAS GA ======================= -->
                <div class="tab-pane fade" id="franquicias-ga" role="tabpanel" aria-labelledby="franquicias-ga-tab">

                    <!-- Aviso: franquicias del canal sin fecha de alta (no se liquidan) -->
                    <div class="alert alert-warning d-none" id="fga-aviso-sin-alta" role="alert"></div>

                    <!-- KPIs del conjunto filtrado -->
                    <div class="row mb-3">
                        <div class="col-md-3 mb-3">
                            <div class="card shadow-sm border-left-primary h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Liquidado</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="fga-kpi-importe">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card shadow-sm border-left-success h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cobrado</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="fga-kpi-cobrado">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card shadow-sm border-left-danger h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Saldo Pendiente</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="fga-kpi-saldo">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card shadow-sm border-left-info h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Comprobantes</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="fga-kpi-comprobantes">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="card border-0 bg-light mb-3 shadow-sm">
                        <div class="card-body p-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Período Desde</label>
                                    <input type="date" class="form-control form-control-sm" id="fga-desde">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Período Hasta</label>
                                    <input type="date" class="form-control form-control-sm" id="fga-hasta">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Franquicia</label>
                                    <select class="form-select form-select-sm" id="fga-sucursal">
                                        <option value="">Todas</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Estado del Lote</label>
                                    <select class="form-select form-select-sm" id="fga-estado">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex gap-1">
                                    <button type="button" class="btn btn-primary btn-sm" id="fga-btn-filtrar" title="Filtrar">
                                        <i class="fa-solid fa-filter"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="fga-btn-limpiar" title="Limpiar filtros">
                                        <i class="fa-solid fa-eraser"></i>
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm ms-auto" id="fga-btn-generar"
                                        title="Reprocesar manualmente si falló el job del lunes">
                                        <i class="fa-solid fa-rotate me-1"></i> Generar lote manual
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="tabla-franq-ga-resumen" class="table table-striped table-hover align-middle" style="width:100%">
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <!-- ================================================================================== -->
                <?php if (trim(strtolower($_SESSION['usuario_nombre'] ?? '')) !== 'vvillarreal'): ?>
                <div class="tab-pane fade" id="sugerencias" role="tabpanel" aria-labelledby="sugerencias-tab">
                    <div class="card bg-light mb-3 shadow-sm border-left-primary">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="m-0 font-weight-bold text-primary"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Propuestas Sugeridas Automáticamente</h6>
                                <p class="small text-muted mb-0">Basado en comprobantes pendientes ordenados por Fecha de Emisión.</p>
                            </div>
                            <button class="btn btn-primary btn-sm shadow-sm" id="btn-generar-sugerencias">
                                <i class="fa-solid fa-sync me-1"></i> Recalcular Sugerencias
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="tabla-sugerencias" class="table table-striped table-hover" style="width:100%">
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <!-- ======================= REPORTE GERENCIAL CONSOLIDADO ======================= -->
                <div class="tab-pane fade" id="reporte-gerencial" role="tabpanel">
                    <div class="p-3">
                        <!-- Filtros del Reporte Gerencial -->
                        <div class="card border-0 bg-light mb-4 shadow-sm">
                            <div class="card-body p-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-calendar-day me-1"></i> Período Desde</label>
                                        <input type="date" class="form-control form-control-sm" id="rep-gerencial-desde">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-calendar-day me-1"></i> Período Hasta</label>
                                        <input type="date" class="form-control form-control-sm" id="rep-gerencial-hasta">
                                    </div>
                                    <div class="col-md-3 d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm px-3" id="btn-filtrar-reporte-gerencial">
                                            <i class="fa-solid fa-filter me-1"></i> Aplicar Filtro
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-reporte-gerencial" title="Últimos 90 días">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <span class="badge bg-white text-dark border px-3 py-2 small shadow-xs">
                                            <i class="fa-solid fa-chart-line text-primary me-1"></i> Vista Estratégica Gerencial
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPIs Ejecutivos Principales -->
                        <div class="row g-3 mb-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-success bg-white">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="text-uppercase small fw-bold text-muted">Total Recaudado</span>
                                            <div class="rounded-circle bg-soft-success p-2">
                                                <i class="fa-solid fa-sack-dollar text-success fs-5"></i>
                                            </div>
                                        </div>
                                        <h4 class="fw-bold mb-0 text-success" id="rep-kpi-total-recaudado">$ 0</h4>
                                        <small class="text-muted">Propuestas canceladas en el período</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-primary bg-white">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="text-uppercase small fw-bold text-muted">Propuestas Totales</span>
                                            <div class="rounded-circle bg-soft-primary p-2">
                                                <i class="fa-solid fa-file-invoice-dollar text-primary fs-5"></i>
                                            </div>
                                        </div>
                                        <h4 class="fw-bold mb-0 text-primary" id="rep-kpi-total-propuestas">0</h4>
                                        <small class="text-muted">Gestionadas en el período</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-warning bg-white">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="text-uppercase small fw-bold text-muted">Tasa de Negociación</span>
                                            <div class="rounded-circle bg-soft-warning p-2">
                                                <i class="fa-solid fa-comments-dollar text-warning fs-5"></i>
                                            </div>
                                        </div>
                                        <h4 class="fw-bold mb-0 text-warning" id="rep-kpi-tasa-contrapropuesta">0 %</h4>
                                        <small class="text-muted">% con Contrapropuesta generada</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-4 border-info bg-white">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="text-uppercase small fw-bold text-muted">Plazo Real vs Solicitado</span>
                                            <div class="rounded-circle bg-soft-info p-2">
                                                <i class="fa-solid fa-stopwatch text-info fs-5"></i>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-baseline gap-2">
                                            <h4 class="fw-bold mb-0 text-dark" id="rep-kpi-plazo-real">0 d</h4>
                                            <span class="text-muted small">vs solic. <strong id="rep-kpi-plazo-inicial">0 d</strong></span>
                                        </div>
                                        <small class="text-muted">Promedio efectivo de pago</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla Detallada por Cliente / Franquicia -->
                        <style>
                            /* ==================== ESTILOS PREMIUM REPORTE GERENCIAL ==================== */
                            #tabla-reporte-gerencial_wrapper .dt-buttons {
                                margin-bottom: 12px;
                            }
                            #tabla-reporte-gerencial_wrapper .dt-buttons .btn {
                                border-radius: 6px;
                                font-size: 0.82rem;
                                font-weight: 600;
                                padding: 6px 14px;
                                box-shadow: 0 1px 3px rgba(0,0,0,0.06);
                            }
                            #tabla-reporte-gerencial {
                                border-collapse: separate !important;
                                border-spacing: 0 !important;
                                font-size: 0.88rem;
                            }
                            #tabla-reporte-gerencial thead th {
                                background-color: #f1f5f9 !important;
                                color: #334155 !important;
                                font-weight: 700 !important;
                                font-size: 0.8rem !important;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                                padding: 12px 10px !important;
                                border-bottom: 2px solid #cbd5e1 !important;
                                vertical-align: middle;
                            }
                            #tabla-reporte-gerencial tbody tr {
                                transition: all 0.2s ease;
                            }
                            #tabla-reporte-gerencial tbody tr:hover {
                                background-color: #f8fafc !important;
                                transform: scale(1.001);
                                box-shadow: inset 0 0 0 1px #e2e8f0;
                            }
                            #tabla-reporte-gerencial tbody td {
                                padding: 12px 10px !important;
                                vertical-align: middle;
                                border-bottom: 1px solid #f1f5f9;
                            }
                            .matrix-progress-bar {
                                height: 5px;
                                border-radius: 3px;
                                background-color: #e2e8f0;
                                overflow: hidden;
                                margin-top: 4px;
                            }
                            .matrix-chip {
                                display: inline-flex;
                                align-items: center;
                                font-weight: 600;
                                padding: 3px 10px;
                                border-radius: 20px;
                                font-size: 0.78rem;
                            }
                        </style>

                        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                            <div class="card-header bg-white py-3 px-4 border-bottom border-light d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark fs-6">
                                        <i class="fa-solid fa-layer-group me-2 text-primary"></i> Matriz de Hábitos de Pago y Negociación por <?php echo (trim(strtolower($_SESSION['usuario_nombre'] ?? '')) === 'vvillarreal') ? 'Mayorista' : 'Franquicia'; ?>
                                    </h6>
                                    <small class="text-muted">Ordenado de mayor a menor tasa de contrapropuestas</small>
                                </div>
                                <span class="badge bg-light text-primary border px-3 py-2 fw-semibold">
                                    <i class="fa-solid fa-bolt me-1"></i> Análisis de Riesgo y Flexibilidad
                                </span>
                            </div>
                            <div class="card-body p-4 pt-3">
                                <div class="table-responsive">
                                    <table id="tabla-reporte-gerencial" class="table table-hover align-middle w-100">
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ======================= MODAL DETALLE DE PROPUESTAS POR CLIENTE (REPORTE GERENCIAL) ======================= -->
<div class="modal fade" id="modalDetalleClienteReporte" tabindex="-1" aria-labelledby="modalDetalleClienteReporteLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold" id="modalDetalleClienteReporteLabel">
                        <i class="fa-solid fa-list-check me-2"></i> Auditoría de Propuestas y Plazos Efectivos
                    </h5>
                    <small class="opacity-75" id="modalDetalleClienteSubtitulo">Detalle exhaustivo de cómo se calculan los plazos</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalDetalleClienteBody">
                <!-- Se llena con JS -->
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ======================= NUEVO MODAL DE CONFIRMACIÓN DE ELIMINACIÓN ======================= -->
<div class="modal fade" id="confirmDeletePropuestaModal" tabindex="-1"
    aria-labelledby="confirmDeletePropuestaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeletePropuestaModalLabel"><i
                        class="fa-solid fa-triangle-exclamation me-2"></i>Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás realmente seguro de que deseas eliminar esta propuesta de pago?</p>
                <p class="text-danger"><strong>Esta acción es irreversible</strong> y borrará todos sus datos asociados
                    (items, historial y adjuntos).</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-delete-propuesta">Sí, Eliminar
                    Propuesta</button>
            </div>
        </div>
    </div>
</div>
<!-- ======================================================================================== -->

<!-- ======================= NUEVO MODAL PARA DETALLE DEL DÍA DEL CRONOGRAMA ======================= -->
<div class="modal fade" id="cronogramaDiaModal" tabindex="-1" aria-labelledby="cronogramaDiaModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cronogramaDiaModalLabel">Detalles del Día</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cronogramaDiaModalBody">
                <!-- El contenido se generará dinámicamente con JavaScript -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- ======================= MODAL PARA DETALLE DE SUGERENCIA ======================= -->
<div class="modal fade" id="modalSugerenciaDetalle" tabindex="-1" aria-labelledby="modalSugerenciaDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalSugerenciaDetalleLabel">
                    <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Propuesta Sugerida
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="sugerencia-detalle-body">
                <!-- Se llenará dinámicamente con JS imitando renderizarDetallePropuestaAdmin -->
                <div class="text-center p-5"><div class="spinner-border" role="status"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success me-auto" id="btn-exportar-sugerencia-excel">
                    <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
                </button>
                <button type="button" class="btn btn-success" id="btn-crear-propuesta-desde-sug">
                    <i class="fa-solid fa-paper-plane me-1"></i> Generar y Enviar Propuesta
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- ======================================================================================== -->

<!-- ======================= MODAL DETALLE DEL LOTE (FRANQUICIAS GA) ======================= -->
<div class="modal fade" id="modalFranqGaDetalle" tabindex="-1" aria-labelledby="modalFranqGaDetalleLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalFranqGaDetalleLabel">
                    <i class="fa-solid fa-list-ul me-2"></i>
                    Detalle de la liquidación &mdash; <span id="fga-detalle-titulo"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="fga-detalle-resumen" class="mb-3"></div>
                <div class="alert alert-light border small py-2">
                    <i class="fa-solid fa-circle-info text-warning me-1"></i>
                    Las filas resaltadas en amarillo son <strong>rezagados</strong>: comprobantes de períodos
                    anteriores que no habían entrado en ningún lote. Las filas en itálica son artículos que no
                    tenían precio en la Lista 30 al momento de generar el lote.
                </div>
                <div class="table-responsive">
                    <table id="tabla-franq-ga-detalle" class="table table-sm table-hover align-middle" style="width:100%">
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ======================= MODAL RECIBOS DEL LOTE (FRANQUICIAS GA) ======================= -->
<div class="modal fade" id="modalFranqGaRecibos" tabindex="-1" aria-labelledby="modalFranqGaRecibosLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalFranqGaRecibosLabel">
                    <i class="fa-solid fa-link me-2"></i>
                    Recibos de cobranza &mdash; <span id="fga-recibos-titulo"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded p-2 bg-light">
                            <div class="text-xs text-uppercase text-muted">Importe del lote</div>
                            <div class="h5 mb-0 fw-bold" id="fga-recibos-importe">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 bg-light">
                            <div class="text-xs text-uppercase text-muted">Cobrado</div>
                            <div class="h5 mb-0 fw-bold" id="fga-recibos-cobrado">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 bg-light">
                            <div class="text-xs text-uppercase text-muted">Saldo pendiente</div>
                            <div class="h5 mb-0 fw-bold" id="fga-recibos-saldo">-</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-success"><i class="fa-solid fa-magnifying-glass-dollar me-1"></i> Recibos candidatos</h6>
                <p class="small text-muted">
                    Ordenados por proximidad al saldo pendiente. Los resaltados en verde coinciden
                    <strong>exactamente</strong> con el saldo.
                </p>
                <div class="table-responsive mb-4">
                    <table id="tabla-franq-ga-recibos" class="table table-sm table-hover align-middle" style="width:100%">
                    </table>
                </div>

                <h6 class="fw-bold text-primary"><i class="fa-solid fa-paperclip me-1"></i> Recibos ya vinculados</h6>
                <div id="fga-recibos-vinculados" class="border rounded p-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- ======================= MODAL ENVÍO DE LIQUIDACIÓN POR MAIL (FRANQUICIAS GA) ======================= -->
<div class="modal fade" id="modalFranqGaMail" tabindex="-1" aria-labelledby="modalFranqGaMailLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalFranqGaMailLabel">
                    <i class="fa-solid fa-envelope me-2"></i>
                    Enviar liquidación por mail &mdash; <span id="fga-mail-titulo"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <!-- Aviso: ya se envió antes -->
                <div class="alert alert-warning d-none" id="fga-mail-aviso-enviado" role="alert"></div>

                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold" for="fga-mail-destinatario">Para</label>
                        <input type="email" class="form-control" id="fga-mail-destinatario"
                            placeholder="mail@franquicia.com" autocomplete="off">
                        <div class="invalid-feedback">Ingresá una dirección de mail válida.</div>
                        <div class="form-text d-none text-danger" id="fga-mail-sin-mail">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                            Esta franquicia <strong>no tiene mail cargado</strong> en SUCURSALES_LAKERS.
                            Escribilo acá para enviar igual, y conviene cargarlo en la tabla para la próxima.
                        </div>
                        <div class="form-text">Tomado de <code>SUCURSALES_LAKERS.MAIL</code>. Se puede modificar.</div>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-bold" for="fga-mail-asunto">Asunto</label>
                        <input type="text" class="form-control" id="fga-mail-asunto" maxlength="300">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Adjunto</label>
                    <div class="border rounded p-2 bg-light small" id="fga-mail-adjunto-info">Preparando adjunto...</div>
                    <div class="form-text">
                        Detalle completo de comprobantes en Excel, idéntico al que se descarga con el botón Excel del detalle.
                    </div>
                </div>

                <label class="form-label small fw-bold">Vista previa del mail</label>
                <div class="border rounded" style="background: #fff;">
                    <!-- srcdoc: se renderiza el HTML exacto que arma el servidor, aislado del CSS de la página -->
                    <iframe id="fga-mail-preview" title="Vista previa del mail" sandbox=""
                        style="width: 100%; height: 520px; border: 0; display: block;" srcdoc=""></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="fga-btn-enviar-mail" disabled>
                    <i class="fa-solid fa-paper-plane me-1"></i> Enviar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ====================================================================================== -->

<?php
include 'templates/layout/footer.php';
?>

<script>
    const globalUsuarioNombre = "<?php echo isset($_SESSION['usuario_nombre']) ? addslashes($_SESSION['usuario_nombre']) : ''; ?>";
</script>
<!-- Scripts específicos para el panel admin -->
<script src="assets/js/sugerencias.js?v=<?= filemtime(__DIR__ . '/assets/js/sugerencias.js') ?>"></script>
<script src="assets/js/franquicias_ga.js?v=<?= filemtime(__DIR__ . '/assets/js/franquicias_ga.js') ?>"></script>
<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>

</body>

</html>