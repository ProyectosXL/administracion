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
    inicializarSelect2Proveedores();
    
    // NOTA: El listener para cargar solicitudes está en directores.php
    // para permitir filtrado por el director actual (ID de sesión)
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
            // Ya no es necesario llenar el select, solo guardamos en caché
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
 * Obtiene el ID del director actual basándose en el campo oculto de la sesión
 */
function obtenerIdDirectorActual() {
    // Primero intentar obtenerlo del campo oculto directo
    const idDirectorSession = document.getElementById('idDirectorSession');
    if (idDirectorSession && idDirectorSession.value) {
        const idDirector = parseInt(idDirectorSession.value, 10);
        if (!isNaN(idDirector) && idDirector > 0) {
            console.log('ID Director obtenido directamente:', idDirector);
            return idDirector;
        }
    }
    
    // Fallback: buscar por nombre en el cache
    const nombreDirectorSession = document.getElementById('nombreDirectorSession');
    if (!nombreDirectorSession) {
        console.error('No se encontró el campo nombreDirectorSession');
        return null;
    }
    
    const nombreDirector = nombreDirectorSession.value;
    const director = directoresCache.find(d => d.nombre_director === nombreDirector);
    
    if (director) {
        const idDirector = parseInt(director.id_director, 10);
        console.log('ID Director obtenido por nombre:', idDirector);
        return idDirector;
    } else {
        console.error('No se encontró el director con nombre:', nombreDirector);
        return null;
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
            const divProveedor = document.getElementById('divProveedor');
            
            if (this.value === 'COMPRA_PERSONAL') {
                if (divArchivos) divArchivos.classList.remove('d-none');
                if (alertaFactura) alertaFactura.classList.remove('d-none');
                if (divProveedor) divProveedor.classList.remove('d-none');
            } else {
                if (divArchivos) divArchivos.classList.add('d-none');
                if (alertaFactura) alertaFactura.classList.add('d-none');
                if (divProveedor) divProveedor.classList.add('d-none');
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
            console.log('Archivo(s) seleccionado(s) desde galería:', e.target.files.length);
            // Cuando se selecciona desde galería, combinar con los archivos de cámara si existen
            const camaraFiles = inputCamara && inputCamara.files.length > 0 ? inputCamara.files : null;
            combinarArchivos(e.target.files, camaraFiles);
        });
    }
    
    if (inputCamara) {
        inputCamara.addEventListener('change', function(e) {
            console.log('Foto capturada desde cámara');
            // Cuando se toma foto con cámara, combinar con archivos de galería si existen
            const archivoFiles = inputArchivo && inputArchivo.files.length > 0 ? inputArchivo.files : null;
            combinarArchivos(archivoFiles, e.target.files);
        });
    }
}

/**
 * Combina archivos de galería y cámara
 */
