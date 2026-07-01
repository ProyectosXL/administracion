const checkContabilizar = (div) => {

    allTd = div.parentElement.parentElement.querySelectorAll("td");
    let fecha = allTd[0].textContent;
    let nro_sucursal = allTd[1].textContent;
    let tipoComprobante = allTd[2].textContent;
    let nroComprobante = allTd[3].textContent;
    let codCuenta = allTd[4].textContent;
    let monto = allTd[6].textContent.replace(/[$.]/g, "");

    let accion = "";

    if(div.checked == true){
        accion = "checkContabilizar"
    }else{
        accion = "uncheckContabilizar"
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
            monto: monto,

        },
        success: function (response) {
        }
    });

    
}

$(document).ready( function () {
    
    $(function() {
        $('[data-toggle="tooltip"]').tooltip()
    })

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
    $('#checkEntorno').bootstrapToggle();

})


const mostrarImagen = (divImagen, startIndex = 0) => {
    let codigosImagenes = [];
    let nComp = divImagen.parentElement.parentElement.querySelectorAll("td")[3].textContent.trim();
    let nroSucursal = divImagen.parentElement.parentElement.querySelectorAll("td")[1].textContent.trim();
    let codComp = divImagen.parentElement.parentElement.querySelectorAll("td")[2].textContent.trim();
    let codCta = divImagen.parentElement.parentElement.querySelectorAll("td")[4].textContent.trim();
    let fechaComprobante = divImagen.parentElement.parentElement.querySelectorAll("td")[0].textContent.trim();
    let carouselElement = document.querySelector('#carruselImagenes'); 
    
    carouselElement.innerHTML = ''; 

    $.ajax({
      url: "Controller/ControlEgresosController.php?accion=contarImagenes",
      type: "POST",
      data: {
        nComp: nComp,
        nroSucursal: nroSucursal,
        codComp: codComp,
        codCta: codCta,
        fechaComprobante: fechaComprobante
      },
      success: function (response) {
        response = JSON.parse(response);

        if (response['cantidad'] > 0) {
          for (let index = 0; index < response['nombre'].length; index++) {
            codigosImagenes.push(response['nombre'][index] + '.jpg');
          }

          const fancyboxImages = codigosImagenes.map((imagen, i) => {
              return {
                  src: '../../../../Imagenes/egresosCaja/' + imagen,
                  caption: `Imagen ${i + 1} de ${codigosImagenes.length}`,
                  nombre: imagen
              };
          });

          // Variable para almacenar las rotaciones de cada imagen localmente
          const rotaciones = {};
          fancyboxImages.forEach((img, idx) => {
              rotaciones[idx] = 0;
          });

          try {
              const fancyboxInstance = Fancybox.show(fancyboxImages, {
                  startIndex: startIndex,
                  loop: true,
                  Toolbar: {
                      display: {
                          left: ['infobar'],
                          middle: [],
                          right: ['zoom', 'slideshow', 'thumbs', 'close']
                      }
                  },
                  on: {
                      done: (fancybox, slide) => {
                          // Aplicar rotación guardada a la imagen actual
                          const currentIndex = slide.index;
                          if (rotaciones[currentIndex] !== undefined && rotaciones[currentIndex] !== 0) {
                              setTimeout(() => {
                                  const img = document.querySelector('.fancybox__slide.is-selected img');
                                  if (img) {
                                      img.style.transform = `rotate(${rotaciones[currentIndex]}deg)`;
                                      img.style.transition = 'none';
                                  }
                              }, 100);
                          }
                          
                          // Agregar botones de rotación a la barra de herramientas
                          const toolbar = document.querySelector('.fancybox__toolbar');
                          if (toolbar && !toolbar.querySelector('.btn-rotate-container')) {
                              const btnStyle = 'background: rgba(30, 30, 30, 0.9); border: 2px solid white; color: white; cursor: pointer; padding: 10px; margin: 0 5px; border-radius: 5px; width: 45px; height: 45px; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; z-index: 99999; pointer-events: auto;';
                              
                              const btnContainer = document.createElement('div');
                              btnContainer.className = 'btn-rotate-container';
                              btnContainer.style.cssText = 'position: absolute; left: 50%; transform: translateX(-50%); display: flex; gap: 10px; z-index: 99999; pointer-events: auto;';
                              
                              const btnLeft = document.createElement('button');
                              btnLeft.className = 'btn-rotate btn-rotate-left';
                              btnLeft.type = 'button';
                              btnLeft.style.cssText = btnStyle;
                              btnLeft.innerHTML = '↶';
                              btnLeft.title = 'Rotar izquierda (antihorario)';
                              btnLeft.addEventListener('click', function(e) {
                                  e.preventDefault();
                                  e.stopPropagation();
                                  const currentSlide = fancybox.getSlide();
                                  if (currentSlide) {
                                      const idx = currentSlide.index;
                                      rotaciones[idx] = (rotaciones[idx] || 0) - 90;
                                      const img = document.querySelector('.fancybox__slide.is-selected img');
                                      if (img) {
                                          img.style.transform = `rotate(${rotaciones[idx]}deg)`;
                                          img.style.transition = 'transform 0.3s ease';
                                      }
                                  }
                              });
                              
                              const btnRight = document.createElement('button');
                              btnRight.className = 'btn-rotate btn-rotate-right';
                              btnRight.type = 'button';
                              btnRight.style.cssText = btnStyle;
                              btnRight.innerHTML = '↷';
                              btnRight.title = 'Rotar derecha (horario)';
                              btnRight.addEventListener('click', function(e) {
                                  e.preventDefault();
                                  e.stopPropagation();
                                  const currentSlide = fancybox.getSlide();
                                  if (currentSlide) {
                                      const idx = currentSlide.index;
                                      rotaciones[idx] = (rotaciones[idx] || 0) + 90;
                                      const img = document.querySelector('.fancybox__slide.is-selected img');
                                      if (img) {
                                          img.style.transform = `rotate(${rotaciones[idx]}deg)`;
                                          img.style.transition = 'transform 0.3s ease';
                                      }
                                  }
                              });
                              
                              btnContainer.appendChild(btnLeft);
                              btnContainer.appendChild(btnRight);
                              toolbar.appendChild(btnContainer);
                          }
                      },
                      'Carousel.change': (fancybox, carousel, to, from) => {
                          // Aplicar rotación guardada al cambiar de imagen
                          if (rotaciones[to] !== undefined) {
                              setTimeout(() => {
                                  const img = document.querySelector('.fancybox__slide.is-selected img');
                                  if (img) {
                                      img.style.transform = `rotate(${rotaciones[to]}deg)`;
                                      img.style.transition = 'none';
                                  }
                              }, 50);
                          }
                      }
                  }
              });
          } catch (error) {
              console.error('Error al iniciar Fancybox:', error);
              alert('Error al mostrar las imágenes: ' + error.message);
          }
        } else {
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
      }
    });
}

const validarExistenciaArchivo = (rutaArchivo, callback) => {
    const img = new Image();
    img.onload = function() {
      // La imagen se ha cargado correctamente, por lo que el archivo existe
      callback(true);
    };
    img.onerror = function() {
      // La imagen no se pudo cargar, por lo que el archivo no existe
      callback(false);
    };
    img.src = rutaArchivo;
}

const pasarImagen = (pos) =>{

    let items = document.querySelectorAll(".carousel-item")
    
    for (let index = 0; index < items.length; index++) {
      
      if (items[index].classList.contains("active")) {
  
        if (items[index + pos] !== undefined) {
    
          items[index].classList.remove("active");
        
          items[index + pos].classList.add("active");
          break; // Detener el bucle una vez que se encontró el siguiente elemento activo
  
        }
  
      }
  
    }
  
}

// --- Cambio de entorno (ARG/UY) ---
const cambiarEntorno = (t) => {
    let entorno = 0;
    if(t.getAttribute("data-off") == "ARG" ){
        entorno = 0;
    }else{
        entorno = 1;
    }
    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data : {entorno: entorno},
        success: function (data) {
            location.reload();
        }
    });
}