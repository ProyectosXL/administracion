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

require_once '../Class/proveedor.php';
require_once '../Class/encabezado.php';

$cid = new Encabezado();
$proveedorClass = new Proveedor();

// Obtener despachos pendientes (sin costos completos o en edición)
// Esto se puede ajustar según tu lógica de negocio
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Despachos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/gestionDespachos.css">
</head>
<body>
    <div class="gestion-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-list-check"></i>
                    Gestión de Despachos
                </h1>
                <p class="page-subtitle">Administra tus despachos de importación</p>
            </div>
            <div class="d-flex gap-2">
                <button id="btnVerOcPendientes" class="btn btn-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Ver OC Pendientes
                    <span id="badgeOcPendientes" class="badge bg-danger" style="display: none;">0</span>
                </button>
                <a href="cargaInicial.php" class="btn-nuevo-despacho">
                    <i class="bi bi-plus-circle"></i>
                    Nuevo Despacho
                </a>
            </div>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover" id="tablaDespachos">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 100px;">Fecha</th>
                            <th style="width: 250px;">Proveedor</th>
                            <th style="width: 120px;">Contenedor</th>
                            <th style="width: 150px;">Material</th>
                            <th style="width: 130px;">Orden Compra</th>
                            <th style="width: 200px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: Órdenes de Compra Pendientes -->
    <div class="modal fade" id="modalOcPendientes" tabindex="-1" aria-labelledby="modalOcPendientesLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalOcPendientesLabel">
                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                        Órdenes de Compra Pendientes (Últimos 18 meses)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Estas son las órdenes de compra que no tienen despacho asignado. 
                        Puedes crear un despacho directamente desde aquí.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="tablaOcPendientes">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">Cod. Proveedor</th>
                                    <th style="width: 250px;">Proveedor</th>
                                    <th style="width: 150px;">N° Orden Compra</th>
                                    <th style="width: 120px;">Fecha Ingreso</th>
                                    <th style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="../assets/jquery/jquery.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script src="../js/gestionDespachos.js"></script>
    
    <script>
        // Inicializar tooltips de Bootstrap 5
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    </script>
</body>
</html>
