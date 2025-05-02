
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
    <link href="css/style.css" rel="stylesheet" media="all">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --accent-color: #ffc107;
            --dark-color: #212529;
            --light-color: #f8f9fa;
            --arg-color: #3498db;
            --uy-color: #1abc9c;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .card-container {
            width: 100%;
            max-width: 800px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary-color), #0b5ed7);
            color: white;
            padding: 25px;
            border-bottom: none;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="white" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,138.7C1248,117,1344,75,1392,53.3L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-repeat: no-repeat;
            background-size: cover;
        }
        
        .card-body {
            padding: 30px;
            background-color: white;
        }
        
        .title {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .country-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--uy-color);
            transition: .4s;
            border-radius: 30px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--arg-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        
        .flag {
            width: 30px;
            height: 20px;
            object-fit: cover;
            border-radius: 3px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-action {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-create {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-create:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
        }
        
        .btn-edit {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--dark-color);
        }
        
        .btn-edit:hover {
            background-color: #ffca2c;
            border-color: #ffc720;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.2);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .country-toggle {
                margin-left: 0;
                margin-bottom: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
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
                            <input type="checkbox" id="country-toggle" <?php echo $checked; ?>>
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
    
    <script>
        $(document).ready(function() {
            const countryToggle = $('#country-toggle');
            const environmentInfo = $('#environment-info');
            
            // Get initial state from your PHP session if available
            // For now we'll default to Argentina (central)
            const initialState = '<?= isset($_SESSION["entorno"]) ? $_SESSION["entorno"] : "central" ?>';
            countryToggle.prop('checked', initialState === 'central');
            
            // Initialize based on checkbox state
            updateEnvironment(countryToggle.is(':checked'));
            
            // Toggle environment
            countryToggle.change(function() {
                updateEnvironment($(this).is(':checked'));
                
                // Call your PHP function to update the session
                $.ajax({
                    url: 'cambiar_entorno.php',
                    type: 'POST',
                    data: { entorno: $(this).is(':checked') ? 'central' : 'uy' },
                    success: function(response) {
                        console.log('Environment updated successfully');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error updating environment:', error);
                    }
                });
            });
            
            function updateEnvironment(isArgentina) {
                if (isArgentina) {
                    environmentInfo.html('Entorno actual: <strong>Argentina (ARG)</strong>');
                } else {
                    environmentInfo.html('Entorno actual: <strong>Uruguay (UY)</strong>');
                }
            }
            
            // Make the toggle visible and styled
            document.querySelector('.toggle-switch').style.width = '60px';
            
    </script>
</body>
</html>