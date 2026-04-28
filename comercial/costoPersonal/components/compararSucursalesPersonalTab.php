<?php
// Variables disponibles desde index.php: $todosLosLocales, $rangoDefault
?>
<!-- Pestaña 5: Comparar Sucursales -->
<div class="comparar-cp-container">

    <!-- Filtros -->
    <div style="background:#fff;border-radius:12px;padding:22px 25px;margin-bottom:22px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #3498db;">
        <div style="display:flex;align-items:center;margin-bottom:18px;font-size:17px;font-weight:700;color:#2c3e50;">
            <i class="bi bi-funnel" style="margin-right:10px;color:#3498db;font-size:20px;"></i>
            Filtros de Comparación
        </div>
        <div class="row">
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-calendar-event"></i> Fecha Desde
                </label>
                <input type="date" class="form-control" id="cpCmpDesde" value="<?= $rangoDefault['desde'] ?>"
                       style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-calendar-event"></i> Fecha Hasta
                </label>
                <input type="date" class="form-control" id="cpCmpHasta" value="<?= $rangoDefault['hasta'] ?>"
                       style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-building"></i> Sucursal 1
                </label>
                <select class="form-control" id="cpCmpSuc1"
                        style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($todosLosLocales as $local): ?>
                        <option value="<?= htmlspecialchars($local['ID']) ?>">
                            <?= htmlspecialchars($local['DESC_SUCURSAL'] ?? $local['SUCURSAL'] ?? $local['ID']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-building"></i> Sucursal 2
                </label>
                <select class="form-control" id="cpCmpSuc2"
                        style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($todosLosLocales as $local): ?>
                        <option value="<?= htmlspecialchars($local['ID']) ?>">
                            <?= htmlspecialchars($local['DESC_SUCURSAL'] ?? $local['SUCURSAL'] ?? $local['ID']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;">
            <button type="button" class="btn btn-primary" id="cpCmpBtnComparar"
                    style="border-radius:8px;font-weight:600;padding:9px 22px;">
                <i class="bi bi-arrow-left-right"></i> Comparar
            </button>
            <button type="button" class="btn btn-secondary" id="cpCmpBtnLimpiar"
                    style="border-radius:8px;font-weight:600;padding:9px 22px;">
                <i class="bi bi-x-circle"></i> Limpiar
            </button>
            <button type="button" class="pdf-btn" id="cpCmpBtnPDF" style="display:none;">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </button>
        </div>
    </div>

    <!-- KPI diferencia -->
    <div id="cpCmpKpi" style="display:none;margin-bottom:22px;">
        <div style="background:#fff;border-radius:12px;padding:22px 25px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #e74c3c;">
            <div style="display:flex;align-items:center;margin-bottom:16px;font-size:16px;font-weight:600;color:#2c3e50;">
                <i class="bi bi-percent" style="color:#e74c3c;font-size:18px;margin-right:8px;"></i>
                Diferencia de Costo de Personal
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;">
                <div style="flex:1;text-align:center;padding:16px;background:#f8f9fa;border-radius:10px;min-width:130px;">
                    <span id="cpCmpNomSuc1" style="display:block;font-size:13px;color:#7f8c8d;margin-bottom:6px;font-weight:500;">Sucursal 1</span>
                    <span id="cpCmpValSuc1" style="display:block;font-size:26px;font-weight:800;color:#3498db;">—</span>
                </div>
                <div id="cpCmpDifCard" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:16px;border-radius:10px;background:#ecf0f1;min-width:140px;">
                    <i class="bi bi-arrow-right" style="font-size:20px;color:#95a5a6;"></i>
                    <span id="cpCmpDifVal" style="font-size:22px;font-weight:800;color:#2c3e50;">—</span>
                    <span id="cpCmpDifTexto" style="font-size:12px;font-weight:700;text-transform:uppercase;color:#7f8c8d;letter-spacing:0.5px;">—</span>
                </div>
                <div style="flex:1;text-align:center;padding:16px;background:#f8f9fa;border-radius:10px;min-width:130px;">
                    <span id="cpCmpNomSuc2" style="display:block;font-size:13px;color:#7f8c8d;margin-bottom:6px;font-weight:500;">Sucursal 2</span>
                    <span id="cpCmpValSuc2" style="display:block;font-size:26px;font-weight:800;color:#9b59b6;">—</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla comparación -->
    <div id="cpCmpTableSection" style="display:none;background:#fff;border-radius:12px;padding:22px 25px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid #ecf0f1;">
            <h3 style="margin:0;font-size:17px;font-weight:700;color:#2c3e50;">
                <i class="bi bi-table" style="color:#3498db;margin-right:8px;"></i>
                Comparación de Costos de Personal
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered" id="cpTablaComparacion" style="font-size:13px;">
                <!-- Llenado dinámico -->
            </table>
        </div>
    </div>

    <!-- Estado vacío -->
    <div id="cpCmpEmptyState" style="text-align:center;padding:60px 20px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
        <i class="bi bi-arrow-left-right" style="font-size:4rem;color:#bdc3c7;display:block;margin-bottom:20px;"></i>
        <h3 style="color:#2c3e50;font-weight:600;margin-bottom:10px;">Seleccione dos sucursales para comparar</h3>
        <p style="color:#7f8c8d;font-size:15px;">Elija dos sucursales distintas y un rango de fechas para realizar la comparación de costos de personal.</p>
    </div>

</div>
