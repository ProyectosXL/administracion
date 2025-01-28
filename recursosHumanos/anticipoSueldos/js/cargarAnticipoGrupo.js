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
                render: function(data) {
                    if (data && data !== '0') {
                        return `<input type="text" class="form-control importe-input" value="$ ${data}" disabled>`;
                    }
                    return `<input type="text" class="form-control importe-input" value="$ 0">`;
                }
            }
            
        ],
        language: {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar MENU registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del START al END de un total de TOTAL registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de MAX registros)",
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
            let inputValue = $(this).val().replace(/\$|\s|,/g, '').trim(); // Eliminamos el signo $ y espacios
    
            new Cleave(this, {
                numeral: true,
                numeralThousandsGroupStyle: 'thousand',
                prefix: '$ ',
                numeralDecimalScale: 0
            });
    
            if (inputValue !== '' && parseInt(inputValue) > 0) {
                $(this).prop('disabled', true);
            } else {
                $(this).prop('disabled', false).val('$ 0'); // Valor por defecto cuando es 0 o vacío
            }
        });
    });

});
// Función para validar y enviar datos
const cargarAnticipoGrupo = () => {
    let valid = false;
    let dataToSend = [];

    $('#empleadosTable tbody tr').each(function() {
        let row = $(this);
        let legajo = row.find('td:first').text().trim();
        let nombre = row.find('td:eq(1)').text().trim();
        let input = row.find('.importe-input:not(:disabled)');

        let valor = input.val() ? input.val().replace(/\$|\s|,/g, '').trim() : '';

        if (valor !== '' && parseInt(valor) > 0) {
            valid = true;
            dataToSend.push({
                legajo: legajo,
                nombre: nombre,
                importe: valor
            });

            input.prop('disabled', true);
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

