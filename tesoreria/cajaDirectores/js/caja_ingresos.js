/**
 * Gestión de Ingresos de Caja
 */

// Formatear importe con separador de miles
function formatearImporte(input) {
    let valor = input.value.replace(/\D/g, '');
    valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = valor;
}

// Aplicar formato a todos los campos de importe
document.querySelectorAll('.importe-input').forEach(input => {
    input.addEventListener('input', function() {
        formatearImporte(this);
    });
});

// Convertir importe formateado a número
function limpiarImporte(importeFormateado) {
    return parseFloat(importeFormateado.replace(/\./g, '')) || 0;
}

// Cargar lista de ingresos
async function cargarIngresos() {
    try {
        const response = await fetch('controller/caja_ingresos_controller.php?accion=listar');
        const result = await response.json();
        
        if (result.success) {
            mostrarListaIngresos(result.data);
        } else {
            console.error('Error al cargar ingresos:', result.message);
        }
    } catch (error) {
        console.error('Error en cargarIngresos:', error);
    }
}

// Mostrar lista de ingresos
function mostrarListaIngresos(ingresos) {
    const contenedor = document.getElementById('listaIngresos');
    
    if (!ingresos || ingresos.length === 0) {
        contenedor.innerHTML = '<p class="text-muted">No hay ingresos registrados</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    
    // Mostrar solo los últimos 5 ingresos
    ingresos.slice(0, 5).forEach(ingreso => {
        // Usar fecha_solo si está disponible, sino fecha
        const fechaMostrar = ingreso.fecha_solo || ingreso.fecha;
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
        }).format(ingreso.importe);
        
        const recibidoBadge = ingreso.recibido == 1 
            ? '<span class="badge bg-success">Recibido</span>' 
            : `<button class="btn btn-sm btn-outline-success" onclick="marcarRecibido(${ingreso.id})">Marcar recibido</button>`;
        
        // Obtener fecha del movimiento (campo fecha)
        const fechaMovimiento = ingreso.fecha ? new Date(ingreso.fecha + 'T00:00:00').toLocaleDateString('es-AR') : 'N/A';
        
        html += `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${importe}</h6>
                    <small class="text-muted">Fecha de Carga: ${fecha}</small>
                </div>
                <div class="mb-1">
                    <small class="text-primary">Fecha: ${fechaMovimiento}</small>
                </div>
                <p class="mb-1"><small>${ingreso.observaciones || 'Sin observaciones'}</small></p>
                <small>Comp: ${ingreso.COD_COMP}${ingreso.N_COMP} ${recibidoBadge}</small>
            </div>
        `;
    });
    
    html += '</div>';
    contenedor.innerHTML = html;
}

// Marcar ingreso como recibido
async function marcarRecibido(id) {
    try {
        const formData = new FormData();
        formData.append('accion', 'marcar_recibido');
        formData.append('id', id);
        
        const response = await fetch('controller/caja_ingresos_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarAlerta('Éxito', result.message);
            cargarIngresos();
            actualizarResumen();
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error en marcarRecibido:', error);
        mostrarAlerta('Error', 'No se pudo procesar la solicitud');
    }
}

// Procesar formulario de ingreso
document.getElementById('formIngreso')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('accion', 'crear');
    formData.append('fecha', document.getElementById('fechaIngreso').value);
    formData.append('importe', document.getElementById('importeIngreso').value);
    formData.append('observaciones', document.getElementById('observacionesIngreso').value);
    
    try {
        const response = await fetch('controller/caja_ingresos_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarAlerta('Éxito', result.message);
            this.reset();
            document.getElementById('fechaIngreso').value = new Date().toISOString().split('T')[0];
            cargarIngresos();
            actualizarResumen();
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error al crear ingreso:', error);
        mostrarAlerta('Error', 'No se pudo registrar el ingreso');
    }
});

// Mostrar alerta
function mostrarAlerta(titulo, mensaje) {
    // Asegurar que no hay otros modales abiertos
    const existingBackdrop = document.querySelector('.modal-backdrop');
    if (existingBackdrop) {
        existingBackdrop.remove();
    }
    
    // Limpiar clases del body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    const modalElement = document.getElementById('modalAlerta');
    if (modalElement) {
        // Limpiar estado anterior del modal
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        
        // Actualizar contenido
        document.getElementById('modalAlertaTitulo').textContent = titulo;
        document.getElementById('modalAlertaMensaje').textContent = mensaje;
        
        // Crear nueva instancia y mostrar
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        // Limpiar al cerrar
        modalElement.addEventListener('hidden.bs.modal', function() {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }, { once: true });
        
        modal.show();
    }
}

// Cargar ingresos al cambiar a la pestaña
document.getElementById('ingresos-tab')?.addEventListener('shown.bs.tab', function() {
    cargarIngresos();
});

// Cargar al inicio ya que ingresos es la pestaña por defecto
document.addEventListener('DOMContentLoaded', function() {
    // Cargar ingresos ya que es la vista por defecto
    cargarIngresos();
});