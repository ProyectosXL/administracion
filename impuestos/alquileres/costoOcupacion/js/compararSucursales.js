/**
 * Módulo de Comparación de Sucursales
 * Maneja la lógica del frontend para comparar costos entre sucursales
 */

let dataTableComparacionInstance = null;
let datosComparacionActuales = null;

$(document).ready(function() {
    inicializarEventosComparacion();
});

/**
 * Inicializa los event listeners para comparación
 */
function inicializarEventosComparacion() {
    $('#btnCompararSucursales').on('click', compararSucursales);
    $('#btnLimpiarComparar').on('click', limpiarFiltrosComparacion);
    $('#btnExportarComparar').on('click', exportarComparacion);
    $('#btnLeyendaComparar').on('click', mostrarLeyendaComparacion);
    
    // Enter en los inputs
    $('#selectSucursal1, #selectSucursal2, #fechaDesdeComparar, #fechaHastaComparar').on('keypress', function(e) {
        if (e.which === 13) {
            compararSucursales();
        }
    });
    
    // Inicializar Select2 para las sucursales
    $('#selectSucursal1, #selectSucursal2').select2({
        placeholder: "Seleccionar sucursal...",
        allowClear: true,
        width: '100%'
    });
}

/**
 * Realiza la comparación entre sucursales
 */
