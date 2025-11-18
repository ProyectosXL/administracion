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
    cargarDirectores();
    configurarFormulario();
    configurarMotivo();
    inicializarSelect2Proveedores();
    configurarArchivos();
    cargarUltimosPagos();
});

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
    
    // Eliminar todo excepto números
    let numeroLimpio = valor.replace(/\D/g, '');
    
    if (numeroLimpio === '') {
        return;
    }
    
    // Formatear con puntos cada 3 dígitos
    let valorFormateado = numeroLimpio.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    
    if (input.value !== valorFormateado) {
        input.value = valorFormateado;
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
        // Validaciones
        const directorSelect = document.getElementById('directorSelect');
        const motivoSelect = document.getElementById('motivoSelect');
        const fechaVencimiento = document.getElementById('fechaVencimiento');
        const importePago = document.getElementById('importePago');
        
        if (!directorSelect.value) {
            mostrarAlerta('Campo Requerido', `
                <div class="text-center">
                    <i class="bi bi-person-fill-x text-warning" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">Director no seleccionado</h5>
                    <p class="mb-0">Debe seleccionar un director antes de continuar.</p>
                </div>
            `);
            return;
        }
        
        if (!motivoSelect.value) {
            mostrarAlerta('Campo Requerido', `
                <div class="text-center">
                    <i class="bi bi-list-ul text-warning" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">Motivo no seleccionado</h5>
                    <p class="mb-0">Debe seleccionar el tipo de pago a realizar.</p>
                </div>
            `);
            return;
        }
        
        if (!fechaVencimiento.value) {
            mostrarAlerta('Campo Requerido', `
                <div class="text-center">
                    <i class="bi bi-calendar-x text-warning" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">Fecha no ingresada</h5>
                    <p class="mb-0">Debe ingresar la fecha de vencimiento de la factura.</p>
                </div>
            `);
            return;
        }
        
        if (!importePago.value || parseFloat(importePago.value.replace(/\./g, '')) <= 0) {
            mostrarAlerta('Campo Requerido', `
                <div class="text-center">
                    <i class="bi bi-currency-dollar text-warning" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">Importe inválido</h5>
                    <p class="mb-0">Debe ingresar un importe mayor a cero.</p>
                </div>
            `);
            return;
        }
        
        if (!archivoSeleccionado) {
            mostrarAlerta('Archivo Requerido', `
                <div class="text-center">
                    <i class="bi bi-file-earmark-image text-warning" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">Factura no adjuntada</h5>
                    <p class="mb-0">Debe adjuntar la foto o PDF de la factura.</p>
                </div>
            `);
            return;
        }
        
        // Validar proveedor si el motivo es "Pago de seguros"
        if (motivoSelect.value === 'Pago de seguros') {
            const proveedorSelect = $('#proveedorSelect');
            if (!proveedorSelect.val()) {
                mostrarAlerta('Proveedor Requerido', `
                    <div class="text-center">
                        <i class="bi bi-person-badge text-warning" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 mb-2">Proveedor no seleccionado</h5>
                        <p class="mb-0">Para pagos de seguros debe seleccionar un proveedor.</p>
                    </div>
                `);
                return;
            }
        }
        
        // Validar CBU si se ingresó
        const cbuInput = document.getElementById('cbuProveedor');
        if (cbuInput && cbuInput.value.trim() !== '') {
            if (!validarCBU(cbuInput.value)) {
                mostrarAlerta('CBU Inválido', `
                    <div class="text-center">
                        <i class="bi bi-bank text-warning" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 mb-2">Formato de CBU incorrecto</h5>
                        <p class="mb-0">El CBU debe tener exactamente 22 dígitos numéricos.</p>
                    </div>
                `);
                return;
            }
        }
        
        // Mostrar loading
        if (typeof mostrarLoading === 'function') {
            mostrarLoading();
        }
        
        // Preparar FormData
        const formData = new FormData();
        formData.append('accion', 'crear');
        formData.append('id_director', directorSelect.value);
        formData.append('nombre_director', directorSelect.options[directorSelect.selectedIndex].text);
        formData.append('motivo', motivoSelect.value);
        formData.append('fecha_vencimiento', fechaVencimiento.value);
        formData.append('importe', importePago.value.replace(/\./g, ''));
        formData.append('observaciones', document.getElementById('observaciones').value || '');
        
        // Agregar datos de proveedor si es "Pago de seguros"
        if (motivoSelect.value === 'Pago de seguros') {
            const proveedorSelect = $('#proveedorSelect');
            formData.append('nom_provee', proveedorSelect.val());
            
            const cbu = document.getElementById('cbuProveedor').value.trim();
            const descripcionCbu = document.getElementById('descripcionCbuProveedor').value.trim();
            
            if (cbu) {
                formData.append('cbu', cbu);
            }
            if (descripcionCbu) {
                formData.append('descripcion_cbu', descripcionCbu);
            }
        }
        
        // Convertir archivo a base64
        const base64 = await convertirArchivoABase64(archivoSeleccionado);
        formData.append('foto', base64);
        
        // Enviar al servidor
        const response = await fetch('controller/pago_servicios_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarAlerta('¡Registro Exitoso!', `
                <div class="text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">Pago de Servicio Registrado</h5>
                    <p class="mb-1">El pago ha sido registrado correctamente en el sistema.</p>
                    <p class="text-muted small mb-0">Comprobante N° ${result.n_comp || 'generado'}</p>
                </div>
            `);
            
            // Limpiar formulario
            document.getElementById('formPagoServicios').reset();
            $('#proveedorSelect').val(null).trigger('change');
            eliminarFactura();
            document.getElementById('seccionProveedor').classList.add('d-none');
            document.getElementById('alertaCBU').classList.add('d-none');
            
            // Recargar lista de últimos pagos
            cargarUltimosPagos();
        } else {
            throw new Error(result.message || 'Error al registrar el pago');
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al Procesar', `
            <div class="text-center">
                <i class="bi bi-exclamation-octagon-fill text-danger" style="font-size: 3rem;"></i>
                <h5 class="mt-3 mb-2">No se pudo completar la operación</h5>
                <p class="mb-2">${error.message}</p>
                <p class="text-muted small mb-0">Si el problema persiste, contacte al administrador del sistema.</p>
            </div>
        `);
    } finally {
        if (typeof ocultarLoading === 'function') {
            ocultarLoading();
        }
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

/**
 * Valida el formato del CBU (22 dígitos)
 */
function validarCBU(cbu) {
    cbu = cbu.replace(/\s/g, '');
    return /^\d{22}$/.test(cbu);
}

/**
 * Muestra un mensaje de alerta (usa modal_global.js)
 * Nota: Esta función está definida en modal_global.js
 * Si no está disponible, se usa alert nativo como fallback
 */
// La función mostrarAlerta ya está definida en modal_global.js
// No es necesario redefinirla aquí

/**
 * Carga y muestra los últimos pagos de servicios registrados
 */
async function cargarUltimosPagos() {
    try {
        const response = await fetch('controller/pago_servicios_controller.php?accion=listar_ultimos&limite=10');
        const result = await response.json();
        
        if (result.success) {
            mostrarUltimosPagos(result.data);
        } else {
            document.getElementById('listaPagosServicios').innerHTML = `
                <div class="alert alert-warning text-center">
                    <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                    <h6 class="mt-2 mb-1">No se pudieron cargar los pagos</h6>
                    <small>Intente recargar la página</small>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error al cargar últimos pagos:', error);
        document.getElementById('listaPagosServicios').innerHTML = `
            <div class="alert alert-danger text-center">
                <i class="bi bi-wifi-off" style="font-size: 2rem;"></i>
                <h6 class="mt-2 mb-1">Error de conexión</h6>
                <small>No se pudo conectar con el servidor</small>
            </div>
        `;
    }
}

