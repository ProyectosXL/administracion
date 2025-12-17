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
                <div> <!-- Contenedor para los botones -->
            <!-- ======================= NUEVO BOTÓN ======================= -->
            <!-- ========================================================= -->
        <button class="btn btn-outline-secondary" id="btn-abrir-parametros" data-bs-toggle="modal" data-bs-target="#parametrosModal" title="Gestionar Parámetros">
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
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Neto a Cobrar</div>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Comprobantes</div>
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
            <div class="card shadow-sm border-left-primary h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Propuestas Activas</div><div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-propuestas-activas">-</div></div></div>
        </div>
        <!-- Monto en Negociación -->
        <div class="col-xl col-md-6 mb-4">
            <div class="card shadow-sm border-left-info h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Monto en Negociación</div><div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-monto-negociacion">-</div></div></div>
        </div>
        <!-- NUEVA TARJETA: Monto Vencido -->
        <div class="col-xl col-md-6 mb-4">
            <div class="card shadow-sm border-left-danger h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Monto Vencido</div><div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-monto-vencido">-</div></div></div>
        </div>
        <!-- Requieren Acción -->
        <div class="col-xl col-md-6 mb-4">
            <div class="card shadow-sm border-left-warning h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Requieren Acción</div><div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-requieren-accion">-</div></div></div>
        </div>
        <!-- Aceptadas (Últ. 30 días) -->
        <div class="col-xl col-md-6 mb-4">
            <div class="card shadow-sm border-left-success h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Aceptadas (Últ. 30 días)</div><div class="h5 mb-0 font-weight-bold text-gray-800" id="kpi-aceptadas-mes">-</div></div></div>
        </div>
    </div>
    <!-- ======================== FIN DEL BLOQUE A REEMPLAZAR ========================= -->
        <div class="row">
            <div class="col-lg-4 mb-4"><div class="card shadow-sm h-100"><div class="card-header">Distribución de Estados</div><div class="card-body d-flex align-items-center justify-content-center"><div style="position: relative; height: 280px; width: 100%;"><canvas id="chartEstados"></canvas></div></div></div></div>
            <div class="col-lg-8 mb-4"><div class="card shadow-sm h-100"><div class="card-header">Propuestas Aceptadas (Últimos 7 Días)</div><div class="card-body"><div style="position: relative; height: 280px; width: 100%;"><canvas id="chartActividadReciente"></canvas></div></div></div></div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
             <ul class="nav nav-tabs card-header-tabs" id="cobranzasTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="gestion-tab" data-bs-toggle="tab" data-bs-target="#gestion" type="button" role="tab" aria-controls="gestion" aria-selected="false">
                        <i class="fa-solid fa-tasks"></i> Gestión de Propuestas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="franquicias-tab" data-bs-toggle="tab" data-bs-target="#franquicias" type="button" role="tab" aria-controls="franquicias" aria-selected="true">
                        <i class="fa-solid fa-store"></i> Pendientes (Franquicias)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="mayoristas-tab" data-bs-toggle="tab" data-bs-target="#mayoristas" type="button" role="tab" aria-controls="mayoristas" aria-selected="false">
                        <i class="fa-solid fa-truck-moving"></i> Pendientes (Mayoristas)
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="cobranzasTabContent">
                <div class="tab-pane fade" id="gestion" role="tabpanel" aria-labelledby="gestion-tab">
                    <div class="table-responsive"><table id="tabla-gestion-propuestas" class="table table-striped table-hover" style="width:100%"></table></div>
                </div>
                <div class="tab-pane fade show active" id="franquicias" role="tabpanel" aria-labelledby="franquicias-tab">
                    <div class="table-responsive"><table id="tabla-franquicias" class="table table-striped table-hover" style="width:100%"></table></div>
                </div>
                <div class="tab-pane fade" id="mayoristas" role="tabpanel" aria-labelledby="mayoristas-tab">
                    <div class="table-responsive"><table id="tabla-mayoristas" class="table table-striped table-hover" style="width:100%"></table></div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ======================= NUEVO MODAL DE CONFIRMACIÓN DE ELIMINACIÓN ======================= -->
<div class="modal fade" id="confirmDeletePropuestaModal" tabindex="-1" aria-labelledby="confirmDeletePropuestaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeletePropuestaModalLabel"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás realmente seguro de que deseas eliminar esta propuesta de pago?</p>
                <p class="text-danger"><strong>Esta acción es irreversible</strong> y borrará todos sus datos asociados (items, historial y adjuntos).</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-delete-propuesta">Sí, Eliminar Propuesta</button>
            </div>
        </div>
    </div>
</div>
<!-- ======================================================================================== -->

<?php 
include 'templates/layout/footer.php'; 
?>

<!-- Scripts específicos para el panel admin -->
<script src="assets/js/app.js"></script>

</body>
</html>