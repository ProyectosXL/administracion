
$(document).ready(function() {

    // Verificar si es Casa Central y mostrar selector de sector
    const isCasaCentral = $('#numSucursal').text().trim() === 'Casa Central';
    if (isCasaCentral) {
        $('#sectorSelector').show();
    }
    
    const table = $('#empleadosTable').DataTable({
        ajax: {
            url: 'Controller/getEmpleadosGrupo.php',
            type: 'GET',
            data: function(d) {
                return {
                    ...d,
                    sector: $('#sector').val()
                };
            },
            dataSrc: function(json) {
                // Asegurar que siempre devolvemos un array
                return json.data || [];
            }
        },
        columns: [
            { data: 'NRO_LEGAJO' },
            { data: 'APELLIDO_Y_NOMBRE' },
            {
                data: null,
                render: function() {
                    return '<input type="text" class="form-control importe-input" placeholder="$0">';
                }
            }
        ],
        language: {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sSearch": "Buscar:",
            "sLoadingRecords": "Cargando..."
        },
        processing: true,
        pageLength: 50,
        order: [[1, 'asc']]
    });

    $('#sector').on('change', function() {
        table.ajax.reload();
    });

    table.on('draw', function() {
        $('.importe-input').each(function() {
            new Cleave(this, {
                numeral: true,
                numeralThousandsGroupStyle: 'thousand',
                prefix: '$ ',
                numeralDecimalScale: 0
            });
        });
    });
});