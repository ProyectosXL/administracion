<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "Class/sucursal.php";

// Los filtros viajan por POST y se guardan en sesión (patrón POST-Redirect-GET)
// para que no queden variables en la URL y un F5 no pida reenviar el formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mes'], $_POST['anio'], $_POST['sucursal'], $_POST['medioPago'])) {
    $mesPost       = str_pad((int)$_POST['mes'], 2, '0', STR_PAD_LEFT);
    $anioPost      = (int)$_POST['anio'];
    $sucursalPost  = (string)$_POST['sucursal'];
    $medioPagoPost = (string)$_POST['medioPago'];

    // Sucursal y medio de pago van directo a la consulta: solo se aceptan valores de las listas del entorno actual
    $sucursalValidacion = new Sucursal();
    $nrosValidos   = array_column((array)$sucursalValidacion->traerLocales(true), 'NRO_SUCURSAL');
    $mediosValidos = array_column((array)$sucursalValidacion->traerTodosLosMediosDePago(), 'MEDIO_PAGO');

    if ((int)$mesPost >= 1 && (int)$mesPost <= 12
        && $anioPost >= 2022 && $anioPost <= (int)date('Y')
        && in_array($sucursalPost, $nrosValidos)
        && in_array($medioPagoPost, $mediosValidos, true)) {
        $_SESSION['controlMasivoCaja'] = [
            'mes'       => $mesPost,
            'anio'      => (string)$anioPost,
            'sucursal'  => $sucursalPost,
            'medioPago' => $medioPagoPost
        ];
    }

    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// 'uy' lo setea Controller/cambiarEntornoTasky.php (tabla RO_T_VENTA_DIARIA_SUCURSALES_UY).
// 'suc_uy' viene de otras pantallas: acá se muestra como Uruguay, igual que el toggle anterior.
$entornoActual = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$esEntornoUy   = ($entornoActual === 'uy');
$flagUyActiva  = in_array($entornoActual, ['uy', 'suc_uy']);

$sucursal = new Sucursal();
$todosLosLocales = $sucursal->traerLocales(true);
if (!is_array($todosLosLocales)) {
    $todosLosLocales = [];
}
$todosLosMediosDePago = $sucursal->traerTodosLosMediosDePago();

$nombresSucursales = [];
foreach ($todosLosLocales as $local) {
    $nombresSucursales[$local['NRO_SUCURSAL']] = $local['DESC_SUCURSAL'];
}
$mediosDePago = array_column((array)$todosLosMediosDePago, 'MEDIO_PAGO');

$filtros = isset($_SESSION['controlMasivoCaja']) ? $_SESSION['controlMasivoCaja'] : [];
$mes  = isset($filtros['mes'])  ? $filtros['mes']  : date("m");
$anio = isset($filtros['anio']) ? $filtros['anio'] : date("Y");

// Si se cambió de entorno y la sucursal / medio guardados no existen en el nuevo, se vuelve a los valores por defecto
$nroSucursal = (isset($filtros['sucursal']) && isset($nombresSucursales[$filtros['sucursal']])) ? $filtros['sucursal'] : '2';
$descSucursal = isset($nombresSucursales[$nroSucursal]) ? $nombresSucursales[$nroSucursal] : 'UNICENTER';

$medioPagoSelected = (isset($filtros['medioPago']) && in_array($filtros['medioPago'], $mediosDePago, true)) ? $filtros['medioPago'] : 'MODO QR';

$mesesNombre = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
];

$currentYear = (int)date('Y');

$primerDia = date('Y-m-01', strtotime($anio . "-" . $mes . "-01"));
$ultimoDia = date('Y-m-t', strtotime($primerDia));

$todosLosImportes = $sucursal->traerImportesTotalesPorPeriodo($nroSucursal, $primerDia, $ultimoDia, $medioPagoSelected);
if (!is_array($todosLosImportes)) {
    $todosLosImportes = [];
}

// En efectivo y euros el $ CONTROL se precarga con el $ SISTEMA
$controlIgualSistema = in_array($medioPagoSelected, ['EUROS', 'EFECTIVO']);

// Resumen
$cantidadDias = count($todosLosImportes);
$cantidadVerificados = 0;
$verificado = true;
foreach ($todosLosImportes as $importe) {
    if ($importe['VERIFICADO'] == 0) {
        $verificado = false;
    }
    if ($importe['VERIFICADO'] == 1) {
        $cantidadVerificados++;
    }
}

