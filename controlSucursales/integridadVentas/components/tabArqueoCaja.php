<?php
// Fecha por defecto: día anterior
$fechaArqueo = date("Y-m-d", strtotime('-1 day'));
?>

<div class="integridadVentas_tab-panel p-4">

    <!-- Filtros -->
    <div class="integridadVentas_filters mb-4">
        <form id="form-consulta-arqueocaja" onsubmit="return false;">
            <div class="row align-items-end g-3">
                <div class="col-md-2">
                    <label for="fecha-arqueoCaja" class="form-label">Fecha:</label>
                    <input type="date" class="form-control" id="fecha-arqueoCaja" name="fecha" value="<?= $fechaArqueo ?>">
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-consultar-arqueoCaja" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Consultar
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btn-exportar-arqueoCaja" class="btn btn-success w-100" style="display:none;">
                        <i class="bi bi-file-earmark-excel"></i> Exportar
                    </button>
                </div>
                <div class="col-md-4">
                    <label for="textBox-arqueoCaja" class="form-label">Búsqueda rápida:</label>
                    <input type="text" id="textBox-arqueoCaja" class="form-control"
                           placeholder="Sobre cualquier campo..." onkeyup="busquedaRapidaArqueo()">
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla de resultados -->
    <div class="integridadVentas_table-wrapper" id="wrapper-tabla-arqueoCaja" style="display:none;">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover integridadVentas_table"
                   id="tabla-arqueo-caja" data-page-length="100">
                <thead>
                    <tr>
                        <th>FECHA</th>
                        <th>NRO. SUCURSAL</th>
                        <th>SUCURSAL</th>
                        <th>COD. CTA. TESORERÍA</th>
                        <th class="text-end">SALDO CAJA</th>
                        <th class="text-end">SALDO CIERRE</th>
                        <th class="text-end">DIFERENCIA</th>
                    </tr>
                </thead>
                <tbody id="tabla-arqueo-caja-body">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mensaje cuando aún no se consultó -->
    <div class="integridadVentas_table-wrapper" id="msg-sin-datos-arqueoCaja">
        <div class="alert alert-info" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            Seleccione una fecha y haga clic en "Consultar" para visualizar los datos.
        </div>
    </div>

</div>
