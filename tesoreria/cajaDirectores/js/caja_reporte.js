/**
 * Reporte de Saldo de Caja
 */

// Actualizar resumen de caja (tarjetas superiores)
async function actualizarResumen() {
    try {
        const response = await fetch('controller/caja_reporte_controller.php?accion=saldo');
        const result = await response.json();
        
        console.log('Datos del saldo recibidos:', result.data);
        
        if (result.success) {
            const formatoMoneda = new Intl.NumberFormat('es-AR', { 
                style: 'currency', 
                currency: 'ARS',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
            
            document.getElementById('totalIngresos').textContent = 
                formatoMoneda.format(result.data.total_ingresos);
            
            document.getElementById('totalEgresos').textContent = 
                formatoMoneda.format(result.data.total_egresos);
            
            const saldo = result.data.saldo;
            console.log('Saldo calculado:', saldo);
            console.log('Ingresos:', result.data.total_ingresos);
            console.log('Egresos:', result.data.total_egresos);
            
            const elementoSaldo = document.getElementById('saldoActual');
            elementoSaldo.textContent = formatoMoneda.format(saldo);
            
            // Cambiar color según saldo positivo o negativo
            const cardSaldo = elementoSaldo.closest('.card');
            if (saldo < 0) {
                cardSaldo.classList.remove('bg-primary');
                cardSaldo.classList.add('bg-warning');
                cardSaldo.querySelector('.card-text').textContent = 'Déficit en caja';
            } else {
                cardSaldo.classList.remove('bg-warning');
                cardSaldo.classList.add('bg-primary');
                cardSaldo.querySelector('.card-text').textContent = 'Efectivo disponible';
            }
        }
    } catch (error) {
        console.error('Error al actualizar resumen:', error);
    }
}

// Cargar movimientos para el reporte
async function cargarReporte(filtros = {}) {
    try {
        // Si no se proporcionan fechas, usar el mes actual
        if (!filtros.fecha_desde || !filtros.fecha_hasta) {
            const hoy = new Date();
            const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            
            filtros.fecha_desde = primerDia.toISOString().split('T')[0];
            filtros.fecha_hasta = ultimoDia.toISOString().split('T')[0];
        }
        
        let url = 'controller/caja_reporte_controller.php?accion=movimientos';
        url += `&fecha_desde=${filtros.fecha_desde}`;
        url += `&fecha_hasta=${filtros.fecha_hasta}`;
        
        console.log('Cargando reporte desde URL:', url);
        
        const response = await fetch(url);
        const result = await response.json();
        
        console.log('Respuesta del reporte:', result);
        
        if (result.success) {
            mostrarReporte(result.data);
        } else {
            console.error('Error al cargar reporte:', result.message);
            document.getElementById('contenidoReporte').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Error: ${result.message}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error en cargarReporte:', error);
        document.getElementById('contenidoReporte').innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> Error de conexión. Verifique la conexión al servidor.
            </div>
        `;
    }
}

// Mostrar tabla de reporte
function mostrarReporte(movimientos) {
    const contenedor = document.getElementById('contenidoReporte');
    
    console.log('Mostrando reporte con movimientos:', movimientos);
    
    if (!movimientos || movimientos.length === 0) {
        contenedor.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No hay movimientos registrados para mostrar en el reporte.
                <br><small>Asegúrese de haber registrado ingresos y/o egresos.</small>
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
    
    let html = `
        <div class="mb-3">

            <small class="text-muted ms-3">Total de movimientos: ${movimientos.length}</small>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>COMP.</th>
                        <th>Concepto</th>
                        <th class="text-end">Importe</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Origen</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    let saldoAcumulado = 0;
    
    movimientos.forEach(mov => {
        // Usar directamente el campo fecha del movimiento
        const fecha = new Date(mov.fecha + 'T00:00:00').toLocaleDateString('es-AR');
        const importe = formatoMoneda.format(mov.importe);
        
        const tipoClass = mov.tipo === 'INGRESO' ? 'text-success' : 'text-danger';
        const tipoIcon = mov.tipo === 'INGRESO' ? 'arrow-down-circle' : 'arrow-up-circle';
        
        let estadoBadge = '';
        let accionBoton = '';
        let origenBadge = '';
        
        // Configurar badge de origen
        switch(mov.origen) {
            case 'MANUAL':
                origenBadge = '<span class="badge bg-primary">Manual</span>';
                break;
            case '599':
                origenBadge = '<span class="badge bg-info">599</span>';
                break;
            case 'TESORERIA':
                origenBadge = '<span class="badge bg-secondary">Tesorería</span>';
                break;
            default:
                origenBadge = '<span class="badge bg-light text-dark">Manual</span>';
        }
        
        if (mov.tipo === 'INGRESO') {
            if (mov.recibido == 1) {
                estadoBadge = '<span class="badge bg-success">Recibido</span>';
                saldoAcumulado += parseFloat(mov.importe);
            } else {
                estadoBadge = '<span class="badge bg-warning">Pendiente</span>';
                
                // Botón de acción según el origen
                if (mov.origen === '599') {
                    accionBoton = ''; // Fuente 599 viene RECIBIDA, sin botón
                } else if (mov.origen === 'TESORERIA') {
                    // TESORERÍA viene PENDIENTE, necesita botón para marcar como recibido
                    const idTesoreria = mov.id.replace('EXT_TES_', '');
                    accionBoton = `
                        <button class="btn btn-sm btn-success" onclick="marcarRecibidoTesoreria('${idTesoreria}', '${mov.fecha}', '${mov.concepto}', ${mov.importe})">
                            <i class="bi bi-check"></i>
                        </button>
                    `;
                } else {
                    // MANUAL con botón normal
                    accionBoton = `
                        <button class="btn btn-sm btn-success" onclick="marcarRecibidoDesdeReporte('${mov.id}')">
                            <i class="bi bi-check"></i>
                        </button>
                    `;
                }
            }
        } else {
            estadoBadge = '<span class="badge bg-success">Pagado</span>';
            saldoAcumulado -= parseFloat(mov.importe);
            origenBadge = '<span class="badge bg-primary">Manual</span>'; // Egresos siempre manuales
        }
        
        // Mostrar COMP solo si no está vacío
        const compDisplay = (mov.cod_comp && mov.n_comp) ? `${mov.cod_comp}${mov.n_comp}` : '-';
        
        html += `
            <tr>
                <td>${fecha}</td>
                <td><i class="bi bi-${tipoIcon} ${tipoClass}"></i> ${mov.tipo}</td>
                <td><small>${compDisplay}</small></td>
                <td>${mov.concepto}</td>
                <td class="text-end ${tipoClass}"><strong>${importe}</strong></td>
                <td class="text-center">${estadoBadge}</td>
                <td class="text-center">${origenBadge}</td>
                <td class="text-center">${accionBoton}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end"><strong>Saldo Calculado:</strong></td>
                        <td class="text-end"><strong>${formatoMoneda.format(saldoAcumulado)}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
    
    contenedor.innerHTML = html;
}

// Marcar ingreso como recibido desde el reporte
async function marcarRecibidoDesdeReporte(id) {
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
            cargarReporte(); // Recargar tabla
            actualizarResumen(); // Actualizar resumen
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo procesar la solicitud');
    }
}

// Marcar ingreso 599 como recibido
async function marcarRecibido599(idSba05, fecha, codComp, nComp, concepto, importe) {
    try {
        const formData = new FormData();
        formData.append('accion', 'marcar_recibido_599');
        formData.append('id_sba05', idSba05);
        formData.append('fecha', fecha);
        formData.append('cod_comp', codComp);
        formData.append('n_comp', nComp);
        formData.append('observaciones', concepto);
        formData.append('importe', importe);
        
        const response = await fetch('controller/caja_ingresos_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarAlerta('Éxito', result.message);
            
            // Recargar usando las fechas de los filtros si están disponibles
            const fechaDesde = document.getElementById('fechaReporteDesde')?.value;
            const fechaHasta = document.getElementById('fechaReporteHasta')?.value;
            
            if (fechaDesde && fechaHasta) {
                cargarReporte({
                    fecha_desde: fechaDesde,
                    fecha_hasta: fechaHasta
                });
            } else {
                cargarReporte();
            }
            
            actualizarResumen(); // Actualizar resumen
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo procesar la solicitud');
    }
}

// Marcar ingreso de TESORERÍA como recibido
async function marcarRecibidoTesoreria(idTesoreria, fecha, concepto, importe) {
    try {
        const formData = new FormData();
        formData.append('accion', 'marcar_recibido_tesoreria');
        formData.append('id_tesoreria', idTesoreria);
        formData.append('fecha', fecha);
        formData.append('observaciones', concepto);
        formData.append('importe', importe);
        
        const response = await fetch('controller/caja_ingresos_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarAlerta('Éxito', result.message);
            
            // Recargar usando las fechas de los filtros si están disponibles
            const fechaDesde = document.getElementById('fechaReporteDesde')?.value;
            const fechaHasta = document.getElementById('fechaReporteHasta')?.value;
            
            if (fechaDesde && fechaHasta) {
                cargarReporte({
                    fecha_desde: fechaDesde,
                    fecha_hasta: fechaHasta
                });
            } else {
                cargarReporte();
            }
            
            actualizarResumen(); // Actualizar resumen
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo procesar la solicitud');
    }
}

async function marcarRecibidoDesdeReporte(id) {
    await marcarRecibido(id);
    
    // Recargar usando las fechas de los filtros si están disponibles
    const fechaDesde = document.getElementById('fechaReporteDesde')?.value;
    const fechaHasta = document.getElementById('fechaReporteHasta')?.value;
    
    if (fechaDesde && fechaHasta) {
        cargarReporte({
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta
        });
    } else {
        cargarReporte();
    }
}

// Exportar reporte (función placeholder para futura implementación)
function exportarReporte() {
    mostrarAlerta('Información', 'La función de exportación estará disponible en la próxima versión');
}

// Cargar reporte al mostrar la pestaña
document.getElementById('reporte-tab')?.addEventListener('shown.bs.tab', function() {
    // Inicializar fechas por defecto
    inicializarFechasReporte();
    cargarReporte();
    actualizarResumen();
});

// Inicializar campos de fecha con valores por defecto
function inicializarFechasReporte() {
    const hoy = new Date();
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
    
    document.getElementById('fechaReporteDesde').value = primerDia.toISOString().split('T')[0];
    document.getElementById('fechaReporteHasta').value = ultimoDia.toISOString().split('T')[0];
}

// Aplicar filtros de reporte
function aplicarFiltrosReporte() {
    const fechaDesde = document.getElementById('fechaReporteDesde').value;
    const fechaHasta = document.getElementById('fechaReporteHasta').value;
    
    if (!fechaDesde || !fechaHasta) {
        mostrarAlerta('Error', 'Por favor selecciona ambas fechas');
        return;
    }
    
    if (fechaDesde > fechaHasta) {
        mostrarAlerta('Error', 'La fecha "Desde" no puede ser mayor que la fecha "Hasta"');
        return;
    }
    
    cargarReporte({
        fecha_desde: fechaDesde,
        fecha_hasta: fechaHasta
    });
}

// Limpiar filtros y volver al mes actual
function limpiarFiltrosReporte() {
    inicializarFechasReporte();
    cargarReporte();
}

// Botón actualizar datos del sidebar
document.getElementById('btnActualizarDatos')?.addEventListener('click', function(e) {
    e.preventDefault();
    actualizarResumen();
    
    // Usar fechas de los filtros si están disponibles
    const fechaDesde = document.getElementById('fechaReporteDesde')?.value;
    const fechaHasta = document.getElementById('fechaReporteHasta')?.value;
    
    if (fechaDesde && fechaHasta) {
        cargarReporte({
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta
        });
    } else {
        cargarReporte();
    }
    
    mostrarAlerta('Éxito', 'Datos actualizados correctamente');
});

// Cargar datos al iniciar la página
document.addEventListener('DOMContentLoaded', function() {
    // Solo actualizar resumen, no cargar reporte automáticamente
    actualizarResumen();
});