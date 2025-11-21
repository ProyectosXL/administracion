<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuración del entorno
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Comercio Exterior | Sistema de Gestión</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">
    
    <!-- Main CSS-->
    <link href="css/index.css" rel="stylesheet" media="all">

</head>

<body>
    <div class="main-wrapper">
        <!-- Header -->
        <div class="main-header">
            <div class="header-content">
                <div class="header-title-section">
                    <h1 class="main-title">
                        <i class="bi bi-globe-americas"></i>
                        Comercio Exterior
                    </h1>
                    <p class="main-subtitle">Sistema de Gestión de Importaciones</p>
                </div>
                
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
            
            <div class="environment-info">
                <i class="bi bi-geo-alt-fill"></i>
                <span id="environment-text">Entorno: <strong><?php echo ($checkedValue === 'central') ? 'Argentina (ARG)' : 'Uruguay (UY)'; ?></strong></span>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tabs-container">
            <ul class="nav nav-tabs custom-tabs" id="mainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="gestion-tab" data-bs-toggle="tab" data-bs-target="#gestion" 
                            type="button" role="tab" aria-controls="gestion" aria-selected="true">
                        <i class="bi bi-list-check"></i>
                        <span>Gestión de Despachos</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="costos-tab" data-bs-toggle="tab" data-bs-target="#costos" 
                            type="button" role="tab" aria-controls="costos" aria-selected="false">
                        <i class="bi bi-calculator"></i>
                        <span>Costos de Nacionalización</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" 
                            type="button" role="tab" aria-controls="dashboard" aria-selected="false">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Dashboard</span>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="mainTabsContent">
            <!-- Gestión de Despachos Tab -->
            <div class="tab-pane fade show active" id="gestion" role="tabpanel" aria-labelledby="gestion-tab">
                <div class="tab-content-wrapper">
                    <iframe src="gestionDespachos.php" class="content-iframe" id="gestionFrame"></iframe>
                </div>
            </div>

            <!-- Costos de Nacionalización Tab -->
            <div class="tab-pane fade" id="costos" role="tabpanel" aria-labelledby="costos-tab">
                <div class="tab-content-wrapper">
                    <iframe src="mostrarOrden.php" class="content-iframe" id="costosFrame"></iframe>
                </div>
            </div>

            <!-- Dashboard Tab -->
            <div class="tab-pane fade" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                <div class="tab-content-wrapper">
                    <iframe src="dashboard.php" class="content-iframe" id="dashboardFrame"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/jquery/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/index.js"></script>
</body>
</html>
