<?php
/**
 * ABM de alias de proveedor.
 *
 * Fragmento reutilizable: lo incluye la pestaña de Parámetros y lo carga por
 * AJAX el modal del cronograma. Por eso no trae <html> ni <script src>: el JS
 * (abmAlias.js) lo carga cada página una sola vez.
 */
?>
<div class="content-card abm-alias">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title-custom mb-1">
                <i class="bi bi-person-badge"></i> Alias de Proveedores
            </h3>
            <p class="card-subtitle-custom mb-0">
                Dos letras que identifican al proveedor en los badges del cronograma
            </p>
        </div>
        <button class="btn btn-primary" id="btnNuevoAlias">
            <i class="bi bi-plus-circle"></i> Nuevo Alias
        </button>
    </div>

    <div class="alert alert-info m-3">
        <i class="bi bi-info-circle"></i>
        Sin alias configurado, el calendario muestra las <strong>2 primeras letras
        del nombre</strong> en gris e itálica. Eso no distingue proveedores con nombres
        parecidos: <code>ZEGUGU</code> y <code>ZEGUYI</code> derivan los dos
        <strong>GU</strong>.
    </div>

    <div class="table-responsive">
        <table class="table table-hover" id="tablaAlias">
            <thead>
                <tr>
                    <th style="width: 70px;">Alias</th>
                    <th style="width: 110px;">Código</th>
                    <th>Proveedor</th>
                    <th style="width: 90px;">OCs</th>
                    <th style="width: 90px;">Estado</th>
                    <th style="width: 130px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </td></tr>
            </tbody>
        </table>
    </div>

    <div class="m-3" id="proveedoresSinAlias"></div>
</div>

<!-- Formulario de alta y edición. Vive dentro del fragmento para que el modal
     del cronograma lo tenga disponible sin duplicar markup. -->
<div class="abm-alias-form" id="formAliasPanel" hidden>
    <div class="abm-alias-form-caja">
        <div class="abm-alias-form-titulo" id="formAliasTitulo">Nuevo alias</div>

        <div class="mb-3">
            <label for="aliasProveedor" class="form-label">
                Proveedor <span class="text-danger">*</span>
            </label>
            <select class="form-select" id="aliasProveedor"></select>
        </div>

        <div class="mb-3">
            <label for="aliasValor" class="form-label">
                Alias <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control alias-input" id="aliasValor"
                   maxlength="2" placeholder="LC" autocomplete="off">
            <div class="form-text">Exactamente 2 letras, de la A a la Z</div>
            <div class="alias-error" id="aliasError" hidden></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Vista previa en el calendario</label>
            <div class="preview-badge" id="previewAlias">
                <span class="preview-alias" id="previewAliasTexto">??</span>
                <span class="preview-contenedor">VER03-26</span>
                <span class="preview-iconos"><i class="bi bi-handbag"></i><i class="bi bi-wallet2"></i></span>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" id="btnCancelarAlias">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnGuardarAlias">Guardar</button>
        </div>
    </div>
</div>
