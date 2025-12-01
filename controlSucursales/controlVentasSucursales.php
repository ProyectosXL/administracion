<?php
session_start(); // <-- AÑADIDO: Necesario para gestionar el entorno
require_once '../class/conexion.php';

// Calcular el primer y último día del mes anterior
$previous_month_first = date('Y-m-01', strtotime("first day of last month"));
$previous_month_last = date('Y-m-t', strtotime("last day of last month"));

// --- INICIO DE CÓDIGO NUEVO PARA GESTIONAR ENTORNO ---
$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$paisActual = ($checkedValue === 'suc_uy') ? 'URUGUAY' : 'ARGENTINA';
$banderaActual = ($checkedValue === 'suc_uy') ? '../assets/images/UY.png' : '../assets/images/bandera_con_sol__55757_std.jpg';
// --- FIN DE CÓDIGO NUEVO ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Ventas por Sucursal</title>
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
    #loading-overlay {
        display: none; /* Oculto por defecto */
        position: fixed; /* Cubre toda la pantalla */
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6); /* Fondo negro semitransparente */
        z-index: 9999; /* Asegura que esté por encima de todo */
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column; /* Para alinear el texto debajo del spinner */
    }

    #loading-overlay .spinner-border {
        width: 3rem; /* Hacemos el spinner un poco más grande */
        height: 3rem;
    }

    #loading-overlay .loading-text {
        color: white;
        margin-top: 15px;
        font-size: 1.2rem;
    }
    /* --- FIN DEL NUEVO CSS --- */

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
                <!-- --- INICIO DE MODIFICACIÓN DEL FORMULARIO --- -->
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2">
                        <label for="fecha-desde">Desde:</label>
                        <input type="date" class="form-control" id="fecha-desde" value="<?php echo $previous_month_first; ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="fecha-hasta">Hasta:</label>
                        <input type="date" class="form-control" id="fecha-hasta" value="<?php echo $previous_month_last; ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <button type="button" id="btn-consultar" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Consultar
                        </button>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="button" id="btn-descargar-excel" class="btn btn-success btn-block">
                            <i class="fas fa-file-excel"></i> Descargar Excel
                        </button>
                    </div>
                    <!-- NUEVO SELECTOR DE PAÍS -->
                    <div class="form-group col-md-4 d-flex align-items-end justify-content-end">
                        <div style="text-align: right;">
                            <label>País:</label>
                            <div class="d-flex align-items-center">
                                <img src="<?= $banderaActual ?>" alt="<?= $paisActual ?>" style="width: 30px; height: 20px; border-radius: 3px; margin-right: 10px;">
                                <select class="form-control" onchange="cambiarEntorno(this)">
                                    <option value="ARG" <?= ($checkedValue !== 'suc_uy') ? 'selected' : '' ?>>Argentina</option>
                                    <option value="URY" <?= ($checkedValue === 'suc_uy') ? 'selected' : '' ?>>Uruguay</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- --- FIN DE MODIFICACIÓN DEL FORMULARIO --- -->
            </form>

            <div id="resultado-consulta" class="mt-4 position-relative">
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
                        <tfoot class="thead-dark">
                            <tr>
                                <th colspan="2" class="text-right">TOTALES:</th>
                                <th id="total-importe-central">$ 0.00</th>
                                <th id="total-importe-local">$ 0.00</th>
                                <th id="total-diferencia">$ 0.00</th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
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

<!-- ===== INICIO: NUEVO SPINNER OVERLAY ===== -->
<div id="loading-overlay">
    <div class="spinner-container">
        <div class="spinner-border text-light" role="status">
            <span class="sr-only">Cargando...</span>
        </div>
        <p class="loading-text">Procesando...</p>
    </div>
</div>
<!-- ===== FIN: NUEVO SPINNER OVERLAY ===== -->

</body>
</html>