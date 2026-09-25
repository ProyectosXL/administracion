<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Los filtros viajan por POST y se guardan en sesión (patrón POST-Redirect-GET)
    // para que no queden variables en la URL y un F5 no pida reenviar el formulario.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desde'], $_POST['hasta'], $_POST['selectSucursal'])) {
        $esFecha = function ($valor) {
            $d = DateTime::createFromFormat('Y-m-d', $valor);
            return $d && $d->format('Y-m-d') === $valor;
        };

        if ($esFecha($_POST['desde']) && $esFecha($_POST['hasta'])) {
            $_SESSION['controlEgresosCaja'] = [
                'desde'          => $_POST['desde'],
                'hasta'          => $_POST['hasta'],
                'selectSucursal' => $_POST['selectSucursal']
            ];
        }

        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    require_once "Class/sucursal.php";

    $fecha_actual = date("Y-m-d");
    $filtros = isset($_SESSION['controlEgresosCaja']) ? $_SESSION['controlEgresosCaja'] : [];

    $desde = isset($filtros['desde']) ? $filtros['desde'] : date("Y-m-d", strtotime($fecha_actual . "- 1 week"));
    $hasta = isset($filtros['hasta']) ? $filtros['hasta'] : date("Y-m-d", strtotime($fecha_actual . "- 1 day"));
    $selectSucursal = explode("-", isset($filtros['selectSucursal']) ? $filtros['selectSucursal'] : '2-UNICENTER', 2);

    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';

    $sucursal = new Sucursal();
    $todosLosLocales = $sucursal->traerLocales(true);

    $data = $sucursal->traerGastosCajaSucursales($desde, $hasta, $selectSucursal[0]);
    if (!is_array($data)) {
        $data = [];
    }

    // Resumen
    $cantidadComprobantes = count($data);
    $totalMonto = 0;
    $cantidadAutorizados = 0;
    $cantidadFactura = 0;
    $cantidadControlados = 0;
    foreach ($data as $gasto) {
        $totalMonto += (float)$gasto['MONTO'];
        if ($gasto['AUTORIZADO'] == 1) $cantidadAutorizados++;
        if ($gasto['FACTURA'] == 1) $cantidadFactura++;
        if ($gasto['CONTROL'] == 1) $cantidadControlados++;
    }

    // Mismo formato de monto que se enviaba al controlador leyendo el texto de la celda
    $montoParaEnviar = function ($monto) {
        $sinPuntos = str_replace('.', '', number_format(abs($monto), 0, '.', '.'));
        return ($monto < 0) ? "- " . $sinPuntos : $sinPuntos;
    };
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Egresos de Caja</title>

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
    <link rel="stylesheet" href="css/controlEgresosCajaSucursales.css">
</head>

