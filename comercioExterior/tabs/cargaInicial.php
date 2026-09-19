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
include '../class/terminal.php';
include '../class/puerto.php';

$proveedor     = new Proveedor();
$terminalClass = new Terminal();
$puertoClass   = new Puerto();
$todosLosProveedores = [];

// ── Detección padre/hija ──────────────────────────────────────────────────────
$idRecibido  = intval($_GET['id']   ?? 0);
$modoParam   = trim($_GET['modo']   ?? '');
$modoLectura = ($modoParam === 'lectura');

$encabezadoClass      = new Encabezado();
$mostrarAvisoRedirect = false;
$idTrabajo            = $idRecibido;
$idPrincipal          = $idRecibido;
$esLectura            = $modoLectura;
$esVinculada          = false;
$ocsGrupo             = [];
$despacho             = null;

if ($idRecibido > 0) {
    $idPrincipal = $encabezadoClass->resolverIdPrincipal($idRecibido);
    $esVinculada = ($idPrincipal !== $idRecibido);

    if ($esVinculada && !$modoLectura) {
        // Hija en modo edición → cargar al principal con aviso
        $idTrabajo            = $idPrincipal;
        $mostrarAvisoRedirect = true;
    } else {
        $idTrabajo = $idRecibido;
    }

    $despacho  = $encabezadoClass->obtenerDespachoPorId($idTrabajo);
    $ocsGrupo  = $encabezadoClass->obtenerOrdenesDelGrupo($idPrincipal);
}

// modo edición = cualquier acceso con id (incluyendo lectura — se renderiza todo)
$modoEdicion = ($idTrabajo > 0);
$idDespacho  = $idTrabajo; // alias para compatibilidad con el resto del archivo
// ─────────────────────────────────────────────────────────────────────────────

