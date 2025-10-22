/**
 * Reporte de Saldo de Caja
 */

// ===========================================
// DIAGNÓSTICO DE VERSIÓN Y DEBUGGING
// ===========================================
console.log('[SYSTEM] ✅ caja_reporte.js cargado correctamente');
console.log('[SYSTEM] 📅 Versión:', new Date().getTime());

// Función de diagnóstico para verificar que todo está funcionando
window.diagnosticarSistemaTesoreria = function() {
    console.log('=== DIAGNÓSTICO DEL SISTEMA TESORERÍA ===');
    console.log('✅ JavaScript cargado correctamente');
    console.log('✅ Función marcarRecibidoTesoreria disponible:', typeof marcarRecibidoTesoreria);
    console.log('✅ Versión actual:', new Date().getTime());
    
    // Buscar botones de TESORERÍA en la página
    const botones = document.querySelectorAll('[onclick*="marcarRecibidoTesoreria"]');
    console.log('🔍 Botones de TESORERÍA encontrados:', botones.length);
    
    botones.forEach((boton, index) => {
        console.log(`Botón ${index + 1}:`, {
            'data-id-sba05': boton.dataset.idSba05,
            'data-fecha': boton.dataset.fecha,
            'onclick': boton.getAttribute('onclick'),
            'todas las claves dataset': Object.keys(boton.dataset)
        });
    });
    
    alert('Diagnóstico completado. Revisa la consola para detalles.');
};

// ===========================================
// FUNCIONES PRINCIPALES
// ===========================================

// Mostrar/ocultar indicador de carga en las tarjetas
function mostrarCargandoTarjetas(mostrar = true) {
    const tarjetas = ['totalIngresos', 'totalEgresos', 'saldoActual'];
    
    tarjetas.forEach(id => {
        const elemento = document.getElementById(id);
        if (elemento) {
            if (mostrar) {
                elemento.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Cargando...';
            }
        }
    });
}

// Mostrar/ocultar indicador de carga en las tarjetas
function mostrarCargandoTarjetas(mostrar = true) {
    const tarjetas = ['totalIngresos', 'totalEgresos', 'saldoActual'];
    
    tarjetas.forEach(id => {
        const elemento = document.getElementById(id);
        if (elemento) {
            if (mostrar) {
                elemento.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Cargando...';
            }
        }
    });
}

