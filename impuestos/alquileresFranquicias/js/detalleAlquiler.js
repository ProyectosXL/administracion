$(document).ready(function() {
    // Manejador para los botones "Subir archivo"
    $(document).on('click', '.subir-archivo', function() {
        var tipo = $(this).data('tipo');
        var id = $(this).data('id');
        $('#contratoId').val(id);
        $('#tipoArchivo').val(tipo);
        $('#subirArchivoModalLabel').text('Subir ' + tipo.charAt(0).toUpperCase() + tipo.slice(1));
        
        // Limpiar el input de archivo y ocultar el botón de ver PDF
        $('#archivo').val('');
        $('#nombreArchivo').text('');
        $('#verPdfBtn').hide();
        
        var myModal = new bootstrap.Modal(document.getElementById('subirArchivoModal'));
        myModal.show();
    });

    const calcularVigencia = () => {
  
            var today = new Date();
            today.setHours(0, 0, 0, 0);

            $('#contratosTable tbody tr').each(function() {
                var vigDesde = new Date($(this).find('td:eq(3)').text());
                var vigHasta = new Date($(this).find('td:eq(4)').text());
                
                if (today >= vigDesde && today <= vigHasta) {
                    $(this).find('.vigente-cell').html('<i class="fas fa-check-circle text-success"></i>');
                } else {
                    $(this).find('.vigente-cell').html('<i class="fas fa-times-circle text-danger"></i>');
                }
            });
    }
    
    $('#archivo').on('change', function() {
        var file = this.files[0];
        if (file) {
            var fileName = file.name;
            $('#nombreArchivo').text(fileName);

            // Mostrar vista previa si es un archivo PDF
            if (file.type === 'application/pdf') {
                var fileURL = URL.createObjectURL(file);
                $('#pdfPreview').attr('src', fileURL).show();
                $('#verPdfBtn').show().data('archivoURL', fileURL);
            } else {
                $('#pdfPreview').hide().attr('src', '');
                $('#verPdfBtn').hide();
            }
        } else {
            $('#nombreArchivo').text('');
            $('#pdfPreview').hide().attr('src', '');
            $('#verPdfBtn').hide();
        }
    });
    
    
    $('#btnGuardarArchivo').on('click', function() {
        var formData = new FormData($('#subirArchivoForm')[0]);
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Subiendo...');
        
        $.ajax({
            url: 'controller/subirArchivoController.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Archivo subido exitosamente',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                    $('#verPdfBtn').show().data('archivo', response.fileName);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al subir el archivo: ' + response.message,
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la solicitud AJAX: ' + textStatus,
                });
            },
            complete: function() {
                $('#btnGuardarArchivo').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
            }
        });
    });

    // Manejador para el botón "Ver PDF" en el modal
    $('#verPdfBtn').on('click', function() {
        var archivoURL = $(this).data('archivoURL');
        if (archivoURL) {
            window.open(archivoURL, '_blank'); // Usar el URL temporal
        }
    });

    // Manejador para los botones "Ver archivo" en la tabla
    $(document).on('click', '.ver-archivo', function() {
        var archivo = $(this).data('archivo');
        if (archivo) {
            window.open('archivos/' + archivo, '_blank');
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo encontrar el archivo.',
            });
        }
    });

    calcularVigencia();
});
