
const checkFactura = (div) => {
    let allTd = div.parentElement.parentElement.querySelectorAll("td");
    let fecha = allTd[0].textContent;
    let nro_sucursal = allTd[1].textContent.trim();
    let tipoComprobante = allTd[2].textContent.trim();
    let nroComprobante = allTd[3].textContent.trim();
    if(nroComprobante.length = 13){
        nroComprobante = ' '+nroComprobante;
    }
    let codCuenta = allTd[4].textContent.trim();
    let descripcionCuenta = allTd[5].textContent.trim();
    let monto = allTd[6].textContent.replace(/[$.]/g, "").trim();
    let leyenda = allTd[7].textContent.trim();
    let accion = "";
    let factura = 0;
    let control = 0;

    if(div.checked == true){
        accion = "checkFactura";
        factura = 1;
    }else{
        accion = "uncheckFactura";
        factura = 0;
    }

    $.ajax({
        type: "POST",
        url: "Controller/ControlEgresosController.php?accion="+accion,
        data: {
            fecha: fecha,
            nro_sucursal: nro_sucursal,
            tipoComprobante: tipoComprobante,
            nroComprobante: nroComprobante,
            codCuenta: codCuenta,
            descripcionCuenta: descripcionCuenta,
            monto: monto,
            leyenda: leyenda,
            factura: factura,
            control: control
        },
        success: function (response) {
            console.log("Factura actualizada correctamente");
        },
        error: function(xhr, status, error) {
            console.error('Error al actualizar factura:', error);
        }
    });
}

const checkControl = (div) => {
    let allTd = div.parentElement.parentElement.querySelectorAll("td");
    let fecha = allTd[0].textContent;
    let nro_sucursal = allTd[1].textContent.trim();
    let tipoComprobante = allTd[2].textContent.trim();
    let nroComprobante = allTd[3].textContent.trim();
    if(nroComprobante.length = 13){
        nroComprobante = ' '+nroComprobante;
    }
    let codCuenta = allTd[4].textContent.trim();
    let descripcionCuenta = allTd[5].textContent.trim();
    let monto = allTd[6].textContent.replace(/[$.]/g, "").trim();
    let leyenda = allTd[7].textContent.trim();

    let accion = "";
    let factura = 0;
    let control = 0;
    let observaciones = "";

    if(div.checked == true){
        accion = "checkControl";
        control = 1;
    }else{
        accion = "uncheckControl";
        control = 0;
    }

    $.ajax({
        type: "POST",
        url: "Controller/ControlEgresosController.php?accion="+accion,
        data: {
            fecha: fecha,
            nro_sucursal: nro_sucursal,
            tipoComprobante: tipoComprobante,
            nroComprobante: nroComprobante,
            codCuenta: codCuenta,
            descripcionCuenta: descripcionCuenta,
            monto: monto,
            leyenda: leyenda,
            factura: factura,
            control: control,
            observaciones: observaciones
        },
        success: function (response) {
            console.log("Control actualizado correctamente");
        },
        error: function(xhr, status, error) {
            console.error('Error al actualizar control:', error);
        }
    });
}

