/**
 * Script para gestión de Resumen de Servicios
 * Maneja filtros, carga de datos y renderizado de tablas
 */

// Variables globales
let fechaDesdeResumenServicios = '';
let fechaHastaResumenServicios = '';
let resumenCompletoServicios = null; // Almacena el resumen completo
let cargandoDatosServicios = false; // Flag para evitar cargas concurrentes

/**
 * Inicialización cuando el documento está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    // Configurar fechas por defecto (últimos 15 días)
    configurarFechasPorDefectoServicios();
    
    // Agregar listener para cuando se active la pestaña
    const tabResumenServicios = document.getElementById('resumen-servicios-tab');
    if (tabResumenServicios) {
        tabResumenServicios.addEventListener('shown.bs.tab', function() {
            console.log('🔄 Pestaña Resumen Servicios activada - recargando datos...');
            // Asegurar que las fechas estén configuradas
            if (!fechaDesdeResumenServicios || !fechaHastaResumenServicios) {
                configurarFechasPorDefectoServicios();
            }
            // Recargar datos SIEMPRE que se muestre la pestaña
            cargarDatosResumenServicios();
        });
    }
    
    // Agregar listeners a los inputs de fecha
    const inputDesde = document.getElementById('fechaResumenServiciosDesde');
    const inputHasta = document.getElementById('fechaResumenServiciosHasta');
    
    if (inputDesde) {
        inputDesde.addEventListener('change', function() {
            fechaDesdeResumenServicios = this.value;
        });
    }
    
    if (inputHasta) {
        inputHasta.addEventListener('change', function() {
            fechaHastaResumenServicios = this.value;
        });
    }
});

/**
 * Configura las fechas por defecto (mes actual completo)
 */
function configurarFechasPorDefectoServicios() {
    const hoy = new Date();
    
    // Primer día del mes actual
    const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    
    // Último día del mes actual
    const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
    
    fechaDesdeResumenServicios = formatearFechaServicios(primerDiaMes);
    fechaHastaResumenServicios = formatearFechaServicios(ultimoDiaMes);
    
    // Actualizar inputs
    const inputDesde = document.getElementById('fechaResumenServiciosDesde');
    const inputHasta = document.getElementById('fechaResumenServiciosHasta');
    
    if (inputDesde) inputDesde.value = fechaDesdeResumenServicios;
    if (inputHasta) inputHasta.value = fechaHastaResumenServicios;
}

/**
 * Formatea una fecha al formato YYYY-MM-DD
 */