try {
    $proveedoresJson = $proveedor->traerProveedores();
    $todosLosProveedores = json_decode($proveedoresJson);

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
                    <input type="hidden" id="modoEdicion"       value="<?= $modoEdicion ? 'true' : 'false' ?>">
                    <input type="hidden" id="idDespacho"        value="<?= htmlspecialchars($idTrabajo) ?>">
                    <input type="hidden" id="idPrincipalGrupo"  value="<?= htmlspecialchars($idPrincipal) ?>">
                    <input type="hidden" id="esLectura"         value="<?= $esLectura   ? '1' : '0' ?>">
                    <input type="hidden" id="esVinculada"       value="<?= $esVinculada ? '1' : '0' ?>">

                    <?php if ($modoEdicion && $despacho): ?>
                    <script>
                        var datosDespacho = <?= json_encode($despacho) ?>;
                        <?php if ($mostrarAvisoRedirect): ?>
                        var mostrarAvisoRedirect = true;
                        <?php endif; ?>
                    </script>
                    <?php endif; ?>

                    <?php if ($esLectura): ?>
                    <?php
                        $ocPrincipalData = array_values(array_filter($ocsGrupo, fn($oc) => $oc['ID_PADRE'] === null));
                        $ocPrincipalOC   = $ocPrincipalData ? trim($ocPrincipalData[0]['ORDEN_COMPRA']) : '';
                    ?>
                    <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                        <i class="bi bi-lock-fill me-2 fs-4"></i>
                        <div class="flex-grow-1">
                            <strong>Modo solo lectura</strong> — Esta OC está vinculada a la OC principal
                            <strong style="font-family:monospace;"><?= htmlspecialchars($ocPrincipalOC) ?></strong>.
                            Las modificaciones se hacen desde la principal.
                        </div>
                        <a href="cargaInicial.php?id=<?= intval($idPrincipal) ?>&modo=edicion"
                           class="btn btn-primary btn-sm ms-3">
                            <i class="bi bi-arrow-left-circle"></i> Ir a la principal
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (count($ocsGrupo) > 1): ?>
                    <div class="ocs-vinculadas-container mb-3 p-3"
                         style="background:#f8f9fa; border-radius:8px; border:1px solid #dee2e6;">
                        <div class="text-muted small mb-2">
                            <i class="bi bi-link-45deg"></i> OCs vinculadas (mismo contenedor)
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($ocsGrupo as $oc):
                                $esPrincipalChip = ($oc['ID_PADRE'] === null);
                                $esActualChip    = ($oc['ID'] == $idTrabajo);
                                $idChip          = intval($oc['ID']);
                            ?>
                            <div class="oc-chip <?= $esActualChip ? 'oc-chip-actual' : '' ?>"
                                 data-id-oc="<?= $idChip ?>"
                                 data-es-principal="<?= $esPrincipalChip ? '1' : '0' ?>"
                                 data-es-actual="<?= $esActualChip ? '1' : '0' ?>"
                                 style="background:#fff; border-radius:6px; padding:10px 14px; min-width:220px;
                                        cursor:<?= $esActualChip ? 'default' : 'pointer' ?>;
                                        border:<?= $esActualChip ? '2px solid #0d6efd' : '1px solid #dee2e6' ?>;">

                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <?php if ($esPrincipalChip): ?>
                                        <i class="bi bi-star-fill text-warning small"></i>
                                        <span class="badge bg-warning text-dark" style="font-size:10px;">PRINCIPAL</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" style="font-size:10px;">VINCULADA</span>
                                    <?php endif; ?>

                                    <?php if ($esActualChip && !$esLectura): ?>
                                        <span class="badge bg-primary ms-auto" style="font-size:10px;">EDITANDO</span>
                                    <?php elseif ($esActualChip && $esLectura): ?>
                                        <span class="badge bg-warning text-dark ms-auto" style="font-size:10px;">
                                            <i class="bi bi-lock-fill"></i> SOLO LECTURA
                                        </span>
                                    <?php elseif (!$esActualChip && $esPrincipalChip): ?>
                                        <span class="ms-auto small text-primary fw-semibold">
                                            Editar acá <i class="bi bi-arrow-right"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="ms-auto small text-muted">
                                            Ver <i class="bi bi-box-arrow-up-right"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="fw-semibold" style="font-family:monospace; font-size:14px;">
                                    <?= htmlspecialchars(trim($oc['ORDEN_COMPRA'])) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ========== SECCIÓN 1: DATOS INICIALES ========== -->
                    <div class="seccion-formulario mt-4 mb-4">
                        <h4 class="seccion-titulo"><i class="bi bi-clipboard-data"></i> Sección 1 - Datos Iniciales</h4>
                        
                        <div class="row row-space">
                            <div class="col-md-5">
                                <label class="label-campo">Proveedor</label>
                                <div class="input-group">
                                    <div class="js-select-simple">
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
                                <label class="label-campo">
                                    Valor F.O.B. U$S
                                    <!-- Sólo se muestra en modo edición: lo prende
                                         establecerModoFormulario(). Es el único campo
                                         de la Sección 1 que sigue siendo editable, y
                                         sin decirlo pasaba desapercibido entre los
                                         campos grises de al lado. -->
                                    <span id="avisoFobEditable" class="badge bg-primary" style="display:none; font-weight:500;">
                                        <i class="bi bi-pencil-fill"></i> Editable
                                    </span>
                                </label>
                                <div class="input-group">
                                    <input class="input--style-1 decimales currencyInput" type="text" id="valorFobDolar" required>
                                </div>
                                <small id="ayudaFobEditable" class="text-muted" style="display:none;">
                                    Al cambiarlo se recalculan el F.O.B. $, el saldo de pagos y el % sobre FOB de los costos.
                                </small>
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
                            <?php if (isset($_SESSION['entorno']) && $_SESSION['entorno'] === 'uy'): ?>
                                <input type="hidden" id="despachante" value="Laffitte">
                            <?php else: ?>
                            <div class="col-md-5">
                                <label class="label-campo">Despachante</label>
                                <div class="input-group">
                                    <div class="js-select-simple">
                                        <select id="despachante" style="width: 283.16px;" required>
                                            <option value="">Seleccione...</option>
                                            <option value="Laffitte">Laffitte</option>
                                            <option value="Farre">Farre</option>
                                        </select>
                                        <div class="select-dropdown"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
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
                                    <div class="js-select-simple">
                                        <select id="puertoOrigen" style="width: 283.16px;">
                                            <option value="">Seleccione...</option>
                                            <?php
                                            // Cargar puertos desde la base de datos
                                            $puertos = $puertoClass->traerPuertos();
                                            foreach ($puertos as $puerto) {
                                                echo '<option value="' . htmlspecialchars($puerto['NOMBRE']) . '">' . htmlspecialchars($puerto['NOMBRE']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <div class="select-dropdown"></div>
                                    </div>
                                </div>    
                            </div>
                            <div class="col-md-5">
                                <label class="label-campo">Terminal</label>
                                <div class="input-group">
                                    <div class="js-select-simple">
                                        <select id="terminal" style="width: 283.16px;">
                                            <option value="">Seleccione...</option>
                                            <?php
                                            // Cargar terminales desde la base de datos según entorno
                                            $terminales = $terminalClass->traerTerminales();
                                            foreach ($terminales as $terminal) {
                                                echo '<option value="' . htmlspecialchars($terminal['NOMBRE']) . '">' . htmlspecialchars($terminal['NOMBRE']) . '</option>';
                                            }
                                            ?>
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
                                <h5 class="mb-3">Registro de Pagos <small class="text-muted">— en U$S</small></h5>
                                <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                        <!-- Los tres números que pide el circuito: qué se debía,
                                             qué se pagó y qué falta. Antes sólo estaba el saldo,
                                             y el total pagado había que sumarlo a ojo de la tabla. -->
                                        <span id="fobTotalUsd" class="badge bg-light text-dark border" style="font-size: 13px; padding: 6px 10px;">
                                            <i class="bi bi-cash-stack"></i> FOB: U$S 0,00
                                        </span>
                                        <span id="totalPagadoUsd" class="badge bg-light text-dark border" style="font-size: 13px; padding: 6px 10px;">
                                            <i class="bi bi-check2-all"></i> Pagado: U$S 0,00
                                        </span>
                                        <label class="label-campo" style="margin-bottom: 0;"><span id="saldoPendiente" class="badge bg-warning" style="font-size: 14px; padding: 6px 12px; border-radius: 4px; color: #856404;"><i class="bi bi-hourglass-split"></i> Saldo pendiente: U$S 0,00</span></label>
                                    </div>
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
                                                <th style="width: 22%;">Fecha de Pago</th>
                                                <th style="width: 23%;">Forma de Pago</th>
                                                <th style="width: 23%;">Medio de Pago</th>
                                                <th style="width: 20%;">Importe U$S</th>
                                                <th style="width: 12%; text-align: center;">Acciones</th>
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
                    <!-- Header informativo -->
                    <div class="alert alert-light border mb-3" style="background-color: #f8f9fa;">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">FOB Total U$S</small>
                                <strong id="modalFobTotal">U$S 0,00</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Saldo Actual U$S</small>
                                <strong id="modalSaldoActual">U$S 0,00</strong>
                            </div>
                            <div class="col-4" id="modalNuevoSaldoContainer">
                                <small class="text-muted d-block">Nuevo Saldo U$S</small>
                                <strong id="modalNuevoSaldo">U$S 0,00</strong>
                            </div>
                        </div>
                    </div>

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
                        <label class="form-label">Importe U$S</label>
                        <div class="input-group">
                            <span class="input-group-text">U$S</span>
                            <input type="number" class="form-control" id="montoNuevo" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <!-- Se dice acá y no sólo en el título: el campo decía
                             "Monto" a secas y la gente cargaba indistintamente
                             pesos o dólares, que es el origen de las 7 filas
                             que hubo que convertir con el script 08. -->
                        <div class="form-text">
                            El pago al proveedor del exterior se registra en dólares.
                            Se admiten pagos parciales: el saldo se actualiza solo.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="guardarNuevoPagoBtn" onclick="guardarNuevoPago()">Guardar Pago</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar un Pago.
         Antes editaba SOLO la fecha. Con el saldo calculado en dólares, el
         importe es lo que más se corrige -un pago cargado de más deja el
         saldo mintiendo- y la única salida era borrar el pago y rehacerlo. -->
    <div class="modal fade" id="modalEditarFechaPago" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i> Editar Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fecha de Pago</label>
                        <input type="text" class="form-control js-datepicker-edit-fecha" id="fechaPagoEdit" readonly>
                        <input type="hidden" id="idPagoEdit">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Forma de Pago</label>
                        <select class="form-select" id="formaPagoEdit">
                            <option>PAGO ANTICIPADO</option>
                            <option>PAGO VISTA</option>
                            <option>PAGO DIFERIDO</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Medio de Pago</label>
                        <select class="form-select" id="medioPagoEdit">
                            <option>Transferencia</option>
                            <option>Cheque</option>
                            <option>Tarjeta</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Importe U$S</label>
                        <div class="input-group">
                            <span class="input-group-text">U$S</span>
                            <input type="number" class="form-control" id="montoEdit" step="0.01" min="0">
                        </div>
                        <div class="form-text" id="avisoOrigenArs" style="display:none;"></div>
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
    <script src="../js/global.js?v=<?php echo time(); ?>"></script>
    <!-- Carga Inicial JS - Contiene toda la lógica del formulario -->
    <script src="../js/cargaInicial.js?v=<?php echo time(); ?>"></script>
    
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
