/**
 * Script para el formulario de Pago de Servicios
 * Gestiona el registro de pagos de seguros, patentes y expensas
 */

let directoresCache = [];
let archivoSeleccionado = null;

/**
 * Inicialización al cargar la página
 */
document.addEventListener('DOMContentLoaded', function() {
    inicializarFormularioPrincipal();
    inicializarHistorialPagos();
});

async function inicializarFormularioPrincipal() {
    await cargarDirectores(); // Esperar a que los directores se carguen
    await cargarMotivos(); // Cargar motivos desde la base de datos
    configurarFormulario();
    configurarMotivo();
    inicializarSelect2Proveedores();
    configurarArchivos();
}

function inicializarHistorialPagos() {
    llenarFiltroDirectores();
    llenarFiltroMotivos();
    setFechasDefault(); // Llenar el filtro del historial
    configurarFiltros();
    cargarPagosFiltrados(); // Carga inicial
}

/**
 * Carga la lista de motivos desde el servidor
 */
async function cargarMotivos() {
    try {
        const response = await fetch('controller/pago_servicios_controller.php?accion=obtener_motivos');
        const result = await response.json();
        
        if (result.success) {
            llenarSelectMotivos(result.data);
            llenarFiltroMotivos(result.data);
        } else {
            console.error('Error al cargar motivos:', result.message);
            mostrarAlerta('Error al Cargar Datos', `
                <div class="text-center">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">No se pudieron cargar los motivos</h5>
                    <p class="mb-0 text-muted">Por favor, recargue la página o contacte al administrador.</p>
                </div>
            `);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error de Conexión', `
            <div class="text-center">
                <i class="bi bi-wifi-off text-danger" style="font-size: 3rem;"></i>
                <h5 class="mt-3 mb-2">Error de conexión</h5>
                <p class="mb-0 text-muted">No se pudo conectar con el servidor. Verifique su conexión a internet.</p>
            </div>
        `);
    }
}

/**
 * Llena el select de motivos con los datos cargados
 */
function llenarSelectMotivos(motivos) {
    const select = document.getElementById('motivoSelect');
    if (!select) return;
    
    // Limpiar opciones existentes excepto la primera
    select.innerHTML = '<option value="">Seleccione un motivo</option>';
    
    // Agregar motivos
    motivos.forEach(motivo => {
        const option = document.createElement('option');
        option.value = motivo.nombre;
        option.textContent = motivo.nombre;
        if (motivo.descripcion) {
            option.title = motivo.descripcion;
        }
        select.appendChild(option);
    });
}

/**
 * Llena el filtro de motivos en el historial
 */
function llenarFiltroMotivos(motivos = null) {
    const select = document.getElementById('filtroMotivo');
    if (!select) return;
    
    // Si no se pasan motivos, intentar cargarlos
    if (!motivos) {
        fetch('controller/pago_servicios_controller.php?accion=obtener_motivos')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    llenarFiltroMotivos(result.data);
                }
            })
            .catch(error => console.error('Error al cargar motivos para filtro:', error));
        return;
    }
    
    // Mantener opción "Todos"
    const valorActual = select.value;
    select.innerHTML = '<option value="">Todos</option>';
    
    // Agregar motivos
    motivos.forEach(motivo => {
        const option = document.createElement('option');
        option.value = motivo.nombre;
        option.textContent = motivo.nombre;
        select.appendChild(option);
    });
    
    // Restaurar selección si existía
    if (valorActual) {
        select.value = valorActual;
    }
}

/**
 * Carga la lista de directores desde el servidor
 */
async function cargarDirectores() {
    try {
        const response = await fetch('controller/pago_servicios_controller.php?accion=obtener_directores');
        const result = await response.json();
        
        if (result.success) {
            directoresCache = result.data;
            llenarSelectDirectores();
            llenarFiltroDirectores();
        } else {
            console.error('Error al cargar directores:', result.message);
            mostrarAlerta('Error al Cargar Datos', `
                <div class="text-center">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">No se pudieron cargar los directores</h5>
                    <p class="mb-0 text-muted">Por favor, recargue la página o contacte al administrador.</p>
                </div>
            `);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error de Conexión', `
            <div class="text-center">
                <i class="bi bi-wifi-off text-danger" style="font-size: 3rem;"></i>
                <h5 class="mt-3 mb-2">Error de conexión</h5>
                <p class="mb-0 text-muted">No se pudo conectar con el servidor. Verifique su conexión a internet.</p>
            </div>
        `);
    }
}

/**
 * Llena el select de directores con los datos cargados
 */
function llenarSelectDirectores() {
    const select = document.getElementById('directorSelect');
    if (!select) return;
    
    // Limpiar opciones existentes excepto la primera
    select.innerHTML = '<option value="">Seleccione un director</option>';
    
    // Agregar directores
    directoresCache.forEach(director => {
        const option = document.createElement('option');
        option.value = director.id;
        option.textContent = director.nombre;
        select.appendChild(option);
    });
}

/**
 * Configura el comportamiento del selector de motivo
 */
function configurarMotivo() {
    const motivoSelect = document.getElementById('motivoSelect');
    const seccionProveedor = document.getElementById('seccionProveedor');
    
    if (!motivoSelect || !seccionProveedor) return;
    
    motivoSelect.addEventListener('change', function() {
        const motivo = this.value;
        
        // Mostrar sección de proveedor solo para "Pago de seguros"
        if (motivo === 'Pago de seguros') {
            seccionProveedor.classList.remove('d-none');
            // Hacer obligatorio el proveedor
            document.getElementById('proveedorSelect').required = true;
        } else {
            seccionProveedor.classList.add('d-none');
            // Limpiar y hacer opcional el proveedor
            const proveedorSelect = $('#proveedorSelect');
            if (proveedorSelect.length > 0) {
                proveedorSelect.val(null).trigger('change');
            }
            document.getElementById('proveedorSelect').required = false;
            document.getElementById('cbuProveedor').value = '';
            document.getElementById('descripcionCbuProveedor').value = '';
            document.getElementById('alertaCBU').classList.add('d-none');
        }
    });
}

/**
 * Configura el formulario y sus eventos
 */
function configurarFormulario() {
    const form = document.getElementById('formPagoServicios');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        await registrarPago();
    });
    
    // Configurar formateo de importe
    const importeInput = document.getElementById('importePago');
    if (importeInput) {
        importeInput.addEventListener('keyup', function(e) {
            if (e.key !== 'Backspace' && e.key !== 'Delete') {
                formatearImporte(e);
            }
        });
        importeInput.addEventListener('blur', formatearImporte);
    }
}

/**
 * Formatea el input de importe con separadores de miles
 */
function formatearImporte(e) {
    const input = e.target;
    let valor = input.value;

    // 1. Separar la parte entera de la decimal
    let partes = valor.split(',');
    let parteEntera = partes[0];
    let parteDecimal = partes.length > 1 ? partes[1] : null;

    // 2. Limpiar y formatear la parte entera (quitar todo lo que no sea dígito)
    let enteroLimpio = parteEntera.replace(/\D/g, '');
    let enteroFormateado = enteroLimpio.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    // 3. Ensamblar el valor final
    let valorFinal = enteroFormateado;
    
    // 4. Si hay parte decimal, limpiarla y añadirla
    if (parteDecimal !== null) {
        let decimalLimpio = parteDecimal.replace(/\D/g, '');
        valorFinal += ',' + decimalLimpio.substring(0, 2); // Limitar a 2 decimales
    }

    // 5. Actualizar el valor del input solo si ha cambiado, para evitar bucles
    if (input.value !== valorFinal) {
        input.value = valorFinal;
    }
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
            }
        },
        minimumInputLength: 2,
        ajax: {
            url: 'controller/pago_servicios_controller.php',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    accion: 'buscar_proveedores',
                    q: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                
                console.log('Datos recibidos del servidor:', data);
                
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
        templateResult: formatProveedor,
        templateSelection: formatProveedorSeleccion
    });
    
    // Evento cuando se selecciona un proveedor
    proveedorSelect.on('select2:select', function(e) {
        const data = e.params.data;
        console.log('Proveedor seleccionado:', data);
        
        const cbuInput = document.getElementById('cbuProveedor');
        const descripcionCbuInput = document.getElementById('descripcionCbuProveedor');
        const alertaCBU = document.getElementById('alertaCBU');
        
        // Caso 1: Opción manual
        if (data.es_manual) {
            if (cbuInput) cbuInput.value = '';
            if (descripcionCbuInput) descripcionCbuInput.value = '';
            if (alertaCBU) alertaCBU.classList.add('d-none');
            return;
        }
        
        // Caso 2: Proveedor con CBU
        if (data.tiene_cbu && data.cbu) {
            if (cbuInput) cbuInput.value = data.cbu;
            if (descripcionCbuInput) descripcionCbuInput.value = data.descripcion_cbu || '';
            if (alertaCBU) alertaCBU.classList.add('d-none');
        } 
        // Caso 3: Proveedor sin CBU
        else {
            if (cbuInput) cbuInput.value = '';
            if (descripcionCbuInput) descripcionCbuInput.value = '';
            if (alertaCBU) alertaCBU.classList.remove('d-none');
        }
    });
    
    // Evento cuando se limpia la selección
    proveedorSelect.on('select2:clear', function() {
        document.getElementById('cbuProveedor').value = '';
        document.getElementById('descripcionCbuProveedor').value = '';
        document.getElementById('alertaCBU').classList.add('d-none');
    });
}

/**
 * Formatea cómo se muestra cada proveedor en la lista desplegable
 */
function formatProveedor(proveedor) {
    if (proveedor.loading) {
        return proveedor.text;
    }
    
    if (proveedor.es_manual) {
        return $('<span><i class="bi bi-tools"></i> ' + proveedor.text + '</span>');
    }
    
    var $proveedor = $(
        '<div class="d-flex flex-column">' +
            '<div class="fw-bold">' + (proveedor.nombre || proveedor.text) + '</div>' +
            '<div class="small text-muted">' +
                '<i class="bi bi-card-text"></i> CUIT: ' + (proveedor.cuit || 'N/A') +
                (proveedor.tiene_cbu ? 
                    ' <span class="badge bg-success badge-sm ms-1"><i class="bi bi-check-circle"></i> Tiene CBU</span>' : 
                    ' <span class="badge bg-warning text-dark badge-sm ms-1"><i class="bi bi-exclamation-triangle"></i> Sin CBU</span>'
                ) +
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
    
    if (proveedor.es_manual) {
        return '🔧 ' + proveedor.text;
    }
    
    if (proveedor.nombre && proveedor.cuit) {
        return proveedor.nombre + ' (' + proveedor.cuit + ')';
    }
    
    return proveedor.text;
}

/**
 * Configura el manejo de archivos (cámara/galería)
 */
function configurarArchivos() {
    const facturaCamera = document.getElementById('facturaCamera');
    const facturaGallery = document.getElementById('facturaGallery');
    const facturaInput = document.getElementById('facturaInput');
    
    if (facturaCamera) {
        facturaCamera.addEventListener('change', function(e) {
            procesarArchivo(e.target.files[0]);
        });
    }
    
    if (facturaGallery) {
        facturaGallery.addEventListener('change', function(e) {
            procesarArchivo(e.target.files[0]);
        });
    }
    
    if (facturaInput) {
        facturaInput.addEventListener('change', function(e) {
            procesarArchivo(e.target.files[0]);
        });
    }
}

/**
 * Procesa el archivo seleccionado
 */
function procesarArchivo(archivo) {
    if (!archivo) return;
    
    archivoSeleccionado = archivo;
    
    const previewFactura = document.getElementById('previewFactura');
    const imgPreview = document.getElementById('imgPreviewFactura');
    const pdfPreview = document.getElementById('pdfPreviewFactura');
    const infoArchivo = document.getElementById('infoArchivo');
    
    // Mostrar preview
    previewFactura.classList.remove('d-none');
    
    // Mostrar información del archivo
    const tamañoKB = (archivo.size / 1024).toFixed(2);
    infoArchivo.innerHTML = `
        <strong>${archivo.name}</strong><br>
        <small class="text-muted">Tamaño: ${tamañoKB} KB</small>
    `;
    
    // Preview según tipo
    if (archivo.type.startsWith('image/')) {
        imgPreview.classList.remove('d-none');
        pdfPreview.classList.add('d-none');
        
        const reader = new FileReader();
        reader.onload = function(e) {
            imgPreview.src = e.target.result;
        };
        reader.readAsDataURL(archivo);
    } else if (archivo.type === 'application/pdf') {
        imgPreview.classList.add('d-none');
        pdfPreview.classList.remove('d-none');
    }
}

/**
 * Elimina el archivo seleccionado
 */
function eliminarFactura() {
    archivoSeleccionado = null;
    
    document.getElementById('facturaCamera').value = '';
    document.getElementById('facturaGallery').value = '';
    document.getElementById('facturaInput').value = '';
    document.getElementById('previewFactura').classList.add('d-none');
    document.getElementById('imgPreviewFactura').src = '';
}

/**
 * Registra el pago en el sistema
 */
async function registrarPago() {
    try {
        const id = document.getElementById('pagoId').value;
        const esEdicion = id !== '';

        // --- VALIDACIONES (se ejecutan para ambos modos: crear y editar) ---
        const directorSelect = document.getElementById('directorSelect');
        const motivoSelect = document.getElementById('motivoSelect');
        const fechaVencimiento = document.getElementById('fechaVencimiento');
        const importePago = document.getElementById('importePago');
        
        if (!directorSelect.value) {
            mostrarAlerta('Campo Requerido', `<div class="text-center"><i class="bi bi-person-fill-x text-warning" style="font-size: 3rem;"></i><h5 class="mt-3 mb-2">Director no seleccionado</h5><p class="mb-0">Debe seleccionar un director.</p></div>`);
            return;
        }
        if (!motivoSelect.value) {
            mostrarAlerta('Campo Requerido', `<div class="text-center"><i class="bi bi-list-ul text-warning" style="font-size: 3rem;"></i><h5 class="mt-3 mb-2">Motivo no seleccionado</h5><p class="mb-0">Debe seleccionar el tipo de pago.</p></div>`);
            return;
        }
        if (!fechaVencimiento.value) {
            mostrarAlerta('Campo Requerido', `<div class="text-center"><i class="bi bi-calendar-x text-warning" style="font-size: 3rem;"></i><h5 class="mt-3 mb-2">Fecha no ingresada</h5><p class="mb-0">Debe ingresar la fecha de vencimiento.</p></div>`);
            return;
        }
        if (!importePago.value || parseFloat(importePago.value.replace(/\./g, '')) <= 0) {
            mostrarAlerta('Campo Requerido', `<div class="text-center"><i class="bi bi-currency-dollar text-warning" style="font-size: 3rem;"></i><h5 class="mt-3 mb-2">Importe inválido</h5><p class="mb-0">Debe ingresar un importe mayor a cero.</p></div>`);
            return;
        }
        // La factura solo es obligatoria al crear, no al editar (a menos que se quiera reemplazar)
        if (!esEdicion && !archivoSeleccionado) {
            mostrarAlerta('Archivo Requerido', `<div class="text-center"><i class="bi bi-file-earmark-image text-warning" style="font-size: 3rem;"></i><h5 class="mt-3 mb-2">Factura no adjuntada</h5><p class="mb-0">Debe adjuntar la foto o PDF de la factura.</p></div>`);
            return;
        }
        if (motivoSelect.value === 'Pago de seguros') {
            const proveedorSelect = $('#proveedorSelect');
            if (!proveedorSelect.val()) {
                mostrarAlerta('Proveedor Requerido', `<div class="text-center"><i class="bi bi-person-badge text-warning" style="font-size: 3rem;"></i><h5 class="mt-3 mb-2">Proveedor no seleccionado</h5><p class="mb-0">Para pagos de seguros debe seleccionar un proveedor.</p></div>`);
                return;
            }
        }
        const cbuInput = document.getElementById('cbuProveedor');
        if (cbuInput && cbuInput.value.trim() !== '') {
            if (!validarCBU(cbuInput.value)) {
                mostrarAlerta('CBU Inválido', `<div class="text-center"><i class="bi bi-bank text-warning" style="font-size: 3rem;"></i><h5 class="mt-3 mb-2">Formato de CBU incorrecto</h5><p class="mb-0">El CBU debe tener 22 dígitos numéricos.</p></div>`);
                return;
            }
        }

        // --- FIN DE VALIDACIONES ---

        if (typeof mostrarLoading === 'function') mostrarLoading();

        const formData = new FormData();
        // Determinar la acción y añadir el ID si es edición
        formData.append('accion', esEdicion ? 'actualizar_pago' : 'crear');
        if (esEdicion) {
            formData.append('id', id);
        }

        // Recopilar datos del formulario
        const selectedOption = directorSelect.options[directorSelect.selectedIndex];
        formData.append('id_director', selectedOption.value); // <-- CORRECTO: Enviamos el ID real del director
        formData.append('nombre_director', selectedOption.textContent); 
        formData.append('motivo', motivoSelect.value);
        formData.append('fecha_vencimiento', fechaVencimiento.value);
        formData.append('importe', importePago.value.replace(/\./g, ''));
        formData.append('observaciones', document.getElementById('observaciones').value || '');

        if (motivoSelect.value === 'Pago de seguros') {
            formData.append('nom_provee', $('#proveedorSelect').val());
            const cbu = document.getElementById('cbuProveedor').value.trim();
            const descripcionCbu = document.getElementById('descripcionCbuProveedor').value.trim();
            if (cbu) formData.append('cbu', cbu);
            if (descripcionCbu) formData.append('descripcion_cbu', descripcionCbu);
        }

        // Convertir archivo a base64 SÓLO si se seleccionó uno nuevo
        if (archivoSeleccionado) {
            const base64 = await convertirArchivoABase64(archivoSeleccionado);
            formData.append('foto', base64);
        } else {
            // No enviar el campo 'foto' si no se subió nada, para que el backend no lo actualice
            // El backend ya está preparado para manejar esto.
        }

        // Enviar al servidor
        const response = await fetch('controller/pago_servicios_controller.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            mostrarAlerta(esEdicion ? '¡Actualización Exitosa!' : '¡Registro Exitoso!', `
                <div class="text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">Pago ${esEdicion ? 'actualizado' : 'registrado'}</h5>
                    <p class="mb-1">La operación se completó correctamente.</p>
                </div>
            `);
            
            resetearFormulario();
            cargarPagosFiltrados(); // Recargar el historial para ver los cambios
        } else {
            throw new Error(result.message || 'Error desconocido al procesar la solicitud.');
        }

    } catch (error) {
        console.error('Error al registrar/actualizar pago:', error);
        mostrarAlerta('Error al Procesar', `
            <div class="text-center">
                <i class="bi bi-exclamation-octagon-fill text-danger" style="font-size: 3rem;"></i>
                <h5 class="mt-3 mb-2">No se pudo completar la operación</h5>
                <p class="mb-2">${error.message}</p>
                <p class="text-muted small mb-0">Si el problema persiste, contacte al administrador.</p>
            </div>
        `);
    } finally {
        if (typeof ocultarLoading === 'function') ocultarLoading();
    }
}

/**
 * Convierte un archivo a base64
 */
function convertirArchivoABase64(archivo) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            resolve(e.target.result);
        };
        reader.onerror = function(error) {
            reject(error);
        };
        reader.readAsDataURL(archivo);
    });
}

function setFechasDefault() {
    const fechaDesdeInput = document.getElementById('filtroFechaDesde');
    const fechaHastaInput = document.getElementById('filtroFechaHasta');

    if (!fechaDesdeInput || !fechaHastaInput) return;

    const hoy = new Date();
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);

    // Formatear a 'YYYY-MM-DD'
    const aISO = fecha => fecha.toISOString().split('T')[0];

    fechaDesdeInput.value = aISO(primerDia);
    fechaHastaInput.value = aISO(ultimoDia);
}

/**
 * Valida el formato del CBU (22 dígitos)
 */
function validarCBU(cbu) {
    cbu = cbu.replace(/\s/g, '');
    return /^\d{22}$/.test(cbu);
}

// REEMPLAZA LAS DOS FUNCIONES ANTERIORES CON ESTO
// Estas funciones ya no se usarán, la nueva lógica está abajo.

/**
 * Ver foto de un egreso
 */
async function verFotoEgreso(idEgreso) {
    try {
        // ===== LA CORRECCIÓN ESTÁ EN ESTA LÍNEA =====
        const response = await fetch(`controller/pago_servicios_controller.php?accion=obtener_foto&id=${idEgreso}`);
        const result = await response.json();
        
        if (result.success && result.foto) {
            const esPdf = result.tipo === 'application/pdf';
            
            if (esPdf) {
                // Para PDFs, crear un enlace de descarga
                const pdfBlob = base64ToBlob(result.foto, 'application/pdf');
                const url = URL.createObjectURL(pdfBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `factura_egreso_${idEgreso}.pdf`;
                document.body.appendChild(link); // Requerido en algunos navegadores
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } else {
                // Para imágenes, mostrar en modal
                let modal = document.getElementById('modalFotoEgresoServicio');
                if (!modal) {
                    const modalHTML = `
                        <div class="modal fade" id="modalFotoEgresoServicio" tabindex="-1">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalFotoEgresoServicioLabel"></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center p-2">
                                        <img id="imagenFotoEgresoServicio" src="" class="img-fluid" style="max-height: 85vh;">
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    document.body.insertAdjacentHTML('beforeend', modalHTML);
                    modal = document.getElementById('modalFotoEgresoServicio');
                }
                
                document.getElementById('modalFotoEgresoServicioLabel').textContent = `Foto de Factura - Egreso ID: ${idEgreso}`;
                document.getElementById('imagenFotoEgresoServicio').src = `data:${result.tipo};base64,${result.foto}`;
                
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        } else {
            throw new Error(result.message || 'Archivo no disponible');
        }
    } catch (error) {
        console.error('Error al cargar archivo:', error);
        mostrarAlerta('Error al Cargar Archivo', `<p>No se pudo cargar el archivo solicitado.</p>`);
    }
}

