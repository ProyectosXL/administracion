<?php
$titulo_pagina = 'Contabilidad';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egresos - Contabilidad</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" crossorigin="anonymous">
    
    <!-- Estilo específico para iconos Bootstrap -->
    <style>
        .bi {
            font-family: "bootstrap-icons" !important;
            font-style: normal;
            font-weight: normal;
            font-variant: normal;
            text-transform: none;
            line-height: 1;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .bi-arrow-clockwise::before {
            content: "\f128";
        }
        
        .filters-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
    </style>
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/solicitudes_estilos.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>
    
    <div class="container-fluid">
        <main class="px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-1 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-calculator"></i> Portal de Contabilidad
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="cargarHistorialPagadas()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                    </button>
                </div>
            </div>
            
            <!-- Información de la pantalla -->
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Portal de Contabilidad:</strong> Consulta de egresos pagados.
                <br>
                <small>
                    Aquí podrá visualizar el historial completo de compras personales y retiros de dinero que ya fueron pagados por Tesorería.
                    Utilice los filtros de fecha para buscar registros específicos y exporte los datos a Excel para su análisis.
                </small>
            </div>
            
            <!-- Pestañas -->
            <ul class="nav nav-tabs mb-3" id="contabilidadTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="compras-tab" data-bs-toggle="tab" 
                            data-bs-target="#compras" type="button" role="tab">
                        <i class="bi bi-receipt"></i> Compras Personales Pagadas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="retiros-tab" data-bs-toggle="tab" 
                            data-bs-target="#retiros" type="button" role="tab">
                        <i class="bi bi-cash-coin"></i> Retiros de Dinero Pagados
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="contabilidadTabsContent">
                <!-- Pestaña Compras Personales -->
                <div class="tab-pane fade show active" id="compras" role="tabpanel">
                    <!-- Filtros para Compras -->
                    <div class="filters-card">
                        <h6 class="mb-3">
                            <i class="bi bi-funnel"></i> Filtros de Búsqueda
                        </h6>
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="form-label">Fecha Desde:</label>
                                <input type="date" class="form-control" id="fechaDesdeCompras">
                            </div>
                            <div class="filter-group">
                                <label class="form-label">Fecha Hasta:</label>
                                <input type="date" class="form-control" id="fechaHastaCompras">
                            </div>
                            <div class="filter-actions">
                                <button class="btn btn-primary" onclick="filtrarComprasPagadas()">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                                <button class="btn btn-secondary" onclick="limpiarFiltrosCompras()">
                                    <i class="bi bi-x-circle"></i> Limpiar
                                </button>
                                <button class="btn btn-success" onclick="exportarComprasExcel()">
                                    <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Historial de Compras Pagadas -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-archive"></i> Historial de Compras Personales Pagadas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="resumenCompras" class="mb-3">
                                <!-- Resumen de resultados -->
                            </div>
                            <div id="historialComprasPagadas">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Cargando historial...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pestaña Retiros de Dinero -->
                <div class="tab-pane fade" id="retiros" role="tabpanel">
                    <!-- Filtros para Retiros -->
                    <div class="filters-card">
                        <h6 class="mb-3">
                            <i class="bi bi-funnel"></i> Filtros de Búsqueda
                        </h6>
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="form-label">Fecha Desde:</label>
                                <input type="date" class="form-control" id="fechaDesdeRetiros">
                            </div>
                            <div class="filter-group">
                                <label class="form-label">Fecha Hasta:</label>
                                <input type="date" class="form-control" id="fechaHastaRetiros">
                            </div>
                            <div class="filter-actions">
                                <button class="btn btn-primary" onclick="filtrarRetirosPagados()">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                                <button class="btn btn-secondary" onclick="limpiarFiltrosRetiros()">
                                    <i class="bi bi-x-circle"></i> Limpiar
                                </button>
                                <button class="btn btn-success" onclick="exportarRetirosExcel()">
                                    <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Historial de Retiros Pagados -->
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-archive"></i> Historial de Retiros de Dinero Pagados
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="resumenRetiros" class="mb-3">
                                <!-- Resumen de resultados -->
                            </div>
                            <div id="historialRetirosPagados">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-info" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Cargando historial...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modales -->
    <?php include 'components/modales.php'; ?>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SheetJS para exportar Excel -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="js/modal_global.js?v=<?php echo time(); ?>"></script>
    <script src="js/contabilidad_listado.js?v=<?php echo time(); ?>"></script>
</body>
</html>
