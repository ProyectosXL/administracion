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
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>COMP.</th>
                        <th>Concepto</th>
                        <th class="text-end">Importe</th>
                        <th class="text-center">Origen</th>
                        <th class="text-center">Foto</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
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
                    accionBoton = `
                        <button class="btn btn-outline-success checkbox-style" 
                                onclick="marcarRecibidoDesdeReporte('${mov.id}')"
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
                <button class="btn btn-outline-primary btn-sm" 
                        onclick="verFotoEgreso(${mov.id})"
                        title="Ver foto del comprobante"
                        style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; line-height: 1;">
                    <i class="bi bi-camera" style="font-size: 14px;"></i>
                </button>
            `;
        } else {
            fotoBoton = '<span class="text-muted">-</span>';
        }
        
        html += `
            <tr>
                <td>${fecha}</td>
                <td><i class="bi bi-${tipoIcon} ${tipoClass}"></i> ${mov.tipo}</td>
                <td><small>${compDisplay}</small></td>
                <td>${mov.concepto}</td>
                <td class="text-end ${tipoClass}"><strong>${importe}</strong></td>
                <td class="text-center">${origenBadge}</td>
                <td class="text-center">${fotoBoton}</td>
                <td class="text-center">${estadoBadge}</td>
                <td class="text-center">${accionBoton}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end"><strong>Saldo Calculado Rango Seleccionado:</strong></td>
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
        } else {
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error', 'No se pudo procesar la solicitud');
    }
}

// Marcar ingreso TESORERÍA como recibido
async function marcarRecibidoTesoreria(botonElemento) {
    console.log('[DEBUG] marcarRecibidoTesoreria iniciada - Versión:', new Date().getTime());
    console.log('[DEBUG] Elemento recibido:', botonElemento);
    
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
        
        // Cambiar el checkbox a "marcado" visualmente mientras se procesa
        botonElemento.classList.add('checked');
        botonElemento.textContent = '☑';
        
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
            mostrarAlerta('Éxito', result.message);
            
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
        } else {
            // Restaurar botón en caso de error
            botonElemento.disabled = false;
            botonElemento.classList.remove('checked');
            botonElemento.textContent = '☐';
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

// Botón actualizar datos del sidebar
document.getElementById('btnActualizarDatos')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    // Usar fechas de los filtros si están disponibles para actualizar tarjetas
    const fechaDesde = document.getElementById('fechaReporteDesde')?.value;
    const fechaHasta = document.getElementById('fechaReporteHasta')?.value;
    
    if (fechaDesde && fechaHasta) {
        // Hay filtros activos: actualizar tarjetas con esos filtros
        actualizarResumen(false, {
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta
        });
        cargarReporte({
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta
        });
    } else {
        // No hay filtros: usar comportamiento por defecto
        actualizarResumen();
        cargarReporte();
    }
    
    mostrarAlerta('Éxito', 'Datos actualizados correctamente');
});

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