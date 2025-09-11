<?php
    require_once "Class/Alquiler.php";
    require_once "Class/sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $conceptos = $alquiler->traerConceptosPorcentaje();
    $locales = $sucursal->traerLocales(true);
    
    $conceptoFiltrado = isset($_GET['conceptos']) ? $_GET['conceptos'] : "6-Porc. S/ventas brutas";

    $idConcepto = explode("-", $conceptoFiltrado)[0];
    $descConcepto = explode("-", $conceptoFiltrado)[1];
    $porcentajePorSucursal = $alquiler->traerPorcentajeSucursal($idConcepto);

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
        $checked = 'checked';
    }else{
        $checked = '';
    }
        
    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
    $dataOnValue = 'ARG';
    $dataOffValue = 'UY';
    
    // Imagen de la bandera que se mostrará al lado del toggle
    $imagenBandera = ($checkedValue === 'central') ? '../../assets/images/bandera_con_sol__55757_std.jpg' : '../../assets/images/UY.png';
    
    $nombrePais = ($checkedValue === 'central') ? 'Argentina' : 'Uruguay';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga Contratos Alquiler</title>
    
    <!-- CSS Includes -->
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/cargaAlquileres.css">
    
    <style>

        /* Toggle simple sin banderas */
        .toggle-on, .toggle-off {
            font-size: 12px !important;
            font-weight: bold !important;
            color: white !important;
            text-shadow: none !important;
            line-height: 30px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Custom toggle styles */
        .toggle.btn {
            height: 38px !important;
            min-width: 90px !important;
            border-radius: 6px !important;
            padding: 0 !important;
        }

        /* Colores específicos para cada estado */
        .toggle-on {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }
        
        .toggle-off {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
        }

        /* Bandera al lado del toggle */
        .flag-indicator {
            width: 40px;
            height: 30px;
            margin-left: 10px;
            border-radius: 4px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border: 2px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .environment-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .country-label {
            color: white;
            font-size: 14px;
            font-weight: bold;
            margin-left: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        /* Asegurar que el toggle tenga colores correctos y no sean sobreescritos */
        .toggle.off .toggle-off {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
        }
        
        .toggle:not(.off) .toggle-on {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }

        /* Forzar estilos del texto del toggle */
        .toggle .toggle-handle {
            background-color: white !important;
            border: 1px solid #ccc !important;
        }

        /* Header title styling */
        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title h1 {
            margin: 0;
            font-size: 24px;
        }

    </style>
</head>

<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <a href="http://192.168.0.13:8000/" class="home-button" title="Ir al menú principal">
                    <img src="../../image/home-button.png" alt="Home">
                </a>
                <i class="bi bi-key" style="font-size: 28px;"></i>
                <h1>Carga Contratos Alquiler</h1>
            </div>
            
            <div class="environment-toggle">
                <div class="environment-controls">
                    <input type="checkbox" <?= $checked ?> data-toggle="toggle" 
                           data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" 
                           class="custom-toggle" onchange="cambiarEntorno(this)" 
                           id="checkEntorno">
                    <div class="flag-indicator" style="background-image: url('<?= $imagenBandera ?>');" title="<?= $nombrePais ?>"></div>
                    <span class="country-label"><?= $nombrePais ?></span>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <div class="form-content">
            <div id="username" class="hidden"><?= $_SESSION['username'] ?></div>
            
            <form>
                <!-- Sucursal Section -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="bi bi-building"></i>
                        Información de Sucursal
                    </div>
                    
                    <div class="form-group">
                        <label for="selectSucursal" class="form-label">Sucursal</label>
                        <select name="selectSucursal" id="selectSucursal" class="form-control">
                            <option value="">Seleccione una sucursal...</option>
                            <?php foreach ($locales as $value): ?>
                                <option value="<?= $value['NRO_SUCURSAL'] ?>-<?= $value['DESC_SUCURSAL'] ?>">
                                    <?= $value['DESC_SUCURSAL'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Vigencia Section -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="bi bi-calendar-range"></i>
                        Período de Vigencia
                    </div>
                    
                    <div class="date-inputs">
                        <div class="date-group">
                            <label for="desde">Desde:</label>
                            <input type="date" id="desde" value="<?= isset($desde) ? $desde : '' ?>">
                        </div>
                        <div class="date-group">
                            <label for="hasta">Hasta:</label>
                            <input type="date" id="hasta" value="<?= isset($hasta) ? $hasta : '' ?>">
                        </div>
                    </div>
                </div>

                <!-- Importes Section -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="bi bi-currency-dollar"></i>
                        Importes del Contrato
                    </div>
                    
                    <div class="amounts-grid">
                        <div class="amount-field">
                            <label for="valorLlave" class="form-label">Valor Llave</label>
                            <input type="text" id="valorLlave" class="form-control" 
                                   placeholder="Ingrese el valor llave" onchange="parseNumber(this)">
                        </div>
                        
                        <div class="amount-field">
                            <label for="comisiones" class="form-label">Comisiones</label>
                            <input type="text" id="comisiones" class="form-control" 
                                   placeholder="Ingrese las comisiones" onchange="parseNumber(this)">
                        </div>
                        
                        <div class="amount-field">
                            <label for="lanzamiento" class="form-label">FPC Lanzamiento</label>
                            <input type="text" id="lanzamiento" class="form-control" 
                                   placeholder="Ingrese FPC lanzamiento" onchange="parseNumber(this)">
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="form-group" style="text-align: center;">
                    <button type="button" class="save-button" onclick="guardar()">
                        <i class="bi bi-floppy"></i>
                        Guardar Contrato
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/js/js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
    <script src="js/cargaContratoAlquileres.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $("#selectSucursal").select2({
                placeholder: "Seleccione una sucursal",
                allowClear: true,
                width: '100%'
            });

            // Style adjustments after initialization
            setTimeout(() => {
                const toggle = document.querySelector(".toggle");
                if (toggle) {
                    toggle.style.width = "90px";
                    toggle.style.height = "38px";
                }
                
                // Asegurar que el toggle muestre texto blanco y centrado
                const toggleOn = document.querySelector(".toggle-on");
                const toggleOff = document.querySelector(".toggle-off");
                
                if (toggleOn) {
                    toggleOn.style.fontSize = "12px";
                    toggleOn.style.fontWeight = "bold";
                    toggleOn.style.color = "white";
                    toggleOn.style.display = "flex";
                    toggleOn.style.alignItems = "center";
                    toggleOn.style.justifyContent = "center";
                    toggleOn.style.textShadow = "none";
                }
                if (toggleOff) {
                    toggleOff.style.fontSize = "12px";
                    toggleOff.style.fontWeight = "bold";
                    toggleOff.style.color = "white";
                    toggleOff.style.display = "flex";
                    toggleOff.style.alignItems = "center";
                    toggleOff.style.justifyContent = "center";
                    toggleOff.style.textShadow = "none";
                }
            }, 100);
        });
    </script>
</body>
</html>