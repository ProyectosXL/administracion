/**
 * Script para manejar solicitudes de directores
 * Versión corregida que usa id_director
 */

let directoresCache = [];

/**
 * Inicialización al cargar la página
 */
document.addEventListener('DOMContentLoaded', function() {
    cargarDirectores();
    configurarFormulario();
    configurarFiltros();
    
    // Cargar solicitudes si estamos en la pestaña de listado
    const tabListado = document.getElementById('listado-tab');
    if (tabListado) {
        tabListado.addEventListener('shown.bs.tab', function() {
            cargarSolicitudes();
        });
    }
});

/**
 * Carga la lista de directores desde el servidor
 */
async function cargarDirectores() {
    mostrarLoading();
    
    try {
        const response = await fetch('controller/solicitud_controller.php?accion=obtener_directores');
        const result = await response.json();
        
        if (result.success) {
            directoresCache = result.data;
            llenarSelectDirectores();
        } else {
            console.error('Error al cargar directores:', result.message);
            mostrarAlerta('Error', 'No se pudieron cargar los directores');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'Error de conexión al cargar directores');
    } finally {
        ocultarLoading();
    }
}

/**
 * Llena los selects de directores
 */
function llenarSelectDirectores() {
    const selectSolicitud = document.getElementById('idDirector');
    const selectFiltro = document.getElementById('filtroDirector');
    
    // Limpiar opciones existentes
    if (selectSolicitud) {
        selectSolicitud.innerHTML = '<option value="">Seleccione un director</option>';
        directoresCache.forEach(director => {
            const option = document.createElement('option');
            option.value = director.id_director;
            option.textContent = director.nombre_director;
            selectSolicitud.appendChild(option);
        });
    }
    
    // Llenar filtro
    if (selectFiltro) {
        selectFiltro.innerHTML = '<option value="">Todos</option>';
        directoresCache.forEach(director => {
            const option = document.createElement('option');
            option.value = director.id_director;
            option.textContent = director.nombre_director;
            selectFiltro.appendChild(option);
        });
    }
}

/**
 * Configura el formulario de nueva solicitud
 */
function configurarFormulario() {
    const form = document.getElementById('formSolicitud');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        await crearSolicitud();
    });
    
    // Configurar input de importe con formateo en tiempo real
    const importeInput = document.getElementById('importeSolicitud');
    if (importeInput) {
        // Usar keyup en lugar de input para evitar conflictos
        importeInput.addEventListener('keyup', function(e) {
            // Solo formatear si no estamos borrando
            if (e.key !== 'Backspace' && e.key !== 'Delete') {
                formatearImporte(e);
            }
        });
        
        // También en blur para formatear cuando se sale del campo
        importeInput.addEventListener('blur', formatearImporte);
    }
    
    // Mostrar/ocultar sección de archivos según motivo
    const motivoSelect = document.getElementById('motivoSolicitud');
    if (motivoSelect) {
        motivoSelect.addEventListener('change', function() {
            const divArchivos = document.getElementById('divArchivos');
            const alertaFactura = document.getElementById('alertaFactura');
            
            if (this.value === 'COMPRA_PERSONAL') {
                if (divArchivos) divArchivos.classList.remove('d-none');
                if (alertaFactura) alertaFactura.classList.remove('d-none');
            } else {
                if (divArchivos) divArchivos.classList.add('d-none');
                if (alertaFactura) alertaFactura.classList.add('d-none');
            }
        });
    }
    
    // Configurar manejo de archivos
    configurarArchivos();
}

/**
 * Formatea el input de importe con separadores de miles
 */
function formatearImporte(e) {
    const input = e.target;
    let valor = input.value;
    
    // Eliminar todo excepto números
    let numeroLimpio = valor.replace(/\D/g, '');
    
    // Si está vacío, no hacer nada
    if (numeroLimpio === '') {
        return;
    }
    
    // Formatear con puntos cada 3 dígitos
    let valorFormateado = numeroLimpio.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    
    // Solo actualizar si cambió
    if (input.value !== valorFormateado) {
        input.value = valorFormateado;
    }
}

/**
 * Agrega separadores de miles a un número
 */
