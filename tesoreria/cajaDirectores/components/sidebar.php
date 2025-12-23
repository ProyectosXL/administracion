<?php
// Nos aseguramos de tener la sesión iniciada para acceder a las variables.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Creamos la variable de control para saber si es el usuario de mantenimiento.
$esUsuarioMantenimiento = (isset($_SESSION['tipo']) && $_SESSION['tipo'] == 'MANTENIMIENTO');
?>


<?php if ($esUsuarioMantenimiento) : ?>

    <!-- ################################################## -->
    <!-- VISTA DEL SIDEBAR EXCLUSIVA PARA ALBERTO           -->
    <!-- ################################################## -->
    <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
        <div class="position-sticky pt-0">
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                <span>Acciones Rápidas</span>
            </h6>
            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link" href="formGastosAlberto.php" target="_blank">
                        <i class="bi bi-receipt"></i> Cargar Gastos Alberto
                    </a>
                </li>
            </ul>
        </div>
    </nav>

<?php else : ?>

    <!-- ################################################## -->
    <!-- VISTA DEL SIDEBAR NORMAL PARA TODOS LOS DEMÁS      -->
    <!-- ################################################## -->
    <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
        <div class="position-sticky pt-0">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="#ingresos" data-bs-toggle="tab">
                        <i class="bi bi-plus-circle"></i> Form Ingresos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#egresos" data-bs-toggle="tab">
                        <i class="bi bi-dash-circle"></i> Form Egresos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#reporte" data-bs-toggle="tab">
                        <i class="bi bi-graph-up"></i> Reporte
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#egresos-socios" data-bs-toggle="tab">
                        <i class="bi bi-people-fill"></i> Reporte Socios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#resumen-servicios" data-bs-toggle="tab">
                        <i class="bi bi-gear-fill"></i> Resumen Servicios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#reporte-alberto" data-bs-toggle="tab">
                        <i class="bi bi-person-badge"></i> Reporte Alberto
                    </a>
                </li>
            </ul>
            
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                <span>Acciones Rápidas</span>
            </h6>
            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link" href="formGastosAlberto.php" target="_blank">
                        <i class="bi bi-receipt"></i> Cargar Gastos Alberto
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="formPagoServicios.php" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> Pago de Servicios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="btnActualizarDatos">
                        <i class="bi bi-arrow-clockwise"></i> Actualizar Datos
                    </a>
                </li>
            </ul>
        </div>
    </nav>

<?php endif; ?>