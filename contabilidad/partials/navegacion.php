<?php
// Barra de navegación entre la pantalla principal (Control de Gastos) y las secundarias.
// Estilos en css/control-gastos-modern.css (.cgm-nav)

$paginasNavegacion = [
    'controlGastos.php'           => ['icono' => 'bi-ui-checks', 'texto' => 'Control de Gastos'],
    'cargaGastos.php'             => ['icono' => 'bi-plus-square', 'texto' => 'Carga de Gastos'],
    'consultaGastos.php'          => ['icono' => 'bi-search',    'texto' => 'Consulta de Gastos'],
    'gestionRelacionesCuenta.php' => ['icono' => 'bi-diagram-3', 'texto' => 'Relaciones de Cuentas'],
];
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<nav class="cgm-nav" aria-label="Navegación de contabilidad">
    <?php foreach ($paginasNavegacion as $archivo => $pagina) { ?>
        <a href="<?= $archivo ?>" class="cgm-nav-link <?= $paginaActual === $archivo ? 'active' : '' ?>" <?= $paginaActual === $archivo ? 'aria-current="page"' : '' ?>>
            <i class="bi <?= $pagina['icono'] ?>"></i> <?= $pagina['texto'] ?>
        </a>
    <?php } ?>
</nav>
