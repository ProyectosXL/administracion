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
                <p class="page-subtitle">Configuración de parámetros del sistema</p>
            </div>
        </div>

        <!-- Alert Info -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Importante:</strong> Los cambios en parámetros solo afectan a <strong>nuevas estimaciones</strong> que se creen después de modificar los valores.
            Las estimaciones existentes (en cualquier estado) mantienen sus valores originales y no se modifican automáticamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="parametrosTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="conceptos-tab" data-bs-toggle="tab" data-bs-target="#conceptos" type="button" role="tab">
                    <i class="bi bi-calculator"></i> Conceptos de Costo
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="terminales-tab" data-bs-toggle="tab" data-bs-target="#terminales" type="button" role="tab">
                    <i class="bi bi-building"></i> Terminales
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="puertos-tab" data-bs-toggle="tab" data-bs-target="#puertos" type="button" role="tab">
                    <i class="bi bi-geo-alt"></i> Puertos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cronograma-tab" data-bs-toggle="tab" data-bs-target="#cronograma" type="button" role="tab">
                    <i class="bi bi-calendar3"></i> Cronograma
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="parametrosTabContent">
            
            <!-- TAB: Conceptos de Costo -->
            <div class="tab-pane fade show active" id="conceptos" role="tabpanel">
                <div class="content-card">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title-custom">
                                <i class="bi bi-list-ul"></i>
                                Conceptos Configurables
                            </h3>
                            <p class="card-subtitle-custom">Gestione los parámetros utilizados en los cálculos de importación</p>
                        </div>
                        <button class="btn btn-primary" onclick="abrirModalNuevoConcepto()">
                            <i class="bi bi-plus-circle"></i> Nuevo Concepto
                        </button>
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

            <!-- TAB: Terminales -->
            <div class="tab-pane fade" id="terminales" role="tabpanel">
                <div class="content-card">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title-custom mb-1">
                                <i class="bi bi-building"></i>
                                Terminales Portuarias
                            </h3>
                            <p class="card-subtitle-custom mb-0">Administra las terminales disponibles por país</p>
                        </div>
                        <button class="btn btn-primary" onclick="abrirModalNuevaTerminal()">
                            <i class="bi bi-plus-circle"></i> Nueva Terminal
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaTerminales">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th style="width: 200px;">Nombre</th>
                                    <th style="width: 120px;">País/Región</th>
                                    <th style="width: 100px;">Estado</th>
                                    <th style="width: 180px;">Última Modificación</th>
                                    <th style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Puertos -->
            <div class="tab-pane fade" id="puertos" role="tabpanel">
                <div class="content-card">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title-custom mb-1">
                                <i class="bi bi-geo-alt"></i>
                                Puertos de Origen
                            </h3>
                            <p class="card-subtitle-custom mb-0">Administra los puertos disponibles</p>
                        </div>
                        <button class="btn btn-primary" onclick="abrirModalNuevoPuerto()">
                            <i class="bi bi-plus-circle"></i> Nuevo Puerto
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaPuertos">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th style="width: 250px;">Nombre</th>
                                    <th style="width: 150px;">País</th>
                                    <th style="width: 100px;">Estado</th>
                                    <th style="width: 180px;">Última Modificación</th>
                                    <th style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Cronograma -->
            <div class="tab-pane fade" id="cronograma" role="tabpanel">
                <div class="content-card">
                    <div class="card-header-custom">
                        <div>
                            <h3 class="card-title-custom mb-1">
                                <i class="bi bi-calendar3"></i>
                                Días del Cronograma
                            </h3>
                            <p class="card-subtitle-custom mb-0">
                                Días corridos que el cronograma usa para proyectar las fechas
                                estimadas de cada contenedor
                            </p>
                        </div>
                    </div>

                    <div class="alert alert-info m-3">
                        <i class="bi bi-info-circle"></i>
                        Estos valores se aplican al <strong>proyectar</strong> fechas estimadas.
                        Cambiarlos no reescribe las fechas ya cargadas; sí afecta las próximas
                        estimaciones y el recálculo de la fecha de distribución.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaParamCronograma">
                            <thead>
                                <tr>
                                    <th style="width: 180px;">Clave</th>
                                    <th>Descripción</th>
                                    <th style="width: 140px;">Días</th>
                                    <th style="width: 180px;">Última Modificación</th>
                                    <th style="width: 120px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Terminal -->
    <div class="modal fade" id="modalTerminal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTerminalTitulo">
                        <i class="bi bi-building"></i> Nueva Terminal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formTerminal">
                        <input type="hidden" id="terminalId" name="id">
                        
                        <div class="mb-3">
                            <label for="terminalNombre" class="form-label">
                                Nombre de la Terminal <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="terminalNombre" name="nombre" required
                                   placeholder="Ej: EXOLGAN, TRP, JAUSER">
                            <div class="form-text">Nombre que aparecerá en el selector</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="terminalEntorno" class="form-label">
                                País/Región <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="terminalEntorno" name="entorno" required>
                                <option value="">Seleccione...</option>
                                <option value="ARG">Argentina</option>
                                <option value="UY">Uruguay</option>
                                <option value="AMBOS">Ambos países</option>
                            </select>
                            <div class="form-text">Define en qué país estará disponible la terminal</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="terminalActivo" class="form-label">Estado</label>
                            <select class="form-select" id="terminalActivo" name="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarTerminal()">
                        <i class="bi bi-check-circle"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Puerto -->
    <div class="modal fade" id="modalPuerto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPuertoTitulo">
                        <i class="bi bi-geo-alt"></i> Nuevo Puerto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formPuerto">
                        <input type="hidden" id="puertoId" name="id">
                        
                        <div class="mb-3">
                            <label for="puertoNombre" class="form-label">
                                Nombre del Puerto <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="puertoNombre" name="nombre" required
                                   placeholder="Ej: Shanghai, Hong Kong, Ningbo">
                            <div class="form-text">Nombre que aparecerá en el selector</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="puertoPais" class="form-label">
                                País <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="puertoPais" name="pais" required
                                   placeholder="Ej: China, Taiwán, Corea del Sur">
                            <div class="form-text">País donde se ubica el puerto</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="puertoActivo" class="form-label">Estado</label>
                            <select class="form-select" id="puertoActivo" name="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarPuerto()">
                        <i class="bi bi-check-circle"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición de Parámetros -->
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
                            <label for="editTipoValor" class="form-label fw-bold">Tipo de Valor</label>
                            <select class="form-select" id="editTipoValor" name="tipo_valor">
                                <option value="P">Porcentaje (P)</option>
                                <option value="I">Importe Fijo (I)</option>
                            </select>
                        </div>

                        <!-- Opción Calcular sobre (solo visible si es Porcentaje P) -->
                        <div class="mb-3" id="divEditRefConcepto" style="display: none;">
                            <label for="editRefConcepto" class="form-label fw-bold">Calcular sobre</label>
                            <select class="form-select" id="editRefConcepto" name="id_ref_concepto">
                                <option value="">(Base por defecto, ej: CIF/FOB)</option>
                                <!-- Se llena dinámicamente con los otros conceptos -->
                            </select>
                        </div>

                        <!-- Opciones de Moneda y Tipo de Cambio para Uruguay -->
                        <div class="row mb-3" id="divMonedaTc" style="display: none;">
                            <div class="col-md-6">
                                <label for="editMoneda" class="form-label fw-bold">Moneda</label>
                                <select class="form-select" id="editMoneda" name="moneda">
                                    <option value="USD">Dólares (U$D)</option>
                                    <option value="UYU">Pesos Uruguayos ($UYU)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editTipoCambio" class="form-label fw-bold">Tipo de Cambio (TC)</label>
                                <input type="number" class="form-control" id="editTipoCambio" name="tipo_cambio" step="0.0001" value="1.0000">
                            </div>
                        </div>

                        <!-- Vista previa de equivalencia cambiaria -->
                        <div class="mb-3" id="divValorConvertido" style="display: none;">
                            <div class="alert alert-info py-2 mb-0">
                                <i class="bi bi-arrow-left-right me-2"></i>
                                <span class="fw-bold">Equivalencia calculada:</span>
                                <div id="spanValorConvertido1"></div>
                                <div id="spanValorConvertido2" style="display: none;"></div>
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

    <!-- Modal de Nuevo Concepto -->
    <div class="modal fade" id="modalNuevoConcepto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle-fill"></i>
                        Nuevo Concepto de Costo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNuevoConcepto">
                        <div class="mb-3">
                            <label for="nuevoConceptoNombre" class="form-label fw-bold">Nombre del Concepto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nuevoConceptoNombre" name="concepto" required placeholder="Ej: Tasas Municipales, Sellos">
                        </div>

                        <div class="mb-3">
                            <label for="nuevoTipoValor" class="form-label fw-bold">Tipo de Valor <span class="text-danger">*</span></label>
                            <select class="form-select" id="nuevoTipoValor" name="tipo_valor" required>
                                <option value="P">Porcentaje (P)</option>
                                <option value="I" selected>Importe Fijo (I)</option>
                            </select>
                        </div>

                        <!-- Opción Calcular sobre (solo visible si es Porcentaje P) -->
                        <div class="mb-3" id="divNuevoRefConcepto" style="display: none;">
                            <label for="nuevoRefConcepto" class="form-label fw-bold">Calcular sobre</label>
                            <select class="form-select" id="nuevoRefConcepto" name="id_ref_concepto">
                                <option value="">(Base por defecto, ej: CIF/FOB)</option>
                                <!-- Se llena dinámicamente con los otros conceptos -->
                            </select>
                        </div>

                        <!-- Opciones de Moneda y Tipo de Cambio para Uruguay (solo visibles si es UY e Importe Fijo) -->
                        <div class="row mb-3" id="divNuevoMonedaTc" style="display: none;">
                            <div class="col-md-6">
                                <label for="nuevoMoneda" class="form-label fw-bold">Moneda</label>
                                <select class="form-select" id="nuevoMoneda" name="moneda">
                                    <option value="USD" selected>Dólares (U$D)</option>
                                    <option value="UYU">Pesos Uruguayos ($UYU)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="nuevoTipoCambio" class="form-label fw-bold">Tipo de Cambio (TC)</label>
                                <input type="number" class="form-control" id="nuevoTipoCambio" name="tipo_cambio" step="0.0001" value="1.0000">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nuevoValorDefault1" class="form-label fw-bold">
                                Parámetro 1 <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="nuevoValorDefault1" name="valor_default_1" step="0.0001" required value="0">
                                <span class="input-group-text" id="nuevoParam1Unidad">USD</span>
                            </div>
                        </div>

                        <div class="mb-3" id="divNuevoParam2" style="display: none;">
                            <label for="nuevoValorDefault2" class="form-label fw-bold">
                                Parámetro 2
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="nuevoValorDefault2" name="valor_default_2" step="0.0001">
                                <span class="input-group-text" id="nuevoParam2Unidad">USD</span>
                            </div>
                        </div>

                        <!-- Equivalencia cambiaria en tiempo real -->
                        <div class="mb-3" id="divNuevoValorConvertido" style="display: none;">
                            <div class="alert alert-info py-2 mb-0">
                                <i class="bi bi-arrow-left-right me-2"></i>
                                <span class="fw-bold">Equivalencia calculada:</span>
                                <div id="spanNuevoValorConvertido1"></div>
                                <div id="spanNuevoValorConvertido2" style="display: none;"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarNuevoConcepto()">
                        <i class="bi bi-plus-circle"></i> Crear Concepto
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
    <script src="js/gestionTerminales.js"></script>
    <script src="js/gestionPuertos.js"></script>
    <script src="js/paramCronograma.js"></script>
</body>
</html>
