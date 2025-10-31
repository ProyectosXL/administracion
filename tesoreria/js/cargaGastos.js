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
                            <button class="btn btn-danger btn-icon btn-delete"><i class="fas fa-trash"></i></button>
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
                    <button class="btn btn-danger btn-icon btn-delete"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
        const rowElement = $(rowHtml);
        tableBody.append(rowElement);
    });

    $('#gastosTable').DataTable({
        responsive: true,
        language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' },
        order: [[0, 'desc']],
        destroy: true, 
        drawCallback: function(settings) {
            $('#gastosTable tbody tr').each(function() {
                const row = $(this);
                const codComp = row.data('cod-comp');
                const nComp = row.data('n-comp');
                const codCta = row.data('cod-cta');
                if(codComp) {
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

    container.on('click', '.btn-delete', function() {
        const element = $(this).closest('[data-cod-comp]');
        const codComp = element.data('cod-comp');
        const nComp = element.data('n-comp');
        const codCta = element.data('cod-cta');
        eliminarGasto(codComp, nComp, codCta, element);
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
        data: { codComp, nComp, codCta },
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                console.error('Error en verificarEstado:', response.error);
                actualizarBotones(element, false, false);
                return;
            }
            actualizarBotones(element, response.tieneFotos, response.estaGuardado);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error al verificar el estado:', { status, error, responseText: xhr.responseText });
            actualizarBotones(element, false, false);
        }
    });
};

const actualizarBotones = (element, tieneFotos, estaGuardado) => {
    const btnSubir = element.find('.btn-upload');
    const btnVer = element.find('.btn-view');
    const btnEliminar = element.find('.btn-delete');

    if (estaGuardado) {
        btnSubir.hide();
        btnVer.show();
        btnEliminar.show();
    } else if (tieneFotos) {
        btnSubir.hide();
        btnVer.show();
        btnEliminar.hide();
    } else {
        btnSubir.show();
        btnVer.hide();
        btnEliminar.hide();
    }
};

const subirFotos = async (input, element) => {
    const codComp = element.data('cod-comp');
    const nComp = element.data('n-comp');
    const codCta = element.data('cod-cta');
    const files = input.files;

    if (!files || files.length === 0) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se seleccionaron archivos.' });
        return;
    }

    const options = { maxSizeMB: 1, maxWidthOrHeight: 1920, useWebWorker: true };
    const formData = new FormData();
    formData.append('codComp', codComp);
    formData.append('nComp', nComp);
    formData.append('codCta', codCta);

    Swal.fire({
        title: 'Subiendo archivos...', text: 'Por favor espere', allowOutsideClick: false, showConfirmButton: false,
        willOpen: () => { Swal.showLoading(); }
    });

    try {
        const compressionPromises = Array.from(files).map(file => {
            if (file.type.startsWith('image/')) {
                return imageCompression(file, options);
            }
            return Promise.resolve(file);
        });
        const compressedFiles = await Promise.all(compressionPromises);
        compressedFiles.forEach(file => {
            formData.append('fotos[]', file, file.name);
        });

        $.ajax({
            url: 'controller/egresoCajaController.php?accion=subirFotos',
            type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json',
            success: function(response) {
                Swal.close();
                if (response.error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.error, footer: response.mensajes ? response.mensajes.join('<br>') : '' });
                    return;
                }
                if (response.success && response.archivos_subidos > 0) {
                    guardarRegistro(codComp, nComp, codCta, element);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron subir los archivos.', footer: response.mensajes ? response.mensajes.join('<br>') : '' });
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Error de Comunicación', text: 'Error al subir los archivos.' });
            }
        });
    } catch (error) {
        Swal.close();
        Swal.fire({ icon: 'error', title: 'Error de Compresión', text: 'Hubo un error al procesar las imágenes.' });
    }
};

const guardarRegistro = (codComp, nComp, codCta, element) => {
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=guardarRegistro',
        type: 'POST',
        data: { codComp, nComp, codCta },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                actualizarBotones(element, true, true);
                Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Registro guardado correctamente.', timer: 2000 });
            } else {
                Swal.fire({ icon: 'warning', title: 'Advertencia', text: response.error || 'No se pudo guardar el registro.' });
                actualizarBotones(element, true, false);
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Problema al guardar el registro.' });
            actualizarBotones(element, true, false);
        }
    });
};

const mostrarFotos = (codComp, nComp, codCta) => {
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=obtenerFotosYEstado', type: 'GET', data: { codComp, nComp, codCta }, dataType: 'json',
        success: function(response) {
            if (response.archivos && response.archivos.length > 0) {
                crearCarrusel(response.archivos, codComp, nComp, codCta, response.estaGuardado);
            } else {
                Swal.fire({ icon: 'info', title: 'Sin archivos', text: 'No hay archivos para mostrar.' });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al obtener los archivos.' });
        }
    });
};

