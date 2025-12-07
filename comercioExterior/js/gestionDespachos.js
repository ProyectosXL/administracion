let tablaDespachos = null;

$(document).ready(function() {
    // Cargar despachos
    cargarDespachos();
});

function cargarDespachos() {
    $.ajax({
        url: '../controller/listarDespachos.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                mostrarDespachos(response.data);
            } else {
                mostrarEstadoVacio();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar despachos:', error);
            mostrarEstadoVacio();
        }
    });
}

function mostrarDespachos(despachos) {
    const tbody = $('#tablaDespachos tbody');
    tbody.empty();
    
    if (despachos.length === 0) {
        mostrarEstadoVacio();
        return;
    }
    
    // Destruir tooltips existentes antes de actualizar
    const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    existingTooltips.forEach(el => {
        const tooltip = bootstrap.Tooltip.getInstance(el);
        if (tooltip) tooltip.dispose();
    });
    
    despachos.forEach(function(despacho) {
        const row = `
            <tr>
                <td><strong>#${despacho.ID}</strong></td>
                <td>${formatearFecha(despacho.FECHA_MOV)}</td>
                <td>${despacho.PROVEEDOR || '-'}</td>
                <td>${despacho.CONTENEDOR || '-'}</td>
                <td>${despacho.MATERIAL || '-'}</td>
                <td>${despacho.ORDEN_COMPRA || '-'}</td>
                <td>
                    <div class="d-flex gap-1" style="flex-wrap: nowrap;">
                        <a href="components/editarDespacho.php?id=${despacho.ID}" 
                           class="btn-action btn-editar" 
                           data-bs-toggle="tooltip" 
                           data-bs-placement="top" 
                           data-bs-title="Completar despacho">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="components/cargarCostos.php?id=${despacho.ID}" 
                           class="btn-action btn-costos" 
                           data-bs-toggle="tooltip" 
                           data-bs-placement="top" 
                           data-bs-title="Gestionar costos">
                            <i class="bi bi-calculator"></i>
                        </a>
                        <button onclick="eliminarDespacho(${despacho.ID}, '${despacho.CONTENEDOR}')" 
                                class="btn-action btn-eliminar" 
                                data-bs-toggle="tooltip" 
                                data-bs-placement="top" 
                                data-bs-title="Eliminar despacho">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Reinicializar tooltips después de agregar el contenido
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    // Destruir DataTable existente si existe
    if ($.fn.DataTable.isDataTable('#tablaDespachos')) {
        $('#tablaDespachos').DataTable().destroy();
    }
    
    // Inicializar DataTable con los datos cargados
    tablaDespachos = $('#tablaDespachos').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
        },
        order: [[0, 'desc']], // Ordenar por ID descendente
        pageLength: 15,
        lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, "Todos"]],
        responsive: true,
        autoWidth: false,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
    });
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    const d = new Date(fecha);
    return d.toLocaleDateString('es-AR', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

function mostrarEstadoVacio() {
    const tbody = $('#tablaDespachos tbody');
    tbody.html(`
        <tr>
            <td colspan="7">
                <div class="empty-state">
                    <i class="bi bi-check-circle"></i>
                    <h3>¡No hay despachos pendientes!</h3>
                    <p>Todos los despachos han sido completados.</p>
                </div>
            </td>
        </tr>
    `);
}

function eliminarDespacho(id, contenedor) {
    Swal.fire({
        title: '¿Eliminar despacho?',
        html: `<p>Estás a punto de eliminar el despacho:</p><strong>${contenedor || 'ID: ' + id}</strong><p class="mt-2">Esta acción no se puede deshacer.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../controller/eliminarDespacho.php',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: 'El despacho ha sido eliminado correctamente.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                            willClose: () => {
                                // Recargar la página para actualizar la tabla
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'No se pudo eliminar el despacho',
                            icon: 'error',
                            confirmButtonColor: '#7066e0'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al eliminar el despacho',
                        icon: 'error',
                        confirmButtonColor: '#7066e0'
                    });
                }
            });
        }
    });
}
