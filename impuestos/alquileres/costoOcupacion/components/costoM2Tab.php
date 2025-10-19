<?php
    // Calcular fechas: 2 meses atrás completos
    $fechaHoy = new DateTime();
    
    // Ir 2 meses atrás
    $fechaDoseMesesAtras = clone $fechaHoy;
    $fechaDoseMesesAtras->modify('-2 months');
    
    // Primer día del mes (2 meses atrás)
    $fechaDesdeDefault = $fechaDoseMesesAtras->format('Y-m-01');
    
    // Último día del mes (2 meses atrás)
    $fechaHastaDefault = $fechaDoseMesesAtras->format('Y-m-t');
?>
<div class="tab-pane fade" id="costo-m2" role="tabpanel">
    <div class="container-fluid mt-4">
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="costoM2FechaDesde">Fecha Desde:</label>
                        <input type="date" id="costoM2FechaDesde" class="form-control" 
                               value="<?php echo $fechaDesdeDefault; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="costoM2FechaHasta">Fecha Hasta:</label>
                        <input type="date" id="costoM2FechaHasta" class="form-control" 
                               value="<?php echo $fechaHastaDefault; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button id="btnConsultarCostoM2" class="btn btn-primary btn-block">
                            <i class="bi bi-search"></i> Consultar
                        </button>
                    </div>
                </div>
                <!-- Leyenda de período seleccionado -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-info mb-0" role="alert">
                            <i class="bi bi-calendar-range"></i>
                            <strong>Período seleccionado:</strong> 
                            <span id="leyendaPeriodoCostoM2">
                                <?php 
                                    $fechaDesdeObj = DateTime::createFromFormat('Y-m-d', $fechaDesdeDefault);
                                    $fechaHastaObj = DateTime::createFromFormat('Y-m-d', $fechaHastaDefault);
                                    echo $fechaDesdeObj->format('d/m/Y') . ' al ' . $fechaHastaObj->format('d/m/Y');
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Indicadores de Promedios -->
        <div class="row mb-4" id="costoM2Promedios" style="display: none;">
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Superficie Total</h6>
                        <h4 id="totalSuperficie">0</h4>
                        <small>M² totales</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Promedio Expensas/M²</h6>
                        <h4 id="promedioExpensasM2">$0</h4>
                        <small>Por metro cuadrado</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Promedio Alquiler/M²</h6>
                        <h4 id="promedioAlquilerM2">$0</h4>
                        <small>Por metro cuadrado</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Sucursales con Datos</h6>
                        <h4 id="countConSuperficie">0</h4>
                        <small>Con superficie registrada</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Resultados -->
        <div class="card" id="cardTablaCostoM2" style="display: none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Costo de Ocupación por Metro Cuadrado</h5>
                <div>
                    <button class="btn btn-success btn-sm" id="btnExportarCostoM2">
                        <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaCostoM2" class="table table-striped table-hover table-sm" style="width:100%">
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th>Nombre</th>
                                <th class="text-right">Superficie (M²)</th>
                                <th class="text-right">Expensas Totales</th>
                                <th class="text-right">Expensas/M²</th>
                                <th class="text-right">Alquiler Total</th>
                                <th class="text-right">Alquiler/M²</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyCostoM2">
                            <!-- Datos cargados dinámicamente -->
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="2">TOTALES / PROMEDIOS</td>
                                <td class="text-right" id="footerSuperficie">-</td>
                                <td class="text-right" id="footerExpensas">-</td>
                                <td class="text-right" id="footerExpensasM2">-</td>
                                <td class="text-right" id="footerAlquiler">-</td>
                                <td class="text-right" id="footerAlquilerM2">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="card mt-3" id="leyendaCostoM2" style="display: none;">
            <div class="card-body">
                <h6 class="card-title">Leyenda de Colores</h6>
                <div class="row">
                    <div class="col-md-4">
                        <span class="badge badge-success">Verde</span>
                        <small>Por debajo del promedio (ahorro)</small>
                    </div>
                    <div class="col-md-4">
                        <span class="badge badge-warning">Amarillo</span>
                        <small>±10% del promedio (dentro del rango)</small>
                    </div>
                    <div class="col-md-4">
                        <span class="badge badge-danger">Rojo</span>
                        <small>Por encima del promedio (exceso)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loadingCostoM2" class="text-center" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <p class="mt-2">Cargando datos...</p>
        </div>
    </div>
</div>
