<?php
// Calcular el primer y último día del mes anterior
$previous_month_first = date('Y-m-01', strtotime("first day of last month"));
$previous_month_last = date('Y-m-t', strtotime("last day of last month"));
?>

<div class="integridadVentas_tab-panel p-4">
    
    <!-- Filtros -->
    <div class="integridadVentas_filters mb-4">
        <form id="form-consulta-control">
            <div class="row align-items-end g-3">
                <div class="col-md-2">
                    <label for="fecha-desde-control" class="form-label">Desde:</label>
                    <input type="date" class="form-control" id="fecha-desde-control" value="<?= $previous_month_first ?>">
                </div>
                <div class="col-md-2">
                    <label for="fecha-hasta-control" class="form-label">Hasta:</label>
                    <input type="date" class="form-control" id="fecha-hasta-control" value="<?= $previous_month_last ?>">
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-consultar-control" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Consultar
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-descargar-excel-control" class="btn btn-success w-100">
                        <i class="bi bi-file-earmark-excel"></i> Descargar Resumen
                    </button>
                </div>
                <div class="col-md-4">
                    <div class="integridadVentas_info-box">
                        <i class="bi bi-info-circle me-2"></i>
                        <span>Comparación de ventas entre central y local por sucursal</span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Resultado de la consulta -->
    <div id="resultado-consulta-control" class="integridadVentas_table-wrapper">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover integridadVentas_table" id="tabla-control-ventas">
                <thead>
                    <tr>
                        <th>Nro. Sucursal</th>
                        <th>Cod. Sucursal</th>
                        <th>Importe Central</th>
                        <th>Importe Local</th>
                        <th>Diferencia</th>
                        <th>Estado</th>
                        <th>Últ. Actualización</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-resultados-control">
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            <i class="bi bi-info-circle me-2"></i>
                            Haga clic en "Consultar" para cargar los datos
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="table-dark">
                        <th colspan="2" class="text-end">TOTALES:</th>
                        <th id="total-importe-central">$ 0.00</th>
                        <th id="total-importe-local">$ 0.00</th>
                        <th id="total-diferencia">$ 0.00</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
</div>
