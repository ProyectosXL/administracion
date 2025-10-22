/**
 * Reporte Alberto - Proveedor OGROLL
 * Adaptación del Reporte de Saldo para mostrar Egresos - Gastos
 */

console.log('[SYSTEM] ✅ caja_reporte_alberto.js cargado correctamente');

// Actualizar resumen de caja para Reporte Alberto (tarjetas superiores)
async function actualizarResumenAlberto(soloSaldo = false, filtrosActivos = null) {
    console.log('Actualizando resumen Alberto...');
    
    try {
        // Para calcular SALDO: siempre usar todos los datos
        const hoy = new Date();
        const hace2Anos = new Date(hoy);
        hace2Anos.setFullYear(hoy.getFullYear() - 2);
        
        const fechaDesdeSaldo = hace2Anos.toISOString().split('T')[0];
        const fechaHastaSaldo = hoy.toISOString().split('T')[0];
        
        let urlSaldo = `controller/caja_reporte_alberto_controller.php?accion=movimientos&_=${Date.now()}`;
        urlSaldo += `&fecha_desde=${fechaDesdeSaldo}`;
        urlSaldo += `&fecha_hasta=${fechaHastaSaldo}`;
        
        const responseSaldo = await fetch(urlSaldo, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!responseSaldo.ok) {
            throw new Error(`HTTP ${responseSaldo.status}`);
        }
        
        const resultSaldo = await responseSaldo.json();
        
        if (resultSaldo.success) {
            const formatoMoneda = new Intl.NumberFormat('es-AR', { 
                style: 'currency', 
                currency: 'ARS',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
            
            // Calcular saldo sobre TODOS los períodos
            let totalEgresosParaSaldo = 0;
            let totalGastosParaSaldo = 0;
            
            resultSaldo.data.forEach(mov => {
                if (mov.tipo === 'EGRESO') {
                    totalEgresosParaSaldo += parseFloat(mov.importe);
                } else if (mov.tipo === 'GASTO') {
                    totalGastosParaSaldo += parseFloat(mov.importe);
                }
            });
            
            // Saldo = Egresos - Gastos (inverso al reporte normal)
            const saldo = totalEgresosParaSaldo - totalGastosParaSaldo;
            
            const elementoSaldo = document.getElementById('saldoAlberto');
            elementoSaldo.textContent = formatoMoneda.format(saldo);
            
            // Cambiar color según saldo
            const cardSaldo = elementoSaldo.closest('.card');
            if (saldo < 0) {
                cardSaldo.classList.remove('bg-primary');
                cardSaldo.classList.add('bg-warning');
                cardSaldo.querySelector('.card-text').textContent = 'Déficit';
            } else {
                cardSaldo.classList.remove('bg-warning');
                cardSaldo.classList.add('bg-primary');
                cardSaldo.querySelector('.card-text').textContent = 'Disponible';
            }
            
            // Actualizar Egresos y Gastos según filtros
            if (!soloSaldo) {
                let fechaDesde, fechaHasta;
                
                if (filtrosActivos && filtrosActivos.fecha_desde && filtrosActivos.fecha_hasta) {
                    fechaDesde = filtrosActivos.fecha_desde;
                    fechaHasta = filtrosActivos.fecha_hasta;
                } else {
                    const hace15Dias = new Date(hoy);
                    hace15Dias.setDate(hoy.getDate() - 15);
                    fechaDesde = hace15Dias.toISOString().split('T')[0];
                    fechaHasta = hoy.toISOString().split('T')[0];
                }
                
                let url = `controller/caja_reporte_alberto_controller.php?accion=movimientos&_=${Date.now()}`;
                url += `&fecha_desde=${fechaDesde}`;
                url += `&fecha_hasta=${fechaHasta}`;
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Cache-Control': 'no-cache'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    let totalEgresos = 0;
                    let totalGastos = 0;
                    
                    result.data.forEach(mov => {
                        if (mov.tipo === 'EGRESO') {
                            totalEgresos += parseFloat(mov.importe);
                        } else if (mov.tipo === 'GASTO') {
                            totalGastos += parseFloat(mov.importe);
                        }
                    });
                    
                    document.getElementById('totalEgresosAlberto').textContent = 
                        formatoMoneda.format(totalEgresos);
                    
                    document.getElementById('totalGastosAlberto').textContent = 
                        formatoMoneda.format(totalGastos);
                }
            }
        }
    } catch (error) {
        console.error('Error al actualizar resumen Alberto:', error);
    }
}

// Cargar reporte Alberto
async function cargarReporteAlberto(filtros = {}) {
    try {
        if (!filtros.fecha_desde || !filtros.fecha_hasta) {
            const hoy = new Date();
            const hace15Dias = new Date(hoy);
            hace15Dias.setDate(hoy.getDate() - 15);
            
            filtros.fecha_desde = hace15Dias.toISOString().split('T')[0];
            filtros.fecha_hasta = hoy.toISOString().split('T')[0];
        }
        
        let url = `controller/caja_reporte_alberto_controller.php?accion=movimientos&_=${Date.now()}`;
        url += `&fecha_desde=${filtros.fecha_desde}`;
        url += `&fecha_hasta=${filtros.fecha_hasta}`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            mostrarReporteAlberto(result.data, filtros);
        } else {
            document.getElementById('contenidoReporteAlberto').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Error: ${result.message}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error en cargarReporteAlberto:', error);
        document.getElementById('contenidoReporteAlberto').innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> Error de conexión.
            </div>
        `;
    }
}

// Mostrar tabla de reporte Alberto
function mostrarReporteAlberto(movimientos, filtros = {}) {
    const contenedor = document.getElementById('contenidoReporteAlberto');
    
    if (!movimientos || movimientos.length === 0) {
        contenedor.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No hay movimientos registrados para Alberto (OGROLL).
            </div>
        `;
        return;
    }
    
    const cantidadSeleccionada = parseInt(localStorage.getItem('reporteAlbertoCantidadPorPagina') || '50');
    const movimientosPaginados = movimientos.slice(0, cantidadSeleccionada);
    
    const formatoMoneda = new Intl.NumberFormat('es-AR', { 
        style: 'currency', 
        currency: 'ARS',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    if (filtros && (filtros.fecha_desde || filtros.fecha_hasta) && filtros.aplicadoManualmente) {
        actualizarResumenAlberto(false, filtros);
    }
    
    let html = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted">Mostrar</span>
                <select class="form-select form-select-sm" style="width: auto;" onchange="cambiarCantidadMovimientosAlberto(this.value)">
                    <option value="50" ${cantidadSeleccionada === 50 ? 'selected' : ''}>50</option>
                    <option value="100" ${cantidadSeleccionada === 100 ? 'selected' : ''}>100</option>
                    <option value="200" ${cantidadSeleccionada === 200 ? 'selected' : ''}>200</option>
                    <option value="500" ${cantidadSeleccionada === 500 ? 'selected' : ''}>500</option>
                </select>
                <span class="text-muted">movimientos</span>
            </div>
            <small class="text-muted">
                Mostrando ${movimientosPaginados.length} de ${movimientos.length} movimientos
            </small>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center align-middle">Fecha</th>
                        <th class="text-center align-middle">Tipo</th>
                        <th class="text-center align-middle">COMP.</th>
                        <th class="text-center align-middle">TIPO_GASTO</th>
                        <th class="align-middle">Observaciones</th>
                        <th class="text-end align-middle">Importe</th>
                        <th class="text-center align-middle">Foto</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    let saldoAcumulado = 0;
    
    movimientosPaginados.forEach(mov => {
        const fecha = new Date(mov.fecha + 'T00:00:00').toLocaleDateString('es-AR');
        const importe = formatoMoneda.format(mov.importe);
        
        const tipoClass = mov.tipo === 'EGRESO' ? 'text-danger' : 'text-warning';
        const tipoIcon = mov.tipo === 'EGRESO' ? 'arrow-up-circle' : 'wallet2';
        
        const compDisplay = (mov.cod_comp && mov.n_comp) ? `${mov.cod_comp}${mov.n_comp}` : '-';
        const tipoGastoDisplay = mov.tipo_gasto || '-';
        
        let fotoBoton = '';
        if (mov.tipo === 'EGRESO' && mov.tiene_foto == 1) {
            fotoBoton = `
                <div class="d-flex justify-content-center">
                    <button class="btn btn-outline-primary btn-sm" 
                            onclick="verFotoEgreso(${mov.id})"
                            title="Ver foto"
                            style="width: 32px; height: 32px; padding: 0;">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
            `;
        } else {
            fotoBoton = '<div class="d-flex justify-content-center"><span class="text-muted">-</span></div>';
        }
        
        if (mov.tipo === 'EGRESO') {
            saldoAcumulado += parseFloat(mov.importe);
        } else {
            saldoAcumulado -= parseFloat(mov.importe);
        }
        
        html += `
            <tr>
                <td class="text-center align-middle">${fecha}</td>
                <td class="text-center align-middle"><i class="bi bi-${tipoIcon} ${tipoClass}"></i> ${mov.tipo}</td>
                <td class="text-center align-middle"><small>${compDisplay}</small></td>
                <td class="text-center align-middle"><small>${tipoGastoDisplay}</small></td>
                <td class="align-middle">${mov.concepto}</td>
                <td class="text-end align-middle ${tipoClass}"><strong>${importe}</strong></td>
                <td class="text-center align-middle">${fotoBoton}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end"><strong>Saldo (Rango Seleccionado):</strong></td>
                        <td class="text-end"><strong>${formatoMoneda.format(saldoAcumulado)}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
    
    contenedor.innerHTML = html;
}

// Cambiar cantidad de movimientos
function cambiarCantidadMovimientosAlberto(cantidad) {
    localStorage.setItem('reporteAlbertoCantidadPorPagina', cantidad);
    
    const fechaDesde = document.getElementById('fechaReporteAlbertoDesde')?.value;
    const fechaHasta = document.getElementById('fechaReporteAlbertoHasta')?.value;
    
    if (fechaDesde && fechaHasta) {
        cargarReporteAlberto({
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta,
            aplicadoManualmente: true
        });
    } else {
        cargarReporteAlberto();
    }
}

// Exportar a Excel
async function exportarReporteAlbertoExcel() {
    try {
        if (typeof XLSX === 'undefined') {
            mostrarAlerta('Error', 'La librería de exportación no está disponible');
            return;
        }
        
        const fechaDesde = document.getElementById('fechaReporteAlbertoDesde').value;
        const fechaHasta = document.getElementById('fechaReporteAlbertoHasta').value;
        
        if (!fechaDesde || !fechaHasta) {
            mostrarAlerta('Error', 'Por favor selecciona un rango de fechas');
            return;
        }
        
        const cantidadSeleccionada = parseInt(localStorage.getItem('reporteAlbertoCantidadPorPagina') || '50');
        
        let url = `controller/caja_reporte_alberto_controller.php?accion=movimientos&_=${Date.now()}`;
        url += `&fecha_desde=${fechaDesde}`;
        url += `&fecha_hasta=${fechaHasta}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (!result.success || !result.data || result.data.length === 0) {
            mostrarAlerta('Información', 'No hay datos para exportar');
            return;
        }
        
        const movimientos = result.data;
        const movimientosPaginados = movimientos.slice(0, cantidadSeleccionada);
        
        const datosExcel = [];
        datosExcel.push(['Fecha', 'Tipo', 'COMP.', 'TIPO_GASTO', 'Observaciones', 'Importe']);
        
        let saldoAcumulado = 0;
        
        movimientosPaginados.forEach(mov => {
            const fecha = new Date(mov.fecha + 'T00:00:00').toLocaleDateString('es-AR');
            const compDisplay = (mov.cod_comp && mov.n_comp) ? `${mov.cod_comp}${mov.n_comp}` : '-';
            const tipoGastoDisplay = mov.tipo_gasto || '-';
            
            if (mov.tipo === 'EGRESO') {
                saldoAcumulado += parseFloat(mov.importe);
            } else {
                saldoAcumulado -= parseFloat(mov.importe);
            }
            
            datosExcel.push([
                fecha,
                mov.tipo,
                compDisplay,
                tipoGastoDisplay,
                mov.concepto,
                parseFloat(mov.importe)
            ]);
        });
        
        datosExcel.push([]);
        datosExcel.push(['', '', '', '', 'Saldo (Rango Seleccionado):', saldoAcumulado]);
        
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(datosExcel);
        
        ws['!cols'] = [
            { wch: 12 },
            { wch: 10 },
            { wch: 10 },
            { wch: 25 },
            { wch: 40 },
            { wch: 15 }
        ];
        
        XLSX.utils.book_append_sheet(wb, ws, 'Reporte Alberto');
        
        const nombreArchivo = `Reporte_Alberto_${fechaDesde}_${fechaHasta}.xlsx`;
        XLSX.writeFile(wb, nombreArchivo);
        
        mostrarAlerta('Éxito', `Reporte exportado: ${nombreArchivo}`);
    } catch (error) {
        console.error('Error al exportar:', error);
        mostrarAlerta('Error', 'No se pudo exportar el reporte');
    }
}

// Cargar al mostrar pestaña
document.getElementById('reporte-alberto-tab')?.addEventListener('shown.bs.tab', function() {
    actualizarResumenAlberto();
    inicializarFechasReporteAlberto();
    cargarReporteAlberto();
});

// Inicializar fechas
function inicializarFechasReporteAlberto() {
    const hoy = new Date();
    const hace15Dias = new Date(hoy);
    hace15Dias.setDate(hoy.getDate() - 15);
    
    document.getElementById('fechaReporteAlbertoDesde').value = hace15Dias.toISOString().split('T')[0];
    document.getElementById('fechaReporteAlbertoHasta').value = hoy.toISOString().split('T')[0];
}

// Aplicar filtros
function aplicarFiltrosReporteAlberto() {
    const fechaDesde = document.getElementById('fechaReporteAlbertoDesde').value;
    const fechaHasta = document.getElementById('fechaReporteAlbertoHasta').value;
    
    if (!fechaDesde || !fechaHasta) {
        mostrarAlerta('Error', 'Por favor selecciona ambas fechas');
        return;
    }
    
    if (fechaDesde > fechaHasta) {
        mostrarAlerta('Error', 'La fecha "Desde" no puede ser mayor que "Hasta"');
        return;
    }
    
    const filtros = {
        fecha_desde: fechaDesde,
        fecha_hasta: fechaHasta,
        aplicadoManualmente: true
    };
    
    cargarReporteAlberto(filtros);
    actualizarResumenAlberto(false, filtros);
}

// Limpiar filtros
function limpiarFiltrosReporteAlberto() {
    inicializarFechasReporteAlberto();
    cargarReporteAlberto();
    actualizarResumenAlberto();
}

// Exponer funciones al scope global
window.cambiarCantidadMovimientosAlberto = cambiarCantidadMovimientosAlberto;
window.aplicarFiltrosReporteAlberto = aplicarFiltrosReporteAlberto;
window.limpiarFiltrosReporteAlberto = limpiarFiltrosReporteAlberto;
window.exportarReporteAlbertoExcel = exportarReporteAlbertoExcel;
