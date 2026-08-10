/**
 * Reporte Alberto - Proveedor OGROLL
 * Adaptación del Reporte de Saldo para mostrar Egresos - Gastos
 */

console.log('[SYSTEM] ✅ caja_reporte_alberto.js cargado correctamente');

// Actualizar resumen de caja para Reporte Alberto (tarjetas superiores)
async function actualizarResumenAlberto(soloSaldo = false, filtrosActivos = null) {
    console.log('Actualizando resumen Alberto...');
    
    try {
        const hoy = new Date();
        let fechaDesde, fechaHasta;
        
        if (filtrosActivos && filtrosActivos.fecha_desde && filtrosActivos.fecha_hasta) {
            fechaDesde = filtrosActivos.fecha_desde;
            fechaHasta = filtrosActivos.fecha_hasta;
        } else {
            // Primer día del mes actual
            const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            // Último día del mes actual
            const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            fechaDesde = primerDiaMes.toISOString().split('T')[0];
            fechaHasta = ultimoDiaMes.toISOString().split('T')[0];
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
            const formatoMoneda = new Intl.NumberFormat('es-AR', { 
                style: 'currency', 
                currency: 'ARS',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
            
            let totalEgresos = 0;
            let totalGastos = 0;
            
            result.data.forEach(mov => {
                if (mov.tipo === 'EGRESO') {
                    totalEgresos += parseFloat(mov.importe);
                } else if (mov.tipo === 'GASTO') {
                    totalGastos += parseFloat(mov.importe);
                }
            });
            
            // Saldo = Egresos - Gastos (para el rango seleccionado)
            const saldo = totalEgresos - totalGastos;
            
            const elementoSaldo = document.getElementById('saldoAlberto');
            if (elementoSaldo) {
                elementoSaldo.textContent = formatoMoneda.format(saldo);
                
                // Cambiar color según saldo
                const cardSaldo = elementoSaldo.closest('.card');
                if (cardSaldo) {
                    if (saldo < 0) {
                        cardSaldo.classList.remove('bg-primary');
                        cardSaldo.classList.add('bg-warning');
                        const textEl = cardSaldo.querySelector('.card-text');
                        if (textEl) textEl.textContent = 'Déficit';
                    } else {
                        cardSaldo.classList.remove('bg-warning');
                        cardSaldo.classList.add('bg-primary');
                        const textEl = cardSaldo.querySelector('.card-text');
                        if (textEl) textEl.textContent = 'Disponible';
                    }
                }
            }
            
            const elementoEgresos = document.getElementById('totalEgresosAlberto');
            if (elementoEgresos) {
                elementoEgresos.textContent = formatoMoneda.format(totalEgresos);
            }
            
            const elementoGastos = document.getElementById('totalGastosAlberto');
            if (elementoGastos) {
                elementoGastos.textContent = formatoMoneda.format(totalGastos);
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
            // Primer día del mes actual
            const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            // Último día del mes actual
            const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            
            filtros.fecha_desde = primerDiaMes.toISOString().split('T')[0];
            filtros.fecha_hasta = ultimoDiaMes.toISOString().split('T')[0];
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
                        <th class="text-center align-middle">N° COMP</th>
                        <th class="text-center align-middle">TIPO GASTO</th>
                        <th class="text-center align-middle">DISTRIBUCIÓN</th>
                        <th class="align-middle">Observaciones</th>
                        <th class="text-end align-middle">Importe</th>
                        <th class="text-center align-middle">Archivo</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    let saldoAcumulado = 0;
    let contadorFilas = 0;
    
    movimientosPaginados.forEach(mov => {
        contadorFilas++;
        const fecha = mov.fecha ? new Date(mov.fecha + 'T00:00:00').toLocaleDateString('es-AR') : '';
        const importe = formatoMoneda.format(mov.importe);
        
        const tipoClass = mov.tipo === 'EGRESO' ? 'text-danger' : 'text-warning';
        const tipoIcon = mov.tipo === 'EGRESO' ? 'arrow-up-circle' : 'wallet2';
        
        const nCompDisplay = mov.n_comp || '-';
        const tipoGastoDisplay = mov.tipo_gasto || '-';
        
        // Determinar si la fila es expandible
        let distribucionDisplay = mov.centro_costo || '-';
        if (mov.es_expandible) {
            const filaId = `fila-${contadorFilas}`;
            distribucionDisplay = `
                <span class="d-flex align-items-center justify-content-center gap-2" 
                      style="cursor: pointer;" 
                      onclick="toggleDistribucion('${filaId}')">
                    <i class="bi bi-chevron-right" id="icono-${filaId}"></i>
                    <span class="badge bg-info">${mov.centro_costo}</span>
                </span>
            `;
        }
        
        let archivoBoton = '';
        if ((mov.tipo === 'EGRESO' || mov.tipo === 'GASTO') && mov.tiene_foto == 1) {
            archivoBoton = `
                <button class="btn btn-outline-primary btn-sm" 
                        onclick="verFotoEgreso(${mov.id})"
                        title="Ver archivo"
                        style="width: 32px; height: 32px; padding: 0;">
                    <i class="bi bi-file-earmark"></i>
                </button>
            `;
        } else {
            archivoBoton = '-';
        }
        
        if (mov.tipo === 'EGRESO') {
            saldoAcumulado += parseFloat(mov.importe);
        } else if (mov.tipo === 'GASTO') {
            saldoAcumulado -= parseFloat(mov.importe);
        }
        
        html += `
            <tr>
                <td class="text-center">${fecha}</td>
                <td class="text-center"><i class="bi bi-${tipoIcon} ${tipoClass}"></i> ${mov.tipo}</td>
                <td class="text-center"><small>${nCompDisplay}</small></td>
                <td class="text-center"><small>${tipoGastoDisplay}</small></td>
                <td class="text-center"><small>${distribucionDisplay}</small></td>
                <td>${mov.concepto}</td>
                <td class="text-end ${tipoClass}"><strong>${importe}</strong></td>
                <td class="text-center">${archivoBoton}</td>
            </tr>
        `;
        
        // Si es expandible, agregar fila oculta con el detalle
        if (mov.es_expandible && mov.distribucion_detalle) {
            const filaId = `fila-${contadorFilas}`;
            html += `
                <tr id="detalle-${filaId}" style="display: none;">
                    <td colspan="8" class="p-0">
                        <div class="bg-light border-top border-bottom p-3">
                            <div class="row g-2">
            `;
            
            mov.distribucion_detalle.forEach((dist, idx) => {
                const importeDist = formatoMoneda.format(dist.importe);
                html += `
                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded border">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-arrow-return-right text-primary"></i>
                                <strong>${dist.nombre_centro}</strong>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary me-2">${dist.porcentaje.toFixed(1)}%</span>
                                <strong class="text-warning">${importeDist}</strong>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }
    });
    
    html += `
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="6" class="text-end"><strong>Saldo (Rango Seleccionado):</strong></td>
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
        datosExcel.push(['Fecha', 'Tipo', 'COMP.', 'TIPO GASTO', 'CENTRO COSTO', 'Observaciones', 'Importe']);
        
        let saldoAcumulado = 0;
        
        movimientosPaginados.forEach(mov => {
            const fecha = new Date(mov.fecha + 'T00:00:00').toLocaleDateString('es-AR');
            const compDisplay = (mov.cod_comp && mov.n_comp) ? `${mov.cod_comp}${mov.n_comp}` : '-';
            const tipoGastoDisplay = mov.tipo_gasto || '-';
            const centroCostoDisplay = mov.centro_costo || '-';
            
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
                centroCostoDisplay,
                mov.concepto,
                parseFloat(mov.importe)
            ]);
        });
        
        datosExcel.push([]);
        datosExcel.push(['', '', '', '', '', 'Saldo (Rango Seleccionado):', saldoAcumulado]);
        
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(datosExcel);
        
        ws['!cols'] = [
            { wch: 12 },  // Fecha
            { wch: 10 },  // Tipo
            { wch: 10 },  // COMP
            { wch: 25 },  // TIPO GASTO
            { wch: 30 },  // CENTRO COSTO
            { wch: 40 },  // Observaciones
            { wch: 15 }   // Importe
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
    // Primer día del mes actual
    const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    // Último día del mes actual
    const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
    
    document.getElementById('fechaReporteAlbertoDesde').value = primerDiaMes.toISOString().split('T')[0];
    document.getElementById('fechaReporteAlbertoHasta').value = ultimoDiaMes.toISOString().split('T')[0];
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

// Ver foto o descargar PDF de egreso/gasto
async function verFotoEgreso(idEgreso) {
    try {
        const response = await fetch(`controller/caja_reporte_alberto_controller.php?accion=obtener_foto&id=${idEgreso}`);
        const result = await response.json();
        
        if (result.success && result.foto) {
            const esPdf = result.tipo === 'application/pdf';
            
            if (esPdf) {
                // Para PDFs, crear un enlace de descarga
                const pdfBlob = base64ToBlob(result.foto, 'application/pdf');
                const url = URL.createObjectURL(pdfBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `comprobante_alberto_${idEgreso}.pdf`;
                link.click();
                URL.revokeObjectURL(url);
            } else {
                // Para imágenes, mostrar en modal
                const modalTitle = document.getElementById('modalFotoEgresoLabel');
                modalTitle.textContent = `Comprobante ID: ${idEgreso}`;
                
                const img = document.getElementById('imagenFotoEgreso');
                img.src = `data:${result.tipo};base64,${result.foto}`;
                
                const modal = new bootstrap.Modal(document.getElementById('modalFotoEgreso'));
                modal.show();
            }
        } else {
            mostrarAlerta('Error', 'No se pudo cargar el archivo');
        }
    } catch (error) {
        console.error('Error al cargar archivo:', error);
        mostrarAlerta('Error', 'Error al cargar el archivo');
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

/**
 * Toggle para expandir/colapsar distribución de centros de costo
 */
function toggleDistribucion(filaId) {
    const detalleRow = document.getElementById(`detalle-${filaId}`);
    const icono = document.getElementById(`icono-${filaId}`);
    
    if (detalleRow.style.display === 'none') {
        detalleRow.style.display = '';
        icono.classList.remove('bi-chevron-right');
        icono.classList.add('bi-chevron-down');
    } else {
        detalleRow.style.display = 'none';
        icono.classList.remove('bi-chevron-down');
        icono.classList.add('bi-chevron-right');
    }
}

// Exponer funciones al scope global
window.cambiarCantidadMovimientosAlberto = cambiarCantidadMovimientosAlberto;
window.aplicarFiltrosReporteAlberto = aplicarFiltrosReporteAlberto;
window.limpiarFiltrosReporteAlberto = limpiarFiltrosReporteAlberto;
window.exportarReporteAlbertoExcel = exportarReporteAlbertoExcel;
window.verFotoEgreso = verFotoEgreso;
window.toggleDistribucion = toggleDistribucion;