function formatearFechaServicios(fecha) {
    const year = fecha.getFullYear();
    const month = String(fecha.getMonth() + 1).padStart(2, '0');
    const day = String(fecha.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Formatea un número como moneda
 */
function formatearMonedaServicios(valor) {
    const numero = parseFloat(valor) || 0;
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(numero);
}

/**
 * Aplica los filtros de fecha
 */
function aplicarFiltrosResumenServicios() {
    // Validar fechas
    if (!fechaDesdeResumenServicios || !fechaHastaResumenServicios) {
        mostrarAlerta('Por favor, seleccione ambas fechas', 'warning');
        return;
    }
    
    // Validar que fecha desde sea menor o igual a fecha hasta
    if (fechaDesdeResumenServicios > fechaHastaResumenServicios) {
        mostrarAlerta('La fecha "Desde" debe ser anterior o igual a la fecha "Hasta"', 'warning');
        return;
    }
    
    // Cargar datos
    cargarDatosResumenServicios();
}

/**
 * Limpia los filtros y recarga con valores por defecto
 */
function limpiarFiltrosResumenServicios() {
    configurarFechasPorDefectoServicios();
    cargarDatosResumenServicios();
}

/**
 * Carga todos los datos de resumen de servicios
 */
async function cargarDatosResumenServicios() {
    // Evitar llamadas concurrentes
    if (cargandoDatosServicios) {
        console.log('⏸️ Ya hay una carga en progreso, ignorando nueva llamada');
        return;
    }
    
    cargandoDatosServicios = true;
    
    try {
        // Validar que las fechas estén configuradas
        if (!fechaDesdeResumenServicios || !fechaHastaResumenServicios) {
            console.warn('⚠️ Fechas no configuradas, configurando por defecto');
            configurarFechasPorDefectoServicios();
        }
        
        console.log('📊 Cargando datos de Resumen Servicios:', fechaDesdeResumenServicios, 'a', fechaHastaResumenServicios);
        
        // Mostrar indicadores de carga
        mostrarCargandoServicios('tablaResumenServicios');
        
        // Cargar resumen y total en paralelo
        const [resumen, total] = await Promise.all([
            cargarResumenServicios(),
            cargarTotalServicios()
        ]);
        
        // Renderizar datos
        renderizarResumenServicios(resumen);
        actualizarTotalServicios(total);
        
        // Guardar datos completos para exportación
        resumenCompletoServicios = resumen;
        
        console.log('✅ Datos de Resumen Servicios cargados correctamente');
        
    } catch (error) {
        console.error('❌ Error al cargar datos de Resumen Servicios:', error);
        mostrarErrorServicios('tablaResumenServicios', 'Error al cargar el resumen');
        mostrarAlerta('Error al cargar los datos de resumen de servicios: ' + error.message, 'danger');
    } finally {
        // Siempre liberar el flag, incluso si hay error
        cargandoDatosServicios = false;
    }
}

/**
 * Carga el resumen de servicios desde el controlador
 */
async function cargarResumenServicios() {
    const params = new URLSearchParams({
        action: 'obtener_resumen',
        fecha_desde: fechaDesdeResumenServicios,
        fecha_hasta: fechaHastaResumenServicios
    });
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 segundos timeout
    
    try {
        const response = await fetch(`controller/resumen_servicios_controller.php?${params}`, {
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error al obtener resumen');
        }
        
        return data.data;
    } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            throw new Error('La petición tardó demasiado tiempo');
        }
        throw error;
    }
}

/**
 * Carga el total de servicios
 */
async function cargarTotalServicios() {
    const params = new URLSearchParams({
        action: 'obtener_total',
        fecha_desde: fechaDesdeResumenServicios,
        fecha_hasta: fechaHastaResumenServicios
    });
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 segundos timeout
    
    try {
        const response = await fetch(`controller/resumen_servicios_controller.php?${params}`, {
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Error al obtener total');
        }
        
        return data.total;
    } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            throw new Error('La petición tardó demasiado tiempo');
        }
        throw error;
    }
}

/**
 * Renderiza la tabla resumen de servicios
 * Estructura: Motivo | Director1 | Director2 | ... | Total
 */
function renderizarResumenServicios(resumen) {
    const container = document.getElementById('tablaResumenServicios');
    
    if (!resumen || !resumen.motivos || resumen.motivos.length === 0) {
        container.innerHTML = `
            <div class="mensaje-sin-datos">
                <i class="bi bi-inbox"></i>
                <p>No hay datos de servicios para mostrar en el período seleccionado</p>
            </div>
        `;
        return;
    }
    
    const motivos = resumen.motivos;
    const directores = resumen.directores;
    const datos = resumen.datos;
    const totalesPorMotivo = resumen.totales_por_motivo;
    const totalesPorDirector = resumen.totales_por_director;
    
    // Construir HTML de la tabla
    let html = '<table class="table table-bordered table-sm">';
    
    // Encabezado
    html += '<thead><tr>';
    html += '<th style="width: 20%;">MOTIVO</th>';
    directores.forEach(director => {
        html += `<th>${director}</th>`;
    });
    html += '<th class="bg-light"><strong>TOTAL</strong></th>';
    html += '</tr></thead>';
    
    // Cuerpo
    html += '<tbody>';
    
    motivos.forEach(motivo => {
        html += '<tr>';
        html += `<td class="fw-bold">${motivo}</td>`;
        
        // Columnas de directores
        directores.forEach(director => {
            const valor = datos[motivo] && datos[motivo][director] ? datos[motivo][director] : 0;
            const clase = valor > 0 ? 'valor-positivo' : 'valor-cero';
            html += `<td class="${clase}">${formatearMonedaServicios(valor)}</td>`;
        });
        
        // Columna de total por motivo
        const totalMotivo = totalesPorMotivo[motivo] || 0;
        html += `<td class="bg-light fw-bold">${formatearMonedaServicios(totalMotivo)}</td>`;
        html += '</tr>';
    });
    
    // Fila de TOTAL
    html += '<tr class="fila-total">';
    html += '<td><strong>TOTAL</strong></td>';
    
    directores.forEach(director => {
        const total = totalesPorDirector[director] || 0;
        html += `<td><strong>${formatearMonedaServicios(total)}</strong></td>`;
    });
    
    // Total general
    let totalGeneral = 0;
    Object.values(totalesPorDirector).forEach(val => {
        totalGeneral += val;
    });
    html += `<td class="bg-light"><strong>${formatearMonedaServicios(totalGeneral)}</strong></td>`;
    
    html += '</tr>';
    html += '</tbody></table>';
    
    container.innerHTML = html;
}

