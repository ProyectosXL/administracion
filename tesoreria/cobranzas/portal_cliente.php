<?php
session_start();
// Protección de la ruta: solo usuarios logueados con rol 'cliente' pueden acceder.
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'cliente') {
    header('Location: login.php');
    exit();
}
include 'templates/layout/header.php'; // Reutilizamos el header
?>

<main>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">
            <i class="fa-solid fa-handshake text-primary"></i>
            Mis Propuestas de Pago
        </h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabla-propuestas-cliente" class="table table-striped table-hover" style="width:100%">
                    <!-- El contenido se cargará con JavaScript -->
                </table>
            </div>
        </div>
    </div>
</main>

<!-- YA NO ESTÁ EL HTML DEL MODAL AQUÍ -->

<?php include 'templates/layout/footer.php'; // Reutilizamos el footer ?>

<!-- Incluimos un JS específico para esta página -->
<script src="assets/js/portal_cliente.js"></script>