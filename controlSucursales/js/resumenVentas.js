/* ===================================
   RESUMEN DE VENTAS - JAVASCRIPT (SIN DATATABLES)
   =================================== */

let sortColumn = 0;
let sortDirection = 'asc';

/**
 * Inicialización al cargar el documento
 */
$(document).ready(function() {
    console.log('Iniciando aplicación de resumen de ventas...');
    
    // Verificar que la tabla existe
    if ($('#tableVentas').length === 0) {
        console.error('Tabla #tableVentas no encontrada');
        return;
    }
    
    // Configurar búsqueda PRIMERO
    setupSearch();
    
    // Configurar ordenamiento
    setupSorting();
    
    // Calcular totales después de todo
    setTimeout(function() {
        calcularTotales();
    }, 500);
    
    // Configurar exportación a Excel
    setupExcelExport();
    
    // Ajustar estilos del toggle
    adjustToggleStyles();
    
    // Configurar spinner de búsqueda
    setupSearchSpinner();
});

/**
 * Configura la funcionalidad de búsqueda
 */
function setupSearch() {
    console.log('Configurando búsqueda...');
    
    // Agregar campo de búsqueda si no existe
    if ($('.dataTables_filter').length === 0 && $('#searchInput').length === 0) {
        const searchHtml = `
            <div class="dataTables_filter" style="margin: 20px 0; text-align: right;">
                <label style="font-weight: 500;">
                    Buscar: 
                    <input type="search" id="searchInput" class="form-control" style="display: inline-block; width: 300px; margin-left: 10px;" placeholder="Buscar en la tabla...">
                </label>
            </div>
        `;
        $('.table-wrapper').before(searchHtml);
        console.log('Campo de búsqueda agregado');
    }
    
    // Evento de búsqueda
    $(document).on('keyup', '#searchInput', function() {
        const searchTerm = $(this).val().toLowerCase();
        console.log('Buscando:', searchTerm);
        
        let visibleCount = 0;
        $('#tableVentas tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            if (rowText.indexOf(searchTerm) === -1) {
                $(this).hide();
            } else {
                $(this).show();
                visibleCount++;
            }
        });
        
        console.log('Filas visibles después de búsqueda:', visibleCount);
        
        // Recalcular totales con filas filtradas
        calcularTotales();
    });
}

/**
 * Configura el ordenamiento por columnas
 */
function setupSorting() {
    console.log('Configurando ordenamiento...');
    
    $('#tableVentas thead th').each(function(index) {
        $(this).css({
            'cursor': 'pointer',
            'position': 'relative',
            'user-select': 'none'
        });
        
        $(this).on('click', function() {
            sortTable(index);
        });
    });
    
    console.log('Ordenamiento configurado para', $('#tableVentas thead th').length, 'columnas');
}

/**
 * Ordena la tabla por la columna especificada
 */
