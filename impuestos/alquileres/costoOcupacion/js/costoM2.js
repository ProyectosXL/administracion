/**
 * Módulo de Costo por Metro Cuadrado
 * Gestiona la carga y visualización de costos de ocupación por superficie
 */

let dataCostoM2 = [];
let promediosCostoM2 = {};
let tablaCostoM2Instance = null;

$(document).ready(function() {
    // Event listeners
    $('#btnConsultarCostoM2').on('click', cargarCostoM2);
    
    $('#btnExportarCostoM2').on('click', function() {
        if (tablaCostoM2Instance) {
            tablaCostoM2Instance.button('.buttons-excel').trigger();
        }
    });

    // Actualizar leyenda de período cuando cambien las fechas
    // Usar múltiples eventos para asegurar compatibilidad
    $('#costoM2FechaDesde, #costoM2FechaHasta').on('change input blur', actualizarLeyendaPeriodo);
    
    // Activar tab
    $('a[data-toggle="tab"][href="#costo-m2"]').on('shown.bs.tab', function() {
        // Ajustar columnas si la tabla ya existe
        if (tablaCostoM2Instance) {
            tablaCostoM2Instance.columns.adjust();
        }
    });
});

/**
 * Actualiza la leyenda del período seleccionado
 */
function actualizarLeyendaPeriodo() {
    const fechaDesde = $('#costoM2FechaDesde').val();
    const fechaHasta = $('#costoM2FechaHasta').val();
    
    console.log('Actualizando leyenda - Desde:', fechaDesde, 'Hasta:', fechaHasta); // Debug
    
    if (fechaDesde && fechaHasta) {
        try {
            // Parsear fechas
            const desde = new Date(fechaDesde + 'T00:00:00');
            const hasta = new Date(fechaHasta + 'T00:00:00');
            
            // Formatear manualmente para asegurar compatibilidad
            const desdeArr = fechaDesde.split('-');
            const hastaArr = fechaHasta.split('-');
            
            const desdeStr = desdeArr[2] + '/' + desdeArr[1] + '/' + desdeArr[0];
            const hastaStr = hastaArr[2] + '/' + hastaArr[1] + '/' + hastaArr[0];
            
            const textoLeyenda = desdeStr + ' al ' + hastaStr;
            console.log('Nueva leyenda:', textoLeyenda); // Debug
            
            $('#leyendaPeriodoCostoM2').text(textoLeyenda);
        } catch (e) {
            console.error('Error al formatear fechas:', e);
        }
    }
}

/**
 * Carga los datos de costo por M2
 */
function cargarCostoM2() {
    const fechaDesde = $('#costoM2FechaDesde').val();
    const fechaHasta = $('#costoM2FechaHasta').val();

    if (!fechaDesde || !fechaHasta) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Por favor, seleccione ambas fechas'
        });
        return;
    }

    // Validar que fecha desde sea menor que fecha hasta
    if (new Date(fechaDesde) > new Date(fechaHasta)) {
        Swal.fire({
            icon: 'error',
            title: 'Error en fechas',
            text: 'La fecha desde debe ser menor o igual a la fecha hasta'
        });
        return;
    }

    // Limpiar datos anteriores ANTES de hacer la petición
    dataCostoM2 = [];
    promediosCostoM2 = {};
    
    // Destruir tabla si existe
    if (tablaCostoM2Instance) {
        tablaCostoM2Instance.destroy();
        tablaCostoM2Instance = null;
    }
    
    // Limpiar el tbody
    $('#tbodyCostoM2').empty();

    // Mostrar loading
    $('#loadingCostoM2').show();
    $('#cardTablaCostoM2').hide();
    $('#costoM2Promedios').hide();
    $('#leyendaCostoM2').hide();

    $.ajax({
        url: 'Controller/costoM2Controller.php',
        method: 'POST',
        cache: false, // Deshabilitar caché de jQuery
        headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache',
            'Expires': '0'
        },
        data: {
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta,
            _nocache: Math.random().toString(36).substring(7) + Date.now() // ID único para evitar caché
        },
        dataType: 'json',
        success: function(response) {
            $('#loadingCostoM2').hide();
            
            if (response.success) {
                dataCostoM2 = response.data;
                promediosCostoM2 = response.promedios;
                
                renderizarPromedios();
                renderizarTablaCostoM2();
                
                $('#cardTablaCostoM2').show();
                $('#costoM2Promedios').show();
                $('#leyendaCostoM2').show();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.error || 'Error al cargar los datos'
                });
            }
        },
        error: function(xhr, status, error) {
            $('#loadingCostoM2').hide();
            console.error('Error al cargar datos:', error);
            console.error('Response:', xhr.responseText);
            
            let errorMsg = 'Error al cargar los datos';
            
            // Try to parse JSON error response
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.error) {
                    errorMsg = response.error;
                    if (response.trace) {
                        console.error('Stack trace:', response.trace);
                    }
                    if (response.file && response.line) {
                        console.error('Error location:', response.file, 'line', response.line);
                    }
                }
            } catch (e) {
                // If response is not JSON, show first 500 chars
                console.error('Raw response (not JSON):', xhr.responseText.substring(0, 500));
                errorMsg = 'Error en el servidor. Revise la consola para más detalles.';
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg
            });
        }
    });
}

/**
 * Renderiza los indicadores de promedios
 */
