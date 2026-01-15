<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Terminales</title>
    
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
                    <i class="bi bi-building"></i>
                    Gestión de Terminales
                </h1>
                <p class="page-subtitle">Administra las terminales portuarias disponibles por país</p>
            </div>
            <div>
                <button class="btn btn-secondary me-2" onclick="window.location.href='index.php'">
                    <i class="bi bi-arrow-left"></i>
                    Volver a Parámetros
                </button>
                <button class="btn btn-primary" onclick="abrirModalNueva()">
                    <i class="bi bi-plus-circle"></i>
                    Nueva Terminal
                </button>
            </div>
        </div>

        <!-- Alert Info -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Información:</strong> Las terminales se asignan por país (Argentina, Uruguay) o ambos. 
            Los cambios se reflejan inmediatamente en los formularios de carga.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <div class="card-header-custom">
                <h3 class="card-title-custom">
                    <i class="bi bi-list-ul"></i>
                    Terminales Configuradas
                </h3>
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

    <!-- Modal Nueva/Editar Terminal -->
    <div class="modal fade" id="modalTerminal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTerminalTitulo">
                        <i class="bi bi-building"></i>
                        Nueva Terminal
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
                            <div class="form-text">Solo las terminales activas aparecen en los formularios</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarTerminal()">
                        <i class="bi bi-check-circle"></i>
                        Guardar
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
    <script src="js/gestionTerminales.js"></script>
</body>
</html>
