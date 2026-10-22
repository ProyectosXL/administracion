<?php
/**
 * Valorización de stock. La sirve Controller/TabController.php, que ya
 * verificó ra.tab.valorizacion_stock.
 *
 * Los filtros, el resumen y la descarga los maneja Js/valorizacion.js. La
 * pestaña no tiene nada editable: el data-puede-editar está por convención,
 * como en todas.
 */
?>
<section class="ra-pestana" id="raValorizacion" data-tab="valorizacion_stock"<?php echo AuthReportes::atributoEdicion('valorizacion_stock'); ?>>

    <div class="ra-card ra-filtros">
        <div class="ra-card-titulo"><i class="bi bi-funnel"></i> Valorización de stock — LAKERS SA</div>

        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3 col-xl-2">
                <label class="form-label" for="raValFecha">Fecha de cierre</label>
                <select class="form-select form-select-sm" id="raValFecha" disabled>
                    <option>Cargando…</option>
                </select>
            </div>

            <div class="col-12 col-md-4 col-xl-3">
                <span class="form-label d-block">Costo</span>
                <div class="btn-group btn-group-sm w-100" role="group" aria-label="Costo">
                    <input type="radio" class="btn-check" name="raValModo" id="raValModoHist" value="historico" checked>
                    <label class="btn btn-outline-secondary" for="raValModoHist"
                           title="STOCK y VALORIZACION tal como se grabaron en el cierre">Histórico (tal como se grabó)</label>
                    <input type="radio" class="btn-check" name="raValModo" id="raValModoAct" value="actual">
                    <label class="btn btn-outline-secondary" for="raValModoAct"
                           title="El stock del cierre, valorizado con el costo de hoy">Actual (costo de hoy)</label>
                </div>
            </div>

            <div class="col-6 col-md-2 col-xl-2">
                <label class="form-label" for="raValSector">Sector</label>
                <select class="form-select form-select-sm" id="raValSector">
                    <option value="">Todos</option>
                    <option value="CENTRAL">Central</option>
                    <option value="LOCALES">Locales</option>
                </select>
            </div>

            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label" for="raValSucursalBtn">Sucursal</label>
                <div class="dropdown">
                    <button class="form-select form-select-sm text-start text-truncate" type="button" id="raValSucursalBtn"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Todas</button>
                    <div class="dropdown-menu ra-sucursales p-2" aria-labelledby="raValSucursalBtn">
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-link btn-sm p-0" id="raValSucTodas" data-lectura>Todas</button>
                            <button type="button" class="btn btn-link btn-sm p-0" id="raValSucNinguna" data-lectura>Ninguna</button>
                        </div>
                        <div id="raValSucLista"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="raValArticulo">Artículo</label>
                <input type="text" class="form-control form-control-sm" id="raValArticulo" maxlength="50"
                       placeholder="Código o descripción" autocomplete="off">
            </div>

            <div class="col-12 col-md-6 col-xl-12 d-flex gap-2 justify-content-xl-end">
                <button type="button" class="btn btn-sm btn-ra" id="raValConsultar" disabled>
                    <i class="bi bi-search"></i> Consultar
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" id="raValExcel" disabled
                        title="Consultá primero: la descarga usa los filtros de la última consulta">
                    <i class="bi bi-file-earmark-excel"></i> Descargar Excel
                </button>
            </div>
        </div>

        <div class="small text-muted mt-2" id="raValCambio" hidden>
            <i class="bi bi-exclamation-circle"></i> Cambiaste los filtros. Tocá <strong>Consultar</strong> para
            actualizar: la pantalla y la descarga siguen mostrando la última consulta.
        </div>
    </div>

    <div id="raValResultado" hidden>
        <div class="ra-leyenda" id="raValLeyenda"></div>
        <div class="ra-kpis" id="raValKpis"></div>

        <div class="ra-card">
            <div class="ra-card-titulo"><i class="bi bi-table"></i> Resumen por sector y sucursal</div>
            <div class="table-responsive">
                <table class="table table-sm ra-tabla mb-0" id="raValResumen">
                    <thead>
                        <tr>
                            <th>Sector</th>
                            <th>Sucursal</th>
                            <th class="num">Stock</th>
                            <th class="num">Valorización</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ra-estado" id="raValVacio" hidden>
        <div class="ra-estado-icon"><i class="bi bi-inbox"></i></div>
        <h3>No hay stock para estos filtros</h3>
        <p>Probá con otra sucursal, otro sector o un texto de artículo más corto.</p>
    </div>

    <div class="ra-estado" id="raValInicio">
        <div class="ra-estado-icon"><i class="bi bi-box-seam"></i></div>
        <h3>Elegí los filtros y tocá Consultar</h3>
        <p>El detalle por artículo se descarga en Excel.</p>
    </div>
</section>