// Mismo texto que mostraba la celda $ SISTEMA (el JS lo usa para calcular diferencias)
$textoMonto = function ($valor) {
    return ($valor < 0) ? "- $" . number_format($valor * -1, 0, ',', '.') : "$" . number_format($valor, 0, ',', '.');
};
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Masivo de Cobranza</title>

    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Estilos base compartidos con Resumen de Ventas -->
    <link rel="stylesheet" href="css/resumenVentas.css">
    <!-- Layout compacto compartido -->
    <link rel="stylesheet" href="css/pantallaCompacta.css">
    <!-- Estilos propios de la pantalla -->
    <link rel="stylesheet" href="css/controlMasivoCaja.css">
</head>

<body class="pantalla-compacta">
    <div class="container-fluid">
        <div class="modern-card">
            <div class="page-title">
                <a href="http://192.168.0.13:8000/" class="btn-home" title="Volver al menú">
                    <img src="../image/home-button.png" alt="Home">
                </a>
                <i class="bi bi-cash"></i>
                <span>Control Masivo de Cobranza</span>
                <span class="date-range">
                    <?= htmlspecialchars($descSucursal) ?> · <?= htmlspecialchars($medioPagoSelected) ?> · <?= $mesesNombre[$mes] ?> <?= $anio ?>
                </span>

                <div class="title-actions ml-auto">
                    <button type="button" class="btn-info-toggle" data-toggle="collapse" data-target="#panelInfo" aria-expanded="false" aria-controls="panelInfo">
                        <i class="bi bi-info-circle"></i> ¿Qué muestra esta pantalla?
                    </button>

                    <div class="custom-toggle-container" onclick="cambiarEntornoCustom(this)" data-entorno-actual="<?= htmlspecialchars($entornoActual) ?>" title="Cambiar entorno">
                        <div class="toggle-flag <?= $flagUyActiva ? '' : 'active' ?>" data-entorno="central">
                            <img src="../assets/images/bandera_con_sol__55757_std.jpg" alt="Argentina">
                            <span>ARG</span>
                        </div>
                        <div class="toggle-flag <?= $flagUyActiva ? 'active' : '' ?>" data-entorno="uy">
                            <img src="../assets/images/UY.png" alt="Uruguay">
                            <span>UY</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel informativo -->
            <div class="collapse" id="panelInfo">
                <div class="info-panel">
                    <div class="info-grid">
                        <div class="info-block">
                            <h6><i class="bi bi-database"></i> ¿Qué datos trae?</h6>
                            <p>
                                Muestra, día por día, lo que cobró <strong>una sucursal con un medio de pago</strong> en el mes elegido,
                                tal como está en la tabla de venta diaria <code>RO_T_VENTA_DIARIA_SUCURSALES</code>
                                (en Uruguay, <code>RO_T_VENTA_DIARIA_SUCURSALES_UY</code>). Hay una fila por cada día que tiene registro.
                            </p>
                            <p>
                                Esta pantalla no crea filas: solo actualiza el <strong>$ control</strong>, las <strong>observaciones</strong>
                                y la marca de <strong>verificado</strong> de las que ya existen.
                            </p>
                        </div>

                        <div class="info-block">
                            <h6><i class="bi bi-list-check"></i> ¿Qué significa cada columna?</h6>
                            <ul class="info-flow">
                                <li><strong>$ Sistema:</strong> importe cobrado según el sistema.</li>
                                <li><strong>Cotización TC:</strong> tipo de cambio guardado para ese día. <strong>Total pesos</strong> = $ sistema × cotización.</li>
                                <li>
                                    <strong>$ Control:</strong> importe que se carga a mano para contrastar.
                                    En <em>EFECTIVO</em> y <em>EUROS</em> se completa con el $ sistema.
                                </li>
                                <li><strong>Diferencia:</strong> $ sistema − $ control. Se recalcula al modificar el $ control.</li>
                                <li>Los días <strong>verificados</strong> se ven bloqueados y marcados en verde.</li>
                            </ul>
                        </div>

                        <div class="info-block">
                            <h6><i class="bi bi-hand-index"></i> Acciones</h6>
                            <ul class="info-flow">
                                <li><i class="bi bi-box-arrow-down"></i> <strong>Guardar:</strong> graba el $ control y las observaciones de todos los días del mes, sin verificarlos.</li>
                                <li>
                                    <i class="bi bi-check-circle"></i> <strong>Controlar:</strong> graba lo mismo y marca todos los días como verificados.
                                    Exige que todos tengan $ control distinto de cero y, si hay diferencias, pide confirmación.
                                    Solo aparece si queda algún día sin verificar.
                                </li>
                                <li><i class="bi bi-file-earmark-excel"></i> <strong>Exportar:</strong> descarga la tabla en Excel.</li>
                            </ul>
                            <p class="info-note">
                                <i class="bi bi-exclamation-triangle"></i>
                                Guardar graba todos los días del mes como no verificados, incluso los que ya estaban verificados.
                            </p>
                        </div>

                        <div class="info-block">
                            <h6><i class="bi bi-diagram-3"></i> Relación con otras pantallas</h6>
                            <p>
                                <strong>Resumen de Ventas</strong> muestra las ventas de todas las sucursales abiertas por medio de pago
                                (Tarjeta, Modo QR, Promo Banco, Efectivo, Dólares, Euros...). Esta pantalla baja al <strong>detalle diario</strong>
                                de una sucursal y un medio para controlarlo, pero lee otra tabla.
                            </p>
                            <p>
                                Las demás pantallas de Control Sucursales <strong>no usan esta tabla</strong>:
                                Integridad de Ventas audita ventas, IVA y arqueos contra la base de la sucursal;
                                Control Egresos de Caja, Carga Factura Sucursales y Carga Gastos Tesorería trabajan sobre gastos (<code>5xxxxx</code>);
                                Control Recepción de Efectivo sigue los retiros de efectivo (RAF).
                                El Promo Banco también se verifica desde la auditoría de promociones bancarias de Agenda de Pagos.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <form class="filters-form" method="POST" id="formFiltros">
                <div>
                    <label for="selectMes">Mes:</label>
                    <select name="mes" id="selectMes" class="form-control">
                        <?php foreach ($mesesNombre as $num => $nombre) { ?>
                            <option value="<?= $num ?>" <?= ($mes == $num) ? "selected" : "" ?>><?= $nombre ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label for="selectAnio">Año:</label>
                    <select name="anio" id="selectAnio" class="form-control">
                        <?php for ($y = 2022; $y <= $currentYear; $y++) { ?>
                            <option value="<?= $y ?>" <?= ($anio == $y) ? "selected" : "" ?>><?= $y ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="filter-sucursal">
                    <label for="selectSucursal">Sucursal:</label>
                    <select name="sucursal" id="selectSucursal" class="form-control">
                        <?php foreach ($todosLosLocales as $local) { ?>
                            <option value="<?= htmlspecialchars($local['NRO_SUCURSAL']) ?>" <?= ($nroSucursal == $local['NRO_SUCURSAL']) ? "selected" : "" ?>>
                                <?= htmlspecialchars($local['DESC_SUCURSAL']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label for="medioPago">Medio de pago:</label>
                    <select name="medioPago" id="medioPago" class="form-control">
                        <?php foreach ($mediosDePago as $medio) { ?>
                            <option value="<?= htmlspecialchars($medio) ?>" <?= ($medioPagoSelected === $medio) ? "selected" : "" ?>><?= htmlspecialchars($medio) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <button type="submit" class="btn-modern btn-search" id="search">
                    <i class="bi bi-search"></i> Buscar
                </button>

                <?php if ($cantidadDias > 0) { ?>
                    <div class="filters-actions">
                        <button type="button" class="btn-modern btn-guardar-todo" id="btnGuardar" onclick="guardar()">
                            <i class="bi bi-box-arrow-down"></i> Guardar
                        </button>
                        <?php if (!$verificado) { ?>
                            <button type="button" class="btn-modern btn-search" id="controlar" onclick="controlar()">
                                <i class="bi bi-check-circle"></i> Controlar
                            </button>
                        <?php } ?>
                        <button type="button" class="btn-modern btn-export" id="btnExport">
                            <i class="bi bi-file-earmark-excel"></i> Exportar
                        </button>
                    </div>
                <?php } ?>
            </form>
        </div>

        <!-- spinner -->
        <div id="boxLoading"></div>

        <?php if ($cantidadDias > 0) { ?>

        <div class="table-wrapper">
            <!-- Resumen + buscador -->
            <div class="table-toolbar">
                <div class="stats-row">
                    <div class="stat-chip">
                        <span class="stat-label">Días</span>
                        <span class="stat-value"><?= $cantidadDias ?></span>
                    </div>
                    <div class="stat-chip">
                        <span class="stat-label">Verificados</span>
                        <span class="stat-value"><?= $cantidadVerificados ?> / <?= $cantidadDias ?></span>
                    </div>
                    <div class="stat-chip">
                        <span class="stat-label">$ Sistema</span>
                        <span class="stat-value" id="statSistema">$0</span>
                    </div>
                    <div class="stat-chip">
                        <span class="stat-label">$ Control</span>
                        <span class="stat-value" id="statControl">$0</span>
                    </div>
                    <div class="stat-chip stat-chip-accent">
                        <span class="stat-label">Diferencia</span>
                        <span class="stat-value" id="statDiferencia">$0</span>
                    </div>
                </div>
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="searchInput" placeholder="Buscar fecha, importe, observación...">
                </div>
            </div>

            <table class="display" id="tablaControl">
                <thead>
                    <tr>
                        <th>FECHA</th>
                        <th class="th-num">$ SISTEMA</th>
                        <th class="th-num">COTIZACIÓN TC</th>
                        <th class="th-num">TOTAL PESOS</th>
                        <th class="th-num">$ CONTROL</th>
                        <th class="th-num">DIFERENCIA</th>
                        <th>OBSERVACIONES</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($todosLosImportes as $importe) {
                        if ($controlIgualSistema) {
                            $importe['IMPORTE_$_FISICO'] = $importe['IMPORTE_$_SISTEMA'];
                        }
                        $totalEnPesos = $importe['IMPORTE_$_SISTEMA'] * $importe['COTIZACION_TC'];
                        if ($totalEnPesos == "-0") {
                            $totalEnPesos = 0;
                        }
                        $valorEnSistema = ($importe['IMPORTE_$_SISTEMA'] != null) ? $importe['IMPORTE_$_SISTEMA'] : 0;
                        $estaVerificado = ($importe['VERIFICADO'] == 1);
                    ?>
                        <tr class="<?= $estaVerificado ? 'row-verificada' : '' ?>"
                            data-id="<?= htmlspecialchars($importe['ID']) ?>"
                            data-sistema="<?= $textoMonto($valorEnSistema) ?>">

                            <td class="td-fecha" data-sort="<?= $importe['FECHA']->format("Y-m-d") ?>">
                                <?= $importe['FECHA']->format("d/m/Y") ?>
                                <?php if ($estaVerificado) { ?>
                                    <i class="bi bi-check-circle-fill icon-ok" title="Verificado"></i>
                                <?php } ?>
                            </td>
                            <td class="td-num" data-sort="<?= (float)$valorEnSistema ?>"><?= $textoMonto($valorEnSistema) ?></td>
                            <td class="td-num" data-sort="<?= (float)$importe['COTIZACION_TC'] ?>">$<?= number_format((float)$importe['COTIZACION_TC'], $esEntornoUy ? 3 : 0, ',', '.') ?></td>
                            <td class="td-num" data-sort="<?= (float)$totalEnPesos ?>">$<?= number_format((float)$totalEnPesos, $esEntornoUy ? 2 : 0, ',', '.') ?></td>
                            <td class="td-num td-control">
                                <input type="text" class="input-tabla input-control" onchange="calcularDiferecias(this)" value="$<?= number_format((float)$importe['IMPORTE_$_FISICO'], 0, ',', '.') ?>" <?= $estaVerificado ? "disabled" : "" ?>>
                            </td>
                            <td class="td-num td-diferencia">0</td>
                            <td class="td-observaciones">
                                <input type="text" class="input-tabla input-observacion" value="<?= htmlspecialchars((string)$importe['OBSERVACIONES']) ?>" <?= $estaVerificado ? "disabled" : "" ?>>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>

                <tfoot>
                    <tr>
                        <td>TOTAL</td>
                        <td id="totalEnSistema"></td>
                        <td></td>
                        <td></td>
                        <td id="totalFisico"></td>
                        <td id="totalDiferencia"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php } else { ?>

        <div class="table-wrapper empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No hay cobranzas para mostrar</h5>
            <p>No se encontraron registros de <?= htmlspecialchars($medioPagoSelected) ?> para <?= htmlspecialchars($descSucursal) ?> en <?= $mesesNombre[$mes] ?> <?= $anio ?>. Probá con otro mes, sucursal o medio de pago.</p>
        </div>

        <?php } ?>
    </div>

    <!-- jQuery (usar versión completa, no slim) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Plugin to export Excel -->
    <script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>

    <script src="js/controlMasivo.js" charset="utf-8"></script>

</body>

</html>
