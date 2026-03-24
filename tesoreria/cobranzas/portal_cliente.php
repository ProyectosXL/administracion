
<?php
session_start();

// El franquiciado llega con ?cliente=FXXX desde seleccionar_sucursal.php
// Cargamos sus datos de sesión a partir del cod_client recibido
$cod_client_activo = '';

if (isset($_GET['cliente']) && !empty(trim($_GET['cliente']))) {
    $cod_client_activo = trim($_GET['cliente']);
    $_SESSION['cod_client_activo'] = $cod_client_activo;
} elseif (isset($_SESSION['cod_client_activo']) && !empty($_SESSION['cod_client_activo'])) {
    $cod_client_activo = $_SESSION['cod_client_activo'];
} else {
    header('Location: login.php');
    exit();
}

// Cargamos los datos de sesión necesarios para que todo el portal funcione
require_once 'config/database.php';

try {
    $conn = Database::getConnection('central');

    // Razón social del local activo
    $sql_rs = "SELECT RAZON_SOCI FROM GVA14 WHERE COD_CLIENT = ?";
    $stmt_rs = sqlsrv_query($conn, $sql_rs, [$cod_client_activo]);
    if ($stmt_rs && $rrs = sqlsrv_fetch_array($stmt_rs, SQLSRV_FETCH_ASSOC)) {
        $_SESSION['razon_social_activo'] = trim($rrs['RAZON_SOCI']);
    }

    // Seteamos lo mínimo para que el portal funcione
    $_SESSION['codigos_cliente_agrupados'] = [$cod_client_activo];
    $_SESSION['usuario_cod_client_individual'] = $cod_client_activo;

    // Si no tiene usuario_id seteamos uno simbólico para que los controllers no fallen
    if (!isset($_SESSION['usuario_id'])) {
        $_SESSION['usuario_id']     = 0;
        $_SESSION['usuario_nombre'] = $_SESSION['razon_social_activo'] ?? $cod_client_activo;
        $_SESSION['usuario_rol']    = 'cliente';
    }

    // Actualizamos vencimientos
    require_once 'api/vencimientos_controller.php';
    $conn_apps = Database::getConnection('apps');
    verificarYActualizarVencimientosCliente($conn_apps, [$cod_client_activo]);

} catch (Exception $e) {
    error_log("Error cargando datos de portal_cliente: " . $e->getMessage());
}

include 'templates/layout/header.php';
?>

<main>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="h3 mb-0">
            <i class="fa-solid fa-handshake" style="color: #74C0FC;"></i>
            Portal de Cliente
        </h5>
        <a href="seleccionar_sucursal.php?cliente=<?php echo urlencode($cod_client_activo); ?>"
           class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-store me-1"></i>
            <?php echo htmlspecialchars($_SESSION['razon_social_activo'] ?? $cod_client_activo); ?>
            <i class="fa-solid fa-chevron-down ms-1" style="font-size: 10px;"></i>
        </a>
    </div>

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-danger h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Deuda Pendiente (Fuera de Propuesta)</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-deuda-total">-</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-primary h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Monto en Negociación</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-monto-negociacion">-</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-success h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pendiente de Pago</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-pendiente-pago">-</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card shadow-sm border-left-warning h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Requieren su Acción</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800" id="kpi-requiere-accion">-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta principal -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row">

                <div class="col-lg-8">
                    <h5 class="mb-3 border-bottom pb-2">
                        <i class="fa-solid fa-money-bill-1 fa-1x"></i> Propuestas de Pago
                    </h5>
                    <div class="table-responsive">
                        <table id="tabla-propuestas-cliente" class="table table-striped table-hover" style="width:100%"></table>
                    </div>
                </div>

                <div class="col-lg-4 border-start ps-lg-4">
                    <h5 class="mb-3 border-bottom pb-2">
                        <i class="fa-solid fa-calendar-days me-2"></i>Cronograma de Pagos
                    </h5>
                    <div id="cronograma-calendario"></div>
                    <div id="cronograma-detalles" class="mt-3">
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-hand-pointer fa-2x mb-3"></i>
                            <p class="mb-0">Seleccione un día resaltado en azul para ver los vencimientos.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal subir comprobante -->
    <div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true" style="z-index: 2000;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadDocModalLabel">Adjuntar Comprobante de Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadDocForm" enctype="multipart/form-data">
                        <input type="hidden" id="uploadPropuestaId" name="id_propuesta">
                        <input type="hidden" id="uploadCuotaId" name="id_cuota">
                        <div class="mb-3">
                            <label for="comprobanteFile" class="form-label">Seleccione el archivo (PDF, JPG, PNG):</label>
                            <input class="form-control" type="file" id="comprobanteFile" name="comprobante"
                                accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="progress" style="display: none;">
                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0"
                                aria-valuemin="0" aria-valuemax="100">0%</div>
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
</main>

<?php include 'templates/layout/footer.php'; ?>

<script src="assets/js/portal_cliente.js?v=1.1"></script>

</body>
</html>