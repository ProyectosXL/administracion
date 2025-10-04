<?php
require_once "Class/Sucursal.php";
require_once "Class/costoOcupacionService.php";

$sucursalObj = new Sucursal();
$service = new CostoOcupacionService();

$todosLosLocales = $sucursalObj->traerLocales(true);
$rangoDefault = $service->calcularRangoDefault();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$checked = ($checkedValue === 'central') ? 'checked' : '';
$dataOnValue = 'ARG';
$dataOffValue = 'UY';
$imagenBandera = ($checkedValue === 'central') ? 
    '../../assets/images/bandera_con_sol__55757_std.jpg' : 
    '../../assets/images/UY.png';
$nombrePais = ($checkedValue === 'central') ? 'Argentina' : 'Uruguay';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Costo de Ocupación</title>
    
    <!-- CSS Includes -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php'; ?>
    
    <!-- External Libraries -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/costoOcupacion.css">
    <link rel="stylesheet" href="css/modalEvolucion.css">
    
    <style>
        /* KPI Button Card */
        .kpi-button-card {
            transition: all 0.3s ease;
            border-left-color: #27ae60 !important;
        }
        
        .kpi-button-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 30px rgba(39, 174, 96, 0.3);
            background: linear-gradient(135deg, #f8fff8 0%, #e8f8e8 100%);
        }
        
        .kpi-button-card:active {
            transform: translateY(-3px) scale(1.01);
        }
 
        /* Toggle styles */
        .toggle-on, .toggle-off {
            font-size: 12px !important;
            font-weight: bold !important;
            color: white !important;
            text-shadow: none !important;
            line-height: 30px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-size: auto !important;
            background-image: none !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            height: auto !important;
            width: auto !important;
            min-height: 34px !important;
            min-width: 45px !important;
        }

        .toggle.btn {
            height: 38px !important;
            min-width: 90px !important;
            border-radius: 6px !important;
            padding: 0 !important;
        }

        .toggle-on {
            background-color: #007bff !important;
            border-color: #007bff !important;
        }
        
        .toggle-off {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
        }

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

        /* Tabs Styles */
        .tabs-container {
            margin-bottom: 20px;
        }

        .nav-tabs-custom {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0;
        }

        .nav-tabs-custom .nav-tabs {
            border-bottom: none;
            margin-bottom: 0;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #7f8c8d;
            font-weight: 600;
            padding: 15px 25px;
            transition: all 0.3s ease;
            border-radius: 8px 8px 0 0;
        }

        .nav-tabs-custom .nav-link:hover {
            color: #2c3e50;
            background-color: #f8f9fa;
        }

        .nav-tabs-custom .nav-link.active {
            color: #3498db;
            background-color: #f8f9fa;
            border-bottom: 3px solid #3498db;
        }

        .nav-tabs-custom .nav-link i {
            margin-right: 8px;
        }

        .tab-content {
            background: transparent;
            border: none;
            padding: 0;
        }

        .tab-pane {
            min-height: 400px;
        }
        
        /* Estilos adicionales para pestañas */
        @media (max-width: 768px) {
            .nav-tabs-custom .nav-link {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .nav-tabs-custom .nav-link i {
                display: none;
            }
        }

        .select2-container .select2-selection--single{
            height: 2rem !important;
        }
    </style>

</head>

<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-title">
                    <a href="http://192.168.0.13:8000/" class="home-button" title="Ir al menú principal">
                        <i class="bi bi-house-fill"></i>
                    </a>
                    <div class="title-info">
                        <h1><i class="bi bi-graph-up-arrow"></i> Costo de Ocupación</h1>
                        <p class="subtitle">Análisis de costos de alquiler por sucursal</p>
                    </div>
                </div>
                
                <div class="environment-toggle">
                    <div class="environment-controls">
                        <input type="checkbox" <?= $checked ?> data-toggle="toggle" 
                               data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" 
                               class="custom-toggle" onchange="cambiarEntorno(this)" 
                               id="checkEntorno">
                        <div class="flag-indicator" style="background-image: url('<?= $imagenBandera ?>');" 
                             title="<?= $nombrePais ?>"></div>
                        <span class="country-label"><?= $nombrePais ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content-wrapper">
            <!-- Tabs Navigation -->
            <div class="tabs-container">
                <nav class="nav-tabs-custom">
                    <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="analisis-tab" data-toggle="tab" href="#analisis" role="tab" aria-controls="analisis" aria-selected="true">
                                <i class="bi bi-graph-up"></i> Análisis Individual
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="comparar-tab" data-toggle="tab" href="#comparar" role="tab" aria-controls="comparar" aria-selected="false">
                                <i class="bi bi-arrow-left-right"></i> Comparar Sucursales
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="controlar-tab" data-toggle="tab" href="#controlar" role="tab" aria-controls="controlar" aria-selected="false">
                                <i class="bi bi-clipboard-check"></i> Controlar Gastos
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="mainTabContent">
                <!-- Pestaña 1: Análisis Individual -->
                <div class="tab-pane fade show active" id="analisis" role="tabpanel" aria-labelledby="analisis-tab">
                    <!-- Filtros Section -->
                    <div class="filters-section">
                <div class="section-title">
                    <i class="bi bi-funnel"></i>
                    <span>Filtros de Búsqueda</span>
                </div>
                
                <div class="filters-form">
                    <div class="filter-group">
                        <div class="filter-field">
                            <label for="selectSucursal" class="filter-label"><i class="bi bi-building"></i> Sucursal:</label>
                            <select id="selectSucursal" class="form-control" style="min-width: 300px; height: calc(2.25rem + 2px);">
                                <option value="">Seleccione una sucursal...</option>
                                <?php foreach ($todosLosLocales as $local): ?>
                                    <option value="<?= $local['ID'] ?>">
                                        <?= $local['SUCURSAL'] ?> (<?= $local['ID'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-field">
                            <label for="fechaDesde" class="filter-label"><i class="bi bi-calendar-event"></i> Desde:</label>
                            <input type="date" id="fechaDesde" class="form-control" 
                                   value="<?= $rangoDefault['desde'] ?>" style="height: calc(2.25rem + 2px);">
                        </div>
                        
                        <div class="filter-field">
                            <label for="fechaHasta" class="filter-label"><i class="bi bi-calendar-event"></i> Hasta:</label>
                            <input type="date" id="fechaHasta" class="form-control" 
                                   value="<?= $rangoDefault['hasta'] ?>" style="height: calc(2.25rem + 2px);">
                        </div>
                        
                        <button type="button" class="btn btn-primary filter-button-primary" id="btnAplicar">
                            <i class="bi bi-search"></i>
                            Aplicar
                        </button>
                        
                        <button type="button" class="btn btn-secondary filter-button-secondary" id="btnLimpiar">
                            <i class="bi bi-x-circle"></i>
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPIs Section (Actualizado) -->
            <div class="kpis-section" id="kpisSection" style="display: none;">
                <!-- KPI 1: Acumulado 12 meses -->
                <div class="kpi-card">
                    <div class="kpi-icon kpi-primary">
                        <i class="bi bi-calendar-range"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Acumulado últimos 12 meses</div>
                        <div class="kpi-value" id="kpiAcumulado12m">--</div>
                    </div>
                </div>
                
                <!-- KPI 2: Variación anual -->
                <div class="kpi-card">
                    <div class="kpi-icon kpi-info" id="kpiIconVariacion">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Variación vs año anterior</div>
                        <div class="kpi-value">
                            <span id="kpiVariacionAnual">--</span>
                            <span class="kpi-trend" id="kpiTrendVariacion"></span>
                        </div>
                    </div>
                </div>
                
                <!-- KPI 3: Botón Ver Evolución -->
                <div class="kpi-card kpi-button-card" style="cursor: pointer;" id="btnVerEvolucion" title="Ver gráfico de evolución">
                    <div class="kpi-icon kpi-success">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-label">Ver Evolución</div>
                        <div class="kpi-value" style="font-size: 18px;">
                            Gráfico comparativo
                            <i class="bi bi-chevron-right" style="font-size: 20px; margin-left: 8px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-section" id="tableSection" style="display: none;">
                <div class="table-header">
                    <h2><i class="bi bi-table"></i> Detalle de Costos</h2>
                    <div class="table-actions">
                        <button class="legend-btn" id="btnLeyenda" title="Ver leyenda de colores">
                            <i class="bi bi-info-circle"></i>
                            Leyenda
                        </button>
                        <button class="export-btn" id="btnExportar" title="Exportar a Excel">
                            <i class="bi bi-file-earmark-excel"></i>
                            Exportar
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="cost-table" id="tablaCostoOcupacion">
                        <thead>
                            <tr id="headerRow">
                                <th class="fixed-column"><i class="bi bi-list-ul"></i> Concepto</th>
                                <!-- Columnas dinámicas de meses se agregan aquí -->
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- Filas dinámicas se agregan aquí -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Estado inicial -->
            <div class="empty-state" id="emptyState">
                <div class="empty-icon">
                    <i class="bi bi-search"></i>
                </div>
                <h3>Seleccione una sucursal para comenzar</h3>
                <p>Elija una sucursal y rango de fechas para visualizar el análisis de costos de ocupación</p>
            </div>
                </div>
                <!-- Fin Pestaña 1 -->

                <!-- Pestaña 2: Comparar Sucursales -->
                <div class="tab-pane fade" id="comparar" role="tabpanel" aria-labelledby="comparar-tab">
                    <?php include 'components/compararSucursales.php'; ?>
                </div>

                <!-- Pestaña 3: Controlar Gastos -->
                <div class="tab-pane fade" id="controlar" role="tabpanel" aria-labelledby="controlar-tab">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-wrench-adjustable" style="font-size: 4rem; color: #bdc3c7;"></i>
                        </div>
                        <h3>Módulo en desarrollo</h3>
                        <p>Esta funcionalidad estará disponible próximamente</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir Modal de Evolución -->
    <?php include 'components/modalEvolucion.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <script>
        $(document).ready(function() {
            // Ajustar estilos del toggle
            setTimeout(() => {
                const toggle = document.querySelector(".toggle");
                if (toggle) {
                    toggle.style.width = "90px";
                    toggle.style.height = "38px";
                }
                
                const toggleOn = document.querySelector(".toggle-on");
                const toggleOff = document.querySelector(".toggle-off");
                
                if (toggleOn) {
                    toggleOn.style.backgroundImage = "none";
                    toggleOn.style.backgroundSize = "auto";
                    toggleOn.style.width = "auto";
                    toggleOn.style.height = "auto";
                    toggleOn.style.minWidth = "45px";
                    toggleOn.style.minHeight = "34px";
                    toggleOn.style.fontSize = "12px";
                    toggleOn.style.fontWeight = "bold";
                    toggleOn.style.color = "white";
                    toggleOn.style.display = "flex";
                    toggleOn.style.alignItems = "center";
                    toggleOn.style.justifyContent = "center";
                    toggleOn.style.textShadow = "none";
                }
                if (toggleOff) {
                    toggleOff.style.backgroundImage = "none";
                    toggleOff.style.backgroundSize = "auto";
                    toggleOff.style.width = "auto";
                    toggleOff.style.height = "auto";
                    toggleOff.style.minWidth = "45px";
                    toggleOff.style.minHeight = "34px";
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

        const cambiarEntorno = (toggle) => {
            const entorno = toggle.checked ? 0 : 1;
            
            Swal.fire({
                title: 'Cambiando entorno',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "Controller/cambiarEntorno.php",
                method: "POST",
                data: { entorno: entorno },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        setTimeout(() => {
                            location.reload();
                        }, 100);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cambiar entorno',
                            text: data.message || 'Error desconocido',
                            confirmButtonText: "Entendido",
                            confirmButtonColor: "#e74c3c"
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al cambiar entorno',
                        text: 'No se pudo cambiar el entorno. Intente nuevamente.',
                        confirmButtonText: "Entendido",
                        confirmButtonColor: "#e74c3c"
                    });
                }
            });
        };
    </script>
    
    <!-- Custom JS -->
    <script src="js/modalEvolucion.js"></script>
    <script src="js/costoOcupacion.js"></script>
    <script src="js/compararSucursales.js"></script>
</body>
</html>