function renderizarPromedios() {
    $('#totalSuperficie').text(formatearNumero(promediosCostoM2.total_superficie, 2) + ' M²');
    $('#promedioExpensasM2').text('$' + formatearNumero(promediosCostoM2.expensas_m2, 2));
    $('#promedioAlquilerM2').text('$' + formatearNumero(promediosCostoM2.alquiler_m2, 2));
    $('#countConSuperficie').text(promediosCostoM2.count_con_superficie);
}

/**
 * Renderiza la tabla de costo por M2
 */
function renderizarTablaCostoM2() {
    const tbody = $('#tbodyCostoM2');
    tbody.empty();

    dataCostoM2.forEach(function(item) {
        const tr = $('<tr>');
        
        // Sucursal
        tr.append($('<td>').text(item.nro_sucursal));
        
        // Nombre
        tr.append($('<td>').text(item.nombre));
        
        // Superficie
        const tdSuperficie = $('<td class="text-right">');
        if (item.sin_superficie) {
            tdSuperficie.html('<span class="text-muted">Sin datos</span>');
        } else {
            tdSuperficie.text(formatearNumero(item.superficie, 2));
        }
        tr.append(tdSuperficie);
        
        // Expensas Totales
        const tdExpensas = $('<td class="text-right">');
        tdExpensas.text('$' + formatearNumero(item.expensas, 0));
        tr.append(tdExpensas);
        
        // Expensas/M²
        const tdExpensasM2 = $('<td class="text-right">');
        if (item.sin_superficie) {
            tdExpensasM2.html('<span class="text-muted">-</span>');
        } else {
            const colorExpensasM2 = obtenerColorSemaforo(item.expensas_m2, promediosCostoM2.expensas_m2);
            tdExpensasM2.html(`<span class="badge badge-${colorExpensasM2}">$${formatearNumero(item.expensas_m2, 2)}</span>`);
        }
        tr.append(tdExpensasM2);
        
        // Alquiler Total
        const tdAlquiler = $('<td class="text-right">');
        tdAlquiler.text('$' + formatearNumero(item.alquiler, 0));
        tr.append(tdAlquiler);
        
        // Alquiler/M²
        const tdAlquilerM2 = $('<td class="text-right">');
        if (item.sin_superficie) {
            tdAlquilerM2.html('<span class="text-muted">-</span>');
        } else {
            const colorAlquilerM2 = obtenerColorSemaforo(item.alquiler_m2, promediosCostoM2.alquiler_m2);
            tdAlquilerM2.html(`<span class="badge badge-${colorAlquilerM2}">$${formatearNumero(item.alquiler_m2, 2)}</span>`);
        }
        tr.append(tdAlquilerM2);
        
        tbody.append(tr);
    });

    // Actualizar footer
    $('#footerSuperficie').text(formatearNumero(promediosCostoM2.total_superficie, 2) + ' M²');
    $('#footerExpensas').text('$' + formatearNumero(promediosCostoM2.total_expensas, 0));
    $('#footerExpensasM2').text('$' + formatearNumero(promediosCostoM2.expensas_m2, 2));
    $('#footerAlquiler').text('$' + formatearNumero(promediosCostoM2.total_alquiler, 0));
    $('#footerAlquilerM2').text('$' + formatearNumero(promediosCostoM2.alquiler_m2, 2));

    // Si ya existe DataTable, solo actualizar datos sin destruir
    if (tablaCostoM2Instance) {
        tablaCostoM2Instance.destroy();
    }

    // Reinicializar DataTable
    tablaCostoM2Instance = $('#tablaCostoM2').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        pageLength: 25,
        order: [[1, 'asc']], // Ordenar por nombre
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                className: 'btn btn-success btn-sm d-none',
                filename: function() {
                    const fechaDesde = $('#costoM2FechaDesde').val();
                    const fechaHasta = $('#costoM2FechaHasta').val();
                    return `Costo_M2_${fechaDesde}_${fechaHasta}`;
                },
                title: 'Costo de Ocupación por Metro Cuadrado',
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            // Remover HTML y extraer solo el valor numérico
                            if ($(node).find('.badge').length > 0) {
                                return $(node).find('.badge').text().trim();
                            }
                            return data;
                        }
                    }
                }
            }
        ],
        columnDefs: [
            { targets: [2, 3, 4, 5, 6], className: 'text-right' }
        ]
    });
}

/**
 * Obtiene el color del semáforo según el valor comparado con el promedio
 * Verde: por debajo del promedio (mejor)
 * Amarillo: ±10% del promedio
 * Rojo: por encima del promedio (peor)
 * 
 * @param {number} valor - Valor a comparar
 * @param {number} promedio - Promedio de referencia
 * @returns {string} Color del badge de Bootstrap
 */
function obtenerColorSemaforo(valor, promedio) {
    if (promedio === 0) {
        return 'secondary';
    }

    const diferenciaPorcentaje = ((valor - promedio) / promedio) * 100;

    if (diferenciaPorcentaje < -10) {
        return 'success'; // Mucho mejor que el promedio
    } else if (diferenciaPorcentaje >= -10 && diferenciaPorcentaje <= 10) {
        return 'warning'; // Dentro del rango aceptable
    } else {
        return 'danger'; // Por encima del promedio
    }
}

/**
 * Formatea un número con separador de miles y decimales
 * @param {number} numero - Número a formatear
 * @param {number} decimales - Cantidad de decimales
 * @returns {string} Número formateado
 */
function formatearNumero(numero, decimales = 0) {
    if (numero === null || numero === undefined || isNaN(numero)) {
        return '0';
    }
    
    return parseFloat(numero).toLocaleString('es-AR', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    });
}
