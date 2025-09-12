$(document).ready(function() {
    // Cargar datos iniciales
    cargarMovimientos();
    
    // Event listener para el botón filtrar
    $('#filtrar').on('click', function(e) {
        e.preventDefault();
        cargarMovimientos();
    });
});

function cargarMovimientos() {
    const desde = $('#desde').val();
    const hasta = $('#hasta').val();
    
    if (!desde || !hasta) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Por favor seleccione ambas fechas'
        });
        return;
    }
    
    if (desde > hasta) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'La fecha desde no puede ser mayor a la fecha hasta'
        });
        return;
    }
    
    // Mostrar loading
    mostrarLoading(true);
    
    $.ajax({
        url: 'Controller/saldoController.php?accion=obtenerMovimientos',
        type: 'GET',
        data: { desde: desde, hasta: hasta },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta del servidor:', response); // Debug
            if (response.success) {
                actualizarEstadisticas(response.estadisticas, response.saldoActualDisplay);
                renderizarTabla(response.movimientos);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.error || 'Error al cargar los movimientos'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            console.error('Respuesta completa:', xhr.responseText); // Debug
            Swal.fire({
                icon: 'error',
                title: 'Error de comunicación',
                text: 'No se pudo conectar con el servidor'
            });
        },
        complete: function() {
            mostrarLoading(false);
        }
    });
}

function actualizarEstadisticas(stats, saldoActual) {
    console.log('Actualizando estadísticas:', stats, saldoActual); // Debug
    
    // Actualizar saldo actual
    $('#saldo-actual').text('$' + (saldoActual || '0'));
    
    // Actualizar estadísticas - agregar verificación de existencia
    if (stats) {
        $('#total-ingresos').text('$' + (stats.totalIngresos || '0'));
        $('#cant-ingresos').text((stats.cantIngresos || 0) + ' movimientos');
        
        $('#total-egresos').text('$' + (stats.totalEgresos || '0'));
        $('#cant-egresos').text((stats.cantEgresos || 0) + ' movimientos');
        
        $('#total-movimientos').text(stats.totalMovimientos || 0);
        $('#periodo-movimientos').text('movimientos en el período');
    } else {
        console.error('Stats es null o undefined');
        // Valores por defecto si no hay estadísticas
        $('#total-ingresos').text('$0');
        $('#cant-ingresos').text('0 movimientos');
        $('#total-egresos').text('$0');
        $('#cant-egresos').text('0 movimientos');
        $('#total-movimientos').text('0');
    }
}

function renderizarTabla(movimientos) {
    console.log('Renderizando tabla con', movimientos.length, 'movimientos'); // Debug
    
    // Destruir DataTable existente si existe
    if ($.fn.DataTable.isDataTable('#movimientosTable')) {
        $('#movimientosTable').DataTable().destroy();
    }
    
    // Limpiar tbody
    const tbody = $('#movimientosTable tbody');
    tbody.empty();
    
    // Agregar filas
    movimientos.forEach(function(mov) {
        let tipoClass = '';
        let montoClass = '';
        let iconoFoto = '';
        
        // Determinar clases CSS según el tipo
        if (mov.TIPO === 'INGRESO') {
            tipoClass = 'tipo-ingreso';
            montoClass = 'monto-positivo';
        } else if (mov.TIPO === 'EGRESO') {
            tipoClass = 'tipo-egreso';
            montoClass = 'monto-negativo';
            // Solo mostrar botón de foto para egresos que no sean saldo inicial
            if (mov.COD_COMP !== 'SALDO_INI' && mov.N_COMP) {
                if (mov.ESTA_GUARDADO && mov.TIENE_FOTOS) {
                    // 🟡👁️ Botón amarillo con ojo para egresos CON fotos
                    iconoFoto = `<button class="btn btn-foto-disponible btn-sm" onclick="mostrarFotosEgreso('${mov.COD_COMP}', '${mov.N_COMP}')" data-bs-toggle="tooltip" title="Ver fotos disponibles">
                        <i class="fas fa-eye"></i>
                    </button>`;
                } else if (mov.ESTA_GUARDADO && !mov.TIENE_FOTOS) {
                    // ⚫📷 Botón gris con cámara para egresos SIN fotos
                    iconoFoto = `<button class="btn btn-foto-no-disponible btn-sm" disabled data-bs-toggle="tooltip" title="Sin fotos disponibles">
                        <i class="fas fa-camera"></i>
                    </button>`;
                } else {
                    // Para egresos no guardados, no mostrar botón
                    iconoFoto = '';
                }
            }
        } else {
            tipoClass = 'tipo-saldo';
            montoClass = 'monto-neutral';
        }
        
        const fila = `
            <tr>
                <td>${mov.FECHA_DISPLAY}</td>
                <td>${mov.COD_COMP || ''}</td>
                <td>${mov.N_COMP || ''}</td>
                <td><span class="${tipoClass}">${mov.TIPO || ''}</span></td>
                <td>${mov.LEYENDA || ''}</td>
                <td class="text-end"><span class="${montoClass}">$${mov.DEBE_DISPLAY}</span></td>
                <td class="text-end"><span class="${montoClass}">$${mov.HABER_DISPLAY}</span></td>
                <td class="text-end"><strong>$${mov.SALDO_DISPLAY}</strong></td>
                <td class="text-center">${iconoFoto}</td>
            </tr>
        `;
        
        tbody.append(fila);
    });
    
    // Inicializar DataTable
    $('#movimientosTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        responsive: true,
        order: [[0, 'asc']], // Ordenar por fecha ASCENDENTE (primera fecha arriba)
        pageLength: 50,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "Todos"]],
        columnDefs: [
            {
                targets: [0], // Columna de fecha
                type: 'date'
            },
            {
                targets: [5, 6, 7], // Columnas de montos
                className: 'text-end'
            },
            {
                targets: [8], // Columna de acciones
                orderable: false,
                className: 'text-center'
            }
        ],
        drawCallback: function() {
            // La información de fotos ya viene desde el servidor, no necesitamos verificaciones adicionales
            
            // Inicializar tooltips de Bootstrap
            setTimeout(function() {
                // Destruir tooltips existentes
                $('[data-bs-toggle="tooltip"]').tooltip('dispose');
                
                // Inicializar nuevos tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }, 100);
        }
    });
}

