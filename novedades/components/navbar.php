<!-- /novedades/components/navbar.php - ACTUALIZADA -->
<?php
// Incluir la configuración de usuario para verificar permisos
require_once __DIR__ . '/../config/usuario_config.php';
require_once __DIR__ . '/../class/Usuario.php';

// Determinar si mostrar el enlace de gestión de tipos de novedad
$mostrarGestionTipos = (Usuario::getTipoUsuario() === Usuario::TIPO_RRHH);

// Obtener período formateado para mostrar
require_once __DIR__ . '/../includes/periodo_helper.php';
$periodoFormateado = PeriodoHelper::getPeriodoFormateado();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="fas fa-briefcase me-2"></i>
            <span class="d-none d-md-inline">Sistema de Novedades</span>
            <span class="d-md-none">RRHH</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link px-3" href="index.php">
                        <i class="fas fa-home me-2"></i>
                        <span class="d-none d-lg-inline">Dashboard</span>
                        <span class="d-lg-none">Inicio</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="nueva_novedad.php">
                        <i class="fas fa-plus-circle me-2"></i>
                        <span class="d-none d-lg-inline">Nueva</span>
                        <span class="d-lg-none">Crear</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="consultar_novedades.php">
                        <i class="fas fa-search me-2"></i>
                        <span class="d-none d-lg-inline">Consultar</span>
                        <span class="d-lg-none">Ver</span>
                    </a>
                </li>
                <?php if ($mostrarGestionTipos): ?>
                <li class="nav-item">
                    <a class="nav-link px-3" href="gestion_tipos_novedad.php" title="Gestión de Tipos de Novedad">
                        <i class="fas fa-cogs me-2"></i>
                        <span class="d-none d-xl-inline">Gestión Tipos</span>
                        <span class="d-xl-none d-lg-inline">Gestión</span>
                        <span class="d-lg-none">Config</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link px-3" href="#" onclick="mostrarManualUso()" title="Ayuda y Manual de Usuario">
                        <i class="fas fa-question-circle me-2"></i>
                        <span class="d-none d-lg-inline">Ayuda</span>
                        <span class="d-lg-none">?</span>
                    </a>
                </li>
            </ul>
            
            <div class="navbar-text text-light">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <div class="small">
                        <div class="fw-semibold d-none d-md-block">Período Actual</div>
                        <!-- ACTUALIZADO: Mostrar formato Mes Año -->
                        <span id="periodo-actual" class="badge bg-light text-primary"><?php echo $periodoFormateado; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
/* Estilos adicionales para la navbar mejorada */
.navbar-nav .nav-link {
    transition: all 0.3s ease;
    border-radius: 6px;
    margin: 0 2px;
}

.navbar-nav .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
}

.navbar-nav .nav-link.active {
    background-color: rgba(255, 255, 255, 0.2);
}

.navbar-brand:hover {
    transform: scale(1.02);
    transition: transform 0.2s ease;
}

@media (max-width: 991px) {
    .navbar-nav {
        margin-top: 1rem;
    }
    
    .navbar-text {
        margin-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 1rem;
    }
}

@media (max-width: 576px) {
    .container-fluid.px-4 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
}
</style>