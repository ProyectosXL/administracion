<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}
include 'templates/layout/header.php';
?>

<main class="pb-4">
    <!-- Header Principal y Navegación de Módulos -->
    <div class="card shadow-sm border-0 mb-3 bg-white">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary text-white rounded p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fa-solid fa-boxes-stacked fs-5"></i>
                    </div>
                    <div>
                        <h1 class="h5 mb-0 text-dark fw-bold">Cobranzas Mayoristas</h1>
                        <small class="text-muted" style="font-size: 0.78rem;">Gestión de Cobranzas, WhatsApp y Conciliación (Z3, Z4, Z5)</small>
                    </div>
                </div>

                <!-- Navegación tipo Segmented Control / Tabs modernas -->
                <div class="modulo-nav-container bg-light p-1 rounded-3 border">
                    <ul class="nav nav-pills gap-1" id="mayoristasTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-1 px-3 rounded-2" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#tab-pendientes" type="button" role="tab">
                                <i class="fa-solid fa-hourglass-half me-1"></i> Cobranzas Pendientes
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-1 px-3 rounded-2" id="historial-tab" data-bs-toggle="tab" data-bs-target="#tab-historial" type="button" role="tab">
                                <i class="fa-solid fa-clock-rotate-left me-1"></i> Historial de Gestiones
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-1 px-3 rounded-2" id="reportes-tab" data-bs-toggle="tab" data-bs-target="#tab-reportes" type="button" role="tab">
                                <i class="fa-solid fa-chart-pie me-1"></i> Reportes
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-content" id="mayoristasTabsContent">

        <!-- ========================================================================= -->
        <!-- PESTAÑA 1: COBRANZAS PENDIENTES -->
        <!-- ========================================================================= -->
        <div class="tab-pane fade show active" id="tab-pendientes" role="tabpanel">
            <!-- Tarjetas de Resumen KPIs -->
            <div class="row g-2 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="card shadow-sm border-start border-primary border-4 h-100">
                        <div class="card-body py-2 px-3">
                            <div class="text-xs font-weight-bold text-primary text-uppercase">Total General Pendiente</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-total-pendiente">$ 0</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card shadow-sm border-start border-success border-4 h-100">
                        <div class="card-body py-2 px-3">
                            <div class="text-xs font-weight-bold text-success text-uppercase">Camino 1 (Facturas)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-total-facturas">$ 0</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card shadow-sm border-start border-purple border-4 h-100" style="border-left-color: #6f42c1 !important;">
                        <div class="card-body py-2 px-3">
                            <div class="text-xs font-weight-bold text-uppercase" style="color: #6f42c1;">Camino 2 (Remitos)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-total-remitos">$ 0</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card shadow-sm border-start border-info border-4 h-100">
                        <div class="card-body py-2 px-3">
                            <div class="text-xs font-weight-bold text-info text-uppercase">Clientes con Pendientes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-clientes-pendientes">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros y Acciones -->
            <div class="card shadow-sm mb-3 border-0">
                <div class="card-body py-2 px-3 bg-white rounded">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Filtrar por Vendedor</label>
                            <select class="form-select form-select-sm" id="filtro-vendedor-pendientes">
                                <option value="TODOS">Todos los Vendedores (Z3, Z4, Z5)</option>
                                <option value="Z3">Z3 - VALERIA VILLARREAL</option>
                                <option value="Z4">Z4 - SERGIO LOPEZ COBOS</option>
                                <option value="Z5">Z5 - CRISTIAN NACKE</option>
                            </select>
                        </div>
                        <div class="col-md-8 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
                            <button class="btn btn-success btn-sm shadow-sm" id="btn-exportar-pendientes-excel">
                                <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
                            </button>
                            <button class="btn btn-outline-primary btn-sm shadow-sm" id="btn-refrescar-pendientes">
                                <i class="fa-solid fa-rotate me-1"></i> Actualizar Listado
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Clientes con Pendientes -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-3">
                    <div id="loading-pendientes" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted small mt-2">Cargando cobranzas pendientes...</p>
                    </div>
                    <div class="table-responsive">
                        <table id="tabla-mayoristas-pendientes" class="table table-hover table-striped align-middle" style="width:100%">
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PESTAÑA 2: HISTORIAL Y GESTIÓN DE RECIBOS -->
        <!-- ========================================================================= -->
        <div class="tab-pane fade" id="tab-historial" role="tabpanel">
            <!-- Filtros Historial -->
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body p-3 bg-white rounded">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Vendedor</label>
                            <select class="form-select form-select-sm" id="filtro-vendedor-historial">
                                <option value="TODOS">Todos</option>
                                <option value="Z3">Z3 - VALERIA VILLARREAL</option>
                                <option value="Z4">Z4 - SERGIO LOPEZ COBOS</option>
                                <option value="Z5">Z5 - CRISTIAN NACKE</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Estado</label>
                            <select class="form-select form-select-sm" id="filtro-estado-historial">
                                <option value="TODOS">Todos los Estados</option>
                                <option value="ENVIADO">Enviada (Pendiente)</option>
                                <option value="PAGADO">Pagada</option>
                            </select>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
                            <button class="btn btn-success btn-sm shadow-sm" id="btn-exportar-historial-excel">
                                <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
                            </button>
                            <button class="btn btn-outline-primary btn-sm shadow-sm" id="btn-refrescar-historial">
                                <i class="fa-solid fa-rotate me-1"></i> Refrescar Historial
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Historial -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-3">
                    <div id="loading-historial" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted small mt-2">Cargando historial...</p>
                    </div>
                    <div class="table-responsive">
                        <table id="tabla-mayoristas-historial" class="table table-hover table-striped align-middle" style="width:100%">
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PESTAÑA 3: REPORTES Y DESVÍOS -->
        <!-- ========================================================================= -->
        <div class="tab-pane fade" id="tab-reportes" role="tabpanel">
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-start border-info border-4 h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Cobranzas Enviadas</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="reporte-kpi-enviados">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-start border-success border-4 h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cobranzas Abonadas</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="reporte-kpi-abonados">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-start border-primary border-4 h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Cobrado Real</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="reporte-kpi-monto-cobrado">$ 0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm border-start border-warning border-4 h-100">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Promedio Días a Pago</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="reporte-kpi-promedio-dias">0 días</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla Desempeño por Vendedor -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fa-solid fa-user-check me-2"></i>Desempeño de Cobranzas por Vendedor
                    </h6>
                    <button class="btn btn-success btn-sm shadow-sm" id="btn-exportar-reportes-excel">
                        <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center"></th>
                                    <th>Vendedor</th>
                                    <th class="text-center">Envíos</th>
                                    <th class="text-center">Abonados</th>
                                    <th class="text-center">Efectividad (%)</th>
                                    <th class="text-end">Monto Propuesto</th>
                                    <th class="text-end">Monto Cobrado</th>
                                    <th class="text-center">Promedio Días a Pago</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo-tabla-reporte-vendedores">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- ========================================================================= -->
