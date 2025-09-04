$(document).ready(function() {
    // Determine which view to render based on screen size
    if (window.matchMedia("(max-width: 768px)").matches) {
        renderCards(gastosData);
    } else {
        renderTable(gastosData);
    }

    // Initialize actions for both views
    initializeGastoActions();
});

const renderCards = (data) => {
    const container = $('#gastos-container-mobile');
    container.empty(); // Clear previous content
    data.forEach(gasto => {
        const cardHtml = `
            <div class="col-12">
                <div class="card gasto-card"
                     data-cod-comp="${gasto.COD_COMP}"
                     data-n-comp="${gasto.N_COMP}"
                     data-cod-cta="${gasto.COD_CTA}">
                    <div class="card-body">
                        <div class="gasto-card-header">
                            <h5 class="card-title">Gasto #${gasto.N_COMP}</h5>
                            <span class="gasto-fecha">${gasto.FECHA_CARD}</span>
                        </div>
                        <p class="card-text"><strong>Cuenta:</strong> ${gasto.DESC_CUENTA} (${gasto.COD_CTA})</p>
                        <p class="card-text"><strong>Monto:</strong> $${gasto.MONTO}</p>
                        <p class="card-text"><strong>Usuario:</strong> ${gasto.USUARIO}</p>
                        <p class="card-text"><strong>Leyenda:</strong> ${gasto.LEYENDA}</p>
                        <div class="gasto-card-actions">
                            <button class="btn btn-primary btn-icon btn-upload"><i class="fas fa-upload"></i></button>
                            <button class="btn btn-warning btn-icon btn-view"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        const cardElement = $(cardHtml);
        container.append(cardElement);
        verificarEstado(gasto.COD_COMP, gasto.N_COMP, gasto.COD_CTA, cardElement.find('.gasto-card'));
    });
};

const renderTable = (data) => {
    const tableBody = $('#gastosTable tbody');
    tableBody.empty(); // Clear previous content
    data.forEach(gasto => {
        const rowHtml = `
            <tr data-cod-comp="${gasto.COD_COMP}"
                data-n-comp="${gasto.N_COMP}"
                data-cod-cta="${gasto.COD_CTA}">
                <td>${gasto.FECHA_TABLE}</td>
                <td>${gasto.COD_COMP}</td>
                <td>${gasto.N_COMP}</td>
                <td>${gasto.COD_CTA}</td>
                <td>${gasto.DESC_CUENTA}</td>
                <td>$${gasto.MONTO}</td>
                <td>${gasto.USUARIO}</td>
                <td>${gasto.LEYENDA}</td>
                <td>
                    <button class="btn btn-primary btn-icon btn-upload"><i class="fas fa-upload"></i></button>
                    <button class="btn btn-warning btn-icon btn-view"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `;
        const rowElement = $(rowHtml);
        tableBody.append(rowElement);
    });

    // Initialize DataTables
    $('#gastosTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        drawCallback: function(settings) {
            $('#gastosTable tbody tr').each(function() {
                const row = $(this);
                const codComp = row.data('cod-comp');
                const nComp = row.data('n-comp');
                const codCta = row.data('cod-cta');
                if(codComp) { // Ensure row has data
                    verificarEstado(codComp, nComp, codCta, row);
                }
            });
        }
    });
};

const initializeGastoActions = () => {
    const container = $('.container-fluid');

    container.on('click', '.btn-upload', function() {
        const element = $(this).closest('[data-cod-comp]');
        handleFileUpload(element);
    });

    container.on('click', '.btn-view', function() {
        const element = $(this).closest('[data-cod-comp]');
        const codComp = element.data('cod-comp');
        const nComp = element.data('n-comp');
        const codCta = element.data('cod-cta');
        mostrarFotos(codComp, nComp, codCta);
    });

    $('#mobileSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.gasto-card').each(function() {
            const card = $(this);
            const cardText = card.text().toLowerCase();
            if (cardText.includes(searchTerm)) {
                card.closest('.col-12').show();
            } else {
                card.closest('.col-12').hide();
            }
        });
    });
};

const handleFileUpload = (element) => {
    const triggerFileInput = (capture) => {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.multiple = true;
        if (capture) {
            fileInput.accept = 'image/*';
            fileInput.capture = 'environment';
        } else {
            fileInput.accept = 'image/*,application/pdf';
        }
        fileInput.style.display = 'none';
        fileInput.addEventListener('change', function() {
            subirFotos(this, element);
            document.body.removeChild(fileInput);
        });
        document.body.appendChild(fileInput);
        fileInput.click();
    };

    const handleCamera = () => {
        if (navigator.permissions && navigator.permissions.query) {
            navigator.permissions.query({ name: 'camera' }).then(permissionStatus => {
                if (permissionStatus.state === 'granted' || permissionStatus.state === 'prompt') {
                    triggerFileInput(true);
                } else if (permissionStatus.state === 'denied') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Permiso de cámara denegado',
                        text: 'Has bloqueado el acceso a la cámara. Por favor, habilítalo en la configuración de tu navegador para poder tomar fotos.',
                    });
                }
            });
        } else {
            triggerFileInput(true);
        }
    };

    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        Swal.fire({
            title: 'Seleccionar acción',
            text: '¿Deseas tomar una foto o seleccionar un archivo?',
            showDenyButton: true,
            confirmButtonText: `Tomar Foto`,
            denyButtonText: `Elegir Archivo`,
        }).then((result) => {
            if (result.isConfirmed) {
                handleCamera();
            } else if (result.isDenied) {
                triggerFileInput(false);
            }
        })
    } else {
        triggerFileInput(false);
    }
};

const verificarEstado = (codComp, nComp, codCta, element) => {
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=verificarEstado',
        type: 'GET',
        data: { codComp: codComp, nComp: nComp, codCta: codCta },
        dataType: 'json',
        success: function(response) {
            console.log('Estado verificado:', response);
            
            if (response.error) {
                console.error('Error en verificarEstado:', response.error);
                actualizarBotones(element, false, false);
                return;
            }
            
            actualizarBotones(element, response.tieneFotos, response.estaGuardado);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error al verificar el estado:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            actualizarBotones(element, false, false);
        }
    });
};

const actualizarBotones = (element, tieneFotos, estaGuardado) => {
    const btnSubir = element.find('.btn-upload');
    const btnVer = element.find('.btn-view');

    console.log('Actualizando botones:', { 
        tieneFotos, 
        estaGuardado,
        elemento: element.data('cod-comp') + '-' + element.data('n-comp') + '-' + element.data('cod-cta')
    });

    if (estaGuardado) {
        // Si está guardado, solo mostrar ver (sin permitir subir más)
        btnSubir.hide();
        btnVer.show();
        console.log('Caso: Registro guardado - Mostrar solo ver');
    } else if (tieneFotos) {
        // Si tiene fotos pero no está guardado, solo mostrar ver
        btnSubir.hide();
        btnVer.show();
        console.log('Caso: Tiene fotos pero no guardado - Mostrar solo ver');
    } else {
        // Si no tiene fotos, mostrar subir y ocultar ver
        btnSubir.show();
        btnVer.hide();
        console.log('Caso: Sin fotos - Mostrar solo subir');
    }
};

const subirFotos = async (input, element) => {
    const codComp = element.data('cod-comp');
    const nComp = element.data('n-comp');
    const codCta = element.data('cod-cta');
    const files = input.files;

    console.log('Iniciando subida de fotos:', { codComp, nComp, codCta, filesCount: files.length });

    if (!files || files.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se seleccionaron archivos.'
        });
        return;
    }

    const options = {
        maxSizeMB: 1,
        maxWidthOrHeight: 1920,
        useWebWorker: true
    };

    const formData = new FormData();
    formData.append('codComp', codComp);
    formData.append('nComp', nComp);
    formData.append('codCta', codCta);

    // Mostrar loading
    Swal.fire({
        title: 'Subiendo archivos...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        // Comprimir archivos si son imágenes
        const compressionPromises = Array.from(files).map((file, index) => {
            console.log(`Procesando archivo ${index}: ${file.name}, tipo: ${file.type}, tamaño: ${file.size}`);
            
            if (file.type.startsWith('image/')) {
                return imageCompression(file, options);
            }
            return Promise.resolve(file);
        });

        const compressedFiles = await Promise.all(compressionPromises);
        
        compressedFiles.forEach((file, index) => {
            console.log(`Archivo ${index} después de compresión: ${file.name}, tamaño: ${file.size}`);
            formData.append('fotos[]', file, file.name);
        });

        $.ajax({
            url: 'controller/egresoCajaController.php?accion=subirFotos',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                Swal.close();
                console.log('Respuesta subida completa:', response);
                
                if (response.error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error,
                        footer: response.mensajes ? response.mensajes.join('<br>') : ''
                    });
                    return;
                }
                
                if (response.success && response.archivos_subidos > 0) {
                    console.log(`${response.archivos_subidos} archivos subidos exitosamente`);
                    
                    // Actualizar botones inmediatamente
                    actualizarBotones(element, true, false);
                    
                    // Intentar guardar el registro
                    guardarRegistro(codComp, nComp, codCta, element);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudieron subir los archivos correctamente.',
                        footer: response.mensajes ? response.mensajes.join('<br>') : ''
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error AJAX en subida:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                let errorMessage = 'Error al subir los archivos';
                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        errorMessage = errorResponse.error || errorMessage;
                    } catch (e) {
                        errorMessage += ': ' + xhr.responseText.substring(0, 100);
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Comunicación',
                    text: errorMessage
                });
            }
        });
    } catch (error) {
        Swal.close();
        console.error('Error en compresión:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Compresión',
            text: 'Hubo un error al comprimir las imágenes: ' + error.message
        });
    }
};

const guardarRegistro = (codComp, nComp, codCta, element) => {
    console.log('Intentando guardar registro:', { codComp, nComp, codCta });
    
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=guardarRegistro',
        type: 'POST',
        data: { codComp: codComp, nComp: nComp, codCta: codCta },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta guardar registro:', response);
            
            if (response.success) {
                // Actualizar botones para mostrar que está guardado
                actualizarBotones(element, true, true);
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Las fotos han sido subidas y el registro guardado correctamente.',
                    showConfirmButton: true,
                    timer: 3000
                });
            } else {
                let errorMsg = 'No se pudo guardar el registro';
                
                if (response.error) {
                    if (response.error === 'El registro ya existe en la tabla de destino') {
                        errorMsg = 'Este registro ya fue guardado previamente.';
                    } else {
                        errorMsg += ': ' + response.error;
                    }
                }
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: errorMsg
                });
                
                // Aunque no se guardó, las fotos sí existen, así que actualizar botones
                actualizarBotones(element, true, false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX al guardar registro:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un problema al comunicarse con el servidor para guardar el registro.'
            });
            
            // Las fotos sí existen, así que actualizar botones apropiadamente
            actualizarBotones(element, true, false);
        }
    });
};

const mostrarFotos = (codComp, nComp, codCta) => {
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=obtenerFotosYEstado',
        type: 'GET',
        data: { codComp: codComp, nComp: nComp, codCta: codCta },
        dataType: 'json', // Especificar que esperamos JSON
        success: function(response) {
            console.log('Respuesta obtenerFotosYEstado:', response);
            
            // Ya no necesitamos JSON.parse porque jQuery lo convierte automáticamente
            if (response.archivos && response.archivos.length > 0) {
                crearCarrusel(response.archivos, codComp, nComp, codCta, response.estaGuardado);
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin archivos',
                    text: 'No hay archivos para mostrar.',
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al obtener fotos:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al obtener los archivos.',
            });
        }
    });
};

const crearCarrusel = (archivos, codComp, nComp, codCta, estaGuardado) => {
    const totalArchivos = archivos.length;
    
    // Crear título responsive
    const isMobile = window.innerWidth <= 768;
    const titulo = isMobile 
        ? `Archivos ${codComp}-${nComp}` 
        : `Archivos de ${codComp}-${nComp}-${codCta}`;
    
    let carruselHTML = `
        <div id="carrusel-${codComp}-${nComp}-${codCta}" class="carousel slide carousel-responsive" data-bs-ride="carousel">
            <div class="carousel-inner">
    `;

    archivos.forEach((archivo, index) => {
        const extension = archivo.split('.').pop().toLowerCase();
        let contenido;

        if (['jpg', 'jpeg', 'png'].includes(extension)) {
            contenido = `
                <img src="../image/gastosTesoreria/${archivo}" 
                     class="d-block" 
                     alt="Archivo ${index + 1}"
                     style="max-width: 100%; max-height: 60vh; height: auto; object-fit: contain;">
            `;
        } else if (extension === 'pdf') {
            const isMobile = window.innerWidth <= 768;
            if (isMobile) {
                // En móvil, mostrar enlace de descarga en lugar de embed
                contenido = `
                    <div class="pdf-mobile-container" style="text-align: center; padding: 20px;">
                        <i class="fas fa-file-pdf" style="font-size: 4rem; color: #dc3545; margin-bottom: 15px;"></i>
                        <h5>Documento PDF</h5>
                        <p style="margin-bottom: 20px;">${archivo}</p>
                        <a href="../image/gastosTesoreria/${archivo}" 
                           target="_blank" 
                           class="btn btn-primary">
                            <i class="fas fa-download"></i> Descargar/Ver PDF
                        </a>
                    </div>
                `;
            } else {
                contenido = `
                    <embed src="../image/gastosTesoreria/${archivo}" 
                           type="application/pdf" 
                           width="100%" 
                           height="500px"
                           style="border-radius: 4px;" />
                    <p style="margin-top: 10px;">
                        <a href="../image/gastosTesoreria/${archivo}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt"></i> Abrir en nueva ventana
                        </a>
                    </p>
                `;
            }
        } else {
            contenido = `
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-file" style="font-size: 3rem; color: #6c757d; margin-bottom: 10px;"></i>
                    <p>Tipo de archivo no soportado para visualización: ${extension}</p>
                    <a href="../image/gastosTesoreria/${archivo}" 
                       download 
                       class="btn btn-primary">
                        <i class="fas fa-download"></i> Descargar
                    </a>
                </div>
            `;
        }

        let deleteButton = '';
        if (!estaGuardado) {
            deleteButton = `
                <button class="btn btn-danger delete-photo" 
                        onclick="eliminarArchivo('${archivo}', '${codComp}', '${nComp}', '${codCta}')"
                        style="margin-top: 10px;">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            `;
        }

        carruselHTML += `
            <div class="carousel-item ${index === 0 ? 'active' : ''}">
                <div class="w-100 d-flex flex-column align-items-center justify-content-center">
                    ${contenido}
                    ${deleteButton}
                </div>
                <div class="image-counter">${index + 1}/${totalArchivos}</div>
            </div>
        `;
    });

    // Solo mostrar controles si hay más de 1 archivo
    const controles = totalArchivos > 1 ? `
        <button class="carousel-control-prev" type="button" data-bs-target="#carrusel-${codComp}-${nComp}-${codCta}" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carrusel-${codComp}-${nComp}-${codCta}" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
        </button>
    ` : '';

    carruselHTML += `
            </div>
            ${controles}
        </div>
    `;

    // Configuración responsive del modal
    const modalConfig = {
        title: titulo,
        html: carruselHTML,
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'modal-fotos'
        },
        width: window.innerWidth <= 768 ? '98%' : '80%',
        padding: window.innerWidth <= 768 ? '10px' : '20px',
        didOpen: (modal) => {
            // Inicializar carrusel
            const carousel = modal.querySelector(`#carrusel-${codComp}-${nComp}-${codCta}`);
            if (carousel) {
                new bootstrap.Carousel(carousel, {
                    interval: false, // Desactivar auto-slide
                    wrap: true,
                    touch: true // Habilitar gestos táctiles
                });
            }
            
            // Ajustar altura en móviles
            if (window.innerWidth <= 768) {
                modal.style.maxHeight = '95vh';
                modal.style.overflowY = 'auto';
            }
        }
    };

    Swal.fire(modalConfig);
};

