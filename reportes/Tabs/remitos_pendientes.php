<?php
/**
 * Remitos pendientes de facturar. La sirve Controller/TabController.php, que
 * ya verificó ra.tab.remitos_pendientes.
 *
 * Los filtros, el resumen y la descarga los maneja Js/remitos.js. La pestaña
 * no tiene nada editable: el data-puede-editar está por convención, como en
 * todas.
 */
?>
<section class="ra-pestana" id="raRemitos" data-tab="remitos_pendientes"<?php echo AuthReportes::atributoEdicion('remitos_pendientes'); ?>>

    <div class="ra-estado" id="raRemInicio">
        <div class="ra-estado-icon"><i class="bi bi-receipt"></i></div>
        <h3>Remitos pendientes de facturar</h3>
        <p>El reporte está en construcción.</p>
    </div>
</section>