<!-- MODAL: DESGLOSE DE PENDIENTES (FACTURAS Y REMITOS) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDesgloseCliente" tabindex="-1" aria-labelledby="modalDesgloseClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-5" id="modalDesgloseClienteLabel">
                    <i class="fa-solid fa-list-check me-2"></i>Desglose de Comprobantes Pendientes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Resumen de Cliente -->
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <span class="text-xs text-muted text-uppercase fw-bold d-block">Cliente</span>
                            <h5 class="mb-0 text-primary fw-bold" id="modal-desglose-cliente-nombre">-</h5>
                            <small class="text-muted" id="modal-desglose-vendedor">-</small>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="bg-white p-2 px-3 rounded border text-end">
                                <span class="text-xs text-success text-uppercase fw-bold d-block">Total Facturas</span>
                                <strong class="h6 mb-0 text-success" id="modal-desglose-total-fac">$ 0</strong>
                            </div>
                            <div class="bg-white p-2 px-3 rounded border text-end">
                                <span class="text-xs text-uppercase fw-bold d-block" style="color: #6f42c1;">Total Remitos</span>
                                <strong class="h6 mb-0" style="color: #6f42c1;" id="modal-desglose-total-rem">$ 0</strong>
                            </div>
                            <div class="bg-white p-2 px-3 rounded border text-end border-primary">
                                <span class="text-xs text-primary text-uppercase fw-bold d-block">Total General</span>
                                <strong class="h6 mb-0 text-dark" id="modal-desglose-total-gral">$ 0</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestañas internas para Facturas y Remitos -->
                <ul class="nav nav-tabs nav-pills mb-3" id="desgloseTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="desglose-facturas-tab" data-bs-toggle="tab" data-bs-target="#desglose-facturas" type="button" role="tab">
                            <i class="fa-solid fa-file-invoice-dollar me-1 text-success"></i> Camino 1: Facturas Pendientes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="desglose-remitos-tab" data-bs-toggle="tab" data-bs-target="#desglose-remitos" type="button" role="tab">
                            <i class="fa-solid fa-truck-ramp-box me-1" style="color: #6f42c1;"></i> Camino 2: Remitos Pendientes
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="desgloseTabContent">
                    <!-- Tab Facturas -->
                    <div class="tab-pane fade show active" id="desglose-facturas" role="tabpanel">
                        <div class="table-responsive border rounded">
                            <table class="table table-hover table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Comprobante</th>
                                        <th>Fecha Emisión</th>
                                        <th>Fecha Prob. Cobro</th>
                                        <th class="text-end">Importe Saldo</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpo-tabla-desglose-facturas">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Remitos -->
                    <div class="tab-pane fade" id="desglose-remitos" role="tabpanel">
                        <div class="table-responsive border rounded">
                            <table class="table table-hover table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>N° Remito</th>
                                        <th>Fecha Emisión / Mov.</th>
                                        <th class="text-end">Importe Remito</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpo-tabla-desglose-remitos">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success px-4" id="btn-desglose-ir-wpp">
                    <i class="fa-brands fa-whatsapp me-1"></i> Generar Cobranza WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: GENERAR Y ENVIAR MENSAJE WHATSAPP -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalGenerarWpp" tabindex="-1" aria-labelledby="modalGenerarWppLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fs-5" id="modalGenerarWppLabel">
                    <i class="fa-brands fa-whatsapp me-2"></i>Generar Mensaje de Cobranza WhatsApp
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-light border mb-3">
                    <div class="row align-items-center g-2">
                        <div class="col-md-5">
                            <strong class="d-block h6 mb-0 text-primary" id="modal-wpp-cliente-nombre">-</strong>
                            <small class="text-muted" id="modal-wpp-vendedor">-</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Teléfono WhatsApp</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fa-brands fa-whatsapp text-success"></i></span>
                                <input type="text" class="form-control" id="modal-wpp-telefono" placeholder="Ej: 54911xxxxxxxx">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">Nombre Contacto</label>
                            <input type="text" class="form-control form-control-sm" id="modal-wpp-contacto-nombre" placeholder="Persona...">
                        </div>
                    </div>
                </div>

                <!-- Selección Granular de Comprobantes (Cobros Parcializados) -->
                <div class="card border mb-3">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-dark text-uppercase">
                            <i class="fa-solid fa-list-check me-1 text-primary"></i> Selección de Comprobantes a Integrar
                        </span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 small" id="btn-wpp-seleccionar-todos">Todos</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 small" id="btn-wpp-deseleccionar-todos">Ninguno</button>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <!-- Facturas a integrar -->
                            <div class="col-md-6 border-end" id="cont-wpp-sel-facturas">
                                <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="chk-switch-todas-fac" checked>
                                        <label class="form-check-label fw-bold text-success small" for="chk-switch-todas-fac">
                                            <i class="fa-solid fa-file-invoice-dollar me-1"></i> Facturas
                                        </label>
                                    </div>
                                    <span class="badge bg-success" id="badge-total-fac-wpp">$ 0</span>
                                </div>
                                <div id="lista-wpp-facturas" class="overflow-auto pe-1" style="max-height: 140px;">
                                    <!-- Checkboxes dinámicos de Facturas -->
                                </div>
                            </div>
                            <!-- Remitos a integrar -->
                            <div class="col-md-6" id="cont-wpp-sel-remitos">
                                <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="chk-switch-todos-rem" checked>
                                        <label class="form-check-label fw-bold small" style="color: #6f42c1;" for="chk-switch-todos-rem">
                                            <i class="fa-solid fa-truck-ramp-box me-1"></i> Remitos
                                        </label>
                                    </div>
                                    <span class="badge" style="background-color: #6f42c1;" id="badge-total-rem-wpp">$ 0</span>
                                </div>
                                <div id="lista-wpp-remitos" class="overflow-auto pe-1" style="max-height: 140px;">
                                    <!-- Checkboxes dinámicos de Remitos -->
                                </div>
                            </div>
                        </div>

                        <!-- Resumen Total Seleccionado a Cobrar -->
                        <div class="bg-light p-2 mt-2 rounded border d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-muted">Total a reclamar en esta gestión:</span>
                            <span class="h6 mb-0 fw-bold text-primary" id="wpp-total-seleccionado-gral">$ 0</span>
                        </div>
                    </div>
                </div>

                <!-- Selección de Formato de Mensaje -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">Formato de Plantilla de Mensaje</label>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="card h-100 p-2 border">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipoMensaje" id="tipoMensaje1" value="1">
                                    <label class="form-check-label fw-bold" for="tipoMensaje1">
                                        Opción 1: Factura + Remito
                                    </label>
                                    <div class="small text-muted mt-1">Incluye condiciones para Facturas y Remitos seleccionados.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 p-2 border">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipoMensaje" id="tipoMensaje2" value="2">
                                    <label class="form-check-label fw-bold" for="tipoMensaje2">
                                        Opción 2: Solo Factura
                                    </label>
                                    <div class="small text-muted mt-1">Incluye únicamente condiciones de Facturas.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vista Previa del Mensaje -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">Vista Previa del Texto a Enviar</label>
                    <textarea class="form-control font-monospace bg-light" id="modal-wpp-preview" rows="10" style="font-size: 0.85rem; line-height: 1.4;"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success px-4" id="btn-confirmar-envio-wpp">
                    <i class="fa-brands fa-whatsapp me-1"></i> Abrir WhatsApp y Registrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: GESTIONAR CONTACTO WHATSAPP -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalContacto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-5" id="contacto-modal-titulo">
                    <i class="fa-solid fa-address-book me-2"></i>Gestionar Contacto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="contacto-cod-client">
                <input type="hidden" id="contacto-razon-soci">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Número de Teléfono (WhatsApp)</label>
                    <input type="text" class="form-control" id="contacto-telefono" placeholder="Ej: 5491112345678">
                    <small class="text-muted">Incluya el código de país y área sin espacios ni guiones.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nombre del Contacto / Encargado</label>
                    <input type="text" class="form-control" id="contacto-nombre" placeholder="Nombre de la persona...">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-contacto">Guardar Contacto</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: GESTIONAR ESTADO DE COBRANZA (ENVIADO / PAGADO) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalGestionarEstado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fs-5">
                    <i class="fa-solid fa-circle-check me-2 text-success"></i>Gestionar Estado de Cobranza
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="gestion-id-cobranza">
                
                <div class="alert alert-light border py-2 mb-3">
                    <strong class="d-block text-primary" id="gestion-cliente-info">-</strong>
                    <small class="text-muted" id="gestion-monto-info">-</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-muted mb-2">Estado de la Gestión</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="card p-2 border h-100" style="cursor: pointer;" onclick="$('#radio-estado-pagado').prop('checked', true).trigger('change');">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="radioEstadoGestion" id="radio-estado-pagado" value="PAGADO" checked>
                                    <label class="form-check-label fw-bold text-success" for="radio-estado-pagado">
                                        <i class="fa-solid fa-check-double me-1"></i> PAGADA
                                    </label>
                                    <div class="small text-muted mt-1">El cliente ya efectuó el pago.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card p-2 border h-100" style="cursor: pointer;" onclick="$('#radio-estado-enviado').prop('checked', true).trigger('change');">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="radioEstadoGestion" id="radio-estado-enviado" value="ENVIADO">
                                    <label class="form-check-label fw-bold text-warning text-dark" for="radio-estado-enviado">
                                        <i class="fa-solid fa-clock me-1"></i> ENVIADA
                                    </label>
                                    <div class="small text-muted mt-1">Pendiente de pago / en seguimiento.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="seccion-datos-pago">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">Fecha de Pago</label>
                            <input type="date" class="form-control form-control-sm" id="gestion-fecha-pago">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">Monto Cobrado ($)</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" id="gestion-monto-pago" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">Observaciones / Detalle de la Gestión</label>
                    <textarea class="form-control form-control-sm" id="gestion-observaciones" rows="2" placeholder="Notas sobre la gestión realizada, acuerdo de pago, etc..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-sm px-4 fw-bold" id="btn-guardar-estado-gestion">
                    <i class="fa-solid fa-check me-1"></i> Guardar Gestión
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DETALLE HISTÓRICO DE COMPROBANTES Y MENSAJE ENVIADO -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDetalleHistorial" tabindex="-1" aria-labelledby="modalDetalleHistorialLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fs-5" id="modalDetalleHistorialLabel">
                    <i class="fa-solid fa-file-invoice-dollar me-2 text-info"></i>Detalle de Propuesta Enviada
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Info Header -->
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <span class="text-xs text-muted text-uppercase fw-bold d-block">Cliente</span>
                            <h5 class="mb-0 text-primary fw-bold" id="hist-modal-cliente">-</h5>
                            <small class="text-muted" id="hist-modal-meta">-</small>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="bg-white p-2 px-3 rounded border text-end">
                                <span class="text-xs text-success text-uppercase fw-bold d-block">Facturas Enviadas</span>
                                <strong class="h6 mb-0 text-success" id="hist-modal-total-fac">$ 0</strong>
                            </div>
                            <div class="bg-white p-2 px-3 rounded border text-end">
                                <span class="text-xs text-uppercase fw-bold d-block" style="color: #6f42c1;">Remitos Enviados</span>
                                <strong class="h6 mb-0" style="color: #6f42c1;" id="hist-modal-total-rem">$ 0</strong>
                            </div>
                            <div class="bg-white p-2 px-3 rounded border text-end border-primary">
                                <span class="text-xs text-primary text-uppercase fw-bold d-block">Total Propuesto</span>
                                <strong class="h6 mb-0 text-dark" id="hist-modal-total-gral">$ 0</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs de Navegación interna -->
                <ul class="nav nav-pills mb-3" id="histDetalleTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="hist-comprobantes-tab" data-bs-toggle="pill" data-bs-target="#hist-comprobantes" type="button" role="tab">
                            <i class="fa-solid fa-list-check me-1"></i> Comprobantes Incluidos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="hist-mensaje-tab" data-bs-toggle="pill" data-bs-target="#hist-mensaje" type="button" role="tab">
                            <i class="fa-brands fa-whatsapp me-1 text-success"></i> Mensaje de WhatsApp Enviado
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="histDetalleTabContent">
                    <!-- Comprobantes -->
                    <div class="tab-pane fade show active" id="hist-comprobantes" role="tabpanel">
                        <div id="hist-detalle-comprobantes-container">
                            <!-- Se renderizan tablas de facturas y remitos aquí dinámicamente -->
                        </div>
                    </div>

                    <!-- Mensaje WhatsApp -->
                    <div class="tab-pane fade" id="hist-mensaje" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-1">Texto Enviado por WhatsApp</label>
                            <textarea class="form-control font-monospace bg-light" id="hist-modal-mensaje-texto" rows="12" readonly style="font-size: 0.85rem; line-height: 1.4;"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
include 'templates/layout/footer.php';
?>
<script src="assets/js/mayoristas.js?v=<?= time() ?>"></script>
</body>
</html>
