<?php
require_once '../class/conexion.php';

// Calcular el primer y último día del mes anterior
$previous_month_first = date('Y-m-01', strtotime("first day of last month"));
$previous_month_last = date('Y-m-t', strtotime("last day of last month"));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Ventas por Sucursal</title>
    <!-- Estilos y scripts existentes del proyecto (ej. Bootstrap, jQuery) -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .card-header-blue {
            background-color: #007bff;
            color: white;
        }
        #loading-spinner {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .table-responsive {
            margin-top: 20px;
        }
    </style>
</head>
<body>



<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header card-header-blue">
            <h4 class="mb-0">Control de Ventas por Sucursal</h4>
        </div>
        <div class="card-body">
            <form id="form-consulta">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label for="fecha-desde">Desde:</label>
                        <input type="date" class="form-control" id="fecha-desde" value="<?php echo $previous_month_first; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="fecha-hasta">Hasta:</label>
                        <input type="date" class="form-control" id="fecha-hasta" value="<?php echo $previous_month_last; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <button type="button" id="btn-consultar" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Consultar
                        </button>
                    </div>
                </div>
            </form>

            <div id="resultado-consulta" class="mt-4 position-relative">
                <div id="loading-spinner" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Nro. Sucursal</th>
                                <th>Cod. Sucursal</th>
                                <th>Importe Central</th>
                                <th>Importe Local</th>
                                <th>Diferencia</th>
                                <th>Estado</th>
                                <th>Últ. Actualización</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-resultados">
                            <!-- Los datos se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/controlVentasSucursales.js"></script>

</body>
</html>
