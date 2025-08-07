<!-- /novedades/components/navbar.php -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-briefcase me-2"></i>
            Sistema de Novedades RRHH
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home me-1"></i>
                        Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="nueva_novedad.php">
                        <i class="fas fa-plus-circle me-1"></i>
                        Nueva Novedad
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="consultar_novedades.php">
                        <i class="fas fa-search me-1"></i>
                        Consultar Novedades
                    </a>
                </li>
            </ul>
            
            <div class="navbar-text">
                <small class="text-light">
                    <i class="fas fa-calendar-alt me-1"></i>
                    Período: <span id="periodo-actual" class="fw-bold">28/07/2025 - 27/08/2025</span>
                </small>
            </div>
        </div>
    </div>
</nav>