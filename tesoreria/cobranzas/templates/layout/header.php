<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Cobranzas</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
    <!-- ======================= LIBRERÍA NUEVA ======================= -->
    <!-- CSS para jQuery UI Datepicker (Tema base) -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <!-- ============================================================== -->

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body class="bg-light">
    <div class="container-fluid mt-4">
        <header class="d-flex justify-content-end align-items-center mb-3">

            <!-- ======================= INICIO DE LA LÓGICA CORREGIDA ======================= -->
            <?php 
            $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
            $rol = $_SESSION['usuario_rol'] ?? '';
            // Ocultar botón de ayuda para mayoristas o en la vista mayoristas.php
            if ($rol !== 'mayoristas' && $currentPage !== 'mayoristas.php') {
                $ayuda_url = ($rol === 'admin') ? 'capacitacion.php' : 'ayuda_cliente.php';
            ?>
                <a href="<?php echo $ayuda_url; ?>" class="btn btn-sm btn-outline-info me-3" title="Ayuda y Guía de Uso">
                    <i class="fa-solid fa-question-circle"></i> Ayuda
                </a>
            <?php 
            } 
            ?>
            <!-- ======================== FIN DE LA LÓGICA CORREGIDA ========================= -->

            <span class="me-3">Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></span>
            <a href="api/auth_controller.php?action=logout" class="btn btn-outline-danger btn-sm">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </a>
        </header>