function sortTable(columnIndex) {
    console.log('Ordenando por columna', columnIndex);
    
    const table = $('#tableVentas tbody');
    const rows = table.find('tr').toArray();
    
    // Cambiar dirección si es la misma columna
    if (sortColumn === columnIndex) {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn = columnIndex;
        sortDirection = 'asc';
    }
    
    console.log('Dirección:', sortDirection);
    
    // Ordenar filas
    rows.sort(function(a, b) {
        let aVal, bVal;
        
        // Obtener valores
        const aCells = $(a).find('td');
        const bCells = $(b).find('td');
        
        if (columnIndex >= aCells.length || columnIndex >= bCells.length) {
            return 0;
        }
        
        // Si la celda tiene data-value, usarlo, sino usar el texto
        const aCell = $(aCells[columnIndex]);
        const bCell = $(bCells[columnIndex]);
        
        aVal = aCell.attr('data-value') || aCell.text();
        bVal = bCell.attr('data-value') || bCell.text();
        
        // Convertir a número si es posible
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // Comparación de texto
        if (sortDirection === 'asc') {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    });
    
    // Actualizar indicadores visuales
    $('#tableVentas thead th').removeClass('sorting_asc sorting_desc');
    const $th = $('#tableVentas thead th').eq(columnIndex);
    $th.addClass(sortDirection === 'asc' ? 'sorting_asc' : 'sorting_desc');
    
    console.log('Clase agregada a columna', columnIndex, ':', sortDirection === 'asc' ? 'sorting_asc' : 'sorting_desc');
    console.log('TH tiene clases:', $th.attr('class'));
    
    // Reordenar DOM
    table.empty();
    $.each(rows, function(index, row) {
        table.append(row);
    });
    
    console.log('Tabla reordenada');
    
    // Recalcular totales
    calcularTotales();
}

/**
 * Calcula los totales de todas las columnas numéricas
 * Solo suma las filas visibles después de aplicar filtros
 */
function calcularTotales() {
    console.log('=== CALCULANDO TOTALES ===');
    
    let totales = {
        tarjeta: 0,
        cuentaDni: 0,
        totalTarjetas: 0,
        mercadoPagoQr: 0,
        mercadoPago: 0,
        modoQr: 0,
        promoBanco: 0,
        efectivo: 0,
        bonusShopping: 0,
        dolares: 0,
        euros: 0,
        totalVentas: 0
    };

    // Obtener filas visibles del tbody
    let filas = $('#tableVentas tbody tr:visible');
    
    console.log('Filas visibles para sumar:', filas.length);
    
    // Iterar sobre cada fila visible
    filas.each(function(index) {
        const $row = $(this);
        
        // Obtener el elemento de total ventas para debug
        const totalVentasEl = $row.find('.tdTotalVentas');
        const totalVentasValue = totalVentasEl.attr('data-value');
        
        // Log de las primeras 3 filas para debug
        if (index < 3) {
            console.log(`Fila ${index}:`);
            console.log('  - Elemento encontrado:', totalVentasEl.length);
            console.log('  - data-value:', totalVentasValue);
            console.log('  - Texto visible:', totalVentasEl.text());
            console.log('  - Clases:', totalVentasEl.attr('class'));
        }
        
        // Usar data-value para obtener los valores numéricos
        totales.tarjeta += parseFloat($row.find('.tdTarjeta').attr('data-value') || 0);
        totales.cuentaDni += parseFloat($row.find('.tdCuentaDni').attr('data-value') || 0);
        totales.totalTarjetas += parseFloat($row.find('.tdTotalTarjetas').attr('data-value') || 0);
        totales.mercadoPagoQr += parseFloat($row.find('.tdMercadoPagoQr').attr('data-value') || 0);
        totales.mercadoPago += parseFloat($row.find('.tdMercadoPago').attr('data-value') || 0);
        totales.modoQr += parseFloat($row.find('.tdModoQr').attr('data-value') || 0);
        totales.promoBanco += parseFloat($row.find('.tdPromoBanco').attr('data-value') || 0);
        totales.efectivo += parseFloat($row.find('.tdEfectivo').attr('data-value') || 0);
        totales.bonusShopping += parseFloat($row.find('.tdBonusShopping').attr('data-value') || 0);
        totales.dolares += parseFloat($row.find('.tdDolares').attr('data-value') || 0);
        totales.euros += parseFloat($row.find('.tdEuros').attr('data-value') || 0);
        totales.totalVentas += parseFloat(totalVentasValue || 0);
    });

    console.log('Totales calculados:', totales);
    console.log('Total Ventas final:', totales.totalVentas);

    // Actualizar los totales en el footer
    $('#totalTarjeta').html(formatearMoneda(totales.tarjeta));
    $('#totalCuentaDni').html(formatearMoneda(totales.cuentaDni));
    $('#totalTarjetas').html(formatearMoneda(totales.totalTarjetas));
    $('#totalMercadoPagoQr').html(formatearMoneda(totales.mercadoPagoQr));
    $('#totalMercadoPago').html(formatearMoneda(totales.mercadoPago));
    $('#totalModoQr').html(formatearMoneda(totales.modoQr));
    $('#totalPromoBanco').html(formatearMonedaNegativa(totales.promoBanco));
    $('#totalEfectivo').html(formatearMoneda(totales.efectivo));
    $('#totalBonusShopping').html(formatearMoneda(totales.bonusShopping));
    $('#totalDolares').html(formatearMoneda(totales.dolares));
    $('#totalEuros').html(formatearMoneda(totales.euros));
    $('#totalVentas').html(formatearMoneda(totales.totalVentas));
    
    console.log('=== TOTALES ACTUALIZADOS EN DOM ===');
}

/**
 * Formatea un valor como moneda
 */
function formatearMoneda(valor) {
    if (isNaN(valor)) valor = 0;
    return '$' + formatNumber(Math.abs(valor));
}

/**
 * Formatea un valor negativo entre paréntesis y en rojo
 */
function formatearMonedaNegativa(valor) {
    if (isNaN(valor)) valor = 0;
    
    if (valor < 0) {
        return '<span style="color: #ef4444;">($' + formatNumber(Math.abs(valor)) + ')</span>';
    } else {
        return '$' + formatNumber(valor);
    }
}

/**
 * Formatea un número con separador de miles y decimales
 * @param {number} number - Número a formatear
 * @returns {string} - Número formateado
 */
function formatNumber(number) {
    if (isNaN(number)) return '0.00';
    
    return number.toLocaleString('de-DE', {
        style: 'decimal',
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
    });
}

/**
 * Configura la exportación a Excel
 */
function setupExcelExport() {
    $("#btnExport").click(function(e) {
        e.preventDefault();
        
        // Obtener las fechas del formulario para el nombre del archivo
        const desde = $('input[name="desde"]').val() || 'sin_fecha';
        const hasta = $('input[name="hasta"]').val() || 'sin_fecha';
        
        $("#tableVentas").table2excel({
            exclude: ".noExport",
            name: "Ventas por medio de pago",
            filename: `Ventas_por_medio_de_pago_${desde}_${hasta}`,
            fileext: ".xlsx"
        });
    });
}

/**
 * Ajusta los estilos del toggle switch
 */
function adjustToggleStyles() {
    if (document.querySelector(".toggle")) {
        const toggle = document.querySelector(".toggle");
        const toggleOn = document.querySelector(".toggle-on");
        const toggleOff = document.querySelector(".toggle-off");
        const toggleBtn = document.querySelector('.toggle.btn.btn-primary');
        
        if (toggle) toggle.style.width = "50px";
        if (toggleOn) toggleOn.style.fontSize = "0";
        if (toggleOff) toggleOff.style.fontSize = "0";
        if (toggleBtn) toggleBtn.style.height = '42px';
    }
}

/**
 * Configura el spinner de carga para el botón de búsqueda
 */
function setupSearchSpinner() {
    $('#search').on('click', function() {
        $("#boxLoading").addClass("loading");
    });
}

/**
 * Función para cambiar el entorno (Argentina/Uruguay)
 */
function cambiarEntornoCustom(container) {
    const flags = $(container).find('.toggle-flag');
    const activeFlag = $(container).find('.toggle-flag.active');
    
    // Obtener el entorno opuesto
    let nuevoEntorno = 0;
    
    if (activeFlag.data('entorno') === 'central') {
        nuevoEntorno = 1; // Cambiar a Uruguay
    } else {
        nuevoEntorno = 0; // Cambiar a Argentina
    }
    
    console.log('Cambiando entorno a:', nuevoEntorno === 0 ? 'Argentina' : 'Uruguay');
    
    // Mostrar spinner
    $("#boxLoading").addClass("loading");
    
    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data: { entorno: nuevoEntorno },
        success: function (data) {
            console.log('Entorno cambiado exitosamente');
            location.reload();
        },
        error: function(xhr, status, error) {
            console.error('Error al cambiar entorno:', error);
            $("#boxLoading").removeClass("loading");
            alert('Error al cambiar el entorno. Por favor intente nuevamente.');
        }
    });
}

/**
 * Función heredada de main.js para cambiar el entorno (compatibilidad)
 * @param {HTMLElement} t - Elemento toggle
 */
const cambiarEntorno = (t) => {
    let entorno = 0;

    if(t.getAttribute("data-off") == "ARG" ){
        entorno = 0;
    } else {
        entorno = 1;
    }

    $.ajax({
        url: "Controller/cambiarEntorno.php",
        method: "POST",
        data : {entorno: entorno},
        success: function (data) {
            location.reload();
        }
    });
}
