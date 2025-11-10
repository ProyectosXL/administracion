<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pago de Servicios - Administración</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/caja_estilos.css">
</head>
<body>
    <!-- Navbar Simple (sin menú) -->
    <nav class="navbar navbar-dark bg-dark sticky-top p-0 shadow">
        <div class="container-fluid">
            <a class="navbar-brand px-3" href="index.php">
                <i class="bi bi-cash-coin"></i> Caja Directores
            </a>
            <div class="navbar-nav d-none d-md-flex">
                <div class="nav-item text-nowrap">
                    <a class="nav-link px-3" href="#">
                        <i class="bi bi-person-circle"></i> Administración
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Contenido Principal -->
            <main class="col-12 px-3 px-md-4" style="padding-top: calc(var(--navbar-height, 56px)); padding-bottom: 1rem;">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h3 h2-md mb-2 mb-md-0">
                        <i class="bi bi-file-earmark-text"></i> Registro de Pago de Servicios
                    </h1>
                    <div class="btn-toolbar">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-calendar3"></i> <span class="d-none d-sm-inline"><?php echo date('d/m/Y'); ?></span>
                        </button>
                    </div>
                </div>
                
                <!-- Contenido Principal -->
                <div class="row g-3">
                    <!-- Formulario de Pago de Servicios -->
                    <div class="col-12 col-lg-6">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-receipt-cutoff"></i> Nuevo Pago de Servicio
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="formPagoServicios">
                                    <!-- Selector de Director -->
                                    <div class="mb-3">
                                        <label for="directorSelect" class="form-label">
                                            Director <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="directorSelect" required>
                                            <option value="">Seleccione un director</option>
                                        </select>
                                        <div class="form-text">
                                            Seleccione el director asociado al pago
                                        </div>
                                    </div>
                                    
                                    <!-- Selector de Motivo -->
                                    <div class="mb-3">
                                        <label for="motivoSelect" class="form-label">
                                            Motivo <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="motivoSelect" required>
                                            <option value="">Seleccione un motivo</option>
                                            <option value="Pago de seguros">Pago de seguros</option>
                                            <option value="Pago de patentes">Pago de patentes</option>
                                            <option value="Pago de expensas">Pago de expensas</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Sección de Proveedor (solo visible para "Pago de seguros") -->
                                    <div id="seccionProveedor" class="mb-3 d-none">
                                        <div class="card border-info">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="mb-0">
                                                    <i class="bi bi-person-badge-fill"></i> Datos del Proveedor
                                                </h6>
                                            </div>
                                            <div class="card-body">
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
                                                        CBU <span class="text-muted">(opcional)</span>
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
                                                </div>
                                                
                                                <div class="mb-0">
                                                    <label for="descripcionCbuProveedor" class="form-label">
                                                        Descripción CBU <span class="text-muted">(opcional)</span>
                                                    </label>
                                                    <input type="text" class="form-control" id="descripcionCbuProveedor" 
                                                           placeholder="Ej: Cuenta Corriente Banco Galicia">
                                                    <div class="form-text">
                                                        Descripción de la cuenta (opcional, se completará automáticamente si el proveedor la tiene cargada)
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Fecha de Vencimiento -->
                                    <div class="mb-3">
                                        <label for="fechaVencimiento" class="form-label">
                                            Fecha de Vencimiento de Factura <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="fechaVencimiento" required>
                                    </div>
                                    
                                    <!-- Importe -->
                                    <div class="mb-3">
                                        <label for="importePago" class="form-label">
                                            Importe <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control importe-input" 
                                                   id="importePago" placeholder="0" required>
                                        </div>
                                        <div class="form-text">
                                            Ingrese el importe en pesos
                                        </div>
                                    </div>
                                    
                                    <!-- Observaciones -->
                                    <div class="mb-3">
                                        <label for="observaciones" class="form-label">
                                            Observaciones
                                        </label>
                                        <textarea class="form-control" id="observaciones" 
                                                  rows="3" placeholder="Detalles adicionales (opcional)"></textarea>
                                    </div>
                                    
                                    <!-- Adjuntar Factura -->
                                    <div class="mb-3">
                                        <label class="form-label">
                                            Adjuntar Factura <span class="text-danger">*</span>
                                        </label>
                                        
                                        <!-- Botones para móvil -->
                                        <div class="d-md-none mb-2">
                                            <div class="btn-group w-100" role="group">
                                                <input type="file" class="d-none" id="facturaCamera" 
                                                       accept="image/*" 
                                                       capture="environment">
                                                <input type="file" class="d-none" id="facturaGallery" 
                                                       accept="image/*,application/pdf">
                                                
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="document.getElementById('facturaCamera').click()">
                                                    <i class="bi bi-camera"></i> Cámara
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="document.getElementById('facturaGallery').click()">
                                                    <i class="bi bi-image"></i> Galería
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Input tradicional para escritorio -->
                                        <input type="file" class="form-control d-none d-md-block" id="facturaInput" 
                                               accept="image/jpeg,image/jpg,image/png,application/pdf">
                                        
                                        <div class="form-text">
                                            <small>
                                                <strong>Móvil:</strong> <span class="text-primary">Cámara</span> abre directamente la cámara | <span class="text-secondary">Galería</span> permite elegir imagen o PDF guardado<br>
                                                <strong>Escritorio:</strong> Seleccione archivo JPG, PNG o PDF<br>
                                                <strong>Obligatorio:</strong> Debe adjuntar la factura
                                            </small>
                                        </div>
                                        
                                        <div id="previewFactura" class="mt-2 d-none">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <img id="imgPreviewFactura" src="" class="img-thumbnail d-none" 
                                                     style="max-width: 150px; max-height: 120px;">
                                                <div id="pdfPreviewFactura" class="d-none">
                                                    <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size: 3rem;"></i>
                                                </div>
                                                <div id="infoArchivo" class="flex-grow-1"></div>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="eliminarFactura()">
                                                    <i class="bi bi-trash"></i> Quitar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Botón de Submit -->
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg py-2">
                                            <i class="bi bi-check-circle"></i> Registrar Pago
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel de Últimos Pagos de Servicios -->
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Últimos Pagos de Servicios</h5>
                            </div>
                            <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                <div id="listaPagosServicios">
                                    <p class="text-muted">Cargando pagos...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal de confirmación -->
    <?php include 'components/modal_confirmar.php'; ?>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (requerido para Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="js/modal_global.js?v=<?php echo time(); ?>"></script>
    <script src="js/form_pago_servicios.js?v=<?php echo time(); ?>"></script>
</body>
</html>
