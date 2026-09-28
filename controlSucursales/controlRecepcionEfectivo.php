<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$estadosValidos = [
    'todos'             => 'Todos',
    'pendiente_recibir' => 'Pendiente recibir',
    'pendiente_control' => 'Pendiente control',
    'pendiente_cargar'  => 'Pendiente cargar',
    'anulados'          => 'Anulados'
];

// Los filtros viajan por POST y se guardan en sesión (patrón POST-Redirect-GET)
// para que no queden variables en la URL y un F5 no pida reenviar el formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desde'], $_POST['hasta'], $_POST['selectEstado'])) {
    $esFecha = function ($valor) {
        $d = DateTime::createFromFormat('Y-m-d', $valor);
        return $d && $d->format('Y-m-d') === $valor;
    };

    if ($esFecha($_POST['desde']) && $esFecha($_POST['hasta']) && isset($estadosValidos[$_POST['selectEstado']])) {
        $_SESSION['controlRecepcionEfectivo'] = [
            'desde'  => $_POST['desde'],
            'hasta'  => $_POST['hasta'],
            'estado' => $_POST['selectEstado']
        ];
    }

    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

require_once "Class/sucursal.php";

$fecha_actual = date("Y-m-d");
$filtros = isset($_SESSION['controlRecepcionEfectivo']) ? $_SESSION['controlRecepcionEfectivo'] : [];

$desde  = isset($filtros['desde'])  ? $filtros['desde']  : date("Y-m-d", strtotime($fecha_actual . "- 1 week"));
$hasta  = isset($filtros['hasta'])  ? $filtros['hasta']  : date("Y-m-d", strtotime($fecha_actual . "- 1 day"));
$estado = isset($filtros['estado']) ? $filtros['estado'] : "todos";

$sucursal = new Sucursal();
$data = $sucursal->traerDatosControlRecepcion($desde, $hasta, $estado);
if (!is_array($data)) {
    $data = [];
}
$locales = $sucursal->traerLocales();

$nombresSucursales = [];
foreach ((array)$locales as $local) {
    $nombresSucursales[$local['NRO_SUCURSAL']] = $local['DESC_SUCURSAL'];
}

$esAnulados = ($estado == 'anulados');

