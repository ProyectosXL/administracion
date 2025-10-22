<?php
$titulo_pagina = 'Tesorería';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Egresos - Tesorería</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] .'/administracion/assets/css/css.php';
    ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons - Versión más reciente -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Fallback para Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" crossorigin="anonymous">
    
    <!-- Estilo específico para iconos Bootstrap -->
    <style>
        .bi {
            font-family: "bootstrap-icons" !important;
            font-style: normal;
            font-weight: normal;
            font-variant: normal;
            text-transform: none;
            line-height: 1;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .bi-arrow-clockwise::before {
            content: "\f128";
        }
    </style>
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/solicitudes_estilos.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>
    
    <div class="container-fluid">
        <main class="px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-1 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-cash-stack"></i> Portal de Tesorería
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <!-- Botón actualizar con icono Bootstrap sutil -->
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="cargarFacturasListas()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                    </button>
                </div>
            </div>
            
            <!-- Información de la pantalla -->
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Portal de Tesorería:</strong> Gestión de pagos.
                <br>
                <small>
                    <strong>Compras Personales:</strong> Solicitudes que ya tienen orden de compra cargada (estado: CARGADO).
                    <br>
                    <strong>Retiros de Dinero:</strong> Solicitudes que van directo a pago sin O.C. (estado: SOLICITADO).
                </small>
            </div>
            
            <!-- Pestañas -->
            <ul class="nav nav-tabs mb-3" id="tesoreriaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="compras-tab" data-bs-toggle="tab" 
                            data-bs-target="#compras" type="button" role="tab">
                        <i class="bi bi-receipt"></i> Compras Personales
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="retiros-tab" data-bs-toggle="tab" 
                            data-bs-target="#retiros" type="button" role="tab">
                        <i class="bi bi-cash-coin"></i> Retiros de Dinero
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="tesoreriaTabsContent">
                <!-- Pestaña Compras Personales -->
                <div class="tab-pane fade show active" id="compras" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-receipt-cutoff"></i> Compras Personales Listas para Pago
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">
                                <i class="bi bi-info-circle"></i> Estas solicitudes ya tienen la orden de compra autorizada. 
                                Debes adjuntar: comprobante de transferencia, orden de pago y retenciones.
                            </p>
                            <div id="comprasListas">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Cargando facturas listas...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Historial de Compras Pagadas -->
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-archive"></i> Historial de Compras Pagadas
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                <i class="bi bi-info-circle"></i> Compras personales que ya fueron pagadas.
                            </p>
                            <div id="historialComprasPagadas">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-secondary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Cargando historial...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pestaña Retiros de Dinero -->
                <div class="tab-pane fade" id="retiros" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-cash-coin"></i> Retiros de Dinero Listos para Pago
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">
                                <i class="bi bi-info-circle"></i> Estas solicitudes de retiro están listas. 
                                Solo debes adjuntar el comprobante de transferencia.
                            </p>
                            <div id="retirosListos">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-info" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Cargando retiros listos...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Historial de Retiros Pagados -->
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-archive"></i> Historial de Retiros Pagados
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                <i class="bi bi-info-circle"></i> Retiros de dinero que ya fueron pagados.
                            </p>
                            <div id="historialRetirosPagados">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-secondary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Cargando historial...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modales -->
    <?php include 'components/modales.php'; ?>
    
    <!-- Modal para adjuntar comprobantes -->
    <div class="modal fade" id="modalAdjuntarComprobantes" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-paperclip"></i> Adjuntar Comprobantes de Pago
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formAdjuntarComprobantes">
                        <input type="hidden" id="idSolicitudPago">
                        <input type="hidden" id="tipoSolicitudPago">
                        
                        <div class="alert alert-info" id="infoComprobantes">
                            <!-- Contenido dinámico -->
                        </div>
                        
                        <!-- Comprobante de transferencia (siempre) -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-bank"></i> Comprobante de Transferencia <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="comprobanteTransferencia" 
                                   accept="image/*,application/pdf" required>
                        </div>
                        
                        <!-- Solo para compras personales -->
                        <div id="comprobantesExtra" class="d-none">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-file-earmark-text"></i> Orden de Pago <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="ordenPago" 
                                       accept="image/*,application/pdf">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-file-earmark-check"></i> Retenciones <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="retenciones" 
                                       accept="image/*,application/pdf" multiple>
                                <small class="form-text text-muted">Puedes seleccionar múltiples archivos</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observacionesPago" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="guardarComprobantesPago()">
                        <i class="bi bi-check-circle"></i> Confirmar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="js/modal_global.js?v=<?php echo time(); ?>"></script>
    <script src="js/tesoreria_listado.js?v=<?php echo time(); ?>"></script>
</body>
</html>