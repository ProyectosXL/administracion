/**
 * proyeccion-costos.js - Gestión de listado de despachos para PCI
 */

let tablaDespachos = null;

$(document).ready(function() {
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
                mostrarError('No se pudieron cargar los despachos');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar despachos:', error);
            mostrarError('Error al conectar con el servidor');
        }
    });
}

function mostrarDespachos(despachos) {
    const tbody = $('#tablaDespachos tbody');
    tbody.empty();
    
    if (despachos.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="empty-state">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
                        <p class="mt-2 text-muted">No hay despachos disponibles</p>
                    </div>
                </td>
            </tr>
        `);
        return;
    }
    
    despachos.forEach(function(despacho) {
        const estadoClass = getEstadoClass(despacho.ESTADO);
        const estadoBadge = `<span class="badge ${estadoClass}">${despacho.ESTADO}</span>`;

        const botonesAccion = generarBotonesAccion(despacho);

        // Badge "+N OCs" cuando hay hijas vinculadas
        const ocsVinculadas = despacho.OCS_VINCULADAS || '';
        const cantOcs = parseInt(despacho.CANT_OCS) || 1;
        let ocDisplay = despacho.ORDEN_COMPRA || '-';
        if (cantOcs > 1 && ocsVinculadas) {
            ocDisplay = `
                <div class="d-flex align-items-center gap-2">
                    <span>${despacho.ORDEN_COMPRA}</span>
                    <span class="badge bg-info text-white"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          title="OCs vinculadas: ${ocsVinculadas}">
                        <i class="bi bi-link-45deg"></i> +${cantOcs - 1}
                    </span>
                </div>`;
        }

        const row = `
            <tr>
                <td><strong>#${despacho.ID}</strong></td>
                <td>${formatearFecha(despacho.FECHA_MOV)}</td>
                <td>${despacho.PROVEEDOR || '-'}</td>
                <td>${despacho.CONTENEDOR || '-'}</td>
                <td>${despacho.MATERIAL || '-'}</td>
                <td>${ocDisplay}</td>
                <td>${estadoBadge}</td>
                <td>${botonesAccion}</td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Inicializar tooltips de Bootstrap para los badges de OCs vinculadas
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        const t = bootstrap.Tooltip.getInstance(el);
        if (t) t.dispose();
        new bootstrap.Tooltip(el);
    });

    // Destruir DataTable existente si existe
    if ($.fn.DataTable.isDataTable('#tablaDespachos')) {
        $('#tablaDespachos').DataTable().destroy();
    }

    // Inicializar DataTable
    tablaDespachos = $('#tablaDespachos').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        pageLength: 15,
        lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, "Todos"]],
        responsive: true,
        autoWidth: false
    });
}

function getEstadoClass(estado) {
    switch(estado) {
        case 'CONFIRMADO':
            return 'bg-success';
        case 'BORRADOR':
            return 'bg-warning';
        case 'PENDIENTE':
            return 'bg-secondary';
        default:
            return 'bg-secondary';
    }
}

function generarBotonesAccion(despacho) {
    const esConfirmado = despacho.ESTADO === 'CONFIRMADO';

    /* Un contenedor confirmado ya no se "ve": se edita. El botón decía "Ver
       Estimación" con un ojo porque la pantalla de destino era de sólo
       lectura; ahora que se pueden corregir los importes, ese texto mandaría
       a la gente a buscar la edición donde no está. */
    const btnEditar = `
        <a href="components/editar-estimacion.php?id=${despacho.ID}"
           class="btn btn-sm ${esConfirmado ? 'btn-info' : 'btn-primary'}"
           title="${esConfirmado ? 'Editar los costos de un contenedor confirmado' : 'Editar estimación'}">
            <i class="bi bi-pencil"></i> Editar
        </a>
    `;
    
    const btnConfirmar = !esConfirmado ? `
        <button onclick="confirmarEstimacion(${despacho.ID}, '${despacho.CONTENEDOR}')" 
                class="btn btn-sm btn-success" 
                title="Confirmar estimación"
                ${despacho.ESTADO === 'PENDIENTE' ? 'disabled' : ''}>
            <i class="bi bi-check-circle"></i> Confirmar
        </button>
    ` : `
        <span class="badge bg-success">
            <i class="bi bi-check-circle-fill"></i> Confirmado
        </span>
    `;
    
    return `
        <div class="d-flex gap-1">
            ${btnEditar}
            ${btnConfirmar}
        </div>
    `;
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    const d = new Date(fecha + 'T00:00:00');
    return d.toLocaleDateString('es-AR', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

function confirmarEstimacion(id, contenedor) {
    Swal.fire({
        title: '¿Confirmar estimación?',
        html: `
            <p>Está a punto de confirmar la estimación del despacho:</p>
            <strong>${contenedor || 'ID: ' + id}</strong>
            <p class="mt-2 text-warning">
                <i class="bi bi-exclamation-triangle"></i>
                Una vez confirmada, no podrá modificar los valores.
            </p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            realizarConfirmacion(id);
        }
    });
}

function realizarConfirmacion(id) {
    Swal.fire({
        title: 'Confirmando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '../controller/confirmarEstimacion.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Actualizar la fila específica sin recargar toda la tabla
                actualizarFilaConfirmada(id);
                
                Swal.fire({
                    title: '¡Confirmado!',
                    text: response.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message,
                    icon: 'error',
                    confirmButtonColor: '#7066e0'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al confirmar la estimación',
                icon: 'error',
                confirmButtonColor: '#7066e0'
            });
        }
    });
}

function actualizarFilaConfirmada(id) {
    // Buscar la fila con el ID específico
    $('#tablaDespachos tbody tr').each(function() {
        const $row = $(this);
        const rowId = $row.find('td:first strong').text().replace('#', '');
        
        if (parseInt(rowId) === parseInt(id)) {
            // Actualizar el badge de estado
            const $estadoCell = $row.find('td:eq(6)');
            $estadoCell.html('<span class="badge bg-success">CONFIRMADO</span>');
            
            // Actualizar los botones de acción
            const $accionesCell = $row.find('td:eq(7)');
            $accionesCell.html(`
                <div class="d-flex gap-1">
                    <a href="components/editar-estimacion.php?id=${id}" 
                       class="btn btn-sm btn-primary" 
                       title="Editar estimación">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle-fill"></i> Confirmado
                    </span>
                </div>
            `);
            
            // Resaltar la fila brevemente
            $row.addClass('table-success');
            setTimeout(() => {
                $row.removeClass('table-success');
            }, 2000);
            
            return false; // Salir del each
        }
    });
}

function mostrarError(mensaje) {
    const tbody = $('#tablaDespachos tbody');
    tbody.html(`
        <tr>
            <td colspan="8" class="text-center py-4">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    ${mensaje}
                </div>
            </td>
        </tr>
    `);
}
