<?php
// Variables disponibles desde index.php: $rangoDefault
?>
<!-- Pestaña 3: Reporte a Fecha (transpuesto) -->
<div class="reporte-fecha-cp-container">

    <!-- Filtros -->
    <div style="background:#fff;border-radius:12px;padding:22px 25px;margin-bottom:22px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #9b59b6;">
        <div style="display:flex;align-items:center;margin-bottom:18px;font-size:17px;font-weight:700;color:#2c3e50;">
            <i class="bi bi-funnel" style="margin-right:10px;color:#9b59b6;font-size:20px;"></i>
            Período de Consulta
        </div>
        <div class="row align-items-end">
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-calendar-event"></i> Fecha Desde
                </label>
                <input type="date" class="form-control" id="cpRafDesde" value="<?= $rangoDefault['desde'] ?>"
                       style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-calendar-event"></i> Fecha Hasta
                </label>
                <input type="date" class="form-control" id="cpRafHasta" value="<?= $rangoDefault['hasta'] ?>"
                       style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-primary w-100" id="cpRafBtnAplicar"
                        style="border-radius:8px;font-weight:600;height:calc(2.25rem + 4px);">
                    <i class="bi bi-search"></i> Consultar
                </button>
            </div>
            <div class="col-md-2">
                <button type="button" class="pdf-btn" id="cpRafBtnPDF" style="display:none;width:100%;">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </button>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div id="cpRafKpisSection" style="display:none;margin-bottom:22px;">
        <div class="row">
            <div class="col-md-3">
                <div style="background:#fff;border-radius:12px;padding:18px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #3498db;">
                    <div style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                        <i class="bi bi-building"></i> Sucursales
                    </div>
                    <div id="cpRafKpiSucursales" style="font-size:28px;font-weight:800;color:#2c3e50;">—</div>
                </div>
            </div>
            <div class="col-md-3">
                <div style="background:#fff;border-radius:12px;padding:18px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #e67e22;">
                    <div style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                        <i class="bi bi-percent"></i> % Costo Red
                    </div>
                    <div id="cpRafKpiPct" style="font-size:28px;font-weight:800;color:#2c3e50;">—</div>
                </div>
            </div>
            <div class="col-md-3">
                <div style="background:#fff;border-radius:12px;padding:18px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #27ae60;">
                    <div style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                        <i class="bi bi-trophy-fill"></i> Mejor Sucursal
                    </div>
                    <div id="cpRafKpiMejorVal" style="font-size:22px;font-weight:800;color:#27ae60;">—</div>
                    <div id="cpRafKpiMejorNombre" style="font-size:12px;color:#95a5a6;margin-top:3px;">—</div>
                </div>
            </div>
            <div class="col-md-3">
                <div style="background:#fff;border-radius:12px;padding:18px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #e74c3c;">
                    <div style="font-size:11px;font-weight:700;color:#7f8c8d;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                        <i class="bi bi-exclamation-triangle-fill"></i> Peor Sucursal
                    </div>
                    <div id="cpRafKpiPeorVal" style="font-size:22px;font-weight:800;color:#e74c3c;">—</div>
                    <div id="cpRafKpiPeorNombre" style="font-size:12px;color:#95a5a6;margin-top:3px;">—</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Spinner -->
    <div id="cpRafLoading" style="display:none;text-align:center;padding:60px 20px;">
        <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;"></div>
        <p style="margin-top:15px;color:#7f8c8d;font-weight:500;">Cargando datos de todas las sucursales…</p>
    </div>

    <!-- Tabla transpuesta -->
    <div id="cpRafTableSection" style="display:none;background:#fff;border-radius:12px;padding:22px 25px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid #ecf0f1;">
            <h3 style="margin:0;font-size:17px;font-weight:700;color:#2c3e50;">
                <i class="bi bi-table" style="color:#9b59b6;margin-right:8px;"></i>
                Todas las Sucursales — Comparativo
            </h3>
            <span id="cpRafPeriodLabel" style="font-size:13px;color:#95a5a6;font-style:italic;"></span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered" id="cpTablaReporteFecha" style="font-size:13px;">
                <!-- Llenado dinámico -->
            </table>
        </div>
    </div>

    <!-- Estado vacío -->
    <div id="cpRafEmptyState" style="text-align:center;padding:60px 20px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
        <i class="bi bi-calendar-range" style="font-size:4rem;color:#bdc3c7;display:block;margin-bottom:20px;"></i>
        <h3 style="color:#2c3e50;font-weight:600;margin-bottom:10px;">Reporte comparativo de todas las sucursales</h3>
        <p style="color:#7f8c8d;font-size:15px;">Seleccione un rango de fechas y haga clic en <strong>Consultar</strong> para ver los costos de personal de todas las sucursales en columnas.</p>
    </div>

</div>
