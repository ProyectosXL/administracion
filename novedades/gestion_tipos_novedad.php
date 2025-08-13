<?php
// /novedades/gestion_tipos_novedad.php
require_once 'config/usuario_config.php';
require_once 'class/Usuario.php';

// Verificar que el usuario sea RRHH
if (Usuario::getTipoUsuario() !== Usuario::TIPO_RRHH) {
    header('Location: index.php?error=acceso_denegado');
    exit;
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
    <link href="css/novedades_estilos.css" rel="stylesheet">
    
    <style>
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 20px;
        }

        .permission-toggle {
            width: 50px;
            height: 25px;
        }

        .permission-cell {
            text-align: center;
            vertical-align: middle;
            width: 120px;
        }

        .btn-action {
            margin: 2px;
            padding: 4px 8px;
            font-size: 12px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
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
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .form-check-input:checked {
            background-color: #28a745;
            border-color: #28a745;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .edit-mode {
            background-color: #fff3cd !important;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'components/navbar.php'; ?>

    <!-- Header Section -->
    <div class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 mb-2">
                        <i class="fas fa-cogs me-3"></i>
                        Gestión de Tipos de Novedad
                    </h1>
                    <p class="lead mb-0">Configure los permisos de acceso por tipo de usuario</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-success btn-lg" onclick="abrirModalNuevoTipo()">
                        <i class="fas fa-plus me-2"></i>
                        Nuevo Tipo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-number" id="total-tipos">0</div>
                    <div class="text-muted">Total Tipos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-number text-success" id="tipos-activos">0</div>
                    <div class="text-muted">Activos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-number text-warning" id="tipos-rrhh">0</div>
                    <div class="text-muted">Con RRHH</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-number text-info" id="tipos-admin">0</div>
                    <div class="text-muted">Con Admin</div>
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
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tabla-tipos">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th style="width: 120px;">Código</th>
                            <th>Descripción</th>
                            <th style="width: 100px;">Estado</th>
                            <th class="permission-cell">Admin</th>
                            <th class="permission-cell">Comercial</th>
                            <th class="permission-cell">Producción</th>
                            <th class="permission-cell">RRHH</th>
                            <th class="permission-cell">Cierre</th>
                            <th class="permission-cell">Corte</th>
                            <th style="width: 200px;">Acciones</th>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTipoTitulo">Nuevo Tipo de Novedad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formTipo">
                        <input type="hidden" id="tipo-id" name="id">
                        
                        <div class="mb-3">
                            <label for="tipo-codigo" class="form-label">Código *</label>
                            <input type="text" class="form-control" id="tipo-codigo" name="codigo" required maxlength="20">
                            <div class="form-text">Código único para identificar el tipo</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo-descripcion" class="form-label">Descripción *</label>
                            <input type="text" class="form-control" id="tipo-descripcion" name="descripcion" required maxlength="100">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tipo-activo" name="activo" checked>
                                <label class="form-check-label" for="tipo-activo">
                                    Activo
                                </label>
                            </div>
                        </div>
                        
                        <h6 class="mb-3">Permisos por Tipo de Usuario</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="tipo-user-adm" name="user_adm">
                                    <label class="form-check-label" for="tipo-user-adm">
                                        <i class="fas fa-user-shield me-1"></i> Administrador
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="tipo-user-com" name="user_com">
                                    <label class="form-check-label" for="tipo-user-com">
                                        <i class="fas fa-chart-line me-1"></i> Comercial
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="tipo-user-prod" name="user_prod">
                                    <label class="form-check-label" for="tipo-user-prod">
                                        <i class="fas fa-industry me-1"></i> Producción
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="tipo-user-rrhh" name="user_rrhh">
                                    <label class="form-check-label" for="tipo-user-rrhh">
                                        <i class="fas fa-users me-1"></i> RRHH
                                    </label>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tipo-cierre" class="form-label">
                                        <i class="fas fa-lock me-1"></i> Cierre
                                    </label>
                                    <select class="form-select" id="tipo-cierre" name="cierre">
                                        <option value="">-- Seleccionar --</option>
                                        <option value="24">24</option>
                                        <option value="Ter día hábil">Ter día hábil</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tipo-corte" class="form-label">
                                        <i class="fas fa-cut me-1"></i> Corte
                                    </label>
                                    <select class="form-select" id="tipo-corte" name="corte">
                                        <option value="">-- Seleccionar --</option>
                                        <option value="Fecha vigencia">Fecha vigencia</option>
                                        <option value="Período">Período</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarTipo()">
                        <i class="fas fa-save me-1"></i>
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
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

    <!-- Incluir modal de manual -->
    <?php include 'components/manual_modal.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/manual.js"></script>
    <script src="js/gestion_tipos_novedad.js"></script>
</body>
</html>
