
<?php
require_once "Class/sucursal.php";

$fecha_actual = date("Y-m-d");
$estado = (isset($_GET['selectEstado']) && $_GET['selectEstado'] != "") ? $_GET['selectEstado'] : "todos";

if(isset($_GET['desde']) && $_GET['desde'] != "" ){
    $desde = $_GET['desde'];
}else{
    $desde = date("Y-m-d",strtotime($fecha_actual."- 1 week"));
}

if(isset($_GET['hasta']) && $_GET['hasta'] != "" ){
    $hasta = $_GET['hasta'];
}else{
    $hasta = date("Y-m-d",strtotime($fecha_actual."- 1 day"));
}

$sucursal = new Sucursal();
$data = $sucursal->traerDatosControlRecepcion($desde, $hasta, $estado);
$locales = $sucursal->traerLocales();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control recepción efectivo de sucursales</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/v/bs5/dt-1.13.8/r-2.5.0/datatables.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/controlRecepcionEfectivo.css" class="rel">

</head>
<body>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center p-2">
                    <h4 class="mb-0">
                        <i class="bi bi-cash me-2"></i>
                        Control recepción efectivo de sucursales
                    </h4>
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#controlRecepcion_modalAyuda">
                        <i class="bi bi-question-circle-fill me-1"></i> Ayuda
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <div class="filters-section">
                    <form id="filterForm" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="desde" class="form-label">Desde</label>
                            <input type="date" class="form-control" id="desde" name="desde" value="<?= $desde ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="hasta" class="form-label">Hasta</label>
                            <input type="date" class="form-control" id="hasta" name="hasta" value="<?= $hasta ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="selectEstado" id="selectEstado">
                                <option value="todos" <?= ($estado == "todos") ? "selected" : "" ?>>Todos</option>
                                <option value="pendiente_recibir" <?= ($estado == "pendiente_recibir") ? "selected" : "" ?>>Pendiente Recibir</option>
                                <option value="pendiente_control" <?= ($estado == "pendiente_control") ? "selected" : "" ?>>Pendiente Control</option>
                                <option value="pendiente_cargar" <?= ($estado == "pendiente_cargar") ? "selected" : "" ?>>Pendiente Cargar</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" id="btnFiltrarControlRecepcion">
                                <i class="bi bi-funnel-fill me-2"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-container mt-4">
                    <table class="table table-striped table-hover" id="tablaControlRecepcion">
                        <thead>
                            <tr>
                                <th>FECHA RAF</th>
                                <th>NRO.SUCURSAL</th>
                                <th>DESC.SUCURSAL</th>
                                <th>TIPO COMP.</th>
                                <th>COMPROBANTE</th>
                                <th>MONTO</th>
                                <th>DESPACHADO</th>
                                <th>PRECINTO</th>
                                <th data-toggle="tooltip" data-placement="top" title="Recibido"><i class="bi bi-box-arrow-in-down icon"></i></th>
                                <th data-toggle="tooltip" data-placement="top" title="Controlado"><i class="bi bi-check-square icon"></i></th>
                                <th data-toggle="tooltip" data-placement="top" title="Cargado"><i class="bi bi-cloud-arrow-up-fill icon"></i></th>
                                <th>OBSERVACIONES</th>
                                <th>ACCIONES</th>
                                <th hidden>COD_CUENTA</th>  <!-- Columna oculta -->
                                <th hidden>DESC_CUENTA</th> <!-- Columna oculta -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($data != null): ?>
                                <?php foreach ($data as $gasto): ?>
                                    <?php
                                    $sucursal = "";
                                    foreach ($locales as $local) {
                                        if ($gasto['NRO_SUCURS'] == $local['NRO_SUCURSAL']) {
                                            $sucursal = $local['DESC_SUCURSAL'];
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $gasto['FECHA']->format("d/m/Y") ?></td>
                                        <td><?= $gasto['NRO_SUCURS'] ?></td>
                                        <td><?= $sucursal ?></td>
                                        <td><?= $gasto['COD_COMP'] ?></td>
                                        <td data-toggle="tooltip" data-placement="top" title="USUARIO: <?= $gasto['USUARIO']?>" data-ncomp="<?= $gasto['N_COMP'] ?>" data-ncomp-original="<?= htmlspecialchars($gasto['N_COMP']) ?>"><?= $gasto['N_COMP'] ?></td>
                                        <td><?= number_format($gasto['MONTO'], 0, ',', '.') ?></td>
                                        <td><?= $gasto['DESPACHADO'] == 1 ? ($gasto['FECHA_DESP'])->format("d/m/Y H:i") : '' ?></td>
                                        <td><?= $gasto['PRECINTO'] > 1 ? $gasto['PRECINTO'] : '' ?></td>
                                        <td class="text-center">
                                            <?php if($gasto['RECIBIDO'] == '1' || $gasto['RECIBIDO'] === 1): ?>
                                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                            <?php else: ?>
                                                <input type="checkbox" class="form-check-input checkbox-lg" onclick="marcarRecibido(this)">
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($gasto['CTROL_TESORERIA'] == '1' || $gasto['CTROL_TESORERIA'] === 1): ?>
                                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                            <?php else: ?>
                                                <input type="checkbox" class="form-check-input checkbox-lg" onclick="marcarControlado(this)">
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($gasto['VINCULADO'] == 1): ?>
                                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <textarea class="form-control" rows="1" <?= $gasto['OBSERVACIONES'] ? 'disabled' : '' ?>><?= $gasto['OBSERVACIONES'] ?></textarea>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group" aria-label="Acciones">
                                                <?php if($gasto['OBSERVACIONES'] == NULL): ?>
                                                    <button class="btn btn-primary btn-sm" data-toggle="tooltip" data-placement="top" onclick="guardarObservaciones(this)" title="Guardar Observaciones">
                                                        <i class="bi bi-save"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if($gasto['VINCULADO'] == 0): ?>
                                                <button class="btn btn-info btn-sm" data-toggle="tooltip" data-placement="top" onclick="vincularRecibo(this)" title="Vincular Recibo">
                                                    <i class="bi bi-link-45deg"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td hidden><?= $gasto['COD_CTA'] ?></td>
                                        <td hidden><?= $gasto['DESC_CUENTA'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/v/bs5/dt-1.13.8/r-2.5.0/datatables.min.js"></script>
    <script src="js/controlRecepcion.js"></script>

    <?php include_once 'components/controlRecepcion_modalAyuda.php'; ?>
    <?php include_once 'components/controlRecepcion_modalVincular.php'; ?>

    <script>
        $(document).ready(function() {
            $('#tablaControlRecepcion').DataTable({
                responsive: true,
                language: {
                    lengthMenu: "Mostrar _MENU_ registros por página",
                    zeroRecords: "No se encontraron registros",
                    info: "Mostrando página _PAGE_ de _PAGES_",
                    infoEmpty: "No hay registros disponibles",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    search: "Buscar:",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                order: [],
                columnDefs: [{
                    targets: '_all',
                    className: 'text-center'
                }]
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>