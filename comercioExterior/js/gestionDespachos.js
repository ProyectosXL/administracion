let tablaDespachos = null;
let tablaOcPendientes = null;

$(document).ready(function() {
    // Cargar despachos
    cargarDespachos();
    
    // Cargar contador de OC pendientes
    cargarContadorOcPendientes();
    
    // Event listener para botón de OC Pendientes
    $('#btnVerOcPendientes').on('click', function() {
        abrirModalOcPendientes();
    });
    
    // Limpiar modales y backdrops cuando la página se esté descargando
    $(window).on('beforeunload', function() {
        $('.modal').modal('hide');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css({
            'overflow': '',
            'padding-right': ''
        });
    });
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

// ========== FUNCIONES PARA ÓRDENES DE COMPRA PENDIENTES ==========

/**
 * Carga el contador de órdenes de compra pendientes (badge en el botón)
 */
function cargarContadorOcPendientes() {
    $.ajax({
        url: '../controller/traerOrdenesPendientesController.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && Array.isArray(response) && response.length > 0) {
                $('#badgeOcPendientes').text(response.length).show();
            } else {
                $('#badgeOcPendientes').hide();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar contador de OC pendientes:', error);
            $('#badgeOcPendientes').hide();
        }
    });
}

/**
 * Abre el modal y carga las órdenes de compra pendientes
 */
function abrirModalOcPendientes() {
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalOcPendientes'));
    modal.show();
    
    // Mostrar loading
    const tbody = $('#tablaOcPendientes tbody');
    tbody.html(`
        <tr>
            <td colspan="5" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando órdenes de compra pendientes...</p>
            </td>
        </tr>
    `);
    
    // Cargar datos
    $.ajax({
        url: '../controller/traerOrdenesPendientesController.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && Array.isArray(response)) {
                mostrarOcPendientes(response);
            } else {
                mostrarOcPendientesVacio();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar OC pendientes:', error);
            tbody.html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <p class="mt-2">Error al cargar las órdenes de compra pendientes</p>
                    </td>
                </tr>
            `);
        }
    });
}

/**
 * Muestra las órdenes de compra pendientes en la tabla del modal
 */
function mostrarOcPendientes(ordenes) {
    const tbody = $('#tablaOcPendientes tbody');
    tbody.empty();
    
    if (ordenes.length === 0) {
        mostrarOcPendientesVacio();
        return;
    }
    
    ordenes.forEach(function(orden) {
        const row = `
            <tr>
                <td><strong>${orden.COD_PROVEE || '-'}</strong></td>
                <td>${orden.PROVEEDOR || '-'}</td>
                <td><strong>${orden.N_ORDEN_CO || '-'}</strong></td>
                <td>${formatearFecha(orden.FECHA_INGRESO)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" 
                            onclick="crearDespachoDesdeOc('${orden.COD_PROVEE}', '${orden.N_ORDEN_CO}')"
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            data-bs-title="Crear despacho con esta OC">
                        <i class="bi bi-plus-circle"></i>
                        Crear Despacho
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Reinicializar tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    // Destruir DataTable existente si existe
    if ($.fn.DataTable.isDataTable('#tablaOcPendientes')) {
        $('#tablaOcPendientes').DataTable().destroy();
    }
    
    // Inicializar DataTable sin paginado
    tablaOcPendientes = $('#tablaOcPendientes').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
        },
        order: [[3, 'desc']], // Ordenar por fecha ingreso descendente
        paging: false, // Sin paginado - mostrar todo
        searching: true, // Mantener búsqueda
        info: false, // Sin información de "Mostrando X de Y registros"
        responsive: true,
        autoWidth: false,
        dom: '<"row"<"col-sm-12"f>>rt' // Solo búsqueda y tabla
    });
}

/**
 * Muestra mensaje cuando no hay órdenes de compra pendientes
 */
function mostrarOcPendientesVacio() {
    const tbody = $('#tablaOcPendientes tbody');
    tbody.html(`
        <tr>
            <td colspan="5" class="text-center">
                <div class="empty-state">
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">¡No hay órdenes de compra pendientes!</h5>
                    <p class="text-muted">Todas las órdenes del último año y medio tienen despacho asignado.</p>
                </div>
            </td>
        </tr>
    `);
}

/**
 * Redirige a la página de carga inicial con proveedor y OC preseleccionados
 */
function crearDespachoDesdeOc(codProvee, nOrdenCo) {
    // Guardar los datos en sessionStorage
    sessionStorage.setItem('ocPendiente_proveedor', codProvee);
    sessionStorage.setItem('ocPendiente_ordenCompra', nOrdenCo);
    
    // Limpiar cualquier backdrop o modal abierto antes de navegar
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css({
        'overflow': '',
        'padding-right': ''
    });
    
    // Navegar directamente sin intentar cerrar el modal
    // El cambio de página limpiará automáticamente el modal
    window.location.href = 'cargaInicial.php';
}
