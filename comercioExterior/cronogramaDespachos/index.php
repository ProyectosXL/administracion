<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Si viene entorno por URL, actualizar la sesión
if (isset($_GET['entorno']) && in_array($_GET['entorno'], ['central', 'uy'])) {
    $_SESSION['entorno'] = $_GET['entorno'];
}

// Headers para evitar caché y forzar recarga
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronograma de Contenedores | Importaciones</title>
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="css/cronograma.css">
    <link rel="stylesheet" href="css/ayuda-modal.css">
</head>
<body>
    <div class="cronograma-container">
        <!-- Header -->
        <div class="cronograma-header">
            <div>
                <h1>📦 Cronograma de Contenedores</h1>
                <div class="header-subtitle">Seguimiento logístico de importaciones</div>
            </div>
            
            <div class="header-actions">
                <div class="filtros-container">
                    <button class="btn-filtros" id="btnFiltros" title="Filtrar por tipo de fecha">
                        <i class="bi bi-funnel"></i> Filtros <span class="badge-filtros" id="badgeFiltros"></span>
                    </button>
                    <div class="filtros-dropdown" id="filtrosDropdown">
                        <div class="filtros-header">
                            <span>Seleccionar Estados</span>
                            <button class="btn-limpiar-filtros" id="btnLimpiarFiltros">Limpiar</button>
                        </div>
                        <div class="filtros-lista">
                            <label class="filtro-item">
                                <input type="checkbox" value="est-emb" class="filtro-checkbox" checked>
                                <span class="filtro-color est-emb"></span>
                                <span>Embarque Estimado</span>
                            </label>
                            <label class="filtro-item">
                                <input type="checkbox" value="emb" class="filtro-checkbox" checked>
                                <span class="filtro-color emb"></span>
                                <span>Embarque Real</span>
                            </label>
                            <label class="filtro-item">
                                <input type="checkbox" value="arr-estimado" class="filtro-checkbox" checked>
                                <span class="filtro-color arr-estimado"></span>
                                <span>Arribo Estimado</span>
                            </label>
                            <label class="filtro-item">
                                <input type="checkbox" value="arr-real" class="filtro-checkbox" checked>
                                <span class="filtro-color arr-real"></span>
                                <span>Arribo Real</span>
                            </label>
                            <label class="filtro-item">
                                <input type="checkbox" value="desp" class="filtro-checkbox" checked>
                                <span class="filtro-color desp"></span>
                                <span>Despacho Aduana</span>
                            </label>
                            <label class="filtro-item">
                                <input type="checkbox" value="rec" class="filtro-checkbox" checked>
                                <span class="filtro-color rec"></span>
                                <span>Recepción</span>
                            </label>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-help" data-bs-toggle="modal" data-bs-target="#ayudaModal" title="Guía de ayuda: Estados, flujo y funcionalidades">
                    <i class="bi bi-info-circle"></i> Ayuda
                </button>
                <!-- Densidad de los badges del calendario. El estado activo lo
                     marca aplicarEstadoDensidad() desde localStorage. -->
                <div class="btn-density-toggle" id="densidadToggle">
                    <button class="btn-densidad" data-densidad="compacto"
                            title="Vista compacta: más contenedores por día">
                        <i class="bi bi-list"></i>
                    </button>
                    <button class="btn-densidad" data-densidad="comodo"
                            title="Vista cómoda: incluye el proveedor">
                        <i class="bi bi-card-list"></i>
                    </button>
                </div>
                <div class="btn-view-toggle">
                    <button class="btn-view active" data-view="calendario">
                        <i class="bi bi-calendar3"></i> Calendario
                    </button>
                    <button class="btn-view" data-view="grilla">
                        <i class="bi bi-grid-3x3"></i> Tarjetas
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Contenido Principal -->
        <div id="contenidoPrincipal">
            <!-- Se renderizará dinámicamente con JavaScript -->
        </div>
    </div>
    
    <!-- Modal de Ayuda -->
    <?php include 'components/ayuda-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript -->
    <script src="js/cronograma.js"></script>
</body>
</html>
