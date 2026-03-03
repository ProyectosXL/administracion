<?php
// Obtener datos de las sucursales para los selectores
$sucursalObj = new Sucursal();
$todosLosLocales = $sucursalObj->traerLocales(true);
$rangoDefault = $service->calcularRangoDefault();
?>

<!-- CSS específico para Comparar Sucursales -->
<style>
/* Estilos específicos para el componente de comparar sucursales */
.comparar-sucursales-container {
    width: 100%;
    background: transparent;
}

/* Filtros Section */
.comparar-sucursales-container .filters-section-comparar {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-left: 4px solid #3498db;
}

/* Botones de filtros */
.comparar-sucursales-container .filter-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.comparar-sucursales-container .filter-button-primary,
.comparar-sucursales-container .filter-button-secondary,
.comparar-sucursales-container .filter-button-info {
    padding: 12px 20px;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    border: none;
    text-decoration: none;
}

.comparar-sucursales-container .filter-button-primary:hover,
.comparar-sucursales-container .filter-button-secondary:hover,
.comparar-sucursales-container .filter-button-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    text-decoration: none;
}

/* Inputs mejorados */
.comparar-sucursales-container .filters-form-comparar .form-control {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 10px 15px;
    font-size: 14px;
    transition: all 0.3s ease;
    height: calc(2.25rem + 4px) !important;
    line-height: 1.5;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Selectores específicos para mantener consistencia */
.comparar-sucursales-container .filters-form-comparar select.form-control {
    height: calc(2.25rem + 4px) !important;
    appearance: none;
    background-color: #ffffff;
    background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%23666" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px;
    padding-right: 40px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.comparar-sucursales-container .filters-form-comparar .form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.comparar-sucursales-container .filters-form-comparar label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.comparar-sucursales-container .filters-section-comparar .section-title {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    font-size: 18px;
    font-weight: 700;
    color: #2c3e50;
}

.comparar-sucursales-container .filters-section-comparar .section-title i {
    margin-right: 10px;
    color: #3498db;
    font-size: 20px;
}

/* KPI de Comparación */
.comparar-sucursales-container .comparacion-kpi {
    margin-bottom: 25px;
}

.comparar-sucursales-container .kpi-card-comparar {
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-left: 4px solid #e74c3c;
    transition: all 0.3s ease;
}

.comparar-sucursales-container .kpi-card-comparar:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.comparar-sucursales-container .kpi-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
}

.comparar-sucursales-container .kpi-header i {
    margin-right: 10px;
    color: #e74c3c;
    font-size: 18px;
}

.comparar-sucursales-container .kpi-values {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.comparar-sucursales-container .kpi-sucursal {
    flex: 1;
    text-align: center;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.comparar-sucursales-container .kpi-sucursal-nombre {
    display: block;
    font-size: 14px;
    color: #7f8c8d;
    margin-bottom: 8px;
    font-weight: 500;
}

.comparar-sucursales-container .kpi-sucursal-valor {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
}

.comparar-sucursales-container .kpi-diferencia {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 15px;
    background: #ecf0f1;
    border-radius: 8px;
    min-width: 150px;
}

.comparar-sucursales-container .kpi-diferencia i {
    font-size: 20px;
    color: #95a5a6;
}

.comparar-sucursales-container .kpi-diferencia-valor {
    font-size: 22px;
    font-weight: 700;
    margin: 5px 0;
}

.comparar-sucursales-container .kpi-diferencia-texto {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 5px 0;
}

/* Estados de diferencia */
.comparar-sucursales-container .kpi-diferencia.mejor {
    background: rgba(39, 174, 96, 0.1);
    color: #27ae60;
}

.comparar-sucursales-container .kpi-diferencia.peor {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.comparar-sucursales-container .kpi-diferencia.igual {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
}

/* Tabla de Comparación */
.comparar-sucursales-container .table-section-comparar {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    margin-bottom: 25px;
}

.comparar-sucursales-container .table-section-comparar .table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #ecf0f1;
}

.comparar-sucursales-container .table-section-comparar .table-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #2c3e50;
    display: flex;
    align-items: center;
}

.comparar-sucursales-container .table-section-comparar .table-header h3 i {
    margin-right: 10px;
    color: #3498db;
}

/* Tabla específica para comparación */
.comparar-sucursales-container #tablaComparacionSucursales {
    margin-bottom: 0;
}

.comparar-sucursales-container #tablaComparacionSucursales thead th {
    background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    color: #ffffff !important;
    font-weight: 700;
    text-align: center;
    padding: 15px 10px;
    border: none;
    position: sticky;
    top: 0;
    z-index: 10;
    text-shadow: none;
    font-size: 14px;
    border-bottom: 2px solid #27ae60;
}

