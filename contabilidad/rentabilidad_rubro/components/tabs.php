<?php
/**
 * tabs.php
 * Navegación de solapas del reporte de rentabilidad por rubro
 */
?>
<nav class="rr-tabs" id="rrTabs" role="tablist" aria-label="Vistas del reporte">
    <button class="rr-tab active" id="tab1Btn" role="tab"
            data-tab="1" aria-selected="true" aria-controls="tablaSection">
        <i class="bi bi-grid-3x3-gap"></i>
        Por Rubro
    </button>
    <button class="rr-tab" id="tab2Btn" role="tab"
            data-tab="2" aria-selected="false" aria-controls="tablaSection">
        <i class="bi bi-globe2"></i>
        Por Origen de Producción
    </button>
    <button class="rr-tab" id="tab3Btn" role="tab"
            data-tab="3" aria-selected="false" aria-controls="tablaSection">
        <i class="bi bi-tags"></i>
        Por Categoría
    </button>
</nav>
