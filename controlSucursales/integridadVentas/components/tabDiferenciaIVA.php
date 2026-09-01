<?php
// Calcular el primer y último día del mes anterior
$previous_month_first = date('Y-m-01', strtotime("first day of last month"));
$previous_month_last = date('Y-m-t', strtotime("last day of last month"));
?>

<div class="integridadVentas_tab-panel p-4">
    
    <!-- Filtros -->
    <div class="integridadVentas_filters mb-4">
        <form id="form-consulta-iva">
            <div class="row align-items-end g-3">
                <div class="col-md-2">
                    <label for="fecha-desde-iva" class="form-label">Desde:</label>
                    <input type="date" class="form-control" id="fecha-desde-iva" value="<?= $previous_month_first ?>">
                </div>
                <div class="col-md-2">
                    <label for="fecha-hasta-iva" class="form-label">Hasta:</label>
                    <input type="date" class="form-control" id="fecha-hasta-iva" value="<?= $previous_month_last ?>">
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-consultar-iva" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Consultar
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-descargar-excel-iva" class="btn btn-success w-100">
                        <i class="bi bi-file-earmark-excel"></i> Descargar Resumen
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-descargar-detalle-iva" class="btn btn-outline-success w-100">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Descargar Detalle
                    </button>
                </div>
                <div class="col-md-2">
                    <div class="integridadVentas_info-box">
                        <i class="bi bi-info-circle me-2"></i>
                        <span>Comparación de IVA Ventas entre central y local por sucursal</span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Resultado de la consulta -->
    <div id="resultado-consulta-iva" class="integridadVentas_table-wrapper">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover integridadVentas_table" id="tabla-diferencia-iva">
                <thead>
                    <tr>
                        <th>Nro. Sucursal</th>
                        <th>Cod. Sucursal</th>
                        <th>Comprobantes con Dif.</th>
                        <th>Importe Local Total</th>
                        <th>Importe Central Total</th>
                        <th>Diferencia Neta</th>
                        <th>Estado</th>
                        <th>Últ. Actualización</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-resultados-iva">
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            <i class="bi bi-info-circle me-2"></i>
                            Haga clic en "Consultar" para cargar los datos
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="table-dark">
                        <th colspan="2" class="text-end">TOTALES:</th>
                        <th id="total-comprobantes-dif">0</th>
                        <th id="total-importe-local-iva">$ 0.00</th>
                        <th id="total-importe-central-iva">$ 0.00</th>
                        <th id="total-diferencia-neta-iva">$ 0.00</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
</div>
