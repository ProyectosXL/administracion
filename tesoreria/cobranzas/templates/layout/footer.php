<!-- templates/layout/footer.php - VERSIÓN FINAL Y CORRECTA -->

    </div> <!-- Cierre del container-fluid del header.php -->

    <!-- ======================= INCLUSIÓN GLOBAL DE MODALES ======================= -->
    
    <!-- Modal para Detalle de Cliente (Crear Propuesta) -->
    <div class="modal fade" id="detalleClienteModal" tabindex="-1" aria-labelledby="detalleClienteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detalleClienteModalLabel">Detalle de Cobranzas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Cliente: <span id="nombreClienteModal" class="fw-bold"></span></h6>
                <div id="detalle-content-container"><!-- Contenido dinámico --></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btn-enviar-propuesta" disabled>
                    <i class="fa-solid fa-paper-plane me-2"></i> Enviar Propuesta
                </button>
            </div>
            </div>
        </div>
    </div>

    <!-- Modal de Parámetros -->
    <?php include_once __DIR__ . '/../modals/parametros_modal.php'; ?>

    <!-- Modal para Detalle de Propuesta (Gestión y Cliente) -->
    <?php include_once __DIR__ . '/../modals/detalle_propuesta_modal.php'; ?>

    <!-- Modal de Confirmación de Eliminación (Este se puede borrar si usas SweetAlert para todo) -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteModalLabel"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><p>¿Estás seguro de que deseas eliminar este parámetro?</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-delete">Sí, Eliminar</button>
            </div>
            </div>
        </div>
    </div>

    <!-- ======================= SCRIPTS ======================= -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- JS para jQuery UI (necesario para el Datepicker). Debe ir DESPUÉS de jQuery. -->
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script> <!-- Traducción a Español -->

    <!-- ======================= LIBRERÍA PARA MODALES ELEGANTES ======================= -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- =============================================================================== -->