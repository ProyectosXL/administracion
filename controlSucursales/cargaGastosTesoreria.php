<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Los filtros viajan por POST y se guardan en sesión (patrón POST-Redirect-GET)
// para que no queden variables en la URL y un F5 no pida reenviar el formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mes'], $_POST['anio'])) {
    $mesPost  = str_pad((int)$_POST['mes'], 2, '0', STR_PAD_LEFT);
    $anioPost = (int)$_POST['anio'];

    if ((int)$mesPost >= 1 && (int)$mesPost <= 12 && $anioPost >= 2022 && $anioPost <= (int)date('Y')) {
        $_SESSION['cargaGastosTesoreria'] = ['mes' => $mesPost, 'anio' => (string)$anioPost];
    }

    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

require_once "Class/sucursal.php";

$sucursal = new Sucursal();
$todosLosLocales = $sucursal->traerLocales();

$filtros = isset($_SESSION['cargaGastosTesoreria']) ? $_SESSION['cargaGastosTesoreria'] : [];
$mes  = isset($filtros['mes'])  ? $filtros['mes']  : date('m');
$anio = isset($filtros['anio']) ? $filtros['anio'] : date('Y');

$mesesNombre = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
];

$fechaParaMostrar = $mesesNombre[$mes] . " " . $anio;

$currentYear = (int)date('Y');

$periodo = (int)$mes . "-" . $anio;

$primerDia = date('Y-m-01', strtotime($anio . "-" . $mes . "-01"));
$ultimoDia = date('Y-m-t', strtotime($primerDia));

$gastosTesoreria = $sucursal->traerGastosTesoreria($primerDia, $ultimoDia);
if (!is_array($gastosTesoreria)) {
    $gastosTesoreria = [];
}

$checkeados = $sucursal->traerGastosTesoreriaCheckeados($periodo);
$arraySucursalesCheckeadas = [];
foreach ((array)$checkeados as $checkeado) {
    $arraySucursalesCheckeadas[] = $checkeado['NRO_SUCURSAL'];
}

// Columnas de cuentas: el SP devuelve NRO_SUCURSAL, DESC_SUCURSAL y luego una columna por cuenta
$columnasCuentas = [];
if (count($gastosTesoreria) > 0) {
    $columnasCuentas = array_slice(array_keys($gastosTesoreria[0]), 2);
}

// Totales por sucursal y cuenta
$totalesPorSucursal = [];
foreach ($gastosTesoreria as $gasto) {
    $nro = $gasto['NRO_SUCURSAL'];
    foreach ($columnasCuentas as $col) {
        if (!isset($totalesPorSucursal[$nro][$col])) {
            $totalesPorSucursal[$nro][$col] = 0;
        }
        $totalesPorSucursal[$nro][$col] += (float)$gasto[$col];
    }
}

$cantidadSucursales = count($todosLosLocales);
$cantidadCargadas = 0;
foreach ($todosLosLocales as $local) {
    if (in_array($local['NRO_SUCURSAL'], $arraySucursalesCheckeadas)) {
        $cantidadCargadas++;
    }
}

$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga Gastos Tesorería</title>

    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos base compartidos con Resumen de Ventas -->
    <link rel="stylesheet" href="css/resumenVentas.css">
    <!-- Layout compacto compartido -->
    <link rel="stylesheet" href="css/pantallaCompacta.css">
    <!-- Estilos propios de la pantalla -->
    <link rel="stylesheet" href="css/cargaGastosTesoreria.css">
</head>

