<!-- ======================= MODAL GENÉRICO PARA DETALLE DE PROPUESTA ======================= -->
<div class="modal fade" id="detallePropuestaModal" tabindex="-1" aria-labelledby="detallePropuestaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="detallePropuestaModalLabel">Detalle de Propuesta de Pago</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="detalle-propuesta-content">
            <!-- Loader que se muestra mientras cargan los datos -->
            <div class="text-center p-5"><div class="spinner-border" role="status"></div></div>
        </div>
      </div>
      <div class="modal-footer">
        <!-- Botones que se mostrarán/ocultarán con JavaScript -->
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        
        <!-- Botones para el Cliente -->
        <button type="button" class="btn btn-danger" id="btn-enviar-contrapropuesta" style="display: none;">
            <i class="fa-solid fa-comments-dollar me-2"></i> Enviar Contrapropuesta
        </button>
        <button type="button" class="btn btn-success" id="btn-aceptar-propuesta" style="display: none;">
            <i class="fa-solid fa-check-circle me-2"></i> Aceptar Propuesta
        </button>
      </div>
    </div>
  </div>
</div>