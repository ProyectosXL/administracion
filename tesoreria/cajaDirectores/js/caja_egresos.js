/**
 * Gestión de Egresos de Caja
 */

// Variable global para almacenar imagen comprimida
let imagenEgresoBase64 = null;

// Detectar si estamos en dispositivo móvil
function esMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Previsualizar foto seleccionada
function previsualizarFoto(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validar tipo de archivo (aceptar todos los formatos de imagen comunes)
    const formatosValidos = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
        'image/bmp', 'image/webp', 'image/tiff', 'image/heic', 'image/heif'
    ];
    
    if (!formatosValidos.includes(file.type) && !file.type.match('image.*')) {
        const mensaje = esMobile() ? 
            'Por favor toma una foto o selecciona una imagen válida de tu galería.' :
            'Por favor selecciona una imagen válida. Formatos soportados: JPG, PNG, GIF, BMP, WebP, TIFF, HEIC, HEIF';
        mostrarAlerta('Error', mensaje);
        input.value = '';
        return;
    }
    
    // Validar tamaño (máximo 10MB para permitir fotos de alta calidad de móviles)
    const maxSize = esMobile() ? 15 * 1024 * 1024 : 10 * 1024 * 1024; // 15MB en móvil, 10MB en escritorio
    if (file.size > maxSize) {
        const maxSizeMB = Math.round(maxSize / (1024 * 1024));
        mostrarAlerta('Error', `La imagen es demasiado grande. Máximo ${maxSizeMB}MB permitido.`);
        input.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('imgPreviewEgreso');
        img.src = e.target.result;
        document.getElementById('previewFotoEgreso').classList.remove('d-none');
        
        // Comprimir y almacenar imagen
        comprimirImagen(e.target.result, function(imagenComprimida) {
            imagenEgresoBase64 = imagenComprimida;
            
            // Mostrar mensaje de éxito en móvil
            if (esMobile()) {
                const fileKB = Math.round(file.size / 1024);
                console.log(`Foto procesada: ${file.name} (${fileKB}KB)`);
            }
        });
    };
    reader.readAsDataURL(file);
    
    // Limpiar otros inputs de archivo para evitar duplicados
    limpiarOtrosInputsFoto(input.id);
}

// Limpiar otros inputs de foto cuando se selecciona uno
function limpiarOtrosInputsFoto(inputActualId) {
    const inputs = ['fotoEgreso', 'fotoEgresoCamera', 'fotoEgresoGallery'];
    inputs.forEach(id => {
        if (id !== inputActualId) {
            const input = document.getElementById(id);
            if (input) input.value = '';
        }
    });
}

// Eliminar preview de foto
function eliminarPreviewFoto() {
    // Limpiar todos los inputs de foto
    ['fotoEgreso', 'fotoEgresoCamera', 'fotoEgresoGallery'].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.value = '';
    });
    
    document.getElementById('previewFotoEgreso').classList.add('d-none');
    document.getElementById('imgPreviewEgreso').src = '';
    imagenEgresoBase64 = null;
}

// Comprimir imagen (función auxiliar)
function comprimirImagen(imagenBase64, callback) {
    const img = new Image();
    img.onload = function() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Calcular nuevas dimensiones
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
        
        // Dibujar imagen redimensionada
        ctx.drawImage(img, 0, 0, width, height);
        
        // Convertir a base64 con compresión
        const imagenComprimida = canvas.toDataURL('image/jpeg', 0.75);
        callback(imagenComprimida);
    };
    img.src = imagenBase64;
}

