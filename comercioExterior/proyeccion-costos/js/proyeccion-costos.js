/**
 * proyeccion-costos.js - Gestión de listado de despachos para PCI
 */

$(document).ready(function() {
    cargarDespachos();
});

function cargarDespachos() {
    $.ajax({
        url: 'controller/listarDespachos.php',
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
        
        const row = `
            <tr>
                <td><strong>#${despacho.ID}</strong></td>
                <td>${formatearFecha(despacho.FECHA_MOV)}</td>
                <td>${despacho.PROVEEDOR || '-'}</td>
                <td>${despacho.CONTENEDOR || '-'}</td>
                <td>${despacho.MATERIAL || '-'}</td>
                <td>${despacho.ORDEN_COMPRA || '-'}</td>
                <td>${estadoBadge}</td>
                <td>${botonesAccion}</td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Inicializar DataTable
    $('#tablaDespachos').DataTable({
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
    const btnEditar = `
        <a href="editar-estimacion.php?id=${despacho.ID}" 
           class="btn btn-sm btn-primary" 
           title="Editar estimación">
            <i class="bi bi-pencil"></i> Editar
        </a>
    `;
    
    const btnConfirmar = despacho.ESTADO !== 'CONFIRMADO' ? `
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
        url: 'controller/confirmarEstimacion.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: '¡Confirmado!',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
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
