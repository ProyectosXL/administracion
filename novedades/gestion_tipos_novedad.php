<?php
// /novedades/gestion_tipos_novedad.php
require_once 'config/usuario_config.php';
require_once 'class/Usuario.php';

// Verificar que el usuario sea RRHH
if (Usuario::getTipoUsuario() !== Usuario::TIPO_RRHH) {
    header('Location: index.php?error=acceso_denegado');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tipos de Novedad - Sistema RRHH</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/novedades_estilos.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <style>
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-top: 10px;
            overflow: hidden;
        }

        .permission-cell {
            text-align: center;
            vertical-align: middle;
            width: 110px;
        }

        .permission-cell .form-switch {
            padding-left: 0;
            margin-bottom: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 1.5rem;
        }

        .permission-toggle {
            margin-left: 0 !important;
            float: none !important;
            width: 2.75em !important;
            height: 1.4em !important;
            cursor: pointer;
        }

        .btn-action {
            padding: 4px 10px;
            font-size: 13px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .header-section {
            background: linear-gradient(135deg, #0d6efd 0%, #4285f4 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.25rem 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            height: 100%;
            border-top: 3px solid transparent;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #0d6efd;
            line-height: 1.1;
            margin-bottom: 0.35rem;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }

        #tabla-tipos thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            border-bottom: 2px solid #0d6efd;
            white-space: nowrap;
            vertical-align: middle;
            padding: 0.75rem 0.65rem;
        }

        #tabla-tipos tbody td {
            vertical-align: middle;
            padding: 0.65rem;
        }

        .permiso-modal-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.65rem 0.85rem;
            background-color: #f8f9fa;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        .permiso-modal-card:hover {
            border-color: #86b7fe;
            background-color: #f0f6ff;
        }

        .permiso-modal-card .form-check {
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-left: 0;
        }

        .permiso-modal-card .form-check-input {
            margin-left: 0;
            margin-top: 0;
            float: none;
            flex-shrink: 0;
            cursor: pointer;
        }

        .permiso-modal-card .form-check-label {
            cursor: pointer;
            margin-bottom: 0;
            width: 100%;
        }

        .edit-mode {
            background-color: #fff3cd !important;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'components/navbar.php'; ?>

    <div class="container-fluid consultar-wrapper px-3 px-xl-4 pt-3 pb-5">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <div>
                <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-cogs text-primary me-2"></i>Gestión de Tipos de Novedad</h4>
                <small class="text-muted">Configure los permisos de acceso, cierre y período de aplicación por tipo</small>
            </div>
        </div>
        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stats-card text-center border-primary">
                    <div class="stats-number" id="total-tipos">0</div>
                    <div class="text-muted small fw-semibold">Total Tipos</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stats-card text-center border-success">
                    <div class="stats-number text-success" id="tipos-activos">0</div>
                    <div class="text-muted small fw-semibold">Activos</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stats-card text-center border-warning">
                    <div class="stats-number text-warning" id="tipos-rrhh">0</div>
                    <div class="text-muted small fw-semibold">Con RRHH</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stats-card text-center border-info">
                    <div class="stats-number text-info" id="tipos-admin">0</div>
                    <div class="text-muted small fw-semibold">Con Administración</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stats-card text-center border-primary">
                    <div class="stats-number text-primary" id="tipos-comercial">0</div>
                    <div class="text-muted small fw-semibold">Con Comercial</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="stats-card text-center border-danger">
                    <div class="stats-number text-danger" id="tipos-produccion">0</div>
                    <div class="text-muted small fw-semibold">Con Producción</div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div class="loading" id="loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando tipos de novedad...</p>
        </div>

        <!-- Table Container -->
        <div class="table-container card border-0">
            <div class="card-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list-ul me-2"></i>Tipos de Novedad Configurados</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabla-tipos">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 170px;">Código</th>
                            <th>Descripción</th>
                            <th style="width: 95px;" class="text-center">Estado</th>
                            <th class="permission-cell">Admin</th>
                            <th class="permission-cell">Comercial</th>
                            <th class="permission-cell">Producción</th>
                            <th class="permission-cell">RRHH</th>
                            <th style="width: 110px;" class="text-center">Cierre</th>
                            <th style="width: 160px;" class="text-center">Período de Aplicación</th>
                            <th style="width: 90px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tipos-tbody">
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar Tipo -->
    <div class="modal fade" id="modalTipo" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTipoTitulo">
                        <i class="fas fa-edit me-2"></i>Editar Tipo de Novedad
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formTipo">
                        <input type="hidden" id="tipo-id" name="id">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="tipo-codigo" class="form-label fw-semibold">Código <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tipo-codigo" name="codigo" required maxlength="20">
                                <div class="form-text">Identificador único interno</div>
                            </div>
                            
                            <div class="col-md-5">
                                <label for="tipo-descripcion" class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tipo-descripcion" name="descripcion" required maxlength="100">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold d-block">Estado</label>
                                <div class="permiso-modal-card py-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="tipo-activo" name="activo" checked>
                                        <label class="form-check-label fw-semibold" for="tipo-activo">
                                            Activo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-bold text-primary mb-3">
                            <i class="fas fa-user-lock me-2"></i>Permisos por Tipo de Usuario
                        </h6>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <div class="permiso-modal-card">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tipo-user-adm" name="user_adm">
                                        <label class="form-check-label" for="tipo-user-adm">
                                            <i class="fas fa-user-shield text-info me-1"></i> Administración
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="permiso-modal-card">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tipo-user-com" name="user_com">
                                        <label class="form-check-label" for="tipo-user-com">
                                            <i class="fas fa-chart-line text-primary me-1"></i> Comercial
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="permiso-modal-card">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tipo-user-prod" name="user_prod">
                                        <label class="form-check-label" for="tipo-user-prod">
                                            <i class="fas fa-industry text-danger me-1"></i> Producción
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="permiso-modal-card">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tipo-user-rrhh" name="user_rrhh">
                                        <label class="form-check-label" for="tipo-user-rrhh">
                                            <i class="fas fa-users text-warning me-1"></i> RRHH
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-bold text-primary mb-3">
                            <i class="fas fa-calendar-alt me-2"></i>Configuración de Cierre y Corte
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="tipo-cierre" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-1 text-muted"></i> Día de Cierre
                                </label>
                                <select class="form-select" id="tipo-cierre" name="cierre">
                                    <option value="">-- Seleccionar --</option>
                                    <?php for ($i = 1; $i <= 31; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                    <option value="1er día hábil">1er día hábil</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="tipo-corte" class="form-label fw-semibold">
                                    <i class="fas fa-calendar-check me-1 text-muted"></i> Período de Aplicación
                                </label>
                                <select class="form-select" id="tipo-corte" name="corte">
                                    <option value="">-- Seleccionar --</option>
                                    <option value="Período">Período</option>
                                    <option value="Fecha Vigencia">Fecha Vigencia</option>
                                    <option value="Período siguiente">Período siguiente</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary px-4" onclick="guardarTipo()">
                        <i class="fas fa-save me-1"></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación - DESHABILITADO -->
    <!-- No se permite eliminar tipos de novedad, solo editar
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">¿Está seguro?</h5>
                        <p class="text-muted">Esta acción no se puede deshacer. El tipo de novedad será eliminado permanentemente.</p>
                        <div class="alert alert-warning mt-3">
                            <strong>Tipo:</strong> <span id="eliminar-descripcion"></span><br>
                            <strong>Código:</strong> <span id="eliminar-codigo"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="confirmarEliminacion()">
                        <i class="fas fa-trash me-1"></i>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
    -->

    <!-- Incluir modal de manual -->
    <?php include 'components/manual_modal.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/manual.js"></script>
    <script src="js/gestion_tipos_novedad.js?v=<?php echo time(); ?>"></script>
</body>
</html>
