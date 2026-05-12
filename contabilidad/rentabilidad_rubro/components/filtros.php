<?php
/**
 * filtros.php
 * Componente de filtros del reporte de rentabilidad por rubro
 */
?>
<section class="filtros-bar" id="filtrosBar">
    <div class="filtros-inner">

        <div class="filtro-group">
            <label class="filtro-label" for="inputDesde">
                <i class="bi bi-calendar3"></i>
                Período Desde
            </label>
            <input
                type="text"
                id="inputDesde"
                class="filtro-input"
                placeholder="Ej: 1-2025"
                maxlength="7"
                autocomplete="off"
            >
        </div>

        <div class="filtro-group">
            <label class="filtro-label" for="inputHasta">
                <i class="bi bi-calendar3-range"></i>
                Período Hasta
            </label>
            <input
                type="text"
                id="inputHasta"
                class="filtro-input"
                placeholder="Ej: 12-2025"
                maxlength="7"
                autocomplete="off"
            >
        </div>

        <div class="filtro-group">
            <label class="filtro-label" for="selectCanal">
                <i class="bi bi-shop"></i>
                Canal
            </label>
            <select id="selectCanal" class="filtro-select">
                <option value="">— Todos los canales —</option>
            </select>
        </div>

        <!-- Rubro (visible en solapas 2 y 3) -->
        <div class="filtro-group" id="filtroRubroGroup" style="display:none;">
            <label class="filtro-label" for="selectRubro">
                <i class="bi bi-tag"></i>
                Rubro
            </label>
            <select id="selectRubro" class="filtro-select">
                <option value="">— Todos los rubros —</option>
            </select>
        </div>

        <!-- Color (visible solo en solapa 3, en cascada con Rubro) -->
        <div class="filtro-group" id="filtroColorGroup" style="display:none;">
            <label class="filtro-label" for="selectColor">
                <i class="bi bi-palette"></i>
                Color
            </label>
            <select id="selectColor" class="filtro-select">
                <option value="">— Todos los colores —</option>
            </select>
        </div>

        <div class="filtro-group">
            <label class="filtro-label">
                <i class="bi bi-currency-exchange"></i>
                Moneda
            </label>
            <div class="moneda-toggle">
                <input type="radio" name="moneda" id="monedaARS" value="ARS" checked>
                <label for="monedaARS">$ ARS</label>
                <input type="radio" name="moneda" id="monedaUSD" value="USD">
                <label for="monedaUSD">U$S</label>
            </div>
        </div>

        <div class="filtro-actions">
            <button id="btnAplicar" class="btn-aplicar" type="button">
                <i class="bi bi-search"></i>
                <span>Aplicar</span>
            </button>

            <button id="btnExportar" class="btn-exportar" type="button" disabled>
                <i class="bi bi-file-earmark-excel"></i>
                <span>Excel</span>
            </button>
        </div>

    </div>

    <div id="alertaFiltros" class="filtros-alerta" style="display:none;"></div>
</section>