const eliminarArchivo = (foto, codComp, nComp, codCta) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "No podrás revertir esta acción",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'controller/egresoCajaController.php?accion=eliminarFoto',
                type: 'POST',
                data: { foto: foto, codComp: codComp, nComp: nComp, codCta: codCta },
                dataType: 'json',
                success: function(response) {
                    console.log('Respuesta eliminarFoto:', response);
                    
                    if(response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminada!',
                            text: response.mensaje || 'La foto ha sido eliminada.',
                            showConfirmButton: true,
                            timer: 2000
                        }).then(() => {
                            // Cerrar el modal de fotos
                            Swal.close();
                            
                            // Verificar el estado actualizado y actualizar botones
                            const element = $(`[data-cod-comp="${codComp}"][data-n-comp="${nComp}"][data-cod-cta="${codCta}"]`);
                            if (element.length > 0) {
                                verificarEstado(codComp, nComp, codCta, element);
                            }
                            
                            // Si estamos en el modal de fotos, cerrarlo y recargar
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        });
                    } else {
                        Swal.fire(
                            'Error',
                            response.error || 'No se pudo eliminar la foto.',
                            'error'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al eliminar foto:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    
                    Swal.fire(
                        'Error',
                        'Hubo un problema al comunicarse con el servidor.',
                        'error'
                    );
                }
            });
        }
    });
};