$(document).ready(function() {

    // Verificar si es Casa Central y mostrar selector de sector
    const isCasaCentral = $('#numSucursal').text().trim() === 'Casa Central';
    if (isCasaCentral) {
        $('#sectorSelector').show();
    }
    let nroSucursal = document.querySelector('#numSucursal').getAttribute('attr-numSucursal');

    const table = $('#empleadosTable').DataTable({
        ajax: {
            url: 'Controller/getEmpleadosGrupo.php?sucursal=' + nroSucursal,
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
                data: 'IMPORTE',
                render: function(data, type, row) {
                    let importe = data ? data : '0'; 
                    let disabled = parseFloat(importe) > 0 ? 'readonly' : ''; 
                    return `<input type="text" class="form-control importe-input" value="${importe}" ${disabled} placeholder="0">`;
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
                numeralDecimalScale: 0
            });
        });        
    });

    // Manejar la validación y el envío del formulario
    $('#adelantoForm').on('submit', function(e) {
        e.preventDefault(); // Evitar el envío predeterminado del formulario
        cargarAnticipoGrupo();
    });
});

// Función para validar y enviar datos
const cargarAnticipoGrupo = () => {
    let valid = false;
    let dataToSend = [];

    $('.importe-input').each(function() {
        let valor = $(this).val().replace(/\$|,/g, '').trim();
        let row = $(this).closest('tr');
        let legajo = row.find('td:first').text().trim();
        let nombre = row.find('td:eq(1)').text().trim();  // Asegúrate de que se captura correctamente el nombre
    
        if (valor !== '' && parseFloat(valor) > 0) {
            valid = true;
            dataToSend.push({ legajo: legajo, nombre: nombre, importe: valor });
        }
    });
    

    if (!valid) {
        Swal.fire({
            icon: 'warning',
            title: 'Validación',
            text: 'Debe haber al menos un registro con un importe válido antes de enviar.'
        });
        return;
    }

    // Enviar datos al backend
    $.ajax({
        url: 'Controller/guardarAnticipo.php',
        type: 'POST',
        data: JSON.stringify(dataToSend),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.message
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un problema al guardar los datos'
            });
        }
    });
};

