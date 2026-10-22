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

    <div class="ra-card ra-filtros">
        <div class="ra-card-titulo"><i class="bi bi-funnel"></i> Remitos pendientes de facturar — LAKERS SA</div>

        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-xl-2">
                <label class="form-label" for="raRemPeriodo">Período (fecha de remito)</label>
                <select class="form-select form-select-sm" id="raRemPeriodo" disabled>
                    <option>Cargando…</option>
                </select>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <label class="form-label" for="raRemDesde">Desde</label>
                <input type="date" class="form-control form-control-sm" id="raRemDesde" disabled>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <label class="form-label" for="raRemHasta"
                       title="La fecha de corte: pendiente quiere decir sin facturar a esta fecha">Hasta (fecha de corte)</label>
                <input type="date" class="form-control form-control-sm" id="raRemHasta" disabled>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <label class="form-label" for="raRemTipo">Tipo de cliente</label>
                <select class="form-select form-select-sm" id="raRemTipo">
                    <option value="">Todos</option>
                    <option value="F">Franquicias (F)</option>
                    <option value="L">Gestión asistida (L)</option>
                    <option value="M">Mayoristas (M)</option>
                </select>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <label class="form-label" for="raRemClienteBtn">Cliente</label>
                <div class="dropdown">
                    <button class="form-select form-select-sm text-start text-truncate" type="button" id="raRemClienteBtn"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Todos</button>
                    <div class="dropdown-menu ra-sucursales ra-clientes p-2" aria-labelledby="raRemClienteBtn">
                        <input type="search" class="form-control form-control-sm mb-2" id="raRemClienteBuscar"
                               placeholder="Código o razón social" autocomplete="off">
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-link btn-sm p-0" id="raRemCliNinguno" data-lectura>Ninguno (todos)</button>
                        </div>
                        <div id="raRemCliLista"><div class="small text-muted">Elegí el período.</div></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-8 col-xl-12 d-flex gap-2 justify-content-xl-end">
                <button type="button" class="btn btn-sm btn-ra" id="raRemConsultar" disabled>
                    <i class="bi bi-search"></i> Consultar
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" id="raRemExcel" disabled
                        title="Consultá primero: la descarga usa los filtros de la última consulta">
                    <i class="bi bi-file-earmark-excel"></i> Descargar Excel
                </button>
            </div>
        </div>

        <div class="small text-danger mt-2" id="raRemError" hidden></div>

        <div class="small text-muted mt-2" id="raRemCambio" hidden>
            <i class="bi bi-exclamation-circle"></i> Cambiaste los filtros. Tocá <strong>Consultar</strong> para
            actualizar: la pantalla y la descarga siguen mostrando la última consulta.
        </div>
    </div>

    <div id="raRemResultado" hidden>
        <div class="ra-leyenda" id="raRemLeyenda"></div>
        <div class="ra-kpis" id="raRemKpis"></div>

        <div class="ra-card">
            <div class="ra-card-titulo"><i class="bi bi-table"></i> Resumen por tipo de cliente</div>
            <div class="table-responsive">
                <table class="table table-sm ra-tabla mb-0" id="raRemTipos">
                    <thead>
                        <tr>
                            <th>Tipo de cliente</th>
                            <th class="num">Remitos</th>
                            <th class="num">Importe neto</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="ra-card">
            <div class="ra-card-titulo"><i class="bi bi-people"></i> Resumen por cliente</div>
            <div class="table-responsive">
                <table class="table table-sm ra-tabla mb-0" id="raRemClientes">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Razón social</th>
                            <th>Tipo</th>
                            <th class="num">Remitos</th>
                            <th class="num">Importe pendiente</th>
                            <th class="num" title="De esos remitos, cuántos ya se facturaron después del corte">Ya facturados después</th>
                            <th class="num">Remito más viejo</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ra-estado" id="raRemVacio" hidden>
        <div class="ra-estado-icon"><i class="bi bi-check2-circle"></i></div>
        <h3>No hay remitos pendientes</h3>
        <p id="raRemVacioTexto"></p>
    </div>

    <div class="ra-estado" id="raRemInicio">
        <div class="ra-estado-icon"><i class="bi bi-receipt"></i></div>
        <h3>Elegí el período y tocá Consultar</h3>
        <p>Muestra los remitos que seguían sin facturar a la fecha Hasta, y cuáles ya se facturaron después.</p>
    </div>
</section>
