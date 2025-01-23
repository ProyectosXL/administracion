
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Anticipos</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table-responsive {
            overflow-x: hidden !important;
        }
        #reportTable {
            width: 100% !important;
            table-layout: auto !important;
        }
        .dataTables_wrapper {
            max-width: 100% !important;
            overflow-x: hidden !important;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: none;
            padding: 1rem;
        }
        .filters-section {
            background-color: #fff;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .dt-buttons .btn {
            margin-right: 0.5rem;
        }
        .search-box {
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .filters-section {
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid mt-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Reporte de Anticipos
                    </h5>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="filters-section">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Período</label>
                            <select id="periodFilter" class="form-select">
                                <option value="">Todos los períodos</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="searchBox" class="form-control" placeholder="Buscar...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="table-responsive">
                    <table id="reportTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Legajo</th>
                                <th>Nombre y Apellido</th>
                                <th>DNI</th>
                                <th>Período</th>
                                <th>Importe</th>
                                <th>Fecha de Carga</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    
    <script src="js/reporteAnticipos.js"></script>
</body>
</html>