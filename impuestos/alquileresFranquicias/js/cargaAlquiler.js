
$(document).ready(function() {
    $('#franquicia').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccione una franquicia',
        allowClear: true
    });

    function handleFileSelect(event, previewId, actionsId) {
        const file = event.target.files[0];
        if (file) {
            const fileName = file.name;
            $(`#${previewId}`).text(fileName);
            $(`#${actionsId}`).show();
        } else {
            $(`#${previewId}`).text('');
            $(`#${actionsId}`).hide();
        }
    }

    $('#contratoComercial').on('change', function(event) {
        handleFileSelect(event, 'previewContratoComercial', 'actionsContratoComercial');
    });

    $('#contratoLocacion').on('change', function(event) {
        handleFileSelect(event, 'previewContratoLocacion', 'actionsContratoLocacion');
    });

    $('.view-file').on('click', function() {
        const fileInput = $(this).closest('.mb-3').find('input[type="file"]');
        const file = fileInput[0].files[0];
        if (file) {
            const fileURL = URL.createObjectURL(file);
            window.open(fileURL, '_blank');
        }
    });

    $('.delete-file').on('click', function() {
        const fileInput = $(this).closest('.mb-3').find('input[type="file"]');
        fileInput.val('');
        const previewDiv = $(this).closest('.mb-3').find('.file-preview');
        previewDiv.text('');
        $(this).closest('.file-actions').hide();
    });

    $('#contratoAlquilerForm').on('submit', function(e) {
        e.preventDefault();
        
        var franquicia = $('#franquicia').val();
        var fechaDesde = $('#fechaDesde').val();
        var fechaHasta = $('#fechaHasta').val();

        if (!franquicia || !fechaDesde || !fechaHasta) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor, complete todos los campos obligatorios.'
            });
            return;
        }

        const formData = new FormData(this);

        $('#spinner').show();
        $('#btnGuardar').attr('disabled', true);

        $.ajax({
            url: 'Controller/insertContratoController.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#spinner').hide();
                $('#btnGuardar').attr('disabled', false);

                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Ocurrió un error al procesar la solicitud.'
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('#spinner').hide();
                $('#btnGuardar').attr('disabled', false);
            
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                console.error("Respuesta del servidor:", jqXHR.responseText);
                
                let errorMessage = 'Hubo un problema al procesar su solicitud.';
                try {
                    const response = JSON.parse(jqXHR.responseText);
                    if (response && response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    console.error("Error parsing JSON response:", e);
                    errorMessage = "La respuesta del servidor no es JSON válido. Revisa la consola para más detalles.";
                }
            
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    });
});