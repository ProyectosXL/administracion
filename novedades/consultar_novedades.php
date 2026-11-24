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
    <link href="css/novedades_estilos.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Contenedor de alertas -->
    <div id="alertas-container" class="container-fluid consultar-wrapper px-3 px-xl-4 mt-3"></div>

    <!-- Contenido principal -->
    <div class="container-fluid consultar-wrapper px-3 px-xl-4 pb-5">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <div>
                <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-search text-primary me-2"></i>Consultar Novedades</h4>
                <small class="text-muted">Visualice, filtre, ordene y exporte las novedades registradas</small>
            </div>
        </div>
        
        <!-- Filtros de búsqueda -->
        <div class="search-filters">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2 text-primary"></i>
                    Filtros de Búsqueda
                </h5>
            </div>
            
            <form id="form-filtros">
                <!-- Fila 1: Selectores principales -->
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <label for="filtro-empleado" class="form-label small fw-semibold text-muted mb-1">
                            <i class="fas fa-user me-1"></i>Empleado / Legajo
                        </label>
                        <select class="form-select" id="filtro-empleado" name="filtro-empleado">
                            <option value="">Buscar empleado...</option>
                        </select>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <label for="filtro-centro-costos" class="form-label small fw-semibold text-muted mb-1">
                            <i class="fas fa-building me-1"></i>Centro de Costos
                        </label>
                        <select class="form-select" id="filtro-centro-costos">
                            <option value="">Todos los centros</option>
                        </select>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <label for="filtro-tipo" class="form-label small fw-semibold text-muted mb-1">
                            <i class="fas fa-tags me-1"></i>Tipo de Novedad
                        </label>
                        <select class="form-select" id="filtro-tipo" name="filtro-tipo">
                            <option value="">Todos los tipos</option>
                        </select>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <label for="filtro-estado" class="form-label small fw-semibold text-muted mb-1">
                            <i class="fas fa-flag me-1"></i>Estado
                        </label>
                        <select class="form-select" id="filtro-estado">
                            <option value="">Todos los estados</option>
                            <option value="1">Enviada</option>
                            <option value="2">En Revisión</option>
                            <option value="3">Aprobada</option>
                            <option value="4">A Revisar</option>
                            <option value="5">Procesada</option>
                        </select>
                    </div>
                </div>

                <!-- Fila 2: Fechas, filtros rápidos y botones de acción -->
                <div class="row g-3 align-items-end mt-1">
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label for="fecha-desde" class="form-label small fw-semibold text-muted mb-1">
                            <i class="fas fa-calendar-alt me-1"></i>Registro Desde
                        </label>
                        <input type="date" class="form-control" id="fecha-desde">
                    </div>
                    
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label for="fecha-hasta" class="form-label small fw-semibold text-muted mb-1">
                            <i class="fas fa-calendar-alt me-1"></i>Registro Hasta
                        </label>
                        <input type="date" class="form-control" id="fecha-hasta">
                    </div>

                    <div class="col-xl-5 col-md-12">
                        <label class="form-label small fw-semibold text-muted mb-1 d-block">
                            Filtros rápidos de período
                        </label>
                        <div class="d-flex flex-wrap gap-1">
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
                    </div>
                    
                    <div class="col-xl-3 col-md-12 d-flex justify-content-xl-end gap-2">
                        <button type="button" class="btn btn-primary px-3" onclick="aplicarFiltros()">
                            <i class="fas fa-search me-1"></i>
                            Buscar
                        </button>
                        <button type="button" class="btn btn-outline-secondary px-3" onclick="limpiarFiltros()">
                            <i class="fas fa-eraser me-1"></i>
                            Limpiar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Estadísticas de la búsqueda -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-sm-6">
                <div class="card stat-card-consult text-center border-primary h-100">
                    <div class="card-body py-3">
                        <i class="fas fa-list-alt fa-2x text-primary mb-2"></i>
                        <h4 class="text-primary mb-0" id="total-resultados">0</h4>
                        <small class="text-muted">Total Novedades</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card stat-card-consult text-center border-success h-100">
                    <div class="card-body py-3">
                        <i class="fas fa-building fa-2x text-success mb-2"></i>
                        <h4 class="text-success mb-0" id="total-centros-costos-filtro">0</h4>
                        <small class="text-muted">Centros de Costos</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card stat-card-consult text-center border-warning h-100">
                    <div class="card-body py-3">
                        <i class="fas fa-users fa-2x text-warning mb-2"></i>
                        <h4 class="text-warning mb-0" id="total-empleados-filtro">0</h4>
                        <small class="text-muted">Empleados</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card stat-card-consult text-center border-info h-100">
                    <div class="card-body py-3">
                        <i class="fas fa-dollar-sign fa-2x text-info mb-2"></i>
                        <h4 class="text-info mb-0" id="total-valores">$0</h4>
                        <small class="text-muted">Valores Monetarios Totales</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="card card-resultados">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 px-3">
                <h5 class="card-title mb-0">
                    <i class="fas fa-table me-2"></i>
                    Novedades Encontradas
                </h5>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-light" onclick="exportarExcel()">
                            <i class="fas fa-file-excel text-success me-1"></i>Exportar Excel
                        </button>
                        <button type="button" class="btn btn-light" onclick="imprimirReporte()">
                            <i class="fas fa-print text-primary me-1"></i>Imprimir
                        </button>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light active" onclick="cambiarVista('tabla')" id="btn-vista-tabla">
                            <i class="fas fa-table me-1"></i>Tabla
                        </button>
                        <button type="button" class="btn btn-outline-light" onclick="cambiarVista('tarjetas')" id="btn-vista-tarjetas">
                            <i class="fas fa-th me-1"></i>Tarjetas
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <!-- Vista de tabla -->
                <div id="vista-tabla" class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tabla-novedades">
                        <thead class="table-light">
                            <tr>
                                <th class="sortable text-nowrap" data-column="fecha_registro" style="cursor: pointer;">
                                    Registro <span class="sort-arrow"><i class="fas fa-sort text-muted"></i></span>
                                </th>
                                <th class="sortable text-nowrap" data-column="legajo" style="cursor: pointer;">
                                    Legajo <span class="sort-arrow"><i class="fas fa-sort text-muted"></i></span>
                                </th>
                                <th class="sortable text-nowrap" data-column="empleado" style="cursor: pointer;">
                                    Empleado <span class="sort-arrow"><i class="fas fa-sort text-muted"></i></span>
                                </th>
                                <th class="sortable text-nowrap" data-column="centro_costos" style="cursor: pointer;">
                                    Centro Costo <span class="sort-arrow"><i class="fas fa-sort text-muted"></i></span>
                                </th>
                                <th class="sortable text-nowrap" data-column="tipo" style="cursor: pointer;">
                                    Novedad <span class="sort-arrow"><i class="fas fa-sort text-muted"></i></span>
                                </th>
                                <th class="sortable text-nowrap text-center" data-column="periodo" style="cursor: pointer;">
                                    Período <span class="sort-arrow"><i class="fas fa-sort text-muted"></i></span>
                                </th>
                                <th class="sortable text-nowrap" data-column="vigencia" style="cursor: pointer;">
                                    Vig. desde <span class="sort-arrow"><i class="fas fa-sort text-muted"></i></span>
                                </th>
                                <th class="sortable text-nowrap" data-column="vigencia_hasta" style="cursor: pointer;">
                                    Vig. hasta <span class="sort-arrow"><i class="fas fa-sort text-muted"></i></span>
                                </th>
                                <th class="sortable text-nowrap" data-column="valor" style="cursor: pointer;">
                                    Valor <span class="sort-arrow"><i class="fas fa-sort text-muted"></i></span>
                                </th>
                                <th class="sortable text-nowrap" data-column="estado" style="cursor: pointer;">
                                    Estado <span class="sort-arrow"><i class="fas fa-sort text-muted"></i></span>
                                </th>
                                <th class="text-nowrap text-center">Acciones</th>
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
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <!-- Manual de Usuario Modal -->
    <?php include 'components/manual_modal.php'; ?>

</body>
</html>