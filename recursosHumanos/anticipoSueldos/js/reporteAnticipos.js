
// reporteAnticipos.js
$(document).ready(function() {
    // Cargar períodos
    $.ajax({
        url: 'Controller/getPeriodos.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (Array.isArray(response)) {
                const select = $('#periodFilter');
                response.forEach(item => {
                    select.append(`<option value="${item.PERIODO}">${item.PERIODO}</option>`);
                });
            } else {
                console.error('Respuesta inesperada:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar períodos:', error);
            console.error('Respuesta:', xhr.responseText);
        }
    });

    // Inicializar DataTable
    const table = $('#reportTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'Controller/getAnticipos.php',
            type: 'POST',
            data: function(d) {
                return {
                    ...d,
                    periodo: $('#periodFilter').val()
                };
            }
        },
        columns: [
            { data: 'NRO_LEGAJO' },
            { data: 'APELLIDO_Y_NOMBRE' },
            { data: 'DNI' },
            { data: 'PERIODO' },
            { data: 'IMPORTE', 
              render: function(data) {
                  return '$ ' + data;
              }
            },
            { data: 'FECHA_CARGA' }
        ],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel me-2"></i>Excel',
                className: 'btn btn-success',
                title: 'Reporte de Anticipos'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf me-2"></i>PDF',
                className: 'btn btn-danger',
                title: 'Reporte de Anticipos'
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json'
        },
        pageLength: 10,
        ordering: true,
        order: [[5, 'desc']],
        responsive: true
    });

    // Manejar filtros
    $('#periodFilter').on('change', function() {
        table.ajax.reload();
    });

    $('#searchBox').on('keyup', function() {
        table.search(this.value).draw();
    });
});