const crearCarrusel = (archivos, codComp, nComp, codCta, estaGuardado) => {
    const totalArchivos = archivos.length;
    const isMobile = window.innerWidth <= 768;
    const titulo = isMobile ? `Archivos ${codComp}-${nComp}` : `Archivos de ${codComp}-${nComp}-${codCta}`;
    
    let carruselHTML = `<div id="carrusel-${codComp}-${nComp}-${codCta}" class="carousel slide carousel-responsive" data-bs-ride="carousel"><div class="carousel-inner">`;

    archivos.forEach((archivo, index) => {
        const extension = archivo.split('.').pop().toLowerCase();
        let contenido;

        if (['jpg', 'jpeg', 'png'].includes(extension)) {
            contenido = `<img src="../image/gastosTesoreria/${archivo}" class="d-block" alt="Archivo ${index + 1}" style="max-width: 100%; max-height: 60vh; height: auto; object-fit: contain;">`;
        } else if (extension === 'pdf') {
            if (isMobile) {
                contenido = `<div class="pdf-mobile-container" style="text-align: center; padding: 20px;"><i class="fas fa-file-pdf" style="font-size: 4rem; color: #dc3545; margin-bottom: 15px;"></i><h5>Documento PDF</h5><p style="margin-bottom: 20px;">${archivo}</p><a href="../image/gastosTesoreria/${archivo}" target="_blank" class="btn btn-primary"><i class="fas fa-download"></i> Descargar/Ver PDF</a></div>`;
            } else {
                contenido = `<embed src="../image/gastosTesoreria/${archivo}" type="application/pdf" width="100%" height="500px" style="border-radius: 4px;" /><p style="margin-top: 10px;"><a href="../image/gastosTesoreria/${archivo}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt"></i> Abrir en nueva ventana</a></p>`;
            }
        } else {
            contenido = `<div style="text-align: center; padding: 20px;"><i class="fas fa-file" style="font-size: 3rem; color: #6c757d; margin-bottom: 10px;"></i><p>Tipo de archivo no soportado</p><a href="../image/gastosTesoreria/${archivo}" download class="btn btn-primary"><i class="fas fa-download"></i> Descargar</a></div>`;
        }

        let deleteButton = '';
        if (!estaGuardado) {
            deleteButton = `<button class="btn btn-danger delete-photo" onclick="eliminarArchivo('${archivo}', '${codComp}', '${nComp}', '${codCta}')" style="margin-top: 10px;"><i class="fas fa-trash"></i> Eliminar</button>`;
        }

        carruselHTML += `<div class="carousel-item ${index === 0 ? 'active' : ''}"><div class="w-100 d-flex flex-column align-items-center justify-content-center">${contenido}${deleteButton}</div><div class="image-counter">${index + 1}/${totalArchivos}</div></div>`;
    });

    const controles = totalArchivos > 1 ? `<button class="carousel-control-prev" type="button" data-bs-target="#carrusel-${codComp}-${nComp}-${codCta}" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span></button><button class="carousel-control-next" type="button" data-bs-target="#carrusel-${codComp}-${nComp}-${codCta}" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span></button>` : '';
    carruselHTML += `</div>${controles}</div>`;

    Swal.fire({
        title: titulo, html: carruselHTML, showCloseButton: true, showConfirmButton: false, customClass: { popup: 'modal-fotos' }, width: isMobile ? '98%' : '80%', padding: isMobile ? '10px' : '20px',
        didOpen: (modal) => { 
            const carousel = modal.querySelector(`#carrusel-${codComp}-${nComp}-${codCta}`);
            if (carousel) {
                new bootstrap.Carousel(carousel, { interval: false, wrap: true, touch: true }); 
            }
        }
    });
};

const eliminarArchivo = (foto, codComp, nComp, codCta) => {
    Swal.fire({
        title: '¿Estás seguro?', text: "No podrás revertir esta acción.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'controller/egresoCajaController.php?accion=eliminarFoto', type: 'POST', data: { foto, codComp, nComp, codCta }, dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire({ icon: 'success', title: 'Eliminada!', text: 'La foto ha sido eliminada.', timer: 1500, showConfirmButton: false })
                        .then(() => {
                             Swal.close();
                             const element = $(`[data-cod-comp="${codComp}"][data-n-comp="${nComp}"][data-cod-cta="${codCta}"]`);
                             verificarEstado(codComp, nComp, codCta, element);
                             setTimeout(() => {
                                 mostrarFotos(codComp, nComp, codCta);
                             }, 100);
                        });
                    } else {
                        Swal.fire('Error', response.error || 'No se pudo eliminar la foto.', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error', 'Hubo un problema al comunicarse con el servidor.', 'error');
                }
            });
        }
    });
};

const eliminarGasto = (codComp, nComp, codCta, element) => {
    Swal.fire({
        title: '¿Eliminar fotos y revertir?',
        text: "Se eliminarán todas las fotos de este gasto y volverá al estado 'pendiente'. ¿Continuar?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar fotos',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                // CORRECCIÓN: La URL va limpia, la acción va en los datos
                url: 'controller/egresoCajaController.php',
                type: 'POST',
                data: {
                    accion: 'eliminarGasto',
                    codComp: codComp,
                    nComp: nComp,
                    codCta: codCta
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            '¡Listo!',
                            'Las fotos fueron eliminadas. Ahora puede subir nuevas.',
                            'success'
                        );
                        verificarEstado(codComp, nComp, codCta, element);
                    } else {
                        Swal.fire(
                            'Error',
                            response.error || 'No se pudieron eliminar las fotos.',
                            'error'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire(
                        'Error de Comunicación',
                        'No se pudo conectar con el servidor.',
                        'error'
                    );
                }
            });
        }
    });
};