<body class="pantalla-compacta">
    <div class="container-fluid">
        <div class="modern-card">
            <div class="page-title">
                <a href="http://192.168.0.13:8000/" class="btn-home" title="Volver al menú">
                    <img src="../image/home-button.png" alt="Home">
                </a>
                <i class="bi bi-cash-stack"></i>
                <span>Control Egresos de Caja</span>
                <span class="date-range">
                    <?= htmlspecialchars($selectSucursal[1] ?? '') ?> · <?= date('d/m/Y', strtotime($desde)) ?> a <?= date('d/m/Y', strtotime($hasta)) ?>
                </span>

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
                                Lista, comprobante por comprobante, los <strong>egresos de caja de una sucursal</strong> entre las fechas elegidas:
                                movimientos al debe en cuentas de gasto <code>5xxxxx</code> (se excluye la <code>522400</code>).
                            </p>
                            <p>
                                En Argentina los datos se leen directo de la base de la sucursal, así se ven también los egresos del día que todavía no se replicaron.
                                Se completan con el histórico ya replicado en central.
                            </p>
                        </div>

                        <div class="info-block">
                            <h6><i class="bi bi-list-check"></i> ¿Qué significa cada columna?</h6>
                            <ul class="info-legend">
                                <li><i class="bi bi-eye"></i> <strong>Ver:</strong> fotos del comprobante que subió la sucursal.</li>
                                <li><i class="bi bi-shield-check"></i> <strong>Autorizado:</strong> marca histórica de la pantalla Autorizar Egresos de Caja, que ya no se usa. Solo se ve en gastos autorizados antes de darla de baja.</li>
                                <li><i class="bi bi-inbox"></i> <strong>Recibido:</strong> el comprobante físico llegó a tesorería central.</li>
                                <li><i class="bi bi-receipt"></i> <strong>Factura:</strong> el gasto tiene factura, o sea que <strong>lleva IVA</strong>. Se marca acá.</li>
                                <li><i class="bi bi-clipboard-check"></i> <strong>Control:</strong> el comprobante fue revisado. Se marca acá.</li>
                            </ul>
                        </div>

                        <div class="info-block info-block-wide">
                            <h6><i class="bi bi-diagram-3"></i> Relación con otras pantallas de Control Sucursales</h6>
                            <ol class="info-flow">
                                <li><strong>Control Egresos de Caja (esta pantalla):</strong> se controla cada comprobante y se indica si tiene factura.</li>
                                <li>
                                    La marca de <strong>Factura</strong> define cómo sigue el gasto:
                                    <ul>
                                        <li><strong>Con factura (con IVA)</strong> → aparece en <strong>Carga Factura Sucursales</strong>, donde se contabiliza comprobante por comprobante.</li>
                                        <li><strong>Sin factura (sin IVA)</strong> → se carga como total mensual por sucursal y cuenta en <strong>Carga Gastos Tesorería</strong>.</li>
                                    </ul>
                                </li>
                            </ol>
                            <p class="info-note">
                                <i class="bi bi-lightbulb"></i>
                                Carga Factura Sucursales muestra únicamente los gastos que tienen tildada la columna Factura en esta pantalla.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <form class="filters-form" method="POST" id="formFiltros">
                <div>
                    <label for="desde">Desde:</label>
                    <input type="date" class="form-control" id="desde" name="desde" value="<?= $desde ?>">
                </div>

                <div>
                    <label for="hasta">Hasta:</label>
                    <input type="date" class="form-control" id="hasta" name="hasta" value="<?= $hasta ?>">
                </div>

                <div class="filter-sucursal">
                    <label for="selectSucursal">Sucursal:</label>
                    <select name="selectSucursal" id="selectSucursal" class="form-control">
                        <?php foreach ($todosLosLocales as $value) { ?>
                            <option value="<?= htmlspecialchars($value['NRO_SUCURSAL'] . "-" . $value['DESC_SUCURSAL']) ?>"
                                <?= ($selectSucursal[0] == $value['NRO_SUCURSAL']) ? "selected" : "" ?>>
                                <?= htmlspecialchars($value['DESC_SUCURSAL']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <button type="submit" class="btn-modern btn-search" id="search">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </form>
        </div>

        <!-- spinner -->
        <div id="boxLoading"></div>

        <!-- Modal de imágenes -->
        <div id="carruselImagenes" class="modal fade" tabindex="-1" aria-hidden="true"></div>

        <?php if ($cantidadComprobantes > 0) { ?>

        <div class="table-wrapper">
            <!-- Resumen + buscador -->
            <div class="table-toolbar">
                <div class="stats-row">
                    <div class="stat-chip">
                        <span class="stat-label">Comprobantes</span>
                        <span class="stat-value"><?= $cantidadComprobantes ?></span>
                    </div>
                    <div class="stat-chip stat-chip-accent">
                        <span class="stat-label">Total</span>
                        <span class="stat-value">$<?= number_format($totalMonto, 0, ',', '.') ?></span>
                    </div>
                    <div class="stat-chip">
                        <span class="stat-label">Autorizados</span>
                        <span class="stat-value"><?= $cantidadAutorizados ?> / <?= $cantidadComprobantes ?></span>
                    </div>
                    <div class="stat-chip">
                        <span class="stat-label">Con factura</span>
                        <span class="stat-value"><span id="statFactura"><?= $cantidadFactura ?></span> / <?= $cantidadComprobantes ?></span>
                    </div>
                    <div class="stat-chip">
                        <span class="stat-label">Controlados</span>
                        <span class="stat-value"><span id="statControl"><?= $cantidadControlados ?></span> / <?= $cantidadComprobantes ?></span>
                    </div>
                </div>
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="searchInput" placeholder="Buscar comprobante, cuenta, leyenda...">
                </div>
            </div>

            <table class="display" id="tablaEgresos">
                <thead>
                    <tr>
                        <th>FECHA</th>
                        <th>SUC.</th>
                        <th>TIPO</th>
                        <th>COMPROBANTE</th>
                        <th>COD. CTA</th>
                        <th>CUENTA</th>
                        <th class="th-num">MONTO</th>
                        <th>LEYENDA</th>
                        <th class="th-icon no-sort">VER</th>
                        <th class="th-icon" title="Autorizado"><i class="bi bi-shield-check"></i></th>
                        <th class="th-icon" title="Recibido"><i class="bi bi-inbox"></i></th>
                        <th class="th-icon" title="Factura (lleva IVA)"><i class="bi bi-receipt"></i></th>
                        <th class="th-icon" title="Control"><i class="bi bi-clipboard-check"></i></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data as $gasto) {
                        $monto = (float)$gasto['MONTO'];
                        $fechaRecibido = ($gasto['FECHA_RECIBIDO'] != null) ? $gasto['FECHA_RECIBIDO']->format("d/m/Y") : "";
                        $fechaAutorizado = ($gasto['FECHA_AUTORIZADO'] != null) ? $gasto['FECHA_AUTORIZADO']->format("d/m/Y") : "";
                        $rowClass = ($gasto['CONTROL'] == 1) ? "row-controlada" : "";
                    ?>
                        <tr class="<?= $rowClass ?>"
                            data-fecha="<?= $gasto['FECHA']->format("d/m/Y") ?>"
                            data-sucursal="<?= htmlspecialchars(trim($gasto['NRO_SUCURS'])) ?>"
                            data-tipo="<?= htmlspecialchars(trim($gasto['COD_COMP'])) ?>"
                            data-comprobante="<?= htmlspecialchars(trim($gasto['N_COMP'])) ?>"
                            data-cod-cuenta="<?= htmlspecialchars(trim($gasto['COD_CTA'])) ?>"
                            data-cuenta="<?= htmlspecialchars(trim($gasto['DESC_CUENTA'])) ?>"
                            data-monto="<?= $montoParaEnviar($monto) ?>"
                            data-leyenda="<?= htmlspecialchars(trim((string)$gasto['LEYENDA'])) ?>">

                            <td data-sort="<?= $gasto['FECHA']->format("Y-m-d") ?>" class="td-fecha"><?= $gasto['FECHA']->format("d/m/Y") ?></td>
                            <td><?= $gasto['NRO_SUCURS'] ?></td>
                            <td><?= $gasto['COD_COMP'] ?></td>
                            <td class="td-comprobante" title="Usuario: <?= htmlspecialchars($gasto['USUARIO'] ?? 'N/A') ?>"><?= $gasto['N_COMP'] ?></td>
                            <td><span class="cuenta-codigo"><?= $gasto['COD_CTA'] ?></span></td>
                            <td><?= $gasto['DESC_CUENTA'] ?></td>
                            <td class="td-num <?= ($monto < 0) ? 'monto-negativo' : '' ?>" data-sort="<?= $monto ?>">
                                <?= ($monto < 0) ? "- " : "" ?>$<?= number_format(abs($monto), 0, ',', '.') ?>
                            </td>
                            <td class="td-leyenda" title="<?= htmlspecialchars((string)$gasto['LEYENDA']) ?>"><?= htmlspecialchars((string)$gasto['LEYENDA']) ?></td>
                            <td class="td-icon">
                                <?php if ($gasto['guardado'] == 1) { ?>
                                    <button type="button" class="btn-ver" onclick="mostrarImagen(this)" title="Ver comprobante">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                <?php } ?>
                            </td>
                            <td class="td-icon" data-sort="<?= ($gasto['AUTORIZADO'] == 1) ? 1 : 0 ?>">
                                <?php if ($gasto['AUTORIZADO'] == 1) { ?>
                                    <i class="bi bi-check-circle-fill icon-ok" title="Autorizado: <?= $fechaAutorizado ?>"></i>
                                <?php } else { ?>
                                    <span class="icon-pendiente" title="Sin autorizar">—</span>
                                <?php } ?>
                            </td>
                            <td class="td-icon" data-sort="<?= ($gasto['RECIBIDO'] == 1) ? 1 : 0 ?>">
                                <?php if ($gasto['RECIBIDO'] == 1) { ?>
                                    <i class="bi bi-check-circle-fill icon-ok" title="Recibido: <?= $fechaRecibido ?>"></i>
                                <?php } else { ?>
                                    <span class="icon-pendiente" title="No recibido">—</span>
                                <?php } ?>
                            </td>
                            <td class="td-icon" data-sort="<?= ($gasto['FACTURA'] == 1) ? 1 : 0 ?>">
                                <input type="checkbox" class="check-tabla checkFactura" onchange="checkFactura(this)" <?= ($gasto['FACTURA'] == 1) ? "checked" : "" ?>>
                            </td>
                            <td class="td-icon" data-sort="<?= ($gasto['CONTROL'] == 1) ? 1 : 0 ?>">
                                <input type="checkbox" class="check-tabla checkControl" onchange="checkControl(this)" <?= ($gasto['CONTROL'] == 1) ? "checked" : "" ?>>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php } else { ?>

        <div class="table-wrapper empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No hay egresos para mostrar</h5>
            <p>No se encontraron egresos de <?= htmlspecialchars($selectSucursal[1] ?? '') ?> entre el <?= date('d/m/Y', strtotime($desde)) ?> y el <?= date('d/m/Y', strtotime($hasta)) ?>. Probá con otro rango de fechas o sucursal.</p>
        </div>

        <?php } ?>
    </div>

    <!-- jQuery (usar versión completa, no slim) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="js/controlEgresosSucursales.js" charset="utf-8"></script>

</body>

</html>