function compararSucursales() {
    const idSucursal1 = $('#selectSucursal1').val();
    const idSucursal2 = $('#selectSucursal2').val();
    const fechaDesde = $('#fechaDesdeComparar').val();
    const fechaHasta = $('#fechaHastaComparar').val();
    
    // Validaciones
    if (!idSucursal1 || !idSucursal2) {
        Swal.fire({
            icon: 'warning',
            title: 'Datos incompletos',
            text: 'Debe seleccionar ambas sucursales para comparar',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    if (idSucursal1 === idSucursal2) {
        Swal.fire({
            icon: 'warning',
            title: 'Sucursales iguales',
            text: 'Debe seleccionar dos sucursales diferentes',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    if (fechaDesde && fechaHasta && new Date(fechaDesde) > new Date(fechaHasta)) {
        Swal.fire({
            icon: 'error',
            title: 'Fechas inválidas',
            text: 'La fecha "Desde" debe ser anterior a la fecha "Hasta"',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Comparando sucursales',
        text: 'Por favor espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Petición AJAX
    $.ajax({
        url: 'Controller/compararSucursalesController.php',
        method: 'POST',
        data: {
            action: 'comparar',
            id_sucursal_1: idSucursal1,
            id_sucursal_2: idSucursal2,
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta
        },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (response.success) {
                datosComparacionActuales = response.data;
                renderizarComparacion(response.data);
            } else {
                mostrarErrorComparacion(response.message || 'No se encontraron datos para la comparación');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('Error AJAX:', error);
            mostrarErrorComparacion('Error de conexión con el servidor');
        }
    });
}

/**
 * Renderiza los datos de comparación en la interfaz
 */
function renderizarComparacion(data) {
    // Ocultar empty state
    $('#emptyStateComparar').hide();
    
    // Mostrar secciones
    $('#comparacionKpi').fadeIn();
    $('#tableSectionComparar').fadeIn();
    $('#btnExportarComparar').show();
    $('#btnPDFComparar').show();
    
    // Renderizar KPI de resumen
    renderizarKpiComparacion(data.resumen);
    
    // Renderizar tabla de comparación
    renderizarTablaComparacion(data.sucursal1, data.sucursal2, data.filas);
}

/**
 * Renderiza el KPI de resumen de comparación
 */
function renderizarKpiComparacion(resumen) {
    console.log('Renderizando KPI comparación:', resumen); // Debug
    
    // Nombres de sucursales
    $('#nombreSucursal1').text(resumen.nombre_sucursal_1);
    $('#nombreSucursal2').text(resumen.nombre_sucursal_2);
    
    // Valores de costo de ocupación con manejo de null
    const valor1 = resumen.costo_ocupacion_1;
    const valor2 = resumen.costo_ocupacion_2;
    
    $('#valorSucursal1').text(valor1 !== null ? formatearPorcentaje(valor1) : 'Sin datos');
    $('#valorSucursal2').text(valor2 !== null ? formatearPorcentaje(valor2) : 'Sin datos');
    
    // Diferencia
    const diferencia = resumen.diferencia;
    
    if (diferencia !== null && diferencia !== undefined && !isNaN(diferencia)) {
        const diferenciAbs = Math.abs(diferencia);
        $('#valorDiferencia').text(diferenciAbs.toFixed(2) + ' pp'); // pp = puntos porcentuales
        
        const kpiDiferencia = $('.kpi-diferencia');
        kpiDiferencia.removeClass('mejor peor igual');
        
        if (diferencia > 0.5) {
            // Sucursal 2 tiene mayor costo (peor)
            kpiDiferencia.addClass('peor');
            $('#textoDiferencia').text('Mayor costo');
        } else if (diferencia < -0.5) {
            // Sucursal 2 tiene menor costo (mejor)
            kpiDiferencia.addClass('mejor');
            $('#textoDiferencia').text('Menor costo');
        } else {
            // Diferencia mínima
            kpiDiferencia.addClass('igual');
            $('#textoDiferencia').text('Similar');
        }
    } else {
        $('#valorDiferencia').text('--');
        $('.kpi-diferencia').removeClass('mejor peor igual').addClass('igual');
        $('#textoDiferencia').text('Sin datos');
    }
}

/**
 * Renderiza la tabla de comparación
 */
function renderizarTablaComparacion(sucursal1, sucursal2, filas) {
    // Destruir tabla existente si existe
    if (dataTableComparacionInstance) {
        dataTableComparacionInstance.destroy();
        $('#tablaComparacionSucursales').empty();
    }
    
    // Construir header
    let headerHtml = '<thead><tr>';
    headerHtml += '<th class="fixed-column">CONCEPTO</th>';
    headerHtml += `<th class="col-sucursal-1">${sucursal1.nombre}</th>`;
    headerHtml += `<th class="col-sucursal-2">${sucursal2.nombre}</th>`;
    headerHtml += '<th class="col-variacion">VARIACIÓN</th>';
    headerHtml += '</tr></thead>';
    
    // Construir body
    let bodyHtml = '<tbody>';
    
    filas.forEach(fila => {
        let rowClass = '';
        
        if (fila.is_subtotal) {
            rowClass = 'row-subtotal-comparar';
        } else if (fila.is_percentage) {
            rowClass = 'row-percentage-comparar';
        }
        
        bodyHtml += `<tr class="${rowClass}">`;
        bodyHtml += `<td class="fixed-column"><strong>${fila.concepto}</strong></td>`;
        
        // Valor Sucursal 1
        const valor1 = fila.valor_sucursal_1;
        const contenido1 = fila.is_percentage ? formatearPorcentaje(valor1) : formatearMoneda(valor1);
        bodyHtml += `<td class="col-sucursal-1 text-right">${contenido1}</td>`;
        
        // Valor Sucursal 2
        const valor2 = fila.valor_sucursal_2;
        const contenido2 = fila.is_percentage ? formatearPorcentaje(valor2) : formatearMoneda(valor2);
        bodyHtml += `<td class="col-sucursal-2 text-right">${contenido2}</td>`;
        
        // Variación
        let variacionHtml = '--';
        if (valor1 !== null && valor2 !== null && valor1 !== 0) {
            const variacionPorcentual = ((valor2 - valor1) / valor1) * 100;
            const signo = variacionPorcentual > 0 ? '+' : '';
            const claseVariacion = variacionPorcentual > 0 ? 'variacion-positiva' : 
                                  variacionPorcentual < 0 ? 'variacion-negativa' : 'variacion-neutra';
            
            variacionHtml = `<span class="${claseVariacion}">${signo}${variacionPorcentual.toFixed(2)}%</span>`;
        }
        
        bodyHtml += `<td class="col-variacion text-center">${variacionHtml}</td>`;
        bodyHtml += '</tr>';
    });
    
    bodyHtml += '</tbody>';
    
    // Actualizar HTML
    $('#tablaComparacionSucursales').html(headerHtml + bodyHtml);
    
    // Inicializar DataTable
    dataTableComparacionInstance = $('#tablaComparacionSucursales').DataTable({
        responsive: false,
        scrollX: true,
        scrollY: '500px',
        scrollCollapse: true,
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        fixedColumns: {
            leftColumns: 1
        },
        language: {
            emptyTable: "No hay datos disponibles",
            zeroRecords: "No se encontraron registros"
        },
        dom: 'Bfrtip',
        buttons: []
    });
}

/**
 * Limpia los filtros de comparación
 */
function limpiarFiltrosComparacion() {
    // Limpiar selects
    $('#selectSucursal1, #selectSucursal2').val('').trigger('change');
    
    // Resetear fechas al rango default
    $.ajax({
        url: 'Controller/costoOcupacionController.php?accion=obtenerRangoDefault',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#fechaDesdeComparar').val(response.data.desde);
                $('#fechaHastaComparar').val(response.data.hasta);
            }
        }
    });
    
    // Ocultar secciones y mostrar empty state
    $('#comparacionKpi').hide();
    $('#tableSectionComparar').hide();
    $('#btnExportarComparar').hide();
    $('#btnPDFComparar').hide();
    $('#emptyStateComparar').fadeIn();
    
    // Limpiar datos
    datosComparacionActuales = null;
    
    if (dataTableComparacionInstance) {
        dataTableComparacionInstance.destroy();
        $('#tablaComparacionSucursales').empty();
        dataTableComparacionInstance = null;
    }
}

/**
 * Exporta los datos de comparación a Excel
 */
function exportarComparacion() {
    if (!datosComparacionActuales) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin datos',
            text: 'No hay datos para exportar',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    // Usar DataTables buttons para exportar
    if (dataTableComparacionInstance) {
        // Crear botón temporal de Excel
        const excelButton = dataTableComparacionInstance.button().add(0, {
            extend: 'excel',
            text: 'Excel',
            title: 'Comparación de Sucursales',
            filename: 'comparacion_sucursales_' + new Date().toISOString().split('T')[0],
            exportOptions: {
                columns: ':visible'
            }
        });
        
        // Trigger click
        excelButton.trigger();
        
        // Remover botón
        excelButton.remove();
    }
}

/**
 * Muestra la leyenda específica para comparación
 */
function mostrarLeyendaComparacion() {
    Swal.fire({
        title: 'Leyenda de Comparación',
        icon: 'info',
        html: `
            <div style="text-align: left; padding: 20px;">
                <h4 style="margin-bottom: 15px; color: #2c3e50;">Columnas de Sucursales:</h4>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="width: 20px; height: 20px; background-color: rgba(52, 152, 219, 0.2); border-left: 3px solid #3498db; margin-right: 10px; border-radius: 3px;"></div>
                    <span><strong>Azul:</strong> Datos de la primera sucursal seleccionada</span>
                </div>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="width: 20px; height: 20px; background-color: rgba(155, 89, 182, 0.2); border-left: 3px solid #9b59b6; margin-right: 10px; border-radius: 3px;"></div>
                    <span><strong>Morado:</strong> Datos de la segunda sucursal seleccionada</span>
                </div>
                <div style="margin-bottom: 10px; display: flex; align-items: center;">
                    <div style="width: 20px; height: 20px; background-color: rgba(241, 196, 15, 0.2); border-left: 3px solid #f1c40f; margin-right: 10px; border-radius: 3px;"></div>
                    <span><strong>Amarillo:</strong> Columna de variación porcentual</span>
                </div>
                <hr style="margin: 15px 0;">
                <h4 style="margin-bottom: 15px; color: #2c3e50;">Interpretación de Variación:</h4>
                <div style="margin-bottom: 8px; color: #e74c3c;">
                    <strong>Rojo (+):</strong> La segunda sucursal tiene mayor costo (peor rendimiento)
                </div>
                <div style="margin-bottom: 8px; color: #27ae60;">
                    <strong>Verde (-):</strong> La segunda sucursal tiene menor costo (mejor rendimiento)
                </div>
                <div style="margin-bottom: 8px; color: #7f8c8d;">
                    <strong>Gris:</strong> Sin variación significativa o datos no comparables
                </div>
                <hr style="margin: 15px 0;">
                <h4 style="margin-bottom: 10px; color: #2c3e50;">Nota:</h4>
                <p style="margin: 0; font-size: 14px; color: #7f8c8d;">
                    • Los totales se calculan para el período completo seleccionado<br>
                    • Las filas de Venta Bruta y Venta Neta no se incluyen en la comparación<br>
                    • La variación se calcula como: ((Sucursal2 - Sucursal1) / Sucursal1) × 100
                </p>
            </div>
        `,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#3498db',
        width: '650px'
    });
}

/**
 * Muestra un mensaje de error específico para comparación
 */
function mostrarErrorComparacion(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error en la comparación',
        text: mensaje,
        confirmButtonText: 'Entendido'
    });
}

/**
 * Formatea un número como porcentaje
 */
function formatearPorcentaje(valor) {
    if (valor === null || valor === undefined || isNaN(valor)) {
        return '--';
    }
    return valor.toFixed(2) + '%';
}

/**
 * Formatea un número como moneda
 */
function formatearMoneda(valor) {
    if (valor === null || valor === undefined || isNaN(valor)) {
        return '--';
    }
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(valor);
}