/**
 * Script para Formulario de Gastos Alberto
 */

// Variable global para almacenar archivo comprimido (imagen o PDF)
let imagenGastoBase64 = null;
let tipoArchivoGasto = null;

// Variables para centros de costo
let centrosCostoDisponibles = [];
let contadorFilasDistribucion = 0;

// Detectar si estamos en dispositivo móvil
function esMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Previsualizar archivo seleccionado (foto o PDF)
function previsualizarFotoGasto(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validar tipo de archivo
    const formatosValidos = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
        'image/bmp', 'image/webp', 'image/tiff', 'image/heic', 'image/heif',
        'application/pdf'
    ];
    
    const esImagen = file.type.match('image.*');
    const esPdf = file.type === 'application/pdf';
    
    if (!formatosValidos.includes(file.type) && !esImagen) {
        const mensaje = esMobile() ? 
            'Por favor selecciona una imagen o PDF válido.' :
            'Por favor selecciona una imagen o PDF válido.';
        mostrarAlerta('Error', mensaje);
        input.value = '';
        return;
    }
    
    // Validar tamaño
    const maxSize = esMobile() ? 15 * 1024 * 1024 : 10 * 1024 * 1024;
    if (file.size > maxSize) {
        const maxSizeMB = Math.round(maxSize / (1024 * 1024));
        mostrarAlerta('Error', `El archivo es demasiado grande. Máximo ${maxSizeMB}MB permitido.`);
        input.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        if (esPdf) {
            // Para PDF, mostrar icono y nombre
            tipoArchivoGasto = 'application/pdf';
            imagenGastoBase64 = e.target.result.split(',')[1]; // Guardar solo el base64 sin el prefijo
            
            document.getElementById('imgPreviewGasto').classList.add('d-none');
            document.getElementById('pdfPreviewGasto').classList.remove('d-none');
            document.getElementById('pdfNameGasto').textContent = file.name;
            document.getElementById('previewFotoGasto').classList.remove('d-none');
        } else {
            // Para imagen, comprimir y mostrar preview
            tipoArchivoGasto = file.type;
            
            const img = document.getElementById('imgPreviewGasto');
            img.src = e.target.result;
            img.classList.remove('d-none');
            document.getElementById('pdfPreviewGasto').classList.add('d-none');
            document.getElementById('previewFotoGasto').classList.remove('d-none');
            
            // Comprimir y almacenar imagen
            comprimirImagenGasto(e.target.result, function(imagenComprimida) {
                imagenGastoBase64 = imagenComprimida.split(',')[1]; // Guardar solo el base64 sin el prefijo
            });
        }
    };
    reader.readAsDataURL(file);
    
    // Limpiar otros inputs de archivo
    limpiarOtrosInputsFotoGasto(input.id);
}

// Limpiar otros inputs de foto
function limpiarOtrosInputsFotoGasto(inputActualId) {
    const inputs = ['fotoGasto', 'fotoGastoCamera', 'fotoGastoGallery'];
    inputs.forEach(id => {
        if (id !== inputActualId) {
            const input = document.getElementById(id);
            if (input) input.value = '';
        }
    });
}

// Eliminar preview de foto o PDF
function eliminarPreviewFotoGasto() {
    ['fotoGasto', 'fotoGastoCamera', 'fotoGastoGallery'].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.value = '';
    });
    
    document.getElementById('previewFotoGasto').classList.add('d-none');
    document.getElementById('imgPreviewGasto').src = '';
    document.getElementById('imgPreviewGasto').classList.add('d-none');
    document.getElementById('pdfPreviewGasto').classList.add('d-none');
    document.getElementById('pdfNameGasto').textContent = '';
    imagenGastoBase64 = null;
    tipoArchivoGasto = null;
}

// Comprimir imagen
function comprimirImagenGasto(imagenBase64, callback) {
    const img = new Image();
    img.onload = function() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        const maxWidth = 800;
        const maxHeight = 600;
        let { width, height } = img;
        
        if (width > height) {
            if (width > maxWidth) {
                height *= maxWidth / width;
                width = maxWidth;
            }
        } else {
            if (height > maxHeight) {
                width *= maxHeight / height;
                height = maxHeight;
            }
        }
        
        canvas.width = width;
        canvas.height = height;
        ctx.drawImage(img, 0, 0, width, height);
        
        const imagenComprimida = canvas.toDataURL('image/jpeg', 0.75);
        callback(imagenComprimida);
    };
    img.src = imagenBase64;
}

