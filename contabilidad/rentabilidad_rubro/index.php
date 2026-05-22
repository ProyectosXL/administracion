<?php
/**
 * index.php
 * Reporte Web: Rentabilidad por Rubro
 * Módulo: Informe Económico — Contabilidad
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentabilidad por Rubro — Informe Económico</title>

    <!-- Google Fonts: DM Sans + DM Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS propio -->
    <link rel="stylesheet" href="css/rentabilidad_rubro.css">

    <!-- SheetJS para exportar Excel (desde CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
</head>
<body>

<div class="rr-wrapper">

    <!-- ── Topbar ── -->
    <header class="rr-topbar">
        <a href="http://192.168.0.13:8000/" class="rr-topbar-home" title="Menú principal">
            <img src="../image/home-button.png" alt="Home"
                 onerror="this.style.display='none'">
        </a>
        <a href="../controlGastos.php" class="rr-topbar-home" title="Control de Gastos" style="margin-left:4px;">
            <i class="bi bi-arrow-left" style="font-size:18px;"></i>
        </a>

        <div class="rr-topbar-divider"></div>

        <div class="rr-topbar-title">
            <div class="rr-topbar-icon">
                <i class="bi bi-bar-chart-line-fill"></i>
            </div>
            <div class="rr-topbar-text">
                <h1>Rentabilidad por Rubro</h1>
                <span>Informe Económico — Contabilidad</span>
            </div>
        </div>

        <button id="btnInfoReporte" class="rr-info-btn" type="button" title="Acerca del informe">
            <i class="bi bi-info-circle"></i>
        </button>
    </header>

    <!-- ── Filtros ── -->
    <?php include 'components/filtros.php'; ?>

    <!-- ── Tabs de navegación ── -->
    <?php include 'components/tabs.php'; ?>

    <!-- ── Contenido principal ── -->
    <main class="rr-content">
        <?php include 'components/tabla_reporte.php'; ?>
    </main>

</div>

<!-- Modal procesamiento de períodos -->
<?php include 'components/modal_procesamiento.php'; ?>

<!-- Modal de información del informe -->
<?php include 'components/modal_info.php'; ?>

<!-- JS propio -->
<script src="js/rentabilidad_rubro.js"></script>

</body>
</html>
