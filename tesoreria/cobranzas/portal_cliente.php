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
        <h1 class="h3 mb-0">
            <i class="fa-solid fa-user-tie text-primary"></i>
            Mi Portal de Cliente
        </h1>
    </div>

    <!-- Fila para las Tarjetas de Resumen (KPIs) -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-left-danger h-100"><div class="card-body"><div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Deuda Pendiente (Fuera de Propuesta)</div><div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-deuda-total">-</div></div></div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-left-warning h-100"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Monto en Negociación</div><div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-monto-negociacion">-</div></div></div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-left-info h-100"><div class="card-body"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Propuestas que Requieren su Acción</div><div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-requiere-accion">-</div></div></div>
        </div>
    </div>

    <!-- Tarjeta principal que contiene todo -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row">

                <!-- Columna para la tabla de propuestas -->
                <div class="col-lg-8">
                    <h5 class="mb-3 border-bottom pb-2">Mis Propuestas de Pago</h5>
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
                            <i class="fa-solid fa-hand-pointer fa-2x mb-2"></i>
                            <p class="mb-0">Seleccione un día resaltado para ver los vencimientos.</p>
                        </div>
                    </div>
                </div>
                <!-- ======================== FIN DE LA MEJORA VISUAL ========================= -->

            </div> 
        </div> 
    </div> 

</main>

<?php include 'templates/layout/footer.php'; ?>

<!-- Script específico para esta página -->
<script src="assets/js/portal_cliente.js"></script>

</body>
</html>