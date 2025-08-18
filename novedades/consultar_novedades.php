<?php
/**
 * Página para consultar novedades
 * /novedades/consultar_novedades.php
 */
require_once 'includes/periodo_helper.php';
$periodoInfo = PeriodoHelper::getPeriodoActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Novedades - Sistema RRHH</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- CSS personalizado -->
    <link href="css/novedades_estilos.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Contenedor de alertas -->
    <div id="alertas-container" class="container mt-3"></div>

    <!-- Header de la página -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>
                        <i class="fas fa-search me-3"></i>
                        Consultar Novedades
                    </h1>
                    <p class="lead mb-0">
                        Visualice y filtre las novedades registradas para el período actual
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="periodo-badge">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php echo $periodoInfo['badge']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container">
        
        <!-- Filtros de búsqueda -->
        <div class="search-filters">
            <h5 class="mb-3">
                <i class="fas fa-filter me-2"></i>
                Filtros de Búsqueda
            </h5>
            
            <form id="form-filtros">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label for="filtro-empleado" class="form-label">
                            <i class="fas fa-user me-2"></i>Empleado/Legajo
                        </label>
                        <select class="form-select" id="filtro-empleado" name="filtro-empleado">
                            <option value="">Buscar empleado...</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="filtro-sucursal" class="form-label">
                            <i class="fas fa-building me-2"></i>Sucursal
                        </label>
                        <select class="form-select" id="filtro-sucursal">
                            <option value="">Todas las sucursales</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="filtro-tipo" class="form-label">
                            <i class="fas fa-tags me-2"></i>Tipo de Novedad
                        </label>
                        <select class="form-select" id="filtro-tipo">
                            <option value="">Todos los tipos</option>
                        </select>
                    </div>
                    
                    <div class="col-md-1">
                        <label for="filtro-estado" class="form-label">
                            <i class="fas fa-flag me-2"></i>Estado
                        </label>
                        <select class="form-select" id="filtro-estado">
                            <option value="">Todos los estados</option>
                            <option value="1">Enviada</option>
                            <option value="2">En Revisión</option>
                            <option value="3">Aprobada</option>
                            <option value="4">Rechazada</option>
                            <option value="5">Procesada</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="fecha-desde" class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Fecha Registro Desde
                        </label>
                        <input type="date" class="form-control" id="fecha-desde">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="fecha-hasta" class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Fecha Registro Hasta
                        </label>
                        <input type="date" class="form-control" id="fecha-hasta">
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <div class="btn-group-vertical w-100">
                            <button type="button" class="btn btn-primary btn-sm" onclick="aplicarFiltros()">
                                <i class="fas fa-search me-1"></i>
                                Buscar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">
                                <i class="fas fa-eraser me-1"></i>
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3 mt-2">
                    <div class="col-md-12 d-flex justify-content-between">
                        <!-- Filtros rápidos por período -->
                        <div class="btn-group" role="group">
                            <span class="text-muted me-3 align-self-center small">Filtros rápidos:</span>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="filtrarPeriodo('hoy')">Hoy</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="filtrarPeriodo('semana')">Esta semana</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="filtrarPeriodo('mes')">Este mes</button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="filtrarPorPeriodoReal('actual')">
                                <i class="fas fa-calendar-check me-1"></i>Período actual
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="filtrarPorPeriodoReal('siguiente')">
                                <i class="fas fa-forward me-1"></i>Período siguiente
                            </button>
                        </div>
                        
                        <!-- Botones de exportar -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="exportarExcel()">
                                <i class="fas fa-file-excel me-1"></i>
                                Exportar Excel
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="imprimirReporte()">
                                <i class="fas fa-print me-1"></i>
                                Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Estadísticas de la búsqueda -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center border-primary">
                    <div class="card-body">
                        <i class="fas fa-list-alt fa-2x text-primary mb-2"></i>
                        <h4 class="text-primary mb-0" id="total-resultados">0</h4>
                        <small class="text-muted">Total Novedades</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <i class="fas fa-building fa-2x text-success mb-2"></i>
                        <h4 class="text-success mb-0" id="total-sucursales-filtro">0</h4>
                        <small class="text-muted">Sucursales</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-warning mb-2"></i>
                        <h4 class="text-warning mb-0" id="total-empleados-filtro">0</h4>
                        <small class="text-muted">Empleados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-info">
                    <div class="card-body">
                        <i class="fas fa-dollar-sign fa-2x text-info mb-2"></i>
                        <h4 class="text-info mb-0" id="total-valores">$0</h4>
                        <small class="text-muted">Valores Totales</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-table me-2"></i>
                    Novedades Encontradas
                </h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="cambiarVista('tabla')" id="btn-vista-tabla">
                        <i class="fas fa-table"></i> Tabla
                    </button>
                    <button class="btn btn-outline-primary" onclick="cambiarVista('tarjetas')" id="btn-vista-tarjetas">
                        <i class="fas fa-th"></i> Tarjetas
                    </button>
                </div>
            </div>
            
            <div class="card-body p-0">
                <!-- Vista de tabla -->
                <div id="vista-tabla" class="table-responsive">
                    <table class="table table-hover mb-0" id="tabla-novedades">
                        <thead class="table-light">
                            <tr>
                                <th class="sortable" data-column="empleado" style="cursor: pointer;">
                                    Empleado 
                                    <span class="sort-arrow">
                                        <i class="fas fa-sort text-muted"></i>
                                    </span>
                                </th>
                                <th class="sortable" data-column="sucursal" style="cursor: pointer;">
                                    Sucursal 
                                    <span class="sort-arrow">
                                        <i class="fas fa-sort text-muted"></i>
                                    </span>
                                </th>
                                <th class="sortable" data-column="tipo" style="cursor: pointer;">
                                    Tipo de Novedad 
                                    <span class="sort-arrow">
                                        <i class="fas fa-sort text-muted"></i>
                                    </span>
                                </th>
                                <th class="sortable" data-column="periodo" style="cursor: pointer;">
                                    Período 
                                    <span class="sort-arrow">
                                        <i class="fas fa-sort text-muted"></i>
                                    </span>
                                </th>
                                <th class="sortable" data-column="vigencia" style="cursor: pointer;">
                                    Vigencia 
                                    <span class="sort-arrow">
                                        <i class="fas fa-sort text-muted"></i>
                                    </span>
                                </th>
                                <th class="sortable" data-column="valor" style="cursor: pointer;">
                                    Valor 
                                    <span class="sort-arrow">
                                        <i class="fas fa-sort text-muted"></i>
                                    </span>
                                </th>
                                <th class="sortable" data-column="fecha_registro" style="cursor: pointer;">
                                    Fecha Registro 
                                    <span class="sort-arrow">
                                        <i class="fas fa-sort text-muted"></i>
                                    </span>
                                </th>
                                <th class="sortable" data-column="estado" style="cursor: pointer;">
                                    Estado 
                                    <span class="sort-arrow">
                                        <i class="fas fa-sort text-muted"></i>
                                    </span>
                                </th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-novedades">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- Vista de tarjetas -->
                <div id="vista-tarjetas" style="display: none;" class="p-3">
                    <div id="container-tarjetas" class="row">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>

                <!-- Loading -->
                <div id="loading-resultados" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <div class="mt-2">Cargando novedades...</div>
                </div>

                <!-- Sin resultados -->
                <div id="sin-resultados" class="no-data" style="display: none;">
                    <i class="fas fa-search"></i>
                    <h6>No se encontraron novedades</h6>
                    <p>No hay novedades que coincidan con los filtros aplicados.</p>
                    <button class="btn btn-primary" onclick="limpiarFiltros()">
                        <i class="fas fa-eraser me-1"></i>
                        Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Paginación -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Mostrando <span id="resultados-desde">0</span> - <span id="resultados-hasta">0</span> 
                de <span id="resultados-total">0</span> resultados
            </div>
            
            <nav aria-label="Paginación">
                <ul class="pagination mb-0" id="paginacion">
                    <!-- Se genera dinámicamente -->
                </ul>
            </nav>
        </div>
    </div>

    <!-- Modal detalle de novedad -->
    <div class="modal fade" id="modalDetalleNovedad" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Detalle de Novedad
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenido-detalle-novedad">
                    <!-- Se llena dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" onclick="imprimirDetalle()">
                        <i class="fas fa-print me-1"></i>
                        Imprimir
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (requerido para Select2) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/novedades_main.js?v=<?php echo time(); ?>"></script>
    <script src="js/consultar_novedades.js?v=<?php echo time(); ?>"></script>
    <script src="js/manual.js?v=<?php echo time(); ?>"></script>
    <script src="js/modal_fix.js?v=<?php echo time(); ?>"></script>

    <!-- Manual de Usuario Modal -->
    <?php include 'components/manual_modal.php'; ?>

</body>
</html>