<body class="pantalla-compacta">
    <div class="container-fluid">
        <div class="modern-card">
            <div class="page-title">
                <a href="http://192.168.0.13:8000/" class="btn-home" title="Volver al menú">
                    <img src="../image/home-button.png" alt="Home">
                </a>
                <i class="bi bi-bank2"></i>
                <span>Carga Gastos Tesorería</span>
                <span class="date-range"> (<?= $fechaParaMostrar ?>)</span>

                <div class="title-actions ml-auto">
                    <button type="button" class="btn-info-toggle" data-toggle="collapse" data-target="#panelInfo" aria-expanded="false" aria-controls="panelInfo">
                        <i class="bi bi-info-circle"></i> ¿Qué muestra esta pantalla?
                    </button>

                    <div class="custom-toggle-container" onclick="cambiarEntornoCustom(this)" title="Cambiar entorno">
                        <div class="toggle-flag <?= ($checkedValue === 'central') ? 'active' : '' ?>" data-entorno="central">
                            <img src="../assets/images/bandera_con_sol__55757_std.jpg" alt="Argentina">
                            <span>ARG</span>
                        </div>
                        <div class="toggle-flag <?= ($checkedValue === 'suc_uy') ? 'active' : '' ?>" data-entorno="suc_uy">
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
                                Muestra, para el mes seleccionado, el total de <strong>gastos de caja de las sucursales que no llevan IVA</strong>
                                (egresos sin factura), acumulados por sucursal y por cuenta contable. Los importes salen del proceso
                                <code>RO_SP_CARGA_GASTOS_CAJA_SUCURSALES</code> entre el primer y el último día del mes.
                            </p>
                            <p>
                                Cada columna numérica es una cuenta de gasto. Las sucursales sin movimientos en el período aparecen en $0.
                            </p>
                        </div>

                        <div class="info-block">
                            <h6><i class="bi bi-check2-square"></i> ¿Qué significa "Cargado"?</h6>
                            <p>
                                Al marcar una sucursal se registra que sus gastos del período ya fueron cargados en tesorería y se guarda
                                una foto de los importes de ese momento. La marca es por sucursal y por mes, y <strong>no se puede deshacer</strong>
                                desde esta pantalla.
                            </p>
                            <p>
                                <em>Marcar todas</em> registra solamente las sucursales que todavía no estaban cargadas.
                            </p>
                        </div>

                        <div class="info-block info-block-wide">
                            <h6><i class="bi bi-diagram-3"></i> Relación con otras pantallas de Control Sucursales</h6>
                            <p>Todas trabajan sobre los mismos egresos de caja de sucursales (cuentas de gasto <code>5xxxxx</code>):</p>
                            <ol class="info-flow">
                                <li><strong>Control Egresos de Caja:</strong> se controla cada comprobante y se indica si tiene <strong>factura</strong> (lleva IVA) o no.</li>
                                <li>
                                    Según esa marca, el gasto sigue uno de dos caminos:
                                    <ul>
                                        <li><strong>Con factura (con IVA)</strong> → <strong>Carga Factura Sucursales</strong>, donde se contabiliza comprobante por comprobante.</li>
                                        <li><strong>Sin factura (sin IVA)</strong> → <strong>esta pantalla</strong>, donde se carga el total mensual por sucursal y cuenta.</li>
                                    </ul>
                                </li>
                            </ol>
                            <p class="info-note">
                                <i class="bi bi-lightbulb"></i>
                                Control Recepción de Efectivo trata los retiros de efectivo (RAF) de las sucursales, no los gastos, así que sus importes no se ven acá.
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

                <button type="submit" class="btn-modern btn-search" id="search">
                    <i class="bi bi-search"></i> Buscar
                </button>

                <button type="button" class="btn-modern btn-export" id="btnExport">
                    <i class="bi bi-file-earmark-excel"></i> Exportar
                </button>

                <button type="button" class="btn-modern btn-check-all" id="checkAll" onclick="checkMasivo()">
                    <i class="bi bi-check2-all"></i> Marcar todas
                </button>
            </form>
        </div>

        <!-- spinner -->
        <div id="boxLoading"></div>

        <div id="periodo" hidden><?= $periodo ?></div>
        <div id="periodoArchivo" hidden><?= $anio . "-" . $mes ?></div>

        <?php if (count($columnasCuentas) > 0) { ?>

        <div class="table-wrapper">
            <!-- Resumen + buscador -->
            <div class="table-toolbar">
                <div class="stats-row">
                    <div class="stat-chip">
                        <span class="stat-label">Sucursales</span>
                        <span class="stat-value"><?= $cantidadSucursales ?></span>
                    </div>
                    <div class="stat-chip">
                        <span class="stat-label">Cargadas</span>
                        <span class="stat-value"><span id="statCargadas"><?= $cantidadCargadas ?></span> / <?= $cantidadSucursales ?></span>
                    </div>
                    <div class="stat-chip stat-chip-accent">
                        <span class="stat-label">Total sin IVA</span>
                        <span class="stat-value" id="statTotal">$0</span>
                    </div>
                </div>
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="searchInput" placeholder="Buscar sucursal o número...">
                </div>
            </div>

            <table class="display" id="tablaGastosTesoreria">
                <thead>
                    <tr>
                        <th>NRO. SUC</th>
                        <th>SUCURSAL</th>
                        <?php foreach ($columnasCuentas as $i => $col) { ?>
                            <th class="th-cuenta" data-col="<?= $i ?>"><?= htmlspecialchars($col) ?></th>
                        <?php } ?>
                        <th class="col-total">TOTAL</th>
                        <th class="noExport col-cargado">CARGADO</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($todosLosLocales as $local) {
                        $nro = $local['NRO_SUCURSAL'];
                        $yaCargada = in_array($nro, $arraySucursalesCheckeadas);
                        $totalFila = 0;
                    ?>
                        <tr data-sucursal="<?= $nro ?>" class="<?= $yaCargada ? 'row-cargada' : '' ?>">
                            <td><?= $nro ?></td>
                            <td><?= $local['DESC_SUCURSAL'] ?></td>
                            <?php foreach ($columnasCuentas as $i => $col) {
                                $valor = isset($totalesPorSucursal[$nro][$col]) ? $totalesPorSucursal[$nro][$col] : 0;
                                $totalFila += $valor;
                            ?>
                                <td class="td-cuenta <?= ($valor == 0) ? 'valor-cero' : '' ?>" data-col="<?= $i ?>" data-value="<?= $valor ?>">$<?= number_format($valor, 0, ',', '.') ?></td>
                            <?php } ?>
                            <td class="td-total col-total" data-value="<?= $totalFila ?>">$<?= number_format($totalFila, 0, ',', '.') ?></td>
                            <td class="noExport col-cargado">
                                <label class="check-cargado">
                                    <input type="checkbox" class="checkControl" onchange="checkControl(this)" <?= $yaCargada ? "checked disabled" : "" ?>>
                                    <span><?= $yaCargada ? "Cargado" : "Pendiente" ?></span>
                                </label>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="2">TOTALES</td>
                        <?php foreach ($columnasCuentas as $i => $col) { ?>
                            <td class="total-cuenta" data-col="<?= $i ?>"></td>
                        <?php } ?>
                        <td class="col-total" id="totalGeneral"></td>
                        <td class="noExport"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php } else { ?>

        <div class="table-wrapper empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No hay gastos sin IVA para <?= $fechaParaMostrar ?></h5>
            <p>Probá con otro mes o año.</p>
        </div>

        <?php } ?>
    </div>

    <!-- jQuery (usar versión completa, no slim) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Plugin to export Excel -->
    <script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>

    <script src="js/gastosTesoreria.js" charset="utf-8"></script>

</body>

</html>
