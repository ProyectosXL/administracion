<?php

include 'Class/gasto.php';
include 'Class/rubroContable.php';
include 'Class/prorrateo.php';
include 'Class/cuentaContable.php';

$gastos = new Gasto();
$rubroContable = new RubroContable();
$todosLosRubros = $rubroContable->traerRubrosContables();
$todosLosRubros = json_decode($todosLosRubros);

$metodoProrrateo = new Prorrateo();
$todosLosMetodos = $metodoProrrateo->traerMetodosProrrateo();
$todosLosMetodos = json_decode($todosLosMetodos);

$codRubro = isset($_GET['codRubro']) ? $_GET['codRubro'] : '%' ;
$codCuenta = isset($_GET['codCuenta']) ?  $_GET['codCuenta'] : '%';

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");
$columna = isset($_GET['textBox']) ? $_GET['textBox'] : null;

$cuenta = new CuentaContable ();
$cuentas = $cuenta->traerCodCuentaAll();

$cuentas = json_decode($cuentas, true);

$data = [];

foreach ($cuentas as $key => $value) {

    $data[$key]['COD_CUENTA'] = $value['COD_CUENTA'];
    $data[$key]['DESC_CUENTA'] = $value['DESC_CUENTA'];

}

$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';

$consultaRealizada = isset($_GET['desde']);
$todosLosGastos = $consultaRealizada ? json_decode($gastos->traerGastosConsulta($desde, $hasta, $codRubro, $columna, $codCuenta)) : [];

