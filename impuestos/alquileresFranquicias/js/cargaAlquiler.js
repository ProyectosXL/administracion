
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

    $('#contratoComercial, #contratoLocacion, #habilitacion').on('change', function(event) {
        handleFileSelect(event, 'preview' + this.id.charAt(0).toUpperCase() + this.id.slice(1), 'actions' + this.id.charAt(0).toUpperCase() + this.id.slice(1));
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
        
        var formData = new FormData(this);

        $('#spinner').show();
        $('#btnGuardar').attr('disabled', true);

        $.ajax({
            url: 'controller/insertContratoController.php',
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
                console.error(jqXHR.responseText);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un problema al procesar su solicitud. Por favor, inténtelo de nuevo.'
                });
            }
        });
    });
});