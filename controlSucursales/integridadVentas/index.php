<?php
session_start();

// Configuración de entorno
$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$paisActual = ($checkedValue === 'suc_uy') ? 'URUGUAY' : 'ARGENTINA';
$banderaActual = ($checkedValue === 'suc_uy') ? '../../assets/images/UY.png' : '../../assets/images/bandera_con_sol__55757_std.jpg';
$dataOnValue = ($checkedValue === 'suc_uy') ? 'UY' : 'ARG';
$dataOffValue = ($checkedValue === 'suc_uy') ? 'ARG' : 'UY';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integridad y Auditoría de Sucursales</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- CSS específico -->
    <link rel="stylesheet" href="css/integridadVentas.css">
    
    <!-- Incluir CSS general del sistema -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/administracion/assets/css/css.php'; ?>
</head>
<body>

    <div class="integridadVentas_wrapper">
        <div class="container-fluid">
            <div class="integridadVentas_main-card">
                
                <!-- Header con título y selector de país -->
                <div class="integridadVentas_header">
                    <div class="integridadVentas_header-content">
                        <div class="integridadVentas_title-section">
                            <a href="http://192.168.0.13:8000/" class="integridadVentas_home-btn" title="Volver al menú principal">
                                <img src="../../image/home-button.png" alt="Home">
                            </a>
                            <h3>
                                <i class="bi bi-shield-check"></i>
                                Integridad y Auditoría de Sucursales
                            </h3>
                        </div>
                        <div class="integridadVentas_country-selector">
                            <span class="integridadVentas_country-label">Cambiar entorno:</span>
                            <div class="custom-toggle-container" onclick="cambiarEntornoCustom(this)">
                                <div class="toggle-flag <?= ($checkedValue === 'central') ? 'active' : '' ?>" data-entorno="central">
                                    <img src="../../assets/images/bandera_con_sol__55757_std.jpg" alt="Argentina">
                                    <span>ARG</span>
                                </div>
                                <div class="toggle-flag <?= ($checkedValue === 'suc_uy') ? 'active' : '' ?>" data-entorno="suc_uy">
                                    <img src="../../assets/images/UY.png" alt="Uruguay">
                                    <span>UY</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs de Bootstrap 5 -->
                <ul class="nav nav-tabs integridadVentas_tabs" id="integridadVentasTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-controlVentas" data-bs-toggle="tab" data-bs-target="#controlVentas" type="button" role="tab" aria-controls="controlVentas" aria-selected="true">
                            <i class="bi bi-graph-up me-2"></i>Control Ventas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-diferenciaIVA" data-bs-toggle="tab" data-bs-target="#diferenciaIVA" type="button" role="tab" aria-controls="diferenciaIVA" aria-selected="false">
                            <i class="bi bi-receipt me-2"></i>Control IVA Ventas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-ventaVsCobranza" data-bs-toggle="tab" data-bs-target="#ventaVsCobranza" type="button" role="tab" aria-controls="ventaVsCobranza" aria-selected="false">
                            <i class="bi bi-card-checklist me-2"></i>Venta vs Cobranza
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-arqueoCaja" data-bs-toggle="tab" data-bs-target="#arqueoCaja" type="button" role="tab" aria-controls="arqueoCaja" aria-selected="false">
                            <i class="bi bi-cash-coin me-2"></i>Arqueo vs Caja
                        </button>
                    </li>
                </ul>
                
                <!-- Contenido de las tabs -->
                <div class="tab-content integridadVentas_tab-content" id="integridadVentasTabContent">
                    
                    <!-- Tab 1: Control de Ventas por Sucursal -->
                    <div class="tab-pane fade show active" id="controlVentas" role="tabpanel" aria-labelledby="tab-controlVentas">
                        <?php include 'components/tabControlVentas.php'; ?>
                    </div>
                    
                    <!-- Tab 2: Diferencia IVA Ventas -->
                    <div class="tab-pane fade" id="diferenciaIVA" role="tabpanel" aria-labelledby="tab-diferenciaIVA">
                        <?php include 'components/tabDiferenciaIVA.php'; ?>
                    </div>
                    
                    <!-- Tab 3: Venta vs Cobranza -->
                    <div class="tab-pane fade" id="ventaVsCobranza" role="tabpanel" aria-labelledby="tab-ventaVsCobranza">
                        <?php include 'components/tabVentaVsCobranza.php'; ?>
                    </div>

                    <!-- Tab 4: Arqueo vs Caja -->
                    <div class="tab-pane fade" id="arqueoCaja" role="tabpanel" aria-labelledby="tab-arqueoCaja">
                        <?php include 'components/tabArqueoCaja.php'; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
    
    <!-- Spinner overlay para operaciones de carga -->
    <div id="loading-overlay" class="integridadVentas_loading-overlay" style="display: none;">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="integridadVentas_loading-text">Procesando...</p>
    </div>
    
    <!-- Modales -->
    <?php include 'modals/modalDetalleIVA.php'; ?>
    <?php include 'modals/modalDetalleVentas.php'; ?>
    
    <!-- jQuery (necesario para DataTables y algunas funcionalidades) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Plugin para exportar a Excel -->
    <script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
    
    <!-- Select2 para selectores avanzados -->
    <script src="js/libs/select2.min.js"></script>
    
    <!-- Scripts específicos de la aplicación -->
    <script src="js/main.js"></script>
    <script src="js/controlVentas.js"></script>
    <script src="js/diferenciaIVA.js"></script>
    <script src="js/ventaVsCobranza.js"></script>
    <script src="js/arqueoCaja.js"></script>

</body>
</html>