// Cargar lista de directores
async function cargarDirectores() {
    try {
        const response = await fetch(`controller/caja_egresos_controller.php?accion=directores&_=${Date.now()}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (jsonError) {
            console.error('Respuesta no es JSON válido:', text);
            throw new Error('Respuesta del servidor no es JSON válido');
        }
        
        if (result.success) {
            const select = document.getElementById('nombreDirector');
            select.innerHTML = '<option value="">Seleccione un director</option>';
            
            result.data.forEach(director => {
                const option = document.createElement('option');
                option.value = director;
                option.textContent = director;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error al cargar directores:', error);
        // No mostrar alerta para no molestar al usuario constantemente
    }
}

// Mostrar/ocultar selector de director según motivo
document.getElementById('motivoEgreso')?.addEventListener('change', function() {
    const divDirector = document.getElementById('divDirector');
    const selectDirector = document.getElementById('nombreDirector');
    
    if (this.value === 'RETIROS') {
        divDirector.classList.remove('d-none');
        selectDirector.required = true;
    } else {
        divDirector.classList.add('d-none');
        selectDirector.required = false;
        selectDirector.value = '';
    }
});

// Cargar lista de egresos
async function cargarEgresos() {
    try {
        const response = await fetch(`controller/caja_egresos_controller.php?accion=listar&_=${Date.now()}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (jsonError) {
            console.error('Respuesta no es JSON válido:', text);
            throw new Error('Respuesta del servidor no es JSON válido');
        }
        
        if (result.success) {
            mostrarListaEgresos(result.data);
        } else {
            console.error('Error al cargar egresos:', result.message);
        }
    } catch (error) {
        console.error('Error en cargarEgresos:', error);
    }
}

// Mostrar lista de egresos
function mostrarListaEgresos(egresos) {
    const contenedor = document.getElementById('listaEgresos');
    
    if (!egresos || egresos.length === 0) {
        contenedor.innerHTML = '<p class="text-muted">No hay egresos registrados</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    
    // Mostrar solo los últimos 5 egresos
    egresos.slice(0, 5).forEach(egreso => {
        // Usar directamente el campo fecha del movimiento
        const fechaMostrar = egreso.fecha;
        let fecha;
        
        if (fechaMostrar && fechaMostrar !== '0000-00-00') {
            fecha = new Date(fechaMostrar + 'T00:00:00').toLocaleDateString('es-AR');
        } else {
            fecha = 'Fecha no disponible';
        }
        
        const importe = new Intl.NumberFormat('es-AR', { 
            style: 'currency', 
            currency: 'ARS',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(egreso.importe);
        
        const motivo = egreso.motivo.charAt(0) + egreso.motivo.slice(1).toLowerCase();
        let concepto = motivo;
        
        if (egreso.motivo === 'RETIROS' && egreso.nombre_director) {
            concepto += ` - ${egreso.nombre_director}`;
        }
        
        // Obtener fecha de carga para aclaración
        const fechaCarga = egreso.fecha_carga ? new Date(egreso.fecha_carga).toLocaleDateString('es-AR') : 'N/A';
        
        html += `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1 text-danger">${importe}</h6>
                    <small class="text-muted">Fecha de Carga: ${fechaCarga}</small>
                </div>
                <div class="mb-1">
                    <small class="text-primary">Fecha: ${fecha}</small>
                </div>
                <p class="mb-1"><strong>${concepto}</strong></p>
                <p class="mb-1"><small>${egreso.observaciones || 'Sin observaciones'}</small></p>
                <small>Comp: ${egreso.COD_COMP}${egreso.N_COMP}</small>
            </div>
        `;
    });
    
    html += '</div>';
    contenedor.innerHTML = html;
}

// Procesar formulario de egreso
document.getElementById('formEgreso')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('accion', 'crear');
    formData.append('fecha', document.getElementById('fechaEgreso').value);
    formData.append('motivo', document.getElementById('motivoEgreso').value);
    formData.append('importe', document.getElementById('importeEgreso').value);
    formData.append('observaciones', document.getElementById('observacionesEgreso').value);
    
    // Agregar foto si existe
    if (imagenEgresoBase64) {
        formData.append('foto', imagenEgresoBase64);
    }
    
    const motivo = document.getElementById('motivoEgreso').value;
    if (motivo === 'RETIROS') {
        const director = document.getElementById('nombreDirector').value;
        if (!director) {
            mostrarAlerta('Error', 'Debe seleccionar un director para retiros de socios');
            return;
        }
        formData.append('nombre_director', director);
    }
    
    try {
        const response = await fetch(`controller/caja_egresos_controller.php?_=${Date.now()}`, {
            method: 'POST',
            body: formData,
            headers: {
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (jsonError) {
            console.error('Respuesta no es JSON válido:', text);
            throw new Error('Respuesta del servidor no es JSON válido: ' + text.substring(0, 200));
        }
        
        if (result.success) {
            mostrarAlerta('Éxito', result.message);
            this.reset();
            document.getElementById('fechaEgreso').value = new Date().toISOString().split('T')[0];
            document.getElementById('divDirector').classList.add('d-none');
            
            // Limpiar foto
            eliminarPreviewFoto();
            
            cargarEgresos();
            actualizarResumen();
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error al crear egreso:', error);
        mostrarAlerta('Error', 'No se pudo registrar el egreso');
    }
});

// Cargar egresos al cambiar a la pestaña
document.getElementById('egresos-tab')?.addEventListener('shown.bs.tab', function() {
    cargarEgresos();
    cargarDirectores();
});