// Actualizar resumen de caja (tarjetas superiores)
async function actualizarResumen(soloSaldo = false, filtrosActivos = null) {
    console.log('Actualizando resumen de caja...');
    
    // Mostrar indicador de carga
    mostrarCargandoTarjetas(true);
    
    // Mostrar indicador de carga
    mostrarCargandoTarjetas(true);
    
    try {
        // Para calcular SALDO: siempre usar rango amplio (últimos 2 años hasta hoy)
        const hoy = new Date();
        const hace2Anos = new Date(hoy);
        hace2Anos.setFullYear(hoy.getFullYear() - 2);
        
        const fechaDesdeSaldo = hace2Anos.toISOString().split('T')[0];
        const fechaHastaSaldo = hoy.toISOString().split('T')[0];
        
        let urlSaldo = `controller/caja_reporte_controller.php?accion=movimientos&_=${Date.now()}`;
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
            throw new Error(`HTTP ${responseSaldo.status}: ${responseSaldo.statusText}`);
        }
        
        const textSaldo = await responseSaldo.text();
        let resultSaldo;
        
        try {
            resultSaldo = JSON.parse(textSaldo);
        } catch (jsonError) {
            console.error('Respuesta no es JSON válido:', textSaldo);
            throw new Error('Respuesta del servidor no es JSON válido');
        }
        
        console.log('Datos para saldo (todos los períodos):', resultSaldo.data);
        
        if (resultSaldo.success) {
            const formatoMoneda = new Intl.NumberFormat('es-AR', { 
                style: 'currency', 
                currency: 'ARS',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
            
            // Calcular saldo sobre TODOS los períodos
            let totalIngresosParaSaldo = 0;
            let totalEgresosParaSaldo = 0;
            
            resultSaldo.data.forEach(mov => {
                if (mov.tipo === 'INGRESO' && mov.recibido == 1) {
                    totalIngresosParaSaldo += parseFloat(mov.importe);
                } else if (mov.tipo === 'EGRESO') {
                    totalEgresosParaSaldo += parseFloat(mov.importe);
                }
            });
            
            // El saldo SIEMPRE se calcula con todos los datos (ingresos - egresos)
            const saldo = totalIngresosParaSaldo - totalEgresosParaSaldo;
            console.log('Saldo calculado sobre todos los períodos:', saldo);
            
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
                cardSaldo.querySelector('.card-text').textContent = 'Disponible';
            }
            
            // Si solo se actualiza el saldo, ocultar el indicador de carga aquí
            if (soloSaldo) {
                mostrarCargandoTarjetas(false);
            }
            
            // Solo actualizar ingresos y egresos si no estamos en modo "solo saldo"
            if (!soloSaldo) {
                // Para INGRESOS y EGRESOS: usar filtros activos si existen, sino usar totales generales
                let fechaDesdeIngEgr, fechaHastaIngEgr;
                
                if (filtrosActivos && filtrosActivos.fecha_desde && filtrosActivos.fecha_hasta) {
                    // Hay filtros activos: usar esas fechas
                    fechaDesdeIngEgr = filtrosActivos.fecha_desde;
                    fechaHastaIngEgr = filtrosActivos.fecha_hasta;
                    console.log('Usando filtros de fecha:', fechaDesdeIngEgr, 'a', fechaHastaIngEgr);
                } else {
                    // No hay filtros activos: usar rango por defecto (últimos 15 días)
                    const hace15Dias = new Date(hoy);
                    hace15Dias.setDate(hoy.getDate() - 15);
                    fechaDesdeIngEgr = hace15Dias.toISOString().split('T')[0];
                    fechaHastaIngEgr = hoy.toISOString().split('T')[0];
                    console.log('Usando rango por defecto (últimos 15 días):', fechaDesdeIngEgr, 'a', fechaHastaIngEgr);
                }
                
                let urlIngEgr = `controller/caja_reporte_controller.php?accion=movimientos&_=${Date.now()}`;
                urlIngEgr += `&fecha_desde=${fechaDesdeIngEgr}`;
                urlIngEgr += `&fecha_hasta=${fechaHastaIngEgr}`;
                
                const responseIngEgr = await fetch(urlIngEgr, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Cache-Control': 'no-cache'
                    }
                });
                
                if (!responseIngEgr.ok) {
                    throw new Error(`HTTP ${responseIngEgr.status}: ${responseIngEgr.statusText}`);
                }
                
                const textIngEgr = await responseIngEgr.text();
                let resultIngEgr;
                
                try {
                    resultIngEgr = JSON.parse(textIngEgr);
                } catch (jsonError) {
                    console.error('Respuesta no es JSON válido:', textIngEgr);
                    throw new Error('Respuesta del servidor no es JSON válido');
                }
                
                if (resultIngEgr.success) {
                    let totalIngresosRango = 0;
                    let totalEgresosRango = 0;
                    
                    resultIngEgr.data.forEach(mov => {
                        if (mov.tipo === 'INGRESO' && mov.recibido == 1) {
                            totalIngresosRango += parseFloat(mov.importe);
                        } else if (mov.tipo === 'EGRESO') {
                            totalEgresosRango += parseFloat(mov.importe);
                        }
                    });
                    
                    console.log('Tarjetas actualizadas - Ingresos:', totalIngresosRango, 'Egresos:', totalEgresosRango);
                    
                    document.getElementById('totalIngresos').textContent = 
                        formatoMoneda.format(totalIngresosRango);
                    
                    document.getElementById('totalEgresos').textContent = 
                        formatoMoneda.format(totalEgresosRango);
                        
                    // Ocultar indicador de carga cuando se completa la actualización
                    mostrarCargandoTarjetas(false);
                } else {
                    console.error('Error en respuesta del servidor:', resultIngEgr);
                    // Ocultar indicador de carga en caso de error
                    mostrarCargandoTarjetas(false);
                }
            }
        }
    } catch (error) {
        console.error('Error al actualizar resumen:', error);
        // Ocultar indicador de carga en caso de error
        mostrarCargandoTarjetas(false);
    }
}

