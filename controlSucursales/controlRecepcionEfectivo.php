
<?php
require_once "Class/sucursal.php";

$fecha_actual = date("Y-m-d");
$estado = (isset($_GET['selectEstado']) && $_GET['selectEstado'] != "") ? $_GET['selectEstado'] : "%";

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
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }
        
        .filters-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .table-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .form-control {
            border-radius: 5px;
        }
        
        .table {
            font-size: 0.9rem;
        }
        
        .table thead th {
            background-color: var(--secondary-color);
            color: white;
            border: none;
        }
        
        .checkbox-lg {
            width: 1.5rem;
            height: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .filters-section .row {
                flex-direction: column;
            }
            
            .filters-section .col {
                margin-bottom: 10px;
            }
            
            .table-container {
                margin: 10px;
                padding: 10px;
            }
        }

        /* Medium devices (tablets, 768px and up) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .filters-section .row {
                display: flex;
                flex-wrap: wrap;
            }
            .filters-section .col-md-3 {
                flex: 0 0 50%;
                max-width: 50%;
                margin-bottom: 1rem;
            }
        }

        /* Large devices (desktops, 992px and up) */
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .filters-section .col-md-3 {
                flex: 0 0 25%;
                max-width: 25%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="bi bi-cash me-2"></i>
                        Control recepción efectivo de sucursales
                    </h4>
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalAyuda">
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
                            <label for="selectEstado" class="form-label">Estado</label>
                            <select class="form-select" name="selectEstado" id="selectEstado">
                                <option value="%" <?= ($estado == "%") ? "selected" : "" ?>>Todos</option>
                                <option value="0" <?= ($estado == "0") ? "selected" : "" ?>>Pendiente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" id="btnFiltrarControlRecepcion">
                                <i class="bi bi-funnel-fill me-2"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-container">
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
                                <th>RECIBIDO</th>
                                <th>CONTROLADO</th>
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
                                        <td data-toggle="tooltip" data-placement="top" title="USUARIO: <?= $gasto['USUARIO']?>" data-ncomp="<?= $gasto['N_COMP'] ?>"><?= $gasto['N_COMP'] ?></td>
                                        <td><?= number_format($gasto['MONTO'], 0, ',', '.') ?></td>
                                        <td><?= $gasto['DESPACHADO'] == 1 ? ($gasto['FECHA_DESP'])->format("d/m/Y H:i") : '' ?></td>
                                        <td><?= $gasto['PRECINTO'] > 1 ? $gasto['PRECINTO'] : '' ?></td>
                                        <td class="text-center">
                                            <?php if($gasto['RECIBIDO'] == 1): ?>
                                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                            <?php else: ?>
                                                <input type="checkbox" class="form-check-input checkbox-lg" onclick="marcarRecibido(this)">
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($gasto['CTROL_TESORERIA'] == 1): ?>
                                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                            <?php else: ?>
                                                <input type="checkbox" class="form-check-input checkbox-lg" onclick="marcarControlado(this)">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <textarea class="form-control" rows="1" <?= $gasto['OBSERVACIONES'] ? 'disabled' : '' ?>><?= $gasto['OBSERVACIONES'] ?></textarea>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group" aria-label="Acciones">
                                                <?php if($gasto['OBSERVACIONES'] == NULL): ?>
                                                    <button class="btn btn-primary btn-sm" onclick="guardarObservaciones(this)" title="Guardar Observaciones">
                                                        <i class="bi bi-save"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-info btn-sm" onclick="vincularRecibo(this)" title="Vincular Recibo">
                                                    <i class="bi bi-link-45deg"></i>
                                                </button>
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

    <!-- Modal para vincular recibos -->
    <div class="modal fade" id="modalVincularRecibo" tabindex="-1" aria-labelledby="modalVincularReciboLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVincularReciboLabel">Vincular Recibo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Buscar por Nro. Comprobante o Leyenda...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="tablaRecibosVincular" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo Comp.</th>
                                    <th>Comprobante</th>
                                    <th>Monto</th>
                                    <th>Leyenda</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán aquí mediante JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Ayuda -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-labelledby="modalAyudaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAyudaLabel">Ayuda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">General</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="acciones-tab" data-bs-toggle="tab" data-bs-target="#acciones" type="button" role="tab" aria-controls="acciones" aria-selected="false">Acciones</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="accordion mt-3" id="accordionGeneral">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                            ¿Qué muestra esta pantalla?
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionGeneral">
                                        <div class="accordion-body">
                                            Esta pantalla muestra los registros de recepción de efectivo de las sucursales. Puede filtrar los registros por fecha y estado (Pendiente o Todos).
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="acciones" role="tabpanel" aria-labelledby="acciones-tab">
                            <div class="accordion mt-3" id="accordionAcciones">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            Vincular Recibo
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionAcciones">
                                        <div class="accordion-body">
                                            El botón "Vincular" permite asociar un comprobante de la tabla con un recibo de tesorería. Al hacer clic, se abrirá una ventana para seleccionar el recibo a vincular. Los montos deben coincidir para poder realizar la vinculación.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            Guardar Observaciones
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionAcciones">
                                        <div class="accordion-body">
                                            Puede agregar observaciones en la columna "OBSERVACIONES" y luego hacer clic en el botón "Guardar" para almacenarlas.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/v/bs5/dt-1.13.8/r-2.5.0/datatables.min.js"></script>
    <script src="js/controlRecepcion.js"></script>
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