/**
 * Muestra la lista de últimos pagos
 */
function mostrarUltimosPagos(pagos) {
    const contenedor = document.getElementById('listaPagosServicios');
    
    if (!pagos || pagos.length === 0) {
        contenedor.innerHTML = `
            <div class="alert alert-info text-center">
                <i class="bi bi-inbox" style="font-size: 2.5rem;"></i>
                <h6 class="mt-2 mb-1">No hay pagos registrados</h6>
                <small>Los pagos que registre aparecerán aquí</small>
            </div>
        `;
        return;
    }
    
    const formatoMoneda = new Intl.NumberFormat('es-AR', { 
        style: 'currency', 
        currency: 'ARS',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    let html = '<div class="list-group">';
    
    pagos.forEach(pago => {
        // Para pagos de servicios, usar fecha_carga en lugar de fecha
        const fechaStr = pago.fecha_carga ? pago.fecha_carga.split(' ')[0] : pago.fecha;
        const fecha = new Date(fechaStr + 'T00:00:00').toLocaleDateString('es-AR');
        const importe = formatoMoneda.format(pago.importe);
        
        // Badge del motivo
        let motivoBadge = '';
        switch(pago.motivo) {
            case 'Pago de seguros':
                motivoBadge = '<span class="badge bg-primary">Seguros</span>';
                break;
            case 'Pago de patentes':
                motivoBadge = '<span class="badge bg-success">Patentes</span>';
                break;
            case 'Pago de expensas':
                motivoBadge = '<span class="badge bg-info">Expensas</span>';
                break;
            case 'Pago de tarjetas':
                motivoBadge = '<span class="badge bg-warning text-dark">Tarjetas</span>';
                break;
            case 'Transf. Haberes':
                motivoBadge = '<span class="badge bg-danger">Haberes</span>';
                break;
            case 'Otros':
                motivoBadge = '<span class="badge bg-dark">Otros</span>';
                break;
            default:
                motivoBadge = '<span class="badge bg-secondary">' + pago.motivo + '</span>';
        }
        
        html += `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                    <h6 class="mb-1">${pago.nombre_director}</h6>
                    <small class="text-muted">${fecha}</small>
                </div>
                <div class="mb-2">
                    ${motivoBadge}
                    <strong class="text-success ms-2">${importe}</strong>
                </div>
        `;
        
        // Mostrar datos de proveedor si existen
        if (pago.proveedor_nom) {
            html += `
                <div class="small text-muted">
                    <i class="bi bi-person-badge-fill"></i> ${pago.proveedor_nom}
            `;
            if (pago.proveedor_cbu) {
                html += `<br><i class="bi bi-bank"></i> CBU: ${pago.proveedor_cbu}`;
            }
            if (pago.proveedor_descripcion_cbu) {
                html += ` - ${pago.proveedor_descripcion_cbu}`;
            }
            html += `</div>`;
        }
        
        if (pago.observaciones) {
            html += `
                <div class="small text-muted mt-1">
                    <i class="bi bi-chat-left-text"></i> ${pago.observaciones}
                </div>
            `;
        }
        
        // Botón para ver foto/descargar PDF si existe
        if (pago.tiene_foto) {
            const esPdf = pago.tipo_archivo === 'application/pdf';
            const textoBoton = esPdf ? 'Descargar PDF' : 'Ver Factura';
            const iconoBoton = esPdf ? 'bi-download' : 'bi-image';
            
            html += `
                <div class="mt-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="verFotoEgreso(${pago.id})">
                        <i class="bi ${iconoBoton}"></i> ${textoBoton}
                    </button>
                </div>
            `;
        }
        
        html += `</div>`;
    });
    
    html += '</div>';
    contenedor.innerHTML = html;
}

/**
 * Ver foto de un egreso
 */
async function verFotoEgreso(idEgreso) {
    try {
        const response = await fetch(`controller/caja_egresos_controller.php?accion=obtener_foto&id=${idEgreso}`);
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
                link.click();
                URL.revokeObjectURL(url);
            } else {
                // Para imágenes, mostrar en modal
                let modal = document.getElementById('modalFotoEgresoServicio');
                if (!modal) {
                    const modalHTML = `
                        <div class="modal fade" id="modalFotoEgresoServicio" tabindex="-1" aria-labelledby="modalFotoEgresoServicioLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalFotoEgresoServicioLabel">Foto de Factura</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <img id="imagenFotoEgresoServicio" src="" class="img-fluid" alt="Foto de factura" style="max-height: 80vh; cursor: zoom-in;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.insertAdjacentHTML('beforeend', modalHTML);
                    modal = document.getElementById('modalFotoEgresoServicio');
                }
                
                // Configurar modal
                const modalTitle = document.getElementById('modalFotoEgresoServicioLabel');
                modalTitle.textContent = `Foto de Factura - Egreso ID: ${idEgreso}`;
                
                // Mostrar imagen
                const img = document.getElementById('imagenFotoEgresoServicio');
                img.src = `data:${result.tipo};base64,${result.foto}`;
                
                // Mostrar modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        } else {
            mostrarAlerta('Error al Cargar Archivo', `
                <div class="text-center">
                    <i class="bi bi-file-earmark-x text-danger" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">Archivo no disponible</h5>
                    <p class="mb-0">No se pudo cargar el archivo solicitado.</p>
                </div>
            `);
        }
    } catch (error) {
        console.error('Error al cargar archivo:', error);
        mostrarAlerta('Error al Cargar Archivo', `
            <div class="text-center">
                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                <h5 class="mt-3 mb-2">Error al procesar el archivo</h5>
                <p class="mb-0">${error.message || 'Ocurrió un error inesperado'}</p>
            </div>
        `);
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
window.cargarUltimosPagos = cargarUltimosPagos;
window.verFotoEgreso = verFotoEgreso;
