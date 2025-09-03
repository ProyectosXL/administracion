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
        container.append(cardHtml);
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
        tableBody.append(rowHtml);
    });

    // Initialize DataTables
    $('#gastosTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        order: [[0, 'desc']]
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

    $('[data-cod-comp]').each(function() {
        const element = $(this);
        const codComp = element.data('cod-comp');
        const nComp = element.data('n-comp');
        const codCta = element.data('cod-cta');
        verificarEstado(codComp, nComp, codCta, element);
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
    const codComp = element.data('cod-comp');
    const nComp = element.data('n-comp');
    const codCta = element.data('cod-cta');

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
        success: function(response) {
            const result = JSON.parse(response);
            actualizarBotones(element, result.tieneFotos, result.estaGuardado);
        },
        error: function(xhr, status, error) {
            console.error('Error al verificar el estado:', error);
        }
    });
};

const actualizarBotones = (element, tieneFotos, estaGuardado) => {
    const btnSubir = element.find('.btn-upload');
    const btnVer = element.find('.btn-view');

    if (estaGuardado) {
        btnSubir.hide();
        btnVer.show();
    } else if (tieneFotos) {
        btnSubir.hide();
        btnVer.show();
    } else {
        btnSubir.show();
        btnVer.hide();
    }
};

const subirFotos = (input, element) => {
    const codComp = element.data('cod-comp');
    const nComp = element.data('n-comp');
    const codCta = element.data('cod-cta');

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
            guardarRegistro(codComp, nComp, codCta, element);
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

const guardarRegistro = (codComp, nComp, codCta, element) => {
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=guardarRegistro',
        type: 'POST',
        data: { codComp: codComp, nComp: nComp, codCta: codCta },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    Swal.fire('¡Éxito!', 'La foto ha sido subida y el registro guardado correctamente.', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    let errorMsg = result.error === 'El registro ya existe en la tabla de destino'
                                 ? 'Este registro ya fue guardado previamente.'
                                 : 'No se pudo guardar el registro.';
                    Swal.fire('Error', errorMsg, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Respuesta del servidor inválida', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Hubo un problema al comunicarse con el servidor para guardar.', 'error');
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