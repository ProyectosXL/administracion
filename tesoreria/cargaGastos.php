<?php
require_once 'Class/gasto.php';

$gasto = new Gasto();

// Establecer fechas por defecto
$desde = isset($_GET['desde']) && $_GET['desde'] != "" ? $_GET['desde'] : date('Y-m-d', strtotime("-30 days"));
$hasta = isset($_GET['hasta']) && $_GET['hasta'] != "" ? $_GET['hasta'] : date('Y-m-d');

// Obtener los gastos filtrados
$gastos = $gasto->traerGastos($desde, $hasta);

// Prepare data for JSON encoding
$gastos_json = array();
foreach ($gastos as $g) {
    $gastos_json[] = array(
        'COD_COMP' => $g['COD_COMP'],
        'N_COMP' => $g['N_COMP'],
        'COD_CTA' => $g['COD_CTA'],
        'FECHA_CARD' => $g['FECHA']->format('d/m/Y'),
        'FECHA_TABLE' => $g['FECHA']->format('Y-m-d'),
        'DESC_CUENTA' => $g['DESC_CUENTA'],
        'MONTO' => number_format($g['MONTO'], 0, ',', '.'),
        'USUARIO' => $g['USUARIO'],
        'LEYENDA' => $g['LEYENDA'],
    );
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egresos de caja Tesorería</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
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
        .gasto-card {
            border-left: 5px solid #0d6efd;
            margin-bottom: 1rem;
        }
        .gasto-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .gasto-fecha {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .gasto-card .card-body {
            padding: 1rem;
        }
        .gasto-card-actions {
            text-align: right;
        }
        .gasto-card p {
            margin-bottom: 0.5rem;
        }

        /* View-switching styles */
        .card-view-container, .mobile-search-container { display: none; }
        .table-view-container { display: block; }

        @media (max-width: 768px) {
            .card-view-container, .mobile-search-container { display: block; }
            .table-view-container { display: none; }
        }
         .mobile-search-container {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h3 class="mb-4"><i class="fas fa-cash-register me-2"></i>Egresos de caja Tesorería</h3>
        <form id="filtroForm" method="GET" action="">
            <div class="row mb-3">
                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                    <label for="desde">Desde:</label>
                    <input type="date" id="desde" name="desde" class="form-control" value="<?php echo $desde; ?>">
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                    <label for="hasta">Hasta:</label>
                    <input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo $hasta; ?>">
                </div>
                <div class="col-lg-3 col-md-4 col-sm-12 mb-3 d-flex align-items-end">
                    <button type="submit" id="filtrar" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Filtrar</button>
                </div>
            </div>
        </form>

        <!-- Mobile Search -->
        <div class="mobile-search-container">
            <input type="text" id="mobileSearch" class="form-control" placeholder="Buscar gastos...">
        </div>

        <!-- Card View for Mobile -->
        <div class="card-view-container">
            <div id="gastos-container-mobile" class="row">
                <!-- Cards will be rendered here by JavaScript -->
            </div>
        </div>

        <!-- Table View for Desktop -->
        <div class="table-view-container">
            <div class="table-responsive">
                <table id="gastosTable" class="table table-striped table-bordered w-100">
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
                        <!-- Table rows will be rendered here by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const gastosData = <?php echo json_encode($gastos_json); ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
    <script src="js/cargaGastos.js"></script>
</body>
</html>