<?php

include 'Class/rubroContable.php';
include 'Class/prorrateo.php';
include 'Class/centroCosto.php';
include 'Class/cuentaContable.php';
include 'Class/gasto.php';

$rubroContable = new RubroContable();
$todosLosRubros = $rubroContable->traerRubrosContables();
$todosLosRubros = json_decode($todosLosRubros);

$metodoProrrateo = new Prorrateo();
$todosLosMetodos = $metodoProrrateo->traerMetodosProrrateo();
$todosLosMetodos = json_decode($todosLosMetodos);

$centroCosto = new CentroCosto();
$todosLosCentrosCosto = $centroCosto->traerCentroCostos();
$todosLosCentrosCosto = json_decode($todosLosCentrosCosto);

$cuentaContable = new CuentaContable();
$todasLasCuentasContables = $cuentaContable->traerCuentasContables();
$todasLasCuentasContables = json_decode($todasLasCuentasContables);

$gastos = new Gasto();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");

$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';

$consultaRealizada = isset($_GET['desde']);
$todosLosGastos = $consultaRealizada ? json_decode($gastos->traerGastos2($desde, $hasta)) : [];

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
    <title>Carga de Gastos</title>
    <link rel="icon" href="../image/icono.jpg?v=2">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <link rel="stylesheet" href="css/control-gastos-modern.css">
    <link rel="stylesheet" href="css/carga-gastos-modern.css">
</head>

