<?php

session_start();

$esUsuarioMantenimiento = (isset($_SESSION['tipo']) && $_SESSION['tipo'] == 'MANTENIMIENTO');

$usuario = isset($_SESSION['descLocal']) ? ucwords(strtolower($_SESSION['descLocal'])) : 'Usuario';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Caja Directores</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- SheetJS para exportación a Excel -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/caja_estilos.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'components/sidebar.php'; ?>
            
            <!-- Contenido Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Panel de Control - Caja Directores</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y'); ?>
                        </button>
                    </div>
                </div>

                                <?php if ($esUsuarioMantenimiento) : ?>
                
                    <!-- ################################################## -->
                    <!-- VISTA EXCLUSIVA PARA ALBERTO (MANTENIMIENTO)       -->
                    <!-- ################################################## -->

                    <div id="reporte-alberto-view">
                        <div class="pt-2 pb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="mb-0">Reporte Alberto (Proveedor OGROLL)</h3>
                                <button type="button" class="btn btn-success" onclick="exportarReporteAlbertoExcel()">
                                    <i class="bi bi-file-earmark-excel"></i> Exportar Reporte
                                </button>
                            </div>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="bi bi-funnel"></i> Filtro de Período</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="fechaReporteAlbertoDesde" class="form-label">Desde</label>
                                            <input type="date" class="form-control" id="fechaReporteAlbertoDesde">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="fechaReporteAlbertoHasta" class="form-label">Hasta</label>
                                            <input type="date" class="form-control" id="fechaReporteAlbertoHasta">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end gap-2">
                                            <button type="button" class="btn btn-primary" onclick="aplicarFiltrosReporteAlberto()"><i class="bi bi-search"></i> Filtrar</button>
                                            <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosReporteAlberto()"><i class="bi bi-arrow-clockwise"></i> Limpiar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card text-white bg-danger mb-3">
                                        <div class="card-header"><i class="bi bi-arrow-up-circle"></i> Total Egresos (Rango Seleccionado)</div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="totalEgresosAlberto">$0</h3>
                                            <p class="card-text">Pagos a Alberto</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-white bg-warning mb-3">
                                        <div class="card-header"><i class="bi bi-wallet2"></i> Total Gastos (Rango Seleccionado)</div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="totalGastosAlberto">$0</h3>
                                            <p class="card-text">Gastos registrados</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-white bg-primary mb-3">
                                        <div class="card-header"><i class="bi bi-cash-stack"></i> Saldo (Egresos - Gastos)</div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="saldoAlberto">$0</h3>
                                            <p class="card-text">Disponible</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="contenidoReporteAlberto"></div>
                        </div>
                    </div>

                <?php else : ?>
                
                <!-- Pestañas de navegación -->
                <ul class="nav nav-tabs" id="cajaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="ingresos-tab" data-bs-toggle="tab" 
                                data-bs-target="#ingresos" type="button" role="tab">
                            <i class="bi bi-plus-circle"></i> Form Ingresos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="egresos-tab" data-bs-toggle="tab" 
                                data-bs-target="#egresos" type="button" role="tab">
                            <i class="bi bi-dash-circle"></i> Form Egresos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reporte-tab" data-bs-toggle="tab" 
                                data-bs-target="#reporte" type="button" role="tab">
                            <i class="bi bi-graph-up"></i> Reporte de Saldo 
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="egresos-socios-tab" data-bs-toggle="tab" 
                                data-bs-target="#egresos-socios" type="button" role="tab">
                            <i class="bi bi-people-fill"></i> Reporte Socios
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="resumen-servicios-tab" data-bs-toggle="tab" 
                                data-bs-target="#resumen-servicios" type="button" role="tab">
                            <i class="bi bi-gear-fill"></i> Resumen de Servicios
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reporte-alberto-tab" data-bs-toggle="tab" 
                                data-bs-target="#reporte-alberto" type="button" role="tab">
                            <i class="bi bi-person-badge"></i> Reporte Alberto
                        </button>
                    </li>
                </ul>
                
                <!-- Contenido de pestañas -->
                <div class="tab-content" id="cajaTabsContent">
                    <!-- Pestaña Ingresos -->
                    <div class="tab-pane fade show active" id="ingresos" role="tabpanel">
                        <div class="pt-2 pb-4">
                            <h3 class="mb-4">Formulario de Ingresos</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <form id="formIngreso">
                                        <div class="mb-3">
                                            <label for="fechaIngreso" class="form-label">Fecha</label>
                                            <input type="date" class="form-control" id="fechaIngreso" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="importeIngreso" class="form-label">Importe</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="text" class="form-control importe-input" 
                                                       id="importeIngreso" placeholder="0" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="observacionesIngreso" class="form-label">Observaciones</label>
                                            <textarea class="form-control" id="observacionesIngreso" 
                                                      rows="3"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check-circle"></i> Registrar Ingreso
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <h5>Últimos Ingresos Manuales</h5>
                                    <div id="listaIngresos"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Egresos -->
                    <div class="tab-pane fade" id="egresos" role="tabpanel">
                        <div class="pt-2 pb-4">
                            <h3 class="mb-4">Formulario de Egresos</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <form id="formEgreso">
                                        <div class="mb-3">
                                            <label for="fechaEgreso" class="form-label">Fecha</label>
                                            <input type="date" class="form-control" id="fechaEgreso" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="motivoEgreso" class="form-label">Motivo</label>
                                            <select class="form-select" id="motivoEgreso" required>
                                                <option value="">Seleccione un motivo</option>
                                                <option value="SUELDOS">Sueldos</option>
                                                <option value="PROVEEDORES">Pagos a Proveedores</option>
                                                <option value="RETIROS">Retiros de Socios</option>
                                                <option value="COMPENSACION_IVA">Compensación IVA</option>
                                                <option value="AJUSTE">Ajuste</option>
                                                <option value="GASTOS_DIRECTORES">Gastos de Directores</option>
                                            </select>
                                        </div>
                                        <div class="mb-3 d-none" id="divDirector">
                                            <label for="nombreDirector" class="form-label">Director</label>
                                            <select class="form-select" id="nombreDirector">
                                                <option value="">Seleccione un director</option>
                                            </select>
                                        </div>
                                        <div class="mb-3 d-none" id="divCentroCosto">
                                            <label for="centroCosto" class="form-label">Centro de Costo</label>
                                            <select class="form-select" id="centroCosto">
                                                <option value="">Seleccione un centro de costo</option>
                                            </select>
                                        </div>
                                        <div class="mb-3 d-none" id="divProveedor">
                                            <label for="proveedor" class="form-label">Proveedor</label>
                                            <select class="form-select" id="proveedor">
                                                <option value="">Seleccione un proveedor</option>
                                            </select>
                                        </div>
                                        <div class="mb-3 d-none" id="divTipoGasto">
                                            <label for="tipoGasto" class="form-label">Tipo de Gasto</label>
                                            <select class="form-select" id="tipoGasto">
                                                <option value="">Seleccione un tipo de gasto</option>
                                                <option value="Uruguay Mantenimiento">Uruguay Mantenimiento</option>
                                                <option value="Locales mantenimiento">Locales mantenimiento</option>
                                                <option value="Dirección de obra locales">Dirección de obra locales</option>
                                                <option value="Uruguay obras">Uruguay obras</option>
                                                <option value="Locales obras">Locales obras</option>
                                                <option value="Materiales y gastos varios">Materiales y gastos varios</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="importeEgreso" class="form-label">Importe</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="text" class="form-control importe-input" 
                                                       id="importeEgreso" placeholder="0" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="observacionesEgreso" class="form-label">Observaciones</label>
                                            <textarea class="form-control" id="observacionesEgreso" 
                                                      rows="3"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="fotoEgreso" class="form-label">
                                                Foto del Comprobante <span class="text-muted">(opcional)</span>
                                            </label>
                                            
                                            <!-- Botones para móvil -->
                                            <div class="d-md-none mb-2">
                                                <div class="btn-group w-100" role="group">
                                                    <input type="file" class="d-none" id="fotoEgresoCamera" 
                                                           accept="image/*" 
                                                           capture="environment"
                                                           onchange="previsualizarFoto(this)">
                                                    <input type="file" class="d-none" id="fotoEgresoGallery" 
                                                           accept="image/*"
                                                           onchange="previsualizarFoto(this)">
                                                    
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="document.getElementById('fotoEgresoCamera').click()">
                                                        <i class="bi bi-camera"></i> Tomar Foto
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" 
                                                            onclick="document.getElementById('fotoEgresoGallery').click()">
                                                        <i class="bi bi-image"></i> Galería
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <!-- Input tradicional para escritorio -->
                                            <input type="file" class="form-control d-none d-md-block" id="fotoEgreso" 
                                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/bmp,image/webp,image/tiff,image/heic,image/heif"
                                                   onchange="previsualizarFoto(this)">
                                            
                                            <div class="form-text">
                                                <small>
                                                    <strong>Móvil:</strong> Usa los botones para tomar foto o seleccionar de galería<br>
                                                    <strong>Escritorio:</strong> Arrastra o selecciona archivo<br>
                                                    <strong>Formatos:</strong> JPG, PNG, GIF, BMP, WebP, TIFF, HEIC, HEIF
                                                </small>
                                            </div>
                                            
                                            <div id="previewFotoEgreso" class="mt-2 d-none">
                                                <img id="imgPreviewEgreso" src="" class="img-thumbnail" 
                                                     style="max-width: 200px; max-height: 150px;">
                                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" 
                                                        onclick="eliminarPreviewFoto()">
                                                    <i class="bi bi-trash"></i> Quitar
                                                </button>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-check-circle"></i> Registrar Egreso
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <h5>Últimos Egresos</h5>
                                    <div id="listaEgresos"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Reporte -->
                    <div class="tab-pane fade" id="reporte" role="tabpanel">
                        <div class="pt-2 pb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="mb-0">Reporte de Saldo de Caja</h3>
                                <button type="button" class="btn btn-success" onclick="exportarReporteExcel()">
                                    <i class="bi bi-file-earmark-excel"></i> Exportar Reporte
                                </button>
                            </div>
                            
                            <!-- Filtros de fecha -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-funnel"></i> Filtro de Período
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="fechaReporteDesde" class="form-label">Desde</label>
                                            <input type="date" class="form-control" id="fechaReporteDesde">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="fechaReporteHasta" class="form-label">Hasta</label>
                                            <input type="date" class="form-control" id="fechaReporteHasta">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end gap-2">
                                            <button type="button" class="btn btn-primary" onclick="aplicarFiltrosReporte()">
                                                <i class="bi bi-search"></i> Filtrar
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosReporte()">
                                                <i class="bi bi-arrow-clockwise"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tarjetas de resumen -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card text-white bg-success mb-3">
                                        <div class="card-header">
                                            <i class="bi bi-download"></i> Total Ingresos (Período)
                                        </div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="totalIngresos">$0</h3>
                                            <p class="card-text">Ingresos confirmados</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-white bg-danger mb-3">
                                        <div class="card-header">
                                            <i class="bi bi-upload"></i> Total Egresos (Período)
                                        </div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="totalEgresos">$0</h3>
                                            <p class="card-text">Egresos registrados</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-white bg-primary mb-3">
                                        <div class="card-header">
                                            <i class="bi bi-cash-stack"></i> Saldo Actual en Caja
                                        </div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="saldoActual">$0</h3>
                                            <p class="card-text" id="saldoActualText">Histórico Acumulado</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="contenidoReporte"></div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Egresos Socios -->
                    <div class="tab-pane fade" id="egresos-socios" role="tabpanel">
                        <div class="pt-2 pb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                                <h3 class="mb-0">Egresos de Socios - Análisis Detallado</h3>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-success btn-sm" onclick="exportarResumenExcel()">
                                        <i class="bi bi-file-earmark-excel"></i> Exportar Resumen
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="exportarDetalleExcel()">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> Exportar Detalle
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Filtros de fecha -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-funnel"></i> Filtro de Período
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="fechaEgresosSociosDesde" class="form-label">Desde</label>
                                            <input type="date" class="form-control" id="fechaEgresosSociosDesde">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="fechaEgresosSociosHasta" class="form-label">Hasta</label>
                                            <input type="date" class="form-control" id="fechaEgresosSociosHasta">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end gap-2">
                                            <button type="button" class="btn btn-primary" onclick="aplicarFiltrosEgresosSocios()">
                                                <i class="bi bi-search"></i> Filtrar
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosEgresosSocios()">
                                                <i class="bi bi-arrow-clockwise"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tarjeta de total -->
                            <div class="row mb-4 justify-content-center">
                                <div class="col-md-6 col-lg-4">
                                    <div class="card text-white bg-info mb-3">
                                        <div class="card-header">
                                            <i class="bi bi-cash-coin"></i> Total Egresos Socios (Rango Seleccionado)
                                        </div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="totalEgresosSocios">$0</h3>
                                            <p class="card-text">Efectivo + Transferencias</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabla Resumen por Director -->
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-table"></i> Resumen por Director
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <div id="tablaResumenSocios"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabla Detalle de Egresos -->
                            <div class="card mb-4">
                                <div class="card-header bg-secondary text-white">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h5 class="mb-0">
                                                <i class="bi bi-list-ul"></i> Detalle de Egresos
                                            </h5>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center justify-content-md-end mt-2 mt-md-0">
                                                <label for="filtroDirectorDetalle" class="text-white me-2 mb-0" style="white-space: nowrap;">
                                                    <i class="bi bi-funnel"></i> Filtrar por:
                                                </label>
                                                <select class="form-select form-select-sm" id="filtroDirectorDetalle" style="max-width: 200px;">
                                                    <option value="">Todos los directores</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <div id="tablaDetalleSocios"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Resumen de Servicios -->
                    <div class="tab-pane fade" id="resumen-servicios" role="tabpanel">
                        <div class="pt-2 pb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                                <h3 class="mb-0">Resumen de Servicios por Director</h3>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-success btn-sm" onclick="exportarResumenServiciosExcel()">
                                        <i class="bi bi-file-earmark-excel"></i> Exportar Resumen
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Filtros de fecha -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-funnel"></i> Filtro de Período
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="fechaResumenServiciosDesde" class="form-label">Desde</label>
                                            <input type="date" class="form-control" id="fechaResumenServiciosDesde">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="fechaResumenServiciosHasta" class="form-label">Hasta</label>
                                            <input type="date" class="form-control" id="fechaResumenServiciosHasta">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end gap-2">
                                            <button type="button" class="btn btn-primary" onclick="aplicarFiltrosResumenServicios()">
                                                <i class="bi bi-search"></i> Filtrar
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosResumenServicios()">
                                                <i class="bi bi-arrow-clockwise"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tarjeta de total -->
                            <div class="row mb-4 justify-content-center">
                                <div class="col-md-6 col-lg-4">
                                    <div class="card text-white bg-success mb-3">
                                        <div class="card-header">
                                            <i class="bi bi-gear"></i> Total Servicios (Rango Seleccionado)
                                        </div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="totalResumenServicios">$0</h3>
                                            <p class="card-text">Gastos en servicios</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabla Resumen por Motivo -->
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-table"></i> Resumen por Motivo y Director
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <div id="tablaResumenServicios"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Reporte Alberto -->
                    <div class="tab-pane fade" id="reporte-alberto" role="tabpanel">
                        <div class="pt-2 pb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="mb-0">Reporte Alberto (Proveedor OGROLL)</h3>
                                <button type="button" class="btn btn-success" onclick="exportarReporteAlbertoExcel()">
                                    <i class="bi bi-file-earmark-excel"></i> Exportar Reporte
                                </button>
                            </div>
                            
                            <!-- Filtros de fecha -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-funnel"></i> Filtro de Período
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="fechaReporteAlbertoDesde" class="form-label">Desde</label>
                                            <input type="date" class="form-control" id="fechaReporteAlbertoDesde">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="fechaReporteAlbertoHasta" class="form-label">Hasta</label>
                                            <input type="date" class="form-control" id="fechaReporteAlbertoHasta">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end gap-2">
                                            <button type="button" class="btn btn-primary" onclick="aplicarFiltrosReporteAlberto()">
                                                <i class="bi bi-search"></i> Filtrar
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="limpiarFiltrosReporteAlberto()">
                                                <i class="bi bi-arrow-clockwise"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tarjetas de resumen -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card text-white bg-danger mb-3">
                                        <div class="card-header">
                                            <i class="bi bi-arrow-up-circle"></i> Total Egresos (Rango Seleccionado)
                                        </div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="totalEgresosAlberto">$0</h3>
                                            <p class="card-text">Pagos a Alberto</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-white bg-warning mb-3">
                                        <div class="card-header">
                                            <i class="bi bi-wallet2"></i> Total Gastos (Rango Seleccionado)
                                        </div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="totalGastosAlberto">$0</h3>
                                            <p class="card-text">Gastos registrados</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-white bg-primary mb-3">
                                        <div class="card-header">
                                            <i class="bi bi-cash-stack"></i> Saldo (Egresos - Gastos)
                                        </div>
                                        <div class="card-body">
                                            <h3 class="card-title" id="saldoAlberto">$0</h3>
                                            <p class="card-text">Disponible</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="contenidoReporteAlberto"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <!-- Modal de confirmación -->
    <?php include 'components/modal_confirmar.php'; ?>
    
    <!-- Modal para ver fotos de egresos -->
    <div class="modal fade" id="modalFotoEgreso" tabindex="-1" aria-labelledby="modalFotoEgresoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFotoEgresoLabel">
                        <i class="bi bi-camera"></i> Foto del Comprobante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="contenedorFotoEgreso">
                        <img id="imagenFotoEgreso" src="" class="img-fluid rounded shadow" 
                             style="max-width: 100%; max-height: 70vh;">
                    </div>
                    <div id="infoFotoEgreso" class="mt-3 text-muted small">
                        <!-- Información adicional del egreso -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para ver detalle de egreso de socio -->
    <div class="modal fade" id="modalDetalleEgresoSocio" tabindex="-1" aria-labelledby="modalDetalleEgresoSocioLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDetalleEgresoSocioLabel">
                        <i class="bi bi-file-text"></i> Detalle del Egreso
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contenidoDetalleEgresoSocio">
                        <!-- Contenido del detalle se cargará dinámicamente -->
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando información...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="js/modal_global.js?v=<?php echo time(); ?>"></script>
    <script src="js/responsive_helper.js?v=<?php echo time(); ?>"></script>
    <script src="js/caja_ingresos.js?v=<?php echo time(); ?>"></script>
    <script src="js/caja_egresos.js?v=<?php echo time(); ?>"></script>
    <script src="js/caja_reporte.js?v=<?php echo time(); ?>"></script>
    <script src="js/caja_reporte_alberto.js?v=<?php echo time(); ?>"></script>
    <script src="js/egresos_socios.js?v=<?php echo time(); ?>"></script>
    <script src="js/resumen_servicios.js?v=<?php echo time(); ?>"></script>
    <script src="js/sincronizar_vistas.js?v=<?php echo time(); ?>"></script>
</body>
</html>