/**
 * Reporte de Saldo de Caja
 */

// ===========================================
// VARIABLE GLOBAL PARA FECHA DE INICIO
// ===========================================
let FECHA_INICIO_APP = null;

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

// Cargar fecha de inicio de la aplicación desde el servidor
async function cargarFechaInicioApp() {
    try {
        const response = await fetch('controller/caja_reporte_controller.php?accion=fecha_inicio_app');
        const result = await response.json();
        
        if (result.success && result.fecha_inicio) {
            FECHA_INICIO_APP = result.fecha_inicio;
            console.log('[CONFIG] Fecha de inicio de la app cargada:', FECHA_INICIO_APP);
        } else {
            // Fallback a fecha por defecto si falla
            FECHA_INICIO_APP = '2025-11-05';
            console.warn('[CONFIG] No se pudo cargar fecha de inicio, usando fallback:', FECHA_INICIO_APP);
        }
    } catch (error) {
        // Fallback a fecha por defecto si falla
        FECHA_INICIO_APP = '2025-11-05';
        console.error('[CONFIG] Error al cargar fecha de inicio, usando fallback:', FECHA_INICIO_APP, error);
    }
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
    
    // Asegurar que tenemos la fecha de inicio cargada
    if (!FECHA_INICIO_APP) {
        await cargarFechaInicioApp();
    }
    
    // Mostrar indicador de carga
    mostrarCargandoTarjetas(true);
    
    // Mostrar indicador de carga
    mostrarCargandoTarjetas(true);
    
    try {
        // Para calcular SALDO: usar desde la fecha de inicio de la app hasta hoy
        const hoy = new Date();
        const fechaDesdeSaldo = FECHA_INICIO_APP; // Usar la fecha de inicio configurada
        const fechaHastaSaldo = hoy.toISOString().split('T')[0];
        
        console.log('[DEBUG] Calculando saldo desde:', fechaDesdeSaldo, 'hasta:', fechaHastaSaldo);
        
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
                    console.log('📅 Usando filtros de fecha:', fechaDesdeIngEgr, 'a', fechaHastaIngEgr);
                } else {
                    // No hay filtros activos: usar rango por defecto (últimos 15 días)
                    const hace15Dias = new Date(hoy);
                    hace15Dias.setDate(hoy.getDate() - 15);
                    fechaDesdeIngEgr = hace15Dias.toISOString().split('T')[0];
                    fechaHastaIngEgr = hoy.toISOString().split('T')[0];
                    console.log('📅 Usando rango por defecto (últimos 15 días):', fechaDesdeIngEgr, 'a', fechaHastaIngEgr);
                }
                
                console.log('🌐 URL completa para movimientos:', `controller/caja_reporte_controller.php?accion=movimientos&fecha_desde=${fechaDesdeIngEgr}&fecha_hasta=${fechaHastaIngEgr}`);
                
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
                    
                    console.log('=== DEBUG: Procesando movimientos para tarjetas ===');
                    console.log('Total de movimientos recibidos:', resultIngEgr.data.length);
                    
                    // Filtrar y mostrar solo egresos con nuevos motivos
                    const egresosNuevosMotivos = resultIngEgr.data.filter(mov => 
                        mov.tipo === 'EGRESO' && 
                        (mov.concepto.includes('Pago de tarjetas') || 
                         mov.concepto.includes('Transf. Haberes') || 
                         mov.concepto.includes('Otros'))
                    );
                    
                    if (egresosNuevosMotivos.length > 0) {
                        console.log('✅ Egresos con nuevos motivos encontrados:', egresosNuevosMotivos);
                    } else {
                        console.log('⚠️ NO se encontraron egresos con nuevos motivos en la respuesta');
                    }
                    
                    resultIngEgr.data.forEach(mov => {
                        if (mov.tipo === 'INGRESO' && mov.recibido == 1) {
                            totalIngresosRango += parseFloat(mov.importe);
                        } else if (mov.tipo === 'EGRESO') {
                            totalEgresosRango += parseFloat(mov.importe);
                            
                            // Log especial para nuevos motivos
                            if (mov.concepto.includes('Otros') || mov.concepto.includes('Pago de tarjetas') || mov.concepto.includes('Transf. Haberes')) {
                                console.log('💰 Sumando egreso nuevo motivo:', mov.concepto, '- Importe:', mov.importe);
                            }
                        }
                    });
                    
                    console.log('Tarjetas actualizadas - Ingresos:', totalIngresosRango, 'Egresos:', totalEgresosRango);
                    console.log('=== FIN DEBUG ===');
                    
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
                
                // *** LÓGICA CORREGIDA PARA EL BOTÓN DE ACCIÓN ***
                // Primero, decidimos si el botón debe "Importar" (crear) o "Actualizar" un registro.
                // El truco es mirar el ID: si empieza con "EXT_", es un ingreso de fuera que hay que importar.
                if (mov.id && String(mov.id).startsWith('EXT_TES_')) {
                    
                    // Es un ingreso de Tesorería que todavía no hemos importado.
                    // Usamos el BOTÓN "IMPORTADOR" (llama a marcarRecibidoTesoreria para CREAR el registro).
                    accionBoton = `
                        <button class="btn btn-outline-success checkbox-style" 
                                onclick="marcarRecibidoTesoreria(this)"
                                data-id-sba05="${mov.ID_SBA05}"
                                data-fecha="${mov.fecha.split('T')[0]}"
                                data-cod-comp="${mov.COD_COMP || ''}"
                                data-n-comp="${mov.N_COMP || ''}"
                                data-concepto="${(mov.observaciones || mov.concepto || '').replace(/"/g, '&quot;')}"
                                data-importe="${mov.importe}"
                                style="width: 32px; height: 32px; padding: 0; border-radius: 4px; border-width: 2px; font-size: 18px;"
                                title="Importar de Tesorería">
                            ☐
                        </button>
                    `;

                } else if (mov.origen === '599') {
                    // Los 599 no tienen acciones, ya vienen recibidos.
                    accionBoton = '<span class="text-muted">-</span>';
                
                } else {
                    
                    // Si el ID no empieza con "EXT_", significa que ya existe en nuestra base de datos.
                    // Puede ser un ingreso Manual o uno de Tesorería que ya importamos.
                    // Usamos el BOTÓN "ACTUALIZADOR" (llama a marcarRecibidoDesdeReporte para ACTUALIZAR el registro).
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
        
        // Formatear concepto: si tiene datos de proveedor extendidos, mostrarlos
        let conceptoDisplay = mov.concepto;
        if (mov.tipo === 'EGRESO' && mov.proveedor_nom) {
            conceptoDisplay += `<br><small class="text-muted">
                <i class="bi bi-building"></i> ${mov.proveedor_nom}`;
            if (mov.proveedor_cbu) {
                conceptoDisplay += `<br><i class="bi bi-bank"></i> CBU: ${mov.proveedor_cbu}`;
            }
            if (mov.proveedor_descripcion_cbu) {
                conceptoDisplay += ` - ${mov.proveedor_descripcion_cbu}`;
            }
            conceptoDisplay += `</small>`;
        }
        
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
                <td class="align-middle">${conceptoDisplay}</td>
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
            const esPdf = result.tipo === 'application/pdf';
            
            if (esPdf) {
                // Para PDFs, crear un enlace de descarga
                const pdfBlob = base64ToBlob(result.foto, 'application/pdf');
                const url = URL.createObjectURL(pdfBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `factura_egreso_${idEgreso}.pdf`;
                link.click();
                URL.revokeObjectURL(url);
            } else {
                // Para imágenes, mostrar en modal
                const modalTitle = document.getElementById('modalFotoEgresoLabel');
                modalTitle.textContent = `Foto del Egreso ID: ${idEgreso}`;
                
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
    console.log('[MANUAL] 🚀 Procesando marcado manual...');
    
    try {
        const ingresoId = botonElemento.dataset.ingresoId;
        
        if (!ingresoId) {
            mostrarAlerta('Error', 'No se pudo obtener el ID del ingreso');
            return;
        }
        
        // Deshabilitar botón visualmente
        botonElemento.disabled = true;
        botonElemento.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        const formData = new FormData();
        formData.append('accion', 'marcar_recibido');
        formData.append('id', ingresoId);
        
        const response = await fetch('controller/caja_ingresos_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ Ingreso marcado correctamente');
            
            // 1. ACTUALIZACIÓN VISUAL INMEDIATA (OPTIMISTA)
            botonElemento.disabled = true;
            botonElemento.classList.add('checked');
            botonElemento.innerHTML = '<i class="bi bi-check-square-fill text-success"></i>';
            botonElemento.title = "Recibido";

            // 2. ACTUALIZAR LA CELDA DE "ESTADO" (Columna 7, índice 7 si empezamos en 0)
            const fila = botonElemento.closest('tr');
            if (fila) {
                // Buscamos la celda que contiene el badge (usualmente la anteúltima)
                // En tu tabla HTML la estructura es: Fecha, Tipo, Comp, Concepto, Importe, Origen, Foto, ESTADO, Acciones
                // Estado es la columna index 7
                if(fila.cells[7]) {
                    fila.cells[7].innerHTML = '<span class="badge bg-success">Recibido</span>';
                }
            }

            // 3. ACTUALIZAR SOLO LOS TOTALES (TARJETAS)
            // No recargamos la tabla (cargarReporte) para evitar que el botón "parpadee" 
            // si la DB es lenta.
            const fechaDesde = document.getElementById('fechaReporteDesde')?.value;
            const fechaHasta = document.getElementById('fechaReporteHasta')?.value;
            
            // Damos un poco más de tiempo (800ms) para que los totales calculen bien
            setTimeout(() => {
                if (fechaDesde && fechaHasta) {
                    actualizarResumen(false, { fecha_desde: fechaDesde, fecha_hasta: fechaHasta });
                } else {
                    actualizarResumen();
                }
            }, 800);

        } else {
            // Restaurar botón en caso de error
            botonElemento.disabled = false;
            botonElemento.innerHTML = '<i class="bi bi-check"></i>'; // O el ícono cuadrado
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('[ERROR]', error);
        botonElemento.disabled = false;
        botonElemento.innerHTML = '☐';
        mostrarAlerta('Error', 'Error de conexión');
    }
}

// Marcar ingreso TESORERÍA como recibido
async function marcarRecibidoTesoreria(botonElemento) {
    console.log('[TESORERÍA] 🚀 Procesando importación...');
    
    try {
        // Obtener datos
        const idSba05 = botonElemento.dataset.idSba05;
        const fecha = botonElemento.dataset.fecha;
        const codComp = botonElemento.dataset.codComp;
        const nComp = botonElemento.dataset.nComp;
        const concepto = botonElemento.dataset.concepto;
        const importe = botonElemento.dataset.importe;
        
        // Deshabilitar botón
        botonElemento.disabled = true;
        botonElemento.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        const formData = new FormData();
        formData.append('accion', 'marcar_recibido_tesoreria');
        formData.append('id_sba05', String(idSba05)); 
        formData.append('fecha', String(fecha));
        formData.append('cod_comp', String(codComp || ''));
        formData.append('n_comp', String(nComp || ''));
        formData.append('observaciones', String(concepto || ''));
        formData.append('importe', String(importe || 0));
        
        const response = await fetch('controller/caja_ingresos_controller.php?' + new Date().getTime(), {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ Tesorería importada correctamente');
            
            // 1. ACTUALIZACIÓN VISUAL INMEDIATA
            botonElemento.disabled = true;
            botonElemento.classList.add('checked');
            botonElemento.innerHTML = '<i class="bi bi-check-square-fill text-success"></i>';
            botonElemento.title = "Importado";
            
            // 2. ACTUALIZAR CELDA DE ESTADO
            const fila = botonElemento.closest('tr');
            if (fila && fila.cells[7]) {
                fila.cells[7].innerHTML = '<span class="badge bg-success">Recibido</span>';
            }

            // 3. ACTUALIZAR TOTALES (sin recargar tabla)
            const fechaDesde = document.getElementById('fechaReporteDesde')?.value;
            const fechaHasta = document.getElementById('fechaReporteHasta')?.value;
            
            setTimeout(() => {
                if (fechaDesde && fechaHasta) {
                    actualizarResumen(false, { fecha_desde: fechaDesde, fecha_hasta: fechaHasta });
                } else {
                    actualizarResumen();
                }
            }, 800);
            
        } else {
            botonElemento.disabled = false;
            botonElemento.innerHTML = '☐';
            mostrarAlerta('Error', result.message);
        }
    } catch (error) {
        console.error('[ERROR]', error);
        botonElemento.disabled = false;
        botonElemento.innerHTML = '☐';
        mostrarAlerta('Error', 'Error de conexión: ' + error.message);
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
document.getElementById('reporte-tab')?.addEventListener('shown.bs.tab', async function() {
    // Asegurar que tenemos la fecha de inicio cargada
    if (!FECHA_INICIO_APP) {
        await cargarFechaInicioApp();
    }
    
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
    // Cargar la fecha de inicio de la app
    cargarFechaInicioApp();
    
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