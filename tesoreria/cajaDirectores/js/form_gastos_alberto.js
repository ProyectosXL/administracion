/**
 * Script para Formulario de Gastos Alberto
 */

// Variable global para almacenar imagen comprimida
let imagenGastoBase64 = null;

// Detectar si estamos en dispositivo móvil
function esMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Previsualizar foto seleccionada
function previsualizarFotoGasto(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validar tipo de archivo
    const formatosValidos = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
        'image/bmp', 'image/webp', 'image/tiff', 'image/heic', 'image/heif'
    ];
    
    if (!formatosValidos.includes(file.type) && !file.type.match('image.*')) {
        const mensaje = esMobile() ? 
            'Por favor toma una foto o selecciona una imagen válida de tu galería.' :
            'Por favor selecciona una imagen válida.';
        mostrarAlerta('Error', mensaje);
        input.value = '';
        return;
    }
    
    // Validar tamaño
    const maxSize = esMobile() ? 15 * 1024 * 1024 : 10 * 1024 * 1024;
    if (file.size > maxSize) {
        const maxSizeMB = Math.round(maxSize / (1024 * 1024));
        mostrarAlerta('Error', `La imagen es demasiado grande. Máximo ${maxSizeMB}MB permitido.`);
        input.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('imgPreviewGasto');
        img.src = e.target.result;
        document.getElementById('previewFotoGasto').classList.remove('d-none');
        
        // Comprimir y almacenar imagen
        comprimirImagenGasto(e.target.result, function(imagenComprimida) {
            imagenGastoBase64 = imagenComprimida;
        });
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

// Eliminar preview de foto
function eliminarPreviewFotoGasto() {
    ['fotoGasto', 'fotoGastoCamera', 'fotoGastoGallery'].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.value = '';
    });
    
    document.getElementById('previewFotoGasto').classList.add('d-none');
    document.getElementById('imgPreviewGasto').src = '';
    imagenGastoBase64 = null;
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
            const select = document.getElementById('centroCostoAlberto');
            select.innerHTML = '<option value="">Seleccione un centro de costo</option>';
            
            result.data.forEach(centro => {
                const option = document.createElement('option');
                option.value = centro.cod_auxiliar;
                option.textContent = centro.centro_costo;
                select.appendChild(option);
            });
        } else {
            console.error('Error al cargar centros de costo:', result.message);
        }
    } catch (error) {
        console.error('Error en cargarCentrosCosto:', error);
    }
}

// Mostrar lista de gastos
function mostrarListaGastos(gastos) {
    const contenedor = document.getElementById('listaGastosAlberto');
    
    if (!gastos || gastos.length === 0) {
        contenedor.innerHTML = '<p class="text-muted">No hay gastos registrados</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    
    // Mostrar solo los últimos 5 gastos
    gastos.slice(0, 5).forEach(gasto => {
        // Parsear fecha correctamente (puede venir como objeto o string)
        let fechaStr = gasto.fecha;
        if (typeof gasto.fecha === 'object' && gasto.fecha.date) {
            fechaStr = gasto.fecha.date.split(' ')[0]; // Extraer solo la fecha del datetime
        }
        const fecha = new Date(fechaStr + 'T00:00:00').toLocaleDateString('es-AR');
        
        const importe = new Intl.NumberFormat('es-AR', { 
            style: 'currency', 
            currency: 'ARS',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(gasto.importe);
        
        // Parsear fecha_carga correctamente
        let fechaCarga = 'N/A';
        if (gasto.fecha_carga) {
            let fechaCargaStr = gasto.fecha_carga;
            if (typeof gasto.fecha_carga === 'object' && gasto.fecha_carga.date) {
                fechaCargaStr = gasto.fecha_carga.date;
            }
            fechaCarga = new Date(fechaCargaStr).toLocaleDateString('es-AR');
        }
        
        html += `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1 text-warning">${importe}</h6>
                    <small class="text-muted">Fecha de Carga: ${fechaCarga}</small>
                </div>
                <div class="mb-1">
                    <small class="text-primary">Fecha: ${fecha}</small>
                </div>
                <p class="mb-1"><strong>${gasto.tipo_gasto || 'Sin tipo'}</strong></p>
                <p class="mb-1"><small>${gasto.observaciones || 'Sin observaciones'}</small></p>
                <small>Comp: ${gasto.COD_COMP}${gasto.N_COMP}</small>
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
});

// Procesar formulario de gasto
document.getElementById('formGastoAlberto')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const tipoGasto = document.getElementById('tipoGastoAlberto').value;
    if (!tipoGasto) {
        mostrarAlerta('Error', 'Debe seleccionar un tipo de gasto');
        return;
    }
    
    const centroCosto = document.getElementById('centroCostoAlberto').value;
    if (!centroCosto) {
        mostrarAlerta('Error', 'Debe seleccionar un centro de costo');
        return;
    }
    
    const formData = new FormData();
    formData.append('fecha', document.getElementById('fechaGasto').value);
    formData.append('tipo_gasto', tipoGasto);
    formData.append('centro_costo', centroCosto);
    formData.append('importe', document.getElementById('importeGasto').value.replace(/\./g, ''));
    formData.append('observaciones', document.getElementById('observacionesGasto').value);
    
    // Agregar foto si existe
    if (imagenGastoBase64) {
        formData.append('foto', imagenGastoBase64);
    }
    
    try {
        const response = await fetch(`controller/gastos_alberto_controller.php?accion=crear&_=${Date.now()}`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarAlerta('Éxito', result.message);
            
            // Limpiar formulario
            document.getElementById('formGastoAlberto').reset();
            document.getElementById('fechaGasto').value = new Date().toISOString().split('T')[0];
            eliminarPreviewFotoGasto();
            
            // Recargar lista
            cargarUltimosGastosAlberto();
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error al guardar gasto:', error);
        mostrarAlerta('Error', 'No se pudo guardar el gasto');
    }
});

// Cargar gastos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarUltimosGastosAlberto();
    cargarCentrosCosto();
});
