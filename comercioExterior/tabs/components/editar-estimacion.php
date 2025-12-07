<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Estimación de Costos - PCI</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../css/proyeccion-costos.css">
    <link rel="icon" type="image/jpg" href="../../images/LOGO XL 2018.jpg">
</head>
<body>
    <div class="pci-edit-container">
        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-calculator-fill"></i>
                    Estimación de Costos de Importación
                </h1>
                <p class="page-subtitle" id="despacheInfo">Cargando información...</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                    <i class="bi bi-arrow-left"></i> Volver
                </button>
                <button type="button" class="btn btn-success" id="btnGuardar" onclick="guardarEstimacion()">
                    <i class="bi bi-save"></i> Guardar
                </button>
            </div>
        </div>

        <!-- Información del Despacho -->
        <div class="info-card">
            <div class="row">
                <div class="col-md-3">
                    <div class="info-item">
                        <label class="info-label">Proveedor:</label>
                        <span class="info-value" id="infoProveedor">-</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-item">
                        <label class="info-label">Contenedor:</label>
                        <span class="info-value" id="infoContenedor">-</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-item">
                        <label class="info-label">Material:</label>
                        <span class="info-value" id="infoMaterial">-</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-item">
                        <label class="info-label">Orden Compra:</label>
                        <span class="info-value" id="infoOrdenCompra">-</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Estimación -->
        <div class="estimation-card">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-list-check"></i>
                    Conceptos de Costos
                </h3>
                <div class="estado-badge" id="estadoBadge">
                    <i class="bi bi-pencil"></i> Borrador
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-estimation">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Concepto</th>
                            <th style="width: 15%;">Tipo</th>
                            <th style="width: 15%;">Parámetro 1</th>
                            <th style="width: 15%;">Parámetro 2</th>
                            <th style="width: 25%;">Importe</th>
                        </tr>
                    </thead>
                    <tbody id="tablaConceptos">
                        <!-- Los conceptos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Acciones -->
        <div class="totals-card">
            <div class="row">
                <div class="col-md-12 text-end">
                    <button type="button" class="btn btn-lg btn-secondary me-2" onclick="window.location.href='../proyeccionCostos.php'">
                        <i class="bi bi-arrow-left"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-lg btn-success" id="btnGuardarBottom" onclick="guardarEstimacion()">
                        <i class="bi bi-save"></i> Guardar Estimación
                    </button>
                </div>
            </div>
        </div>

        <!-- Datos ocultos -->
        <input type="hidden" id="idDespacho" value="">
        <input type="hidden" id="valorFOB" value="0">
        <input type="hidden" id="estaConfirmada" value="0">
    </div>

    <!-- jQuery -->
    <script src="../../assets/jquery/jquery.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script src="../../js/editar-estimacion.js"></script>
</body>
</html>
