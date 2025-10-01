/**
 * Gestión de Egresos de Caja
 */

// Cargar lista de directores
async function cargarDirectores() {
    try {
        const response = await fetch('controller/caja_egresos_controller.php?accion=directores');
        const result = await response.json();
        
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
        const response = await fetch('controller/caja_egresos_controller.php?accion=listar');
        const result = await response.json();
        
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
        const response = await fetch('controller/caja_egresos_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarAlerta('Éxito', result.message);
            this.reset();
            document.getElementById('fechaEgreso').value = new Date().toISOString().split('T')[0];
            document.getElementById('divDirector').classList.add('d-none');
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