<body>

    <?php include 'partials/navegacion.php'; ?>

    <header class="cgc-header">
        <div>
            <h3 class="cgc-titulo"><i class="bi bi-plus-square"></i> Carga de Gastos</h3>
            <p class="cgc-subtitulo">Registro manual de gastos para el Informe Económico</p>
        </div>

        <div class="custom-toggle-container" onclick="cambiarEntornoCustom(this)" title="Cambiar entorno ARG / UY">
            <div class="toggle-flag <?= $checkedValue === 'central' ? 'active' : '' ?>" data-entorno="central">
                <img src="images/bandera_con_sol__55757_std.jpg" alt="ARG">
            </div>
            <div class="toggle-flag <?= $checkedValue === 'uy' ? 'active' : '' ?>" data-entorno="uy">
                <img src="images/UY.png" alt="UY">
            </div>
        </div>
    </header>

    <main class="cgc-main">

        <!-- Alta de gasto (las clases .fecha, .codCentro, .auxiliar, etc. las lee js/carga.js) -->
        <section class="cgc-card">
            <h6 class="cgc-card-titulo"><i class="bi bi-plus-circle"></i> Nuevo gasto</h6>

            <div class="cgc-form-alta">
                <div class="cgc-campo">
                    <label for="fechaGasto">Fecha</label>
                    <input type="date" id="fechaGasto" class="form-control form-control-sm fecha" value="<?= escHtml($hasta) ?>">
                </div>

                <div class="cgc-campo cgc-campo-ancho">
                    <label for="codCentro">Auxiliar</label>
                    <select class="codCentro select-auxiliar" id="codCentro" data-placeholder="Seleccionar auxiliar">
                        <option selected disabled></option>
                        <?php foreach ($todosLosCentrosCosto as $value) { ?>
                            <option value="<?= escHtml($value->COD_AUXILIAR) ?>"><?= escHtml($value->COD_AUXILIAR . '-' . $value->DESC_AUXILIAR) ?></option>
                        <?php } ?>
                    </select>
                    <div class="cgc-datos-auxiliar">
                        <span class="cgc-dato"><small>Auxiliar</small><span class="cgc-valor auxiliar"></span></span>
                        <span class="cgc-dato"><small>Sector</small><span class="cgc-valor sector"></span></span>
                        <span class="cgc-dato"><small>Suc.</small><span class="cgc-valor suc"></span></span>
                    </div>
                </div>

                <div class="cgc-campo cgc-campo-ancho">
                    <label for="codCuenta">Cuenta contable</label>
                    <select class="codCuenta" id="codCuenta" onchange="seleccionarCodRubro()" data-placeholder="Seleccionar cuenta">
                        <option selected disabled></option>
                        <?php foreach ($todasLasCuentasContables as $value) { ?>
                            <option value="<?= escHtml($value->COD_CUENTA) ?>"><?= escHtml($value->COD_CUENTA . '-' . $value->DESC_CUENTA) ?></option>
                        <?php } ?>
                    </select>
                    <div class="cgc-desc cuenta"></div>
                </div>

                <div class="cgc-campo">
                    <label for="importeGasto">Importe</label>
                    <input type="number" id="importeGasto" class="form-control form-control-sm importe" step="0.01" placeholder="0,00">
                </div>

                <div class="cgc-campo">
                    <label for="amortizarGasto" title="Plazo de amortización">Amortizar (meses)</label>
                    <input type="number" id="amortizarGasto" class="form-control form-control-sm amortizar" min="0" placeholder="—">
                </div>

                <div class="cgc-campo cgc-campo-ancho">
                    <label for="leyendaGasto">Leyenda</label>
                    <input type="text" id="leyendaGasto" class="form-control form-control-sm leyenda" placeholder="Detalle del gasto">
                </div>

                <div class="cgc-campo cgc-campo-ancho">
                    <label for="codRubro">Rubro contable</label>
                    <select class="codRubro" id="codRubro" data-placeholder="Seleccionar rubro">
                        <option selected disabled></option>
                        <?php foreach ($todosLosRubros as $value) { ?>
                            <option value="<?= escHtml($value->COD_RUBRO) ?>"><?= escHtml($value->COD_RUBRO . '-' . $value->RUBRO_CONTABLE) ?></option>
                        <?php } ?>
                    </select>
                    <div class="cgc-desc rubro"></div>
                </div>

                <div class="cgc-campo cgc-campo-ancho">
                    <label for="codProrrateo">Método de prorrateo</label>
                    <select class="codProrrateo" id="codProrrateo" data-placeholder="Seleccionar prorrateo">
                        <option selected disabled></option>
                        <?php foreach ($todosLosMetodos as $value) { ?>
                            <option value="<?= escHtml($value->COD_PRORRATEO) ?>"><?= escHtml($value->COD_PRORRATEO . '-' . $value->DESC_PRORRATEO) ?></option>
                        <?php } ?>
                    </select>
                    <div class="cgc-desc descProrrateo"></div>
                </div>

                <div class="cgc-acciones-alta">
                    <button type="button" class="btn btn-success" id="btnaddrow" onclick="guardarGasto();"><i class="bi bi-check2"></i> Guardar gasto</button>
                </div>
            </div>
        </section>

        <!-- Gastos cargados -->
        <section class="cgc-card">
            <div class="cgc-card-cabecera">
                <h6 class="cgc-card-titulo">
                    <i class="bi bi-list-ul"></i> Gastos cargados
                    <?php if ($consultaRealizada) { ?><span class="badge"><?= count($todosLosGastos) ?></span><?php } ?>
                </h6>

                <form method="GET" action="cargaGastos.php" class="cgc-form-busqueda">
                    <div class="cgc-campo">
                        <label for="desde">Desde</label>
                        <input type="date" class="form-control form-control-sm" name="desde" id="desde" value="<?= escHtml($desde) ?>">
                    </div>
                    <div class="cgc-campo">
                        <label for="hasta">Hasta</label>
                        <input type="date" class="form-control form-control-sm" name="hasta" id="hasta" value="<?= escHtml($hasta) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm cgc-btn-buscar"><i class="bi bi-search"></i> Buscar</button>
                </form>
            </div>

            <?php if ($consultaRealizada) { ?>

                <table class="table table-striped table-bordered" id="tablaGastosCargados" style="width: 100%;" data-page-length="100">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cod. Auxiliar</th>
                            <th>Auxiliar</th>
                            <th>Sector</th>
                            <th>Cod. Cuenta</th>
                            <th>Desc. Cuenta</th>
                            <th>Importe</th>
                            <th>Leyenda</th>
                            <th>Cod. Rubro</th>
                            <th>Rubro Contable</th>
                            <th>Cod. Prorrateo</th>
                            <th>Desc. Prorrateo</th>
                            <th>Nro. Suc.</th>
                            <th title="Plazo de amortización">Amortizar</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todosLosGastos as $key) { ?>
                            <tr>
                                <td><?= escHtml($key->ID_CTA_2) ?></td>
                                <td class="cgc-nowrap"><?= escHtml(substr($key->FECHA->date, 0, 10)) ?></td>
                                <td><?= escHtml($key->COD_AUXILIAR) ?></td>
                                <td><?= escHtml($key->DESC_AUXILIAR) ?></td>
                                <td><?= escHtml($key->SECTOR) ?></td>
                                <td><?= escHtml($key->COD_CUENTA) ?></td>
                                <td><?= escHtml($key->DESC_CUENTA) ?></td>
                                <td class="cgc-importe <?= $key->SALDO < 0 ? 'negativo' : '' ?>" data-order="<?= (float)$key->SALDO ?>"><?= number_format($key->SALDO, 2) ?></td>
                                <td><?= escHtml($key->DESC_LEYENDA) ?></td>
                                <td><?= escHtml($key->COD_RUBRO) ?></td>
                                <td><?= escHtml($key->RUBRO_CONTABLE) ?></td>
                                <td><?= escHtml($key->COD_PRORRATEO) ?></td>
                                <td><?= escHtml($key->DESC_PRORRATEO) ?></td>
                                <td><?= escHtml($key->NUM_SUCURSAL) ?></td>
                                <td><?= escHtml($key->AMORTIZAR) ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn cgc-btn-eliminar" data-id="<?= escHtml($key->ID_CTA_2) ?>" onclick="eliminarGasto(this)" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

            <?php } else { ?>

                <div class="cgc-vacio">
                    <i class="bi bi-calendar-range"></i>
                    Elegí un rango de fechas y presioná <strong>Buscar</strong> para ver los gastos cargados.
                </div>

            <?php } ?>
        </section>

    </main>

    <script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/carga.js"></script>

    <script>
        $(document).ready(function() {
            $('.select-auxiliar, .codCuenta, #codRubro, .codProrrateo').each(function () {
                $(this).select2({ width: '100%', placeholder: $(this).data('placeholder') });
            });

            $('#tablaGastosCargados').DataTable({
                aaSorting: [],
                columnDefs: [
                    { targets: -1, sortable: false, searchable: false }
                ],
                oLanguage: {
                    sSearch: 'Filtrar resultados:',
                    sLengthMenu: 'Mostrar _MENU_ registros',
                    sInfo: '_START_ a _END_ de _TOTAL_ registros',
                    sInfoEmpty: 'Sin registros',
                    sInfoFiltered: '(filtrado de _MAX_)',
                    sZeroRecords: 'No hay gastos cargados en el rango',
                    oPaginate: { sPrevious: 'Anterior', sNext: 'Siguiente' }
                },
                initComplete: function () {
                    // La tabla scrollea sola; buscador y paginado quedan fijos
                    $('#tablaGastosCargados').wrap('<div class="cgc-tabla-wrapper"></div>');
                }
            });
        });

        // Sugiere el rubro según la relación cuenta + sector (gestionRelacionesCuenta)
        const seleccionarCodRubro = () => {
            let codCuenta = document.querySelector('.codCuenta').value;
            let sector = document.querySelector('.sector').textContent;

            $.ajax({
                url: 'Controller/consultarCodRubro.php',
                method: 'GET',
                data: { codCuenta: codCuenta, sector: sector },
                success: function(data) {
                    let codRubro = 0;
                    data = JSON.parse(data);

                    if (data != 0) {
                        codRubro = data[0].COD_RUBRO;
                    }

                    if (codRubro != 0) {
                        completarCampoRubro(codRubro);
                    }
                    $('#codRubro').val(codRubro).trigger('change');
                }
            });
        }

        const eliminarGasto = (boton) => {
            let id = boton.getAttribute('data-id');

            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar el gasto ' + id + '?',
                text: 'Esta acción no se puede revertir',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: 'Controller/eliminarGasto.php',
                    method: 'POST',
                    data: { id: id },
                    success: function() {
                        window.location.reload();
                    }
                });
            });
        }
    </script>

</body>

</html>