// Función para mostrar imágenes con lógica de compatibilidad
const mostrarImagen = (divImagen, startIndex = 0) => {
    let nComp = divImagen.parentElement.parentElement.querySelectorAll("td")[3].textContent;
    let codCta = divImagen.parentElement.parentElement.querySelectorAll("td")[4].textContent;
    let codComp = divImagen.parentElement.parentElement.querySelectorAll("td")[2].textContent; // COD_COMP
    let nroSucursal = divImagen.parentElement.parentElement.querySelectorAll("td")[1].textContent;
    let fechaComprobante = divImagen.parentElement.parentElement.querySelectorAll("td")[0].textContent; // FECHA
    
    let carouselElement = document.querySelector('#carruselImagenes'); 
    carouselElement.innerHTML = ''; 

    // Buscar primero con nueva nomenclatura (incluye COD_COMP)
    $.ajax({
        url: "Controller/ControlEgresosController.php?accion=contarImagenes",
        type: "POST",
        data: { 
            nComp: nComp,
            codCta: codCta,
            codComp: codComp,
            nroSucursal: nroSucursal,
            fechaComprobante: fechaComprobante
        },
        success: function (response) {
            response = JSON.parse(response);
            
            if (response['cantidad'] === 0) {
                // Si no encuentra con nueva nomenclatura, buscar con la vieja
                // PERO solo si es del mismo año que cuando se guardó
                let nombreViejo = nComp + nroSucursal + codCta;
                $.ajax({
                    url: "Controller/ControlEgresosController.php?accion=contarImagenes",
                    type: "POST",
                    data: { 
                        nComp: nombreViejo,
                        codCta: codCta,
                        nroSucursal: nroSucursal,
                        fechaComprobante: fechaComprobante
                    },
                    success: function (responseViejo) {
                        responseViejo = JSON.parse(responseViejo);
                        procesarImagenes(responseViejo, carouselElement, startIndex);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al buscar imágenes con nomenclatura vieja:', error);
                        mostrarSinImagenes();
                    }
                });
            } else {
                procesarImagenes(response, carouselElement, startIndex);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al buscar imágenes:', error);
            mostrarSinImagenes();
        }
    });
};

// Función auxiliar para procesar las imágenes encontradas
function procesarImagenes(response, carouselElement, startIndex) {
    if (response['cantidad'] > 0) {
        let codigosImagenes = [];
        for (let index = 0; index < response['nombre'].length; index++) {
            codigosImagenes.push(response['nombre'][index] + '.jpg');
        }

        let modalContent = document.createElement('div');
        modalContent.className = 'modal-dialog modal-dialog-centered modal-fullscreen';

        let modalBody = document.createElement('div');
        modalBody.className = 'modal-content';

        let modalHeader = document.createElement('div');
        modalHeader.className = 'modal-header';
        
        let closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'close';
        closeButton.setAttribute('data-dismiss', 'modal');
        closeButton.setAttribute('aria-label', 'Close');
        closeButton.innerHTML = '<span aria-hidden="true">&times;</span>';
        modalHeader.appendChild(closeButton);
        
        modalBody.appendChild(modalHeader);

        let carousel = document.createElement('div');
        carousel.innerHTML = "";
        carousel.className = 'carousel slide';
        carousel.setAttribute('data-bs-ride', 'carousel');
        carousel.id = 'imageCarousel';

        let carouselInner = document.createElement('div');
        carouselInner.className = 'carousel-inner h-100';
        carouselInner.style.overflowY = 'hidden';

        codigosImagenes.forEach((imagen, index) => {
            validarExistenciaArchivo('../../../../Imagenes/egresosCaja/' + imagen, function (existe) {
                if (existe) {
                    let carouselItem = document.createElement('div');
                    carouselItem.className = index === startIndex ? 'carousel-item active h-100' : 'carousel-item h-100';
                    carouselItem.style = 'display:flex; align-items:center; justify-content:center; text-align:center; position: relative; min-height:80vh;';
                    let imgElement = document.createElement('img');
                    imgElement.src = '../../../../Imagenes/egresosCaja/' + imagen;
                    imgElement.className = 'd-block img-fluid mx-auto';
                    imgElement.style = 'max-height: 80vh; max-width: 90vw; width: auto; height: auto; margin: 0 auto;';

                    carouselItem.appendChild(imgElement);
                    carouselInner.appendChild(carouselItem);

                    // Crear el botón de rotar
                    let rotateButton = document.createElement('button');
                    rotateButton.type = 'button';
                    rotateButton.className = 'btn btn-primary';
                    rotateButton.style.width = '150px';
                    rotateButton.style.position = 'absolute';
                    rotateButton.style.bottom = '20px';
                    rotateButton.style.left = '50%';
                    rotateButton.style.transform = 'translateX(-50%)';
                    rotateButton.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Rotar Imagen';
                    rotateButton.addEventListener('click', function () {
                        rotarImagen();
                    });

                    carouselItem.appendChild(rotateButton);
                }
            });
        });

        carousel.appendChild(carouselInner);
        modalBody.appendChild(carousel);
        modalContent.appendChild(modalBody);
        carouselElement.appendChild(modalContent);

        let prevControl = document.createElement('button');
        prevControl.className = 'carousel-control-prev';
        prevControl.type = 'button';
        prevControl.setAttribute('data-bs-target', '#imageCarousel');
        prevControl.setAttribute('data-bs-slide', 'prev');
        prevControl.innerHTML = '<span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span>';
        prevControl.addEventListener('click', function() {
            pasarImagen(-1);
        });

        let nextControl = document.createElement('button');
        nextControl.className = 'carousel-control-next';
        nextControl.type = 'button';
        nextControl.setAttribute('data-bs-target', '#imageCarousel');
        nextControl.setAttribute('data-bs-slide', 'next');
        nextControl.innerHTML = '<span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span>';
        nextControl.addEventListener('click', function() {
            pasarImagen(1);
        });

        carousel.appendChild(prevControl);
        carousel.appendChild(nextControl);

        // Usar Bootstrap 4 modal
        $('#carruselImagenes').modal('show');
    } else {
        mostrarSinImagenes();
    }
}

// Función para mostrar mensaje cuando no hay imágenes
function mostrarSinImagenes() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: 'Sin imágenes',
            text: 'No hay imágenes para mostrar.'
        });
    } else {
        alert('No hay imágenes para mostrar.');
    }
}

