<?php
    require_once "Class/cuentaContable.php";
    include 'Class/centroCosto.php';
    include 'Class/rubroContable.php';
    include 'Class/gasto.php';
    include 'Class/prorrateo.php';


    $cuenta = new CuentaContable();
    $centroCostos = new CentroCosto();
    $rubroContable = new RubroContable();
    $gasto = new Gasto();
    $prorrateo = new Prorrateo();

    $codigosDeCuenta = $cuenta->traerCuentasContables();
    $codigosDeCuenta = json_decode($codigosDeCuenta);


    $centroCostos = $centroCostos->traerSectoresCentroCostos();


    $todosLosRubros = $rubroContable->traerRubrosContables();
    $todosLosRubros = json_decode($todosLosRubros);

    $RelacionCuentaRubroContable = $gasto->traerRelacionCuentaRubroContable();
    $metodosProrrateo = $prorrateo->traerMetodosProrrateo();
    $arrayMetodosProrrateo = json_decode($metodosProrrateo);

    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';

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
    <title>Gestión de Relaciones</title>
    <link rel="icon" href="../image/icono.jpg?v=2">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <link rel="stylesheet" href="css/control-gastos-modern.css">
    <link rel="stylesheet" href="css/gestion-relaciones-modern.css">
</head>

<body>

    <div id="username" hidden><?= escHtml(isset($_SESSION['username']) ? $_SESSION['username'] : '') ?></div>

    <?php include 'partials/navegacion.php'; ?>

    <header class="grc-header">
        <div>
            <h3 class="grc-titulo"><i class="bi bi-diagram-3"></i> Gestión de Relaciones</h3>
            <p class="grc-subtitulo">Cuenta contable · Sector · Rubro contable · Método de prorrateo</p>
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

    <main class="grc-main">

        <!-- Alta de relación -->
        <section class="grc-card">
            <h6 class="grc-card-titulo"><i class="bi bi-plus-circle"></i> Nueva relación</h6>

            <div class="grc-form-alta">
                <div class="grc-campo">
                    <label for="codCuenta">Cuenta contable</label>
                    <select class="codCuenta" name="codCuenta" id="codCuenta" onchange="traerDescCuenta(this)" data-placeholder="Seleccionar cuenta">
                        <option disabled="disabled" selected></option>
                        <?php foreach ($codigosDeCuenta as $value) { ?>
                            <option value="<?= escHtml($value->COD_CUENTA) ?>" attr-desc-cuenta="<?= escHtml($value->DESC_CUENTA) ?>"><?= escHtml($value->COD_CUENTA . ' - ' . $value->DESC_CUENTA) ?></option>
                        <?php } ?>
                    </select>
                    <div class="grc-desc" id="descCuenta"></div>
                </div>

                <div class="grc-campo">
                    <label for="sector">Sector</label>
                    <select class="sector" name="sector" id="sector" data-placeholder="Seleccionar sector">
                        <option disabled="disabled" selected></option>
                        <?php foreach ($centroCostos as $value) { ?>
                            <option value="<?= escHtml($value['SECTOR']) ?>"><?= escHtml($value['SECTOR']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="grc-campo">
                    <label for="codRubro">Rubro contable</label>
                    <select class="codRubro" name="codRubro" id="codRubro" onchange="traerDescRubro(this)" data-placeholder="Seleccionar rubro">
                        <option disabled="disabled" selected></option>
                        <?php foreach ($todosLosRubros as $value) { ?>
                            <option value="<?= escHtml($value->COD_RUBRO) ?>" attr-desc-rubro="<?= escHtml($value->RUBRO_CONTABLE) ?>"><?= escHtml($value->COD_RUBRO . '-' . $value->RUBRO_CONTABLE) ?></option>
                        <?php } ?>
                    </select>
                    <div class="grc-desc" id="rubroContable"></div>
                </div>

                <div class="grc-campo">
                    <label for="codProrrateo">Método de prorrateo</label>
                    <select class="codProrrateo" name="codProrrateo" id="codProrrateo" onchange="traerDescProrrateo(this)" data-placeholder="Seleccionar prorrateo">
                        <option disabled="disabled" selected></option>
                        <?php foreach ($arrayMetodosProrrateo as $value) { ?>
                            <option value="<?= escHtml($value->COD_PRORRATEO) ?>" attr-desc-prorrateo="<?= escHtml($value->DESC_PRORRATEO) ?>"><?= escHtml($value->COD_PRORRATEO . '-' . $value->DESC_PRORRATEO) ?></option>
                        <?php } ?>
                    </select>
                    <div class="grc-desc" id="descProrrateo"></div>
                </div>

                <div class="grc-acciones-alta">
                    <button type="button" class="btn btn-success" onclick="agregar()"><i class="bi bi-plus-lg"></i> Agregar</button>
                </div>
            </div>
        </section>

        <!-- Relaciones existentes -->
        <section class="grc-card">
            <h6 class="grc-card-titulo">
                <i class="bi bi-list-ul"></i> Relaciones existentes
                <span class="badge"><?= count($RelacionCuentaRubroContable) ?></span>
            </h6>

            <div class="grc-tabla-wrapper">
                <table id="tableData" class="table table-hover table-striped" style="width: 100%" cellspacing="0" data-page-length="100">
                    <thead class="thead-dark">
                        <tr>
                            <th>Cod. Cuenta</th>
                            <th>Desc. Cuenta</th>
                            <th>Sector</th>
                            <th>Cod. Rubro</th>
                            <th>Rubro Contable</th>
                            <th>Cod. Prorrateo</th>
                            <th>Desc. Prorrateo</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($RelacionCuentaRubroContable as $value) { ?>
                            <tr>
                                <td class="grc-codigo"><?= escHtml($value['COD_CUENTA']) ?></td>
                                <td><?= escHtml($value['DESC_CUENTA']) ?></td>
                                <td><span class="grc-sector"><?= escHtml($value['SECTOR']) ?></span></td>
                                <td class="grc-codigo"><?= escHtml($value['COD_RUBRO']) ?></td>
                                <td><?= escHtml($value['RUBRO_CONTABLE']) ?></td>
                                <td class="grc-codigo"><?= escHtml($value['COD_PRORRATEO']) ?></td>
                                <td><?= escHtml($value['DESC_PRORRATEO']) ?></td>
                                <td class="text-center">
                                    <div class="grc-acciones">
                                        <button type="button" class="btn grc-btn-icono grc-btn-editar" onclick="editarRelacion(<?= (int)$value['ID'] ?>, <?= escHtml(json_encode((string)$value['COD_CUENTA'])) ?>, <?= escHtml(json_encode((string)$value['SECTOR'])) ?>, <?= escHtml(json_encode((string)$value['COD_RUBRO'])) ?>, <?= escHtml(json_encode((string)$value['COD_PRORRATEO'])) ?>)" title="Editar"><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="btn grc-btn-icono grc-btn-eliminar" onclick="eliminarRelacion(<?= (int)$value['ID'] ?>)" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- Modal para editar relaciones -->
    <div class="modal fade" id="editarModal" tabindex="-1" role="dialog" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarModalLabel"><i class="bi bi-pencil-square"></i> Editar relación</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idRelacion">
                    <div class="form-row">
                        <div class="form-group col-4">
                            <label for="editCodCuenta">Cód. cuenta</label>
                            <input type="text" class="form-control" id="editCodCuenta" readonly>
                        </div>
                        <div class="form-group col-8">
                            <label for="editSector">Sector</label>
                            <input type="text" class="form-control" id="editSector" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editDescCuenta">Descripción cuenta</label>
                        <input type="text" class="form-control" id="editDescCuenta" readonly>
                    </div>
                    <div class="form-group">
                        <label for="editCodRubro">Rubro contable</label>
                        <select class="form-control editCodRubro" id="editCodRubro" onchange="traerDescRubroEdit(this)">
                            <option disabled="disabled" selected></option>
                            <?php foreach ($todosLosRubros as $value) { ?>
                                <option value="<?= escHtml($value->COD_RUBRO) ?>" attr-desc-rubro="<?= escHtml($value->RUBRO_CONTABLE) ?>"><?= escHtml($value->COD_RUBRO . '-' . $value->RUBRO_CONTABLE) ?></option>
                            <?php } ?>
                        </select>
                        <div id="editRubroContable" class="grc-desc"></div>
                    </div>
                    <div class="form-group">
                        <label for="editCodProrrateo">Método de prorrateo</label>
                        <select class="form-control editCodProrrateo" id="editCodProrrateo" onchange="traerDescProrrateoEdit(this)">
                            <option disabled="disabled" selected></option>
                            <?php foreach ($arrayMetodosProrrateo as $value) { ?>
                                <option value="<?= escHtml($value->COD_PRORRATEO) ?>" attr-desc-prorrateo="<?= escHtml($value->DESC_PRORRATEO) ?>"><?= escHtml($value->COD_PRORRATEO . '-' . $value->DESC_PRORRATEO) ?></option>
                            <?php } ?>
                        </select>
                        <div id="editDescProrrateo" class="grc-desc"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarCambios()"><i class="bi bi-check2"></i> Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/gestionRelacionesCuenta.js"></script>

    <script>
        $(document).ready(function () {
            $('.codCuenta, .sector, .codRubro, .codProrrateo').each(function () {
                $(this).select2({ width: '100%', placeholder: $(this).data('placeholder') });
            });

            $('#tableData').DataTable({
                bInfo: false,
                paging: false,
                aaSorting: [],
                columnDefs: [
                    { targets: -1, sortable: false, searchable: false }
                ],
                oLanguage: {
                    sSearch: 'Búsqueda rápida:',
                    sZeroRecords: 'No se encontraron relaciones'
                },
                dom: 'ft',
                initComplete: function () {
                    // El buscador queda fuera del contenedor con scroll
                    $('#tableData_filter').insertBefore('.grc-tabla-wrapper');
                }
            });
        });
    </script>
</body>

</html>
