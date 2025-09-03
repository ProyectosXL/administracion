$(document).ready(function() {
    initializeGastoActions();
});

const initializeGastoActions = () => {
    const container = $('#gastos-container');

    container.on('click', '.btn-upload', function() {
        const card = $(this).closest('.gasto-card');
        const codComp = card.data('cod-comp');
        const nComp = card.data('n-comp');
        const codCta = card.data('cod-cta');
        handleFileUpload(codComp, nComp, codCta);
    });

    container.on('click', '.btn-view', function() {
        const card = $(this).closest('.gasto-card');
        const codComp = card.data('cod-comp');
        const nComp = card.data('n-comp');
        const codCta = card.data('cod-cta');
        mostrarFotos(codComp, nComp, codCta);
    });

    container.on('click', '.btn-save', function() {
        const card = $(this).closest('.gasto-card');
        const codComp = card.data('cod-comp');
        const nComp = card.data('n-comp');
        const codCta = card.data('cod-cta');
        guardarRegistro(codComp, nComp, codCta, card);
    });

    $('.gasto-card').each(function() {
        const card = $(this);
        const codComp = card.data('cod-comp');
        const nComp = card.data('n-comp');
        const codCta = card.data('cod-cta');
        verificarEstadoCard(codComp, nComp, codCta, card);
    });
};

const handleFileUpload = (codComp, nComp, codCta) => {
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
            subirFotos(this, codComp, nComp, codCta);
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

const verificarEstadoCard = (codComp, nComp, codCta, card) => {
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=verificarEstado',
        type: 'GET',
        data: { codComp: codComp, nComp: nComp, codCta: codCta },
        success: function(response) {
            const result = JSON.parse(response);
            actualizarBotonesCard(card, result.tieneFotos, result.estaGuardado);
        },
        error: function(xhr, status, error) {
            console.error('Error al verificar el estado de la card:', error);
        }
    });
};

const actualizarBotonesCard = (card, tieneFotos, estaGuardado) => {
    const btnSubir = card.find('.btn-upload');
    const btnVer = card.find('.btn-view');
    const btnGuardar = card.find('.btn-save');

    if (estaGuardado) {
        btnSubir.hide();
        btnGuardar.hide();
        btnVer.show();
    } else if (tieneFotos) {
        btnSubir.hide();
        btnGuardar.show();
        btnVer.show();
    } else {
        btnSubir.show();
        btnGuardar.hide();
        btnVer.hide();
    }
};

const subirFotos = (input, codComp, nComp, codCta) => {
    const formData = new FormData();
    const files = input.files;

    for (let i = 0; i < files.length; i++) {
        formData.append('fotos[]', files[i]);
    }
    formData.append('codComp', codComp);
    formData.append('nComp', nComp);
    formData.append('codCta', codCta);

    $.ajax({
        url: 'controller/egresoCajaController.php?accion=subirFotos',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: response,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al subir los archivos.'
            });
        }
    });
};

const guardarRegistro = (codComp, nComp, codCta, card) => {
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=guardarRegistro',
        type: 'POST',
        data: { codComp: codComp, nComp: nComp, codCta: codCta },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    Swal.fire('Éxito', 'Registro guardado correctamente', 'success');
                    verificarEstadoCard(codComp, nComp, codCta, card);
                } else {
                    let errorMsg = 'No se pudo guardar el registro';
                    if (result.error) {
                        if (typeof result.error === 'string') {
                            errorMsg += ': ' + result.error;
                        } else if (Array.isArray(result.error)) {
                            errorMsg += ': ' + result.error.map(e => e.message).join(', ');
                        }
                    }
                    Swal.fire('Error', errorMsg, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Respuesta del servidor inválida', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Hubo un problema al comunicarse con el servidor', 'error');
        }
    });
};

const mostrarFotos = (codComp, nComp, codCta) => {
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=obtenerFotosYEstado',
        type: 'GET',
        data: { codComp: codComp, nComp: nComp, codCta: codCta },
        success: function(response) {
            const result = JSON.parse(response);
            if (result.archivos.length > 0) {
                crearCarrusel(result.archivos, codComp, nComp, codCta, result.estaGuardado);
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin archivos',
                    text: 'No hay archivos para mostrar.',
                });
            }
        },
        error: function() {
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
    let carruselHTML = `
        <div id="carrusel-${codComp}-${nComp}-${codCta}" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
    `;

    archivos.forEach((archivo, index) => {
        const extension = archivo.split('.').pop().toLowerCase();
        let contenido;

        if (['jpg', 'jpeg', 'png'].includes(extension)) {
            contenido = `<img src="../image/gastosTesoreria/${archivo}" class="d-block w-100" alt="Archivo ${index + 1}">`;
        } else if (extension === 'pdf') {
            contenido = `
                <embed src="../image/gastosTesoreria/${archivo}" type="application/pdf" width="100%" height="600px" />
                <p>Tu navegador no puede mostrar PDFs. <a href="../image/gastosTesoreria/${archivo}" target="_blank">Haz clic aquí para descargar el archivo.</a></p>
            `;
        } else {
            contenido = `<p>Tipo de archivo no soportado para visualización: ${extension}</p>`;
        }

        let deleteButton = '';
        if (!estaGuardado) {
            deleteButton = `
                <button class="btn btn-danger delete-photo" 
                        onclick="eliminarArchivo('${archivo}', '${codComp}', '${nComp}', '${codCta}')">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            `;
        }

        carruselHTML += `
            <div class="carousel-item ${index === 0 ? 'active' : ''}">
                ${contenido}
                ${deleteButton}
                <div class="image-counter">Imagen ${index + 1}/${totalArchivos}</div>
            </div>
        `;
    });

    carruselHTML += `
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carrusel-${codComp}-${nComp}-${codCta}" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carrusel-${codComp}-${nComp}-${codCta}" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>
    `;

    Swal.fire({
        title: `Archivos de ${codComp}-${nComp}-${codCta}`,
        html: carruselHTML,
        width: '40%',
        showCloseButton: true,
        showConfirmButton: false,
        didOpen: (modal) => {
            new bootstrap.Carousel(modal.querySelector(`#carrusel-${codComp}-${nComp}-${codCta}`));
        }
    });
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
                success: function(response) {
                    response = JSON.parse(response);
                    if(response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminada!',
                            text: 'La foto ha sido eliminada.',
                            allowOutsideClick: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Error',
                            'No se pudo eliminar la foto.',
                            'error'
                        );
                    }
                },
                error: function() {
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