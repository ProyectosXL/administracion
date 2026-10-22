<?php
/**
 * index.php
 * Reportes de Administración.
 *
 * La página es una sola: barra de arriba, solapas y un panel por pestaña al
 * que el HTML llega por AJAX (Controller/TabController.php), igual que el
 * Informe Económico. Las solapas salen de Class/Menu.php filtradas por
 * permiso: una pestaña sin permiso no se dibuja, y si se la pide igual el
 * TabController contesta 403.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Class/Menu.php';

// Sin usuario del padrón no se muestra nada, ni siquiera la cáscara
if (!AuthReportes::estaAutenticado()) {
    header('Location: /sistemas/login.php');
    exit;
}

$u = AuthReportes::usuario();
$pestanas = Menu::permitidas();
$urlHub = '/sistemas/hub/index.php';

/** La versión de un archivo propio, para que el navegador no use uno viejo */
function raVersion($ruta) {
    return @filemtime(__DIR__ . '/' . $ruta) ?: 0;
}

if (!$pestanas) {
    http_response_code(403);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Administración | XL</title>
    <link rel="icon" type="image/jpg" href="../assets/images/logo.jpg">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Css/reportes.css?v=<?php echo raVersion('Css/reportes.css'); ?>">
</head>
<body>
<div class="ra-wrapper">

    <header class="ra-topbar">
        <a href="<?php echo $urlHub; ?>" class="ra-topbar-home" title="Volver al HUB">
            <i class="bi bi-house-door"></i>
        </a>

        <div class="ra-topbar-title">
            <div class="ra-topbar-icon"><i class="bi bi-file-earmark-spreadsheet"></i></div>
            <div>
                <h1>Reportes de Administración</h1>
                <span>Reportes a medida para Administración &amp; Finanzas</span>
            </div>
        </div>

        <div class="ra-topbar-user" title="<?php echo htmlspecialchars('@' . $u['username']); ?>">
            <i class="bi bi-person-circle"></i>
            <div>
                <strong><?php echo htmlspecialchars($u['nombre'] ?: $u['username']); ?></strong>
                <small><?php echo htmlspecialchars($u['rol_nombre']); ?></small>
            </div>
        </div>
    </header>

    <?php if (!$pestanas): ?>
        <main class="ra-content">
            <div class="ra-estado">
                <div class="ra-estado-icon"><i class="bi bi-lock"></i></div>
                <h3>Acceso restringido</h3>
                <p>El usuario <strong>@<?php echo htmlspecialchars($u['username']); ?></strong>
                   (rol <?php echo htmlspecialchars($u['rol_nombre']); ?>) no tiene ningún reporte
                   habilitado. Los accesos se gestionan desde Gestionusuarios.</p>
                <p><a href="<?php echo $urlHub; ?>">Volver al HUB</a></p>
            </div>
        </main>
    <?php else: ?>
        <nav class="ra-tabs" id="raTabs" role="tablist" aria-label="Reportes">
            <?php foreach ($pestanas as $i => $p): ?>
                <button class="ra-tab<?php echo $i === 0 ? ' active' : ''; ?>" role="tab" type="button"
                        data-tab="<?php echo htmlspecialchars($p['tab']); ?>">
                    <i class="bi <?php echo htmlspecialchars($p['icono']); ?>"></i>
                    <?php echo htmlspecialchars($p['nombre']); ?>
                </button>
            <?php endforeach; ?>
        </nav>

        <main class="ra-content" id="raContenido">
            <?php foreach ($pestanas as $p): ?>
                <div class="ra-panel" id="raPanel-<?php echo htmlspecialchars($p['tab']); ?>" hidden></div>
            <?php endforeach; ?>
        </main>
    <?php endif; ?>

</div>

<?php if ($pestanas): ?>
<script src="../assets/js/libs/1jquery-3.5.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php foreach (Menu::libs($pestanas) as $lib): ?>
<script src="<?php echo htmlspecialchars($lib); ?>"></script>
<?php endforeach; ?>
<script src="Js/permisos.js?v=<?php echo raVersion('Js/permisos.js'); ?>"></script>
<script src="Js/reportes.js?v=<?php echo raVersion('Js/reportes.js'); ?>"></script>
<?php foreach (Menu::scripts($pestanas) as $js): ?>
<script src="Js/<?php echo $js; ?>?v=<?php echo raVersion('Js/' . $js); ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