// Escapa texto para HTML / atributos
function escHtml($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Gastos</title>
    <link rel="icon" href="../image/icono.jpg?v=2">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <link rel="stylesheet" href="css/control-gastos-modern.css">
    <link rel="stylesheet" href="css/consulta-gastos-modern.css">
</head>

<body>

    <?php include 'partials/navegacion.php'; ?>

    <div class="alert alert-secondary">
        <div class="row">

            <div class="cns-cabecera">
                <div id="titlePrincipal">
                    <h3 class="title"><i class="bi bi-search"></i> Consulta de Gastos</h3>
                </div>

                <div class="custom-toggle-container" onclick="cambiarEntornoCustom(this)" title="Cambiar entorno ARG / UY">
                    <div class="toggle-flag <?= $checkedValue === 'central' ? 'active' : '' ?>" data-entorno="central">
                        <img src="images/bandera_con_sol__55757_std.jpg" alt="ARG">
                    </div>
                    <div class="toggle-flag <?= $checkedValue === 'uy' ? 'active' : '' ?>" data-entorno="uy">
                        <img src="images/UY.png" alt="UY">
                    </div>
                </div>
            </div>

            <div class="cgm-filtros-wrapper">
                <form method="GET" action="consultaGastos.php" id="formFiltros">
                    <div class="cgm-fila-principal">
                        <div class="cns-campo-fecha">
                            <label for="desde">Desde:</label>
                            <input type="date" class="form-control form-control-sm" name="desde" id="desde" value="<?= escHtml($desde) ?>">
                        </div>

                        <div class="cns-campo-fecha">
                            <label for="hasta">Hasta:</label>
                            <input type="date" class="form-control form-control-sm" name="hasta" id="hasta" value="<?= escHtml($hasta) ?>">
                        </div>

                        <div class="cns-campo-cuenta">
                            <label for="codCuenta">Código de cuenta:</label>
                            <select class="form-control form-control-sm codCuenta" name="codCuenta" id="codCuenta">
                                <option value="%" <?= $codCuenta == '%' ? 'selected' : '' ?>>Todos</option>
                                <?php foreach ($data as $cuenta) { ?>
                                    <option value="<?= escHtml($cuenta['COD_CUENTA']) ?>" <?= $codCuenta == $cuenta['COD_CUENTA'] ? 'selected' : '' ?>><?= escHtml($cuenta['COD_CUENTA'] . ' - ' . $cuenta['DESC_CUENTA']) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="cns-campo-rubro">
                            <label for="codRubro">Rubro:</label>
                            <select class="form-control form-control-sm codRubro" name="codRubro" id="codRubro">
                                <option value="%" <?= $codRubro == '%' ? 'selected' : '' ?>>Todos</option>
                                <?php foreach ($todosLosRubros as $value) { ?>
                                    <option value="<?= escHtml($value->COD_RUBRO) ?>" <?= $codRubro == $value->COD_RUBRO ? 'selected' : '' ?>><?= escHtml($value->COD_RUBRO . '-' . $value->RUBRO_CONTABLE) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="cns-campo-texto">
                            <label for="textBox">Búsqueda rápida:</label>
                            <input type="text" id="textBox" name="textBox" value="<?= escHtml($columna) ?>" placeholder="Leyenda, razón social o nro. comp." class="form-control form-control-sm">
                        </div>

                        <div>
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary" id="search"><i class="bi bi-funnel-fill"></i> Consultar</button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <?php if ($consultaRealizada) { ?>

        <section class="cns-resultados">
            <h6 class="cns-resultados-titulo">
                <i class="bi bi-list-ul"></i> Gastos
                <span class="badge"><?= count($todosLosGastos) ?></span>
            </h6>

            <table class="table table-striped table-bordered" id="myTable" style="width: 100%;" cellspacing="0" data-page-length="100">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha / Periodo</th>
                        <th>Auxiliar</th>
                        <th>Sector</th>
                        <th>Cod. Cuenta</th>
                        <th>Desc. Cuenta</th>
                        <th>Saldo</th>
                        <th>Leyenda</th>
                        <th>Tipo Comp.</th>
                        <th>Razón Social</th>
                        <th>Nro. Comp.</th>
                        <th>Cod. Rubro</th>
                        <th>Rubro Contable</th>
                        <th>Cod. Prorrateo</th>
                        <th>Desc. Prorrateo</th>
                        <th>Módulo</th>
                        <th>Nro. Suc.</th>
                        <th>ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todosLosGastos as $key) { ?>
                        <tr>
                            <td class="cns-fecha" data-order="<?= escHtml(substr($key->FECHA->date, 0, 10)) ?>"><?= escHtml(substr($key->FECHA->date, 0, 10)) ?><span class="cns-periodo"><?= escHtml($key->PERIODO) ?></span></td>
                            <td><?= escHtml($key->DESC_AUXILIAR) ?></td>
                            <td><?= escHtml($key->SECTOR) ?></td>
                            <td><?= escHtml($key->COD_CUENTA) ?></td>
                            <td class="cns-desc-cuenta"><?= escHtml($key->DESC_CUENTA) ?></td>
                            <td class="cns-saldo <?= $key->SALDO < 0 ? 'negativo' : '' ?>" data-order="<?= (float)$key->SALDO ?>"><?= number_format($key->SALDO, 2) ?></td>
                            <td><?= escHtml($key->DESC_LEYENDA) ?></td>
                            <td><?= escHtml($key->T_COMP) ?></td>
                            <td><?= escHtml($key->RAZON_SOCIAL) ?></td>
                            <td><?= escHtml($key->N_COMP) ?></td>
                            <td>
                                <select class="codRubroC" title="Cambiar rubro">
                                    <option selected disabled><?= escHtml($key->COD_RUBRO) ?></option>
                                    <?php foreach ($todosLosRubros as $value) { ?>
                                        <option value="<?= escHtml($value->COD_RUBRO) ?>"><?= escHtml($value->COD_RUBRO . '-' . $value->RUBRO_CONTABLE) ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                            <td><?= escHtml($key->RUBRO_CONTABLE) ?></td>
                            <td>
                                <select class="codProrrateoC" title="Cambiar prorrateo">
                                    <option selected disabled><?= escHtml($key->COD_PRORRATEO) ?></option>
                                    <?php foreach ($todosLosMetodos as $value) { ?>
                                        <option value="<?= escHtml($value->COD_PRORRATEO) ?>"><?= escHtml($value->COD_PRORRATEO . '-' . $value->DESC_PRORRATEO) ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                            <td><?= escHtml($key->DESC_PRORRATEO) ?></td>
                            <td><?= escHtml($key->MODULO) ?></td>
                            <td><?= escHtml($key->NUM_SUCURSAL) ?></td>
                            <td><?= escHtml($key->ID) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>

    <?php } else { ?>

        <div class="cns-vacio">
            <i class="bi bi-funnel"></i>
            Elegí el rango de fechas y los filtros, y presioná <strong>Consultar</strong>.
        </div>

    <?php } ?>


    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/consultaGastos.js"></script>

    <script>
        $(document).ready(function() {
            $('.codCuenta, .codRubro').select2({ width: '100%' });

            $('#myTable').DataTable({
                aaSorting: [],
                columnDefs: [
                    { targets: [10, 12], sortable: false }
                ],
                oLanguage: {
                    sSearch: 'Filtrar resultados:',
                    sLengthMenu: 'Mostrar _MENU_ registros',
                    sInfo: '_START_ a _END_ de _TOTAL_ registros',
                    sInfoEmpty: 'Sin registros',
                    sInfoFiltered: '(filtrado de _MAX_)',
                    sZeroRecords: 'No se encontraron gastos',
                    oPaginate: { sPrevious: 'Anterior', sNext: 'Siguiente' }
                },
                initComplete: function () {
                    // La tabla scrollea sola; buscador y paginado quedan fijos
                    $('#myTable').wrap('<div class="cns-tabla-wrapper"></div>');
                }
            });
        });
    </script>

</body>

</html>
