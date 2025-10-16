<?php
$titulo_pagina = 'Directores';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egresos - Directores</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
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
                    <i class="bi bi-person-badge"></i> Panel de Directores
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <!-- Botón actualizar con icono Bootstrap sutil -->
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Pestañas de navegación -->
            <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="solicitud-tab" data-bs-toggle="tab" 
                            data-bs-target="#solicitud" type="button" role="tab">
                        <i class="bi bi-file-earmark-plus"></i> Nueva Solicitud
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="listado-tab" data-bs-toggle="tab" 
                            data-bs-target="#listado" type="button" role="tab">
                        <i class="bi bi-list-ul"></i> Mis Solicitudes
                    </button>
                </li>
            </ul>
            
            <!-- Contenido de pestañas -->
            <div class="tab-content" id="mainTabsContent">
                <!-- Pestaña Nueva Solicitud -->
                <div class="tab-pane fade show active" id="solicitud" role="tabpanel">
                    <div class="pt-3 pb-4">
                        <h3 class="mb-4">Nueva Solicitud de Egreso</h3>
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <form id="formSolicitud">
                                    <div class="mb-3">
                                        <label for="idDirector" class="form-label">
                                            Director <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="idDirector" required>
                                            <option value="">Seleccione un director</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="motivoSolicitud" class="form-label">
                                            Motivo <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="motivoSolicitud" required>
                                            <option value="">Seleccione un motivo</option>
                                            <option value="COMPRA_PERSONAL">Compra Personal</option>
                                            <option value="RETIRO_DINERO">Retiro de Dinero</option>
                                        </select>
                                        <div class="form-text">
                                            <strong>Compra Personal:</strong> Pago de facturas por compras personales (requiere adjuntar factura)<br>
                                            <strong>Retiro de Dinero:</strong> Transferencias por retiros de directores
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="importeSolicitud" class="form-label">
                                            Importe SIN IVA <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control importe-input" 
                                                   id="importeSolicitud" placeholder="0" required>
                                        </div>
                                        <div class="form-text">
                                            Ingrese el importe en pesos SIN IVA. 
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="observacionesSolicitud" class="form-label">
                                            Observaciones
                                        </label>
                                        <textarea class="form-control" id="observacionesSolicitud" 
                                                  rows="3" placeholder="Detalles adicionales (opcional)"></textarea>
                                    </div>
                                    
                                    <!-- Alerta de factura requerida -->
                                    <div id="alertaFactura" class="alert alert-warning d-none">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <strong>Importante:</strong> Para compras personales es obligatorio adjuntar la factura.
                                    </div>
                                    
                                    <!-- Área de carga de archivos -->
                                    <div id="divArchivos" class="mb-3 d-none">
                                        <label class="form-label">
                                            Adjuntar Factura <span class="text-danger">*</span>
                                        </label>
                                        
                                        <!-- Interfaz para Escritorio -->
                                        <div class="d-none d-md-block mb-3">
                                            <button type="button" class="btn btn-outline-primary w-100" 
                                                    onclick="document.getElementById('archivoSolicitud').click()">
                                                <i class="bi bi-paperclip"></i> Adjuntar Archivo
                                            </button>
                                        </div>
                                        
                                        <!-- Interfaz para Móvil -->
                                        <div class="d-md-none">
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-outline-primary w-100" 
                                                            onclick="document.getElementById('archivoSolicitud').click()">
                                                        <i class="bi bi-folder-open"></i> Galería
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-outline-success w-100" 
                                                            onclick="document.getElementById('camaraSolicitud').click()">
                                                        <i class="bi bi-camera"></i> Cámara
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Input para galería (escritorio y móvil) -->
                                        <input type="file" id="archivoSolicitud" class="d-none" 
                                               accept="image/jpeg,image/jpg,image/png,image/gif,application/pdf" 
                                               multiple>
                                        
                                        <!-- Input para cámara (solo móvil) -->
                                        <input type="file" id="camaraSolicitud" class="d-none d-md-none" 
                                               accept="image/*" 
                                               capture="environment">
                                        
                                        <div id="listaArchivos" class="mt-3">
                                            <p class="text-muted"><small>No hay archivos adjuntos</small></p>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-send"></i> Enviar Solicitud
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <i class="bi bi-info-circle"></i> Información
                                    </div>
                                    <div class="card-body">
                                        <h6>¿Cómo funciona?</h6>
                                        <ol class="small">
                                            <li>Selecciona tu nombre de la lista</li>
                                            <li>Elige el motivo del egreso</li>
                                            <li>Ingresa el importe</li>
                                            <li>Si es compra personal, adjunta la factura</li>
                                            <li>Envía la solicitud</li>
                                        </ol>
                                        
                                        <hr>
                                        
                                        <h6>Estados de solicitud</h6>
                                        <ul class="list-unstyled small">
                                            <li class="mb-2">
                                                <span class="badge estado-solicitado">Solicitado</span>
                                                <br>
                                                <small>Solicitud enviada, pendiente de revisión</small>
                                            </li>
                                            <li class="mb-2">
                                                <span class="badge estado-cargado">Cargado</span>
                                                <br>
                                                <small>Factura cargada en el sistema</small>
                                            </li>
                                            <li>
                                                <span class="badge estado-pagado">Pagado</span>
                                                <br>
                                                <small>Pago realizado</small>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pestaña Listado -->
                <div class="tab-pane fade" id="listado" role="tabpanel">
                    <div class="pt-3 pb-4">
                        <h3 class="mb-4">Mis Solicitudes</h3>
                        
                        <!-- Filtros -->
                        <div class="filtros-container">
                            <h5 class="mb-3">
                                <i class="bi bi-funnel"></i> Filtros
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="filtroDirector" class="form-label">Director</label>
                                    <select class="form-select form-select-sm" id="filtroDirector">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroEstado" class="form-label">Estado</label>
                                    <select class="form-select form-select-sm" id="filtroEstado">
                                        <option value="">Todos</option>
                                        <option value="SOLICITADO">Solicitado</option>
                                        <option value="CARGADO">Cargado</option>
                                        <option value="PAGADO">Pagado</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="filtroFechaDesde" class="form-label">Desde</label>
                                    <input type="date" class="form-control form-control-sm" id="filtroFechaDesde">
                                </div>
                                <div class="col-md-2">
                                    <label for="filtroFechaHasta" class="form-label">Hasta</label>
                                    <input type="date" class="form-control form-control-sm" id="filtroFechaHasta">
                                </div>
                                <div class="col-md-2 d-flex align-items-end gap-1">
                                    <button class="btn btn-sm btn-primary" onclick="aplicarFiltros()">
                                        <i class="bi bi-search"></i> Filtrar
                                    </button>
                                    <button class="btn btn-sm btn-secondary" onclick="limpiarFiltros()">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lista de solicitudes -->
                        <div id="listadoSolicitudes" class="mt-4">
                            <!-- Contenido dinámico -->
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
    
    <!-- Scripts personalizados -->
    <script src="js/modal_global.js?v=<?php echo time(); ?>"></script>
    <script src="js/solicitud_listado.js?v=<?php echo time(); ?>"></script>
    <script src="js/directores_solicitud.js?v=<?php echo time(); ?>"></script>
    
    <script>
        // Cargar directores en filtro cuando se muestra listado
        document.getElementById('listado-tab')?.addEventListener('shown.bs.tab', async function() {
            // Cargar solicitudes
            if (typeof cargarSolicitudes === 'function') {
                cargarSolicitudes();
            }
            
            // Cargar directores para filtro
            try {
                const response = await fetch('controller/solicitud_controller.php?accion=obtener_directores');
                const result = await response.json();
                
                if (result.success) {
                    const selectFiltro = document.getElementById('filtroDirector');
                    const valorActual = selectFiltro.value;
                    selectFiltro.innerHTML = '<option value="">Todos</option>';
                    
                    result.data.forEach(director => {
                        const option = document.createElement('option');
                        option.value = director.id_director;
                        option.textContent = director.nombre_director;
                        selectFiltro.appendChild(option);
                    });
                    
                    selectFiltro.value = valorActual;
                }
            } catch (error) {
                console.error('Error al cargar directores para filtro:', error);
            }
        });
    </script>
</body>
</html>