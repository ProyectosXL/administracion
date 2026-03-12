<?php
/**
 * modal_procesamiento.php
 * Modal para procesar períodos sin datos en RO_T_RENT_BRUTA_RUBRO
 */
?>
<div id="modalProcesamiento" class="mp-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="mpTitulo">
    <div class="mp-card">

        <!-- Header -->
        <div class="mp-header">
            <div class="mp-header-icon">
                <i class="bi bi-calendar-x"></i>
            </div>
            <div class="mp-header-text">
                <h2 id="mpTitulo">Meses sin datos calculados</h2>
                <p>Procesá los meses faltantes para generar el informe.</p>
            </div>
            <button class="mp-close" id="mpBtnCerrar" title="Cerrar" type="button">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Lista de períodos -->
        <div class="mp-body">
            <div class="mp-section-label">Períodos seleccionados</div>
            <ul class="mp-lista" id="mpLista"></ul>
        </div>

        <!-- Barra de progreso -->
        <div class="mp-progress-wrap" id="mpProgressWrap" style="display:none;">
            <div class="mp-progress-bar" id="mpProgressBar" style="width:0%"></div>
        </div>

        <!-- Footer -->
        <div class="mp-footer">
            <button class="mp-btn-secundario" id="mpBtnCancelar" type="button">Cancelar</button>
            <button class="mp-btn-primario" id="mpBtnProcesar" type="button">
                <i class="bi bi-play-circle-fill"></i>
                <span id="mpBtnLabel">Procesar todos</span>
            </button>
        </div>

    </div>
</div>
