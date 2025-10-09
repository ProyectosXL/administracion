/**
 * Funcionalidad del modal de ayuda
 */
$(document).ready(function() {
    
    // Función para abrir el modal de ayuda
    function mostrarAyuda() {
        var modalAyuda = new bootstrap.Modal(document.getElementById('modalAyuda'));
        modalAyuda.show();
    }
    
    // Event listener para el botón de ayuda
    $(document).on('click', '#btnAyuda', function(e) {
        e.preventDefault();
        mostrarAyuda();
    });
    
    // Función para mostrar ayuda contextual según la sección
    function mostrarAyudaContextual(seccion) {
        var modalAyuda = new bootstrap.Modal(document.getElementById('modalAyuda'));
        modalAyuda.show();
        
        // Expandir la sección específica
        switch(seccion) {
            case 'uso':
                $('#collapseUso').addClass('show');
                break;
            case 'documentos':
                $('#collapseDocumentos').addClass('show');
                break;
            case 'estados':
                $('#collapseEstados').addClass('show');
                break;
            case 'funciones':
                $('#collapseFunciones').addClass('show');
                break;
            case 'consejos':
                $('#collapseConsejos').addClass('show');
                break;
        }
    }
    
    // Tooltips para elementos que necesitan explicación adicional
    function inicializarTooltips() {
        // Tooltip para el botón de contratos pendientes
        $('#btnContratosPendientes').attr('data-bs-toggle', 'tooltip')
            .attr('data-bs-placement', 'top')
            .attr('title', 'Ver sucursales sin contratos vigentes');
            
        // Tooltip para el botón de pronto vencimiento
        $('#btnProntoVencimiento').attr('data-bs-toggle', 'tooltip')
            .attr('data-bs-placement', 'top')
            .attr('title', 'Ver contratos que vencen en los próximos 45 días');
            
        // Tooltip para el select de vigencia
        $('#contratoVigente').attr('data-bs-toggle', 'tooltip')
            .attr('data-bs-placement', 'top')
            .attr('title', 'Filtrar por estado de vigencia de contratos');
            
        // Inicializar todos los tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Función para mostrar ayuda rápida en elementos específicos
    function configurarAyudaRapida() {
        // Ayuda rápida en botones de subir archivo
        $(document).on('mouseenter', '.subir-archivo', function() {
            $(this).attr('title', 'Subir documento PDF para este tipo de contrato');
        });
        
        // Ayuda rápida en botones de ver archivo
        $(document).on('mouseenter', '.ver-archivo', function() {
            $(this).attr('title', 'Ver documento PDF en nueva ventana');
        });
        
        // Ayuda rápida en botones de eliminar archivo
        $(document).on('mouseenter', '.eliminar-archivo', function() {
            $(this).attr('title', 'Eliminar documento permanentemente');
        });
    }
    
    // Función para mostrar información de ayuda cuando no hay datos
    function mostrarMensajesAyuda() {
        // Si no hay contratos, mostrar mensaje informativo
        if ($('#contratosTable tbody tr').length === 0) {
            $('#contratosTable tbody').html(
                '<tr><td colspan="9" class="text-center py-4">' +
                '<div class="alert alert-info mb-0">' +
                '<i class="fas fa-info-circle me-2"></i>' +
                'No se encontraron contratos con los filtros aplicados. ' +
                '<button type="button" class="btn btn-sm btn-outline-info ms-2" onclick="mostrarAyudaContextual(\'uso\')">' +
                '<i class="fas fa-question-circle me-1"></i>¿Necesita ayuda?' +
                '</button>' +
                '</div>' +
                '</td></tr>'
            );
        }
    }
    
    // Inicializar funcionalidades de ayuda
    inicializarTooltips();
    configurarAyudaRapida();
    
    // Exponer funciones globalmente para uso en otros scripts
    window.mostrarAyuda = mostrarAyuda;
    window.mostrarAyudaContextual = mostrarAyudaContextual;
    
});