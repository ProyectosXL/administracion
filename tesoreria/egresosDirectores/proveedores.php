<?php
$titulo_pagina = 'Proveedores';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egresos - Proveedores</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons - Versión más reciente -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Fallback para Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" crossorigin="anonymous">
    
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
                    <i class="bi bi-building"></i> Portal de Proveedores
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="cargarFacturasPendientes()">
                        <i class="bi bi-arrow-clockwise"></i> 🔄 Actualizar
                    </button>
                </div>
            </div>
            
            <!-- Información de la pantalla -->
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Portal de Proveedores:</strong> Ver facturas pendientes de carga y cargar órdenes de compra.
                <br>
                <small>En esta pantalla podrás ver las solicitudes de compra personal que están pendientes de cargar la orden de compra.</small>
            </div>
            
            <!-- Card con facturas pendientes -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt"></i> Facturas Pendientes de Carga
                    </h5>
                </div>
                <div class="card-body">
                    <div id="facturasPendientes">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando facturas pendientes...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modales -->
    <?php include 'components/modales.php'; ?>
    
    <!-- Modal para cargar orden de compra -->
    <div class="modal fade" id="modalCargarOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-file-earmark-check"></i> Cargar Orden de Compra
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCargarOrden">
                        <input type="hidden" id="idSolicitudOrden">
                        
                        <div class="mb-3">
                            <label class="form-label">Número de Orden de Compra</label>
                            <input type="text" class="form-control" id="numeroOrdenCompra" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observacionesOrden" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarOrdenCompra()">
                        <i class="bi bi-check-circle"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="js/modal_global.js?v=<?php echo time(); ?>"></script>
    <script src="js/proveedores_listado.js?v=<?php echo time(); ?>"></script>
</body>
</html>