// Cargar movimientos para el reporte
async function cargarReporte(filtros = {}) {
    try {
        // Si no se proporcionan fechas, usar los últimos 15 días
        if (!filtros.fecha_desde || !filtros.fecha_hasta) {
            const hoy = new Date();
            const hace15Dias = new Date(hoy);
            hace15Dias.setDate(hoy.getDate() - 15);
            
            filtros.fecha_desde = hace15Dias.toISOString().split('T')[0];
            filtros.fecha_hasta = hoy.toISOString().split('T')[0];
        }
        
        let url = `controller/caja_reporte_controller.php?accion=movimientos&_=${Date.now()}`;
        url += `&fecha_desde=${filtros.fecha_desde}`;
        url += `&fecha_hasta=${filtros.fecha_hasta}`;
        
        console.log('Cargando reporte desde URL:', url);
        
        const response = await fetch(url, {
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
        
        console.log('Respuesta del reporte:', result);
        
        if (result.success) {
            mostrarReporte(result.data, filtros);
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
function mostrarReporte(movimientos, filtros = {}) {
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
    
    // Obtener cantidad seleccionada para paginación (por defecto 50)
    const cantidadSeleccionada = parseInt(localStorage.getItem('reporteCantidadPorPagina') || '50');
    const movimientosPaginados = movimientos.slice(0, cantidadSeleccionada);
    
    const formatoMoneda = new Intl.NumberFormat('es-AR', { 
        style: 'currency', 
        currency: 'ARS',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    // Actualizar tarjetas superiores: pasar filtros para ingresos/egresos, saldo siempre general
    if (filtros && (filtros.fecha_desde || filtros.fecha_hasta) && filtros.aplicadoManualmente) {
        // Filtros aplicados manualmente: actualizar ingresos/egresos con el rango, saldo sigue siendo general
        actualizarResumen(false, filtros);
    }
    
    let html = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted">Mostrar</span>
                <select class="form-select form-select-sm" style="width: auto;" onchange="cambiarCantidadMovimientos(this.value)">
                    <option value="50" ${cantidadSeleccionada === 50 ? 'selected' : ''}>50</option>
                    <option value="100" ${cantidadSeleccionada === 100 ? 'selected' : ''}>100</option>
                    <option value="200" ${cantidadSeleccionada === 200 ? 'selected' : ''}>200</option>
                    <option value="500" ${cantidadSeleccionada === 500 ? 'selected' : ''}>500</option>
                </select>
                <span class="text-muted">movimientos</span>
            </div>
            <small class="text-muted">
                Mostrando ${movimientosPaginados.length} de ${movimientos.length} movimientos
                ${movimientos.length > cantidadSeleccionada ? '' : ''}
            </small>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center align-middle">Fecha</th>
                        <th class="text-center align-middle">Tipo</th>
                        <th class="text-center align-middle">COMP.</th>
                        <th class="align-middle">Concepto</th>
                        <th class="text-end align-middle">Importe</th>
                        <th class="text-center align-middle">Origen</th>
                        <th class="text-center align-middle">Foto</th>
                        <th class="text-center align-middle">Estado</th>
                        <th class="text-center align-middle">Acciones</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    let saldoAcumulado = 0;
    
    movimientosPaginados.forEach(mov => {
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
                
                // Botón de acción según el origen - con estilo de checkbox simplificado
                if (mov.origen === 'TESORERIA') {
                    accionBoton = `
                        <button class="btn btn-outline-success checkbox-style" 
                                onclick="marcarRecibidoTesoreria(this)"
                                data-id-sba05="${mov.ID_SBA05}"
                                data-fecha="${mov.fecha}"
                                data-cod-comp="${mov.cod_comp || ''}"
                                data-n-comp="${mov.n_comp || ''}"
                                data-concepto="${mov.concepto.replace(/"/g, '&quot;')}"
                                data-importe="${mov.importe}"
                                style="width: 32px; height: 32px; padding: 0; border-radius: 4px; border-width: 2px; font-size: 18px;"
                                title="Marcar como recibido">
                            ☐
                        </button>
                    `;
                } else if (mov.origen === '599') {
                    // 599 siempre aparece como recibido, no necesita botón
                    accionBoton = '<span class="text-muted">-</span>';
                } else {
                    // Ingresos MANUALES - pasar el botón para poder cambiarlo visualmente
                    accionBoton = `
                        <button class="btn btn-outline-success checkbox-style" 
                                onclick="marcarRecibidoDesdeReporte(this)"
                                data-ingreso-id="${mov.id}"
                                style="width: 32px; height: 32px; padding: 0; border-radius: 4px; border-width: 2px; font-size: 18px;"
                                title="Marcar como recibido">
                            ☐
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
        
        // Columna de foto (solo para egresos)
        let fotoBoton = '';
        if (mov.tipo === 'EGRESO' && mov.tiene_foto == 1) {
            fotoBoton = `
                <div class="d-flex justify-content-center">
                    <button class="btn btn-outline-primary btn-sm" 
                            onclick="verFotoEgreso(${mov.id})"
                            title="Ver foto del comprobante"
                            style="width: 32px; height: 32px; padding: 0;">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
            `;
        } else {
            fotoBoton = '<div class="d-flex justify-content-center"><span class="text-muted">-</span></div>';
        }
        
        html += `
            <tr>
                <td class="text-center align-middle">${fecha}</td>
                <td class="text-center align-middle"><i class="bi bi-${tipoIcon} ${tipoClass}"></i> ${mov.tipo}</td>
                <td class="text-center align-middle"><small>${compDisplay}</small></td>
                <td class="align-middle">${mov.concepto}</td>
                <td class="text-end align-middle ${tipoClass}"><strong>${importe}</strong></td>
                <td class="text-center align-middle">${origenBadge}</td>
                <td class="text-center align-middle">${fotoBoton}</td>
                <td class="text-center align-middle">${estadoBadge}</td>
                <td class="text-center align-middle">${accionBoton}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end"><strong>Saldo (Rango Seleccionado):</strong></td>
                        <td class="text-end"><strong>${formatoMoneda.format(saldoAcumulado)}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
    
    contenedor.innerHTML = html;
}

// Ver foto de un egreso
async function verFotoEgreso(idEgreso) {
    try {
        const response = await fetch(`controller/caja_egresos_controller.php?accion=obtener_foto&id=${idEgreso}`);
        const result = await response.json();
        
        if (result.success && result.foto) {
            // Configurar modal
            const modalTitle = document.getElementById('modalFotoEgresoLabel');
            modalTitle.textContent = `Foto del Egreso ID: ${idEgreso}`;
            
            // Mostrar imagen
            const img = document.getElementById('imagenFotoEgreso');
            img.src = `data:image/jpeg;base64,${result.foto}`;
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalFotoEgreso'));
            modal.show();
        } else {
            mostrarAlerta('Error', 'No se pudo cargar la foto del egreso');
        }
    } catch (error) {
        console.error('Error al cargar foto:', error);
        mostrarAlerta('Error', 'Error al cargar la foto');
    }
}

// Cambiar cantidad de movimientos mostrados
function cambiarCantidadMovimientos(cantidad) {
    localStorage.setItem('reporteCantidadPorPagina', cantidad);
    
    // Recargar usando las fechas de los filtros si están disponibles
    const fechaDesde = document.getElementById('fechaReporteDesde')?.value;
    const fechaHasta = document.getElementById('fechaReporteHasta')?.value;
    
    if (fechaDesde && fechaHasta) {
        cargarReporte({
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta,
            aplicadoManualmente: true
        });
    } else {
        cargarReporte();
    }
}

// Marcar ingreso como recibido desde el reporte
async function marcarRecibidoDesdeReporte(botonElemento) {
    console.log('='.repeat(80));
    console.log('[MANUAL] 🚀 FUNCIÓN LLAMADA - marcarRecibidoDesdeReporte');
    console.log('[MANUAL] Timestamp:', new Date().toISOString());
    console.log('[MANUAL] Elemento recibido:', botonElemento);
    console.log('='.repeat(80));
    
    try {
        // Extraer el ID del ingreso desde el data-attribute
        const ingresoId = botonElemento.dataset.ingresoId;
        
        if (!ingresoId) {
            console.error('[ERROR] No se encontró el ID del ingreso');
            mostrarAlerta('Error', 'No se pudo obtener el ID del ingreso');
            return;
        }
        
        console.log('[MANUAL] ID del ingreso:', ingresoId);
        
        // Deshabilitar botón y mostrar loading
        botonElemento.disabled = true;
        botonElemento.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        const formData = new FormData();
        formData.append('accion', 'marcar_recibido');
        formData.append('id', ingresoId);
        
        console.log('[MANUAL] Enviando petición...');
        
        const response = await fetch('controller/caja_ingresos_controller.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('[MANUAL] Respuesta recibida, status:', response.status);
        const result = await response.json();
        console.log('[MANUAL] Resultado:', result);
        
        if (result.success) {
            console.log('✅ Ingreso manual marcado como recibido');
            
            // Cambiar el checkbox a "marcado" visualmente INMEDIATAMENTE
            botonElemento.disabled = true;
            botonElemento.classList.add('checked');
            botonElemento.innerHTML = '<i class="bi bi-check-square-fill text-success"></i>';
            
            // Pequeño delay antes de recargar para dar tiempo al backend
            setTimeout(() => {
                // Recargar usando las fechas de los filtros si están disponibles
                const fechaDesde = document.getElementById('fechaReporteDesde')?.value;
                const fechaHasta = document.getElementById('fechaReporteHasta')?.value;
                
                if (fechaDesde && fechaHasta) {
                    cargarReporte({
                        fecha_desde: fechaDesde,
                        fecha_hasta: fechaHasta,
                        aplicadoManualmente: true
                    });
                    // Actualizar todas las tarjetas con los filtros activos
                    actualizarResumen(false, {
                        fecha_desde: fechaDesde,
                        fecha_hasta: fechaHasta
                    });
                } else {
                    cargarReporte();
                    // Actualizar todas las tarjetas sin filtros
                    actualizarResumen();
                }
            }, 300); // 300ms de delay
        } else {
            // Restaurar botón en caso de error
            botonElemento.disabled = false;
            botonElemento.innerHTML = '<i class="bi bi-check"></i>';
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('[ERROR] Error en marcarRecibidoDesdeReporte:', error);
        // Restaurar botón en caso de error
        botonElemento.disabled = false;
        botonElemento.innerHTML = '<i class="bi bi-check"></i>';
        mostrarAlerta('Error', 'No se pudo procesar la solicitud: ' + error.message);
    }
}

// Marcar ingreso TESORERÍA como recibido
async function marcarRecibidoTesoreria(botonElemento) {
    console.log('='.repeat(80));
    console.log('[TESORERÍA] 🚀 FUNCIÓN LLAMADA - marcarRecibidoTesoreria');
    console.log('[TESORERÍA] Timestamp:', new Date().toISOString());
    console.log('[TESORERÍA] Elemento recibido:', botonElemento);
    console.log('[TESORERÍA] Tipo de elemento:', typeof botonElemento);
    console.log('[TESORERÍA] Es HTMLElement?', botonElemento instanceof HTMLElement);
    console.log('='.repeat(80));
    
    try {
        // Verificar que el elemento tiene dataset
        if (!botonElemento || !botonElemento.dataset) {
            console.error('[ERROR] Elemento sin dataset');
            mostrarAlerta('Error', 'Datos del botón no encontrados');
            return;
        }
        
        console.log('[DEBUG] Dataset completo:', botonElemento.dataset);
        console.log('[DEBUG] Todas las claves del dataset:', Object.keys(botonElemento.dataset));
        
        // Obtener datos desde los data-attributes del botón
        const idSba05 = botonElemento.dataset.idSba05;
        const fecha = botonElemento.dataset.fecha;
        const codComp = botonElemento.dataset.codComp;
        const nComp = botonElemento.dataset.nComp;
        const concepto = botonElemento.dataset.concepto;
        const importe = botonElemento.dataset.importe;
        
        console.log('[DEBUG] Datos extraídos:');
        console.log('  idSba05:', idSba05, '(tipo:', typeof idSba05, ')');
        console.log('  fecha:', fecha, '(tipo:', typeof fecha, ')');
        console.log('  codComp:', codComp, '(tipo:', typeof codComp, ')');
        console.log('  nComp:', nComp, '(tipo:', typeof nComp, ')');
        console.log('  concepto:', concepto, '(tipo:', typeof concepto, ')');
        console.log('  importe:', importe, '(tipo:', typeof importe, ')');
        
        // Validaciones CRÍTICAS
        if (!idSba05 || idSba05 === 'undefined' || idSba05 === 'null') {
            console.error('[CRITICAL ERROR] id_sba05 inválido:', idSba05);
            mostrarAlerta('Error', 'ERROR CRÍTICO: ID SBA05 no encontrado o inválido: ' + idSba05);
            return;
        }
        
        if (!fecha || fecha === 'undefined' || fecha === 'null') {
            console.error('[CRITICAL ERROR] fecha inválida:', fecha);
            mostrarAlerta('Error', 'ERROR CRÍTICO: fecha no encontrada o inválida: ' + fecha);
            return;
        }
        
        console.log('[DEBUG] Validaciones CRÍTICAS pasadas, continuando...');
        
        console.log('Datos para marcar TESORERÍA:', {
            idSba05, fecha, codComp, nComp, concepto, importe
        });
        
        // Log adicional de los data attributes
        console.log('Data attributes del botón:', {
            'data-id-sba05': botonElemento.dataset.idSba05,
            'data-fecha': botonElemento.dataset.fecha,
            'data-cod-comp': botonElemento.dataset.codComp,
            'data-n-comp': botonElemento.dataset.nComp,
            'data-concepto': botonElemento.dataset.concepto,
            'data-importe': botonElemento.dataset.importe
        });
        
        // Deshabilitar botón durante el proceso
        botonElemento.disabled = true;
        botonElemento.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        const formData = new FormData();
        formData.append('accion', 'marcar_recibido_tesoreria');
        formData.append('id_sba05', String(idSba05)); // Forzar a string
        formData.append('fecha', String(fecha)); // Forzar a string
        formData.append('cod_comp', String(codComp || ''));
        formData.append('n_comp', String(nComp || ''));
        formData.append('observaciones', String(concepto || ''));
        formData.append('importe', String(importe || 0));
        
        // Log del FormData que se enviará
        console.log('[DEBUG] FormData que se enviará:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: "${value}" (tipo: ${typeof value})`);
        }
        
        // Validación final antes del envío
        const finalIdSba05 = formData.get('id_sba05');
        const finalFecha = formData.get('fecha');
        
        if (!finalIdSba05 || finalIdSba05 === 'undefined' || finalIdSba05 === 'null') {
            console.error('[FINAL ERROR] FormData id_sba05 inválido:', finalIdSba05);
            mostrarAlerta('Error', 'ERROR FINAL: ID SBA05 inválido en FormData: ' + finalIdSba05);
            botonElemento.disabled = false;
            botonElemento.innerHTML = '<i class="bi bi-check"></i>';
            return;
        }
        
        console.log('[DEBUG] Validación final OK. Enviando petición...');
        
        const response = await fetch('controller/caja_ingresos_controller.php?' + new Date().getTime(), { // Cache busting
            method: 'POST',
            body: formData
        });
        
        console.log('[DEBUG] Respuesta recibida, status:', response.status);
        const responseText = await response.text();
        console.log('[DEBUG] Texto de respuesta:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('[ERROR] Error parseando JSON:', e);
            throw new Error('Respuesta del servidor no es JSON válido: ' + responseText);
        }
        
        if (result.success) {
            console.log('✅ Ingreso de tesorería marcado como recibido');
            
            // Cambiar el checkbox a "marcado" visualmente INMEDIATAMENTE
            botonElemento.disabled = true;
            botonElemento.classList.add('checked');
            botonElemento.innerHTML = '<i class="bi bi-check-square-fill text-success"></i>';
            
            // Pequeño delay antes de recargar para dar tiempo al backend
            setTimeout(() => {
                // Recargar usando las fechas de los filtros si están disponibles
                const fechaDesde = document.getElementById('fechaReporteDesde')?.value;
                const fechaHasta = document.getElementById('fechaReporteHasta')?.value;
                
                if (fechaDesde && fechaHasta) {
                    cargarReporte({
                        fecha_desde: fechaDesde,
                        fecha_hasta: fechaHasta,
                        aplicadoManualmente: true
                    });
                    // Actualizar todas las tarjetas con los filtros activos
                    actualizarResumen(false, {
                        fecha_desde: fechaDesde,
                        fecha_hasta: fechaHasta
                    });
                } else {
                    cargarReporte();
                    // Actualizar todas las tarjetas sin filtros
                    actualizarResumen();
                }
            }, 300); // 300ms de delay
        } else {
            // Restaurar botón en caso de error
            botonElemento.disabled = false;
            botonElemento.innerHTML = '<i class="bi bi-check"></i>';
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('[ERROR] Error en marcarRecibidoTesoreria:', error);
        // Restaurar botón en caso de error
        botonElemento.disabled = false;
        botonElemento.classList.remove('checked');
        botonElemento.textContent = '☐';
        mostrarAlerta('Error', 'No se pudo procesar la solicitud: ' + error.message);
    }
}

// Exportar reporte (función placeholder para futura implementación)
function exportarReporte() {
    mostrarAlerta('Información', 'La función de exportación estará disponible en la próxima versión');
}

// Exportar Reporte de Saldo a Excel
async function exportarReporteExcel() {
    try {
        // Verificar que SheetJS esté disponible
        if (typeof XLSX === 'undefined') {
            mostrarAlerta('Error', 'La librería de exportación no está disponible');
            return;
        }
        
        // Obtener filtros aplicados
        const fechaDesde = document.getElementById('fechaReporteDesde').value;
        const fechaHasta = document.getElementById('fechaReporteHasta').value;
        
        if (!fechaDesde || !fechaHasta) {
            mostrarAlerta('Error', 'Por favor selecciona un rango de fechas');
            return;
        }
        
        // Obtener cantidad seleccionada para paginación
        const cantidadSeleccionada = parseInt(localStorage.getItem('reporteCantidadPorPagina') || '50');
        
        // Cargar movimientos con los filtros actuales
        let url = `controller/caja_reporte_controller.php?accion=movimientos&_=${Date.now()}`;
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
        
        if (!result.success || !result.data || result.data.length === 0) {
            mostrarAlerta('Información', 'No hay datos para exportar');
            return;
        }
        
        const movimientos = result.data;
        const movimientosPaginados = movimientos.slice(0, cantidadSeleccionada);
        
        // Preparar datos para Excel
        const datosExcel = [];
        
        // Agregar encabezado
        datosExcel.push([
            'Fecha',
            'Tipo',
            'COMP.',
            'Concepto',
            'Importe',
            'Origen',
            'Estado'
        ]);
        
        let saldoAcumulado = 0;
        
        // Agregar filas de datos
        movimientosPaginados.forEach(mov => {
            const fecha = new Date(mov.fecha + 'T00:00:00').toLocaleDateString('es-AR');
            const compDisplay = (mov.cod_comp && mov.n_comp) ? `${mov.cod_comp}${mov.n_comp}` : '-';
            
            let origen = '';
            switch(mov.origen) {
                case 'MANUAL':
                    origen = 'Manual';
                    break;
                case '599':
                    origen = '599';
                    break;
                case 'TESORERIA':
                    origen = 'Tesorería';
                    break;
                default:
                    origen = 'Manual';
            }
            
            let estado = '';
            if (mov.tipo === 'INGRESO') {
                estado = mov.recibido == 1 ? 'Recibido' : 'Pendiente';
                if (mov.recibido == 1) {
                    saldoAcumulado += parseFloat(mov.importe);
                }
            } else {
                estado = 'Pagado';
                saldoAcumulado -= parseFloat(mov.importe);
            }
            
            datosExcel.push([
                fecha,
                mov.tipo,
                compDisplay,
                mov.concepto,
                parseFloat(mov.importe),
                origen,
                estado
            ]);
        });
        
        // Agregar fila en blanco
        datosExcel.push([]);
        
        // Agregar fila de total
        datosExcel.push([
            '',
            '',
            '',
            'Saldo (Rango Seleccionado):',
            saldoAcumulado,
            '',
            ''
        ]);
        
        // Crear libro de Excel
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(datosExcel);
        
        // Configurar ancho de columnas
        ws['!cols'] = [
            { wch: 12 }, // Fecha
            { wch: 10 }, // Tipo
            { wch: 10 }, // COMP.
            { wch: 40 }, // Concepto
            { wch: 15 }, // Importe
            { wch: 12 }, // Origen
            { wch: 12 }  // Estado
        ];
        
        // Estilo para el encabezado (primera fila)
        const range = XLSX.utils.decode_range(ws['!ref']);
        for (let C = range.s.c; C <= range.e.c; ++C) {
            const address = XLSX.utils.encode_col(C) + "1";
            if (!ws[address]) continue;
            ws[address].s = {
                font: { bold: true },
                fill: { fgColor: { rgb: "4472C4" } },
                alignment: { horizontal: "center" }
            };
        }
        
        // Formato de moneda para la columna de Importe
        for (let R = 1; R <= range.e.r; ++R) {
            const cell_address = XLSX.utils.encode_cell({ r: R, c: 4 }); // Columna E (Importe)
            if (!ws[cell_address]) continue;
            ws[cell_address].z = '"$"#,##0';
        }
        
        // Agregar hoja al libro
        XLSX.utils.book_append_sheet(wb, ws, 'Reporte de Saldo');
        
        // Generar nombre de archivo con fechas
        const nombreArchivo = `Reporte_Saldo_${fechaDesde}_${fechaHasta}.xlsx`;
        
        // Descargar archivo
        XLSX.writeFile(wb, nombreArchivo);
        
        mostrarAlerta('Éxito', `Reporte de Saldo exportado correctamente como: ${nombreArchivo}`);
        
    } catch (error) {
        console.error('Error al exportar reporte:', error);
        mostrarAlerta('Error', 'No se pudo exportar el reporte');
    }
}

// Cargar reporte al mostrar la pestaña
document.getElementById('reporte-tab')?.addEventListener('shown.bs.tab', function() {
    // Primero cargar totales generales
    actualizarResumen();
    // Luego inicializar fechas y cargar datos específicos
    inicializarFechasReporte();
    cargarReporte();
});

// Inicializar campos de fecha con valores por defecto (últimos 15 días)
function inicializarFechasReporte() {
    const hoy = new Date();
    const hace15Dias = new Date(hoy);
    hace15Dias.setDate(hoy.getDate() - 15);
    
    document.getElementById('fechaReporteDesde').value = hace15Dias.toISOString().split('T')[0];
    document.getElementById('fechaReporteHasta').value = hoy.toISOString().split('T')[0];
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
    
    // Mostrar indicador de carga inmediatamente
    mostrarCargandoTarjetas(true);
    
    const filtros = {
        fecha_desde: fechaDesde,
        fecha_hasta: fechaHasta,
        aplicadoManualmente: true
    };
    
    // Actualizar tanto el reporte como las tarjetas con los filtros aplicados
    cargarReporte(filtros);
    actualizarResumen(false, filtros);
}

// Limpiar filtros y volver a los últimos 15 días
function limpiarFiltrosReporte() {
    inicializarFechasReporte();
    cargarReporte();
    // Restaurar tarjetas: ingresos/egresos a rango por defecto (últimos 15 días), saldo sigue siendo general
    actualizarResumen();
}

// Cargar datos al iniciar la página
document.addEventListener('DOMContentLoaded', function() {
    // Las tarjetas ahora están solo en la pestaña de reporte
    // Se cargarán cuando el usuario vaya a esa pestaña
    console.log('Página cargada - las tarjetas se actualizarán al ir a la pestaña Reporte');
    
    // Agregar evento para cuando se cambie a la pestaña de reporte
    const reporteTab = document.getElementById('reporte-tab');
    if (reporteTab) {
        reporteTab.addEventListener('shown.bs.tab', function() {
            console.log('🔄 Usuario cambió a pestaña Reporte - actualizando tarjetas...');
            actualizarResumen();
        });
    }
});

// IMPORTANTE: Exponer funciones críticas al scope global para onclick
window.marcarRecibidoTesoreria = marcarRecibidoTesoreria;
window.marcarRecibidoDesdeReporte = marcarRecibidoDesdeReporte;
window.cambiarCantidadMovimientos = cambiarCantidadMovimientos;
window.aplicarFiltrosReporte = aplicarFiltrosReporte;
window.limpiarFiltrosReporte = limpiarFiltrosReporte;
// window.verDetalleMovimiento = verDetalleMovimiento; // TODO: Implementar esta función si es necesaria
window.exportarReporteExcel = exportarReporteExcel;
window.verFotoEgreso = verFotoEgreso;