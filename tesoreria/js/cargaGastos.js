
$(document).ready(function() {
    $('body').append('<input type="file" id="fileInput" multiple style="display: none;" />');
    inicializarSubidaFotos();
    inicializarBotones();
});


const inicializarBotones = () => {
    // Botón de subir fotos
    $('.btn-primary').on('click', function() {
        const row = $(this).closest('tr');
        const codComp = row.find('td:eq(1)').text().trim();
        const nComp = row.find('td:eq(2)').text().trim();
        const codCta = row.find('td:eq(3)').text().trim();
        subirFotos(this, codComp, nComp, codCta);
    });

    // Botón de ver fotos
    $('.btn-warning').on('click', function() {
        const row = $(this).closest('tr');
        const codComp = row.find('td:eq(1)').text().trim();
        const nComp = row.find('td:eq(2)').text().trim();
        const codCta = row.find('td:eq(3)').text().trim();
        mostrarFotos(codComp, nComp, codCta);
    });

    // Botón de guardar
    $('.btn-success').on('click', function() {
        const row = $(this).closest('tr');
        const codComp = row.find('td:eq(1)').text().trim();
        // Usamos .text() sin .trim() para N_COMP para preservar el espacio inicial
        const nComp = row.find('td:eq(2)').text();
        const codCta = row.find('td:eq(3)').text().trim();
        guardarRegistro(codComp, nComp, codCta, row);
    });
    // Inicialmente, ocultar botones de ver y guardar
    $('.btn-warning, .btn-success').hide();

    // Verificar estado inicial de cada fila
    $('table tbody tr').each(function() {
        const row = $(this);
        const codComp = row.find('td:eq(1)').text().trim();
        const nComp = row.find('td:eq(2)').text().trim();
        const codCta = row.find('td:eq(3)').text().trim();
        verificarEstadoFila(codComp, nComp, codCta, row);
    });
};


const inicializarSubidaFotos = () => {
    $('.btn-icon.btn-primary').off('click').on('click', function(e) {
        e.preventDefault();
        const row = $(this).closest('tr');
        const codComp = row.find('td:eq(1)').text().trim();
        const nComp = row.find('td:eq(2)').text().trim();
        const codCta = row.find('td:eq(3)').text().trim();

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.multiple = true;
        fileInput.accept = '.jpg,.jpeg,.png,.pdf';
        fileInput.style.display = 'none';
        fileInput.addEventListener('change', function() {
            subirFotos(this, codComp, nComp, codCta);
        });
        document.body.appendChild(fileInput);
        fileInput.click();
        document.body.removeChild(fileInput);
    });
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


const verificarEstadoFila = (codComp, nComp, codCta, row) => {
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=verificarEstado',
        type: 'GET',
        data: { codComp: codComp, nComp: nComp, codCta: codCta },
        success: function(response) {
            console.log("Respuesta de verificarEstado:", response);
            const result = JSON.parse(response);
            actualizarBotones(row, result.tieneFotos, result.estaGuardado);
        },
        error: function(xhr, status, error) {
            console.error('Error al verificar el estado de la fila:', error);
            console.log('Respuesta del servidor:', xhr.responseText);
        }
    });
};

const actualizarBotones = (row, tieneFotos, estaGuardado) => {
    const btnSubir = row.find('.btn-primary');
    const btnVer = row.find('.btn-warning');
    const btnGuardar = row.find('.btn-success');

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


const guardarRegistro = (codComp, nComp, codCta, row) => {
    $.ajax({
        url: 'controller/egresoCajaController.php?accion=guardarRegistro',
        type: 'POST',
        data: { codComp: codComp, nComp: nComp, codCta: codCta },
        success: function(response) {
            console.log("Respuesta de guardarRegistro:", response);
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    Swal.fire('Éxito', 'Registro guardado correctamente', 'success');
                    verificarEstadoFila(codComp, nComp, codCta, row);
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
                    console.error('Error detallado:', result.error);
                }
            } catch (e) {
                console.error('Error al parsear la respuesta:', e);
                Swal.fire('Error', 'Respuesta del servidor inválida', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al guardar el registro:', error);
            console.log('Respuesta del servidor:', xhr.responseText);
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
            // Inicializar el carrusel de Bootstrap
            new bootstrap.Carousel(modal.querySelector(`#carrusel-${codComp}-${nComp}-${codCta}`));

            // Añadir estilos personalizados para las flechas y el carrusel
            const style = document.createElement('style');
            style.textContent = `
                .carousel-control-prev, .carousel-control-next {
                    width: 10%;
                    opacity: 0.7;
                }
                .carousel-control-prev-icon, .carousel-control-next-icon {
                    background-color: rgba(0, 0, 0, 0.5);
                    border-radius: 50%;
                    padding: 20px;
                }
                .carousel-item img {
                    max-height: 70vh;
                    object-fit: contain;
                }
                .swal2-modal {
                    padding-bottom: 30px;
                }
                .delete-photo {
                    position: absolute;
                    bottom: 10px;
                    right: 10px;
                }
                .image-counter {
                    position: relative;
                    top: 5px;
                    left: 10px;
                    color: black;
                    padding: 5px 10px;
                    border-radius: 5px;
                    font-size: 18px;
                    font-weight: bold;
                }
            `;
            document.head.appendChild(style);
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
                            // Recargar la página
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