function formatearNumero(numero) {
    return numero.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/**
 * Configura el manejo de archivos
 */
function configurarArchivos() {
    const inputArchivo = document.getElementById('archivoSolicitud');
    const inputCamara = document.getElementById('camaraSolicitud');
    
    if (inputArchivo) {
        inputArchivo.addEventListener('change', function(e) {
            const camaraFiles = inputCamara ? inputCamara.files : new FileList();
            combinarArchivos(e.target.files, camaraFiles);
        });
    }
    
    if (inputCamara) {
        inputCamara.addEventListener('change', function(e) {
            const archivoFiles = inputArchivo ? inputArchivo.files : new FileList();
            combinarArchivos(archivoFiles, e.target.files);
        });
    }
}

/**
 * Combina archivos de galería y cámara
 */
function combinarArchivos(archivosGaleria, archivosCamara) {
    const dt = new DataTransfer();
    
    // Agregar archivos de galería
    if (archivosGaleria && archivosGaleria.length > 0) {
        for (let i = 0; i < archivosGaleria.length; i++) {
            dt.items.add(archivosGaleria[i]);
        }
    }
    
    // Agregar archivos de cámara
    if (archivosCamara && archivosCamara.length > 0) {
        for (let i = 0; i < archivosCamara.length; i++) {
            dt.items.add(archivosCamara[i]);
        }
    }
    
    // Actualizar el input principal con todos los archivos
    const inputArchivo = document.getElementById('archivoSolicitud');
    if (inputArchivo) {
        inputArchivo.files = dt.files;
        console.log('Archivos combinados:', dt.files.length, 'archivo(s)');
    }
    
    mostrarArchivosSeleccionados(dt.files);
}

/**
 * Muestra los archivos seleccionados
 */
function mostrarArchivosSeleccionados(archivos) {
    const listaArchivos = document.getElementById('listaArchivos');
    if (!listaArchivos) return;
    
    if (archivos.length === 0) {
        listaArchivos.innerHTML = '<div class="alert alert-light border"><small><i class="bi bi-info-circle me-1"></i> No hay archivos adjuntos</small></div>';
        return;
    }
    
    let html = '<div class="alert alert-success mb-3">';
    html += '<i class="bi bi-check-circle me-2"></i>';
    html += '<strong>' + archivos.length + '</strong> archivo(s) adjunto(s)';
    html += '</div>';
    
    html += '<div class="list-group mb-3">';
    
    for (let i = 0; i < archivos.length; i++) {
        const archivo = archivos[i];
        const esImagen = archivo.type.startsWith('image/');
        const icono = esImagen ? 'bi-image-fill' : 'bi-file-earmark-pdf-fill';
        const tamaño = (archivo.size / 1024).toFixed(1);
        const unidad = tamaño < 1024 ? 'KB' : 'MB';
        const tamañoFormateado = tamaño < 1024 ? tamaño : (tamaño / 1024).toFixed(2);
        
        html += `
            <div class="list-group-item">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="bi ${icono} fs-3 text-primary"></i>
                    </div>
                    <div class="col">
                        <div class="fw-semibold text-break">${archivo.name}</div>
                        <small class="text-muted">
                            <i class="bi bi-hdd"></i> ${tamañoFormateado} ${unidad}
                        </small>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group" role="group">
                            ${esImagen ? `
                            <button type="button" class="btn btn-sm btn-outline-info" 
                                    onclick="previsualizarImagen(${i})" 
                                    title="Ver vista previa">
                                <i class="bi bi-eye-fill"></i>
                                <span class="d-none d-sm-inline ms-1">Ver</span>
                            </button>
                            ` : ''}
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="eliminarArchivo(${i})" 
                                    title="Eliminar archivo">
                                <i class="bi bi-trash-fill"></i>
                                <span class="d-none d-sm-inline ms-1">Eliminar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    listaArchivos.innerHTML = html;
}

/**
 * Elimina un archivo de la selección
 */
function eliminarArchivo(indice) {
    const inputArchivo = document.getElementById('archivoSolicitud');
    if (!inputArchivo) return;
    
    // Crear nuevo FileList sin el archivo eliminado
    const dt = new DataTransfer();
    const archivos = inputArchivo.files;
    
    for (let i = 0; i < archivos.length; i++) {
        if (i !== indice) {
            dt.items.add(archivos[i]);
        }
    }
    
    inputArchivo.files = dt.files;
    console.log('Archivo eliminado. Archivos restantes:', inputArchivo.files.length);
    mostrarArchivosSeleccionados(inputArchivo.files);
}

/**
 * Previsualiza una imagen en un modal
 */
function previsualizarImagen(indice) {
    const inputArchivo = document.getElementById('archivoSolicitud');
    if (!inputArchivo || !inputArchivo.files[indice]) return;
    
    const archivo = inputArchivo.files[indice];
    if (!archivo.type.startsWith('image/')) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        // Crear modal para previsualización
        const modalHtml = `
            <div class="modal fade" id="modalPreview" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Vista Previa: ${archivo.name}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${e.target.result}" class="img-fluid" alt="Vista previa">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Eliminar modal anterior si existe
        const modalAnterior = document.getElementById('modalPreview');
        if (modalAnterior) modalAnterior.remove();
        
        // Agregar y mostrar nuevo modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('modalPreview'));
        modal.show();
        
        // Limpiar modal al cerrar
        document.getElementById('modalPreview').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    };
    
    reader.readAsDataURL(archivo);
}

/**
 * Crea una nueva solicitud
 */
async function crearSolicitud() {
    mostrarLoading();
    
    try {
        const formData = new FormData();
        formData.append('accion', 'crear');
        formData.append('id_director', document.getElementById('idDirector').value);
        formData.append('motivo', document.getElementById('motivoSolicitud').value);
        formData.append('importe', document.getElementById('importeSolicitud').value);
        formData.append('observaciones', document.getElementById('observacionesSolicitud').value || '');
        
        // Validar datos requeridos
        if (!formData.get('id_director')) {
            throw new Error('Debe seleccionar un director');
        }
        
        if (!formData.get('motivo')) {
            throw new Error('Debe seleccionar un motivo');
        }
        
        if (!formData.get('importe') || parseFloat(formData.get('importe').replace(/\./g, '').replace(',', '.')) <= 0) {
            throw new Error('Debe ingresar un importe válido');
        }
        
        // Agregar archivos si es compra personal
        if (formData.get('motivo') === 'COMPRA_PERSONAL') {
            const inputArchivos = document.getElementById('archivoSolicitud');
            
            console.log('Validando archivos para COMPRA_PERSONAL...');
            console.log('Input encontrado:', !!inputArchivos);
            console.log('Cantidad de archivos:', inputArchivos ? inputArchivos.files.length : 0);
            
            if (!inputArchivos || inputArchivos.files.length === 0) {
                throw new Error('Las compras personales requieren adjuntar la factura');
            }
            
            console.log('Adjuntando', inputArchivos.files.length, 'archivo(s) al FormData...');
            
            // Agregar cada archivo al FormData (sin [] para que PHP lo reciba correctamente)
            for (let i = 0; i < inputArchivos.files.length; i++) {
                formData.append('archivos[]', inputArchivos.files[i]);
                console.log(`Archivo ${i + 1}: ${inputArchivos.files[i].name} (${inputArchivos.files[i].size} bytes)`);
            }
            
            // Debug: verificar que se agregaron al FormData
            console.log('FormData keys:', Array.from(formData.keys()));
            console.log('Archivos en FormData:', formData.getAll('archivos[]').length)
        }
        
        const response = await fetch('controller/solicitud_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            let mensaje = `Solicitud creada correctamente. ID: ${result.id_solicitud}`;
            if (result.archivos && result.archivos.length > 0) {
                mensaje += `\nArchivos adjuntos: ${result.archivos.length}`;
            }
            
            mostrarAlerta('Éxito', mensaje);
            
            // Limpiar formulario
            document.getElementById('formSolicitud').reset();
            
            // Limpiar inputs de archivos manualmente
            const inputArchivo = document.getElementById('archivoSolicitud');
            const inputCamara = document.getElementById('camaraSolicitud');
            if (inputArchivo) inputArchivo.value = '';
            if (inputCamara) inputCamara.value = '';
            
            // Resetear visualización de archivos
            const listaArchivos = document.getElementById('listaArchivos');
            if (listaArchivos) {
                listaArchivos.innerHTML = '<div class="alert alert-light border"><small><i class="bi bi-info-circle me-1"></i> No hay archivos adjuntos</small></div>';
            }
            
            // Ocultar secciones de archivos
            const divArchivos = document.getElementById('divArchivos');
            const alertaFactura = document.getElementById('alertaFactura');
            if (divArchivos) divArchivos.classList.add('d-none');
            if (alertaFactura) alertaFactura.classList.add('d-none');
            
            // Cambiar a la pestaña de listado para ver la solicitud
            const tabListado = document.getElementById('listado-tab');
            if (tabListado) {
                tabListado.click();
            }
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', error.message);
    } finally {
        ocultarLoading();
    }
}

/**
 * Configura los filtros
 */
function configurarFiltros() {
    const btnAplicar = document.getElementById('btnAplicarFiltros');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    
    if (btnAplicar) {
        btnAplicar.addEventListener('click', () => {
            if (typeof aplicarFiltros === 'function') {
                aplicarFiltros();
            }
        });
    }
    
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            if (typeof limpiarFiltros === 'function') {
                limpiarFiltros();
            }
        });
    }
}

/**
 * Obtiene el nombre del director por ID
 */
function obtenerNombreDirector(idDirector) {
    const director = directoresCache.find(d => d.id_director == idDirector);
    return director ? director.nombre_director : 'Director no encontrado';
}

// Exportar funciones para uso global
window.cargarDirectores = cargarDirectores;
window.crearSolicitud = crearSolicitud;
window.obtenerNombreDirector = obtenerNombreDirector;
window.eliminarArchivo = eliminarArchivo;
window.previsualizarImagen = previsualizarImagen;