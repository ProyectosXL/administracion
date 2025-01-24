
// js/cargarAnticipoGrupo.js
$(document).ready(function() {
    const table = $('#empleadosTable').DataTable({
        ajax: {
            url: 'Controller/getEmpleadosGrupo.php',
            dataSrc: ''
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
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla",
            "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar:",
            "sUrl":           "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            }
        },
        order: [[1, 'asc']],
        pageLength: 50,
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rtip'
    });

    // Inicializar Cleave.js para todos los inputs de importe
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

    // Submit del formulario
    $('#adelantoForm').on('submit', function(e) {
        e.preventDefault();
        // ... resto del código de submit ...
    });
});