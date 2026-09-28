<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Los filtros viajan por POST y se guardan en sesión (patrón POST-Redirect-GET)
// para que no queden variables en la URL y un F5 no pida reenviar el formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desde'], $_POST['hasta'])) {
    $esFecha = function ($valor) {
        $d = DateTime::createFromFormat('Y-m-d', $valor);
        return $d && $d->format('Y-m-d') === $valor;
    };

    if ($esFecha($_POST['desde']) && $esFecha($_POST['hasta'])) {
        $_SESSION['resumenVentas'] = [
            'desde' => $_POST['desde'],
            'hasta' => $_POST['hasta']
        ];
    }

    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

include 'Class/ventas.php';

$ventas = new Ventas();

// Como antes, la consulta (que ejecuta un SP pesado) solo corre después de buscar
$filtros = isset($_SESSION['resumenVentas']) ? $_SESSION['resumenVentas'] : null;
$buscado = ($filtros !== null);

$desde = $buscado ? $filtros['desde'] : date("Y-m-d");
$hasta = $buscado ? $filtros['hasta'] : date("Y-m-d");

$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';

$todasLasVentas = [];
if ($buscado) {
    $todasLasVentas = json_decode($ventas->traerVentas($desde, $hasta));
    if (!is_array($todasLasVentas)) {
        $todasLasVentas = [];
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de ventas</title>

    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/resumenVentas.css">
    <!-- Layout compacto compartido -->
    <link rel="stylesheet" href="css/pantallaCompacta.css">

</head>

<body class="pantalla-compacta">
    <div class="container-fluid">
        <div class="modern-card">
            <div class="page-title">
                <a href="http://192.168.0.13:8000/" class="btn-home" title="Volver al menú">
                    <img src="../image/home-button.png" alt="Home">
                </a>
                <i class="bi bi-credit-card"></i>
                <span>Ventas por medio de pago</span>
                <?php if ($buscado): ?>
                    <span class="date-range"><?= date('d/m/Y', strtotime($desde)) ?> a <?= date('d/m/Y', strtotime($hasta)) ?></span>
                <?php endif; ?>

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
                                Muestra, por sucursal, el <strong>total vendido en el rango de fechas</strong> abierto por <strong>medio de pago</strong>.
                                Cada vez que buscás se ejecuta el proceso <code>RO_RESUMEN_VENTA_SUCURSALES</code> sobre la base de locales
                                (o la de sucursales de Uruguay si el entorno es UY), así que la búsqueda puede demorar unos segundos.
                            </p>
                            <p>
                                Si el proceso no informa el total de ventas de una sucursal, se calcula sumando todos los medios de pago.
                            </p>
                        </div>

                        <div class="info-block">
                            <h6><i class="bi bi-list-check"></i> Columnas</h6>
                            <ul class="info-flow">
                                <li><strong>Total tarjetas</strong> (columna verde): Tarjeta + Cuenta DNI.</li>
                                <li><strong>Promo banco:</strong> descuentos bancarios. Los valores negativos se muestran en rojo y entre paréntesis.</li>
                                <li><strong>Dólares / Euros:</strong> ventas cobradas en moneda extranjera.</li>
                                <li><strong>Total ventas</strong> (columna naranja): total de la sucursal en el período.</li>
                            </ul>
                            <p>Los totales del pie suman solo las filas visibles, así que respetan el buscador.</p>
                        </div>

                        <div class="info-block info-block-wide">
                            <h6><i class="bi bi-diagram-3"></i> Relación con otras pantallas de Control Sucursales</h6>
                            <ul class="info-flow">
                                <li>
                                    <strong>Control Masivo de Cobranza:</strong> controla día por día, para una sucursal y un medio de pago,
                                    el importe del sistema contra el importe controlado. Lee otra fuente (la venta diaria por sucursal), no el resumen de esta pantalla.
                                </li>
                                <li>
                                    <strong>Integridad de Ventas:</strong> audita las ventas (central vs. local, IVA ventas, venta vs. cobranza y arqueo de caja).
                                    Esta pantalla es solo un resumen de consulta y no marca nada.
                                </li>
                                <li>
                                    <strong>Control Recepción de Efectivo:</strong> sigue el efectivo que cada sucursal envía a tesorería (RAF).
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <form class="filters-form" method="POST" id="formFiltros">
                <div>
                    <label for="desde">Desde:</label>
                    <input type="date" class="form-control" id="desde" name="desde" value="<?= $desde ?>" required>
                </div>

                <div>
                    <label for="hasta">Hasta:</label>
                    <input type="date" class="form-control" id="hasta" name="hasta" value="<?= $hasta ?>" required>
                </div>

                <button type="submit" class="btn-modern btn-search" id="search">
                    <i class="bi bi-search"></i> Buscar
                </button>

                <?php if (count($todasLasVentas) > 0): ?>
                    <button type="button" class="btn-modern btn-export" id="btnExport">
                        <i class="bi bi-file-earmark-excel"></i> Exportar
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- spinner -->
        <div id="boxLoading"></div>

    <?php if (count($todasLasVentas) > 0): ?>

        <div class="table-wrapper">
            <!-- Resumen + buscador -->
            <div class="table-toolbar">
                <div class="stats-row">
                    <div class="stat-chip">
                        <span class="stat-label">Sucursales</span>
                        <span class="stat-value" id="statSucursales"><?= count($todasLasVentas) ?></span>
                    </div>
                    <div class="stat-chip stat-chip-success">
                        <span class="stat-label">Total tarjetas</span>
                        <span class="stat-value" id="statTotalTarjetas">$0</span>
                    </div>
                    <div class="stat-chip stat-chip-accent">
                        <span class="stat-label">Total ventas</span>
                        <span class="stat-value" id="statTotalVentas">$0</span>
                    </div>
                </div>
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="searchInput" placeholder="Buscar sucursal o número...">
                </div>
            </div>

            <table class="display" id="tableVentas">
                <thead>
                    <tr>
                        <th>NRO. SUC</th>
                        <th>SUCURSAL</th>
                        <th>TARJETA</th>
                        <th>CUENTA DNI</th>
                        <th>TOTAL TARJETAS</th>
                        <th>MERCADO PAGO QR</th>
                        <th>MERCADO PAGO</th>
                        <th>MODO QR</th>
                        <th>PROMO BANCO</th>
                        <th>EFECTIVO</th>
                        <th>BONUS SHOPPING</th>
                        <th>DOLARES</th>
                        <th>EUROS</th>
                        <th>TOTAL VENTAS</th>
                    </tr>
                </thead>

                <tbody id="table">
                    <?php
                    foreach ($todasLasVentas as $valor => $key) {
                    ?>
                        <tr>
                            <td><?= $key->NRO_SUCURSAL ?></td>
                            <td><?= $key->DESC_SUCURSAL ?></td>
                            <td class="tdTarjeta" data-value="<?= $key->TARJETA ?? 0 ?>">$<?= number_format($key->TARJETA ?? 0, 2, '.', ',') ?></td>
                            <td class="tdCuentaDni" data-value="<?= $key->CUENTA_DNI ?? 0 ?>">$<?= number_format($key->CUENTA_DNI ?? 0, 2, '.', ',') ?></td>
                            <td class="tdTotalTarjetas col-total-tarjetas" data-value="<?= ($key->TARJETA ?? 0) + ($key->CUENTA_DNI ?? 0) ?>">$<?= number_format(($key->TARJETA ?? 0) + ($key->CUENTA_DNI ?? 0), 2, '.', ',') ?></td>
                            <td class="tdMercadoPagoQr" data-value="<?= $key->MERCADO_PAGO_QR ?? 0 ?>">$<?= number_format($key->MERCADO_PAGO_QR ?? 0, 2, '.', ',') ?></td>
                            <td class="tdMercadoPago" data-value="<?= $key->MERCADO_PAGO ?? 0 ?>">$<?= number_format($key->MERCADO_PAGO ?? 0, 2, '.', ',') ?></td>
                            <td class="tdModoQr" data-value="<?= $key->MODO_QR ?? 0 ?>">$<?= number_format($key->MODO_QR ?? 0, 2, '.', ',') ?></td>
                            <td class="tdPromoBanco valor-negativo" data-value="<?= $key->PROMO_BANCO ?? 0 ?>">
                                <?php if (($key->PROMO_BANCO ?? 0) < 0): ?>
                                    <span style="color: #ef4444;">($<?= number_format(abs($key->PROMO_BANCO ?? 0), 2, '.', ',') ?>)</span>
                                <?php else: ?>
                                    $<?= number_format($key->PROMO_BANCO ?? 0, 2, '.', ',') ?>
                                <?php endif; ?>
                            </td>
                            <td class="tdEfectivo" data-value="<?= $key->EFECTIVO ?? 0 ?>">$<?= number_format($key->EFECTIVO ?? 0, 2, '.', ',') ?></td>
                            <td class="tdBonusShopping" data-value="<?= $key->BONUS_SHOPPING ?? 0 ?>">$<?= number_format($key->BONUS_SHOPPING ?? 0, 2, '.', ',') ?></td>
                            <td class="tdDolares" data-value="<?= $key->DOLARES ?? 0 ?>">$<?= number_format($key->DOLARES ?? 0, 2, '.', ',') ?></td>
                            <td class="tdEuros" data-value="<?= $key->EUROS ?? 0 ?>">$<?= number_format($key->EUROS ?? 0, 2, '.', ',') ?></td>
                            <td class="tdTotalVentas col-total-ventas" data-value="<?= $key->VENTAS ?? 0 ?>">$<?= number_format($key->VENTAS ?? 0, 2, '.', ',') ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="2">TOTALES</td>
                        <td id="totalTarjeta"></td>
                        <td id="totalCuentaDni"></td>
                        <td id="totalTarjetas" class="col-total-tarjetas"></td>
                        <td id="totalMercadoPagoQr"></td>
                        <td id="totalMercadoPago"></td>
                        <td id="totalModoQr"></td>
                        <td id="totalPromoBanco"></td>
                        <td id="totalEfectivo"></td>
                        <td id="totalBonusShopping"></td>
                        <td id="totalDolares"></td>
                        <td id="totalEuros"></td>
                        <td id="totalVentas" class="col-total-ventas"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    <?php elseif ($buscado): ?>

        <div class="table-wrapper empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No hay ventas para mostrar</h5>
            <p>No se encontraron ventas entre el <?= date('d/m/Y', strtotime($desde)) ?> y el <?= date('d/m/Y', strtotime($hasta)) ?>. Probá con otro rango de fechas.</p>
        </div>

    <?php else: ?>

        <div class="table-wrapper empty-state">
            <i class="bi bi-calendar-range"></i>
            <h5>Elegí un rango de fechas</h5>
            <p>Seleccioná Desde y Hasta y tocá <strong>Buscar</strong> para ver las ventas por medio de pago.</p>
        </div>

    <?php endif; ?>
    </div>

    <!-- jQuery (usar versión completa, no slim) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    <!-- Plugin to export Excel -->
    <script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>

    <!-- Script específico de resumen de ventas -->
    <script src="js/resumenVentas.js" charset="utf-8"></script>

</body>

</html>