function combinarArchivos(archivosGaleria, archivosCamara) {
    const dt = new DataTransfer();
    
    // Primero agregar archivos existentes de galería
    if (archivosGaleria && archivosGaleria.length > 0) {
        console.log('Agregando archivos de galería:', archivosGaleria.length);
        for (let i = 0; i < archivosGaleria.length; i++) {
            dt.items.add(archivosGaleria[i]);
        }
    }
    
    // Luego agregar foto de cámara si existe
    if (archivosCamara && archivosCamara.length > 0) {
        console.log('Agregando foto de cámara:', archivosCamara.length);
        for (let i = 0; i < archivosCamara.length; i++) {
            dt.items.add(archivosCamara[i]);
        }
    }
    
    // Actualizar el input principal con todos los archivos combinados
    const inputArchivo = document.getElementById('archivoSolicitud');
    if (inputArchivo) {
        inputArchivo.files = dt.files;
        console.log('Total de archivos combinados:', dt.files.length);
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
        // Obtener el ID del director actual
        const idDirector = obtenerIdDirectorActual();
        if (!idDirector) {
            throw new Error('No se pudo identificar el director. Por favor, recargue la página.');
        }
        
        const formData = new FormData();
        formData.append('accion', 'crear');
        formData.append('id_director', idDirector);
        formData.append('motivo', document.getElementById('motivoSolicitud').value);
        formData.append('importe', document.getElementById('importeSolicitud').value);
        formData.append('observaciones', document.getElementById('observacionesSolicitud').value || '');
        
        // Validar datos requeridos
        if (!formData.get('motivo')) {
            throw new Error('Debe seleccionar un motivo');
        }
        
        if (!formData.get('importe') || parseFloat(formData.get('importe').replace(/\./g, '').replace(',', '.')) <= 0) {
            throw new Error('Debe ingresar un importe válido');
        }
        
        // Agregar datos de proveedor si es compra personal
        if (formData.get('motivo') === 'COMPRA_PERSONAL') {
            const proveedorSelect = document.getElementById('proveedorSelect');
            const cbuInput = document.getElementById('cbuProveedor');
            const descripcionCbuInput = document.getElementById('descripcionCbuProveedor');
            
            // Validar que se haya seleccionado un proveedor
            if (!proveedorSelect || !proveedorSelect.value) {
                throw new Error('Debe seleccionar un proveedor');
            }
            
            // Validar CBU
            if (!cbuInput || !cbuInput.value) {
                throw new Error('Debe ingresar el CBU del proveedor');
            }
            
            const cbu = cbuInput.value.replace(/\s/g, '');
            if (!validarCBU(cbu)) {
                throw new Error('El CBU debe tener exactamente 22 dígitos');
            }
            
            // Agregar al FormData
            formData.append('nom_provee', proveedorSelect.value);
            formData.append('cbu', cbu);
            
            // Descripción CBU es opcional
            if (descripcionCbuInput && descripcionCbuInput.value.trim()) {
                formData.append('descripcion_cbu', descripcionCbuInput.value.trim());
            }
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
            
            // Agregar cada archivo al FormData con el nombre 'archivos[]'
            // Esto es importante: el servidor espera $_FILES['archivos'] como array
            for (let i = 0; i < inputArchivos.files.length; i++) {
                formData.append('archivos[]', inputArchivos.files[i], inputArchivos.files[i].name);
                console.log(`Archivo ${i + 1}: ${inputArchivos.files[i].name} (${inputArchivos.files[i].size} bytes)`);
            }
            
            // Debug: verificar que se agregaron al FormData
            const archivosEnFormData = formData.getAll('archivos[]');
            console.log('FormData keys:', Array.from(formData.keys()));
            console.log('Archivos en FormData:', archivosEnFormData.length);
            
            // Verificar cada archivo
            archivosEnFormData.forEach((archivo, index) => {
                console.log(`FormData archivo ${index}:`, archivo.name, archivo.size, 'bytes');
            });
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
            
            // Limpiar Select2 de proveedores
            const proveedorSelect = $('#proveedorSelect');
            if (proveedorSelect.length > 0) {
                proveedorSelect.val(null).trigger('change');
            }
            
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
            
            // Ocultar secciones de archivos y proveedores
            const divArchivos = document.getElementById('divArchivos');
            const alertaFactura = document.getElementById('alertaFactura');
            const divProveedor = document.getElementById('divProveedor');
            const alertaCBU = document.getElementById('alertaCBU');
            
            if (divArchivos) divArchivos.classList.add('d-none');
            if (alertaFactura) alertaFactura.classList.add('d-none');
            if (divProveedor) divProveedor.classList.add('d-none');
            if (alertaCBU) alertaCBU.classList.add('d-none');
            
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

/**
 * Inicializa el Select2 para búsqueda de proveedores
 */
function inicializarSelect2Proveedores() {
    const proveedorSelect = $('#proveedorSelect');
    
    if (proveedorSelect.length === 0) {
        console.warn('No se encontró el select de proveedores');
        return;
    }
    
    proveedorSelect.select2({
        theme: 'bootstrap-5',
        placeholder: 'Escriba para buscar proveedor...',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "No se encontraron proveedores";
            },
            searching: function() {
                return "Buscando proveedores...";
            },
            inputTooShort: function() {
                return "Escriba al menos 2 caracteres para buscar";
            },
            loadingMore: function() {
                return "Cargando más resultados...";
            },
            errorLoading: function() {
                return "No se pudieron cargar los resultados";
            }
        },
        minimumInputLength: 2, // Buscar después de 2 caracteres
        ajax: {
            url: 'controller/proveedor_controller.php',
            dataType: 'json',
            delay: 300, // Esperar 300ms después de que el usuario deja de escribir
            data: function(params) {
                return {
                    accion: 'buscar',
                    q: params.term || '', // término de búsqueda
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                
                console.log('========== DATOS DEL SERVIDOR ==========');
                console.log('Data recibida:', data);
                console.log('Cantidad de resultados:', data.results ? data.results.length : 0);
                
                if (data.results && data.results.length > 0) {
                    console.log('Primer resultado:', data.results[0]);
                    console.log('CBU del primer resultado:', data.results[0].cbu);
                    console.log('Descripción CBU del primer resultado:', data.results[0].descripcion_cbu);
                    console.log('Tiene CBU:', data.results[0].tiene_cbu);
                }
                console.log('========================================');
                
                // Verificar si hay resultados
                if (!data.results || data.results.length === 0) {
                    return {
                        results: [{
                            id: 'MANUAL',
                            text: '🔧 No encontrado - Cargar a mano',
                            nombre: '',
                            cuit: '',
                            cbu: '',
                            descripcion_cbu: '',
                            tiene_cbu: false,
                            es_manual: true
                        }]
                    };
                }
                
                return data;
            },
            cache: true
        },
        templateResult: formatProveedor, // Formato personalizado en resultados
        templateSelection: formatProveedorSeleccion // Formato cuando está seleccionado
    });
    
    // Evento cuando se selecciona un proveedor
    proveedorSelect.on('select2:select', function(e) {
        const data = e.params.data;
        console.log('========== PROVEEDOR SELECCIONADO ==========');
        console.log('Data completa:', data);
        console.log('ID:', data.id);
        console.log('Nombre:', data.nombre);
        console.log('CUIT:', data.cuit);
        console.log('CBU:', data.cbu);
        console.log('Descripción CBU:', data.descripcion_cbu);
        console.log('Tiene CBU:', data.tiene_cbu);
        console.log('Es manual:', data.es_manual);
        console.log('===========================================');
        
        const cbuInput = document.getElementById('cbuProveedor');
        const descripcionCbuInput = document.getElementById('descripcionCbuProveedor');
        const alertaCBU = document.getElementById('alertaCBU');
        
        // Caso 1: Opción manual
        if (data.es_manual) {
            console.log('✅ Caso 1: Opción manual seleccionada');
            if (cbuInput) cbuInput.value = '';
            if (descripcionCbuInput) descripcionCbuInput.value = '';
            if (alertaCBU) alertaCBU.classList.add('d-none');
            return;
        }
        
        // Caso 2: Proveedor con CBU (descripción es opcional)
        if (data.tiene_cbu && data.cbu) {
            console.log('✅ Caso 2: Proveedor con CBU');
            console.log('Asignando CBU:', data.cbu);
            console.log('Asignando Descripción:', data.descripcion_cbu || '(vacío)');
            
            if (cbuInput) {
                cbuInput.value = data.cbu;
                console.log('CBU asignado al input:', cbuInput.value);
            } else {
                console.error('❌ Input cbuProveedor no encontrado');
            }
            
            if (descripcionCbuInput) {
                // Asignar descripción si existe, sino dejar vacío
                descripcionCbuInput.value = data.descripcion_cbu || '';
                console.log('Descripción asignada al input:', descripcionCbuInput.value);
            } else {
                console.error('❌ Input descripcionCbuProveedor no encontrado');
            }
            
            // Si tiene CBU, no mostrar alerta (aunque no tenga descripción)
            if (alertaCBU) alertaCBU.classList.add('d-none');
            
            // Si no tiene descripción, mostrar un mensaje informativo
            if (!data.descripcion_cbu || data.descripcion_cbu.trim() === '') {
                console.log('ℹ️ Proveedor tiene CBU pero sin descripción. Usuario debe completar.');
            }
        } 
        // Caso 3: Proveedor sin CBU
        else {
            console.log('⚠️ Caso 3: Proveedor sin CBU');
            console.log('tiene_cbu:', data.tiene_cbu);
            console.log('cbu:', data.cbu);
            console.log('descripcion_cbu:', data.descripcion_cbu);
            
            if (cbuInput) cbuInput.value = '';
            if (descripcionCbuInput) descripcionCbuInput.value = '';
            if (alertaCBU) alertaCBU.classList.remove('d-none');
        }
    });
    
    // Evento cuando se limpia la selección
    proveedorSelect.on('select2:clear', function() {
        console.log('Selección limpiada');
        const cbuInput = document.getElementById('cbuProveedor');
        const descripcionCbuInput = document.getElementById('descripcionCbuProveedor');
        const alertaCBU = document.getElementById('alertaCBU');
        
        if (cbuInput) cbuInput.value = '';
        if (descripcionCbuInput) descripcionCbuInput.value = '';
        if (alertaCBU) alertaCBU.classList.add('d-none');
    });
}

/**
 * Formatea cómo se muestra cada proveedor en la lista desplegable
 */
function formatProveedor(proveedor) {
    if (proveedor.loading) {
        return proveedor.text;
    }
    
    // Si es la opción manual
    if (proveedor.es_manual) {
        return $('<span><i class="bi bi-tools"></i> ' + proveedor.text + '</span>');
    }
    
    // Formato normal: Nombre (CUIT)
    var $proveedor = $(
        '<div class="d-flex flex-column">' +
            '<div class="fw-bold">' + (proveedor.nombre || proveedor.text) + '</div>' +
            '<div class="small text-muted">' +
                '<i class="bi bi-card-text"></i> CUIT: ' + (proveedor.cuit || 'N/A') +
                (proveedor.tiene_cbu ? ' <span class="badge bg-success badge-sm ms-1"><i class="bi bi-check-circle"></i> Tiene CBU</span>' : ' <span class="badge bg-warning text-dark badge-sm ms-1"><i class="bi bi-exclamation-triangle"></i> Sin CBU</span>') +
            '</div>' +
        '</div>'
    );
    
    return $proveedor;
}

/**
 * Formatea cómo se muestra el proveedor seleccionado
 */
function formatProveedorSeleccion(proveedor) {
    if (!proveedor.id) {
        return proveedor.text;
    }
    
    // Si es manual
    if (proveedor.es_manual) {
        return '🔧 ' + proveedor.text;
    }
    
    // Si tiene nombre y CUIT, mostrar ambos
    if (proveedor.nombre && proveedor.cuit) {
        return proveedor.nombre + ' (' + proveedor.cuit + ')';
    }
    
    return proveedor.text;
}

/**
 * Valida el CBU (22 dígitos)
 */
function validarCBU(cbu) {
    // Eliminar espacios
    cbu = cbu.replace(/\s/g, '');
    
    // Verificar que tenga exactamente 22 dígitos
    return /^\d{22}$/.test(cbu);
}

// Exportar funciones para uso global
window.cargarDirectores = cargarDirectores;
window.crearSolicitud = crearSolicitud;
window.obtenerNombreDirector = obtenerNombreDirector;
window.obtenerIdDirectorActual = obtenerIdDirectorActual;
window.eliminarArchivo = eliminarArchivo;
window.previsualizarImagen = previsualizarImagen;
window.inicializarSelect2Proveedores = inicializarSelect2Proveedores;
window.validarCBU = validarCBU;
window.formatProveedor = formatProveedor;
window.formatProveedorSeleccion = formatProveedorSeleccion;