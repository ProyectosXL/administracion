<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Si viene entorno por URL, actualizar la sesión
if (isset($_GET['entorno']) && in_array($_GET['entorno'], ['central', 'uy'])) {
    $_SESSION['entorno'] = $_GET['entorno'];
}

// Headers para evitar caché y forzar recarga
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include '../class/proveedor.php';
include '../class/ordenDeCompra.php';
include '../class/encabezado.php';

$proveedor = new Proveedor();
$todosLosProveedores = [];

// Detectar modo edición
$modoEdicion = isset($_GET['modo']) && $_GET['modo'] === 'edicion';
$idDespacho = isset($_GET['id']) ? intval($_GET['id']) : 0;
$despacho = null;

if ($modoEdicion && $idDespacho > 0) {
    $encabezadoClass = new Encabezado();
    // Cargar datos del despacho (implementaremos este método)
    $despacho = $encabezadoClass->obtenerDespachoPorId($idDespacho);
}

try {
    $proveedoresJson = $proveedor->traerProveedores();
    $todosLosProveedores = json_decode($proveedoresJson);
    
    // Si la decodificación falla o está vacía, usar array vacío
    if (!is_array($todosLosProveedores)) {
        $todosLosProveedores = [];
    }
} catch (Exception $e) {
    error_log("Error al cargar proveedores: " . $e->getMessage());
    $todosLosProveedores = [];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Title Page-->
    <title>Costos Importacion</title>

    <!-- Librerias Bootstrap -->
    <!-- CSS only -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
                        <!------------------------------------------------------------>

    <!-- Icons font CSS-->
    <link href="../assets/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">
    <link href="../assets/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <!-- Font special for pages-->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <!-- Vendor CSS-->
    <link href="../assets/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="../assets/datepicker/daterangepicker.css" rel="stylesheet" media="all">

    <link rel="icon" type="image/jpg" href="../images/LOGO XL 2018.jpg">
    <!-- Main CSS-->
    <link href="../css/style.css" rel="stylesheet" media="all">
    <!-- Carga Inicial CSS-->
    <link href="../css/cargaInicial.css" rel="stylesheet" media="all">
    
    <!-- Feriados Argentinos para validación JavaScript -->
    <script>
    <?php
    require_once __DIR__ . '/../class/FechasHabiles.php';
    $yearActual = date('Y');
    $yearSiguiente = $yearActual + 1;
    $feriados = FechasHabiles::obtenerFeriadosRango($yearActual, $yearSiguiente);
    ?>
    // Array de feriados argentinos (cargado desde API)
    const feriadosArgentinos = <?php echo json_encode($feriados); ?>;
    </script>
    
</head>

<body>
    <div class="carga-inicial-container">
        <div class="carga-inicial-header">
            <h1 class="page-title">
                <i class="bi bi-<?= $modoEdicion ? 'pencil-square' : 'file-earmark-plus' ?>"></i>
                <?= $modoEdicion ? 'Completar Despacho de Importación' : 'Nuevo Despacho de Importación' ?>
            </h1>
            <p class="page-subtitle"><?= $modoEdicion ? 'Complete las secciones faltantes del despacho' : 'Complete los datos del despacho de importación' ?></p>
        </div>
        
        <div class="carga-inicial-content">
                    <div id="entorno" hidden><?= (isset($_SESSION['entorno'])) ? $_SESSION['entorno'] : 'central' ?></div>
                    <input type="hidden" id="modoEdicion" value="<?= $modoEdicion ? 'true' : 'false' ?>">
                    <input type="hidden" id="idDespacho" value="<?= $idDespacho ?>">
                    
                    <?php if ($modoEdicion && $despacho): ?>
                    <script>
                        // Datos del despacho a cargar
                        var datosDespacho = <?= json_encode($despacho) ?>;
                    </script>
                    <?php endif; ?>

                    <!-- ========== SECCIÓN 1: DATOS INICIALES ========== -->
                    <div class="seccion-formulario mt-4 mb-4">
                        <h4 class="seccion-titulo"><i class="bi bi-clipboard-data"></i> Sección 1 - Datos Iniciales</h4>
                        
                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Proveedor</label>
                                <div class="input-group">
                                    <select id="proveedor" style="width: 283.16px;" required>
                                        <option selected disabled>Seleccione...</option>
                                        <?php
                                        if (is_array($todosLosProveedores) && count($todosLosProveedores) > 0) {
                                            foreach($todosLosProveedores as $valor => $value){
                                        ?>
                                        <option id="proveedor-" value="<?= $value->COD_PROVEE; ?>"><?= $value->NOM_PROVEE; ?></option>
                                        <?php   
                                            }
                                        } else {
                                        ?>
                                        <option disabled>No hay proveedores disponibles</option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                    <div class="select-dropdown"></div>
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Nº Orden Proveedor</label>
                                <div class="input-group">
                                    <input class="input--style-1 mayusc" type="text" id="contenedor" required>
                                </div>    
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Material</label>
                                <div class="input-group">
                                    <input class="input--style-1 mayusc" type="text" id="material" required>
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Origen</label>
                                <div class="input-group">
                                    <input class="input--style-1 mayusc" type="text" value="CHINA" id="origen" required>
                                </div>    
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Valor F.O.B. U$S</label>
                                <div class="input-group">
                                    <input class="input--style-1 decimales currencyInput" type="text" id="valorFobDolar" required>
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Órdenes de Compra</label>
                                <div class="input-group">
                                    <button type="button" id="btnAddOrdenCompra" style="width: 100%;"><i class="bi bi-plus-circle-fill"></i> Agregar Orden</button>
                                </div>    
                                <div id="ordenesSeleccionadas" class="contenedor"></div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Fecha Estimada Embarque</label>
                                <div class="input-group">
                                    <input class="input--style-1 js-datepicker-estimada" type="text" id="fechaEstEmb" required>
                                    <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar-estimada"></i>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Despachante</label>
                                <div class="input-group">
                                    <div class="rs-select2 js-select-simple select--no-search">
                                        <select id="despachante" style="width: 283.16px;" required>
                                            <option value="">Seleccione...</option>
                                            <option value="Laffitte">Laffitte</option>
                                            <option value="Farre">Farre</option>
                                        </select>
                                        <div class="select-dropdown"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-10">
                                <div class="orden-manual-container">
                                    <label class="orden-manual-label">
                                        <input type="checkbox" id="ordenManual" onchange="traerOrden()">
                                        <span class="checkmark"></span>
                                        <span class="label-text">
                                            <i class="bi bi-gear-fill"></i>
                                            Generar Orden de Compra Manual
                                        </span>
                                    </label>
                                    <p class="orden-manual-hint">Activa esta opción si no tienes una orden de compra existente</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========== SECCIÓN 2: DATOS DE EMBARQUE ========== -->
                    <!-- LÓGICA PRIORITARIA: Si existe Fecha Embarque (ETD), se usa para cálculos. Si no, se usa Fecha Estimada -->
                    <div class="seccion-formulario mt-4 mb-4">
                        <h4 class="seccion-titulo"><i class="bi bi-ship"></i> Sección 2 - Datos de Embarque</h4>
                        
                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Fecha Embarque - ETD <i class="bi bi-exclamation-circle text-info" title="Fecha real de embarque (ETD). Tiene prioridad sobre la fecha estimada para los cálculos automáticos"></i></label>
                                <div class="input-group">
                                    <input class="input--style-1 js-datepicker-etd" type="text" id="fechaEmb">
                                    <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar-etd"></i>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Fecha Arribo - ETA <span class="badge-auto">Auto</span></label>
                                <div class="input-group" style="margin-bottom: 5px;">
                                    <input class="input--style-1 js-datepicker-arribo" type="text" id="fechaArr">
                                    <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar-arribo"></i>
                                </div>
                                <!-- Checkbox ETA Confirmada -->
                                <div class="form-check" style="margin-top: 4px; margin-bottom: 0;">
                                    <input type="checkbox" 
                                           id="etaConfirmada" 
                                           name="eta_confirmada" 
                                           class="form-check-input"
                                           value="1"
                                           style="cursor: pointer;">
                                    <label for="etaConfirmada" class="form-check-label" style="cursor: pointer; font-size: 12px; color: #666; font-weight: 500;">
                                        <i class="bi bi-check-circle" style="font-size: 13px;"></i> ETA Confirmada
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Número BL</label>
                                <div class="input-group">
                                    <input class="input--style-1 soloNum" type="text" id="numeroBl">
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Factura Proveedor</label>
                                <div class="input-group">
                                    <input class="input--style-1 mayusc" type="text" id="factura">
                                </div>    
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Puerto Origen</label>
                                <div class="input-group">
                                    <div class="rs-select2 js-select-simple select--no-search">
                                        <select id="puertoOrigen" style="width: 283.16px;">
                                            <option value="">Seleccione...</option>
                                            <option value="Shanghai">Shanghai</option>
                                            <option value="Shenzhen">Shenzhen</option>
                                            <option value="Ningbo">Ningbo</option>
                                            <option value="Guangzhou">Guangzhou</option>
                                            <option value="Qingdao">Qingdao</option>
                                            <option value="Tianjin">Tianjin</option>
                                            <option value="Hong Kong">Hong Kong</option>
                                            <option value="Xiamen">Xiamen</option>
                                            <option value="Dalian">Dalian</option>
                                            <option value="Yantian">Yantian</option>
                                        </select>
                                        <div class="select-dropdown"></div>
                                    </div>        
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Terminal</label>
                                <div class="input-group">
                                    <div class="rs-select2 js-select-simple select--no-search">
                                        <select id="terminal" style="width: 283.16px;">
                                            <option value="">Seleccione...</option>
                                            <option value="EXOLGAN">EXOLGAN</option>
                                            <option value="TRP">TRP</option>
                                            <option value="T4">T4</option>
                                        </select>
                                        <div class="select-dropdown"></div>
                                    </div>        
                                </div>    
                            </div>
                        </div>
                    </div>

                    <!-- ========== SECCIÓN 3: DATOS FINANCIEROS Y ADUANA ========== -->
                    <div class="seccion-formulario mt-4 mb-4">
                        <h4 class="seccion-titulo"><i class="bi bi-cash-coin"></i> Sección 3 - Datos Financieros y Aduana</h4>
                        
                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Tipo de Cambio</label>
                                <div class="input-group">
                                    <input class="input--style-1 currencyInput" type="text" id="tipoCambio">
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Valor F.O.B. $ <span class="badge-auto">Auto</span></label>
                                <div class="input-group">
                                    <input class="input--style-1" type="text" id="valorFobPeso" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Fecha Est. Pago <span class="badge-auto">Auto</span></label>
                                <div class="input-group">
                                    <input class="input--style-1 js-datepicker-est-pago" type="text" id="fechaEstPago" readonly>
                                    <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar-est-pago"></i>
                                </div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-12">
                                <h5 class="mb-3">Registro de Pagos</h5>
                                <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <label class="label-campo" style="margin-bottom: 0;"><strong id="saldoPendiente" style="color: #d9534f; font-size: 16px;">$ 0</strong></label>
                                    <button type="button" id="btnAgregarPago" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle"></i> Agregar Pago
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-12">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-sm table-bordered" id="tablaPagos" style="background: white; margin: 0;">
                                        <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 1;">
                                            <tr>
                                                <th style="width: 25%;">Fecha de Pago</th>
                                                <th style="width: 25%;">Forma de Pago</th>
                                                <th style="width: 25%;">Medio de Pago</th>
                                                <th style="width: 20%;">Monto ($)</th>
                                                <th style="width: 5%; text-align: center;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyPagos">
                                            <!-- Los pagos se agregarán aquí dinámicamente -->
                                            <tr id="sinPagos">
                                                <td colspan="5" style="text-align: center; color: #999; padding: 30px;">
                                                    <i class="bi bi-inbox" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                                                    No hay pagos registrados. Haz clic en "Agregar Pago" para comenzar.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Fecha Nacionalización <span class="badge-auto">Auto</span></label>
                                <div class="input-group">
                                    <input class="input--style-1 js-datepicker-despacho" type="text" id="fechaDespAdu">
                                    <i class="zmdi zmdi-calendar-note input-icon js-btn-calendar-despacho"></i>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Despacho N°</label>
                                <div class="input-group">
                                    <input class="input--style-1 mayusc" type="text" id="despacho">
                                </div>    
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="gestionDespachos.php" class="btn btn-secondary btn-back">
                            <i class="bi bi-arrow-left"></i> Volver a Pendientes
                        </a>
                        <button class="btn btn-primary btn-save" id="btnSave" onclick="guardarCabecera()">
                            <i class="bi bi-save"></i> Guardar Despacho
                        </button>
                    </div>
                </div>
            </div>
    </div>

    
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Jquery JS-->
    <script src="../assets/jquery/jquery.min.js"></script>
    <!-- Vendor JS-->
    <script src="../assets/select2/select2.min.js"></script>
    <script src="../assets/datepicker/moment.min.js"></script>
    <script src="../assets/datepicker/daterangepicker.js"></script>

    <!-- Modal para Agregar Pago -->
    <div class="modal fade" id="modalAgregarPago" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle"></i> Agregar Nuevo Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fecha de Pago</label>
                        <input type="text" class="form-control js-datepicker-nuevo-pago" id="fechaPagoNuevo" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Forma de Pago</label>
                        <select class="form-select" id="formaPagoNuevo">
                            <option value="">Seleccione...</option>
                            <option>PAGO ANTICIPADO</option>
                            <option>PAGO VISTA</option>
                            <option>PAGO DIFERIDO</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Medio de Pago</label>
                        <select class="form-select" id="medioPagoNuevo">
                            <option value="">Seleccione...</option>
                            <option>Transferencia</option>
                            <option>Cheque</option>
                            <option>Tarjeta</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto</label>
                        <input type="number" class="form-control" id="montoNuevo" placeholder="0.00" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarNuevoPago()">Guardar Pago</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Fecha de Pago -->
    <div class="modal fade" id="modalEditarFechaPago" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-event"></i> Editar Fecha de Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fecha de Pago</label>
                        <input type="text" class="form-control js-datepicker-edit-fecha" id="fechaPagoEdit" readonly>
                        <input type="hidden" id="idPagoEdit">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarFechaPago()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main JS-->
    <script src="../js/global.js"></script>
    <!-- Carga Inicial JS - Contiene toda la lógica del formulario -->
    <script src="../js/cargaInicial.js"></script>
    
    <script>
    // Inicializar select de proveedor para cargar órdenes de compra
    document.addEventListener('DOMContentLoaded', function() {
        const selectProveedor = document.getElementById('proveedor');
        
        if (selectProveedor) {
            selectProveedor.addEventListener('change', function() {
                const proveedor = this.value;
                
                if (proveedor && proveedor !== 'Seleccione...') {
                    // Cargar órdenes de compra del proveedor
                    fetch('../class/ordenDeCompra.php?proveedor=' + proveedor)
                        .then(response => response.json())
                        .then(ordenes => {
                            localStorage.setItem('ordenes', JSON.stringify(ordenes));
                            console.log('Órdenes de compra cargadas:', ordenes);
                        })
                        .catch(error => {
                            console.error('Error al cargar órdenes:', error);
                        });
                }
            });
        }
    });
    </script>

</body>

</html>
