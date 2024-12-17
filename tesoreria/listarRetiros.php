
<?php
session_start();
require_once 'Class/sucursal.php';

if (!isset($_SESSION['numsuc'])) {
    $_SESSION['numsuc'] = '2';
}

$nroSucurs = $_SESSION['numsuc'];

// Calcular fechas por defecto
$fechaHasta = date('Y-m-d');
$fechaDesde = date('Y-m-d', strtotime('-15 days'));

// Si hay filtros aplicados
if (isset($_POST['filtrar'])) {
    $fechaDesde = $_POST['fechaDesde'];
    $fechaHasta = $_POST['fechaHasta'];
}

$data = new Sucursal();
$guias = $data->listarGuiasRetiro($nroSucurs, $fechaDesde, $fechaHasta);


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Retiros de Sucursal</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css" rel="stylesheet">
        
    <style>
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: none;
            margin-bottom: 1rem;
        }

        .btn-action {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 2px;
    }

    .btn-estado {
        cursor: default;
        pointer-events: none;
    }

    .btn i {
        font-size: 1rem;
    }

        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .filters-card {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .btn-responsive {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .fecha-column {
                min-width: 100px;
            }

            .btn-action {
            width: 28px;
            height: 28px;
            padding: 0.2rem;
            }
            
            .btn i {
                font-size: 0.875rem;
            }
        }

    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Título -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="../../sistemas/index.php" class="btn btn-secondary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Volver al Menú">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 mb-0">
                    <i class="bi bi-clipboard-data me-2"></i>
                    Lista de Retiros de Sucursal
                </h1>
            </div>
            <a href="registrarRetiro.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>
                Nueva Guía
            </a>
        </div>

        <!-- Filtros -->
        <div class="card filters-card mb-4">
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label">Fecha Desde</label>
                        <input type="date" class="form-control" name="fechaDesde" value="<?php echo $fechaDesde; ?>">
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label">Fecha Hasta</label>
                        <input type="date" class="form-control" name="fechaHasta" value="<?php echo $fechaHasta; ?>">
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <button type="submit" name="filtrar" class="btn btn-secondary w-100">
                            <i class="bi bi-filter me-2"></i>
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-body">
                <table id="tablaguias" class="table table-striped dt-responsive nowrap w-100">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Registro</th>
                            <th>Emisor</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guias as $guia): ?>
                            <tr>
                                <td class="fecha-column"><?php echo $guia['FECHA']; ?></td>
                                <td><?php echo $guia['NRO_REGISTRO']; ?></td>
                                <td><?php echo $guia['EMISOR']; ?></td>
                                <td class="text-center">
                                    <?php if ($guia['ESTADO'] == 1): ?>
                                        <button type="button" 
                                                class="btn btn-danger btn-action btn-estado" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                data-bs-title="Borrador">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-success btn-action btn-estado" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                data-bs-title="Enviada">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($guia['ESTADO'] == 1): ?>
                                        <a href="editarRetiro.php?id=<?php echo $guia['NRO_REGISTRO']; ?>" 
                                        class="btn btn-warning btn-action" 
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        data-bs-title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="verRetiro.php?id=<?php echo $guia['NRO_REGISTRO']; ?>" 
                                        class="btn btn-info btn-action" 
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        data-bs-title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
    <script src="js/listarRetiros.js"></script>

</body>
</html>