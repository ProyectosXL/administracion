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
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="alias-tab" data-bs-toggle="tab" data-bs-target="#alias" type="button" role="tab">
                    <i class="bi bi-person-badge"></i> Alias de Proveedores
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rubros-tab" data-bs-toggle="tab" data-bs-target="#rubros" type="button" role="tab">
                    <i class="bi bi-tags"></i> Iconos de Rubro
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

            <!-- TAB: Alias de Proveedores -->
            <div class="tab-pane fade" id="alias" role="tabpanel">
                <?php include __DIR__ . '/components/abm-alias.php'; ?>
            </div>

            <!-- TAB: Iconos de Rubro -->
            <div class="tab-pane fade" id="rubros" role="tabpanel">
                <div class="content-card">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title-custom mb-1">
                                <i class="bi bi-tags"></i> Iconos de Rubro
                            </h3>
                            <p class="card-subtitle-custom mb-0">
                                Icono que representa cada rubro en los badges del cronograma
                            </p>
                        </div>
                        <button class="btn btn-primary" id="btnNuevoRubroIcono">
                            <i class="bi bi-plus-circle"></i> Nuevo Icono
                        </button>
                    </div>

                    <div class="alert alert-info m-3">
                        <i class="bi bi-info-circle"></i>
                        El icono puede ser una clase de <strong>Bootstrap Icons</strong>
                        (por ejemplo <code>bi-handbag-fill</code>) o directamente un
                        <strong>emoji</strong>. Los rubros de indumentaria y calzado usan emoji
                        porque Bootstrap Icons no tiene glifos para ellos.
                    </div>

                    <div id="rubrosSinMapear" class="m-3"></div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaRubroIcono">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Icono</th>
                                    <th>Rubro</th>
                                    <th style="width: 160px;">Valor</th>
                                    <th style="width: 110px;">Artículos</th>
                                    <th style="width: 80px;">Orden</th>
                                    <th style="width: 90px;">Estado</th>
                                    <th style="width: 130px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="7" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal de icono de rubro -->
    <div class="modal fade" id="modalRubroIcono" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRubroIconoTitulo">
                        <i class="bi bi-tags"></i> Nuevo Icono de Rubro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rubroIconoId">

                    <div class="mb-3">
                        <label for="rubroIconoRubro" class="form-label">
                            Rubro <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="rubroIconoRubro"
                               list="listaRubrosTango" maxlength="40"
                               placeholder="Elegí uno de Tango o escribí uno nuevo">
                        <datalist id="listaRubrosTango"></datalist>
                        <div class="form-text">Debe coincidir exactamente con el rubro de Tango</div>
                    </div>

                    <div class="mb-3">
                        <label for="rubroIconoValor" class="form-label">
                            Icono <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="rubroIconoValor" maxlength="60"
                               placeholder="bi-handbag-fill  o  👜">
                        <div class="form-text">Clase de Bootstrap Icons o un emoji</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Vista previa</label>
                        <div class="preview-badge" id="previewRubroIcono">
                            <span class="preview-alias">LC</span>
                            <span class="preview-contenedor">VER03-26</span>
                            <span class="preview-iconos" id="previewRubroIconoGlifo"></span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="rubroIconoOrden" class="form-label">Orden</label>
                            <input type="number" class="form-control" id="rubroIconoOrden" value="0" min="0">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="rubroIconoActivo" class="form-label">Estado</label>
                            <select class="form-select" id="rubroIconoActivo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarRubroIcono">Guardar</button>
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

                        <!-- Desde cuándo rige el valor nuevo.
                             Sin esta fecha, editar un parámetro pisaba el valor
                             anterior y no quedaba forma de saber qué alícuota
                             regía cuando se nacionalizó un contenedor de hace
                             ocho meses. Ahora cada edición abre una vigencia y
                             cierra la anterior el día previo. -->
                        <div class="mb-3">
                            <label for="editVigenciaDesde" class="form-label fw-bold">
                                Vigente desde
                            </label>
                            <input type="date" class="form-control" id="editVigenciaDesde" name="vigencia_desde">
                            <div class="form-text">
                                El valor anterior no se pierde: queda cerrado el día previo a esta fecha
                                y las operaciones nacionalizadas antes lo siguen usando.
                                Vacío = hoy.
                            </div>
                        </div>

                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Atención:</strong> este cambio no reescribe estimaciones ya guardadas:
                            cada estimación conserva los valores con los que se calculó.
                            Lo que sí cambia es qué alícuota se usa al recalcular, y eso se decide
                            por la <strong>fecha de nacionalización</strong> del contenedor, no por la de hoy.
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

    <!-- Modal de Vigencias de una alícuota.
         Muestra el historial completo -incluidas las retiradas- y permite
         cargar una vigencia nueva con su período. Es la pantalla que faltaba
         para que el historial que la tabla guarda se pueda mirar. -->
    <div class="modal fade" id="modalVigencias" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-range"></i>
                        Vigencias de <span id="vigConceptoNombre"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="vigIdCe">
                    <input type="hidden" id="vigTipoValor">

                    <div id="vigAviso" class="alert alert-secondary py-2 px-3" style="display:none;"></div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70px;">#</th>
                                    <th>Valor</th>
                                    <th>Valor 2</th>
                                    <th style="width: 120px;">Desde</th>
                                    <th style="width: 120px;">Hasta</th>
                                    <th style="width: 110px;">Estado</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody id="vigTbody"></tbody>
                        </table>
                    </div>

                    <hr>

                    <h6 class="fw-bold"><i class="bi bi-plus-circle"></i> Nueva vigencia</h6>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Valor <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="vigValor1" step="0.000001">
                                <span class="input-group-text" id="vigUnidad1"></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valor 2</label>
                            <input type="number" class="form-control" id="vigValor2" step="0.000001">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Desde <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="vigDesde">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hasta</label>
                            <input type="date" class="form-control" id="vigHasta">
                            <!-- Vacío es un estado, no un olvido: es lo que
                                 distingue "sigue vigente" de "rigió hasta tal
                                 día y después no la reemplazó ninguna". -->
                            <div class="form-text">Vacío = sigue vigente</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observación</label>
                            <input type="text" class="form-control" id="vigObservacion" maxlength="200"
                                   placeholder="Ej: Resolución General 5.xxx/2026">
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 mt-3 mb-0">
                        <i class="bi bi-info-circle"></i>
                        La alícuota de una operación se resuelve contra su <strong>fecha de
                        nacionalización</strong>. Los dos extremos entran: una operación
                        nacionalizada justo el "desde" o justo el "hasta" usa esta vigencia.
                        Al guardar, la vigencia abierta anterior se cierra el día previo.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarVigencia" onclick="guardarVigencia()">
                        <i class="bi bi-save"></i> Agregar vigencia
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
    <!-- El ABM de vigencias de alícuotas. Va después de parametros.js porque
         usa cargarParametros() para refrescar la grilla al guardar. -->
    <script src="js/vigenciasAlicuotas.js"></script>
    <script src="js/gestionTerminales.js"></script>
    <script src="js/gestionPuertos.js"></script>
    <script src="js/paramCronograma.js"></script>
    <script src="js/abmAlias.js"></script>
    <script src="js/abmRubroIcono.js"></script>
</body>
</html>
