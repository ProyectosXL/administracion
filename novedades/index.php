<?php
/**
 * Página principal del sistema
 * /novedades/index.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Novedades RRHH</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS personalizado -->
    <link href="css/novedades_estilos.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Contenedor de alertas -->
    <div id="alertas-container" class="container mt-3"></div>

    <!-- Header de la página -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>
                        <i class="fas fa-home me-3"></i>
                        Panel de Control
                    </h1>
                    <p class="lead mb-0">
                        Sistema de gestión de novedades para liquidación de sueldos
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="periodo-badge">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Período Actual: 28/07/2025 - 27/08/2025
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container">
        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body">
                        <i class="fas fa-plus-circle fa-2x mb-2"></i>
                        <div class="display-4" id="total-novedades">-</div>
                        <h6>Novedades del Período</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <div class="display-4" id="novedades-pendientes">-</div>
                        <h6>Pendientes de Revisión</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body">
                        <i class="fas fa-building fa-2x mb-2"></i>
                        <div class="display-4" id="total-sucursales">-</div>
                        <h6>Sucursales Activas</h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="row">
            <div class="col-md-6">
                <div class="card h-100 slide-up">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            Nueva Novedad
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <p class="card-text flex-grow-1">
                            Registre una nueva novedad que impacte en la liquidación de sueldos. 
                            Complete los datos del empleado y seleccione el tipo de novedad correspondiente.
                        </p>
                        <div class="mt-auto">
                            <a href="nueva_novedad.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-plus me-2"></i>
                                Crear Nueva Novedad
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100 slide-up">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-search me-2"></i>
                            Consultar Novedades
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <p class="card-text flex-grow-1">
                            Consulte y filtre las novedades registradas para el período actual. 
                            Visualice los detalles y genere reportes para el área de RRHH.
                        </p>
                        <div class="mt-auto">
                            <a href="consultar_novedades.php" class="btn btn-outline-primary btn-lg w-100">
                                <i class="fas fa-search me-2"></i>
                                Ver Novedades
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimas novedades registradas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>
                            Últimas Novedades Registradas
                        </h5>
                        <a href="consultar_novedades.php" class="btn btn-sm btn-outline-primary">
                            Ver todas <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div id="ultimas-novedades-container">
                            <div class="text-center py-3">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <div class="mt-2">Cargando últimas novedades...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del período -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>
                        Información del Período Actual
                    </h6>
                    <p class="mb-0">
                        <strong>Período de liquidación:</strong> Del 28 de Julio de 2025 al 27 de Agosto de 2025<br>
                        <strong>Fecha límite para carga:</strong> Las novedades deben registrarse antes del cierre del período<br>
                        <strong>Estado:</strong> <span class="badge bg-success">Activo</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-4 bg-light">
        <div class="container text-center">
            <p class="text-muted mb-0">
                <i class="fas fa-building me-2"></i>
                Sistema de Novedades RRHH - Desarrollado para optimizar la gestión de liquidaciones
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/novedades_main.js"></script>
    <script src="js/dashboard_index.js"></script>
    <script src="js/manual.js"></script>
    <script src="js/modal_fix.js"></script>

    <!-- Manual de Usuario Modal -->
    <?php include 'components/manual_modal.php'; ?>

</body>
</html>