// Resumen
$cantidad = count($data);
$totalesPorMoneda = [];
$cantidadRecibidos = 0;
$cantidadControlados = 0;
$cantidadCargados = 0;
foreach ($data as $gasto) {
    $moneda = ($gasto['MONEDA'] ?? '') !== '' ? $gasto['MONEDA'] : 'S/M';
    $totalesPorMoneda[$moneda] = ($totalesPorMoneda[$moneda] ?? 0) + (float)$gasto['MONTO'];
    if ($gasto['RECIBIDO'] == 1) $cantidadRecibidos++;
    if ($gasto['CTROL_TESORERIA'] == 1) $cantidadControlados++;
    if ($gasto['VINCULADO'] == 1) $cantidadCargados++;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Recepción Efectivo</title>

    <?php require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php'; ?>

    <!-- Bootstrap 5 (lo requiere el modal de vincular recibo) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos base compartidos con Resumen de Ventas -->
    <link rel="stylesheet" href="css/resumenVentas.css">
    <!-- Layout compacto compartido -->
    <link rel="stylesheet" href="css/pantallaCompacta.css">
    <!-- Estilos propios de la pantalla -->
    <link rel="stylesheet" href="css/controlRecepcionEfectivo.css">
</head>

<body class="pantalla-compacta">
    <div class="container-fluid">
        <div class="modern-card">
            <div class="page-title">
                <a href="http://192.168.0.13:8000/" class="btn-home" title="Volver al menú">
                    <img src="../image/home-button.png" alt="Home">
                </a>
                <i class="bi bi-cash"></i>
                <span>Control Recepción de Efectivo</span>
                <span class="date-range">
                    <?= date('d/m/Y', strtotime($desde)) ?> a <?= date('d/m/Y', strtotime($hasta)) ?> · <?= $estadosValidos[$estado] ?>
                </span>

                <div class="title-actions ms-auto">
                    <button type="button" class="btn-info-toggle" data-bs-toggle="collapse" data-bs-target="#panelInfo" aria-expanded="false" aria-controls="panelInfo">
                        <i class="bi bi-info-circle"></i> ¿Qué muestra esta pantalla?
                    </button>
                </div>
            </div>

            <!-- Panel informativo -->
            <div class="collapse" id="panelInfo">
                <div class="info-panel">
                    <div class="info-grid">
                        <div class="info-block">
                            <h6><i class="bi bi-database"></i> ¿Qué datos trae?</h6>
                            <p>
                                Lista los <strong>retiros de efectivo (RAF)</strong> que las sucursales envían a tesorería central
                                (cuenta de caja <code>100100</code>) entre las fechas elegidas, con el despacho y el precinto de la guía de retiro.
                            </p>
                            <p>
                                El filtro <em>Anulados</em> muestra los RAF anulados en Tango. Esos comprobantes ya están compensados contablemente
                                con su REV: no se pueden vincular ni agregarles observaciones.
                            </p>
                        </div>

                        <div class="info-block">
                            <h6><i class="bi bi-arrow-right-circle"></i> Flujo de estados</h6>
                            <ol class="info-flow">
                                <li><strong>Despachado:</strong> la sucursal envió el efectivo con precinto. Se ve la fecha y hora del despacho.</li>
                                <li><i class="bi bi-box-arrow-in-down"></i> <strong>Recibido:</strong> el efectivo llegó a tesorería.</li>
                                <li><i class="bi bi-check-square"></i> <strong>Controlado:</strong> el monto contado coincide con el declarado. Primero tiene que estar recibido.</li>
                                <li><i class="bi bi-cloud-arrow-up-fill"></i> <strong>Cargado:</strong> el RAF se vinculó con el recibo contable. Primero tiene que estar recibido y controlado.</li>
                            </ol>
                            <p>Recibido y Controlado <strong>no se pueden destildar</strong> una vez marcados.</p>
                        </div>

                        <div class="info-block">
                            <h6><i class="bi bi-hand-index"></i> Acciones</h6>
                            <ul class="info-flow">
                                <li><i class="bi bi-save"></i> <strong>Guardar observación:</strong> una vez guardada no se puede modificar.</li>
                                <li>
                                    <i class="bi bi-link-45deg"></i> <strong>Vincular recibo:</strong> muestra los recibos de caja de los últimos 30 días
                                    (<code>100101</code> ARS, <code>100901</code> USD) que todavía no están vinculados. El monto tiene que coincidir exactamente.
                                </li>
                            </ul>
                        </div>

                        <div class="info-block">
                            <h6><i class="bi bi-diagram-3"></i> Relación con otras pantallas</h6>
                            <p>
                                Esta pantalla sigue el <strong>efectivo</strong> que sale de la sucursal, no los gastos. Comparte la tabla de seguimiento
                                con Control Egresos de Caja, Carga Factura Sucursales y Carga Gastos Tesorería, pero sus registros son de la cuenta de caja
                                y no se mezclan con los gastos (<code>5xxxxx</code>) de esas pantallas.
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
                    <label for="selectEstado">Estado:</label>
                    <select class="form-control" name="selectEstado" id="selectEstado">
                        <?php foreach ($estadosValidos as $valor => $texto) { ?>
                            <option value="<?= $valor ?>" <?= ($estado == $valor) ? "selected" : "" ?>><?= $texto ?></option>
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

        <?php if ($cantidad > 0) { ?>

        <div class="table-wrapper">
            <?php if ($esAnulados) { ?>
                <div class="aviso-anulados">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Movimientos anulados en Tango (CTA28.SITUACION = 'A'). Están compensados contablemente con su REV correspondiente.
                </div>
            <?php } ?>

            <!-- Resumen + buscador -->
            <div class="table-toolbar">
                <div class="stats-row">
                    <div class="stat-chip">
                        <span class="stat-label">Comprobantes</span>
                        <span class="stat-value"><?= $cantidad ?></span>
                    </div>
                    <?php foreach ($totalesPorMoneda as $moneda => $total) { ?>
                        <div class="stat-chip stat-chip-accent">
                            <span class="stat-label">Total <?= htmlspecialchars($moneda) ?></span>
                            <span class="stat-value"><?= number_format($total, 0, ',', '.') ?></span>
                        </div>
                    <?php } ?>
                    <?php if (!$esAnulados) { ?>
                        <div class="stat-chip">
                            <span class="stat-label">Recibidos</span>
                            <span class="stat-value"><span id="statRecibidos"><?= $cantidadRecibidos ?></span> / <?= $cantidad ?></span>
                        </div>
                        <div class="stat-chip">
                            <span class="stat-label">Controlados</span>
                            <span class="stat-value"><span id="statControlados"><?= $cantidadControlados ?></span> / <?= $cantidad ?></span>
                        </div>
                        <div class="stat-chip">
                            <span class="stat-label">Cargados</span>
                            <span class="stat-value"><span id="statCargados"><?= $cantidadCargados ?></span> / <?= $cantidad ?></span>
                        </div>
                    <?php } ?>
                </div>
                <div class="table-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="buscarTabla" placeholder="Buscar sucursal, comprobante, precinto...">
                </div>
            </div>

            <table class="display" id="tablaControlRecepcion">
                <thead>
                    <tr>
                        <th>FECHA RAF</th>
                        <th>SUC.</th>
                        <th>SUCURSAL</th>
                        <th>TIPO</th>
                        <th>COMPROBANTE</th>
                        <th class="th-num">MONTO</th>
                        <th>MONEDA</th>
                        <th>DESPACHADO</th>
                        <th>PRECINTO</th>
                        <th class="th-icon" title="Recibido"><i class="bi bi-box-arrow-in-down"></i></th>
                        <th class="th-icon" title="Controlado"><i class="bi bi-check-square"></i></th>
                        <th class="th-icon" title="Cargado (vinculado a recibo)"><i class="bi bi-cloud-arrow-up-fill"></i></th>
                        <th class="no-sort">OBSERVACIONES</th>
                        <th class="th-icon no-sort">ACCIONES</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($data as $gasto) {
                        $nroSucursal = $gasto['NRO_SUCURS'];
                        $monto = (float)$gasto['MONTO'];
                        $montoFormateado = number_format($monto, 0, ',', '.');
                        $moneda = $gasto['MONEDA'] ?? '';
                        $recibido = ($gasto['RECIBIDO'] == 1);
                        $controlado = ($gasto['CTROL_TESORERIA'] == 1);
                        $vinculado = ($gasto['VINCULADO'] == 1);
                        $tieneObservacion = !empty($gasto['OBSERVACIONES']);
                        $despachado = ($gasto['DESPACHADO'] == 1 && $gasto['FECHA_DESP'] != null);
                    ?>
                        <tr class="<?= $vinculado ? 'row-completa' : '' ?>"
                            data-fecha="<?= $gasto['FECHA']->format("d/m/Y") ?>"
                            data-fecha-iso="<?= $gasto['FECHA']->format("Y-m-d") ?>"
                            data-sucursal="<?= htmlspecialchars(trim($nroSucursal)) ?>"
                            data-desc-sucursal="<?= htmlspecialchars($nombresSucursales[$nroSucursal] ?? '') ?>"
                            data-tipo="<?= htmlspecialchars(trim($gasto['COD_COMP'])) ?>"
                            data-ncomp="<?= htmlspecialchars($gasto['N_COMP']) ?>"
                            data-monto="<?= str_replace('.', '', $montoFormateado) ?>"
                            data-monto-formateado="<?= $montoFormateado ?>"
                            data-cod-cuenta="<?= htmlspecialchars($gasto['COD_CTA']) ?>"
                            data-desc-cuenta="<?= htmlspecialchars($gasto['DESC_CUENTA']) ?>">

                            <td class="td-fecha" data-sort="<?= $gasto['FECHA']->format("Y-m-d") ?>"><?= $gasto['FECHA']->format("d/m/Y") ?></td>
                            <td><?= $nroSucursal ?></td>
                            <td><?= htmlspecialchars($nombresSucursales[$nroSucursal] ?? '') ?></td>
                            <td><?= $gasto['COD_COMP'] ?></td>
                            <td class="td-comprobante" title="Usuario: <?= htmlspecialchars($gasto['USUARIO'] ?? '') ?>">
                                <?= $gasto['N_COMP'] ?>
                                <?php if ($esAnulados) { ?>
                                    <span class="badge-anulado">Anulado</span>
                                <?php } ?>
                            </td>
                            <td class="td-num" data-sort="<?= $monto ?>"><?= $montoFormateado ?></td>
                            <td>
                                <span class="badge-moneda <?= ($moneda === 'USD') ? 'moneda-usd' : '' ?>"><?= htmlspecialchars($moneda) ?></span>
                            </td>
                            <td class="td-fecha" data-sort="<?= $despachado ? $gasto['FECHA_DESP']->format("Y-m-d H:i") : '' ?>">
                                <?= $despachado ? $gasto['FECHA_DESP']->format("d/m/Y H:i") : '<span class="icon-pendiente">—</span>' ?>
                            </td>
                            <td><?= $gasto['PRECINTO'] > 1 ? $gasto['PRECINTO'] : '' ?></td>
                            <td class="td-icon col-recibido" data-sort="<?= $recibido ? 1 : 0 ?>">
                                <?php if ($recibido) { ?>
                                    <i class="bi bi-check-circle-fill icon-ok"></i>
                                <?php } else { ?>
                                    <input type="checkbox" class="check-tabla" onclick="marcarRecibido(this)" title="Marcar como recibido">
                                <?php } ?>
                            </td>
                            <td class="td-icon col-controlado" data-sort="<?= $controlado ? 1 : 0 ?>">
                                <?php if ($controlado) { ?>
                                    <i class="bi bi-check-circle-fill icon-ok"></i>
                                <?php } else { ?>
                                    <input type="checkbox" class="check-tabla" onclick="marcarControlado(this)" title="Marcar como controlado">
                                <?php } ?>
                            </td>
                            <td class="td-icon col-cargado" data-sort="<?= $vinculado ? 1 : 0 ?>">
                                <?php if ($vinculado) { ?>
                                    <i class="bi bi-check-circle-fill icon-ok"></i>
                                <?php } ?>
                            </td>
                            <td class="td-observaciones">
                                <textarea class="obs-input" rows="1" placeholder="<?= ($tieneObservacion || $esAnulados) ? '' : 'Agregar observación...' ?>" <?= ($tieneObservacion || $esAnulados) ? 'disabled' : '' ?>><?= htmlspecialchars((string)$gasto['OBSERVACIONES']) ?></textarea>
                            </td>
                            <td class="td-icon">
                                <?php if (!$esAnulados) { ?>
                                    <div class="acciones">
                                        <?php if (!$tieneObservacion) { ?>
                                            <button type="button" class="btn-accion btn-guardar" onclick="guardarObservaciones(this)" title="Guardar observación">
                                                <i class="bi bi-save"></i>
                                            </button>
                                        <?php } ?>
                                        <?php if (!$vinculado) { ?>
                                            <button type="button" class="btn-accion btn-vincular" onclick="vincularRecibo(this)" title="Vincular recibo">
                                                <i class="bi bi-link-45deg"></i>
                                            </button>
                                        <?php } ?>
                                    </div>
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
            <h5>No hay retiros de efectivo para mostrar</h5>
            <p>No se encontraron RAF <?= $estado != 'todos' ? 'en estado "' . $estadosValidos[$estado] . '" ' : '' ?>entre el <?= date('d/m/Y', strtotime($desde)) ?> y el <?= date('d/m/Y', strtotime($hasta)) ?>. Probá con otro rango de fechas o estado.</p>
        </div>

        <?php } ?>
    </div>

    <?php include_once 'components/controlRecepcion_modalVincular.php'; ?>

    <!-- jQuery (usar versión completa, no slim) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="js/controlRecepcion.js" charset="utf-8"></script>
</body>

</html>