/**
 * Convierte base64 a Blob
 */
function base64ToBlob(base64, contentType) {
    const byteCharacters = atob(base64);
    const byteNumbers = new Array(byteCharacters.length);
    for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    const byteArray = new Uint8Array(byteNumbers);
    return new Blob([byteArray], { type: contentType });
}

// Exportar funciones para uso global
window.eliminarFactura = eliminarFactura;
window.validarCBU = validarCBU;
window.verFotoEgreso = verFotoEgreso;


function configurarFiltros() {
    const form = document.getElementById('formFiltros');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        cargarPagosFiltrados();
    });

    form.addEventListener('reset', () => {
        // Usamos un timeout para que el reset se aplique antes de recargar
        setTimeout(() => cargarPagosFiltrados(), 0);
    });
}

function llenarFiltroDirectores() {
    const select = document.getElementById('filtroDirector');
    if (!select || directoresCache.length === 0) return;
    
    // Limpiamos por si se llama más de una vez
    select.innerHTML = '<option value="">Todos</option>';
    
    directoresCache.forEach(d => {
        // Usamos el NOMBRE como valor, ya que el backend lo espera así
        const option = new Option(d.nombre, d.nombre);
        select.add(option);
    });
}

async function cargarPagosFiltrados() {
    const tbody = document.getElementById('tbodyPagos');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm" role="status"></div> Cargando...</td></tr>`;

    // Construir la URL con los filtros
    const params = new URLSearchParams({
        accion: 'listar_pagos_filtrados',
        director: document.getElementById('filtroDirector').value,
        motivo: document.getElementById('filtroMotivo').value,
        fecha_desde: document.getElementById('filtroFechaDesde').value,
        fecha_hasta: document.getElementById('filtroFechaHasta').value
    });

    // Limpiar parámetros vacíos para una URL más limpia
    for (const [key, value] of [...params.entries()]) {
        if (!value) {
            params.delete(key);
        }
    }
    // Asegurarse de que la acción siempre esté presente
    params.set('accion', 'listar_pagos_filtrados');

    try {
        const response = await fetch(`controller/pago_servicios_controller.php?${params.toString()}`);
        const result = await response.json();
        
        if (result.success) {
            renderTablaPagos(result.data);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error al cargar pagos:', error);
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle"></i> Error al cargar los datos.</td></tr>`;
    }
}

function renderTablaPagos(pagos) {
    const tbody = document.getElementById('tbodyPagos');
    if (!tbody) return;

    if (!pagos || pagos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No se encontraron resultados.</td></tr>`;
        return;
    }

    let html = '';
    pagos.forEach(pago => {
        const fechaCarga = new Date(pago.fecha_carga.split(' ')[0] + 'T00:00:00').toLocaleDateString('es-AR', { timeZone: 'UTC' });
        const importe = formatoMoneda.format(pago.importe);
        
        const adjuntoHtml = pago.tiene_foto 
            ? `<button class="btn btn-sm btn-outline-secondary" onclick="verFotoEgreso(${pago.id})" title="${pago.tipo_archivo === 'application/pdf' ? 'Descargar PDF' : 'Ver Imagen'}">
                 <i class="bi ${pago.tipo_archivo === 'application/pdf' ? 'bi-file-earmark-pdf' : 'bi-image'}"></i>
               </button>`
            : '<span class="text-muted small">-</span>';

        html += `
            <tr id="fila-pago-${pago.id}">
                <td>${fechaCarga}</td>
                <td>${pago.nombre_director}</td>
                <td><span class="badge bg-light text-dark border">${pago.motivo}</span></td>
                <td class="text-end fw-bold">${importe}</td>
                <td class="text-center">${adjuntoHtml}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="editarPago(${pago.id})" title="Editar (próximamente)">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarPago(${pago.id})" title="Eliminar (próximamente)">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}


// --- FUNCIONES DE ACCIÓN (A IMPLEMENTAR EN FASE 2) ---

async function editarPago(id) {
    const form = document.getElementById('formPagoServicios');

    try {
        const response = await fetch(`controller/pago_servicios_controller.php?accion=obtener_pago_detalle&id=${id}`);
        const result = await response.json();

        if (result.success) {
            poblarFormularioParaEdicion(result.data);
            // Hacer scroll hasta el formulario para que el usuario lo vea
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        mostrarAlerta('Error', `No se pudieron cargar los datos del pago: ${error.message}`);
    }
}

function poblarFormularioParaEdicion(pago) {
    // 1. Guardar el ID en el campo oculto
    document.getElementById('pagoId').value = pago.id;

    // 2. Poblar campos simples
    document.getElementById('directorSelect').value = pago.nombre_director;
    document.getElementById('motivoSelect').value = pago.motivo;
    document.getElementById('fechaVencimiento').value = pago.fecha;
    document.getElementById('observaciones').value = pago.observaciones || '';
    
    // Formatear el importe para mostrarlo correctamente
    const importeInput = document.getElementById('importePago');
    importeInput.value = new Intl.NumberFormat('es-AR').format(pago.importe);

    // 3. Manejar el Select2 de proveedor (la parte más compleja)
    const motivoSelect = document.getElementById('motivoSelect');
    if (pago.motivo === 'Pago de seguros' && pago.proveedor_nom) {
        motivoSelect.dispatchEvent(new Event('change')); // Simula el cambio para mostrar la sección
        
        const proveedorSelect = $('#proveedorSelect');
        // Crear una nueva opción con los datos del proveedor
        const option = new Option(pago.proveedor_nom, pago.proveedor_nom, true, true);
        proveedorSelect.append(option).trigger('change');
        
        // Poblar CBU y descripción
        document.getElementById('cbuProveedor').value = pago.proveedor_cbu || '';
        document.getElementById('descripcionCbuProveedor').value = pago.proveedor_descripcion_cbu || '';
    }

    // 4. Cambiar el texto y estilo del botón de submit
    const submitButton = document.querySelector('#formPagoServicios button[type="submit"]');
    submitButton.innerHTML = '<i class="bi bi-save"></i> Actualizar Pago';
    submitButton.classList.remove('btn-primary');
    submitButton.classList.add('btn-success');
    
    // 5. Limpiar el campo de archivo y mostrar un mensaje
    eliminarFactura();
    document.getElementById('infoArchivo').innerHTML = '<div class="alert alert-info py-1 small"><i class="bi bi-info-circle"></i> La factura original se conservará. Adjunte un nuevo archivo solo si desea reemplazarla.</div>';
    document.getElementById('previewFactura').classList.remove('d-none');
}

function resetearFormulario() {
    const form = document.getElementById('formPagoServicios');
    form.reset();

    // Resetear campo oculto y botón
    document.getElementById('pagoId').value = '';
    const submitButton = document.querySelector('#formPagoServicios button[type="submit"]');
    submitButton.innerHTML = '<i class="bi bi-check-circle"></i> Registrar Pago';
    submitButton.classList.remove('btn-success');
    submitButton.classList.add('btn-primary');

    // Resetear Select2 y sección de proveedor
    $('#proveedorSelect').val(null).trigger('change');
    document.getElementById('seccionProveedor').classList.add('d-none');
    
    // Limpiar preview de factura
    eliminarFactura();
}

function eliminarPago(id) {
    // Usamos el modal de confirmación global
    mostrarConfirmacion(
        '¿Eliminar Pago?',
        `
        <div class="text-center">
            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
            <h5 class="mt-3">¿Está seguro de que desea eliminar este pago?</h5>
            <p class="text-muted">Esta acción es permanente y no se puede deshacer.</p>
        </div>
        `,
        async () => {
            try {
                const formData = new FormData();
                formData.append('accion', 'eliminar_pago');
                formData.append('id', id);

                const response = await fetch('controller/pago_servicios_controller.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Opcional: mostrar un "toast" de éxito
                    
                    // Eliminar la fila de la tabla visualmente
                    const fila = document.getElementById(`fila-pago-${id}`);
                    if (fila) {
                        fila.classList.add('fade-out'); // Añadir clase para animación
                        setTimeout(() => {
                            fila.remove();
                            // Comprobar si la tabla queda vacía
                            const tbody = document.getElementById('tbodyPagos');
                            if (tbody.rows.length === 0) {
                                renderTablaPagos([]); // Muestra el mensaje "No se encontraron resultados"
                            }
                        }, 500); // 500ms, igual que la duración de la animación
                    }
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                mostrarAlerta('Error', `<p>No se pudo eliminar el pago. ${error.message}</p>`);
            }
        },
        'Sí, eliminar', // Texto del botón de confirmación
        'btn-danger'    // Clase del botón de confirmación
    );
}

// Asegurarse de que el formato de moneda esté definido
const formatoMoneda = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });
