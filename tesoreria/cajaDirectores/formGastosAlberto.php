<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registro de Gastos - Alberto</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
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
                        <i class="bi bi-person-circle"></i> Usuario
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
                    <h1 class="h3 h2-md mb-2 mb-md-0"><i class="bi bi-receipt"></i> Registro de Gastos - Alberto</h1>
                    <div class="btn-toolbar">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-calendar3"></i> <span class="d-none d-sm-inline"><?php echo date('d/m/Y'); ?></span>
                        </button>
                    </div>
                </div>
                
                <!-- Contenido Principal -->
                <div class="row g-3">
                    <!-- Formulario de Gastos -->
                    <div class="col-12 col-lg-6">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Registrar Gasto</h5>
                            </div>
                            <div class="card-body">
                                <form id="formGastoAlberto">
                                    <div class="row">
                                        <div class="col-12 col-sm-6 mb-3">
                                            <label class="form-label">Motivo</label>
                                            <input type="text" class="form-control form-control-sm" value="Pago a Proveedores" 
                                                   readonly style="background-color: #f8f9fa;">
                                        </div>
                                        
                                        <div class="col-12 col-sm-6 mb-3">
                                            <label class="form-label">Proveedor</label>
                                            <input type="text" class="form-control form-control-sm" value="ROLLAN ALBERTO ESTEBAN" 
                                                   readonly style="background-color: #f8f9fa;">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tipoGastoAlberto" class="form-label">Tipo de Gasto *</label>
                                        <select class="form-select" id="tipoGastoAlberto" required>
                                            <option value="">Seleccione un tipo de gasto</option>
                                            <option value="Uruguay Mantenimiento">Uruguay Mantenimiento</option>
                                            <option value="Locales mantenimiento">Locales mantenimiento</option>
                                            <option value="Dirección de obra locales">Dirección de obra locales</option>
                                            <option value="Uruguay obras">Uruguay obras</option>
                                            <option value="Locales obras">Locales obras</option>
                                            <option value="Materiales y gastos varios">Materiales y gastos varios</option>
                                        </select>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-12 col-sm-6 mb-3">
                                            <label for="importeGasto" class="form-label">Importe *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="text" class="form-control importe-input" 
                                                       id="importeGasto" placeholder="0" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12 col-sm-6 mb-3">
                                            <label for="fechaGasto" class="form-label">Fecha *</label>
                                            <input type="date" class="form-control" id="fechaGasto" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="observacionesGasto" class="form-label">Observaciones</label>
                                        <textarea class="form-control" id="observacionesGasto" 
                                                  rows="2" placeholder="Descripción del gasto"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fotoGasto" class="form-label">
                                            Foto del Comprobante <span class="text-muted">(opcional)</span>
                                        </label>
                                        
                                        <!-- Botones para móvil -->
                                        <div class="d-md-none mb-2">
                                            <div class="btn-group w-100" role="group">
                                                <!-- Input para cámara: solo captura desde cámara trasera -->
                                                <input type="file" class="d-none" id="fotoGastoCamera" 
                                                       accept="image/*" 
                                                       capture="environment"
                                                       onchange="previsualizarFotoGasto(this)">
                                                <!-- Input para galería: sin capture permite elegir archivo -->
                                                <input type="file" class="d-none" id="fotoGastoGallery" 
                                                       accept="image/*"
                                                       onchange="previsualizarFotoGasto(this)">
                                                
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="document.getElementById('fotoGastoCamera').click()">
                                                    <i class="bi bi-camera"></i> Cámara
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="document.getElementById('fotoGastoGallery').click()">
                                                    <i class="bi bi-image"></i> Galería
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Input tradicional para escritorio -->
                                        <input type="file" class="form-control d-none d-md-block" id="fotoGasto" 
                                               accept="image/jpeg,image/jpg,image/png,image/gif,image/bmp,image/webp,image/tiff,image/heic,image/heif"
                                               onchange="previsualizarFotoGasto(this)">
                                        
                                        <div class="form-text">
                                            <small>
                                                <strong>Móvil:</strong> <span class="text-primary">Cámara</span> abre directamente la cámara para tomar foto | <span class="text-secondary">Galería</span> permite elegir imagen guardada<br>
                                                <strong>Escritorio:</strong> Arrastra o selecciona archivo<br>
                                                <strong>Formatos:</strong> JPG, PNG, GIF, BMP, WebP, TIFF, HEIC, HEIF
                                            </small>
                                        </div>
                                        
                                        <div id="previewFotoGasto" class="mt-2 d-none">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <img id="imgPreviewGasto" src="" class="img-thumbnail" 
                                                     style="max-width: 150px; max-height: 120px;">
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="eliminarPreviewFotoGasto()">
                                                    <i class="bi bi-trash"></i> Quitar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning text-dark w-100 py-2">
                                        <i class="bi bi-check-circle"></i> Registrar Gasto
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel de Últimos Gastos -->
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Últimos Gastos Registrados</h5>
                            </div>
                            <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                <div id="listaGastosAlberto">
                                    <p class="text-muted">Cargando gastos...</p>
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
    
    <!-- Scripts personalizados -->
    <script src="js/modal_global.js?v=<?php echo time(); ?>"></script>
    <script src="js/form_gastos_alberto.js?v=<?php echo time(); ?>"></script>
</body>
</html>
