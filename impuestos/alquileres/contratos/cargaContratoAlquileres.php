<?php
    require_once "Class/Contrato.php";
    require_once "../Class/Alquiler.php";
    require_once "../Class/sucursal.php";

    $contrato = new Contrato();
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
    
    // Entornos de este módulo: 'central' (Argentina) y 'uy' (Uruguay).
    $checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
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
    <link rel="stylesheet" href="css/cargaContratoAlquileres.css">
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
            
            <div class="toggle-wrapper">
                <label>Cambiar entorno:</label>
                <div class="custom-toggle-container" onclick="cambiarEntornoCustom(this)" title="Cambiar entorno">
                    <div class="toggle-flag <?= ($checkedValue === 'central') ? 'active' : '' ?>" data-entorno="central">
                        <img src="../../../assets/images/bandera_con_sol__55757_std.jpg" alt="Argentina">
                        <span>ARG</span>
                    </div>
                    <div class="toggle-flag <?= ($checkedValue === 'uy') ? 'active' : '' ?>" data-entorno="uy">
                        <img src="../../../assets/images/UY.png" alt="Uruguay">
                        <span>UY</span>
                    </div>
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
                            <optgroup label="Activas">
                            <?php foreach ($locales as $value): if (!empty($value['HABILITADO'])): ?>
                                <option value="<?= $value['NRO_SUCURSAL'] ?>-<?= $value['DESC_SUCURSAL'] ?>">
                                    <?= $value['DESC_SUCURSAL'] ?>
                                </option>
                            <?php endif; endforeach; ?>
                            </optgroup>
                            <optgroup label="Cerradas con historial">
                            <?php foreach ($locales as $value): if (empty($value['HABILITADO'])): ?>
                                <option value="<?= $value['NRO_SUCURSAL'] ?>-<?= $value['DESC_SUCURSAL'] ?>">
                                    <?= $value['DESC_SUCURSAL'] ?> (Cerrada)
                                </option>
                            <?php endif; endforeach; ?>
                            </optgroup>
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
    <script src="js/cargaContratoAlquileres.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $("#selectSucursal").select2({
                placeholder: "Seleccione una sucursal",
                allowClear: true,
                width: '100%'
            });
        });

        /**
         * Selector de entorno con banderas. Un clic cambia al entorno opuesto.
         * En Alquileres los entornos son 'central' y 'uy'; el controller espera 0 / 1.
         */
        function cambiarEntornoCustom(container) {
            const activa = container.querySelector('.toggle-flag.active');
            const entorno = (activa && activa.dataset.entorno === 'central') ? 1 : 0;

            // Sin esto el clic parece no hacer nada mientras se procesa el request.
            container.style.pointerEvents = 'none';
            container.style.opacity = '0.6';

            $.ajax({
                url: '../Controller/cambiarEntorno.php',
                method: 'POST',
                data: { entorno: entorno },
                success: function() {
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error('Error al cambiar entorno:', error);
                    container.style.pointerEvents = '';
                    container.style.opacity = '';
                }
            });
        }
    </script>
</body>
</html>