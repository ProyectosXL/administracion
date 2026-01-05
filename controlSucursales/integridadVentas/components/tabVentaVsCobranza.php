<?php
// Configuración de fechas por defecto
$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");
?>

<div class="integridadVentas_tab-panel p-4">
    
    <!-- Filtros -->
    <div class="integridadVentas_filters mb-4">
        <form id="form-consulta-ventavscobranza" method="GET">
            <input type="hidden" name="tab" value="ventaVsCobranza">
            <div class="row align-items-end g-3">
                <div class="col-md-2">
                    <label for="fecha-desde-vvc" class="form-label">Desde:</label>
                    <input type="date" class="form-control" id="fecha-desde-vvc" name="desde" value="<?= $desde ?>">
                </div>
                <div class="col-md-2">
                    <label for="fecha-hasta-vvc" class="form-label">Hasta:</label>
                    <input type="date" class="form-control" id="fecha-hasta-vvc" name="hasta" value="<?= $hasta ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" id="btn-buscar-vvc" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-exportar-vvc" class="btn btn-success w-100">
                        <i class="bi bi-file-earmark-excel"></i> Exportar
                    </button>
                </div>
                <div class="col-md-4">
                    <label for="textBox-vvc" class="form-label">Búsqueda rápida:</label>
                    <input type="text" id="textBox-vvc" class="form-control" placeholder="Sobre cualquier campo..." onkeyup="busquedaRapida()">
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($_GET['desde']) && isset($_GET['hasta'])) { 
        // Cargar los datos desde la clase Ventas
        require_once 'Class/ventas.php';
        $ventas = new Ventas();
        $todasLasVentas = json_decode($ventas->traerComprobantes($_GET['desde'], $_GET['hasta']));
    ?>

    <!-- Tabla de resultados -->
    <div class="integridadVentas_table-wrapper">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover integridadVentas_table" id="tabla-venta-vs-cobranza" data-page-length="100">
                <thead>
                    <tr>
                        <th>FECHA</th>
                        <th>NRO. SUCURSAL</th>
                        <th style="width: 230px;">SUCURSAL</th>
                        <th>TIPO COMPROBANTE</th>
                        <th>NRO. COMPROBANTE</th>
                        <th>VENTA $</th>
                        <th>COBRANZA $</th>
                        <th style="color: #28a745;">DIFERENCIA $</th>
                        <th style="text-align:center">EXCLUIR</th>
                        <th style="text-align:center">OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody id="tabla-vvc-body">
                    <?php foreach ($todasLasVentas as $valor => $key) { ?>
                        <tr>
                            <td><?= substr($key->FECHA->date, 0, 10) ?></td>
                            <td><?= $key->NRO_SUCURS ?></td>
                            <td><?= $key->DESC_SUCURSAL ?></td>
                            <td><?= $key->T_COMP ?></td>
                            <td><?= $key->N_COMP ?></td>
                            <td><?= number_format($key->IMP_VENTA, 0, '', '.') ?></td>
                            <td><?= number_format($key->IMP_COBRANZA, 0, '', '.') ?></td>
                            <td><?= number_format($key->DIFERENCIA, 0, '', '.') ?></td>
                            <td style="text-align:center">
                                <input type="checkbox" class="form-check-input" onchange="confirmarVentaVsCobranza(this)">
                            </td>
                            <td style="text-align:center">
                                <textarea class="form-control form-control-sm" rows="2" cols="30" oninput="checkLineBreak(this)"></textarea>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php } else { ?>
    
    <!-- Mensaje cuando no hay datos -->
    <div class="integridadVentas_table-wrapper">
        <div class="alert alert-info" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            Seleccione un rango de fechas y haga clic en "Buscar" para visualizar los datos.
        </div>
    </div>

    <?php } ?>
    
</div>
