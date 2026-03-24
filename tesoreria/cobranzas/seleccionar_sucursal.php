
<?php
// tesoreria/cobranzas/seleccionar_sucursal.php
session_start();

$cod_client_entrada = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';

if (empty($cod_client_entrada)) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Sucursal — Cobranzas XL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/seleccionar_sucursal.css">
</head>
<body>
    <div class="page-wrapper">

        <header class="page-header">
            <div class="header-top">
                <div class="brand-logo">
                    <div class="logo-icon">
                        <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="8" fill="currentColor"/>
                            <text x="20" y="27" text-anchor="middle" fill="white" font-size="18" font-weight="700" font-family="sans-serif">XL</text>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <div class="logo-title">Extra Large</div>
                        <div class="logo-subtitle">Portal de Cobranzas</div>
                    </div>
                </div>
                <a href="../../../sistemas/login.php?logout=1" class="btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Cerrar sesión</span>
                </a>
            </div>
            <div class="header-main">
                <h1 class="page-title" id="titulo-card">Seleccioná tu sucursal</h1>
                <p class="page-description" id="subtitulo-card">
                    Tu cuenta tiene acceso a múltiples locales. Elegí con cuál querés trabajar.
                </p>
            </div>
        </header>

        <div class="main-card">
            <div id="contenido">
                <div class="loading-state">
                    <div class="spinner-ring"></div>
                    <span>Cargando sucursales…</span>
                </div>
            </div>
        </div>

        <p class="page-footer">XL Extra Large — Sistema de Gestión</p>
    </div>

    <script>
        // Pasar el código de cliente al script externo
        window.COD_CLIENT_ENTRADA = <?php echo json_encode($cod_client_entrada); ?>;
    </script>
    <script src="assets/js/seleccionar_sucursal.js"></script>
</body>
</html>