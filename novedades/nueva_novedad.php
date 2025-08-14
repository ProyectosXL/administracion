<?php
/**
 * Página para crear nueva novedad - COMPLETA
 * /novedades/nueva_novedad.php
 */
require_once 'includes/periodo_helper.php';
$periodoInfo = PeriodoHelper::getPeriodoActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Novedad - Sistema RRHH</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- CSS personalizado -->
    <link href="css/novedades_estilos.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Contenedor de alertas -->
    <div id="alertas-container" class="container mt-3"></div>

    <!-- Header de la página -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>
                        <i class="fas fa-plus-circle me-3"></i>
                        Nueva Novedad
                    </h1>
                    <p class="lead mb-0">
                        Registre una nueva novedad para liquidación de sueldos
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="periodo-badge">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php echo $periodoInfo['badge']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container">
        <form id="form-novedad" novalidate>
            
            <!-- Sección: Datos del Empleado -->
            <div class="form-section">
                <h5>
                    <i class="fas fa-user me-2"></i>
                    Datos del Empleado
                </h5>
                
                <div class="row">
                    <div class="col-md-8">
                        <label for="empleado-select" class="form-label">
                            Empleado/Legajo <span class="required">*</span>
                        </label>
                        <select class="form-select" id="empleado-select" name="empleado-select">
                            <option value="">Seleccionar empleado...</option>
                        </select>
                        <input type="hidden" id="legajo" name="legajo" required>
                        <div class="invalid-feedback">
                            Debe seleccionar un empleado
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="sucursal" class="form-label">
                            Sucursal <span class="required">*</span>
                        </label>
                        <select class="form-select" id="sucursal" name="sucursal" required>
                            <option value="">Seleccione sucursal...</option>
                        </select>
                        <div class="invalid-feedback">
                            La sucursal es obligatoria
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección: Tipo de Novedad -->
            <div class="form-section">
                <h5>
                    <i class="fas fa-tags me-2"></i>
                    Tipo de Novedad
                </h5>
                
                <div class="row">
                    <div class="col-md-8">
                        <label for="tipo_novedad" class="form-label">
                            Seleccione el tipo de novedad <span class="required">*</span>
                        </label>
                        <select class="form-select" id="tipo_novedad" name="tipo_novedad" required>
                            <option value="">Seleccione tipo de novedad...</option>
                        </select>
                        <div class="invalid-feedback">
                            El tipo de novedad es obligatorio
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración específica por tipo de novedad -->
            <div id="configuracion-novedad" style="display: none;">
                
                <!-- 1. Cambio de sucursal -->
                <div class="form-section campo-dinamico" id="config-cambio-sucursal">
                    <h5>
                        <i class="fas fa-building me-2"></i>
                        Configuración: Cambio de Sucursal
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="nueva_sucursal" class="form-label">
                                Nueva Sucursal <span class="required">*</span>
                            </label>
                            <select class="form-select" id="nueva_sucursal" name="nueva_sucursal">
                                <option value="">Seleccione nueva sucursal...</option>
                            </select>
                            <div class="invalid-feedback">
                                La nueva sucursal es obligatoria
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_vigencia_sucursal" class="form-label">
                                Fecha de entrada en vigencia <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_vigencia_sucursal" name="fecha_vigencia">
                            <div class="invalid-feedback">
                                La fecha de vigencia es obligatoria
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Nuevo puesto -->
                <div class="form-section campo-dinamico" id="config-nuevo-puesto">
                    <h5>
                        <i class="fas fa-briefcase me-2"></i>
                        Configuración: Cambio de Puesto
                    </h5>
                    <div class="row">
                        <!-- Puesto Actual -->
                        <div class="col-md-12 mb-3">
                            <div class="alert alert-info" id="puesto-actual-info" style="display: none;">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Puesto actual:</strong> <span id="puesto-actual-texto">-</span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="nuevo_puesto" class="form-label">
                                Nuevo Puesto <span class="required">*</span>
                            </label>
                            <select class="form-select" id="nuevo_puesto" name="puesto">
                                <option value="">Buscar y seleccionar puesto...</option>
                            </select>
                            <div class="invalid-feedback">
                                El puesto es obligatorio
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_vigencia_puesto" class="form-label">
                                Fecha de entrada en vigencia <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_vigencia_puesto" name="fecha_vigencia">
                            <div class="invalid-feedback">
                                La fecha de vigencia es obligatoria
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Nuevo salario neto -->
                <div class="form-section campo-dinamico" id="config-nuevo-salario">
                    <h5>
                        <i class="fas fa-dollar-sign me-2"></i>
                        Configuración: Nuevo Salario Neto
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="importe_salario" class="form-label">
                                Importe <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="importe_salario" 
                                       name="importe" step="0.01" min="0" max="999999999999.99" placeholder="0.00">
                            </div>
                            <div class="invalid-feedback">
                                El importe es obligatorio
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_vigencia_salario" class="form-label">
                                Fecha de entrada en vigencia <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_vigencia_salario" name="fecha_vigencia">
                            <div class="invalid-feedback">
                                La fecha de vigencia es obligatoria
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Ajuste de premios -->
                <div class="form-section campo-dinamico" id="config-ajuste-premios">
                    <h5>
                        <i class="fas fa-award me-2"></i>
                        Configuración: Ajuste de Premios
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="importe_premios" class="form-label">
                                Importe <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="importe_premios" 
                                       name="importe" step="0.01" min="0" max="999999999999.99" placeholder="0.00">
                            </div>
                            <div class="invalid-feedback">
                                El importe es obligatorio
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_vigencia_premios" class="form-label">
                                Fecha de entrada en vigencia <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_vigencia_premios" name="fecha_vigencia">
                            <div class="invalid-feedback">
                                La fecha de vigencia es obligatoria
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Horas extras -->
                <div class="form-section campo-dinamico" id="config-horas-extras">
                    <h5>
                        <i class="fas fa-clock me-2"></i>
                        Configuración: Horas Extras
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="cantidad_horas_extras" class="form-label">
                                Cantidad de horas <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cantidad_horas_extras" 
                                       name="cantidad_horas" min="1" placeholder="0">
                                <span class="input-group-text">hs</span>
                            </div>
                            <div class="invalid-feedback">
                                La cantidad de horas es obligatoria
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_vigencia_horas_extras" class="form-label">
                                Fecha de entrada en vigencia <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_vigencia_horas_extras" name="fecha_vigencia">
                            <div class="invalid-feedback">
                                La fecha de vigencia es obligatoria
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Horas adicionales -->
                <div class="form-section campo-dinamico" id="config-horas-adicionales">
                    <h5>
                        <i class="fas fa-plus-circle me-2"></i>
                        Configuración: Horas Adicionales
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="cantidad_horas_adicionales" class="form-label">
                                Cantidad de horas <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cantidad_horas_adicionales" 
                                       name="cantidad_horas" min="1" placeholder="0">
                                <span class="input-group-text">hs</span>
                            </div>
                            <div class="invalid-feedback">
                                La cantidad de horas es obligatoria
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_vigencia_horas_adicionales" class="form-label">
                                Fecha de entrada en vigencia <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_vigencia_horas_adicionales" name="fecha_vigencia">
                            <div class="invalid-feedback">
                                La fecha de vigencia es obligatoria
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 7. Permisos -->
                <div class="form-section campo-dinamico" id="config-permisos">
                    <h5>
                        <i class="fas fa-calendar-times me-2"></i>
                        Configuración: Permisos
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="fecha_permiso" class="form-label">
                                Fecha del permiso <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_permiso" name="fecha_permiso">
                            <div class="invalid-feedback">
                                La fecha del permiso es obligatoria
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="compensa" class="form-label">
                                Compensa <span class="required">*</span>
                            </label>
                            <select class="form-select" id="compensa" name="compensa">
                                <option value="">Seleccione...</option>
                                <option value="1">SI</option>
                                <option value="0">NO</option>
                            </select>
                            <div class="invalid-feedback">
                                Debe indicar si compensa
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 8. Cortes -->
                <div class="form-section campo-dinamico" id="config-cortes">
                    <h5>
                        <i class="fas fa-minus-circle me-2"></i>
                        Configuración: Cortes
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="cantidad_cortes" class="form-label">
                                Cantidad de cortes <span class="required">*</span>
                            </label>
                            <input type="number" class="form-control" id="cantidad_cortes" 
                                   name="cantidad_cortes" min="1" placeholder="0">
                            <div class="invalid-feedback">
                                La cantidad de cortes es obligatoria
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_vigencia_cortes" class="form-label">
                                Fecha de entrada en vigencia <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_vigencia_cortes" name="fecha_vigencia">
                            <div class="invalid-feedback">
                                La fecha de vigencia es obligatoria
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 9. Producción 25% -->
                <div class="form-section campo-dinamico" id="config-produccion-25">
                    <h5>
                        <i class="fas fa-chart-bar me-2"></i>
                        Configuración: Producción 25%
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="cantidad_unidades_25" class="form-label">
                                Cantidad de unidades producidas <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cantidad_unidades_25" 
                                       name="cantidad_unidades" min="1" placeholder="0">
                                <span class="input-group-text">un.</span>
                            </div>
                            <div class="invalid-feedback">
                                La cantidad de unidades es obligatoria
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_vigencia_produccion_25" class="form-label">
                                Fecha de entrada en vigencia <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_vigencia_produccion_25" name="fecha_vigencia">
                            <div class="invalid-feedback">
                                La fecha de vigencia es obligatoria
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 10. Producción 50% -->
                <div class="form-section campo-dinamico" id="config-produccion-50">
                    <h5>
                        <i class="fas fa-chart-line me-2"></i>
                        Configuración: Producción 50%
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="cantidad_unidades_50" class="form-label">
                                Cantidad de unidades producidas <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cantidad_unidades_50" 
                                       name="cantidad_unidades" min="1" placeholder="0">
                                <span class="input-group-text">un.</span>
                            </div>
                            <div class="invalid-feedback">
                                La cantidad de unidades es obligatoria
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_vigencia_produccion_50" class="form-label">
                                Fecha de entrada en vigencia <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_vigencia_produccion_50" name="fecha_vigencia">
                            <div class="invalid-feedback">
                                La fecha de vigencia es obligatoria
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 11. Producción 100% -->
                <div class="form-section campo-dinamico" id="config-produccion-100">
                    <h5>
                        <i class="fas fa-chart-area me-2"></i>
                        Configuración: Producción 100%
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="cantidad_unidades_100" class="form-label">
                                Cantidad de unidades producidas <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cantidad_unidades_100" 
                                       name="cantidad_unidades" min="1" placeholder="0">
                                <span class="input-group-text">un.</span>
                            </div>
                            <div class="invalid-feedback">
                                La cantidad de unidades es obligatoria
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_vigencia_produccion_100" class="form-label">
                                Fecha de entrada en vigencia <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="fecha_vigencia_produccion_100" name="fecha_vigencia">
                            <div class="invalid-feedback">
                                La fecha de vigencia es obligatoria
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Campo: Observaciones -->
            <div class="form-section">
                <h5>
                    <i class="fas fa-comment me-2"></i>
                    Observaciones
                </h5>
                <div class="row">
                    <div class="col-12">
                        <label for="observaciones" class="form-label">
                            Observaciones Adicionales
                        </label>
                        <textarea class="form-control" id="observaciones" name="observaciones" 
                                  rows="3" placeholder="Agregue cualquier observación relevante..."></textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Información adicional que considere relevante para el procesamiento de la novedad.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="form-section">
                <div class="row">
                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" onclick="limpiarFormularioManual()">
                            <i class="fas fa-eraser me-2"></i>
                            Limpiar
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver
                        </button>
                        <button type="submit" class="btn btn-success btn-lg" id="btn-guardar">
                            <i class="fas fa-save me-2"></i>
                            Guardar Novedad
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <!-- Modal de búsqueda de empleado -->
    <?php include 'components/modal_empleado.php'; ?>

    <!-- Modal de confirmación -->
    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>
                        Novedad Registrada Exitosamente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-2 text-center">
                            <i class="fas fa-check-circle fa-3x text-success"></i>
                        </div>
                        <div class="col-md-10">
                            <h6>¡Novedad guardada correctamente!</h6>
                            <p class="mb-2">
                                La novedad ha sido registrada exitosamente en el sistema y está lista 
                                para ser procesada por el área de Recursos Humanos.
                            </p>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Próximos pasos:</strong> La novedad será incluida en la liquidación 
                                del período 28/07/2025 - 27/08/2025.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="crearNuevaNovedad()">
                        <i class="fas fa-plus me-1"></i>
                        Crear Otra Novedad
                    </button>
                    <a href="consultar_novedades.php" class="btn btn-outline-primary">
                        <i class="fas fa-list me-1"></i>
                        Ver Novedades
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home me-1"></i>
                        Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de limpieza -->
    <div class="modal fade" id="modalConfirmarLimpieza" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirmar Limpieza
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        ¿Está seguro que desea limpiar todos los campos del formulario? 
                        Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-warning" onclick="confirmarLimpieza()">
                        <i class="fas fa-eraser me-1"></i>
                        Sí, Limpiar Todo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (requerido para Select2) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/novedades_main.js?v=<?php echo time(); ?>"></script>
    <script src="js/nueva_novedad_form.js?v=<?php echo time(); ?>"></script>

    <script>
        // Manejar errores no capturados (especialmente de extensiones del navegador)
        window.addEventListener('error', function(event) {
            // Filtrar errores conocidos de extensiones que no afectan la funcionalidad
            const mensaje = event.message || '';
            if (mensaje.includes('Could not establish connection') || 
                mensaje.includes('Receiving end does not exist') ||
                mensaje.includes('Extension context invalidated')) {
                // Estos son errores de extensiones del navegador, no los mostramos al usuario
                console.warn('🔔 Error de extensión del navegador ignorado:', mensaje);
                event.preventDefault();
                return false;
            }
        });
        
        window.addEventListener('unhandledrejection', function(event) {
            // Manejar promesas rechazadas no capturadas
            const razon = event.reason || '';
            if (typeof razon === 'string' && (razon.includes('Could not establish connection') || 
                razon.includes('Receiving end does not exist'))) {
                console.warn('🔔 Promise rejechada por extensión del navegador ignorada:', razon);
                event.preventDefault();
                return false;
            }
        });
        
        // Función helper para acceso seguro a NovedadesApp
        function getNovedadesApp() {
            // Priorizar NovedadesApp global
            if (typeof NovedadesApp !== 'undefined') {
                return NovedadesApp;
            }
            
            // Fallback a window.NovedadesApp
            if (typeof window.NovedadesApp !== 'undefined') {
                return window.NovedadesApp;
            }
            
            // Si no existe ninguno, crear uno básico
            console.warn('⚠️ NovedadesApp no está disponible, creando instancia mínima');
            window.NovedadesApp = {
                empleadoSeleccionado: null
            };
            
            return window.NovedadesApp;
        }
        
        // Inicializar Select2 para búsqueda de empleados
        $(document).ready(function() {
            // Configurar Select2 para empleados
            $('#empleado-select').select2({
                theme: 'bootstrap-5',
                placeholder: 'Seleccionar empleado...',
                allowClear: true,
                ajax: {
                    url: 'controller/novedades_controller.php?action=buscar_empleados_select2',
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            q: params.term,
                            limit: 15
                        };
                    },
                    processResults: function (data) {
                        if (data.success) {
                            return {
                                results: data.data
                            };
                        }
                        return { results: [] };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                language: {
                    inputTooShort: function () {
                        return 'Escriba al menos 2 caracteres para buscar';
                    },
                    noResults: function () {
                        return 'No se encontraron empleados';
                    },
                    searching: function () {
                        return 'Buscando empleados...';
                    }
                }
            });

            // Cuando se selecciona un empleado del Select2 - CON MANEJO ROBUSTO DE ERRORES
            $('#empleado-select').on('select2:select', function (e) {
                try {
                    var data = e.params.data;
                    console.log('🎯 Select2 seleccionado - datos completos:', data);
                    
                    if (data) {
                        // Completar campos automáticamente
                        $('#legajo').val(data.legajo || data.id);
                        
                        // Remover clases de error
                        $('#legajo').removeClass('is-invalid').addClass('is-valid');
                        $(this).removeClass('is-invalid').addClass('is-valid');
                        
                        // Obtener NovedadesApp de forma segura
                        const app = getNovedadesApp();
                        
                        // Guardar empleado seleccionado (incluyendo nombre y apellido para el backend)
                        const empleadoData = {
                            legajo: data.legajo || data.id,
                            nombre: data.nombre || '',
                            apellido: data.apellido || ''
                        };
                        
                        // Si nombre/apellido no vienen en el data, extraer del text
                        if (!empleadoData.nombre || !empleadoData.apellido) {
                            const textoParts = data.text.match(/^(.+?)\s+(.+?)\s*\((\d+)\)$/);
                            if (textoParts) {
                                empleadoData.nombre = textoParts[1].trim();
                                empleadoData.apellido = textoParts[2].trim();
                            }
                        }
                        
                        // Guardar empleado en la app
                        app.empleadoSeleccionado = empleadoData;
                        
                        console.log('✅ Empleado guardado en NovedadesApp:', empleadoData);
                        // Verificación segura para el log final
                        if (app && app.empleadoSeleccionado) {
                            console.log('🔗 NovedadesApp.empleadoSeleccionado:', app.empleadoSeleccionado);
                        }
                        
                        // Si está activo el tipo "Cambio de Puesto", actualizar puesto actual
                        const tipoSelect = document.getElementById('tipo_novedad');
                        if (tipoSelect && tipoSelect.value === '2') {
                            console.log('🔄 Tipo Cambio de Puesto activo, actualizando puesto actual...');
                            configurarCambioPuesto();
                        }
                    }
                } catch (error) {
                    console.error('❌ Error en select2:select:', error);
                    // No relanzar el error para evitar que interrumpa el flujo
                }
            });

            // Cuando se limpia el Select2
            $('#empleado-select').on('select2:clear', function (e) {
                try {
                    $('#legajo').val('');
                    
                    $('#legajo').removeClass('is-valid is-invalid');
                    $(this).removeClass('is-valid is-invalid');
                    
                    // Limpiar empleado seleccionado usando función helper
                    const app = getNovedadesApp();
                    if (app && app.hasOwnProperty('empleadoSeleccionado')) {
                        app.empleadoSeleccionado = null;
                        console.log('🧹 Empleado limpiado de NovedadesApp');
                    }
                } catch (error) {
                    console.error('❌ Error en select2:clear:', error);
                    // No relanzar el error para evitar que interrumpa el flujo
                }
            });

            // Sincronizar cambio manual de legajo con Select2 (deshabilitado porque legajo es readonly)
            // El legajo ahora es readonly, solo se puede cambiar via Select2

            // Configurar Select2 para búsqueda de puestos
            $('#nuevo_puesto').select2({
                theme: 'bootstrap-5',
                placeholder: 'Buscar puesto...',
                allowClear: true,
                ajax: {
                    url: 'controller/novedades_controller.php?action=buscar_puestos_select2',
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            q: params.term,
                            limit: 20
                        };
                    },
                    processResults: function (data) {
                        if (data.success) {
                            return {
                                results: data.data
                            };
                        } else {
                            console.error('Error en búsqueda de puestos:', data.message);
                            return {
                                results: []
                            };
                        }
                    },
                    cache: true
                },
                minimumInputLength: 0, // Permitir búsqueda desde el primer carácter
                language: {
                    noResults: function () {
                        return "No se encontraron puestos";
                    },
                    searching: function () {
                        return "Buscando puestos...";
                    }
                }
            });

            // Manejar selección de puesto
            $('#nuevo_puesto').on('select2:select', function (e) {
                console.log('🎯 Puesto seleccionado:', e.params.data);
                $(this).removeClass('is-invalid').addClass('is-valid');
            });

            // Manejar limpieza de puesto
            $('#nuevo_puesto').on('select2:clear', function (e) {
                $(this).removeClass('is-valid is-invalid');
            });
        });

        // Función para mostrar modal de confirmación de limpieza
        function mostrarConfirmacionLimpieza() {
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmarLimpieza'));
            modal.show();
        }
        
        // Función para confirmar limpieza
        function confirmarLimpieza() {
            console.log('🧹 Ejecutando limpieza confirmada...');
            
            // Limpiar el formulario directamente sin llamar a la función original
            const form = document.getElementById('form-novedad');
            if (form) {
                form.reset();
                
                // Limpiar Select2 específicamente
                if (typeof $ !== 'undefined' && $('#empleado-select').length) {
                    $('#empleado-select').val(null).trigger('change');
                }
                
                // Ocultar configuraciones si existe la función
                if (typeof ocultarTodasLasConfiguraciones === 'function') {
                    document.getElementById('configuracion-novedad').style.display = 'none';
                    ocultarTodasLasConfiguraciones();
                }

                // Remover clases de validación
                const campos = form.querySelectorAll('.form-control, .form-select');
                campos.forEach(campo => {
                    campo.classList.remove('is-invalid', 'is-valid');
                });
                
                // Limpiar empleado seleccionado
                try {
                    const app = getNovedadesApp();
                    if (app && app.hasOwnProperty('empleadoSeleccionado')) {
                        app.empleadoSeleccionado = null;
                    }
                } catch (error) {
                    console.error('Error limpiando empleado:', error);
                }
            }
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarLimpieza'));
            if (modal) {
                modal.hide();
            }
            
            // Mostrar mensaje de éxito
            if (typeof NovedadesApp !== 'undefined' && NovedadesApp.mostrarExito) {
                NovedadesApp.mostrarExito('Formulario limpiado correctamente');
            }
        }
        
        // Función específica para limpiar cuando se guarda correctamente
        function limpiarFormularioSinConfirmacion() {
            console.log('🧹 Limpiando formulario después de guardar...');
            
            const form = document.getElementById('form-novedad');
            if (form) {
                form.reset();
                
                // Limpiar Select2 específicamente
                if (typeof $ !== 'undefined' && $('#empleado-select').length) {
                    $('#empleado-select').val(null).trigger('change');
                }
                
                // Ocultar configuraciones
                if (typeof ocultarTodasLasConfiguraciones === 'function') {
                    document.getElementById('configuracion-novedad').style.display = 'none';
                    ocultarTodasLasConfiguraciones();
                }

                // Remover clases de validación
                const campos = form.querySelectorAll('.form-control, .form-select');
                campos.forEach(campo => {
                    campo.classList.remove('is-invalid', 'is-valid');
                });
                
                // Limpiar empleado seleccionado
                try {
                    const app = getNovedadesApp();
                    if (app && app.hasOwnProperty('empleadoSeleccionado')) {
                        app.empleadoSeleccionado = null;
                    }
                } catch (error) {
                    console.error('Error limpiando empleado:', error);
                }
            }
        }
        
        // Función para el botón limpiar manual (CON confirmación)
        function limpiarFormularioManual() {
            console.log('🧹 Limpieza manual solicitada...');
            
            // Verificar si hay datos en el formulario
            const form = document.getElementById('form-novedad');
            if (!form) return;
            
            const formData = new FormData(form);
            let hayDatos = false;
            
            // También verificar Select2 y campos hidden
            const empleadoSelect = document.getElementById('empleado-select');
            const legajoField = document.getElementById('legajo');
            
            if ((empleadoSelect && empleadoSelect.value) || (legajoField && legajoField.value)) {
                hayDatos = true;
            }
            
            // Verificar otros campos
            if (!hayDatos) {
                for (let [key, value] of formData.entries()) {
                    if (value && value.toString().trim() !== '') {
                        hayDatos = true;
                        break;
                    }
                }
            }
            
            if (hayDatos) {
                mostrarConfirmacionLimpieza();
            } else {
                // Si no hay datos, mostrar mensaje
                if (typeof NovedadesApp !== 'undefined' && NovedadesApp.mostrarExito) {
                    NovedadesApp.mostrarExito('No hay datos para limpiar');
                }
            }
        }
    </script>
    
    <!-- Manual JavaScript -->
    <script src="js/manual.js"></script>
    <script src="js/modal_fix.js"></script>
    <script src="js/periodo_utils.js"></script>

    <!-- Manual de Usuario Modal -->
    <?php include 'components/manual_modal.php'; ?>

</body>
</html>