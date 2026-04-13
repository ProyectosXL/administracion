<?php

session_start();

// Convertir el nombre del usuario a formato capitalizado (primera letra en mayúscula)
$usuario = isset($_SESSION['descLocal']) ? ucwords(strtolower($_SESSION['descLocal'])) : 'Usuario';
$id_usuario = $_SESSION['idUsuario'] ?? null;

$titulo_pagina = $usuario;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egresos - Directores</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
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
                        <!-- Botón volver -->
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.href='../../../ppp/index.php'">
                            <i class="bi bi-arrow-left me-1"></i>Volver
                        </button>
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
                                        <label class="form-label">
                                            Director
                                        </label>
                                        <div class="p-2 bg-light rounded border">
                                            <strong><?php echo htmlspecialchars($usuario); ?></strong>
                                        </div>
                                        <input type="hidden" id="nombreDirectorSession" 
                                               value="<?php echo htmlspecialchars($usuario); ?>">
                                        <input type="hidden" id="idDirectorSession" 
                                               value="<?php echo htmlspecialchars($id_usuario); ?>">
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
                                    
                                    <!-- Sección de tipo de asignación (solo para RETIRO_DINERO) -->
                                    <div id="divTipoAsignacion" class="mb-3 d-none">
                                        <label class="form-label">
                                            Tipo de Asignación <span class="text-danger">*</span>
                                        </label>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="tipoAsignacion" 
                                                           id="tipoIndividual" value="INDIVIDUAL" checked>
                                                    <label class="form-check-label" for="tipoIndividual">
                                                        Individual
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="tipoAsignacion" 
                                                           id="tipoMultiple" value="MULTIPLE">
                                                    <label class="form-check-label" for="tipoMultiple">
                                                        Múltiple
                                                    </label>
                                                </div>
                                            </div>
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
                                    
                                    <!-- Sección de distribución entre directores (solo para tipo MULTIPLE) -->
                                    <div id="divDistribucion" class="mb-3 d-none">
                                        <h5 class="border-bottom pb-2 mb-3">
                                            <i class="bi bi-people-fill"></i> Distribución entre Directores
                                        </h5>
                                        
                                        <div class="alert alert-info mb-3">
                                            <i class="bi bi-info-circle"></i>
                                            Ingrese manualmente el importe para cada director. La suma debe coincidir exactamente con el importe total.
                                        </div>
                                        
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="calcularDistribucionEquitativa()">
                                                <i class="bi bi-calculator"></i> Distribuir equitativamente
                                            </button>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover" id="tablaDistribucion">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Director</th>
                                                        <th style="width: 200px;">Importe Asignado</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="cuerpoTablaDistribucion">
                                                    <!-- Se llenará dinámicamente -->
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <th class="text-end">TOTAL:</th>
                                                        <th>
                                                            <span id="totalDistribuido" class="fw-bold">$ 0</span>
                                                        </th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        
                                        <div id="alertaValidacion" class="alert alert-danger d-none">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <strong>Error:</strong> La suma de los importes asignados 
                                            (<span id="sumaActual">0</span>) no coincide con el importe total 
                                            (<span id="importeTotal">0</span>).
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="observacionesSolicitud" class="form-label">
                                            Observaciones
                                        </label>
                                        <textarea class="form-control" id="observacionesSolicitud" 
                                                  rows="3" placeholder="Detalles adicionales (opcional)"></textarea>
                                    </div>
                                    
                                    <!-- Sección de datos de proveedor (solo para compras personales) -->
                                    <div id="divProveedor" class="mb-3 d-none">
                                        <h5 class="border-bottom pb-2 mb-3">
                                            <i class="bi bi-building"></i> Datos del Proveedor
                                        </h5>
                                        
                                        <div class="mb-3">
                                            <label for="proveedorSelect" class="form-label">
                                                Proveedor <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" id="proveedorSelect" style="width: 100%;">
                                                <option value=""></option>
                                            </select>
                                            <div class="form-text">
                                                Busque por nombre o CUIT del proveedor. Escriba al menos 2 caracteres para buscar.
                                            </div>
                                        </div>
                                        
                        <div class="mb-3">
                            <label for="cbuProveedor" class="form-label">
                                CBU
                            </label>
                            <input type="text" class="form-control" id="cbuProveedor" 
                                   placeholder="22 dígitos (opcional)" maxlength="22" pattern="\d{22}">
                            <div class="form-text">
                                CBU de 22 dígitos (opcional - se completará automáticamente si el proveedor lo tiene cargado)
                            </div>
                            <div id="alertaCBU" class="alert alert-info d-none mt-2">
                                <i class="bi bi-info-circle"></i>
                                <strong>Información:</strong> Este proveedor aún no tiene CBU cargado en la base. Puede completarlo si lo conoce.
                            </div>
                        </div>                                        <div class="mb-3">
                                            <label for="descripcionCbuProveedor" class="form-label">
                                                Descripción CBU
                                            </label>
                                            <input type="text" class="form-control" id="descripcionCbuProveedor" 
                                                   placeholder="Ej: Cuenta Corriente Banco Galicia">
                                            <div class="form-text">
                                                Descripción de la cuenta (opcional, se completará automáticamente si el proveedor la tiene cargada)
                                            </div>
                                        </div>
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
                                                    <button type="button" class="btn btn-outline-secondary w-100" 
                                                            onclick="document.getElementById('archivoSolicitud').click()">
                                                        <i class="bi bi-image"></i> Galería
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-outline-primary w-100" 
                                                            onclick="document.getElementById('camaraSolicitud').click()">
                                                        <i class="bi bi-camera"></i> Cámara
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="form-text mb-2">
                                                <small>
                                                    <span class="text-primary"><i class="bi bi-camera"></i> Cámara:</span> Toma foto directamente<br>
                                                    <span class="text-secondary"><i class="bi bi-image"></i> Galería:</span> Selecciona imagen o PDF guardado
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Input para galería (escritorio y móvil) - PERMITE MÚLTIPLES ARCHIVOS -->
                                        <input type="file" id="archivoSolicitud" class="d-none" 
                                               accept="image/jpeg,image/jpg,image/png,image/gif,application/pdf" 
                                               multiple>
                                        
                                        <!-- Input para cámara (solo móvil) - CAPTURA DIRECTA DESDE CÁMARA TRASERA -->
                                        <input type="file" id="camaraSolicitud" class="d-none" 
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
                                    <label for="filtroEstado" class="form-label">Estado</label>
                                    <select class="form-select form-select-sm" id="filtroEstado">
                                        <option value="">Todos</option>
                                        <option value="SOLICITADO">Solicitado</option>
                                        <option value="CARGADO">Cargado</option>
                                        <option value="PAGADO">Pagado</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroFechaDesde" class="form-label">Desde</label>
                                    <input type="date" class="form-control form-control-sm" id="filtroFechaDesde">
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroFechaHasta" class="form-label">Hasta</label>
                                    <input type="date" class="form-control form-control-sm" id="filtroFechaHasta">
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-1">
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
    
    <!-- jQuery (requerido para Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <!-- Select2 JS (requiere jQuery) -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="js/modal_global.js?v=<?php echo time(); ?>"></script>
    <script src="js/solicitud_listado.js?v=<?php echo time(); ?>"></script>
    <script src="js/directores_solicitud.js?v=<?php echo time(); ?>"></script>
    
    <script>
        // Obtener el ID del director desde PHP (asegurar que sea número)
        const ID_DIRECTOR_ACTUAL = parseInt(<?php echo json_encode($id_usuario); ?>, 10);
        
        // Cargar solicitudes cuando se muestra listado
        document.getElementById('listado-tab')?.addEventListener('shown.bs.tab', async function() {
            // Cargar solicitudes filtrando por el director actual
            if (typeof cargarSolicitudes === 'function') {
                if (ID_DIRECTOR_ACTUAL && !isNaN(ID_DIRECTOR_ACTUAL)) {
                    cargarSolicitudes({ id_director: ID_DIRECTOR_ACTUAL });
                } else {
                    console.error('ID del director no válido:', ID_DIRECTOR_ACTUAL);
                    cargarSolicitudes();
                }
            }
        });
        
        // También filtrar cuando se aplican filtros
        window.aplicarFiltros = function() {
            const filtros = {
                estado: document.getElementById('filtroEstado')?.value || '',
                fecha_desde: document.getElementById('filtroFechaDesde')?.value || '',
                fecha_hasta: document.getElementById('filtroFechaHasta')?.value || ''
            };
            
            // Agregar el filtro de director si está disponible
            if (ID_DIRECTOR_ACTUAL && !isNaN(ID_DIRECTOR_ACTUAL)) {
                filtros.id_director = ID_DIRECTOR_ACTUAL;
            }
            
            cargarSolicitudes(filtros);
        };
        
        // Asegurar que al limpiar filtros también se mantenga el filtro del director
        window.limpiarFiltros = function() {
            document.getElementById('filtroEstado').value = '';
            document.getElementById('filtroFechaDesde').value = '';
            document.getElementById('filtroFechaHasta').value = '';
            
            if (ID_DIRECTOR_ACTUAL && !isNaN(ID_DIRECTOR_ACTUAL)) {
                cargarSolicitudes({ id_director: ID_DIRECTOR_ACTUAL });
            } else {
                cargarSolicitudes();
            }
        };
    </script>
</body>
</html>