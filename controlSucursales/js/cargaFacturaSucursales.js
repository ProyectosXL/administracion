/* ===================================
   CARGA FACTURA SUCURSALES - JAVASCRIPT
   =================================== */

let sortColumn = null;
let sortDirection = 'asc';

/**
 * Marca un gasto como contabilizado. Una vez contabilizado no se puede destildar:
 * el checkbox se reemplaza por un ícono.
 */
const checkContabilizar = (input) => {
    if (!input.checked) return;

    Swal.fire({
        icon: 'question',
        title: '¿Marcar como contabilizada?',
        text: 'Una vez contabilizada no se podrá destildar.',
        showCancelButton: true,
        confirmButtonText: 'Sí, contabilizar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) {
            input.checked = false;
            return;
        }
        guardarContabilizada(input);
    });
}

const guardarContabilizada = (input) => {
    const tr = input.closest('tr');
    const d = tr.dataset;

    input.disabled = true;

    $.ajax({
        type: "POST",
        url: "Controller/ControlEgresosController.php?accion=checkContabilizar",
        data: {
            fecha: d.fecha,
            nro_sucursal: d.sucursal,
            tipoComprobante: d.tipo,
            nroComprobante: d.comprobante,
            codCuenta: d.codCuenta,
            monto: d.monto
        }
    }).done(function (response) {
        // El controlador responde "1" si el UPDATE se ejecutó (y vacío si falló)
        if (!/1\s*$/.test(String(response))) {
            revertirCheck(input);
            return;
        }
        $(input).closest('td').attr('data-sort', 1);
        $(input).replaceWith('<i class="bi bi-check-circle-fill icon-ok icono-contabilizada" title="Contabilizada"></i>');
        tr.classList.add('row-contabilizada');
        $('#statContabilizadas').text($('#tablaFacturas .icono-contabilizada').length);
    }).fail(function () {
        revertirCheck(input);
    });
}

const revertirCheck = (input) => {
    input.checked = false;
    input.disabled = false;
    Swal.fire({
        icon: 'error',
        title: 'No se pudo guardar',
        text: 'Ocurrió un error al actualizar el comprobante. Intentá nuevamente.'
    });
}

const mostrarImagen = (divImagen, startIndex = 0) => {
    let codigosImagenes = [];
    const d = divImagen.closest('tr').dataset;
    let nComp = d.comprobante.trim();
    let nroSucursal = d.sucursal.trim();
    let codComp = d.tipo.trim();
    let codCta = d.codCuenta.trim();
    let fechaComprobante = d.fecha;

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

/* ===================================
   INICIALIZACIÓN
   =================================== */

$(document).ready(function () {
    $("#selectSucursal").select2({ width: '240px' });

    $('#formFiltros').on('submit', function() {
        $("#boxLoading").addClass("loading");
    });

    if ($('#tablaFacturas').length === 0) {
        return;
    }

    $('#tablaFacturas [title]').tooltip({ container: 'body' });

    setupSearch();
    setupSorting();
});

/**
 * Buscador sobre las filas de la tabla
 */
function setupSearch() {
    $('#searchInput').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();

        $('#tablaFacturas tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(searchTerm) !== -1);
        });
    });
}

/**
 * Ordenamiento al hacer click en los encabezados
 */
function setupSorting() {
    $('#tablaFacturas thead th').each(function(index) {
        if ($(this).hasClass('no-sort')) {
            return;
        }
        $(this).on('click', function() {
            sortTable(index);
        });
    });
}

function valorOrden(td) {
    const $td = $(td);
    return $td.attr('data-sort') !== undefined ? $td.attr('data-sort') : $td.text().trim();
}

function sortTable(columnIndex) {
    const tbody = $('#tablaFacturas tbody');
    const rows = tbody.find('tr').toArray();

    if (sortColumn === columnIndex) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn = columnIndex;
        sortDirection = 'asc';
    }

    rows.sort(function(a, b) {
        const aVal = valorOrden($(a).find('td')[columnIndex]);
        const bVal = valorOrden($(b).find('td')[columnIndex]);

        const aNum = Number(aVal);
        const bNum = Number(bVal);

        let resultado;
        if (aVal !== '' && bVal !== '' && !isNaN(aNum) && !isNaN(bNum)) {
            resultado = aNum - bNum;
        } else {
            resultado = aVal.localeCompare(bVal, 'es', { numeric: true });
        }

        return sortDirection === 'asc' ? resultado : -resultado;
    });

    $('#tablaFacturas thead th').removeClass('sorting_asc sorting_desc');
    $('#tablaFacturas thead th').eq(columnIndex).addClass(sortDirection === 'asc' ? 'sorting_asc' : 'sorting_desc');

    tbody.append(rows);
}

/**
 * Cambia el entorno (Argentina/Uruguay)
 */
function cambiarEntornoCustom(container) {
    const activeFlag = $(container).find('.toggle-flag.active');
    const nuevoEntorno = (activeFlag.data('entorno') === 'central') ? 1 : 0;

    $("#boxLoading").addClass("loading");

    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data: { entorno: nuevoEntorno },
        success: function () {
            location.reload();
        },
        error: function() {
            $("#boxLoading").removeClass("loading");
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo cambiar el entorno. Por favor intente nuevamente.'
            });
        }
    });
}
