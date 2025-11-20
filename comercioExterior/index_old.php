
<?php
include 'Class/proveedor.php';
include 'Class/ordenDeCompra.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
    $checked = 'checked';
}else{
    $checked = '';
}
    
$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$dataOnValue = ($checkedValue === 'uy') ? 'UY' : 'ARG';
$dataOffValue = ($checkedValue === 'uy') ? 'ARG' : 'UY';
$imageOn = ($checkedValue === 'central') ? 'css/bandera_con_sol__55757_std.jpg' : 'css/UY.png';
$imageOff = ($checkedValue === 'central') ? 'css/UY.png' : 'css/bandera_con_sol__55757_std.jpg';

$proveedor = new Proveedor();
$todosLosProveedores = $proveedor->traerProveedores();
$todosLosProveedores = json_decode($todosLosProveedores);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Costos Importación</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    
    <!-- Icons font CSS-->
    <link href="assets/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">
    <link href="assets/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    
    <!-- Font special for pages-->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    
    <!-- Vendor CSS-->
    <link href="assets/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="assets/datepicker/daterangepicker.css" rel="stylesheet" media="all">
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">
    
    <!-- Main CSS-->
    <link href="css/index.css" rel="stylesheet" media="all">

</head>

<body>
    <div class="card-container">
        <div class="card">
            <div class="card-header">
                <h1 class="text-center mb-0">
                    <i class="bi bi-globe-americas me-2"></i>
                    Comercio Exterior
                </h1>
            </div>
            <div class="card-body">
                <div class="title">
                    <i class="bi bi-folder-check"></i> 
                    Panel de Control
                    
                    <div class="country-toggle">
                        <img src="<?php echo $imageOff; ?>" class="flag" id="flag-left" alt="<?php echo $dataOffValue; ?>">
                        <label class="toggle-switch">
                            <input type="checkbox" id="country-toggle" <?php echo $checked; ?> 
                                   data-on="<?php echo $dataOnValue; ?>" 
                                   data-off="<?php echo $dataOffValue; ?>">
                            <span class="slider"></span>
                        </label>
                        <img src="<?php echo $imageOn; ?>" class="flag" id="flag-right" alt="<?php echo $dataOnValue; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <span id="environment-info">Entorno actual: <strong><?php echo ($checkedValue === 'central') ? 'Argentina (ARG)' : 'Uruguay (UY)'; ?></strong></span>
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-action btn-create" id="btnCrear">
                        <i class="bi bi-plus-circle-fill"></i>
                        Crear Nuevo
                    </button>
                    <button class="btn btn-action btn-edit" id="btnEditar">
                        <i class="bi bi-pencil-square"></i>
                        Editar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Jquery JS-->
    <script src="assets/jquery/jquery.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
    
    <!-- SweetAlert2 for nice alerts -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Vendor JS-->
    <script src="assets/select2/select2.min.js"></script>
    <script src="assets/datepicker/moment.min.js"></script>
    <script src="assets/datepicker/daterangepicker.js"></script>
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
    
    <!-- Main JS-->
    <script src="js/global.js"></script>
    <script src="js/index.js"></script>
</body>
</html>