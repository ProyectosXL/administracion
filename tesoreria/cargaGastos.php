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
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        <div id="gastos-container" class="row">
            <?php foreach ($gastos as $gasto): ?>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="card gasto-card"
                         data-cod-comp="<?php echo $gasto['COD_COMP']; ?>"
                         data-n-comp="<?php echo $gasto['N_COMP']; ?>"
                         data-cod-cta="<?php echo $gasto['COD_CTA']; ?>">
                        <div class="card-body">
                            <div class="gasto-card-header">
                                <h5 class="card-title">Gasto #<?php echo $gasto['N_COMP']; ?></h5>
                                <span class="gasto-fecha"><?php echo $gasto['FECHA']->format("d/m/Y"); ?></span>
                            </div>
                            <p class="card-text"><strong>Cuenta:</strong> <?php echo $gasto['DESC_CUENTA']; ?> (<?php echo $gasto['COD_CTA']; ?>)</p>
                            <p class="card-text"><strong>Monto:</strong> $<?php echo number_format($gasto['MONTO'], 0, ',', '.'); ?></p>
                            <p class="card-text"><strong>Usuario:</strong> <?php echo $gasto['USUARIO']; ?></p>
                            <p class="card-text"><strong>Leyenda:</strong> <?php echo $gasto['LEYENDA']; ?></p>
                            <div class="gasto-card-actions">
                                <button class="btn btn-primary btn-icon btn-upload"><i class="fas fa-upload"></i></button>
                                <button class="btn btn-warning btn-icon btn-view"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-success btn-icon btn-save"><i class="fas fa-save"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/cargaGastos.js"></script>
</body>
</html>