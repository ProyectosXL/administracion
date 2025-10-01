<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Caja Directores</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
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
                
                <!-- Tarjetas de resumen -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-header">
                                <i class="bi bi-arrow-down-circle"></i> Total Ingresos
                            </div>
                            <div class="card-body">
                                <h3 class="card-title" id="totalIngresos">$0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-danger mb-3">
                            <div class="card-header">
                                <i class="bi bi-arrow-up-circle"></i> Total Egresos
                            </div>
                            <div class="card-body">
                                <h3 class="card-title" id="totalEgresos">$0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-header">
                                <i class="bi bi-cash-stack"></i> Saldo Actual
                            </div>
                            <div class="card-body">
                                <h3 class="card-title" id="saldoActual">$0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pestañas de navegación -->
                <ul class="nav nav-tabs" id="cajaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="ingresos-tab" data-bs-toggle="tab" 
                                data-bs-target="#ingresos" type="button" role="tab">
                            <i class="bi bi-plus-circle"></i> Ingresos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="egresos-tab" data-bs-toggle="tab" 
                                data-bs-target="#egresos" type="button" role="tab">
                            <i class="bi bi-dash-circle"></i> Egresos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reporte-tab" data-bs-toggle="tab" 
                                data-bs-target="#reporte" type="button" role="tab">
                            <i class="bi bi-graph-up"></i> Reporte de Saldo
                        </button>
                    </li>
                </ul>
                
                <!-- Contenido de pestañas -->
                <div class="tab-content" id="cajaTabsContent">
                    <!-- Pestaña Ingresos -->
                    <div class="tab-pane fade show active" id="ingresos" role="tabpanel">
                        <div class="py-4">
                            <h3>Formulario de Ingresos</h3>
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
                                    <h5>Últimos Ingresos</h5>
                                    <div id="listaIngresos"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Egresos -->
                    <div class="tab-pane fade" id="egresos" role="tabpanel">
                        <div class="py-4">
                            <h3>Formulario de Egresos</h3>
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
                                            </select>
                                        </div>
                                        <div class="mb-3 d-none" id="divDirector">
                                            <label for="nombreDirector" class="form-label">Director</label>
                                            <select class="form-select" id="nombreDirector">
                                                <option value="">Seleccione un director</option>
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
                        <div class="py-4">
                            <h3>Reporte de Saldo de Caja</h3>
                            
                            <!-- Filtros de fecha -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-funnel"></i> Filtros de Período
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
                            
                            <div id="contenidoReporte"></div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal de confirmación -->
    <?php include 'components/modal_confirmar.php'; ?>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="js/modal_global.js"></script>
    <script src="js/caja_ingresos.js"></script>
    <script src="js/caja_egresos.js"></script>
    <script src="js/caja_reporte.js"></script>
    <script src="js/sincronizar_vistas.js"></script>
</body>
</html>