/**
 * Actualiza el total de servicios en la tarjeta
 */
function actualizarTotalServicios(total) {
    const elemento = document.getElementById('totalResumenServicios');
    if (elemento) {
        elemento.textContent = formatearMonedaServicios(total);
    }
}

/**
 * Muestra un indicador de carga
 */
function mostrarCargandoServicios(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="cargando-datos">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p>Cargando datos...</p>
            </div>
        `;
    }
}

/**
 * Muestra un mensaje de error
 */
function mostrarErrorServicios(containerId, mensaje) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> ${mensaje}
            </div>
        `;
    }
}

/**
 * Exporta la tabla resumen a Excel (.xlsx)
 */
function exportarResumenServiciosExcel() {
    if (!resumenCompletoServicios || !resumenCompletoServicios.motivos || resumenCompletoServicios.motivos.length === 0) {
        mostrarAlerta('No hay datos para exportar', 'warning');
        return;
    }
    
    const resumen = resumenCompletoServicios;
    const motivos = resumen.motivos;
    const directores = resumen.directores;
    const datos = resumen.datos;
    const totalesPorMotivo = resumen.totales_por_motivo;
    const totalesPorDirector = resumen.totales_por_director;
    
    // Preparar datos para Excel
    const datosExcel = [];
    
    // Encabezado
    const encabezado = ['MOTIVO', ...directores, 'TOTAL'];
    datosExcel.push(encabezado);
    
    // Filas de motivos
    motivos.forEach(motivo => {
        const fila = [motivo];
        
        // Valores por director
        directores.forEach(director => {
            const valor = datos[motivo] && datos[motivo][director] ? datos[motivo][director] : 0;
            fila.push(valor);
        });
        
        // Total del motivo
        fila.push(totalesPorMotivo[motivo] || 0);
        
        datosExcel.push(fila);
    });
    
    // Fila de TOTAL
    const filaTotalGeneral = ['TOTAL'];
    let totalGeneral = 0;
    
    directores.forEach(director => {
        const total = totalesPorDirector[director] || 0;
        filaTotalGeneral.push(total);
        totalGeneral += total;
    });
    
    filaTotalGeneral.push(totalGeneral);
    datosExcel.push(filaTotalGeneral);
    
    // Crear libro de Excel
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(datosExcel);
    
    // Ajustar ancho de columnas
    const wscols = [{ wch: 25 }]; // Primera columna (MOTIVO)
    directores.forEach(() => wscols.push({ wch: 15 })); // Columnas de directores
    wscols.push({ wch: 15 }); // Columna TOTAL
    ws['!cols'] = wscols;
    
    // Agregar hoja al libro
    XLSX.utils.book_append_sheet(wb, ws, 'Resumen Servicios');
    
    // Generar nombre de archivo
    const nombreArchivo = `Resumen_Servicios_${fechaDesdeResumenServicios}_a_${fechaHastaResumenServicios}.xlsx`;
    
    // Descargar archivo
    XLSX.writeFile(wb, nombreArchivo);
    
    mostrarAlerta('Éxito', `Resumen de Servicios exportado correctamente como: ${nombreArchivo}`);
}

// Hacer funciones disponibles globalmente
window.aplicarFiltrosResumenServicios = aplicarFiltrosResumenServicios;
window.limpiarFiltrosResumenServicios = limpiarFiltrosResumenServicios;
window.cargarDatosResumenServicios = cargarDatosResumenServicios;
window.exportarResumenServiciosExcel = exportarResumenServiciosExcel;