// Función para pasar a la siguiente imagen
const pasarImagen = (pos) => {
    let items = document.querySelectorAll(".carousel-item");

    for (let index = 0; index < items.length; index++) {
        if (items[index].classList.contains("active")) {
            items[index].classList.remove("active");

            let newIndex = (index + pos + items.length) % items.length;
            items[newIndex].classList.add("active");
            break;
        }
    }
};

// Función para validar existencia de archivos
const validarExistenciaArchivo = (rutaArchivo, callback) => {
    const img = new Image();
    img.onload = function() {
        callback(true);
    };
    img.onerror = function() {
        callback(false);
    };
    img.src = rutaArchivo;
};

// Función para rotar imagen
const rotarImagen = () => {
    let carousel = document.querySelector('#carruselImagenes .carousel-inner');
    let activeItem = carousel.querySelector('.carousel-item.active img');

    if (activeItem) {
        let currentRotation = activeItem.getAttribute('data-rotation') || 0;
        let newRotation = (parseInt(currentRotation) + 90) % 360;

        activeItem.style.transform = `rotate(${newRotation}deg)`;
        activeItem.setAttribute('data-rotation', newRotation);
    }
};

$(document).ready(function () {
    // Inicializar tooltips
    $(function() {
        $('[data-toggle="tooltip"]').tooltip()
    })

    // Inicializar DataTable
    $('#myTable').DataTable({
        "bLengthChange": false,
        "bInfo": false,
        "aaSorting": false,
        'columnDefs': [
            {
                "targets": "_all", 
                "className": "text-center",
                "sortable": false,
            },
        ],
        "oLanguage": {
            "sSearch": "Busqueda rapida:",
            "sSearchPlaceholder": "Sobre cualquier campo"
        },
    });

    // Inicializar el toggle de banderas
    if (typeof $.fn.bootstrapToggle !== 'undefined') {
        $('#checkEntorno').bootstrapToggle();
    }
});

// --- Cambio de entorno (ARG/UY) ---
const cambiarEntorno = (element) => {
    // Mostrar indicador de carga
    $('body').append(`
        <div class="controlEgresos_loading" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
             background: rgba(255,255,255,0.9); z-index: 9999; display: flex; align-items: center; justify-content: center;">
            <div style="text-align: center;">
                <i class="bi bi-arrow-repeat" style="font-size: 2rem; animation: spin 1s linear infinite;"></i>
                <p style="margin-top: 1rem;">Cambiando entorno...</p>
            </div>
        </div>
        <style>
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        </style>
    `);

    // Obtener el valor seleccionado del select
    let entorno = element.value === "ARG" ? 0 : 1;
    
    // Actualizar la información de país mostrada
    const countryName = document.querySelector('.controlEgresos_country-name');
    const countryFlag = document.querySelector('.controlEgresos_flag');
    
    if (element.value === "ARG") {
        countryName.textContent = "ARGENTINA";
        countryFlag.src = "../assets/images/bandera_con_sol__55757_std.jpg";
        countryFlag.alt = "Argentina";
    } else {
        countryName.textContent = "URUGUAY";
        countryFlag.src = "../assets/images/UY.png";
        countryFlag.alt = "Uruguay";
    }
    
    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data: { entorno: entorno },
        success: function (data) {
            setTimeout(() => location.reload(), 500);
        },
        error: function(xhr, status, error) {
            console.error('Error al cambiar entorno:', error);
            $('.controlEgresos_loading').remove();
            alert('Error al cambiar el entorno. Por favor, intente nuevamente.');
        }
    });
}