<?php
session_start();
// Protección de la ruta
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'cliente') {
    header('Location: login.php');
    exit();
}
include 'templates/layout/header.php';
?>

<main>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="h3 mb-0">
            <i class="fa-solid fa-handshake" style="color: #74C0FC;"></i>
            Portal de Cliente
        </h5>
    </div>

<!-- Fila para las Tarjetas de Resumen (KPIs) -->
<div class="row mb-4">
    <!-- KPI Deuda Pendiente -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card shadow-sm border-left-danger h-100"><div class="card-body"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Deuda Pendiente (Fuera de Propuesta)</div><div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-deuda-total">-</div></div></div>
    </div>
    <!-- KPI Monto en Negociación -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card shadow-sm border-left-primary h-100"><div class="card-body"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Monto en Negociación</div><div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-monto-negociacion">-</div></div></div>
    </div>
    
    <!-- ======================= NUEVA TARJETA KPI ======================= -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card shadow-sm border-left-success h-100"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pendiente de Pago</div><div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-pendiente-pago">-</div></div></div>
    </div>
    <!-- ================================================================== -->
    
    <!-- KPI Requieren Acción -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card shadow-sm border-left-warning h-100"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Requieren su Acción</div><div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-requiere-accion">-</div></div></div>
    </div>
</div>

    <!-- Tarjeta principal que contiene todo -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row">

                <!-- Columna para la tabla de propuestas -->
                <div class="col-lg-8">
                    <h5 class="mb-3 border-bottom pb-2"><i class="fa-solid fa-money-bill-1 fa-1x"></i> Propuestas de Pago </h5>
                    <div class="table-responsive">
                        <table id="tabla-propuestas-cliente" class="table table-striped table-hover" style="width:100%"></table>
                    </div>
                </div>

                <!-- ======================= INICIO DE LA MEJORA VISUAL ======================= -->
                <!-- Columna para el calendario CON DIVISOR y padding -->
                <div class="col-lg-4 border-start ps-lg-4">
                    <h5 class="mb-3 border-bottom pb-2"><i class="fa-solid fa-calendar-days me-2"></i>Cronograma de Pagos</h5>
                    <div id="cronograma-calendario"></div>
                    <div id="cronograma-detalles" class="mt-3">
                        <!-- Mensaje inicial -->
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-hand-pointer fa-2x mb-3"></i>
                            <p class="mb-0">Seleccione un día resaltado en azul para ver los vencimientos.</p>
                        </div>
                    </div>
                </div>
                <!-- ======================== FIN DE LA MEJORA VISUAL ========================= -->

            </div> 
        </div> 
    </div> 
    <!-- ======================= NUEVO MODAL PARA SUBIR DOCUMENTACIÓN ======================= -->
    <div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadDocModalLabel">Adjuntar Comprobante de Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadDocForm" enctype="multipart/form-data">
                        <input type="hidden" id="uploadPropuestaId" name="id_propuesta">
                        <div class="mb-3">
                            <label for="comprobanteFile" class="form-label">Seleccione el archivo (PDF, JPG, PNG):</label>
                            <input class="form-control" type="file" id="comprobanteFile" name="comprobanteFile" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="progress" style="display: none;">
                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSubirComprobante">
                        <i class="fa-solid fa-upload me-2"></i>Subir
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- ==================================================================================== -->
</main>

<?php include 'templates/layout/footer.php'; ?>

<!-- Script específico para esta página -->
<script src="assets/js/portal_cliente.js"></script>

</body>
</html>