/* Asegurar que el texto del cuerpo de la tabla sea oscuro */
.comparar-sucursales-container #tablaComparacionSucursales tbody td {
    color: #2c3e50 !important;
}

/* Override para cualquier color blanco aplicado por DataTables */
.comparar-sucursales-container #tablaComparacionSucursales {
    color: #2c3e50 !important;
}

/* Estilos específicos para diferentes columnas del encabezado */
.comparar-sucursales-container #tablaComparacionSucursales thead th.fixed-column {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
    color: #ffffff !important;
    font-weight: 700;
}

.comparar-sucursales-container #tablaComparacionSucursales thead th.col-sucursal-1 {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: #ffffff;
}

.comparar-sucursales-container #tablaComparacionSucursales thead th.col-sucursal-2 {
    background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
    color: #ffffff;
}

.comparar-sucursales-container #tablaComparacionSucursales thead th.col-variacion {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: #ffffff;
}

.comparar-sucursales-container #tablaComparacionSucursales tbody td {
    padding: 12px 10px;
    vertical-align: middle;
    border-color: #ecf0f1;
}

/* Columna fija para conceptos */
.comparar-sucursales-container #tablaComparacionSucursales .fixed-column {
    background: #f8f9fa !important;
    font-weight: 600;
    color: #2c3e50 !important;
    position: sticky;
    left: 0;
    z-index: 5;
    min-width: 280px;
    max-width: 280px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Columnas de sucursales */
.comparar-sucursales-container .col-sucursal-1 {
    background: rgba(52, 152, 219, 0.05);
    border-left: 3px solid #3498db;
}

.comparar-sucursales-container .col-sucursal-2 {
    background: rgba(155, 89, 182, 0.05);
    border-left: 3px solid #9b59b6;
}

/* Columna de variación */
.comparar-sucursales-container .col-variacion {
    background: rgba(241, 196, 15, 0.05);
    border-left: 3px solid #f1c40f;
    font-weight: 600;
}

.comparar-sucursales-container .variacion-positiva {
    color: #e74c3c;
}

.comparar-sucursales-container .variacion-negativa {
    color: #27ae60;
}

.comparar-sucursales-container .variacion-neutra {
    color: #7f8c8d;
}

/* Filas especiales */
.comparar-sucursales-container .row-subtotal-comparar {
    background: rgba(52, 152, 219, 0.1) !important;
    font-weight: 700;
    border-top: 3px solid #3498db;
}

.comparar-sucursales-container .row-percentage-comparar {
    background: rgba(231, 76, 60, 0.1) !important;
    font-weight: 700;
    border-top: 3px solid #e74c3c;
}

/* Estado vacío */
.comparar-sucursales-container .empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.comparar-sucursales-container .empty-state .empty-icon {
    margin-bottom: 20px;
}

.comparar-sucursales-container .empty-state h3 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 600;
}

.comparar-sucursales-container .empty-state p {
    color: #7f8c8d;
    font-size: 16px;
    margin-bottom: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .comparar-sucursales-container .kpi-values {
        flex-direction: column;
        gap: 15px;
    }
    
    .comparar-sucursales-container .kpi-diferencia {
        flex-direction: row;
        justify-content: center;
        min-width: auto;
        width: 100%;
    }
    
    .comparar-sucursales-container .filters-section-comparar .row {
        margin: 0;
    }
    
    .comparar-sucursales-container .filters-section-comparar .col-md-3 {
        margin-bottom: 15px;
    }
}

@media (max-width: 992px) {
    .comparar-sucursales-container .table-section-comparar {
        padding: 15px;
    }
    
    .comparar-sucursales-container #tablaComparacionSucursales .fixed-column {
        min-width: 220px;
        max-width: 220px;
    }
}

@media (max-width: 576px) {
    .comparar-sucursales-container #tablaComparacionSucursales .fixed-column {
        min-width: 180px;
        max-width: 180px;
        font-size: 13px;
    }
}

div.dataTables_scrollHead table.table-bordered {
    border-bottom-width: 0;
    color: wheat;
    border: none;
}
</style>

