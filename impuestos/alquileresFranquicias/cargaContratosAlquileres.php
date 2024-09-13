
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <div class="col">
                    <label for="fechaDesde" class="form-label">Desde</label>
                    <input type="date" class="form-control" id="fechaDesde" name="fechaDesde" required>
                </div>
                <div class="col">
                    <label for="fechaHasta" class="form-label">Hasta</label>
                    <input type="date" class="form-control" id="fechaHasta" name="fechaHasta" required>
                </div>
            </div>
            
            <h5 class="mt-4">Contrato Comercial</h5>
            <div class="mb-3">
                <input type="file" class="form-control" id="contratoComercial" name="contratoComercial" accept=".pdf">
                <div class="file-preview" id="previewContratoComercial"></div>
                <div class="file-actions" id="actionsContratoComercial">
                    <button type="button" class="btn btn-primary btn-sm view-file">Ver</button>
                    <button type="button" class="btn btn-danger btn-sm delete-file">Eliminar</button>
                </div>
            </div>

            <h5 class="mt-4">Contrato Locación</h5>
            <div class="mb-3">
                <input type="file" class="form-control" id="contratoLocacion" name="contratoLocacion" accept=".pdf">
                <div class="file-preview" id="previewContratoLocacion"></div>
                <div class="file-actions" id="actionsContratoLocacion">
                    <button type="button" class="btn btn-primary btn-sm view-file">Ver</button>
                    <button type="button" class="btn btn-danger btn-sm delete-file">Eliminar</button>
                </div>
            </div>

            <h5 class="mt-4">Habilitación Local</h5>
            <div class="mb-3">
                <input type="file" class="form-control" id="habilitacion" name="habilitacion" accept=".pdf">
                <div class="file-preview" id="previewHabilitacion"></div>
                <div class="file-actions" id="actionsHabilitacion">
                    <button type="button" class="btn btn-primary btn-sm view-file">Ver</button>
                    <button type="button" class="btn btn-danger btn-sm delete-file">Eliminar</button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg w-100" id="btnGuardar">
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