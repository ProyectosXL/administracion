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
            $_SESSION['cargaFacturaSucursales'] = [
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
    $filtros = isset($_SESSION['cargaFacturaSucursales']) ? $_SESSION['cargaFacturaSucursales'] : [];

    $desde = isset($filtros['desde']) ? $filtros['desde'] : date("Y-m-d", strtotime($fecha_actual . "- 1 week"));
    $hasta = isset($filtros['hasta']) ? $filtros['hasta'] : date("Y-m-d", strtotime($fecha_actual . "- 1 day"));
    $selectSucursal = explode("-", isset($filtros['selectSucursal']) ? $filtros['selectSucursal'] : '2-UNICENTER', 2);

    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';

    $sucursal = new Sucursal();
    $todosLosLocales = $sucursal->traerLocales(true);

    // Solo los gastos marcados con factura en Control Egresos de Caja
    $data = $sucursal->traerGastosCajaSucursales($desde, $hasta, $selectSucursal[0], true);
    if (!is_array($data)) {
        $data = [];
    }

    // Resumen
    $cantidadComprobantes = count($data);
    $totalMonto = 0;
    $cantidadContabilizadas = 0;
    foreach ($data as $gasto) {
        $totalMonto += (float)$gasto['MONTO'];
        if ($gasto['CONTABILIZADA'] == 1) $cantidadContabilizadas++;
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
    <title>Carga Factura Sucursales</title>

    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Select2 y Fancybox (visor de comprobantes) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />

    <!-- Estilos base compartidos con Resumen de Ventas -->
    <link rel="stylesheet" href="css/resumenVentas.css">
    <!-- Layout compacto compartido -->
    <link rel="stylesheet" href="css/pantallaCompacta.css">
    <!-- Estilos propios de la pantalla -->
    <link rel="stylesheet" href="css/cargaFacturaSucursales.css">
</head>

<body class="pantalla-compacta">
    <div class="container-fluid">
        <div class="modern-card">
            <div class="page-title">
                <a href="http://192.168.0.13:8000/" class="btn-home" title="Volver al menú">
                    <img src="../image/home-button.png" alt="Home">
                </a>
                <i class="bi bi-receipt"></i>
                <span>Carga Factura Sucursales</span>
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
                                Lista los <strong>gastos de caja de una sucursal que tienen factura</strong> (llevan IVA) entre las fechas elegidas.
                                Son los mismos egresos de <em>Control Egresos de Caja</em> (cuentas <code>5xxxxx</code>), pero solo los que
                                tienen tildada la columna <strong>Factura</strong> en esa pantalla.
                            </p>
                            <p>
                                En Argentina los datos se leen directo de la base de la sucursal y se completan con el histórico ya replicado en central.
                            </p>
                        </div>

                        <div class="info-block">
                            <h6><i class="bi bi-check2-square"></i> ¿Qué significa "Contabilizada"?</h6>
                            <p>
                                Al tildarla se registra que la factura del gasto <strong>ya se cargó en contabilidad</strong>. Una vez contabilizada
                                se muestra con el ícono <i class="bi bi-check-circle-fill icon-ok"></i> y ya no se puede destildar.
                            </p>
                            <p>
                                <i class="bi bi-eye"></i> <strong>Ver:</strong> abre las fotos del comprobante que subió la sucursal
                                (se pueden rotar desde el visor).
                            </p>
                        </div>

                        <div class="info-block info-block-wide">
                            <h6><i class="bi bi-diagram-3"></i> Relación con otras pantallas de Control Sucursales</h6>
                            <ol class="info-flow">
                                <li><strong>Control Egresos de Caja:</strong> se controla cada comprobante y se indica si tiene factura (lleva IVA).</li>
                                <li>
                                    Según esa marca, el gasto sigue uno de dos caminos:
                                    <ul>
                                        <li><strong>Con factura (con IVA)</strong> → <strong>esta pantalla</strong>, donde se contabiliza comprobante por comprobante.</li>
                                        <li><strong>Sin factura (sin IVA)</strong> → <strong>Carga Gastos Tesorería</strong>, donde se carga el total mensual por sucursal y cuenta.</li>
                                    </ul>
                                </li>
                            </ol>
                            <p class="info-note">
                                <i class="bi bi-lightbulb"></i>
                                Si un gasto con factura no aparece acá, revisá que tenga tildada la columna Factura en Control Egresos de Caja.
                            </p>
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

                <div>
                    <label for="selectSucursal">Sucursal:</label>
                    <select name="selectSucursal" id="selectSucursal" class="form-control">
                        <?php foreach ((array)$todosLosLocales as $value) { ?>
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
                    <div class="stat-chip stat-chip-success">
                        <span class="stat-label">Contabilizadas</span>
                        <span class="stat-value"><span id="statContabilizadas"><?= $cantidadContabilizadas ?></span> / <?= $cantidadComprobantes ?></span>
                    </div>
                </div>
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="searchInput" placeholder="Buscar comprobante, cuenta, leyenda...">
                </div>
            </div>

            <table class="display" id="tablaFacturas">
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
                        <th class="th-icon">CONTABILIZADA</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data as $gasto) {
                        $monto = (float)$gasto['MONTO'];
                        $contabilizada = ($gasto['CONTABILIZADA'] == 1);
                    ?>
                        <tr class="<?= $contabilizada ? 'row-contabilizada' : '' ?>"
                            data-fecha="<?= $gasto['FECHA']->format("Y-m-d") ?>"
                            data-sucursal="<?= htmlspecialchars($gasto['NRO_SUCURS']) ?>"
                            data-tipo="<?= htmlspecialchars($gasto['COD_COMP']) ?>"
                            data-comprobante="<?= htmlspecialchars($gasto['N_COMP']) ?>"
                            data-cod-cuenta="<?= htmlspecialchars($gasto['COD_CTA']) ?>"
                            data-monto="<?= $montoParaEnviar($monto) ?>">

                            <td class="td-fecha" data-sort="<?= $gasto['FECHA']->format("Y-m-d") ?>"><?= $gasto['FECHA']->format("d/m/Y") ?></td>
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
                            <td class="td-icon" data-sort="<?= $contabilizada ? 1 : 0 ?>">
                                <?php if ($contabilizada) { ?>
                                    <i class="bi bi-check-circle-fill icon-ok icono-contabilizada" title="Contabilizada"></i>
                                <?php } else { ?>
                                    <input type="checkbox" class="check-tabla checkContabilizar" onchange="checkContabilizar(this)">
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php } else { ?>

        <div class="table-wrapper empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No hay gastos con factura para mostrar</h5>
            <p>No se encontraron gastos con factura de <?= htmlspecialchars($selectSucursal[1] ?? '') ?> entre el <?= date('d/m/Y', strtotime($desde)) ?> y el <?= date('d/m/Y', strtotime($hasta)) ?>. Probá con otro rango de fechas o sucursal.</p>
        </div>

        <?php } ?>
    </div>

    <!-- jQuery (usar versión completa, no slim) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>

    <script src="js/cargaFacturaSucursales.js" charset="utf-8"></script>

</body>

</html>
