
<?php
require_once 'Class/gasto.php';

$gasto = new Gasto();

// Establecer fechas por defecto
$desde = isset($_GET['desde']) && $_GET['desde'] != "" ? $_GET['desde'] : date('Y-m-d', strtotime("-30 days"));
$hasta = isset($_GET['hasta']) && $_GET['hasta'] != "" ? $_GET['hasta'] : date('Y-m-d');

// Obtener los gastos filtrados
$gastos = $gasto->traerGastos($desde, $hasta);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egresos de caja Tesorería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container-fluid {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }
        h3 {
            color: #6c757d;
        }
        .dataTables_wrapper {
            margin-top: 20px;
        }
        .btn-icon {
            width: 30px;
            height: 30px;
            padding: 6px 0;
            border-radius: 4px;
            text-align: center;
            font-size: 12px;
            line-height: 1.428571429;
            margin: 2px;
        }
        .table {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h3 class="mb-4"><i class="fas fa-cash-register me-2"></i>Egresos de caja Tesorería</h3>
        <form id="filtroForm" method="GET" action="">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="desde">Desde:</label>
                    <input type="date" id="desde" name="desde" class="form-control" value="<?php echo $desde; ?>">
                </div>
                <div class="col-md-3">
                    <label for="hasta">Hasta:</label>
                    <input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo $hasta; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" id="filtrar" class="btn btn-primary"><i class="fas fa-filter me-2"></i>Filtrar</button>
                </div>
            </div>
        </form>
        <table id="gastosTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>FECHA</th>
                    <th>COD_COMP</th>
                    <th>N_COMP</th>
                    <th>COD_CTA</th>
                    <th>DESC_CUENTA</th>
                    <th>MONTO</th>
                    <th>USUARIO</th>
                    <th>LEYENDA</th>
                    <th>IMAGENES</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gastos as $gasto): ?>
                <tr>
                    <td><?php echo $gasto['FECHA']->format("Y-m-d"); ?></td>
                    <td><?php echo $gasto['COD_COMP']; ?></td>
                    <td><?php echo $gasto['N_COMP']; ?></td>
                    <td><?php echo $gasto['COD_CTA']; ?></td>
                    <td><?php echo $gasto['DESC_CUENTA']; ?></td>
                    <td>$<?php echo number_format($gasto['MONTO'], 0, ',', '.'); ?></td>
                    <td><?php echo $gasto['USUARIO']; ?></td>
                    <td><?php echo $gasto['LEYENDA']; ?></td>
                    <td>
                        <button class="btn btn-primary btn-icon"><i class="fas fa-upload"></i></button>
                        <button class="btn btn-warning btn-icon"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-success btn-icon"><i class="fas fa-save"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/cargaGastos.js"></script>
    <script>

        $(document).ready(function() {
            $('#gastosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                pageLength: 100,
                order: [[0, 'desc']]
            });
        });

    </script>
</body>
</html>