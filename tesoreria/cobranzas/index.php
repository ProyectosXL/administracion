<?php include 'templates/layout/header.php'; ?>

<main>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">
            <i class="fa-solid fa-file-invoice-dollar text-primary"></i>
            Cobranzas Pendientes
        </h1>
        <button class="btn btn-outline-secondary" id="btn-abrir-parametros" data-bs-toggle="modal" data-bs-target="#parametrosModal" title="Gestionar Parámetros">
            <i class="fa-solid fa-gear fa-spin-hover"></i>
        </button>
    </div>
        <!-- ===================== NUEVO BLOQUE DE TARJETAS DE RESUMEN ===================== -->
    <div class="row mb-4" id="summary-cards">
        <div class="col-md-4">
            <div class="card shadow-sm border-left-primary h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Neto a Cobrar</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="summary-total-neto">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-left-success h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Comprobantes</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="summary-total-comprobantes">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-left-info h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Clientes con Deuda</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800" id="summary-total-clientes">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ===================== FIN DEL BLOQUE DE TARJETAS ===================== -->
    <div class="card shadow-sm">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="cobranzasTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="franquicias-tab" data-bs-toggle="tab" data-bs-target="#franquicias" type="button" role="tab" aria-controls="franquicias" aria-selected="true">
                        <i class="fa-solid fa-store"></i> Franquicias
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="mayoristas-tab" data-bs-toggle="tab" data-bs-target="#mayoristas" type="button" role="tab" aria-controls="mayoristas" aria-selected="false">
                        <i class="fa-solid fa-truck-moving"></i> Mayoristas
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="cobranzasTabContent">
                <div class="tab-pane fade show active" id="franquicias" role="tabpanel" aria-labelledby="franquicias-tab">
                    <div class="table-responsive">
                        <table id="tabla-franquicias" class="table table-striped table-hover" style="width:100%">
                            <!-- El contenido se cargará con JavaScript -->
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="mayoristas" role="tabpanel" aria-labelledby="mayoristas-tab">
                    <div class="table-responsive">
                         <table id="tabla-mayoristas" class="table table-striped table-hover" style="width:100%">
                            <!-- El contenido se cargará con JavaScript -->
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ======================= MODAL PARA DETALLE DE CLIENTE ======================= -->
<div class="modal fade" id="detalleClienteModal" tabindex="-1" aria-labelledby="detalleClienteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="detalleClienteModalLabel">Detalle de Cobranzas</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h6 class="mb-3">Cliente: <span id="nombreClienteModal" class="fw-bold"></span></h6>
<div id="detalle-content-container">
    <!-- Este div se llenará dinámicamente con el loader o la tabla -->
</div>
      </div>
            <!-- ===================== AÑADIR ESTE BLOQUE ===================== -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" id="btn-enviar-email-seleccion" disabled>
            <i class="fa-solid fa-envelope me-2"></i>
            Enviar Selección
        </button>
      </div>
      <!-- ===================== FIN DEL BLOQUE A AÑADIR ===================== -->
    </div>
  </div>
</div>
<!-- ======================= MODAL DE CONFIRMACIÓN DE ELIMINACIÓN ======================= -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="confirmDeleteModalLabel">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>Confirmar Eliminación
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>¿Estás seguro de que deseas eliminar este parámetro de forma permanente? Esta acción no se puede deshacer.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btn-confirmar-delete">Sí, Eliminar</button>
      </div>
    </div>
  </div>
</div>
<!-- ===================== FIN DEL MODAL DE CONFIRMACIÓN ===================== -->
<?php include 'templates/layout/footer.php'; ?>