// Cargar últimos gastos
async function cargarUltimosGastosAlberto() {
    try {
        const response = await fetch(`controller/gastos_alberto_controller.php?accion=listar&_=${Date.now()}`);
        const result = await response.json();
        
        if (result.success) {
            mostrarListaGastos(result.data);
        } else {
            console.error('Error al cargar gastos:', result.message);
        }
    } catch (error) {
        console.error('Error en cargarUltimosGastosAlberto:', error);
    }
}

// Cargar centros de costo
async function cargarCentrosCosto() {
    try {
        const response = await fetch(`controller/gastos_alberto_controller.php?accion=centros_costo&_=${Date.now()}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            centrosCostoDisponibles = result.data;
            
            // Agregar primera fila de distribución por defecto
            agregarFilaDistribucion();
        } else {
            console.error('Error al cargar centros de costo:', result.message);
        }
    } catch (error) {
        console.error('Error en cargarCentrosCosto:', error);
    }
}

// Agregar fila de distribución
function agregarFilaDistribucion() {
    contadorFilasDistribucion++;
    
    const contenedor = document.getElementById('contenedorDistribucion');
    const filaId = `fila_${contadorFilasDistribucion}`;
    
    const fila = document.createElement('div');
    fila.className = 'fila-distribucion mb-2 p-2 border rounded';
    fila.id = filaId;
    fila.dataset.filaId = contadorFilasDistribucion;
    
    let optionsCentros = '<option value="">Seleccione un centro de costo</option>';
    centrosCostoDisponibles.forEach(centro => {
        optionsCentros += `<option value="${centro.cod_auxiliar}">${centro.centro_costo}</option>`;
    });
    
    fila.innerHTML = `
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <select class="form-select form-select-sm select-centro" required onchange="validarYActualizar()">
                    ${optionsCentros}
                </select>
            </div>
            <div class="col-3 col-md-1">
                <input type="number" class="form-control form-control-sm input-porcentaje text-center" 
                       placeholder="0" min="0.01" max="100" step="0.01" 
                       required onchange="validarYActualizar()" oninput="validarYActualizar()">
            </div>
            <div class="col-1" style="max-width: 40px; padding-left: 0;">
                <span class="text-muted">%</span>
            </div>
            <div class="col-7 col-md-4">
                <span class="text-muted small d-block importe-calculado" style="line-height: 31px;">$0</span>
            </div>
            <div class="col-1 col-md-1 text-center" style="min-width: 40px;">
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="eliminarFilaDistribucion('${filaId}')" 
                        ${contadorFilasDistribucion === 1 ? 'disabled' : ''}
                        style="padding: 0.25rem 0.4rem;"
                        title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    contenedor.appendChild(fila);
    validarYActualizar();
}

// Eliminar fila de distribución
function eliminarFilaDistribucion(filaId) {
    const fila = document.getElementById(filaId);
    if (fila) {
        // No permitir eliminar si solo hay una fila
        const totalFilas = document.querySelectorAll('.fila-distribucion').length;
        if (totalFilas <= 1) {
            mostrarAlerta('Error', 'Debe haber al menos un centro de costo');
            return;
        }
        
        fila.remove();
        validarYActualizar();
    }
}

// Validar y actualizar totales
function validarYActualizar() {
    const importeTotal = parseFloat(document.getElementById('importeGasto').value.replace(/\./g, '').replace(/,/g, '.')) || 0;
    let totalPorcentaje = 0;
    let hayErrores = false;
    let mensajesError = [];
    
    // Calcular total de porcentajes y importes individuales
    const filas = document.querySelectorAll('.fila-distribucion');
    filas.forEach(fila => {
        const porcentaje = parseFloat(fila.querySelector('.input-porcentaje').value) || 0;
        totalPorcentaje += porcentaje;
        
        const importeCalculado = (importeTotal * porcentaje / 100);
        const spanImporte = fila.querySelector('.importe-calculado');
        spanImporte.textContent = '$' + importeCalculado.toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    });
    
    // Actualizar totales en badges
    document.getElementById('totalPorcentaje').textContent = totalPorcentaje.toFixed(2);
    document.getElementById('totalImporteDistribucion').textContent = importeTotal.toLocaleString('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    // Validar suma de porcentajes
    if (Math.abs(totalPorcentaje - 100) > 0.01 && totalPorcentaje > 0) {
        hayErrores = true;
        mensajesError.push(`La suma debe ser 100%. Actual: ${totalPorcentaje.toFixed(2)}%`);
    }
    
    // Validar centros duplicados
    const centrosSeleccionados = [];
    let hayDuplicados = false;
    filas.forEach(fila => {
        const centro = fila.querySelector('.select-centro').value;
        if (centro && centrosSeleccionados.includes(centro)) {
            hayDuplicados = true;
        }
        if (centro) centrosSeleccionados.push(centro);
    });
    
    if (hayDuplicados) {
        hayErrores = true;
        mensajesError.push('No puede seleccionar el mismo centro más de una vez');
    }
    
    // Mostrar/ocultar alertas
    const alertaDiv = document.getElementById('alertaDistribucion');
    const mensajeSpan = document.getElementById('mensajeAlerta');
    
    if (hayErrores) {
        alertaDiv.classList.remove('d-none');
        mensajeSpan.textContent = mensajesError.join('. ');
    } else {
        alertaDiv.classList.add('d-none');
    }
    
    return !hayErrores;
}

// Obtener distribución actual
function obtenerDistribucion() {
    const importeTotal = parseFloat(document.getElementById('importeGasto').value.replace(/\./g, '').replace(/,/g, '.')) || 0;
    const distribucion = [];
    
    const filas = document.querySelectorAll('.fila-distribucion');
    filas.forEach((fila, index) => {
        const centro = fila.querySelector('.select-centro').value;
        const porcentaje = parseFloat(fila.querySelector('.input-porcentaje').value) || 0;
        
        if (centro && porcentaje > 0) {
            let importe = (importeTotal * porcentaje / 100);
            
            // Ajustar último importe para evitar diferencias por redondeo
            if (index === filas.length - 1) {
                const sumaAnteriores = distribucion.reduce((sum, item) => sum + item.importe, 0);
                importe = importeTotal - sumaAnteriores;
            }
            
            distribucion.push({
                centro_costo: centro,
                porcentaje: porcentaje,
                importe: Math.round(importe * 100) / 100  // Redondear a 2 decimales
            });
        }
    });
    
    return distribucion;
}

// Mostrar lista de gastos
function mostrarListaGastos(gastos) {
    const contenedor = document.getElementById('listaGastosAlberto');
    
    if (!gastos || gastos.length === 0) {
        contenedor.innerHTML = '<p class="text-muted">No hay gastos registrados</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    
    // Agrupar gastos por N_COMP para mostrar los distribuidos
    const gastosAgrupados = {};
    gastos.forEach(gasto => {
        const nComp = gasto.N_COMP;
        if (!gastosAgrupados[nComp]) {
            gastosAgrupados[nComp] = {
                gastos: [],
                fecha: gasto.fecha,
                fecha_carga: gasto.fecha_carga,
                tipo_gasto: gasto.tipo_gasto,
                observaciones: gasto.observaciones,
                COD_COMP: gasto.COD_COMP,
                N_COMP: gasto.N_COMP
            };
        }
        gastosAgrupados[nComp].gastos.push(gasto);
    });
    
    // Mostrar solo los últimos 5 gastos agrupados
    const gastosOrdenados = Object.values(gastosAgrupados).slice(0, 5);
    
    gastosOrdenados.forEach((grupo, index) => {
        // Parsear fecha correctamente
        let fechaStr = grupo.fecha;
        if (typeof grupo.fecha === 'object' && grupo.fecha.date) {
            fechaStr = grupo.fecha.date.split(' ')[0];
        }
        const fecha = new Date(fechaStr + 'T00:00:00').toLocaleDateString('es-AR');
        
        // Calcular importe total
        const importeTotal = grupo.gastos.reduce((sum, g) => sum + parseFloat(g.importe), 0);
        const importe = new Intl.NumberFormat('es-AR', { 
            style: 'currency', 
            currency: 'ARS',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(importeTotal);
        
        // Parsear fecha_carga correctamente
        let fechaCarga = 'N/A';
        if (grupo.fecha_carga) {
            let fechaCargaStr = grupo.fecha_carga;
            if (typeof grupo.fecha_carga === 'object' && grupo.fecha_carga.date) {
                fechaCargaStr = grupo.fecha_carga.date;
            }
            fechaCarga = new Date(fechaCargaStr).toLocaleDateString('es-AR');
        }
        
        const idGasto = `gasto-${index}`;
        
        // Construir HTML de distribución expandible
        let distribucionHtml = '';
        if (grupo.gastos.length > 1) {
            distribucionHtml = `
                <div class="mt-2">
                    <span class="badge bg-info text-dark" 
                          style="cursor: pointer;" 
                          onclick="toggleGastoDistribucion('${idGasto}')">
                        <i class="bi bi-chevron-right" id="icono-${idGasto}"></i>
                        ${grupo.gastos.length} centros de costo
                    </span>
                </div>
                <div id="detalle-${idGasto}" style="display: none;" class="mt-2">
                    <div class="bg-light rounded p-2">
            `;
            
            grupo.gastos.forEach(gasto => {
                const importeGasto = new Intl.NumberFormat('es-AR', { 
                    style: 'currency', 
                    currency: 'ARS',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(gasto.importe);
                
                const porcentaje = ((parseFloat(gasto.importe) / importeTotal) * 100).toFixed(1);
                const nombreCentro = gasto.centro_costo_nombre || gasto.centro_costo || 'Sin centro';
                
                distribucionHtml += `
                    <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded border mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-return-right text-primary"></i>
                            <small><strong>${nombreCentro}</strong></small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-secondary me-1">${porcentaje}%</span>
                            <small class="text-warning"><strong>${importeGasto}</strong></small>
                        </div>
                    </div>
                `;
            });
            
            distribucionHtml += `
                    </div>
                </div>
            `;
        } else if (grupo.gastos.length === 1 && grupo.gastos[0].centro_costo) {
            const nombreCentro = grupo.gastos[0].centro_costo_nombre || grupo.gastos[0].centro_costo;
            distribucionHtml = `<div class="mt-1"><small class="text-muted"><i class="bi bi-pin-angle"></i> ${nombreCentro}</small></div>`;
        }
        
        html += `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1 text-warning">${importe}</h6>
                    <small class="text-muted">Cargado: ${fechaCarga}</small>
                </div>
                <div class="mb-1">
                    <small class="text-primary">Fecha: ${fecha}</small>
                </div>
                <p class="mb-1"><strong>${grupo.tipo_gasto || 'Sin tipo'}</strong></p>
                <p class="mb-1"><small>${grupo.observaciones || 'Sin observaciones'}</small></p>
                ${distribucionHtml}
                <small class="text-muted">Comp: ${grupo.COD_COMP}${grupo.N_COMP}</small>
            </div>
        `;
    });
    
    html += '</div>';
    contenedor.innerHTML = html;
}

// Formatear número con separador de miles
function formatearImporte(input) {
    let valor = input.value.replace(/[^\d]/g, '');
    if (valor) {
        valor = parseInt(valor).toLocaleString('es-AR');
    }
    input.value = valor;
}

// Aplicar formato a campo de importe
document.getElementById('importeGasto')?.addEventListener('input', function() {
    formatearImporte(this);
    validarYActualizar();  // Actualizar cálculos cuando cambia el importe
});

// Procesar formulario de gasto
document.getElementById('formGastoAlberto')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const tipoGasto = document.getElementById('tipoGastoAlberto').value;
    if (!tipoGasto) {
        mostrarAlerta('Error', 'Debe seleccionar un tipo de gasto');
        return;
    }
    
    // Validar importe antes de continuar
    const importeInput = document.getElementById('importeGasto');
    const importeValor = importeInput.value.trim();
    
    if (!importeValor || importeValor === '' || importeValor === '0') {
        mostrarAlerta('Error', 'Debe ingresar un importe válido');
        importeInput.focus();
        return;
    }
    
    // Parsear y validar que sea un número positivo
    const importeNumerico = parseFloat(importeValor.replace(/\./g, '').replace(/,/g, '.'));
    if (isNaN(importeNumerico) || importeNumerico <= 0) {
        mostrarAlerta('Error', 'El importe debe ser un número mayor a cero');
        importeInput.focus();
        return;
    }
    
    // Validar distribución
    const distribucion = obtenerDistribucion();
    
    if (distribucion.length === 0) {
        mostrarAlerta('Error', 'Debe agregar al menos un centro de costo');
        return;
    }
    
    // Validar que la suma sea 100%
    const totalPorcentaje = distribucion.reduce((sum, item) => sum + item.porcentaje, 0);
    if (Math.abs(totalPorcentaje - 100) > 0.01) {
        mostrarAlerta('Error', `La suma de porcentajes debe ser 100%. Actual: ${totalPorcentaje.toFixed(2)}%`);
        return;
    }
    
    // Validar centros únicos
    const centros = distribucion.map(d => d.centro_costo);
    const centrosUnicos = new Set(centros);
    if (centros.length !== centrosUnicos.size) {
        mostrarAlerta('Error', 'No puede seleccionar el mismo centro de costo más de una vez');
        return;
    }
    
    const formData = new FormData();
    formData.append('fecha', document.getElementById('fechaGasto').value);
    formData.append('tipo_gasto', tipoGasto);
    formData.append('importe', document.getElementById('importeGasto').value.replace(/\./g, ''));
    formData.append('observaciones', document.getElementById('observacionesGasto').value);
    
    // Agregar checkbox es_factura
    const esFactura = document.getElementById('es_factura').checked ? 1 : 0;
    formData.append('es_factura', esFactura);
    
    // Agregar distribución como JSON
    formData.append('distribucion', JSON.stringify(distribucion));
    
    // Debug: mostrar lo que se va a enviar
    console.log('=== DATOS A ENVIAR ===');
    console.log('Fecha:', document.getElementById('fechaGasto').value);
    console.log('Tipo Gasto:', tipoGasto);
    console.log('Importe (raw):', document.getElementById('importeGasto').value);
    console.log('Importe (limpio):', document.getElementById('importeGasto').value.replace(/\./g, ''));
    console.log('Distribución:', JSON.stringify(distribucion));
    console.log('Es Factura:', esFactura);
    
    // Agregar archivo (foto o PDF) si existe
    if (imagenGastoBase64) {
        formData.append('foto', imagenGastoBase64);
        formData.append('tipo_archivo', tipoArchivoGasto || 'image/jpeg');
        console.log('Archivo adjunto:', tipoArchivoGasto, 'Tamaño base64:', imagenGastoBase64.length);
    }
    
    try {
        console.log('Enviando solicitud...');
        const response = await fetch(`controller/gastos_alberto_controller.php?accion=crear&_=${Date.now()}`, {
            method: 'POST',
            body: formData
        });
        
        console.log('Respuesta recibida. Status:', response.status, response.statusText);
        
        // Intentar parsear como JSON
        let result;
        try {
            const responseText = await response.text();
            console.log('Respuesta texto:', responseText);
            result = JSON.parse(responseText);
            console.log('Respuesta JSON:', result);
        } catch (jsonError) {
            console.error('Error al parsear JSON:', jsonError);
            const textoRespuesta = await response.text();
            console.error('Respuesta como texto:', textoRespuesta);
            throw new Error('Respuesta inválida del servidor');
        }
        
        if (result.success) {
            mostrarAlerta('Éxito', result.message);
            
            // Limpiar formulario
            document.getElementById('formGastoAlberto').reset();
            document.getElementById('fechaGasto').value = new Date().toISOString().split('T')[0];
            eliminarPreviewFotoGasto();
            
            // Limpiar distribución y agregar una fila nueva
            document.getElementById('contenedorDistribucion').innerHTML = '';
            contadorFilasDistribucion = 0;
            agregarFilaDistribucion();
            
            // Recargar lista
            cargarUltimosGastosAlberto();
        } else {
            console.error('Error del servidor:', result.message);
            mostrarAlerta('Error', result.message || 'No se pudo registrar el gasto');
        }
    } catch (error) {
        console.error('Error al guardar gasto:', error);
        mostrarAlerta('Error', 'No se pudo guardar el gasto. Error: ' + error.message);
    }
});

// Cargar gastos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarUltimosGastosAlberto();
    cargarCentrosCosto();
});

/**
 * Toggle para expandir/colapsar distribución de gastos
 */
function toggleGastoDistribucion(gastoId) {
    const detalleDiv = document.getElementById(`detalle-${gastoId}`);
    const icono = document.getElementById(`icono-${gastoId}`);
    
    if (detalleDiv.style.display === 'none') {
        detalleDiv.style.display = 'block';
        icono.classList.remove('bi-chevron-right');
        icono.classList.add('bi-chevron-down');
    } else {
        detalleDiv.style.display = 'none';
        icono.classList.remove('bi-chevron-down');
        icono.classList.add('bi-chevron-right');
    }
}

// Exponer funciones al scope global
window.agregarFilaDistribucion = agregarFilaDistribucion;
window.eliminarFilaDistribucion = eliminarFilaDistribucion;
window.validarYActualizar = validarYActualizar;
window.toggleGastoDistribucion = toggleGastoDistribucion;
