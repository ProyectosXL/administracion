
$(document).ready(function() {
    // Configurar Select2 con mejoras para móvil
    $('#franquicia').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccione una franquicia',
        allowClear: true,
        dropdownAutoWidth: true,
        dropdownParent: $('#nuevoContratoModal')
    });

    // Función para formatear el tamaño de archivo
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Función para manejar la selección de archivos con información de tamaño
    function handleFileSelect(event) {
        const file = event.target.files[0];
        const inputId = event.target.id;
        const sizeInfoId = 'sizeInfo' + inputId.charAt(0).toUpperCase() + inputId.slice(1);
        const compressionId = 'compression' + inputId.charAt(0).toUpperCase() + inputId.slice(1);
        
        if (file) {
            const fileSize = formatFileSize(file.size);
            const maxSize = 10 * 1024 * 1024; // 10MB
            const largeSize = 2 * 1024 * 1024; // 2MB
            
            // Mostrar información del tamaño
            let sizeText = `Tamaño: ${fileSize}`;
            let sizeClass = 'text-info';
            
            if (file.size > maxSize) {
                sizeText += ' - ⚠️ Archivo muy grande (máx. 10MB)';
                sizeClass = 'text-danger';
            } else if (file.size > largeSize) {
                sizeText += ' - El servidor intentará optimizar el archivo';
                sizeClass = 'text-warning';
                $(`#${compressionId}`).addClass('show');
            }
            
            $(`#${sizeInfoId}`).text(sizeText).removeClass('text-info text-warning text-danger').addClass(sizeClass);
            
            // Validar tipo de archivo
            if (file.type !== 'application/pdf') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Formato de archivo',
                    text: 'Se recomienda usar archivos PDF para mejor compatibilidad.',
                    confirmButtonText: 'Continuar'
                });
            }
        } else {
            $(`#${sizeInfoId}`).text('');
            $(`#${compressionId}`).removeClass('show');
        }
    }

    // Aplicar el manejador a todos los inputs de archivo
    $('#contratoComercial, #contratoLocacion, #habilitacion').on('change', handleFileSelect);

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
        const inputId = fileInput.attr('id');
        const sizeInfoId = 'sizeInfo' + inputId.charAt(0).toUpperCase() + inputId.slice(1);
        const compressionId = 'compression' + inputId.charAt(0).toUpperCase() + inputId.slice(1);
        
        // Confirmar eliminación
        Swal.fire({
            title: '¿Eliminar archivo?',
            text: 'Se eliminará el archivo seleccionado.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                fileInput.val('');
                $(`#${sizeInfoId}`).text('');
                $(`#${compressionId}`).removeClass('show');
            }
        });
    });

    $('#contratoAlquilerForm').on('submit', async function(e) {
        e.preventDefault();

        let sucursal = document.querySelector('#franquicia').value;
        let fechaDesde = document.querySelector('#fechaDesde').value;
        let fechaHasta = document.querySelector('#fechaHasta').value;
        let contratoComercial = document.querySelector('#contratoComercial').files[0];

        if(sucursal == ''){
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar una franquicia.'
            });
            return;
        }

        if(fechaDesde == '' || fechaHasta == ''){
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un rango de fechas.'
            });
            return;
        }

        if(fechaDesde > fechaHasta){
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La fecha de inicio no puede ser mayor a la fecha de fin.'
            });
            return;
        }

        if(contratoComercial == undefined){
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe adjuntar el contrato comercial.'
            });
            return;
        }

        // Chequeo de solapamiento de vigencia antes de subir archivos
        let ignorarSolapamiento = false;
        try {
            const vigenciaCheck = await $.post('controller/validarVigenciaController.php', {
                franquicia: sucursal,
                fechaDesde: fechaDesde,
                fechaHasta: fechaHasta
            });

            if (vigenciaCheck.overlap) {
                const confirmResult = await Swal.fire({
                    icon: 'warning',
                    title: 'Solapamiento de vigencia',
                    text: 'Ya existe un contrato cuyas fechas se superponen con las ingresadas. ¿Deseas cargarlo de todas formas?',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#f0ad4e'
                });
                if (!confirmResult.isConfirmed) return;
                ignorarSolapamiento = true;
            }
        } catch (err) {
            // Si el chequeo previo falla, continuar y dejar que el backend decida
        }

        var formData = new FormData(this);
        if (ignorarSolapamiento) {
            formData.append('ignorarSolapamiento', '1');
        }

        $('#spinner').show();
        $('#btnGuardar').attr('disabled', true);

        $.ajax({
            url: 'controller/insertContratoController.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                
                // Mostrar progreso de subida
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('.upload-progress').show();
                        $('.progress-bar').css('width', percentComplete + '%').attr('aria-valuenow', percentComplete);
                    }
                }, false);
                
                return xhr;
            },
            success: function(response) {
                $('#spinner').hide();
                $('#btnGuardar').attr('disabled', false);
                $('.upload-progress').hide();

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
                $('.upload-progress').hide();

                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                console.error(jqXHR.responseText);
                
                let errorMessage = 'Hubo un problema al procesar su solicitud.';
                
                if (jqXHR.status === 413) {
                    errorMessage = 'El archivo es demasiado grande. Intente con un archivo más pequeño.';
                } else if (jqXHR.status === 0) {
                    errorMessage = 'Sin conexión a internet. Verifique su conexión y vuelva a intentar.';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    });

    // Mejorar experiencia en dispositivos táctiles
    if ('ontouchstart' in window) {
        // Agregar clase para dispositivos táctiles
        $('body').addClass('touch-device');
        
        // Mejorar el comportamiento de Select2 en móvil
        $('#franquicia').on('select2:open', function() {
            // Pequeño delay para asegurar que el dropdown esté renderizado
            setTimeout(function() {
                $('.select2-search__field').focus();
            }, 100);
        });
    }

    // Validación en tiempo real de fechas
    $('#fechaDesde, #fechaHasta').on('change', function() {
        const fechaDesde = $('#fechaDesde').val();
        const fechaHasta = $('#fechaHasta').val();
        
        if (fechaDesde && fechaHasta) {
            if (new Date(fechaDesde) > new Date(fechaHasta)) {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">La fecha de inicio no puede ser posterior a la fecha de fin.</div>');
                }
            } else {
                $('#fechaDesde, #fechaHasta').removeClass('is-invalid');
                $('.invalid-feedback').remove();
            }
        }
    });

    // Prevenir envío accidental del formulario en móvil
    $('#contratoAlquilerForm').on('submit', function() {
        // Deshabilitar botón inmediatamente para prevenir doble envío
        $('#btnGuardar').attr('disabled', true);
    });

    // Función para comprimir imágenes si se necesita (futuro)
    function compressImage(file, maxSizeMB = 2, quality = 0.7) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = function() {
                // Calcular nuevas dimensiones manteniendo ratio
                let { width, height } = img;
                const maxDim = 1920;
                
                if (width > maxDim || height > maxDim) {
                    if (width > height) {
                        height = (height * maxDim) / width;
                        width = maxDim;
                    } else {
                        width = (width * maxDim) / height;
                        height = maxDim;
                    }
                }
                
                canvas.width = width;
                canvas.height = height;
                
                // Dibujar imagen redimensionada
                ctx.drawImage(img, 0, 0, width, height);
                
                // Convertir a blob
                canvas.toBlob(resolve, 'image/jpeg', quality);
            };
            
            img.src = URL.createObjectURL(file);
        });
    }
});