<!-- Contenedor principal con CSS específico -->
<div class="comparar-sucursales-container">
    <!-- Filtros Section para Comparar Sucursales -->
    <div class="filters-section-comparar">
        <div class="section-title">
            <i class="bi bi-funnel"></i>
            <span>Filtros de Comparación</span>
        </div>
        
        <div class="filters-form-comparar">
            <div class="row">
                <!-- Fechas -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label for="fechaDesdeComparar"><i class="bi bi-calendar-event"></i> Fecha Desde</label>
                        <input type="date" class="form-control" id="fechaDesdeComparar" 
                               value="<?= $rangoDefault['desde'] ?>">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="filter-group">
                        <label for="fechaHastaComparar"><i class="bi bi-calendar-event"></i> Fecha Hasta</label>
                        <input type="date" class="form-control" id="fechaHastaComparar" 
                               value="<?= $rangoDefault['hasta'] ?>">
                    </div>
                </div>
                
                <!-- Sucursal 1 -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label for="selectSucursal1"><i class="bi bi-building"></i> Sucursal 1</label>
                        <select class="form-control" id="selectSucursal1">
                            <option value="">Seleccionar sucursal...</option>
                            <?php foreach ($todosLosLocales as $local): ?>
                                <option value="<?= $local['ID'] ?>"><?= $local['SUCURSAL'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Sucursal 2 -->
                <div class="col-md-3">
                    <div class="filter-group">
                        <label for="selectSucursal2"><i class="bi bi-building"></i> Sucursal 2</label>
                        <select class="form-control" id="selectSucursal2">
                            <option value="">Seleccionar sucursal...</option>
                            <?php foreach ($todosLosLocales as $local): ?>
                                <option value="<?= $local['ID'] ?>"><?= $local['SUCURSAL'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="button" class="btn btn-primary filter-button-primary" id="btnCompararSucursales">
                    <i class="bi bi-arrow-left-right"></i>
                    Comparar
                </button>
                <button type="button" class="btn btn-secondary filter-button-secondary" id="btnLimpiarComparar">
                    <i class="bi bi-x-circle"></i>
                    Limpiar
                </button>
                <button type="button" class="btn btn-info filter-button-info" id="btnExportarComparar" style="display: none;">
                    <i class="bi bi-download"></i>
                    Exportar
                </button>
                <button type="button" class="pdf-btn" id="btnPDFComparar" style="display: none;" title="Descargar PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                    Descargar PDF
                </button>
            </div>
        </div>
    </div>

    <!-- KPI de Resumen -->
    <div class="comparacion-kpi" id="comparacionKpi" style="display: none;">
        <div class="kpi-card-comparar">
            <div class="kpi-header">
                <i class="bi bi-percent"></i>
                <span>Diferencia de Costo de Ocupación</span>
            </div>
            <div class="kpi-content">
                <div class="kpi-values">
                    <div class="kpi-sucursal">
                        <span class="kpi-sucursal-nombre" id="nombreSucursal1">Sucursal 1</span>
                        <span class="kpi-sucursal-valor" id="valorSucursal1">--</span>
                    </div>
                    <div class="kpi-diferencia">
                        <i class="bi bi-arrow-right"></i>
                        <span class="kpi-diferencia-valor" id="valorDiferencia">--</span>
                        <span class="kpi-diferencia-texto" id="textoDiferencia">--</span>
                    </div>
                    <div class="kpi-sucursal">
                        <span class="kpi-sucursal-nombre" id="nombreSucursal2">Sucursal 2</span>
                        <span class="kpi-sucursal-valor" id="valorSucursal2">--</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Comparación -->
    <div class="table-section-comparar" id="tableSectionComparar" style="display: none;">
        <div class="table-header">
            <h3><i class="bi bi-table"></i> Comparación de Costos</h3>
            <div class="table-actions">
                <button type="button" class="btn btn-info btn-lg" id="btnLeyendaComparar" style="padding: 12px 24px; font-size: 16px; font-weight: 600; border-radius: 8px;">
                    <i class="bi bi-info-circle" style="margin-right: 10px; font-size: 18px;"></i>
                    Leyenda
                </button>
            </div>
        </div>
        
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="tablaComparacionSucursales">
                    <!-- Se llena dinámicamente -->
                </table>
            </div>
        </div>
    </div>

    <!-- Estado inicial -->
    <div class="empty-state" id="emptyStateComparar">
        <div class="empty-icon">
            <i class="bi bi-arrow-left-right" style="font-size: 4rem; color: #bdc3c7;"></i>
        </div>
        <h3>Seleccione dos sucursales para comparar</h3>
        <p>Elija dos sucursales y un rango de fechas para realizar la comparación de costos de ocupación</p>
    </div>
</div>