function verificarEstadosEgresos() {
    // Esta función ya no es necesaria porque la información de fotos 
    // viene directamente desde el servidor en la respuesta de obtenerMovimientos
    console.log('Los estados de egresos ya vienen desde el servidor');
}

function verificarEstadoEgreso(codComp, nComp, fila) {
    $.ajax({
        url: 'Controller/saldoController.php?accion=verificarEstadoEgreso',
        type: 'GET',
        data: { codComp: codComp, nComp: nComp },
        dataType: 'json',
        success: function(response) {
            const botonFoto = fila.find('.btn-foto');
            if (response.success && response.tieneFotos) {
                botonFoto.prop('disabled', false);
                botonFoto.removeClass('btn-secondary').addClass('btn-foto');
                botonFoto.attr('title', `Ver ${response.numeroFotos} foto(s)`);
            } else {
                botonFoto.prop('disabled', true);
                botonFoto.removeClass('btn-foto').addClass('btn-secondary');
                botonFoto.attr('title', 'Sin fotos');
            }
        },
        error: function() {
            const botonFoto = fila.find('.btn-foto');
            botonFoto.prop('disabled', true);
            botonFoto.removeClass('btn-foto').addClass('btn-secondary');
        }
    });
}

function mostrarFotosEgreso(codComp, nComp) {
    $.ajax({
        url: 'Controller/saldoController.php?accion=obtenerFotosEgreso',
        type: 'GET',
        data: { codComp: codComp, nComp: nComp },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.archivos && response.archivos.length > 0) {
                crearCarruselFotos(response.archivos, codComp, nComp);
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin archivos',
                    text: 'No hay archivos para mostrar.'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al obtener fotos:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al obtener los archivos.'
            });
        }
    });
}

function crearCarruselFotos(archivos, codComp, nComp) {
    const totalArchivos = archivos.length;
    const isMobile = window.innerWidth <= 768;
    const titulo = isMobile ? `${codComp}-${nComp}` : `Archivos de ${codComp}-${nComp}`;
    
    let carruselHTML = `
        <div id="carrusel-${codComp}-${nComp}" class="carousel slide carousel-responsive" data-bs-ride="carousel">
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
            if (isMobile) {
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

        carruselHTML += `
            <div class="carousel-item ${index === 0 ? 'active' : ''}">
                <div class="w-100 d-flex flex-column align-items-center justify-content-center">
                    ${contenido}
                </div>
                <div class="image-counter">${index + 1}/${totalArchivos}</div>
            </div>
        `;
    });

    // Solo mostrar controles si hay más de 1 archivo
    const controles = totalArchivos > 1 ? `
        <button class="carousel-control-prev" type="button" data-bs-target="#carrusel-${codComp}-${nComp}" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carrusel-${codComp}-${nComp}" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
        </button>
    ` : '';

    carruselHTML += `
            </div>
            ${controles}
        </div>
    `;

    // Configuración del modal
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
            const carousel = modal.querySelector(`#carrusel-${codComp}-${nComp}`);
            if (carousel && window.bootstrap) {
                new bootstrap.Carousel(carousel, {
                    interval: false,
                    wrap: true,
                    touch: true
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
}

function mostrarLoading(mostrar) {
    if (mostrar) {
        Swal.fire({
            title: 'Cargando movimientos...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
    } else {
        Swal.close();
    }
}

// Funciones de utilidad
function formatearMonto(monto) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(monto);
}

function formatearFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-AR');
}