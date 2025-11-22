$(document).ready(function() {
    // Cargar despachos
    cargarDespachos();
    
    // Inicializar DataTable
    const table = $('#tablaDespachos').DataTable({
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
});

function cargarDespachos() {
    $.ajax({
        url: 'Controller/listarDespachos.php',
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
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="editarDespacho.php?id=${despacho.ID}" class="btn-action btn-editar">
                            <i class="bi bi-pencil"></i> Completar
                        </a>
                        <a href="cargarCostos.php?id=${despacho.ID}" class="btn-action btn-costos">
                            <i class="bi bi-calculator"></i> Costos
                        </a>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
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
