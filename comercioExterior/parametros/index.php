<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parámetros de Importación</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/parametros.css">
    <link rel="icon" type="image/jpg" href="../images/LOGO XL 2018.jpg">
</head>
<body>
    <div class="parametros-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-gear-fill"></i>
                    Parámetros de Importación
                </h1>
                <p class="page-subtitle">Configuración de valores por defecto para estimaciones de costos</p>
            </div>
        </div>

        <!-- Alert Info -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Importante:</strong> Los cambios en parámetros solo afectan a <strong>nuevas estimaciones</strong> que se creen después de modificar los valores.
            Las estimaciones existentes (en cualquier estado) mantienen sus valores originales y no se modifican automáticamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-list-ul"></i>
                    Conceptos Configurables
                </h3>
                <p class="card-subtitle-custom">Gestione los parámetros utilizados en los cálculos de importación</p>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="tablaParametros">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 200px;">Concepto</th>
                            <th style="width: 120px;">Tipo</th>
                            <th style="width: 150px;">Parámetro 1</th>
                            <th style="width: 150px;">Parámetro 2</th>
                            <th style="width: 180px;">Última Actualización</th>
                            <th style="width: 100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargarán dinámicamente -->
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leyenda de Tipos -->
        <div class="legend-card">
            <h6 class="mb-3"><i class="bi bi-info-circle"></i> Tipos de Parámetros</h6>
            <div class="row">
                <div class="col-md-6">
                    <span class="badge bg-info me-2">P</span>
                    <strong>Porcentaje:</strong> Se multiplica por el valor base (ej: 0.21 = 21%)
                </div>
                <div class="col-md-6">
                    <span class="badge bg-secondary me-2">I</span>
                    <strong>Importe:</strong> Valor fijo en moneda (ej: 5000.00 = USD 5,000)
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición -->
    <div class="modal fade" id="modalEditarParametro" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i>
                        Editar Parámetro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarParametro">
                        <input type="hidden" id="editIdCe" name="id_ce">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Concepto</label>
                            <input type="text" class="form-control-plaintext" id="editConcepto" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Valor</label>
                            <div class="tipo-valor-display" id="editTipoValorContainer">
                                <i class="bi bi-tag-fill"></i>
                                <span id="editTipoValor"></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editValorDefault1" class="form-label fw-bold">
                                Parámetro 1 <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="editValorDefault1" 
                                       name="valor_default_1"
                                       step="0.0001"
                                       required>
                                <span class="input-group-text" id="editParam1Unidad"></span>
                            </div>
                            <div class="form-text" id="editParam1Help"></div>
                        </div>

                        <div class="mb-3" id="divParam2">
                            <label for="editValorDefault2" class="form-label fw-bold">
                                Parámetro 2
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="editValorDefault2" 
                                       name="valor_default_2"
                                       step="0.0001">
                                <span class="input-group-text" id="editParam2Unidad"></span>
                            </div>
                            <div class="form-text" id="editParam2Help"></div>
                        </div>

                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Atención:</strong> Este cambio solo afectará a nuevas estimaciones que se creen a partir de ahora.
                            Las estimaciones existentes NO se modificarán automáticamente.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarParametro()">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="../assets/jquery/jquery.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script src="js/parametros.js"></script>
</body>
</html>
