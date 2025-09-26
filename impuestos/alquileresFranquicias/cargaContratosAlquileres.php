
<?php
require_once 'Class/Alquiler.php';

$alquiler = new Alquiler();

$franquicias = [];
$mensaje = '';

try {
    $franquicias = $alquiler->traerFranquicias();
} catch (Exception $e) {
    error_log("Error al obtener franquicias: " . $e->getMessage());
    $mensaje = "Error al cargar las franquicias. Por favor, inténtelo de nuevo más tarde.";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#007bff">
    <link rel="icon" href="../../image/icono.jpg" type="image/jpg">
    <title>Carga Contratos Alquiler Franquicias</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-5-theme/1.3.0/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/cargaContratos.css" class="rel">
    <style>
        
    </style>
</head>
<body>
    <div class="container mt-5">
        <h3 class="mb-4">
            <i class="fas fa-building me-2"></i>
            Carga Contratos Alquiler Franquicias
        </h3>
        
        <form id="contratoAlquilerForm" method="POST">
            <div class="mb-3">
                <label for="franquicia" class="form-label">Franquicia</label>
                <select class="form-select" id="franquicia" name="franquicia" required>
                    <option value="">Seleccione una franquicia</option>
                    <?php foreach ($franquicias as $franquicia): ?>
                        <option value="<?php echo htmlspecialchars($franquicia['NRO_SUCURSAL']); ?>">
                            <?php echo htmlspecialchars($franquicia['DESC_SUCURSAL']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <h5 class="mt-4">Vigencia</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="fechaDesde" class="form-label">Desde</label>
                    <input type="date" class="form-control" id="fechaDesde" name="fechaDesde" required>
                </div>
                <div class="col-md-6">
                    <label for="fechaHasta" class="form-label">Hasta</label>
                    <input type="date" class="form-control" id="fechaHasta" name="fechaHasta" required>
                </div>
            </div>
            
            <h5 class="mt-4">Contrato Comercial <span class="text-danger">*</span></h5>
            <div class="mb-3">
                <div class="file-input-group">
                    <input type="file" class="form-control" id="contratoComercial" name="contratoComercial" accept=".pdf" required>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary view-file" title="Ver archivo"><i class="fas fa-eye"></i></button>
                        <button type="button" class="btn btn-danger delete-file" title="Eliminar archivo"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <div class="file-size-info" id="sizeInfoComercial"></div>
                <div class="compression-status" id="compressionComercial">
                    <i class="fas fa-compress-alt status-icon"></i> Archivo comprimido automáticamente
                </div>
                <div class="upload-progress" id="progressComercial">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar"></div>
                    </div>
                </div>
            </div>

            <h5 class="mt-4">Contrato Locación</h5>
            <div class="mb-3">
                <div class="file-input-group">
                    <input type="file" class="form-control" id="contratoLocacion" name="contratoLocacion" accept=".pdf">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary view-file" title="Ver archivo"><i class="fas fa-eye"></i></button>
                        <button type="button" class="btn btn-danger delete-file" title="Eliminar archivo"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <div class="file-size-info" id="sizeInfoLocacion"></div>
                <div class="compression-status" id="compressionLocacion">
                    <i class="fas fa-compress-alt status-icon"></i> Archivo comprimido automáticamente
                </div>
                <div class="upload-progress" id="progressLocacion">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar"></div>
                    </div>
                </div>
            </div>

            <h5 class="mt-4">Habilitación Local</h5>
            <div class="mb-3">
                <div class="file-input-group">
                    <input type="file" class="form-control" id="habilitacion" name="habilitacion" accept=".pdf">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary view-file" title="Ver archivo"><i class="fas fa-eye"></i></button>
                        <button type="button" class="btn btn-danger delete-file" title="Eliminar archivo"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <div class="file-size-info" id="sizeInfoHabilitacion"></div>
                <div class="compression-status" id="compressionHabilitacion">
                    <i class="fas fa-compress-alt status-icon"></i> Archivo comprimido automáticamente
                </div>
                <div class="upload-progress" id="progressHabilitacion">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar"></div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100" id="btnGuardar">
                <span id="spinner" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <i class="fas fa-save me-2"></i>Guardar
            </button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="js/cargaAlquiler.js"></script>

</body>
</html>