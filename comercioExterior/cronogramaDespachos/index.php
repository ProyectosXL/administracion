<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
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
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="css/cronograma.css">
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
                <select id="filtroEstado" class="filtro-estado" title="Filtrar por tipo de fecha">
                    <option value="todos">📦 Todas las Fechas</option>
                    <option value="est-emb">📅 Embarque Estimado</option>
                    <option value="emb">🚢 Embarque Real</option>
                    <option value="arr">🛃 Arribo</option>
                    <option value="desp">🚚 Despacho Aduana</option>
                    <option value="rec">📦 Recepción</option>
                </select>
                <a href="components/ayuda.php" target="_blank" class="btn-help" title="Guía de ayuda: Estados, flujo y funcionalidades">
                    <i class="bi bi-info-circle"></i> Ayuda
                </a>
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
    
    <!-- JavaScript -->
    <script src="js/cronograma.js"></script>
</body>
</html>
