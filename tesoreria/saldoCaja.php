
<?php
require_once 'Class/saldo.php';

// Establecer fechas por defecto - últimos 15 días incluyendo hoy
$hasta = date('Y-m-d');
$desde = date('Y-m-d', strtotime('-15 days'));

// Obtener filtros si existen
$desde = isset($_GET['desde']) && $_GET['desde'] != "" ? $_GET['desde'] : $desde;
$hasta = isset($_GET['hasta']) && $_GET['hasta'] != "" ? $_GET['hasta'] : $hasta;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Saldo de Caja - Tesorería</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/saldoCaja.css">
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-wallet me-2"></i>
                Saldo de Caja - Tesorería
            </h1>
            <p class="page-subtitle">Consulta de movimientos y saldo actual de la cuenta 100101</p>
        </div>

        <!-- Filtros -->
        <div class="filters-card">
            <form id="filtroForm" method="GET" action="">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <label for="desde" class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i>Fecha Desde
                        </label>
                        <input type="date" id="desde" name="desde" class="form-control" value="<?php echo $desde; ?>">
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <label for="hasta" class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i>Fecha Hasta
                        </label>
                        <input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo $hasta; ?>">
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-12 mb-3">
                        <button type="button" id="filtrar" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Consultar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Saldo Actual Destacado -->
        <div class="row">
            <div class="col-12">
                <div class="stat-card saldo-actual-card">
                    <div class="stat-icon saldo">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-value" id="saldo-actual">$0</div>
                    <div class="stat-label">Saldo Actual en Caja</div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="stats-container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon ingreso">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-value" id="total-ingresos">$0</div>
                        <div class="stat-label" id="cant-ingresos">0 movimientos</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon egreso">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="stat-value" id="total-egresos">$0</div>
                        <div class="stat-label" id="cant-egresos">0 movimientos</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon saldo">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-value" id="total-movimientos">0</div>
                        <div class="stat-label" id="periodo-movimientos">movimientos en el período</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon saldo">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-value"><?php echo date('d/m/Y'); ?></div>
                        <div class="stat-label">Fecha de Consulta</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Movimientos -->
        <div class="table-container">
            <div class="table-responsive">
                <table id="movimientosTable" class="table table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Comp.</th>
                            <th>N° Comp.</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Debe</th>
                            <th>Haber</th>
                            <th>Saldo</th>
                            <th>Fotos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargarán vía AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/saldoCaja.js"></script>
</body>
</html>