
<?php

if (!isset($_SESSION['numsuc'])) {
    $_SESSION['numsuc'] = 'Casa Central';
}

$nroSucurs = $_SESSION['numsuc'];

?>

<!-- index.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga de Anticipo de Sueldo</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>

        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .table-responsive {
            overflow-x: hidden;
        }
        .alert {
            border-left: 4px solid;
            margin-bottom: 1rem;
        }

        .alert-secondary {
            border-left-color: #6c757d;
        }

        .alert-info {
            border-left-color: #0dcaf0;
        }

        .alert-warning {
            border-left-color: #ffc107;
        }

        .alert i {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .alert {
                margin-bottom: 0.5rem;
            }
            
            .row.mb-4 {
                margin-bottom: 1rem !important;
            }
        }
        .info-box {
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    }

    .info-box:hover {
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }

    .info-box-content {
        display: flex;
        flex-direction: column;
    }

    .info-box-text {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }

    .info-box-number {
        font-size: 1rem;
        font-weight: 600;
        color: #212529;
    }

    .card-header {
        border-bottom: 0;
        padding: 1rem;
    }

    .card {
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }

    @media (max-width: 768px) {
        .info-box {
            margin-bottom: 0.5rem;
        }

        .info-box-content {
            text-align: center;
        }
    }

    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
    <div class="header-section mb-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-money-bill-wave me-2"></i>
                Carga de Anticipo de Sueldo
            </h5>
        </div>
        
        <div class="card-body">
            <div class="row g-3">
                <!-- Sucursal -->
                <div class="col-12">
                    <div class="info-box bg-light">
                        <div class="info-box-content">
                            <i class="fas fa-building me-2 text-primary"></i>
                            <span class="info-box-text">Sucursal:</span>
                            <span class="info-box-number" id="numSucursal">Casa Central</span>
                        </div>
                    </div>
                </div>
                
                <!-- Fecha de Depósito -->
                <div class="col-md-4">
                    <div class="info-box bg-info bg-opacity-10">
                        <div class="info-box-content">
                            <i class="fas fa-calendar-check me-2 text-info"></i>
                            <span class="info-box-text">Fecha de Depósito:</span>
                            <span class="info-box-number" id="fechaDeposito"></span> <!-- Remover valor por defecto -->
                        </div>
                    </div>
                </div>

                <!-- Vigencia -->
                <div class="col-md-8">
                    <div class="info-box bg-warning bg-opacity-10">
                        <div class="info-box-content">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-clock me-2 text-warning"></i>
                                <span class="info-box-text fw-bold">Vigencia</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <small class="d-block text-muted">Desde:</small>
                                            <span class="info-box-number" id="vigDesde"></span> <!-- Remover valor por defecto -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <small class="d-block text-muted">Hasta:</small>
                                            <span class="info-box-number" id="vigHasta"></span> <!-- Remover valor por defecto -->
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
</div>

        <!-- Main Form -->
        <form id="adelantoForm" class="mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="empleadoSelect" class="form-label">Seleccione Empleado</label>
                            <select class="form-select" id="empleadoSelect" style="width: 100%">
                                <option value="">Buscar empleado...</option>
                            </select>
                        </div>
                    </div>

                    <!-- DataTable for Records -->
                    <div class="table-responsive">
                        <table id="registrosTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Legajo</th>
                                    <th>Nombre y Apellido</th>
                                    <th>DNI</th>
                                    <th>Importe Adelanto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Enviar
                </button>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/mostrarDatos.js"></script>
    <script src="js/cargarAnticipo.js"></script>

</body>
</html>